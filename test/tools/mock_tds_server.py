#!/usr/bin/env python3
# Copyright (c) Microsoft Corporation. All rights reserved.
# Licensed under the MIT License.
"""
Minimal Python TDS mock server for testing SQL driver connectivity.

Handles PreLogin -> TLS 7.4 upgrade -> Login7 (SQL auth + FedAuth) -> query
execution with NVarChar results. Requires only Python 3.6+ stdlib.

Usage:
    python3 mock_tds_server.py --port 0 --print-port --cert server.pem --key server.key
    python3 mock_tds_server.py --port 1433 --cert server.pem --key server.key --log-level DEBUG
"""

import argparse
import hashlib
import logging
import os
import re
import socket
import ssl
import struct
import subprocess
import sys
import tempfile
import threading

# ---------------------------------------------------------------------------
# Constants
# ---------------------------------------------------------------------------

TDS_HEADER_SIZE = 8
TDS_MAX_PACKET_SIZE = 4096

# Packet types
PKT_SQL_BATCH = 0x01
PKT_RPC_REQUEST = 0x03
PKT_TABULAR_RESULT = 0x04
PKT_ATTENTION = 0x06
PKT_LOGIN7 = 0x10
PKT_PRELOGIN = 0x12

# Packet status flags
STATUS_NORMAL = 0x00
STATUS_EOM = 0x01

# Token types
TK_COLMETADATA = 0x81
TK_ROW = 0xD1
TK_DONE = 0xFD
TK_DONEPROC = 0xFE
TK_DONEINPROC = 0xFF
TK_ENVCHANGE = 0xE3
TK_LOGINACK = 0xAD
TK_ERROR = 0xAA
TK_INFO = 0xAB
TK_FEATUREEXTACK = 0xAE

# PreLogin option tokens
PL_VERSION = 0x00
PL_ENCRYPTION = 0x01
PL_FEDAUTH = 0x06
PL_TERMINATOR = 0xFF

# Encryption values
ENCRYPT_OFF = 0x00
ENCRYPT_ON = 0x01
ENCRYPT_NOT_SUP = 0x02

# FedAuth constants
FEATURE_EXT_SESSIONRECOVERY = 0x01
FEATURE_EXT_FEDAUTH = 0x02
FEATURE_EXT_TERMINATOR = 0xFF
FEDAUTH_LIBRARY_SECURITYTOKEN = 0x01

# TLS record types (for detecting raw TLS after TDS-wrapped handshake)
TLS_CHANGE_CIPHER_SPEC = 0x14
TLS_ALERT = 0x15
TLS_HANDSHAKE = 0x16
TLS_APPLICATION_DATA = 0x17

# TDS data type codes
TDS_INTN = 0x26
TDS_NVARCHAR = 0xE7

log = logging.getLogger("mock_tds")


# ---------------------------------------------------------------------------
# TDS Packet I/O
# ---------------------------------------------------------------------------

def recv_exact(sock, n):
    """Read exactly n bytes from socket, raising on short read."""
    buf = bytearray()
    while len(buf) < n:
        chunk = sock.recv(n - len(buf))
        if not chunk:
            if not buf:
                return None  # clean close
            raise ConnectionError(f"Connection closed mid-read (got {len(buf)}/{n})")
        buf.extend(chunk)
    return bytes(buf)


def read_tds_packet(sock):
    """Read a complete TDS message, reassembling multi-packet messages.

    Returns (packet_type, payload_bytes) or (None, None) on clean close.
    """
    payload = bytearray()
    packet_type = None

    while True:
        header_bytes = recv_exact(sock, TDS_HEADER_SIZE)
        if header_bytes is None:
            return None, None

        pkt_type, status, length, spid, pkt_id, window = struct.unpack(
            ">BBHHBB", header_bytes
        )
        if packet_type is None:
            packet_type = pkt_type

        body_len = length - TDS_HEADER_SIZE
        if body_len > 0:
            body = recv_exact(sock, body_len)
            if body is None:
                raise ConnectionError("Connection closed mid-packet")
            payload.extend(body)

        log.debug(
            "  << pkt_type=0x%02X status=0x%02X length=%d pkt_id=%d",
            pkt_type, status, length, pkt_id,
        )

        if status & STATUS_EOM:
            break

    return packet_type, bytes(payload)


def write_tds_packet(sock, packet_type, payload):
    """Write a single TDS packet with EOM status."""
    total_len = TDS_HEADER_SIZE + len(payload)
    header = struct.pack(">BBHHBB", packet_type, STATUS_EOM, total_len, 0, 1, 0)
    sock.sendall(header + payload)
    log.debug(
        "  >> pkt_type=0x%02X length=%d", packet_type, total_len,
    )


# ---------------------------------------------------------------------------
# TDS-TLS Wrapper for TDS 7.4 mid-stream TLS upgrade
#
# Uses ssl.MemoryBIO + ssl.SSLObject for the handshake phase so we can
# intercept bytes and add/strip TDS packet headers.  After the handshake
# the client switches to sending raw TLS records, so we enter passthrough
# mode and let the SSLObject read/write directly to the raw socket.
# ---------------------------------------------------------------------------

def _is_tls_record_type(b):
    return TLS_CHANGE_CIPHER_SPEC <= b <= TLS_APPLICATION_DATA


def _recv_tds_or_raw_tls(sock):
    """Read one chunk from the network: either a TDS-wrapped payload or a raw
    TLS record.  Returns (payload_bytes, is_raw_tls)."""
    first = recv_exact(sock, 1)
    if first is None:
        return None, False

    if _is_tls_record_type(first[0]):
        # Raw TLS record: type(1) version(2) length(2) body(length)
        rest_hdr = recv_exact(sock, 4)
        if rest_hdr is None:
            return first, True
        rec_len = struct.unpack(">H", rest_hdr[2:4])[0]
        body = recv_exact(sock, rec_len) or b""
        return first + rest_hdr + body, True

    # TDS-wrapped: read rest of 8-byte header then body
    rest_hdr = recv_exact(sock, TDS_HEADER_SIZE - 1)
    if rest_hdr is None:
        return None, False
    header_bytes = first + rest_hdr
    _, status, length, _, _, _ = struct.unpack(">BBHHBB", header_bytes)
    body_len = length - TDS_HEADER_SIZE
    body = recv_exact(sock, body_len) if body_len > 0 else b""
    log.debug(
        "TLS wrapper recv: stripped TDS 0x%02X header, payload %d bytes",
        first[0], body_len,
    )
    return body, False


class TlsOverTdsSocket:
    """Presents a socket-like read/write interface over TLS-inside-TDS.

    The TLS handshake is performed using MemoryBIO so we can wrap/unwrap
    TDS headers.  After the handshake completes the client sends raw TLS
    records, so we switch to feeding those directly into the BIOs.

    After do_tds_tls_upgrade() succeeds, callers use recv/sendall on this
    object exactly like a normal socket.
    """

    def __init__(self, raw_sock, ssl_object, incoming_bio, outgoing_bio):
        self._raw = raw_sock
        self._ssl = ssl_object
        self._in_bio = incoming_bio
        self._out_bio = outgoing_bio

    # -- socket-like API used by read_tds_packet / write_tds_packet -----------

    def recv(self, bufsize):
        while True:
            try:
                return self._ssl.read(bufsize)
            except ssl.SSLWantReadError:
                pass

            # Feed more data from network into the incoming BIO
            data, _raw = _recv_tds_or_raw_tls(self._raw)
            if data is None:
                return b""
            self._in_bio.write(data)

    def sendall(self, data):
        self._ssl.write(data)
        self._flush_outgoing()

    def send(self, data):
        self.sendall(data)
        return len(data)

    def close(self):
        try:
            self._raw.close()
        except OSError:
            pass

    def settimeout(self, t):
        self._raw.settimeout(t)

    # -- internal -------------------------------------------------------------

    def _flush_outgoing(self):
        """Write any pending outgoing BIO data to the raw socket."""
        data = self._out_bio.read()
        if data:
            self._raw.sendall(data)


def do_tds_tls_upgrade(raw_sock, ssl_context):
    """Perform TDS 7.4-style TLS upgrade using MemoryBIO.

    During the handshake:
      - Client sends TLS records wrapped in TDS PreLogin (0x12) packets
      - Server sends TLS records wrapped in TDS TabularResult (0x04) packets
      - At some point the client switches to sending raw TLS records
    After the handshake we return a TlsOverTdsSocket that transparently
    decrypts/encrypts.
    """
    incoming_bio = ssl.MemoryBIO()
    outgoing_bio = ssl.MemoryBIO()
    ssl_obj = ssl_context.wrap_bio(
        incoming_bio, outgoing_bio, server_side=True,
    )

    # Drive the handshake
    handshake_done = False
    passthrough = False

    while not handshake_done:
        try:
            ssl_obj.do_handshake()
            handshake_done = True
        except ssl.SSLWantReadError:
            pass

        # Flush any outgoing TLS data (server hello, etc.)
        out_data = outgoing_bio.read()
        if out_data:
            if passthrough:
                raw_sock.sendall(out_data)
            else:
                # Wrap in TDS TabularResult packet
                total_len = TDS_HEADER_SIZE + len(out_data)
                hdr = struct.pack(
                    ">BBHHBB", PKT_TABULAR_RESULT, STATUS_EOM,
                    total_len, 0, 1, 0,
                )
                raw_sock.sendall(hdr + out_data)
                log.debug("TLS handshake: sent %d bytes in TDS 0x04", len(out_data))

        if handshake_done:
            break

        # Read next chunk from client
        chunk, is_raw = _recv_tds_or_raw_tls(raw_sock)
        if chunk is None:
            raise ConnectionError("Connection closed during TLS handshake")
        if is_raw and not passthrough:
            log.debug("TLS handshake: switching to passthrough")
            passthrough = True
        incoming_bio.write(chunk)

    log.debug("TDS-TLS handshake complete")
    return TlsOverTdsSocket(raw_sock, ssl_obj, incoming_bio, outgoing_bio)


# ---------------------------------------------------------------------------
# PreLogin
# ---------------------------------------------------------------------------

def build_prelogin_response(supports_encryption=True, supports_fedauth=True):
    """Build a PreLogin response with VERSION + ENCRYPTION + optional FEDAUTH."""
    buf = bytearray()

    if supports_fedauth:
        # Directory: VERSION(5) + ENCRYPTION(5) + FEDAUTH(5) + TERMINATOR(1) = 16
        # VERSION data at 16, len 6
        # ENCRYPTION at 22, len 1
        # FEDAUTH at 23, len 1
        buf.append(PL_VERSION)
        buf.extend(struct.pack(">HH", 16, 6))
        buf.append(PL_ENCRYPTION)
        buf.extend(struct.pack(">HH", 22, 1))
        buf.append(PL_FEDAUTH)
        buf.extend(struct.pack(">HH", 23, 1))
        buf.append(PL_TERMINATOR)
        # VERSION: 16.0.0.0
        buf.extend(b"\x10\x00\x00\x00\x00\x00")
        # ENCRYPTION
        buf.append(ENCRYPT_ON if supports_encryption else ENCRYPT_NOT_SUP)
        # FEDAUTH ON
        buf.append(0x01)
    else:
        # Directory: VERSION(5) + ENCRYPTION(5) + TERMINATOR(1) = 11
        # VERSION data at 11, len 6
        # ENCRYPTION at 17, len 1
        buf.append(PL_VERSION)
        buf.extend(struct.pack(">HH", 11, 6))
        buf.append(PL_ENCRYPTION)
        buf.extend(struct.pack(">HH", 17, 1))
        buf.append(PL_TERMINATOR)
        # VERSION: 16.0.0.0
        buf.extend(b"\x10\x00\x00\x00\x00\x00")
        # ENCRYPTION
        buf.append(ENCRYPT_ON if supports_encryption else ENCRYPT_NOT_SUP)

    return bytes(buf)


# ---------------------------------------------------------------------------
# Login7 Parser
# ---------------------------------------------------------------------------

class LoginInfo:
    __slots__ = (
        "username", "password", "server_name", "app_name", "hostname",
        "has_fedauth", "access_token", "fedauth_library",
    )

    def __init__(self):
        self.username = ""
        self.password = ""
        self.server_name = ""
        self.app_name = ""
        self.hostname = ""
        self.has_fedauth = False
        self.access_token = None  # str if present
        self.fedauth_library = 0


def _deobfuscate_password(raw_bytes):
    """Deobfuscate Login7 password: swap nibbles, XOR 0xA5."""
    out = bytearray(len(raw_bytes))
    for i, b in enumerate(raw_bytes):
        out[i] = (((b << 4) & 0xF0) | ((b >> 4) & 0x0F)) ^ 0xA5
    return bytes(out)


def _read_utf16le_field(data, offset_pos):
    """Read a UTF-16LE string field from Login7 offset table entry.

    offset_pos: position in data of the 4-byte offset/length pair.
    Returns (string, raw_bytes).
    """
    if offset_pos + 4 > len(data):
        return "", b""
    off = struct.unpack_from("<HH", data, offset_pos)
    char_offset, char_count = off[0], off[1]
    byte_offset = char_offset
    byte_count = char_count * 2
    if byte_offset + byte_count > len(data):
        return "", b""
    raw = data[byte_offset:byte_offset + byte_count]
    try:
        return raw.decode("utf-16-le"), raw
    except UnicodeDecodeError:
        return "", raw


def parse_login7(payload):
    """Parse Login7 packet body (after TDS header is stripped).

    Returns LoginInfo with username, password, server_name, has_fedauth,
    access_token, etc.
    """
    info = LoginInfo()

    if len(payload) < 58:
        log.warning("Login7 too short: %d bytes", len(payload))
        return info

    # Offset table entries (each 4 bytes: 2 offset + 2 length-in-chars)
    # Entry 0 @ 36: HostName
    # Entry 1 @ 40: UserName
    # Entry 2 @ 44: Password
    # Entry 3 @ 48: AppName
    # Entry 4 @ 52: ServerName
    # Entry 5 @ 56: Unused / FeatureExt ibExtension pointer offset

    info.hostname, _ = _read_utf16le_field(payload, 36)
    info.username, _ = _read_utf16le_field(payload, 40)

    # Password is XOR-obfuscated
    pw_off, pw_cnt = struct.unpack_from("<HH", payload, 44)
    pw_byte_count = pw_cnt * 2
    if pw_off + pw_byte_count <= len(payload):
        raw_pw = payload[pw_off:pw_off + pw_byte_count]
        deobfuscated = _deobfuscate_password(raw_pw)
        try:
            info.password = deobfuscated.decode("utf-16-le")
        except UnicodeDecodeError:
            info.password = ""

    info.app_name, _ = _read_utf16le_field(payload, 48)
    info.server_name, _ = _read_utf16le_field(payload, 52)

    # Check OptionFlags3 bit 4 for FeatureExt
    option_flags3 = payload[27]
    has_feature_ext = bool(option_flags3 & 0x10)

    log.debug(
        "Login7: user=%r host=%r server=%r app=%r flags3=0x%02X feat_ext=%s",
        info.username, info.hostname, info.server_name, info.app_name,
        option_flags3, has_feature_ext,
    )

    if not has_feature_ext:
        return info

    # FeatureExt offset table entry at bytes 56-59
    # Contains offset (2 bytes) to a DWORD pointer in the variable data
    if len(payload) < 60:
        return info

    feat_ext_ptr_offset = struct.unpack_from("<H", payload, 56)[0]

    if feat_ext_ptr_offset + 4 > len(payload):
        log.debug("FeatureExt ptr offset %d out of bounds", feat_ext_ptr_offset)
        return info

    # The DWORD at feat_ext_ptr_offset is the actual offset to the feature list
    feature_ext_offset = struct.unpack_from("<I", payload, feat_ext_ptr_offset)[0]

    log.debug(
        "FeatureExt: ptr_offset=%d -> feature_data_offset=%d",
        feat_ext_ptr_offset, feature_ext_offset,
    )

    if feature_ext_offset >= len(payload):
        return info

    # Walk feature entries
    i = feature_ext_offset
    while i < len(payload):
        feature_id = payload[i]
        if feature_id == FEATURE_EXT_TERMINATOR:
            break
        if i + 5 > len(payload):
            break

        feat_len = struct.unpack_from("<I", payload, i + 1)[0]

        if feature_id == FEATURE_EXT_FEDAUTH:
            info.has_fedauth = True
            feat_data = payload[i + 5:i + 5 + feat_len]

            if feat_data:
                options = feat_data[0]
                info.fedauth_library = (options >> 1) & 0x03

                if info.fedauth_library == FEDAUTH_LIBRARY_SECURITYTOKEN and len(feat_data) > 5:
                    token_len = struct.unpack_from("<I", feat_data, 1)[0]
                    if len(feat_data) >= 5 + token_len:
                        token_bytes = feat_data[5:5 + token_len]
                        try:
                            info.access_token = token_bytes.decode("utf-16-le")
                        except UnicodeDecodeError:
                            info.access_token = token_bytes.hex()
                        log.debug(
                            "FedAuth token: %d bytes (decoded %d chars)",
                            token_len, len(info.access_token),
                        )
            break

        i += 5 + feat_len

    return info


# ---------------------------------------------------------------------------
# Response Builders
# ---------------------------------------------------------------------------

def _utf16le(s):
    """Encode string as UTF-16LE bytes."""
    return s.encode("utf-16-le")


def build_login_ack():
    """Build LoginAck + 3 EnvChange tokens (collation, database, packetsize)."""
    buf = bytearray()

    # --- LoginAck token (0xAD) ---
    buf.append(TK_LOGINACK)
    length_pos = len(buf)
    buf.extend(b"\x00\x00")  # placeholder for token length (LE)

    buf.append(0x01)  # Interface: SQL Server
    buf.extend(struct.pack(">I", 0x74000004))  # TDS version 7.4 (big-endian)

    prog_name = "MockTdsServer"
    buf.append(len(prog_name))
    buf.extend(_utf16le(prog_name))

    buf.append(16)  # Major version
    buf.append(0)   # Minor version
    buf.extend(struct.pack("<H", 0))  # Build (LE)

    token_length = len(buf) - length_pos - 2
    struct.pack_into("<H", buf, length_pos, token_length)

    # --- EnvChange: Collation (type 7) ---
    buf.append(TK_ENVCHANGE)
    env_len_pos = len(buf)
    buf.extend(b"\x00\x00")  # placeholder
    buf.append(7)  # SQL_COLLATION
    buf.append(5)  # new value length
    buf.extend(struct.pack("<I", 0x09040000))  # LCID
    buf.append(0xD0)  # Flags
    buf.append(0)  # old value length
    env_len = len(buf) - env_len_pos - 2
    struct.pack_into("<H", buf, env_len_pos, env_len)

    # --- EnvChange: Database (type 1) ---
    buf.append(TK_ENVCHANGE)
    env_len_pos = len(buf)
    buf.extend(b"\x00\x00")
    buf.append(1)  # DATABASE
    db_name = "master"
    buf.append(len(db_name))
    buf.extend(_utf16le(db_name))
    buf.append(0)  # old value length
    env_len = len(buf) - env_len_pos - 2
    struct.pack_into("<H", buf, env_len_pos, env_len)

    # --- EnvChange: PacketSize (type 4) ---
    buf.append(TK_ENVCHANGE)
    env_len_pos = len(buf)
    buf.extend(b"\x00\x00")
    buf.append(4)  # PACKETSIZE
    ps = "4096"
    buf.append(len(ps))
    buf.extend(_utf16le(ps))
    buf.append(len(ps))
    buf.extend(_utf16le(ps))
    env_len = len(buf) - env_len_pos - 2
    struct.pack_into("<H", buf, env_len_pos, env_len)

    return bytes(buf)


def build_feature_ext_ack_fedauth(include_session_recovery=False):
    """Build FeatureExtAck token acknowledging FedAuth (and optionally session recovery)."""
    buf = bytearray()
    buf.append(TK_FEATUREEXTACK)
    # FedAuth acknowledgement
    buf.append(FEATURE_EXT_FEDAUTH)       # Feature ID
    buf.extend(struct.pack("<I", 0))       # Feature data length = 0
    # Session recovery acknowledgement (enables ConnectRetryCount-based recovery)
    if include_session_recovery:
        buf.append(FEATURE_EXT_SESSIONRECOVERY)  # Feature ID = 0x01
        buf.extend(struct.pack("<I", 0))          # Feature data length = 0
    buf.append(FEATURE_EXT_TERMINATOR)     # Terminator
    return bytes(buf)


def build_done_token(row_count=0, token_type=TK_DONE, status=0x0000):
    """Build DONE/DONEPROC/DONEINPROC token."""
    buf = bytearray()
    buf.append(token_type)
    buf.extend(struct.pack("<H", status))    # Status flags
    buf.extend(struct.pack("<H", 0x00C1))   # CurCmd: SELECT
    buf.extend(struct.pack("<Q", row_count))
    return bytes(buf)


def build_return_status(value=0):
    """Build a ReturnStatus token (0xAC) for RPC responses."""
    buf = bytearray()
    buf.append(0xAC)  # TK_RETURNSTATUS
    buf.extend(struct.pack("<i", value))
    return bytes(buf)


def build_error_response(message):
    """Build an Error token + DONE token."""
    buf = bytearray()
    buf.append(TK_ERROR)

    length_pos = len(buf)
    buf.extend(b"\x00\x00")  # placeholder for token length (BE u16 per spec)

    buf.extend(struct.pack("<I", 50000))  # error number
    buf.append(1)   # state
    buf.append(16)  # severity

    msg_utf16 = _utf16le(message)
    buf.extend(struct.pack("<H", len(message)))
    buf.extend(msg_utf16)

    buf.append(0)  # server name length
    buf.append(0)  # procedure name length
    buf.extend(struct.pack("<I", 1))  # line number

    token_length = len(buf) - length_pos - 2
    struct.pack_into(">H", buf, length_pos, token_length)

    buf.extend(build_done_token(0))
    return bytes(buf)


def build_int_result(column_name, value):
    """Build a result set with a single INT column."""
    buf = bytearray()

    # ColMetadata
    buf.append(TK_COLMETADATA)
    buf.extend(struct.pack("<H", 1))  # 1 column
    buf.extend(struct.pack("<I", 0))  # UserType
    buf.extend(struct.pack("<H", 0))  # Flags
    buf.append(TDS_INTN)              # IntN type
    buf.append(4)                     # max length = 4 (Int)
    name_utf16 = _utf16le(column_name)
    buf.append(len(column_name))
    buf.extend(name_utf16)

    # Row
    buf.append(TK_ROW)
    buf.append(4)  # length indicator
    buf.extend(struct.pack("<i", value))

    # Done
    buf.extend(build_done_token(1))
    return bytes(buf)


def build_nvarchar_result(column_name, value):
    """Build a result set with a single NVARCHAR column."""
    buf = bytearray()
    value_utf16 = _utf16le(value)

    # ColMetadata
    buf.append(TK_COLMETADATA)
    buf.extend(struct.pack("<H", 1))     # 1 column
    buf.extend(struct.pack("<I", 0))     # UserType
    buf.extend(struct.pack("<H", 0))     # Flags
    buf.append(TDS_NVARCHAR)             # NVarChar type 0xE7
    buf.extend(struct.pack("<H", 256))   # MaxLength (bytes)
    # Collation: SQL_Latin1_General_CP1_CI_AS
    buf.extend(struct.pack("<I", 0x09040000))  # LCID
    buf.append(0xD0)                            # Flags
    # Column name
    name_utf16 = _utf16le(column_name)
    buf.append(len(column_name))
    buf.extend(name_utf16)

    # Row
    buf.append(TK_ROW)
    buf.extend(struct.pack("<H", len(value_utf16)))
    buf.extend(value_utf16)

    # Done
    buf.extend(build_done_token(1))
    return bytes(buf)


# ---------------------------------------------------------------------------
# SQL Batch Parser
# ---------------------------------------------------------------------------

def parse_sql_batch(payload):
    """Parse SqlBatch packet body, skip ALL_HEADERS, return SQL string."""
    if len(payload) < 4:
        return ""
    all_headers_len = struct.unpack_from("<I", payload, 0)[0]
    if all_headers_len > len(payload):
        all_headers_len = 4  # fallback: assume no headers
    sql_bytes = payload[all_headers_len:]
    try:
        return sql_bytes.decode("utf-16-le").strip()
    except UnicodeDecodeError:
        return ""


def _skip_plp(payload, pos):
    """Skip PLP (Partially Length-Prefixed) data, return new position or -1."""
    if pos + 8 > len(payload):
        return -1
    total_len = struct.unpack_from("<Q", payload, pos)[0]
    pos += 8
    if total_len == 0xFFFFFFFFFFFFFFFF:  # PLP_NULL
        return pos
    # Read chunks until terminator (chunk_len == 0)
    while True:
        if pos + 4 > len(payload):
            return -1
        chunk_len = struct.unpack_from("<I", payload, pos)[0]
        pos += 4
        if chunk_len == 0:
            return pos
        pos += chunk_len


def _read_plp_nvarchar(payload, pos):
    """Read PLP-encoded NVARCHAR(MAX) data, return (string, new_pos) or ("", -1)."""
    if pos + 8 > len(payload):
        return "", -1
    total_len = struct.unpack_from("<Q", payload, pos)[0]
    pos += 8
    if total_len == 0xFFFFFFFFFFFFFFFF:  # PLP_NULL
        return "", pos
    data = bytearray()
    while True:
        if pos + 4 > len(payload):
            return "", -1
        chunk_len = struct.unpack_from("<I", payload, pos)[0]
        pos += 4
        if chunk_len == 0:
            break
        if pos + chunk_len > len(payload):
            return "", -1
        data.extend(payload[pos:pos + chunk_len])
        pos += chunk_len
    try:
        return data.decode("utf-16-le").strip(), pos
    except UnicodeDecodeError:
        return "", -1


def _rpc_skip_param(payload, pos):
    """Skip one RPC parameter, return new position or -1 on failure.

    Parameter layout: NameLength(1) + Name(variable) + StatusFlags(1) + TYPE_INFO + value
    """
    if pos >= len(payload):
        return -1
    name_len = payload[pos]
    pos += 1
    pos += name_len * 2  # skip name (UTF-16LE)
    if pos >= len(payload):
        return -1
    pos += 1  # status flags
    if pos >= len(payload):
        return -1
    type_id = payload[pos]
    pos += 1

    if type_id in (0xE7, 0xEF):  # NVARCHAR / NCHAR
        if pos + 7 > len(payload):
            return -1
        max_len = struct.unpack_from("<H", payload, pos)[0]
        pos += 2  # max_len
        pos += 5  # collation
        if max_len == 0xFFFF:  # NVARCHAR(MAX) – PLP encoding
            return _skip_plp(payload, pos)
        if pos + 2 > len(payload):
            return -1
        actual_len = struct.unpack_from("<H", payload, pos)[0]
        pos += 2
        if actual_len == 0xFFFF:
            return pos  # NULL value, no data bytes
        pos += actual_len
    elif type_id == 0x63:  # NTEXT
        # RPC params: TYPE_INFO(max_len 4 + collation 5) then 4-byte data_length
        if pos + 9 > len(payload):
            return -1
        pos += 4  # max_len (4 bytes for TEXT/NTEXT)
        pos += 5  # collation
        if pos + 4 > len(payload):
            return -1
        actual_len = struct.unpack_from("<i", payload, pos)[0]  # signed
        pos += 4
        if actual_len < 0:  # -1 (0xFFFFFFFF) = NULL
            return pos
        pos += actual_len
    elif type_id == 0x26:  # INTN
        if pos >= len(payload):
            return -1
        max_len = payload[pos]
        pos += 1
        if pos >= len(payload):
            return -1
        actual_len = payload[pos]
        pos += 1
        pos += actual_len
    elif type_id in (0x24,):  # GUIDTYPE
        if pos >= len(payload):
            return -1
        max_len = payload[pos]
        pos += 1
        if pos >= len(payload):
            return -1
        actual_len = payload[pos]
        pos += 1
        pos += actual_len
    elif type_id in (0x30, 0x34, 0x38, 0x3E, 0x3B, 0x3D):
        # Fixed-length types: TINYINT(1), INT4(4), INT8(8), FLT8(8), FLT4(4), MONEY(8)
        sizes = {0x30: 1, 0x34: 4, 0x38: 8, 0x3E: 8, 0x3B: 4, 0x3D: 8}
        pos += sizes.get(type_id, 4)
    else:
        log.debug("_rpc_skip_param: unknown type_id=0x%02X at pos=%d", type_id, pos - 1)
        return -1
    return pos


def _rpc_read_nvarchar(payload, pos):
    """Read one NVARCHAR RPC parameter value, return (string, new_pos) or ("", -1)."""
    if pos >= len(payload):
        return "", -1
    name_len = payload[pos]
    pos += 1
    pos += name_len * 2  # skip name
    if pos >= len(payload):
        return "", -1
    pos += 1  # status flags
    if pos >= len(payload):
        return "", -1
    type_id = payload[pos]
    pos += 1

    if type_id in (0xE7, 0xEF):
        if pos + 7 > len(payload):
            return "", -1
        max_len = struct.unpack_from("<H", payload, pos)[0]
        pos += 2  # max_len
        pos += 5  # collation
        if max_len == 0xFFFF:  # NVARCHAR(MAX) – PLP encoding
            return _read_plp_nvarchar(payload, pos)
        if pos + 2 > len(payload):
            return "", -1
        actual_len = struct.unpack_from("<H", payload, pos)[0]
        pos += 2
        if actual_len == 0xFFFF:
            return "", pos  # NULL
        if pos + actual_len > len(payload):
            return "", -1
        sql_bytes = payload[pos:pos + actual_len]
        pos += actual_len
    elif type_id == 0x63:  # NTEXT
        # RPC params: TYPE_INFO(max_len 4 + collation 5) then 4-byte data_length
        if pos + 9 > len(payload):
            return "", -1
        pos += 4  # max_len
        pos += 5  # collation
        if pos + 4 > len(payload):
            return "", -1
        actual_len = struct.unpack_from("<i", payload, pos)[0]  # signed
        pos += 4
        if actual_len < 0:  # NULL
            return "", pos
        if pos + actual_len > len(payload):
            return "", -1
        sql_bytes = payload[pos:pos + actual_len]
        pos += actual_len
    else:
        log.debug("_rpc_read_nvarchar: expected NVARCHAR/NCHAR/NTEXT, got 0x%02X", type_id)
        return "", -1
    try:
        return sql_bytes.decode("utf-16-le").strip(), pos
    except UnicodeDecodeError:
        return "", -1


def parse_rpc_request(payload):
    """Parse RPC Request packet body, extract the SQL text.

    Handles:
      - sp_executesql (ProcID=10): SQL is parameter 1
      - sp_prepexec (ProcID=13): SQL is parameter 3 (after handle + param defs)
    Returns the SQL string, or "" if not parseable.
    """
    if len(payload) < 4:
        return ""
    pos = 0
    # Skip ALL_HEADERS
    all_headers_len = struct.unpack_from("<I", payload, pos)[0]
    if all_headers_len < 4 or all_headers_len > len(payload):
        all_headers_len = 4
    pos = all_headers_len

    if pos + 2 > len(payload):
        return ""

    # Check ProcIDSwitch: 0xFFFF means special stored proc by ID
    proc_id_switch = struct.unpack_from("<H", payload, pos)[0]
    pos += 2
    if proc_id_switch == 0xFFFF:
        if pos + 2 > len(payload):
            return ""
        proc_id = struct.unpack_from("<H", payload, pos)[0]
        pos += 2
        if proc_id not in (10, 13):
            log.debug("RPC proc_id=%d not sp_executesql/sp_prepexec, ignoring", proc_id)
            return ""
    else:
        # Named procedure
        name_len = proc_id_switch
        pos += name_len * 2
        return ""

    # Option flags (2 bytes)
    if pos + 2 > len(payload):
        return ""
    pos += 2

    if proc_id == 10:
        # sp_executesql: param 1 = SQL (NVARCHAR)
        sql, _ = _rpc_read_nvarchar(payload, pos)
        return sql
    elif proc_id == 13:
        # sp_prepexec: param 1 = handle (INTN output), param 2 = param defs (NVARCHAR),
        #              param 3 = SQL (NVARCHAR)
        pos = _rpc_skip_param(payload, pos)  # skip handle
        if pos < 0:
            log.debug("sp_prepexec: failed to skip handle param")
            return ""
        pos = _rpc_skip_param(payload, pos)  # skip param defs
        if pos < 0:
            log.debug("sp_prepexec: failed to skip param defs")
            return ""
        sql, _ = _rpc_read_nvarchar(payload, pos)
        return sql

    return ""


# ---------------------------------------------------------------------------
# Connection Handler
# ---------------------------------------------------------------------------

class ConnectionHandler:
    """Handles a single client connection through the TDS protocol."""

    def __init__(self, sock, addr, server):
        self._raw_sock = sock
        self._sock = sock  # may be upgraded to TLS
        self._addr = addr
        self._server = server
        self._authenticated = False
        self._username = ""  # SQL auth username or token-derived name
        self._app_name = ""  # app_name from Login7 packet
        with server._spid_lock:
            self._spid = server._next_spid
            server._next_spid += 1
        self._log = logging.getLogger(f"mock_tds.conn.{addr[0]}:{addr[1]}")

    def handle(self):
        try:
            self._handle_prelogin()
            if self._server.ssl_context:
                self._handle_tls_upgrade()
            self._handle_login7()
            self._handle_queries()
        except (ConnectionError, OSError, ssl.SSLError) as e:
            self._log.debug("Connection ended: %s", e)
        except Exception:
            self._log.exception("Unexpected error handling connection")
        finally:
            try:
                self._sock.close()
            except OSError:
                pass

    def _handle_prelogin(self):
        pkt_type, payload = read_tds_packet(self._raw_sock)
        if pkt_type is None:
            raise ConnectionError("Client disconnected before PreLogin")
        if pkt_type != PKT_PRELOGIN:
            raise ConnectionError(f"Expected PreLogin (0x12), got 0x{pkt_type:02X}")

        self._log.debug("PreLogin received (%d bytes)", len(payload))

        # Parse client PreLogin options to echo back only what was requested
        client_wants_fedauth = False
        i = 0
        while i < len(payload):
            opt = payload[i]
            if opt == PL_TERMINATOR:
                break
            if opt == PL_FEDAUTH:
                client_wants_fedauth = True
            i += 5  # skip offset(2) + length(2) + token(1)

        response = build_prelogin_response(
            supports_encryption=self._server.ssl_context is not None,
            supports_fedauth=client_wants_fedauth,
        )
        write_tds_packet(self._raw_sock, PKT_TABULAR_RESULT, response)
        self._log.debug("PreLogin response sent (fedauth=%s)", client_wants_fedauth)

    def _handle_tls_upgrade(self):
        self._log.debug("Starting TDS 7.4 TLS upgrade")
        self._sock = do_tds_tls_upgrade(self._raw_sock, self._server.ssl_context)
        self._log.info("TLS upgrade complete")

    def _handle_login7(self):
        pkt_type, payload = read_tds_packet(self._sock)
        if pkt_type is None:
            raise ConnectionError("Client disconnected before Login7")
        if pkt_type != PKT_LOGIN7:
            raise ConnectionError(f"Expected Login7 (0x10), got 0x{pkt_type:02X}")

        login_info = parse_login7(payload)
        self._log.info(
            "Login7: user=%r fedauth=%s token=%s",
            login_info.username,
            login_info.has_fedauth,
            f"{len(login_info.access_token)} chars" if login_info.access_token else "none",
        )

        # Save app_name from Login7 for APP_NAME() queries
        self._app_name = login_info.app_name

        # Determine the authenticated identity
        if login_info.has_fedauth and login_info.access_token:
            self._username = self._server.resolve_token_username(login_info.access_token)
            self._log.info("FedAuth resolved username: %r", self._username)
        else:
            self._username = login_info.username or "guest"

        self._authenticated = True

        # Build response
        response = bytearray()
        response.extend(build_login_ack())
        if login_info.has_fedauth:
            response.extend(build_feature_ext_ack_fedauth(
                include_session_recovery=self._server.enable_session_recovery))
        response.extend(build_done_token(0))

        write_tds_packet(self._sock, PKT_TABULAR_RESULT, bytes(response))
        self._log.debug("LoginAck sent")

    def _handle_queries(self):
        while True:
            pkt_type, payload = read_tds_packet(self._sock)
            if pkt_type is None:
                self._log.debug("Client disconnected")
                break

            if pkt_type == PKT_SQL_BATCH:
                sql = parse_sql_batch(payload)
                self._log.info("SQL: %r", sql)
                response = self._execute_query(sql)
                write_tds_packet(self._sock, PKT_TABULAR_RESULT, response)

            elif pkt_type == PKT_ATTENTION:
                self._log.debug("Attention received")
                write_tds_packet(self._sock, PKT_TABULAR_RESULT, build_done_token(0))

            elif pkt_type == PKT_RPC_REQUEST:
                sql = parse_rpc_request(payload)
                if sql:
                    self._log.info("RPC SQL: %r", sql)
                    inner = self._execute_query(sql)
                    response = bytearray()
                    response.extend(inner)
                    response.extend(build_return_status(0))
                    response.extend(build_done_token(0, token_type=TK_DONEPROC))
                    write_tds_packet(self._sock, PKT_TABULAR_RESULT, bytes(response))
                else:
                    self._log.debug("RPC request (no SQL extracted, returning empty DONE)")
                    response = bytearray()
                    response.extend(build_return_status(0))
                    response.extend(build_done_token(0, token_type=TK_DONEPROC))
                    write_tds_packet(self._sock, PKT_TABULAR_RESULT, bytes(response))

            else:
                self._log.debug("Unknown packet type 0x%02X, ignoring", pkt_type)

    def _execute_query(self, sql):
        sql_upper = sql.upper().strip()

        # Check server query registry first
        for pattern, handler in self._server.query_handlers.items():
            if sql_upper == pattern.upper():
                return handler(self, sql)

        # Built-in handlers
        if "USER_NAME()" in sql_upper or "USER_NAME ()" in sql_upper:
            return build_nvarchar_result("", self._username)

        if sql_upper == "SELECT 1":
            return build_int_result("", 1)

        if sql_upper.startswith("SELECT @@VERSION"):
            return build_nvarchar_result(
                "",
                "Microsoft SQL Server 2022 (RTM) - 16.0.1000.6 - MockTdsServer",
            )

        if sql_upper.startswith("SELECT @@SPID"):
            return build_int_result("", self._spid)

        if "APP_NAME()" in sql_upper:
            return build_nvarchar_result("", self._app_name)

        # KILL <spid> — forcibly close another connection's TCP socket
        kill_match = re.match(r"KILL\s+(\d+)", sql_upper)
        if kill_match:
            target_spid = int(kill_match.group(1))
            if self._server.kill_spid(target_spid):
                self._log.info("Killed SPID %d", target_spid)
                return build_done_token(0)
            else:
                self._log.warning("KILL: SPID %d not found", target_spid)
                return build_done_token(0)

        # Default: return empty DONE
        self._log.debug("No handler for query, returning empty DONE")
        return build_done_token(0)


# ---------------------------------------------------------------------------
# Server
# ---------------------------------------------------------------------------

class MockTdsServer:
    """Mock TDS server for testing SQL driver connectivity."""

    def __init__(self, host="127.0.0.1", port=1433, cert_file=None, key_file=None):
        self.host = host
        self.port = port
        self.ssl_context = None
        self.query_handlers = {}  # pattern -> callable(handler, sql) -> bytes
        self._token_username_map = {}
        self._server_socket = None
        self._shutdown = threading.Event()
        self._next_spid = 100  # per-connection SPID counter
        self._spid_lock = threading.Lock()
        self._connections = {}  # spid -> ConnectionHandler (for KILL support)
        self._connections_lock = threading.Lock()
        self.enable_session_recovery = False  # include session recovery in FeatureExtAck

        if cert_file and key_file:
            self.ssl_context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
            self.ssl_context.load_cert_chain(certfile=cert_file, keyfile=key_file)
            # Don't require client certs
            self.ssl_context.check_hostname = False
            self.ssl_context.verify_mode = ssl.CERT_NONE
            log.info("TLS enabled with cert=%s", cert_file)

    def register_token_username(self, token, username):
        """Register an explicit mapping from access token string to username."""
        self._token_username_map[token] = username

    def resolve_token_username(self, token):
        """Resolve an access token to a username.

        Returns explicitly registered username if available, otherwise
        a deterministic hash-based username.
        """
        if token in self._token_username_map:
            return self._token_username_map[token]
        # Deterministic fallback
        h = hashlib.sha256(token.encode("utf-8")).hexdigest()[:8]
        return f"user_{h}"

    def register_query(self, sql_pattern, handler):
        """Register a custom query handler.

        handler(connection_handler, sql) -> response_bytes
        """
        self.query_handlers[sql_pattern] = handler

    def start(self):
        """Start the server (blocking)."""
        self._server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self._server_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self._server_socket.settimeout(1.0)  # allow periodic shutdown checks
        self._server_socket.bind((self.host, self.port))
        self._server_socket.listen(5)

        actual_addr = self._server_socket.getsockname()
        self.port = actual_addr[1]
        log.info("Mock TDS server listening on %s:%d", actual_addr[0], actual_addr[1])

        while not self._shutdown.is_set():
            try:
                client_sock, addr = self._server_socket.accept()
            except socket.timeout:
                continue
            except OSError:
                break

            log.info("New connection from %s:%d", addr[0], addr[1])
            t = threading.Thread(
                target=self._handle_client,
                args=(client_sock, addr),
                daemon=True,
            )
            t.start()

        self._server_socket.close()
        log.info("Server stopped")

    def start_background(self):
        """Start the server in a background thread. Returns the thread."""
        t = threading.Thread(target=self.start, daemon=True)
        t.start()
        # Wait for the socket to be ready
        while self._server_socket is None and t.is_alive():
            import time
            time.sleep(0.01)
        return t

    def stop(self):
        """Signal the server to stop."""
        self._shutdown.set()
        if self._server_socket:
            try:
                self._server_socket.close()
            except OSError:
                pass

    def kill_spid(self, spid):
        """Forcibly close the TCP connection for the given SPID.

        Returns True if the connection was found and killed, False otherwise.
        This is used by the KILL <spid> SQL command handler and can also
        be called programmatically from tests.
        """
        with self._connections_lock:
            handler = self._connections.get(spid)
        if handler is None:
            return False
        log.info("Killing SPID %d", spid)
        try:
            handler._raw_sock.shutdown(socket.SHUT_RDWR)
        except OSError:
            pass
        try:
            handler._raw_sock.close()
        except OSError:
            pass
        return True

    def _register_connection(self, handler):
        with self._connections_lock:
            self._connections[handler._spid] = handler

    def _unregister_connection(self, handler):
        with self._connections_lock:
            self._connections.pop(handler._spid, None)

    def _handle_client(self, client_sock, addr):
        handler = ConnectionHandler(client_sock, addr, self)
        self._register_connection(handler)
        try:
            handler.handle()
        finally:
            self._unregister_connection(handler)


# ---------------------------------------------------------------------------
# Certificate Generation
# ---------------------------------------------------------------------------

def generate_self_signed_cert(cert_path, key_path):
    """Generate a self-signed certificate.

    Tries openssl CLI first, then falls back to the Python ``cryptography``
    package so the server works on Windows where openssl may not be on PATH.
    """
    try:
        subprocess.run(
            [
                "openssl", "req", "-x509", "-newkey", "rsa:2048",
                "-keyout", key_path,
                "-out", cert_path,
                "-days", "30",
                "-nodes",
                "-subj", "/CN=localhost",
            ],
            check=True,
            capture_output=True,
        )
        log.info("Generated self-signed cert (openssl): %s, key: %s", cert_path, key_path)
        return
    except (FileNotFoundError, subprocess.CalledProcessError) as exc:
        log.debug("openssl CLI unavailable (%s), trying cryptography package", exc)

    # Fallback: use the Python cryptography package
    try:
        from cryptography import x509
        from cryptography.x509.oid import NameOID
        from cryptography.hazmat.primitives import hashes, serialization
        from cryptography.hazmat.primitives.asymmetric import rsa
        import datetime
    except ImportError:
        raise RuntimeError(
            "Cannot generate a self-signed certificate: neither the openssl CLI "
            "nor the Python 'cryptography' package is available.  Install one of "
            "them (e.g.  pip install cryptography)."
        )

    key = rsa.generate_private_key(public_exponent=65537, key_size=2048)
    subject = issuer = x509.Name([
        x509.NameAttribute(NameOID.COMMON_NAME, "localhost"),
    ])
    cert = (
        x509.CertificateBuilder()
        .subject_name(subject)
        .issuer_name(issuer)
        .public_key(key.public_key())
        .serial_number(x509.random_serial_number())
        .not_valid_before(datetime.datetime.now(datetime.timezone.utc))
        .not_valid_after(datetime.datetime.now(datetime.timezone.utc) + datetime.timedelta(days=30))
        .sign(key, hashes.SHA256())
    )
    with open(key_path, "wb") as f:
        f.write(key.private_bytes(
            serialization.Encoding.PEM,
            serialization.PrivateFormat.TraditionalOpenSSL,
            serialization.NoEncryption(),
        ))
    with open(cert_path, "wb") as f:
        f.write(cert.public_bytes(serialization.Encoding.PEM))
    log.info("Generated self-signed cert (cryptography): %s, key: %s", cert_path, key_path)


# ---------------------------------------------------------------------------
# CLI
# ---------------------------------------------------------------------------

def main():
    parser = argparse.ArgumentParser(
        description="Mock TDS server for testing SQL driver connectivity",
    )
    parser.add_argument("--host", default="127.0.0.1", help="Bind address")
    parser.add_argument("--port", type=int, default=1433, help="Bind port (0 = auto)")
    parser.add_argument("--cert", help="PEM certificate file")
    parser.add_argument("--key", help="PEM private key file")
    parser.add_argument(
        "--generate-cert", action="store_true",
        help="Generate self-signed cert if --cert/--key files don't exist",
    )
    parser.add_argument("--print-port", action="store_true", help="Print PORT=N to stdout")
    parser.add_argument(
        "--log-level", default="INFO",
        choices=["DEBUG", "INFO", "WARNING", "ERROR"],
        help="Logging level",
    )
    parser.add_argument("--log-file", help="Log to file instead of stderr")
    parser.add_argument(
        "--map-token", action="append", default=[],
        metavar="TOKEN=USERNAME",
        help="Map access token to username (repeatable)",
    )
    parser.add_argument(
        "--session-recovery", action="store_true",
        help="Include session recovery in FeatureExtAck (enables ConnectRetryCount recovery)",
    )
    args = parser.parse_args()

    # Configure logging
    log_handlers = []
    if args.log_file:
        log_handlers.append(logging.FileHandler(args.log_file))
    else:
        log_handlers.append(logging.StreamHandler(sys.stderr))

    logging.basicConfig(
        level=getattr(logging, args.log_level),
        format="%(asctime)s %(name)s %(levelname)s %(message)s",
        handlers=log_handlers,
    )

    # Handle cert generation
    if args.generate_cert and args.cert and args.key:
        if not os.path.exists(args.cert) or not os.path.exists(args.key):
            generate_self_signed_cert(args.cert, args.key)

    server = MockTdsServer(
        host=args.host,
        port=args.port,
        cert_file=args.cert,
        key_file=args.key,
    )
    server.enable_session_recovery = args.session_recovery

    # Register token-username mappings from CLI
    for mapping in args.map_token:
        if "=" in mapping:
            token, username = mapping.split("=", 1)
            server.register_token_username(token, username)

    if args.print_port:
        # We need to bind first to get the actual port
        server._server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        server._server_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        server._server_socket.settimeout(1.0)
        server._server_socket.bind((server.host, server.port))
        server._server_socket.listen(5)
        server.port = server._server_socket.getsockname()[1]
        print(f"PORT={server.port}", flush=True)

        # Now run the accept loop directly (socket already bound)
        log.info("Mock TDS server listening on %s:%d", server.host, server.port)
        while not server._shutdown.is_set():
            try:
                client_sock, addr = server._server_socket.accept()
            except socket.timeout:
                continue
            except OSError:
                break
            log.info("New connection from %s:%d", addr[0], addr[1])
            t = threading.Thread(
                target=server._handle_client,
                args=(client_sock, addr),
                daemon=True,
            )
            t.start()
        server._server_socket.close()
    else:
        server.start()


if __name__ == "__main__":
    main()

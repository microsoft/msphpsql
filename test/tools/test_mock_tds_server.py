#!/usr/bin/env python3
# Copyright (c) Microsoft Corporation. All rights reserved.
# Licensed under the MIT License.
"""
Unit tests for mock_tds_server.py

Includes:
  - Byte-level protocol tests (PreLogin, Login7 parsing, response builders)
  - Token-to-username mapping tests
  - Live socket tests (self-connect without TLS)
  - Optional sqlcmd integration test (skipped if sqlcmd/openssl not available)
"""

import hashlib
import os
import shutil
import socket
import struct
import subprocess
import sys
import tempfile
import threading
import time
import unittest
import uuid

# Add test/tools to path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import mock_tds_server as m


class TestPasswordDeobfuscation(unittest.TestCase):
    def test_known_password(self):
        # Round-trip test: obfuscate then deobfuscate
        # Obfuscation: XOR 0xA5, then swap nibbles
        plain = uuid.uuid4().hex
        plain_bytes = plain.encode("utf-16-le")
        # Obfuscate: for each byte, XOR 0xA5 then swap nibbles
        obfuscated = bytearray(len(plain_bytes))
        for i, b in enumerate(plain_bytes):
            x = b ^ 0xA5
            obfuscated[i] = ((x >> 4) & 0x0F) | ((x << 4) & 0xF0)
        result = m._deobfuscate_password(bytes(obfuscated))
        self.assertEqual(result.decode("utf-16-le"), plain)

    def test_empty_password(self):
        result = m._deobfuscate_password(b"")
        self.assertEqual(result, b"")


class TestPreLoginResponse(unittest.TestCase):
    def test_with_encryption_and_fedauth(self):
        data = m.build_prelogin_response(supports_encryption=True, supports_fedauth=True)
        # Parse option directory
        i = 0
        options = {}
        while i < len(data):
            opt = data[i]
            if opt == 0xFF:
                break
            off = struct.unpack(">H", data[i + 1:i + 3])[0]
            length = struct.unpack(">H", data[i + 3:i + 5])[0]
            options[opt] = (off, length)
            i += 5

        # VERSION should be present
        self.assertIn(m.PL_VERSION, options)
        # ENCRYPTION should be present and ON
        self.assertIn(m.PL_ENCRYPTION, options)
        enc_off, enc_len = options[m.PL_ENCRYPTION]
        self.assertEqual(enc_len, 1)
        self.assertEqual(data[enc_off], m.ENCRYPT_ON)
        # FEDAUTH should be present
        self.assertIn(m.PL_FEDAUTH, options)

    def test_without_fedauth(self):
        data = m.build_prelogin_response(supports_encryption=False, supports_fedauth=False)
        i = 0
        options = {}
        while i < len(data):
            opt = data[i]
            if opt == 0xFF:
                break
            off = struct.unpack(">H", data[i + 1:i + 3])[0]
            length = struct.unpack(">H", data[i + 3:i + 5])[0]
            options[opt] = (off, length)
            i += 5
        self.assertNotIn(m.PL_FEDAUTH, options)
        enc_off, enc_len = options[m.PL_ENCRYPTION]
        self.assertEqual(data[enc_off], m.ENCRYPT_NOT_SUP)


class TestLoginAck(unittest.TestCase):
    def test_login_ack_contains_tokens(self):
        data = m.build_login_ack()
        # Should start with LoginAck token
        self.assertEqual(data[0], m.TK_LOGINACK)
        # Should contain EnvChange tokens
        self.assertIn(bytes([m.TK_ENVCHANGE]), data)


class TestNVarCharResult(unittest.TestCase):
    def test_result_contains_value(self):
        data = m.build_nvarchar_result("col1", "hello")
        # Should contain ColMetadata
        self.assertEqual(data[0], m.TK_COLMETADATA)
        # Should contain the value in UTF-16LE
        self.assertIn("hello".encode("utf-16-le"), data)
        # Should end with Done token
        self.assertEqual(data[-13], m.TK_DONE)


class TestIntResult(unittest.TestCase):
    def test_result_contains_value(self):
        data = m.build_int_result("count", 42)
        self.assertEqual(data[0], m.TK_COLMETADATA)
        # Should contain packed int 42
        self.assertIn(struct.pack("<i", 42), data)


class TestTokenUsernameMapping(unittest.TestCase):
    def test_explicit_mapping(self):
        srv = m.MockTdsServer(port=0)
        srv.register_token_username("token_abc", "alice")
        self.assertEqual(srv.resolve_token_username("token_abc"), "alice")

    def test_hash_fallback(self):
        srv = m.MockTdsServer(port=0)
        username = srv.resolve_token_username("some_random_token")
        h = hashlib.sha256("some_random_token".encode()).hexdigest()[:8]
        self.assertEqual(username, f"user_{h}")

    def test_same_token_same_name(self):
        srv = m.MockTdsServer(port=0)
        u1 = srv.resolve_token_username("token_xyz")
        u2 = srv.resolve_token_username("token_xyz")
        self.assertEqual(u1, u2)

    def test_different_token_different_name(self):
        srv = m.MockTdsServer(port=0)
        u1 = srv.resolve_token_username("token_aaa")
        u2 = srv.resolve_token_username("token_bbb")
        self.assertNotEqual(u1, u2)


class TestSqlBatchParsing(unittest.TestCase):
    def test_with_all_headers(self):
        sql = "SELECT 1"
        sql_bytes = sql.encode("utf-16-le")
        header_len = 4  # just the total length
        payload = struct.pack("<I", header_len) + sql_bytes
        result = m.parse_sql_batch(payload)
        self.assertEqual(result, "SELECT 1")

    def test_empty(self):
        result = m.parse_sql_batch(b"")
        self.assertEqual(result, "")


class TestErrorResponse(unittest.TestCase):
    def test_error_contains_message(self):
        data = m.build_error_response("Test error")
        self.assertEqual(data[0], m.TK_ERROR)
        self.assertIn("Test error".encode("utf-16-le"), data)


class TestFeatureExtAck(unittest.TestCase):
    def test_fedauth_ack(self):
        data = m.build_feature_ext_ack_fedauth()
        self.assertEqual(data[0], m.TK_FEATUREEXTACK)
        self.assertEqual(data[1], m.FEATURE_EXT_FEDAUTH)
        self.assertEqual(data[-1], m.FEATURE_EXT_TERMINATOR)


class TestLiveNoTLS(unittest.TestCase):
    """Test actual socket communication (no TLS)."""

    @classmethod
    def setUpClass(cls):
        cls.server = m.MockTdsServer(host="127.0.0.1", port=0)
        cls.server_thread = cls.server.start_background()
        # Wait for port to be assigned
        deadline = time.time() + 5
        while cls.server.port == 0 and time.time() < deadline:
            time.sleep(0.05)
        assert cls.server.port != 0, "Server failed to start"

    @classmethod
    def tearDownClass(cls):
        cls.server.stop()
        cls.server_thread.join(timeout=5)

    def _connect(self):
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(5)
        sock.connect(("127.0.0.1", self.server.port))
        return sock

    def _send_prelogin(self, sock):
        """Send a minimal PreLogin request."""
        # Minimal: VERSION option + TERMINATOR
        body = bytearray()
        body.append(m.PL_VERSION)
        body.extend(struct.pack(">HH", 6, 6))  # offset 6, length 6
        body.append(m.PL_TERMINATOR)
        body.extend(b"\x00\x00\x00\x00\x00\x00")  # version data
        m.write_tds_packet(sock, m.PKT_PRELOGIN, bytes(body))

    def _read_prelogin_response(self, sock):
        pkt_type, payload = m.read_tds_packet(sock)
        self.assertEqual(pkt_type, m.PKT_TABULAR_RESULT)
        return payload

    def _send_login7_sql_auth(self, sock, username="sa", password=None):
        """Build and send a minimal Login7 packet with SQL authentication."""
        if password is None:
            password = uuid.uuid4().hex
        # Build variable data
        username_bytes = username.encode("utf-16-le")
        password_bytes = password.encode("utf-16-le")

        # Obfuscate password
        obfuscated = bytearray(len(password_bytes))
        for i, b in enumerate(password_bytes):
            x = b ^ 0xA5
            obfuscated[i] = ((x >> 4) & 0x0F) | ((x << 4) & 0xF0)

        # Fixed part: 58 bytes (36 fixed + offsets for 6 fields)
        # We need at least up to byte 58
        hostname = "testhost"
        appname = "testapp"
        servername = ""

        hostname_bytes = hostname.encode("utf-16-le")
        appname_bytes = appname.encode("utf-16-le")
        servername_bytes = servername.encode("utf-16-le")

        # Variable data starts at offset 58 (after fixed header)
        var_start = 58
        offset = var_start

        # Build offset table and variable data
        var_data = bytearray()

        hostname_off = offset
        hostname_chars = len(hostname)
        var_data.extend(hostname_bytes)
        offset += len(hostname_bytes)

        username_off = offset
        username_chars = len(username)
        var_data.extend(username_bytes)
        offset += len(username_bytes)

        password_off = offset
        password_chars = len(password)
        var_data.extend(bytes(obfuscated))
        offset += len(obfuscated)

        appname_off = offset
        appname_chars = len(appname)
        var_data.extend(appname_bytes)
        offset += len(appname_bytes)

        servername_off = offset
        servername_chars = len(servername)
        var_data.extend(servername_bytes)
        offset += len(servername_bytes)

        # Fixed header (58 bytes)
        fixed = bytearray(58)
        total_len = 58 + len(var_data)
        struct.pack_into("<I", fixed, 0, total_len)  # Length

        # TDS version 7.4
        struct.pack_into("<I", fixed, 4, 0x74000004)

        # PacketSize
        struct.pack_into("<I", fixed, 8, 4096)

        # Offset table entries
        struct.pack_into("<HH", fixed, 36, hostname_off, hostname_chars)
        struct.pack_into("<HH", fixed, 40, username_off, username_chars)
        struct.pack_into("<HH", fixed, 44, password_off, password_chars)
        struct.pack_into("<HH", fixed, 48, appname_off, appname_chars)
        struct.pack_into("<HH", fixed, 52, servername_off, servername_chars)

        # OptionFlags3 at byte 27: no FeatureExt (bit 4 = 0)
        fixed[27] = 0x00

        payload = bytes(fixed) + bytes(var_data)
        m.write_tds_packet(sock, m.PKT_LOGIN7, payload)

    def _read_login_response(self, sock):
        pkt_type, payload = m.read_tds_packet(sock)
        self.assertEqual(pkt_type, m.PKT_TABULAR_RESULT)
        # Should contain LoginAck token
        self.assertIn(bytes([m.TK_LOGINACK]), payload)
        return payload

    def _send_sql_batch(self, sock, sql):
        """Send a SQL batch packet."""
        sql_bytes = sql.encode("utf-16-le")
        payload = struct.pack("<I", 4) + sql_bytes  # ALL_HEADERS = 4
        m.write_tds_packet(sock, m.PKT_SQL_BATCH, payload)

    def _read_query_result(self, sock):
        pkt_type, payload = m.read_tds_packet(sock)
        self.assertEqual(pkt_type, m.PKT_TABULAR_RESULT)
        return payload

    def test_full_flow_sql_auth(self):
        """Test PreLogin -> Login7 (SQL auth) -> SELECT 1."""
        sock = self._connect()
        try:
            self._send_prelogin(sock)
            self._read_prelogin_response(sock)

            self._send_login7_sql_auth(sock, "testuser")
            self._read_login_response(sock)

            # SELECT 1
            self._send_sql_batch(sock, "SELECT 1")
            result = self._read_query_result(sock)
            # Should contain int value 1
            self.assertIn(struct.pack("<i", 1), result)
        finally:
            sock.close()

    def test_select_user_name(self):
        """Test that SELECT USER_NAME() returns the authenticated username."""
        sock = self._connect()
        try:
            self._send_prelogin(sock)
            self._read_prelogin_response(sock)

            self._send_login7_sql_auth(sock, "myuser")
            self._read_login_response(sock)

            self._send_sql_batch(sock, "SELECT USER_NAME()")
            result = self._read_query_result(sock)
            # Should contain "myuser" in UTF-16LE
            self.assertIn("myuser".encode("utf-16-le"), result)
        finally:
            sock.close()

    def test_select_version(self):
        """Test SELECT @@VERSION returns something."""
        sock = self._connect()
        try:
            self._send_prelogin(sock)
            self._read_prelogin_response(sock)
            self._send_login7_sql_auth(sock)
            self._read_login_response(sock)

            self._send_sql_batch(sock, "SELECT @@VERSION")
            result = self._read_query_result(sock)
            self.assertIn("MockTdsServer".encode("utf-16-le"), result)
        finally:
            sock.close()


class TestSqlcmdIntegration(unittest.TestCase):
    """Integration test using sqlcmd to connect to the mock server.

    Requires:
      - sqlcmd (or /opt/mssql-tools*/bin/sqlcmd) on PATH
      - openssl CLI for cert generation
    """

    _cert_dir = None
    _server = None
    _server_thread = None
    _sqlcmd_path = None

    @classmethod
    def setUpClass(cls):
        # Find sqlcmd
        cls._sqlcmd_path = shutil.which("sqlcmd")
        if not cls._sqlcmd_path:
            # Check common SQL Tools paths
            for path in [
                "/opt/mssql-tools18/bin/sqlcmd",
                "/opt/mssql-tools/bin/sqlcmd",
            ]:
                if os.path.isfile(path) and os.access(path, os.X_OK):
                    cls._sqlcmd_path = path
                    break

        if not cls._sqlcmd_path:
            raise unittest.SkipTest("sqlcmd not found")

        if not shutil.which("openssl"):
            raise unittest.SkipTest("openssl not found")

        cls._test_password = uuid.uuid4().hex

        # Generate cert
        cls._cert_dir = tempfile.mkdtemp(prefix="mock_tds_test_")
        cert = os.path.join(cls._cert_dir, "server.pem")
        key = os.path.join(cls._cert_dir, "server.key")
        m.generate_self_signed_cert(cert, key)

        # Start server with TLS
        cls._server = m.MockTdsServer(
            host="127.0.0.1", port=0,
            cert_file=cert, key_file=key,
        )
        cls._server_thread = cls._server.start_background()
        deadline = time.time() + 5
        while cls._server.port == 0 and time.time() < deadline:
            time.sleep(0.05)
        assert cls._server.port != 0, "TLS server failed to start"

    @classmethod
    def tearDownClass(cls):
        if cls._server:
            cls._server.stop()
        if cls._server_thread:
            cls._server_thread.join(timeout=5)
        if cls._cert_dir:
            shutil.rmtree(cls._cert_dir, ignore_errors=True)

    def test_sqlcmd_select_1(self):
        """Verify sqlcmd can connect and run SELECT 1 through TDS 7.4 TLS."""
        result = subprocess.run(
            [
                self._sqlcmd_path,
                "-S", f"127.0.0.1,{self._server.port}",
                "-U", "sa",
                "-P", self._test_password,
                "-Q", "SELECT 1",
                "-C",  # trust server cert
                "-l", "5",  # login timeout
            ],
            capture_output=True,
            text=True,
            timeout=15,
        )
        # sqlcmd should succeed and output contain "1"
        self.assertEqual(result.returncode, 0, f"sqlcmd failed: {result.stderr}")
        self.assertIn("1", result.stdout)

    def test_sqlcmd_user_name(self):
        """Verify SELECT USER_NAME() through sqlcmd returns the login username."""
        result = subprocess.run(
            [
                self._sqlcmd_path,
                "-S", f"127.0.0.1,{self._server.port}",
                "-U", "testadmin",
                "-P", self._test_password,
                "-Q", "SELECT USER_NAME()",
                "-C",
                "-l", "5",
            ],
            capture_output=True,
            text=True,
            timeout=15,
        )
        self.assertEqual(result.returncode, 0, f"sqlcmd failed: {result.stderr}")
        self.assertIn("testadmin", result.stdout)


if __name__ == "__main__":
    import logging
    logging.basicConfig(
        level=logging.DEBUG if "-v" in sys.argv else logging.WARNING,
        format="%(asctime)s %(name)s %(levelname)s %(message)s",
    )
    unittest.main()

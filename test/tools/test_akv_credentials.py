#!/usr/bin/env python3
# Copyright (c) Microsoft Corporation. All rights reserved.
# Licensed under the MIT License.
"""Compile and test the production AKV configuration functions without a server.

Run in a C++ build environment:
    python test/tools/test_akv_credentials.py --sanitize

Linux CI retains GCC coverage data under source/ for the existing gcovr step:
    python3 test/tools/test_akv_credentials.py --coverage --build-dir source/.akv-credentials

Only the PHP allocation/error and ODBC boundaries are replaced. Function bodies,
payload layout and authentication constants are read from production sources.
"""

import argparse
from contextlib import nullcontext
import json
import os
from pathlib import Path
import re
import subprocess
from tempfile import TemporaryDirectory


def extract_function(path, signature):
    source = path.read_text(encoding="utf-8")
    start = source.index(signature + "\n{")
    end = source.index("\n}", start) + len("\n}")
    line = source.count("\n", 0, start) + 1
    return f"#line {line} {json.dumps(path.as_posix())}\n{source[start:end]}\n"


def write_test_sources(root, work):
    shared = root / "source" / "shared"
    header = (shared / "msodbcsql.h").read_text(encoding="utf-8")
    structure = re.search(
        r"typedef struct CEKeystoreData\s*\{.*?\} CEKEYSTOREDATA;", header, re.DOTALL
    ).group()
    names = (
        "AKV_CONFIG_FLAGS", "AKV_CONFIG_PRINCIPALID", "AKV_CONFIG_AUTHSECRET",
        "AKVCFG_AUTHMODE_CLIENTKEY", "AKVCFG_AUTHMODE_PASSWORD",
    )
    constants = "\n".join(
        re.search(rf"^#define {name}\s+[^\n]+", header, re.MULTILINE).group()
        for name in names
    )
    (work / "akv_layout.inc").write_text(structure + "\n" + constants, encoding="utf-8")

    signatures = (
        "void configure_azure_key_vault(sqlsrv_conn* conn, BYTE config_attr, const DWORD config_value, size_t /*key_size*/)",
        "void configure_azure_key_vault(sqlsrv_conn* conn, BYTE config_attr, const char* config_value, size_t key_size)",
        "void load_azure_key_vault(_Inout_ sqlsrv_conn* conn)",
    )
    implementation = ""
    if os.name != "nt":
        implementation += extract_function(
            shared / "StringFunctions.cpp",
            "int mplat_memcpy_s( void * dest, size_t destSize, const void * src, size_t count )",
        )
    implementation += "\n".join(
        extract_function(shared / "core_conn.cpp", signature)
        for signature in signatures
    )
    (work / "akv_implementation.inc").write_text(implementation, encoding="utf-8")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--build-dir", type=Path, help="Retain build and coverage files here.")
    instrumentation = parser.add_mutually_exclusive_group()
    instrumentation.add_argument("--coverage", action="store_true", help="Enable GCC coverage.")
    instrumentation.add_argument("--sanitize", action="store_true", help="Enable AddressSanitizer.")
    args = parser.parse_args()
    if args.coverage and os.name == "nt":
        parser.error("--coverage requires a GCC build environment.")

    root = Path(__file__).resolve().parents[2]
    directory = nullcontext(args.build_dir.resolve()) if args.build_dir else TemporaryDirectory(prefix="msphpsql-akv-")
    with directory as directory_name:
        work = Path(directory_name)
        work.mkdir(parents=True, exist_ok=True)
        write_test_sources(root, work)
        harness = root / "test" / "tools" / "akv_credentials_test.cpp"
        if os.name == "nt":
            command = ["cl", "/nologo", "/std:c++14", "/EHsc", "/W4", "/WX", f"/I{work}"]
            if args.sanitize:
                command += ["/fsanitize=address", "/Zi"]
            command += [str(harness), "/Fe:akv_credentials_test.exe"]
            executable = work / "akv_credentials_test.exe"
        else:
            command = [os.environ.get("CXX", "c++"), "-std=c++11", "-O0", "-g",
                       "-Wall", "-Wextra", "-Werror", "-I", str(work)]
            if args.coverage:
                command += ["--coverage"]
            if args.sanitize:
                command += ["-fsanitize=address", "-fno-omit-frame-pointer"]
            command += [str(harness), "-o", "akv_credentials_test"]
            executable = work / "akv_credentials_test"
        subprocess.run(command, cwd=work, check=True)
        subprocess.run([str(executable)], cwd=work, check=True)


if __name__ == "__main__":
    main()

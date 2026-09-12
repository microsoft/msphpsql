#!/usr/bin/env bash
# Copyright (c) Microsoft Corporation. All rights reserved.
# Licensed under the MIT License.
# Linux native regression. Requires matching php/phpize/php-config, PHP FFI,
# a C++ compiler, make and unixODBC development headers. No production test API.
# By default run live success/failure cases using MSSQL_SERVER, MSSQL_USER,
# MSSQL_PASSWORD and MSSQL_DRIVER_NAME; pass --no-server for parser-only cases.
set -euo pipefail

if [[ "$(uname -s)" != Linux ]]; then
    echo 'This GNU linker instrumentation test requires Linux.' >&2
    exit 1
fi
if [[ $# -gt 1 || ( $# -eq 1 && "$1" != --no-server ) ]]; then
    echo 'Usage: test_pdo_password_cleanup.sh [--no-server]' >&2
    exit 1
fi
if [[ ${1:-} == --no-server ]]; then
    export MSPHPSQL_CLEANUP_NO_SERVER=1
else
    export MSPHPSQL_CLEANUP_NO_SERVER=0
    : "${MSSQL_SERVER:?Set MSSQL_SERVER for live cleanup tests}"
    : "${MSSQL_USER:?Set MSSQL_USER for live cleanup tests}"
    : "${MSSQL_PASSWORD?Set MSSQL_PASSWORD for live cleanup tests}"
    : "${MSSQL_DRIVER_NAME:?Set MSSQL_DRIVER_NAME for live cleanup tests}"
fi

if [[ "$(php -n -r 'echo PHP_VERSION_ID;')" != "$(php-config --vernum)" ]]; then
    echo 'php and php-config must refer to the same PHP version.' >&2
    exit 1
fi
# The observer uses the release-build _efree ABI; debug builds add arguments.
if [[ "$(php -n -r 'echo (int) PHP_DEBUG;')" != 0 ]]; then
    echo 'This observer requires a non-debug PHP build.' >&2
    exit 1
fi
php -n -d extension=ffi -r 'exit(extension_loaded("FFI") ? 0 : 1);'

root=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)
work=$(mktemp -d /tmp/msphpsql-cleanup.XXXXXX)
trap 'rm -rf -- "$work"' EXIT
mkdir -p "$work/pdo_sqlsrv/shared"
cp "$root"/source/pdo_sqlsrv/*.{cpp,h,m4} "$work/pdo_sqlsrv/"
cp "$root"/source/shared/*.{cpp,h,hpp} "$work/pdo_sqlsrv/shared/"
cp "$root/test/native/pdo_password_cleanup_observer.cpp" "$work/observer.cpp"

# This observer wraps only calls originating in the disposable test module.
# The production secure erase is still called; zeroed bytes are then inspected
# in __wrap__efree immediately BEFORE Zend actually releases the allocation.
c++ -std=c++11 -O2 -fPIC -Wall -Wextra -Werror -c "$work/observer.cpp" -o "$work/observer.o"
cd "$work/pdo_sqlsrv"
if ! { phpize > "$work/build.log" 2>&1 && ./configure --with-pdo_sqlsrv >> "$work/build.log" 2>&1; }; then
    tail -n 60 "$work/build.log" >&2
    exit 1
fi
if ! make -j2 LDFLAGS="$work/observer.o -Wl,--wrap=explicit_bzero,--wrap=__explicit_bzero_chk,--wrap=_efree" >> "$work/build.log" 2>&1; then
    tail -n 60 "$work/build.log" >&2
    exit 1
fi
export MSPHPSQL_CLEANUP_MODULE="$work/pdo_sqlsrv/modules/pdo_sqlsrv.so"
php -n -d extension=pdo -d extension=ffi -d ffi.enable=1 \
    -d zend.exception_ignore_args=1 \
    -d "extension=$MSPHPSQL_CLEANUP_MODULE" \
    "$root/test/native/pdo_password_cleanup.php"
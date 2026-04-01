#!/bin/bash
#
# Build sqlsrv and pdo_sqlsrv PHP extensions on Linux.
#
# Usage:
#   ./build-linux.sh              # build and copy output to build-output/
#   ./build-linux.sh --install    # build and install to PHP extension dir (requires sudo)
#   ./build-linux.sh --clean      # build, copy output, then clean intermediate files
#   ./build-linux.sh --clean-only # just remove build artifacts (no build)
#
# Output:
#   build-output/sqlsrv.so
#   build-output/pdo_sqlsrv.so

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SRC_DIR="$SCRIPT_DIR/source"
OUT_DIR="$SCRIPT_DIR/build-output"

DO_BUILD=true
DO_CLEAN=false
DO_INSTALL=false

for arg in "$@"; do
    case "$arg" in
        --clean)      DO_CLEAN=true ;;
        --clean-only) DO_BUILD=false; DO_CLEAN=true ;;
        --install)    DO_INSTALL=true ;;
        *) echo "Unknown option: $arg"; exit 1 ;;
    esac
done

# Build one extension.
#   $1 = extension source dir (e.g. source/sqlsrv)
#   $2 = configure flag       (e.g. --enable-sqlsrv)
build_ext() {
    local ext_dir="$1"
    local configure_flag="$2"
    local ext_name
    ext_name="$(basename "$ext_dir")"

    echo "=== Building $ext_name ==="

    pushd "$ext_dir" > /dev/null

    # Ensure shared/ symlink exists
    if [ ! -e shared ]; then
        ln -s ../shared shared
    fi

    phpize
    ./configure "$configure_flag"
    make -j"$(nproc)"

    popd > /dev/null
}

# Copy built .so to output dir.
collect_output() {
    mkdir -p "$OUT_DIR"
    cp "$SRC_DIR/sqlsrv/modules/sqlsrv.so" "$OUT_DIR/"
    cp "$SRC_DIR/pdo_sqlsrv/modules/pdo_sqlsrv.so" "$OUT_DIR/"
    echo ""
    echo "=== Extensions copied to build-output/ ==="
    ls -lh "$OUT_DIR"/*.so
}

# Remove all build artifacts from one extension dir.
#   $1 = extension source dir
clean_ext() {
    local ext_dir="$1"
    local ext_name
    ext_name="$(basename "$ext_dir")"

    echo "=== Cleaning $ext_name ==="

    pushd "$ext_dir" > /dev/null

    # make clean removes .lo/.la/.so/.libs etc.
    [ -f Makefile ] && make clean 2>/dev/null || true

    # phpize --clean removes autoconf scaffolding
    [ -f configure ] && phpize --clean 2>/dev/null || true

    # Remove the symlink we created
    [ -L shared ] && rm -f shared

    popd > /dev/null
}

# Also clean .lo/.dep files that ended up in source/shared/
clean_shared() {
    echo "=== Cleaning shared/ build artifacts ==="
    find "$SRC_DIR/shared" -name '*.lo' -o -name '*.dep' -o -name '*.o' | xargs rm -f 2>/dev/null || true
    rm -rf "$SRC_DIR/shared/.libs" 2>/dev/null || true
}

if $DO_BUILD; then
    build_ext "$SRC_DIR/sqlsrv"     "--enable-sqlsrv"
    build_ext "$SRC_DIR/pdo_sqlsrv" "--with-pdo_sqlsrv"
    collect_output

    if $DO_INSTALL; then
        echo ""
        echo "=== Installing extensions ==="
        pushd "$SRC_DIR/sqlsrv" > /dev/null
        sudo make install
        popd > /dev/null
        pushd "$SRC_DIR/pdo_sqlsrv" > /dev/null
        sudo make install
        popd > /dev/null
    fi
fi

if $DO_CLEAN; then
    clean_ext "$SRC_DIR/sqlsrv"
    clean_ext "$SRC_DIR/pdo_sqlsrv"
    clean_shared
    # Remove the top-level run-tests.php copied by phpize
    rm -f "$SCRIPT_DIR/run-tests.php"
    echo ""
    echo "=== Build artifacts cleaned ==="
fi

echo "Done."

#!/bin/bash
BUILDDIR=$PWD
echo $BUILDDIR
if [ $# -ne 0 ]; then
    [[ -d $1 ]] || { echo "No such path!"; exit 1; }
    $BUILDDIR=$1
fi
    cd $BUILDDIR/sqlsrv
    phpize
    ./configure
    make
    sudo make install
    cd $BUILDDIR/pdo_sqlsrv
    phpize
    ./configure
    make
    sudo make install


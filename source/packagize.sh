#!/bin/bash
if [ "$1" == "" ]; then
    cp -rf $PWD/shared $PWD/sqlsrv
    cp -rf $PWD/shared $PWD/pdo_sqlsrv  
else
    [[ -d $1 ]] || { echo "No such path!"; exit 1; }
    cp -rf $PWD/sqlsrv $1
    cp -rf $PWD/pdo_sqlsrv $1 
    cp -rf $PWD/shared $1/sqlsrv
    cp -rf $PWD/shared $1/pdo_sqlsrv  
fi

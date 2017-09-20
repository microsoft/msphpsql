#!/usr/bin/env python
# py cleanup_dbs.py -dbname <DBNAME> 

import os
import sys
import subprocess
import platform
import argparse
from subprocess import Popen, PIPE
from exec_sql_scripts import *

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('-dbname', '--DBNAME', required=True)
    args = parser.parse_args()

    try:    
        server = os.environ['TEST_PHP_SQL_SERVER'] 
        uid = os.environ['TEST_PHP_SQL_UID'] 
        pwd = os.environ['TEST_PHP_SQL_PWD'] 
    except :
        print("TEST_PHP_SQL_SERVER environment variable must be set to the name of the server to use")
        print("TEST_PHP_SQL_UID environment variable must be set to the name of the user to authenticate with")
        print("TEST_PHP_SQL_PWD environment variable must be set to the password of the use to authenticate with")
        sys.exit(1)

    conn_options = ' -S ' + server + ' -U ' + uid + ' -P ' + pwd + ' '  
    
    executeSQLscript( os.path.join( os.path.dirname(os.path.realpath(__file__)), 'drop_db.sql'), conn_options, args.DBNAME)

    # if Windows, remove self signed certificate using ps command
    if platform.system() == 'Windows':
        remove_cert_ps = "Get-ChildItem Cert:CurrentUser\My | Where-Object { $_.Subject -match 'PHPAlwaysEncryptedCert' } | Remove-Item"
        inst_command  = 'powershell -executionPolicy Unrestricted -command ' + remove_cert_ps
        executeCommmand(inst_command)
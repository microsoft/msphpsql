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
    
    executeSQLscript('drop_db.sql', conn_options, args.DBNAME)

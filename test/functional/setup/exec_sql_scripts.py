#!/usr/bin/env python3
# contains helper methods
import os
import subprocess
from subprocess import Popen, PIPE

def executeCommmand(inst_command):
    proc = subprocess.Popen(inst_command , stdout=PIPE, stderr= PIPE, shell=True)
    print ( inst_command )
    oo,ee = proc.communicate()
    print (ee)
    print (oo)

def executeSQLscript(sqlfile, conn_options, dbname):
    inst_command  = 'sqlcmd -I ' + conn_options + ' -i ' + sqlfile + ' -d ' + dbname
    executeCommmand(inst_command)

def manageTestDB(sqlfile, conn_options, dbname):
    tmp_sql_file = 'test_db_tmp.sql'
    if os.path.exists(tmp_sql_file):
        os.remove(tmp_sql_file)
    with open(sqlfile, 'r') as infile:
        script = infile.read().replace('TEST_DB', dbname)
    with open(tmp_sql_file, 'w') as outfile:
        outfile.write(script)

    executeSQLscript(tmp_sql_file, conn_options, 'master')
    os.remove(tmp_sql_file)

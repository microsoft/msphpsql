#!/usr/bin/env python3
# py setup_dbs.py -dbname <DBNAME> -azure <yes or no>
# OR 
# py setup_dbs.py -dbname <DBNAME> 
import os
import sys
import subprocess
import platform
import argparse
from subprocess import Popen, PIPE
from exec_sql_scripts import *

def createLoginUsers(conn_options, dbname, azure):
    if (azure.lower() == 'yes'):
        # can only create logins in the master database
        createLoginUsersAzure('create_logins_azure.sql', conn_options, 'master')
        # create users to use those logins to access the test database (dbname)
        createLoginUsersAzure('create_users_azure.sql', conn_options, dbname)
    else:
        executeSQLscript('test_password.sql', conn_options, dbname)

def createLoginUsersAzure(sqlfile, conn_options, dbname):
    inst_command  = 'sqlcmd ' + conn_options + ' -i ' + sqlfile + ' -d ' + dbname
    executeCommmand(inst_command)

def setupTestDatabase(conn_options, dbname, azure):
    sqlFiles = ['test_types.sql', '168256.sql', 'cd_info.sql', 'tracks.sql']
    
    # for Azure, must specify the database for the sql scripts to work
    if (azure.lower() == 'yes'):
        conn_options += ' -d ' + dbname

    for sqlFile in sqlFiles:
        executeSQLscript(sqlFile, conn_options, dbname)

def populateTables(conn_options, dbname):
    executeBulkCopy(conn_options, dbname, 'cd_info', 'cd_info')
    executeBulkCopy(conn_options, dbname, 'tracks', 'tracks')
    executeBulkCopy(conn_options, dbname, 'test_streamable_types', 'test_streamable_types')
    executeBulkCopy(conn_options, dbname, '159137', 'xml')
    executeBulkCopy(conn_options, dbname, '168256', '168256')

def executeBulkCopy(conn_options, dbname, tblname, datafile):
    redirect_string = 'bcp {0}..[{1}] in {2}.dat -f {2}.fmt '
    inst_command = redirect_string.format(dbname, tblname, datafile) + conn_options 
    executeCommmand(inst_command)
    
def setupAE(conn_options, dbname):
    if (platform.system() == 'Windows'):
        # import self signed certificate
        inst_command = "certutil -user -p '' -importPFX My PHPcert.pfx NoRoot"
        executeCommmand(inst_command)
        # create Column Master Key and Column Encryption Key
        script_command = 'sqlcmd ' + conn_options + ' -i ae_keys.sql -d ' + dbname
        executeCommmand(script_command)
    
if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('-dbname', '--DBNAME', required=True)
    parser.add_argument('-azure', '--AZURE', required=False, default='no')
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

    current_working_dir=os.getcwd()
    os.chdir(os.path.dirname(os.path.realpath(__file__)))
    conn_options = ' -S ' + server + ' -U ' + uid + ' -P ' + pwd + ' '  
    
    # In Azure, assume an empty test database has been created using Azure portal
    if (args.AZURE.lower() == 'no'):
        executeSQLscript('create_db.sql', conn_options, args.DBNAME)

    # create login users 
    createLoginUsers(conn_options, args.DBNAME, args.AZURE)
    # create tables in the new database
    setupTestDatabase(conn_options, args.DBNAME, args.AZURE)    
    # populate these tables
    populateTables(conn_options, args.DBNAME)
    # setup AE (certificate, column master key and column encryption key)
    setupAE(conn_options, args.DBNAME)
    
    os.chdir(current_working_dir)
    

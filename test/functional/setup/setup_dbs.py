#!/usr/bin/env python3
# py setup_dbs.py -dbname <DBNAME> -azure <yes or no>
# OR
# py setup_dbs.py -dbname <DBNAME>
import os
import sys
import platform
import argparse
from exec_sql_scripts import *

def setupTestDatabase(conn_options, dbname, azure):
    sqlFiles = ['test_types.sql', '168256.sql', 'cd_info.sql', 'tracks.sql']

    for sqlFile in sqlFiles:
        executeSQLscript(sqlFile, conn_options, dbname)

def populateTables(conn_options, dbname):
    executeBulkCopy(conn_options, dbname, 'cd_info', 'cd_info')
    executeBulkCopy(conn_options, dbname, 'tracks', 'tracks')
    executeBulkCopy(conn_options, dbname, 'test_streamable_types', 'test_streamable_types')
    executeBulkCopy(conn_options, dbname, '159137', 'xml')
    executeBulkCopy(conn_options, dbname, '168256', '168256')

def executeBulkCopy(conn_options, dbname, tblname, datafile):
    redirect_string = 'bcp {0}..{1} in {2}.dat -f {2}.fmt -q'
    inst_command = redirect_string.format(dbname, tblname, datafile) + conn_options
    executeCommmand(inst_command)

def setupAE(conn_options, dbname):
    if (platform.system() == 'Windows'):
        # import self signed certificate
        inst_command = "certutil -user -p '' -importPFX My PHPcert.pfx NoRoot"
        executeCommmand(inst_command)
        inst_command = "certutil -user -p '' -importPFX My AEV2Cert.pfx NoRoot"
        executeCommmand(inst_command)
        # create Column Master Key and Column Encryption Key
        script_command = 'sqlcmd -I ' + conn_options + ' -i ae_keys.sql -d ' + dbname
        executeCommmand(script_command)

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('-dbname', '--DBNAME', required=True)
    parser.add_argument('-azure', '--AZURE', required=False, default='no')
    args = parser.parse_args()
    
    print("Start\n")

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
        manageTestDB('create_db.sql', conn_options, args.DBNAME)

    print("About to set up databases...\n")
    # create tables in the new database
    setupTestDatabase(conn_options, args.DBNAME, args.AZURE)
    print("About to populate tables...\n")
    # populate these tables
    populateTables(conn_options, args.DBNAME)
    print("About to set up encryption...\n")
    # setup AE (certificate, column master key and column encryption key)
    setupAE(conn_options, args.DBNAME)

    os.chdir(current_working_dir)
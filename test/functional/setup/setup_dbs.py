#!/usr/bin/env python3
# py setup_dbs.py -dbname <DBNAME> -azure <yes or no>
# OR
# py setup_dbs.py -dbname <DBNAME>
import os
import sys
import platform
import argparse
from exec_sql_scripts import *

def _is_mssqltools_v18():
    """Return True if mssql-tools >= 18 (encrypt mandatory by default)."""
    import subprocess, re
    try:
        result = subprocess.run(['bcp', '-v'], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        output = result.stdout + result.stderr
        m = re.search(r'Version:\s*(\d+)', output)
        if m and int(m.group(1)) >= 18:
            return True
    except Exception:
        pass
    return False

# mssql-tools18 defaults to Encrypt=Mandatory.  The flags below add encrypt-
# optional + trust-server-certificate so sqlcmd/bcp work against servers
# without a valid TLS certificate. Empty strings for older tools.
_v18 = _is_mssqltools_v18()
_encrypt_opt_sqlcmd = ' -No -C' if _v18 else ''
_encrypt_opt_bcp   = ' -Yo -u' if _v18 else ''

def setupTestDatabase(conn_options_sqlcmd, dbname, azure):
    sqlFiles = ['test_types.sql', '168256.sql', 'cd_info.sql', 'tracks.sql']

    for sqlFile in sqlFiles:
        executeSQLscript(sqlFile, conn_options_sqlcmd, dbname)

def populateTables(conn_options_bcp, dbname):
    executeBulkCopy(conn_options_bcp, dbname, 'cd_info', 'cd_info')
    executeBulkCopy(conn_options_bcp, dbname, 'tracks', 'tracks')
    executeBulkCopy(conn_options_bcp, dbname, 'test_streamable_types', 'test_streamable_types')
    executeBulkCopy(conn_options_bcp, dbname, '159137', 'xml')
    executeBulkCopy(conn_options_bcp, dbname, '168256', '168256')

def executeBulkCopy(conn_options_bcp, dbname, tblname, datafile):
    redirect_string = 'bcp {0}..{1} in {2}.dat -f {2}.fmt -q'
    inst_command = redirect_string.format(dbname, tblname, datafile) + conn_options_bcp
    executeCommmand(inst_command)

def setupAE(conn_options_sqlcmd, dbname):
    if (platform.system() == 'Windows'):
        # import self signed certificate
        inst_command = "certutil -user -p '' -importPFX My PHPcert.pfx NoRoot"
        executeCommmand(inst_command)
        inst_command = "certutil -user -p '' -importPFX My AEV2Cert.pfx NoRoot"
        executeCommmand(inst_command)
        # create Column Master Key and Column Encryption Key
        script_command = 'sqlcmd -I ' + conn_options_sqlcmd + ' -i ae_keys.sql -d ' + dbname
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
    base_conn = ' -S ' + server + ' -U ' + uid + ' -P ' + pwd + ' '
    conn_options_sqlcmd = base_conn + _encrypt_opt_sqlcmd
    conn_options_bcp = base_conn + _encrypt_opt_bcp

    # In Azure, assume an empty test database has been created using Azure portal
    if (args.AZURE.lower() == 'no'):
        manageTestDB('create_db.sql', conn_options_sqlcmd, args.DBNAME)

    print("About to set up databases...\n")
    # create tables in the new database
    setupTestDatabase(conn_options_sqlcmd, args.DBNAME, args.AZURE)
    print("About to populate tables...\n")
    # populate these tables
    populateTables(conn_options_bcp, args.DBNAME)
    print("About to set up encryption...\n")
    # setup AE (certificate, column master key and column encryption key)
    setupAE(conn_options_sqlcmd, args.DBNAME)

    os.chdir(current_working_dir)
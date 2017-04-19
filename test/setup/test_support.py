#!/usr/bin/env python3
# py test_support.py test cd_collection fulltextdb
import os
import sys
import subprocess
import platform
from subprocess import Popen, PIPE

### Important : make sure .sql files encoding is ANSI
if platform.system() == 'Windows':
    os_separator = ' & '
    os_redirect_command = ' type '
elif platform.system() == 'Linux' or platform.system() == 'Darwin':
    os_separator = ' ; '
    os_redirect_command = ' cat '

dbname1 = str(sys.argv[1]) 

try:    
    server = os.environ['TEST_PHP_SQL_SERVER'] 
    uid = os.environ['TEST_PHP_SQL_UID'] 
    pwd = os.environ['TEST_PHP_SQL_PWD'] 
except :
    print("TEST_PHP_SQL_SERVER environment variable must be set to the name of the server to use")
    print("TEST_PHP_SQL_UID environment variable must be set to the name of the user to authenticate with")
    print("TEST_PHP_SQL_PWD environment variable must be set to the password of the use to authenticate with")
    sys.exit(0)
 
conn_options = ' -S ' + server + ' -U ' + uid + ' -P ' + pwd + ' '
#This following line is a workaround, because Linux sqlcmd does not support -v option. It inserts setvar dbname into the beginning of .sql files
redirect_string = '(echo :setvar dbname {0})  > temp.sql ' + os_separator + os_redirect_command+ '{1} >> temp.sql' + os_separator   
sqlcmd = 'sqlcmd ' + conn_options + ' -i temp.sql ' + os_separator

# Create Databases
inst_command = '(@echo off)' + os_separator
inst_command = inst_command + redirect_string.format(dbname1, 'create_db.sql') + sqlcmd

# Create Tables
inst_command = inst_command + redirect_string.format(dbname1, 'test_password.sql' ) + sqlcmd
inst_command = inst_command + redirect_string.format(dbname1, 'test_types_noInd.sql') + sqlcmd
inst_command = inst_command + redirect_string.format(dbname1, '168256.sql') + sqlcmd
inst_command = inst_command + redirect_string.format(dbname1, 'cd_info.sql') + sqlcmd
inst_command = inst_command + redirect_string.format(dbname1, 'tracks_noInd.sql') + sqlcmd

#BCP
inst_command = inst_command + 'bcp ' + dbname1 + '..cd_info in cd_info.dat -f cd_info.fmt ' +  conn_options + os_separator
inst_command = inst_command + 'bcp ' + dbname1 + '..tracks in tracks.dat -f tracks.fmt ' + conn_options + os_separator
inst_command = inst_command + 'bcp ' + dbname1 + '..test_streamable_types in test_streamable_types.dat -f test_streamable_types.fmt ' + conn_options  + os_separator
inst_command = inst_command + 'bcp ' + dbname1 + '..[159137] in xml.dat -f xml.fmt ' + conn_options  + os_separator
inst_command = inst_command + 'bcp ' + dbname1 + '..[168256] in 168256.dat -f 168256.fmt ' + conn_options 

proc = subprocess.Popen(inst_command , stdout=PIPE, stderr= PIPE, shell=True)
print ( inst_command )
oo,ee = proc.communicate()
print (ee)
os.remove('temp.sql')

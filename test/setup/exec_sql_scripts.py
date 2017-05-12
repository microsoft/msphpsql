#!/usr/bin/env python3
# contains helper methods  
import os
import sys
import subprocess
import platform
import argparse
from subprocess import Popen, PIPE

def executeCommmand(inst_command):
    proc = subprocess.Popen(inst_command , stdout=PIPE, stderr= PIPE, shell=True)
    print ( inst_command )
    oo,ee = proc.communicate()
    print (ee)
    print (oo)

def executeSQLscript(sqlfile, conn_options, dbname):
    if platform.system() == 'Windows':
        executeSQLscriptWindows(sqlfile, conn_options, dbname)
    elif platform.system() == 'Linux' or platform.system() == 'Darwin':
        executeSQLscriptUnix(sqlfile, conn_options, dbname)
    
def executeSQLscriptWindows(sqlfile, conn_options, dbname):
    inst_command  = 'sqlcmd ' + conn_options + ' -i ' + sqlfile + ' -v dbname =' + dbname
    executeCommmand(inst_command)

def executeSQLscriptUnix(sqlfile, conn_options, dbname):
    # This is a workaround because sqlcmd in Unix does not support -v option for variables. 
    # It inserts setvar dbname into the beginning of a temp .sql file
    tmpFileName = sqlfile[0:-4] + '_tmp.sql'
    redirect_string = '(echo :setvar dbname {0})  > {2}; cat {1} >> {2}; '
    sqlcmd = 'sqlcmd ' + conn_options + ' -i ' + tmpFileName

    # Execute a simple query via sqlcmd: without this step, the next step fails in travis CI
    simple_cmd = 'sqlcmd ' + conn_options + ' -Q \"select @@Version\" '
    executeCommmand(simple_cmd)

    # inst_command = redirect_string.format(dbname, sqlfile, tmpFileName) + sqlcmd
    inst_command = redirect_string.format(dbname, sqlfile, tmpFileName)
    executeCommmand(inst_command)
    executeCommmand(sqlcmd)
    
    os.remove(tmpFileName)

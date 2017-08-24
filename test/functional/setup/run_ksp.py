#!/usr/bin/python3
#########################################################################################
#
# Description:  This script assumes the existence of the ksp_app executable and will
#               invoke it to create / remove the Column Master Key, the Column Encryption key, 
#               and the table [CustomKSPTestTable] in the test database.  
#
# Requirement:
#               python 3.x
#               ksp_app executable
#
# Execution:    Run with command line with required options
#               py run_ksp.py --SERVER=server --DBNAME=database --UID=uid --PWD=pwd
#               py run_ksp.py --SERVER=server --DBNAME=database --UID=uid --PWD=pwd --REMOVE
#
#############################################################################################

import sys
import os
import platform
import argparse

################################### Main Function ###################################
if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('-server', '--SERVER', required=True, help='SQL Server')
    parser.add_argument('-dbname', '--DBNAME', required=True, help='Name of an existing database')
    parser.add_argument('-uid', '--UID', required=True, help='User name')
    parser.add_argument('-pwd', '--PWD', required=True, help='User password')
    parser.add_argument('-remove', '--REMOVE', action='store_true', help='Clean up KSP related data, false by default')

    args = parser.parse_args()

    app_name = 'ksp_app'
    cwd = os.getcwd()

    # first check if the ksp app is present
    work_dir = os.path.dirname(os.path.realpath(__file__))
    os.chdir(work_dir) 

    if platform.system() == 'Windows':
        path = os.path.join(work_dir, app_name + '.exe')
        executable = app_name
    else:
        path = os.path.join(work_dir, app_name)
        executable = './' + app_name
    
    if not os.path.exists(path):
        print('Error: {0} not found!'.format(path))
        exit(1)
    
    if args.REMOVE:
        os.system('{0} 1 {1} {2} {3} {4}'.format(executable, args.SERVER, args.DBNAME, args.UID, args.PWD))
    else:
        os.system('{0} 0 {1} {2} {3} {4}'.format(executable, args.SERVER, args.DBNAME, args.UID, args.PWD))

    os.chdir(cwd)    

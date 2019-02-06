#!/usr/bin/python3
#########################################################################################
#
# Description:  This contains helper methods for source indexing
#
# Requirement:
#               python 3.x
#               srctool.exe and pdbstr.exe
#
#############################################################################################

import os.path
import argparse
import subprocess
from subprocess import Popen, PIPE

def write_index(index_filename, tag_version):
    """This writes to a temporary index file for the pdbstr tool

    For example

    SRCSRV: ini ------------------------------------------------
    VERSION=1
    SRCSRV: variables ------------------------------------------
    PATH=%var2%
    SRCSRVTRG=%TARG%\%PDBVERSION%\%fnbksl%(%var2%)
    SRCURL=https://raw.githubusercontent.com/Microsoft/msphpsql/%SRCVERSION%/source/%PATH%
    SRCSRVCMD=powershell -Command "$r=New-Object -ComObject Msxml2.XMLHTTP; $r.open('GET', '%SRCURL%', $false); $r.send(); [io.file]::WriteAllBytes('%SRCSRVTRG%', $r.responseBody)"
    SRCVERSION=v5.6.0
    PDBVERSION=v5.6.0
    For example
    """
    with open(index_filename, 'w') as f:
        f.write('SRCSRV: ini ------------------------------------------------' + os.linesep)
        f.write('VERSION=1' + os.linesep)
        f.write('SRCSRV: variables ------------------------------------------' + os.linesep)
        f.write('PATH=%var2%' + os.linesep)
        f.write('SRCSRVTRG=%TARG%\%PDBVERSION%\%fnbksl%(%var2%)' + os.linesep)
        f.write('SRCURL=https://raw.githubusercontent.com/Microsoft/msphpsql/%SRCVERSION%/source/%PATH%' + os.linesep)
        f.write('SRCSRVCMD=powershell -Command ')
        f.write('\"$r=New-Object -ComObject Msxml2.XMLHTTP; ')
        f.write('$r.open(\'GET\', \'%SRCURL%\', $false); ')
        f.write('$r.send(); [io.file]::WriteAllBytes(\'%SRCSRVTRG%\', $r.responseBody)\"' + os.linesep)
        f.write('SRCVERSION=' + tag_version + os.linesep)
        f.write('PDBVERSION=' + tag_version + os.linesep)

def append_source_filess(index_filename, source_files, driver):
    """This appends the paths to different source files to the temporary index file

    For example

    SRCSRV: source files ---------------------------------------
    c:\php-sdk\phpdev\vc15\x86\php-7.2.14-src\ext\pdo_sqlsrv\pdo_dbh.cpp*pdo_sqlsrv/pdo_dbh.cpp
    c:\php-sdk\phpdev\vc15\x86\php-7.2.14-src\ext\pdo_sqlsrv\pdo_init.cpp*pdo_sqlsrv/pdo_init.cpp
    ... ...
    c:\php-sdk\phpdev\vc15\x86\php-7.2.14-src\ext\pdo_sqlsrv\shared\core_stream.cpp*shared/core_stream.cpp
    c:\php-sdk\phpdev\vc15\x86\php-7.2.14-src\ext\pdo_sqlsrv\shared\core_util.cpp*shared/core_util.cpp
    SRCSRV: end ------------------------------------------------
    """
    failed = False
    with open(index_filename, 'a') as idx_file:
        idx_file.write('SRCSRV: source files ---------------------------------------' + os.linesep)
        with open(source_files, 'r') as src_file:
            for line in src_file:
                pos = line.find('shared')
                if (pos > 0):           # it's a nested folder, so it must be positive
                    relative_path = line[pos:]
                    src_line = line[:-1] + '*' + relative_path.replace('\\', '/')
                else:                   # not a file in the shared folder
                    pos = line.find(driver)
                    if (pos <= 0):
                        print('ERROR: Expected to find', driver, 'in', line)
                        failed = True
                        break
                    else:
                        relative_path = line[pos:]
                        src_line = line[:-1] + '*' + relative_path.replace('\\', '/')
                idx_file.write(src_line)
        idx_file.write('SRCSRV: end ------------------------------------------------' + os.linesep)
    return failed

def run_indexing_tools(pdbfile, driver, tag_version):
    """This invokes the source indexing tools, srctool.exe and pdbstr.exe

    :param  pdbfile: the absolute path to the symbol file
    :param  driver: either sqlsrv or pdo_sqlsrv
    :param  tag_version: tag version for source indexing
    :outcome: the driver pdb file will be source indexed
    """
    # run srctool.exe to get all driver's source files from the PDB file
    # srctool.exe -r <PDBfile> | find "<driver>\" | sort > files.txt
    batch_filename = 'runsrctool.bat'
    index_filename = 'idx.txt'
    source_files = 'files.txt'
    
    with open(batch_filename, 'w') as batch_file:
        batch_file.write('@ECHO OFF' + os.linesep)
        batch_file.write('@CALL srctool -r %1 | find "%2\\" | sort > ' + source_files + '  2>&1' + os.linesep)
    
    get_source_filess = batch_filename + ' {0} {1} '
    get_source_filess_cmd = get_source_filess.format(pdbfile, driver)
    subprocess.call(get_source_filess_cmd)
    
    # create an index file using the above inputs for pdbstr.exe
    write_index(index_filename, tag_version)
    failed = append_source_filess(index_filename, source_files, driver)
    
    if failed:
        print("ERROR: Failed to prepare the temporary index file for the pdbstr tool")
        exit(1)

    # run pdbstr.exe to insert the information into the PDB file
    # pdbstr.exe -w -p:<PDBfile> -i:idx.txt -s:srcsrv
    pdbstr_str = 'pdbstr.exe -w -p:{0} -i:{1} -s:srcsrv'
    pdbstr_cmd = pdbstr_str.format(pdbfile, index_filename)
    subprocess.call(pdbstr_cmd)
    
    os.remove(batch_filename)
    os.remove(index_filename)
    os.remove(source_files)

################################### Main Function ###################################
if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('PDBFILE', help="the path to the pdb file for source indexing")
    parser.add_argument('DRIVER', choices=['sqlsrv', 'pdo_sqlsrv'], help="driver name of this pdb file")
    parser.add_argument('TAG_VERSION', help="the tag version for source indexing (e.g. v5.6.0)")
    parser.add_argument('TOOLS_PATH',help="the path to the source indexing tools")
    
    args = parser.parse_args()

    srctool_exe = os.path.join(args.TOOLS_PATH, 'srctool.exe')
    pdbstr_exe = os.path.join(args.TOOLS_PATH, 'pdbstr.exe')
    if not os.path.exists(srctool_exe) or not os.path.exists(pdbstr_exe):
        print('ERROR: Missing the required source indexing tools')
        exit(1)

    work_dir = os.path.dirname(os.path.realpath(__file__))
    os.chdir(args.TOOLS_PATH)
    
    print('Source indexing begins...')
    run_indexing_tools(args.PDBFILE, args.DRIVER.lower(), args.TAG_VERSION)
    print('Source indexing done')
    
    os.chdir(work_dir)

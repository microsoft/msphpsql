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
import subprocess

def write_template(index_filename, tag_version):
    """This writes to a temporary index file for later use

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
        f.write('SRCSRV: ini ------------------------------------------------\n')
        f.write('VERSION=1\n')
        f.write('SRCSRV: variables ------------------------------------------\n')
        f.write('PATH=%var2%\n')
        f.write('SRCSRVTRG=%TARG%\%PDBVERSION%\%fnbksl%(%var2%)\n')
        f.write('SRCURL=https://raw.githubusercontent.com/Microsoft/msphpsql/%SRCVERSION%/source/%PATH%\n')
        f.write('SRCSRVCMD=powershell -Command ')
        f.write('\"$r=New-Object -ComObject Msxml2.XMLHTTP; ')
        f.write('$r.open(\'GET\', \'%SRCURL%\', $false); ')
        f.write('$r.send(); [io.file]::WriteAllBytes(\'%SRCSRVTRG%\', $r.responseBody)\"\n')
        f.write('SRCVERSION=' + tag_version + '\n')
        f.write('PDBVERSION=' + tag_version + '\n')

def append_source_files(index_filename, source_file, driver):
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
    with open(index_filename, 'a') as idx_file:
        idx_file.write('SRCSRV: source files ---------------------------------------\n')
        with open(source_file, 'r') as src_file:
            for line in src_file:
                if 'indexed' not in line:   # skip this line
                    pos = line.find('shared')
                    if (pos > 0):           # if found, it must be positive
                        relative_path = line[pos:]
                        src_line = line[:-1] + '*' + relative_path.replace('\\', '/')
                    else:
                        pos = line.find(driver)
                        if (pos <= 0):
                            print('Something is wrong!!')
                            break
                        else:
                            relative_path = line[pos:]
                            src_line = src_line = line[:-1] + '*' + relative_path.replace('\\', '/')
                    idx_file.write(src_line)
        idx_file.write('SRCSRV: end ------------------------------------------------\n')

def run_indexing_tools(pdbfile, driver, tag_version):
    """This invokes the source indexing tools

    :param  pdbfile: the absolute path to the symbol file
    :param  driver: either sqlsrv or pdo_sqlsrv
    :param  tag_version: tag version for source indexing
    :outcome: the driver pdb file will be source indexed
    """
    # run srctool.exe to get all the driver's source files from the PDB file
    # srctool.exe -r <PDBfile> | find "<driver>" | find /v "dll" | sort > files.txt
    source_file = 'files.txt'
    index_filename = 'idx.txt'

    srctool_str = 'srctool.exe -r {0} | find \"{1}\" | find /v \"dll\" | sort > {2}'
    srctool_cmd = srctool_str.format(pdbfile, driver, source_file)
    subprocess.call(srctool_cmd)

    # create an index file using the above inputs for pdbstr.exe
    write_template(index_filename, tag_version)
    append_source_files(index_filename, source_file, driver)

    # run pdbstr.exe to insert the information into the PDB file
    # pdbstr.exe -w -p:<PDBfile> -i:idx.txt -s:srcsrv
    pdbstr_str = 'pdbstr.exe -w -p:{0} -i:{1} -s:srcsrv'
    pdbstr_cmd = pdbstr_str.format(pdbfile, index_filename)
    subprocess.call(pdbstr_cmd)


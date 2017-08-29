#!/usr/bin/python3
#########################################################################################
#
# Description:  This script builds a custom keystore provider and compiles the app that 
#               uses this KSP. Their names can be passed as arguments, but the outputs
#               are always 
#               - myKSP.dll (myKSPx64.dll) / myKSP.so 
#               - ksp_app.exe / ksp_app
#
# Requirement:
#               python 3.x
#               myKSP.c (or any equivalent)
#               ksp_app.c (or any equivalent)
#               msodbcsql.h (odbc header file)
#
# Execution: Run with command line with optional options
#            py build_ksp.py --KSP myKSP --APP ksp_app
#
#############################################################################################

import sys
import os
import platform
import argparse

# This creates a batch *filename*, which compiles a C program according to 
# *command* and *arch* (either x86 or x64)
def create_batch_file(arch, filename, command):
    root_dir = 'C:' + os.sep
    vcvarsall = os.path.join(root_dir, "Program Files (x86)", "Microsoft Visual Studio 14.0", "VC", "vcvarsall.bat")

    try:
        file = open(filename, 'w')
        file.write('@ECHO OFF' + os.linesep)
        if arch == 'x64':
            file.write('@CALL "' + vcvarsall + '" amd64' + os.linesep)
        else:
            file.write('@CALL "' + vcvarsall + '" x86' + os.linesep)
            
        # compile the code
        file.write('@CALL ' + command + os.linesep)
        file.close()
    except:
        print('Cannot create ', filename)

# This invokes the newly created batch file to compile the code, 
# according to *arch* (either x86 or x64). The batch file will be 
# removed afterwards
def compile_KSP_windows(arch, ksp_src):
    output = 'myKSP'
    if arch == 'x64':
        output = output + arch + '.dll'
    else:
        output = output + '.dll'

    command = 'cl {0} /LD /MD /link /out:'.format(ksp_src) + output
    batchfile = 'build_KSP.bat'
    create_batch_file(arch, batchfile, command)
    os.system(batchfile)
    os.remove(batchfile)

# This compiles myKSP.c
#
# In Windows, this will create batch files to compile two dll(s). 
# Otherwise, this will compile the code and generate a .so file.
#
# Output:   A custom keystore provider created 
def compile_KSP(ksp_src):
    print('Compiling ', ksp_src)
    if platform.system() == 'Windows':
        compile_KSP_windows('x64', ksp_src)
        compile_KSP_windows('x86', ksp_src)
    else:
        os.system('gcc -fshort-wchar -fPIC -o myKSP.so -shared {0}'.format(ksp_src))

# This compiles ksp app, which assumes the existence of the .dll or the .so file.
#
# In Windows, a batch file is created in order to compile the code. 
def configure_KSP(app_src):
    print('Compiling ', app_src)
    if platform.system() == 'Windows':
        command = 'cl /MD {0} /link odbc32.lib /out:ksp_app.exe'.format(app_src)
        batchfile = 'build_app.bat'
        create_batch_file('x86', batchfile, command)
        os.system(batchfile)
        os.remove(batchfile)        
    else:
        os.system('gcc -o ksp_app -fshort-wchar {0} -lodbc -ldl'.format(app_src))

################################### Main Function ###################################
if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('-ksp', '--KSPSRC', default='myKSP.c', help='The source file of KSP (keystore provider)')
    parser.add_argument('-app', '--APPSRC', default='ksp_app.c', help='The source file for the app that uses the KSP')
    args = parser.parse_args()

    ksp_src = args.KSPSRC
    app_src = args.APPSRC
    header = 'msodbcsql.h'

    cwd = os.getcwd()

    # make sure all required source and header files are present
    work_dir = os.path.dirname(os.path.realpath(__file__))   
    os.chdir(work_dir) 
        
    if not os.path.exists(os.path.join(work_dir, header)):
        print('Error: {0} not found!'.format(header))
        exit(1)
    if not os.path.exists(os.path.join(work_dir, ksp_src)):
        print('Error: {0}.c not found!'.format(ksp_src))
        exit(1)
    if not os.path.exists(os.path.join(work_dir, app_src)):
        print('Error: {0}.c not found!'.format(app_src))
        exit(1)
    
    compile_KSP(ksp_src)
    configure_KSP(app_src)
    
    os.chdir(cwd)    

    
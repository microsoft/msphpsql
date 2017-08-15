#!/usr/bin/python3
#########################################################################################
#
# Description:  This script helps to build drivers in a Windows environment for PHP 7+ (32-bit/64-bit)
#
# Requirement:
#               python 3.x
#               PHP SDK and PHP Source 
#               Driver source code folder / GitHub repository
#               Visual Studio 2015 (PHP 7.0* and 7.1*) and Visual Studio 2017 (PHP 7.2*)
#
# Execution: Run with command line with required options.
# Examples: 
#           py builddrivers.py (for interactive mode)
#           py builddrivers.py -v=7.1.7 -a=x86 -t=ts -d=all -g=no
#           py builddrivers.py --PHPVER=7.0.22 --ARCH=x64 --THREAD=nts --DRIVER=all --GITHUB=yes
#
# Output: Build the drivers using PHP SDK. When running locally, if build is unsuccessful, 
#         the log file will be launched for examination. Otherwise, the drivers will be renamed 
#         and copied to the designated location(s).
#
#############################################################################################

import sys
import shutil
import os.path
import argparse
from buildtools import BuildUtil

class BuildDriver(object):
    """Build sqlsrv and/or pdo_sqlsrv drivers with PHP source with the following properties:
    
    Attributes:
        util            # BuildUtil object whose constructor takes phpver, driver, arch, thread, debug 
        repo            # GitHub repository
        branch          # GitHub repository branch
        download_source # download source from GitHub or not
        remote_path     # remote destination to where the drivers will be placed (None for local builds)
        local           # a boolean flag - whether the build is local
        rebuild         # a boolean flag - whether the user is rebuilding
        make_clean      # a boolean flag - whether make clean is necessary
        source_path     # path to a local source folder
    """
    
    def __init__(self, phpver, driver, arch, thread, debug, repo, branch, download, path):
        self.util = BuildUtil(phpver, driver, arch, thread, debug)
        self.repo = repo
        self.branch = branch
        self.download_source = download
        self.remote_path = path
        self.local = path is None   # the default path is None, which means running locally 
        self.rebuild = False
        self.make_clean = False
        self.source_path = None     # None initially but will be set later if not downloading from GitHub
    
    def show_config(self):
        print()
        print('PHP Version: ', self.util.phpver)
        print('Arch: ', self.util.arch)
        print('Thread: ', self.util.thread)
        print('Driver: ', self.util.driver) 
        print('Debug enabled: ', self.util.debug_enabled) 
        print()

    def clean_or_remove(self, root_dir, work_dir):
        """Only check this when building locally and not rebuilding. If the php source directory 
        already exists, this will prompt user whether to rebuild, clean, or superclean, the last option
        will remove the entire php source directory.
        
        :param  root_dir: the C:\ drive
        :param  work_dir: the directory of this script
        :outcome: the old binaries, if exist, will be removed
        """
        phpsrc = self.util.phpsrc_root(root_dir)
        if os.path.exists( phpsrc ):
            print(phpsrc + " exists.")
            print("Choose rebuild(r) if using the same configuration. Choose clean(c) otherwise. If unsure, choose superclean(s).") 
            build_choice = validate_input("Want to rebuild (r), clean (c) or superclean (s)? ", "r/c/s")
            self.make_clean = False
            if build_choice == 'r':
                print('Will rebuild the binaries')
                # only the old binaries based on the current configuration will be removed
                self.util.remove_prev_build(root_dir)
            elif build_choice == 'c':
                print('Will make clean')
                self.make_clean = True
                # all old builds are removed, and this step is necessary because 
                # the user might have changed the configuration
                self.util.remove_old_builds(root_dir)
            else:
                print('Will remove ' + phpsrc)
                os.system('RMDIR /s /q ' + phpsrc)
                
            os.chdir(work_dir)  # change back to the working directory

    def build_extensions(self, dest, logfile):
        """This takes care of getting the drivers' source files, building the drivers. 
        If running locally, *dest* should be the root drive. Otherwise, *dest* should be None. 
        In this case, remote_path must be defined such that the binaries will be copied 
        to the designated destinations.
        
        :param  dest: either None (for remote builds) or the C:\ drive (for local builds)
        :param  logfile: the name of the logfile
        :outcome: the drivers and symbols will renamed and placed in the appropriate location(s)

        """
        work_dir = os.path.dirname(os.path.realpath(__file__))
            
        if self.download_source:
            # This will download from the specified branch on GitHub repo and copy the source to the working directory
            self.util.download_msphpsql_source(repo, branch)
        else:
            # This case only happens when building locally (interactive mode)
            # because download_source must be True for remote builds
            while True:
                if self.source_path is None:
                    source = input('Enter the full path to the Source folder: ')
                else:
                    source = input("Hit ENTER to reuse '" + self.source_path + "' or provide another path to the Source folder: ")
                    if len(source) == 0:
                        source = self.source_path

                valid = True
                if os.path.exists(source) and os.path.exists(os.path.join(source, 'shared')):
                    # Checking the existence of 'shared' folder only, assuming
                    # sqlsrv and/or pdo_sqlsrv are also present if it exists
                    self.source_path = source
                    break
                    
                print("The path provided is invalid. Please re-enter.")            
                                
            print('Copying source files from', source)
                
            os.system('ROBOCOPY ' + source + '\shared ' + work_dir + '\Source\shared /xx /xo ')
            os.system('ROBOCOPY ' + source + '\sqlsrv ' + work_dir + '\Source\sqlsrv /xx /xo ')
            os.system('ROBOCOPY ' + source + '\pdo_sqlsrv ' + work_dir + '\Source\pdo_sqlsrv /xx /xo ')
                    
        print('Start building PHP with the extension...')

        self.util.build_drivers(self.make_clean, dest, logfile)

        if dest is None:       
            # This indicates the script is NOT running locally, and that 
            # the drivers should be in the working directory

            # Make sure drivers path is defined
            if self.remote_path is None:
                print('Errors: Drivers destination should be defined! Doing nothing.')
            else:
                dest_drivers = os.path.join(self.remote_path, self.util.major_version(), self.util.arch)
                dest_symbols = os.path.join(dest_drivers, 'Symbols')
                
                # All intermediate directories will be created in order to create the leaf directory
                if os.path.exists(dest_symbols) == False:
                    os.makedirs(dest_symbols)
                    
                # Now copy all the binaries
                if self.util.driver == 'all':
                    self.util.copy_binary(work_dir, dest_drivers, 'sqlsrv', '.dll')
                    self.util.copy_binary(work_dir, dest_symbols, 'sqlsrv', '.pdb')
                    self.util.copy_binary(work_dir, dest_drivers, 'pdo_sqlsrv', '.dll')
                    self.util.copy_binary(work_dir, dest_symbols, 'pdo_sqlsrv', '.pdb')
                else:
                    self.util.copy_binary(work_dir, dest_drivers, self.util.driver, '.dll')
                    self.util.copy_binary(work_dir, dest_symbols, self.util.driver, '.pdb')
    

    def build(self):
        """This is the main entry point of building drivers for PHP. 
        For local builds, this will loop till the user decides to quit.
        """
        self.show_config()
    
        work_dir = os.path.dirname(os.path.realpath(__file__))
        root_dir = 'C:' + os.sep
        
        quit = False
        while not quit:
            if not self.rebuild and self.local:
                self.clean_or_remove(root_dir, work_dir)
                
            logfile = self.util.get_logfile_name()

            try:
                dest = None
                if self.local:
                    dest = root_dir
                    
                self.build_extensions(dest, logfile)
                print('Build Completed')
            except:
                print('Something went wrong. Build incomplete.')
                if self.local:          # display log file only when building locally
                    os.startfile(os.path.join(root_dir, 'php-sdk', logfile))
                os.chdir(work_dir)    
                break

            # Only ask when building locally
            if self.local:               
                choice = input("Rebuild the same configuration(yes) or quit (no) [yes/no]: ")

                if choice.lower() == 'yes' or choice.lower() == 'y' or choice.lower() == '':
                    print('Rebuilding drivers...')
                    self.make_clean = False
                    self.rebuild = True
                    self.util.remove_prev_build(root_dir)
                else:
                    quit = True
            else:
                quit = True
            
            os.chdir(work_dir)    

def validate_input(question, values):
    """Return the user selected value, and it must be valid based on *values*."""
    while True:
        options = values.split('/') 
        prompt = '[' + values + ']'
        value = input(question + prompt + ': ')
        value = value.lower()
        if not value in options:
            print("An invalid choice is entered. Choose from", prompt)
        else:
            break
    return value

################################### Main Function ###################################
if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('-v', '--PHPVER', help="PHP version, e.g. 7.1.*, 7.2.* etc.")
    parser.add_argument('-a', '--ARCH', choices=['x64', 'x86'])
    parser.add_argument('-t', '--THREAD', choices=['nts', 'ts'])
    parser.add_argument('-d', '--DRIVER', choices=['all', 'sqlsrv', 'pdo_sqlsrv'])
    parser.add_argument('-m', '--DEBUG', default='no', choices=['yes', 'no'], help="enable debug mode")
    parser.add_argument('-r', '--REPO', default='Microsoft', help="GitHub repository")
    parser.add_argument('-b', '--BRANCH', default='dev', help="GitHub repository branch")
    parser.add_argument('-g', '--GITHUB', default='yes', help="get source from GitHub or not")
    parser.add_argument('-p', '--DESTPATH', default=None, help="the remote destination for the drivers (do not use this for local builds)")

    args = parser.parse_args()

    phpver = args.PHPVER
    arch = args.ARCH
    thread = args.THREAD
    driver = args.DRIVER
    debug = args.DEBUG == 'yes'
    repo = args.REPO
    branch = args.BRANCH
    download = args.GITHUB.lower() == 'yes'
    path = args.DESTPATH

    if phpver is None:
        # assuming it is building drivers locally when required to prompt
        # thus will not prompt for drivers' destination path, which is None by default
        phpver = input("PHP Version (e.g. 7.1.* or 7.2.*): ")
        arch_version = input("64-bit? [y/n]: ")
        thread = validate_input("Thread safe? ", "nts/ts")
        driver = validate_input("Driver to build? ", "all/sqlsrv/pdo_sqlsrv")
        debug_mode = input("Debug enabled? [y/n]: ")

        answer = input("Download source from a GitHub repo? [y/n]: ")
        download = False
        if answer == 'yes' or answer == 'y' or answer == '':
            download = True
            repo = input("Name of the repo (hit enter for 'Microsoft'): ")
            branch = input("Name of the branch (hit enter for 'dev'): ")
            if repo == '':
                repo = 'Microsoft'
            if branch == '':
                branch = 'dev'
            
        arch_version = arch_version.lower()    
        arch = 'x64' if arch_version == 'yes' or arch_version == 'y' or arch_version == '' else 'x86'
        
        debug_mode = debug_mode.lower()
        debug = debug_mode == 'yes' or debug_mode == 'y' or debug_mode == '' 
        
    builder = BuildDriver(phpver, 
                          driver, 
                          arch, 
                          thread, 
                          debug, 
                          repo, 
                          branch, 
                          download, 
                          path)
    builder.build()

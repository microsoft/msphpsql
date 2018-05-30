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
#           py builddrivers.py --PHPVER=7.0.22 --ARCH=x64 --THREAD=nts --DRIVER=all --DEBUG
#
# Output: Build the drivers using PHP SDK. When running for local development, if build is unsuccessful, 
#         the log file will be launched for examination. Otherwise, the drivers will be renamed 
#         and copied to the designated location (if defined).
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
        dest_path       # alternative destination for the drivers (None for development builds)
        rebuild         # a boolean flag - whether the user is rebuilding
        make_clean      # a boolean flag - whether make clean is necessary
        source_path     # path to a local source folder
        testing         # whether the user has turned on testing mode
    """
    
    def __init__(self, phpver, driver, arch, thread, debug, repo, branch, source, path, testing, no_rename):
        self.util = BuildUtil(phpver, driver, arch, thread, no_rename, debug)
        self.repo = repo
        self.branch = branch
        self.source_path = source
        self.dest_path = path
        self.testing = testing
        self.rebuild = False
        self.make_clean = False
    
    def show_config(self):
        print()
        print('PHP Version: ', self.util.phpver)
        print('Arch: ', self.util.arch)
        print('Thread: ', self.util.thread)
        print('Driver: ', self.util.driver) 
        print('Source: ', self.source_path)
        print('Debug enabled: ', self.util.debug_enabled) 
        print()

    def clean_or_remove(self, root_dir, work_dir):
        """Only check this for local development and not rebuilding. If the php source directory 
        already exists, this will prompt user whether to rebuild, clean, or superclean, the last option
        will remove the entire php source directory.
        
        :param  root_dir: the C:\ drive
        :param  work_dir: the directory of this script
        :outcome: the old binaries, if exist, will be removed
        """
        phpsrc = self.util.phpsrc_root(root_dir)
        if os.path.exists( phpsrc ):
            print(phpsrc + " exists.")
            build_choice = validate_input("(r)ebuild for the same configuration, (c)lean otherwise, (s)uperclean if unsure ", "r/c/s")
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

    def get_local_source(self, source_path):
        """This assumes interactive mode (not testing) and takes care of getting 
        the user's input to the path of the local source files for the drivers
        """
        while True:
            if source_path is None:
                source = input('Enter the full path to the source folder: ')
            else:
                source = input("Hit ENTER to use '" + source_path + "' or provide another path to the source folder: ")
                if len(source) == 0:
                    source = source_path

            valid = True
            if os.path.exists(source) and os.path.exists(os.path.join(source, 'shared')):
                # Checking the existence of 'shared' folder only, assuming
                # sqlsrv and/or pdo_sqlsrv are also present if it exists
                self.source_path = source
                break
                
            print("The path provided is invalid. Please re-enter.")
        return source
    
    def build_extensions(self, root_dir, logfile):
        """This takes care of getting the drivers' source files, building the drivers. 
        If dest_path is defined, the binaries will be copied to the designated destinations.
        
        :param  root_dir: the root directory
        :param  logfile: the name of the logfile
        :outcome: the drivers and symbols will renamed and placed in the appropriate location(s)

        """
        work_dir = os.path.dirname(os.path.realpath(__file__))
        
        get_source = False if self.source_path is None else True
        if self.repo is None or self.branch is None:
            # If GitHub repo or branch is None, get the source locally 
            get_source = True

        if not get_source:
            # This will download from the specified branch on GitHub repo and copy the source
            self.util.download_msphpsql_source(repo, branch)
        else:
            source = self.source_path 
            # Do not prompt user for input if it's in a testing mode 
            if not self.testing:
                source = self.get_local_source(self.source_path)
            
            print('Copying source files from', source)
                
            os.system('ROBOCOPY ' + source + '\shared ' + work_dir + '\Source\shared /xx /xo ')
            os.system('ROBOCOPY ' + source + '\sqlsrv ' + work_dir + '\Source\sqlsrv /xx /xo ')
            os.system('ROBOCOPY ' + source + '\pdo_sqlsrv ' + work_dir + '\Source\pdo_sqlsrv /xx /xo ')
                    
        print('Start building PHP with the extension...')

        # If not testing, dest should be the root drive. Otherwise, dest should be None. 
        dest = None if self.testing else root_dir

        # ext_dir is the directory where we can find the built extension(s)
        ext_dir = self.util.build_drivers(self.make_clean, dest, logfile)

        # Copy the binaries if a destination path is defined
        if self.dest_path is not None:
            dest_drivers = os.path.join(self.dest_path, self.util.major_version(), self.util.arch)
            dest_symbols = os.path.join(dest_drivers, 'Symbols', self.util.thread)
            
            # All intermediate directories will be created in order to create the leaf directory
            if os.path.exists(dest_symbols) == False:
                os.makedirs(dest_symbols)
                
            # Now copy all the binaries
            if self.util.driver == 'all':
                self.util.copy_binary(ext_dir, dest_drivers, 'sqlsrv', '.dll')
                self.util.copy_binary(ext_dir, dest_symbols, 'sqlsrv', '.pdb')
                self.util.copy_binary(ext_dir, dest_drivers, 'pdo_sqlsrv', '.dll')
                self.util.copy_binary(ext_dir, dest_symbols, 'pdo_sqlsrv', '.pdb')
            else:
                self.util.copy_binary(ext_dir, dest_drivers, self.util.driver, '.dll')
                self.util.copy_binary(ext_dir, dest_symbols, self.util.driver, '.pdb')

        return ext_dir

    def build(self):
        """This is the main entry point of building drivers for PHP. 
        For development, this will loop till the user decides to quit.
        """
        self.show_config()
    
        work_dir = os.path.dirname(os.path.realpath(__file__))
        root_dir = 'C:' + os.sep
        
        quit = False
        while not quit:
            if self.testing:
                self.make_clean = True
                self.util.remove_old_builds(work_dir)
            elif not self.rebuild: 
                self.clean_or_remove(root_dir, work_dir)
                
            logfile = self.util.get_logfile_name()

            try:
                ext_dir = self.build_extensions(root_dir, logfile)
                print('Build Completed')
            except:
                print('Something went wrong, launching log file', logfile)
                # display log file only when not testing
                if not self.testing:
                    os.startfile(os.path.join(root_dir, 'php-sdk', logfile))
                os.chdir(work_dir)
                exit(1)

            if not self.testing:
                choice = input("Rebuild using the same configuration(yes) or quit (no) [yes/no]: ")
                choice = choice.lower()
                if choice == 'yes' or choice == 'y' or choice == '':
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
    parser.add_argument('--PHPVER', help="PHP version, e.g. 7.1.*, 7.2.* etc.")
    parser.add_argument('--ARCH', choices=['x64', 'x86'])
    parser.add_argument('--THREAD', choices=['nts', 'ts'])
    parser.add_argument('--DRIVER', default='all', choices=['all', 'sqlsrv', 'pdo_sqlsrv'], help="driver to build (default: all)")
    parser.add_argument('--DEBUG', action='store_true', help="enable debug mode (default: False)")
    parser.add_argument('--REPO', default='Microsoft', help="GitHub repository (default: Microsoft)")
    parser.add_argument('--BRANCH', default='dev', help="GitHub repository branch (default: dev)")
    parser.add_argument('--SOURCE', default=None, help="a local path to source file (default: None)")
    parser.add_argument('--TESTING', action='store_true', help="turns on testing mode (default: False)")
    parser.add_argument('--DESTPATH', default=None, help="an alternative destination for the drivers (default: None)")
    parser.add_argument('--NO_RENAME', action='store_true', help="drivers will not be renamed(default: False)")

    args = parser.parse_args()

    phpver = args.PHPVER
    arch = args.ARCH
    thread = args.THREAD
    driver = args.DRIVER
    debug = args.DEBUG
    repo = args.REPO
    branch = args.BRANCH
    source = args.SOURCE
    path = args.DESTPATH
    testing = args.TESTING
    no_rename = args.NO_RENAME

    if phpver is None:
        # starts interactive mode, testing mode is False
        # will not prompt for drivers' destination path, which is None by default
        while True:
            # perform some minimal checks
            phpver = input("PHP Version (e.g. 7.1.* or 7.2.*): ")
            if phpver == '':
                print('Empty PHP version entered! Please try again.')
            elif phpver[0] < '7':
                print('Only PHP 7.0 or above is supported. Please try again.')
            else:
                break
                
        arch_version = input("64-bit? [y/n]: ")
        thread = validate_input("Thread safe? ", "nts/ts")
        driver = validate_input("Driver to build? ", "all/sqlsrv/pdo_sqlsrv")
        debug_mode = input("Debug enabled? [y/n]: ")
        
        answer = input("Download source from a GitHub repo? [y/n]: ")
        if answer == 'yes' or answer == 'y' or answer == '':
            repo = input("Name of the repo (hit enter for 'Microsoft'): ")
            branch = input("Name of the branch (hit enter for 'dev'): ")
            if repo == '':
                repo = 'Microsoft'
            if branch == '':
                branch = 'dev'
        else:
            repo = branch = None

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
                          source, 
                          path,
                          testing,
                          no_rename)
    builder.build()

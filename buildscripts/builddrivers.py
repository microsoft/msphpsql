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
import subprocess
import re

# Import BuildUtil from the fixed version we created earlier
# Note: This assumes BuildUtil class is defined in buildutil.py
# If it's in the same file, remove this import and include the class directly
try:
    from buildutil import BuildUtil
except ImportError:
    # If buildutil.py doesn't exist, we'll define a minimal version here
    # but for production, you should have the actual BuildUtil class
    print("Error: BuildUtil class not found. Please ensure buildutil.py exists.")
    sys.exit(1)

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
        if os.path.exists(phpsrc):
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
                # Use subprocess instead of os.system for security
                try:
                    # Use shutil.rmtree for cross-platform compatibility
                    shutil.rmtree(phpsrc, ignore_errors=True)
                    print(f"Removed {phpsrc}")
                except Exception as e:
                    print(f"Error removing directory {phpsrc}: {e}")
                
            # Change back to the working directory
            try:
                os.chdir(work_dir)
            except OSError as e:
                print(f"Warning: Could not change to directory {work_dir}: {e}")

    def sanitize_path(self, path):
        """Sanitize a path to prevent command injection."""
        # Remove any dangerous characters
        if not path:
            return path
        # Replace backslashes with forward slashes for consistency
        path = path.replace('\\', '/')
        # Remove any command injection attempts
        dangerous_chars = [';', '&', '|', '`', '$', '(', ')', '{', '}', '[', ']', '<', '>', '!']
        for char in dangerous_chars:
            path = path.replace(char, '')
        return path

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
            
            # Sanitize the path
            source = self.sanitize_path(source)
            
            # Check if path exists and has required structure
            if not source or len(source.strip()) == 0:
                print("Empty path provided. Please re-enter.")
                continue
                
            # Validate the path
            if os.path.exists(source):
                # Check for required folders
                shared_exists = os.path.exists(os.path.join(source, 'shared'))
                sqlsrv_exists = os.path.exists(os.path.join(source, 'sqlsrv'))
                pdo_sqlsrv_exists = os.path.exists(os.path.join(source, 'pdo_sqlsrv'))
                
                if shared_exists and (sqlsrv_exists or pdo_sqlsrv_exists):
                    self.source_path = source
                    break
                else:
                    missing = []
                    if not shared_exists:
                        missing.append('shared')
                    if self.util.driver in ['all', 'sqlsrv'] and not sqlsrv_exists:
                        missing.append('sqlsrv')
                    if self.util.driver in ['all', 'pdo_sqlsrv'] and not pdo_sqlsrv_exists:
                        missing.append('pdo_sqlsrv')
                    print(f"Missing required folders: {', '.join(missing)}. Please re-enter.")
            else:
                print("The path provided does not exist. Please re-enter.")
        return source
    
    def copy_source_files_safely(self, source, work_dir):
        """Safely copy source files using shutil instead of os.system."""
        try:
            # Create Source directory if it doesn't exist
            source_dir = os.path.join(work_dir, 'Source')
            os.makedirs(source_dir, exist_ok=True)
            
            # Define source and destination paths
            shared_src = os.path.join(source, 'shared')
            shared_dst = os.path.join(source_dir, 'shared')
            
            sqlsrv_src = os.path.join(source, 'sqlsrv')
            sqlsrv_dst = os.path.join(source_dir, 'sqlsrv')
            
            pdo_sqlsrv_src = os.path.join(source, 'pdo_sqlsrv')
            pdo_sqlsrv_dst = os.path.join(source_dir, 'pdo_sqlsrv')
            
            # Copy directories if they exist
            def copy_if_exists(src, dst):
                if os.path.exists(src):
                    if os.path.exists(dst):
                        shutil.rmtree(dst, ignore_errors=True)
                    shutil.copytree(src, dst)
                    return True
                return False
            
            print(f'Copying source files from {source}')
            
            # List source directory contents for debugging
            if os.path.exists(source):
                try:
                    dir_list = os.listdir(source)
                    print(f"Files and directories in '{source}':")
                    for item in dir_list:
                        print(f"  {item}")
                except OSError as e:
                    print(f"Warning: Could not list directory {source}: {e}")
            
            # Copy required directories
            copied_any = False
            
            if copy_if_exists(shared_src, shared_dst):
                copied_any = True
                print(f"Copied shared files to {shared_dst}")
            
            if self.util.driver in ['all', 'sqlsrv']:
                if copy_if_exists(sqlsrv_src, sqlsrv_dst):
                    copied_any = True
                    print(f"Copied sqlsrv files to {sqlsrv_dst}")
            
            if self.util.driver in ['all', 'pdo_sqlsrv']:
                if copy_if_exists(pdo_sqlsrv_src, pdo_sqlsrv_dst):
                    copied_any = True
                    print(f"Copied pdo_sqlsrv files to {pdo_sqlsrv_dst}")
            
            if not copied_any:
                raise FileNotFoundError(f"No source files found in {source}. Required: shared, and sqlsrv or pdo_sqlsrv")
                
            return True
            
        except Exception as e:
            print(f"Error copying source files: {e}")
            raise

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
            try:
                self.util.download_msphpsql_source(self.repo, self.branch)
            except Exception as e:
                print(f"Error downloading from GitHub: {e}")
                print("Falling back to local source...")
                get_source = True
        
        if get_source:
            source = self.source_path 
            # Do not prompt user for input if it's in a testing mode 
            if not self.testing:
                source = self.get_local_source(self.source_path)
            
            # Copy source files safely
            self.copy_source_files_safely(source, work_dir)
                    
        print('Start building PHP with the extension...')

        # If not testing, dest should be the root drive. Otherwise, dest should be None. 
        dest = None if self.testing else root_dir

        # ext_dir is the directory where we can find the built extension(s)
        ext_dir = self.util.build_drivers(self.make_clean, dest, logfile)

        print('Build completed')
        
        # Copy the binaries if a destination path is defined
        if self.dest_path is not None:
            dest_drivers = os.path.join(self.dest_path, self.util.major_version(), self.util.arch)
            dest_symbols = os.path.join(dest_drivers, 'Symbols', self.util.thread)
            
            # All intermediate directories will be created in order to create the leaf directory
            if not os.path.exists(dest_symbols):
                os.makedirs(dest_symbols, exist_ok=True)
                
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
        
        quit_flag = False
        while not quit_flag:
            if self.testing:
                self.make_clean = True
                self.util.remove_old_builds(work_dir)
            elif not self.rebuild: 
                self.clean_or_remove(root_dir, work_dir)
                
            logfile = self.util.get_logfile_name()

            try:
                ext_dir = self.build_extensions(root_dir, logfile)
                print('Build Completed')
            except Exception as e:
                print(f'Something went wrong: {e}')
                print('Launching log file', logfile)

                logfile_path = os.path.join(os.getcwd(), logfile)

                if os.path.isfile(logfile_path):
                    try:
                        with open(logfile_path, 'r', encoding='utf-8') as f:
                            print("\n=== Last 50 lines of build log ===")
                            lines = f.readlines()
                            # Show last 50 lines for context
                            for line in lines[-50:]:
                                print(line.rstrip())
                    except Exception as read_error:
                        print(f"Error reading log file: {read_error}")
                else:
                    print('Unable to open logfile')
                        
                try:
                    os.chdir(work_dir)
                except OSError as dir_error:
                    print(f"Warning: Could not change to directory {work_dir}: {dir_error}")
                
                # Don't exit in interactive mode, allow retry
                if not self.testing:
                    retry = input("Would you like to retry? (yes/no): ").lower()
                    if retry in ['yes', 'y', '']:
                        continue
                
                sys.exit(1)

            if not self.testing:
                while True:
                    choice = input("Rebuild using the same configuration(yes) or quit (no) [yes/no]: ").lower()
                    if choice in ['yes', 'y', '']:
                        print('Rebuilding drivers...')
                        self.make_clean = False
                        self.rebuild = True
                        self.util.remove_prev_build(root_dir)
                        break
                    elif choice in ['no', 'n']:
                        quit_flag = True
                        break
                    else:
                        print("Please enter 'yes' or 'no'")
            else:
                quit_flag = True
            
            try:
                os.chdir(work_dir)
            except OSError as e:
                print(f"Warning: Could not change to directory {work_dir}: {e}")

def validate_input(question, values):
    """Return the user selected value, and it must be valid based on *values*."""
    while True:
        options = values.split('/') 
        prompt = '[' + values + ']'
        value = input(question + prompt + ': ')
        value = value.lower()
        if value not in options:
            print(f"An invalid choice is entered. Choose from {prompt}")
        else:
            break
    return value

def validate_php_version(version):
    """Validate PHP version format."""
    if not version:
        return False
    # Pattern for PHP versions like 7.0.22, 7.4, 8.0.3, etc.
    pattern = r'^(\d+)\.(\d+)(\.\d+)?([-\.](RC\d+|beta\d+|alpha\d+|[a-zA-Z]+))?$'
    match = re.match(pattern, version)
    if not match:
        return False
    major = int(match.group(1))
    return major >= 7

################################### Main Function ###################################
if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--PHPVER', help="PHP version, e.g. 7.4.* etc.")
    parser.add_argument('--ARCH', choices=['x64', 'x86'])
    parser.add_argument('--THREAD', choices=['nts', 'ts'])
    parser.add_argument('--DRIVER', default='all', choices=['all', 'sqlsrv', 'pdo_sqlsrv'], help="driver to build (default: all)")
    parser.add_argument('--DEBUG', action='store_true', help="enable debug mode (default: False)")
    parser.add_argument('--REPO', default='Microsoft', help="GitHub repository (default: Microsoft)")
    parser.add_argument('--BRANCH', default='dev', help="GitHub repository branch or tag (default: dev)")
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
            if not phpver:
                print('Empty PHP version entered! Please try again.')
                continue
                
            if not validate_php_version(phpver):
                print(f'Invalid PHP version format: {phpver}. Must be 7.0 or above (e.g., 7.0.22, 7.4, 8.0.3).')
                continue
                
            # Check major version
            try:
                major_version = int(phpver.split('.')[0])
                if major_version < 7:
                    print('Only PHP 7.0 or above is supported. Please try again.')
                    continue
            except (ValueError, IndexError):
                print('Invalid version format. Please try again.')
                continue
                
            break
                
        arch_version = input("64-bit? [y/n]: ")
        thread = validate_input("Thread safe? ", "nts/ts")
        driver = validate_input("Driver to build? ", "all/sqlsrv/pdo_sqlsrv")
        debug_mode = input("Debug enabled? [y/n]: ")
        
        answer = input("Download source from a GitHub repo? [y/n]: ")
        if answer.lower() in ['yes', 'y', '']:
            repo_input = input("Name of the repo (hit enter for 'Microsoft'): ")
            branch_input = input("Name of the branch or tag (hit enter for 'dev'): ")
            repo = repo_input if repo_input else 'Microsoft'
            branch = branch_input if branch_input else 'dev'
        else:
            repo = branch = None

        arch_version = arch_version.lower()
        arch = 'x64' if arch_version in ['yes', 'y', ''] else 'x86'
        
        debug_mode = debug_mode.lower()
        debug = debug_mode in ['yes', 'y', '']
        
    else:
        # Validate command line PHP version
        if not validate_php_version(phpver):
            print(f'Error: Invalid PHP version format: {phpver}. Must be 7.0 or above (e.g., 7.0.22, 7.4, 8.0.3).')
            sys.exit(1)

    try:
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
    except KeyboardInterrupt:
        print("\n\nBuild interrupted by user.")
        sys.exit(1)
    except Exception as e:
        print(f"\nFatal error: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)

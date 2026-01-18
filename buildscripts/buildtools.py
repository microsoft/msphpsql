#!/usr/bin/python3
#########################################################################################
#
# Description:  The class BuildUtil will build Microsoft SQL Server PHP 7+ Drivers 
#               for 32 bit and 64 bit.
#
# Requirement:
#               python 3.x
#               PHP SDK and PHP Source 
#               Driver source code folder
#               Git for Windows
#               Visual Studio 2015 (PHP 7.0* and 7.1*) and Visual Studio 2017 (PHP 7.2*)
#
# Output: The drivers will be renamed and copied to the specified location.
#
#############################################################################################

import shutil
import os.path
import stat
import datetime
import subprocess
import tempfile
import glob
import re
import fileinput
from typing import Optional

class BuildUtil(object):
    """Build sqlsrv and/or pdo_sqlsrv drivers with PHP source with the following properties:
    
    Attributes:
        phpver          # PHP version, e.g. 7.1.*, 7.2.* etc.
        driver          # all, sqlsrv, or pdo_sqlsrv
        arch            # x64 or x86
        thread          # nts or ts
        no_rename       # do NOT rename the drivers if True
        debug_enabled   # whether debug is enabled
    """
    
    def __init__(self, phpver: str, driver: str, arch: str, thread: str, no_rename: bool, debug_enabled: bool = False):
        # Validate inputs
        if not self._validate_php_version(phpver):
            raise ValueError(f"Invalid PHP version format: {phpver}")
        if driver.lower() not in ['all', 'sqlsrv', 'pdo_sqlsrv']:
            raise ValueError(f"Driver must be 'all', 'sqlsrv', or 'pdo_sqlsrv', got: {driver}")
        if arch.lower() not in ['x64', 'x86']:
            raise ValueError(f"Architecture must be 'x64' or 'x86', got: {arch}")
        if thread.lower() not in ['nts', 'ts']:
            raise ValueError(f"Thread safety must be 'nts' or 'ts', got: {thread}")
        
        self.phpver = phpver
        self.driver = driver.lower()
        self.arch = arch.lower()
        self.thread = thread.lower()
        self.no_rename = no_rename
        self.debug_enabled = debug_enabled
        self.vc = ''

    def _validate_php_version(self, version: str) -> bool:
        """Validate PHP version format."""
        pattern = r'^\d+\.\d+(\.\d+)?([-\.](RC\d+|beta\d+|alpha\d+|[a-zA-Z]+))?$'
        return bool(re.match(pattern, version))

    def major_version(self) -> str:
        """Return the major version number based on the PHP version."""
        # Extract major.minor (e.g., "7.2" from "7.2.1" or "7.2-RC1")
        match = re.match(r'^(\d+\.\d+)', self.phpver)
        if match:
            return match.group(1)
        return self.phpver[0:3]
        
    def version_label(self) -> str:
        """Return the version label based on the PHP version."""
        major_ver = self.major_version()       
        # Remove the dot (e.g., "7.2" becomes "72")
        version = major_ver.replace('.', '')
        return version

    def driver_name(self, driver: str, suffix: str) -> str:
        """Return the *driver* name with *suffix* after PHP is successfully compiled."""
        return 'php_' + driver + suffix

    def driver_new_name(self, driver: str, suffix: str) -> str:
        """Return the *driver* name with *suffix* based on PHP version and thread."""
        version = self.version_label()
        return 'php_' + driver + '_' + version + '_' + self.thread + suffix

    def determine_compiler(self, sdk_dir: str, vs_ver: int) -> str:
        """Return the compiler version using vswhere.exe."""
        vswhere = os.path.join(sdk_dir, 'php-sdk', 'bin', 'vswhere.exe')
        if not os.path.exists(vswhere):
            print('Could not find ' + vswhere)
            exit(1)
        
        try:
            # Use subprocess instead of os.system to avoid race conditions
            result = subprocess.run(
                [vswhere, '-version', f'[{vs_ver},{vs_ver + 1})', '-property', 'installationVersion','-format', 'text'],
                capture_output=True,
                text=True,
                check=True
            )
            
            versions = result.stdout.strip().split('\n')
            if not versions or not versions[0]:
                print(f"No Visual Studio version {vs_ver} found")
                exit(1)
                
            ver = versions[0]
            print('Version: ' + ver)
            
            # Extract major version (e.g., "15" from "15.9.28307.344")
            major_version = ver.split('.')[0]
            if major_version == '15':
                return 'vc15'
            elif major_version == '16':
                return 'vs16'
            else:
                print(f"Unsupported Visual Studio version: {ver}")
                exit(1)
                
        except subprocess.CalledProcessError as e:
            print(f"Error running vswhere: {e}")
            exit(1)

    def compiler_version(self, sdk_dir: str) -> str:
        """Return the appropriate compiler version based on PHP version."""
        if self.vc == '':
            VC = 'vc15'
            try:
                # Get the major version number (e.g., 7, 8, etc.)
                major_version = int(self.phpver.split('.')[0])
                if major_version >= 8:
                    VC = 'vs16'
                else:
                    # For PHP 7.x, we need to check if VS2019 is available
                    # If not, fall back to vc15
                    try:
                        self.determine_compiler(sdk_dir, 16)
                        VC = 'vs16'
                    except:
                        VC = 'vc15'
            except (ValueError, IndexError):
                # If we can't parse the version, use default
                VC = 'vc15'
            
            self.vc = VC
            print('Compiler: ' + self.vc)
        return self.vc

    def phpsrc_root(self, sdk_dir: str) -> str:
        """Return the path to the PHP source folder based on *sdk_dir*."""
        vc = self.compiler_version(sdk_dir)
        return os.path.join(sdk_dir, 'php-sdk', 'phpdev', vc, self.arch, 'php-'+self.phpver+'-src')
        
    def build_abs_path(self, sdk_dir: str) -> str:   
        """Return the absolute path to the PHP build folder based on *sdk_dir*."""
        phpsrc = self.phpsrc_root(sdk_dir)
        
        build_dir = 'Release'
        if self.debug_enabled:
            build_dir = 'Debug'
        
        if self.thread == 'ts':
            build_dir = build_dir + '_TS'
            
        if self.arch == 'x64':
            build_dir = self.arch + os.sep + build_dir
        
        return os.path.join(phpsrc, build_dir)

    def remove_old_builds(self, sdk_dir: str) -> None:
        """Remove the extensions, e.g. the driver subfolders in php-7.*-src\ext."""
        if not os.path.exists(os.path.join(sdk_dir, 'php-sdk')):
            print('No old builds to be removed...')
            return
    
        print('Removing old builds...')

        phpsrc = self.phpsrc_root(sdk_dir)
        ext_path = os.path.join(phpsrc, 'ext')
        for driver in ['sqlsrv', 'pdo_sqlsrv']:
            driver_path = os.path.join(ext_path, driver)
            if os.path.exists(driver_path):
                shutil.rmtree(driver_path, ignore_errors=True) 
        
        if self.arch == 'x64':
            arch_path = os.path.join(phpsrc, self.arch)
            if os.path.exists(arch_path):
                shutil.rmtree(arch_path, ignore_errors=True)
        else:
            for build_type in ['Debug', 'Debug_TS', 'Release', 'Release_TS']:
                build_path = os.path.join(phpsrc, build_type)
                if os.path.exists(build_path):
                    shutil.rmtree(build_path, ignore_errors=True)

    def remove_prev_build(self, sdk_dir: str) -> None:
        """Remove all binaries and source code in the Release* or Debug* 
        folders according to the current configuration
        """
        if not os.path.exists(os.path.join(sdk_dir, 'php-sdk')):
            print('No old builds to be removed...')
            return
    
        print('Removing previous build...')
        build_dir = self.build_abs_path(sdk_dir)
        if not os.path.exists(build_dir):
            return
            
        # Safely delete files with pattern
        try:
            os.chdir(build_dir)
            for file in glob.glob('*sqlsrv*'):
                try:
                    os.remove(file)
                except OSError as e:
                    print(f"Warning: Could not remove {file}: {e}")
        except OSError as e:
            print(f"Warning: Could not change to directory {build_dir}: {e}")
        
        # remove the extensions in the phpsrc's release* or debug* folder's ext subfolder
        release_ext_path = os.path.join(build_dir, 'ext')
        if os.path.exists(release_ext_path):
            for driver in ['sqlsrv', 'pdo_sqlsrv']:
                driver_path = os.path.join(release_ext_path, driver)
                if os.path.exists(driver_path):
                    shutil.rmtree(driver_path, ignore_errors=True) 
        
        # next remove the binaries too
        if os.path.exists(release_ext_path):
            try:
                os.chdir(release_ext_path)
                for file in glob.glob('*sqlsrv*'):
                    try:
                        os.remove(file)
                    except OSError as e:
                        print(f"Warning: Could not remove {file}: {e}")
            except OSError as e:
                print(f"Warning: Could not change to directory {release_ext_path}: {e}")
        
    @staticmethod
    def get_logfile_name() -> str:
        """Return the filename for the log file based on timestamp."""
        return 'Build_' + datetime.datetime.now().strftime("%Y%m%d_%H%M") + '.log'
    
    @staticmethod
    def update_file_content(file: str, search_str: str, new_str: str) -> None:
        """Find *search_str* and replace it by *new_str* in a *file*"""
        try:
            os.chmod(file, stat.S_IWRITE)
            
            # Read the entire file
            with open(file, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Replace all occurrences
            if search_str in content:
                content = content.replace(search_str.replace('\r\n','\n').replace('\r','\n'), new_str.replace('\r\n','\n'))
                
                # Write back to file
                with open(file, 'w', encoding='utf-8') as f:
                    f.write(content)
            else:
                print(f"Warning: Search string '{search_str}' not found in {file}")
                
        except IOError as e:
            print(f"Error updating file {file}: {e}")
            raise

    @staticmethod
    def generateMMDD() -> str:
        """Return the generated Microsoft PHP Build Version Number"""
        d = datetime.date.today()

        startYear = 2009
        startMonth = 4
        passYear = d.year - startYear
        passMonth = d.month - startMonth
        MM = passYear * 12 + passMonth
        dd = d.day

        MMDD = str(MM)
        if dd < 10:
            return MMDD + "0" + str(dd)
        else:
            return MMDD + str(dd)
            
    @staticmethod
    def get_driver_version(version_file: str) -> str:
        """Read the *version_file* and return the driver version."""
        major = minor = patch = "0"
        
        try:
            with open(version_file, 'r', encoding='utf-8') as f:
                for line in f:
                    if 'SQLVERSION_MAJOR' in line:
                        parts = line.split()
                        if len(parts) >= 3:
                            major = parts[2]
                    elif 'SQLVERSION_MINOR' in line:
                        parts = line.split()
                        if len(parts) >= 3:
                            minor = parts[2]
                    elif 'SQLVERSION_PATCH' in line:
                        parts = line.split()
                        if len(parts) >= 3:
                            patch = parts[2]
                            break
            
            return major + '.' + minor + '.' + patch
        except (IOError, IndexError) as e:
            print(f"Error reading version file {version_file}: {e}")
            return "0.0.0"

    @staticmethod
    def write_lines_to_copy_source(driver: str, file) -> None:
        """Write to file the commands to copy *driver* source."""
        source = '%currDir%' + os.sep + 'Source' + os.sep + driver
        dest = '%phpSrc%' + os.sep + 'ext' + os.sep + driver
        file.write('@CALL ROBOCOPY "' + source + '" "' + dest + '" /s /xx /xo' + os.linesep)
        
        source = '%currDir%' + os.sep + 'Source' + os.sep + 'shared'
        dest = '%phpSrc%' + os.sep + 'ext' + os.sep + driver + os.sep + 'shared'
        file.write('@CALL ROBOCOPY "' + source + '" "' + dest + '" /s /xx /xo' + os.linesep)
    
    @staticmethod
    def download_msphpsql_source(repo: str, branch: str, dest_folder: str = 'Source') -> None:
        """Download to *dest_folder* the msphpsql archive of the specified 
        GitHub *repo* and *branch*. The downloaded files will be removed by default.
        """
        try:
            work_dir = os.path.dirname(os.path.realpath(__file__))   

            temppath = os.path.join(work_dir, 'temp')
            # There is no need to remove tree - 
            # for Bamboo, it will be cleaned up eventually
            # for local development, this can act as a cached copy of the repo
            if os.path.exists(temppath):
                shutil.rmtree(temppath, ignore_errors=True)
                os.makedirs(temppath)
            os.chdir(temppath)
            
            msphpsqlFolder = os.path.join(temppath, 'msphpsql-' + branch)
            
            url = 'https://github.com/' + repo + '/msphpsql.git'
            # Use subprocess with proper quoting
            subprocess.run(['git', 'clone', url, '-b', branch, '--single-branch', '--depth', '1', msphpsqlFolder], 
                          check=True, capture_output=True, text=True)
            
            source = os.path.join(msphpsqlFolder, 'source')
            os.chdir(work_dir)
            
            # Use os.path.join for platform independence
            shared_source = os.path.join(source, 'shared')
            shared_dest = os.path.join(dest_folder, 'shared')
            
            pdo_source = os.path.join(source, 'pdo_sqlsrv')
            pdo_dest = os.path.join(dest_folder, 'pdo_sqlsrv')
            
            sqlsrv_source = os.path.join(source, 'sqlsrv')
            sqlsrv_dest = os.path.join(dest_folder, 'sqlsrv')
            
            # Ensure destination directories exist
            os.makedirs(shared_dest, exist_ok=True)
            os.makedirs(pdo_dest, exist_ok=True)
            os.makedirs(sqlsrv_dest, exist_ok=True)
            
            # Copy files using shutil instead of ROBOCOPY for cross-platform compatibility
            def copy_dir(src, dst):
                if os.path.exists(src):
                    for item in os.listdir(src):
                        s = os.path.join(src, item)
                        d = os.path.join(dst, item)
                        if os.path.isdir(s):
                            shutil.copytree(s, d, dirs_exist_ok=True)
                        else:
                            shutil.copy2(s, d)
            
            copy_dir(shared_source, shared_dest)
            copy_dir(pdo_source, pdo_dest)
            copy_dir(sqlsrv_source, sqlsrv_dest)
                
        except subprocess.CalledProcessError as e:
            print(f'Git command failed: {e.stderr}')
            raise
        except Exception as e:
            print(f'Error occurred when downloading source: {e}')
            raise

    def update_driver_source(self, source_dir: str, driver: str) -> str: 
        """Update the *driver* source in *source_path* with the 
        latest version, file descriptions, etc.
        If debug is enabled, will remove the optimization flag  
        Returns the driver version string.
        """
        driver_dir = os.path.join(source_dir, driver)
        
        if self.debug_enabled:
            # Adding linker flags for creating more debugging information in the binaries
            print('Adding linker flags for', driver)
            config_file = os.path.join(driver_dir, 'config.w32')
            if os.path.exists(config_file):
                if driver == 'sqlsrv':
                    self.update_file_content(config_file, 
                                           'ADD_FLAG( "LDFLAGS_SQLSRV", "/NXCOMPAT /DYNAMICBASE /debug /guard:cf" );', 
                                           'ADD_FLAG( "LDFLAGS_SQLSRV", "/NXCOMPAT /DYNAMICBASE /debug /guard:cf /debugtype:cv,fixup" );')
                elif driver == 'pdo_sqlsrv':
                    self.update_file_content(config_file, 
                                           'ADD_FLAG( "LDFLAGS_PDO_SQLSRV", "/NXCOMPAT /DYNAMICBASE /debug /guard:cf" );', 
                                           'ADD_FLAG( "LDFLAGS_PDO_SQLSRV", "/NXCOMPAT /DYNAMICBASE /debug /guard:cf /debugtype:cv,fixup" );')
                    
        # Update Template.rc 
        template_file = os.path.join(driver_dir, 'template.rc')
        if os.path.exists(template_file):
            if driver == 'sqlsrv':
                drivername = self.driver_new_name(driver, '.dll') 
                self.update_file_content(template_file, 'FILE_NAME \"\\0\"', '"' + drivername + '\\0"')
                self.update_file_content(template_file, '\"Microsoft Drivers for PHP for SQL Server\\0\"', 
                                       '"Microsoft Drivers for PHP for SQL Server (SQLSRV Driver)\\0"')
            elif driver == 'pdo_sqlsrv':
                drivername = self.driver_new_name(driver, '.dll') 
                self.update_file_content(template_file, 'FILE_NAME \"\\0\"', '"' + drivername + '\\0"')
                self.update_file_content(template_file, '\"Microsoft Drivers for PHP for SQL Server\\0\"', 
                                       '"Microsoft Drivers for PHP for SQL Server (PDO Driver)\\0"')
            
        # Update Version.h
        version_file = os.path.join(source_dir, 'shared', 'version.h')
        if os.path.exists(version_file):
            build_number = self.generateMMDD()
            self.update_file_content(version_file, 'SQLVERSION_BUILD 0', 'SQLVERSION_BUILD ' + build_number)

        # get the latest version
        version = self.get_driver_version(version_file) + '.' + build_number
        print('Driver version is: ', version)
            
        # Update CREDIT file
        credits_file = os.path.join(driver_dir, 'CREDITS')
        if os.path.exists(credits_file):
            if driver == 'sqlsrv':
                self.update_file_content(credits_file, 'Microsoft Drivers for PHP for SQL Server', 
                                       'Microsoft Drivers ' + version + ' for PHP for SQL Server (' + self.driver.upper() + ' driver)')
            elif driver == 'pdo_sqlsrv': 
                self.update_file_content(credits_file, 'Microsoft Drivers for PHP for SQL Server (PDO driver)', 
                                       'Microsoft Drivers ' + version + ' for PHP for SQL Server (' + self.driver.upper() + ' driver)')
        
        return version

    def generate_build_options(self) -> str:
        """Return the generated build configuration and arguments"""
        cmd_line = ''
        if self.debug_enabled:
            cmd_line = ' --enable-debug '
            
        if self.driver == 'all':
            cmd_line = ' --enable-sqlsrv=shared --enable-pdo --with-pdo-sqlsrv=shared ' + cmd_line
        else:
            if self.driver == 'sqlsrv':
                cmd_line = ' --enable-sqlsrv=shared ' + cmd_line
            else:       # pdo_sqlsrv
                cmd_line = ' --enable-pdo --with-pdo-sqlsrv=shared ' + cmd_line
                
        cmd_line = 'cscript configure.js --disable-all --enable-cli --enable-cgi --enable-json --enable-embed --enable-mbstring --enable-ctype' + cmd_line
        if self.thread == 'nts':
            cmd_line = cmd_line + ' --disable-zts'
        return cmd_line
    
    def create_local_batch_file(self, make_clean: bool, cmd_line: str, log_file: str) -> str:
        """Generate the batch file to be picked up by the PHP starter script."""
        filename = 'phpsdk-build-task.bat'
        print('Generating ', filename)
        try:
            file = open(filename, 'w', encoding='utf-8')
            file.write('@ECHO OFF' + os.linesep)
            file.write('SET currDir=%CD%' + os.linesep)
            file.write('SET LOG_NAME=%currDir%\\' + log_file + os.linesep)       
            file.write('@CALL phpsdk_buildtree phpdev > "%LOG_NAME%" 2>&1' + os.linesep)
            
            # for PHP version with release tags, such as 'RC', 'beta', etc. 
            # we need to remove the hyphen '-' between the version number and tag
            # because in https://github.com/php/php-src the released tags have no hyphens
            
            php_tag = 'php-' + self.phpver.replace('-', '')
            php_src = 'php-' + self.phpver +'-src'
            
            # if not exists, check out the specified tag
            file.write('IF NOT EXIST "' + php_src + '" @CALL git clone -b ' + php_tag + ' --depth 1 --single-branch https://github.com/php/php-src.git "' + php_src + '"' + os.linesep)        
            file.write('CD "' + php_src + '"' + os.linesep)
            file.write('SET phpSrc=%CD%' + os.linesep)
            file.write('@CALL phpsdk_deps -u >> "%LOG_NAME%" 2>&1' + os.linesep)
            
            # copy source files to extension
            if self.driver == 'all':
                self.write_lines_to_copy_source('sqlsrv', file)
                self.write_lines_to_copy_source('pdo_sqlsrv', file)
            else:
                self.write_lines_to_copy_source(self.driver, file)
            
            # configure and build
            file.write('@CALL buildconf --force >> "%LOG_NAME%" 2>&1' + os.linesep)
            file.write('@CALL ' + cmd_line + ' >> "%LOG_NAME%" 2>&1' + os.linesep)
            if make_clean:
                file.write('nmake clean >> "%LOG_NAME%" 2>&1' + os.linesep)
            file.write('nmake >> "%LOG_NAME%" 2>&1' + os.linesep)
            file.write('exit' + os.linesep)
            file.close()

            return filename
        except IOError as e:
            print(f'Cannot create {filename}: {e}')
            raise

    def build_drivers(self, make_clean: bool = False, dest: Optional[str] = None, log_file: Optional[str] = None) -> str:
        """Build sqlsrv/pdo_sqlsrv extensions for PHP, assuming the Source folder 
        exists in the working directory, and this folder will be removed when the build 
        is complete.
        Returns the directory where binaries were copied.
        """
        print("build_drivers")
        work_dir = os.path.dirname(os.path.realpath(__file__))   
        # First, update the driver source file contents
        source_dir = os.path.join(work_dir, 'Source')
        if not os.path.exists(source_dir):
            raise FileNotFoundError(f"Source directory not found: {source_dir}")
            
        if self.driver == 'all':
            self.update_driver_source(source_dir, 'sqlsrv') 
            self.update_driver_source(source_dir, 'pdo_sqlsrv') 
        else:
            self.update_driver_source(source_dir, self.driver) 

        # Next, generate the build configuration and arguments
        cmd_line = self.generate_build_options()
        print('cmd_line: ' + cmd_line)

        # Generate a batch file based on the inputs
        if log_file is None:
            log_file = self.get_logfile_name()
        
        batch_file = self.create_local_batch_file(make_clean, cmd_line, log_file)
        
        # Reference: https://github.com/php/php-sdk-binary-tools
        # Clone the master branch of PHP sdk if the directory does not exist 
        print('Downloading the latest php SDK...')
        
        # if *dest* is None, simply use the current working directory
        sdk_dir = dest
        copy_to_ext = True      # this determines where to copy the binaries to
        if dest is None:
            sdk_dir = work_dir
            copy_to_ext = False

        phpSDK = os.path.join(sdk_dir, 'php-sdk')
        if not os.path.exists(phpSDK):
            subprocess.run(['git', 'clone', 'https://github.com/php/php-sdk-binary-tools.git', 
                          '--branch', 'master', '--single-branch', '--depth', '1', phpSDK],
                         check=True, capture_output=True, text=True)
        
        os.chdir(phpSDK)
        subprocess.run(['git', 'pull'], check=True, capture_output=True, text=True)
        print('Done cloning the latest php SDK...')

        # Move the generated batch file to phpSDK for the php starter script 
        print('Moving the sdk bath file over...')
        sdk_batch_file = os.path.join(phpSDK, batch_file)
        if os.path.exists(sdk_batch_file):
            os.remove(sdk_batch_file)
        shutil.move(os.path.join(work_dir, batch_file), phpSDK)
        
        print('Checking if source exists...')
        sdk_source = os.path.join(phpSDK, 'Source')
        # Sometimes, for various reasons, the Source folder from previous build 
        # might exist in phpSDK. If so, remove it first
        if os.path.exists(sdk_source):  
            os.chmod(sdk_source, stat.S_IWRITE)
            shutil.rmtree(sdk_source, ignore_errors=True) 
        shutil.move(source_dir, phpSDK)
        
        # Invoke phpsdk-<vc>-<arch>.bat
        vc = self.compiler_version(sdk_dir)
        starter_script = 'phpsdk-' + vc + '-' + self.arch + '.bat'
        print('Running starter script: ', starter_script)
        
        # Use subprocess to run the starter script
        try:
            subprocess.run([starter_script, '-t', batch_file], check=True, shell=True, cwd=phpSDK)
        except subprocess.CalledProcessError as e:
            print(f"Starter script failed: {e}")
            # Check if log file exists and show last few lines
            log_path = os.path.join(phpSDK, log_file)
            if os.path.exists(log_path):
                print("\n=== Last 20 lines of build log ===")
                with open(log_path, 'r', encoding='utf-8') as f:
                    lines = f.readlines()
                    for line in lines[-20:]:
                        print(line.rstrip())
            raise
        
        print('Starter script complete')


        # Now we can safely remove the Source folder, because its contents have 
        # already been modified prior to building the extensions
        source_path = os.path.join(phpSDK, 'Source')
        if os.path.exists(source_path):
            shutil.rmtree(source_path, ignore_errors=True) 
            print('rmtree complete')
        
        # Next, rename the newly compiled PHP extensions, if required
        if not self.no_rename:
            self.rename_binaries(sdk_dir)
            print('rename_binaries complete')

        # Final step, copy the binaries to the right place
        ext_dir = self.copy_binaries(sdk_dir, copy_to_ext)
        print('copy_binaries complete')
        
        return ext_dir

    def rename_binary(self, path: str, driver: str) -> None:
        """Rename the *driver* binary (sqlsrv or pdo_sqlsrv) (only the dlls)."""
        driver_old_name = self.driver_name(driver, '.dll')
        driver_new_name = self.driver_new_name(driver, '.dll')

        old_path = os.path.join(path, driver_old_name)
        new_path = os.path.join(path, driver_new_name)
        
        if os.path.exists(old_path):
            os.rename(old_path, new_path)
            print(f"Renamed {driver_old_name} to {driver_new_name}")
        else:
            print(f"Warning: File not found for renaming: {old_path}")

    def rename_binaries(self, sdk_dir: str) -> None:
        """Rename the sqlsrv and/or pdo_sqlsrv dlls according to the PHP
        version and thread.
        """
        
        # Derive the path to where the extensions are located
        ext_dir = self.build_abs_path(sdk_dir)
        print("Renaming binaries in ", ext_dir)
        
        if self.driver == 'all':
            self.rename_binary(ext_dir, 'sqlsrv')
            self.rename_binary(ext_dir, 'pdo_sqlsrv')
        else:
            self.rename_binary(ext_dir, self.driver)
                
    def copy_binary(self, from_dir: str, dest_dir: str, driver: str, suffix: str) -> None:
        """Copy sqlsrv or pdo_sqlsrv binary (based on *suffix*) to *dest_dir*."""
        print('')
        if not self.no_rename and suffix == '.dll':
            binary = self.driver_new_name(driver, suffix)
        else:
            binary = self.driver_name(driver, suffix)

        source_path = os.path.join(from_dir, binary)
        dest_path = os.path.join(dest_dir, binary)
        
        print(f'copy2 [{from_dir}][{binary}] -> [{dest_dir}]...')
        
        if os.path.exists(source_path):
            shutil.copy2(source_path, dest_path)
            print('Done')
            
            php_ini_file = os.path.join(from_dir, 'php.ini')
            if os.path.exists(php_ini_file):
                    # Check if extension already exists in php.ini
                extension_line = 'extension=' + binary
                with open(php_ini_file, 'r', encoding='utf-8') as php_ini:
                    content = php_ini.read()
                    
                if extension_line not in content:
                    with open(php_ini_file, 'a', encoding='utf-8') as php_ini:
                        php_ini.write(extension_line + '\n')
                        print(f'Added {extension_line} to php.ini')
                else:
                    print(f'Extension {binary} already exists in php.ini')
        else:
            print(f'Warning: Source file not found: {source_path}')
    
    def copy_binaries(self, sdk_dir: str, copy_to_ext: bool) -> str:
        """Copy the sqlsrv and/or pdo_sqlsrv binaries, including the pdb files, 
        to the right place, depending on *copy_to_ext*. The default is to 
        copy them to the 'ext' folder.
        Returns the destination directory.
        """
        
        # Get php.ini file from php.ini-production
        build_dir = self.build_abs_path(sdk_dir)
        php_ini_file = os.path.join(build_dir, 'php.ini')
        print('Setting up php ini file', php_ini_file, 'sdk_dir = [', sdk_dir, '], build_dir = [', build_dir, ']')
        
        # List files for debugging
        if os.path.exists(build_dir):
            dir_list = os.listdir(build_dir)
            print("Files and directories in '", build_dir, "' :")
            print(dir_list)
        else:
            raise FileNotFoundError(f"Build directory not found: {build_dir}")
        
        # Copy php.ini-production file to php.ini
        phpsrc = self.phpsrc_root(sdk_dir)
        php_ini_production = os.path.join(phpsrc, 'php.ini-production')
        
        if os.path.exists(php_ini_production):
            shutil.copy(php_ini_production, php_ini_file)
        else:
            print(f"Warning: php.ini-production not found at {php_ini_production}")
            # Create an empty php.ini file
            with open(php_ini_file, 'w', encoding='utf-8') as f:
                f.write('')
        
        # Copy run-tests.php as well
        run_tests_src = os.path.join(phpsrc, 'run-tests.php')
        if os.path.exists(run_tests_src):
            shutil.copy(run_tests_src, build_dir)
        else:
            print(f"Warning: run-tests.php not found at {run_tests_src}")
        
        print('Copying the binaries from', build_dir)
        if copy_to_ext:
            dest_dir = os.path.join(build_dir, 'ext') 
            os.makedirs(dest_dir, exist_ok=True)
            ext_dir_line = 'extension_dir=ext\\'
        else:   
            ext_dir_line = 'extension_dir=.\\'
            # Simply make a copy of the binaries in sdk_dir
            dest_dir = sdk_dir
        
        print('Destination:', dest_dir)
        
        # Update php.ini with extension_dir
        if os.path.exists(php_ini_file):
            with open(php_ini_file, 'a', encoding='utf-8') as php_ini:
                print('Writing extension_dir to:', php_ini_file)
                php_ini.write(ext_dir_line + '\n')
                print('Write complete')
        else:
            print(f'Warning: php.ini file not found at {php_ini_file}')

        # Now copy the binaries
        if self.driver == 'all':
            print('Copy ALL')
            self.copy_binary(build_dir, dest_dir, 'sqlsrv', '.dll')
            self.copy_binary(build_dir, dest_dir, 'sqlsrv', '.pdb')
            self.copy_binary(build_dir, dest_dir, 'pdo_sqlsrv', '.dll')
            self.copy_binary(build_dir, dest_dir, 'pdo_sqlsrv', '.pdb')
            print('Copy ALL complete')
        else:
            print('Copy DRIVER')
            self.copy_binary(build_dir, dest_dir, self.driver, '.dll')
            self.copy_binary(build_dir, dest_dir, self.driver, '.pdb')
            print('Copy DRIVER complete')
            
        return dest_dir


# Main execution if script is run directly
if __name__ == "__main__":
    import argparse
    
    parser = argparse.ArgumentParser(description='Build Microsoft SQL Server PHP Drivers')
    parser.add_argument('--phpver', required=True, help='PHP version (e.g., 7.4.1, 8.0.0)')
    parser.add_argument('--driver', required=True, choices=['all', 'sqlsrv', 'pdo_sqlsrv'], 
                       help='Driver to build')
    parser.add_argument('--arch', required=True, choices=['x64', 'x86'], 
                       help='Architecture')
    parser.add_argument('--thread', required=True, choices=['nts', 'ts'], 
                       help='Thread safety')
    parser.add_argument('--no-rename', action='store_true', 
                       help='Do not rename output binaries')
    parser.add_argument('--debug', action='store_true', 
                       help='Enable debug build')
    parser.add_argument('--make-clean', action='store_true', 
                       help='Run nmake clean before building')
    parser.add_argument('--dest', help='Destination directory for output')
    parser.add_argument('--log', help='Log file name')
    
    args = parser.parse_args()
    
    try:
        builder = BuildUtil(
            phpver=args.phpver,
            driver=args.driver,
            arch=args.arch,
            thread=args.thread,
            no_rename=args.no_rename,
            debug_enabled=args.debug
        )
        
        output_dir = builder.build_drivers(
            make_clean=args.make_clean,
            dest=args.dest,
            log_file=args.log
        )
        
        print(f"\nBuild completed successfully!")
        print(f"Output directory: {output_dir}")
        
    except Exception as e:
        print(f"\nBuild failed with error: {e}")
        import traceback
        traceback.print_exc()
        exit(1)

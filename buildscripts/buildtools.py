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
import urllib.request
import zipfile 
import fileinput

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
    
    def __init__(self, phpver, driver, arch, thread, no_rename, debug_enabled = False):
        self.phpver = phpver
        self.driver = driver.lower()
        self.arch = arch.lower()
        self.thread = thread.lower()
        self.no_rename = no_rename
        self.debug_enabled = debug_enabled
    
    def major_version(self):
        """Return the major version number based on the PHP version."""
        return self.phpver[0:3]
        
    def version_label(self):
        """Return the version label based on the PHP version."""
        major_ver = self.major_version()
        
        if major_ver[2] == '0':
            version = major_ver[0]
        else:
            version = major_ver[0] + major_ver[2]
        return version

    def driver_name(self, driver, suffix):
        """Return the *driver* name with *suffix* after PHP is successfully compiled."""
        return 'php_' + driver + suffix

    def driver_new_name(self, driver, suffix):
        """Return the *driver* name with *suffix* based on PHP version and thread."""
        version = self.version_label()
        return 'php_' + driver + '_' + version + '_' + self.thread + suffix

    def compiler_version(self):
        """Return the appropriate compiler version based on PHP version."""
        VC = 'vc14'
        version = self.version_label()
        if version >= '72':     # Compiler version for PHP 7.2 or above
            VC = 'vc15'
        return VC
        
    def phpsrc_root(self, sdk_dir):   
        """Return the path to the PHP source folder based on *sdk_dir*."""
        vc = self.compiler_version()
        return os.path.join(sdk_dir, 'php-sdk', 'phpdev', vc, self.arch, 'php-'+self.phpver+'-src')
        
    def build_abs_path(self, sdk_dir):   
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

    def remove_old_builds(self, sdk_dir):
        """Remove the extensions, e.g. the driver subfolders in php-7.*-src\ext."""
        print('Removing old builds...')

        phpsrc = self.phpsrc_root(sdk_dir)
        ext_path = os.path.join(phpsrc, 'ext')
        if os.path.exists( ext_path ):
            shutil.rmtree(os.path.join(ext_path, 'sqlsrv'), ignore_errors=True) 
            shutil.rmtree(os.path.join(ext_path, 'pdo_sqlsrv'), ignore_errors=True) 
        
        if self.arch == 'x64':
            shutil.rmtree(os.path.join(phpsrc, self.arch), ignore_errors=True)
        else:
            shutil.rmtree(os.path.join(phpsrc, 'Debug'), ignore_errors=True)
            shutil.rmtree(os.path.join(phpsrc, 'Debug_TS'), ignore_errors=True)
            shutil.rmtree(os.path.join(phpsrc, 'Release'), ignore_errors=True)
            shutil.rmtree(os.path.join(phpsrc, 'Release_TS'), ignore_errors=True)

    def remove_prev_build(self, sdk_dir):
        """Remove all binaries and source code in the Release* or Debug* 
        folders according to the current configuration
        """
        print('Removing previous build...')
        build_dir = self.build_abs_path(sdk_dir)
        if not os.path.exists(build_dir):
            return
            
        os.chdir(build_dir)
        os.system('DEL *sqlsrv*')    
        
        # remove the extensions in the phpsrc's release* or debug* folder's ext subfolder
        release_ext_path = os.path.join(build_dir, 'ext')
        if os.path.exists( release_ext_path ):
            shutil.rmtree(os.path.join(release_ext_path, 'sqlsrv'), ignore_errors=True) 
            shutil.rmtree(os.path.join(release_ext_path, 'pdo_sqlsrv'), ignore_errors=True) 
        
        # next remove the binaries too
        os.chdir(release_ext_path)
        os.system('DEL *sqlsrv*')    
        
    @staticmethod
    def get_logfile_name():
        """Return the filename for the log file based on timestamp."""
        return 'Build_' + datetime.datetime.now().strftime("%Y%m%d_%H%M") + '.log'
    
    @staticmethod
    def update_file_content(file, search_str, new_str):
        """Find *search_str* and replace it by *new_str* in a *file*"""
        os.chmod(file, stat.S_IWRITE)
        with fileinput.FileInput(file, inplace=True) as f:
            for line in f:
                print(line.replace(search_str, new_str), end='')

    @staticmethod
    def generateMMDD():
        """Return the generated Microsoft PHP Build Version Number"""
        d = datetime.date.today()

        startYear = 2009
        startMonth = 4
        passYear = int( '%02d' % d.year ) - startYear
        passMonth = int( '%02d' % d.month ) - startMonth
        MM = passYear * 12 + passMonth
        dd = d.day

        MMDD = "" + str( MM )
        if( dd < 10 ):
            return MMDD + "0" + str( dd )
        else:
            return MMDD + str( dd )
            
    @staticmethod
    def get_driver_version(version_file):
        """Read the *version_file* and return the driver version."""
        with open(version_file) as f:
            for line in f:
                if 'SQLVERSION_MAJOR' in line:      # major version
                    major = line.split()[2]
                elif 'SQLVERSION_MINOR' in line:    # minor version  
                    minor = line.split()[2]
                elif 'SQLVERSION_PATCH' in line:    # patch  
                    patch = line.split()[2]
                    break

        return major + '.' + minor + '.' + patch 

    @staticmethod
    def write_lines_to_copy_source(driver, file):
        """Write to file the commands to copy *driver* source."""
        source = '%currDir%' + os.sep + 'Source' + os.sep + driver
        dest = '%phpSrc%' + os.sep + 'ext' + os.sep + driver
        file.write('@CALL ROBOCOPY ' + source + ' ' + dest + ' /s /xx /xo' + os.linesep)
        
        source = '%currDir%' + os.sep + 'Source' + os.sep + 'shared'
        dest = '%phpSrc%' + os.sep + 'ext' + os.sep + driver + os.sep + 'shared'
        file.write('@CALL ROBOCOPY ' + source + ' ' + dest + ' /s /xx /xo' + os.linesep)
    
    @staticmethod
    def download_msphpsql_source(repo, branch, dest_folder = 'Source', clean_up = True):
        """Download to *dest_folder* the msphpsql archive of the specified 
        GitHub *repo* and *branch*. The downloaded files will be removed by default.
        """
        try:
            work_dir = os.path.dirname(os.path.realpath(__file__))   

            temppath = os.path.join(work_dir, 'temp')
            if os.path.exists(temppath):
                shutil.rmtree(temppath)
            os.makedirs(temppath)
            os.chdir(temppath)
            
            file = branch + '.zip'
            url = 'https://github.com/' + repo + '/msphpsql/archive/' + branch + '.zip'

            print('Downloading ' + url + ' ...')
            try:
                with urllib.request.urlopen(url) as response, open(file, 'wb') as out_file:
                    shutil.copyfileobj(response, out_file)
            except:
                print ("Resort to skip ssl verification...")
                # need to skip ssl verification on some agents
                # see https://www.python.org/dev/peps/pep-0476/
                with urllib.request.urlopen(url, context=ssl._create_unverified_context()) as response, open(file, 'wb') as out_file:
                    shutil.copyfileobj(response, out_file)

            print('Extracting ' + file + ' ...')
            zip = zipfile.ZipFile(file)
            zip.extractall()
            zip.close()
            
            msphpsqlFolder = os.path.join(temppath, 'msphpsql-' + branch)
            source = os.path.join(msphpsqlFolder, 'source')
            os.chdir(work_dir)
            
            os.system('ROBOCOPY ' + source + '\shared ' + dest_folder + '\shared /xx /xo')
            os.system('ROBOCOPY ' + source + '\pdo_sqlsrv ' + dest_folder + '\pdo_sqlsrv /xx /xo')
            os.system('ROBOCOPY ' + source + '\sqlsrv ' + dest_folder + '\sqlsrv /xx /xo')

            if clean_up:
                shutil.rmtree(temppath)        
                
        except:
            print('Error occurred when downloading source')
            raise

    def update_driver_source(self, source_dir, driver): 
        """Update the *driver* source in *source_path* with the 
        latest version, file descriptions, etc.
        If debug is enabled, will remove the optimization flag  
        """
        driver_dir = os.path.join(source_dir, driver)
        
        if self.debug_enabled:
            # Adding linker flags for creating more debugging information in the binaries
            print('Adding linker flags for', driver)
            config_file = os.path.join(driver_dir, 'config.w32')
            if driver == 'sqlsrv':
                self.update_file_content(config_file, 'ADD_FLAG( "LDFLAGS_SQLSRV", "/NXCOMPAT /DYNAMICBASE /debug /guard:cf" );', 'ADD_FLAG( "LDFLAGS_SQLSRV", "/NXCOMPAT /DYNAMICBASE /debug /guard:cf /debugtype:cv,fixup" );')
            elif driver == 'pdo_sqlsrv':
                self.update_file_content(config_file, 'ADD_FLAG( "LDFLAGS_PDO_SQLSRV", "/NXCOMPAT /DYNAMICBASE /debug /guard:cf" );', 'ADD_FLAG( "LDFLAGS_PDO_SQLSRV", "/NXCOMPAT /DYNAMICBASE /debug /guard:cf /debugtype:cv,fixup" );')
                    
        # Update Template.rc 
        template_file = os.path.join(driver_dir, 'template.rc')
        if driver == 'sqlsrv':
            drivername = self.driver_new_name(driver, '.dll') 
            self.update_file_content(template_file, 'FILE_NAME \"\\0\"', '"' + drivername + '\\0"')
            self.update_file_content(template_file, '\"Microsoft Drivers for PHP for SQL Server\\0\"', '"Microsoft Drivers for PHP for SQL Server (SQLSRV Driver)\\0"')
        elif driver == 'pdo_sqlsrv':
            drivername = self.driver_new_name(driver, '.dll') 
            self.update_file_content(template_file, 'FILE_NAME \"\\0\"', '"' + drivername + '\\0"')
            self.update_file_content(template_file, '\"Microsoft Drivers for PHP for SQL Server\\0\"', '"Microsoft Drivers for PHP for SQL Server (PDO Driver)\\0"')
            
        # Update Version.h
        version_file = os.path.join(source_dir, 'shared', 'version.h')
        build_number = self.generateMMDD()
        self.update_file_content(version_file, 'SQLVERSION_BUILD 0', 'SQLVERSION_BUILD ' + build_number)

        # get the latest version
        version = self.get_driver_version(version_file) + '.' + build_number
        print('Driver version is: ', version)
            
        # Update CREDIT file
        credits_file = os.path.join(driver_dir, 'CREDITS')
        if driver == 'sqlsrv':
            self.update_file_content(credits_file, 'Microsoft Drivers for PHP for SQL Server', 'Microsoft Drivers ' + version + ' for PHP for SQL Server (' + self.driver.upper() + ' driver)')
        elif driver == 'pdo_sqlsrv': 
            self.update_file_content(credits_file, 'Microsoft Drivers for PHP for SQL Server (PDO driver)', 'Microsoft Drivers ' + version + ' for PHP for SQL Server (' + self.driver.upper() + ' driver)')

    def generate_build_options(self):
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
                
        cmd_line = 'cscript configure.js --disable-all --enable-cli --enable-cgi --enable-embed' + cmd_line
        if self.thread == 'nts':
            cmd_line = cmd_line + ' --disable-zts'
        return cmd_line
    
    def create_local_batch_file(self, make_clean, cmd_line, log_file):
        """Generate the batch file to be picked up by the PHP starter script."""
        filename = 'phpsdk-build-task.bat'
        print('Generating ', filename)
        try:
            file = open(filename, 'w')
            file.write('@ECHO OFF' + os.linesep)
            file.write('SET currDir=%CD%' + os.linesep)
            file.write('SET LOG_NAME=%currDir%\\' + log_file + os.linesep)       
            file.write('@CALL phpsdk_buildtree phpdev > %LOG_NAME% 2>&1' + os.linesep)
            
            # for PHP version with release tags, such as 'RC', 'beta', etc. 
            # we need to remove the hyphen '-' between the version number and tag
            # because in https://github.com/php/php-src the released tags have no hyphens
            
            php_tag = 'php-' + self.phpver.replace('-', '')
            php_src = 'php-' + self.phpver +'-src'
            
            # if not exists, check out the specified tag
            file.write('IF NOT EXIST ' + php_src + ' @CALL git clone -b ' + php_tag + ' --depth 1 --single-branch https://github.com/php/php-src.git ' + php_src +  os.linesep)        
            file.write('CD ' + php_src + os.linesep)
            file.write('SET phpSrc=%CD%' + os.linesep)
            file.write('@CALL phpsdk_deps -u >> %LOG_NAME% 2>&1' + os.linesep)
            
            # copy source files to extension
            if self.driver == 'all':
                self.write_lines_to_copy_source('sqlsrv', file)
                self.write_lines_to_copy_source('pdo_sqlsrv', file)
            else:
                self.write_lines_to_copy_source(self.driver, file)
            
            # configure and build
            file.write('@CALL buildconf --force >> %LOG_NAME% 2>&1' + os.linesep)
            file.write('@CALL ' + cmd_line + ' >> %LOG_NAME% 2>&1' + os.linesep)
            if make_clean:
                file.write('nmake clean >> %LOG_NAME% 2>&1' + os.linesep)
            file.write('nmake >> %LOG_NAME% 2>&1' + os.linesep)
            file.write('exit' + os.linesep)
            file.close()
            return filename
        except:
            print('Cannot create ', filename)

    def build_drivers(self, make_clean = False, dest = None, log_file = None):
        """Build sqlsrv/pdo_sqlsrv extensions for PHP, assuming the Source folder 
        exists in the working directory, and this folder will be removed when the build 
        is complete.
        """
        work_dir = os.path.dirname(os.path.realpath(__file__))   

        # First, update the driver source file contents
        source_dir = os.path.join(work_dir, 'Source')
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
        
        # Reference: https://github.com/OSTC/php-sdk-binary-tools
        # Clone the master branch of PHP sdk if the directory does not exist 
        print('Downloading the latest php SDK...')
        
        # if *dest* is None, simply use the current working directory
        sdk_dir = dest
        copy_to_ext = True      # this determines where to copy the binaries to
        if dest is None:
            sdk_dir = work_dir
            copy_to_ext = False

        phpSDK = os.path.join(sdk_dir, 'php-sdk')
        if not os.path.exists( phpSDK ):
            os.system('git clone https://github.com/OSTC/php-sdk-binary-tools.git --branch master --single-branch --depth 1 ' + phpSDK)
        os.chdir(phpSDK)
        os.system('git pull ')

        # Move the generated batch file to phpSDK for the php starter script 
        sdk_batch_file = os.path.join(phpSDK, batch_file)
        if os.path.exists(sdk_batch_file):
            os.remove(sdk_batch_file)
        shutil.move(os.path.join(work_dir, batch_file), phpSDK)
        
        sdk_source = os.path.join(phpSDK, 'Source')
        # Sometimes, for various reasons, the Source folder from previous build 
        # might exist in phpSDK. If so, remove it first
        if os.path.exists(sdk_source):  
            os.chmod(sdk_source, stat.S_IWRITE)
            shutil.rmtree(sdk_source, ignore_errors=True) 
        shutil.move(source_dir, phpSDK)
        
        # Invoke phpsdk-<vc>-<arch>.bat
        vc = self.compiler_version()                           
        starter_script = 'phpsdk-' + vc + '-' + self.arch + '.bat'
        print('Running starter script: ', starter_script)
        os.system(starter_script + ' -t ' + batch_file)
        
        # Now we can safely remove the Source folder, because its contents have 
        # already been modified prior to building the extensions
        shutil.rmtree(os.path.join(phpSDK, 'Source'), ignore_errors=True) 
        
        # Next, rename the newly compiled PHP extensions, if required
        if not self.no_rename:
            self.rename_binaries(sdk_dir)
        
        # Final step, copy the binaries to the right place
        ext_dir = self.copy_binaries(sdk_dir, copy_to_ext)
        return ext_dir

    def rename_binary(self, path, driver):
        """Rename the *driver* binary (sqlsrv or pdo_sqlsrv) (only the dlls)."""
        driver_old_name = self.driver_name(driver, '.dll')
        driver_new_name = self.driver_new_name(driver, '.dll')

        os.rename(os.path.join(path, driver_old_name), os.path.join(path, driver_new_name))

    def rename_binaries(self, sdk_dir):
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
                
    def copy_binary(self, from_dir, dest_dir, driver, suffix):
        """Copy sqlsrv or pdo_sqlsrv binary (based on *suffix*) to *dest_dir*."""
        if not self.no_rename and suffix == '.dll':
            binary = self.driver_new_name(driver, suffix)
        else:
            binary = self.driver_name(driver, suffix)
        shutil.copy2(os.path.join(from_dir, binary), dest_dir)
        if suffix == '.dll':
            php_ini_file = os.path.join(from_dir, 'php.ini')
            with open(php_ini_file, 'a') as php_ini:
                php_ini.write('extension=' + binary + '\n');
    
    def copy_binaries(self, sdk_dir, copy_to_ext):
        """Copy the sqlsrv and/or pdo_sqlsrv binaries, including the pdb files, 
        to the right place, depending on *copy_to_ext*. The default is to 
        copy them to the 'ext' folder.
        """
        
        # Get php.ini file from php.ini-production
        build_dir = self.build_abs_path(sdk_dir)
        php_ini_file = os.path.join(build_dir, 'php.ini')
        print('Setting up php ini file', php_ini_file)
        
        # Copy php.ini-production file to php.ini
        phpsrc = self.phpsrc_root(sdk_dir)
        shutil.copy(os.path.join(phpsrc, 'php.ini-production'), php_ini_file)
        
        # Copy run-tests.php as well
        phpsrc = self.phpsrc_root(sdk_dir)
        shutil.copy(os.path.join(phpsrc, 'run-tests.php'), build_dir)
        
        print('Copying the binaries from', build_dir)
        if copy_to_ext:
            dest_dir = os.path.join(build_dir, 'ext') 
            ext_dir_line = 'extension_dir=ext\\'
        else:   
            ext_dir_line = 'extension_dir=.\\'
            # Simply make a copy of the binaries in sdk_dir
            dest_dir = sdk_dir
        
        print('Destination:', dest_dir)
        with open(php_ini_file, 'a') as php_ini:
            php_ini.write(ext_dir_line + '\n')

        # Now copy the binaries
        if self.driver == 'all':
            self.copy_binary(build_dir, dest_dir, 'sqlsrv', '.dll')
            self.copy_binary(build_dir, dest_dir, 'sqlsrv', '.pdb')
            self.copy_binary(build_dir, dest_dir, 'pdo_sqlsrv', '.dll')
            self.copy_binary(build_dir, dest_dir, 'pdo_sqlsrv', '.pdb')
        else:
            self.copy_binary(build_dir, dest_dir, self.driver, '.dll')
            self.copy_binary(build_dir, dest_dir, self.driver, '.pdb')
            
        return dest_dir
              

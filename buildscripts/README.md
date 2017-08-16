# Windows

## Prerequisites

To build extensions for 
    * PHP 7.0* or PHP 7.1*, install Visual Studio 2015 and make sure C++ tools are enabled. 
    * PHP 7.2*, install Visual Studio 2017, including Visual C++ toolset, the Windows SDK components, and Git for Windows. 

To use the sample build scripts `builddrivers.py` and `buildtools.py`, install Python 3.x in Windows. 

## Compile the drivers 

You must first be able to build PHP 7.* without including these extensions. For help with doing this, see the [official PHP website](https://wiki.php.net/internals/windows/stepbystepbuild) for building PHP 7.0* or PHP 7.1* on Windows or [PHP SDK page](https://github.com/OSTC/php-sdk-binary-tools) for the new instructions for building PHP 7.2 and/or above.

The Microsoft Drivers for PHP for SQL Server have been compiled and tested with PHP 7.0.* and 7.1.* using the Visual C++ 2015 as well as PHP 7.2.0 beta using the Visual C++ 2017 v15.0. 

### Manually building from source 

1. Download the *source* directory from this repository

2. Make a copy of the *shared* folder as a subfolder in *sqlsrv* and/or *pdo_sqlsrv* folder

3. Copy the *sqlsrv* and/or *pdo_sqlsrv* folder(s) into the PHP source ext subdirectory

4. Run `buildconf --force` to rebuild the configure.js script to include the *sqlsrv* and/or *pdo_sqlsrv* driver(s).

5. Run `configure.bat` with the desired driver options (as shown below) to generate the makefile. You can run `configure.bat --help` to see what other options are available. For example, for non-thread safe build, add this option `--disable-zts`.  
    * For SQLSRV add: `--enable-sqlsrv=shared`
    * For PDO_SQLSRV add: `--enable-pdo --with-pdo-sqlsrv=shared`

6. Run `nmake`. Optionally, you can run `nmake clean` first.

7. To install the drivers, there are two ways:
    * Run `nmake install`, or
    * Copy the drivers:
        * Find the directory where the newly compiled `php.exe` is
        * Locate the compiled php_sqlsrv.dll and/or php_pdo_sqlsrv.dll 
        * Copy the dll(s) to the `ext` subfolder  

### Using the sample build scripts

The sample build scripts, `builddrivers.py` and `buildtools.py`, are expected to build our extensions for PHP in Windows.

#### Overview

When asked to provide the PHP version, you should enter values like `7.1.7`. If it's alpha, beta, or RC, make sure the name you provide matches the PHP tag name without the prefix `php-`. For example, for PHP 7.2 beta 2, the tag name is `php-7.2.0beta2`, so you should enter `7.2.0beta2`. Visit [PHP SRC]( https://github.com/php/php-src) to find the appropriate tag names.

It's recommended that the PHP SDK is unzipped into the shortest possible path, preferrably somewhere near the root drive. Therefore, this script will create a `php-sdk` folder in the C:\ drive. This `php-sdk` folder will remain unless you remove it yourself. For ongoing development, it's suggested you keep it around. The build scripts will handle updating the PHP SDK if new version is available. 

#### Steps

1. Launch a regular `cmd` prompt 

2. Change to the directory where the Python scripts `builddrivers.py` and `buildtools.py` are

3. Interactive mode: 
    * Run `py builddrivers.py` to use the interactive mode. Use lower cases to answer the following questions:
        * PHP Version (i.e. the version number like `7.1.7` or `7.2.0beta2`)
        * 64-bit?
        * Thread safe?
        * Driver?
        * Debug enabled?
        * Download source from GitHub?
    * For `yes/no` questions, you can simply hit `ENTER` key for `yes`. Other questions are self-explanatory.
    
4. Use Command-line arguments
    * Run `py builddrivers.py -h` to get a list of options and their descriptions
    * For example, 
        * `py builddrivers.py -v=7.1.7 -a=x86 -t=ts -d=all -g=no`
        * `py builddrivers.py --PHPVER=7.0.22 --ARCH=x64 --THREAD=nts --DRIVER=sqlsrv --GITHUB=yes`

5. If the script detects the presence of a PHP source directory, you will be prompted whether to rebuild, clean or superclean. Choose
    * `rebuild` if you have always used the same configuration (32 bit, thread safe, etc.)
    * `clean` to remove previous builds (binaries) 
    * `superclean` to remove the entire `php-<version>-src` directory, which is often unnecessary

6. If you choose not to download from a GitHub repository, you will be asked to provide the full path to your local Source folder.

7. If the compilation is successful, you will be given the option to rebuild or quit. 

#### Troubleshooting

When something goes wrong during the build, the log file will be launched (you can find the log files in `C:\php-sdk`). Otherwise, the log file will not be shown, and they remain in `C:\php-sdk` until you remove them manually.

In addition to the log files in `C:\php-sdk`, you can examine the contents of `C:\php-sdk\phpsdk-build-task.bat`, which is overwritten every time you run the build scripts.

#### Building the extensions unattended

You can invoke `py builddrivers.py` with the desired options plus the destination path `--DESTPATH=<some valid path>`. 

In such case, the `php-sdk` folder will be created in the same directory of these build scripts. Note that the PHP drivers will also be copied to the designated path. 

The script `builddrivers.py` provides an example for this case. Again, it's your choice whether to remove the `php-sdk` folder afterwards.







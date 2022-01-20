# Windows

## Prerequisites

To build extensions for 
1. PHP 7.0* or PHP 7.1*
    * install Visual Studio 2015 and make sure C++ tools are enabled. 
2. PHP 7.2* or above
    * install Visual Studio 2017 (PHP 7.*) or Visual Studio 2019 (PHP 8.*), including Visual C++ toolset and the Windows SDK components. 

To use the sample build scripts `builddrivers.py` and `buildtools.py`, install Python 3.x and Git for Windows (which comes with Visual Studio 2017 or 2019). If `git` is unrecognized in a regular command prompt, make sure the environment path is set up correctly.

## Compile the drivers 

You must first be able to build PHP source without including our PHP extensions. For help with building PHP 7.0* or PHP 7.1* in Windows, see the [official PHP website](https://wiki.php.net/internals/windows/stepbystepbuild). For PHP 7.2 or above, visit [PHP SDK page](https://github.com/OSTC/php-sdk-binary-tools) for new instructions.

The Microsoft Drivers for PHP for SQL Server have been compiled and tested with PHP 7.3+ using Visual Studio 2017 and PHP 8.0+ using Visual Studio 2019. The drivers for Windows that are published for each release (including previews) are digitally signed. You are recommended to sign the binaries you have compiled locally for your own development or testing purposes, using tools like Authenticode. It verifies the publisher's identity and prevents malicious actors from posing as legitimate developers.

### Manually building from source 

1. Download the *source* directory from this repository

2. Make a copy of the *shared* folder as a subfolder in *sqlsrv* and/or *pdo_sqlsrv* folder

3. Copy the *sqlsrv* and/or *pdo_sqlsrv* folder(s) into the PHP source *ext* subdirectory

4. Run `buildconf --force` to rebuild the configure.js script to include the *sqlsrv* and/or *pdo_sqlsrv* driver(s).

5. Run `configure.bat` with the desired driver options (as shown below) to generate the makefile. You can run `configure.bat --help` to see what other options are available. For example, for non-thread safe build, add this option `--disable-zts`.
    * For SQLSRV add: `--enable-sqlsrv=shared`
    * For PDO_SQLSRV add: `--enable-pdo --with-pdo-sqlsrv=shared`

6. Run `nmake`. Optionally, you can run `nmake clean` first.

7. To install the drivers, there are two ways:
    * Run `nmake install`, or
    * Copy the drivers:
        * Find the directory where the newly compiled *php.exe* is
        * Locate the compiled *php_sqlsrv.dll* and/or *php_pdo_sqlsrv.dll* 
        * Copy the dll(s) to the *ext* subfolder  

### Using the sample build scripts

The sample build scripts, `builddrivers.py` and `buildtools.py`, can be used to build our extensions for PHP in Windows.

#### Overview

When asked to provide the PHP version, you should enter values like `7.4.27`. If it's alpha, beta, or RC version, make sure the name you provide matches the PHP tag name without the prefix `php-`. For example, for PHP 8.0.0 beta 3, the tag name is `php-8.0.0beta3`, so you will enter `8.0.0beta3`. Visit [PHP SRC]( https://github.com/php/php-src) to find the appropriate tag names.

PHP recommends to unzip the PHP SDK into the shortest possible path, preferrably somewhere near the root drive. Therefore, this script will, by default, create a `php-sdk` folder in the C:\ drive, and this `php-sdk` directory tree will remain unless you remove it yourself. For ongoing development, we suggest you keep it around. The build scripts will handle updating the PHP SDK if a new version is available. 

#### Steps

1. Launch a regular `cmd` prompt 

2. Change to the directory where the Python scripts `builddrivers.py` and `buildtools.py` are

3. Interactive mode: 
    * Type `py builddrivers.py` to start the interactive mode. Use lower cases to answer the following questions:
        * PHP Version
        * 64-bit?
        * Thread safe?
        * Driver?
        * Debug enabled?
        * Download source from GitHub?
    * For `yes/no` questions, you can simply hit `ENTER` key for `yes`. Other questions are self-explanatory.
    
4. Use Command-line arguments
    * Type `py builddrivers.py -h` to get a list of options and their descriptions
    * For example, 
        * `py builddrivers.py --PHPVER=7.4.10 --ARCH=x64 --THREAD=nts --DRIVER=sqlsrv --SOURCE=C:\local\source`
        * `py builddrivers.py --PHPVER=8.1.0 --ARCH=x86 --THREAD=ts --DEBUG`

5. Based on the given configuration, if the script detects the presence of the PHP source directory, you can choose whether to rebuild, clean or superclean:
    * `rebuild` to build again using the same configuration (32 bit, thread safe, etc.)
    * `clean` to remove previous builds (binaries) 
    * `superclean` to remove the entire `php-<version>-src` directory, which is often unnecessary

6. If you choose not to download from a GitHub repository, you will be asked to provide the full path to your local Source folder.

7. If the compilation is successful, you will be given the option to rebuild or quit. 

#### Troubleshooting

If something went wrong or the build failed, the log file will be launched (you can find the log files in `C:\php-sdk`). Otherwise, the log file will not be shown, and they remain in `C:\php-sdk` until you remove them manually.

In addition to the log files in `C:\php-sdk`, you can examine the contents of `C:\php-sdk\phpsdk-build-task.bat`, which is overwritten every time you run the build scripts.

#### Testing mode and/or setting alternative destination 

If your main goal is to build the drivers for testing, and/or there is no need to keep the `php-sdk` directory around, you can invoke `py builddrivers.py` with the necessary command-line arguments plus `--TESTING`, which turns on the *testing* mode (it is False by default).

Setting the testing mode automatically turns off the looping mechanism. When the build is finished, you will find a copy of the drivers (unless the build failed) and the `php-sdk` folder in the same directory of these Python scripts. 

In addition, you can set an alternative destination using `--DESTPATH=<some valid path>`, which is **None** by default. Note that these two options are *not* available in the interactive mode. However, they are particularly useful for testing purposes (such as testing in a virtual machine) in which these build scripts are copied to a temporary folder. After the drivers have been successfully compiled and copied to the designated location, the temporary folder can be safely removed. 






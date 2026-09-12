# Windows

## Prerequisites

Install the Visual Studio version appropriate to your PHP version, including the Visual C++ toolset and Windows SDK components. The sample scripts select the following compiler and PHP SDK launcher:

| PHP version | Compiler | Visual Studio | SDK launcher |
|-------------|----------|---------------|--------------|
| 7.x through 8.3 (preserved mapping) | `vs16` | 2019 | `phpsdk-vs16-<arch>.bat` |
| 8.4 and 8.5 | `vs17` | 2022 | `phpsdk-vs17-<arch>.bat` |
| 8.6 and later | `vs18` | 2026 | `phpsdk-vs18-<arch>.bat` |

Here `<arch>` is `x64` or `x86`. Compiler selection uses integer major/minor components, including for prereleases. PHP sources and build outputs use the corresponding `php-sdk\phpdev\<compiler>\<arch>` directory.

PHP 8.6 / Visual Studio 2026 handling is **initial development compatibility**, not a claim of production support or completed toolchain validation. It requires a PHP SDK with the matching `vs18` launcher and compatible dependencies. The preserved older mappings describe script behavior, not the driver's current supported PHP versions.

To use the sample build scripts `builddrivers.py` and `buildtools.py`, install Python 3.x and Git for Windows. If `git` is unrecognized in a regular command prompt, make sure the environment path is set up correctly.

## Compile the drivers 

You must first be able to build PHP source without including our PHP extensions. Visit [PHP SDK page](https://github.com/php/php-sdk-binary-tools) for instructions on building PHP in Windows.

The Microsoft Drivers for PHP for SQL Server have been compiled and tested with PHP 8.2+ using Visual Studio 2019 or Visual Studio 2022. The drivers for Windows that are published for each release (including previews) are digitally signed. You are recommended to sign the binaries you have compiled locally for your own development or testing purposes, using tools like Authenticode. It verifies the publisher's identity and prevents malicious actors from posing as legitimate developers.

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

The shared version validator accepts PHP 7 or later in `major.minor` or `major.minor.patch` form, plus prereleases. For an alpha, beta, or RC version, use the exact PHP tag name without the `php-` prefix:

| Input | Generated PHP source tag |
|-------|--------------------------|
| `8.6.0alpha1` | `php-8.6.0alpha1` |
| `8.6.0beta3` | `php-8.6.0beta3` |
| `8.6.0RC1` | `php-8.6.0RC1` |

Legacy hyphen/dot prerelease separators are also accepted: `8.6.0-beta3` and `8.6.0.beta3` both generate `php-8.6.0beta3`. Only the separator before the suffix is removed; numeric version dots and suffix case are preserved. The local source directory retains the original input (for example, `php-8.6.0.beta3-src`). Stable inputs are unchanged (`8.6.0` generates `php-8.6.0`, and `8.6` generates `php-8.6`).

Validation does not check that a tag exists. Visit [PHP SRC](https://github.com/php/php-src) to select an available exact tag before building.

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
        * `py builddrivers.py --PHPVER=8.4.0 --ARCH=x64 --THREAD=nts --DRIVER=sqlsrv --SOURCE=C:\local\source`
        * `py builddrivers.py --PHPVER=8.3.0 --ARCH=x86 --THREAD=ts --DEBUG`

5. Based on the given configuration, if the script detects the presence of the PHP source directory, you can choose whether to rebuild, clean or superclean:
    * `rebuild` to build again using the same configuration (32 bit, thread safe, etc.)
    * `clean` to remove previous builds (binaries) 
    * `superclean` to remove the entire `php-<version>-src` directory, which is often unnecessary

6. If you choose not to download from a GitHub repository, you will be asked to provide the full path to your local Source folder.

7. If the compilation is successful, you will be given the option to rebuild or quit. 

#### Troubleshooting

If something went wrong or the build failed, the log file will be launched (you can find the log files in `C:\php-sdk`). Otherwise, the log file will not be shown, and they remain in `C:\php-sdk` until you remove them manually.

In addition to the log files in `C:\php-sdk`, you can examine the contents of `C:\php-sdk\phpsdk-build-task.bat`, which is overwritten every time you run the build scripts.

#### Local script tests

From the repository root, run the focused standard-library tests:

```powershell
py -B -m unittest discover -s buildscripts -p "test_buildtools.py" -v
```

These offline tests cover shared CLI validation, compiler/output paths, generated batch tags, and SDK launcher selection. External build and filesystem side effects are mocked; no downloads, Visual Studio installation, SQL Server, or PHP build are required. They do not replace compiling and testing the drivers with the actual PHP SDK and toolchain.

#### Testing mode and/or setting alternative destination 

If your main goal is to build the drivers for testing, and/or there is no need to keep the `php-sdk` directory around, you can invoke `py builddrivers.py` with the necessary command-line arguments plus `--TESTING`, which turns on the *testing* mode (it is False by default).

Setting the testing mode automatically turns off the looping mechanism. When the build is finished, you will find a copy of the drivers (unless the build failed) and the `php-sdk` folder in the same directory of these Python scripts. 

In addition, you can set an alternative destination using `--DESTPATH=<some valid path>`, which is **None** by default. Note that these two options are *not* available in the interactive mode. However, they are particularly useful for testing purposes (such as testing in a virtual machine) in which these build scripts are copied to a temporary folder. After the drivers have been successfully compiled and copied to the designated location, the temporary folder can be safely removed. 





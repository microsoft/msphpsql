## Windows

### Prerequisites
To use the Python build scripts `builddrivers.py` and `buildtools.py`, install Python 3.x. 

To build extensions for PHP 7.0* or 7.1*, install Visual Studio 2015 and make sure C++ tools are enabled. 

For PHP 7.2*, install Visual Studio 2017, and make sure Visual C++ toolset, the Windows SDK components, and Git for Windows are installed. 

See [PHP SDK page](https://github.com/OSTC/php-sdk-binary-tools) for more details.

### Build PHP extensions

Launch a regular `cmd` prompt and change to the directory where these Python scripts are. 

You'll be asked to input the PHP version. Simply type the version number like `7.1.7`. 

If it's alpha, beta, or RC, make sure the name you provide matches the PHP tag name without the prefix `php-`. For example, for PHP 7.2 beta 2, the tag name is `php-7.2.0beta2`, so you should enter `7.2.0beta2`. Visit https://github.com/php/php-src to find the appropriate tag names.

**Use Interactive mode**

Run `py builddrivers.py` 

Most questions are self-explanatory. Use lower cases for your inputs, and you can simply hit `ENTER` key for `yes/no` questions. 

When given a choice whether to download from a GitHub repository, you can choose to provide the full path to your local Source folder instead.

**Use Command-line arguments**

Run `py builddrivers.py` with command line arguments. For example:

`py builddrivers.py -v=7.1.7 -a=x86 -t=ts -d=all -g=no`

or

`py builddrivers.py --PHPVER=7.0.22 --ARCH=x64 --THREAD=nts --DRIVER=sqlsrv --GITHUB=yes`

**Notes**

It's recommended that the PHP SDK is unzipped into the shortest possible path, preferrably somewhere near the root drive. Therefore, this script will create a `php-sdk` folder in C:\ drive. This `php-sdk` folder will remain unless you remove it yourself. For ongoing development, it's suggested you keep it around. The build scripts will handle updating the PHP SDK if new version is available. 

If this is not the first time you build the drivers for a PHP version with certain configuration options (such as arch, thread safe, etc.), you will be prompted whether to rebuild, clean or superclean. Choose `rebuild` if you have always used the same configuration. Otherwise, choose `clean` to remove previous builds (binaries). The last option will remove the entire `php-<version>-src` directory, which is often unnecessary.

When something goes wrong during the build, the log file will be launched (you can find the log files in `C:\php-sdk`). Otherwise, the log file will not be shown, and they remain in `C:\php-sdk` until you remove them manually.

After the compilation is complete, you will be given the option to rebuild or quit. If you choose rebuild, the extensions will be re-compiled (say after you have changed any source or header file). This script will keep running until you choose to quit. 

In addition to the log files in `C:\php-sdk`, you can examine the contents of `C:\php-sdk\phpsdk-build-task.bat`, which is overwritten every time you run the build scripts.

When running build unattended, you should specify the destination path `--DESTPATH=<some valid path>`. In such case, the `php-sdk` folder will be created in the same working directory of these build scripts, and the drivers will be copied to your designated path. The script `builddrivers.py` provides an example for this case. Again, it's your choice whether to remove the `php-sdk` folder afterwards.







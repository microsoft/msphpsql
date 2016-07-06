#Microsoft Drivers for PHP for SQL Server

**Welcome to the Microsoft Drivers for PHP for SQL Server project!**

**Note:** For the PHP 7 project, see the PHP7 branch

The Microsoft Drivers for PHP for SQL Server are PHP 5 extensions that allow for the reading and writing of SQL Server data from within PHP scripts. The release contains two drivers, the SQLSRV driver and the PDO_SQLSRV driver. The SQLSRV extension provides a procedural interface while the PDO_SQLSRV extension implements PDO for accessing data in all editions of SQL Server 2005 and later (including SQL Azure). These drivers rely on the Microsoft ODBC Driver 11 for SQL Server to handle the low-level communication with SQL Server.

We hope you enjoy using the Microsoft Drivers for PHP for SQL Server.

The Microsoft Drivers for PHP for SQL Server Team

Announcements
- **July 06, 2016:** PHP Driver 4.0 for SQL Server with PHP 7 support is now GA. You can get the binaries [HERE](https://github.com/Azure/msphpsql/releases) or download the exe from the [Microsoft Download Center](https://www.microsoft.com/en-us/download/details.aspx?id=20098)
- **Jan 29, 2016:** A PHP7 branch is now active for the Early Technical Preview supporting PHP 7 
- Please visit the [blog][blog] for more announcements.

## Prerequisites

You must first be able to build PHP without including these
extensions.  For help with doing this, see the [official PHP website][phpweb].

## Build

To compile the SQLSRV and PDO_SQLSRV:

1. Copy the source code directories from this repository into the ext
subdirectory.

2. Run buildconf.bat to rebuild the configure.js script to include the
new drivers.

3. Run "cscript configure.js --enable-sqlsrv=shared --enable-pdo
--with-pdo-sqlsrv=shared [other options]" to generate the makefile.
Run "cscript configure.js --help" to see what other options are
available.  It is possible (and even probable) that other extensions
will have to be disabled or enabled for the compile to succeed.
Search bing.com for configurations that have worked for other people.
  * It might be possible to compile these extensions as non-shared but that configuration has not been tested.
  * NB: To build the driver with PHP 5.6.7 and later, you will need to specify the --with-odbcver=0x0380 argument to configure.js

4. Run "nmake".  It is suggested that you run the entire build.  If you
wish to do so, run "nmake clean" first.

5. To install the resulting build, run "nmake install" or just copy
php_sqlsrv.dll and php_pdo_sqlsrv.dll to your PHP extension directory.
Also enable them within your PHP installation's php.ini file.

This software has been compiled and tested under PHP 5.4.32 and later
using the Visual C++ 2008 and 2012, Express and Standard compilers.

## Documentation

This driver is documented on [Microsoft's Documentation web site][phpdoc].

## Changes

For details about the changes included in this release, please see our [blog][blog] or see the SQLSRV_Readme.htm 
file that is part of the download package.

## Known Issues

Please visit the [project on Github][project] to view outstanding [issues][issues].

## Download the driver
The driver can be downloaded from the [Microsoft Download Center][link]

## Notes

####Note about version.h

The version numbers in version.h in the source do not match the
version numbers in the supported PHP extension.

## License

The Microsoft Drivers for PHP for SQL Server are licensed under the MIT license.  See the LICENSE file for more details.

[blog]: http://blogs.msdn.com/b/sqlphp/

[project]: https://github.com/Azure/msphpsql

[issues]: https://github.com/Azure/msphpsql/issues

[phpweb]: http://php.net

[phpdoc]: http://msdn.microsoft.com/en-us/library/dd903047%28SQL.11%29.aspx

[link]: https://www.microsoft.com/en-us/download/details.aspx?id=20098

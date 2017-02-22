**This branch has been archived at January 2017 and will not be updated.**

# Microsoft Drivers for PHP for SQL Server

**Welcome to the Microsoft Drivers for PHP for SQL Server PHP 7**

The Microsoft Drivers for PHP for SQL Server are PHP extensions that allow for the reading and writing of SQL Server data from within PHP scripts. The SQLSRV extension provides a procedural interface while the PDO_SQLSRV extension implements PDO for accessing data in all editions of SQL Server 2005 and later (including Azure SQL DB). These drivers rely on the Microsoft ODBC Driver for SQL Server to handle the low-level communication with SQL Server.

This release contains the SQLSRV and PDO_SQLSRV drivers for PHP 7 with improvements on both drivers and some limitations (see Limitations below for details).  Upcoming release(s) will contain more functionality, bug fixes, and more (see Plans below for more details).

SQL Server Team
###Status of Most Recent Builds
| AppVeyor (Windows)      |Travis CI (Linux) |        Coverage Status  
|-------------------------|--------------------------| ------------------
| [![av-image][]][av-site]| [![tv-image][]][tv-site] |[![Coverage Status][]][coveralls-site]

[av-image]:  https://ci.appveyor.com/api/projects/status/github/Microsoft/msphpsql?branch=PHP-7.0&svg=true
[av-site]: https://ci.appveyor.com/project/Microsoft-PHPSQL/msphpsql
[tv-image]:  https://travis-ci.org/Microsoft/msphpsql.svg?branch=PHP-7.0-Linux
[tv-site]: https://travis-ci.org/Microsoft/msphpsql/
[Coverage Status]: https://coveralls.io/repos/github/Microsoft/msphpsql/badge.svg?branch=PHP-7.0-Linux
[coveralls-site]: https://coveralls.io/github/Microsoft/msphpsql?branch=PHP-7.0-Linux

##Get Started

* [**Ubuntu + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php-ubuntu)
* [**RedHat + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php-rhel)
* [**Windows + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php-windows)


##Announcements

**December 19, 2016**: We are delighted announce that production release for PHP Linux Driver for SQL Server is available. PECL packages (4.0.8) are updated with the latest changes, and Linux binaries (4.0.8) compiled with PHP 7.0.14 are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. For complete list of changes please visit [CHANGELOG](https://github.com/Microsoft/msphpsql/blob/PHP-7.0-Linux/CHANGELOG.md) file.

 Please visit the [blog][blog] for more announcements.


## Build

Note: if you prefer, you can use the pre-compiled binary found [HERE](https://github.com/Azure/msphpsql/releases)

####Prerequisites

You must first be able to build PHP 7 without including these extensions.  For help with doing this, see the [official PHP website][phpbuild] for building your own PHP on Windows.

####Compile the drivers

1. Copy the sqlsrv and/or pdo_sqlsrv source code directory from this repository into the ext subdirectory.

2. Run `buildconf.bat` to rebuild the configure.js script to include the driver.

3. Run `configure.bat --with-odbcver=0x0380 and the desired driver options (as below) [plus other options such as --disable-zts for the Non Thread Safe build]` to generate the makefile.  You can run `configure.bat --help` to see what other options are available.
  * For SQLSRV use: `--enable-sqlsrv=shared`
  * For PDO_SQLSRV use: `--enable-pdo=shared --with-pdo-sqlsrv=shared`

4. Run `nmake`.  It is suggested that you run the entire build.  If you wish to do so, run `nmake clean` first.

5. To install the resulting build, run `nmake install` or just copy php_sqlsrv.dll and/or php_pdo_sqlsrv.dll to your PHP extension directory.

This software has been compiled and tested under PHP 7.0.8 using the Visual C++ 2015 compiler.

## Install

####Prerequisites

- A Web server such as Internet Information Services (IIS) is required. Your Web server must be configured to run PHP
- [Microsoft ODBC Driver 11][odbc11] or [Microsoft ODBC Driver 13][odbc13]

####Enable the drivers

1. Make sure that the driver is in your PHP extension directory (you can simply copy it there if you did not use nmake install).

2. Enable it within your PHP installation's php.ini: `extension=php_sqlsrv.dll` and/or `extension=php_pdo_sqlsrv.dll`.  If necessary, specify the extension directory using extension_dir, for example: `extension_dir = "C:\PHP\ext"`

3. Restart the Web server.

## Sample Code
For samples, please see the sample folder.  For setup instructions, see [here] [phpazure]

## Limitations

- This release contains the PHP 7 port of the SQLSRV and PDO_SQLSRV drivers, and does not provide backwards compatibility with PHP 5.
- Binding output parameter using emulate prepare is not supported.

## Known Issues
-  User defined data types and SQL_VARIANT.
- Binary column binding with emulate prepare ([issue#140](https://github.com/Microsoft/msphpsql/issues/140) )

## Future Plans
- Expand SQL 16 Feature Support (example: Always Encrypted).
- Build Verification/Fundamental Tests.
- Bug Fixes.

## Guidelines for Reporting Issues
We appreciate you taking the time to test the driver, provide feedback and report any issues.  It would be extremely helpful if you:

- Report each issue as a new issue (but check first if it's already been reported)
- Try to be detailed in your report. Useful information for good bug reports include:
  * What you are seeing and what the expected behaviour is
  * Can you connect to SQL Server via `sqlcmd`? 
  * Which driver: SQLSRV or PDO_SQLSRV?
  * Environment details: e.g. PHP version, thread safe (TS) or non-thread safe (NTS), 32-bit &/or 64-bit?
  * Table schema (for some issues the data types make a big difference!)
  * Any other relevant information you want to share
- Try to include a PHP script demonstrating the isolated problem.

Thank you!

## FAQs
**Q:** Can we get dates for any of the Future Plans listed above?

**A:** At this time, Microsoft is not able to announce dates. We are working extremely hard to release future versions of the driver. We will share future plans once they solidify over the next few weeks. 

**Q:** What's next?

**A:** On July 20, 2016 we released the early technical preview for our PHP Driver. We will continue releasing frequent technical previews until we reach production quality.

**Q:** Is Microsoft taking pull requests for this project?

**A:** We will not be seeking to take pull requests until GA, Build Verification, and Fundamental tests are released. At this point Microsoft will also begin actively developing using this GitHub project as the prime repository.



## License

The Microsoft Drivers for PHP for SQL Server are licensed under the MIT license.  See the LICENSE file for more details.

## Code of conduct

This project has adopted the Microsoft Open Source Code of Conduct. For more information see the Code of Conduct FAQ or contact opencode@microsoft.com with any additional questions or comments.

## Resources

**Documentation**: [MSDN Online Documentation][phpdoc].  Please note that this documentation is not yet updated for PHP 7.

**Team Blog**: Browse our blog for comments and announcements from the team in the [team blog][blog].

**Known Issues**: Please visit the [project on Github][project] to view outstanding [issues][issues] and report new ones.

[blog]: http://blogs.msdn.com/b/sqlphp/

[project]: https://github.com/Azure/msphpsql

[issues]: https://github.com/Azure/msphpsql/issues

[phpweb]: http://php.net

[phpbuild]: https://wiki.php.net/internals/windows/stepbystepbuild

[phpdoc]: http://msdn.microsoft.com/library/dd903047%28SQL.11%29.aspx

[odbc11]: https://www.microsoft.com/download/details.aspx?id=36434

[odbc13]: https://www.microsoft.com/download/details.aspx?id=50420

[odbcLinux]: https://msdn.microsoft.com/library/hh568454(v=sql.110).aspx

[phpazure]: https://azure.microsoft.com/documentation/articles/sql-database-develop-php-simple-windows/

[PHPMan]: http://php.net/manual/install.unix.php

[LinuxDM]: https://msdn.microsoft.com/library/hh568449(v=sql.110).aspx

[httpd_source]: http://httpd.apache.org/

[apr_source]: http://apr.apache.org/

[httpdconf]: http://php.net/manual/en/install.unix.apache2.php

[ODBCinstallers]: https://blogs.msdn.microsoft.com/sqlnativeclient/2016/09/06/preview-release-of-the-sql-server-cc-odbc-driver-13-0-0-for-linux

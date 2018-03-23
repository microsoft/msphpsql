# Microsoft Drivers for PHP for Microsoft SQL Server

**Welcome to the Microsoft Drivers for PHP for Microsoft SQL Server**

The Microsoft Drivers for PHP for Microsoft SQL Server are PHP extensions that allow for the reading and writing of SQL Server data from within PHP scripts. The SQLSRV extension provides a procedural interface while the PDO_SQLSRV extension implements PHP Data Objects (PDO) for accessing data in all editions of SQL Server 2008 R2 and later (including Azure SQL DB). These drivers rely on the Microsoft ODBC Driver for SQL Server to handle the low-level communication with SQL Server.

This release contains the SQLSRV and PDO_SQLSRV drivers for PHP 7.* with improvements on both drivers and some limitations (see Limitations below for details).  Upcoming releases will contain additional functionality, bug fixes, and more (see Plans below for more details).

SQL Server Team


## Take our survey

Thank you for taking the time to participate in our last survey. You can continue to help us improve by letting us know how we are doing and how you use PHP by taking our December pulse survey:

<a href="https://aka.ms/mssqlphpsurvey"><img style="float: right;"  height="67" width="156" src="https://meetsstorenew.blob.core.windows.net/contianerhd/survey.png?st=2017-02-17T22%3A03%3A00Z&se=2100-02-18T22%3A03%3A00Z&sp=rl&sv=2015-12-11&sr=b&sig=DJSFoihBptSvO%2BjvWzwpHecf8o5yfAbJoD2qW5oB8tc%3D"></a>

### Status of Most Recent Builds
| AppVeyor (Windows)       | Travis CI (Linux)        | Coverage (Windows)                    | Coverage (Linux)                          |
|--------------------------|--------------------------|---------------------------------------|-------------------------------------------|
| [![av-image][]][av-site] | [![tv-image][]][tv-site] | [![Coverage Codecov][]][codecov-site] | [![Coverage Coveralls][]][coveralls-site] |

[av-image]:  https://ci.appveyor.com/api/projects/status/xhp4nq9ouljnhxqf/branch/dev?svg=true
[av-site]: https://ci.appveyor.com/project/Microsoft-PHPSQL/msphpsql-frhmr/branch/dev
[tv-image]:  https://travis-ci.org/Microsoft/msphpsql.svg?branch=dev
[tv-site]: https://travis-ci.org/Microsoft/msphpsql/
[Coverage Coveralls]: https://coveralls.io/repos/github/Microsoft/msphpsql/badge.svg?branch=dev
[coveralls-site]: https://coveralls.io/github/Microsoft/msphpsql?branch=dev
[Coverage Codecov]: https://codecov.io/gh/microsoft/msphpsql/branch/dev/graph/badge.svg
[codecov-site]: https://codecov.io/gh/microsoft/msphpsql

## Get Started

* [**Windows + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php/windows)
* [**Ubuntu + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php/ubuntu)
* [**RedHat + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php/rhel)
* [**SUSE + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php/sles)
* [**macOS + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php/mac/)
* [**Docker**](https://hub.docker.com/r/lbosqmsft/mssql-php-msphpsql/)


## Announcements

 Please visit the [blog][blog] for more announcements.

## Prerequisites

For full details on the system requirements for the drivers, see the [system requirements](https://docs.microsoft.com/en-us/sql/connect/php/system-requirements-for-the-php-sql-driver) on MSDN.

On the client machine:
- PHP 7.0.x, 7.1.x, or 7.2.x (7.2.0 and up on Unix, 7.2.1 and up on Windows)
- A Web server such as Internet Information Services (IIS) is required. Your Web server must be configured to run PHP
- [Microsoft ODBC Driver 11][odbc11], [Microsoft ODBC Driver 13][odbc13], or [Microsoft ODBC Driver 17][odbc17]

On the server side, Microsoft SQL Server 2008 R2 and above on Windows is supported, as is Microsoft SQL Server 2016 and above on Linux.

## Building and Installing the Drivers on Windows

The drivers are distributed as pre-compiled extensions for PHP found on the [releases page](https://github.com/Microsoft/msphpsql/releases). They are available in thread-safe and non thread-safe versions, and in 32-bit and 64-bit versions. The source code for the drivers is also available, and you can compile them as thread safe or non-thread safe versions. The thread safety configuration of your web server will determine which version you need. 
 
If you choose to build the drivers, you must be able to build PHP 7 without including these extensions. For help building PHP on Windows, see the [official PHP website][phpbuild]. For details on compiling the drivers, see the [documentation](https://github.com/Microsoft/msphpsql/tree/dev/buildscripts#windows) -- an example buildscript is provided, but you can also compile the drivers manually.

To load the drivers, make sure that the driver is in your PHP extension directory and enable it in your PHP installation's php.ini file by adding `extension=php_sqlsrv.dll` and/or `extension=php_pdo_sqlsrv.dll` to it.  If necessary, specify the extension directory using `extension_dir`, for example: `extension_dir = "C:\PHP\ext"`. Note that the precompiled binaries have different names -- substitute accordingly in php.ini. For more details on loading the drivers, see [Loading the PHP SQL Driver](https://docs.microsoft.com/en-us/sql/connect/php/loading-the-php-sql-driver) on MSDN.

Finally, restart the Web server.

## Install (UNIX)

For full instructions on installing the drivers on all supported Unix platforms, see [the installation instructions on MSDN](https://docs.microsoft.com/en-us/sql/connect/php/installation-tutorial-linux-mac).

## Sample Code
For PHP code samples, please see the [sample](https://github.com/Microsoft/msphpsql/tree/master/sample) folder or the [code samples on MSDN](https://docs.microsoft.com/en-us/sql/connect/php/code-samples-for-php-sql-driver).

## Limitations and Known Issues
Please refer to [Releases](https://github.com/Microsoft/msphpsql/releases) for the latest limitations and known issues.

## Version number
The version numbers of the PHP drivers follow [semantic versioning](http://semver.org/):

Given a version number MAJOR.MINOR.PATCH, 

 - MAJOR version is incremented when an incompatible API change is made, 
 - MINOR version is incremented when functionality is added in a backwards-compatible manner, and
 - PATCH version is incremented when backwards-compatible bug fixes are made.
 
The version number may have trailing pre-release version identifiers to indicate the stability and/or build metadata.

- Pre-release version is denoted by a hyphen followed by `preview` or `RC` and may be followed by a series of dot separated identifiers. Production quality releases do not contain the pre-release version. `preview` has lower precedence than `RC`. Example of precedence: *preview < preview.1 < RC < RC.1*. Note that the PECL package version numbers do not have the hyphen before the pre-release version, owing to restrictions in PECL. Example of a PECL package version number: 1.2.3preview
- Build metadata may be denoted by a plus sign followed by 4 or 5 digits, such as  `1.2.3-preview+5678` or `1.2.3+5678`. Build metadata does not figure into the precedence order.

## Future Plans
- Expand SQL Server 2016 feature support (example: Always Encrypted)
- Add more verification/fundamental tests
- Bug fixes

## Guidelines for Reporting Issues
We appreciate you taking the time to test the driver, provide feedback and report any issues.  It would be extremely helpful if you:

- Report each issue as a new issue (but check first if it's already been reported)
- Try to be detailed in your report. Useful information for good bug reports includes:
  * What you are seeing and what the expected behaviour is
  * Can you connect to SQL Server via `sqlcmd`? 
  * Which driver: SQLSRV or PDO_SQLSRV?
  * Environment details: e.g. PHP version, thread safe (TS) or non-thread safe (NTS), 32-bit or 64-bit?
  * Table schema (for some issues, the data types make a big difference!)
  * Any other relevant information you want to share
- Try to include a PHP script demonstrating the isolated problem.

Thank you!

## FAQs
**Q:** Can we get dates for any of the Future Plans listed above?

**A:** At this time, Microsoft is not able to announce dates. We are working hard to release future versions of the driver and will share future plans as appropriate. 

**Q:** What's next?

**A:** On March 23, 2018 we released the production release version 5.2.0 of our PHP Driver. We will continue working on our future plans and releasing previews of upcoming releases frequently.

**Q:** Is Microsoft taking pull requests for this project?

**A:** Yes. Please submit pull requests to the **dev** branch and not the **master** branch.



## License

The Microsoft Drivers for PHP for SQL Server are licensed under the MIT license.  See the LICENSE file for more details.

## Code of conduct

This project has adopted the Microsoft Open Source Code of Conduct. For more information see the Code of Conduct FAQ or contact opencode@microsoft.com with any additional questions or comments.

## Resources

**Documentation**: [MSDN Online Documentation][phpdoc].

**Team Blog**: Browse our blog for comments and announcements from the team in the [team blog][blog].

**Known Issues**: Please visit the [project on Github][project] to view outstanding [issues][issues] and report new ones.

[blog]: http://blogs.msdn.com/b/sqlphp/

[project]: https://github.com/Microsoft/msphpsql

[issues]: https://github.com/Microsoft/msphpsql/issues

[phpweb]: http://php.net

[phpbuild]: https://wiki.php.net/internals/windows/stepbystepbuild

[phpdoc]: http://msdn.microsoft.com/library/dd903047%28SQL.11%29.aspx

[odbc11]: https://www.microsoft.com/download/details.aspx?id=36434

[odbc13]: https://www.microsoft.com/download/details.aspx?id=50420

[odbc17]: https://github.com/Microsoft/msphpsql/tree/master/ODBC%2017%20binaries%20preview

[odbcLinux]: https://msdn.microsoft.com/library/hh568454(v=sql.110).aspx

[PHPMan]: http://php.net/manual/install.unix.php

[LinuxDM]: https://msdn.microsoft.com/library/hh568449(v=sql.110).aspx

[httpd_source]: http://httpd.apache.org/

[apr_source]: http://apr.apache.org/

[httpdconf]: http://php.net/manual/en/install.unix.apache2.php
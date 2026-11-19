# Microsoft Drivers for PHP for Microsoft SQL Server

**Welcome to the Microsoft Drivers for PHP for Microsoft SQL Server**

The [Microsoft Drivers for PHP for Microsoft SQL Server][phpdoc] are PHP extensions that allow for the reading and writing of SQL Server data from within PHP scripts. The SQLSRV extension provides a procedural interface while the PDO_SQLSRV extension implements PHP Data Objects (PDO) for accessing data in all editions of SQL Server 2012 and later (including Azure SQL DB). These drivers rely on the [Microsoft ODBC Driver for SQL Server][odbcdoc] to handle the low-level communication with SQL Server.

This 5.14.0 release supports PHP 8.4, PHP 8.5, and PHP 8.6 with the SQLSRV and PDO_SQLSRV drivers. PHP 8.3 and earlier are no longer supported. Upcoming [releases][releases] will contain additional functionalities, bug fixes, and more.

## Take our survey

Thank you for taking the time to participate in the [sentiment survey](https://github.com/microsoft/msphpsql/wiki/Survey-Results). You can continue to help us improve by letting us know how we are doing and how you use [PHP][phpweb]:

[**Click here to start the PHP survey**](https://aka.ms/mssqlphpsurvey)

### Status of Most Recent Builds
| Azure Pipelines (Linux)  | AppVeyor (Windows)       | Coverage (Windows)                    |
|--------------------------|--------------------------|---------------------------------------|
| [![az-image][]][az-site] | [![av-image][]][av-site] | [![Coverage Codecov][]][codecov-site] |

[av-image]: https://ci.appveyor.com/api/projects/status/vo4rfei6lxlamrnc?svg=true
[av-site]: https://ci.appveyor.com/project/msphpsql/msphpsql/branch/dev
[az-site]: https://sqlclientdrivers.visualstudio.com/public/_build/latest?definitionId=1230&branchName=refs%2Fpull%2F1492%2Fmerge
[az-image]: https://sqlclientdrivers.visualstudio.com/public/_apis/build/status%2FPHP%2Fmicrosoft.msphpsql?branchName=refs%2Fpull%2F1492%2Fmerge
[Coverage Codecov]: https://codecov.io/gh/microsoft/msphpsql/branch/dev/graph/badge.svg
[codecov-site]: https://codecov.io/gh/microsoft/msphpsql

### macOS CI setup

The macOS job in `azure-pipelines.yml` reuses installed Homebrew formulae without automatic updates or upgrades, installs missing tools serially, and uses the Docker CLI with Colima rather than Docker Desktop. Its minimal PHP build does not enable `intl`, so it does not install ICU. PHP's build system still checks the required Bison and re2c versions.

After ODBC installation, Colima and SQL Server startup run alongside PHP compilation. The job waits for both, uses bounded SQL connection probes instead of fixed startup sleeps, and prints setup diagnostics even when compilation fails. Both functional suites still run in full. `test/tools/test_macos_ci_setup.py` exercises this coordination and failure handling with mocked tools; actual macOS CI is required to measure runtime savings.

## Get Started

Please follow the [Getting started](https://docs.microsoft.com/sql/connect/php/getting-started-with-the-php-sql-driver) page.

Install [PIE (PHP Installer for Extensions)](https://github.com/php/pie/blob/1.6.x/docs/usage.md) and the [prerequisites](#prerequisites), then run:

```shell
pie install microsoft/sqlsrv
pie install microsoft/pdo_sqlsrv
```

Starting with 5.14.0, new driver releases are published through PIE instead of PECL. PIE supports Windows, Linux, and macOS; prebuilt binaries remain available on the [releases page][releases].

## Announcements

 Please follow [SQL Server Drivers][sqldrivers] for announcements.

## Prerequisites

For full details on the system requirements for the drivers, see the [system requirements](https://docs.microsoft.com/sql/connect/php/system-requirements-for-the-php-sql-driver) on Microsoft Docs.

On the client machine:
- PHP 8.4.x, 8.5.x, or 8.6.x
- [Microsoft ODBC Driver 18 or 17][odbcdoc]
- If using a Web server such as Internet Information Services (IIS) or Apache, it must be configured to run PHP

On the server side, Microsoft SQL Server 2012 and above on Windows are supported, as are Microsoft SQL Server 2016 and above on Linux.

## Building and Installing the Drivers on Windows

PIE automatically selects the precompiled extension matching your PHP version, thread safety, and architecture, and attempts to enable it. Alternatively, download precompiled extensions from the [releases page][releases] for manual installation. They are available in thread-safe and non-thread-safe versions, and in 32-bit (Windows only) and 64-bit versions. The source code for the drivers is also available, and you can compile them as thread safe or non-thread-safe versions. The thread safety configuration of your web server will determine which version you need.
 
If you choose to build the drivers, you must be able to build PHP 8.4, 8.5 or 8.6 without including these extensions. For help building PHP on Windows, see the [official PHP website][phpbuild]. For details on compiling the drivers, see the [documentation](https://github.com/microsoft/msphpsql/blob/master/buildscripts/README.md) -- an example buildscript is provided, but you can also compile the drivers manually.

For manual installation, make sure that the driver is in your PHP extension directory and enable it in your PHP installation's php.ini file by adding `extension=php_sqlsrv.dll` and/or `extension=php_pdo_sqlsrv.dll` to the ini file. If necessary, specify the extension directory using `extension_dir`, for example: `extension_dir = "C:\PHP\ext"`. Note that the precompiled binaries have different names -- substitute accordingly in php.ini. Do not add duplicate entries if PIE has already enabled the drivers. For more details on loading the drivers, see [Loading the PHP SQL Driver](https://docs.microsoft.com/sql/connect/php/loading-the-php-sql-driver) on Microsoft Docs.

Finally, if running PHP in a Web server, restart the Web server.

## Install (UNIX)

For full instructions on installing the drivers with PIE on supported Unix platforms, see the [Linux and macOS installation tutorial](Linux-mac-install.md) or [the installation instructions on Microsoft Docs][unixinstructions].

## Sample Code
For PHP code samples, please see the [sample](https://github.com/Microsoft/msphpsql/tree/master/sample) folder or the [code samples on Microsoft Docs](https://docs.microsoft.com/sql/connect/php/code-samples-for-php-sql-driver). For information on how to use the driver, see [Microsoft Drivers for PHP for Microsoft SQL Server][phpdoc].

## Limitations and Known Issues
Please refer to [Releases][releases] for the latest limitations and known issues.

## Version number
The version numbers of the PHP drivers follow [semantic versioning](https://semver.org/):

Given a version number MAJOR.MINOR.PATCH, 

 - MAJOR version is incremented when an incompatible API change is made, 
 - MINOR version is incremented when functionality is added in a backwards-compatible manner, and
 - PATCH version is incremented when backwards-compatible bug fixes are made.
 
The version number may have trailing pre-release version identifiers to indicate the stability and/or build metadata.

- Pre-release version is denoted by a hyphen followed by `beta` or `RC` followed by a number. Production quality releases do not contain the pre-release version. `beta` has lower precedence than `RC`. Historical PECL package versions omit the hyphen before the pre-release version, for example `5.9.0beta2`; new releases use PIE.
- Build metadata may be denoted by a plus sign followed by a number of digits, such as `5.9.0-beta2+13930`. Build metadata does not affect the precedence order.

## Future Plans
- Expand SQL Server feature support (example: Azure Active Directory, Always Encrypted, etc.)
- Add more verification/fundamental tests
- Improve performance
- Bug fixes

## Guidelines for Reporting Issues
We appreciate you taking the time to test the driver, provide feedback and report any issues.  It would be extremely helpful if you:

- First check the [FAQ](https://github.com/Microsoft/msphpsql/wiki/FAQ) for common problems
- Report each issue as a new issue (but check first if it's already been reported)
- Please address the questions in the new issue template and provide scripts, table schema, and/or any details that may help reproduce the problem(s)

Thank you!

## Questions
**Q:** Can we get dates for any of the Future Plans listed above?

**A:** At this time, Microsoft is not able to announce dates. We are working hard to release future versions of the driver and will share future plans as appropriate. 

**Q:** What's next?

**A:** We will continue working on our future plans and releasing previews of upcoming [releases][releases]

**Q:** Is Microsoft taking pull requests for this project?

**A:** Yes. Please submit pull requests to the **dev** branch, not the **master** branch.

## License

The Microsoft Drivers for PHP for SQL Server are licensed under the MIT license. See the LICENSE file for more details.

## Code of conduct

This project has adopted the Microsoft Open Source Code of Conduct. For more information see the Code of Conduct FAQ or contact opencode@microsoft.com with any additional questions or comments.

## Resources

**Documentation**: [Microsoft Docs Online][phpdoc].

**SQL Server Drivers**: Please browse the articles for announcements of various [SQL Server Drivers][sqldrivers].

**Known Issues**: Please visit the [project on Github][project] to view outstanding [issues][issues] and report new ones.

[sqldrivers]: https://techcommunity.microsoft.com/t5/SQL-Server/bg-p/SQLServer/label-name/SQLServerDrivers

[project]: https://github.com/microsoft/msphpsql

[issues]: https://github.com/microsoft/msphpsql/issues

[releases]: https://github.com/microsoft/msphpsql/releases

[phpweb]: https://php.net

[phpbuild]: https://wiki.php.net/internals/windows/stepbystepbuild_sdk_2

[phpdoc]: https://docs.microsoft.com/sql/connect/php/microsoft-php-driver-for-sql-server

[odbcdoc]: https://docs.microsoft.com/sql/connect/odbc/microsoft-odbc-driver-for-sql-server

[unixinstructions]: https://docs.microsoft.com/sql/connect/php/installation-tutorial-linux-mac

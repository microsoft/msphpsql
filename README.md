# Microsoft Drivers for PHP for SQL Server

**Welcome to the Microsoft Drivers for PHP for SQL Server PHP 7 (Early Technical Preview)**

The Microsoft Drivers for PHP for SQL Server are PHP extensions that allow for the reading and writing of SQL Server data from within PHP scripts. The SQLSRV extension provides a procedural interface while the PDO_SQLSRV extension implements PDO for accessing data in all editions of SQL Server 2005 and later (including Azure SQL DB). These drivers rely on the Microsoft ODBC Driver for SQL Server to handle the low-level communication with SQL Server.

This preview contains the SQLSRV and PDO_SQLSRV drivers for PHP 7 with limitations (see Limitations below for details).  Upcoming release(s) will contain more functionality, bug fixes, and more (see Plans below for more details).

The Microsoft Drivers for PHP for SQL Server Team

##Announcements

April 12, 2016 (4.0.3): The PDO_SQLSRV driver (32-bit and 64-bit) is now available.  For the SQLSRV driver, we also have a few bug fixes to share:
- Fixed ability to fetch a user defined object into a class
- Fixed issue with re-preparing the same statement with referenced datetime parameters
- Fixed issue with binding output parameters with php type string with binary and char encodings and sql types SQLSRV_SQLTYPE_NCHAR and SQLSRV_SQLTYPE_NVARCHAR

March 15, 2016 (4.0.2): 64-bit support is now available for the SQLSRV driver.  We also have some additional minor improvements to share:
- Fixed the ability to retrieve strings as an output parameter
- Fixed a number of memory leaks in initialization

Feb 23, 2016 (4.0.1): Thanks to the community’s input, we have mostly been focusing on making updates to support native 64-bit and the PDO driver.  We will be sharing these updates in the coming weeks once we have something functional.  In the meantime, we have a couple of minor updates to the SQLSRV driver to share:
- Fixed the ability to bind parameters with datetime types
- Fixed output and bidirectional (input/output) parameters.  Note to users: we determined that output and bidirectional parameters now need to be passed in by reference (i.e. &$var) so that they can be updated with the output data and added an error check for these cases.
- Updated refcounting to avoid unnecessary reference counting for scalar values


## Build

Note: if you prefer, you can use the pre-compiled binary found [HERE](https://github.com/Azure/msphpsql/tree/PHP-7.0/binaries)

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

This software has been compiled and tested under PHP 7.0.5 using the Visual C++ 2015 compiler.

## Install

####Prerequisites

- A Web server such as Internet Information Services (IIS) is required. Your Web server must be configured to run PHP
- [Microsoft ODBC Driver 11][odbc]

####Enable the drivers

1. Make sure that the driver is in your PHP extension directory (you can simply copy it there if you did not use nmake install).

2. Enable it within your PHP installation's php.ini: `extension=php_sqlsrv.dll` and/or `extension=php_pdo_sqlsrv.dll`.  If necessary, specify the extension directory using extension_dir, for example: `extension_dir = "C:\PHP\ext"`

3. Restart the Web server.

## Sample Code
For samples, please see the sample folder.  For setup instructions, see [here] [phpazure]

## Limitations

This preview contains the PHP 7 port of the SQLSRV and PDO_SQLSRV drivers. The focus was on basic functionality and does not provide backwards compatibility with PHP 5. The following items have known issues:

SQLSRV:
- Retrieving stream data and metadata
- Handle UTF8 strings
- Memory management

SQLSRV 64-bit only:
- Retrieving integers as output parameters

PDO_SQLSRV:
- The $driver_options (for specifying encoding) in PDOStatement::bindParam does not work due to a bug in the PDO extension source code. A fix for this bug now [exists](https://github.com/php/php-src/commit/5b8d0dc6ae01907d35ea51c061addedfe81e4e1f) but it hasn't made it into an official PHP release yet.

## Future Plans

- Linux Version
- Expand SQL 16 Feature Support (example: Always Encrypted)
- Build Verification/Fundamental Tests
- Bug Fixes

## Guidelines for Reporting Issues
We appreciate you taking the time to test the driver, provide feedback and report any issues.  It would be extremely helpful if you:

- Report each issue as a new issue (but check first if it's already been reported)
- Try to be detailed in your report. Useful information for good bug reports include:
  * What you are seeing and what the expected behaviour is
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

**A:** On Jan 29, 2016 we released an early technical preview for our PHP Driver and several since. We will continue releasing frequent technical previews until we reach production quality.

**Q:** Is Microsoft taking pull requests for this project?

**A:** We will not be seeking to take pull requests until GA, Build Verification, and Fundamental tests are released. At this point Microsoft will also begin actively developing using this GitHub project as the prime repository.



## License

The Microsoft Drivers for PHP for SQL Server are licensed under the MIT license.  See the LICENSE file for more details.

## Resources

**Documentation**: [MSDN Online Documentation][phpdoc].  Please note that this documentation is not yet updated for PHP 7.

**Team Blog**: Browse our blog for comments and announcements from the team in the [team blog][blog].

**Known Issues**: Please visit the [project on Github][project] to view outstanding [issues][issues] and report new ones.

[blog]: http://blogs.msdn.com/b/sqlphp/

[project]: https://github.com/Azure/msphpsql

[issues]: https://github.com/Azure/msphpsql/issues

[phpweb]: http://php.net

[phpbuild]: https://wiki.php.net/internals/windows/stepbystepbuild

[phpdoc]: http://msdn.microsoft.com/en-us/library/dd903047%28SQL.11%29.aspx

[odbc]: https://www.microsoft.com/en-us/download/details.aspx?id=36434

[phpazure]: https://azure.microsoft.com/en-us/documentation/articles/sql-database-develop-php-simple-windows/

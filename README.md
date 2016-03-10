# Microsoft Drivers for PHP for SQL Server

**Welcome to the Microsoft Drivers for PHP for SQL Server PHP 7 (Early Technical Preview)**

The Microsoft Drivers for PHP for SQL Server are PHP extensions that allow for the reading and writing of SQL Server data from within PHP scripts. The SQLSRV extension provides a procedural interface while the PDO_SQLSRV extension implements PDO for accessing data in all editions of SQL Server 2005 and later (including Azure SQL DB). These drivers rely on the Microsoft ODBC Driver for SQL Server to handle the low-level communication with SQL Server.

This preview contains the SQLSRV driver for 32-bit PHP 7 with limitations (see Limitations below for details).  Upcoming release(s) will contain more functionality, bug fixes, the PDO_SQLSRV driver, 64-bit support and more (see Plans below for more details).

The Microsoft Drivers for PHP for SQL Server Team

##Announcements

Feb 23, 2016: Thanks to the community’s input, we have mostly been focusing on making updates to support native 64-bit and the PDO driver.  We will be sharing these updates in the coming weeks once we have something functional.  In the meantime, we have a couple of minor updates to the SQLSRV driver to share:
- Fixed the ability to bind parameters with datetime types
- Fixed output and bidirectional (input/output) parameters.  Note to users: we determined that output and bidirectional parameters now need to be passed in by reference (i.e. &$var) so that they can be updated with the output data and added an error check for these cases.
- Updated refcounting to avoid unnecessary reference counting for scalar values


## Build

Note: if you prefer, you can use the pre-compiled binary found [HERE](https://github.com/Azure/msphpsql/releases/tag/v4.0.0)

####Prerequisites

You must first be able to build PHP 7 without including these extensions.  For help with doing this, see the [official PHP website][phpbuild] for building your own PHP on Windows.

####Compile the SQLSRV driver

1. Copy the sqlsrv source code directory from this repository into the ext subdirectory.

2. Run `buildconf.bat` to rebuild the configure.js script to include the driver.

3. Run `configure.bat --enable-sqlsrv=shared --with-odbcver=0x0380 [other options such as --disable-zts for the Non Thread Safe build]` to generate the makefile.  You can run `configure.bat --help` to see what other options are available.

4. Run `nmake`.  It is suggested that you run the entire build.  If you wish to do so, run `nmake clean` first.

5. To install the resulting build, run `nmake install` or just copy php_sqlsrv.dll to your PHP extension directory.

This software has been compiled and tested under PHP 7.0.2 using the Visual C++ 2015 compiler.

## Install

####Prerequisites

- A Web server such as Internet Information Services (IIS) is required. Your Web server must be configured to run PHP
- [Microsoft ODBC Driver 11][odbc]

####Enable the SQLSRV driver

1. Make sure that the driver is in your PHP extension directory (you can simply copy it there if you did not use nmake install).

2. Enable it within your PHP installation's php.ini: `extension=php_sqlsrv.dll`.  If necessary, specify the extension directory using extension_dir, for example: `extension_dir = "C:\PHP\ext"`

3. Restart the Web server.

## Sample Code
For samples, please see the sample folder.  For setup instructions, see [here] [phpazure]

## Limitations

This preview contains the 32-bit port for PHP 7 of the SQLSRV driver. The focus was on basic functionality. The following items are not supported:

- PDO
- Native 64 Bit
- Backwards compatibility with PHP 5
- Retrieving stream data and metadata
- Retrieving some varchar, nvarchar, ntext, binary, varbinary, uniqueidentifier, datetime, smalldatetime, and timestamp fields
- Handle UTF8 strings
- Retrieve strings as an output parameter
- Fetch a user defined object into a class

And some aspects of the following items need improvement:
- Memory management
- Logging and error handling


## Future Plans

- PDO Support
- Linux Version
- Expand SQL 16 Feature Support (example: Always Encrypted)
- Build Verification/Fundamental Tests
- Bug Fixes

##FAQs
**Q:** Can we get dates for any of the Future Plans listed above?

**A:** At this time, Microsoft is not able to announce dates. We are working extremely hard to release future versions of the driver. We will share future plans once they solidify over the next few weeks. 

**Q:** What's next?

**A:** On Jan 29, 2016, we have released the early technical preview for our PHP Driver. Our next step will be a Community Tech Preview with completed functionally, PDO support, and more.

**Q:** Is Microsoft taking pull requests for this project?

**A:** We will not be seeking to take pull requests until GA, Build Verification, and Fundamental tests are released. At this point Microsoft will also begin actively developing using this GitHub project as the prime repository.



## License

The Microsoft Drivers for PHP for SQL Server are licensed under the MIT license.  See the LICENSE file for more details.

## Resources

**Documentation**: [MSDN Online Documentation][phpdoc].  Please note that this documentation is not yet updated for PHP 7.

**Team Blog**: Browse our blog for comments and announcements from the team in the [team blog][blog].

**Known Issues**: Please visit the [project on Github][project] to view outstanding [issues][issues].

[blog]: http://blogs.msdn.com/b/sqlphp/

[project]: https://github.com/Azure/msphpsql

[issues]: https://github.com/Azure/msphpsql/issues

[phpweb]: http://php.net

[phpbuild]: https://wiki.php.net/internals/windows/stepbystepbuild

[phpdoc]: http://msdn.microsoft.com/en-us/library/dd903047%28SQL.11%29.aspx

[odbc]: https://www.microsoft.com/en-us/download/details.aspx?id=36434

[phpazure]: https://azure.microsoft.com/en-us/documentation/articles/sql-database-develop-php-simple-windows/



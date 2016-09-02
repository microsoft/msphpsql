# Microsoft Drivers for PHP for SQL Server

**Welcome to the Microsoft Drivers for PHP for SQL Server PHP 7**

**Note:** For the PHP 5 project, see the PHP 5 branch.

The Microsoft Drivers for PHP for SQL Server are PHP extensions that allow for the reading and writing of SQL Server data from within PHP scripts. The SQLSRV extension provides a procedural interface while the PDO_SQLSRV extension implements PDO for accessing data in all editions of SQL Server 2005 and later (including Azure SQL DB). These drivers rely on the Microsoft ODBC Driver for SQL Server to handle the low-level communication with SQL Server.

This release contains the SQLSRV and PDO_SQLSRV drivers for PHP 7 with improvements on both drivers and some limitations (see Limitations below for details).  Upcoming release(s) will contain more functionality, bug fixes, and more (see Plans below for more details).

The Microsoft Drivers for PHP for SQL Server Team

##Announcements

**August 22, 2016** (4.1.1): Updated Windows drivers built and compiled with PHP 7.0.9 are available and include a couple of bug fixes:

- Fixed issue with storing integers in varchar field.
- Fixed issue with invalid connection handler if one connection fails.
- Fixed crash when emulate prepare is on.

**July 28, 2016** (4.1.0): Thanks to the community's input, this release expands drivers functionalities and also includes some bug fixes:

 - `SQLSRV_ATTR_FETCHES_NUMERIC_TYPE`  connection attribute flag is added to PDO_SQLSRV driver to handle numeric fetches from columns with numeric Sql types (only bit, integer, smallint, tinyint, float and real). This flag can be turned on by setting its value in  `PDO::setAttribute` to `true`, For example,
               `$conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE,true);` 
		  If `SQLSRV_ATTR_FETCHES_NUMERIC_TYPE`  is set to `true` the results from an integer column will be represented as an `int`, likewise, Sql types float and real will be represented as `float`. 
		  Note for exceptions:
	 - When connection option flag `ATTR_STRINGIFY_FETCHES` is on, even when `SQLSRV_ATTR_FETCHES_NUMERIC_TYPE` is on, the return value will still be string.
	 - 	When the returned PDO type in bind column is `PDO_PARAM_INT`, the return value from a integer column will be int even if `SQLSRV_ATTR_FETCHES_NUMERIC_TYPE` is off.
 - Fixed float truncation when using buffered query. 
 - Fixed handling of Unicode strings and binary when emulate prepare is on in `PDOStatement::bindParam`.  To bind a unicode string, `PDO::SQLSRV_ENCODING_UTF8` should be set using `$driverOption`, and to bind a string to column of Sql type binary, `PDO::SQLSRV_ENCODING_BINARY` should be set.
 - Fixed string truncation in bind output parameters when the size is not set and the length of initialized variable is less than the output.
 - Fixed bind string parameters as bidirectional parameters (`PDO::PARAM_INPUT_OUTPUT `) in PDO_SQLSRV driver. Note for output or bidirectional parameters, `PDOStatement::closeCursor` should be called to get the output value.

**July 06, 2016**: PHP Driver 4.0 for SQL Server with PHP 7 support is now GA. You can get the binaries [HERE](https://github.com/Azure/msphpsql/releases) or download the exe from the [Microsoft Download Center](https://www.microsoft.com/en-us/download/details.aspx?id=20098).
 
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

## Future Plans
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

**A:** On Jan 29, 2016 we released an early technical preview for our PHP Driver and several since. We will continue to release frequently to improve the quality of our driver.

**Q:** Is Microsoft taking pull requests for this project?

**A:** We will not be seeking to take pull requests until GA, Build Verification, and Fundamental tests are released. At this point Microsoft will also begin actively developing using this GitHub project as the prime repository.



## License

The Microsoft Drivers for PHP for SQL Server are licensed under the MIT license.  See the LICENSE file for more details.

## Resources

**Documentation**: [MSDN Online Documentation][phpdoc]. 

**Team Blog**: Browse our blog for comments and announcements from the team in the [team blog][blog].

**Known Issues**: Please visit the [project on Github][project] to view outstanding [issues][issues] and report new ones.

[blog]: http://blogs.msdn.com/b/sqlphp/

[project]: https://github.com/Azure/msphpsql

[issues]: https://github.com/Azure/msphpsql/issues

[phpweb]: http://php.net

[phpbuild]: https://wiki.php.net/internals/windows/stepbystepbuild

[phpdoc]: http://msdn.microsoft.com/en-us/library/dd903047%28SQL.11%29.aspx

[odbc11]: https://www.microsoft.com/en-us/download/details.aspx?id=36434

[odbc13]: https://www.microsoft.com/en-us/download/details.aspx?id=50420

[phpazure]: https://azure.microsoft.com/en-us/documentation/articles/sql-database-develop-php-simple-windows/

This project has adopted the [Microsoft Open Source Code of Conduct](https://opensource.microsoft.com/codeofconduct/). For more information see the [Code of Conduct FAQ](https://opensource.microsoft.com/codeofconduct/faq/) or contact [opencode@microsoft.com](mailto:opencode@microsoft.com) with any additional questions or comments.
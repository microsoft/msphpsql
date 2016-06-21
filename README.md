# Microsoft Drivers for PHP for SQL Server

**Welcome to the Microsoft Drivers for PHP for SQL Server PHP 7 Linux (Early Technical Preview)**

The Microsoft Drivers for PHP for SQL Server are PHP extensions that allow for the reading and writing of SQL Server data from within PHP scripts. The SQLSRV extension provides a procedural interface while the PDO_SQLSRV extension implements PDO for accessing data in all editions of SQL Server 2005 and later (including Azure SQL DB). These drivers rely on the Microsoft ODBC Driver for SQL Server to handle the low-level communication with SQL Server.

This preview contains the SQLSRV and PDO_SQLSRV drivers for PHP 7 (64-bit) with limitations (see Limitations below for details).  Upcoming release(s) will contain more functionality, bug fixes, and more.

The Microsoft Drivers for PHP for SQL Server Team

##Announcements

June 20, 2016 (4.0.0): The early technical preview (ETP) for SQLSRV and PDO_SQLSRV drivers for Linux with basic functionalities is now available. The SQLSRV driver has been built and tested on Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2, and PDO_SQLSRV driver has been built and tested on Ubuntu 15.04, Ubuntu 16.04.

####Prerequisites

- A Web server such as Apache is required. Your Web server must be configured to run PHP
- Microsoft ODBC Driver 13 for Linux.[odbcLinux]
- The unixODBC 2.3.1 driver manager.

## Install

The drivers are distributed as shared binary extensions for PHP. You must download the PHP 7 source and then unzip to a local directory. Then follow the steps below:

1. Switch to the PHP directory and run ./buildconf --force.

2. Compile PHP with at least the LIBS=-lodbc, and for the PDO_SQLSRV driver with --enable-pdo=shared options given to ./configure. In addition, you may need to indicate the path to the include files for unixODBC using --with-unixODBC=/usr. Thus, your ./configure command should look like ./configure --enable-pdo=shared --with-unixODBC=/usr LIBS=-lodbc.
	Note: Since PDO must be compiled as a shared extension, any other PDO extensions must be compiled as shared extensions by adding --with-pdo-<name_of_extension>=shared to the ./configure command.

3. Run 'make' and then put the precompiled binaries into the <php_source_directory>/modules/ directory.

4. Run 'make install' to install the binaries into the default php extensions directory.

####Enable the drivers

1. Make sure that the driver is in your PHP extensions directory.

2. Enable it within your PHP installation's php.ini: Add `extension=php_sqlsrv_7_ts.so`, and if using the PDO_SQLSRV driver add `extension=pdo.so & extension=php_pdo_sqlsrv_7_ts.so`. If necessary, specify the extension directory using extension_dir, for example: `extension_dir = "/usr/local/bin".

3. Restart the Web server.

## Sample Code
For samples, please see the sample folder.  For setup instructions, see [here] [phpazure]

## Limitations

This preview contains the PHP 7 port of the SQLSRV and PDO_SQLSRV drivers, and does not provide backwards compatibility with PHP 5. The following items have known issues:

- Parameterized queries.
- Stream operations.
- Number-to-string and string-to-number localization is not supported.
- Logging.
- Local encodings other than UTF-8 are not supported for output.
- Integrated authentication is not supported.

SQLSRV:
- sqlsrv_rows_affected returns an empty string when the number of rows is 0.

PDO_SQLSRV:
- Buffered cursors are not supported.
- lastInsertId().
- ODBC 3.52 is supported but not 3.8.


## Guidelines for Reporting Issues
We appreciate you taking the time to test the driver, provide feedback and report any issues.  It would be extremely helpful if you:

- Report each issue as a new issue (but check first if it's already been reported)
- Try to be detailed in your report. Useful information for good bug reports include:
  * What you are seeing and what the expected behaviour is
  * Which driver: SQLSRV or PDO_SQLSRV?
  * Environment details: e.g. PHP version, thread safe (TS) or non-thread safe (NTS)?
  * Table schema (for some issues the data types make a big difference!)
  * Any other relevant information you want to share
- Try to include a PHP script demonstrating the isolated problem.

Thank you!

## FAQs
**Q:** Can we get dates for any of the Future Plans listed above?

**A:** At this time, Microsoft is not able to announce dates. We are working extremely hard to release future versions of the driver. We will share future plans once they solidify over the next few weeks. 

**Q:** What's next?

**A:** On June 20, 2016 we released the early technical preview for our PHP Driver. We will continue releasing frequent technical previews until we reach production quality.

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

[odbc11]: https://www.microsoft.com/en-us/download/details.aspx?id=36434

[odbc13]: https://www.microsoft.com/en-us/download/details.aspx?id=50420

[odbcLinux]: https://msdn.microsoft.com/en-us/library/hh568454(v=sql.110).aspx

[phpazure]: https://azure.microsoft.com/en-us/documentation/articles/sql-database-develop-php-simple-windows/

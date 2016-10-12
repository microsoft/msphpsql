# Microsoft Drivers for PHP for SQL Server

**Welcome to the Microsoft Drivers for PHP for SQL Server PHP 7 Linux (Early Technical Preview)**

The Microsoft Drivers for PHP for SQL Server are PHP extensions that allow for the reading and writing of SQL Server data from within PHP scripts. The SQLSRV extension provides a procedural interface while the PDO_SQLSRV extension implements PDO for accessing data in all editions of SQL Server 2005 and later (including Azure SQL DB). These drivers rely on the Microsoft ODBC Driver for SQL Server to handle the low-level communication with SQL Server.

This preview contains the SQLSRV and PDO_SQLSRV drivers for PHP 7 (64-bit) with limitations (see Limitations below for details).  Upcoming release(s) will contain more functionality, bug fixes, and more.

SQL Server Team

##Announcements

**October 4, 2016**: We are excited to announce that PECL packages for Linux SQLSRV and PDO_SQLSRV drivers (4.0.5) are available. You can also find pre-compiled binaries (4.0.5) with PHP 7.0.11  for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2 [here](https://github.com/Microsoft/msphpsql/releases). This release includes the following fixes:

- Fixed segmentation fault when calling PDOStatement::getColumnMeta on RedHat 7.2.
- Fixed segmentation fault when fetch mode is set to ATTR_EMULATE_PREPARES on RedHat 7.2.
- Fixed [issue #139](https://github.com/Microsoft/msphpsql/issues/139) : sqlsrv_fetch_object calls custom class constructor in static context and outputs an error.

**September 9, 2016**: Linux drivers (4.0.4) compiled with PHP 7.0.10 are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. This release includes the following fixes:

- Fixed  undefined symbols at SQL* error when loading the drivers.
- Fixed undefined symbol issues at LocalAlloc and LocalFree on RedHat7.2.
- Fixed [issue #144](https://github.com/Microsoft/msphpsql/issues/144) (floating point exception).
- Fixed [issue #119](https://github.com/Microsoft/msphpsql/issues/119) (modifying class name in sqlsrv_fetch_object).
- Added Support for EMULATE_PREPARE feature.
- Added following integer SQL Types constants for cases which function-like SQL types constants cannot be used e.g. type comparison:

    SQLSRV constant | Typical SQL Server data type | SQL type identifier
    ------------ | ----------------------- | ----------------------
   SQLSRV_SQLTYPE_DECIMAL | decimal       | SQL_DECIMAL
   SQLSRV_SQLTYPE_NUMERIC | numeric       | SQL_NUMERIC
   SQLSRV_SQLTYPE_CHAR    | char          | SQL_CHAR
   SQLSRV_SQLTYPE_NCHAR   | nchar         | SQL_WCHAR
   SQLSRV_SQLTYPE_VARCHAR | varchar       | SQL_VARCHAR
   SQLSRV_SQLTYPE_NVARCHAR | nvarchar     | SQL_WVARCHAR
   SQLSRV_SQLTYPE_BINARY   | binary       | SQL_BINARY
   SQLSRV_SQLTYPE_VARBINARY  | varbinary   | SQL_VARBINARY

    Note: These constants should be used in type comparison operations (refer to issue [#87](https://github.com/Microsoft/msphpsql/issues/87) and [#99](https://github.com/Microsoft/msphpsql/issues/99) ), and don't replace the function like constants with similar syntax. For binding parameters you should use the function-like constants, otherwise you'll get an error.



**August 23, 2016** : Linux drivers (4.0.3) compiled with PHP 7.0.9 are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. The source code of the drivers has also been made available, we recommend building drivers from source using the tutorial [here](https://github.com/Azure/msphpsql/blob/PHP-7.0-Linux/LinuxTutorial.md). This release includes following bug fixes:

- Fixed data corruption in binding integer parameters.
- Fixed invalid sql_display_size error.
- Fixed issue with invalid statement options.
- Fixed binding bit parameters.


**July 29, 2016**: Updated Linux drivers  (4.0.2) are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. This update provides the following improvements and bug fixes:

- The PDO_SQLSRV driver no longer requires PDO to be built as a shared extension.
- Fixed an issue with format specifiers in error messages.
- Fixed a segmentation fault when using buffered cursors.
- Fixed an issue whereby calling sqlsrv_rows_affected on an empty result set would return a null result instead of 0.
- Fixed an issue with error messages when there is an error in sizes in SQLSRV_SQLTYPE_*.

**July 11, 2016**: Thread safe and non-thread safe variations for SQLSRV and PDO_SQLSRV for Linux drivers (4.0.1) with basic functionalities are now available. The drivers have been built and tested on Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2.. Also, there are some improvements on the drivers that we would like to share:

- Improved handling varchar(MAX).
- Improved handling basic stream operations.

June 20, 2016 (4.0.0): The early technical preview (ETP) for SQLSRV and PDO_SQLSRV drivers for Linux with basic functionalities is now available. The SQLSRV driver has been built and tested on Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2, and PDO_SQLSRV driver has been built and tested on Ubuntu 15.04, Ubuntu 16.04.


## Install

####Prerequisites

- A Web server such as Apache is required. Your Web server must be configured to run PHP. See below for information on installing Apache to work with the drivers.
- [Microsoft ODBC Driver 13 for Linux][odbcLinux], you can install ODBC drivers using [ODBC command line installers][ODBCinstallers], or [ODBC install scripts](https://github.com/Microsoft/msphpsql/tree/PHP-7.0-Linux/ODBC%20install%20scripts).
- 64-bit [UnixODBC 2.3.1 driver manager][LinuxDM], built for 64-bit SQLLEN/SQLULEN.
- If building PHP from source, you need libraries required to [build PHP][PHPMan].

####Tutorial
- Follow the steps on [Microsoft Drivers for PHP for SQL Server Team Blog](https://blogs.msdn.microsoft.com/sqlphp/2016/10/10/getting-started-with-php-7-sql-server-and-azure-sql-database-on-linux-ubuntu-with-apache/) to easily configure your environment and install PHP drivers. For detailed instructions please review the tutorial [here](https://github.com/Azure/msphpsql/blob/PHP-7.0-Linux/LinuxTutorial.md). 


The drivers are distributed as shared binary extensions for PHP. They are available in thread safe (*_ts.so) and-non thread safe (*_nts.so) versions. The source code for the drivers is also available, and you can choose whether to compile them as thread safe or non-thread safe versions. The thread safety configuration of your web server will determine which version you need. If you wish to install Apache from source, follow these instructions:


1. Download the source from [Apache.org][httpd_source]. Unzip the source to a local directory. 

2. Download the [Apache Portable Runtime (APR) and Utility][apr_source]. Unzip the APR source into srclib/apr and the APR-Util source into srclib/apr-util in your Apache directory from Step 1.

3. If you have the thread safe binaries, run `./configure --enable-so --with-mpm=worker`. If you have the non-thread safe binaries, run `./configure --enable-so --with-mpm=prefork`.

4. Run `make` and `sudo make install`.

Install [Microsoft ODBC Driver 13 on Linux][odbcLinux] and Driver manager using the instructions on MSDN. Run `sqlcmd -S <myserver> -d <mydatabase> -U <myusername> -P <mypassword> -I` to make sure ODBC driver and driver manager have been installed successfully.

Now you are ready to install PHP.

- Method 1: Using your package  manager:

	Make sure the packaged  PHP version is PHP 7. You may need to add repositories to obtain PHP 7 as described in the [tutorial]((https://github.com/Azure/msphpsql/blob/PHP-7.0-Linux/LinuxTutorial.md)).
    
	1. Use your package manager to install the php package and if you are installing PDO_SQLSRV drivers install the php-pdo package.
	2. Copy the precompiled binaries into the extensions directory (likely in /usr/lib/php).
	3. Edit the php.ini file as indicated  in the "Enable the drivers" section.

- Method 2: Using the PHP source:

	Download the PHP 7 source and unzip it to a local directory. Then follow the steps below:

    1. Switch to the PHP directory and run `./buildconf --force`. You may need to install autoconf with your package manager prior to this step.

    2. Run `./configure` with the following options on the command line:
      
       (i) the path to apxs or apxs2 to configure PHP for Apache using `--with-apxs2=<path-to-apxs>`. To find the path to apxs (or apxs2), run `sudo find / -name apxs` (or `sudo find / -name apxs2`) and add the resulting path to the option. For example, if the find command yields `/usr/bin/apxs2`, add `--with-apxs2=/usr/bin/apxs2` to the `./configure` command line. 
       (ii) if your web server has thread safety enabled, specify a thread-safe build of PHP. Add `--enable-maintainer-zts` to `./configure`. 

      Thus your `./configure` command should look like `./configure --with-apxs2=<path-to-apxs-executable> --enable-maintainer-zts`.

      If your `./configure` command exits with an error saying it cannot find xml2-config, you may need to install libxml2-dev using your package manager before continuing.

	3. Run `make` and then put the precompiled binaries into the < php_source_directory >/modules/ directory.

	4. Run `make install` to install the binaries into the default php extensions directory.

- Method 3: Compile the drivers from source along with PHP:

	Download the PHP 7 source and unzip it to a local directory. Then follow the steps below:

    1. Switch to the PHP directory and unzip the Linux driver sources to the `ext/` directory. There are two directories, `sqlsrv/` and `pdo_sqlsrv/`. Run `./buildconf --force`. You may need to install autoconf with your package-manager prior to this step.

    2. Run `./configure` with the same options listed above, in addition to the following:
    
      (i) `CXXFLAGS=-std=c++11` to ensure your compiler uses the C++11 standard. 
      (ii) `--enable-sqlsrv=shared `
      (iii)`--with-pdo_sqlsrv=shared` 


      Thus your `./configure` command should look like `./configure --with-apxs2=<path-to-apxs-executable> CXXFLAGS=-std=c++11 --enable-sqlsrv=shared --with-pdo_sqlsrv=shared`. 

   3. Run `make`. The compiled drivers will be located in the `modules/` directory, and are named `sqlsrv.so` and `pdo_sqlsrv.so`.       

   4. Run `sudo make install` to install the binaries into the default php extensions directory.

- Method 4: Compile the drivers from source with phpize:

	Make sure that [phpize](http://php.net/manual/en/install.pecl.phpize.php) is properly installed.

    1. Switch to the source directory of the extension you want to install, e.g. `source/sqlsrv` or `source/pdo_sqlsrv`.

    2. Run `phpize && ./configure CXXFLAGS=-std=c++11 && make` to compile the extension.

    3. Run `sudo make install` to install the binaries into the default php extensions directory.
    
- Method 5: install the drivers with PECL:

1. Use your package manager to install the php package and if you are installing PDO_SQLSRV drivers install the php-pdo package. Make sure the packaged  PHP version is PHP 7. You may need to add repositories to obtain PHP 7 as described in the [tutorial]((https://github.com/Azure/msphpsql/blob/PHP-7.0-Linux/LinuxTutorial.md)).

2. Use your package manager and install `php-pear` and  `php-dev`.

3.   Run `pecl search sqlsrv`, you should get the list of sqlsrv and pdo_sqlsrv packages with their associated version. For example, `pdo_sqlsrv 4.0.5 (devel)   4.0.5 Microsoft Drivers for PHP for SQL Server (PDO_SQLSRV)`.

 4. Run `sudo pecl install pdo_sqlsrv-4.0.5` .
 
####Enable the drivers

1. Make sure that the driver is in your PHP extensions directory.

2. Enable it within your PHP installation's php.ini: In your local PHP directory, copy `php.ini-development` to `php.ini`. If using the SQLSRV driver, add `extension=php_sqlsrv_7_ts.so` or `extension=php_sqlsrv_7_nts.so` to `php.ini`. If using the PDO_SQLSRV driver, add `extension=php_pdo_sqlsrv_7_ts.so` or `extension=php_pdo_sqlsrv_7_nts.so`.  Modify these filenames as appropriate if you compiled the drivers from source. If necessary, specify the extension directory using `extension_dir`, for example: `extension_dir = /usr/local/bin`.

3. If using Apache web server, follow the [instructions here][httpdconf] for editing your Apache configuration file.

4. Restart the web server.

## Sample Code
For samples, please see the sample folder.  For setup instructions, see [here] [phpazure]

## Limitations

- This preview contains the PHP 7 port of the SQLSRV and PDO_SQLSRV drivers, and does not provide backwards compatibility with PHP 5. 
- Binding output parameter using emulate prepare is not supported.
- ODBC 3.52 is supported but not 3.8.

##Known issues

The following items have known issues:
- Retrieving, inserting, and binding Char, Varchar datatypes.
- Logging.
- Local encodings other than UTF-8 are not supported for output.
- Integrated authentication is not supported.
- Buffered cursors are not supported.
- Fetch object and fetch array have issues.
- Query from large column name.
- Binary column binding with emulate prepare ([issue#140](https://github.com/Microsoft/msphpsql/issues/140))



## Guidelines for Reporting Issues
We appreciate you taking the time to test the driver, provide feedback and report any issues.  It would be extremely helpful if you:

- Report each issue as a new issue (but check first if it's already been reported)
- Try to be detailed in your report. Useful information for good bug reports include:
  * What you are seeing and what the expected behaviour is
  * Can you connect to SQL Server via `sqlcmd`? 
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

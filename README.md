# Microsoft Drivers for PHP for SQL Server

**Welcome to the Microsoft Drivers for PHP for SQL Server PHP 7 Linux (Early Technical Preview)**

The Microsoft Drivers for PHP for SQL Server are PHP extensions that allow for the reading and writing of SQL Server data from within PHP scripts. The SQLSRV extension provides a procedural interface while the PDO_SQLSRV extension implements PDO for accessing data in all editions of SQL Server 2005 and later (including Azure SQL DB). These drivers rely on the Microsoft ODBC Driver for SQL Server to handle the low-level communication with SQL Server.

This preview contains the SQLSRV and PDO_SQLSRV drivers for PHP 7 (64-bit) with limitations (see Limitations below for details).  Upcoming release(s) will contain more functionality, bug fixes, and more.

SQL Server Team

##Announcements

August 23, 2016 (4.0.3): Linux drivers built with PHP 7.0.9 are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. The source code of the drivers has also been made available, we recommend building drivers from source using the tutorial [here](https://github.com/Azure/msphpsql/blob/PHP-7.0-Linux/LinuxTutorial.md). This release includes following bug fixes:

- Fixed data corruption in binding integer parameters.
- Fixed invalid sql_display_size error.
- Fixed issue with invalid statement options.
- Fixed binding bit parameters.


July 29, 2016 (4.0.2): Updated Linux drivers are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. This update provides the following improvements and bug fixes:

- The PDO_SQLSRV driver no longer requires PDO to be built as a shared extension.
- Fixed an issue with format specifiers in error messages.
- Fixed a segmentation fault when using buffered cursors.
- Fixed an issue whereby calling sqlsrv_rows_affected on an empty result set would return a null result instead of 0.
- Fixed an issue with error messages when there is an error in sizes in SQLSRV_SQLTYPE_*.

July 11, 2016 (4.0.1): Thread safe and non-thread safe variations for SQLSRV and PDO_SQLSRV for Linux drivers with basic functionalities are now available. The drivers have been built and tested on Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2.. Also, there are some improvements on the drivers that we would like to share:

- Improved handling varchar(MAX).
- Improved handling basic stream operations.

June 20, 2016 (4.0.0): The early technical preview (ETP) for SQLSRV and PDO_SQLSRV drivers for Linux with basic functionalities is now available. The SQLSRV driver has been built and tested on Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2, and PDO_SQLSRV driver has been built and tested on Ubuntu 15.04, Ubuntu 16.04.

####Prerequisites

- A Web server such as Apache is required. Your Web server must be configured to run PHP. See below for information on installing Apache to work with the drivers.
- [Microsoft ODBC Driver 13 for Linux][odbcLinux]
- 64-bit [UnixODBC 2.3.1 driver manager][LinuxDM], built for 64-bit SQLLEN/SQLULEN.
- If building PHP from source, you need libraries required to [build PHP][PHPMan].

## Install

For detailed instructions please review the tutorial [here](https://github.com/Azure/msphpsql/blob/PHP-7.0-Linux/LinuxTutorial.md). 
The drivers are distributed as shared binary extensions for PHP. They are available in thread safe (*_ts.so) and-non thread safe (*_nts.so) versions. The source code for the drivers is also available, and you can choose whether to compile them as thread safe or non-thread safe versions. The thread safety configuration of your web server will determine which version you need. If you wish to install Apache from source, follow these instructions:


1. Download the source from [Apache.org][httpd_source]. Unzip the source to a local directory. 
2. Download the [Apache Portable Runtime (APR) and Utility][apr_source]. Unzip the APR source into srclib/apr and the APR-Util source into srclib/apr-util in your Apache directory from Step 1.

3. If you have the thread safe binaries, run `./configure --enable-so --with-mpm=worker`. If you have the non-thread safe binaries, run `./configure --enable-so --with-mpm=prefork`.

4. Run `make` and `sudo make install`.

Install [Microsoft ODBC Driver 13 on Linux][odbcLinux] and Driver manager using the instructions on MSDN. Run `sqlcmd -S <myserver> -d <mydatabase> -U <myusername> -P <mypassword> -I` to make sure ODBC driver and driver manager have been installed successfully.

Now you are ready to install PHP.

- Method 1: Using your package  manager:

	Make sure the packaged  PHP version is PHP 7. *Note*: We do not recommend this method, as php-odbc may introduce conflicts with the unixODBC driver manager that you have already installed. 
    
	1. Use your package manager to install php and php-odbc.
	2. Copy the precompiled binaries into the extensions directory (likely in /usr/lib/php).
	3. Edit the php.ini file as indicated  in "Enable the drivers" section.

- Method 2: Using the PHP source:

	Download the PHP 7 source and unzip it to a local directory. Then follow the steps below:

    1. Switch to the PHP directory and run `./buildconf --force`. You may need to install autoconf with your package-manager prior to this step.

    2. Run `./configure` with the following options on the command line:
      
      (i) `LIBS=-lodbc` 
(ii) the path for the unixODBC header files using `--with-unixODBC=<path-to-ODBC-headers>`. To find the path for the header files, use the command `sudo find / -name sql.h`. Then add this path, without the /include/sql.h, to the command line. For example, if the find command yields /usr/include/sql.h, add `--with-unixODBC=/usr` to the `./configure` command line. 
(iii) the path to apxs or apxs2 to configure PHP for Apache using `--with-apxs2=<path-to-apxs>`. To find the path to apxs (or apxs2), run `sudo find / -name apxs` (or `sudo find / -name apxs2`) and add the resulting path to the option.
(iv) if your web server has thread safety enabled, a thread-safe build of PHP. Add `--enable-maintainer-zts` to `./configure`. 

      Thus your `./configure` command should look like `./configure LIBS=-lodbc --with-unixODBC=<path-to-ODBC-headers> --with-apxs2=<path-to-apxs-executable> --enable-maintainer-zts`.

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
(iv)`--with-odbcver=0x0380`. This option has no practical effect as the drivers use ODBC version 3.52, but it suppresses compilation warnings arising from the fact that PHP's default setting of version 3.00 is different from unixODBC's setting of 3.80. 

      Thus your `./configure` command should look like `./configure LIBS=-lodbc --with-unixODBC=<path-to-ODBC-headers> --with-apxs2=<path-to-apxs-executable> CXXFLAGS=-std=c++11 --enable-sqlsrv=shared --with-pdo_sqlsrv=shared --with-odbcver=0x0380`. 

   
   3. Run `make`. The compiled drivers will be located in the `modules/` directory, and are named `sqlsrv.so` and `pdo_sqlsrv.so`.       

   4. Run `sudo make install` to install the binaries into the default php extensions directory.

- Method 4: Compile the drivers from source with phpize:

	Make sure that [phpize](http://php.net/manual/en/install.pecl.phpize.php) is properly installed.

    1. Switch to the source directory of the extension you want to install, e.g. `source/sqlsrv` or `source/pdo_sqlsrv`.

    2. Run `phpize && ./configure CXXFLAGS=-std=c++11 && make` to compile the extension.

    3. Run `sudo make install` to install the binaries into the default php extensions directory.

####Enable the drivers

1. Make sure that the driver is in your PHP extensions directory.

2. Enable it within your PHP installation's php.ini: In your local PHP directory, copy `php.ini-development` to `php.ini`. If using the SQLSRV driver, add `extension=php_sqlsrv_7_ts.so` or `extension=php_sqlsrv_7_nts.so` to `php.ini`. If using the PDO_SQLSRV driver, add `extension=php_pdo_sqlsrv_7_ts.so` or `extension=php_pdo_sqlsrv_7_nts.so`.  Modify these filenames as appropriate if you compiled the drivers from source. If necessary, specify the extension directory using `extension_dir`, for example: `extension_dir = /usr/local/bin`.

3. If using Apache web server, follow the [instructions here][httpdconf] for editing your Apache configuration file.

4. Restart the web server.

## Sample Code
For samples, please see the sample folder.  For setup instructions, see [here] [phpazure]

## Limitations

- This preview contains the PHP 7 port of the SQLSRV and PDO_SQLSRV drivers, and does not provide backwards compatibility with PHP 5. 
- ODBC 3.52 is supported but not 3.8.

##Known issues

The following items have known issues:
- There are issues with loading the drivers, we recommend building drivers from source for all variations until we fix the issue.
- Retrieving, inserting, and binding Char, Varchar datatypes.
- Logging.
- Local encodings other than UTF-8 are not supported for output.
- Integrated authentication is not supported.
- Buffered cursors are not supported.
- Fetch object and fetch array have issues.
- lastInsertId().
- Query from large column name.
- ODBC 3.52 is supported but not 3.8.


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

# Microsoft Drivers for PHP for SQL Server

**Welcome to the Microsoft Drivers for PHP for SQL Server PHP 7 Linux (Early Technical Preview)**

The Microsoft Drivers for PHP for SQL Server are PHP extensions that allow for the reading and writing of SQL Server data from within PHP scripts. The SQLSRV extension provides a procedural interface while the PDO_SQLSRV extension implements PDO for accessing data in all editions of SQL Server 2005 and later (including Azure SQL DB). These drivers rely on the Microsoft ODBC Driver for SQL Server to handle the low-level communication with SQL Server.

This preview contains the SQLSRV and PDO_SQLSRV drivers for PHP 7 (64-bit) with limitations (see Limitations below for details).  Upcoming release(s) will contain more functionality, bug fixes, and more.

SQL Server Team

##Get Started

* [**Ubuntu + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php-ubuntu)
* [**RedHat + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php-rhel)
* [**Windows + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php-windows)

## Install

### Step 1: Install  PHP (unless already installed)

**Ubuntu 15.10**

	sudo apt-get install python-software-properties software-properties-common
	sudo LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php
	sudo apt-get install php7.0 php7.0-fpm php-pear php7.0-dev mcrypt php7.0-mcrypt php-mbstring apache2 libapache2-mod-php7.0
	
**Ubuntu 16.04**

	apt-get update
	sudo apt-get -y install php7.0 libapache2-mod-php7.0 mcrypt php7.0-mcrypt php-mbstring php-pear php7.0-dev apache2
**RedHat 7**

	wget https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
	wget http://rpms.remirepo.net/enterprise/remi-release-7.rpm
	rpm -Uvh remi-release-7.rpm epel-release-latest-7.noarch.rpm
	subscription-manager repos --enable=rhel-7-server-optional-rpms
	yum update
	yum install php70-php

**RedHat 6**

	wget https://dl.fedoraproject.org/pub/epel/epel-release-latest-6.noarch.rpm
	wget http://rpms.remirepo.net/enterprise/remi-release-6.rpm
	rpm -Uvh remi-release-6.rpm epel-release-latest-6.noarch.rpm
	rhn-channel --add --channel=rhel-$(uname -i)-server-optional-6
	yum update
	yum install php70-php



### Step 2: Install  pre-requisites


**Ubuntu 15.10**

	sudo su 
	curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
	curl https://packages.microsoft.com/config/ubuntu/15.10/prod.list > /etc/apt/sources.list.d/mssql-release.list
	exit
	sudo apt-get update
	sudo ACCEPT_EULA=Y apt-get install msodbcsql mssql-tools
	sudo apt-get install unixodbc-dev-utf16 #this step is optional but recommended*
	
**Ubuntu 16.04**

	sudo su 
	curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
	curl https://packages.microsoft.com/config/ubuntu/16.04/prod.list > /etc/apt/sources.list.d/mssql-release.list
	exit
	sudo apt-get update
	sudo ACCEPT_EULA=Y apt-get install msodbcsql mssql-tools 
	sudo apt-get install unixodbc-dev-utf16

**RedHat 6**

	sudo su
	curl https://packages.microsoft.com/config/rhel/6/prod.repo > /etc/yum.repos.d/mssql-release.repo
	exit
	sudo yum update
	sudo yum remove unixODBC #to avoid conflicts
	sudo ACCEPT_EULA=Y yum install msodbcsql mssql-tools 
	sudo yum install unixODBC-utf16-devel 

**RedHat 7**

	sudo su
	curl https://packages.microsoft.com/config/rhel/7/prod.repo > /etc/yum.repos.d/mssql-release.repo
	exit
	sudo yum update
	sudo yum remove unixODBC #to avoid conflicts
	sudo ACCEPT_EULA=Y yum install msodbcsql mssql-tools 
	sudo yum install unixODBC-utf16-devel 


*Note: On Ubuntu, you need to make sure you install PHP 7 before you proceed to step 2. The Microsoft PHP Drivers for SQL Server will only work for PHP 7+. You can install PHP following the instructions here.

### Step 2: Install Apache

**Ubuntu**

    sudo apt-get install libapache2-mod-php7.0 
    sudo apt-get install apache2
    
**RedHat** 

    sudo yum install httpd
    
### Step 3: Install the Microsoft PHP Drivers for SQL Server

    sudo pecl install sqlsrv-4.0.7
    sudo pecl install pdo_sqlsrv-4.0.7
    
    
### Step 4: Add the Microsoft PHP Drivers for SQL Server to php.ini

**Ubuntu**

    echo "extension=/usr/lib/php/20151012/sqlsrv.so" >> /etc/php/7.0/apache2/php.ini
    echo "extension=/usr/lib/php/20151012/pdo_sqlsrv.so" >> /etc/php/7.0/apache2/php.ini
    echo "extension=/usr/lib/php/20151012/sqlsrv.so" >> /etc/php/7.0/cli/php.ini
	echo "extension=/usr/lib/php/20151012/pdo_sqlsrv.so" >> /etc/php/7.0/cli/php.ini

**RedHat** 

    echo "extension=/usr/lib/php/20151012/sqlsrv.so" >> /etc/php.ini
    echo "extension=/usr/lib/php/20151012/pdo_sqlsrv.so" >> /etc/php.ini
    echo "extension=/usr/lib/php/20151012/sqlsrv.so" >> /etc/opt/remi/php70/php.ini
	echo "extension=/usr/lib/php/20151012/pdo_sqlsrv.so" >> /etc/opt/remi/php70/php.ini
	
### Step 5: Restart Apache to load the new php.ini file

**Ubuntu**

	sudo service apache2 restart

**RedHat**

	sudo apachectl restart 

### Step 6: Create your sample app
Navigate to /var/www/html and create a new file called testsql.php. Copy and paste the following code in tetsql.php and change the servername, username, password and databasename.

    <?php
    $serverName = "yourServername";
    $connectionOptions = array(
        "Database" => "yourPassword",
        "Uid" => "yourUsername",
        "PWD" => "yourPassword"
    );
    //Establishes the connection
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    //Select Query
    $tsql= "SELECT @@Version as SQL_VERSION";
    //Executes the query
    $getResults= sqlsrv_query($conn, $tsql);
    //Error handling
     
    if ($getResults == FALSE)
        die(FormatErrors(sqlsrv_errors()));
    ?> 
     <h1> Results : </h1>
     <?php
    while ($row = sqlsrv_fetch_array($getResults, SQLSRV_FETCH_ASSOC)) {
        echo ($row['SQL_VERSION']);
        echo ("<br/>");
    }
    sqlsrv_free_stmt($getResults);
    function FormatErrors( $errors )  
    {  
        /* Display errors. */  
        echo "Error information: <br/>";  
      
        foreach ( $errors as $error )  
        {  
            echo "SQLSTATE: ".$error['SQLSTATE']."<br/>";  
            echo "Code: ".$error['code']."<br/>";  
            echo "Message: ".$error['message']."<br/>";  
        }  
    }  
    ?>
    
### Step 7: Run your sample app

Go to your browser and type in http://localhost/testsql.php
You should be able to connect to your SQL Server/Azure SQL Database and see the following results


The drivers are distributed as shared binary extensions for PHP. They are available in thread safe (*_ts.so) and-non thread safe (*_nts.so) versions. The source code for the drivers is also available, and you can choose whether to compile them as thread safe or non-thread safe versions. The thread safety configuration of your web server will determine which version you need. If you wish to install Apache from source, follow these instructions:

##Announcements

**November 23, 2016**: Linux drivers (4.0.7) compiled with PHP 7.0.13 are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. Here is the list of updates and fixes:

 - Code structure is updated to facilitate the development; shared codes between both drivers are moved to "shared" folder to avoid code duplication issues in development. To build the driver from source, use "packagize" script as follows:
	 - if you are using the phpize, clone or download the “source”, run the script within the “source” directory and then run phpize.
	 - if you are building the driver from source using PHP source, give the path to the PHP source to the script. 
 - Fixed string truncation error when inserting long strings.
 - Fixed querying from large column name.
 - Fixed issue with trailing garbled characters in string retrieval.
 - Fixed issue with detecting invalid UTF-16 strings coming from server.
 - Fixed issues with binding input text, ntext, and image parameters.
 - Ported buffered cursor to Linux.


**October 25, 2016**: Linux drivers (4.0.6) compiled with PHP 7.0.12 are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. Here is the list of updates and fixes:

 - Drivers versioning has been redesigned as Major#.Minor#.Release#.Build#. Build number is specific to binaries and it doesn't match with the number on the source.
 - Fixed the issue with  duplicate warning messages in PDO_SQLSRV drivers when error mode is set to PDO::ERRMODE_WARNING.
 - Fixed the issue with invalid UTF-8 strings, those are detected before executing any queries and proper error message is returned. 
 - Fixed segmentation fault in sqlsrv_fetch_object and sqlsrv_fetch_array function.
 - Compiler C++ 11 is enabled in config file.



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


## Limitations

- This preview contains the PHP 7 port of the SQLSRV and PDO_SQLSRV drivers, and does not provide backwards compatibility with PHP 5. 
- Binding output parameter using emulate prepare is not supported.
- ODBC 3.52 is supported but not 3.8.
- Connection using named instances using '\' is not supported.
- Connection pooling in PDO_SQLSRV is not supported.
- Local encodings other than UTF-8 are not supported, and SQLSRV_ENC_CHAR only supports ASCII characters with ASCII code of 0 to 127.

## Known issues

The following items have known issues:
- Buffered result set only works with ASCII characters (0 - 127).
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

# Microsoft Drivers for PHP for SQL Server

**Welcome to the Microsoft Drivers for PHP for SQL Server PHP 7 Linux**

The Microsoft Drivers for PHP for SQL Server are PHP extensions that allow for the reading and writing of SQL Server data from within PHP scripts. The SQLSRV extension provides a procedural interface while the PDO_SQLSRV extension implements PDO for accessing data in all editions of SQL Server 2005 and later (including Azure SQL DB). These drivers rely on the Microsoft ODBC Driver for SQL Server to handle the low-level communication with SQL Server.

This project contains the SQLSRV and PDO_SQLSRV drivers for PHP 7 (64-bit) with limitations (see Limitations below for details).  Upcoming release(s) will contain more functionality, bug fixes, and more.

SQL Server Team
###Status of Most Recent Builds
 Travis CI (Linux) |        Coverage Status 
 --------------------------| ------------------
 [![tv-image][]][tv-site] |[![Coverage Status][]][coveralls-site]

[tv-image]:  https://travis-ci.org/Microsoft/msphpsql.svg?branch=PHP-7.0-Linux
[tv-site]: https://travis-ci.org/Microsoft/msphpsql/
[Coverage Status]: https://coveralls.io/repos/github/Microsoft/msphpsql/badge.svg?branch=PHP-7.0-Linux
[coveralls-site]: https://coveralls.io/github/Microsoft/msphpsql?branch=PHP-7.0-Linux

##Get Started

* [**Ubuntu + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php-ubuntu)
* [**RedHat + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php-rhel)
* [**Windows + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php-windows)

## Install
Following instructions shows how to install PHP 7.x, Microsoft ODBC driver, apache, and Microsoft PHP drivers on Ubuntu 15, 16 and RedHat 7. To see how to get PHP SQLSRV drivers running on Debian, please visit [Wiki](https://github.com/Microsoft/msphpsql/wiki/Dockerfile-for-getting-pdo_sqlsrv-for-PHP-7.0-on-Debian-in-3-ways). Note that Debian is not officially supported and this instruction hasn't been tested in our test lab.

### Step 1: Install  PHP (unless already installed)

#### PHP 7.0

**Ubuntu 15.04, Ubuntu 15.10**

	sudo su
	sh -c 'echo "deb http://packages.dotdeb.org jessie all \ndeb-src http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list'
	apt-get update
	apt-get install php7.0 php7.0-fpm php-pear php7.0-dev mcrypt php7.0-mcrypt php-mbstring php7.0-xml

	
**Ubuntu 16.04**

	sudo su
	apt-get update
	apt-get -y install php7.0 mcrypt php7.0-mcrypt php-mbstring php-pear php7.0-dev php7.0-xml

	
**RedHat 7**

	sudo su
	wget https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
	wget http://rpms.remirepo.net/enterprise/remi-release-7.rpm
	rpm -Uvh remi-release-7.rpm epel-release-latest-7.noarch.rpm
	subscription-manager repos --enable=rhel-7-server-optional-rpms
	yum-config-manager --enable remi-php70
	yum update
	yum install php php-pdo php-xml php-pear php-devel


**RedHat 6**

	sudo su
	wget https://dl.fedoraproject.org/pub/epel/epel-release-latest-6.noarch.rpm
	wget http://rpms.remirepo.net/enterprise/remi-release-6.rpm
	rpm -Uvh remi-release-6.rpm epel-release-latest-6.noarch.rpm
	rhn-channel --add --channel=rhel-$(uname -i)-server-optional-6
	yum-config-manager --enable remi-php70
	yum update
	yum install php php-pdo php-xml php-pear php-devel


#### PHP 7.1


**Ubuntu 16.04**

	sudo su
	add-apt-repository ppa:ondrej/php
	apt-get update
	apt-get -y install php7.1 mcrypt php7.1-mcrypt php-mbstring php-pear php7.1-dev 

**RedHat 7**
	
	sudo su
	wget https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
	wget http://rpms.remirepo.net/enterprise/remi-release-7.rpm
	rpm -Uvh remi-release-7.rpm epel-release-latest-7.noarch.rpm
	subscription-manager repos --enable=rhel-7-server-optional-rpms
	yum-config-manager --enable remi-php71
	yum update
	yum install php php-pdo php-xml php-pear php-devel
    
**RedHat 6**

	sudo su
	wget https://dl.fedoraproject.org/pub/epel/epel-release-latest-6.noarch.rpm
	wget http://rpms.remirepo.net/enterprise/remi-release-6.rpm
	rpm -Uvh remi-release-6.rpm epel-release-latest-6.noarch.rpm
	rhn-channel --add --channel=rhel-$(uname -i)-server-optional-6
	yum-config-manager --enable remi-php71
	yum update
	yum install php php-pdo php-xml php-pear php-devel
    


### Step 2: Install  pre-requisites

**Ubuntu 15.04**

    sudo su 
    sh -c 'echo "deb [arch=amd64] https://apt-mo.trafficmanager.net/repos/mssql-ubuntu-vivid-release/ vivid main" > /etc/apt/sources.list.d/mssqlpreview.list'
    sudo apt-key adv --keyserver apt-mo.trafficmanager.net --recv-keys 417A0893
    apt-get update
    apt-get install msodbcsql
    #for silent install use ACCEPT_EULA=Y apt-get install msodbcsql
    sudo apt-get install unixodbc-dev-utf16 
    
**Ubuntu 15.10**

	sudo su 
	curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
	curl https://packages.microsoft.com/config/ubuntu/15.10/prod.list > /etc/apt/sources.list.d/mssql-release.list
	exit
	sudo apt-get update
	sudo ACCEPT_EULA=Y apt-get install msodbcsql mssql-tools
	sudo apt-get install unixodbc-dev-utf16 

	
**Ubuntu 16.04**

	sudo su 
	curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
	curl https://packages.microsoft.com/config/ubuntu/16.04/prod.list > /etc/apt/sources.list.d/mssql-release.list
	exit
	sudo apt-get update
	sudo ACCEPT_EULA=Y apt-get install msodbcsql mssql-tools 
	sudo apt-get install unixodbc-dev-utf16

**RedHat 7**

	sudo su
	curl https://packages.microsoft.com/config/rhel/7/prod.repo > /etc/yum.repos.d/mssql-release.repo
	exit
	sudo yum update
	sudo yum remove unixODBC #to avoid conflicts
	sudo ACCEPT_EULA=Y yum install msodbcsql mssql-tools 
	sudo yum install unixODBC-utf16-devel 

**RedHat 6**

	sudo su
	curl https://packages.microsoft.com/config/rhel/6/prod.repo > /etc/yum.repos.d/mssql-release.repo
	exit
	sudo yum update
	sudo yum remove unixODBC #to avoid conflicts
	sudo ACCEPT_EULA=Y yum install msodbcsql mssql-tools 
	sudo yum install unixODBC-utf16-devel 


*Note: On Ubuntu, you need to make sure you install PHP 7 before you proceed to step 2. The Microsoft PHP Drivers for SQL Server will only work for PHP 7+.

### Step 3: Install Apache

####PHP 7.0

**Ubuntu**

    sudo apt-get install libapache2-mod-php7.0 
    sudo apt-get install apache2
    
**RedHat** 

    sudo yum install httpd

####PHP 7.1 

**Ubuntu**
	
	sudo apt-get install libapache2-mod-php7.1 
	sudo apt-get install apache2
    
**RedHat** 

    sudo yum install httpd 
    

### Step 4: Install the Microsoft PHP Drivers for SQL Server

    sudo pecl install sqlsrv
    sudo pecl install pdo_sqlsrv
    
*Note: it installs the stable version, for specific version you should set the version. For example, `sudo pecl install sqlsrv-4.0.8`

       
### Step 5: Add the Microsoft PHP Drivers for SQL Server to php.ini


####PHP 7.0

**Ubuntu**
	
	echo "extension=/usr/lib/php/20151012/sqlsrv.so" >> /etc/php/7.0/apache2/php.ini
	echo "extension=/usr/lib/php/20151012/pdo_sqlsrv.so" >> /etc/php/7.0/apache2/php.ini
	echo "extension=/usr/lib/php/20151012/sqlsrv.so" >> /etc/php/7.0/cli/php.ini
	echo "extension=/usr/lib/php/20151012/pdo_sqlsrv.so" >> /etc/php/7.0/cli/php.ini


**RedHat** 

	echo "extension= /usr/lib64/php/modules/sqlsrv.so" > /etc/php.d/sqlsrv.ini
	echo "extension= /usr/lib64/php/modules/pdo_sqlsrv.so" > /etc/php.d/pdo_sqlsrv.ini


####PHP 7.1


**Ubuntu 16.04**
	
	echo "extension=/usr/lib/php/20160303/sqlsrv.so" >> /etc/php/7.1/apache2/php.ini
	echo "extension=/usr/lib/php/20160303/pdo_sqlsrv.so" >> /etc/php/7.1/apache2/php.ini
	echo "extension=/usr/lib/php/20160303/sqlsrv.so" >> /etc/php/7.1/cli/php.ini
	echo "extension=/usr/lib/php/20160303/pdo_sqlsrv.so" >> /etc/php/7.1/cli/php.ini



**RedHat** 
	
	echo "extension= /usr/lib64/php/modules/sqlsrv.so" > /etc/php.d/sqlsrv.ini
	echo "extension= /usr/lib64/php/modules/pdo_sqlsrv.so" > /etc/php.d/pdo_sqlsrv.ini


	
### Step 6: Restart Apache to load the new php.ini file

**Ubuntu**

	sudo service apache2 restart

**RedHat**

	sudo apachectl restart 

### Step 7: Create your sample app
Navigate to `/var/www/html` and create a new file called testsql.php. Copy and paste the following code in tetsql.php and change the servername, username, password and databasename.

    <?php
    $serverName = "yourServername";
    $connectionOptions = array(
        "Database" => "yourDatabase",
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

### Step 8: Run your sample app

Go to your browser and type in http://localhost/testsql.php
You should be able to connect to your SQL Server/Azure SQL Database.


The drivers are distributed as shared binary extensions for PHP. They are available in thread safe (*_ts.so) and-non thread safe (*_nts.so) versions. The source code for the drivers is also available, and you can choose whether to compile them as thread safe or non-thread safe versions. The thread safety configuration of your web server will determine which version you need. 

##Announcements

**December 19, 2016**: We are delighted announce that production release for PHP Linux Driver for SQL Server is available. PECL packages (4.0.8) are updated with the latest changes, and Linux binaries (4.0.8) compiled with PHP 7.0.14 are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. For complete list of changes please visit [CHANGELOG](https://github.com/Microsoft/msphpsql/blob/PHP-7.0-Linux/CHANGELOG.md) file.

 Please visit the [blog][blog] for more announcements.

## Limitations

- This preview contains the PHP 7 port of the SQLSRV and PDO_SQLSRV drivers, and does not provide backwards compatibility with PHP 5. 
- Binding output parameter using emulate prepare is not supported.
- ODBC 3.52 is supported but not 3.8.
- Connection using named instances using '\' is not supported.
- Local encodings other than UTF-8 are not supported, and SQLSRV_ENC_CHAR only supports ASCII characters with ASCII code of 0 to 127.

## Known issues

The following items have known issues:
- Connection pooling in PDO_SQLSRV is not supported.
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

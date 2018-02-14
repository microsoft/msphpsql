# Microsoft Drivers for PHP for SQL Server

**Welcome to the Microsoft Drivers for PHP for SQL Server PHP 7**

The Microsoft Drivers for PHP for SQL Server are PHP extensions that allow for the reading and writing of SQL Server data from within PHP scripts. The SQLSRV extension provides a procedural interface while the PDO_SQLSRV extension implements PDO for accessing data in all editions of SQL Server 2008 R2 and later (including Azure SQL DB). These drivers rely on the Microsoft ODBC Driver for SQL Server to handle the low-level communication with SQL Server.

This release contains the SQLSRV and PDO_SQLSRV drivers for PHP 7.0.* or above with improvements on both drivers and some limitations (see Limitations below for details).  Upcoming release(s) will contain more functionality, bug fixes, and more (see Plans below for more details).

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

* [**Ubuntu + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php/ubuntu)
* [**RedHat + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php/rhel)
* [**SUSE + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php/sles)
* [**Windows + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php/windows)
* [**macOS + SQL Server + PHP 7**](https://www.microsoft.com/en-us/sql-server/developer-get-started/php/mac/)
* [**Docker**](https://hub.docker.com/r/lbosqmsft/mssql-php-msphpsql/)


## Announcements

 Please visit the [blog][blog] for more announcements.


## Build (Windows)

Note: if you prefer, you can use the pre-compiled binaries found in the [releases](https://github.com/Microsoft/msphpsql/releases)

#### Prerequisites

You must first be able to build PHP 7.0.* or above without including these extensions.  For help with doing this, see the [official PHP website][phpbuild] for building your own PHP in Windows.

#### Compile the drivers

The Microsoft Drivers for PHP for SQL Server have been compiled and tested with PHP 7.0.* and 7.1.* using Visual C++ 2015 as well as PHP 7.2.0* using Visual C++ 2017 v15.0. 
For details, please read the documentation and/or take a look at the sample [build scripts](https://github.com/Microsoft/msphpsql/tree/dev/buildscripts#windows).

## Install (Windows)

#### Prerequisites

- A Web server such as Internet Information Services (IIS) is required. Your Web server must be configured to run PHP
- [Microsoft ODBC Driver 11][odbc11], [Microsoft ODBC Driver 13][odbc13] or [Microsoft ODBC Driver 17][odbc17]

#### Enable the drivers

1. Make sure that the driver is in your PHP extension directory (you can simply copy it there if you did not use nmake install).

2. Enable it within your PHP installation's php.ini: `extension=php_sqlsrv.dll` and/or `extension=php_pdo_sqlsrv.dll`.  If necessary, specify the extension directory using extension_dir, for example: `extension_dir = "C:\PHP\ext"`. Note that the precompiled binaries have different names -- substitute accordingly in php.ini.

3. Restart the Web server.

## Install (UNIX)
The following instructions assume a clean environment and show how to install PHP 7.x, Microsoft ODBC driver, Apache, and Microsoft PHP drivers on Ubuntu 16, 17 RedHat 7, Debian 8, 9 SUSE 12, and macOS 10.11, 10.12. 

Note that [Microsoft ODBC Driver 17][odbc17] is required for Ubuntu 17 and Debian 9.

### Step 1: Install PHP7+ 

#### PHP 7.0

**Ubuntu 16.04, 17.10**

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
    yum install php php-pdo php-xml php-pear php-devel re2c gcc-c++ gcc

**Debian 8**

    sudo su
    apt-get install curl apt-transport-https
    curl https://www.dotdeb.org/dotdeb.gpg | apt-key add -
    echo "deb http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list
    echo "deb-src http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list
    apt-get update
    apt-get install -y php7.0 php-pear php7.0-dev php7.0-xml

**Debian 9**

    sudo su
    apt-get install curl apt-transport-https
    curl https://www.dotdeb.org/dotdeb.gpg | apt-key add -
    echo "deb http://packages.dotdeb.org stretch all" >> /etc/apt/sources.list
    echo "deb-src http://packages.dotdeb.org stretch all" >> /etc/apt/sources.list
    apt-get update
    apt-get install -y php7.0 php-pear php7.0-dev php7.0-xml
    
**SUSE 12**

    sudo su
    zypper refresh
    zypper install -y php7 php7-pear php7-devel

**macOS 10.11, 10.12**

    /usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
    brew tap 
    brew tap homebrew/dupes
    brew tap homebrew/versions
    brew tap homebrew/homebrew-php
    brew install php70 --with-pear --with-httpd24 --with-cgi
    echo 'export PATH="/usr/local/sbin:$PATH"' >> ~/.bash_profile
    echo 'export PATH="/usr/local/bin:$PATH"' >> ~/.bash_profile
    source ~/.bash_profile

#### PHP 7.1

**Ubuntu 16.04, 17.10**

    sudo su
    add-apt-repository ppa:ondrej/php
    apt-get update
    apt-get -y install php7.1 mcrypt php7.1-mcrypt php-mbstring php-pear php7.1-dev php7.1-xml

**RedHat 7**

    sudo su
    wget https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
    wget http://rpms.remirepo.net/enterprise/remi-release-7.rpm
    rpm -Uvh remi-release-7.rpm epel-release-latest-7.noarch.rpm
    subscription-manager repos --enable=rhel-7-server-optional-rpms
    yum-config-manager --enable remi-php71
    yum update
    yum install php php-pdo php-xml php-pear php-devel re2c gcc-c++ gcc

**Debian 8, 9**

    sudo su
    apt-get install curl apt-transport-https
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
    apt-get update
    apt-get install -y php7.1 php-pear php7.1-dev php7.1-xml

**SUSE 12**

    sudo su
    zypper -n ar -f http://download.opensuse.org/repositories/devel:/languages:/php/openSUSE_Leap_42.3/ devel:languages:php
    zypper --gpg-auto-import-keys refresh
    zypper -n install php7 php7-pear php7-devel

**macOS 10.11, 10.12**

    /usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
    brew tap 
    brew tap homebrew/dupes
    brew tap homebrew/versions
    brew tap homebrew/homebrew-php
    brew install php71 --with-pear --with-httpd24 --with-cgi
    echo 'export PATH="/usr/local/sbin:$PATH"' >> ~/.bash_profile
    echo 'export PATH="/usr/local/bin:$PATH"' >> ~/.bash_profile
    source ~/.bash_profile


### Step 2: Install Prerequisites

**Ubuntu 16.04**

    sudo su 
    curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
    curl https://packages.microsoft.com/config/ubuntu/16.04/prod.list > /etc/apt/sources.list.d/mssql-release.list
    exit
    sudo apt-get update
    sudo ACCEPT_EULA=Y apt-get install msodbcsql=13.1.4.0-1 mssql-tools=14.0.3.0-1 unixodbc-dev 
    sudo apt-get install unixodbc-dev
    echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bash_profile
    echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bashrc
    source ~/.bashrc

**Ubuntu 17.10 (available upon the official release of ODBC driver 17)**

    sudo su 
    curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
    curl https://packages.microsoft.com/config/ubuntu/17.10/prod.list > /etc/apt/sources.list.d/mssql-release.list
    exit
    sudo apt-get update
    sudo ACCEPT_EULA=Y apt-get install msodbcsql mssql-tools 
    sudo apt-get install unixodbc-dev
    echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bash_profile
    echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bashrc
    source ~/.bashrc
    
**RedHat 7**

    sudo su
    curl https://packages.microsoft.com/config/rhel/7/prod.repo > /etc/yum.repos.d/mssql-release.repo
    exit
    sudo yum update
    sudo yum remove unixODBC-utf16-devel #to avoid conflicts
    sudo ACCEPT_EULA=Y yum install msodbcsql mssql-tools 
    sudo yum install unixODBC-devel
    echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bash_profile
    echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bashrc
    source ~/.bashrc

**Debian 8**

    sudo su 
    curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
    curl https://packages.microsoft.com/config/debian/8/prod.list > /etc/apt/sources.list.d/mssql-release.list
    apt-get install -y locales
    echo "en_US.UTF-8 UTF-8" > /etc/locale.gen
    locale-gen
    exit
    sudo apt-get update
    sudo ACCEPT_EULA=Y apt-get install msodbcsql
    sudo apt-get install unixodbc-dev

**Debian 9 (available upon the official release of ODBC driver 17)**

    sudo su 
    curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
    curl https://packages.microsoft.com/config/debian/9/prod.list > /etc/apt/sources.list.d/mssql-release.list
    apt-get install -y locales
    echo "en_US.UTF-8 UTF-8" > /etc/locale.gen
    locale-gen
    exit
    sudo apt-get update
    sudo ACCEPT_EULA=Y apt-get install msodbcsql
    sudo apt-get install unixodbc-dev

**SUSE 12**

    sudo su
    zypper ar https://packages.microsoft.com/config/sles/12/prod.repo
    sudo zypper --gpg-auto-import-keys refresh
    exit
    sudo ACCEPT_EULA=Y zypper install msodbcsql
    sudo ACCEPT_EULA=Y zypper install mssql-tools
    sudo zypper install unixODBC-devel
    echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bash_profile
    echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bashrc
    source ~/.bashrc

**macOS 10.11, 10.12**

    brew tap microsoft/msodbcsql https://github.com/Microsoft/homebrew-mssql-release
    brew update
    brew install --no-sandbox msodbcsql
    brew install mssql-tools
    brew install autoconf

*Note: Be sure to install PHP 7+ before proceeding to step 3. The Microsoft PHP Drivers for SQL Server will only work for PHP 7+.

### Step 3: Install the Microsoft PHP Drivers for SQL Server

*Note: You can run `sudo pecl search sqlsrv` to search for the latest releases and `sudo pecl install sqlsrv-[version]` to install a specific version. PECL installs the stable version when version is not specified. Drivers are Mac-compatible starting from `4.1.7preview` release.

On Ubuntu, Debian, and SUSE systems only, run:

    sudo pear config-set php_ini `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"` system

On macOS, run:

    chmod -R ug+w /usr/local/opt/php71/lib/php
    pear config-set php_ini /usr/local/etc/php/7.1/php.ini system

On all systems, run:

    pecl install sqlsrv
    pecl install pdo_sqlsrv
   
### Step 4: Install and Configure Apache

#### PHP 7.0

**Ubuntu and Debian**
    
    sudo su
    apt-get install libapache2-mod-php7.0 apache2
    a2dismod mpm_event
    a2enmod mpm_prefork
    a2enmod php7.0
    echo "extension=sqlsrv.so" >> /etc/php/7.0/apache2/php.ini
    echo "extension=pdo_sqlsrv.so" >> /etc/php/7.0/apache2/php.ini

**RedHat** 

    sudo su
    yum install httpd
    echo "extension=sqlsrv.so" > /etc/php.d/sqlsrv.ini
    echo "extension=pdo_sqlsrv.so" > /etc/php.d/pdo_sqlsrv.ini

**SUSE**

    sudo su
    zypper install apache2 apache2-mod_php7
    a2enmod php7
    echo "extension=sqlsrv.so" >> /etc/php7/apache2/php.ini
    echo "extension=pdo_sqlsrv.so" >> /etc/php7/apache2/php.ini

**macOS** 

    (echo "<FilesMatch .php$>"; echo "SetHandler application/x-httpd-php"; echo "</FilesMatch>";) >> /usr/local/etc/apache2/2.4/httpd.conf

#### PHP 7.1 

**Ubuntu and Debian**
    
    sudo su
    apt-get install libapache2-mod-php7.1 apache2
    a2dismod mpm_event
    a2enmod mpm_prefork
    a2enmod php7.1
    echo "extension=sqlsrv.so" >> /etc/php/7.1/apache2/php.ini
    echo "extension=pdo_sqlsrv.so" >> /etc/php/7.1/apache2/php.ini

**RedHat** 

    sudo su
    yum install httpd
    echo "extension=sqlsrv.so" > /etc/php.d/sqlsrv.ini
    echo "extension=pdo_sqlsrv.so" > /etc/php.d/pdo_sqlsrv.ini

**SUSE**

    sudo su
    zypper install apache2 apache2-mod_php7
    a2enmod php7
    echo "extension=sqlsrv.so" >> /etc/php7/apache2/php.ini
    echo "extension=pdo_sqlsrv.so" >> /etc/php7/apache2/php.ini

**macOS** 

    (echo "<FilesMatch .php$>"; echo "SetHandler application/x-httpd-php"; echo "</FilesMatch>";) >> /usr/local/etc/apache2/2.4/httpd.conf
    
    
### Step 5: Restart Apache to load the new php.ini file

**Ubuntu, Debian, and SUSE**

    sudo systemctl restart apache2

**RedHat**

    sudo systemctl restart httpd 

Note: On RedHat, SELinux is installed by default and runs in Enforcing mode. To allow Apache to connect to a database through SELinux, run the following command: 

    sudo setsebool -P httpd_can_network_connect_db 1     

**macOS** 

    sudo apachectl restart  


### Step 6: Create your sample app
Navigate to your system's document root -- `/var/www/html` on Ubuntu, Debian, and Redhat, `/srv/www/htdocs` on SUSE, or `/usr/local/var/www/htdocs` on Mac. Create a new file called testsql.php. Copy and paste the following code into testsql.php and change the servername, username, password and databasename.

    <?php
    $serverName = "yourServername";
    $connectionOptions = array(
        "Database" => "yourDatabase",
        "Uid" => "yourUsername",
        "PWD" => "yourPassword"
    );
    //Establishes the connection
    $conn = sqlsrv_connect( $serverName, $connectionOptions );
    if( $conn === false ) {
        die( FormatErrors( sqlsrv_errors()));
    }
    //Select Query
    $tsql= "SELECT @@Version as SQL_VERSION";
    //Executes the query
    $getResults= sqlsrv_query( $conn, $tsql );
    //Error handling
     
    if ( $getResults == FALSE )
        die( FormatErrors( sqlsrv_errors()));
    ?> 
     <h1> Results : </h1>
     <?php
    while ( $row = sqlsrv_fetch_array( $getResults, SQLSRV_FETCH_ASSOC )) {
        echo ( $row['SQL_VERSION']);
        echo ("<br/>");
    }
    sqlsrv_free_stmt( $getResults );
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

Go to your browser and type in http://localhost/testsql.php (http://localhost:8080/testsql.php on Mac)
You should be able to connect to your SQL Server/Azure SQL Database.

The drivers are distributed as shared binary extensions for PHP. They are available in thread safe (*_ts.so) and-non thread safe (*_nts.so) versions. The source code for the drivers is also available, and you can choose whether to compile them as thread safe or non-thread safe versions. The thread safety configuration of your web server will determine which version you need. 

## Sample Code
For samples, please see the sample folder.  For setup instructions, see [here](https://docs.microsoft.com/en-us/azure/sql-database/sql-database-develop-php-simple).

## Limitations

- This release contains the PHP 7 port of the SQLSRV and PDO_SQLSRV drivers, and does not provide backwards compatibility with PHP 5.
- Binding output parameters using emulate prepare is not supported.
- Linux
  - ODBC 3.52 is supported but not 3.8.
  - Connection using named instances using '\' is not supported.
  - Local encodings other than UTF-8 are not supported, and SQLSRV_ENC_CHAR only supports ASCII characters with ASCII code of 0 to 127.

## Known Issues
- When pooling is enabled in Linux or macOS
   - unixODBC <= 2.3.4 (Linux and macOS) might not return proper diagnostics information, such as error messages, warnings and informative messages
   - due to this unixODBC bug, fetch large data (such as xml, binary) as streams as a workaround. See the examples [here](https://github.com/Microsoft/msphpsql/wiki/Features#connection-pooling-in-linux-and-mac)

## Version number
Version number of PHP drivers follow the [semantic versioning](http://semver.org/):

Given a version number MAJOR.MINOR.PATCH, 

 - MAJOR version is incremented when an incompatible API changes is made, 
 - MINOR version is incremented when a functionality in a backwards-compatible manner is added, and
 - PATCH version is incremented when backwards-compatible bug fixes are made.
 
version number MAY have trailing pre-release version to indicate the stability, and/or build meta data.

- Pre-release version is denoted by hyphen followed by `preview` or `rc` keyword and may be followed by a series of dot separated identifiers. Production quality releases do not contain the pre-release version. `preview` has lower precedence than `rc`. Example of precedence: *preview < preview.1 < rc < rc.1*. 
*Note that PECL package version does not have the hyphen before pre-release version, due to restrictions in PECL. Example of PECL package version: 1.2.3preview*
- Build metadata MAY be denoted by a plus sign followed by 4 digits, such as  `1.2.3-preview+5678` or `1.2.3+5678`. Build meta data does NOT figure into the precedence order.

    

## Future Plans
- Expand SQL 16 Feature Support (example: Always Encrypted).
- Add More Verification/Fundamental Tests.
- Bug Fixes.

## Guidelines for Reporting Issues
We appreciate you taking the time to test the driver, provide feedback and report any issues.  It would be extremely helpful if you:

- Report each issue as a new issue (but check first if it's already been reported)
- Try to be detailed in your report. Useful information for good bug reports include:
  * What you are seeing and what the expected behaviour is
  * Can you connect to SQL Server via `sqlcmd`? 
  * Which driver: SQLSRV or PDO_SQLSRV?
  * Environment details: e.g. PHP version, thread safe (TS) or non-thread safe (NTS), 32-bit &/or 64-bit?
  * Table schema (for some issues the data types make a big difference!)
  * Any other relevant information you want to share
- Try to include a PHP script demonstrating the isolated problem.

Thank you!

## FAQs
**Q:** Can we get dates for any of the Future Plans listed above?

**A:** At this time, Microsoft is not able to announce dates. We are working extremely hard to release future versions of the driver. We will share future plans as appropriate. 

**Q:** What's next?

**A:** On July 6, 2017 we released the production release version 4.3.0 of our PHP Driver. We will continue working on our future plans and releasing previews of upcoming releases frequently.

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

[project]: https://github.com/Azure/msphpsql

[issues]: https://github.com/Azure/msphpsql/issues

[phpweb]: http://php.net

[phpbuild]: https://wiki.php.net/internals/windows/stepbystepbuild

[phpdoc]: http://msdn.microsoft.com/library/dd903047%28SQL.11%29.aspx

[odbc11]: https://www.microsoft.com/download/details.aspx?id=36434

[odbc13]: https://www.microsoft.com/download/details.aspx?id=50420

[odbc17]: https://github.com/Microsoft/msphpsql/tree/master/ODBC%2017%20binaries%20preview

[odbcLinux]: https://msdn.microsoft.com/library/hh568454(v=sql.110).aspx

[phpazure]: https://azure.microsoft.com/documentation/articles/sql-database-develop-php-simple-windows/

[PHPMan]: http://php.net/manual/install.unix.php

[LinuxDM]: https://msdn.microsoft.com/library/hh568449(v=sql.110).aspx

[httpd_source]: http://httpd.apache.org/

[apr_source]: http://apr.apache.org/

[httpdconf]: http://php.net/manual/en/install.unix.apache2.php

[ODBCinstallers]: https://blogs.msdn.microsoft.com/sqlnativeclient/2016/09/06/preview-release-of-the-sql-server-cc-odbc-driver-13-0-0-for-linux

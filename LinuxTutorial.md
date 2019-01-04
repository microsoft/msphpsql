# PHP Linux Drivers for SQL Server - Installation Tutorial

In this tutorial, we will show you how to install the PHP Linux drivers for Microsoft SQL Server, along with the additional required software to get them to work. The drivers are available from Github, where you will find several variants. They are available as a SQLSRV variant or a PDO_SQLSRV variant; the latter implements the PDO PHP extension for accessing data. In addition, the drivers are built for both thread safe and non-thread safe environments. The PHP Linux drivers built for thread safe servers are named php_sqlsrv_7_ts.so and php_pdo_sqlsrv_7_ts.so. The drivers built for servers without thread safety enabled are php_sqlsrv_7_nts.so and php_pdo_sqlsrv_7_nts.so. 

Prior to installing the PHP Linux drivers, you must install the unixODBC driver manager, the Microsoft ODBC driver for Linux, PHP 7, and a web server. In the following, we will assume the web server is Apache. 

### Install the unixODBC driver manager and Microsoft ODBC driver for Linux

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
	
**Verify that the ODBC driver on Linux is registered successfully by executing the following commands: **

    `odbcinst –j`
    `odbcinst -q -d -n "ODBC Driver 13 for SQL Server"`
	  
 You should see output similar to the following: 

[![pic1](https://msdnshared.blob.core.windows.net/media/2016/07/image1101.png)](https://msdnshared.blob.core.windows.net/media/2016/07/image1101.png) 

### Install Apache
 
You are now ready to install Apache. You can install from source, or you can use your package manager.
To install from source, follow these instructions.

1. From the Apache web site, download the Apache source. Go to [http://httpd.apache.org/download.cgi#apache24](http://httpd.apache.org/download.cgi) and click on the link to the tar.gz file. In what follows, we'll assume it is `httpd-2.4.20.tar.gz`. Take note of the directory to which it is downloaded.

	[![pic2](https://msdnshared.blob.core.windows.net/media/2016/07/image2100.png)](https://msdnshared.blob.core.windows.net/media/2016/07/image2100.png) 
	
2.  Download the Apache Portable Runtime (APR) and APR utilities from [http://apr.apache.org/download.cgi](http://apr.apache.org/download.cgi). Click on `apr-1.5.2.tar.gz` and `apr-util-1.5.4.tar.gz` to download.

	[![pic3](https://msdnshared.blob.core.windows.net/media/2016/07/image3100.png)](https://msdnshared.blob.core.windows.net/media/2016/07/image3100.png) 
	
3.  Extract the web server into a local directory and then extract the apr packages into the srclib/ directory. Run the following commands:

	`cd <path to download directory>` 
	`tar xvzf httpd-2.4.20.tar.gz` 
	`tar xvzf apr-1.5.2.tar.gz`
	`tar xvzf apr-util-1.5.4.tar.gz` 
	`mkdir httpd-2.4.20/srclib/apr-util` 
	`cp -r apr-1.5.2/* httpd-2.4.20/srclib/apr` 
	`cp -r apr-util-1.5.4/* httpd-2.4.20/srclib/apr-util` 
	`cd httpd-2.4.20/`

4.  Now we compile Apache. The compilation depends on whether the PHP drivers are thread safe. If you have downloaded the thread safe drivers (with names ending in _ts.so), run the following command:

	`./configure --enable-so --with-mpm=worker` 
	
	If you have downloaded the non-thread safe drivers (with names ending in _nts.so), run: 
    
	`./configure --enable-so --with-mpm=prefork`
	
    If you get a message saying that PCRE is not found, it can be installed with your package manager. Run `sudo apt-get install libpcre3-dev on Ubuntu`, or `sudo yum install pcre-devel on CentOS`.

5.  Run `make` and `sudo make install` to complete the installation.

### To install Apache from your package manager, follow these steps:

1.  If using Red Hat or CentOS, run the following command:

	`sudo yum install httpd httpd-devel`
	
    If using Ubuntu, run the following command: 

	`sudo apt-get install apache2 apache2-dev`

Note that your package manager's version of Apache is likely not thread safe. To verify that Apache is installed and working properly, point your web browser to localhost/. If you installed from source, you will see a message saying 'It works!`. If you installed from package, you may see a different landing page – here is the landing page on Ubuntu: 

[![pic4](https://msdnshared.blob.core.windows.net/media/2016/07/image463.png)](https://msdnshared.blob.core.windows.net/media/2016/07/image463.png) 
	
### Install PHP
Now you are ready to install PHP. You can install by source or, if the packaged version is PHP 7, with your package manager. However, we recommend you install from source. To install PHP from source, follow these instructions:

1.  Download the latest stable PHP 7 source from [http://php.net/downloads.php](http://php.net/downloads.php). In what follows, we will assume the downloaded source file is `php-7.0.8.tar.gz`.

2.  Run the following commands:

	`cd <path to download directory>` 
	`tar xvzf php-7.0.8.tar.gz` 
	`cd php-7.0.8/` 
	`./buildconf --force`

3.  Run `./configure` with the following options on the command line: 
    a.  the path to apxs or apxs2 to configure PHP for Apache using `--with-apxs2=<path-to-apxs>`. To find the path to apxs (or apxs2), run `sudo find / -name apxs` or `sudo find / -name apxs2` and add the resulting path to the option. For example, if the find command yields `/usr/bin/apxs2`, add `--with-apxs2=/usr/bin/apxs2` to the `./configure` command line.
	b.  if your web server has thread safety enabled, add `--enable-maintainer-zts` to `./configure`. Otherwise you may omit this option.
	
	Thus your `./configure` command should look like `./configure --with-apxs2=<path-to-apxs-executable> --enable-maintainer-zts`. 

	[![pic5](https://msdnshared.blob.core.windows.net/media/2016/07/image510.png)](https://msdnshared.blob.core.windows.net/media/2016/07/image510.png) 
	
	If your `./configure` command exits with an error saying it cannot find `xml2-config`, you need to install `libxml2-dev` using your package manager before continuing. Run the following command: `sudo yum install libxml2-devel` on Red Hat or CentOS, or `sudo apt-get install libxml2-dev` on Ubuntu.

4.  Run `make` and then copy the downloaded PHP drivers into the modules/ directory.

5.  Run `sudo make install` to install the binaries into the default php extensions directory.

6.  Edit your Apache config file so that Apache can load PHP files. If you installed Apache from source, locate your `httpd.conf` file (using, for example, `sudo find / -name httpd.conf`) and add the following lines to it:

    `LoadModule php7_module modules/libphp7.so`
    `<FilesMatch \.php$>`
    `  SetHandler application/x-httpd-php`
    `</FilesMatch>`

    You can alter these lines to allow different file types to be parsed as PHP files. See [http://php.net/manual/en/install.unix.apache2.php](http://php.net/manual/en/install.unix.apache2.php) for more information. 

    If you installed Apache via the package manager, the Apache config file(s) may have a different structure. In Ubuntu, you can find `apache2.conf` in `/etc/apache2`. Add the above lines to this file.   

### Install PHP from the package manager

To install PHP and the PHP apache module using your package manager, you must ensure that your distribution provides PHP 7, as earlier versions will not work. 

Follow these steps to install from the package manager on Ubuntu:

1.  Run `apt-cache show php | grep Version`. The output will look like Version: 1:7.0+35ubuntu6\. The actual version of PHP immediately follows the 1: . 
2.  If the packaged PHP version is not PHP 7.0, you can add the [Ondrej PPA repository](https://launchpad.net/~ondrej/+archive/ubuntu/php) to install it. Run `sudo add-apt-repository ppa:ondrej/php` and then `sudo apt-get update`. 
3.  Run `sudo apt-get install php libapache2-mod-php` to install PHP and the Apache module.

Follow these steps to install from the package manager on Red Hat/CentOS:

1.  Run `yum info php | grep Version` and verify that the version is 7.0.
2.  If the packaged PHP version is not PHP 7.0, you can add a new repository to install it. We recommend using the [Remi RPM repository](http://blog.remirepo.net/post/2016/02/14/Install-PHP-7-on-CentOS-RHEL-Fedora). To configure this repository, run 

    `wget https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm`
    `wget http://rpms.remirepo.net/enterprise/remi-release-7.rpm`
    `rpm -Uvh remi-release-7.rpm epel-release-latest-7.noarch.rpm`
    `yum-config-manager --enable remi-php70`

3.  Run `sudo yum install php php-pdo` to install both PHP and the Apache module.

4.  Run `sudo yum update`.

Remi RPM repository also provides a package to install Microsoft PHP drivers for SQLServer in Red Hat/CentOS. After following the steps above, all you need to do is 

5. Run `sudo yum install php-sqlsrv.x86_64`

To confirm that the drivers have been installed, run `php -m` and you should find both `sqlsrv` and `pdo_sqlsrv` amongst the list of modules.

### Compiling and installing Microsoft PHP drivers for SQL Server from source: 

Instead of using the precompiled binaries, you can compile your own when compiling PHP. To compile the binaries yourself, follow these steps: 

1.  Download the source from Github. Download the latest stable PHP 7 source from http://php.net/. 

2.  Unpack the PHP source and the SQL Server extension source, and copy the extension source to the PHP extension directory: 

    `cd <path to download directory>`
    `tar xvzf php-7.0.8.tar.gz`
    `cp –r sqlsrv/ php-7.0.8/ext/`
    `cp –r pdo_sqlsrv/ php-7.0.8/ext/`
    `cd php-7.0.8/`
    `./buildconf --force`

3.  Run `./configure` with the same options as described in step 3 above, in addition to the following: 
    a.  `CXXFLAGS=-std=c++11` to ensure your compiler uses the C++11 standard. 
    b.  `--enable-sqlsrv=shared` 
    c.  `--with-pdo_sqlsrv=shared` 

    Thus your `./configure` command should look like `./configure --with-apxs2=<path-to-apxs-executable> --disable-maintainer-zts CXXFLAGS=-std=c++11 --enable-sqlsrv=shared --with-pdo_sqlsrv=shared`. 

    Note: if you get a message saying `WARNING: unrecognized options: --enable-sqlsrv, --with-pdo_sqlsrv`, run `touch ext/*/config.m4` and then `./buildconf --force` before trying `./configure` again. 

4.  Run `make`. The compiled drivers will be located in the `modules/` directory, and are named `sqlsrv.so` and `pdo_sqlsrv.so`. 

5.  Run `sudo make install` to install the binaries into the default php extensions directory. 

6.  Edit your apache configuration file as described in step 6 above. 

### Installing Microsoft PHP drivers for SQL Server using PECL packages:

You can install the SQL Server drivers using PHP's PECL package system. This can be done after having installed PHP from source or from package.

1. If installing PHP from source, download the PHP sources and run `./buildconf` as described above. Run `./configure` (without extra options), `make`, and `sudo make install` to install PHP.

  If installing PHP from package, install php as described above. In addition, on Ubuntu install php-pear and php-dev using `sudo apt-get install php-pear php-dev`.

2. Verify that `pear` and `pecl` are installed by running `pear version` and `pecl version`. If they are not, go to step 4.

3. Run `sudo pecl search sqlsrv` to list the available sqlsrv and pdo_sqlsrv extensions. Take note of the version number and run `sudo pecl install sqlsrv-<version-number>` and `sudo pecl install pdo_sqlsrv-<version-number>` to install the drivers. If you do not include the version number, you will install the latest 'stable' release, which does not work with PHP 7.

4. If `pear` and `pecl` are not installed, you can download the [SQLSRV](http://pecl.php.net/package/sqlsrv) and [PDO_SQLSRV](http://pecl.php.net/package/pdo_sqlsrv) PECL packages from pecl.php.net (click on 'Latest Tarball' to download). You can then install the SQLSRV driver as follows:

 `tar xvzf sqlsrv-<version-number>.tgz`
`cd sqlsrv-<version-number>/`
`phpize`
`./configure`
`make`
`sudo make install`

and similarly for the PDO_SQLSRV driver.

### Loading the drivers

Finally, edit your `php.ini` file to load the PHP drivers when PHP starts.

1.  To find the location of your `php.ini` file, run `php --ini` to find the directory PHP searches for `php.ini`. You will see output similar to the following:

	[![pic6](https://msdnshared.blob.core.windows.net/media/2016/07/image611.png)](https://msdnshared.blob.core.windows.net/media/2016/07/image611.png) 
	
  If you installed PHP from package, the output will be slightly different and will probably list more .ini files, but you will likely need to edit only the `php.ini` file listed under `Loaded Configuration File`.

2.  If `Loaded Configuration File` shows that a `php.ini` is loaded, edit that file. Otherwise go to the PHP directory in your home directory, run `cp php.ini-development php.ini` and copy the newly created `php.ini` file to the `Configuration File (php.ini) Path` indicated when running `php --ini`. If using the SQLSRV driver, add the following lines to your php.ini: `extension=php_sqlsrv_7_ts.so` or `extension=php_sqlsrv_7_nts.so`. If using the PDO_SQLSRV driver, add `extension=php_pdo_sqlsrv_7_ts.so` or `extension=php_pdo_sqlsrv_7_nts.so`. Change the names of the drivers accordingly if you have compiled them from source and they have different names. If necessary, specify the extension directory using extension_dir, for example: `extension_dir = “/usr/local/lib/php/extensions/”`. To find the default extension directory, run `php -i | grep extension_dir`.

 Note that you can add lines to php.ini from the command line as follows:
`echo "extension=sqlsrv.so" | sudo tee --append <path-to-php.ini>`

3.  Stop and restart the Apache web server.

4.  Test your apache and PHP installation with a script that calls phpinfo(). Copy the following to a file called phpinfo.php:

 `<?php phpinfo(); ?>` and copy that file to your web directory. This is likely to be either `/var/www/html`, or the `htdocs/` directory in the Apache directory. In a web browser, go to localhost/phpinfo.php. You should see a page with information about your PHP installation, and information on enabled extensions, including sqlsrv and pdo_sqlsrv.

[![pic7](https://msdnshared.blob.core.windows.net/media/2016/07/image711.png)](https://msdnshared.blob.core.windows.net/media/2016/07/image711.png) 
	
[![pic8](https://msdnshared.blob.core.windows.net/media/2016/07/image810.png)](https://msdnshared.blob.core.windows.net/media/2016/07/image810.png) 

[![pic9](https://msdnshared.blob.core.windows.net/media/2016/07/image915.png)](https://msdnshared.blob.core.windows.net/media/2016/07/image915.png) 
	
 If you do not see sections on sqlsrv and pdo_sqlsrv extensions, these extensions are not loaded. Near the top of the PHP info page, check which `php.ini` is loaded. This may be different from the `php.ini` file loaded when running php from the command line, especially if Apache and PHP were installed from your package manager. In this case, edit the `php.ini` displayed on the PHP info page to load the extensions in the same way described above. Restart the Apache web server and verify that phpinfo() loads the sqlsrv extensions.   

#### Links: 
Microsoft PHP GitHub repository: https://github.com/Azure/msphpsql 
UnixODBC 2.3.1 for Ubuntu: [http://www.unixodbc.org/pub/unixODBC/unixODBC-2.3.1.tar.gz](http://www.unixodbc.org/pub/unixODBC/unixODBC-2.3.1.tar.gz) 
Microsoft® ODBC Driver 13 (Preview) for SQL Server® - Ubuntu Linux: [https://www.microsoft.com/en-us/download/details.aspx?id=50419](https://www.microsoft.com/en-us/download/details.aspx?id=50419) 
Microsoft® ODBC Driver 13 (Preview) and 11 for SQL Server® - Red Hat Linux: [https://www.microsoft.com/en-us/download/details.aspx?id=36437](https://www.microsoft.com/en-us/download/details.aspx?id=36437) 
Apache source:  [http://httpd.apache.org/download.cgi#apache24](http://httpd.apache.org/download.cgi) 
Apache Portable Runtime (APR): [http://apr.apache.org/download.cgi](http://apr.apache.org/download.cgi) 
PHP source download page: [http://php.net/downloads.php](http://php.net/downloads.php)
Ondrej PPA repository: [https://launchpad.net/~ondrej/+archive/ubuntu/php](https://launchpad.net/~ondrej/+archive/ubuntu/php)
Remi RPM repository: [http://blog.remirepo.net/post/2016/02/14/Install-PHP-7-on-CentOS-RHEL-Fedora](http://blog.remirepo.net/post/2016/02/14/Install-PHP-7-on-CentOS-RHEL-Fedora)
PECL SQLSRV package: [http://pecl.php.net/package/sqlsrv](http://pecl.php.net/package/sqlsrv)
PECL PDO_SQLSRV package: [http://pecl.php.net/package/pdo_sqlsrv](http://pecl.php.net/package/pdo_sqlsrv)

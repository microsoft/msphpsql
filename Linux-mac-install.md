# Linux and macOS Installation Tutorial for the Microsoft Drivers for PHP for SQL Server
The following instructions assume a clean environment and show how to install PHP 7.x, the Microsoft ODBC driver, Apache, and the Microsoft Drivers for PHP for SQL Server on Ubuntu 16.04, 18.04, and 18.10, RedHat 7, Debian 8 and 9, Suse 12 and 15, and macOS 10.11, 10.12, 10.13, and 10.14. These instructions advise installing the drivers using PECL, but you can also download the prebuilt binaries from the [Microsoft Drivers for PHP for SQL Server](https://github.com/Microsoft/msphpsql/releases) Github project page and install them following the instructions in [Loading the Microsoft Drivers for PHP for SQL Server](https://docs.microsoft.com/sql/connect/php/loading-the-php-sql-driver). For an explanation of extension loading and why we do not add the extensions to php.ini, see the section on [loading the drivers](https://docs.microsoft.com/sql/connect/php/loading-the-php-sql-driver##loading-the-driver-at-php-startup).

These instructions install PHP 7.3 by default. Note that some supported Linux distros default to PHP 7.0 or earlier, which is not supported for the PHP drivers for SQL Server -- please see the notes at the beginning of each section to install PHP 7.1 or 7.2 instead.

## Contents of this page:

- [Installing the drivers on Ubuntu 16.04, 18.04, and 18.10](#installing-the-drivers-on-ubuntu-1604-1804-and-1810)
- [Installing the drivers on Red Hat 7](#installing-the-drivers-on-red-hat-7)
- [Installing the drivers on Debian 8 and 9](#installing-the-drivers-on-debian-8-and-9)
- [Installing the drivers on Suse 12 and 15](#installing-the-drivers-on-suse-12-and-15)
- [Installing the drivers on macOS Sierra, High Sierra, and Mojave](#installing-the-drivers-on-macos-sierra-high-sierra-and-mojave)

## Installing the drivers on Ubuntu 16.04, 18.04, and 18.10

> [!NOTE]
> To install PHP 7.1 or 7.2, replace 7.3 with 7.1 or 7.2 in the following commands.

### Step 1. Install PHP
```
sudo su
add-apt-repository ppa:ondrej/php -y
apt-get update
apt-get install php7.3 php7.3-dev php7.3-xml -y --allow-unauthenticated
```
### Step 2. Install prerequisites
Install the ODBC driver for Ubuntu by following the instructions on the [Linux and macOS installation page](https://docs.microsoft.com/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server).

### Step 3. Install the PHP drivers for Microsoft SQL Server
```
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv
sudo su
echo extension=pdo_sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/30-pdo_sqlsrv.ini
echo extension=sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/20-sqlsrv.ini
exit
```
### Step 4. Install Apache and configure driver loading
```
sudo su
apt-get install libapache2-mod-php7.3 apache2
a2dismod mpm_event
a2enmod mpm_prefork
a2enmod php7.3
echo "extension=pdo_sqlsrv.so" >> /etc/php/7.3/apache2/conf.d/30-pdo_sqlsrv.ini
echo "extension=sqlsrv.so" >> /etc/php/7.3/apache2/conf.d/20-sqlsrv.ini
exit
```
### Step 5. Restart Apache and test the sample script
```
sudo service apache2 restart
```
To test your installation, see [Testing your installation](#testing-your-installation) at the end of this document.

## Installing the drivers on Red Hat 7

> [!NOTE]
> To install PHP 7.1 or 7.2, replace remi-php73 with remi-php71 or remi-php72 respectively in the following commands.

### Step 1. Install PHP

```
sudo su
wget https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
wget https://rpms.remirepo.net/enterprise/remi-release-7.rpm
rpm -Uvh remi-release-7.rpm epel-release-latest-7.noarch.rpm
subscription-manager repos --enable=rhel-7-server-optional-rpms
yum install yum-utils
yum-config-manager --enable remi-php73
yum update
yum install php php-pdo php-xml php-pear php-devel re2c gcc-c++ gcc
```
### Step 2. Install prerequisites
Install the ODBC driver for Red Hat 7 by following the instructions on the [Linux and macOS installation page](https://docs.microsoft.com/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server).

Compiling the PHP drivers with PECL with PHP 7.2 or 7.3 requires a more recent GCC than the default:
```
sudo yum-config-manager --enable rhel-server-rhscl-7-rpms
sudo yum install devtoolset-7
scl enable devtoolset-7 bash
```
### Step 3. Install the PHP drivers for Microsoft SQL Server
```
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv
sudo su
echo extension=pdo_sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/30-pdo_sqlsrv.ini
echo extension=sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/20-sqlsrv.ini
exit
```
An issue in PECL may prevent correct installation of the latest version of the drivers even if you have upgraded GCC. To install, download the packages and compile manually (similar steps for pdo_sqlsrv):
```
pecl download sqlsrv
tar xvzf sqlsrv-5.6.1.tgz
cd sqlsrv-5.6.1/
phpize
./configure --with-php-config=/usr/bin/php-config
make
sudo make install
```
You can alternatively download the prebuilt binaries from the [Github project page](https://github.com/Microsoft/msphpsql/releases), or install from the Remi repo:
```
sudo yum install php-sqlsrv
```
### Step 4. Install Apache
```
sudo yum install httpd
```
SELinux is installed by default and runs in Enforcing mode. To allow Apache to connect to databases through SELinux, run the following command:
```
sudo setsebool -P httpd_can_network_connect_db 1
```
### Step 5. Restart Apache and test the sample script
```
sudo apachectl restart
```
To test your installation, see [Testing your installation](#testing-your-installation) at the end of this document.

## Installing the drivers on Debian 8 and 9

> [!NOTE]
> To install PHP 7.1 or 7.2, replace 7.3 in the following commands with 7.1 or 7.2.

### Step 1. Install PHP
```
sudo su
apt-get install curl apt-transport-https
wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
apt-get update
apt-get install -y php7.3 php7.3-dev php7.3-xml
```
### Step 2. Install prerequisites
Install the ODBC driver for Debian by following the instructions on the [Linux and macOS installation page](https://docs.microsoft.com/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server). 

You may also need to generate the correct locale to get PHP output to display correctly in a browser. For example, for the en_US UTF-8 locale, run the following commands:
```
sudo su
sed -i 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/g' /etc/locale.gen
locale-gen
```

### Step 3. Install the PHP drivers for Microsoft SQL Server
```
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv
sudo su
echo extension=pdo_sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/30-pdo_sqlsrv.ini
echo extension=sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/20-sqlsrv.ini
exit
```
### Step 4. Install Apache and configure driver loading
```
sudo su
apt-get install libapache2-mod-php7.3 apache2
a2dismod mpm_event
a2enmod mpm_prefork
a2enmod php7.3
echo "extension=pdo_sqlsrv.so" >> /etc/php/7.3/apache2/conf.d/30-pdo_sqlsrv.ini
echo "extension=sqlsrv.so" >> /etc/php/7.3/apache2/conf.d/20-sqlsrv.ini
```
### Step 5. Restart Apache and test the sample script
```
sudo service apache2 restart
```
To test your installation, see [Testing your installation](#testing-your-installation) at the end of this document.

## Installing the drivers on Suse 12 and 15

> [!NOTE]
> In the following instructions, replace <SuseVersion> with your version of Suse - if you are using Suse Enterprise Linux 15, it will be SLE_15 or SLE_15_SP1, and similarly for other versions. Not all versions of PHP are available for all versions of Suse Linux - please refer to `http://download.opensuse.org/repositories/devel:/languages:/php` to see which versions of Suse have the default version PHP available, or to `http://download.opensuse.org/repositories/devel:/languages:/php:/` to see which other versions of PHP are available for which versions of Suse.

> [!NOTE]
> Packages for PHP 7.3 are not available for Suse 12. 
> To install PHP 7.1, replace the repository URL below with the following URL:
      `https://download.opensuse.org/repositories/devel:/languages:/php:/php71/<SuseVersion>/devel:languages:php:php71.repo`.
> To install PHP 7.2, replace the repository URL below with the following URL:
      `https://download.opensuse.org/repositories/devel:/languages:/php:/php72/<SuseVersion>/devel:languages:php:php72.repo`.

### Step 1. Install PHP
```
sudo su
zypper -n ar -f https://download.opensuse.org/repositories/devel:languages:php/<SuseVersion>/devel:languages:php.repo
zypper --gpg-auto-import-keys refresh
zypper -n install php7 php7-pear php7-devel php7-openssl
```
### Step 2. Install prerequisites
Install the ODBC driver for Suse by following the instructions on the [Linux and macOS installation page](https://docs.microsoft.com/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server).

### Step 3. Install the PHP drivers for Microsoft SQL Server
> [!NOTE]
> If you get an error message saying `Connection to 'pecl.php.net:443' failed: Unable to find the socket transport "ssl"`, edit the pecl script at /usr/bin/pecl and remove the `-n` switch in the last line. This switch prevents PECL from loading ini files when PHP is called, which prevents the OpenSSL extension from loading.

```
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv
sudo su
echo extension=pdo_sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/pdo_sqlsrv.ini
echo extension=sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/sqlsrv.ini
exit
```
### Step 4. Install Apache and configure driver loading
```
sudo su
zypper install apache2 apache2-mod_php7
a2enmod php7
echo "extension=sqlsrv.so" >> /etc/php7/apache2/php.ini
echo "extension=pdo_sqlsrv.so" >> /etc/php7/apache2/php.ini
exit
```
### Step 5. Restart Apache and test the sample script
```
sudo systemctl restart apache2
```
To test your installation, see [Testing your installation](#testing-your-installation) at the end of this document.

## Installing the drivers on macOS Sierra, High Sierra, and Mojave

If you do not already have it, install brew as follows:
```
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
```

> [!NOTE]
> To install PHP 7.1 or 7.2, replace php@7.3 with php@7.1 or php@7.2 respectively in the following commands.

### Step 1. Install PHP

```
brew tap
brew tap homebrew/core
brew install php@7.3
```
PHP should now be in your path -- run `php -v` to verify that you are running the correct version of PHP. If PHP is not in your path or it is not the correct version, run the following:
```
brew link --force --overwrite php@7.3
```

### Step 2. Install prerequisites
Install the ODBC driver for macOS by following the instructions on the [Linux and macOS installation page](https://docs.microsoft.com/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server). 

In addition, you may need to install the GNU make tools:
```
brew install autoconf automake libtool
```

### Step 3. Install the PHP drivers for Microsoft SQL Server
```
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv
```
### Step 4. Install Apache and configure driver loading
```
brew install apache2
```
To find the Apache configuration file for your Apache installation, run 
```
apachectl -V | grep SERVER_CONFIG_FILE
``` 
and substitute the path for `httpd.conf` in the following commands:
```
echo "LoadModule php7_module /usr/local/opt/php@7.3/lib/httpd/modules/libphp7.so" >> /usr/local/etc/httpd/httpd.conf
(echo "<FilesMatch .php$>"; echo "SetHandler application/x-httpd-php"; echo "</FilesMatch>";) >> /usr/local/etc/httpd/httpd.conf
```
### Step 5. Restart Apache and test the sample script
```
sudo apachectl restart
```
To test your installation, see [Testing your installation](#testing-your-installation) at the end of this document.

## Testing Your Installation

To test this sample script, create a file called testsql.php in your system's document root. This is `/var/www/html/` on Ubuntu, Debian, and Redhat, `/srv/www/htdocs` on SUSE, or `/usr/local/var/www` on macOS. Copy the following script to it, replacing the server, database, username, and password as appropriate.
```
<?php
$serverName = "yourServername";
$connectionOptions = array(
    "database" => "yourDatabase",
    "uid" => "yourUsername",
    "pwd" => "yourPassword"
);

// Establishes the connection
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(formatErrors(sqlsrv_errors()));
}

// Select Query
$tsql = "SELECT @@Version AS SQL_VERSION";

// Executes the query
$stmt = sqlsrv_query($conn, $tsql);

// Error handling
if ($stmt === false) {
    die(formatErrors(sqlsrv_errors()));
}
?>

<h1> Results : </h1>

<?php
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo $row['SQL_VERSION'] . PHP_EOL;
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

function formatErrors($errors)
{
    // Display errors
    echo "Error information: <br/>";
    foreach ($errors as $error) {
        echo "SQLSTATE: ". $error['SQLSTATE'] . "<br/>";
        echo "Code: ". $error['code'] . "<br/>";
        echo "Message: ". $error['message'] . "<br/>";
    }
}
?>
```
Point your browser to https://localhost/testsql.php (https://localhost:8080/testsql.php on macOS). You should now be able to connect to your SQL Server/Azure SQL database.
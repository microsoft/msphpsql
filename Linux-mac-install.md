# Linux and macOS Installation Tutorial for the Microsoft Drivers for PHP for SQL Server
The following instructions assume a clean environment and show how to install PHP 8.0, the Microsoft ODBC driver, the Apache web server, and the Microsoft Drivers for PHP for SQL Server on Ubuntu 16.04, 18.04, and 20.04, RedHat 7 and 8, Debian 9 and 10, Suse 12 and 15, Alpine 3.11 and 3.12, and macOS 10.14, 10.15, and 11.0. These instructions advise installing the drivers using PECL, but you can also download the prebuilt binaries from the [Microsoft Drivers for PHP for SQL Server](https://github.com/Microsoft/msphpsql/releases) Github project page and install them following the instructions in [Loading the Microsoft Drivers for PHP for SQL Server](https://docs.microsoft.com/sql/connect/php/loading-the-php-sql-driver). For an explanation of extension loading and why we do not add the extensions to php.ini, see the section on [loading the drivers](https://docs.microsoft.com/sql/connect/php/loading-the-php-sql-driver#loading-the-driver-at-php-startup).

The following instructions install PHP 8.0 by default using `pecl install`, if the PHP 8.0 packages are available. You may need to run `pecl channel-update pecl.php.net` first. Note that some supported Linux distros default to PHP 7.1 or earlier, which is not supported for the latest version of the PHP drivers for SQL Server -- please see the notes at the beginning of each section to install PHP 7.4 or 7.3 instead.

Also included are instructions for installing the PHP FastCGI Process Manager, PHP-FPM, on Ubuntu. This is needed if you are using the nginx web server instead of Apache.

While these instructions contain commands to install both SQLSRV and PDO_SQLSRV drivers, the drivers can be installed and function independently. Users comfortable with customizing their configuration can adjust these instructions to be specific to SQLSRV or PDO_SQLSRV. Both drivers have the same dependencies except where noted below.

## Contents of this page

- [Installing the drivers on Ubuntu 16.04, 18.04, and 20.04](#installing-the-drivers-on-ubuntu-1604-1804-and-2004)
- [Installing the drivers with PHP-FPM on Ubuntu](#installing-the-drivers-with-php-fpm-on-ubuntu)
- [Installing the drivers on Red Hat 7 and 8](#installing-the-drivers-on-red-hat-7-and-8)
- [Installing the drivers on Debian 9 and 10](#installing-the-drivers-on-debian-9-and-10)
- [Installing the drivers on Suse 12 and 15](#installing-the-drivers-on-suse-12-and-15)
- [Installing the drivers on Alpine 3.11 and 3.12](#installing-the-drivers-on-alpine-311-and-312)
- [Installing the drivers on macOS Mojave, Catalina and Big Sur](#installing-the-drivers-on-macos-mojave-catalina-and-big-sur)

## Installing the drivers on Ubuntu 16.04, 18.04, and 20.04

> [!NOTE]
> To install PHP 7.4 or 7.3, replace 8.0 with 7.4 or 7.3 in the following commands.

### Step 1. Install PHP
```bash
sudo su
add-apt-repository ppa:ondrej/php -y
apt-get update
apt-get install php8.0 php8.0-dev php8.0-xml -y --allow-unauthenticated
```
### Step 2. Install prerequisites
Install the ODBC driver for Ubuntu by following the instructions on the [Install the Microsoft ODBC driver for SQL Server (Linux)](https://docs.microsoft.com/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-ver15).

### Step 3. Install the PHP drivers for Microsoft SQL Server
```bash
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv
sudo su
printf "; priority=20\nextension=sqlsrv.so\n" > /etc/php/8.0/mods-available/sqlsrv.ini
printf "; priority=30\nextension=pdo_sqlsrv.so\n" > /etc/php/8.0/mods-available/pdo_sqlsrv.ini
exit
sudo phpenmod -v 8.0 sqlsrv pdo_sqlsrv
```

If there is only one PHP version in the system, then the last step can be simplified to `phpenmod sqlsrv pdo_sqlsrv`.

### Step 4. Install Apache and configure driver loading
```bash
sudo su
apt-get install libapache2-mod-php8.0 apache2
a2dismod mpm_event
a2enmod mpm_prefork
a2enmod php8.0
exit
```
### Step 5. Restart Apache and test the sample script
```bash
sudo service apache2 restart
```
To test your installation, see [Testing your installation](#testing-your-installation) at the end of this document.

## Installing the drivers with PHP-FPM on Ubuntu

> [!NOTE]
> To install PHP 7.4 or 7.3, replace 8.0 with 7.4 or 7.3 in the following commands.

### Step 1. Install PHP
```bash
sudo su
add-apt-repository ppa:ondrej/php -y
apt-get update
apt-get install php8.0 php8.0-dev php8.0-fpm php8.0-xml -y --allow-unauthenticated
```
Verify the status of the PHP-FPM service by running
```bash
systemctl status php8.0-fpm
```
### Step 2. Install prerequisites
Install the ODBC driver for Ubuntu by following the instructions on the [Install the Microsoft ODBC driver for SQL Server (Linux)](https://docs.microsoft.com/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-ver15).

### Step 3. Install the PHP drivers for Microsoft SQL Server
```bash
sudo pecl config-set php_ini /etc/php/8.0/fpm/php.ini
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv
sudo su
printf "; priority=20\nextension=sqlsrv.so\n" > /etc/php/8.0/mods-available/sqlsrv.ini
printf "; priority=30\nextension=pdo_sqlsrv.so\n" > /etc/php/8.0/mods-available/pdo_sqlsrv.ini
exit
sudo phpenmod -v 8.0 sqlsrv pdo_sqlsrv
```
If there is only one PHP version in the system, then the last step can be simplified to `phpenmod sqlsrv pdo_sqlsrv`.

Verify that `sqlsrv.ini` and `pdo_sqlsrv.ini` are located in `/etc/php/8.0/fpm/conf.d/`:
```bash
ls /etc/php/8.0/fpm/conf.d/*sqlsrv.ini
```
Restart the PHP-FPM service:
```bash
sudo systemctl restart php8.0-fpm
```

### Step 4. Install and configure nginx
```bash
sudo apt-get update
sudo apt-get install nginx
sudo systemctl status nginx
```
To configure nginx, you must edit the `/etc/nginx/sites-available/default` file. Add `index.php` to the list below the section that says `# Add index.php to the list if you are using PHP`:
```
# Add index.php to the list if you are using PHP
index index.html index.htm index.nginx-debian.html index.php;
```
Next, uncomment and modify the section following `# pass PHP scripts to FastCGI server` as follows:
```
# pass PHP scripts to FastCGI server
#
location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.0-fpm.sock;
}
```
### Step 5. Restart nginx and test the sample script
```bash
sudo systemctl restart nginx.service
```
To test your installation, see [Testing your installation](#testing-your-installation) at the end of this document.

## Installing the drivers on Red Hat 7 and 8

### Step 1. Install PHP

To install PHP on Red Hat 7, run the following:
> [!NOTE]
> To install PHP 7.4 or 7.3, replace remi-php80 with remi-php74 or remi-php73 respectively in the following commands.
```bash
sudo su
yum install https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
yum install https://rpms.remirepo.net/enterprise/remi-release-7.rpm
subscription-manager repos --enable=rhel-7-server-optional-rpms
yum install yum-utils
yum-config-manager --enable remi-php80
yum update
# Note: The php-pdo package is required only for the PDO_SQLSRV driver
yum install php php-pdo php-xml php-pear php-devel re2c gcc-c++ gcc
```

To install PHP on Red Hat 8, run the following:
> [!NOTE]
> To install PHP 7.4 or 7.3, replace remi-8.0 with remi-7.4 or remi-7.3 respectively in the following commands.
```bash
sudo su
dnf install https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm
dnf install https://rpms.remirepo.net/enterprise/remi-release-8.rpm
dnf install yum-utils
dnf module reset php
dnf module install php:remi-8.0
subscription-manager repos --enable codeready-builder-for-rhel-8-x86_64-rpms
dnf update
# Note: The php-pdo package is required only for the PDO_SQLSRV driver
dnf install php-pdo php-pear php-devel
```

### Step 2. Install prerequisites
Install the ODBC driver for Red Hat 7 or 8 by following the instructions on the [Install the Microsoft ODBC driver for SQL Server (Linux)](https://docs.microsoft.com/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-ver15).

### Step 3. Install the PHP drivers for Microsoft SQL Server
```bash
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv
sudo su
echo extension=pdo_sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/30-pdo_sqlsrv.ini
echo extension=sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/20-sqlsrv.ini
exit
```

You can alternatively install from the Remi repo:
```bash
sudo yum install php-sqlsrv
```
### Step 4. Install Apache
```bash
sudo yum install httpd
```
SELinux is installed by default and runs in Enforcing mode. To allow Apache to connect to databases through SELinux, run the following command:
```bash
sudo setsebool -P httpd_can_network_connect_db 1
```
### Step 5. Restart Apache and test the sample script
```bash
sudo apachectl restart
```
To test your installation, see [Testing your installation](#testing-your-installation) at the end of this document.

## Installing the drivers on Debian 9 and 10

> [!NOTE]
> To install PHP 7.4 or 7.3, replace 8.0 in the following commands with 7.4 or 7.3.

### Step 1. Install PHP
```bash
sudo su
apt-get install curl apt-transport-https
wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
apt-get update
apt-get install -y php8.0 php8.0-dev php8.0-xml php8.0-intl
```
### Step 2. Install prerequisites
Install the ODBC driver for Debian by following the instructions on the [Install the Microsoft ODBC driver for SQL Server (Linux)](https://docs.microsoft.com/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-ver15). 

You may also need to generate the correct locale to get PHP output to display correctly in a browser. For example, for the en_US UTF-8 locale, run the following commands:
```bash
sudo su
sed -i 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/g' /etc/locale.gen
locale-gen
```
You may need to add `/usr/sbin` to your `$PATH`, as the `locale-gen` executable is located there.

### Step 3. Install the PHP drivers for Microsoft SQL Server
```bash
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv
sudo su
printf "; priority=20\nextension=sqlsrv.so\n" > /etc/php/8.0/mods-available/sqlsrv.ini
printf "; priority=30\nextension=pdo_sqlsrv.so\n" > /etc/php/8.0/mods-available/pdo_sqlsrv.ini
exit
sudo phpenmod -v 8.0 sqlsrv pdo_sqlsrv
```

If there is only one PHP version in the system, then the last step can be simplified to `phpenmod sqlsrv pdo_sqlsrv`. As with `locale-gen`, `phpenmod` is located in `/usr/sbin` so you may need to add this directory to your `$PATH`.

### Step 4. Install Apache and configure driver loading
```bash
sudo su
apt-get install libapache2-mod-php8.0 apache2
a2dismod mpm_event
a2enmod mpm_prefork
a2enmod php8.0
```
### Step 5. Restart Apache and test the sample script
```bash
sudo service apache2 restart
```
To test your installation, see [Testing your installation](#testing-your-installation) at the end of this document.

## Installing the drivers on Suse 12 and 15

> [!NOTE]
> In the following instructions, replace `<SuseVersion>` with your version of Suse - if you are using Suse Enterprise Linux 15, it will be SLE_15_SP1 or SLE_15_SP2. For Suse 12, use SLE_12_SP4 (or above if applicable). Not all versions of PHP are available for all versions of Suse Linux - please refer to `http://download.opensuse.org/repositories/devel:/languages:/php` to see which versions of Suse have the default version PHP available, or check `http://download.opensuse.org/repositories/devel:/languages:/php:/` to see which other versions of PHP are available for which versions of Suse.

> [!NOTE]
> Packages for PHP 7.4 or above are not available for Suse 12 and Package for PHP 8.0 is not yet available for Suse 15.
> To install PHP 7.3, replace the repository URL below with the following URL:
      `https://download.opensuse.org/repositories/devel:/languages:/php:/php73/<SuseVersion>/devel:languages:php:php73.repo`.

### Step 1. Install PHP
```bash
sudo su
zypper -n ar -f https://download.opensuse.org/repositories/devel:languages:php/<SuseVersion>/devel:languages:php.repo
zypper --gpg-auto-import-keys refresh
zypper -n install php7 php7-devel php7-openssl
```
### Step 2. Install prerequisites
Install the ODBC driver for Suse by following the instructions on the [Install the Microsoft ODBC driver for SQL Server (Linux)](https://docs.microsoft.com/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-ver15).

### Step 3. Install the PHP drivers for Microsoft SQL Server
> [!NOTE]
> If you get an error message saying `Connection to 'pecl.php.net:443' failed: Unable to find the socket transport "ssl"`, edit the pecl script at /usr/bin/pecl and remove the `-n` switch in the last line. This switch prevents PECL from loading ini files when PHP is called, which prevents the OpenSSL extension from loading.

```bash
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv
sudo su
echo extension=pdo_sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/pdo_sqlsrv.ini
echo extension=sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/sqlsrv.ini
exit
```
### Step 4. Install Apache and configure driver loading
```bash
sudo su
zypper install apache2 apache2-mod_php7
a2enmod php7
echo "extension=sqlsrv.so" >> /etc/php7/apache2/php.ini
echo "extension=pdo_sqlsrv.so" >> /etc/php7/apache2/php.ini
exit
```
### Step 5. Restart Apache and test the sample script
```bash
sudo systemctl restart apache2
```
To test your installation, see [Testing your installation](#testing-your-installation) at the end of this document.

## Installing the drivers on Alpine 3.11 and 3.12

> [!NOTE]
> The default version of PHP is 7.3. PHP 7.4 or above may be available from testing or edge repositories for Alpine. You can instead compile PHP from source.

### Step 1. Install PHP
PHP packages for Alpine can be found in the `edge/community` repository. Please check [Enable Community Repository](https://wiki.alpinelinux.org/wiki/Enable_Community_Repository) on their WIKI page. Add the following line to `/etc/apk/repositories`, replacing `<mirror>` with the URL of an Alpine repository mirror:
```bash
http://<mirror>/alpine/edge/community
```
Then run:
```bash
sudo su
apk update
# Note: The php7-pdo package is required only for the PDO_SQLSRV driver
apk add php7 php7-dev php7-pear php7-pdo php7-openssl autoconf make g++
```
### Step 2. Install prerequisites
Install the ODBC driver for Alpine by following the instructions on the [Install the Microsoft ODBC driver for SQL Server (Linux)](https://docs.microsoft.com/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-ver15). 

### Step 3. Install the PHP drivers for Microsoft SQL Server
```bash
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv
sudo su
echo extension=pdo_sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/10_pdo_sqlsrv.ini
echo extension=sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/00_sqlsrv.ini
```

### Step 4. Install Apache and configure driver loading
```bash
sudo apk add php7-apache2 apache2
```
### Step 5. Restart Apache and test the sample script
```bash
sudo rc-service apache2 restart
```
To test your installation, see [Testing your installation](#testing-your-installation) at the end of this document.


## Installing the drivers on macOS Mojave, Catalina and Big Sur

If you do not already have it, install brew as follows:
```bash
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
```

> [!NOTE]
> To install PHP 7.4 or 7.3, replace php@8.0 with php@7.4 or php@7.3 respectively in the following commands.

### Step 1. Install PHP

```bash
brew tap
brew tap homebrew/core
brew install php@8.0
```
PHP should now be in your path. Run `php -v` to verify that you are running the correct version of PHP. If PHP is not in your path or it is not the correct version, run the following:
```bash
brew link --force --overwrite php@8.0
```

### Step 2. Install prerequisites
Install the ODBC driver for macOS by following the instructions on the [Install the Microsoft ODBC driver for SQL Server (macOS)](
https://docs.microsoft.com/sql/connect/odbc/linux-mac/install-microsoft-odbc-driver-sql-server-macos?view=sql-server-ver15). 

In addition, you may need to install the GNU make tools:
```bash
brew install autoconf automake libtool
```

### Step 3. Install the PHP drivers for Microsoft SQL Server
```bash
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv
```
### Step 4. Install Apache and configure driver loading
```bash
brew install apache2
```
To find the Apache configuration file, `httpd.conf`, for your Apache installation, run 
```bash
/usr/local/bin/apachectl -V | grep SERVER_CONFIG_FILE
``` 
The following commands append the required configuration to `httpd.conf`. Be sure to substitute the path returned by the preceding command in place of `/usr/local/etc/httpd/httpd.conf`:
```bash
echo "LoadModule php7_module /usr/local/opt/php@8.0/lib/httpd/modules/libphp7.so" >> /usr/local/etc/httpd/httpd.conf
(echo "<FilesMatch .php$>"; echo "SetHandler application/x-httpd-php"; echo "</FilesMatch>";) >> /usr/local/etc/httpd/httpd.conf
```
### Step 5. Restart Apache and test the sample script
```bash
sudo apachectl restart
```
To test your installation, see [Testing your installation](#testing-your-installation) at the end of this document.

## Testing Your Installation

To test this sample script, create a file called testsql.php in your system's document root. This is `/var/www/html/` on Ubuntu, Debian, and Redhat, `/srv/www/htdocs` on SUSE, `/var/www/localhost/htdocs` on Alpine, or `/usr/local/var/www` on macOS. Copy the following script to it, replacing the server, database, username, and password as appropriate.

### SQLSRV example

```php
<?php
$serverName = "yourServername";
$connectionOptions = array(
    "database" => "yourDatabase",
    "uid" => "yourUsername",
    "pwd" => "yourPassword"
);

function exception_handler($exception) {
    echo "<h1>Failure</h1>";
    echo "Uncaught exception: " , $exception->getMessage();
    echo "<h1>PHP Info for troubleshooting</h1>";
    phpinfo();
}

set_exception_handler('exception_handler');

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

<h1> Success Results : </h1>

<?php
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo $row['SQL_VERSION'] . PHP_EOL;
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

function formatErrors($errors)
{
    // Display errors
    echo "<h1>SQL Error:</h1>";
    echo "Error information: <br/>";
    foreach ($errors as $error) {
        echo "SQLSTATE: ". $error['SQLSTATE'] . "<br/>";
        echo "Code: ". $error['code'] . "<br/>";
        echo "Message: ". $error['message'] . "<br/>";
    }
}
?>
```

### PDO_SQLSRV example

```php
<?php
try {
    $serverName = "yourServername";
    $databaseName = "yourDatabase";
    $uid = "yourUsername";
    $pwd = "yourPassword";
    
    $conn = new PDO("sqlsrv:server = $serverName; Database = $databaseName;", $uid, $pwd);

    // Select Query
    $tsql = "SELECT @@Version AS SQL_VERSION";

    // Executes the query
    $stmt = $conn->query($tsql);
} catch (PDOException $exception1) {
    echo "<h1>Caught PDO exception:</h1>";
    echo $exception1->getMessage() . PHP_EOL;
    echo "<h1>PHP Info for troubleshooting</h1>";
    phpinfo();
}

?>

<h1> Success Results : </h1>

<?php
try {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['SQL_VERSION'] . PHP_EOL;
    }
} catch (PDOException $exception2) {
    // Display errors
    echo "<h1>Caught PDO exception:</h1>";
    echo $exception2->getMessage() . PHP_EOL;
}

unset($stmt);
unset($conn);
?>
```

Point your browser to https://localhost/testsql.php (https://localhost:8080/testsql.php on macOS). You should now be able to connect to your SQL Server/Azure SQL database. If you don't see a success message showing SQL version information, you can do some basic troubleshooting by running the script from the command line:

```bash
php testsql.php
```

If running from the command line is successful but nothing shows in your browser, check the [Apache log files](https://linuxize.com/post/apache-log-files/#location-of-the-log-files). For additional help, see [Support resources](support-resources-for-the-php-sql-driver.md) for places to go.


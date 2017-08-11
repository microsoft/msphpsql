#!/bin/bash
set -e
if [[ ("$1" = "Ubuntu16" || "$1" = "RedHat7" || "$1" = "Sierra") ]]; then
    PLATFORM=$1
else
    echo "First argument must be one of Ubuntu16, RedHat7, Sierra. Exiting..."
    exit 1
fi
if [[ "$2" != 7.*.* ]]; then
    echo "Second argument must be PHP version in format of 7.x.x.Exiting..."
    exit
else
    PHP_VERSION=$2
fi
if [[ ("$3" != "nts" && "$3" != "ts") ]]; then
    echo "Thrid argument must be either nts or ts. Exiting..."
    exit 1
else
    PHP_THREAD=$3
fi
if [[ (! -d "$4") || (! -d $4/sqlsrv) || (! -d $4/pdo_sqlsrv) || (! -d $4/shared) ]]; then
    echo "Fourth argument must be path to source folder.Path not found.Exiting..."
    exit 1
else
    DRIVER_SOURCE_PATH=$4
fi
rm -rf env_setup.log
touch env_setup.log
if [ $PLATFORM = "Ubuntu16" ]; then
    echo "Update..."
    yes | sudo dpkg --configure -a >> env_setup.log 2>&1
    yes | sudo apt-get update >> env_setup.log 2>&1
    echo "Installing git, zip, curl, libxml, autoconf, openssl, python3, pip3..."
    yes | sudo apt-get install git zip curl autoconf libxml2-dev libssl-dev pkg-config python3 python3-pip >> env_setup.log 2>&1  
    echo "OK"
    echo "Installing MSODBCSQL..."
    curl -s https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
    curl -s https://packages.microsoft.com/config/ubuntu/16.04/prod.list > /etc/apt/sources.list.d/mssql-release.list
    yes | sudo apt-get update >> env_setup.log 2>&1
    yes | sudo ACCEPT_EULA=Y apt-get install msodbcsql >> env_setup.log 2>&1
    yes | sudo apt-get install -qq unixodbc-dev >> env_setup.log 2>&1
    echo "Installing pyodbc"
    pip3 install --upgrade pip >> env_setup.log 2>&1
    pip3 install pyodbc >> env_setup.log 2>&1
    echo "OK"
elif [ $PLATFORM = "RedHat7" ]; then
    echo "Update..."
    yes | sudo yum update >> env_setup.log 2>&1
    echo "OK"
    echo "Enabling EPEL repo..."
    wget https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm >> env_setup.log 2>&1
    yes | sudo yum install epel-release-latest-7.noarch.rpm >> env_setup.log 2>&1 || true
    echo "OK"
    echo "Installing python34-setuptools..."
    yes | sudo yum install python34-setuptools -y >> env_setup.log 2>&1
    echo "OK"
    echo "Installing gcc, git, zip libxml, openssl, EPEL, python3, pip3..."
    yes | sudo yum install -y gcc-c++ libxml2-devel git zip openssl-devel python34 python34-devel python34-pip >> env_setup.log 2>&1
    echo "OK"
    echo "Installing MSODBCSQL..."
    curl -s https://packages.microsoft.com/config/rhel/7/prod.repo > /etc/yum.repos.d/mssql-release.repo
    (yes | sudo ACCEPT_EULA=Y yum install -y msodbcsql >> env_setup.log 2>&1)
    (yes | sudo yum install -y unixODBC-devel autoconf >> env_setup.log 2>&1)
    echo "OK"
    echo "Installing pyodbc"
    pip3 install --upgrade pip >> env_setup.log 2>&1
    pip3 install pyodbc >> env_setup.log 2>&1
    echo "OK"
elif [ $PLATFORM = "Sierra" ]; then
    echo "Installing homebrew..."
    yes | /usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)" >> env_setup.log 2>&1
    echo "OK"
    echo "Installing wget..."
    brew install wget >> env_setup.log 2>&1
    echo "OK"
    echo "Installing svn..."
    brew install svn >> env_setup.log 2>&1
    echo "OK"
    echo "Installing openssl..."
    brew install pkg-config >> env_setup.log 2>&1
    brew install openssl >> env_setup.log 2>&1
    echo "OK"
    echo "Installing python3..."
    brew install python3 >> env_setup.log 2>&1
    echo "OK"
    echo "Installing MSODBCSQL..."
    brew tap microsoft/msodbcsql https://github.com/Microsoft/homebrew-msodbcsql >> env_setup.log 2>&1
    brew update >> env_setup.log 2>&1
    yes | ACCEPT_EULA=Y brew install --no-sandbox msodbcsql >> env_setup.log 2>&1
    echo "OK"
    yes | brew install autoconf >> env_setup.log 2>&1
    echo "Installing pyodbc..."
    pip3 install pyodbc >> env_setup.log 2>&1
    echo "OK"
fi
echo "Downloading PHP-$PHP_VERSION source tarball..."
wget http://ca1.php.net/get/php-$PHP_VERSION.tar.gz/from/this/mirror -O php-$PHP_VERSION.tar.gz >> env_setup.log 2>&1
echo "OK"
echo "Extracting PHP source tarball..."
rm -rf php-$PHP_VERSION
tar -xf php-$PHP_VERSION.tar.gz
echo "OK"
cd php-$PHP_VERSION
mkdir ext/sqlsrv ext/pdo_sqlsrv
cp -r $DRIVER_SOURCE_PATH/sqlsrv/* $DRIVER_SOURCE_PATH/shared ext/sqlsrv
cp -r $DRIVER_SOURCE_PATH/pdo_sqlsrv/* $DRIVER_SOURCE_PATH/shared ext/pdo_sqlsrv
./buildconf --force >> ../env_setup.log 2>&1
CONFIG_OPTIONS="--enable-cli --enable-cgi --enable-pdo --enable-sqlsrv=shared --with-pdo_sqlsrv=shared --with-odbcver=0x0380 --with-zlib --enable-mbstring --prefix=/usr/local"
[ "${PHP_THREAD}" == "ts" ] && CONFIG_OPTIONS=${CONFIG_OPTIONS}" --enable-maintainer-zts"
if [ $PLATFORM = "Sierra" ]; then
    CONFIG_OPTIONS=$CONFIG_OPTIONS" --with-openssl=/usr/local/opt/openssl/"
else
    CONFIG_OPTIONS=$CONFIG_OPTIONS" --with-openssl"
fi
echo "Configuring PHP..."
(./configure $CONFIG_OPTIONS >> ../env_setup.log 2>&1)
echo "OK"
echo "Compiling PHP and the drivers..."
make >> ../env_setup.log 2>&1
echo "OK"
sudo make install >> ../env_setup.log 2>&1
cp php.ini-production php.ini
echo "extension=sqlsrv.so" >> php.ini
echo "extension=pdo_sqlsrv.so" >> php.ini
sudo cp php.ini /usr/local/lib
cd ..
php -v
php --ri sqlsrv
php --ri pdo_sqlsrv
echo "Installing Composer..."
wget https://getcomposer.org/installer -O composer-setup.php >> env_setup.log 2>&1
php composer-setup.php >> env_setup.log 2>&1
echo "OK"
echo "Installing PHPBench..."
php composer.phar install >> env_setup.log 2>&1
echo "OK"
echo "Cleaning up..."
rm -rf php-$PHP_VERSION* compser-setup.php
echo "OK"
echo "Setup completed!"
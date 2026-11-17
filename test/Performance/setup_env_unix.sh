#!/bin/bash

set -e

if [[ ("$1" = "Ubuntu16" || "$1" = "RedHat7" || "$1" = "SUSE12" || "$1" = "Sierra") ]]; then
    PLATFORM=$1
else
    echo "1st argument must be one of Ubuntu16, RedHat7, SUSE12, Sierra. Exiting..."
    exit 1
fi

if [[ "$2" != 7.*.* ]]; then
    echo "2nd argument must be PHP version in format of 7.x.y. Exiting..."
    exit
else
    PHP_VERSION=$2
fi

if [[ ("$3" != "nts" && "$3" != "ts") ]]; then
    echo "3rd argument must be either nts or ts. Exiting..."
    exit 1
else
    PHP_THREAD=$3
fi

if [[ (! -f "$4") || (! -f "$5") ]]; then
    echo "4th and 5th argument must be paths to sqlsrv and pdo drivers. Exiting..."
    exit 1
else
    SQLSRV_DRIVER=$4
    PDO_DRIVER=$5
fi

if [ $PLATFORM = "Ubuntu16" ]; then
    printf "Update..."
    yes | sudo dpkg --configure -a > env_setup.log
    yes | sudo apt-get update >> env_setup.log
    printf "done\n"

    printf "Installing git, zip, curl, libxml, autoconf, openssl, python3, pip3..."
    yes | sudo apt-get install git zip curl autoconf libxml2-dev libssl-dev pkg-config python3 python3-pip >> env_setup.log
    printf "done\n"

    printf "Installing MSODBCSQL..."
    curl -s https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
    curl -s https://packages.microsoft.com/config/ubuntu/16.04/prod.list > /etc/apt/sources.list.d/mssql-release.list
    yes | sudo apt-get update >> env_setup.log
    yes | sudo ACCEPT_EULA=Y apt-get install msodbcsql >> env_setup.log
    yes | sudo apt-get install -qq unixodbc-dev >> env_setup.log
    printf "done\n"

    printf "Installing pyodbc..."
    pip3 install --upgrade pip >> env_setup.log
    pip3 install pyodbc >> env_setup.log
    printf "done\n"

elif [ $PLATFORM = "RedHat7" ]; then
    printf "Update..."
    yes | sudo yum update >> env_setup.log
    printf "done\n"

    printf "Enabling EPEL repo..."
#   pipe non-error to log file (wget and yum install reports error when there's nothing to do)
    wget https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm >> env_setup.log
    yes | sudo yum install epel-release-latest-7.noarch.rpm >> env_setup.log || true
    printf "done\n"

    printf "Installing python34-setuptools..."
    yes | sudo yum install python34-setuptools -y >> env_setup.log
    printf "done\n"

    printf "Installing gcc, git, zip libxml, openssl, EPEL, python3, pip3..."
    yes | sudo yum install -y gcc-c++ libxml2-devel git zip openssl-devel python34 python34-devel python34-pip >> env_setup.log
    printf "done\n"

    printf "Installing MSODBCSQL..."
    curl -s https://packages.microsoft.com/config/rhel/7/prod.repo > /etc/yum.repos.d/mssql-release.repo
    (yes | sudo ACCEPT_EULA=Y yum install -y msodbcsql >> env_setup.log)
    (yes | sudo yum install -y unixODBC-devel autoconf >> env_setup.log)
    printf "done\n"

    printf "Installing pyodbc..."
    pip3 install --upgrade pip >> env_setup.log
    pip3 install pyodbc >> env_setup.log
    printf "done\n"
    
elif [ $PLATFORM = "SUSE12" ]; then
    printf "Update..."
    sudo zypper refresh >> env_setup.log
    printf "done\n"
    
    printf "Installing autoconf, gcc, g++, git, zip, libxml, openssl, python3..."
    sudo zypper -n install autoconf gcc gcc-c++ libxml2-devel git zip libopenssl-devel python3-devel python3-setuptools >> env_setup.log
    printf "done\n"
    
    printf "Installing MSODBCSQL..."
    zypper -n ar https://packages.microsoft.com/config/sles/12/prod.repo
    zypper --gpg-auto-import-keys refresh
    ACCEPT_EULA=Y zypper -n install msodbcsql >> env_setup.log
    ACCEPT_EULA=Y zypper -n install mssql-tools >> env_setup.log
    zypper -n install unixODBC-devel >> env_setup.log
    printf "done\n"
    
    printf "Installing pyodbc..."
    wget https://github.com/mkleehammer/pyodbc/archive/master.zip
    unzip master.zip
    cd pyodbc-master/
    python3 setup.py build >> env_setup.log
    sudo python3 setup.py install >> env_setup.log
    printf "done\n"

elif [ $PLATFORM = "Sierra" ]; then
    printf "Installing homebrew..."
    yes | /usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)" >> env_setup.log
    printf "done\n"

    printf "Installing wget..."
    brew install wget >> env_setup.log
    printf "done\n"

    printf "Installing svn..."
    brew install svn >> env_setup.log
    printf "done\n"

    printf "Installing openssl..."
    brew install pkg-config >> env_setup.log
    brew install openssl >> env_setup.log
    printf "done\n"

    printf "Installing python3..."
    brew install python3 >> env_setup.log
    printf "done\n"

    printf "Installing MSODBCSQL..."
    brew tap microsoft/msodbcsql https://github.com/Microsoft/homebrew-msodbcsql >> env_setup.log
    brew update >> env_setup.log
    yes | ACCEPT_EULA=Y brew install --no-sandbox msodbcsql >> env_setup.log
    printf "done\n"

    yes | brew install autoconf >> env_setup.log
    printf "Installing pyodbc..."
    pip3 install pyodbc >> env_setup.log
    printf "done\n"
fi

printf "Downloading PHP-$PHP_VERSION source tarball..."
#   pipe non-error to log file
wget http://ca1.php.net/get/php-$PHP_VERSION.tar.gz/from/this/mirror -O php-$PHP_VERSION.tar.gz >> env_setup.log
printf "done\n"

printf "Extracting PHP source tarball..."
rm -rf php-$PHP_VERSION
tar -xf php-$PHP_VERSION.tar.gz
printf "done\n"

phpDir=php-$PHP_VERSION
cd $phpDir

printf "Configuring PHP..."
./buildconf --force >> ../env_setup.log
CONFIG_OPTIONS="--enable-cli --enable-cgi --with-zlib --enable-mbstring --prefix=/usr/local"
[ "${PHP_THREAD}" == "ts" ] && CONFIG_OPTIONS=${CONFIG_OPTIONS}" --enable-maintainer-zts"
if [ $PLATFORM = "Sierra" ]; then
    CONFIG_OPTIONS=$CONFIG_OPTIONS" --with-openssl=/usr/local/opt/openssl/"
else
    CONFIG_OPTIONS=$CONFIG_OPTIONS" --with-openssl"
fi
#pipe non-error to log file
(./configure $CONFIG_OPTIONS >> ../env_setup.log)
printf "done\n"

printf "Compiling and installing PHP..."
make >> ../env_setup.log
sudo make install >> ../env_setup.log
printf "done\n"

# check PHP version
/usr/local/bin/php -v

printf "Setting up drivers..."
phpExtDir=`/usr/local/bin/php-config --extension-dir`
cp php.ini-production php.ini
driverName=$(basename $SQLSRV_DRIVER)
echo "extension=$driverName" >> php.ini
sudo cp -r $SQLSRV_DRIVER $phpExtDir/$driverName
sudo chmod a+r $phpExtDir/$driverName

driverName=$(basename $PDO_DRIVER)
echo "extension=$driverName" >> php.ini
sudo cp -r $PDO_DRIVER $phpExtDir/$driverName
sudo chmod a+r $phpExtDir/$driverName

sudo cp php.ini /usr/local/lib
printf "done\n"

# check drivers
/usr/local/bin/php --ri sqlsrv
/usr/local/bin/php --ri pdo_sqlsrv

printf "Installing Composer..."
cd ..
#   pipe non-error to log file
wget https://getcomposer.org/installer -O composer-setup.php >> env_setup.log
/usr/local/bin/php composer-setup.php >> env_setup.log
printf "done\n"

printf "Installing PHPBench...\n"
/usr/local/bin/php composer.phar install >> env_setup.log
printf "done\n"

printf "Cleaning up..."
rm -rf $phpDir compser-setup.php
printf "done\n"

echo "Setup completed!"

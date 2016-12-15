#!/bin/bash
sudo apt-get update
rm -rf /tmp/msodbcubuntu
mkdir /tmp/msodbcubuntu
sudo wget ftp://ftp.unixodbc.org/pub/unixODBC/unixODBC-2.3.1.tar.gz -P /tmp/msodbcubuntu/
cd /tmp/msodbcubuntu
sudo tar -xzf /tmp/msodbcubuntu/unixODBC-2.3.1.tar.gz
cd /tmp/msodbcubuntu/unixODBC-2.3.1/
sudo -i export CPPFLAGS="-DSIZEOF_LONG_INT=8"
sudo apt-get -y install g++-5
echo "Configuring the unixODBC 2.3.1 Driver Manager"
./configure --prefix=/usr --libdir=/usr/lib --sysconfdir=/etc --disable-gui --disable-drivers --enable-iconv --with-iconv-char-enc=UTF8 --with-iconv-ucode-enc=UTF16LE 1> odbc_con.log 2> make_err.log
echo "Building and Installing the unixODBC 2.3.1 Driver Manager"
sudo make 1> make_std.log 2> make_err.log
sudo make install 1> makeinstall_std.log 2> makeinstall_err.log


echo "Downloading the Microsoft ODBC Driver 13 for SQL Server- Ubuntu"
sudo wget -O /tmp/msodbcubuntu/msodbcsql-13.0.0.0.tar.gz "https://meetsstorenew.blob.core.windows.net/contianerhd/Ubuntu%2013.0%20Tar/msodbcsql-13.0.0.0.tar.gz?st=2016-10-18T17%3A29%3A00Z&se=2022-10-19T17%3A29%3A00Z&sp=rl&sv=2015-04-05&sr=b&sig=cDwPfrouVeIQf0vi%2BnKt%2BzX8Z8caIYvRCmicDL5oknY%3D"
cd /tmp/msodbcubuntu/
tar xvfz /tmp/msodbcubuntu/msodbcsql-13.0.0.0.tar.gz
cd /tmp/msodbcubuntu/msodbcsql-13.0.0.0/
ldd /tmp/msodbcubuntu/msodbcsql-13.0.0.0/lib64/libmsodbcsql-13.0.so.0.0
echo "Installing Dependencies"
sudo apt-get -y install libssl1.0.0
sudo apt-get -y install libgss3
sudo sh -c "echo '/usr/lib' >> /etc/ld.so.conf"
sudo ldconfig
sudo ldconfig -p | grep odbc
echo "Installing the Microsoft ODBC Driver 13 for SQL Server- Ubuntu"
sudo bash ./install.sh install --force --accept-license
echo "Cleaning up"
sudo rm -rf /tmp/msodbcubuntu

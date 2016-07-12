cleanup ()
{ # This is about as simple as functions get.
	sudo rm -rf /tmp/msodbcrhel
	sudo rm -rf /usr/lib64/msodbcsql-13.0.0.0*
} # Function declaration must precede call.

mkdir /tmp/msodbcrhel

sudo wget ftp://ftp.unixodbc.org/pub/unixODBC/unixODBC-2.3.1.tar.gz -P /tmp/msodbcrhel/
sudo tar -xzf /tmp/msodbcrhel/unixODBC-2.3.1.tar.gz
if [ -d "usr/lib64/libodbc*" ]; then
	echo "exists"
	sudo rm /usr/lib64/libodbc*
fi

cd /tmp/msodbcrhel/
sudo tar -xzf unixODBC-2.3.1.tar.gz
cd /tmp/msodbcrhel/unixODBC-2.3.1
sudo -i export CPPFLAGS="-DSIZEOF_LONG_INT=8"
if sudo yum install gcc; 
then 
	echo "Succesfuly installed Yum Dependencies" 
else
        echo "Package installation failed. Script will now exit and cleanup"
	cleanup
	exit
fi

sudo ./configure --prefix=/usr --libdir=/usr/lib64 --sysconfdir=/etc --enable-gui=no --enable-drivers=no --enable-iconv --with-iconv-char-enc=UTF8 --with-iconv-ucode-enc=UTF16LE --enable-stats=no 1> configure_std.log 2> configure_err.log

if sudo make 1> make_std.log 2> make_err.log ; 
then 
	echo "Installing unixODBC 2.3.1" 
else
        echo "unixODBC failed. Script will cleanup"
	cleanup
	exit
fi
if sudo make install 1> makeinstall_std.log 2> makeinstall_err.log ; 
then 
	echo "Successfuly installed unixODBC 2.3.1" 
else
        echo "unixODBC failed. Script will cleanup"
	cleanup
	exit
fi


cd /usr/lib64
sudo ln -s libodbccr.so.2   libodbccr.so.1
sudo ln -s libodbcinst.so.2 libodbcinst.so.1
sudo ln -s libodbc.so.2     libodbc.so.1

cd /tmp/msodbcrhel/

cd usr/lib64
if sudo wget https://download.microsoft.com/download/B/C/D/BCDD264C-7517-4B7D-8159-C99FC5535680/msodbcsql-13.0.0.0.tar.gz ; 
then 
	echo "Successfuly download the ODBC Driver and tools." 
else
        echo "Unable to get Microsfot ODBC Driver from download center."
	cleanup
	exit
fi


if sudo tar xvzf msodbcsql-13.0.0.0.tar.gz ;
then
	echo "Successfuly unpackaged the Microsoft ODBC Driver 13 tar.gz"

else
	echo "Unable to get Microsfot ODBC Driver from download center."
	cleanup
	exit	
fi

cd msodbcsql-13.0.0.0

if sudo ./install.sh install --accept-license --force ;
then
	echo "Successfuly installed the Microsoft ODBC Driver"
else
	echo "Unable to install the Microsoft ODBC Driver."
	cleanup
	exit
fi

if wget https://gallery.technet.microsoft.com/Tools-wget-1655337a/file/153986/1/mssql-tools-13.0.0.0.tar.gz -P /tmp/msodbcrhel/
then
	echo "Successfuly downloaded sqlcmd and bcp"
else
	echo "Unable to download sqlcmd and bcp"
	cleanup
	exit
fi


cd /tmp/msodbcrhel/

if sudo tar -xzvf /tmp/msodbcrhel/mssql-tools-13.0.0.0.tar.gz
then
	echo "Succesfuly unpacked sqlcmd and bcp"
else
	echo "Unable to unpack the tools"
	cleanup
	exit
fi

cd /tmp/msodbcrhel/mssql-tools-13.0.0.0

if sudo ./setup.sh remove ;
then
	echo "Cleaning up SQLCMD and BCP"
else
	echo "Unable to install SQLCMD and BCP."
	cleanup
	exit
fi

if sudo ./setup.sh install --accept-license --force ;
then
	echo "Successfuly installed SQLCMD and BCP"
else
	echo "Unable to install SQLCMD and BCP."
	cleanup
	exit
fi

cleanup

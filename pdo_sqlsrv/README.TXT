* Copyright and License Information *

Copyright Microsoft Corporation

Licensed under the Apache License, Version 2.0 (the "License"); you
may not use this file except in compliance with the License.

You may obtain a copy of the License at:
http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
implied.  See the License for the specific language governing
permissions and limitations under the License.

* Notes about changes to the Microsoft Drivers 3.0 for PHP for SQL Server *

For details about the changes included in this release, please see our blog at
http://blogs.msdn.com/sqlphp or see the SQLSRV_Readme.htm 
file that is part of the download package.

* Notes about compiling the Microsoft Drivers 3.0 for PHP for SQL Server *

Prerequisites: 

* You must first be able to build PHP without including these
extensions.  For help with doing this, see the official PHP website,
http://php.net.

To compile the SQLSRV301 and PDO_SQLSRV301:

1) Copy the source code directories from this repository into the ext
subdirectory.

2) run buildconf.bat to rebuild the configure.js script to include the
new drivers.

3) run "cscript configure.js --enable-sqlsrv=shared --enable-pdo
--with-pdo-sqlsrv=shared <other options>" to generate the makefile.
Run "cscript configure.js --help" to see what other options are
available.  It is possible (and even probable) that other extensions
will have to be disabled or enabled for the compile to succeed.
Search bing.com for configurations that have worked for other people.

   3a) It might be possible to compile these extensions as non-shared
   but that configuration has not been tested.

4) run "nmake".  It is suggested that you run the entire build.  If you
wish to do so, run "nmake clean" first.
 
5) To install the resulting build, run "nmake install" or just copy
php_sqlsrv.dll and php_pdo_sqlsrv.dll to your PHP extension directory.
Also enable them within your PHP installation's php.ini file.

This software has been compiled and tested under PHP 5.3.6 and later
using the Visual C++ 2008 and 2010, Express and Standard compilers.

* Note about version.h *

The version numbers in version.h in the source do not match the
version numbers in the supported PHP extension.

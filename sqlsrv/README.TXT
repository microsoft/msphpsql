* Copyright and License Information *

Copyright 2010 Microsoft Corporation

Licensed under the Apache License, Version 2.0 (the "License"); you
may not use this file except in compliance with the License.

You may obtain a copy of the License at:
http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
implied.  See the License for the specific language governing
permissions and limitations under the License.

* Notes about changes to the Microsoft Drivers for PHP for SQL Server
  Driver 2.0 *

For details about the changes included in this release, please see our blog at
http://blogs.msdn.com/sqlphp or see the SQLSRV20_Readme.htm 
file that is part of the download package.

* Notes about compiling the Microsoft Drivers for PHP for SQL Server 2.0 *

Prerequisites: 

* You must first be able to build PHP without including these
extensions.  For help with doing this, see xthe official PHP website,
http://php.net.

* The header file sqlncli.h is required to build these extensions.  It
may be installed with the Microsoft SQL Server® 2008 R2 Native Client 
(found at Microsoft.com)

To compile the SQLSRV20 and PDO_SQLSRV20:

1) Copy the source code directories from this repository into the ext
subdirectory.

   1a) Make sure that sqlncli.h is either on the INCLUDE file path or 
   copied locally to the extension directories.

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

This software has been compiled and tested under PHP 5.2.10 and PHP 5.3.0
using the Visual C++ 2008 Express and Standard compilers.

* Note about version.h *

The version numbers in version.h in the source do not match the
version numbers in the supported PHP extension.

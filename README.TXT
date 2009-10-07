
* Notes about changes to the SQL Server Driver for PHP 1.1 October 2009 *

For details about the changes included in this release, please see our blog at
http://blogs.msdn.com/sqlphp or see the SQLServerDriverForPHP_Readme.htm 
file that is part of the download package.

* Notes about compiling the SQL Server Driver for PHP 1.1 October 2009 *

Prerequisites: You must first be able to build PHP without including
the SQL Server Driver for PHP extension.  For help with doing this, see 
the official PHP website, http://php.net.

To compile the SQL Server Driver for PHP:

1) Copy the source code into the ext\sqlsrv directory.

2) run buildconf.bat

3) run "cscript configure.js --enable-sqlsrv=shared <other options>" to
generate the makefile.  Run "cscript configure.js --help" to see what other
options are available.  It is possible (and even probable) that other
extensions will have to be disabled or enabled for the compile to succeed.
Search live.com for configurations that have worked for other people.

   3a) It might be possible to compile with just --enable-sqlsrv rather
   than --enable-sqlsrv=shared, but that configuration has not been tested.

4) run "nmake".  It is suggested that you run the entire build.  If you
wish to do so, run "nmake clean" first.
 
5) To install the resulting build, run "nmake install" or just copy
php_sqlsrv.dll to your PHP extension directory.

This software has been compiled and tested under PHP 5.2.11 and PHP 5.3.0
using the Visual C++ 2008 Express and Standard compilers.

This software is released under the Microsoft Public License.  A copy of
the license agreement may be found online at
http://www.codeplex.com/SQLSRVPHP/license.

* Note about version.h *

The version numbers in version.h in the source do not match the version numbers
in the supported PHP extension.

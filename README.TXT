
*Notes on compiling SQL Server 2005 Driver for PHP*

Prerequisites: You must first be able to build PHP not including
this extension.  For help on doing so, see the official PHP website,
http://php.net.

To compile SQL Server 2005 Driver for PHP:

1) Copy the source code into the ext\sqlsrv directory.

2) run buildconf.bat

3) run "cscript configure.js --enable-sqlsrv=shared <other options>" to
generate the makefile.  run "cscript configure.js --help" to see what other
options are available.  It is possible (and even probable) that other
extensions will have to be disabled or enabled for the compile to succeed.
Search live.com for configurations that have worked for other people.

   3a) It might be possible to compile with just --enable-sqlsrv rather
   than --enable-sqlsrv=shared, but that configuration has not been tested.

4) run "nmake".  It is suggested that you run the entire build.  If you
wish to do so, run "nmake clean" first.
 
5) To install the resulting build, run "nmake install" or just copy
php_sqlsrv.dll to your PHP extension directory.

This software has been compiled and tested under PHP 5.2.6 and PHP 5.2.4 
using the Visual C++ 2005 Express and Visual C++ 2008 Standard compilers.

This software is released under the Microsoft Public License.  A copy of
the license agreement may be found online at
http://www.codeplex.com/SQL2K5PHP/license.

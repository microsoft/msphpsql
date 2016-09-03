dnl
dnl $Id$
dnl

PHP_ARG_ENABLE(sqlsrv, whether to enable sqlsrv functions,
[  --disable-sqlsrv         Disable sqlsrv functions], yes)

if test "$PHP_SQLSRV" != "no"; then
  PHP_REQUIRE_CXX()
  PHP_SUBST(SQLSRV_SHARED_LIBADD)
  PHP_ADD_LIBRARY(stdc++, 1, SQLSRV_SHARED_LIBADD)
  PHP_ADD_LIBRARY(odbc, 1, SQLSRV_SHARED_LIBADD)
  AC_DEFINE(HAVE_SQLSRV, 1, [ ])
  PHP_NEW_EXTENSION(sqlsrv, conn.cpp stmt.cpp init.cpp util.cpp \
							core_init.cpp core_conn.cpp core_stmt.cpp \
							core_util.cpp core_stream.cpp core_results.cpp \
							FormattedPrint.cpp localizationimpl.cpp, $ext_shared,,-std=c++11)
fi

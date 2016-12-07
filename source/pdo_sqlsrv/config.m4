PHP_ARG_WITH(pdo_sqlsrv, for pdo_sqlsrv support,
[  --with-pdo_sqlsrv             Include pdo_sqlsrv support])

if test "$PHP_PDO_SQLSRV" != "no"; then
  if test "$PHP_PDO" = "no" && test "$ext_shared" = "no"; then
    AC_MSG_ERROR([PDO is not enabled! Add --enable-pdo to your configure line.])
  fi

  ifdef([PHP_CHECK_PDO_INCLUDES],
  [
    PHP_CHECK_PDO_INCLUDES
  ],[
    AC_MSG_CHECKING([for PDO includes])
    if test -f $abs_srcdir/include/php/ext/pdo/php_pdo_driver.h; then
      pdo_cv_inc_path=$abs_srcdir/ext
    elif test -f $abs_srcdir/ext/pdo/php_pdo_driver.h; then
      pdo_cv_inc_path=$abs_srcdir/ext
    elif test -f $phpincludedir/ext/pdo/php_pdo_driver.h; then
      pdo_cv_inc_path=$phpincludedir/ext
    else
      AC_MSG_ERROR([Cannot find php_pdo_driver.h.])
    fi
    AC_MSG_RESULT($pdo_cv_inc_path)
  ])

  pdo_sqlsrv_src_class="\
           pdo_dbh.cpp \
           pdo_parser.cpp \  
           pdo_util.cpp \
           pdo_init.cpp  \
           pdo_stmt.cpp  \
           "
    
  shared_src_class="\
           shared/core_conn.cpp \
           shared/core_results.cpp \
           shared/core_stream.cpp \
           shared/core_init.cpp \ 
           shared/core_stmt.cpp \
           shared/core_util.cpp \
           shared/FormattedPrint.cpp \
           shared/localizationimpl.cpp \
           shared/StringFunctions.cpp \
           "
  AC_MSG_CHECKING([for PDO_SQLSRV headers])
  if test -f $srcdir/ext/pdo_sqlsrv/shared/core_sqlsrv.h; then
    pdo_sqlsrv_inc_path=$srcdir/ext/pdo_sqlsrv/shared/
  elif  test -f $srcdir/shared/core_sqlsrv.h; then
    pdo_sqlsrv_inc_path=$srcdir/shared/
  else  
    AC_MSG_ERROR([Cannot find PDO_SQLSRV headers])
  fi
    AC_MSG_RESULT($pdo_sqlsrv_inc_path)
    
           
  CXXFLAGS="$CXXFLAGS -std=c++11"
  PHP_REQUIRE_CXX()
  PHP_ADD_LIBRARY(stdc++, 1, PDO_SQLSRV_SHARED_LIBADD)
  PHP_ADD_LIBRARY(odbc, 1, PDO_SQLSRV_SHARED_LIBADD)
  AC_DEFINE(HAVE_PDO_SQLSRV, 1, [ ])
  PHP_ADD_INCLUDE([$pdo_sqlsrv_inc_path])
  PHP_NEW_EXTENSION(pdo_sqlsrv, $pdo_sqlsrv_src_class $shared_src_class, $ext_shared,,-I$pdo_cv_inc_path -std=c++11)
  PHP_SUBST(PDO_SQLSRV_SHARED_LIBADD)
  PHP_ADD_EXTENSION_DEP(pdo_sqlsrv, pdo)
fi


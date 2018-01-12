PHP_ARG_ENABLE(sqlsrv, whether to enable sqlsrv functions,
[  --disable-sqlsrv         Disable sqlsrv functions], yes)

if test "$PHP_SQLSRV" != "no"; then
    sqlsrv_src_class="\
           conn.cpp \
           util.cpp \
           init.cpp  \
           stmt.cpp  \
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
    AC_MSG_CHECKING([for SQLSRV headers])
    if test -f $srcdir/ext/sqlsrv/shared/core_sqlsrv.h; then
        sqlsrv_inc_path=$srcdir/ext/sqlsrv/shared/
    elif  test -f $srcdir/shared/core_sqlsrv.h; then
        sqlsrv_inc_path=$srcdir/shared/
    else  
        AC_MSG_ERROR([Cannot find SQLSRV headers])
    fi
        AC_MSG_RESULT($sqlsrv_inc_path)    
        
  CXXFLAGS="$CXXFLAGS -std=c++11"
  CXXFLAGS="$CXXFLAGS -D_FORTIFY_SOURCE=2 -O2"
  CXXFLAGS="$CXXFLAGS -fstack-protector"

  HOST_OS_ARCH=`uname`
  if test "${HOST_OS_ARCH}" = "Darwin"; then
      SQLSRV_SHARED_LIBADD="$SQLSRV_SHARED_LIBADD -Wl,-bind_at_load"
      MACOSX_DEPLOYMENT_TARGET=`sw_vers -productVersion`
  else
      SQLSRV_SHARED_LIBADD="$SQLSRV_SHARED_LIBADD -Wl,-z,now"
  fi

  PHP_REQUIRE_CXX()
  PHP_ADD_LIBRARY(stdc++, 1, SQLSRV_SHARED_LIBADD)
  PHP_ADD_LIBRARY(odbc, 1, SQLSRV_SHARED_LIBADD)
  PHP_ADD_LIBRARY(odbcinst, 1, SQLSRV_SHARED_LIBADD)
  PHP_SUBST(SQLSRV_SHARED_LIBADD)
  AC_DEFINE(HAVE_SQLSRV, 1, [ ])
  PHP_ADD_INCLUDE([$sqlsrv_inc_path])
  PHP_NEW_EXTENSION(sqlsrv, $sqlsrv_src_class $shared_src_class, $ext_shared,,-std=c++11)
  PHP_ADD_BUILD_DIR([$ext_builddir/shared], 1)
fi

--TEST--
sqlsrv_configure.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php 

    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    // test negative cases first
    // must have two parameters
    $result = sqlsrv_configure( "WarningsReturnAsErrors" );
    if( $result ) {
        FatalError( "sqlsrv_configure(1) should have failed." );
    }
    
    // warnings_return_as_errors the only supported option
    $result = sqlsrv_configure( "blahblahblah", 1 );
    if( $result ) {     
        FatalError( "sqlsrv_configure(2) should have failed." );
    }
    else {
        print_r( sqlsrv_errors() );
    }
    
    $result = sqlsrv_get_config( 'blahblahblah' );
    if( !$result && !sqlsrv_errors() ) {
        FatalError( "sqlsrv_get_config should have failed." );
    }
    else {
        print_r( sqlsrv_errors() );
    }

    $result = sqlsrv_configure( "WarningsReturnAsErrors", True );
    if( !$result || sqlsrv_get_config( "WarningsReturnAsErrors" ) == false ) {
        FatalError( "sqlsrv_configure(3) should have passed" );
    }
    
    $result = sqlsrv_configure( "WarningsReturnAsErrors", False );
    if( !$result || sqlsrv_get_config( "WarningsReturnAsErrors" ) == true ) {
        FatalError( "sqlsrv_configure(4) should have passed" );
    }
    
    $result = sqlsrv_configure( "WarningsReturnAsErrors", 1 );
    if( !$result || sqlsrv_get_config( "WarningsReturnAsErrors" ) == false ) {
        FatalError( "sqlsrv_configure(5) should have passed" );
    }
    
    $result = sqlsrv_configure( "WarningsReturnAsErrors", 0 );
    if( !$result || sqlsrv_get_config( "WarningsReturnAsErrors" ) == true ) {
        FatalError( "sqlsrv_configure(6) should have passed" );
    }
    
    $result = sqlsrv_configure( "WarningsReturnAsErrors", null );
    if( !$result || sqlsrv_get_config( "WarningsReturnAsErrors" ) == true ) {
        FatalError( "sqlsrv_configure(7) should have passed" );
    }

    $result = sqlsrv_configure( "WarningsReturnAsErrors", "1" );
    if( !$result || sqlsrv_get_config( "WarningsReturnAsErrors" ) == false ) {
        FatalError( "sqlsrv_configure(8) should have passed" );
    }

    $result = sqlsrv_configure( "WarningsReturnAsErrors", "0" );
    if( !$result || sqlsrv_get_config( "WarningsReturnAsErrors" ) == true ) {
        FatalError( "sqlsrv_configure(9) should have passed" );
    }

    // test values for LogSystem and LogSeverity
    $result = sqlsrv_configure( "LogSeverity", SQLSRV_LOG_SEVERITY_ALL );
    if( !$result ) {
        FatalError( "sqlsrv_configure(10) should have passed." );
    }

    $result = sqlsrv_configure( "LogSeverity", 0 );
    if( $result ) {
        FatalError( "sqlsrv_configure(11) should not have passed." );
    }

    $result = sqlsrv_configure( "LogSeverity", SQLSRV_LOG_SEVERITY_ERROR );
    if( !$result ) {
        FatalError( "sqlsrv_configure(12) should have passed." );
    }

    $result = sqlsrv_configure( "LogSeverity", SQLSRV_LOG_SEVERITY_WARNING );
    if( !$result ) {
        FatalError( "sqlsrv_configure(13) should have passed." );
    }

    $result = sqlsrv_configure( "LogSeverity", SQLSRV_LOG_SEVERITY_NOTICE );
    if( !$result ) {
        FatalError( "sqlsrv_configure(14) should have passed." );
    }

    $result = sqlsrv_configure( "LogSeverity", 1000 );
    if( $result ) {
        FatalError( "sqlsrv_configure(15) should not have passed." );
    }

    sqlsrv_configure( "LogSeverity", SQLSRV_LOG_SEVERITY_ALL );

    $result = sqlsrv_configure( "LogSubsystems", SQLSRV_LOG_SYSTEM_ALL );
    if( !$result ) {
        FatalError( "sqlsrv_configure(16) should have passed." );
    }

    $result = sqlsrv_configure( "LogSubsystems", SQLSRV_LOG_SYSTEM_OFF );
    if( !$result ) {
        FatalError( "sqlsrv_configure(17) should not have passed." );
    }

    $result = sqlsrv_configure( "LogSubsystems", SQLSRV_LOG_SYSTEM_INIT );
    if( !$result ) {
        FatalError( "sqlsrv_configure(18) should have passed." );
    }

    $result = sqlsrv_configure( "LogSubsystems", SQLSRV_LOG_SYSTEM_CONN );
    if( !$result ) {
        FatalError( "sqlsrv_configure(19) should have passed." );
    }

    $result = sqlsrv_configure( "LogSubsystems", SQLSRV_LOG_SYSTEM_STMT );
    if( !$result ) {
        FatalError( "sqlsrv_configure(20) should have passed." );
    }

    $result = sqlsrv_configure( "LogSubsystems", SQLSRV_LOG_SYSTEM_UTIL );
    if( !$result ) {
        FatalError( "sqlsrv_configure(21) should have passed." );
    }

    $result = sqlsrv_configure( "LogSubsystems", 1000 );
    if( $result ) {
        FatalError( "sqlsrv_configure(22) should not have passed." );
    }

?>
--EXPECTREGEX--
Warning: sqlsrv_configure\(\) expects exactly 2 parameters, 1 given in .+(\/|\\)sqlsrv_configure\.php on line [0-9]+
Array
\(
    \[0\] => Array
        \(
            \[0\] => IMSSP
            \[SQLSTATE\] => IMSSP
            \[1\] => -14
            \[code\] => -14
            \[2\] => An invalid parameter was passed to sqlsrv_configure.
            \[message\] => An invalid parameter was passed to sqlsrv_configure.
        \)

\)
Array
\(
    \[0\] => Array
        \(
            \[0\] => IMSSP
            \[SQLSTATE\] => IMSSP
            \[1\] => -14
            \[code\] => -14
            \[2\] => An invalid parameter was passed to sqlsrv_get_config.
            \[message\] => An invalid parameter was passed to sqlsrv_get_config.
        \)

\)
sqlsrv.LogSubsystems = -1
sqlsrv_configure: entering
sqlsrv.LogSubsystems = 8
sqlsrv_configure: entering
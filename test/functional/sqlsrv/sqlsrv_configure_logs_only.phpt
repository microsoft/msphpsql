--TEST--
sqlsrv_configure to test logs. 
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php 

    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );
    sqlsrv_configure( 'LogSubsystems', SQLSRV_LOG_SYSTEM_ALL );

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
--EXPECT--
sqlsrv.LogSubsystems = -1
sqlsrv_configure: entering
sqlsrv.WarningsReturnAsErrors = On
sqlsrv_get_config: entering
sqlsrv_configure: entering
sqlsrv.WarningsReturnAsErrors = Off
sqlsrv_get_config: entering
sqlsrv_configure: entering
sqlsrv.WarningsReturnAsErrors = On
sqlsrv_get_config: entering
sqlsrv_configure: entering
sqlsrv.WarningsReturnAsErrors = Off
sqlsrv_get_config: entering
sqlsrv_configure: entering
sqlsrv.WarningsReturnAsErrors = Off
sqlsrv_get_config: entering
sqlsrv_configure: entering
sqlsrv.WarningsReturnAsErrors = On
sqlsrv_get_config: entering
sqlsrv_configure: entering
sqlsrv.WarningsReturnAsErrors = Off
sqlsrv_get_config: entering
sqlsrv_configure: entering
sqlsrv.LogSeverity = -1
sqlsrv_configure: entering
sqlsrv_configure: entering
sqlsrv.LogSeverity = 4
sqlsrv_configure: entering
sqlsrv_configure: entering
sqlsrv.LogSeverity = -1
sqlsrv_configure: entering
sqlsrv.LogSubsystems = -1
sqlsrv_configure: entering
sqlsrv.LogSubsystems = 8
sqlsrv_configure: entering
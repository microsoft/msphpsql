--TEST--
DateTime Inheritence
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require( 'MsCommon.inc' );
    require ('sqlsrv_test_base.inc');

    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    class DateTime1 extends DateTime {
    }

    class DateTime2 extends DateTime1 {
    }

    class DateTimeExtended extends DateTime2 {
    }

    $dt = new DateTimeExtended('2011-01-01');
    $dt->setTime(14,59,59);

    $conn = Connect();

    $stmt = sqlsrv_query($conn, "Select ?", array(array($dt)));
    $errors = sqlsrv_errors();
    print_r($errors);

    echo "Test Succeeded";
 
?>

--EXPECTF--

Test Succeeded
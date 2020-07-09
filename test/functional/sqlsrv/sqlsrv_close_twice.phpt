--TEST--
Free statement twice
--FILE--
﻿<?php
require_once('MsCommon.inc');
set_error_handler("warningHandler", E_WARNING);

// When testing with PHP 8.0 it throws a TypeError instead of a warning. Thus implement a custom 
// warning handler such that with PHP 7.x the warning would be handled to throw a TypeError.
function warningHandler($errno, $errstr) 
{ 
    throw new TypeError($errstr);
}

function CloseTwice()
{
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // Connect
    $conn = connect();
    if (!$conn) {
        fatalError("Could not connect.\n");
    }

    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_tinyint] tinyint)");
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    sqlsrv_free_stmt($stmt);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    startTest("sqlsrv_close_twice");
    try {
        CloseTwice();
    } catch (TypeError $e) {
        echo $e->getMessage() . PHP_EOL;
    } catch (Exception $e) {
        echo $e->getMessage() . PHP_EOL;
    }
    echo "\nDone\n";
    endTest("sqlsrv_close_twice");
}

Repro();

?>
--EXPECT--
﻿sqlsrv_free_stmt(): supplied resource is not a valid ss_sqlsrv_stmt resource

Done
Test "sqlsrv_close_twice" completed successfully.

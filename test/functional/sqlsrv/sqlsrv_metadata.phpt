--TEST--
field metadata for all types.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    require( 'MsCommon.inc' );
    $conn = Connect(); 
    if (!$conn)
    {
        die( print_r( sqlsrv_errors(), true ));
    }

    $stmt = sqlsrv_query( $conn, "SELECT * FROM test_types" );

    $metadata = sqlsrv_field_metadata( $stmt );

    print_r( $metadata );

    sqlsrv_free_stmt( $stmt );
    sqlsrv_close( $conn );
?>
--EXPECT--
Array
(
    [0] => Array
        (
            [Name] => bigint_type
            [Type] => -5
            [Size] => 
            [Precision] => 19
            [Scale] => 
            [Nullable] => 1
        )

    [1] => Array
        (
            [Name] => int_type
            [Type] => 4
            [Size] => 
            [Precision] => 10
            [Scale] => 
            [Nullable] => 1
        )

    [2] => Array
        (
            [Name] => smallint_type
            [Type] => 5
            [Size] => 
            [Precision] => 5
            [Scale] => 
            [Nullable] => 1
        )

    [3] => Array
        (
            [Name] => tinyint_type
            [Type] => -6
            [Size] => 
            [Precision] => 3
            [Scale] => 
            [Nullable] => 1
        )

    [4] => Array
        (
            [Name] => bit_type
            [Type] => -7
            [Size] => 
            [Precision] => 1
            [Scale] => 
            [Nullable] => 1
        )

    [5] => Array
        (
            [Name] => decimal_type
            [Type] => 3
            [Size] => 
            [Precision] => 38
            [Scale] => 0
            [Nullable] => 1
        )

    [6] => Array
        (
            [Name] => money_type
            [Type] => 3
            [Size] => 
            [Precision] => 19
            [Scale] => 4
            [Nullable] => 1
        )

    [7] => Array
        (
            [Name] => smallmoney_type
            [Type] => 3
            [Size] => 
            [Precision] => 10
            [Scale] => 4
            [Nullable] => 1
        )

    [8] => Array
        (
            [Name] => float_type
            [Type] => 6
            [Size] => 
            [Precision] => 53
            [Scale] => 
            [Nullable] => 1
        )

    [9] => Array
        (
            [Name] => real_type
            [Type] => 7
            [Size] => 
            [Precision] => 24
            [Scale] => 
            [Nullable] => 1
        )

    [10] => Array
        (
            [Name] => datetime_type
            [Type] => 93
            [Size] => 
            [Precision] => 23
            [Scale] => 3
            [Nullable] => 1
        )

    [11] => Array
        (
            [Name] => smalldatetime_type
            [Type] => 93
            [Size] => 
            [Precision] => 16
            [Scale] => 0
            [Nullable] => 1
        )

)

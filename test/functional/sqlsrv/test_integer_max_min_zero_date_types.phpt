--TEST--
Test Integer types MAX, MIN, ZERO values.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require( 'MsCommon.inc' );

    $tableUniqueName = "PHP_Firefly_Table";

    $conn = Connect();

    date_default_timezone_set('Canada/Pacific');
    // drop an old table if exists, create a new one
    $stmt = sqlsrv_query($conn, "IF OBJECT_ID('$tableUniqueName', 'U') IS NOT NULL DROP TABLE$tableUniqueName");

    // -- create the new table
    $tsql = "CREATE TABLE $tableUniqueName (
        [bigint_type] BIGINT null,
        [int_type] INT null,
        [smallint_type] SMALLINT null,
        [tinyint_type] TINYINT null,
        [bit_type] BIT null,
        [decimal_type] DECIMAL(38,0) null,
        [money_type] MONEY null,
        [smallmoney_type] SMALLMONEY null,
        [datetime_type] DATETIME null,
        [smalldatetime_type] SMALLDATETIME null );";
    $stmt = sqlsrv_query($conn,$tsql);

    sqlsrv_free_stmt( $stmt );

    // inserting data into the table

    // -- maximum test
    $tsql = "INSERT INTO $tableUniqueName (bigint_type, int_type, smallint_type, tinyint_type, bit_type, decimal_type, datetime_type, money_type, smallmoney_type) 
        VALUES (9223372036854775807, 2147483647, 32767, 255, 1, 9999999999999999999999999999999999999, '12/12/1968 16:20', 922337203685477.5807, 214748.3647)";
    $stmt = sqlsrv_query($conn, $tsql);


    // -- minimum test
    $tsql = "INSERT INTO $tableUniqueName (bigint_type, int_type, smallint_type, tinyint_type, bit_type, decimal_type, datetime_type, money_type, smallmoney_type)
        VALUES (-9223372036854775808, -2147483648, -32768, 0, 0, -10000000000000000000000000000000000001,'12/12/1968 16:20', -922337203685477.5808, -214748.3648)";
    $stmt = sqlsrv_query($conn, $tsql);

    // -- zero test
    $tsql = "INSERT INTO $tableUniqueName (bigint_type, int_type, smallint_type, tinyint_type, bit_type, decimal_type, datetime_type, money_type, smallmoney_type) 
                VALUES (0, 0, 0, 0, 0, 0, '12/12/1968 16:20', 0, 0)";
    $stmt = sqlsrv_query($conn, $tsql);

    // ----MAX-------
    $tsql = "SELECT * FROM $tableUniqueName";
    $stmt = sqlsrv_query($conn, $tsql);
    $row = sqlsrv_fetch_array($stmt,1);
    print_r($row);

    // ----MIN-------
    $row = sqlsrv_fetch_array($stmt,2);
    print_r($row);

    // ----ZERO-------
    $row = sqlsrv_fetch_array($stmt);
    print_r($row);
    
    sqlsrv_query($conn, "DROP TABLE $tableUniqueName");
    sqlsrv_free_stmt( $stmt );
    sqlsrv_close($conn);
?>
--EXPECT--
Array
(
    [0] => 9223372036854775807
    [1] => 2147483647
    [2] => 32767
    [3] => 255
    [4] => 1
    [5] => 9999999999999999999999999999999999999
    [6] => 922337203685477.5807
    [7] => 214748.3647
    [8] => DateTime Object
        (
            [date] => 1968-12-12 16:20:00.000000
            [timezone_type] => 3
            [timezone] => Canada/Pacific
        )

    [9] => 
)
Array
(
    [bigint_type] => -9223372036854775808
    [int_type] => -2147483648
    [smallint_type] => -32768
    [tinyint_type] => 0
    [bit_type] => 0
    [decimal_type] => -10000000000000000000000000000000000001
    [money_type] => -922337203685477.5808
    [smallmoney_type] => -214748.3648
    [datetime_type] => DateTime Object
        (
            [date] => 1968-12-12 16:20:00.000000
            [timezone_type] => 3
            [timezone] => Canada/Pacific
        )

    [smalldatetime_type] => 
)
Array
(
    [0] => 0
    [bigint_type] => 0
    [1] => 0
    [int_type] => 0
    [2] => 0
    [smallint_type] => 0
    [3] => 0
    [tinyint_type] => 0
    [4] => 0
    [bit_type] => 0
    [5] => 0
    [decimal_type] => 0
    [6] => .0000
    [money_type] => .0000
    [7] => .0000
    [smallmoney_type] => .0000
    [8] => DateTime Object
        (
            [date] => 1968-12-12 16:20:00.000000
            [timezone_type] => 3
            [timezone] => Canada/Pacific
        )

    [datetime_type] => DateTime Object
        (
            [date] => 1968-12-12 16:20:00.000000
            [timezone_type] => 3
            [timezone] => Canada/Pacific
        )

    [9] => 
    [smalldatetime_type] => 
)
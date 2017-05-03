--TEST--
Test for integer, float, and datetime types vs various sql server types.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    date_default_timezone_set( 'UTC' );

    require( 'MsCommon.inc' );

function get_fields( $stmt ) {

    // bigint
    $field = sqlsrv_get_field( $stmt, 0, SQLSRV_PHPTYPE_INT );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        var_dump( sqlsrv_errors( SQLSRV_ERR_WARNINGS ) );
        echo "$field\n";
    }

    // int
    $field = sqlsrv_get_field( $stmt, 1, SQLSRV_PHPTYPE_INT );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        var_dump( sqlsrv_errors( SQLSRV_ERR_WARNINGS ) );
        echo "$field\n";
    }

    // smallint
    $field = sqlsrv_get_field( $stmt, 2, SQLSRV_PHPTYPE_INT );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        var_dump( sqlsrv_errors( SQLSRV_ERR_WARNINGS ) );
        echo "$field\n";
    }

    // tinyint
    $field = sqlsrv_get_field( $stmt, 3, SQLSRV_PHPTYPE_INT );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        var_dump( sqlsrv_errors( SQLSRV_ERR_WARNINGS ) );
        echo "$field\n";
    }

    // bit
    $field = sqlsrv_get_field( $stmt, 4, SQLSRV_PHPTYPE_INT );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        var_dump( sqlsrv_errors( SQLSRV_ERR_WARNINGS ) );
        echo "$field\n";
    }

    // decimal(38,0)
    $field = sqlsrv_get_field( $stmt, 5, SQLSRV_PHPTYPE_FLOAT );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        var_dump( sqlsrv_errors( SQLSRV_ERR_WARNINGS ) );
        echo "$field\n";
    }

    // datetime
    $field = sqlsrv_get_field( $stmt, 6, SQLSRV_PHPTYPE_DATETIME );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        var_dump( sqlsrv_errors( SQLSRV_ERR_WARNINGS ) );
        echo $field->format( "m/d/Y h:i:s" );
        echo "\n";
    }
    
    // money
    $field = sqlsrv_get_field( $stmt, 7, SQLSRV_PHPTYPE_FLOAT );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        var_dump( sqlsrv_errors( SQLSRV_ERR_WARNINGS ) );
        echo "$field\n";
    }

    // smallmoney
    $field = sqlsrv_get_field( $stmt, 8, SQLSRV_PHPTYPE_FLOAT );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        var_dump( sqlsrv_errors( SQLSRV_ERR_WARNINGS ) );
        echo "$field\n";
    }

    // float(53)
    $field = sqlsrv_get_field( $stmt, 9, SQLSRV_PHPTYPE_FLOAT );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        var_dump( sqlsrv_errors( SQLSRV_ERR_WARNINGS ) );
        echo "$field\n";
    }

    // real (this doesn't get the max or min, but the closes representation to 0 without being 0)
    $field = sqlsrv_get_field( $stmt, 10, SQLSRV_PHPTYPE_FLOAT );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        var_dump( sqlsrv_errors( SQLSRV_ERR_WARNINGS ) );
        echo "$field\n";
    }

    echo "get_fields done.\n";
}

    $conn = Connect();
    if( !$conn ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_connect failed." );
    }

    $stmt = sqlsrv_query( $conn, "SELECT bigint_type, int_type, smallint_type, tinyint_type, bit_type, decimal_type, datetime_type, money_type, smallmoney_type, float_type, real_type FROM [test_types]" );
    if( !$stmt ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_query failed" );
    }
    
    $success = sqlsrv_fetch( $stmt );
    if( !$success ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_fetch failed" );        
    }
    
    // maximum values
    get_fields( $stmt );
    
    $success = sqlsrv_fetch( $stmt );
    if( !$success ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_fetch failed" );        
    }
    
    // minimum values
    get_fields( $stmt );    

    $success = sqlsrv_fetch( $stmt );
    if( !$success ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_fetch failed" );        
    }
    
    // zero values
    get_fields( $stmt );    

    $stmt = sqlsrv_query( $conn, "SELECT int_type, decimal_type, datetime_type, real_type FROM [test_types]" );
    if( !$stmt ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_query failed" );
    }
    
    $success = sqlsrv_fetch( $stmt );
    if( !$success ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_fetch failed" );        
    }

    $field = sqlsrv_get_field( $stmt, 0, SQLSRV_PHPTYPE_INT );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_get_field failed" );        
    }
    $field = sqlsrv_get_field( $stmt, 0, SQLSRV_PHPTYPE_INT );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        die( "sqlsrv_get_field should have failed" );        
    }    

    $field = sqlsrv_get_field( $stmt, 1, SQLSRV_PHPTYPE_FLOAT );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_get_field failed" );        
    }
    $field = sqlsrv_get_field( $stmt, 1, SQLSRV_PHPTYPE_FLOAT );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        die( "sqlsrv_get_field should have failed" );        
    }

    $field = sqlsrv_get_field( $stmt, 2, SQLSRV_PHPTYPE_DATETIME );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_get_field failed" );        
    }
    $field = sqlsrv_get_field( $stmt, 2, SQLSRV_PHPTYPE_DATETIME );
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        die( "sqlsrv_get_field should have failed" );        
    }

    $field = sqlsrv_get_field( $stmt, 3, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_get_field failed" );        
    }
    $field = sqlsrv_get_field( $stmt, 3, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if( $field === false ) {
        var_dump( sqlsrv_errors() );
    }
    else {
        die( "sqlsrv_get_field should have failed" );        
    }
?>
--EXPECTF--
array(1) {
  [0]=>
  array(6) {
    [0]=>
    string(5) "22003"
    ["SQLSTATE"]=>
    string(5) "22003"
    [1]=>
    int(0)
    ["code"]=>
    int(0)
    [2]=>
    string(%x) "%SNumeric value out of range"
    ["message"]=>
    string(%x) "%SNumeric value out of range"
  }
}
NULL
2147483647
NULL
32767
NULL
255
NULL
1
NULL
1.0E+37
NULL
12/12/1968 04:20:00
NULL
9.2233720368548E+14
NULL
214748.3647
NULL
1.79E+308
NULL
1.1799999457746E-38
get_fields done.
array(1) {
  [0]=>
  array(6) {
    [0]=>
    string(5) "22003"
    ["SQLSTATE"]=>
    string(5) "22003"
    [1]=>
    int(0)
    ["code"]=>
    int(0)
    [2]=>
    string(%x) "%SNumeric value out of range"
    ["message"]=>
    string(%x) "%SNumeric value out of range"
  }
}
NULL
-2147483648
NULL
-32768
NULL
0
NULL
0
NULL
-1.0E+37
NULL
12/12/1968 04:20:00
NULL
-9.2233720368548E+14
NULL
-214748.3648
NULL
-1.79E+308
NULL
-1.1799999457746E-38
get_fields done.
NULL
0
NULL
0
NULL
0
NULL
0
NULL
0
NULL
0
NULL
12/12/1968 04:20:00
NULL
0
NULL
0
NULL
0
NULL
0
get_fields done.
array(1) {
  [0]=>
  array(6) {
    [0]=>
    string(5) "IMSSP"
    ["SQLSTATE"]=>
    string(5) "IMSSP"
    [1]=>
    int(-5)
    ["code"]=>
    int(-5)
    [2]=>
    string(25) "Field 0 returned no data."
    ["message"]=>
    string(25) "Field 0 returned no data."
  }
}
array(1) {
  [0]=>
  array(6) {
    [0]=>
    string(5) "IMSSP"
    ["SQLSTATE"]=>
    string(5) "IMSSP"
    [1]=>
    int(-5)
    ["code"]=>
    int(-5)
    [2]=>
    string(25) "Field 1 returned no data."
    ["message"]=>
    string(25) "Field 1 returned no data."
  }
}
array(1) {
  [0]=>
  array(6) {
    [0]=>
    string(5) "IMSSP"
    ["SQLSTATE"]=>
    string(5) "IMSSP"
    [1]=>
    int(-5)
    ["code"]=>
    int(-5)
    [2]=>
    string(25) "Field 2 returned no data."
    ["message"]=>
    string(25) "Field 2 returned no data."
  }
}
array(1) {
  [0]=>
  array(6) {
    [0]=>
    string(5) "IMSSP"
    ["SQLSTATE"]=>
    string(5) "IMSSP"
    [1]=>
    int(-5)
    ["code"]=>
    int(-5)
    [2]=>
    string(25) "Field 3 returned no data."
    ["message"]=>
    string(25) "Field 3 returned no data."
  }
}

--TEST--
datetime objects as fields and as parameters.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

set_time_limit(0); 
sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

date_default_timezone_set( 'America/Vancouver' );

require( 'MsCommon.inc' );

$conn = Connect();
if( $conn === false ) {
    die( print_r( sqlsrv_errors(), true ));
} 
$stmt = sqlsrv_query($conn, "IF OBJECT_ID('php_table_SERIL1_1', 'U') IS NOT NULL DROP TABLE [php_table_SERIL1_1]");
if( $stmt !== false ) sqlsrv_free_stmt( $stmt );
 
$stmt = sqlsrv_query($conn, "CREATE TABLE [php_table_SERIL1_1] ([c1_datetime] datetime, [c2_smalldatetime] smalldatetime)");
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
sqlsrv_free_stmt($stmt);

// test inserting into date time as a default
$date_time = date_create( '1963-02-01 20:56' ); 
$stmt = sqlsrv_query($conn, "INSERT INTO [php_table_SERIL1_1] (c1_datetime, c2_smalldatetime) VALUES (?,?)", array( $date_time, $date_time ));
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "SELECT c1_datetime, c2_smalldatetime FROM [php_table_SERIL1_1]" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$result = sqlsrv_fetch( $stmt );
if( $result === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$date = sqlsrv_get_field( $stmt, 0, SQLSRV_PHPTYPE_DATETIME );
if( $date === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
print_r( date_format( $date, 'Y-m-d H:i:s.u'));
echo "\n";
$date = sqlsrv_get_field( $stmt, 1, SQLSRV_PHPTYPE_DATETIME );
if( $date === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
print_r( date_format( $date, 'Y-m-d H:i:s.u'));
echo "\n";
$stmt = sqlsrv_query( $conn, "TRUNCATE TABLE [php_table_SERIL1_1]");
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
sqlsrv_free_stmt( $stmt );

// try full complement of information for parameters
$stmt = sqlsrv_query($conn, "INSERT INTO [php_table_SERIL1_1] (c1_datetime, c2_smalldatetime) VALUES (?,?)", 
    array( array( $date_time, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_DATETIME, SQLSRV_SQLTYPE_DATETIME ),
           array( $date_time, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_DATETIME, SQLSRV_SQLTYPE_DATETIME )));
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "SELECT c1_datetime, c2_smalldatetime FROM [php_table_SERIL1_1]" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$result = sqlsrv_fetch( $stmt );
if( $result === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$date = sqlsrv_get_field( $stmt, 0, SQLSRV_PHPTYPE_DATETIME );
if( $date === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
print_r( date_format( $date, 'Y-m-d H:i:s.u'));
echo "\n";
$date = sqlsrv_get_field( $stmt, 1 );
if( $date === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
print_r( date_format( $date, 'Y-m-d H:i:s.u'));
echo "\n";

sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query( $conn, "TRUNCATE TABLE [php_table_SERIL1_1]");
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
sqlsrv_free_stmt( $stmt );

// try with only php type
$stmt = sqlsrv_query($conn, "INSERT INTO [php_table_SERIL1_1] (c1_datetime, c2_smalldatetime) VALUES (?,?)", 
    array( array( $date_time, null, SQLSRV_PHPTYPE_DATETIME ),
           array( $date_time, null, SQLSRV_PHPTYPE_DATETIME )));
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "SELECT c1_datetime, c2_smalldatetime FROM [php_table_SERIL1_1]" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$result = sqlsrv_fetch( $stmt );
if( $result === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$date = sqlsrv_get_field( $stmt, 0 );
if( $date === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
print_r( date_format( $date, 'Y-m-d H:i:s.u'));
echo "\n";
$date = sqlsrv_get_field( $stmt, 1, SQLSRV_PHPTYPE_DATETIME );
if( $date === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
print_r( date_format( $date, 'Y-m-d H:i:s.u'));
echo "\n";

sqlsrv_free_stmt($stmt);

// try an invalid date
$date_time = date_create( '1872-02-01 20:56' ); 
$stmt = sqlsrv_query($conn, "INSERT INTO [php_table_SERIL1_1] (c2_smalldatetime) VALUES (?)", array( $date_time ));
if( $stmt !== false ) {
    die( "date time should have been out of range." );
}
print_r( sqlsrv_errors() );

class SimpleClass
{
    // member declaration
    public $var = 'a default value';

    // method declaration
    public function displayVar() {
        echo $this->var;
    }
}

$simple_class = new SimpleClass();
$stmt = sqlsrv_query($conn, "INSERT INTO [php_table_SERIL1_1] (c2_smalldatetime) VALUES (?)", array( $simple_class ));
if( $stmt !== false ) {
    die( "class should have failed." );
}
print_r( sqlsrv_errors() );

sqlsrv_query($conn, "DROP TABLE [php_table_SERIL1_1]");

sqlsrv_close($conn); 

echo "test succeeded.";

?>
--EXPECTF--
1963-02-01 20:56:00.000000
1963-02-01 20:56:00.000000
1963-02-01 20:56:00.000000
1963-02-01 20:56:00.000000
1963-02-01 20:56:00.000000
1963-02-01 20:56:00.000000
Array
(
    [0] => Array
        (
            [0] => 22007
            [SQLSTATE] => 22007
            [1] => 242
            [code] => 242
            [2] => %SThe conversion of a datetimeoffset data type to a smalldatetime data type resulted in an out-of-range value.
            [message] => %SThe conversion of a datetimeoffset data type to a smalldatetime data type resulted in an out-of-range value.
        )

    [1] => Array
        (
            [0] => 01000
            [SQLSTATE] => 01000
            [1] => 3621
            [code] => 3621
            [2] => %SThe statement has been terminated.
            [message] => %SThe statement has been terminated.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -16
            [code] => -16
            [2] => An invalid PHP type for parameter 1 was specified.
            [message] => An invalid PHP type for parameter 1 was specified.
        )

)
test succeeded.

--TEST--
Read numeric types from SQLSRV with buffered query.
--DESCRIPTION--
Test numeric conversion (number to string, string to number) functionality for buffered queries with SQLSRV.
--SKIPIF--
--FILE--
<?php

require_once("autonomous_setup.php");

$connectionInfo = array("UID"=>"$username", "PWD"=>"$password", "CharacterSet" => "UTF-8");
$conn = sqlsrv_connect($serverName, $connectionInfo);
if( $conn === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$sample = 1234567890.1234;
$sample1 = -1234567890.1234;
$sample2 = 1;
$sample3 = -1;
$sample4 = 0.5;
$sample5 = -0.55;

$query = 'CREATE TABLE #TESTTABLE (a float(53), neg_a float(53), b int, neg_b int, c decimal(16, 6), neg_c decimal(16, 6), zero int, zerof float(53), zerod decimal(16,6))';

// Create table
$stmt = sqlsrv_query( $conn, $query );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$query = 'INSERT INTO #TESTTABLE (a, neg_a, b, neg_b, c, neg_c, zero, zerof, zerod) VALUES(?, ?, ?, ?, ?, ?, 0, 0, 0)';
$params = array($sample, $sample1, $sample2, $sample3, $sample4, $sample5);  

$stmt = sqlsrv_query( $conn, $query, $params );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$params = array($sample4, $sample5, 100000, -1234567, $sample, $sample1);  
$stmt = sqlsrv_query( $conn, $query, $params );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}




$query = 'SELECT TOP 2 * FROM #TESTTABLE';
$stmt = sqlsrv_query( $conn, $query, array(), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED));
if(!$stmt)
{
	echo "Statement could not be prepared.\n";
	die( print_r( sqlsrv_errors(),true));
}
sqlsrv_execute( $stmt );

$array = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC );
var_dump($array);
$array = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC );
var_dump($array);




$numFields = sqlsrv_num_fields( $stmt );
$meta = sqlsrv_field_metadata( $stmt );
$rowcount = sqlsrv_num_rows( $stmt);
for($i = 0; $i < $rowcount; $i++){
        sqlsrv_fetch( $stmt, SQLSRV_SCROLL_ABSOLUTE, $i );
        for($j = 0; $j < $numFields; $j++) { 
                $name = $meta[$j]["Name"];
                print("\ncolumn: $name\n");
                $field = sqlsrv_get_field( $stmt, $j, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR) );        
                var_dump($field);        
                if ($meta[$j]["Type"] == SQLSRV_SQLTYPE_INT)
                {
                        $field = sqlsrv_get_field( $stmt, $j, SQLSRV_PHPTYPE_INT );
                        var_dump($field);
                }
                $field = sqlsrv_get_field( $stmt, $j, SQLSRV_PHPTYPE_FLOAT);
                var_dump($field);
        }
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECT--
array(9) {
  [0]=>
  float(1234567890.1234)
  [1]=>
  float(-1234567890.1234)
  [2]=>
  int(1)
  [3]=>
  int(-1)
  [4]=>
  string(7) ".500000"
  [5]=>
  string(8) "-.550000"
  [6]=>
  int(0)
  [7]=>
  float(0)
  [8]=>
  string(7) ".000000"
}
array(9) {
  [0]=>
  float(0.5)
  [1]=>
  float(-0.55)
  [2]=>
  int(100000)
  [3]=>
  int(-1234567)
  [4]=>
  string(17) "1234567890.123400"
  [5]=>
  string(18) "-1234567890.123400"
  [6]=>
  int(0)
  [7]=>
  float(0)
  [8]=>
  string(7) ".000000"
}

column: a
string(15) "1234567890.1234"
float(1234567890.1234)

column: neg_a
string(16) "-1234567890.1234"
float(-1234567890.1234)

column: b
string(1) "1"
int(1)
float(1)

column: neg_b
string(2) "-1"
int(-1)
float(-1)

column: c
string(7) ".500000"
float(0.5)

column: neg_c
string(8) "-.550000"
float(-0.55)

column: zero
string(1) "0"
int(0)
float(0)

column: zerof
string(1) "0"
float(0)

column: zerod
string(7) ".000000"
float(0)

column: a
string(3) "0.5"
float(0.5)

column: neg_a
string(5) "-0.55"
float(-0.55)

column: b
string(6) "100000"
int(100000)
float(100000)

column: neg_b
string(8) "-1234567"
int(-1234567)
float(-1234567)

column: c
string(17) "1234567890.123400"
float(1234567890.1234)

column: neg_c
string(18) "-1234567890.123400"
float(-1234567890.1234)

column: zero
string(1) "0"
int(0)
float(0)

column: zerof
string(1) "0"
float(0)

column: zerod
string(7) ".000000"
float(0)


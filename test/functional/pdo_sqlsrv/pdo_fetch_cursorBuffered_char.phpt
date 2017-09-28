--TEST--
prepare with cursor buffered and fetch a varchar column
--SKIPIF--

--FILE--
<?php
require_once( "MsCommon.inc" );

$conn = connect();
$sample = "eight";

$tbname = "TESTTABLE";
create_table( $conn, $tbname, array( new columnMeta( "varchar(10)", "exist" )));

$query = "INSERT INTO $tbname VALUES(:p0)";
$stmt = $conn->prepare($query);
$stmt->bindValue(':p0', $sample, PDO::PARAM_STR);
$stmt->execute();

$query = "SELECT TOP 1 * FROM $tbname";

//prepare with no buffered cursor
print "no buffered cursor, stringify off, fetch_numeric off\n"; //stringify and fetch_numeric is off by default
$stmt = $conn->prepare($query);
$stmt->execute();
$value = $stmt->fetchColumn();
var_dump ($value);

print "\nno buffered cursor, stringify off, fetch_numeric on\n";
$conn->setAttribute( PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
$stmt = $conn->prepare($query);
$stmt->execute();
$value = $stmt->fetchColumn();
var_dump ($value);

print "\nno buffered cursor, stringify on, fetch_numeric on\n";
$conn->setAttribute( PDO::ATTR_STRINGIFY_FETCHES, true);
$stmt = $conn->prepare($query);
$stmt->execute();
$value = $stmt->fetchColumn();
var_dump ($value);

print "\nno buffered cursor, stringify on, fetch_numeric off\n";
$conn->setAttribute( PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, false);
$stmt = $conn->prepare($query);
$stmt->execute();
$value = $stmt->fetchColumn();
var_dump ($value);

//prepare with client buffered cursor
print "\nbuffered cursor, stringify off, fetch_numeric off\n";
$conn->setAttribute( PDO::ATTR_STRINGIFY_FETCHES, false);
$stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
$stmt->execute();
$value = $stmt->fetchColumn();
var_dump ($value);

print "\nbuffered cursor, stringify off, fetch_numeric on\n";
$conn->setAttribute( PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
$stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
$stmt->execute();
$value = $stmt->fetchColumn();
var_dump ($value);

print "\nbuffered cursor, stringify on, fetch_numeric on\n";
$conn->setAttribute( PDO::ATTR_STRINGIFY_FETCHES, true);
$stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
$stmt->execute();
$value = $stmt->fetchColumn();
var_dump ($value);

print "\nbuffered cursor, stringify on, fetch_numeric off\n";
$conn->setAttribute( PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, false);
$stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
$stmt->execute();
$value = $stmt->fetchColumn();
var_dump ($value);

DropTable( $conn, $tbname );
unset( $stmt );
unset( $conn );

?>
--EXPECT--
no buffered cursor, stringify off, fetch_numeric off
string(5) "eight"

no buffered cursor, stringify off, fetch_numeric on
string(5) "eight"

no buffered cursor, stringify on, fetch_numeric on
string(5) "eight"

no buffered cursor, stringify on, fetch_numeric off
string(5) "eight"

buffered cursor, stringify off, fetch_numeric off
string(5) "eight"

buffered cursor, stringify off, fetch_numeric on
string(5) "eight"

buffered cursor, stringify on, fetch_numeric on
string(5) "eight"

buffered cursor, stringify on, fetch_numeric off
string(5) "eight"

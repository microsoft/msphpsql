--TEST--
prepare with cursor buffered and fetch a float column with the column bound and specified to type LOB
--SKIPIF--

--FILE--
<?php
function FlatsAreEqual($a, $b, $epsilon = 3.9265E-6)
{
  return (abs($a - $b) < $epsilon);
}
require_once("MsSetup.inc");
$conn = new PDO( "sqlsrv:server=$server", $uid, $pwd);
$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$sample = 1234567890.1234;

$query = 'CREATE TABLE #TESTTABLE (exist float(53))';
$stmt = $conn->exec($query);
$query = 'INSERT INTO #TESTTABLE VALUES(:p0)';
$stmt = $conn->prepare($query);
$stmt->bindValue(':p0', $sample, PDO::PARAM_INT);
$stmt->execute();

$query = 'SELECT exist FROM #TESTTABLE';

//prepare with no buffered cursor
print "no buffered cursor, stringify off, fetch_numeric off\n"; //stringify and fetch_numeric is off by default
$stmt = $conn->prepare($query);
$stmt->execute();
$stmt->bindColumn('exist', $float_col, PDO::PARAM_LOB);
$value = $stmt->fetch();
var_dump ($float_col);

print "\nno buffered cursor, stringify off, fetch_numeric on\n";
$conn->setAttribute( PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
$stmt = $conn->prepare($query);
$stmt->execute();
$stmt->bindColumn('exist', $float_col, PDO::PARAM_LOB);
$value = $stmt->fetch();
var_dump ($float_col);

print "\nno buffered cursor, stringify on, fetch_numeric on\n";
$conn->setAttribute( PDO::ATTR_STRINGIFY_FETCHES, true);
$stmt = $conn->prepare($query);
$stmt->execute();
$stmt->bindColumn('exist', $float_col, PDO::PARAM_LOB);
$value = $stmt->fetch();
var_dump ($float_col);

print "\nno buffered cursor, stringify on, fetch_numeric off\n";
$conn->setAttribute( PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, false);
$stmt = $conn->prepare($query);
$stmt->execute();
$stmt->bindColumn('exist', $float_col, PDO::PARAM_LOB);
$value = $stmt->fetch();
var_dump ($float_col);

//prepare with client buffered cursor
print "\nbuffered cursor, stringify off, fetch_numeric off\n";
$conn->setAttribute( PDO::ATTR_STRINGIFY_FETCHES, false);
$stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
$stmt->execute();
$stmt->bindColumn('exist', $float_col, PDO::PARAM_LOB);
$value = $stmt->fetch();
var_dump ($float_col);

print "\nbuffered cursor, stringify off, fetch_numeric on\n";
$conn->setAttribute( PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
$stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
$stmt->execute();
$stmt->bindColumn('exist', $float_col, PDO::PARAM_LOB);
$value = $stmt->fetch();
var_dump ($float_col);

print "\nbuffered cursor, stringify on, fetch_numeric on\n";
$conn->setAttribute( PDO::ATTR_STRINGIFY_FETCHES, true);
$stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
$stmt->execute();
$stmt->bindColumn('exist', $float_col, PDO::PARAM_LOB);
$value = $stmt->fetch();
var_dump ($float_col);

print "\nbuffered cursor, stringify on, fetch_numeric off\n";
$conn->setAttribute( PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, false);
$stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
$stmt->execute();
$stmt->bindColumn('exist', $float_col, PDO::PARAM_LOB);
$value = $stmt->fetch();
var_dump ($float_col);

$stmt = null;
$conn = null;

?>
--EXPECT--
no buffered cursor, stringify off, fetch_numeric off
string(15) "1234567890.1234"

no buffered cursor, stringify off, fetch_numeric on
string(15) "1234567890.1234"

no buffered cursor, stringify on, fetch_numeric on
string(15) "1234567890.1234"

no buffered cursor, stringify on, fetch_numeric off
string(15) "1234567890.1234"

buffered cursor, stringify off, fetch_numeric off
string(15) "1234567890.1234"

buffered cursor, stringify off, fetch_numeric on
string(15) "1234567890.1234"

buffered cursor, stringify on, fetch_numeric on
string(15) "1234567890.1234"

buffered cursor, stringify on, fetch_numeric off
string(15) "1234567890.1234"

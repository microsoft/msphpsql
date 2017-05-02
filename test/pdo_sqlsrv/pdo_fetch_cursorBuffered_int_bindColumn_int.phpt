--TEST--
prepare with cursor buffered and fetch a int column with the column bound and specified as pdo type int
--SKIPIF--

--FILE--
<?php
require_once("MsSetup.inc");
$conn = new PDO( "sqlsrv:server=$server", $uid, $pwd);
$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

$sample = 1234567890;

$query = 'CREATE TABLE #TESTTABLE (exist int)';
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
$stmt->bindColumn('exist', $int_col, PDO::PARAM_INT);
$value = $stmt->fetch( PDO::FETCH_BOUND );
var_dump ($int_col);

print "\nno buffered cursor, stringify off, fetch_numeric on\n";
$conn->setAttribute( PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
$stmt = $conn->prepare($query);
$stmt->execute();
$stmt->bindColumn('exist', $int_col, PDO::PARAM_INT);
$value = $stmt->fetch( PDO::FETCH_BOUND );
var_dump ($int_col);

print "\nno buffered cursor, stringify on, fetch_numeric on\n";
$conn->setAttribute( PDO::ATTR_STRINGIFY_FETCHES, true);
$stmt = $conn->prepare($query);
$stmt->execute();
$stmt->bindColumn('exist', $int_col, PDO::PARAM_INT);
$value = $stmt->fetch( PDO::FETCH_BOUND );
var_dump ($int_col);

print "\nno buffered cursor, stringify on, fetch_numeric off\n";
$conn->setAttribute( PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, false);
$stmt = $conn->prepare($query);
$stmt->execute();
$stmt->bindColumn('exist', $int_col, PDO::PARAM_INT);
$value = $stmt->fetch( PDO::FETCH_BOUND );
var_dump ($int_col);

//prepare with client buffered cursor
print "\nbuffered cursor, stringify off, fetch_numeric off\n";
$conn->setAttribute( PDO::ATTR_STRINGIFY_FETCHES, false);
$stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
$stmt->execute();
$stmt->bindColumn('exist', $int_col, PDO::PARAM_INT);
$value = $stmt->fetch( PDO::FETCH_BOUND );
var_dump ($int_col);

print "\nbuffered cursor, stringify off, fetch_numeric on\n";
$conn->setAttribute( PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
$stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
$stmt->execute();
$stmt->bindColumn('exist', $int_col, PDO::PARAM_INT);
$value = $stmt->fetch( PDO::FETCH_BOUND );
var_dump ($int_col);

print "\nbuffered cursor, stringify on, fetch_numeric on\n";
$conn->setAttribute( PDO::ATTR_STRINGIFY_FETCHES, true);
$stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
$stmt->execute();
$stmt->bindColumn('exist', $int_col, PDO::PARAM_INT);
$value = $stmt->fetch( PDO::FETCH_BOUND );
var_dump ($int_col);

print "\nbuffered cursor, stringify on, fetch_numeric off\n";
$conn->setAttribute( PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, false);
$stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
$stmt->execute();
$stmt->bindColumn('exist', $int_col, PDO::PARAM_INT);
$value = $stmt->fetch( PDO::FETCH_BOUND );
var_dump ($int_col);

$stmt = null;
$conn = null;

?>
--EXPECT--
no buffered cursor, stringify off, fetch_numeric off
int(1234567890)

no buffered cursor, stringify off, fetch_numeric on
int(1234567890)

no buffered cursor, stringify on, fetch_numeric on
string(10) "1234567890"

no buffered cursor, stringify on, fetch_numeric off
string(10) "1234567890"

buffered cursor, stringify off, fetch_numeric off
int(1234567890)

buffered cursor, stringify off, fetch_numeric on
int(1234567890)

buffered cursor, stringify on, fetch_numeric on
string(10) "1234567890"

buffered cursor, stringify on, fetch_numeric off
string(10) "1234567890"

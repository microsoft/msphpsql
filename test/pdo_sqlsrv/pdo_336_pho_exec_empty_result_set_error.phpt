--TEST--
GitHub issue #336 - PDO::exec should not return an error with query returning SQL_NO_DATA
--DESCRIPTION--
Verifies GitHub issue 336 is fixed, PDO::exec on query returning SQL_NO_DATA will not give an error
--SKIPIF--
--FILE--
<?php
require_once("pdo_tools.inc");

// Connect 
require_once("autonomous_setup.php");

$dbName = "tempdb";

$conn = new PDO("sqlsrv:server=$serverName;Database=$dbName", $username, $password);   
$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );   

$sql = "DELETE FROM foo_table WHERE id = 42";
$sqlWithParameter = "DELETE FROM foo_table WHERE id = :id";
$sqlParameter = 42;

$Statement = $conn->exec("IF OBJECT_ID('foo_table', 'U') IS NOT NULL DROP TABLE foo_table");
$Statement = $conn->exec("CREATE TABLE foo_table (id BIGINT PRIMARY KEY NOT NULL IDENTITY, intField INT NOT NULL)");
$Statement = $conn->exec("INSERT INTO foo_table (intField) VALUES(3)");

//test prepare, not args
$stmt = $conn->prepare($sql);
$stmt->execute();
if ($conn->errorCode() == "00000")
    echo "prepare OK\n";

//test prepare, with args
$stmt = $conn->prepare($sqlWithParameter);
$stmt->execute(array(':id' => $sqlParameter));
if ($conn->errorCode() == "00000")
    echo "prepare with args OK\n";

//test direct exec
$stmt = $conn->exec($sql);
if ($conn->errorCode() == "00000")
    echo "direct exec OK\n";

$Statement = $conn->exec("IF OBJECT_ID('foo_table', 'U') IS NOT NULL DROP TABLE foo_table");

$stmt = NULL;
$Statement = NULL;
$conn = NULL;

?>
--EXPECT--
prepare OK
prepare with args OK
direct exec OK

--TEST--
Test emulate prepare utf8 encoding set at the statement level
--SKIPIF--
--FILE--

<?php
require_once("autonomous_setup.php");

$pdo_options = [];
$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
$pdo_options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
$database = "tempdb";

$connection = new \PDO("sqlsrv:server=$serverName;Database=$database",  $username, $password, $pdo_options);

$pdo_options = array();
$pdo_options[PDO::ATTR_EMULATE_PREPARES] = TRUE;
$pdo_options[PDO::SQLSRV_ATTR_DIRECT_QUERY] = TRUE;
$pdo_options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
$pdo_options[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;
$pdo_options[PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE] = PDO::SQLSRV_CURSOR_BUFFERED;

// Drop
try {
    $st = $connection->prepare("DROP TABLE TEST", $pdo_options);
    $st->execute();
}
catch(\Exception $e) {}

// Recreate
$st = $connection->prepare("CREATE TABLE TEST([id] [int] IDENTITY(1,1) NOT NULL, [name] nvarchar(max))", $pdo_options);
$st->execute();

$prefix = '가각';
$name = '가각ácasa';
$name2 = '가각sample2';
 
$pdo_options[PDO::ATTR_EMULATE_PREPARES] = FALSE;
$st = $connection->prepare("INSERT INTO TEST(name) VALUES(:p0)", $pdo_options);
$st->execute(['p0' => $name]);

$pdo_options[PDO::ATTR_EMULATE_PREPARES] = TRUE;
$st = $connection->prepare("INSERT INTO TEST(name) VALUES(:p0)", $pdo_options);
$st->execute(['p0' => $name2]);
 
$statement = $connection->prepare("SELECT * FROM TEST WHERE NAME LIKE :p0", $pdo_options);
$statement->execute(['p0' => "$prefix%"]);
foreach ($statement as $row) {
  echo "\n" . 'FOUND: ' . $row['name'];
}

$pdo_options = array();
$pdo_options[PDO::ATTR_EMULATE_PREPARES] = FALSE;
$pdo_options[PDO::SQLSRV_ATTR_DIRECT_QUERY] = TRUE;
$pdo_options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
$statement = $connection->prepare("SELECT * FROM TEST WHERE NAME LIKE :p0", $pdo_options);
$statement->execute(['p0' => "$prefix%"]);
foreach ($statement as $row) {
  echo "\n" . 'FOUND: ' . $row['name'];
}
$stmt = NULL;
$connection = NULL;

?>
--EXPECT--
FOUND: 가각ácasa
FOUND: 가각sample2
FOUND: 가각ácasa
FOUND: 가각sample2
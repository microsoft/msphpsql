--TEST--
test query time out at the connection level and statement level
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function queryTimeout($connLevel)
{
    $conn = connect('', array(), PDO::ERRMODE_SILENT);

    $tableName = getTableName();
    createTable($conn, $tableName, array("c1_int" => "int", "c2_varchar" => "varchar(25)"));

    insertRow($conn, $tableName, array("c1_int" => 1, "c2_varchar" => "QueryTimeout 1"));
    insertRow($conn, $tableName, array("c1_int" => 2, "c2_varchar" => "QueryTimeout 2"));

    $query = "SELECT * FROM $tableName";

    if ($connLevel) {
        echo "Setting query timeout as an attribute in connection\n";
        $conn->setAttribute(constant('PDO::SQLSRV_ATTR_QUERY_TIMEOUT'), 1);
        $stmt = $conn->query("WAITFOR DELAY '00:00:03'; $query");
        var_dump($conn->errorInfo());
    } else {
        echo "Setting query timeout in the statement\n";
        $stmt = $conn->prepare("WAITFOR DELAY '00:00:03'; $query", array(constant('PDO::SQLSRV_ATTR_QUERY_TIMEOUT') => 1));
        $stmt->execute();
        var_dump($stmt->errorInfo());
    }

    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);
}

echo "Starting test...\n";
try {
    queryTimeout(true);
    queryTimeout(false);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "Done\n";
?>
--EXPECTREGEX--
Starting test\.\.\.
Setting query timeout as an attribute in connection
array\(3\) \{
  \[0\]=>
  string\(5\) \"HYT00\"
  \[1\]=>
  int\(0\)
  \[2\]=>
  string\(63\) \"\[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Query timeout expired\"
\}
Setting query timeout in the statement
array\(3\) \{
  \[0\]=>
  string\(5\) \"HYT00\"
  \[1\]=>
  int\(0\)
  \[2\]=>
  string\(63\) \"\[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Query timeout expired\"
\}
Done

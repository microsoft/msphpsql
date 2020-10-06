--TEST--
Test for inserting and retrieving encrypted data of datetime and smalldatetime types encrypted
--DESCRIPTION--
Verify that inserting into smalldatetime column (if encrypted) might trigger "Datetime field overflow" error
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

function getColDef($name, $type)
{
    $append = " ENCRYPTED WITH (ENCRYPTION_TYPE = deterministic, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256', COLUMN_ENCRYPTION_KEY = AEColumnKey) ";

    $colDef = '[' . $name . '] ' . $type . $append;
    return $colDef;
}

function createTableEncryptedQuery($conn, $tableName, $columns)
{
    $tsql = "CREATE TABLE $tableName (";
    foreach ($columns as $name => $type) {
        $colDef = getColDef($name, $type) . ', ';
        $tsql .= $colDef;
    }

    $tsql = rtrim($tsql, ', ') . ')';
    return $tsql;

}

function createTablePlainQuery($conn, $tableName, $columns)
{
    $tsql = "CREATE TABLE $tableName (";
    foreach ($columns as $name => $type) {
        $colDef = '[' . $name . '] ' . $type . ', ';
        $tsql .= $colDef;
    }

    $tsql = rtrim($tsql, ', ') . ')';
    return $tsql;
}

try {
    // This test requires to connect with the Always Encrypted feature
    // First check if the system is qualified to run this test
    $dsn = "sqlsrv:Server=$server; Database=$databaseName;";
    $conn = new PDO($dsn, $uid, $pwd);
    $qualified = isAEQualified($conn) && (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

    if ($qualified) {
        unset($conn);

        // Now connect with ColumnEncryption enabled
        $connectionInfo = "ColumnEncryption = Enabled;";
        $conn = new PDO("sqlsrv:server = $server; database=$databaseName; $connectionInfo", $uid, $pwd);
    }

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tableName = 'pdo_datetime_encrypted';
    dropTable($conn, $tableName);
    
    // Define the column definitions
    $columns = array('c1' => 'smalldatetime', 'c2' => 'datetime', 'c3' => 'datetime2(0)', 'c4' => 'datetime2(4)');

    if ($qualified) {
        $tsql = createTableEncryptedQuery($conn, $tableName, $columns);
    } else {
        $tsql = createTablePlainQuery($conn, $tableName, $columns);
    }
    $conn->exec($tsql);
    
    // Insert values that cause errors
    $val1 = '9999-12-31 23:59:59';
    $val2 = null;
    $val3 = null;
    $val4 = '9999-12-31 23:59:59.9999';

    $tsql = "INSERT INTO $tableName (c1, c2, c3, c4) VALUES (?,?,?,?)";
    $stmt = $conn->prepare($tsql);

    $stmt->bindParam(1, $val1);
    $stmt->bindParam(2, $val2);
    $stmt->bindParam(3, $val3);
    $stmt->bindParam(4, $val4);
    
    try {
        $stmt->execute();
    } catch (PDOException $e) {
        $error = ($qualified)? '*Datetime field overflow' : '*The conversion of a nvarchar data type to a smalldatetime data type resulted in an out-of-range value.';
        if (!fnmatch($error, $e->getMessage())) {
            echo "Expected $error but got:\n";
            var_dump($e->getMessage());
        }
    }
    
    // These values should work
    $val1 = '2021-11-03 11:49:00';
    $val2 = '2015-10-23 07:03:00.000';
    $val3 = '0001-01-01 01:01:01';
    
    try {
        $stmt->execute();
    } catch (PDOException $e) {
        echo "Errors unexpected!!\n";
        var_dump($e->getMessage());
    }
  
    unset($stmt);
    
    // Now fetch the values
    $tsql = "SELECT * FROM $tableName";
    
    $stmt = $conn->prepare($tsql);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_NUM);
    var_dump($row);
    
    dropTable($conn, $tableName);

    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    echo $e->getMessage();
}

echo "Done\n";

?>
--EXPECT--
array(4) {
  [0]=>
  string(19) "2021-11-03 11:49:00"
  [1]=>
  string(23) "2015-10-23 07:03:00.000"
  [2]=>
  string(19) "0001-01-01 01:01:01"
  [3]=>
  string(24) "9999-12-31 23:59:59.9999"
}
Done
--TEST--
Test for inserting and retrieving encrypted data of datetime and smalldatetime types encrypted
--DESCRIPTION--
Verify that inserting into smalldatetime column (if encrypted) might trigger "Datetime field overflow" error
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
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
    
    require_once("MsCommon.inc");

    // This test requires to connect with the Always Encrypted feature
    // First check if the system is qualified to run this test
    $options = array('Database' => $database, 'UID' => $userName, 'PWD' => $userPassword, 'ReturnDatesAsStrings' => true);
    $conn = sqlsrv_connect($server, $options);
    if ($conn === false) {
        fatalError("Failed to connect to $server.");
    }

    $qualified = AE\isQualified($conn) && (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    if ($qualified) {
        sqlsrv_close($conn);

        // Now connect with ColumnEncryption enabled
        $connectionOptions = array_merge($options, array('ColumnEncryption' => 'Enabled'));
        $conn = sqlsrv_connect($server, $connectionOptions);
        if ($conn === false) {
            fatalError("Failed to connect to $server.");
        }
    }

    $tableName = 'srv_datetime_encrypted';
    dropTable($conn, $tableName);
    
    // Define the column definitions
    $columns = array('c1' => 'smalldatetime', 'c2' => 'datetime', 'c3' => 'datetime2(0)', 'c4' => 'datetime2(4)');

    if ($qualified) {
        $tsql = createTableEncryptedQuery($conn, $tableName, $columns);
    } else {
        $tsql = createTablePlainQuery($conn, $tableName, $columns);
    }
    
    $stmt = sqlsrv_query($conn, $tsql);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    // Insert values that cause errors
    $val1 = '9999-12-31 23:59:59';
    $val2 = null;
    $val3 = null;
    $val4 = '9999-12-31 23:59:59.9999';

    $tsql = "INSERT INTO $tableName (c1, c2, c3, c4) VALUES (?,?,?,?)";
    $params = array($val1, $val2, $val3, $val4);

    $stmt = sqlsrv_prepare($conn, $tsql, $params);
    if (!$stmt) {
        fatalError("Failed to prepare insert statement");
    }
    $result = sqlsrv_execute($stmt);
    if ($result) {
        echo "Inserting invalid values should have failed!\n";
    } else {
        $error = ($qualified)? '*Datetime field overflow' : '*The conversion of a varchar data type to a smalldatetime data type resulted in an out-of-range value.';
        if (!fnmatch($error, sqlsrv_errors()[0]['message'])) {
            echo "Expected $error but got:\n";
            var_dump(sqlsrv_errors());
        }
    }
    
    sqlsrv_free_stmt($stmt);

    // These values should work
    $val1 = '2021-11-03 11:49:00';
    $val2 = '2015-10-23 07:03:00.000';
    $val3 = '0001-01-01 01:01:01';
    
    $params = array($val1, $val2, $val3, $val4);
    $stmt = sqlsrv_prepare($conn, $tsql, $params);
    if (!$stmt) {
        fatalError("Failed to prepare insert statement");
    }
    $result = sqlsrv_execute($stmt);
    if (!$result) {
        fatalError("Failed to insert valid values\n");
    }

    sqlsrv_free_stmt($stmt);

    // Now fetch the values
    $tsql = "SELECT * FROM $tableName";
    
    $stmt = sqlsrv_query($conn, $tsql);
    if (!$stmt) {
        fatalError("Failed to select from $tableName");
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    var_dump($row);

    dropTable($conn, $tableName);

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    echo "Done\n";

?>
--EXPECT--
array(4) {
  ["c1"]=>
  string(19) "2021-11-03 11:49:00"
  ["c2"]=>
  string(23) "2015-10-23 07:03:00.000"
  ["c3"]=>
  string(19) "0001-01-01 01:01:01"
  ["c4"]=>
  string(24) "9999-12-31 23:59:59.9999"
}
Done

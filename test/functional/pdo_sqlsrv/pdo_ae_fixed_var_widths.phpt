--TEST--
Test Always Encrypted in Windows by comparing fetched values from fields of fixed and variable widths
--DESCRIPTION--
See Internal issue 2824 for details. In the plaintext case, the padding is added by SQL, not the driver. For AE, the motivation was to facilitate matching between char and varchar types, that is, a deterministic encryption of char(10) with “abcd” to match varchar(10 with “abcd”.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

function getColDef($name, $type)
{
    $append = ' ';
    if (stripos($name, "char") !== false) {
        $append .= 'COLLATE Latin1_General_BIN2';
    }
    $append .= " ENCRYPTED WITH (ENCRYPTION_TYPE = deterministic, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256', COLUMN_ENCRYPTION_KEY = AEColumnKey) ";

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

function compareFieldValues($f1, $f2, $qualified)
{
    $matched = true;
    if ($qualified) {
        if ($f1 != $f2) {
            echo "Always Encrypted: values do not match!\n";
            $matched = false;
        }
    } else {
        if (strpos($f1, $f2) != 0) {
            echo "Plain text: values do not match!\n";
            $matched = false;
        };
    }

    if (!$matched) {
        var_dump($f1);
        var_dump($f2);
    }
}

require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

try {
    // This test requires to connect with the Always Encrypted feature
    // First check if the system is qualified to run this test
    $dsn = getDSN($server, null);
    $conn = new PDO($dsn, $uid, $pwd);
    $qualified = isAEQualified($conn) && (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

    if ($qualified) {
        unset($conn);

        // Now connect with ColumnEncryption enabled
        $connectionInfo = "ColumnEncryption = Enabled;";
        $conn = new PDO("sqlsrv:server = $server; database=$databaseName; $connectionInfo", $uid, $pwd);
    }

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tableName = 'pdo_fixed_var_types_ae';
    dropTable($conn, $tableName);

    // Define the column definitions
    $columns = array('c_char' => 'CHAR(10)', 'c_varchar' => 'VARCHAR(10)',
                     'c_nchar' => 'NCHAR(10)', 'c_nvarchar' => 'NVARCHAR(10)',
                     'c_binary' => 'BINARY(10)', 'c_varbinary' => 'VARBINARY(10)');

    if ($qualified) {
        $tsql = createTableEncryptedQuery($conn, $tableName, $columns);
    } else {
        $tsql = createTablePlainQuery($conn, $tableName, $columns);
    }
    $conn->exec($tsql);

    // Insert values
    $values = array('ABCDE', 'ABCDE',
                    'WXYZ', 'WXYZ',
                    '41424344', '41424344');

    $tsql = "INSERT INTO $tableName (c_char, c_varchar, c_nchar, c_nvarchar, c_binary, c_varbinary) VALUES (?,?,?,?,?,?)";
    $stmt = $conn->prepare($tsql);

    for ($i = 0; $i < count($values); $i++) {
        if ($i < 4) {
            $stmt->bindParam($i+1, $values[$i]);
        } else {
            $stmt->bindParam($i+1, $values[$i], PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
        }
    }
    $stmt->execute();
    unset($stmt);

    // Now fetch the values
    if ($qualified) {
        $tsql = "SELECT CAST(c_char AS VARCHAR(10)), c_varchar,
                        CAST(c_nchar AS NVARCHAR(10)), c_nvarchar,
                        CAST(c_binary AS VARBINARY(10)), c_varbinary FROM $tableName";
    } else {
        $tsql = "SELECT c_char, c_varchar,
                        c_nchar, c_nvarchar,
                        c_binary, c_varbinary FROM $tableName";
    }

    $stmt = $conn->prepare($tsql);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_NUM);

    compareFieldValues($row[0], $row[1], $qualified);
    compareFieldValues($row[2], $row[3], $qualified);
    compareFieldValues($row[4], $row[5], $qualified);
   
    dropTable($conn, $tableName);

    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    echo $e->getMessage();
}

echo "Done\n";

?>
--EXPECT--
Done
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

    require_once("MsCommon.inc");

    // This test requires to connect with the Always Encrypted feature
    // First check if the system is qualified to run this test
    $options = array("Database" => $database, "UID" => $userName, "PWD" => $userPassword);
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

    $tableName = 'srv_fixed_var_types_ae';

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
    $stmt = sqlsrv_query($conn, $tsql);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    // Insert values
    $values = array('ABCDE', 'ABCDE',
                    'WXYZ', 'WXYZ',
                    '41424344', '41424344');

    $params = array(
        $values[0],
        $values[1],
        $values[2],
        $values[3],
        array(
            $values[4],
            SQLSRV_PARAM_IN,
            null,
            SQLSRV_SQLTYPE_BINARY(10)
        ),
        array(
            $values[5],
            SQLSRV_PARAM_IN,
            null,
            SQLSRV_SQLTYPE_VARBINARY(10)
        )
    );

    $tsql = "INSERT INTO $tableName (c_char, c_varchar, c_nchar, c_nvarchar, c_binary, c_varbinary) VALUES (?,?,?,?,?,?)";
    $stmt = sqlsrv_prepare($conn, $tsql, $params);
    if (!$stmt) {
        fatalError("Failed to prepare insert statement");
    }
    $result = sqlsrv_execute($stmt);
    if (!$result) {
        fatalError("Failed to insert values");
    }
    sqlsrv_free_stmt($stmt);

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
    $stmt = sqlsrv_query($conn, $tsql);
    if (!$stmt) {
        fatalError("Failed to select from $tableName");
    }

    while (sqlsrv_fetch($stmt)) {
        $f0 = sqlsrv_get_field($stmt, 0);
        $f1 = sqlsrv_get_field($stmt, 1);

        compareFieldValues($f0, $f1, $qualified);

        $f2 = sqlsrv_get_field($stmt, 2);
        $f3 = sqlsrv_get_field($stmt, 3);

        compareFieldValues($f2, $f3, $qualified);

        $f4 = sqlsrv_get_field($stmt, 4, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        $f5 = sqlsrv_get_field($stmt, 5, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));

        compareFieldValues($f4, $f5, $qualified);
    }

    dropTable($conn, $tableName);

    // Close connection
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    print "Done"
?>

--EXPECT--
Done

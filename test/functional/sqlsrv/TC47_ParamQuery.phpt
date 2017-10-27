--TEST--
Parameterized Query Test
--DESCRIPTION--
Verifies that ability to execute a parameterized INSERT query.
The query behavior is checked for all updateable data types.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function paramQuery($minType, $maxType)
{
    $testName = "Parameterized Query";
    startTest($testName);

    setup();
    $tableName = 'TC47test';
    $conn1 = AE\connect();

    for ($k = $minType; $k <= $maxType; $k++) {
        $data = getSampleData($k);
        if ($data != null) {
            $sqlType = getSqlType($k);
            $phpDriverType = getSqlsrvSqlType($k, strlen($data));
            
            if ($k == 10 || $k == 11) {
                // do not encrypt money type -- ODBC restrictions
                $noEncrypt = true;
            } else {
                $noEncrypt = false;
            }
            $columns = array(new AE\ColumnMeta('int', 'c1'),
                             new AE\ColumnMeta($sqlType, 'c2', null, true, $noEncrypt));
            traceData($sqlType, $data);

            $res = null;
            AE\createTable($conn1, $tableName, $columns);
            AE\insertRow($conn1,
                $tableName,
                array("c1"=>$k, "c2"=>array($data, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), $phpDriverType)), 
                $res,
                AE\INSERT_QUERY_PARAMS
            );

            checkData($conn1, $tableName, 2, $data);
            dropTable($conn1, $tableName);
        }
    }

    sqlsrv_close($conn1);

    endTest($testName);
}

function checkData($conn, $tableName, $cols, $expectedValue)
{
    $stmt = AE\selectFromTable($conn, $tableName);
    if (!sqlsrv_fetch($stmt)) {
        die("Table $tableName was not expected to be empty.");
    }
    $numFields = sqlsrv_num_fields($stmt);
    if ($numFields != $cols) {
        die("Table $tableName was expected to have $cols fields.");
    }
    $actualValue = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    sqlsrv_free_stmt($stmt);
    if (strncmp($actualValue, $expectedValue, strlen($expectedValue)) != 0) {
        die("Data corruption: $expectedValue => $actualValue.");
    }
}

try {
    paramQuery(1, 28);
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Parameterized Query" completed successfully.

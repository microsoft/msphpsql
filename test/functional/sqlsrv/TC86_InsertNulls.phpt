--TEST--
PHP - Insert Nulls
--DESCRIPTION--
Test inserting nulls into nullable columns
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function InsertNullsTest($phptype, $sqltype)
{
    $outvar = null;

    $failed = false;

    setup();

    $conn = connect();

    $tableName = 'TC86test';
    dropTable($conn, $tableName);

    createTable($conn, $tableName);

    $stmt = sqlsrv_query(
        $conn,
        <<<SQL
SELECT [TABLE_NAME],[COLUMN_NAME],[IS_NULLABLE] FROM [INFORMATION_SCHEMA].[COLUMNS] WHERE [TABLE_NAME] = '$tableName'
SQL
);

    if ($stmt === false) {
        fatalError("Could not query for column information on table $tableName");
    }

    while ($row = sqlsrv_fetch($stmt)) {
        $tableName = sqlsrv_get_field($stmt, 0);
        $columnName = sqlsrv_get_field($stmt, 1);
        $nullable = sqlsrv_get_field($stmt, 2);

        trace($columnName . ": " . $nullable . "\n");

        if (($nullable == 'YES') && (strpos($columnName, "binary") !== false)) {
            $stmt2 = sqlsrv_prepare(
                $conn,
                "INSERT INTO [$tableName] ([" . $columnName . "]) VALUES (?)",
                array(array( null, SQLSRV_PARAM_IN, $phptype, $sqltype))
            );

            if (!sqlsrv_execute($stmt2)) {
                print_r(sqlsrv_errors(SQLSRV_ERR_ALL));
                $failed = true;
            }
        }
    }


    dropTable($conn, $tableName);

    return $failed;
}



//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    $failed = null;

    $testName = "PHP - Insert Nulls";

    startTest($testName);

    try {
        $failed |= InsertNullsTest(SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), null);
        $failed |= InsertNullsTest(null, SQLSRV_SQLTYPE_VARBINARY('10'));
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    if ($failed) {
        fatalError("Possible Regression: Could not insert NULL");
    }

    endTest($testName);
}

repro();

?>
--EXPECT--
Test "PHP - Insert Nulls" completed successfully.

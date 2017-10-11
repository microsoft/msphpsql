--TEST--
Fetch Field Test
--DESCRIPTION--
Verifies the ability to successfully retrieve field data via "sqlsrv_get_field" by
retrieving fields from a table including rows with all supported SQL types (28 types).
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function FetchFields()
{
    include 'MsSetup.inc';

    $testName = "Fetch - Field";
    startTest($testName);

    setup();
    if (! isWindows()) {
        $conn1 = connect(array( 'CharacterSet'=>'UTF-8' ));
    } else {
        $conn1 = connect();
    }

    createTable($conn1, $tableName);

    $noRows = 10;
    $noRowsInserted = insertRows($conn1, $tableName, $noRows);

    $stmt1 = selectFromTable($conn1, $tableName);
    $numFields = sqlsrv_num_fields($stmt1);

    trace("Retrieving $noRowsInserted rows with $numFields fields each ...");
    for ($i = 0; $i < $noRowsInserted; $i++) {
        $row = sqlsrv_fetch($stmt1);
        if ($row === false) {
            fatalError("Row $i is missing");
        }
        for ($j = 0; $j < $numFields; $j++) {
            $fld = sqlsrv_get_field($stmt1, $j);
            if ($fld === false) {
                fatalError("Field $j of Row $i is missing\n");
            }
        }
    }
    sqlsrv_free_stmt($stmt1);
    trace(" completed successfully.\n");

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    try {
        FetchFields();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

repro();

?>
--EXPECT--
Test "Fetch - Field" completed successfully.

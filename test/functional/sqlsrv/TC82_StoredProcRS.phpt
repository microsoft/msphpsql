--TEST--
Complex Stored Proc Test
--DESCRIPTION--
Test output string parameters with rows affected return results before output parameter.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function storedProcCheck()
{
    $testName = "ResultSet with Stored Proc";

    startTest($testName);

    setup();
    $tableName = 'TC82test';
    $procName = "TC82test_proc";
    $conn1 = AE\connect();

    $table1 = $tableName."_1";
    $table2 = $tableName."_2";
    $table3 = $tableName."_3";
    $date = date("Y-m-d H:i:s"); 
    // When AE is enabled, the size must match exactly
    $size = AE\isColEncrypted() ? "256" : "max";
    
    $procArgs = "@p1 int, @p2 nchar(32), @p3 nvarchar(64), @p4 datetime, @p5 nvarchar($size) OUTPUT";
    $introText="Initial Value";
    $callArgs = array(array(1, SQLSRV_PARAM_IN, 
                            SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT),
                      array('Dummy No', SQLSRV_PARAM_IN, 
                            SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), 
                            SQLSRV_SQLTYPE_NCHAR(32)),
                      array('Dummy ID', SQLSRV_PARAM_IN, 
                            SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), 
                            SQLSRV_SQLTYPE_NVARCHAR(50)),
                      array($date, SQLSRV_PARAM_IN, 
                            SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), 
                            SQLSRV_SQLTYPE_DATETIME),
                      array(&$introText, SQLSRV_PARAM_OUT, 
                            SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), 
                            SQLSRV_SQLTYPE_NVARCHAR(256)));
    $procCode =
        "IF (@p3 IS NULL)
        BEGIN
            INSERT INTO [$table1] (DataID, ExecTime, DataNo) values (@p1, @p4, @p2)
        END
        ELSE
        BEGIN
            INSERT INTO [$table1] (DataID, ExecTime, DataNo, DataRef) values (@p1, @p4, @p2, @p3)
        END
        INSERT INTO [$table2] (DataID, DataNo) values (@p1, @p2)
        SELECT @p5=(SELECT Intro from [$table3] WHERE DataID=@p1) ";

    $columns1 = array(new AE\ColumnMeta("int", "DataID"),
                      new AE\ColumnMeta("datetime", "ExecTime"),
                      new AE\ColumnMeta("nchar(32)", "DataNo"),
                      new AE\ColumnMeta("nvarchar(64)", "DataRef"));
    AE\createTable($conn1, $table1, $columns1);
    
    $columns2 = array(new AE\ColumnMeta("int", "DataID"),
                      new AE\ColumnMeta("nchar(32)", "DataNo"));
    AE\createTable($conn1, $table2, $columns2);

    $columns3 = array(new AE\ColumnMeta("int", "DataID"),
                      new AE\ColumnMeta("nvarchar($size)", "Intro"));
    AE\createTable($conn1, $table3, $columns3);

    createProc($conn1, $procName, $procArgs, $procCode);

    $r = null;
    $stmt1 = AE\insertRow($conn1, $table3, array("DataID" => 1, "Intro" => 'Test Value 1'), $r, AE\INSERT_PREPARE_PARAMS);
    insertCheck($stmt1);

    $stmt2 = AE\insertRow($conn1, $table3, array("DataID" => 2, "Intro" => 'Test Value 2'), $r, AE\INSERT_PREPARE_PARAMS);
    insertCheck($stmt2);

    $stmt3 = AE\insertRow($conn1, $table3, array("DataID" => 3, "Intro" => 'Test Value 3'), $r, AE\INSERT_PREPARE_PARAMS);
    insertCheck($stmt3);

    $stmt4 = callProcEx($conn1, $procName, "", "?, ?, ?, ?, ?", $callArgs);
    $result = sqlsrv_next_result($stmt4);
    while ($result != null) {
        if ($result === false) {
            fatalError("Failed to execute sqlsrv_next_result");
        }
        $result = sqlsrv_next_result($stmt4);
    }
    sqlsrv_free_stmt($stmt4);

    dropProc($conn1, $procName);

    echo "$introText\n";

    dropTable($conn1, $table1);
    dropTable($conn1, $table2);
    dropTable($conn1, $table3);
    sqlsrv_close($conn1);

    endTest($testName);
}

try {
    storedProcCheck();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test Value 1
Test "ResultSet with Stored Proc" completed successfully.

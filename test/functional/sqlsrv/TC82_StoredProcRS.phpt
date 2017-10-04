--TEST--
Complex Stored Proc Test
--DESCRIPTION--
Test output string parameters with rows affected return results before output parameter.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function StoredProcCheck()
{
    include 'MsSetup.inc';
    
    $testName = "ResultSet with Stored Proc";

    startTest($testName);

    setup();

    $conn1 = connect();

    $table1 = $tableName."_1";
    $table2 = $tableName."_2";
    $table3 = $tableName."_3";
    $procArgs = "@p1 int, @p2 nchar(32), @p3 nvarchar(64), @p4 nvarchar(max) OUTPUT";
    $introText="Initial Value";
    $callArgs = array(array(1, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT),
                  array('Dummy No', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NCHAR(32)),
                      array('Dummy ID', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(50)),
                  array( &$introText, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(256)));
    $procCode =
        "IF (@p3 IS NULL)
        BEGIN
            INSERT INTO [$table1] (DataID, ExecTime, DataNo) values (@p1, GETDATE(), @p2)
        END
        ELSE
        BEGIN
            INSERT INTO [$table1] (DataID, ExecTime, DataNo, DataRef) values (@p1, GETDATE(), @p2, @p3)
        END
        INSERT INTO [$table2] (DataID, DataNo) values (@p1, @p2)
        SELECT @p4=(SELECT Intro from [$table3] WHERE DataID=@p1) ";

    createTableEx($conn1, $table1, "DataID int, ExecTime datetime, DataNo nchar(32), DataRef nvarchar(64)");
    createTableEx($conn1, $table2, "DataID int, DataNo nchar(32)");
    createTableEx($conn1, $table3, "DataID int, Intro nvarchar(max)");
    createProc($conn1, $procName, $procArgs, $procCode);

    $stmt1 = sqlsrv_query($conn1, "INSERT INTO [$table3] (DataID, Intro) VALUES (1, 'Test Value 1')");
    insertCheck($stmt1);

    $stmt2 = sqlsrv_query($conn1, "INSERT INTO [$table3] (DataID, Intro) VALUES (2, 'Test Value 2')");
    insertCheck($stmt2);

    $stmt3 = sqlsrv_query($conn1, "INSERT INTO [$table3] (DataID, Intro) VALUES (3, 'Test Value 3')");
    insertCheck($stmt3);

    $stmt4 = callProcEx($conn1, $procName, "", "?, ?, ?, ?", $callArgs);
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


//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    try {
        StoredProcCheck();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

repro();

?>
--EXPECT--
Test Value 1
Test "ResultSet with Stored Proc" completed successfully.

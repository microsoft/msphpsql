--TEST--
Prepare and Execute Test
--DESCRIPTION--
Checks the data returned by a query first prepared and then executed multiple times.
Validates that a prepared statement can be successfully executed more than once.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function PrepareAndExecute($noPasses)
{
    include 'MsSetup.inc';

    $testName = "Statement - Prepare and Execute";
    StartTest($testName);

    Setup();
    $conn1 = Connect();
    CreateTable($conn1, $tableName);

    InsertRows($conn1, $tableName, 1);

    $values = array();
    $fieldlVal = "";

    // Prepare reference values
    Trace("Execute a direct SELECT query on $tableName ...");
    $stmt1 = SelectFromTable($conn1, $tableName);
    $numFields1 = sqlsrv_num_fields($stmt1);
    sqlsrv_fetch($stmt1);
    for ($i = 0; $i < $numFields1; $i++)
    {
        if (UseUTF8Data()){    
            $fieldVal = sqlsrv_get_field($stmt1, $i, SQLSRV_PHPTYPE_STRING('UTF-8'));
        }
        else{
            $fieldVal = sqlsrv_get_field($stmt1, $i, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        }
        if ($fieldVal === false)
        {
            FatalError("Failed to retrieve field $i");
        }
        $values[$i] = $fieldVal;
    }
    sqlsrv_free_stmt($stmt1);
    Trace(" $numFields1 fields retrieved.\n"); 

    // Prepare once and execute several times
    Trace("Prepare a SELECT query on $tableName ...");
    $stmt2 = PrepareQuery($conn1, "SELECT * FROM [$tableName]");
    $numFields2 = sqlsrv_num_fields($stmt2);
    Trace(" $numFields2 fields expected.\n"); 
    if ($numFields2 != $numFields1)
    {
        SetUTF8Data(false);
        die("Incorrect number of fields: $numFields2");
    }

    for ($j = 0; $j < $noPasses; $j++)
    {
        Trace("Executing the prepared query ...");
        sqlsrv_execute($stmt2);
        sqlsrv_fetch($stmt2);
        for ($i = 0; $i < $numFields2; $i++)
        {
            if (UseUTF8Data()){
                $fieldVal = sqlsrv_get_field($stmt2, $i, SQLSRV_PHPTYPE_STRING('UTF-8'));
            }
            else{
                $fieldVal = sqlsrv_get_field($stmt2, $i, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
            }
            if ($fieldVal === false)
            {
                FatalError("Failed to retrieve field $i");
            }
            if ($values[$i] != $fieldVal)
            {
                SetUTF8Data(false);
                die("Incorrect value for field $i at iteration $j");
            }
        }
        Trace(" $numFields2 fields verified.\n"); 
    }
    sqlsrv_free_stmt($stmt2);

    DropTable($conn1, $tableName);  
    
    sqlsrv_close($conn1);

    EndTest($testName); 
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    if (! IsWindows())
    {
        SetUTF8Data(true);
    }
    try
    {
        PrepareAndExecute(5);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    SetUTF8Data(false);
}

Repro();

?>
--EXPECT--
Test "Statement - Prepare and Execute" completed successfully.
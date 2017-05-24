--TEST--
Parameterized Query Test
--DESCRIPTION--
Verifies that ability to execute a parameterized INSERT query.
The query behavior is checked for all updateable data types.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function ParamQuery($minType, $maxType)
{
    include 'MsSetup.inc';

    $testName = "Parameterized Query";
    StartTest($testName);

    Setup();
    $conn1 = Connect();
    
    for ($k = $minType; $k <= $maxType; $k++)
    {
        $data = GetSampleData($k);
        if ($data != null)
        {
            $sqlType = GetSqlType($k);
            $phpDriverType = GetDriverType($k, strlen($data));
            $dataType = "[c1] int, [c2] $sqlType";
            $dataOptions = array($k, array($data, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), $phpDriverType));

            TraceData($sqlType, $data);
            CreateQueryTable($conn1, $tableName, $dataType, "c1, c2", "?, ?", $dataOptions);
            CheckData($conn1, $tableName, 2, $data);
            DropTable($conn1, $tableName);
        }
    }   

    sqlsrv_close($conn1);

    EndTest($testName);

}


function CreateQueryTable($conn, $table, $dataType, $dataCols, $dataValues, $dataOptions)
{
    CreateTableEx($conn, $table, $dataType);
    InsertRowEx($conn, $table, $dataCols, $dataValues, $dataOptions);
}

function CheckData($conn, $table, $cols, $expectedValue)
{
    $stmt = SelectFromTable($conn, $table);
    if (!sqlsrv_fetch($stmt))
    {
        die("Table $tableName was not expected to be empty.");
    }
    $numFields = sqlsrv_num_fields($stmt);
    if ($numFields != $cols)
    {
        die("Table $tableName was expected to have $cols fields.");
    }
    $actualValue = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    sqlsrv_free_stmt($stmt);
    if (strncmp($actualValue, $expectedValue, strlen($expectedValue)) != 0)
    {
        die("Data corruption: $expectedValue => $actualValue.");
    }
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    try
    {
        ParamQuery(1, 28);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Parameterized Query" completed successfully.

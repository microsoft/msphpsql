--TEST--
Fetch Object Test
--DESCRIPTION--
Verifies data retrieval via “sqlsrv_fetch_object”.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

class TestClass
{
    function __construct($a1, $a2, $a3)
    {

    }
}

function FetchRow($minFetchMode, $maxFetchMode)
{
    include 'MsSetup.inc';

    $testName = "Fetch - Object";
    StartTest($testName);

    Setup();
    if (! IsWindows())
        $conn1 = ConnectUTF8();
    else 
        $conn1 = Connect();
    CreateTable($conn1, $tableName);

    $noRows = 10;
    $noRowsInserted = InsertRows($conn1, $tableName, $noRows);

    $actual = null;
    $expected = null;
    $numFields = 0;
    for ($k = $minFetchMode; $k <= $maxFetchMode; $k++)
    {
        $stmt1 = SelectFromTable($conn1, $tableName);
        if ($numFields == 0)
        {
            $numFields = sqlsrv_num_fields($stmt1);
        }
        else
        {
            $count = sqlsrv_num_fields($stmt1);
            if ($count != $numFields)
            {
                die("Unexpected number of fields: $count");
            }
        }

        switch ($k)
        {
        case 0:		// fetch array (to retrieve reference values)
            $expected = FetchArray($stmt1, $noRowsInserted, $numFields);
            break;

        case 1:		// fetch object (without class)
            $actual = FetchObject($stmt1, $noRowsInserted, $numFields, false);
            CheckData($noRowsInserted, $numFields, $actual, $expected);
            break;

        case 2:		// fetch object (with class)
            $actual = FetchObject($stmt1, $noRowsInserted, $numFields, true);
            CheckData($noRowsInserted, $numFields, $actual, $expected);
            break;

        default:	// default
            break;
        }
        sqlsrv_free_stmt($stmt1);
    }

    DropTable($conn1, $tableName);	
    
    sqlsrv_close($conn1);

    EndTest($testName);	
}


function FetchObject($stmt, $rows, $fields, $useClass)
{
    Trace("\tRetrieving $rows objects with $fields fields each ...\n");
    $values = array();
    for ($i = 0; $i < $rows; $i++)
    {
        if ($useClass)
        {
            $obj = sqlsrv_fetch_object($stmt, "TestClass", array(1, 2, 3));
        }
        else
        {
            $obj = sqlsrv_fetch_object($stmt);
        }
        if ($obj === false)
        {
            FatalError("Row $i is missing");
        }
        $values[$i] = $obj;
    }
    return ($values);
}


function FetchArray($stmt, $rows, $fields)
{
    $values = array();
    for ($i = 0; $i < $rows; $i++)
    {
        $row = sqlsrv_fetch_array($stmt);
        if ($row === false)
        {
            FatalError("Row $i is missing");
        }
        $values[$i] = $row;
    }
    return ($values);
}


function CheckData($rows, $fields, $actualValues, $expectedValues)
{
    if (($actualValues != null) && ($expectedValues != null))
    {
        for ($i = 0; $i < $rows; $i++)
        {
            for ($j = 0; $j < $fields; $j++)
            {
                $colName = GetColName($j + 1);
                $actual = $actualValues[$i]->$colName;
                $expected = $expectedValues[$i][$colName];
                if ($actual != $expected)
                {
                    die("Data corruption on row ".($i + 1)." column ".($j + 1).": $expected => $actual");
                }
            }
        }
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
        FetchRow(0, 2);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Fetch - Object" completed successfully.

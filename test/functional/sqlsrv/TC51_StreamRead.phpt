--TEST--
Stream Read Test
--DESCRIPTION--
Verifies that all SQL types defined as capable of streaming (13 types)
can be successfully retrieved as streams.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function StreamRead($noRows, $startRow)
{
    include 'MsSetup.inc';

    $testName = "Stream - Read";
    StartTest($testName);

    Setup();
    if (! IsWindows())
        $conn1 = ConnectUTF8();
    else 
        $conn1 = Connect();

    CreateTable($conn1, $tableName);
    InsertRowsByRange($conn1, $tableName, $startRow, $startRow + $noRows - 1);

    $query = "SELECT * FROM [$tableName] ORDER BY c27_timestamp";
    $stmt1 = SelectQuery($conn1, $query);
    $numFields = sqlsrv_num_fields($stmt1);

    for ($row = 1; $row <= $noRows; $row++)
    {
        if (!sqlsrv_fetch($stmt1))
        {
            FatalError("Failed to fetch row ".$row);
        }
        Trace("\nStreaming row $row:\n");
        $skipCount = 0;
        for ($j = 0; $j < $numFields; $j++)
        {
            $col = $j + 1;
            if (!IsUpdatable($col))
            {
                $skipCount++;
            }
            if (IsStreamable($col))
            {
                VerifyStream($stmt1, $startRow + $row - 1, $j, $skipCount);
            }
        }
    }

    sqlsrv_free_stmt($stmt1);
    
    DropTable($conn1, $tableName);	
    
    sqlsrv_close($conn1);

    EndTest($testName);
}

function VerifyStream($stmt, $row, $colIndex, $skip)
{
    $col = $colIndex + 1;
    if (IsStreamable($col))
    {
        $type = GetSqlType($col);
        if (IsBinary($col))
        {
            $stream = sqlsrv_get_field($stmt, $colIndex, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
        }
        else
        {
            if (UseUTF8Data()){
                $stream = sqlsrv_get_field($stmt, $colIndex, SQLSRV_PHPTYPE_STREAM('UTF-8'));
            }
            else{
                $stream = sqlsrv_get_field($stmt, $colIndex, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR));
            }
        }
        if ($stream === false)
        {
            FatalError("Failed to read field $col: $type");
        }
        else
        {
            $value = '';
            if ($stream)
            {
                while (!feof($stream))
                {
                    $value .= fread($stream, 8192);
                }
                fclose($stream);
                $data = GetInsertData($row, $col, $skip);
                if (!CheckData($col, $value, $data))
                {
                    SetUTF8Data(false);
                    Trace("Data corruption on row $row column $col\n");
                    die("Data corruption on row $row column $col\n");
                }
            }
            TraceData($type, "".strlen($value)." bytes");
        }
    }
}


function CheckData($col, $actual, $expected)
{
    $success = true;

    if (IsBinary($col))
    {
        $actual = bin2hex($actual);
        if (strncasecmp($actual, $expected, strlen($expected)) != 0)
        {
            $success = false;
        }
    }
    else
    {
        if (strncasecmp($actual, $expected, strlen($expected)) != 0)
        {
            if ($col != 19)
            {	// skip ntext
                $pos = strpos($actual, $expected);
                if (($pos === false) || ($pos > 1))
                {
                    $success = false;
                }
            }
        }
    }
    if (!$success)
    {
        Trace("\nData error\nExpected:\n$expected\nActual:\n$actual\n");
    }

    return ($success);
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
        StreamRead(20, 1);
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
Test "Stream - Read" completed successfully.


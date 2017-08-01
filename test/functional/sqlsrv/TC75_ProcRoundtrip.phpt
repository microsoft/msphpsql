--TEST--
Data Roundtrip via Stored Proc
--DESCRIPTION--
Verifies that data is not corrupted through a roundtrip via a store procedure.
Checks all character data types.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function StoredProcRoundtrip($minType, $maxType)
{
    include 'MsSetup.inc';

    $testName = "Stored Proc Roundtrip";
    StartTest($testName);

    Setup();
    $conn1 = Connect();

    $data = "The quick brown fox jumps over the lazy dog 0123456789";
    $dataSize = strlen($data);

    for ($i = $minType; $i <= $maxType; $i++)
    {
        $dataTypeIn = GetSqlType($i);
        $phpTypeIn = GetDriverType($i, $dataSize);

        for ($j = $minType; $j <= $maxType; $j++)
        {
            $k = $j;
            switch ($j)
            {   // avoid LOB types as output
                case 14:        // varchar(max)
                case 18:        // text
                    $k = 13;    // varchar
                    break;

                case 17:        // nvarchar(max)
                case 19:        // ntext
                    $k = 16;    // nvarchar
                    break;

                default:
                    break;
                
            }
            $dataTypeOut = GetSqlType($k);
            $phpTypeOut = GetDriverType($k, 512);
            ExecProcRoundtrip($conn1, $procName, $dataTypeIn, $dataTypeOut, $phpTypeIn, $phpTypeOut, $data);
        }
    }   

    sqlsrv_close($conn1);

    EndTest($testName);
}


function ExecProcRoundtrip($conn, $procName, $dataTypeIn, $dataTypeOut, $phpTypeIn, $phpTypeOut, $dataIn)
{
    $procArgs = "@p1 $dataTypeIn, @p2 $dataTypeOut OUTPUT";
    $procCode = "SELECT @p2 = CONVERT($dataTypeOut, @p1)";
    CreateProc($conn, $procName, $procArgs, $procCode);

    $callArgs = "?, ?";
    $callResult = "";
    $callValues = array(array($dataIn, SQLSRV_PARAM_IN, null, $phpTypeIn), 
                array(&$callResult, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), $phpTypeOut));
    CallProc($conn, $procName, $callArgs, $callValues);
    DropProc($conn, $procName);

    $dataOut = trim($callResult);

    if (strncmp($dataOut, $dataIn, strlen($dataIn)) != 0) 
    {
        TraceData($dataTypeIn."=>".$dataTypeOut, "\n In: [".$dataIn."]\nOut: [".$dataOut."]");
        die("Unexpected result for ".$dataTypeIn."=>".$dataTypeOut);
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
        StoredProcRoundtrip(12, 19);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Stored Proc Roundtrip" completed successfully.
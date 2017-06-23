--TEST--
Stream Prepared Test
--DESCRIPTION--
Verifies that all SQL types defined as capable of streaming (13 types)
can be successfully uploaded as streams via sqlsrv_prepare/sqlsvr_query.
Validates that a warning message is issued when parameters are not passed by reference.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function SendStream($minType, $maxType)
{
    include 'MsSetup.inc';

    $testName = "Stream - Prepared Send";
    StartTest($testName);

    Setup();
    $conn1 = Connect();

    for ($k = $minType; $k <= $maxType; $k++)
    {
        switch ($k)
        {
        case 12:    // char
        case 13:    // varchar
        case 14:    // varchar(max)
        case 15:    // nchar
        case 16:    // nvarchar
        case 17:    // nvarchar(max)
        case 18:    // text
        case 19:    // ntext
            $data = "The quick brown fox jumps over the lazy dog 0123456789";
            break;

        case 20:    // binary
        case 21:    // varbinary
        case 22:    // varbinary(max)
        case 23:    // image
            $data = "01234567899876543210";
            break;

        default:
            die("Unexpected data type: $k.");
            break;
        }

        $fname1 = fopen($fileName, "w");
        fwrite($fname1, $data);
        fclose($fname1);
        $fname2 = fopen($fileName, "r");

        $sqlType = GetSqlType($k);
        $phpDriverType = GetDriverType($k, strlen($data));

        $dataType = "[c1] int, [c2] $sqlType";
        $dataOptions = array(array($k, SQLSRV_PARAM_IN),
                     array(&$fname2, SQLSRV_PARAM_IN, null, $phpDriverType));
        TraceData($sqlType, $data);

        CreateTableEx($conn1, $tableName, $dataType);
        InsertData($conn1, $tableName, "c1, c2", "?, ?", $dataOptions);
        CheckData($conn1, $tableName, 2, $data);

        fclose($fname2);
    }

    DropTable($conn1, $tableName);  
    
    sqlsrv_close($conn1);

    EndTest($testName); 
}


function InsertData($conn, $tableName, $dataCols, $dataValues, $dataOptions)
{
    $sql = "INSERT INTO [$tableName] ($dataCols) VALUES ($dataValues)";
    $stmt = sqlsrv_prepare($conn, $sql, $dataOptions);
    if ($stmt === false)
    {
        FatalError("Failed to prepare insert query: ".$sql);
    }
    $outcome = sqlsrv_execute($stmt);
    if ($outcome === false)
    {
        FatalError("Failed to execute prepared query: ".$sql);
    }
    while (sqlsrv_send_stream_data($stmt))
    {
    }
    $numRows = sqlsrv_rows_affected($stmt);
    sqlsrv_free_stmt($stmt);
    if ($numRows != 1)
    {
        die("Unexpected row count at insert: ".$numRows);   
    }
}


function CheckData($conn, $table, $cols, $expectedValue)
{
    $stmt = SelectFromTable($conn, $table);
    if (!sqlsrv_fetch($stmt))
    {
        FatalError("Table $tableName was not expected to be empty.");
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
        SendStream(12, 23);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECTREGEX--

Test "Stream - Prepared Send" completed successfully.



--TEST--
PDO - Insert Nulls
--DESCRIPTION--
Test inserting nulls into nullable columns
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function InsertNullsTest($bindType)
{
    include 'MsSetup.inc';
  
    $outvar = null;

    $failed = false;

    Setup();

    $conn = Connect();

    DropTable($conn, $tableName);

    CreateTable($conn, $tableName);

    $stmt = $conn->query(<<<SQL
SELECT [TABLE_NAME],[COLUMN_NAME],[IS_NULLABLE] FROM [INFORMATION_SCHEMA].[COLUMNS] WHERE [TABLE_NAME] = '$tableName'
SQL
);

    if ($stmt === false)
    {
        FatalError("Could not query for column information on table $tableName");
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
    {
        Trace($row['COLUMN_NAME'] . ": " . $row['IS_NULLABLE'] . "\n");

        $stmt2 = $conn->prepare("INSERT INTO [$tableName] ([" . $row['COLUMN_NAME'] . "]) VALUES (:p1)");

        if (strpos($row['COLUMN_NAME'], "timestamp") !== false) continue;

        if (($row['IS_NULLABLE'] == 'YES') && (strpos($row['COLUMN_NAME'], "binary") !== false))
        {
            if ($bindType == PDO::PARAM_LOB)
            {
                $stmt2->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_BINARY);
                $stmt2->bindValue(":p1", null, $bindType);
            }
            else if ($bindType == PDO::PARAM_STR)
            {
                $stmt2->bindParam(":p1", $outvar, $bindType, null, PDO::SQLSRV_ENCODING_BINARY);
            }
        }
        else
        {
            $stmt2->bindParam(":p1", $outvar);
        }

        $stmt2->execute();

        if ($stmt2->errorCode() !== '00000')
        {
            print_r($stmt2->errorInfo());

            $failed = true;             
        }
    }

    DropTable($conn, $tableName);

    return($failed);
}



//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    $failed = null;
    
    $testName = "PDO - Insert Nulls";

    StartTest($testName);

    try
    {
        $failed |= InsertNullsTest(PDO::PARAM_LOB);
        $failed |= InsertNullsTest(PDO::PARAM_STR);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }

    if ($failed)
        FatalError("Possible Regression: Could not insert NULL");
}

Repro();

?>
--EXPECT--

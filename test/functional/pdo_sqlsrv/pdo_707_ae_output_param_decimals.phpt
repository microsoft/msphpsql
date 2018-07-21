--TEST--
GitHub issue 707 - binding decimals/numerics to integers or booleans with ColumnEncryption
--DESCRIPTION--
Verifies that the double values will be rounded as integers or returned as booleans
The key of this test is to connect with ColumnEncryption enabled, and the table columns
do not need to be encrypted
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

$error = "Error converting a double (value out of range) to an integer";

function getOutputs($stmt, $outSql, $id, $pdoParamType, $inout = false) 
{
    $dec = $num = 0;
    
    if ($inout) {
        $paramType = $pdoParamType | PDO::PARAM_INPUT_OUTPUT;
    } else {
        $paramType = $pdoParamType;
    }

    $stmt->bindParam(1, $id, PDO::PARAM_INT);
    $stmt->bindParam(2, $dec, $paramType, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
    $stmt->bindParam(3, $num, $paramType, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
    
    $stmt->execute();
    
    if ($pdoParamType == PDO::PARAM_BOOL) {
        if (!$dec || !$num) {
            echo "The returned booleans ($dec, $num) were unexpected!\n";
        }
    } else {
        if ($dec != 100 || $num != 200) {
            echo "The returned integers ($dec, $num) were unexpected!\n";
        }
    }
}

function getOutputsWithException($stmt, $outSql, $id, $pdoParamType, $inout = false)
{
    global $error;
    
    try {
        getOutputs($stmt, $outSql, $id, $pdoParamType, $inout);
    } catch (PDOException $e) {
        $message = $e->getMessage();
        $found = strpos($message, $error);
        if ($found === false) {
            echo "Exception message unexpected!\n";
        }
    }
}

function getSmallNumbers($conn, $outSql)
{
    $stmt = $conn->prepare($outSql);
    getOutputs($stmt, $outSql, 1, PDO::PARAM_BOOL);
    getOutputs($stmt, $outSql, 1, PDO::PARAM_INT);

    getOutputs($stmt, $outSql, 1, PDO::PARAM_BOOL, true);
    getOutputs($stmt, $outSql, 1, PDO::PARAM_INT, true);
    
    unset($stmt);
}

function getHugeNumbers($conn, $outSql)
{
    // Expects an exception for each call
    $stmt = $conn->prepare($outSql);
    
    getOutputsWithException($stmt, $outSql, 2, PDO::PARAM_BOOL);
    getOutputsWithException($stmt, $outSql, 2, PDO::PARAM_INT);

    getOutputsWithException($stmt, $outSql, 2, PDO::PARAM_BOOL, true);
    getOutputsWithException($stmt, $outSql, 2, PDO::PARAM_INT, true);

    unset($stmt);
}

try {
    // Check eligibility
    $conn = new PDO( "sqlsrv:server = $server", $uid, $pwd );
    if (!isAEQualified($conn)) {
        echo "Done\n";
        return;
    }
    unset($conn);

    // Connection with column encryption enabled
    $connectionInfo = "ColumnEncryption = Enabled;";
    $conn = new PDO("sqlsrv:server = $server; database=$databaseName; $connectionInfo", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tableName = "test_707_decimals";
    $procName = "sp_test_707_decimals";
    
    dropTable($conn, $tableName);
    dropProc($conn, $procName);
    
    // Create a test table
    $tsql = "CREATE TABLE $tableName (id int identity(1,1), c1_decimal decimal(19,4), c2_numeric numeric(20, 6))";
    $stmt = $conn->query($tsql);
    unset($stmt);
    
    // Insert two rows
    $tsql = "INSERT INTO $tableName (c1_decimal, c2_numeric) VALUES (100.078, 200.034)";
    $stmt = $conn->query($tsql);
    unset($stmt);

    $tsql = "INSERT INTO $tableName (c1_decimal, c2_numeric) VALUES (199999999999.0123, 999243876923.09887)";
    $stmt = $conn->query($tsql);
    unset($stmt);

    // Create a stored procedure
    $procArgs = "@id int, @c_decimal decimal(19,4) OUTPUT, @c_numeric numeric(20, 6) OUTPUT";
    $procCode = "SELECT @c_decimal = c1_decimal, @c_numeric = c2_numeric FROM $tableName WHERE id = @id";
    createProc($conn, $procName, $procArgs, $procCode);

    // Read them back by calling the stored procedure
    $outSql = getCallProcSqlPlaceholders($procName, 3);
    getSmallNumbers($conn, $outSql);
    getHugeNumbers($conn, $outSql);
    
    dropProc($conn, $procName);
    dropTable($conn, $tableName);
    
    unset($conn);
    echo "Done\n";
} catch( PDOException $e ) {
    print_r( $e->getMessage() );
}

?>
--EXPECT--
Done
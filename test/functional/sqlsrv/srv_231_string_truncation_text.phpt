--TEST--
GitHub issue #231 - String truncation when binding text/ntext/image to check error messages
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

sqlsrv_configure('WarningsReturnAsErrors', 1);

require_once("MsCommon.inc");

// connect
$conn = AE\connect();
$tableName = 'testLOBTypes_GH231_lob';
$columnNames = array("c1", "c2");

for ($k = 1; $k <= 3; $k++) {
    $sqlType = SQLType($k);
    $columns = array(new AE\ColumnMeta('int', $columnNames[0]),
                     new AE\ColumnMeta($sqlType, $columnNames[1]));
    AE\createTable($conn, $tableName, $columns);

    $sql = "INSERT INTO [$tableName] ($columnNames[0], $columnNames[1]) VALUES (?, ?)";
    $data = getData($k);

    $phpType = SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR);
    $sqlsrvSQLType = sqlsrvSqlType($k, strlen($data));
    
    $params = array($k, array($data, SQLSRV_PARAM_IN, $phpType, $sqlsrvSQLType));
    $stmt = sqlsrv_prepare($conn, $sql, $params);
    sqlsrv_execute($stmt);
    sqlsrv_free_stmt($stmt);

    execProc($conn, $tableName, $columnNames, $k, $data, $sqlType);

    dropTable($conn, $tableName);
}

sqlsrv_close($conn);


function execProc($conn, $tableName, $columnNames, $k, $data, $sqlType)
{
    $spArgs = "@p1 int, @p2 $sqlType OUTPUT";
    $spCode = "SET @p2 = ( SELECT c2 FROM $tableName WHERE c1 = @p1 )";
    $procName = "testBindOutSp";
    
    dropProc($conn, $procName);
    $stmt1 = sqlsrv_query($conn, "CREATE PROC [$procName] ($spArgs) AS BEGIN $spCode END");
    sqlsrv_free_stmt($stmt1);

    echo "\nData Type: ".$sqlType." binding as \n";

    $direction = SQLSRV_PARAM_OUT;
    echo "Output parameter: ";
    invokeProc($conn, $procName, $k, $direction, $data);

    $direction = SQLSRV_PARAM_INOUT;
    echo "InOut parameter: ";
    invokeProc($conn, $procName, $k, $direction, $data);

    dropProc($conn, $procName);
}

function invokeProc($conn, $procName, $k, $direction, $data)
{
    $sqlsrvSQLType = sqlsrvSqlType($k, strlen($data));
    $callArgs = "?, ?";

    // Data to initialize $callResult variable
    $initData = "ShortString";
    $callResult = $initData;

    // No need to specify the SQLSRV PHP type but must specify SQLSRV SQL Type
    // when AE is enabled
    $intType = AE\isColEncrypted()? SQLSRV_SQLTYPE_INT : null;
    $params = array( array( $k, SQLSRV_PARAM_IN, null, $intType ),
                     array( &$callResult, $direction, null, $sqlsrvSQLType ));
    $stmt = sqlsrv_query($conn, "{ CALL [$procName] ($callArgs)}", $params);
    if ($stmt) {
        fatalError("Expect this to fail!");
    } else {
        echo (sqlsrv_errors()[0]['message']) . PHP_EOL;
    }
}

function getData($k)
{
    $data = "LongStringForTesting";
    return $data;
}

function SQLType($k)
{
    switch ($k) {
        case 1: return ("text");
        case 2: return ("ntext");
        case 3: return ("image");
        default: break;
    }
    return ("udt");
}

function sqlsrvSqlType($k, $dataSize)
{
    switch ($k) {
        case 1:  return (SQLSRV_SQLTYPE_TEXT);
        case 2:  return (SQLSRV_SQLTYPE_NTEXT);
        case 3:  return (SQLSRV_SQLTYPE_IMAGE);
        default: break;
    }
    return (SQLSRV_SQLTYPE_UDT);
}

?>

--EXPECT--

Data Type: text binding as 
Output parameter: Stored Procedures do not support text, ntext or image as OUTPUT parameters.
InOut parameter: Stored Procedures do not support text, ntext or image as OUTPUT parameters.

Data Type: ntext binding as 
Output parameter: Stored Procedures do not support text, ntext or image as OUTPUT parameters.
InOut parameter: Stored Procedures do not support text, ntext or image as OUTPUT parameters.

Data Type: image binding as 
Output parameter: Stored Procedures do not support text, ntext or image as OUTPUT parameters.
InOut parameter: Stored Procedures do not support text, ntext or image as OUTPUT parameters.

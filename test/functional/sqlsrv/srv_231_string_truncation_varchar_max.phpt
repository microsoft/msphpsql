--TEST--
GitHub issue #231 - String truncation when binding varchar(max)
--SKIPIF--
--FILE--
<?php

sqlsrv_configure('WarningsReturnAsErrors', 1);

require_once("MsCommon.inc");

// connect
$conn = connect();
if (!$conn) {
    printErrors("Connection could not be established.\n");
}

$tableName = GetTempTableName('testDataTypes_GH231');
$columnNames = array( "c1","c2" );

for ($k = 1; $k <= 8; $k++) {
    $sqlType = SqlType($k);
    $dataType = "[$columnNames[0]] int, [$columnNames[1]] $sqlType";

    $sql = "CREATE TABLE [$tableName] ($dataType)";
    $stmt1 = sqlsrv_query($conn, $sql);
    sqlsrv_free_stmt($stmt1);

    $sql = "INSERT INTO [$tableName] ($columnNames[0], $columnNames[1]) VALUES (?, ?)";
    $data = GetData($k);
    $phpType = PhpType($k);
    $driverType = DriverType($k, strlen($data));

    $params = array($k, array($data, SQLSRV_PARAM_IN, $phpType, $driverType));
    $stmt2 = sqlsrv_prepare($conn, $sql, $params);
    sqlsrv_execute($stmt2);
    sqlsrv_free_stmt($stmt2);

    ExecProc($conn, $tableName, $columnNames, $k, $data, $sqlType);

    $stmt3 = sqlsrv_query($conn, "DROP TABLE [$tableName]");
    sqlsrv_free_stmt($stmt3);
}

sqlsrv_close($conn);


function ExecProc($conn, $tableName, $columnNames, $k, $data, $sqlType)
{
    $spArgs = "@p1 int, @p2 $sqlType OUTPUT";
    $spCode = "SET @p2 = ( SELECT c2 FROM $tableName WHERE c1 = @p1 )";
    $procName = "testBindOutSp";

    $stmt1 = sqlsrv_query($conn, "CREATE PROC [$procName] ($spArgs) AS BEGIN $spCode END");
    sqlsrv_free_stmt($stmt1);

    echo "\nData Type: ".$sqlType." binding as \n";

    $direction = SQLSRV_PARAM_OUT;
    echo "Output parameter: \t";
    InvokeProc($conn, $procName, $k, $direction, $data);

    $direction = SQLSRV_PARAM_INOUT;
    echo "InOut parameter: \t";
    InvokeProc($conn, $procName, $k, $direction, $data);

    $stmt2 = sqlsrv_query($conn, "DROP PROC [$procName]");
    sqlsrv_free_stmt($stmt2);
}

function InvokeProc($conn, $procName, $k, $direction, $data)
{
    $driverType = DriverType($k, strlen($data));
    $callArgs = "?, ?";

    // Data to initialize $callResult variable. This variable should be shorter than inserted data in the table
    $initData = "ShortString";
    $callResult = $initData;

    // Make sure not to specify the PHP type
    $params = array( array( $k, SQLSRV_PARAM_IN ),
                     array( &$callResult, $direction, null, $driverType ));
    $stmt = sqlsrv_query($conn, "{ CALL [$procName] ($callArgs)}", $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // $callResult should be updated to the value in the table
    $matched = ($callResult === $data);
    if ($matched) {
        echo "data matched!\n";
    } else {
        echo "failed!\n";
    }

    sqlsrv_free_stmt($stmt);
}

function GetData($k)
{
    $data = "LongStringForTesting";
    if ($k == 8) {
        $data = "<XmlTestData><Letters1>The quick brown fox jumps over the lazy dog</Letters1><Digits1>0123456789</Digits1></XmlTestData>";
    }

    return $data;
}

function PhpType($k)
{
    $phpType = SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR);
    if ($k == 7) {
        $phpType = SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY);
    }

    return $phpType;
}

function SqlType($k)
{
    switch ($k) {
        case 1:  return ("char(512)");
        case 2:  return ("varchar(512)");
        case 3:  return ("varchar(max)");
        case 4:  return ("nchar(512)");
        case 5:  return ("nvarchar(512)");
        case 6:  return ("nvarchar(max)");
        case 7:  return ("varbinary(max)");
        case 8:  return ("xml");
        default: break;
    }
    return ("udt");
}

function DriverType($k, $dataSize)
{
    switch ($k) {
        case 1:  return (SQLSRV_SQLTYPE_CHAR($dataSize));
        case 2:  return (SQLSRV_SQLTYPE_VARCHAR($dataSize));
        case 3:  return (SQLSRV_SQLTYPE_VARCHAR('max'));
        case 4:  return (SQLSRV_SQLTYPE_NCHAR($dataSize));
        case 5:  return (SQLSRV_SQLTYPE_NVARCHAR($dataSize));
        case 6:  return (SQLSRV_SQLTYPE_NVARCHAR('max'));
        case 7:  return (SQLSRV_SQLTYPE_VARBINARY('max'));
        case 8:  return (SQLSRV_SQLTYPE_XML);

        default: break;
    }
    return (SQLSRV_SQLTYPE_UDT);
}

?>

--EXPECT--

Data Type: char(512) binding as 
Output parameter: 	data matched!
InOut parameter: 	data matched!

Data Type: varchar(512) binding as 
Output parameter: 	data matched!
InOut parameter: 	data matched!

Data Type: varchar(max) binding as 
Output parameter: 	data matched!
InOut parameter: 	data matched!

Data Type: nchar(512) binding as 
Output parameter: 	data matched!
InOut parameter: 	data matched!

Data Type: nvarchar(512) binding as 
Output parameter: 	data matched!
InOut parameter: 	data matched!

Data Type: nvarchar(max) binding as 
Output parameter: 	data matched!
InOut parameter: 	data matched!

Data Type: varbinary(max) binding as 
Output parameter: 	data matched!
InOut parameter: 	data matched!

Data Type: xml binding as 
Output parameter: 	data matched!
InOut parameter: 	data matched!

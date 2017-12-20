--TEST--
GitHub issue #231 - String truncation when binding varchar(max)
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

sqlsrv_configure('WarningsReturnAsErrors', 1);

require_once("MsCommon.inc");

// connect
$conn = AE\connect();
$tableName = 'testDataTypes_GH231_VC';
$columnNames = array("c1", "c2");

for ($k = 1; $k <= 8; $k++) {
    $sqlType = SQLType($k);
    $columns = array(new AE\ColumnMeta('int', $columnNames[0]),
                     new AE\ColumnMeta($sqlType, $columnNames[1]));
    AE\createTable($conn, $tableName, $columns);

    $sql = "INSERT INTO [$tableName] ($columnNames[0], $columnNames[1]) VALUES (?, ?)";
    $data = getData($k);
    $phpType = getPhpType($k);
    
    $len = AE\isColEncrypted() ? 512 : strlen($data);
    $sqlsrvType = getSQLSRVType($k, $len);

    $params = array($k, array($data, SQLSRV_PARAM_IN, $phpType, $sqlsrvType));
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
    if (!$stmt1) {
        fatalError("Failed to create stored procedure $procName");
    } else {
        sqlsrv_free_stmt($stmt1);
    }

    echo "\nData Type: ".$sqlType." binding as \n";

    $direction = SQLSRV_PARAM_OUT;
    echo "Output parameter: \t";
    invokeProc($conn, $procName, $k, $direction, $data);

    $direction = SQLSRV_PARAM_INOUT;
    echo "InOut parameter: \t";
    invokeProc($conn, $procName, $k, $direction, $data);

    dropProc($conn, $procName);
}

function invokeProc($conn, $procName, $k, $direction, $data)
{
    $len = AE\isColEncrypted() ? 512 : strlen($data);
    $sqlsrvType = getSQLSRVType($k, $len);

    $callArgs = "?, ?";

    // Data to initialize $callResult variable. This variable 
    // should be shorter than inserted data in the table
    $initData = "ShortString";
    $callResult = $initData;

    // Make sure not to specify the PHP type
    $params = array( array( $k, SQLSRV_PARAM_IN ),
                     array( &$callResult, $direction, null, $sqlsrvType ));
    $stmt = AE\executeQueryParams($conn, "{ CALL [$procName] ($callArgs)}", $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // $callResult should be updated to the value in the table
    if (AE\isColEncrypted()) {
        // with AE enabled, char/nchar fields have size up to 512
        $matched = (trim($callResult) === $data);
    } else {
        $matched = ($callResult === $data);
    }
    if ($matched) {
        echo "data matched!\n";
    } else {
        echo "failed! $callResult vs $data\n";
    }

    sqlsrv_free_stmt($stmt);
}

function getData($k)
{
    $data = "LongStringForTesting";
    if ($k == 8) {
        $data = "<XmlTestData><Letters1>The quick brown fox jumps over the lazy dog</Letters1><Digits1>0123456789</Digits1></XmlTestData>";
    }

    return $data;
}

function getPhpType($k)
{
    $phpType = SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR);
    if ($k == 7) {
        $phpType = SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY);
    }

    return $phpType;
}

function SQLType($k)
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

function getSQLSRVType($k, $dataSize)
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

--TEST--
Encoding of sqlsrv errors
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
function verifyErrorContents()
{
    require_once('MsCommon.inc');
    $error = sqlsrv_errors()[0];
    
    if ($error['SQLSTATE'] !== '42S22') {
        echo "Expected SQLSTATE 42S22\n";
        var_dump($error);
    }
    
    // The error message is different when testing against Azure DB / Data Warehouse
    // Use wildcard patterns for matching
    if (isSQLAzure()) {
        $expected = "*Invalid column name [\"']BadColumn[\"']\.";
    } else {
        $expected = "*Ungültiger Spaltenname [\"']BadColumn[\"']\.";
    }

    if (!fnmatch($expected, $error['message'])) {
        echo "Expected to find $expected in the error message\n";
        var_dump($error);
    }
    
}

require_once('MsSetup.inc');

$connectionOptions = array('UID' => $userName, 'PWD' => $userPassword, 'CharacterSet' => 'UTF-8');
$conn = sqlsrv_connect($server, $connectionOptions);
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

$stmt = sqlsrv_query($conn, "SET LANGUAGE German");
if (!$stmt) {
    print_r(sqlsrv_errors());
    exit;
}
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "select *, BadColumn from sys.syslanguages");
if ($stmt) {
    echo 'This should have failed!\n';
    sqlsrv_free_stmt($stmt);
} else {
    verifyErrorContents();
}

sqlsrv_close($conn);

echo "Done\n";
?>
--EXPECT--
Done
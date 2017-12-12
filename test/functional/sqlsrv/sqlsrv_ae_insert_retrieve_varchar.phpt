--TEST--
Test for inserting encrypted varchar data of variable lengths and retrieving encrypted and decrypted data
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = AE\connect();
$testPass = true;

// Create the table
$tbname = 'VarcharAnalysis';
$colMetaArr = array( new AE\ColumnMeta("int", "CharCount", "IDENTITY(0,1)"), new AE\ColumnMeta("varchar(1000)"));
AE\createTable($conn, $tbname, $colMetaArr);


// insert 1000 rows
for ($i = 0; $i < 1000; $i++) {
    $data = str_repeat("*", $i);
    $stmt = AE\insertRow($conn, $tbname, array( AE\getDefaultColname("varchar(1000)") => $data ));
}

$selectSql = "SELECT * FROM $tbname";
$stmt = sqlsrv_query($conn, $selectSql);
while ($decrypted_row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if ($decrypted_row[ 'CharCount' ] != strlen($decrypted_row[ AE\getDefaultColname("varchar(1000)") ])) {
        $rowInd = $decrypted_row[ 'CharCount' ] + 1;
        echo "Failed to decrypted at row $rowInd\n";
        $testPass = false;
    }
}
sqlsrv_free_stmt($stmt);

// for AE only
if (AE\isDataEncrypted()) {
    $conn1 = connect(null, true);
    $stmt = sqlsrv_query($conn1, $selectSql);
    while ($encrypted_row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if ($encrypted_row[ 'CharCount' ] == strlen($encrypted_row[ AE\getDefaultColname("varchar(1000)") ])) {
            $rowInd = $encrypted_row[ 'CharCount' ] + 1;
            echo "Failed to encrypted at row $rowInd\n";
            $testPass = false;
        }
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn1);
}

dropTable($conn, $tbname);
sqlsrv_close($conn);

if ($testPass) {
    echo "Test successfully done.\n";
}

?>
--EXPECT--
Test successfully done.

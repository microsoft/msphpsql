--TEST--
Test for inserting encrypted nvarchar data of variable lengths and retrieving encrypted and decrypted data
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
$testPass = true;
try {
    $conn = connect();
    // Create the table
    $tbname = 'NVarcharAnalysis';
    $colMetaArr = array( new ColumnMeta("int", "CharCount", "IDENTITY(0,1)"), new columnMeta("nvarchar(1000)"));
    createTable($conn, $tbname, $colMetaArr);

    // insert 1000 rows
    for ($i = 0; $i < 1000; $i++) {
        $data = str_repeat("*", $i);
        $stmt = insertRow($conn, $tbname, array(getDefaultColName("nvarchar(1000)") => $data));
    }

    $selectSql = "SELECT * FROM $tbname";
    $stmt = $conn->query($selectSql);
    while ($decrypted_row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($decrypted_row['CharCount'] != strlen($decrypted_row[getDefaultColName("nvarchar(1000)")])) {
            $rowInd = $decrypted_row['CharCount'] + 1;
            echo "Failed to decrypted at row $rowInd\n";
            $testPass = false;
        }
    }
    unset($stmt);
} catch (PDOException $e) {
    echo $e->getMessage();
}
// for AE only
if (isColEncrypted()) {
    try {
        $conn1 = connect('', array(), PDO::ERRMODE_EXCEPTION, true);
        $stmt = $conn1->query($selectSql);
        while ($decrypted_row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($decrypted_row['CharCount'] == strlen($decrypted_row[getDefaultColName("nvarchar(1000)")])) {
                $rowInd = $decrypted_row[ 'CharCount' ] + 1;
                echo "Failed to encrypted at row $rowInd\n";
                $testPass = false;
            }
        }
        unset($stmt);
        unset($conn1);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}
dropTable($conn, $tbname);
unset($conn);
if ($testPass) {
    echo "Test successfully done.\n";
}
?>
--EXPECT--
Test successfully done.

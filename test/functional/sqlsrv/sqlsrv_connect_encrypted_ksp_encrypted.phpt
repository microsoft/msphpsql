--TEST--
Fetch encrypted data from a prepopulated test table given a custom keystore provider
--SKIPIF--
<?php require('skipif_not_ksp.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 1);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsHelper.inc');
    $conn = AE\connect(array('ReturnDatesAsStrings'=>true));
    if ($conn === false) {
        fatalError("Failed to connect.\n");
    } else {
        echo "Connected successfully with ColumnEncryption disabled.\n";
    }

    $ksp_test_table = AE\KSP_TEST_TABLE;
    $tsql = "SELECT * FROM $ksp_test_table";
    $stmt = sqlsrv_prepare($conn, $tsql);
    if (!sqlsrv_execute($stmt)) {
        fatalError("Failed to fetch data.\n");
    }

    // fetch data
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)) {
        // all columns should return binary data except the first column
        echo "c1=" . $row[0];
        echo "\tc2=" . bin2hex($row[1]);
        echo "\tc3=" . bin2hex($row[2]);
        echo "\tc4=" . bin2hex($row[3]);
        echo "\n" ;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    echo "Done\n";
?>
--EXPECTREGEX--
Connected successfully with ColumnEncryption disabled.
c1=1	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=12	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=23	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=34	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=45	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=56	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=67	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=78	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=89	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
c1=100	c2=[a-f0-9]+	c3=[a-f0-9]+	c4=[a-f0-9]+
Done
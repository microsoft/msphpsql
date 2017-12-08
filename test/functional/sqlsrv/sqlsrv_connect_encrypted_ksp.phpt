--TEST--
Fetch data from a prepopulated test table given a custom keystore provider
--SKIPIF--
<?php require('skipif_not_ksp.inc'); ?>
--FILE--
<?php
    function verifyData($row, $num)
    {
        $c1 = $num * 10 + $num + 1;
        if (AE\isColEncrypted()) {
            $c2 = "Sample data $num for column 2";
            
            $c3 = '';
            for ($i = 0; $i < 3; $i++) {
                // add to letter 'a'
                $c3 .= chr(97 + $num + $i);
            }
            $c4 = "2017-08-" . ($num + 10);
            
            // need to trim the third value because it is a char(5)
            if ($row[0] !== $c1 || $row[1] !== $c2 || trim($row[2]) !== $c3 || $row[3] !== $c4) {
                echo "Expected the following\n";
                echo "c1=$c1\nc2=$c2\nc3=$c3\nc4=$c4\n";
                echo "But got these instead\n";
                echo "c1=" . $row[0] . "\nc2=" . $row[1] . "\nc3=" . $row[2] . "\nc4=" . $row[3] . "\n" ;
                
                return false;
            }
        } else {
            if ($row[0] !== $c1) {
                echo "Expected $c1 but got $row[0]\n";
            }
            // should expect binary values for the other columns
            for ($i = 1; $i <= 3; $i++) {
                if (ctype_print($row[1])) {
                    print "Error: expected a binary array for column $i!\n";
                }
            }
        }
        
        return true;
    }
    
    sqlsrv_configure('WarningsReturnAsErrors', 1);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');
    $conn = AE\connect(array('ReturnDatesAsStrings'=>true));
    if ($conn !== false) {
        echo "Connected successfully with ColumnEncryption enabled.\n";
    }

    $ksp_test_table = AE\KSP_TEST_TABLE;
    $tsql = "SELECT * FROM $ksp_test_table";
    $stmt = sqlsrv_prepare($conn, $tsql);
    if (!sqlsrv_execute($stmt)) {
        fatalError("Failed to fetch data.\n");
    }

    // fetch data
    $id = 0;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)) {
        if (!verifyData($row, $id++)) {
            break;
        }
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    echo "Done\n";
?>
--EXPECT--
Connected successfully with ColumnEncryption enabled.
Done

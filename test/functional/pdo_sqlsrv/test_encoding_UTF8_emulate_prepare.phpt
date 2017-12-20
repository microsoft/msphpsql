--TEST--
Test UTF8 Encoding with emulate prepare
--SKIPIF--
<?php require_once('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function checkError($e, $expMsg, $aeExpMsg)
{
    $error = $e->getMessage();
    if (!isAEConnected()) {
        if (strpos($error, $expMsg) === false) echo $error;
    } else {
        if (strpos($error, $aeExpMsg) === false) echo $error;
    }
}

try {
    $inValue1 = pack('H*', '3C586D6C54657374446174613E4A65207072C3A966C3A87265206C27C3A974C3A93C2F586D6C54657374446174613E');
    $inValueLen = strlen($inValue1);

    $conn = connect();
    $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_SYSTEM);

    $tbname = "Table_UTF";
    createTable($conn, $tbname, array(new ColumnMeta("int", "c1_int", "PRIMARY KEY"), "c2_char" => "char(512)"));

    $stmt3 = $conn->prepare("INSERT INTO [Table_UTF] (c1_int, c2_char) VALUES (:var1, :var2)");
    $stmt3->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
    $stmt3->bindParam(2, $inValue1);
    $stmt3->bindValue(1, 1);
    $stmt3->execute();
    $stmt3->bindValue(1, 2);
    $stmt3->execute();
    $stmt3->bindValue(1, 3);
    $stmt3->execute();
    $stmt3->bindValue(1, 4);
    $stmt3->execute();
    $stmt3->bindValue(1, 5);
    $stmt3->execute();
    unset($stmt3);

    $stmt4 = $conn->prepare("SELECT * FROM $tbname");
    $stmt4->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
    $outValue1 = null;
    $stmt4->execute();
    $row1 = $stmt4->fetch(PDO::FETCH_NUM);
    $count1 = count($row1);
    echo("Number of columns: $count1\n");
    $v0 = $row1[0];
    $outValue1 = $row1[1];
    if (strncmp($inValue1, $outValue1, $inValueLen) == 0) {
        echo "outValue is the same as inValue.\n";
    }

    for ($i = 0; $i < 4; $i++) {
        $outValue1 = $stmt4->fetchColumn(1);
        if (strncmp($inValue1, $outValue1, $inValueLen) == 0) {
            echo "outValue is the same as inValue.\n";
        }
    }
    unset($stmt4);

    $option;
    if (!isAEConnected()) {
        $option[PDO::ATTR_EMULATE_PREPARES] = true;
    } else {
        $option[PDO::ATTR_EMULATE_PREPARES] = false;
    }
    $stmt5 = $conn->prepare("SELECT ? = c2_char FROM $tbname", $option);
    $stmt5->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
    $outValue1 = "hello";
    $stmt5->bindParam(1, $outValue1, PDO::PARAM_STR, 1024);
    $stmt5->execute();
    if (strncmp($inValue1, $outValue1, $inValueLen) == 0) {
        echo "outValue is the same as inValue.\n";
    } else {
        echo "outValue is $outValue1\n";
    }
    unset($stmt5);
    dropTable($conn, $tbname);
    unset($conn);
} catch (PDOexception $e) {
    // binding parameters in the select list is not supported with Column Encryption
    $expMsg = "Statement with emulate prepare on does not support output or input_output parameters.";
    $aeExpMsg = "Invalid Descriptor Index";
    checkError($e, $expMsg, $aeExpMsg);
}
?>
--EXPECT--
Number of columns: 2
outValue is the same as inValue.
outValue is the same as inValue.
outValue is the same as inValue.
outValue is the same as inValue.
outValue is the same as inValue.

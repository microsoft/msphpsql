--TEST--
PDOStatement::BindParam for binary types with empty strings and non-empty ones  
--DESCRIPTION--
PDOStatement::BindParam for binary types with empty strings and non-empty ones 
Related to GitHub PR 865 - verify that the same binary data can be reused rather
than flushed after the first use
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();
    $tableName = "pdoEmptyBinary";
    $size = 6;
    
    $colMetaArr = array(new ColumnMeta("binary($size)", "BinaryCol"), 
                        new ColumnMeta("varbinary($size)", "VarBinaryCol"),
                        new ColumnMeta("varbinary(max)", "VarBinaryMaxCol"));
    createTable($conn, $tableName, $colMetaArr);

    // Insert two rows, first empty strings and the second not empty
    $inputs = array('', 'ABC');
    
    $bin = fopen('php://memory', 'a');
    fwrite($bin, $inputs[0]);                   // an empty string will be 0x in hex
    rewind($bin);

    $query = "INSERT INTO $tableName VALUES(?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $bin, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    $stmt->bindParam(2, $bin, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    $stmt->bindParam(3, $bin, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);

    $stmt->execute();
    fclose($bin);
    
    $bin2 = fopen('php://memory', 'a');
    fwrite($bin2, $inputs[1]);                  // 'ABC' will be 0x414243 in hex
    rewind($bin2);

    $stmt->bindParam(1, $bin2, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    $stmt->bindParam(2, $bin2, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    $stmt->bindParam(3, $bin2, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);

    $stmt->execute();
    fclose($bin2);
    
    // Verify the data by fetching and comparing against the inputs
    $query = "SELECT * FROM $tableName";
    $stmt = $conn->query($query);
    $rowset = $stmt->fetchAll();
    
    for ($i = 0; $i < 2; $i++) {
        for ($j = 0; $j < 3; $j++) {
            $str = $rowset[$i][$j];
            $len = strlen($str);
            $failed = false;
            
            if ($j == 0) {
                // binary fields have fixed size, unlike varbinary ones
                if ($len !== $size || trim($str) !== $inputs[$i]) {
                    $failed = true;
                }
            } else {
                if ($len !== strlen($inputs[$i]) || $str !== $inputs[$i]) {
                    $failed = true;
                }
            }
            
            if ($failed) {
                $row = $i + 1;
                $col = $j + 1;
                echo "Unexpected value returned from row $row and column $col: \n";
                var_dump($str);
            }
        }
    } 
    
    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);
    
    echo "Done\n";
} catch (PDOException $e) {
    var_dump($e);
    exit;
}
?>
--EXPECT--
Done

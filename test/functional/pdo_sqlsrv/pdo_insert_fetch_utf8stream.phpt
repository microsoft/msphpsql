--TEST--
Test inserting UTF-8 stream via PHP including some checking of error conditions
--DESCRIPTION--
This is similar to sqlsrv 0067.phpt with checking for error conditions concerning encoding issues.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();
    
    // Create test table
    $tableName = 'pdoUTF8stream';
    $columns = array(new ColumnMeta('tinyint', 'c1'),
                     new ColumnMeta('char(10)', 'c2'),
                     new ColumnMeta('float', 'c3'),
                     new ColumnMeta('varchar(max)', 'c4'));
    $stmt = createTable($conn, $tableName, $columns);
    
    $f1 = 1;
    $f2 = "testtestte";
    $f3 = 12.0;
    $f4 = fopen("data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r");
    
    $insertSql = "INSERT INTO $tableName (c1, c2, c3, c4) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bindParam(1, $f1);
    $stmt->bindParam(2, $f2);
    $stmt->bindParam(3, $f3);
    $stmt->bindParam(4, $f4, PDO::PARAM_LOB);
    
    $stmt->execute();

    // Next test UTF-8 cutoff in the middle of a valid 3 byte UTF-8 char
    $utf8 = str_repeat("41", 8188);
    $utf8 = $utf8 . "e38395";
    $utf8 = pack("H*", $utf8);
    $f4 = fopen("data://text/plain," . $utf8, "r");
    $stmt->bindParam(4, $f4, PDO::PARAM_LOB);
    $stmt->execute();

    // Now test a 2 byte incomplete character
    $utf8 = str_repeat("41", 8188);
    $utf8 = $utf8 . "dfa0";
    $utf8 = pack("H*", $utf8);
    $f4 = fopen("data://text/plain," . $utf8, "r");
    $stmt->bindParam(4, $f4, PDO::PARAM_LOB);
    $stmt->execute();

    // Then test a 4 byte incomplete character
    $utf8 = str_repeat("41", 8186);
    $utf8 = $utf8 . "f1a680bf";
    $utf8 = pack("H*", $utf8);
    $f4 = fopen("data://text/plain," . $utf8, "r");
    $stmt->bindParam(4, $f4, PDO::PARAM_LOB);
    $stmt->execute();

    // Finally, verify error conditions with invalid inputs
    $error = '*An error occurred translating a PHP stream from UTF-8 to UTF-16:*';
    
    // First test UTF-8 cutoff (really cutoff)
    $utf8 = str_repeat("41", 8188);
    $utf8 = $utf8 . "e383";
    $utf8 = pack("H*", $utf8);
    $f4 = fopen("data://text/plain," . $utf8, "r");
    try {
        $stmt->bindParam(4, $f4, PDO::PARAM_LOB);
        $stmt->execute();
        echo "Should have failed with a cutoff UTF-8 string\n";
    } catch (PDOException $e) {
        if ($e->getCode() !== "IMSSP" || !fnmatch($error, $e->getMessage())) {
            var_dump($e->getMessage());
        }
    }
    
    // Then test UTF-8 invalid/corrupt stream
    $utf8 = str_repeat("41", 8188);
    $utf8 = $utf8 . "e38395e38395";
    $utf8 = substr_replace($utf8, "fe", 1000, 2);
    $utf8 = pack("H*", $utf8);
    $f4 = fopen("data://text/plain," . $utf8, "r");
    try {
        $stmt->bindParam(4, $f4, PDO::PARAM_LOB);
        $stmt->execute();
        echo "Should have failed with an invalid UTF-8 string\n";
    } catch (PDOException $e) {
        if ($e->getCode() !== "IMSSP" || !fnmatch($error, $e->getMessage())) {
            var_dump($e->getMessage());
        }
    }

    echo "Done\n";
    
    // Done testing with stored procedures and table
    dropTable($conn, $tableName);
    
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
Done
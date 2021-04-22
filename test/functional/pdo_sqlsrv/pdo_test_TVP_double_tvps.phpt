--TEST--
Test Table-valued parameter with a stored procedure that takes two TVPs
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsSetup.inc');
require_once('MsCommon_mid-refactor.inc');

function cleanup($conn, $schema)
{
    global $dropSchema;
    
    $dropProcedure = dropProcSQL($conn, "[$schema].[AddReview]");
    $conn->exec($dropProcedure);

    $dropTableType = dropTableTypeSQL($conn, "TestTVP3", $schema);
    $conn->exec($dropTableType);
    $dropTableType = dropTableTypeSQL($conn, "SupplierType", $schema);
    $conn->exec($dropTableType);

    $conn->exec($dropSchema);
}

try {
    $conn = new PDO("sqlsrv:server = $server; database=$databaseName;", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Use a different schema instead of dbo
    $schema = 'Sales DB';
    cleanup($conn, $schema);
    
    // Create the table type and stored procedure
    $conn->exec($createSchema);
    $conn->exec($createTestTVP3);
    $conn->exec($createSupplierType);
    $conn->exec($createAddReview);
    
    // Create the TVP input arrays
    $inputs1 = [
        [12345, 'μεγάλο'],
        [67890, 'μεσαία'],
        [45678, 'μικρές'],
    ];

    $inputs2 = [
        ['abcde', 12345, '2019-12-31 23:59:59.123456'],
        ['fghij', 67890, '2000-07-15 12:30:30.5678'],
        ['klmop', 45678, '2007-04-08 06:15:15.333'],
    ];

    $tvpType1 = "$schema.SupplierType";
    $tvpType2 = "$schema.TestTVP3";

    $tvpInput1 = array($tvpType1 => $inputs1);
    $tvpInput2 = array($tvpType2 => $inputs2);

    $stmt = $conn->prepare($callAddReview);
    $stmt->bindValue(1, $tvpInput1, PDO::PARAM_LOB);
    $stmt->bindValue(2, $tvpInput2, PDO::PARAM_LOB);
    $stmt->execute();

    // Verify the results
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    $stmt->nextRowset();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    unset($stmt);
    
    cleanup($conn, $schema);

    unset($conn);
    echo "Done" . PHP_EOL;
    
} catch (PDOException $e) {
    var_dump($e->getMessage());
}
?>
--EXPECT--
Array
(
    [SupplierId] => 12345
    [SupplierName] => μεγάλο
)
Array
(
    [SupplierId] => 67890
    [SupplierName] => μεσαία
)
Array
(
    [SupplierId] => 45678
    [SupplierName] => μικρές
)
Array
(
    [SupplierId] => 12345
    [SalesDate] => 2019-12-31 23:59:59.1234560
    [Review] => abcde
)
Array
(
    [SupplierId] => 67890
    [SalesDate] => 2000-07-15 12:30:30.5678000
    [Review] => fghij
)
Array
(
    [SupplierId] => 45678
    [SalesDate] => 2007-04-08 06:15:15.3330000
    [Review] => klmop
)
Done
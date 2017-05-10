--TEST--
Tests error returned when binding output parameter with emulate prepare
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require("MsSetup.inc");

$dsn = "sqlsrv:Server=$server ; Database = $databaseName";
try {
    $conn = new PDO($dsn, $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $count = 0;
    
    $query = "select ? = count(* ) from cd_info";
    $stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
    $stmt->bindParam( 1, $count, PDO::PARAM_STR, 10 );
    $stmt->execute();
    echo "Result: ".$count."\n";
    
    $query = "select bigint_type, int_type, money_type from [test_types] where int_type < 0";
    $stmt1 = $conn->prepare($query);
    $stmt1->execute();
    $row = $stmt1->fetch( PDO::FETCH_ASSOC ); 
    print_r($row);  

    $int = 0;
    $bigint = 100;
    $query = "select ? = bigint_type, ? = int_type, ? = money_type from [test_types] where int_type < 0";
    $stmt2 = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
    $stmt2->bindparam( 1, $bigint, PDO::PARAM_STR, 256 );
    $stmt2->bindParam( 2, $int, PDO::PARAM_INT, 4 );
    $stmt2->bindParam( 3, $money, PDO::PARAM_STR, 1024 );
    $stmt2->execute();
    echo "Big integer: ".$bigint."\n";
    echo "Integer: ".$int."\n";
    echo "Money: ".$money."\n";
    
    //free the statement and connection
    $stmt = null;
    $stmt1 = null;
    $stmt2 = null;
    $conn = null;
    
}
catch(PDOException $e) {
    print("Error: " . $e->getMessage() . "\n");
}
?>
--EXPECT--
Error: SQLSTATE[IMSSP]: Statement with emulate prepare on does not support output or input_output parameters.
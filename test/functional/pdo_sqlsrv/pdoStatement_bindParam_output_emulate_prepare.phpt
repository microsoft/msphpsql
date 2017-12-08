--TEST--
Tests error returned when binding output parameter with emulate prepare
--SKIPIF--
<?php require_once('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();

    $count = 0;

    $query = "select ? = count(* ) from cd_info";
    $stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
    $stmt->bindParam(1, $count, PDO::PARAM_STR, 10);
    $stmt->execute();
    echo "Result: ".$count."\n";

    $query = "select bigint_type, int_type, money_type from [test_types] where int_type < 0";
    $stmt1 = $conn->prepare($query);
    $stmt1->execute();
    $row = $stmt1->fetch(PDO::FETCH_ASSOC);
    print_r($row);

    $int = 0;
    $bigint = 100;
    $query = "select ? = bigint_type, ? = int_type, ? = money_type from [test_types] where int_type < 0";
    $stmt2 = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
    $stmt2->bindparam(1, $bigint, PDO::PARAM_STR, 256);
    $stmt2->bindParam(2, $int, PDO::PARAM_INT, 4);
    $stmt2->bindParam(3, $money, PDO::PARAM_STR, 1024);
    $stmt2->execute();
    echo "Big integer: ".$bigint."\n";
    echo "Integer: ".$int."\n";
    echo "Money: ".$money."\n";

    //free the statement and connection
    unset($stmt);
    unset($stmt1);
    unset($stmt2);
    unset($conn);
} catch (PDOException $e) {
    print("Error: " . $e->getMessage() . "\n");
}
?>
--EXPECT--
Error: SQLSTATE[IMSSP]: Statement with emulate prepare on does not support output or input_output parameters.

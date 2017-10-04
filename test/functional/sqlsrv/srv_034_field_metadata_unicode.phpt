--TEST--
Field metadata unicode
--SKIPIF--
--FILE--
<?php

require_once("MsCommon.inc");

// connect
$conn = connect(array("CharacterSet"=>"UTF-8"));
if (!$conn) {
    fatalError("Connection could not be established.\n");
}

$tableName = 'test_srv_034';

// Create table. Column names: passport
$sql = "CREATE TABLE $tableName (पासपोर्ट CHAR(2), پاسپورٹ VARCHAR(2), Διαβατήριο VARCHAR(MAX))";
$stmt = sqlsrv_query($conn, $sql);

// Prepare the statement
$sql = "SELECT * FROM $tableName";
$stmt = sqlsrv_prepare($conn, $sql);

// Get and display field metadata
$metadata = sqlsrv_field_metadata($stmt);
if (! $metadata) {
    printErrors();
} else {
    var_dump($metadata);
}

sqlsrv_query($conn, "DROP TABLE $tableName");

// Free statement and connection resources
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

print "Done"
?>

--EXPECT--
array(3) {
  [0]=>
  array(6) {
    ["Name"]=>
    string(24) "पासपोर्ट"
    ["Type"]=>
    int(1)
    ["Size"]=>
    int(2)
    ["Precision"]=>
    NULL
    ["Scale"]=>
    NULL
    ["Nullable"]=>
    int(1)
  }
  [1]=>
  array(6) {
    ["Name"]=>
    string(14) "پاسپورٹ"
    ["Type"]=>
    int(12)
    ["Size"]=>
    int(2)
    ["Precision"]=>
    NULL
    ["Scale"]=>
    NULL
    ["Nullable"]=>
    int(1)
  }
  [2]=>
  array(6) {
    ["Name"]=>
    string(20) "Διαβατήριο"
    ["Type"]=>
    int(12)
    ["Size"]=>
    int(0)
    ["Precision"]=>
    NULL
    ["Scale"]=>
    NULL
    ["Nullable"]=>
    int(1)
  }
}
Done

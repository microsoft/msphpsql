--TEST--
shows the PDO::ATR_ERRMODE attribute, before and after changing its value
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:Server=$server ; Database = $databaseName", "$uid", "$pwd");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

$attributes1 = array( "ERRMODE" );
foreach ( $attributes1 as $val ) {
    echo "PDO::ATTR_$val: ";
    var_dump ($conn->getAttribute( constant( "PDO::ATTR_$val" ) ));
}

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$attributes1 = array( "ERRMODE" );
foreach ( $attributes1 as $val ) {
    echo "PDO::ATTR_$val: ";
    var_dump ($conn->getAttribute( constant( "PDO::ATTR_$val" ) ));
}

//free the connection
unset($conn);
?>
--EXPECTREGEX--
PDO::ATTR_ERRMODE: int\(0\)
PDO::ATTR_ERRMODE: int\(2\)

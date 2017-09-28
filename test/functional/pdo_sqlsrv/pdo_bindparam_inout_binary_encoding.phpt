--TEST--
bind inout param with PDO::SQLSRV_ENCODING_BINARY
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once( "MsCommon.inc" );

$pdo = connect();

$tbname = "my_table";
create_table( $pdo, $tbname, array( new columnMeta( "varchar(20)", "value" ), new columnMeta( "varchar(20)", "name" )));

insert_row( $pdo, $tbname, array( "value" => "Initial string", "name" => "name" ));

$value = 'Some string value.';
$name = 'name';

$sql = "UPDATE my_table SET value = :value WHERE name = :name";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':value', $value, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
$stmt->bindParam(':name', $name);
$stmt->execute();

$result = select_row( $pdo, $tbname, "PDO::FETCH_ASSOC" );
print_r($result);

$stmt->closeCursor();
unset( $stmt );
unset( $pdo );
?>
--EXPECT--
Array
(
    [value] => Some string value.
    [name] => name
)
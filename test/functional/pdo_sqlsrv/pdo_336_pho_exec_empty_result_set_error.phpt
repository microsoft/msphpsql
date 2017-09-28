--TEST--
GitHub issue #336 - PDO::exec should not return an error with query returning SQL_NO_DATA
--DESCRIPTION--
Verifies GitHub issue 336 is fixed, PDO::exec on query returning SQL_NO_DATA will not give an error
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
// Connect 
require_once( "MsCommon.inc" );

$conn = connect();

$tbname = "foo_table";
create_table( $conn, $tbname, array( new columnMeta( "bigint", "id", "PRIMARY KEY NOT NULL IDENTITY" ), new columnMeta( "int", "intField", "NOT NULL" )));
insert_row( $conn, $tbname, array( "intField" => 3 ), "exec" );

//test prepare, not args
$sql = "DELETE FROM foo_table WHERE id = 42";
$stmt = $conn->prepare($sql);
$stmt->execute();
if ($conn->errorCode() == "00000")
    echo "prepare OK\n";
else
    echo "unexpected error at prepare";

//test prepare, with args
$sqlWithParameter = "DELETE FROM foo_table WHERE id = :id";
$sqlParameter = 42;
$stmt = $conn->prepare($sqlWithParameter);
$stmt->execute(array(':id' => $sqlParameter));
if ($conn->errorCode() == "00000")
    echo "prepare with args OK\n";
else
    echo "unexpected error at prepare with args";

//test direct exec
$stmt = $conn->exec($sql);
$err = $conn->errorCode();
if ($stmt == 0 && $err == "00000")
    echo "direct exec OK\n";
else
    if ($stmt != 0)
        echo "unexpected row returned at direct exec\n";
    if ($err != "00000")
        echo "unexpected error at direct exec";

DropTable( $conn, $tbname );
unset( $stmt );
unset( $conn );

?>
--EXPECT--
prepare OK
prepare with args OK
direct exec OK

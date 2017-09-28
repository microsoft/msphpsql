--TEST--
Insert binary HEX data then fetch it back as string
--DESCRIPTION--
Insert binary HEX data into an nvarchar field then read it back as UTF-8 string 
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
try
{
    require_once( "MsCommon.inc" );

    // Connect
    $conn = connect();

    // Create table
    $tableName = 'pdo_033test';
    create_table( $conn, $tableName, array( new columnMeta( "nvarchar(100)", "c1" )));

    $input = pack( "H*", '49006427500048005000' );  // I'LOVE_SYMBOL'PHP
    $result;
    $stmt = insert_row( $conn, $tableName, array( "c1" => new bindParamOp( 1, $input, "PDO::PARAM_STR", 0, "PDO::SQLSRV_ENCODING_BINARY" )), "prepareBindParam", $result );
    
    if (! $result)
        echo "Failed to insert!\n";

    $stmt = $conn->query("SELECT * FROM $tableName");
    $utf8 = $stmt->fetchColumn();

    echo "$utf8\n";

    DropTable( $conn, $tableName );
    $stmt = null;
    $conn = null;
}
catch (Exception $e)
{
    echo $e->getMessage();
}
    
print "Done";

?>

--EXPECT--
I❤PHP
Done

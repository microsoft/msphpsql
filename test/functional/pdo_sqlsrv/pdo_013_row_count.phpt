--TEST--
Number of rows in a result set
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

$conn = connect();

// Create table
$tableName = GetTempTableName( '', false );
create_table( $conn, $tableName, array( new ColumnMeta( "varchar(32)", "c1" )));

if ( !is_col_encrypted())
{
    // Insert data
    $query = "INSERT INTO $tableName VALUES ('Salmon'),('Butterfish'),('Cod'),('NULL'),('Crab')";
    $stmt = $conn->query($query);
    $res[] = $stmt->rowCount();
    
    // Update data
    $query = "UPDATE $tableName SET c1='Salmon' WHERE c1='Cod'";
    $stmt = $conn->query($query);
    $res[] = $stmt->rowCount();
    
    // Update data
    $query = "UPDATE $tableName SET c1='Salmon' WHERE c1='NULL'";
    $stmt = $conn->query($query);
    $res[] = $stmt->rowCount();

    // Update data
    $query = "UPDATE $tableName SET c1='Salmon' WHERE c1='NO_NAME'";
    $stmt = $conn->query($query);
    $res[] = $stmt->rowCount();

    // Update data
    $query = "UPDATE $tableName SET c1='N/A'";
    $stmt = $conn->query($query);
    $res[] = $stmt->rowCount();
    
    unset( $stmt );
}
else
{
    // Insert data
    // bind parameter does not work with inserting multiple rows in one SQL command, thus need to insert each row separately
    $query = "INSERT INTO $tableName VALUES ( ? )";
    $stmt = $conn->prepare( $query );
    $params = array( "Salmon", "Butterfish", "Cod", "NULL", "Crab" );
    foreach ( $params as $param )
    {
        $stmt->execute( array( $param ) );
    }
    $res[] = count( $params );
    
    // Update data
    $query = "UPDATE $tableName SET c1=? WHERE c1=?";
    $stmt = $conn->prepare( $query );
    $stmt->execute( array( "Salmon", "Cod" ));
    $res[] = $stmt->rowCount();
    
    // Update data
    $stmt->execute( array( "Salmon", "NULL" ));
    $res[] = $stmt->rowCount();
    
    // Update data
    $stmt->execute( array( "Salmon", "NO_NAME" ));
    $res[] = $stmt->rowCount();
    
    $query = "UPDATE $tableName SET c1=?";
    $stmt = $conn->prepare( $query );
    $stmt->execute( array( "N/A" ));
    $res[] = $stmt->rowCount();
    
    unset( $stmt );
}

print_r($res);

DropTable( $conn, $tableName );
unset( $conn );
print "Done"
?>
--EXPECT--
Array
(
    [0] => 5
    [1] => 1
    [2] => 1
    [3] => 0
    [4] => 5
)
Done

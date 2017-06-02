--TEST--
creates a statement resource then retrieves and displays the field metadata
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
     echo "Could not connect.<br>";
     die( print_r( sqlsrv_errors(), true));
}

/* Prepare the statement. */
$tsql = "SELECT ReviewerName, Comments FROM Production.ProductReview";
$stmt = sqlsrv_prepare( $conn, $tsql);

/* Get and display field metadata. */
foreach( sqlsrv_field_metadata( $stmt) as $fieldMetadata)
{
      foreach( $fieldMetadata as $name => $value)
      {
           echo "$name: $value<br>";
      }
      echo "<br>";
}

/* Note: sqlsrv_field_metadata can be called on any statement
resource, pre- or post-execution. */

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_close( $conn);
?>
--EXPECT--
Name: ReviewerName<br>Type: -9<br>Size: 50<br>Precision: <br>Scale: <br>Nullable: 0<br><br>Name: Comments<br>Type: -9<br>Size: 3850<br>Precision: <br>Scale: <br>Nullable: 1<br><br>
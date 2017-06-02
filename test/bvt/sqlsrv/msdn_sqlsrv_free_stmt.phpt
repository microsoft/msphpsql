--TEST--
creates a statement resource, executes a simple query, and free all resources associated with the statement
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

$stmt = sqlsrv_query( $conn, "SELECT * FROM Person.Person");
if( $stmt )
{
     echo "Statement executed.<br>";
}
else
{
     echo "Query could not be executed.<br>";
     die( print_r( sqlsrv_errors(), true));
}

/* Get and display field metadata. */
foreach( sqlsrv_field_metadata( $stmt) as $fieldMetadata)
{
      foreach( $fieldMetadata as $name => $value)
      {
           echo "$name: $value<br>";
      }
      echo "<br>";
}

/* Free the statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_close( $conn);
?>
--EXPECT--
Statement executed.<br>Name: BusinessEntityID<br>Type: 4<br>Size: <br>Precision: 10<br>Scale: <br>Nullable: 0<br><br>Name: PersonType<br>Type: -8<br>Size: 2<br>Precision: <br>Scale: <br>Nullable: 0<br><br>Name: NameStyle<br>Type: -7<br>Size: <br>Precision: 1<br>Scale: <br>Nullable: 0<br><br>Name: Title<br>Type: -9<br>Size: 8<br>Precision: <br>Scale: <br>Nullable: 1<br><br>Name: FirstName<br>Type: -9<br>Size: 50<br>Precision: <br>Scale: <br>Nullable: 0<br><br>Name: MiddleName<br>Type: -9<br>Size: 50<br>Precision: <br>Scale: <br>Nullable: 1<br><br>Name: LastName<br>Type: -9<br>Size: 50<br>Precision: <br>Scale: <br>Nullable: 0<br><br>Name: Suffix<br>Type: -9<br>Size: 10<br>Precision: <br>Scale: <br>Nullable: 1<br><br>Name: EmailPromotion<br>Type: 4<br>Size: <br>Precision: 10<br>Scale: <br>Nullable: 0<br><br>Name: AdditionalContactInfo<br>Type: -152<br>Size: 0<br>Precision: <br>Scale: <br>Nullable: 1<br><br>Name: Demographics<br>Type: -152<br>Size: 0<br>Precision: <br>Scale: <br>Nullable: 1<br><br>Name: rowguid<br>Type: -11<br>Size: 36<br>Precision: <br>Scale: <br>Nullable: 0<br><br>Name: ModifiedDate<br>Type: 93<br>Size: <br>Precision: 23<br>Scale: 3<br>Nullable: 0<br><br>
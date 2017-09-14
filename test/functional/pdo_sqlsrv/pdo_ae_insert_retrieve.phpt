--TEST--
Test for inserting encrypted data and retrieving both encrypted and decrypted data
Retrieving SQL query contains encrypted filter
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

try
{
    $conn = ae_connect();

    // Create the table
    $tbname = 'Patients';
    $colMetaArr = array( new columnMeta( "int", "PatientId", "IDENTITY(1,1)" ),
                         new columnMeta( "char(11)", "SSN"),
                         new columnMeta( "nvarchar(50)", "FirstName", "NULL" ),
                         new columnMeta( "nvarchar(50)", "LastName", "NULL"),
                         new columnMeta( "date", "BirthDate", null, "randomized" ));
    create_table( $conn, $tbname, $colMetaArr );
    
    // insert a row
    $SSN = "795-73-9838";
    $inputs = array( "SSN" => $SSN, "FirstName" => "Catherine", "LastName" => "Abel", "BirthDate" => "1996-10-19" );
    $stmt = insert_row( $conn, $tbname, $inputs );
   
    echo "Retrieving plaintext data:\n";
    $selectSql = "SELECT SSN, FirstName, LastName, BirthDate FROM $tbname WHERE SSN = ?";
    $stmt = $conn->prepare( $selectSql );
    $stmt->bindParam( 1, $SSN );
    $stmt->execute();
    $decrypted_row = $stmt->fetch( PDO::FETCH_ASSOC );
    foreach ( $decrypted_row as $key => $value )
    {
        print "$key: $value\n";
    }
    unset( $stmt );
}
catch( PDOException $e )
{
    echo $e->getMessage();
}

// for AE only
echo "\nChecking ciphertext data:\n";
if ( is_col_enc() )
{
    try
    {
        $conn1 = ae_connect( null, null, true );
        $selectSql = "SELECT SSN, FirstName, LastName, BirthDate FROM $tbname";
        $stmt = $conn1->query( $selectSql );
        $encrypted_row = $stmt->fetch( PDO::FETCH_ASSOC );
        foreach ( $encrypted_row as $key => $value )
        {
            if ( ctype_print( $value ))
                print "Error: expected a binary array for $key\n";
        }
        unset( $stmt );
        unset( $conn1 );
    }
    catch( PDOException $e )
    {
        echo $e->getMessage();
    }
}

DropTable( $conn, $tbname );
unset( $conn );

echo "Done\n";
?>
--EXPECT--
Retrieving plaintext data:
SSN: 795-73-9838
FirstName: Catherine
LastName: Abel
BirthDate: 1996-10-19

Checking ciphertext data:
Done
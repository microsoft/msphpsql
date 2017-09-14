--TEST--
Test for inserting encrypted varchar data of variable lengths and retrieving encrypted and decrypted data
--SKIPIF--

--FILE--
<?php
include 'MsSetup.inc';
include 'MsCommon.inc';
include 'AEData.inc';

$testPass = true;
try
{
    $conn = ae_connect();
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );

    // Create the table
    $tbname = 'VarcharAnalysis';
    $colMetaArr = array( new columnMeta( "int", "CharCount", "IDENTITY(0,1)" ), new columnMeta( "varchar(1000)" ));
    create_table( $conn, $tbname, $colMetaArr );
    
    // insert 1000 rows
    for( $i = 0; $i < 1000; $i++ )
    {
        $data = str_repeat( "*", $i );
        $stmt = insert_row( $conn, $tbname, array( get_default_colname( "varchar(1000)" ) => $data ) );
    }
    
    $selectSql = "SELECT * FROM $tbname";
    $stmt = $conn->query( $selectSql );
    while ( $decrypted_row = $stmt->fetch( PDO::FETCH_ASSOC )) 
    {
        if ( $decrypted_row[ 'CharCount' ] != strlen( $decrypted_row[ get_default_colname( "varchar(1000)" ) ] )) 
        {
            $rowInd = $decrypted_row[ 'CharCount' ] + 1;
            echo "Failed to decrypted at row $rowInd\n";
            $testPass = false;
        }
    }
    unset( $stmt );
    unset( $conn );
}
catch( PDOException $e )
{
    echo $e->getMessage();
}

// for AE only
if ( $keystore != "none" )
{
    try
    {
        $conn = connect( null, null, true );
        $stmt = $conn->query( $selectSql );
        while ( $decrypted_row = $stmt->fetch( PDO::FETCH_ASSOC )) 
        {
            if ( $decrypted_row[ 'CharCount' ] == strlen( $decrypted_row[ get_default_colname( "varchar(1000)" ) ] )) 
            {
                $rowInd = $decrypted_row[ 'CharCount' ] + 1;
                echo "Failed to encrypted at row $rowInd\n";
                $testPass = false;
            }
        }

        if ( $testPass ) {
            echo "Test successfully.\n";
        }

        DropTable( $conn, $tbname );
        unset( $stmt );
        unset( $conn );
    }
    catch( PDOException $e )
    {
        echo $e->getMessage();
    }
}
?>
--EXPECT--
Test successfully.
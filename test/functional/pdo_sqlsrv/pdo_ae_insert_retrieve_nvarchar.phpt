--TEST--
Test for inserting encrypted nvarchar data of variable lengths and retrieving encrypted and decrypted data
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

$testPass = true;
try
{
    $conn = ae_connect();

    // Create the table
    $tbname = 'NVarcharAnalysis';
    $colMetaArr = array( new columnMeta( "int", "CharCount", "IDENTITY(0,1)" ), new columnMeta( "nvarchar(1000)" ));
    create_table( $conn, $tbname, $colMetaArr );
    
    // insert 1000 rows
    for ( $i = 0; $i < 1000; $i++ )
    {
        $data = str_repeat( "*", $i );
        $stmt = insert_row( $conn, $tbname, array( get_default_colname( "nvarchar(1000)" ) => $data ) );
    }
    
    $selectSql = "SELECT * FROM $tbname";
    $stmt = $conn->query( $selectSql );
    while ( $decrypted_row = $stmt->fetch( PDO::FETCH_ASSOC )) 
    {
        if ( $decrypted_row[ 'CharCount' ] != strlen( $decrypted_row[ get_default_colname( "nvarchar(1000)" ) ] )) 
        {
            $rowInd = $decrypted_row[ 'CharCount' ] + 1;
            echo "Failed to decrypted at row $rowInd\n";
            $testPass = false;
        }
    }
    unset( $stmt );
}
catch( PDOException $e )
{
    echo $e->getMessage();
}

// for AE only
if ( is_col_enc() )
{
    try
    {
        $conn1 = ae_connect( null, null, true );
        $stmt = $conn1->query( $selectSql );
        while ( $decrypted_row = $stmt->fetch( PDO::FETCH_ASSOC )) 
        {
            if ( $decrypted_row[ 'CharCount' ] == strlen( $decrypted_row[ get_default_colname( "nvarchar(1000)" ) ] )) 
            {
                $rowInd = $decrypted_row[ 'CharCount' ] + 1;
                echo "Failed to encrypted at row $rowInd\n";
                $testPass = false;
            }
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

if ( $testPass ) {
    echo "Test successfully done.\n";
}

?>
--EXPECT--
Test successfully done.
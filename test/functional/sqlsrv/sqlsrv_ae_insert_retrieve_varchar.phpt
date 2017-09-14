--TEST--
Test for inserting encrypted varchar data of variable lengths and retrieving encrypted and decrypted data
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

$conn = ae_connect();
$testPass = true;

// Create the table
$tbname = 'VarcharAnalysis';
$colMetaArr = array( new columnMeta( "int", "CharCount", "IDENTITY(0,1)" ), new columnMeta( "varchar(1000)" ));
create_table( $conn, $tbname, $colMetaArr );


// insert 1000 rows
for ( $i = 0; $i < 1000; $i++ )
{
    $data = str_repeat( "*", $i );
    $stmt = insert_row( $conn, $tbname, array( get_default_colname( "varchar(1000)" ) => $data ));
}

$selectSql = "SELECT * FROM $tbname";
$stmt = sqlsrv_query( $conn, $selectSql );
while ( $decrypted_row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC ))
{
    if ( $decrypted_row[ 'CharCount' ] != strlen( $decrypted_row[ get_default_colname( "varchar(1000)" ) ] ))
    {
        $rowInd = $decrypted_row[ 'CharCount' ] + 1;
        echo "Failed to decrypted at row $rowInd\n";
        $testPass = false;
    }
}
sqlsrv_free_stmt( $stmt );

// for AE only
if ( is_col_enc() )
{
    $conn1 = ae_connect( null, true );
    $stmt = sqlsrv_query( $conn1, $selectSql );
    while ( $encrypted_row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC ))
    {
        if ( $encrypted_row[ 'CharCount' ] == strlen( $encrypted_row[ get_default_colname( "varchar(1000)" ) ] ))
        {
            $rowInd = $encrypted_row[ 'CharCount' ] + 1;
            echo "Failed to encrypted at row $rowInd\n";
            $testPass = false;
        }
    }
    sqlsrv_free_stmt( $stmt );
    sqlsrv_close( $conn1 );
}

DropTable( $conn, $tbname );
sqlsrv_close( $conn );

if ( $testPass ) {
    echo "Test successfully done.\n";
}

?>
--EXPECT--
Test successfully done.
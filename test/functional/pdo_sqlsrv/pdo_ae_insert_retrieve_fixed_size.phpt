--TEST--
Test for inserting encrypted fixed size types data and retrieve both encrypted and decrypted data
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

try
{
    $conn = ae_connect();

    // Create the table
    $tbname = 'FixedSizeAnalysis';
    $colMetaArr = array( new columnMeta( "tinyint", "TinyIntData" ), 
                         new columnMeta( "smallint", "SmallIntData" ), 
                         new columnMeta( "int", "IntData" ), 
                         new columnMeta( "bigint", "BigIntData" ), 
                         new columnMeta( "decimal(38,0)", "DecimalData" ), 
                         new columnMeta( "bit", "BitData" ), 
                         new columnMeta( "datetime", "DateTimeData" ), 
                         new columnMeta( "datetime2", "DateTime2Data" ));
    create_table( $conn, $tbname, $colMetaArr );
    
    // insert a row
    $inputs = array( "TinyIntData" => 255, 
                     "SmallIntData" => 32767, 
                     "IntData" => 2147483647, 
                     "BigIntData" => 92233720368547, 
                     "DecimalData" => 79228162514264, 
                     "BitData" => true, 
                     "DateTimeData" => '9999-12-31 23:59:59.997', 
                     "DateTime2Data" => '9999-12-31 23:59:59.9999999');
    $paramOptions = array( new bindParamOption( 4, "PDO::PARAM_INT" ) );
    $r;
    $stmt = insert_row( $conn, $tbname, $inputs, $r, "prepareBindParam", $paramOptions );
    
    print "Decrypted values:\n";
    fetch_all( $conn, $tbname );

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
        
        $selectSql = "SELECT * FROM $tbname";
        $stmt = $conn1->query( $selectSql );
        $encrypted_row = $stmt->fetch( PDO::FETCH_ASSOC );
        foreach ( $encrypted_row as $key => $value )
        {
            if ( ctype_print( $value ))
            {
                print "Error: expected a binary array for $key\n";
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

echo "Done\n";
?>
--EXPECT--
Decrypted values:
TinyIntData: 255
SmallIntData: 32767
IntData: 2147483647
BigIntData: 92233720368547
DecimalData: 79228162514264
BitData: 1
DateTimeData: 9999-12-31 23:59:59.997
DateTime2Data: 9999-12-31 23:59:59.9999999
Done
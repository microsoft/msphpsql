--TEST--
Provide name in lastInsertId to retrieve the last sequence number
--SKIPIF--
--FILE--
<?php  
require_once("MsCommon.inc");

function getNextSeq( $conn, $sequenceName )
{
    $sql = "SELECT NEXT VALUE FOR $sequenceName";
    $stmt = $conn->query( $sql );
    return $stmt->fetchColumn();
}

try{
    $database = "tempdb";
    $conn = connect();
    
    // sequence is only supported in SQL server 2012 and up (or version 11 and up)
    // Output Done once the server version is found to be < 11
    $version_arr = explode(".", $conn->getAttribute(PDO::ATTR_SERVER_VERSION));
    if ($version_arr[0] < 11) {
        echo "Done\n";
    }
    else {
        $tableName1 = GetTempTableName('tab1', false);
        $tableName2 = GetTempTableName('tab2', false);
        $sequenceName = 'sequence1';
    
        create_table( $conn, $tableName1, array( new columnMeta( "int", "seqnum", "NOT NULL PRIMARY KEY" ), new columnMeta( "int", "SomeNumber" )));
        create_table( $conn, $tableName2, array( new columnMeta( "int", "ID", "IDENTITY(1,2)" ), new columnMeta( "char(10)", "SomeValue" )));
        $conn->exec( "IF OBJECT_ID('$sequenceName', 'SO') IS NOT NULL DROP SEQUENCE $sequenceName" );
        $sql = "CREATE SEQUENCE $sequenceName AS INTEGER START WITH 1 INCREMENT BY 1 MINVALUE 1 MAXVALUE 100 CYCLE";
        $conn->exec( $sql );
    
        if ( !is_col_encrypted() )
        {
            $conn->exec("INSERT INTO $tableName1 VALUES( NEXT VALUE FOR $sequenceName, 20 )");
            $conn->exec("INSERT INTO $tableName1 VALUES( NEXT VALUE FOR $sequenceName, 40 )");
            $conn->exec("INSERT INTO $tableName1 VALUES( NEXT VALUE FOR $sequenceName, 60 )");
        }
        else
        {
            // if column seqnum is encrypted, need to get "NEXT VALUE FOR $sequenceName" separately first and then bind param
            $sql = "INSERT INTO $tableName1 VALUES( ?, ? )";
            $stmt = $conn->prepare( $sql );
            $nextSeq = getNextSeq( $conn, $sequenceName );
            $stmt->execute( array( $nextSeq, 20 ));
            $nextSeq = getNextSeq( $conn, $sequenceName );
            $stmt->execute( array( $nextSeq, 40 ));
            $nextSeq = getNextSeq( $conn, $sequenceName );
            $stmt->execute( array( $nextSeq, 60 ));
        }
        insert_row( $conn, $tableName2, array( "SomeValue" => "20" ));

        // return the last sequence number is sequence name is provided
        $lastSeq = $conn->lastInsertId($sequenceName);
        // defaults to $tableName2 -- because it returns the last inserted row id value
        $lastRow = $conn->lastInsertId();
        
        if ($lastSeq == 3 && $lastRow == 1) {
            echo "Done\n";
        }
        else {
            echo "sequence value or identity does not match as expected\n";
        }
        DropTable( $conn, $tableName1 );
        DropTable( $conn, $tableName2 );
        $conn->exec( "DROP SEQUENCE $sequenceName" );
        unset( $stmt );
    }
    unset( $conn );
}
catch (Exception $e){
    echo "Exception $e\n";
}
   
?>
--EXPECT--
Done
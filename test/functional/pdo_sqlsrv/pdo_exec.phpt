--TEST--
Test the PDO::exec() method.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once 'MsCommon.inc';
   
try 
{         
    $db = connect();
    
    $tbname = "tmp_table";
    $numRows = create_table( $db, $tbname, array( new columnMeta( "int", "id", "NOT NULL PRIMARY KEY" ), new columnMeta( "varchar(10)", "val" )));
    var_dump( $numRows );

    if ( !is_col_encrypted() )
    {
        $sql = "INSERT INTO $tbname VALUES(1, 'A')";
        $numRows = $db->exec($sql);
        var_dump($numRows);

        $sql = "INSERT INTO $tbname VALUES(2, 'B')";
        $numRows = $db->exec($sql);
        var_dump($numRows);

        $numRows = $db->exec("UPDATE $tbname SET val = 'X' WHERE id > 0");
        var_dump($numRows);

    }
    else
    {
        // cannot use exec for insertion and update with Always Encrypted
        $stmt = insert_row( $db, $tbname, array( "id" => 1, "val" => "A" ));
        $numRows = $stmt->rowCount();
        var_dump( $numRows );
        
        $stmt = insert_row( $db, $tbname, array( "id" => 2, "val" => "B" ));
        $numRows = $stmt->rowCount();
        var_dump( $numRows );
        
        // greater or less than operator is not support for encrypted columns
        $sql = "UPDATE $tbname SET val = ?";
        $stmt = $db->prepare( $sql );
        $stmt->execute( array( "X" ));
        $numRows = $stmt->rowCount();
        var_dump( $numRows );
    }
        
    $numRows = $db->exec( "DELETE FROM $tbname" );
    var_dump($numRows);
    
    DropTable( $db, $tbname );
    unset( $stmt );
    unset( $db );
    
}

catch( PDOException $e ) {
    var_dump( $e );
    exit;
}


?> 
--EXPECT--
int(0)
int(1)
int(1)
int(2)
int(2)

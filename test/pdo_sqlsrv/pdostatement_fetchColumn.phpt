--TEST--
Test the fetchColumn() method.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once 'MsCommon.inc';
 
  
  function fetch_column( $conn )
  {
     global $table1;
     $stmt = $conn->query( "Select * from ". $table1 );
     
     // Fetch the first column from the next row in resultset. (This wud be first row since this is a first call to fetchcol)
     $result = $stmt->fetchColumn();
     var_dump($result);
     
     // Fetch the second column from the next row. (This would be second row since this is a second call to fetchcol).
     $result = $stmt->fetchColumn(1);
     var_dump($result);

	 // Test false is returned when there are no more rows.
     $result = $stmt->fetchColumn(1);
     var_dump($result);
  }
 
  
try 
{      
   //$verbose = false;
   
   $db = connect();
   create_and_insert_table1( $db );
   fetch_column($db);
 
}

catch( PDOException $e ) {

    var_dump( $e );
    exit;
}


?> 
--EXPECT--

string(1) "1"
string(10) "STRINGCOL2"
bool(false)

 

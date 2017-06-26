--TEST--
fetches the rows in a result set in an array
--SKIPIF--

--FILE--
<?php
   require('connect.inc');
   $conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

   print "-----------\n";
   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $result = $stmt->fetchAll(PDO::FETCH_BOTH);
   print_r( $result );
   print "\n-----------\n";

   print "-----------\n";
   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $result = $stmt->fetchAll(PDO::FETCH_NUM);
   print_r( $result );
   print "\n-----------\n";

   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
   print_r( $result );
   print "\n-----------\n";

   class cc {
      function __construct( $arg ) {
         echo "$arg\n";
      }

      function __toString() {
         echo "To string\n";
      }
   };

   $stmt = $conn->query( 'SELECT TOP(2) * FROM Person.ContactType' );
   $all = $stmt->fetchAll( PDO::FETCH_CLASS, 'cc', array( 'Hi!' ));
   var_dump( $all );
   
   //free the statement and connection 
   $stmt=null;
   $conn=null;
?>
--EXPECT--
-----------
Array
(
    [0] => Array
        (
            [ContactTypeID] => 1
            [0] => 1
            [Name] => Accounting Manager
            [1] => Accounting Manager
            [ModifiedDate] => 2008-04-30 00:00:00.000
            [2] => 2008-04-30 00:00:00.000
        )

    [1] => Array
        (
            [ContactTypeID] => 2
            [0] => 2
            [Name] => Assistant Sales Agent
            [1] => Assistant Sales Agent
            [ModifiedDate] => 2008-04-30 00:00:00.000
            [2] => 2008-04-30 00:00:00.000
        )

    [2] => Array
        (
            [ContactTypeID] => 3
            [0] => 3
            [Name] => Assistant Sales Representative
            [1] => Assistant Sales Representative
            [ModifiedDate] => 2008-04-30 00:00:00.000
            [2] => 2008-04-30 00:00:00.000
        )

    [3] => Array
        (
            [ContactTypeID] => 4
            [0] => 4
            [Name] => Coordinator Foreign Markets
            [1] => Coordinator Foreign Markets
            [ModifiedDate] => 2008-04-30 00:00:00.000
            [2] => 2008-04-30 00:00:00.000
        )

)

-----------
-----------
Array
(
    [0] => Array
        (
            [0] => 1
            [1] => Accounting Manager
            [2] => 2008-04-30 00:00:00.000
        )

    [1] => Array
        (
            [0] => 2
            [1] => Assistant Sales Agent
            [2] => 2008-04-30 00:00:00.000
        )

    [2] => Array
        (
            [0] => 3
            [1] => Assistant Sales Representative
            [2] => 2008-04-30 00:00:00.000
        )

    [3] => Array
        (
            [0] => 4
            [1] => Coordinator Foreign Markets
            [2] => 2008-04-30 00:00:00.000
        )

)

-----------
Array
(
    [0] => Accounting Manager
    [1] => Assistant Sales Agent
    [2] => Assistant Sales Representative
    [3] => Coordinator Foreign Markets
)

-----------
Hi!
Hi!
array(2) {
  [0]=>
  object(cc)#2 (3) {
    ["ContactTypeID"]=>
    string(1) "1"
    ["Name"]=>
    string(18) "Accounting Manager"
    ["ModifiedDate"]=>
    string(23) "2008-04-30 00:00:00.000"
  }
  [1]=>
  object(cc)#4 (3) {
    ["ContactTypeID"]=>
    string(1) "2"
    ["Name"]=>
    string(21) "Assistant Sales Agent"
    ["ModifiedDate"]=>
    string(23) "2008-04-30 00:00:00.000"
  }
}
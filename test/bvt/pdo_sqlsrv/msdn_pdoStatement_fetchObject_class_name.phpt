--TEST--
fetches the next row as an object of a user defined class
--SKIPIF--

--FILE--
<?php
	//create class of contactType
	//the names of the attributes in the class has to be the same as the column names in the database
	class contactTypes{
		public $ContactTypeID;
		public $Name;
		public $ModifiedDate;
		// function that increments that contact id by 10
		public function upperCaseName(){
			return strtoupper($this->Name);
		}
	}// end of class
   require('connect.inc');
   $conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID = 5 " );
   $contactTypes = $stmt->fetchObject('contactTypes');
   
   //print the class properties
   print $contactTypes->ContactTypeID."\n";
   print $contactTypes->upperCaseName()."\n";
   print $contactTypes->ModifiedDate;
   
   // close the database connection
   $stmt=null;
   $conn=null;
?>
--EXPECT--
5
EXPORT ADMINISTRATOR
2008-04-30 00:00:00.000
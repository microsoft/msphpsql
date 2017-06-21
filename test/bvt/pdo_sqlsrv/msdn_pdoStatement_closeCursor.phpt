--TEST--
closes the cursor
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd", array('MultipleActiveResultSets' => false ) );

$stmt = $conn->prepare('SELECT * FROM Person.ContactType');

$stmt2 = $conn->prepare('SELECT * FROM HumanResources.Department');

$stmt->execute();

$result = $stmt->fetch();
print_r($result);

$stmt->closeCursor();

$stmt2->execute();
$result = $stmt2->fetch();
print_r($result);

//free the statements and connection 
$stmt=null;
$stmt2=null;
$conn=null;
?>
--EXPECT--
Array
(
    [ContactTypeID] => 1
    [0] => 1
    [Name] => Accounting Manager
    [1] => Accounting Manager
    [ModifiedDate] => 2008-04-30 00:00:00.000
    [2] => 2008-04-30 00:00:00.000
)
Array
(
    [DepartmentID] => 1
    [0] => 1
    [Name] => Engineering
    [1] => Engineering
    [GroupName] => Research and Development
    [2] => Research and Development
    [ModifiedDate] => 2008-04-30 00:00:00.000
    [3] => 2008-04-30 00:00:00.000
)
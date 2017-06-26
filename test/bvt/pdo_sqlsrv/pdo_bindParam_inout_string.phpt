--TEST--
call a stored procedure and retrieve the errorString that is returned
--SKIPIF--

--FILE--
<?php
	require('connect.inc');
	$conn = new PDO( "sqlsrv:server=$server ; Database = tempdb", "$uid", "$pwd");

	// Drop the stored procedure if it already exists
	$tsql_dropSP = "IF OBJECT_ID('sp_Test_String', 'P') IS NOT NULL
					DROP PROCEDURE sp_Test_String";
					
	$stmt = $conn->query($tsql_dropSP);

	// Create the stored procedure
	$tsql_createSP = "CREATE PROCEDURE sp_Test_String
						@ErrorString as varchar(20) OUTPUT
					  AS
					  BEGIN
						SET @ErrorString = REVERSE(@ErrorString)
						SELECT 1,2,3
					  END";
	$stmt = $conn->query($tsql_createSP);

	// Call the stored procedure
	$stmt = $conn->prepare("{CALL sp_Test_String (?)}");

	$errorString = "12345";	
	$stmt->bindParam(1, $errorString, PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT, 20);
	print("Error String: $errorString\n\n");

	$stmt->execute();

	$result = $stmt->fetchAll(PDO::FETCH_NUM);

	$stmt->closeCursor();
	
	print("Error String: $errorString\n\n");
	print_r($result);
	
	//free the statement and connection
	$stmt = null;
	$conn = null;
?>
--EXPECT--
Error String: 12345

Error String: 54321

Array
(
    [0] => Array
        (
            [0] => 1
            [1] => 2
            [2] => 3
        )

)
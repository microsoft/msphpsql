--TEST--
call a stored procedure and retrieve the errorNumber that is returned
--SKIPIF--

--FILE--
<?php
	require('connect.inc');
	$conn = new PDO( "sqlsrv:server=$server ; Database = tempdb", "$uid", "$pwd");

	// Drop the stored procedure if it already exists
	$tsql_dropSP = "IF OBJECT_ID('sp_Test_Integer', 'P') IS NOT NULL
					DROP PROCEDURE sp_Test_Integer";
					
	$stmt = $conn->query($tsql_dropSP);

	// Create the stored procedure
	$tsql_createSP = "CREATE PROCEDURE sp_Test_Integer
						@ErrorNumber AS INT = 0 OUTPUT
					  AS
					  BEGIN
						SET @ErrorNumber = -1
						SELECT 1,2,3
					  END";

	$stmt = $conn->query($tsql_createSP);

	// Call the stored procedure
	$stmt = $conn->prepare("{CALL sp_Test_Integer (:errornumber)}");

	$errorNumber = 0;	
	$stmt->bindParam('errornumber', $errorNumber, PDO::PARAM_INT|PDO::PARAM_INPUT_OUTPUT, 4);

	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_NUM);

	$stmt->closeCursor();

	print("Error Number: $errorNumber\n\n");
	print_r($result);
	
	//free the statement and connection
	$stmt = null;
	$conn = null;
?>
--EXPECT--
Error Number: -1

Array
(
    [0] => Array
        (
            [0] => 1
            [1] => 2
            [2] => 3
        )

)
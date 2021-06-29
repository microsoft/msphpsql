--TEST--
call a stored procedure and retrieve the errorNumber that is returned
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
	require('connect.inc');
	$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

	dropProc($conn, 'sp_Test_Double');

	// Create the stored procedure
	$tsql_createSP = "CREATE PROCEDURE sp_Test_Double
						@ErrorNumber as float(53) = 0.0 OUTPUT
					  AS
					  BEGIN
						SET @ErrorNumber = -1.111
						SELECT 1, 2, 3
					  END";
	$stmt = $conn->query($tsql_createSP);

	// Call the stored procedure
	$stmt = $conn->prepare("{CALL sp_Test_Double (?)}");

	$errorNumber = 0.0;	
	$stmt->bindParam(1, $errorNumber, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 20);
	$stmt->execute();

	$result = $stmt->fetchAll(PDO::FETCH_NUM);

	$stmt->closeCursor();

	print("Error Number: $errorNumber\n\n");
	$value = $errorNumber - 2;
	print("Error Number minus 2: $value\n\n");

	print_r($result);
	dropProc($conn, 'sp_Test_Double', false);

	//free the statement and connection
	unset($stmt);
	unset($conn);
?>
--EXPECT--
Error Number: -1.111

Error Number minus 2: -3.111

Array
(
    [0] => Array
        (
            [0] => 1
            [1] => 2
            [2] => 3
        )

)
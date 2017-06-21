--TEST--
call a stored procedure (SQLSRV Driver) and retrieve the errorNumber that is returned
--SKIPIF--

--FILE--
<?php
	// Connect to the database
	require('connect.inc');
	$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
	$conn = sqlsrv_connect( $server, $connectionInfo);

	if ($conn === false){
		echo "Could not connect.\n";
		die (print_r (sqlsrv_errors(), true));
	}

	// Drop the stored procedure if it already exists
	$tsql_dropSP = "IF OBJECT_ID('sp_Test', 'P') IS NOT NULL
					DROP PROCEDURE sp_Test";
	$stmt1 = sqlsrv_query($conn, $tsql_dropSP);
	if ($stmt1 === false) {
		echo "Error in executing statement 1.\n";
		die (print_r (sqlsrv_errors(), true));		
	}
	
	// Create the stored procedure
	$tsql_createSP = "CREATE PROCEDURE sp_Test
						@ErrorNumber INT = 0 OUTPUT
					  AS
					  BEGIN
						SET @ErrorNumber = -1
						SELECT 1,2,3
					  END";
	$stmt2 = sqlsrv_query($conn, $tsql_createSP);
	if ($stmt2 === false) {
		echo "Error in executing statement 2.\n";
		die (print_r (sqlsrv_errors(), true));		
	}
					  
	// Call the stored procedure
	$tsql_callSP = "{CALL sp_Test (?)}";
	
	// Define the parameter array
	$errorNumber = -5;
	$params = array(
				array(&$errorNumber, SQLSRV_PARAM_INOUT)
				);
	
	// Execute the query
	$stmt3 = sqlsrv_query($conn, $tsql_callSP, $params);
	if ($stmt3 === false) {
		echo "Error in executing statement 3.\n";
		die (print_r (sqlsrv_errors(), true));		
	}
	
	// Display the value of the output parameter $errorNumber
	sqlsrv_next_result($stmt3);
	print("Error Number: $errorNumber\n\n");
	
	// Free the statement and connection resources. */
	sqlsrv_free_stmt( $stmt1);
	sqlsrv_free_stmt( $stmt2);
	sqlsrv_free_stmt( $stmt3);
	sqlsrv_close( $conn);
?>
--EXPECT--
Error Number: -1

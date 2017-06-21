--TEST--
queries a call procedure with an in-out parameter.
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
     echo "Could not connect.\n";
     die( print_r( sqlsrv_errors(), true));
}
/* Drop the stored procedure if it already exists. */
$tsql_dropSP = "IF OBJECT_ID('SubtractVacationHours', 'P') IS NOT NULL
                DROP PROCEDURE SubtractVacationHours";
$stmt1 = sqlsrv_query( $conn, $tsql_dropSP);
if( $stmt1 === false )
{
     echo "Error in executing statement 1.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Create the stored procedure. */
$tsql_createSP = "CREATE PROCEDURE SubtractVacationHours
                        @BusinessEntityID int,
                        @VacationHrs smallint OUTPUT
                  AS
                  UPDATE HumanResources.Employee
                  SET VacationHours = VacationHours - @VacationHrs
                  WHERE BusinessEntityID = @BusinessEntityID;
                  SET @VacationHrs = (SELECT VacationHours
                                      FROM HumanResources.Employee
                                      WHERE BusinessEntityID = @BusinessEntityID)";

$stmt2 = sqlsrv_query( $conn, $tsql_createSP);
if( $stmt2 === false )
{
     echo "Error in executing statement 2.\n";
     die( print_r( sqlsrv_errors(), true));
}

/*--------- The next few steps call the stored procedure. ---------*/

/* Define the Transact-SQL query. Use question marks (?) in place of
the parameters to be passed to the stored procedure */
$tsql_callSP = "{call SubtractVacationHours( ?, ?)}";

/* Define the parameter array. By default, the first parameter is an
INPUT parameter. The second parameter is specified as an INOUT
parameter. Initializing $vacationHrs to 8 sets the returned PHPTYPE to
integer. To ensure data type integrity, output parameters should be
initialized before calling the stored procedure, or the desired
PHPTYPE should be specified in the $params array.*/

$employeeId = 4;
$vacationHrs = 1;
$params = array( 
                 array($employeeId, SQLSRV_PARAM_IN),
                 array(&$vacationHrs, SQLSRV_PARAM_INOUT)
               );

/* Execute the query. */
$stmt3 = sqlsrv_query( $conn, $tsql_callSP, $params);
if( $stmt3 === false )
{
     echo "Error in executing statement 3.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Display the value of the output parameter $vacationHrs. */
sqlsrv_next_result($stmt3);
echo "Remaining vacation hours: ".$vacationHrs;

/* Revert the update in vacation hours */
$r_sql = "UPDATE HumanResources.Employee SET VacationHours=48 WHERE BusinessEntityID=4";
$stmt4 = sqlsrv_query($conn, $r_sql);

/*Free the statement and connection resources. */
sqlsrv_free_stmt( $stmt1);
sqlsrv_free_stmt( $stmt2);
sqlsrv_free_stmt( $stmt3);
sqlsrv_free_stmt( $stmt4);
sqlsrv_close( $conn);
?>
--EXPECT--
Remaining vacation hours: 47
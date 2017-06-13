--TEST--
disables the default error-handling behaviour using configure and returns warnings
--SKIPIF--

--FILE--
<?php
/* Turn off the default behavior of treating errors as warnings.
Note: Turning off the default behavior is done here for demonstration
purposes only. If setting the configuration fails, display errors and
exit the script. */
if( sqlsrv_configure("WarningsReturnAsErrors", 0) === false)
{
     DisplayErrors();
     die;
}

/* Connect to the local server using Windows Authentication and 
specify the AdventureWorks database as the database in use. */
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);

/* If the connection fails, display errors and exit the script. */
if( $conn === false )
{
     DisplayErrors();
     die;
}
/* Display any warnings. */
DisplayWarnings();

/* Revert previous updates */
$r_sql="UPDATE HumanResources.Employee SET VacationHours=61 WHERE BusinessEntityID=7;
		UPDATE HumanResources.Employee SET VacationHours=62 WHERE BusinessEntityID=8;
		UPDATE HumanResources.Employee SET VacationHours=63 WHERE BusinessEntityID=9;
		UPDATE HumanResources.Employee SET VacationHours=7 WHERE BusinessEntityID=11";
$stmt4=sqlsrv_query($conn, $r_sql);
sqlsrv_free_stmt( $stmt4 );

/* Drop the stored procedure if it already exists. */
$tsql1 = "IF OBJECT_ID('SubtractVacationHours', 'P') IS NOT NULL
                DROP PROCEDURE SubtractVacationHours";
$stmt1 = sqlsrv_query($conn, $tsql1);

/* If the query fails, display errors and exit the script. */
if( $stmt1 === false)
{
     DisplayErrors();
     die;
}
/* Display any warnings. */
DisplayWarnings();

/* Free the statement resources. */
sqlsrv_free_stmt( $stmt1 );

/* Create the stored procedure. */
$tsql2 = "CREATE PROCEDURE SubtractVacationHours
                  @BusinessEntityId int,
                  @VacationHours smallint OUTPUT
              AS
                  UPDATE HumanResources.Employee
                  SET VacationHours = VacationHours - @VacationHours
                  WHERE BusinessEntityId = @BusinessEntityId;
                  SET @VacationHours = (SELECT VacationHours  
                                       FROM HumanResources.Employee
                                       WHERE BusinessEntityId = @BusinessEntityId);
              IF @VacationHours < 0 
              BEGIN
                PRINT 'WARNING: Vacation hours are now less than zero.'
              END;";
$stmt2 = sqlsrv_query( $conn, $tsql2 );

/* If the query fails, display errors and exit the script. */
if( $stmt2 === false)
{
     DisplayErrors();
     die;
}
/* Display any warnings. */
DisplayWarnings();

/* Free the statement resources. */
sqlsrv_free_stmt( $stmt2 );

/* Set up the array that maps employee ID to used vacation hours. */
$emp_hrs = array (7=>4, 8=>5, 9=>8, 11=>50);

/* Initialize variables that will be used as parameters. */
$businessEntityId = 0;
$vacationHrs = 0;

/* Set up the parameter array. */
$params = array(
                 array(&$businessEntityId, SQLSRV_PARAM_IN),
                 array(&$vacationHrs, SQLSRV_PARAM_INOUT)
                );

/* Define and prepare the query to substract used vacation hours. */
$tsql3 = "{call SubtractVacationHours(?, ?)}";
$stmt3 = sqlsrv_prepare($conn, $tsql3, $params);

/* If the statement preparation fails, display errors and exit the script. */
if( $stmt3 === false)
{
     DisplayErrors();
     die;
}
/* Display any warnings. */
DisplayWarnings();

/* Loop through the employee=>vacation hours array. Update parameter
 values before statement execution. */
foreach(array_keys($emp_hrs) as $businessEntityId)
{
     $vacationHrs = $emp_hrs[$businessEntityId];
     /* Execute the query.  If it fails, display the errors. */
     if( sqlsrv_execute($stmt3) === false)
     {
          DisplayErrors();
          die;
     }
     /* Display any warnings. */
     DisplayWarnings();

     /*Move to the next result returned by the stored procedure. */
     if( sqlsrv_next_result($stmt3) === false)
     {
          DisplayErrors();
          die;
     }
     /* Display any warnings. */
     DisplayWarnings();

     /* Display updated vacation hours. */
     echo "BusinessEntityId $businessEntityId has $vacationHrs ";
     echo "remaining vacation hours.\n";
}

/* Free the statement*/
sqlsrv_free_stmt( $stmt3 );

/* close connection resources. */
sqlsrv_close( $conn );

/* ------------- Error Handling Functions --------------*/
function DisplayErrors()
{
    $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
    foreach( $errors as $error )
    {
        echo "Error: ".$error['message']."\n";
    }
}

function DisplayWarnings()
{
    $warnings = sqlsrv_errors(SQLSRV_ERR_WARNINGS);
    if(!is_null($warnings))
    {
        foreach( $warnings as $warning )
        {
            $message = $warning['message'];
            // Skips the message with 'unixODBC' (an unnecessary duplicate message in some platform)
            if (! stripos($message, 'unixODBC') )
                echo "Warning: $message\n";
        }
    }
}
?>
--EXPECTREGEX--
Warning: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Changed database context to 'AdventureWorks2014'.
Warning: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Changed language setting to us_english.
BusinessEntityId 7 has 57 remaining vacation hours.
BusinessEntityId 8 has 57 remaining vacation hours.
BusinessEntityId 9 has 55 remaining vacation hours.
Error: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]The UPDATE statement conflicted with the CHECK constraint "CK_Employee_VacationHours". The conflict occurred in database "AdventureWorks2014", table "HumanResources.Employee", column 'VacationHours'.
Error: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]The statement has been terminated.
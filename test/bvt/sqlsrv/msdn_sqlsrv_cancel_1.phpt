--TEST--
cancels a statement then reuse.
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

/* Prepare and execute the query. */
echo "<h4>SELECT : Scrollable => SQLSRV_CURSOR_KEYSET</h4>";
$sql = "SELECT * FROM Person.Address";
// $tsql = "SELECT * FROM HumanResources.Employee";


$params = array();
$options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
$options = array();
$stmt = sqlsrv_query($conn, $sql, $params, $options);

// sqlsrv_execute ( $stmt );

// $stmt = sqlsrv_query( $conn, $sql, array(), array() );

// PRINT RESULT SET
$numRowsPrint = 3;
echo "<h4>Printing first $numRowsPrint rows</h4>";
echo "<p><table cellpadding=3 border=1 cellspacing=4>";
$count = 0;
// while( ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) && $count <$numRowsPrint)
while( ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_NUMERIC)) && $count <$numRowsPrint)
{
	echo "<tr>";
 	echo "<td>" . $row[0] . "</td>";
	echo "<td>" . $row[1] . "</td>";
	echo "<td>" . $row[2] . "</td>";
	echo "<td>" . $row[3] . "</td>";
	echo "<td>" . $row[4] . "</td>";
	echo "<td>" . $row[7] . "</td>";
	echo "</tr>";
    $count++;
}
echo "</table>";	



/* Cancel the pending results. The statement can be reused. */
sqlsrv_cancel( $stmt);

// PRINT RESULT SET
echo "<h4>SQLSRV_CANCEL + Print next 5 rows of the result set (MUST BE EMPTY)</h4>";
echo "<p><table cellpadding=3 border=1 cellspacing=4>";
while( ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_NUMERIC)) && $count <($numRowsPrint + 5))
{
	echo "<p><font color=red>IF sqlsrv_cancel() is executed, YOU SHOUL NOT SEE THIS</font>";
	
	echo "<tr>";
 	echo "<td>" . $row[0] . "</td>";
	echo "<td>" . $row[1] . "</td>";
	echo "<td>" . $row[3] . "</td>";
	echo "<td>" . $row[4] . "</td>";
	echo "<td>" . $row[7] . "</td>";
	echo "</tr>";
    $count++;
}
echo "</table>";	

echo "<p><font color=green>Finished successfully</font>";



?>
--EXPECT--
<h4>SELECT : Scrollable => SQLSRV_CURSOR_KEYSET</h4><h4>Printing first 3 rows</h4><p><table cellpadding=3 border=1 cellspacing=4><tr><td>1</td><td>1970 Napa Ct.</td><td></td><td>Bothell</td><td>79</td><td>9AADCB0D-36CF-483F-84D8-585C2D4EC6E9</td></tr><tr><td>2</td><td>9833 Mt. Dias Blv.</td><td></td><td>Bothell</td><td>79</td><td>32A54B9E-E034-4BFB-B573-A71CDE60D8C0</td></tr><tr><td>3</td><td>7484 Roundtree Drive</td><td></td><td>Bothell</td><td>79</td><td>4C506923-6D1B-452C-A07C-BAA6F5B142A4</td></tr></table><h4>SQLSRV_CANCEL + Print next 5 rows of the result set (MUST BE EMPTY)</h4><p><table cellpadding=3 border=1 cellspacing=4></table><p><font color=green>Finished successfully</font>
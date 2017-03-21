--TEST--
Connection recovery test
--DESCRIPTION--
Connect and execute a command, kill the connection, execute another command. Then do it again without a buffered result set, by freeing the statement and then not freeing it. The latter case is the only one that should fail. Finally, execute two queries in two threads on a recovered non-MARS connection. This should fail too.
--SKIPIF--
<?php if ( !( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) ) die( "Skip, not running on windows." ); ?>
--FILE--
<?php
require_once( "break_pdo.php" );

StartMSSQLServer( $serverName );

$connectionInfo = "ConnectRetryCount = 10; ConnectRetryInterval = 10;";

///////////////////////////////////////////////////
// Part 1 /////////////////////////////////////////
///////////////////////////////////////////////////

try
{
    $conn = new PDO( "sqlsrv:server = $serverName ; Database = $databaseName ; $connectionInfo", "$username", "$password" );
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
}
catch( PDOException $e )
{
    echo "Could not connect.\n";
    print_r( $e->getMessage() );
}

$query1 = "SELECT * FROM $tableName1";

try
{
    $stmt1 = $conn->prepare( $query1, array( PDO::ATTR_CURSOR=> PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=> PDO::SQLSRV_CURSOR_BUFFERED ) );
    $stmt1->execute();
    echo "Statement 1 successful.\n";

    $rowcount = $stmt1->rowCount();
    echo $rowcount." rows in result set.\n";
}
catch( PDOException $e )
{
    echo "Error executing statement 1.\n";
    print_r( $e->getMessage() );
}

RestartMSSQLServer( $serverName );

$query2 = "SELECT * FROM $tableName2";

try
{
    $stmt2 = $conn->prepare( $query2, array( PDO::ATTR_CURSOR=> PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=> PDO::SQLSRV_CURSOR_BUFFERED ) );
    $stmt2->execute();
    echo "Statement 2 successful.\n";

    $rowcount = $stmt2->rowCount();
    echo $rowcount." rows in result set.\n";
}
catch( PDOException $e )
{
    echo "Error executing statement 2.\n";
    print_r( $e->getMessage() );
}

$conn = null;

///////////////////////////////////////////////////
// Part 2 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = "ConnectRetryCount = 11; ConnectRetryInterval = 10;";

try
{
    $conn = new PDO( "sqlsrv:server = $serverName ; Database = $databaseName ; $connectionInfo", "$username", "$password" );
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
}
catch( PDOException $e )
{
    echo "Could not connect.\n";
    print_r( $e->getMessage() );
}

$query1 = "SELECT * FROM $tableName1";

try
{
    $stmt3 = $conn->query( $query1 );
    echo "Statement 3 successful.\n";

    $rowcount = $stmt3->rowCount();
    echo $rowcount." rows in result set.\n";
}
catch( PDOException $e )
{
    echo "Error executing statement 3.\n";
    print_r( $e->getMessage() );
}

$stmt3 = null;

RestartMSSQLServer( $serverName );

$query2 = "SELECT * FROM $tableName2";

try
{
    $stmt4 = $conn->query( $query2 );
    echo "Statement 4 successful.\n";

    $rowcount = $stmt4->rowCount();
    echo $rowcount." rows in result set.\n";
}
catch( PDOException $e )
{
    echo "Error executing statement 4.\n";
    print_r( $e->getMessage() );
}

$conn = null;

///////////////////////////////////////////////////
// Part 3 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = "ConnectRetryCount = 12; ConnectRetryInterval = 10;";

try
{
    $conn = new PDO( "sqlsrv:server = $serverName ; Database = $databaseName ; $connectionInfo", "$username", "$password" );
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
}
catch( PDOException $e )
{
    echo "Could not connect.\n";
    print_r( $e->getMessage() );
}

$query1 = "SELECT * FROM $tableName1";

try
{
    $stmt5 = $conn->query( $query1 );
    echo "Statement 5 successful.\n";

    $rowcount = $stmt5->rowCount();
    echo $rowcount." rows in result set.\n";
}
catch( PDOException $e )
{
    echo "Error executing statement 5.\n";
    print_r( $e->getMessage() );
}

RestartMSSQLServer( $serverName );

$query2 = "SELECT * FROM $tableName2";

try
{
    $stmt6 = $conn->query( $query2 );
    echo "Statement 6 successful.\n";

    $rowcount = $stmt6->rowCount();
    echo $rowcount." rows in result set.\n";
}
catch( PDOException $e )
{
    echo "Error executing statement 6.\n";
    print_r( $e->getMessage() );
}

$conn = null;

///////////////////////////////////////////////////
// Part 4 /////////////////////////////////////////
///////////////////////////////////////////////////

$connectionInfo = "ConnectRetryCount = 10; ConnectRetryInterval = 10; MultipleActiveResultSets = false;";

try
{
    $conn = new PDO( "sqlsrv:server = $serverName ; Database = $databaseName ; $connectionInfo", "$username", "$password" );
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
}
catch( PDOException $e )
{
    echo "Could not connect.\n";
    print_r( $e->getMessage() );
}

RestartMSSQLServer( $serverName );

try
{
    $stmt7 = $conn->query( "SELECT * FROM $tableName1" );
    echo "Statement 7 successful.\n";
}
catch( PDOException $e )
{
    echo "Error executing statement 7.\n";
    print_r( $e->getMessage() );
}

try
{
    $stmt8 = $conn->query( "SELECT * FROM $tableName2" );
    echo "Statement 8 successful.\n";
}
catch( PDOException $e )
{
    echo "Error executing statement 8.\n";
    print_r( $e->getMessage() );
}

$conn = null;

?>
--EXPECTREGEX--
Statement 1 successful.
16 rows in result set.
Statement 2 successful.
9 rows in result set.
Statement 3 successful.
-1 rows in result set.
Statement 4 successful.
-1 rows in result set.
Statement 5 successful.
-1 rows in result set.
Error executing statement 6.
SQLSTATE\[08S02\]: \[Microsoft\]\[ODBC Driver 11 for SQL Server\]TCP Provider: An existing connection was forcibly closed by the remote host.
Statement 7 successful.
Error executing statement 8.
SQLSTATE\[IMSSP\]: The connection cannot process this operation because there is a statement with pending results.  To make the connection available for other queries, either fetch all results or cancel or free the statement.  For more information, see the product documentation about the MultipleActiveResultSets connection option.

<?php
require_once("ConInfo.inc");

// Using the tempdb database for two tables specifically constructed
// for the connection resiliency tests
$dbName = "tempdb";

$tableName1 = "test_connres1";
$tableName2 = "test_connres2";

// Generate tables for use with the connection resiliency tests.
// Using generated tables will eventually allow us to put the
// connection resiliency tests on Github, since the integrated testing
// from AppVeyor does not have AdventureWorks.
function GenerateTables( $server, $uid, $pwd, $dbName, $tableName1, $tableName2 )
{
    $conn = new PDO( "sqlsrv:server = $server ; Database = $dbName ;", $uid, $pwd );
    if ( $conn === false )
    {
        die ( print_r( sqlsrv_errors() ) );
    }

    // Create table
    $sql = "CREATE TABLE $tableName1 ( c1 INT, c2 VARCHAR(40) )";
    $stmt = $conn->query( $sql );

    // Insert data
    $sql = "INSERT INTO $tableName1 VALUES ( ?, ? )";
    for( $t = 100; $t < 116; $t++ ) 
    {
        $stmt = $conn->prepare( $sql );
        $ts = substr( sha1( $t ),0,5 );
        $params = array( $t,$ts );
        $stmt->execute( $params );
    }

    // Create table
    $sql = "CREATE TABLE $tableName2 ( c1 INT, c2 VARCHAR(40) )";
    $stmt = $conn->query( $sql );

    // Insert data
    $sql = "INSERT INTO $tableName2 VALUES ( ?, ? )";
    for( $t = 200; $t < 209; $t++ ) 
    {
        $stmt = $conn->prepare( $sql );
        $ts = substr( sha1( $t ),0,5 );
        $params = array( $t,$ts );
        $stmt->execute( $params );
    }

    $conn = null;
}

// Break connection by getting the session ID and killing it.
// Note that breaking a connection and testing reconnection requires a
// TCP/IP protocol connection (as opposed to a Shared Memory protocol).
function BreakConnection( $conn, $conn_break )
{
    $stmt1 = $conn->query( "SELECT @@SPID" );
    $obj = $stmt1->fetch( PDO::FETCH_NUM );
    $spid = $obj[0];

    $stmt2 = $conn_break->query( "KILL ".$spid );
    sleep(1);
}

// Remove any databases previously created by GenerateDatabase
function DropTables( $server, $uid, $pwd, $tableName1, $tableName2 )
{
    $conn = new PDO( "sqlsrv:server = $server ; ", $uid, $pwd );
    
    $query="IF OBJECT_ID('tempdb.dbo.$tableName1', 'U') IS NOT NULL DROP TABLE tempdb.dbo.$tableName1";
    $stmt=$conn->query( $query );

    $query="IF OBJECT_ID('tempdb.dbo.$tableName2', 'U') IS NOT NULL DROP TABLE tempdb.dbo.$tableName2";
    $stmt=$conn->query( $query );
}

DropTables( $server, $uid, $pwd, $tableName1, $tableName2 );
GenerateTables( $server, $uid, $pwd, $dbName, $tableName1, $tableName2 );

?>
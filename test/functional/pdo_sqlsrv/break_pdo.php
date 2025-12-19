<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

// Using the test database for two tables specifically constructed
// for the connection resiliency tests
$tableName1 = "test_connres1";
$tableName2 = "test_connres2";

// Generate tables for use with the connection resiliency tests.
// Using generated tables will eventually allow us to put the
// connection resiliency tests on Github, since the integrated testing
// from AppVeyor does not have AdventureWorks.
function generateTables($tableName1, $tableName2)
{
    try {
        $conn = connect();

        // Create table
        $sql = "CREATE TABLE $tableName1 (c1 INT, c2 VARCHAR(40))";
        $stmt = $conn->query($sql);

        // Insert data
        $sql = "INSERT INTO $tableName1 VALUES (?, ?)";
        for ($t = 100; $t < 116; $t++) {
            $stmt = $conn->prepare($sql);
            $ts = substr(sha1($t), 0, 5);
            $params = array($t, $ts);
            $stmt->execute($params);
        }

        // Create table
        $sql = "CREATE TABLE $tableName2 (c1 INT, c2 VARCHAR(40))";
        $stmt = $conn->query($sql);

        // Insert data
        $sql = "INSERT INTO $tableName2 VALUES (?, ?)";
        for ($t = 200; $t < 209; $t++) {
            $stmt = $conn->prepare($sql);
            $ts = substr(sha1($t), 0, 5);
            $params = array($t, $ts);
            $stmt->execute($params);
        }

        unset($conn);
    } catch (PDOException $e) {
        var_dump($e->errorInfo);
    }
}

// Break connection by getting the session ID and killing it.
// Note that breaking a connection and testing reconnection requires a
// TCP/IP protocol connection (as opposed to a Shared Memory protocol).
// Wait one second before and after breaking to ensure the break occurs
// in the correct order, otherwise there may be timing issues in Linux
// that can cause tests to fail intermittently and unpredictably.
function breakConnection($conn, $conn_break)
{
    sleep(1);
    $stmt1 = $conn->query("SELECT @@SPID");
    $obj = $stmt1->fetch(PDO::FETCH_NUM);
    $spid = $obj[0];

    $stmt2 = $conn_break->query("KILL ".$spid);
    sleep(1);
}

// Remove any databases previously created by GenerateDatabase
function dropTables($tableName1, $tableName2)
{
    $conn = connect();

    $query = "IF OBJECT_ID('$tableName1', 'U') IS NOT NULL DROP TABLE $tableName1";
    $stmt = $conn->query($query);

    $query = "IF OBJECT_ID('$tableName2', 'U') IS NOT NULL DROP TABLE $tableName2";
    $stmt = $conn->query($query);
}

dropTables($tableName1, $tableName2);
generateTables($tableName1, $tableName2);
--TEST--
Error checking for multiple active result sets (MARS) disabled
--SKIPIF--
--FILE--
<?php

require_once("MsCommon.inc");

// connect
$conn = connect(array('MultipleActiveResultSets' => false));
if (!$conn) {
    fatalError("Connection could not be established.\n");
}

// Query
$stmt1 = sqlsrv_query($conn, "SELECT 'ONE'");
if (!$stmt1) {
    print_r(sqlsrv_errors());
}
sqlsrv_fetch($stmt1);

// Query. Returns if multiple result sets are disabled
$stmt2 = sqlsrv_query($conn, "SELECT 'TWO'");
if ($stmt2) {
    echo "Expect case 2 to fail\n";
} else {
    print_r(sqlsrv_errors());
}

// Free statement and connection resources
sqlsrv_free_stmt($stmt1);
sqlsrv_close($conn);

print "Done"
?>

--EXPECTREGEX--
Array
\(
    \[0\] => Array
        \(
            \[0\] => IMSSP
            \[SQLSTATE\] => IMSSP
            \[1\] => -44
            \[code\] => -44
            \[2\] => The connection cannot process this operation because there is a statement with pending results\.  To make the connection available for other queries, either fetch all results or cancel or free the statement\.  For more information, see the product documentation about the MultipleActiveResultSets connection option\.
            \[message\] => The connection cannot process this operation because there is a statement with pending results\.  To make the connection available for other queries, either fetch all results or cancel or free the statement\.  For more information, see the product documentation about the MultipleActiveResultSets connection option\.
        \)

    \[1\] => Array
        \(
            \[0\] => HY000
            \[SQLSTATE\] => HY000
            \[1\] => 0
            \[code\] => 0
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Connection is busy with results for another command
            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Connection is busy with results for another command
        \)

\)
Done

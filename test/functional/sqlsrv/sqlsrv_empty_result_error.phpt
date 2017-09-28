--TEST--
Test that calling sqlsrv_next_result() on a null result set produces the correct error message. Fix for Github 507.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

$conn = sqlsrv_connect($server, array("Database"=>$databaseName, "uid"=>$uid, "pwd"=>$pwd));

$stmt = sqlsrv_query($conn, "IF OBJECT_ID('TestEmptySetTable', 'U') IS NOT NULL DROP TABLE TestEmptySetTable");
$stmt = sqlsrv_query($conn, "CREATE TABLE TestEmptySetTable ([c1] nvarchar(10),[c2] nvarchar(10))");
$stmt = sqlsrv_query($conn, "INSERT INTO TestEmptySetTable (c1, c2) VALUES ('a', 'b')");
$stmt = sqlsrv_query($conn, "IF OBJECT_ID('TestEmptySetProc', 'P') IS NOT NULL DROP PROCEDURE TestEmptySetProc");
$stmt = sqlsrv_query($conn, "CREATE PROCEDURE TestEmptySetProc @a nvarchar(10), @b nvarchar(10)
                             AS SET NOCOUNT ON
                             BEGIN
                                 IF @b='b'
                                 BEGIN
                                     SELECT 'a' as testValue
                                 END
                                 ELSE
                                 BEGIN
                                     UPDATE TestEmptySetTable SET c2 = 'c' WHERE c1 = @a
                                 END
                             END");

// errors out when reaching the second nextRowset() call
// returned error indicates there are no more results
echo "Return a nonempty result set:\n";

$stmt = sqlsrv_query($conn,"TestEmptySetProc @a='a', @b='b'");
$result = sqlsrv_fetch_array($stmt);
print_r($result);
sqlsrv_next_result($stmt);
$result = sqlsrv_fetch_array($stmt);
print_r($result);
sqlsrv_next_result($stmt);

print_r(sqlsrv_errors());

// errors out indicating the result set contains no fields
echo "Return an empty result set, call nextRowset on it before fetching anything:\n";

$stmt = sqlsrv_query($conn, "TestEmptySetProc @a='a', @b='c'");
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

// errors out indicating the result set contains no fields
echo "Return an empty result set, call fetch on it:\n";

$stmt = sqlsrv_query($conn, "TestEmptySetProc @a='a', @b='c'");
$result = sqlsrv_fetch_array($stmt);
print_r($result);
print_r(sqlsrv_errors());

$stmt = sqlsrv_query($conn, "DROP TABLE TestEmptySetTable");
$stmt = sqlsrv_query($conn, "DROP PROCEDURE TestEmptySetProc");
sqlsrv_close($conn);
?>
--EXPECT--
Return a nonempty result set:
Array
(
    [0] => a
    [testValue] => a
)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -26
            [code] => -26
            [2] => There are no more results returned by the query.
            [message] => There are no more results returned by the query.
        )

    [1] => Array
        (
            [0] => HY010
            [SQLSTATE] => HY010
            [1] => 0
            [code] => 0
            [2] => [Microsoft][ODBC Driver Manager] Function sequence error
            [message] => [Microsoft][ODBC Driver Manager] Function sequence error
        )

)
Return an empty result set, call nextRowset on it before fetching anything:
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -28
            [code] => -28
            [2] => The active result for the query contains no fields.
            [message] => The active result for the query contains no fields.
        )

)
Return an empty result set, call fetch on it:
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -28
            [code] => -28
            [2] => The active result for the query contains no fields.
            [message] => The active result for the query contains no fields.
        )

)

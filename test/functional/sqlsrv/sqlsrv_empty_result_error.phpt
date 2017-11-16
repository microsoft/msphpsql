--TEST--
Error messages from nonempty, empty, and null result sets
--DESCRIPTION--
Test that calling sqlsrv_next_result() and fetching on nonempty, empty, and null result sets produces the correct results or error messages.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon.inc");

$conn = sqlsrv_connect($server, array("Database"=>$databaseName, "uid"=>$uid, "pwd"=>$pwd));

DropTable($conn, 'TestEmptySetTable');
$stmt = sqlsrv_query($conn, "CREATE TABLE TestEmptySetTable ([c1] nvarchar(10),[c2] nvarchar(10))");
$stmt = sqlsrv_query($conn, "INSERT INTO TestEmptySetTable (c1, c2) VALUES ('a', 'b')");

// Create a procedure that can return a nonempty result set, an empty result set, or a null result
DropProc($conn, 'TestEmptySetProc');
$stmt = sqlsrv_query($conn, "CREATE PROCEDURE TestEmptySetProc @a nvarchar(10), @b nvarchar(10)
                             AS SET NOCOUNT ON
                             BEGIN
                                 IF @b='b'
                                 BEGIN
                                     SELECT 'a' as testValue
                                 END
                                 ELSE IF @b='w'
                                 BEGIN
                                     SELECT * FROM TestEmptySetTable WHERE c1 = @b
                                 END
                                 ELSE
                                 BEGIN
                                     UPDATE TestEmptySetTable SET c2 = 'c' WHERE c1 = @a
                                 END
                             END");

// Call fetch on a nonempty result set
echo "Nonempty result set, call fetch first: ###############################\n";

$stmt = sqlsrv_query($conn,"TestEmptySetProc @a='a', @b='b'");

echo "First fetch...\n";
$result = sqlsrv_fetch_array($stmt);//$result=sqlsrv_get_field($stmt,0);
print_r($result);
print_r(sqlsrv_errors());

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

echo "Fetch...\n";
$result = sqlsrv_fetch_array($stmt);
print_r($result);
print_r(sqlsrv_errors());

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

// Call next_result on a nonempty result set
echo "Nonempty result set, call next_result first: #########################\n";

$stmt = sqlsrv_query($conn,"TestEmptySetProc @a='a', @b='b'");

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

echo "Fetch...\n";
$result = sqlsrv_fetch_array($stmt);
print_r($result);
print_r(sqlsrv_errors());

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

// Call next_result twice in succession on a nonempty result set
echo "Nonempty result set, call next_result twice: #########################\n";

$stmt = sqlsrv_query($conn, "TestEmptySetProc @a='a', @b='b'");

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

// Call fetch on an empty result set
echo "Empty result set, call fetch first: ##################################\n";

$stmt = sqlsrv_query($conn,"TestEmptySetProc @a='a', @b='w'");

echo "First fetch...\n";
$result = sqlsrv_fetch_array($stmt);
print_r($result);
print_r(sqlsrv_errors());

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

echo "Fetch...\n";
$result = sqlsrv_fetch_array($stmt);
print_r($result);
print_r(sqlsrv_errors());

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

// Call next_result on an empty result set
echo "Empty result set, call next_result first: ############################\n";

$stmt = sqlsrv_query($conn,"TestEmptySetProc @a='a', @b='w'");

echo "First go to next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

echo "Fetch...\n";
$result = sqlsrv_fetch_array($stmt);
print_r($result);
print_r(sqlsrv_errors());

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

// Call next_result twice in succession on an empty result set
echo "Empty result set, call next_result twice: ############################\n";

$stmt = sqlsrv_query($conn, "TestEmptySetProc @a='a', @b='w'");

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

// Call fetch on a null result set
echo "Null result set, call fetch first: ###################################\n";

$stmt = sqlsrv_query($conn, "TestEmptySetProc @a='a', @b='c'");

echo "Fetch...\n";
$result = sqlsrv_fetch_array($stmt);
print_r($result);
print_r(sqlsrv_errors());

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

// Call next_result on a null result set
echo "Null result set, call next result first: #############################\n";

$stmt = sqlsrv_query($conn, "TestEmptySetProc @a='a', @b='c'");

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

echo "Fetch...\n";
$result = sqlsrv_fetch_array($stmt);
print_r(sqlsrv_errors());

// Call next_result twice in succession on a null result set
echo "Null result set, call next result twice: #############################\n";

$stmt = sqlsrv_query($conn, "TestEmptySetProc @a='a', @b='c'");

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

echo "Next result...\n";
sqlsrv_next_result($stmt);
print_r(sqlsrv_errors());

$stmt = sqlsrv_query($conn, "DROP TABLE TestEmptySetTable");
$stmt = sqlsrv_query($conn, "DROP PROCEDURE TestEmptySetProc");
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
--EXPECTF--
Nonempty result set, call fetch first: ###############################
First fetch...
Array
(
    [0] => a
    [testValue] => a
)
Next result...
Fetch...
Array
(
    [0] => Array
        (
            [0] => HY010
            [SQLSTATE] => HY010
            [1] => 0
            [code] => 0
            [2] => [%rMicrosoft|unixODBC%r][%rODBC D|D%rriver Manager]Function sequence error
            [message] => [%rMicrosoft|unixODBC%r][%rODBC D|D%rriver Manager]Function sequence error
        )

)
Next result...
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
            [2] => [%rMicrosoft|unixODBC%r][%rODBC D|D%rriver Manager]Function sequence error
            [message] => [%rMicrosoft|unixODBC%r][%rODBC D|D%rriver Manager]Function sequence error
        )

)
Nonempty result set, call next_result first: #########################
Next result...
Fetch...
Array
(
    [0] => Array
        (
            [0] => HY010
            [SQLSTATE] => HY010
            [1] => 0
            [code] => 0
            [2] => [%rMicrosoft|unixODBC%r][%rODBC D|D%rriver Manager]Function sequence error
            [message] => [%rMicrosoft|unixODBC%r][%rODBC D|D%rriver Manager]Function sequence error
        )

)
Next result...
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
            [2] => [%rMicrosoft|unixODBC%r][%rODBC D|D%rriver Manager]Function sequence error
            [message] => [%rMicrosoft|unixODBC%r][%rODBC D|D%rriver Manager]Function sequence error
        )

)
Nonempty result set, call next_result twice: #########################
Next result...
Next result...
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

)
Empty result set, call fetch first: ##################################
First fetch...
Next result...
Fetch...
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -22
            [code] => -22
            [2] => There are no more rows in the active result set.  Since this result set is not scrollable, no more data may be retrieved.
            [message] => There are no more rows in the active result set.  Since this result set is not scrollable, no more data may be retrieved.
        )

)
Next result...
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

)
Empty result set, call next_result first: ############################
First go to next result...
Fetch...
Array
(
    [0] => Array
        (
            [0] => HY010
            [SQLSTATE] => HY010
            [1] => 0
            [code] => 0
            [2] => [%rMicrosoft|unixODBC%r][%rODBC D|D%rriver Manager]Function sequence error
            [message] => [%rMicrosoft|unixODBC%r][%rODBC D|D%rriver Manager]Function sequence error
        )

)
Next result...
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
            [2] => [%rMicrosoft|unixODBC%r][%rODBC D|D%rriver Manager]Function sequence error
            [message] => [%rMicrosoft|unixODBC%r][%rODBC D|D%rriver Manager]Function sequence error
        )

)
Empty result set, call next_result twice: ############################
Next result...
Next result...
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

)
Null result set, call fetch first: ###################################
Fetch...
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
Next result...
Null result set, call next result first: #############################
Next result...
Fetch...
Array
(
    [0] => Array
        (
            [0] => HY010
            [SQLSTATE] => HY010
            [1] => 0
            [code] => 0
            [2] => [%rMicrosoft|unixODBC%r][%rODBC D|D%rriver Manager]Function sequence error
            [message] => [%rMicrosoft|unixODBC%r][%rODBC D|D%rriver Manager]Function sequence error
        )

)
Null result set, call next result twice: #############################
Next result...
Next result...
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

)

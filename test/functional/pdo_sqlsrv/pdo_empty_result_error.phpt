--TEST--
Error messages from nonempty, empty, and null result sets
--DESCRIPTION--
Test that calling nextRowset() and fetching on nonempty, empty, and null result sets produces the correct results or error messages.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon.inc");

// These are the error messages we expect at various points below
$errorNoMoreResults = "There are no more results returned by the query.";
$errorNoFields      = "The active result for the query contains no fields.";

// This function compares the expected error message and the error returned by errorInfo().
function CheckError($stmt, $expectedError=NULL)
{
    $actualError = $stmt->errorInfo();

    if ($actualError[2] != $expectedError) {
        echo "Wrong error message:\n";
        print_r($actualError);
    }
}
 
function Fetch($stmt, $error=NULL)
{
    echo "Fetch...\n";
    $result = $stmt->fetchObject();
    print_r($result);
    CheckError($stmt, $error);
}

function NextResult($stmt, $error=NULL)
{
    echo "Next result...\n";
    $stmt->nextRowset();
    CheckError($stmt, $error);
}

$conn = new PDO( "sqlsrv:Server = $server; Database = $databaseName; ", $uid, $pwd );
$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );

DropTable($conn, 'TestEmptySetTable');
$stmt = $conn->query("CREATE TABLE TestEmptySetTable ([c1] nvarchar(10),[c2] nvarchar(10))");
$stmt = $conn->query("INSERT INTO TestEmptySetTable (c1, c2) VALUES ('a', 'b')");

// Create a procedure that can return a nonempty result set, an empty result set, or a null result
DropProc($conn, 'TestEmptySetProc');
$stmt = $conn->query("CREATE PROCEDURE TestEmptySetProc @a nvarchar(10), @b nvarchar(10)
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

$stmt = $conn->query("TestEmptySetProc @a='a', @b='b'");
Fetch($stmt);
NextResult($stmt);
Fetch($stmt);
NextResult($stmt, $errorNoMoreResults);

// Call nextRowset on a nonempty result set
echo "Nonempty result set, call nextRowset first: #########################\n";

$stmt = $conn->query("TestEmptySetProc @a='a', @b='b'");
NextResult($stmt);
Fetch($stmt);
NextResult($stmt, $errorNoMoreResults);

// Call nextRowset twice in succession on a nonempty result set
echo "Nonempty result set, call nextRowset twice: #########################\n";

$stmt = $conn->query("TestEmptySetProc @a='a', @b='b'");
NextResult($stmt);
NextResult($stmt, $errorNoMoreResults);

// Call fetch on an empty result set
echo "Empty result set, call fetch first: ##################################\n";

$stmt = $conn->query("TestEmptySetProc @a='a', @b='w'");
Fetch($stmt);
NextResult($stmt);
Fetch($stmt);
NextResult($stmt, $errorNoMoreResults);

// Call nextRowset on an empty result set
echo "Empty result set, call nextRowset first: ############################\n";

$stmt = $conn->query("TestEmptySetProc @a='a', @b='w'");
NextResult($stmt);
Fetch($stmt);
NextResult($stmt, $errorNoMoreResults);

// Call nextRowset twice in succession on an empty result set
echo "Empty result set, call nextRowset twice: ############################\n";

$stmt = $conn->query("TestEmptySetProc @a='a', @b='w'");
NextResult($stmt);
NextResult($stmt, $errorNoMoreResults);

// Call fetch on a null result set
echo "Null result set, call fetch first: ###################################\n";

$stmt = $conn->query("TestEmptySetProc @a='a', @b='c'");
Fetch($stmt, $errorNoFields);
NextResult($stmt);

// Call nextRowset on a null result set
echo "Null result set, call next result first: #############################\n";

$stmt = $conn->query("TestEmptySetProc @a='a', @b='c'");
NextResult($stmt);
Fetch($stmt);

// Call nextRowset twice in succession on a null result set
echo "Null result set, call next result twice: #############################\n";

$stmt = $conn->query("TestEmptySetProc @a='a', @b='c'");
NextResult($stmt);
NextResult($stmt, $errorNoMoreResults);

$stmt = $conn->query("DROP TABLE TestEmptySetTable");
$stmt = $conn->query("DROP PROCEDURE TestEmptySetProc");
$stmt = null;
$conn = null;
?>
--EXPECT--
Nonempty result set, call fetch first: ###############################
Fetch...
stdClass Object
(
    [testValue] => a
)
Next result...
Fetch...
Next result...
Nonempty result set, call nextRowset first: #########################
Next result...
Fetch...
Next result...
Nonempty result set, call nextRowset twice: #########################
Next result...
Next result...
Empty result set, call fetch first: ##################################
Fetch...
Next result...
Fetch...
Next result...
Empty result set, call nextRowset first: ############################
Next result...
Fetch...
Next result...
Empty result set, call nextRowset twice: ############################
Next result...
Next result...
Null result set, call fetch first: ###################################
Fetch...
Next result...
Null result set, call next result first: #############################
Next result...
Fetch...
Null result set, call next result twice: #############################
Next result...
Next result...
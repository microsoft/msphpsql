--TEST--
Error messages from null result sets
--DESCRIPTION--
Test that calling nextRowset() on an empty result set produces the correct error message. Fix for Github 507.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon.inc");

$conn = new PDO( "sqlsrv:Server = $server; Database = $databaseName; ", $uid, $pwd );
$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

DropTable($conn, 'TestEmptySetTable');
$stmt = $conn->query("CREATE TABLE TestEmptySetTable ([c1] nvarchar(10),[c2] nvarchar(10))");
$stmt = $conn->query("INSERT INTO TestEmptySetTable (c1, c2) VALUES ('a', 'b')");

// Create a procedure that can return a result set or can return nothing
DropProc($conn, 'TestEmptySetProc');
$stmt = $conn->query("CREATE PROCEDURE TestEmptySetProc @a nvarchar(10), @b nvarchar(10)
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
try
{
    $stmt = $conn->query("TestEmptySetProc @a='a', @b='b'");
    $result = $stmt->fetchAll();
    print_r($result);
    $stmt->nextRowset();
    $result = $stmt->fetchAll();
    print_r($result);
    $stmt->nextRowset();
}
catch(Exception $e)
{
    echo $e->getMessage()."\n";
}

// errors out indicating the result set contains no fields
echo "Return an empty result set, call nextRowset on it before fetching anything:\n";
try
{
    $stmt = $conn->query("TestEmptySetProc @a='a', @b='c'");
    $stmt->nextRowset();
}
catch(Exception $e)
{
    echo $e->getMessage()."\n";
}

// errors out indicating the result set contains no fields
echo "Return an empty result set, call fetch on it:\n";
try
{
    $stmt = $conn->query("TestEmptySetProc @a='a', @b='c'");
    $result = $stmt->fetchAll();
    print_r($result);
}
catch(Exception $e)
{
    echo $e->getMessage()."\n";
}

$stmt = $conn->query("DROP TABLE TestEmptySetTable");
$stmt = $conn->query("DROP PROCEDURE TestEmptySetProc");

$conn = null;
?>
--EXPECT--
Return a nonempty result set:
Array
(
    [0] => Array
        (
            [testValue] => a
            [0] => a
        )

)
Array
(
)
SQLSTATE[IMSSP]: There are no more results returned by the query.
Return an empty result set, call nextRowset on it before fetching anything:
SQLSTATE[IMSSP]: The active result for the query contains no fields.
Return an empty result set, call fetch on it:
SQLSTATE[IMSSP]: The active result for the query contains no fields.

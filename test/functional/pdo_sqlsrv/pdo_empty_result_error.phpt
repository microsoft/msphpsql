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

$conn = new PDO( "sqlsrv:Server = $server; Database = $databaseName; ", $uid, $pwd );
$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

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

try
{
    $stmt = $conn->query("TestEmptySetProc @a='a', @b='b'");

    echo "First fetch...\n";
    $result = $stmt->fetchObject();
    print_r($result);

    echo "Next result...\n";
    $stmt->nextRowset();

    echo "Fetch...\n";
    $result = $stmt->fetch();
    print_r($result);

    echo "Next result...\n";
    $stmt->nextRowset();
}
catch(Exception $e)
{
    echo $e->getMessage()."\n";
}

// Call next_result on a nonempty result set
echo "Nonempty result set, call next_result first: #########################\n";

try
{
    $stmt = $conn->query("TestEmptySetProc @a='a', @b='b'");

    echo "Next result...\n";
    $stmt->nextRowset();

    echo "Fetch...\n";
    $result = $stmt->fetchObject();
    print_r($result);

    echo "Next result...\n";
    $stmt->nextRowset();
}
catch(Exception $e)
{
    echo $e->getMessage()."\n";
}

// Call next_result twice in succession on a nonempty result set
echo "Nonempty result set, call next_result twice: #########################\n";
try
{
    $stmt = $conn->query("TestEmptySetProc @a='a', @b='b'");

    echo "Next result...\n";
    $stmt->nextRowset();

    echo "Next result...\n";
    $stmt->nextRowset();
}
catch(Exception $e)
{
    echo $e->getMessage()."\n";
}

// Call fetch on an empty result set
echo "Empty result set, call fetch first: ##################################\n";

try
{
    $stmt = $conn->query("TestEmptySetProc @a='a', @b='w'");

    echo "First fetch...\n";
    $result = $stmt->fetchObject();
    print_r($result);

    echo "Next result...\n";
    $stmt->nextRowset();

    echo "Fetch...\n";
    $result = $stmt->fetchObject();
    print_r($result);

    echo "Next result...\n";
    $stmt->nextRowset();
}
catch(Exception $e)
{
    echo $e->getMessage()."\n";
}

// Call next_result on an empty result set
echo "Empty result set, call next_result first: ############################\n";

try
{
    $stmt = $conn->query("TestEmptySetProc @a='a', @b='w'");

    echo "First go to next result...\n";
    $stmt->nextRowset();

    echo "Fetch...\n";
    $result = $stmt->fetchObject();
    print_r($result);

    echo "Next result...\n";
    $stmt->nextRowset();
}
catch(Exception $e)
{
    echo $e->getMessage()."\n";
}

// Call next_result twice in succession on an empty result set
echo "Empty result set, call next_result twice: ############################\n";

try
{
    $stmt = $conn->query("TestEmptySetProc @a='a', @b='w'");

    echo "Next result...\n";
    $stmt->nextRowset();

    echo "Next result...\n";
    $stmt->nextRowset();
}
catch(Exception $e)
{
    echo $e->getMessage()."\n";
}

// Call fetch on a null result set
echo "Null result set, call fetch first: ###################################\n";

try
{
    $stmt = $conn->query("TestEmptySetProc @a='a', @b='c'");

    echo "Fetch...\n";
    $result = $stmt->fetchObject();
    print_r($result);

    echo "Next result...\n";
    $stmt->nextRowset();
}
catch(Exception $e)
{
    echo $e->getMessage()."\n";
}

// Call next_result on a null result set
echo "Null result set, call next result first: #############################\n";

try
{
    $stmt = $conn->query("TestEmptySetProc @a='a', @b='c'");

    echo "Next result...\n";
    $stmt->nextRowset();

    echo "Fetch...\n";
    $result = $stmt->fetchObject();
    print_r($result);
}
catch(Exception $e)
{
    echo $e->getMessage()."\n";
}

// Call next_result twice in succession on a null result set
echo "Null result set, call next result twice: #############################\n";

try
{
    $stmt = $conn->query("TestEmptySetProc @a='a', @b='c'");

    echo "Next result...\n";
    $stmt->nextRowset();


    echo "Next result...\n";
    $stmt->nextRowset();

}
catch(Exception $e)
{
    echo $e->getMessage()."\n";
}

$stmt = $conn->query("DROP TABLE TestEmptySetTable");
$stmt = $conn->query("DROP PROCEDURE TestEmptySetProc");
$stmt = null;
$conn = null;
?>
--EXPECT--
Nonempty result set, call fetch first: ###############################
First fetch...
stdClass Object
(
    [testValue] => a
)
Next result...
Fetch...
Next result...
SQLSTATE[IMSSP]: There are no more results returned by the query.
Nonempty result set, call next_result first: #########################
Next result...
Fetch...
Next result...
SQLSTATE[IMSSP]: There are no more results returned by the query.
Nonempty result set, call next_result twice: #########################
Next result...
Next result...
SQLSTATE[IMSSP]: There are no more results returned by the query.
Empty result set, call fetch first: ##################################
First fetch...
Next result...
Fetch...
Next result...
SQLSTATE[IMSSP]: There are no more results returned by the query.
Empty result set, call next_result first: ############################
First go to next result...
Fetch...
Next result...
SQLSTATE[IMSSP]: There are no more results returned by the query.
Empty result set, call next_result twice: ############################
Next result...
Next result...
SQLSTATE[IMSSP]: There are no more results returned by the query.
Null result set, call fetch first: ###################################
Fetch...
SQLSTATE[IMSSP]: The active result for the query contains no fields.
Null result set, call next result first: #############################
Next result...
Fetch...
Null result set, call next result twice: #############################
Next result...
Next result...
SQLSTATE[IMSSP]: There are no more results returned by the query.

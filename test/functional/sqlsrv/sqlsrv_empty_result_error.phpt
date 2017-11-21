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

// These are the error messages we expect at various points below
$errorNoMoreResults = "There are no more results returned by the query.";
$errorNoMoreRows    = "There are no more rows in the active result set.  Since this result set is not scrollable, no more data may be retrieved.";
$errorNoFields      = "The active result for the query contains no fields.";

// Variable function gets an error message that depends on the OS
function getFuncSeqError()
{
    if ( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) {
        return "[Microsoft][ODBC Driver Manager] Function sequence error";
    } else {
        return "[unixODBC][Driver Manager]Function sequence error";
    }
}
        
$errorFuncSeq = 'getFuncSeqError';

// This function takes an array of expected error messages and compares the
// contents to the actual errors
function CheckError($expectedErrors)
{
    $actualErrors = sqlsrv_errors();
    $sizeActualErrors = 0;
    if (!is_null($actualErrors)) {
        $sizeActualErrors = sizeof($actualErrors);
    }

    if (($sizeActualErrors) != sizeof($expectedErrors)) {
        echo "Wrong size for error array\n";
        print_r($actualErrors);
        return;
    }
    
    $i = 0;
    
    foreach ($expectedErrors as $e) {
        if ($actualErrors[$i]['message'] != $e) {
            echo "Wrong error message:\n";
            print_r($actualErrors[$i]);
        }
        $i++;
    }
}
 
function Fetch($stmt, $errors)
{
    echo "Fetch...\n";
    $result = sqlsrv_fetch_array($stmt);
    print_r($result);
    CheckError($errors);
}

function NextResult($stmt, $errors)
{
    echo "Next result...\n";
    sqlsrv_next_result($stmt);
    CheckError($errors);
}

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
Fetch($stmt, []);
NextResult($stmt, []);
Fetch($stmt, [$errorFuncSeq()]);
NextResult($stmt, [$errorNoMoreResults, $errorFuncSeq()]);

// Call next_result on a nonempty result set
echo "Nonempty result set, call next_result first: #########################\n";

$stmt = sqlsrv_query($conn,"TestEmptySetProc @a='a', @b='b'");
NextResult($stmt, []);
Fetch($stmt, [$errorFuncSeq()]);
NextResult($stmt, [$errorNoMoreResults, $errorFuncSeq()]);

// Call next_result twice in succession on a nonempty result set
echo "Nonempty result set, call next_result twice: #########################\n";

$stmt = sqlsrv_query($conn, "TestEmptySetProc @a='a', @b='b'");
NextResult($stmt, []);
NextResult($stmt, [$errorNoMoreResults]);

// Call fetch on an empty result set
echo "Empty result set, call fetch first: ##################################\n";

$stmt = sqlsrv_query($conn,"TestEmptySetProc @a='a', @b='w'");
Fetch($stmt, []);
NextResult($stmt, []);
Fetch($stmt, [$errorNoMoreRows]);
NextResult($stmt, [$errorNoMoreResults]);

// Call next_result on an empty result set
echo "Empty result set, call next_result first: ############################\n";

$stmt = sqlsrv_query($conn,"TestEmptySetProc @a='a', @b='w'");
NextResult($stmt, []);
Fetch($stmt, [$errorFuncSeq()]);
NextResult($stmt, [$errorNoMoreResults, $errorFuncSeq()]);

// Call next_result twice in succession on an empty result set
echo "Empty result set, call next_result twice: ############################\n";

$stmt = sqlsrv_query($conn, "TestEmptySetProc @a='a', @b='w'");
NextResult($stmt, []);
NextResult($stmt, [$errorNoMoreResults]);

// Call fetch on a null result set
echo "Null result set, call fetch first: ###################################\n";

$stmt = sqlsrv_query($conn, "TestEmptySetProc @a='a', @b='c'");
Fetch($stmt, [$errorNoFields]);
NextResult($stmt, []);

// Call next_result on a null result set
echo "Null result set, call next result first: #############################\n";

$stmt = sqlsrv_query($conn, "TestEmptySetProc @a='a', @b='c'");
NextResult($stmt, []);
Fetch($stmt, [$errorFuncSeq()]);

// Call next_result twice in succession on a null result set
echo "Null result set, call next result twice: #############################\n";

$stmt = sqlsrv_query($conn, "TestEmptySetProc @a='a', @b='c'");
NextResult($stmt, []);
NextResult($stmt, [$errorNoMoreResults]);

$stmt = sqlsrv_query($conn, "DROP TABLE TestEmptySetTable");
$stmt = sqlsrv_query($conn, "DROP PROCEDURE TestEmptySetProc");
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
--EXPECT--
Nonempty result set, call fetch first: ###############################
Fetch...
Array
(
    [0] => a
    [testValue] => a
)
Next result...
Fetch...
Next result...
Nonempty result set, call next_result first: #########################
Next result...
Fetch...
Next result...
Nonempty result set, call next_result twice: #########################
Next result...
Next result...
Empty result set, call fetch first: ##################################
Fetch...
Next result...
Fetch...
Next result...
Empty result set, call next_result first: ############################
Next result...
Fetch...
Next result...
Empty result set, call next_result twice: ############################
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

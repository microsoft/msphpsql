--TEST--
Test various scenarios which all return the same error about statement not executed
--DESCRIPTION--
This is similar to sqlsrv test_warning_errors2.phpt with checking for error conditions concerning fetching and metadata.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

$execError = '*The statement must be executed before results can be retrieved.';
$noMoreResult = '*There are no more results returned by the query.';

function getNextResult($stmt, $error = null)
{
    try {
        $result = $stmt->nextRowset();
        if (!is_null($error)) {
            echo "getNextResult: expect this to fail with an error from the driver\n";
        } elseif ($result !== false) {
            echo "getNextResult: expect this to simply return false\n";
        }
    } catch (PDOException $e) {
        if ($e->getCode() !== "IMSSP" || !fnmatch($error, $e->getMessage())) {
            var_dump($e->getMessage());
        }
    }
}

try {
    $conn = connect();
    
    $tsql = 'SELECT name FROM sys.objects';
    $stmt = $conn->prepare($tsql);
    
    $colCount = $stmt->columnCount();
    if ($colCount != 0) {
        echo "Before execute(), result set should only have 0 columns\n";
    }
    $metadata = $stmt->getColumnMeta(0);
    if ($metadata !== false) {
        echo "Before execute(), result set is empty so getColumnMeta should have failed\n";
    }

    // When fetching, PDO checks if statement is executed before passing the 
    // control to the driver, so it simply fails without error message
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result !== false) {
        echo "Before execute(), fetch should have failed\n";
    }
    
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    var_dump($result);
    
    $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    var_dump($result);

    getNextResult($stmt, $execError);
    
    // Now, call execute()
    $stmt->execute();
    
    $colCount = $stmt->columnCount();
    if ($colCount != 1) {
        echo "Expected only one column\n";
    }

    $metadata = $stmt->getColumnMeta(0);
    if ($metadata['native_type'] !== 'string') {
        echo "The metadata returned is unexpected: \n";
        var_dump($metadata);
    }

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($result) && count($result) == 0) {
        echo "After execute(), fetch should have returned an array with results\n";
    }
    
    $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!is_array($result) && count($result) == 0) {
        echo "After execute(), fetchAll should have returned an array with results\n";
    }

    getNextResult($stmt);
    
    getNextResult($stmt, $noMoreResult);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result !== false) {
        // When nextRowset() fails, it resets the execute flag to false
        echo "After nextRowset failed, fetch should have failed\n";
    }

    echo "Done\n";
    
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
array(0) {
}
array(0) {
}
Done
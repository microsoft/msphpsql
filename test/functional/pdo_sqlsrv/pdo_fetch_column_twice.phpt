--TEST--
Test fetchColumn twice in a row. Intentionally trigger various error messages.
--DESCRIPTION--
This is similar to sqlsrv_fetch_field_twice_data_types.phpt.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function fetchBeforeExecute($conn, $tableName, $inputs)
{
    try {
        $tsql = "SELECT * FROM $tableName";
        $stmt = $conn->prepare($tsql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            echo "fetchBeforeExecute: fetch should have failed before execute!\n";
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_NUM);

        for ($i = 0; $i < count($inputs); $i++) {
            if ($row[$i] !== $inputs[$i]) {
                echo "fetchBeforeExecute: expected $inputs[$i] but got $row[$i]\n";
            }
        }

        unset($stmt);
    } catch (PDOException $e) {
        var_dump($e->getMessage());
    }
}

function fetchColumnTwice($conn, $tableName, $col, $input)
{
    try {
        $tsql = "SELECT * FROM $tableName";
        $stmt = $conn->query($tsql);
        $result = $stmt->fetchColumn($col);
        if ($result !== $input) {
            echo "fetchColumnTwice (1): expected $input but got $result\n";
        }
        $result = $stmt->fetchColumn($col);
        if ($result !== false) {
            echo "fetchColumnTwice (2): expected the second fetchColumn to fail\n";
        }

        // Re-run the query with fetch style
        $stmt = $conn->query($tsql, PDO::FETCH_COLUMN, $col);
        $result = $stmt->fetch();
        if ($result !== $input) {
            echo "fetchColumnTwice (3): expected $input but got $result\n";
        }
        $result = $stmt->fetch();
        if ($result !== false) {
            echo "fetchColumnTwice (4): expected the second fetch to fail\n";
        }
        $result = $stmt->fetchColumn($col);
        echo "fetchColumnTwice (5): expected fetchColumn to throw an exception\n";
        unset($stmt);
    } catch (PDOException $e) {
        $error = '*There are no more rows in the active result set.  Since this result set is not scrollable, no more data may be retrieved.';

        if (!fnmatch($error, $e->getMessage())) {
            echo "Error message unexpected in fetchColumnTwice\n";
            var_dump($e->getMessage());
        }
    }
}

function fetchColumnOutOfBound1($conn, $tableName, $col)
{
    try {
        $tsql = "SELECT * FROM $tableName";
        $stmt = $conn->query($tsql);
        $result = $stmt->fetchColumn($col);
        echo "fetchColumnOutOfBound1: expected fetchColumn to throw an exception\n";
        unset($stmt);
    } catch (PDOException $e) {
        $error1 = '*General error: Invalid column index';
        $error2 = '*An invalid column number was specified.';

        // Different errors may be returned depending on running with run-tests.php or not
        if (fnmatch($error1, $e->getMessage()) || fnmatch($error2, $e->getMessage())) {
            ;
        } else {
            echo "Error message unexpected in fetchColumnOutOfBound1\n";
            var_dump($e->getMessage());
        }
    } catch (ValueError $ve) {
        $error = '*Column index must be greater than or equal to 0';
        if (!fnmatch($error, $ve->getMessage())) {
            echo "Error message unexpected in fetchColumnOutOfBound1\n";
            var_dump($ve->getMessage());
        }
    }
}

function fetchColumnOutOfBound2($conn, $tableName, $col)
{
    $error = '*Invalid column index';
    try {
        $tsql = "SELECT * FROM $tableName";
        $stmt = $conn->query($tsql, PDO::FETCH_COLUMN, $col);
        $result = $stmt->fetch();
        unset($stmt);
    } catch (Error $e) {
        if (!fnmatch($error, $e->getMessage())) {
            var_dump($e->getMessage());
        }
    } catch (ValueError $ve) {
        if (!fnmatch($error, $ve->getMessage())) {
            var_dump($ve->getMessage());
        }
    }
}

// When testing with PHP 8.0 some test cases throw ValueError instead of exceptions or warnings. 
// Thus implement a custom warning handler such that with PHP 7.x the warning would be handled 
// to throw an Error (ValueError not available).
function warningHandler($errno, $errstr) 
{ 
    throw new Error($errstr);
}

try {
    $conn = connect();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tableName = 'pdoFetchColumnTwice';
    $colMeta = array(new ColumnMeta('int', 'c1_int'),
                     new ColumnMeta('varchar(20)', 'c2_varchar'),
                     new ColumnMeta('decimal(5, 3)', 'c3_decimal'),
                     new ColumnMeta('datetime', 'c4_datetime'));
    createTable($conn, $tableName, $colMeta);

    $inputs = array('968580013', 'dummy value', '3.438', ('1756-04-16 23:27:09.130'));
    $numCols = count($inputs);

    $tsql = "INSERT INTO $tableName(c1_int, c2_varchar, c3_decimal, c4_datetime) VALUES (?,?,?,?)";
    $stmt = $conn->prepare($tsql);

    for ($i = 0; $i < $numCols; $i++) {
        $stmt->bindParam($i + 1, $inputs[$i]);
    }
    $stmt->execute();
    unset($stmt);

    fetchBeforeExecute($conn, $tableName, $inputs);
    for ($i = 0; $i < $numCols; $i++) {
        fetchColumnTwice($conn, $tableName, $i, $inputs[$i]);
    }
    
    fetchColumnOutOfBound1($conn, $tableName, -1);

    // Change to warning mode
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    set_error_handler("warningHandler", E_WARNING);
    fetchColumnOutOfBound2($conn, $tableName, $numCols + 1);
    restore_error_handler();
    
    dropTable($conn, $tableName);
    unset($conn);
    echo "Done\n";
} catch (PDOException $e) {
    var_dump($e);
}
?>
--EXPECT--
Done

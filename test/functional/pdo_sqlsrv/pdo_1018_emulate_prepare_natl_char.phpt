--TEST--
GitHub issue 1018 - Test emulate prepared statements with the extended string types 
--DESCRIPTION--
This test verifies the extended string types, PDO::ATTR_DEFAULT_STR_PARAM, PDO::PARAM_STR_NATL and 
PDO::PARAM_STR_CHAR will affect "emulate prepared" statements. If the parameter encoding is specified, 
it also matters. The N'' prefix will be used when either it is PDO::PARAM_STR_NATL or the 
parameter encoding is UTF-8.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_old_php.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

$p = 'Ĉǽŋ';
$p1 = 'C??';

function toEmulatePrepare($conn, $pdoStrParam, $value, $testCase, $utf8 = false)
{
    global $p;
    
    $sql = 'SELECT :value';
    $options = array(PDO::ATTR_EMULATE_PREPARES => true);
    $stmt = $conn->prepare($sql, $options);

    if ($utf8) {
        $stmt->bindParam(':value', $p, $pdoStrParam, 0, PDO::SQLSRV_ENCODING_UTF8);
    } else {
        $stmt->bindParam(':value', $p, $pdoStrParam);
    }
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_NUM);
    trace("$testCase: expected $value and returned $result[0]\n");
    if ($result[0] !== $value) {
        echo("$testCase: expected $value but returned:\n");
        var_dump($result);
    }
}

try {
    $conn = connect();
    
    // Test case 1: PDO::PARAM_STR_NATL
    $testCase = 'Test case 1: no default but specifies PDO::PARAM_STR_NATL';
    toEmulatePrepare($conn, PDO::PARAM_STR | PDO::PARAM_STR_NATL, $p, $testCase);
    
    // Test case 2: PDO::PARAM_STR_CHAR
    $testCase = 'Test case 2: no default but specifies PDO::PARAM_STR_CHAR';
    toEmulatePrepare($conn, PDO::PARAM_STR | PDO::PARAM_STR_CHAR, $p1, $testCase);

    // Test case 3: no extended string types
    $testCase = 'Test case 3: no default but no extended string types either';
    toEmulatePrepare($conn, PDO::PARAM_STR, $p1, $testCase);

    // Test case 4: no extended string types but specifies UTF 8 encoding
    $testCase = 'Test case 4: no default but no extended string types but with UTF-8';
    toEmulatePrepare($conn, PDO::PARAM_STR, $p, $testCase, true);
    
    ////////////////////////////////////////////////////////////////////////
    // NEXT tests: set the default string type: PDO::PARAM_STR_CHAR first
    $conn->setAttribute(PDO::ATTR_DEFAULT_STR_PARAM, PDO::PARAM_STR_CHAR);
    
    // Test case 5: overrides the default PDO::PARAM_STR_CHAR
    $testCase = 'Test case 5: overrides the default PDO::PARAM_STR_CHAR';
    toEmulatePrepare($conn, PDO::PARAM_STR | PDO::PARAM_STR_NATL, $p, $testCase);
    
    // Test case 6: specifies PDO::PARAM_STR_CHAR directly
    $testCase = 'Test case 6: specifies PDO::PARAM_STR_CHAR, same as the default';
    toEmulatePrepare($conn, PDO::PARAM_STR | PDO::PARAM_STR_CHAR, $p1, $testCase);

    // Test case 7: uses the default PDO::PARAM_STR_CHAR without specifying
    $testCase = 'Test case 7: no extended string types (uses the default)';
    toEmulatePrepare($conn, PDO::PARAM_STR, $p1, $testCase);

    // Test case 8: uses the default PDO::PARAM_STR_CHAR without specifying but with UTF 8 encoding
    $testCase = 'Test case 8: no extended string types (uses the default) but with UTF-8 ';
    toEmulatePrepare($conn, PDO::PARAM_STR, $p, $testCase, true);

    ////////////////////////////////////////////////////////////////////////
    // NEXT tests: set the default string type: PDO::PARAM_STR_NATL
    $conn->setAttribute(PDO::ATTR_DEFAULT_STR_PARAM, PDO::PARAM_STR_NATL);
    
    // Test case 9: overrides the default PDO::PARAM_STR_NATL
    $testCase = 'Test case 9: overrides the default PDO::PARAM_STR_NATL';
    toEmulatePrepare($conn, PDO::PARAM_STR | PDO::PARAM_STR_CHAR, $p1, $testCase);
    
    // Test case 10: specifies PDO::PARAM_STR_NATL directly
    $testCase = 'Test case 10: specifies PDO::PARAM_STR_NATL, same as the default';
    toEmulatePrepare($conn, PDO::PARAM_STR | PDO::PARAM_STR_NATL, $p, $testCase);

    // Test case 11: uses the default PDO::PARAM_STR_NATL without specifying
    $testCase = 'Test case 11: no extended string types (uses the default)';
    toEmulatePrepare($conn, PDO::PARAM_STR, $p, $testCase);
    
    // Test case 12: uses the default PDO::PARAM_STR_NATL without specifying but with UTF 8 encoding
    $testCase = 'Test case 12: no extended string types (uses the default) but with UTF-8';
    toEmulatePrepare($conn, PDO::PARAM_STR, $p, $testCase, true);

    echo "Done\n";
} catch (PdoException $e) {
    if (isAEConnected()) {
        // The Always Encrypted feature does not support emulate prepare for binding parameters
        $expected = '*Parameterized statement with attribute PDO::ATTR_EMULATE_PREPARES is not supported in a Column Encryption enabled Connection.';
        if (!fnmatch($expected, $e->getMessage())) {
            echo "Unexpected exception caught when connecting with Column Encryption enabled:\n";
            echo $e->getMessage() . PHP_EOL;
        } else {
            echo "Done\n";
        }
    } else {
        echo $e->getMessage() . PHP_EOL;
    }
}

?>
--EXPECT--
Done

--TEST--
Test passwords with non alphanumeric characters and braces
--DESCRIPTION--
The first two cases should fail with a message about login failures. Only the last case fails because the right curly brace was not escaped with another right brace.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once 'MsSetup.inc';
require_once 'MsCommon_mid-refactor.inc';

function generateRandomPassword($insertBraces = true, $escapeBraces = true)
{
    $random = '! ;.4{X#r?o,*';
    $brace = '}';
    
    if (!$insertBraces) {
        // simply return the string with non alphanumeric characters
        return $random;
    } else {
        // randomly insert one or more braces into $random
        $len = strlen($random);
        $pos = rand(0, $len);
        
        $result = substr($random, 0, $pos);
        $result .= $brace;
        if ($escapeBraces) {
            $result .= $brace;
        }
        
        $result .= substr($random, $pos);
        return $result;
    }
}

try {
    $randomPwd = generateRandomPassword(false);
    trace($randomPwd . PHP_EOL);
    $conn = new PDO("sqlsrv:Server=$server;", $uid, $randomPwd);
    
    echo "Incorrect password '$randomPwd' without right braces should have failed!" . PHP_EOL;
} catch (PDOException $e) {
    $error = '*Login failed for user*';
    if (!fnmatch($error, $e->getMessage())) {
        echo "Expected $error but got:\n";
        var_dump($e->getMessage());
    }
}

try {
    $randomPwd = generateRandomPassword();
    trace($randomPwd . PHP_EOL);
    $conn = new PDO("sqlsrv:Server=$server;", $uid, $randomPwd);
    
    echo "Incorrect password '$randomPwd' with right braces should have failed!" . PHP_EOL;
} catch (PDOException $e) {
    $error = '*Login failed for user*';
    if (!fnmatch($error, $e->getMessage())) {
        echo "Expected $error but got:\n";
        var_dump($e->getMessage());
    }
}

try {
    $randomPwd = generateRandomPassword(true, false);
    trace($randomPwd . PHP_EOL);
    $conn = new PDO("sqlsrv:Server=$server;", $uid, $randomPwd);

    echo ("Shouldn't have connected without escaping braces!" . PHP_EOL);
} catch (PDOException $e) {
    echo $e->getMessage() . PHP_EOL;
}

echo "Done" . PHP_EOL;
?>
--EXPECT--
SQLSTATE[IMSSP]: An unescaped right brace (}) was found in either the user name or password.  All right braces must be escaped with another right brace (}}).
Done
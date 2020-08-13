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
sqlsrv_configure('WarningsReturnAsErrors', 0);

require_once 'MsCommon.inc';

function generateRandomPassword($insertBraces = true, $escapeBraces = true)
{
    $random = '! {W#g&;.,*6';
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

function checkErrorMessages($conn, $testCase, $randomPwd)
{
    $error = '*Login failed for user*';
    if (!$conn) {
        if (!fnmatch($error, sqlsrv_errors()[0]['message'])) {
            echo "Unexpected error for $testCase with '$randomPwd':" . PHP_EOL;
            var_dump(sqlsrv_errors());
        }
    } else {
        echo "$testCase: should have failed!" . PHP_EOL;
    }
}

$randomPwd = generateRandomPassword(false);
trace($randomPwd . PHP_EOL);
$conn = sqlsrv_connect($server, array("UID" => $userName, "pwd" => $randomPwd));
checkErrorMessages($conn, 'Password without right braces', $randomPwd);

$randomPwd = generateRandomPassword();
trace($randomPwd . PHP_EOL);
$conn = sqlsrv_connect($server, array("UID" => $userName, "pwd" => $randomPwd));
checkErrorMessages($conn, 'Password with right braces', $randomPwd);

$randomPwd = generateRandomPassword(true, false);
trace($randomPwd . PHP_EOL);
$conn = sqlsrv_connect($server, array("UID" => $userName, "pwd" => $randomPwd));
if ($conn) {
    echo ("Shouldn't have connected without escaping braces!" . PHP_EOL);
}
$errors = sqlsrv_errors();
echo $errors[0]["message"] . PHP_EOL;

echo "Done" . PHP_EOL;
?>
--EXPECT--
An unescaped right brace (}) was found in either the user name or password.  All right braces must be escaped with another right brace (}}).
Done
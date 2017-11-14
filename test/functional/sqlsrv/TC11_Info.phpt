--TEST--
Driver Loading Test
--DESCRIPTION--
Loads the PHP driver to check whether it is operational or not by checking if SQLSRV is enabled in phpinfo()
--ENV--
PHPT_EXEC=true
--FILE--
<?php
require_once('MsCommon.inc');

function info()
{
    ob_start();
    phpinfo();
    $data = ob_get_contents();
    ob_clean();

    return $data;
}

$testName = "Driver Loading";
startTest($testName);

preg_match('/sqlsrv support.*/', info(), $matches);
var_dump($matches);

endTest($testName);

?>
--EXPECT--
array(1) {
  [0]=>
  string(25) "sqlsrv support => enabled"
}
Test "Driver Loading" completed successfully.

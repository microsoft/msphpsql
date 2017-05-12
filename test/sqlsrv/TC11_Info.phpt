--TEST--
Driver Loading Test
--DESCRIPTION--
Loads the PHP driver to check whether it is operational or not by checking if SQLSRV is enabled in phpinfo()
--ENV--
PHPT_EXEC=true
--FILE--
<?php
include 'MsCommon.inc';

function Info()
{
    ob_start();
    phpinfo();
    $data = ob_get_contents();
    ob_clean();
    
    return $data;
}

$testName = "Driver Loading";
StartTest($testName);

preg_match ( '/sqlsrv support.*/', Info(), $matches );
var_dump( $matches );

EndTest($testName);

?>
--EXPECT--
array(1) {
  [0]=>
  string(25) "sqlsrv support => enabled"
}
Test "Driver Loading" completed successfully.
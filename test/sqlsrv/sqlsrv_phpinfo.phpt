--TEST--
Check if SQLSRV is enabled in phpinfo()
--FILE--
<?php
#get phpinfo() as string
function pinfo() {
    ob_start();
    phpinfo();
    $data = ob_get_contents();
    ob_clean();
    return $data;
}
preg_match ( '/sqlsrv support.*/', pinfo(), $matches );
var_dump( $matches ); 
?>
--EXPECT--
array(1) {
  [0]=>
  string(25) "sqlsrv support => enabled"
}

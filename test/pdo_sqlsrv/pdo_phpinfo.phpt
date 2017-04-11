--TEST--
Check if PDO_SQLSRV is enabled in phpinfo()
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
preg_match ('/pdo_sqlsrv support.*/', pinfo(), $matches);
var_dump( $matches ); 
?>
--EXPECT--
array(1) {
  [0]=>
  string(29) "pdo_sqlsrv support => enabled"
}
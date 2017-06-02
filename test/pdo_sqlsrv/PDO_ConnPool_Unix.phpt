--TEST--
PDO Connection Pooling Test on Unix
--DESCRIPTION--
This test assumes odbcinst.ini has not been modified. 
This test also requires root privileges to modify odbcinst.ini file on Linux.
--SKIPIF--
<?php if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') die("Skipped: Test for Linux and Mac");
--FILE--
<?php
$lines_to_add="CPTimeout=5\n[ODBC]\nPooling=Yes\n";

//get odbcinst.ini location
$lines = explode("\n", shell_exec("odbcinst -j"));
$odbcinst_ini = explode(" ", $lines[1])[1];

//enable pooling by modifying the odbcinst.ini file
$current = file_get_contents($odbcinst_ini);
$current.=$lines_to_add;
file_put_contents($odbcinst_ini, $current);

//Creating a new php process, because for changes in odbcinst.ini file to affect pooling, drivers must be reloaded.
print_r(shell_exec("php ./test/pdo_sqlsrv/isPooled.php"));

//disable pooling by modifying the odbcinst.ini file
$current = file_get_contents($odbcinst_ini);
$current = str_replace($lines_to_add,'',$current);
file_put_contents($odbcinst_ini, $current);

print_r(shell_exec("php ./test/pdo_sqlsrv/isPooled.php"));
?>
--EXPECT--
Pooled
Not Pooled



--TEST--
PDO Connection Pooling Test on Unix
--DESCRIPTION--
This test assumes odbcinst.ini has not been modified. 
This test also requires root privileges to modify odbcinst.ini file on Linux.
--SKIPIF--
<?php if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') die("Skipped: Test for Linux and Mac"); ?>
--FILE--
<?php
// On Bamboo we must use sudo to fiddle with odbcinst.ini
// On travis-ci we can't use sudo
$sudo = '';
$user = posix_getpwuid(posix_geteuid());
if (strtolower($user['name']) == 'bamboo')
{
    $sudo = 'sudo ';
}

$lines_to_add="CPTimeout=5\n[ODBC]\nPooling=Yes\n";

//get odbcinst.ini location
$lines = explode("\n", shell_exec("odbcinst -j"));
$odbcinst_ini = explode(" ", $lines[1])[1];

//back up the odbcinst.ini file
shell_exec($sudo."cp $odbcinst_ini $odbcinst_ini.bak");

//enable pooling by modifying the odbcinst.ini file
$current = file_get_contents($odbcinst_ini);
$current.=$lines_to_add;
shell_exec("cp $odbcinst_ini .");
file_put_contents("odbcinst.ini", $current);
shell_exec($sudo."cp odbcinst.ini $odbcinst_ini");

//Creating a new php process, because for changes in odbcinst.ini file to affect pooling, drivers must be reloaded.
print_r(shell_exec(PHP_BINARY." ".dirname(__FILE__)."/isPooled.php"));

//disable pooling by modifying the odbcinst.ini file
$current = file_get_contents($odbcinst_ini);
$current = str_replace($lines_to_add,'',$current);
file_put_contents("odbcinst.ini", $current);
shell_exec($sudo."cp odbcinst.ini $odbcinst_ini");

print_r(shell_exec(PHP_BINARY." ".dirname(__FILE__)."/isPooled.php"));
?>
--CLEAN--
<?php
$sudo = '';
$user = posix_getpwuid(posix_geteuid());
if (strtolower($user['name']) == 'bamboo')
{
    $sudo = 'sudo ';
}

$lines = explode("\n", shell_exec("odbcinst -j"));
$odbcinst_ini = explode(" ", $lines[1])[1];
shell_exec($sudo."cp $odbcinst_ini.bak $odbcinst_ini");
shell_exec($sudo."rm $odbcinst_ini.bak");
?>
--EXPECT--
Pooled
Not Pooled


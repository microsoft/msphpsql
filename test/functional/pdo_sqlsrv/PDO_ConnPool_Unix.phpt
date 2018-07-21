--TEST--
PDO_SQLSRV Connection Pooling Test on Unix
--DESCRIPTION--
This test assumes the default odbcinst.ini has not been modified. 
--SKIPIF--
<?php if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') die("Skipped: Test for Linux and Mac"); ?>
--FILE--
<?php
function findODBCDriver($content, $lines_to_add)
{
    require_once('MsSetup.inc');
    $command = "odbcinst -q -d -n '$driver'";
    $info = shell_exec($command);

    return str_replace($info, $info.$lines_to_add, $content);
}

$lines_to_add="CPTimeout=5\n[ODBC]\nPooling=Yes\n";

//get default odbcinst.ini location
$lines = explode("\n", shell_exec("odbcinst -j"));
$odbcinst_ini = explode(" ", $lines[1])[1];
$custom_odbcinst_ini = dirname(__FILE__)."/odbcinst.ini";

//copy the default odbcinst.ini into the current folder
copy( $odbcinst_ini, $custom_odbcinst_ini);

//enable pooling by modifying the odbcinst.ini file
$current = file_get_contents($custom_odbcinst_ini);
$new_content = findODBCDriver($current, $lines_to_add);
file_put_contents($custom_odbcinst_ini, $new_content);

//Creating a new php process, because for changes in odbcinst.ini file to affect pooling, drivers must be reloaded.
//Also setting the odbcini path to the current folder for the same process.
//This will let us modify odbcinst.ini without root permissions
print_r(shell_exec("export ODBCSYSINI=".dirname(__FILE__)."&&".PHP_BINARY." ".dirname(__FILE__)."/isPooled.php"));


//disable pooling by modifying the odbcinst.ini file
$current = file_get_contents($custom_odbcinst_ini);
$current = str_replace($lines_to_add,'',$current);
file_put_contents($custom_odbcinst_ini, $current);

print_r(shell_exec("export ODBCSYSINI=".dirname(__FILE__)."&&".PHP_BINARY." ".dirname(__FILE__)."/isPooled.php"));
?>
--CLEAN--
<?php
$custom_odbcinst_ini = dirname(__FILE__)."/odbcinst.ini";
unlink($custom_odbcinst_ini);
//back to default odbcinst.ini
shell_exec("unset ODBCSYSINI");

?>
--EXPECT--
Pooled
Not Pooled

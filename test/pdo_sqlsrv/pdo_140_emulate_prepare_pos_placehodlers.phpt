--TEST--
Test emulate prepare with mix bound param encodings and positional placeholders (i.e., using '?' as placeholders)
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
 
$connection_options['pdo'] = array();
$connection_options['pdo'][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

$databaseName = "tempdb";
$cnn = new PDO("sqlsrv:Server=$server;Database=$databaseName", $uid, $pwd, $connection_options['pdo']);

// Drop
try {
    $pdo_options = array();
    $pdo_options[PDO::ATTR_EMULATE_PREPARES] = TRUE;
    $pdo_options[PDO::SQLSRV_ATTR_DIRECT_QUERY] = TRUE;
    $pdo_options[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;
    $pdo_options[PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE] = PDO::SQLSRV_CURSOR_BUFFERED;
    $st = $cnn->prepare('DROP TABLE WATCHDOG', $pdo_options);
    
    $st->execute();  
}
catch(\Exception $e) {}
 
$tablescript = <<<EOF
CREATE TABLE [dbo].[watchdog](
    [system_encoding] [nvarchar](128),
    [utf8_encoding] [nvarchar](128),
    [binary_encoding] [varbinary](max))
EOF;
 
// Recreate
$st = $cnn->prepare($tablescript, $pdo_options);
$st->execute();  

$query = <<<EOF
INSERT INTO [watchdog] ([system_encoding], [utf8_encoding], [binary_encoding]) VALUES
(?, ?, ?)
EOF;
 
/** @var MyStatement */
$st = $cnn->prepare($query, $pdo_options);

$system_param = 'system encoded string';
$utf8_param = '가각ácasa';
$binary_param = fopen('php://memory', 'a');
fwrite($binary_param, 'asdgasdgasdgsadg');
rewind($binary_param);

$st->bindParam(1, $system_param, PDO::PARAM_STR);
$st->bindParam(2, $utf8_param, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8);
$st->bindParam(3, $binary_param, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);

$st->execute();

$st = $cnn->query("SELECT * FROM [watchdog]");
var_dump($st->fetchAll());

$st = NULL;
$cnn = NULL;

?>
--EXPECT--
array(1) {
  [0]=>
  array(6) {
    ["system_encoding"]=>
    string(21) "system encoded string"
    [0]=>
    string(21) "system encoded string"
    ["utf8_encoding"]=>
    string(12) "가각ácasa"
    [1]=>
    string(12) "가각ácasa"
    ["binary_encoding"]=>
    string(16) "asdgasdgasdgsadg"
    [2]=>
    string(16) "asdgasdgasdgsadg"
  }
}
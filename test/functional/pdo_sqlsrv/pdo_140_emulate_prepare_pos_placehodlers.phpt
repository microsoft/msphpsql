--TEST--
Test emulate prepare with mix bound param encodings and positional placeholders (i.e., using '?' as placeholders)
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once( "MsCommon_mid-refactor.inc" );
 
try {
    $cnn = connect();

    $pdo_options = array();
    if (!isColEncrypted()) {
        // Emulate prepare and direct query are not supported with Always Encrypted
        $pdo_options[PDO::ATTR_EMULATE_PREPARES] = TRUE;
        $pdo_options[PDO::SQLSRV_ATTR_DIRECT_QUERY] = TRUE;
    }
    $pdo_options[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;
    $pdo_options[PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE] = PDO::SQLSRV_CURSOR_BUFFERED;

    $tbname = "watchdog";
    createTable( $cnn, $tbname, array( "system_encoding" => "nvarchar(128)", "utf8_encoding" => "nvarchar(128)", "binary_encoding" => "varbinary(max)"));

    $system_param = 'system encoded string';
    $utf8_param = '가각ácasa';
    $binary_param = fopen('php://memory', 'a');
    fwrite($binary_param, 'asdgasdgasdgsadg');
    rewind($binary_param);

    $inputs = array("system_encoding" => $system_param,
                    "utf8_encoding" => new BindParamOp( 2, $utf8_param, "PDO::PARAM_STR", 0, "PDO::SQLSRV_ENCODING_UTF8" ),
                    "binary_encoding" => new BindParamOp( 3, $binary_param, "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY" ));

    insertRow($cnn, $tbname, $inputs, "prepareBindParam");

    $data = selectAll($cnn, $tbname);
    var_dump($data);

    dropTable($cnn, $tbname);
    unset($st);
    unset($cnn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
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
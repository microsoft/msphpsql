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
    if (!isAEConnected()) {
        // Emulate prepare and direct query not supported when connected with Always Encrypted
        $pdo_options[PDO::ATTR_EMULATE_PREPARES] = TRUE;
        $pdo_options[PDO::SQLSRV_ATTR_DIRECT_QUERY] = TRUE;
    }
    $pdo_options[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;
    $pdo_options[PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE] = PDO::SQLSRV_CURSOR_BUFFERED;

    $tbname = "watchdog";
    createTable( $cnn, $tbname, array( "system_encoding" => "nvarchar(128)", "utf8_encoding" => "nvarchar(128)", "binary_encoding" => "varbinary(max)"));

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

    $system_param2 = 'another string';
    $utf8_param = 'Привет';
    $binary_param = fopen('php://memory', 'a');
    fwrite($binary_param, hex2bin('80838790a9'));   // testing some extended characters
    rewind($binary_param);

    $st->bindParam(1, $system_param2, PDO::PARAM_STR);
    $st->bindParam(2, $utf8_param, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8);
    $st->bindParam(3, $binary_param, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);

    $st->execute();

    $select = "SELECT * FROM $tbname WHERE system_encoding = ?";
    $st = $cnn->prepare($select);
    $st->bindParam(1, $system_param);
    $st->execute();
    
    $data = $st->fetchAll(PDO::FETCH_BOTH);
    var_dump($data);
    
    $st->bindParam(1, $system_param2);
    $st->execute();
    
    $st->bindColumn('utf8_encoding', $param2);
    $st->bindColumn('binary_encoding', $param3);
    $row = $st->fetch(PDO::FETCH_BOUND);
    if ($param2 != $utf8_param)
        echo "$param2\n";
    if (bin2hex($param3) != '80838790a9')
        echo "$param3\n";

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
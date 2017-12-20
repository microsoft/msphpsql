--TEST--
Test PDO::prepare() with PDO::ATTR_EMULATE_PREPARES.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require_once('skipif_mid-refactor.inc'); ?>
--FILE--
<?php

require_once("MsCommon_mid-refactor.inc");

$db = connect();

// retrieve correct results
$s = $db->prepare("SELECT '' + TITLE FROM cd_info GROUP BY '' + TITLE");
$s->execute();

$titles = array();
while ($r = $s->fetch()) {
    $titles[] = $r[0];
}

$exception_thrown = false;

try {
    $s = $db->prepare('SELECT :prefix + TITLE FROM cd_info GROUP BY :prefix + TITLE');
    $s->bindValue(':prefix', "");
    $s->execute();

    while ($r = $s->fetch()) {
        print_r($r);
    }
} catch (PDOException $e) {
    $exception_thrown = true;
}

if (!$exception_thrown) {
    die("Exception not thrown\nTest failed\n");
}

// Column encryption is not supported by emulate prepared statement
$option[PDO::ATTR_EMULATE_PREPARES] = true;
if (isAEConnected()) {
    $option[PDO::ATTR_EMULATE_PREPARES] = false;
}

if (!isAEConnected()) {
    $s = $db->prepare("SELECT :prefix + TITLE FROM cd_info GROUP BY :prefix + TITLE", $option);
    $s->bindValue(':prefix', "");
} else {
    // binding parameters in the select list is not supported with Column Encryption
    $s = $db->prepare("SELECT TITLE FROM cd_info GROUP BY TITLE", $option);
}
$s->execute();

$param_titles = array();
while ($r = $s->fetch()) {
    $param_titles[] = $r[0];
}

if ($titles === $param_titles) {
    echo "Test succeeded\n";
} else {
    echo "Test failed\n";
    print_r($titles);
    print_r($param_titles);
}

?>
--EXPECT--
Test succeeded

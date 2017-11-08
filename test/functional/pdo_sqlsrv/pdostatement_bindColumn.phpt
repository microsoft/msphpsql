--TEST--
Test the bindColumn method using either by bind by column number or bind by column name
--SKIPIF--
<?php require "skipif_mid-refactor.inc"; ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsData_PDO_AllTypes.inc");

function bindColumnByName($db, $tbname)
{
    $stmt = $db->prepare("SELECT IntCol, CharCol, DateTimeCol FROM $tbname");
    $stmt->execute();
    $stmt->bindColumn('IntCol', $intCol);
    $stmt->bindColumn('CharCol', $charCol);
    $stmt->bindColumn('DateTimeCol', $dateTimeCol);

    while ($row = $stmt->fetch(PDO::FETCH_BOUND)) {
        echo $intCol . " : " . $charCol . " : " . $dateTimeCol . "\n";
    }
}

function bindColumnByNumber($db, $tbname)
{
    $stmt = $db->prepare("SELECT IntCol, CharCol, DateTimeCol FROM $tbname");
    $stmt->execute();
    $stmt->bindColumn(1, $intCol);
    $stmt->bindColumn(2, $charCol);
    $stmt->bindColumn(3, $dateTimeCol);

    while ($row = $stmt->fetch(PDO::FETCH_BOUND)) {
        echo $intCol . " : " . $charCol . " : " . $dateTimeCol . "\n";
    }
}

try {
    $db = connect();
    $tbname = "PDO_MainTypes";
    createAndInsertTableMainTypes($db, $tbname);
    echo "Bind Column by name :\n";
    bindColumnByName($db, $tbname);
    echo "Bind Column by number :\n";
    bindColumnByNumber($db, $tbname);

    dropTable($db, $tbname);
    unset($db);
} catch (PDOException $e) {
    var_dump($e);
}

?>
--EXPECT--
Bind Column by name :
1 : STRINGCOL1 : 2000-11-11 11:11:11.110
2 : STRINGCOL2 : 2000-11-11 11:11:11.223
Bind Column by number :
1 : STRINGCOL1 : 2000-11-11 11:11:11.110
2 : STRINGCOL2 : 2000-11-11 11:11:11.223

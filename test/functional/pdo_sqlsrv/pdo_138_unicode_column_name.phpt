--TEST--
Github 138. Test for Unicode Column Metadata.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
/**
 * Summary of prepare
 *
 * @param mixed $connection
 * @param mixed $query
 * @return PDOStatement
 */
function prepare($connection, $query)
{
    $pdo_options = array();
    // emulate and binding parameter with direct query are not supported in Always Encrypted
    if (!isAEConnected()) {
        $pdo_options[PDO::ATTR_EMULATE_PREPARES] = true;
        $pdo_options[PDO::SQLSRV_ATTR_DIRECT_QUERY] = true;
    }
    $pdo_options[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;
    $pdo_options[PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE] = PDO::SQLSRV_CURSOR_BUFFERED;
    return $connection->prepare($query, $pdo_options);
}

//*******************************************************
// TEST BEGIN
//*******************************************************

require_once("MsCommon_mid-refactor.inc");

try {
    $connection = connect();

    // Create Table
    $tbname = "mytáble";
    createTable($connection, $tbname, array(new ColumnMeta("nchar(10)", "id"), new ColumnMeta("nchar(10)", "väriable"), new ColumnMeta("nchar(10)", "tésting")));


    $query = "INSERT INTO $tbname (id, tésting, väriable) VALUES (:db_insert0, :db_insert1, :db_insert2)";

    /** @var MyStatement */
    $st = prepare($connection, $query);

    $st->bindValue(':db_insert0', 'a', PDO::PARAM_STR);
    $st->bindValue(':db_insert1', 'b', PDO::PARAM_STR);
    $st->bindValue(':db_insert2', 'c', PDO::PARAM_STR);

    $st->execute();

    $st = prepare($connection, "SELECT * FROM $tbname");

    $st->execute();

    while ($row = $st->fetchAll()) {
        $row = reset($row);
        echo(isset($row['id']) ? "OK" : "FAIL") , "\n";
        echo(isset($row['tésting']) ? "OK" : "FAIL") , "\n";
        echo(isset($row['väriable']) ? "OK" : "FAIL") , "\n";
    }

    for ($i = 0; $i < $st->columnCount(); $i++) {
        $meta = $st->getColumnMeta($i);
        echo $meta['name'] , "\n";
    }

    dropTable($connection, $tbname);
    unset($st);
    unset($connection);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>

--EXPECT--
OK
OK
OK
id
väriable
tésting

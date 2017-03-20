--TEST--
Bug #127 (sqlsrv: data type sql_variant is unsupported)
--SKIPIF--
<?php if(!extension_loaded("sqlsrv")) print "skip"; ?>
--INI--
--FILE--
<?php
require('config.inc');

$conn = sqlsrv_connect($serverName, ['Database' => $database, 'Uid' => $username, 'PWD' => $password]);
print 'sqlsrv connection successfull: '.($conn !== false ? 'yes' : 'no').PHP_EOL;

function build_sql($value) {
    // See https://msdn.microsoft.com/en-us/library/ms178550.aspx
    $properties= ['BaseType', 'Precision', 'Scale', 'TotalBytes', 'Collation', 'MaxLength'];

    $sql = "DECLARE @v1 sql_variant; SET @v1 = {$value}; ";
    $sql .= "SELECT @v1 as Value";
    foreach ($properties as $property) {
        $sql .=  ", SQL_VARIANT_PROPERTY(@v1, '{$property}') as {$property}";
    }
    $sql .= ";";

    return $sql;
}

function test_variant_type($conn, $type, $value) {
    $result = sqlsrv_query($conn, build_sql($value));
    print 'sqlsrv query successfull: '.($result !== false ? 'yes' : 'no').PHP_EOL;

    $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    print 'sql_variant ' . $type . ' php type: ' . gettype($row['Value']) . PHP_EOL;
    print 'sql_variant ' . $type . ' value: ' . $row['Value'] . PHP_EOL;
    print 'sql_variant ' . $type . ' base type: ' . $row['BaseType'] . PHP_EOL;
    print 'sql_variant ' . $type . ' precision: ' . $row['Precision'] . PHP_EOL;
    print 'sql_variant ' . $type . ' scale: ' . $row['Scale'] . PHP_EOL;
    print 'sql_variant ' . $type . ' total bytes: ' . $row['TotalBytes'] . PHP_EOL;
    print 'sql_variant ' . $type . ' collation: ' . $row['Collation'] . PHP_EOL;
    print 'sql_variant ' . $type . ' max length: ' . $row['MaxLength'] . PHP_EOL;

    sqlsrv_free_stmt($result);
}

test_variant_type($conn, 'string', "'ABC'");
test_variant_type($conn, 'float', "cast (46279.1 as decimal(8,2))");

?>
--EXPECT--
sqlsrv connection successfull: yes
sqlsrv query successfull: yes
sql_variant string php type: string
sql_variant string value: ABC
sql_variant string base type: varchar
sql_variant string precision: 0
sql_variant string scale: 0
sql_variant string total bytes: 11
sql_variant string collation: SQL_Latin1_General_CP1_CI_AS
sql_variant string max length: 3
sqlsrv query successfull: yes
sql_variant float php type: string
sql_variant float value: 46279.10
sql_variant float base type: decimal
sql_variant float precision: 8
sql_variant float scale: 2
sql_variant float total bytes: 9
sql_variant float collation: 
sql_variant float max length: 5

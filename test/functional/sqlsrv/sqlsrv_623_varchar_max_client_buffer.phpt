--TEST--
GitHub issue #623 - data is correctly fetched using a client buffer even with varchar(max) in the result set 
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
sqlsrv_configure('WarningsReturnAsErrors', 1);

// step 01: Connect without Always Encrypted feature
require_once('MsSetup.inc');
$conn = sqlsrv_connect($server, $connectionOptions);
if (! $conn) {
    fatalError("Failed to connect\n");
}

$tableName = 'systemtag';

// step 02: Setup table
require_once('MsCommon.inc');
dropTable($conn, $tableName);
$sql = "CREATE TABLE [$tableName](
            [id] [int] IDENTITY(1,1) NOT NULL,
            [name] [varchar](255) NOT NULL,
            [tag] [varchar](max) NULL,
            CONSTRAINT [PK_usertag] PRIMARY KEY CLUSTERED (
                [id] ASC
            ))";

$stmt = sqlsrv_query($conn, $sql);

// step 03: Insert test data
$name = 'Disclaimer e-mail';
$tag = 'De informatie van deze e-mail en de eventueel bijgevoegde bestanden is vertrouwelijk en kan juridisch beschermd zijn. Het is uitsluitend bedoeld voor degene(n) aan wie het gericht is of degene(n) die geautoriseerd zijn om het bericht te ontvangen. Indien het bericht niet voor u bestemd is, wordt u verzocht de inhoud ervan niet te lezen, en het bericht aan ons terug te sturen. In dat geval wijzen wij u er tevens op dat het kopië';

$sql = "INSERT INTO $tableName (name, tag) VALUES (?, ?)";
$parameters = [$name, $tag];
$stmt = sqlsrv_query($conn, $sql, $parameters);

// step 04: Fetch the data
$sql = "SELECT name, tag FROM $tableName";

$stmt = sqlsrv_query( $conn, $sql, [], ['Scrollable' => SQLSRV_CURSOR_CLIENT_BUFFERED]);
$result = sqlsrv_fetch($stmt);
if ($result) {
    $value1 = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    var_dump($value1 === $name);
    $value2 = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    var_dump($value2 === $tag);
} else {
    fatalError('Something went wrong\n');
}

dropTable($conn, $tableName);
echo "Done\n";
?>
--EXPECT--
bool(true)
bool(true)
Done
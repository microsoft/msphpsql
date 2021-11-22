--TEST--
GitHub issue 1329 - string truncation error when binding some parameters as non-nulls the second time
--DESCRIPTION--
The test shows the same parameters, though bound as nulls in the first insertion, can be bound as non-nulls in the subsequent insertions.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = AE\connect();

dropTable($conn, 'srv_domains');

$tsql = <<<CREATESQL
CREATE TABLE srv_domains (
     id bigint IDENTITY(1,1) NOT NULL,
     authority nvarchar(255) COLLATE SQL_Latin1_General_CP1_CI_AS NOT NULL,
     base_url_redirect nvarchar(255) COLLATE SQL_Latin1_General_CP1_CI_AS NULL,
     regular_not_found_redirect nvarchar(255) COLLATE SQL_Latin1_General_CP1_CI_AS NULL,
     invalid_short_url_redirect nvarchar(255) COLLATE SQL_Latin1_General_CP1_CI_AS NULL,
     CONSTRAINT PK__srv_domains__3213E83F512B36BA PRIMARY KEY (id))
CREATESQL;

$stmt = sqlsrv_query($conn, $tsql);
if (!$stmt) {
    fatalError("failed to create test table");
}

$tsql = <<<INSERTSQL
INSERT INTO srv_domains (authority, base_url_redirect, regular_not_found_redirect, invalid_short_url_redirect) VALUES (?, ?, ?, ?)
INSERTSQL;

$authority = 'foo.com';
$base = null;
$notFound = null;
$invalid = null;
$params = [&$authority, &$base, &$notFound, &$invalid];
$stmt = sqlsrv_prepare($conn, $tsql, $params);
if (!$stmt) {
    fatalError("failed to prepare the insert statement");
}
$result = sqlsrv_execute($stmt);
if (!$result) {
    fatalError("failed to execute the insert statement (1)");
}

$authority = 'detached-with-redirects.com';
$base = 'foo.com';
$notFound = 'bar.com';
$invalid = null;
$result = sqlsrv_execute($stmt);
if (!$result) {
    fatalError("failed to execute the insert statement (2)");
}

$authority = 'other-redirects.com';
$base = 'foobar.com';
$notFound = null;
$invalid = 'none';
$result = sqlsrv_execute($stmt);
if (!$result) {
    fatalError("failed to execute the insert statement (3)");
}

// fetch the data
$tsql = "SELECT * FROM srv_domains";
$stmt = sqlsrv_query($conn, $tsql);
if (!$stmt) {
    fatalError("failed to run select query");
}
while ($row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC)) {
    print_r($row);
}

dropTable($conn, 'srv_domains');

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo "Done\n";

?>
--EXPECT--
Array
(
    [0] => 1
    [1] => foo.com
    [2] => 
    [3] => 
    [4] => 
)
Array
(
    [0] => 2
    [1] => detached-with-redirects.com
    [2] => foo.com
    [3] => bar.com
    [4] => 
)
Array
(
    [0] => 3
    [1] => other-redirects.com
    [2] => foobar.com
    [3] => 
    [4] => none
)
Done

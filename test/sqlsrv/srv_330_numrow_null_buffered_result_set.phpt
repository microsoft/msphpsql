--TEST--
GitHub issue #330 - get numrow of null buffered result set
--DESCRIPTION--
A variation of the example in GitHub issue 330. A -1 value returned as numrow of a null buffered result set.
--SKIPIF--
--FILE--
<?php
require_once("tools.inc");

require_once("autonomous_setup.php");

// Connect
$conn = sqlsrv_connect($serverName, $connectionInfo) ?: FatalError("Failed to connect");

$stmt = sqlsrv_query($conn, "IF EXISTS (SELECT * FROM [sys].[objects] WHERE (name LIKE 'non_existent_table_name%') AND type in (N'U'))
    BEGIN
    select 0
    END", [], ['Scrollable' => SQLSRV_CURSOR_CLIENT_BUFFERED]);

if ($stmt) {
    $hasRows = sqlsrv_has_rows($stmt);
    $numRows = sqlsrv_num_rows($stmt);
    echo "hasRows: ";
    var_dump($hasRows);
    echo "numRows: ";
    var_dump($numRows);
}
?>
--EXPECT--
hasRows: bool(false)
numRows: int(-1)
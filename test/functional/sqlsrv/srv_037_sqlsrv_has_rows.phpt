--TEST--
sqlsrv_has_rows() using a forward and scrollable cursor
--DESCRIPTION--
This test calls sqlsrv_has_rows multiple times. Previously, multiple calls
with a forward cursor would advance the cursor. Subsequent fetch calls
would then fail.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

// connect
$conn = AE\connect();
$tableName = 'test037'; 

// Create table
$columns = array(new AE\ColumnMeta('VARCHAR(10)', 'ID'));
AE\createTable($conn, $tableName, $columns);

AE\insertRow($conn, $tableName, array("ID" => '1998.1'));
AE\insertRow($conn, $tableName, array("ID" => '-2004'));
AE\insertRow($conn, $tableName, array("ID" => '2016'));
AE\insertRow($conn, $tableName, array("ID" => '4.2EUR'));

// Fetch data using forward only cursor
$query = "SELECT ID FROM $tableName";
$stmt = AE\executeQuery($conn, $query);

// repeated calls should return true and fetch should work.
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";

if (sqlsrv_has_rows($stmt)) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)) {
        echo $row[0]."\n";
    }
}

// Fetch data using a scrollable cursor
$stmt = sqlsrv_query($conn, $query, [], array("Scrollable"=>"buffered"));

echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";

if (sqlsrv_has_rows($stmt)) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)) {
        echo $row[0]."\n";
    }
}

// $query = "SELECT ID FROM $tableName where ID='nomatch'";
$stmt = AE\executeQuery($conn, $query, "ID = ?", array('nomatch'));

// repeated calls should return false if there are no rows.
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";

// Fetch data using a scrollable cursor
$stmt = AE\executeQuery($conn, $query, "ID = ?", array('nomatch'), array("Scrollable"=>"buffered"));

// repeated calls should return false if there are no rows.
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";
echo "Has Rows?" . (sqlsrv_has_rows($stmt) ? " Yes!" : " NO!") . "\n";

dropTable($conn, $tableName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done"
?>

--EXPECT--
Has Rows? Yes!
Has Rows? Yes!
Has Rows? Yes!
Has Rows? Yes!
1998.1
-2004
2016
4.2EUR
Has Rows? Yes!
Has Rows? Yes!
Has Rows? Yes!
Has Rows? Yes!
1998.1
-2004
2016
4.2EUR
Has Rows? NO!
Has Rows? NO!
Has Rows? NO!
Has Rows? NO!
Done

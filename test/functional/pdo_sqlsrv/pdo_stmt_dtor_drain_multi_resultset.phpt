--TEST--
Verify stmt dtor drains unconsumed result sets from multiple SELECT batches
--DESCRIPTION--
Exercises the drain code in pdo_sqlsrv_stmt_dtor by executing a batch
that returns multiple result sets, then destroying the statement without
consuming them. The drain loop must call core_sqlsrv_next_result for
each pending result set.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = connect();

// Execute a batch that returns multiple result sets
// Each SELECT produces its own result set
$stmt = $conn->prepare("SELECT 1 AS a; SELECT 2 AS b; SELECT 3 AS c");
$stmt->execute();

// Only fetch from the first result set, leaving 2 unconsumed
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "First result: " . $row['a'] . "\n";

// Destroy statement with 2 unconsumed result sets — exercises the drain loop
unset($stmt);

// Verify connection is still usable
$row = $conn->query("SELECT 42 AS answer")->fetch(PDO::FETCH_ASSOC);
echo "After drain: " . $row['answer'] . "\n";

unset($conn);
echo "Done.\n";
?>
--EXPECT--
First result: 1
After drain: 42
Done.

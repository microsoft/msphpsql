--TEST--
preparing a statement and executing it more than once.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

    require_once('MsCommon.inc');

    $conn = connect();
    if (!$conn) {
        fatalError("Failed to connect.");
    }

    $stmt = sqlsrv_prepare($conn, "sp_who");
    if (!$stmt) {
        fatalError("prepare failed");
    }

    $success = sqlsrv_execute($stmt);
    if (!$success) {
        fatalError("first execute failed");
    }

    echo "first execute succeeded<br/>\n";
    while ($row = sqlsrv_fetch($stmt)) {
    }
    if ($row === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $success = sqlsrv_execute($stmt);
    if (!$success) {
        var_dump(sqlsrv_errors());
        fatalError("second execute failed");
    }

    echo "second execute succeeded<br/>\n";
    while ($row = sqlsrv_fetch_array($stmt)) {
        // var_dump( $row );
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
?>
--EXPECTF--
first execute succeeded<br/>
second execute succeeded<br/>

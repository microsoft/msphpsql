--TEST--
GitHub issue 1685 - fetch time should not grow with the number of bound parameters
--DESCRIPTION--
PDO calls the driver's param hook for every bound parameter on every fetched row
unless the driver skips the FETCH_PRE and FETCH_POST events. Compare the time to
fetch the same rows with 1 and with 2000 unused bound parameters. Before the fix
the ratio was about 170x on a local test machine; with identical workloads it is
about 1x. The threshold is generous to avoid failing on noisy machines.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

const ROWS = 2000;
const ITERATIONS = 9;
const MAX_RATIO = 4;

function median(array $values)
{
    sort($values);
    return $values[intdiv(count($values), 2)];
}

function fetchTime($conn, $paramCount)
{
    $sql = 'SELECT TOP (' . ROWS . ') ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS n
        FROM sys.all_objects a CROSS JOIN sys.all_objects b WHERE 1 = 1'
        . str_repeat(' OR ? IS NULL', $paramCount);

    // a client-buffered cursor retrieves all rows during execute(), so server and
    // network waits are excluded and only local fetch overhead is timed
    $stmt = $conn->prepare($sql, [
        PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL,
        PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED,
    ]);
    $stmt->execute(array_fill(0, $paramCount, 'x'));

    $start = hrtime(true);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $elapsed = hrtime(true) - $start;

    if (count($rows) !== ROWS) {
        echo "Expected " . ROWS . " rows with $paramCount params, got " . count($rows) . "\n";
    }
    return $elapsed;
}

try {
    // with Column Encryption, the standalone `? IS NULL` parameters are described
    // as int and the string values fail to convert, so disable it
    $conn = connect('', [], PDO::ERRMODE_EXCEPTION, true);

    // warm up, then interleave the two cases so drift affects both equally
    fetchTime($conn, 1);
    fetchTime($conn, 2000);

    $few = [];
    $many = [];
    for ($i = 0; $i < ITERATIONS; $i++) {
        $few[] = fetchTime($conn, 1);
        $many[] = fetchTime($conn, 2000);
    }

    $fewMs = median($few) / 1e6;
    $manyMs = median($many) / 1e6;
    $ratio = $manyMs / $fewMs;

    if ($ratio < MAX_RATIO) {
        echo "Fetch time ratio is within limits\n";
    } else {
        printf("Fetch time ratio %.1fx exceeds %dx (1 param: %.3f ms, 2000 params: %.3f ms)\n",
               $ratio, MAX_RATIO, $fewMs, $manyMs);
    }

    unset($conn);
    echo "Done\n";
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
Fetch time ratio is within limits
Done

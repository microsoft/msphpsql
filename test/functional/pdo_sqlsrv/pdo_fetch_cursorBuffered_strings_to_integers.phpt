--TEST--
prepare with cursor buffered and fetch various columns with the column bound and specified to pdo type int
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();
    $decimal = -2345209.3103;
    $numeric = 987234.9919;
    $salary = "3456789.15";
    $debt = "98765.99";

    $tbname = "TESTTABLE";
    createTable($conn, $tbname, array("c_decimal" => "decimal(28,4)", "c_numeric" => "numeric(32,4)",
                                      "c_varchar" => "varchar(20)", "c_nvarchar" => "nvarchar(20)"));

    $query = "INSERT INTO $tbname VALUES(:p0, :p1, :p2, :p3)";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':p0', $decimal);
    $stmt->bindValue(':p1', $numeric);
    $stmt->bindValue(':p2', $salary);
    $stmt->bindValue(':p3', $debt);
    $stmt->execute();

    $decimal2 = $decimal * 2;
    $numeric2 = $numeric * 2;
    $salary2 = $salary * 2;
    $debt2 = $debt * 2;

    $stmt->bindValue(':p0', $decimal2);
    $stmt->bindValue(':p1', $numeric2);
    $stmt->bindValue(':p2', $salary2);
    $stmt->bindValue(':p3', $debt2);
    $stmt->execute();

    $decimal3 = $decimal * 3;
    $numeric3 = $numeric * 3;
    $salary3 = $salary * 3;
    $debt3 = $debt * 3;

    $stmt->bindValue(':p0', $decimal3);
    $stmt->bindValue(':p1', $numeric3);
    $stmt->bindValue(':p2', $salary3);
    $stmt->bindValue(':p3', $debt3);
    $stmt->execute();

    unset($stmt);

    echo("Input values:\n\torginal:$decimal\t$numeric\t$salary\t$debt\n\tdoubles:$decimal2\t$numeric2\t$salary2\t$debt2\n\ttriples:$decimal3\t$numeric3\t$salary3\t$debt3\n");

    $query = "SELECT * FROM $tbname";

    // prepare with no buffered cursor
    echo "\n\nComparing results (stringify off, fetch_numeric on):\n";
    // no buffered cursor, stringify off, fetch_numeric on
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
    $stmt1 = $conn->prepare($query);
    $stmt1->execute();

    // buffered cursor, stringify off, fetch_numeric on
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
    $stmt2 = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
    $stmt2->execute();

    compareResults($stmt1, $stmt2);

    unset($stmt1);
    unset($stmt2);

    echo "\n\nComparing results (stringify off, fetch_numeric off):\n";
    // no buffered cursor, stringify off, fetch_numeric off
    $conn->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, false);
    $stmt1 = $conn->prepare($query);
    $stmt1->execute();

    // buffered cursor, stringify off, fetch_numeric off
    $conn->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, false);
    $stmt2 = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
    $stmt2->execute();

    compareResults($stmt1, $stmt2);

    unset($stmt1);
    unset($stmt1);

    DropTable($conn, $tbname);
    unset($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}

function compareResults($stmt1, $stmt2)
{
    $stmt1->bindColumn('c_decimal', $decimal_col1, PDO::PARAM_INT);
    $stmt1->bindColumn('c_numeric', $numeric_col1, PDO::PARAM_INT);
    $stmt1->bindColumn('c_varchar', $salary_col1, PDO::PARAM_INT);
    $stmt1->bindColumn('c_nvarchar', $debt_col1, PDO::PARAM_INT);

    $stmt2->bindColumn('c_decimal', $decimal_col2, PDO::PARAM_INT);
    $stmt2->bindColumn('c_numeric', $numeric_col2, PDO::PARAM_INT);
    $stmt2->bindColumn('c_varchar', $salary_col2, PDO::PARAM_INT);
    $stmt2->bindColumn('c_nvarchar', $debt_col2, PDO::PARAM_INT);

    $numRows = 3;
    for ($i = 1; $i <= $numRows; $i++) {
        echo "\nreading row " . $i . "\n";

        $value1 = $stmt1->fetch(PDO::FETCH_BOUND);
        $value2 = $stmt2->fetch(PDO::FETCH_BOUND);

        compareData($decimal_col1, $decimal_col2);
        compareData($numeric_col1, $numeric_col2);
        compareData($salary_col1, $salary_col2);
        compareData($debt_col1, $debt_col2);
    }
}

function compareData($data1, $data2)
{
    if ($data1 != $data2) {
        echo "Not matched!\n";
    } else {
        echo "Matched!\n";
    }

    echo("\tExpected: ");
    var_dump($data1);
    echo("\tActual: ");
    var_dump($data2);
}
?>
--EXPECT--
Input values:
	orginal:-2345209.3103	987234.9919	3456789.15	98765.99
	doubles:-4690418.6206	1974469.9838	6913578.3	197531.98
	triples:-7035627.9309	2961704.9757	10370367.45	296297.97


Comparing results (stringify off, fetch_numeric on):

reading row 1
Matched!
	Expected: int(-2345209)
	Actual: int(-2345209)
Matched!
	Expected: int(987234)
	Actual: int(987234)
Matched!
	Expected: int(3456789)
	Actual: int(3456789)
Matched!
	Expected: int(98765)
	Actual: int(98765)

reading row 2
Matched!
	Expected: int(-4690418)
	Actual: int(-4690418)
Matched!
	Expected: int(1974469)
	Actual: int(1974469)
Matched!
	Expected: int(6913578)
	Actual: int(6913578)
Matched!
	Expected: int(197531)
	Actual: int(197531)

reading row 3
Matched!
	Expected: int(-7035627)
	Actual: int(-7035627)
Matched!
	Expected: int(2961704)
	Actual: int(2961704)
Matched!
	Expected: int(10370367)
	Actual: int(10370367)
Matched!
	Expected: int(296297)
	Actual: int(296297)


Comparing results (stringify off, fetch_numeric off):

reading row 1
Matched!
	Expected: int(-2345209)
	Actual: int(-2345209)
Matched!
	Expected: int(987234)
	Actual: int(987234)
Matched!
	Expected: int(3456789)
	Actual: int(3456789)
Matched!
	Expected: int(98765)
	Actual: int(98765)

reading row 2
Matched!
	Expected: int(-4690418)
	Actual: int(-4690418)
Matched!
	Expected: int(1974469)
	Actual: int(1974469)
Matched!
	Expected: int(6913578)
	Actual: int(6913578)
Matched!
	Expected: int(197531)
	Actual: int(197531)

reading row 3
Matched!
	Expected: int(-7035627)
	Actual: int(-7035627)
Matched!
	Expected: int(2961704)
	Actual: int(2961704)
Matched!
	Expected: int(10370367)
	Actual: int(10370367)
Matched!
	Expected: int(296297)
	Actual: int(296297)

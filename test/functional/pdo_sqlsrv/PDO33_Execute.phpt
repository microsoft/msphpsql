--TEST--
PDO Statement Execution Test
--DESCRIPTION--
Basic verification for "PDOStatement::execute()".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function ExecStmt()
{
    include 'MsSetup.inc';

    $testName = "PDO Statement - Execute";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val VARCHAR(10), val2 VARCHAR(16)", null);
    $data = array(  array('10', 'Abc', 'zxy'),
            array('20', 'Def', 'wvu'),
            array('30', 'Ghi', 'tsr'),
            array('40', 'Jkl', 'qpo'),
                array('50', 'Mno', 'nml'),
            array('60', 'Pqr', 'kji'));

    // Insert using question mark placeholders
    $stmt1 = PrepareQuery($conn1, "INSERT INTO [$tableName] VALUES(?, ?, ?)");
    foreach ($data as $row)
    {
        $stmt1->execute($row);
    }
    unset($stmt1);

    // Count inserted rows
    $stmt2 = PrepareQuery($conn1, "SELECT COUNT(id) FROM [$tableName]");
    $stmt2->execute();
    $num = $stmt2->fetchColumn();
    echo 'There are ' . $num . " rows in the table.\n";
    $stmt2->closeCursor();

    // Insert using named parameters
    $stmt1 = PrepareQuery($conn1, "INSERT INTO [$tableName] VALUES(:first, :second, :third)");
    foreach ($data as $row)
    {
        $stmt1->execute(array(':first'=>($row[0] + 5), ':second'=>$row[1], ':third'=>$row[2]));
    }
    unset($stmt1);

    $stmt2->execute();
    $num = $stmt2->fetchColumn();
    unset($stmt2);
    echo 'There are ' . $num . " rows in the table.\n";

    // Fetch
    $stmt1 = PrepareQuery($conn1, "SELECT TOP(1) :param FROM [$tableName] ORDER BY id ASC");
    $stmt1->execute(array(':param' => 'ID'));
    var_dump($stmt1->fetchAll(PDO::FETCH_ASSOC));
    $stmt1->closeCursor();

    // Cleanup
    DropTable($conn1, $tableName);
    $stmt1 = null;
    $stmt2 = null;
    $conn1 = null;

    EndTest($testName);
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{

    try
    {
        ExecStmt();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
There are 6 rows in the table.
There are 12 rows in the table.
array(1) {
  [0]=>
  array(1) {
    [""]=>
    string(2) "ID"
  }
}
Test "PDO Statement - Execute" completed successfully.
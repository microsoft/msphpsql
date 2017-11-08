--TEST--
Test simple insert, fetch and update with ColumnEncryption enabled and a custome keystore provider
--SKIPIF--
<?php require('skipif_not_ksp.inc'); ?>
--FILE--
<?php
    function CreatePatientsTable()
    {
        global $conn;
        $tableName = 'Patients';

        $columns = array(new AE\ColumnMeta('int', 'PatientId', 'IDENTITY(1,1) NOT NULL'),
                         new AE\ColumnMeta('char(11)', 'SSN'),
                         new AE\ColumnMeta('nvarchar(50)', 'FirstName'),
                         new AE\ColumnMeta('nvarchar(50)', 'LastName'),
                         new AE\ColumnMeta('date', 'BirthDate'));
        $stmt = AE\createTable($conn, $tableName, $columns);
        if (!$stmt) {
            fatalError("Failed to create test table!\n");
        }

        return $tableName;
    }

    function insertData($ssn, $fname, $lname, $date)
    {
        global $conn, $tableName;
        $params = array(
                    array($ssn, null, null, SQLSRV_SQLTYPE_CHAR(11)), array($fname, null, null, SQLSRV_SQLTYPE_NVARCHAR(50)), array($lname, null, null, SQLSRV_SQLTYPE_NVARCHAR(50)), array($date, null, null, SQLSRV_SQLTYPE_DATE)
        );

        $tsql = "INSERT INTO $tableName (SSN, FirstName, LastName, BirthDate) VALUES (?, ?, ?, ?)";
        if (! $stmt = sqlsrv_prepare($conn, $tsql, $params)) {
            fatalError("Failed to prepare statement.\n");
        }

        if (! sqlsrv_execute($stmt)) {
            fatalError("Failed to insert a new record.\n");
        }
    }

    function selectData()
    {
        global $conn, $tableName;
        $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
        while ($obj = sqlsrv_fetch_object($stmt)) {
            echo $obj->PatientId . "\n";
            echo $obj->SSN . "\n";
            echo $obj->FirstName . "\n";
            echo $obj->LastName . "\n";
            echo $obj->BirthDate . "\n\n";
        }
    }

    function selectDataBuffered()
    {
        global $conn, $tableName;

        $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName", array(), array("Scrollable"=>"buffered"));

        $row_count = sqlsrv_num_rows($stmt);
        echo "\nRow count for result set is $row_count\n";

        echo "First record=>\t";
        $row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_FIRST);
        $SSN = sqlsrv_get_field($stmt, 1);
        echo "SSN = $SSN\n";

        echo "Next record=>\t";
        $row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_NEXT);
        $BirthDate = sqlsrv_get_field($stmt, 4);
        echo "BirthDate = $BirthDate\n";

        echo "Last record=>\t";
        $row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_LAST);
        $LastName = sqlsrv_get_field($stmt, 3);
        echo "LastName = $LastName\n";
    }

    sqlsrv_configure('WarningsReturnAsErrors', 1);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsHelper.inc');
    $conn = AE\connect(array('ReturnDatesAsStrings'=>true));
    if($conn === false) {
        fatalError( "Failed to connect.\n");
    } else {
        echo "Connected successfully with ColumnEncryption enabled.\n";
    }

    $tableName = CreatePatientsTable();

    insertData('748-68-0245', 'Jeannette', 'McDonald', '2002-11-28');
    insertData('795-73-9838', 'John', 'Doe', '2001-05-29');
    insertData('456-12-5486', 'Jonathan', 'Wong', '1999-12-20');
    insertData('156-45-5486', 'Marianne', 'Smith', '1997-03-04');

    selectData();

    ///////////////////////////////////////////
    echo "Update Patient Jonathan Wong...\n";
    $params = array(array('1999-12-31', null, null, SQLSRV_SQLTYPE_DATE), 
                    array('Chang', null, null, SQLSRV_SQLTYPE_NVARCHAR(50)), 
                    array('456-12-5486', null, null, SQLSRV_SQLTYPE_CHAR(11)));

    $tsql = "UPDATE Patients SET BirthDate = ?, LastName = ? WHERE SSN = ?";
    $stmt = sqlsrv_query($conn, $tsql, $params);

    if (!$stmt) {
        fatalError("Failed to update record\n");
    }

    echo "Update his birthdate too...\n";
    $params = array(array('456-12-5486', null, null, SQLSRV_SQLTYPE_CHAR(11)));
    $tsql = "SELECT SSN, FirstName, LastName, BirthDate FROM Patients WHERE SSN = ?";
    $stmt = sqlsrv_query($conn, $tsql, $params);
    if (!$stmt) {
        fatalError("Failed to select with a WHERE clause\n");
    } else {
        $obj = sqlsrv_fetch_object($stmt);
        echo "BirthDate updated for $obj->FirstName:\n";
        echo $obj->SSN . "\n";
        echo $obj->FirstName . "\n";
        echo $obj->LastName . "\n";
        echo $obj->BirthDate . "\n\n";
    }

    ///////////////////////////////////////////
    $procName = '#phpAEProc1';
    $spArgs = "@p1 INT, @p2 DATE OUTPUT";
    $spCode = "SET @p2 = (SELECT [BirthDate] FROM Patients WHERE [PatientId] = @p1)";
    $stmt = sqlsrv_query($conn, "CREATE PROC [$procName] ($spArgs) AS BEGIN $spCode END");
    sqlsrv_free_stmt($stmt);

    $callResult = '1900-01-01';
    //when binding parameter using sqlsrv_query in a column encryption enabled connection, need to provide the sql_type in all parameters
    $params = array(array(1, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT), 
                    array(&$callResult, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_DATE));
    $callArgs = "?, ?";
    $stmt = sqlsrv_query($conn, "{ CALL [$procName] ($callArgs)}", $params);
    if (!$stmt) {
        print_r(sqlsrv_errors());
    } else {
        echo "BirthDate for the first record is: $callResult\n";
    }

    ///////////////////////////////////////////
    $procName = '#phpAEProc2';
    $spArgs = "@p1 INT, @p2 CHAR(11) OUTPUT";
    $spCode = "SET @p2 = (SELECT [SSN] FROM Patients WHERE [PatientId] = @p1)";
    $stmt = sqlsrv_query($conn, "CREATE PROC [$procName] ($spArgs) AS BEGIN $spCode END");
    sqlsrv_free_stmt($stmt);

    $callResult = '000-00-0000';
    // when binding parameter using sqlsrv_query in a column encryption enabled connection, 
    // need to provide the sql_type in all parameters
    $params = array(array(1, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT), 
                    array(&$callResult, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_CHAR(11)));
    $callArgs = "?, ?";
    $stmt = sqlsrv_query($conn, "{ CALL [$procName] ($callArgs)}", $params);
    if (!$stmt) {
        print_r(sqlsrv_errors());
    } else {
        echo "SSN for the first record is: $callResult\n";
    }

    selectDataBuffered();

    echo "\nDone\n";
?>
--EXPECT--
Connected successfully with ColumnEncryption enabled.
1
748-68-0245
Jeannette
McDonald
2002-11-28

2
795-73-9838
John
Doe
2001-05-29

3
456-12-5486
Jonathan
Wong
1999-12-20

4
156-45-5486
Marianne
Smith
1997-03-04

Update Patient Jonathan Wong...
Update his birthdate too...
BirthDate updated for Jonathan:
456-12-5486
Jonathan
Chang
1999-12-31

BirthDate for the first record is: 2002-11-28
SSN for the first record is: 748-68-0245

Row count for result set is 4
First record=>	SSN = 748-68-0245
Next record=>	BirthDate = 2001-05-29
Last record=>	LastName = Smith

Done
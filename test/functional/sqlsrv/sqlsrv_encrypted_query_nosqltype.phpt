--TEST--
Test using sqlsrv_query for binding parameters with column encryption and a custom keystore provider
--SKIPIF--
<?php require('skipif_not_ksp.inc'); ?>
--FILE--
<?php
    function createPatientsTable()
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

    function printError()
    {
        $errors = sqlsrv_errors();
        foreach ($errors as $error) {
            echo "  SQLSTATE: " . $error['SQLSTATE'] . "\n";
            echo "  code: " . $error['code'] . "\n";
            echo "  message: " . $error['message'] . "\n\n";
        }
    }

    sqlsrv_configure('WarningsReturnAsErrors', 1);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsHelper.inc');
    $conn = AE\connect(array('ReturnDatesAsStrings'=>true));

    if ($conn === false) {
        echo "Failed to connect.\n";
        printError();
    } else {
        echo "Connected successfully with ColumnEncryption enabled.\n\n";
    }

    $tableName = createPatientsTable();

    $tsql = "INSERT INTO $tableName (SSN, FirstName, LastName, BirthDate) VALUES (?, ?, ?, ?)";
    $inputs = array('748-68-0245', 'Jeannette', 'McDonald', '2002-11-28');

    // expects an error in Column Encryption enabled connection
    print_r("Using sqlsrv_query and binding parameters with literal values:\n");
    $stmt = sqlsrv_query($conn, $tsql, $inputs);
    if (!$stmt) {
        printError();
    }

    // expects an error in Column Encryption enabled connection
    print_r("Using sqlsrv_query and binding parameters with parameter arrays and no sqltypes provided:\n");
    $stmt = sqlsrv_query($conn, $tsql, array(array($inputs[0], SQLSRV_PARAM_IN),
                                             array($inputs[1], SQLSRV_PARAM_IN),
                                             array($inputs[2], SQLSRV_PARAM_IN),
                                             array($inputs[3], SQLSRV_PARAM_IN)));
    if (!$stmt) {
        printError();
    }
    // no error is expected
    print_r("Using sqlsrv_query and binding parameters with parameter arrays and sqltypes provided:\n");
    $stmt = sqlsrv_query($conn, $tsql, array(array($inputs[0], null, null, SQLSRV_SQLTYPE_CHAR(11)),
                                             array($inputs[1], null, null, SQLSRV_SQLTYPE_NVARCHAR(50)),
                                             array($inputs[2], null, null, SQLSRV_SQLTYPE_NVARCHAR(50)),
                                             array($inputs[3], null, null, SQLSRV_SQLTYPE_DATE)));
    if (!$stmt) {
        printError();
    }
    selectData();

    echo "Done\n";
?>
--EXPECT--
Connected successfully with ColumnEncryption enabled.

Using sqlsrv_query and binding parameters with literal values:
  SQLSTATE: IMSSP
  code: -63
  message: Must specify the SQL type for each parameter in a parameterized query when using sqlsrv_query in a column encryption enabled connection.

Using sqlsrv_query and binding parameters with parameter arrays and no sqltypes provided:
  SQLSTATE: IMSSP
  code: -63
  message: Must specify the SQL type for each parameter in a parameterized query when using sqlsrv_query in a column encryption enabled connection.

Using sqlsrv_query and binding parameters with parameter arrays and sqltypes provided:
1
748-68-0245
Jeannette
McDonald
2002-11-28

Done
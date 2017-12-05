--TEST--
Test using sqlsrv_query for binding parameters with column encryption and a custom keystore provider
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
    class Patient 
    {
        public $SSN;
        public $FirstName;
        public $LastName;
        public $BirthDate;
        
        public function __construct($ssn, $fname, $lname, $bdate)
        {
            $this->SSN = $ssn;
            $this->FirstName = $fname;
            $this->LastName = $lname;
            $this->BirthDate = $bdate;
        }
    }
    
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
        global $conn, $tableName, $numRows, $patient;

        $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");

        $id = 1;
        while ($obj = sqlsrv_fetch_object($stmt)) {
            if ($obj->PatientId !== $id) {
                echo "Expected $id but got $obj->PatientId\n";
            }
            if ($obj->SSN !== $patient->SSN) {
                echo "Expected $patient->SSN but got $obj->SSN\n";
            }
            if ($obj->FirstName !== $patient->FirstName) {
                echo "Expected $patient->FirstName but got $obj->FirstName\n";
            }
            if ($obj->LastName !== $patient->LastName) {
                echo "Expected $patient->LastName but got $obj->LastName\n";
            }
            if ($obj->BirthDate !== $patient->BirthDate) {
                echo "Expected $patient->BirthDate but got $obj->BirthDate\n";
            }
            
            $id++;
        }
        $rowFetched = $id - 1;
        if ($rowFetched != $numRows){
            echo "Expected $numRows rows but got $rowFetched\n";
        }
    }

    function printError()
    {
        global $AEQueryError;
        
        $errors = sqlsrv_errors();
        if (AE\isColEncrypted()) {
            verifyError($errors[0], 'IMSSP', $AEQueryError);
        } else {
            foreach ($errors as $error) {
                echo "  SQLSTATE: " . $error['SQLSTATE'] . "\n";
                echo "  code: " . $error['code'] . "\n";
                echo "  message: " . $error['message'] . "\n\n";
            }
        }
    }

    sqlsrv_configure('WarningsReturnAsErrors', 1);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsHelper.inc');
    $conn = AE\connect(array('ReturnDatesAsStrings'=>true));
    if ($conn !== false) {
        echo "Connected successfully with ColumnEncryption enabled.\n\n";
    }

    $AEQueryError = 'Must specify the SQL type for each parameter in a parameterized query when using sqlsrv_query in a column encryption enabled connection.';

    $tableName = createPatientsTable();

    $numRows = 0;
    
    $tsql = "INSERT INTO $tableName (SSN, FirstName, LastName, BirthDate) VALUES (?, ?, ?, ?)";
    $patient = new Patient('748-68-0245', 'Jeannette', 'McDonald', '2002-11-28');
    $inputs = array($patient->SSN, $patient->FirstName, $patient->LastName, $patient->BirthDate);

    // expects an error in Column Encryption enabled connection
    print_r("Using sqlsrv_query and binding parameters with literal values:\n");

    $stmt = sqlsrv_query($conn, $tsql, $inputs);
    if (!$stmt) {
        printError();
    } else {
        $numRows++;
    }

    // expects an error in Column Encryption enabled connection
    print_r("Using sqlsrv_query and binding parameters with parameter arrays and no sqltypes provided:\n");
    $stmt = sqlsrv_query($conn, $tsql, array(array($inputs[0], SQLSRV_PARAM_IN),
                                             array($inputs[1], SQLSRV_PARAM_IN),
                                             array($inputs[2], SQLSRV_PARAM_IN),
                                             array($inputs[3], SQLSRV_PARAM_IN)));
    if (!$stmt) {
        printError();
    } else {
        $numRows++;
    }
    
    // no error is expected
    print_r("Using sqlsrv_query and binding parameters with parameter arrays and sqltypes provided:\n");
    $stmt = sqlsrv_query($conn, $tsql, array(array($inputs[0], null, null, SQLSRV_SQLTYPE_CHAR(11)),
                                             array($inputs[1], null, null, SQLSRV_SQLTYPE_NVARCHAR(50)),
                                             array($inputs[2], null, null, SQLSRV_SQLTYPE_NVARCHAR(50)),
                                             array($inputs[3], null, null, SQLSRV_SQLTYPE_DATE)));
    if (!$stmt) {
        printError();
    } else {
        $numRows++;
    }
    
    selectData();

    echo "Done\n";
?>
--EXPECT--
Connected successfully with ColumnEncryption enabled.

Using sqlsrv_query and binding parameters with literal values:
Using sqlsrv_query and binding parameters with parameter arrays and no sqltypes provided:
Using sqlsrv_query and binding parameters with parameter arrays and sqltypes provided:
Done
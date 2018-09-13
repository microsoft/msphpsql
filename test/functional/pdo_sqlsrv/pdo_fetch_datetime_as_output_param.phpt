--TEST--
Test attribute PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE and datetimes as output params 
--DESCRIPTION--
Do not support returning DateTime objects as output parameters. Setting attribute PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE to true should have no effect.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    date_default_timezone_set('America/Los_Angeles');

    $attr = array(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE => false);
    $conn = connect("", $attr);
    
    // Generate input values for the test table 
    $query = 'SELECT SYSDATETIME(), SYSDATETIMEOFFSET(), CONVERT(time, CURRENT_TIMESTAMP)';
    $stmt = $conn->query($query);
    $values = $stmt->fetch(PDO::FETCH_NUM);

    // create a test table with the above input date time values
    $tableName = "TestDateTimeOutParam";
    $columns = array('c1', 'c2', 'c3');
    $dataTypes = array("datetime2", "datetimeoffset", "time");

    $colMeta = array(new ColumnMeta($dataTypes[0], $columns[0]),
                     new ColumnMeta($dataTypes[1], $columns[1]),
                     new ColumnMeta($dataTypes[2], $columns[2]));
    createTable($conn, $tableName, $colMeta);
    
    $query = "INSERT INTO $tableName VALUES(?, ?, ?)";
    $stmt = $conn->prepare($query);
    for ($i = 0; $i < count($columns); $i++) {
        $stmt->bindParam($i+1, $values[$i], PDO::PARAM_LOB);
    }
    $stmt->execute();

    $lobException = 'An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams cannot be specified as output parameters.';

    for ($i = 0; $i < count($columns); $i++) {
        // create the stored procedure first
        $storedProcName = "spDateTimeOutParam" . $i;
        $procArgs = "@col $dataTypes[$i] OUTPUT";
        $procCode = "SELECT @col = $columns[$i] FROM $tableName";
        createProc($conn, $storedProcName, $procArgs, $procCode);

        // call stored procedure to retrieve output param type PDO::PARAM_STR
        $dateStr = '';
        $outSql = getCallProcSqlPlaceholders($storedProcName, 1);
        $options = array(PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE => true);
        $stmt = $conn->prepare($outSql, $options);
        $stmt->bindParam(1, $dateStr, PDO::PARAM_STR, 1024);
        $stmt->execute();
        
        if ($dateStr != $values[$i]) {
            echo "Expected $values[$i] for column ' . ($i+1) .' but got: ";
            var_dump($dateStr);
        } 
        
        // for output param type PDO::PARAM_LOB it should fail with the correct exception 
        try {
            $stmt->bindParam(1, $dateStr, PDO::PARAM_LOB, 1024);
            $stmt->execute();
            echo "Expected this to fail\n";
        } catch (PDOException $e) {
            $message = $e->getMessage();
            $matched = strpos($message, $lobException);
            if (!$matched) {
                var_dump($e->errorInfo);
            }
        }
        
        dropProc($conn, $storedProcName);
    }
    
    dropTable($conn, $tableName);
    echo "Done\n";
    
    unset($stmt); 
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
Done

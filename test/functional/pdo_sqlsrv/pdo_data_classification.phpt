--TEST--
Test data classification feature - retrieving sensitivity metadata if supported
--DESCRIPTION--
If both ODBC and server support this feature, this test verifies that sensitivity metadata can be added and correctly retrieved. If not, it will at least test the new statement attribute and some error cases.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once('MsSetup.inc');
require_once('MsCommon_mid-refactor.inc');

function testConnAttrCases()
{
    // Attribute PDO::SQLSRV_ATTR_DATA_CLASSIFICATION is limited to statement level only
    global $server, $databaseName, $driver, $uid, $pwd;

    $stmtErr = '*The given attribute is only supported on the PDOStatement object.';
    $noSupportErr = '*driver does not support that attribute';

    try {
        $dsn = getDSN($server, $databaseName, $driver);
        $attr = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::SQLSRV_ATTR_DATA_CLASSIFICATION => true);
        $conn = new PDO($dsn, $uid, $pwd, $attr);
    } catch (PDOException $e) {
        if (!fnmatch($stmtErr, $e->getMessage())) {
            echo "Connection attribute test (1) unexpected\n";
            var_dump($e->getMessage());
        }
    }

    try {
        $dsn = getDSN($server, $databaseName, $driver);
        $attr = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
        $conn = new PDO($dsn, $uid, $pwd, $attr);
        $conn->setAttribute(PDO::SQLSRV_ATTR_DATA_CLASSIFICATION, true);
    } catch (PDOException $e) {
        if (!fnmatch($stmtErr, $e->getMessage())) {
            echo "Connection attribute test (2) unexpected\n";
            var_dump($e->getMessage());
        }
    }

    try {
        $dsn = getDSN($server, $databaseName, $driver);
        $attr = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
        $conn = new PDO($dsn, $uid, $pwd, $attr);
        $conn->getAttribute(PDO::SQLSRV_ATTR_DATA_CLASSIFICATION);
    } catch (PDOException $e) {
        if (!fnmatch($noSupportErr, $e->getMessage())) {
            echo "Connection attribute test (3) unexpected\n";
            var_dump($e->getMessage());
        }
    }
}

function testNotAvailable($conn, $tableName, $isSupported)
{
    // If supported, the query should return a column with no classification
    $options = array(PDO::SQLSRV_ATTR_DATA_CLASSIFICATION => true);
    $tsql = ($isSupported)? "SELECT PatientId FROM $tableName" : "SELECT * FROM $tableName";
    $stmt = $conn->prepare($tsql, $options);
    $stmt->execute();

    $notAvailableErr = '*Failed to retrieve Data Classification Sensitivity Metadata. If the driver and the server both support the Data Classification feature, check whether the query returns columns with classification information.';
    try {
        $metadata = $stmt->getColumnMeta(0);
        echo "testNotAvailable: expected getColumnMeta to fail\n";
    } catch (PDOException $e) {
        if (!fnmatch($notAvailableErr, $e->getMessage())) {
            echo "testNotAvailable: exception unexpected\n";
            var_dump($e->getMessage());
        }
    }
}

function isDataClassSupported($conn)
{
    // Check both SQL Server version and ODBC driver version
    $msodbcsqlVer = $conn->getAttribute(PDO::ATTR_CLIENT_VERSION)["DriverVer"];
    $version = explode(".", $msodbcsqlVer);

    // ODBC Driver must be 17.2 or above
    if ($version[0] < 17 || $version[1] < 2) {
        return false;
    }

    // SQL Server must be SQL Server 2019 or above
    $serverVer = $conn->getAttribute(PDO::ATTR_SERVER_VERSION);
    if (explode('.', $serverVer)[0] < 15)
        return false;

    return true;
}

function getRegularMetadata($conn, $tsql)
{
    // Run the query without data classification metadata
    $stmt1 = $conn->query($tsql);

    // Run the query with the attribute set to false
    $options = array(PDO::SQLSRV_ATTR_DATA_CLASSIFICATION => false);
    $stmt2 = $conn->prepare($tsql, $options);
    $stmt2->execute();

    // The metadata for each column should be identical
    $numCol = $stmt1->columnCount();
    for ($i = 0; $i < $numCol; $i++) {
        $metadata1 = $stmt1->getColumnMeta($i);
        $metadata2 = $stmt2->getColumnMeta($i);

        $diff = array_diff($metadata1, $metadata2);
        if (!empty($diff)) {
            print_r($diff);
        }
    }

    return $stmt1;
}

function verifyClassInfo($input, $actual)
{
    // For simplicity of this test, only one set of sensitivity data (Label, Information Type)
    if (count($actual) != 1) {
        echo "Expected an array with only one element\n";
        return false;
    }

    if (count($actual[0]) != 2) {
        echo "Expected a Label pair and Information Type pair\n";
        return false;
    }

    // Label should be name and id pair (id should be empty)
    if (count($actual[0]['Label']) != 2) {
        echo "Expected only two elements for the label\n";
        return false;
    }
    $label = $input[0];
    if ($actual[0]['Label']['name'] !== $label || !empty($actual[0]['Label']['id'])){
        return false;
    }

    // Like Label, Information Type should also be name and id pair (id should be empty)
    if (count($actual[0]['Information Type']) != 2) {
        echo "Expected only two elements for the information type\n";
        return false;
    }
    $info = $input[1];
    if ($actual[0]['Information Type']['name'] !== $info || !empty($actual[0]['Information Type']['id'])){
        return false;
    }

    return true;
}

function compareDataClassification($stmt1, $stmt2, $classData)
{
    $numCol = $stmt1->columnCount();
    $noClassInfo = array('Data Classification' => array());

    for ($i = 0; $i < $numCol; $i++) {
        $metadata1 = $stmt1->getColumnMeta($i);
        $metadata2 = $stmt2->getColumnMeta($i);

        // If classification sensitivity data exists, only the
        // 'flags' field should be different
        foreach ($metadata2 as $key => $value) {
            if ($key == 'flags') {
                // Is classification input data empty?
                if (empty($classData[$i])) {
                    // Then it should be equivalent to $noClassInfo
                    if ($value !== $noClassInfo) {
                        var_dump($value);
                    }
                } else {
                    // Verify the classification metadata
                    if (!verifyClassInfo($classData[$i], $value['Data Classification'])) {
                        var_dump($value);
                    }
                }
            } else {
                // The other fields should be identical
                if ($metadata1[$key] !== $value) {
                    var_dump($value);
                }
            }
        }
    }
}

///////////////////////////////////////////////////////////////////////////////////////
try {
    testConnAttrCases();

    $conn = connect();
    $isSupported = isDataClassSupported($conn);

    // Create a test table
    $tableName = 'pdoPatients';
    $colMeta = array(new ColumnMeta('INT', 'PatientId', 'IDENTITY NOT NULL'),
                     new ColumnMeta('CHAR(11)', 'SSN'),
                     new ColumnMeta('NVARCHAR(50)', 'FirstName'),
                     new ColumnMeta('NVARCHAR(50)', 'LastName'),
                     new ColumnMeta('DATE', 'BirthDate'));
    createTable($conn, $tableName, $colMeta);

    // If data classification is supported, then add sensitivity classification metadata
    // to columns SSN and Birthdate
    $classData = [
                    array(),
                    array('Highly Confidential - GDPR', 'Credentials'),
                    array(),
                    array(),
                    array('Confidential Personal Data', 'Birthdays')
                 ];

    if ($isSupported) {
        // column SSN
        $label = $classData[1][0];
        $infoType = $classData[1][1];
        $sql = "ADD SENSITIVITY CLASSIFICATION TO [$tableName].SSN WITH (LABEL = '$label', INFORMATION_TYPE = '$infoType')";
        $conn->query($sql);

        // column BirthDate
        $label = $classData[4][0];
        $infoType = $classData[4][1];
        $sql = "ADD SENSITIVITY CLASSIFICATION TO [$tableName].BirthDate WITH (LABEL = '$label', INFORMATION_TYPE = '$infoType')";
        $conn->query($sql);
    }

    // Test another error condition
    testNotAvailable($conn, $tableName, $isSupported);

    // Run the query without data classification metadata
    $tsql = "SELECT * FROM $tableName";
    $stmt = getRegularMetadata($conn, $tsql);

    // Proceeed to retrieve sensitivity metadata, if supported
    if ($isSupported) {
        $options = array(PDO::SQLSRV_ATTR_DATA_CLASSIFICATION => true);
        $stmt1 = $conn->prepare($tsql, $options);
        $stmt1->execute();

        compareDataClassification($stmt, $stmt1, $classData);

        // $stmt2 should produce the same result as the previous $stmt1
        $stmt2 = $conn->prepare($tsql);
        $stmt2->execute();
        $stmt2->setAttribute(PDO::SQLSRV_ATTR_DATA_CLASSIFICATION, true);

        compareDataClassification($stmt, $stmt2, $classData);

        unset($stmt1);
        unset($stmt2);
    }

    dropTable($conn, $tableName);

    unset($stmt);
    unset($conn);

    echo "Done\n";
} catch (PDOException $e) {
    var_dump($e->getMessage());
}
?>
--EXPECT--
Done
--TEST--
Stream Send Test
--DESCRIPTION--
Verifies that all SQL types defined as capable of streaming (13 types)
can be successfully uploaded as streams.
Verifies that streams can be sent either directly at execution or
via sqlsrv_send_stream_data (i.e. after execution).
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function sendStream($minType, $maxType, $atExec)
{
    $testName = "Stream - ".($atExec ? "Send at Execution" : "Send after Execution");
    startTest($testName);

    setup();
    $tableName = "TC52test" . rand(0, 100);
    $fileName = "TC52test.dat";
    $conn1 = AE\connect();

    for ($k = $minType; $k <= $maxType; $k++) {
        switch ($k) {
        case 1: // int
        case 2: // tinyint
        case 3: // smallint
        case 4: // bigint
        case 5: // bit
        case 6: // float
        case 7: // real
        case 8: // decimal
        case 9: // numeric
        case 10:// money
        case 11:// smallmoney
        case 27:// timestamp
            $data = null;
            break;

        case 12:// char
        case 15:// nchar
            $data = "The quick brown fox jumps over the lazy dog";
            break;

        case 13:// varchar
        case 16:// nvarchar
            $data = "The quick brown fox jumps over the lazy dog 9876543210";
            break;

        case 14:// varchar(max)
        case 17:// nvarchar(max)
            $data = "The quick brown fox jumps over the lazy dog 0123456789";
            break;

        case 18:// text
        case 19:// ntext
            $data = "0123456789 The quick brown fox jumps over the lazy dog";
            break;

        case 20:// binary
            $data = "0123456789";
            break;

        case 21:// varbinary
            $data = "01234567899876543210";
            break;

        case 22:// varbinary(max)
            $data = "98765432100123456789";
            break;

        case 23:// image
            $data = "01234567899876543210";
            $phpType = SQLSRV_SQLTYPE_IMAGE;
            break;

        case 24:// uniqueidentifier
            $data = "12345678-9012-3456-7890-123456789012";
            break;

        case 25:// datetime
        case 26:// smalldatetime
            $data = date("Y-m-d");
            break;

        case 28:// xml
            $data = "<XmlTestData><Letters1>The quick brown fox jumps over the lazy dog</Letters1><Digits1>0123456789</Digits1></XmlTestData>";
            break;

        default:
            die("Unknown data type: $k.");
            break;
        }

        if ($data != null) {
            $fname1 = fopen($fileName, "w");
            fwrite($fname1, $data);
            fclose($fname1);
            $fname2 = fopen($fileName, "r");

            $sqlType = getSqlType($k);
            $phpDriverType = getSqlsrvSqlType($k, strlen($data));
            traceData($sqlType, $data);

            // create table
            $columns = array(new AE\ColumnMeta('int', 'c1'),
                             new AE\ColumnMeta($sqlType, 'c2'));
            AE\createTable($conn1, $tableName, $columns);

            // insert data
            $params = array($k, array($fname2, SQLSRV_PARAM_IN, null, $phpDriverType));
            insertQueryTable($conn1, $tableName, $params, $atExec);
            checkData($conn1, $tableName, 2, $data);

            fclose($fname2);
        }
    }
    dropTable($conn1, $tableName);
    unlink($fileName);

    sqlsrv_close($conn1);

    endTest($testName);
}

function insertQueryTable($conn, $tableName, $params, $execMode)
{
    $sql = "INSERT INTO $tableName (c1, c2) VALUES (?, ?)";
    $flag = $execMode ? 1 : 0;
    $options = array('SendStreamParamsAtExec' => $flag);
    if (AE\isColEncrypted()) {
        $stmt = sqlsrv_prepare($conn, $sql, $params, $options);
        if (!sqlsrv_execute($stmt)) {
            fatalError("Failed to execute query!", true);
        }
    } else {
        $stmt = sqlsrv_query($conn, $sql, $params, $options);
    }

    if (!$stmt) {
        fatalError("Failed to run query!", true);
    }
    if (!$execMode) {
        while (sqlsrv_send_stream_data($stmt)) {
        }
    }
    insertCheck($stmt);
}

function checkData($conn, $table, $cols, $expectedValue)
{
    $stmt = AE\selectFromTable($conn, $table);
    if (!sqlsrv_fetch($stmt)) {
        fatalError("Table $tableName was not expected to be empty.");
    }
    $numFields = sqlsrv_num_fields($stmt);
    if ($numFields != $cols) {
        die("Table $tableName was expected to have $cols fields.");
    }
    $actualValue = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    sqlsrv_free_stmt($stmt);
    if (strncmp($actualValue, $expectedValue, strlen($expectedValue)) != 0) {
        die("Data corruption: $expectedValue => $actualValue.");
    }
}

try {
    sendStream(12, 28, true);   // send stream at execution
    sendStream(12, 28, false);  // send stream after execution
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Stream - Send at Execution" completed successfully.
Test "Stream - Send after Execution" completed successfully.

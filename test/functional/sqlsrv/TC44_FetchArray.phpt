--TEST--
Fetch Array Test
--DESCRIPTION--
Verifies data retrieval via �sqlsrv_fetch_array�,
by checking all fetch type modes.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function FetchRow($minFetchMode, $maxFetchMode)
{
    include 'MsSetup.inc';

    $testName = "Fetch - Array";
    startTest($testName);

    if (!IsMarsSupported()) {
        endTest($testName);
        return;
    }

    setup();
    if (! isWindows()) {
        $conn1 = connect(array( 'CharacterSet'=>'UTF-8' ));
    } else {
        $conn1 = connect();
    }
    createTable($conn1, $tableName);

    $noRows = 10;
    $numFields = 0;
    insertRows($conn1, $tableName, $noRows);

    for ($k = $minFetchMode; $k <= $maxFetchMode; $k++) {
        $stmt1 = selectFromTable($conn1, $tableName);
        $stmt2 = selectFromTable($conn1, $tableName);
        if ($numFields == 0) {
            $numFields = sqlsrv_num_fields($stmt1);
        } else {
            $count = sqlsrv_num_fields($stmt1);
            if ($count != $numFields) {
                setUTF8Data(false);
                die("Unexpected number of fields: $count");
            }
        }

        switch ($k) {
        case 1:        // fetch array - numeric mode
            FetchArray($stmt1, $stmt2, SQLSRV_FETCH_NUMERIC, $noRows, $numFields);
            break;

        case 2:        // fetch array - associative mode
            FetchArray($stmt1, $stmt2, SQLSRV_FETCH_ASSOC, $noRows, $numFields);
            break;

        case 3:        // fetch array - both numeric & associative
            FetchArray($stmt1, $stmt2, SQLSRV_FETCH_BOTH, $noRows, $numFields);
            break;

        default:    // default
            FetchArray($stmt1, $stmt2, null, $noRows, $numFields);
            break;
        }

        sqlsrv_free_stmt($stmt1);
        sqlsrv_free_stmt($stmt2);
    }

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

function FetchArray($stmt, $stmtRef, $mode, $rows, $fields)
{
    $size = $fields;
    $fetchMode = $mode;
    if ($fetchMode == SQLSRV_FETCH_NUMERIC) {
        trace("\tRetrieving $rows arrays of size $size (Fetch Mode = NUMERIC) ...\n");
    } elseif ($fetchMode == SQLSRV_FETCH_ASSOC) {
        trace("\tRetrieving $rows arrays of size $size (Fetch Mode = ASSOCIATIVE) ...\n");
    } elseif ($fetchMode == SQLSRV_FETCH_BOTH) {
        $size = $fields * 2;
        trace("\tRetrieving $rows arrays of size $size (Fetch Mode = BOTH) ...\n");
    } else {
        $fetchMode = null;
        $size = $fields * 2;
        trace("\tRetrieving $rows arrays of size $size (Fetch Mode = DEFAULT) ...\n");
    }
    for ($i = 0; $i < $rows; $i++) {
        if ($fetchMode == null) {
            $row = sqlsrv_fetch_array($stmt);
        } else {
            $row = sqlsrv_fetch_array($stmt, $fetchMode);
        }
        if ($row === false) {
            fatalError("Row $i is missing");
        }
        $rowSize = count($row);
        if ($rowSize != $size) {
            setUTF8Data(false);
            die("Row array has an incorrect size: ".$rowSize);
        }
        $rowRref = sqlsrv_fetch($stmtRef);
        for ($j = 0; $j < $fields; $j++) {
            if (!CheckData($row, $stmtRef, $j, $fetchMode)) {
                setUTF8Data(false);
                die("Data corruption on row ".($i + 1)." column ".($j + 1));
            }
        }
    }
}


function CheckData($row, $stmt, $index, $mode)
{
    $success = true;

    $col = $index + 1;
    $actual = (($mode == SQLSRV_FETCH_ASSOC) ? $row[GetColName($col)] : $row[$index]);
    $expected = null;

    if (!IsUpdatable($col)) {
        // do not check the timestamp
    } elseif (IsNumeric($col) || IsDateTime($col)) {
        $expected = sqlsrv_get_field($stmt, $index);
        if ($expected != $actual) {
            $success = false;
        }
    } elseif (IsBinary($col)) {
        $expected = sqlsrv_get_field($stmt, $index, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        $actual = bin2hex($actual);
        if (strcasecmp($actual, $expected) != 0) {
            $success = false;
        }
    } else { // if (IsChar($col))
        if (useUTF8Data()) {
            $expected = sqlsrv_get_field($stmt, $index, SQLSRV_PHPTYPE_STRING('UTF-8'));
        } else {
            $expected = sqlsrv_get_field($stmt, $index, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        }
        if (strcmp($actual, $expected) != 0) {
            $success = false;
        }
    }
    if (!$success) {
        trace("\nData error\nExpected:\n$expected\nActual:\n$actual\n");
    }
    return ($success);
}


//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    if (! isWindows()) {
        setUTF8Data(true);
    }
    try {
        FetchRow(1, 4);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    setUTF8Data(false);
}

repro();

?>
--EXPECT--
Test "Fetch - Array" completed successfully.

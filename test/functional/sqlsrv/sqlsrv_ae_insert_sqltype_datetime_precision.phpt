--TEST--
Test for inserting encrypted data of datetime2, datetimeoffset and time datatypes with different precisions
--DESCRIPTION--
Test implicit conversions between different precisions
With Always Encrypted, implicit conversion works if:
1. From input of SQLSRV_SQLTYPE_DATETIME2 to a dateteim2(7) column
2. From input of SQLSRV_SQLTYPE_DATETIMEOFFSET to a datetimeoffset(7) column
3. From input of SQLSRV_SQLTYPE_TIME to a time(7) column
Note: with Always Encrypted, implicit converion should work as long as the SQLSRV_SQLTYPE has a smaller precision than the one defined in the column. However, the SQLSRV driver does not let the user specify the precision in these SQLSRV_SQLTYPE_* constants and they are all default to a precision of 7. Hence when user specifies SQLSRV_SQLTYPE_DATETIME2, SQLSRV_SQLTYPE_DATETIMEOFFSET or SQLSRV_SQLTYPE_TIME when binding parameter during insertion, only insertion into a column of precision 7 is allowed.
Without AlwaysEncrypted, implicit conversion between different precisions works
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function compareDate($dtobj, $dtstr, $dataType, $precision) {
    $dtobj_date = $dtobj->format("Y-m-d H:i:s.u");
    $dtobj_timezone = $dtobj->getTimezone()->getName();
    $dtarr = null;
    
    if ($dataType == "datetimeoffset") {
        $dtarr = explode(' ', $dtstr);
    }
    
    // php only supports up to 6 decimal places in datetime
    // drop the last decimal place before comparing
    if ($precision == 7) {
        $dtstr = substr($dtstr, 0, -1);
        if (!is_null($dtarr)) {
            $dtarr[1] = substr($dtarr[1], 0, -1);
        }
    }
    if (strpos($dtobj_date, $dtstr) !== false) {
        return true;
    }
    if ($dataType == "datetimeoffset") {
        if (strpos($dtobj_date, $dtarr[0]) !== false && strpos($dtobj_date, $dtarr[1]) !== false && strpos($dtobj_timezone, $dtarr[2]) !== false) {
            return true;
        }
    }
    return false;
}

$dataTypes = array("datetime2", "datetimeoffset", "time");
$precisions = array(0, 1, 2, 4, 7);
$inputValuesInit = array("datetime2" => array("0001-01-01 00:00:00", "9999-12-31 23:59:59"),
                         "datetimeoffset" => array("0001-01-01 00:00:00 -14:00", "9999-12-31 23:59:59 +14:00"),
                         "time" => array("00:00:00", "23:59:59"));

$conn = AE\connect();
foreach($dataTypes as $dataType) {
    foreach($precisions as $m) {
        // add $m number of decimal digits to the some input values
        $inputValues[0] = $inputValuesInit[$dataType][0];
        $inputValues[1] = $inputValuesInit[$dataType][1];
        if ($m != 0) {
            if ($dataType == "datetime2") {
                $inputValues[1] .= "." . str_repeat("4", $m);
            } else if ($dataType == "datetimeoffset") {
                $dtoffsetPieces = explode(" ", $inputValues[1]);
                $inputValues[1] = $dtoffsetPieces[0] . " " . $dtoffsetPieces[1] . "." . str_repeat("4", $m) . " " . $dtoffsetPieces[2];
            } else if ($dataType == "time") {
                $inputValues[0] .= "." . str_repeat("0", $m);
                $inputValues[1] .= "." . str_repeat("4", $m);
            }
        }
        $typeFull = "$dataType($m)";
        echo "\nTesting $typeFull:\n";
    
        // create table containing datetime2(m), datetimeoffset(m) or time(m) columns
        $tbname = "test_" . $dataType . $m;
        $colMetaArr = array(new AE\ColumnMeta($typeFull, "c_det"), new AE\ColumnMeta($typeFull, "c_rand", null, false));
        AE\createTable($conn, $tbname, $colMetaArr);
        
        // insert by specifying the corresponding SQLSRV_SQLTYPE
        $sqlType = "SQLSRV_SQLTYPE_" . strtoupper($dataType);
        $inputs = array(new AE\BindParamOption($inputValues[0], null, null, $sqlType),
                        new AE\BindParamOption($inputValues[1], null, null, $sqlType));
        $r;
        $stmt = AE\insertRow($conn, $tbname, array("c_det" => $inputs[0], "c_rand" => $inputs[1]), $r, AE\INSERT_PREPARE_PARAMS);
        
        // check the case when the column precision (m) is less than 7
        // with AE: should not work
        // without AE: should work
        if ($m < 7) {
            if (AE\isDataEncrypted()) {
                if ($r !== false) {
                    echo "AE: Conversion from $sqlType to $typeFull should not be supported\n";
                } else {
                    if (sqlsrv_errors()[0]['SQLSTATE'] != "22018") {
                        echo "AE: Conversion from $sqlType to $typeFull expects an operand type clash error, actual error is incorrect\n";
                        var_dump(sqlsrv_errors());
                    } else {
                        echo "Test successfully done\n";
                    }
                }
            } else {
                if ($r === false) {
                    echo "Conversion from $sqlType to $typeFull should be supported\n";
                } else {
                    $sql = "SELECT c_det, c_rand FROM $tbname";
                    $stmt = sqlsrv_query($conn, $sql);
                    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                    if (!compareDate($row['c_det'], $inputValues[0], $dataType, $m) || !compareDate($row['c_rand'], $inputValues[1], $dataType, $m)) {
                        echo "Conversion from $sqlType to $typeFull causes data corruption\n";
                    } else {
                        echo "Test successfully done\n";
                    }
                }
            }
        // check the case when the column precision is 7
        // should work with AE or non AE
        } else {
            if ($r === false) {
                echo "Conversion from $sqlType to $typeFull should be supported\n";
            } else {
                $sql = "SELECT c_det, c_rand FROM $tbname";
                $stmt = sqlsrv_query($conn, $sql);
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if (compareDate($row['c_det'], $inputValues[0], $dataType, $m) && compareDate($row['c_rand'], $inputValues[1], $dataType, $m)) {
                    echo "****Conversion from $sqlType to $typeFull is supported****\n";
                } else {
                    echo "Conversion from $sqlType to $typeFull causes data corruption\n";
                    var_dump($row);
                }
            }
        }
    }
    // cleanup
    sqlsrv_free_stmt($stmt);
    dropTable($conn, $tbname);
}
sqlsrv_close($conn);
    
?>
--EXPECT--
Testing datetime2(0):
Test successfully done

Testing datetime2(1):
Test successfully done

Testing datetime2(2):
Test successfully done

Testing datetime2(4):
Test successfully done

Testing datetime2(7):
****Conversion from SQLSRV_SQLTYPE_DATETIME2 to datetime2(7) is supported****

Testing datetimeoffset(0):
Test successfully done

Testing datetimeoffset(1):
Test successfully done

Testing datetimeoffset(2):
Test successfully done

Testing datetimeoffset(4):
Test successfully done

Testing datetimeoffset(7):
****Conversion from SQLSRV_SQLTYPE_DATETIMEOFFSET to datetimeoffset(7) is supported****

Testing time(0):
Test successfully done

Testing time(1):
Test successfully done

Testing time(2):
Test successfully done

Testing time(4):
Test successfully done

Testing time(7):
****Conversion from SQLSRV_SQLTYPE_TIME to time(7) is supported****
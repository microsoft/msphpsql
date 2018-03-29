--TEST--
Test for retrieving encrypted data of char types with different sizes as output parameters
--DESCRIPTION--
Test implicit conversions between different char types of different sizes
With Always Encrypted, implicit conversion works if:
1. From a char(m) column to a SQLSRV_SQLTYPE_CHAR(n) output parameter where m == n
2. From a char(m) column to a SQLSRV_SQLTYPE_VARCHAR(n) output parameter where m == n
3. From a varchar(m) column to a SQLSRV_SQLTYPE_CHAR(n) output parameter where m == n
4. From a varchar(m) column to a SQLSRV_SQLTYPE_VARCHAR(n) output parameter where m == n
Without AlwaysEncrypted, implicit conversion works if:
1. From a char(m) column to a SQLSRV_SQLTYPE_CHAR(n) output parameter where m, n == any value
2. From a char(m) column to a SQLSRV_SQLTYPE_VARCHAR(n) output parameter where m <= n (exclude SQLSRV_SQLTYPE_VARCHAR('max'))
3. From a varchar(m) column to a SQLSRV_SQLTYPE_CHAR(n) output parameter where m, n == any value
4. From a varchar(m) column to a SQLSRV_SQLTYPE_VARCHAR(n) output parameter where m, n == any value
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$dataTypes = array("char", "varchar", "varchar(max)");
$lengths = array(1, 8, 64, 512, 4096, 8000);
$sqlTypes = array("SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_VARCHAR('max')");
$sqltypeLengths = $lengths;
$directions = array("SQLSRV_PARAM_OUT", "SQLSRV_PARAM_INOUT");
$inputValue = "d";

$conn = AE\connect();
foreach ($dataTypes as $dataType) {
    $maxcol = strpos($dataType, "(max)");
    foreach ($lengths as $m) {
        if ($maxcol) {
            $typeFull = $dataType;
        } else {
            $typeFull = "$dataType($m)";
        }
        echo "\nTesting $typeFull:\n";
            
        // create and populate table containing char(m) or varchar(m) columns
        $tbname = getTempTableName("test_" . str_replace(array('(', ')'), '', $dataType) . $m, false);
        $colMetaArr = array(new AE\ColumnMeta($typeFull, "c1", null, false));
        AE\createTable($conn, $tbname, $colMetaArr);
        $stmt = AE\insertRow($conn, $tbname, array($colMetaArr[0]->colName => $inputValue));
        
        // create a stored procedure and sql string for calling the stored procedure
        $spname = 'selectAllColumns';
        createProc($conn, $spname, "@c1 $typeFull OUTPUT", "SELECT @c1 = c1 FROM $tbname");
        $sql = AE\getCallProcSqlPlaceholders($spname, 1);
        
        // retrieve by specifying SQLSRV_SQLTYPE_CHAR(n) or SQLSRV_SQLTYPE_VARCHAR(n) as SQLSRV_PARAM_OUT or SQLSRV_PARAM_INOUT
        foreach ($directions as $dir) {
            echo "Testing as $dir:\n";
            foreach ($sqlTypes as $sqlType) {
                $maxsqltype = strpos($sqlType, "max");
                foreach ($sqltypeLengths as $n) {
                    $sqltypeconst;
                    $sqltypeFull;
                    if ($maxsqltype) {
                        $sqltypeconst = SQLSRV_SQLTYPE_VARCHAR('max');
                        $sqltypeFull = $sqlType;
                    } else {
                        $sqltypeconst = call_user_func($sqlType, $n);
                        $sqltypeFull = "$sqlType($n)";
                    }
                    
                    $c1 = '';
                    $stmt = sqlsrv_prepare($conn, $sql, array(array(&$c1, constant($dir), null, $sqltypeconst)));
                    $r = sqlsrv_execute($stmt);
                    
                    // check the case when SQLSRV_SQLTYPE length (n) is not the same as the column length (m)
                    // with AE: should not work
                    // without AE: should work, except when a SQLSRV_SQLTYPE_VARCHAR length (n) is less than a char column length (m)
                    if (($n != $m || $maxsqltype || $maxcol) && !($maxcol && $maxsqltype)) {
                        if (AE\isDataEncrypted()) {
                            if ($r !== false) {
                                echo "AE: Conversion from $typeFull to output $sqltypeFull should not be supported\n";
                            } else {
                                if (sqlsrv_errors()[0]['SQLSTATE'] != "22018") {
                                    echo "AE: Conversion from $typeFull to output $sqltypeFull expects an operand type clash error, actual error is incorrect\n";
                                    var_dump(sqlsrv_errors());
                                }
                            }
                        } else {
                            if (!AE\isColEncrypted() && strpos($sqltypeFull, "VARCHAR") !== false && $dataType == "char" && $m > $n  && strpos($sqltypeFull, "max") === false && $dir == "SQLSRV_PARAM_OUT") {
                                if ($r !== false) {
                                    echo "Conversions from $typeFull to output $sqltypeFull should not be supported\n";
                                    var_dump($c1);
                                }
                            } else {
                                if ($r === false) {
                                    if (strpos($sqltypeFull, "VARCHAR") !== false || $dataType != "char" || $m <= $n) {
                                        echo "Conversions from $typeFull to output $sqltypeFull should be supported\n";
                                    }
                                }
                                if (trim($c1) != $inputValue) {
                                    echo "Conversion from $typeFull to output $sqltypeFull causes data corruption\n";
                                }
                            }
                        }
                    // check the case then SQLSRV_SQLTYPE length (n) is the same as the column length (m)
                    // should work with AE or non AE
                    } else {
                        if ($r === false) {
                            echo "Conversion from $typeFull to output $sqltypeFull should be supported\n";
                        } else {
                            if (trim($c1) == $inputValue) {
                                echo "****Conversion from $typeFull to output $sqltypeFull is supported****\n";
                           } else {
                                echo "Conversion from $typeFull to output $sqltypeFull causes data corruption\n";
                           }
                        }
                    }
                    // cleanup
                    sqlsrv_free_stmt($stmt);
                }
            }
        }
        dropProc($conn, $spname);
        dropTable($conn, $tbname);
    }
}
sqlsrv_close($conn);
                
?>
--EXPECT--
Testing char(1):
Testing as SQLSRV_PARAM_OUT:
****Conversion from char(1) to output SQLSRV_SQLTYPE_CHAR(1) is supported****
****Conversion from char(1) to output SQLSRV_SQLTYPE_VARCHAR(1) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from char(1) to output SQLSRV_SQLTYPE_CHAR(1) is supported****
****Conversion from char(1) to output SQLSRV_SQLTYPE_VARCHAR(1) is supported****

Testing char(8):
Testing as SQLSRV_PARAM_OUT:
****Conversion from char(8) to output SQLSRV_SQLTYPE_CHAR(8) is supported****
****Conversion from char(8) to output SQLSRV_SQLTYPE_VARCHAR(8) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from char(8) to output SQLSRV_SQLTYPE_CHAR(8) is supported****
****Conversion from char(8) to output SQLSRV_SQLTYPE_VARCHAR(8) is supported****

Testing char(64):
Testing as SQLSRV_PARAM_OUT:
****Conversion from char(64) to output SQLSRV_SQLTYPE_CHAR(64) is supported****
****Conversion from char(64) to output SQLSRV_SQLTYPE_VARCHAR(64) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from char(64) to output SQLSRV_SQLTYPE_CHAR(64) is supported****
****Conversion from char(64) to output SQLSRV_SQLTYPE_VARCHAR(64) is supported****

Testing char(512):
Testing as SQLSRV_PARAM_OUT:
****Conversion from char(512) to output SQLSRV_SQLTYPE_CHAR(512) is supported****
****Conversion from char(512) to output SQLSRV_SQLTYPE_VARCHAR(512) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from char(512) to output SQLSRV_SQLTYPE_CHAR(512) is supported****
****Conversion from char(512) to output SQLSRV_SQLTYPE_VARCHAR(512) is supported****

Testing char(4096):
Testing as SQLSRV_PARAM_OUT:
****Conversion from char(4096) to output SQLSRV_SQLTYPE_CHAR(4096) is supported****
****Conversion from char(4096) to output SQLSRV_SQLTYPE_VARCHAR(4096) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from char(4096) to output SQLSRV_SQLTYPE_CHAR(4096) is supported****
****Conversion from char(4096) to output SQLSRV_SQLTYPE_VARCHAR(4096) is supported****

Testing char(8000):
Testing as SQLSRV_PARAM_OUT:
****Conversion from char(8000) to output SQLSRV_SQLTYPE_CHAR(8000) is supported****
****Conversion from char(8000) to output SQLSRV_SQLTYPE_VARCHAR(8000) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from char(8000) to output SQLSRV_SQLTYPE_CHAR(8000) is supported****
****Conversion from char(8000) to output SQLSRV_SQLTYPE_VARCHAR(8000) is supported****

Testing varchar(1):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varchar(1) to output SQLSRV_SQLTYPE_CHAR(1) is supported****
****Conversion from varchar(1) to output SQLSRV_SQLTYPE_VARCHAR(1) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varchar(1) to output SQLSRV_SQLTYPE_CHAR(1) is supported****
****Conversion from varchar(1) to output SQLSRV_SQLTYPE_VARCHAR(1) is supported****

Testing varchar(8):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varchar(8) to output SQLSRV_SQLTYPE_CHAR(8) is supported****
****Conversion from varchar(8) to output SQLSRV_SQLTYPE_VARCHAR(8) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varchar(8) to output SQLSRV_SQLTYPE_CHAR(8) is supported****
****Conversion from varchar(8) to output SQLSRV_SQLTYPE_VARCHAR(8) is supported****

Testing varchar(64):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varchar(64) to output SQLSRV_SQLTYPE_CHAR(64) is supported****
****Conversion from varchar(64) to output SQLSRV_SQLTYPE_VARCHAR(64) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varchar(64) to output SQLSRV_SQLTYPE_CHAR(64) is supported****
****Conversion from varchar(64) to output SQLSRV_SQLTYPE_VARCHAR(64) is supported****

Testing varchar(512):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varchar(512) to output SQLSRV_SQLTYPE_CHAR(512) is supported****
****Conversion from varchar(512) to output SQLSRV_SQLTYPE_VARCHAR(512) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varchar(512) to output SQLSRV_SQLTYPE_CHAR(512) is supported****
****Conversion from varchar(512) to output SQLSRV_SQLTYPE_VARCHAR(512) is supported****

Testing varchar(4096):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varchar(4096) to output SQLSRV_SQLTYPE_CHAR(4096) is supported****
****Conversion from varchar(4096) to output SQLSRV_SQLTYPE_VARCHAR(4096) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varchar(4096) to output SQLSRV_SQLTYPE_CHAR(4096) is supported****
****Conversion from varchar(4096) to output SQLSRV_SQLTYPE_VARCHAR(4096) is supported****

Testing varchar(8000):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varchar(8000) to output SQLSRV_SQLTYPE_CHAR(8000) is supported****
****Conversion from varchar(8000) to output SQLSRV_SQLTYPE_VARCHAR(8000) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varchar(8000) to output SQLSRV_SQLTYPE_CHAR(8000) is supported****
****Conversion from varchar(8000) to output SQLSRV_SQLTYPE_VARCHAR(8000) is supported****

Testing varchar(max):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****

Testing varchar(max):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****

Testing varchar(max):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****

Testing varchar(max):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****

Testing varchar(max):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****

Testing varchar(max):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
****Conversion from varchar(max) to output SQLSRV_SQLTYPE_VARCHAR('max') is supported****
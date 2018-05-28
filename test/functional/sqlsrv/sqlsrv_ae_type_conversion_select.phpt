--TEST--
Test fetching data by conversion with CAST in the SELECT statement
--DESCRIPTION--
This test checks the allowed data type conversions in SELECT statements under Always Encrypted and non-encrypted
Reference chart for conversions found at https://www.microsoft.com/en-us/download/details.aspx?id=35834
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('sqlsrv_ae_azure_key_vault_common.php');

// These are the errors we expect to see if a conversion fails.
// 22001 String data is right-truncated
// 22003 Numeric value out of range/Overflow converting to numeric type
// 22007 Conversion (date/time from string) failed
// 22018 Conversion not allowed
// 42S22 Column not found
// 6522 .NET Framework error in hierarchyId construction
// 8114 Error converting binary/string type to numeric type
// 8169 Error converting from string to uniqueID
function checkErrors(&$convError)
{
    if ($convError[0][0] != '22018' and
        $convError[0][0] != '22001' and
        $convError[0][0] != '22003' and
        $convError[0][0] != '22007' and
        $convError[0][0] != '42S22' and
        $convError[0][1] != '6522' and
        $convError[0][1] != '8114' and
        $convError[0][1] != '8169') {
        print_r($convError);
        fatalError("Conversion failed with unexpected error message. i=$i, j=$j, v=$v\n");
    }
}
                
// Build the select queries. We want every combination of types for conversion
// testing, so the matrix of queries selects every type from every column
// and convert using CAST.
function formulateSelectQuery($tableName, &$selectQuery, &$selectQueryAE, &$dataTypes)
{
    $numTypes = sizeof($dataTypes);
    
    for ($i = 0; $i < $numTypes; ++$i) {
        $selectQuery[] = array();
        
        // Replace parentheses for column names
        // The column names look like c_binary_256_AE and c_binary_256
        $colnamei = str_replace(array("(", ",", ")"), array("_", "_", ""), $dataTypes[$i]);
        
        for ($j = 0; $j < sizeof($dataTypes); ++$j) {
            $selectQuery[$i][] = "SELECT CAST(c_".$colnamei." AS $dataTypes[$j]) FROM $tableName";
            $selectQueryAE[$i][] = "SELECT CAST(c_".$colnamei."_AE AS $dataTypes[$j]) FROM $tableName";
        }
    }
}

$dataTypes = array ("binary(".STRSIZE.")", "varbinary(".STRSIZE.")", "varbinary(max)", "char(".STRSIZE.")",
                    "varchar(".STRSIZE.")", "varchar(max)", "nchar(".STRSIZE.")", "nvarchar(".STRSIZE.")",
                    "nvarchar(max)", "datetime", "smalldatetime", "date", "time(5)", "datetimeoffset(5)",
                    "datetime2(5)", "decimal(28,4)", "numeric(32,4)", "float", "real", "bigint", "int", 
                    "smallint", "tinyint", "bit", 
                    "binary(".LONG_STRSIZE.")", "varbinary(".LONG_STRSIZE.")", "char(".LONG_STRSIZE.")",
                    "varchar(".LONG_STRSIZE.")", "nchar(".LONG_STRSIZE.")", "nvarchar(".LONG_STRSIZE.")",
                    "time", "datetimeoffset", "datetime2", "decimal(32,4)", "numeric(36,4)"
                    );

// Conversion matrix for SQL types listing allowed conversions for
// non-encrypted data, based on the reference conversion chart
// at https://www.microsoft.com/en-us/download/details.aspx?id=35834
// i = implicit conversion
// e = explicit conversion
// x = conversion not allowed
// @ = not applicable
// c = explicit CAST required
$conversionMatrix = array(array('@','i','i','i','i','i','i','i','i','i','i','e','e','e','e','i','i','x','x','i','i','i','i','i','i','i','i','i','i','i','e','e','e','i','i'),//binary
                          array('i','@','i','i','i','i','i','i','i','i','i','e','e','e','e','i','i','x','x','i','i','i','i','i','i','i','i','i','i','i','e','e','e','i','i'),//varbinary
                          array('i','i','@','i','i','i','i','i','i','i','i','e','e','e','e','i','i','x','x','i','i','i','i','i','i','i','i','i','i','i','e','e','e','i','i'),//varbinary(max)
                          array('e','e','e','@','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','e','e','i','i','i','i','i','i','i','i','i'),//char
                          array('e','e','e','i','@','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','e','e','i','i','i','i','i','i','i','i','i'),//varchar
                          array('e','e','e','i','i','@','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','e','e','i','i','i','i','i','i','i','i','i'),//varchar(max)
                          array('e','e','e','i','i','i','@','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','e','e','i','i','i','i','i','i','i','i','i'),//nchar
                          array('e','e','e','i','i','i','i','@','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','e','e','i','i','i','i','i','i','i','i','i'),//nvarchar
                          array('e','e','e','i','i','i','i','i','@','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','e','e','i','i','i','i','i','i','i','i','i'),//nvarchar(max)
                          array('e','e','e','i','i','i','i','i','i','@','i','i','i','i','i','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','i','i','e','e'),//datetime
                          array('e','e','e','i','i','i','i','i','i','i','@','i','i','i','i','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','i','i','e','e'),//samlldatetime
                          array('e','e','e','i','i','i','i','i','i','i','i','@','x','i','i','x','x','x','x','x','x','x','x','x','e','e','i','i','i','i','x','i','i','x','x'),//date
                          array('e','e','e','i','i','i','i','i','i','i','i','x','@','i','i','x','x','x','x','x','x','x','x','x','e','e','i','i','i','i','i','i','i','x','x'),//time
                          array('e','e','e','i','i','i','i','i','i','i','i','i','i','@','i','x','x','x','x','x','x','x','x','x','e','e','i','i','i','i','i','i','i','x','x'),//datetimeoffset
                          array('e','e','e','i','i','i','i','i','i','i','i','i','i','i','@','x','x','x','x','x','x','x','x','x','e','e','i','i','i','i','i','i','i','x','x'),//datetime2
                          array('i','i','i','i','i','i','i','i','i','i','i','x','x','x','x','c','c','i','i','i','i','i','i','i','i','i','i','i','i','i','x','x','x','c','c'),//decimal
                          array('i','i','i','i','i','i','i','i','i','i','i','x','x','x','x','c','c','i','i','i','i','i','i','i','i','i','i','i','i','i','x','x','x','c','c'),//numeric
                          array('i','i','i','i','i','i','i','i','i','i','i','x','x','x','x','i','i','c','i','i','i','i','i','i','i','i','i','i','i','i','x','x','x','i','i'),//float
                          array('i','i','i','i','i','i','i','i','i','i','i','x','x','x','x','i','i','i','@','i','i','i','i','i','i','i','i','i','i','i','x','x','x','i','i'),//real
                          array('i','i','i','i','i','i','i','i','i','i','i','x','x','x','x','i','i','i','i','@','i','i','i','i','i','i','i','i','i','i','x','x','x','i','i'),//bigint
                          array('i','i','i','i','i','i','i','i','i','i','i','x','x','x','x','i','i','i','i','i','@','i','i','i','i','i','i','i','i','i','x','x','x','i','i'),//int
                          array('i','i','i','i','i','i','i','i','i','i','i','x','x','x','x','i','i','i','i','i','i','@','i','i','i','i','i','i','i','i','x','x','x','i','i'),//smallint
                          array('i','i','i','i','i','i','i','i','i','i','i','x','x','x','x','i','i','i','i','i','i','i','@','i','i','i','i','i','i','i','x','x','x','i','i'),//tinyint
                          array('i','i','i','i','i','i','i','i','i','i','i','x','x','x','x','i','i','i','i','i','i','i','i','@','i','i','i','i','i','i','x','x','x','i','i'),//bit
                          array('i','i','i','i','i','i','i','i','i','i','i','e','e','e','e','i','i','x','x','i','i','i','i','i','@','i','i','i','i','i','e','e','e','i','i'),//binary
                          array('i','i','i','i','i','i','i','i','i','i','i','e','e','e','e','i','i','x','x','i','i','i','i','i','i','@','i','i','i','i','e','e','e','i','i'),//varbinary
                          array('e','e','e','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','e','e','@','i','i','i','i','i','i','i','i'),//char
                          array('e','e','e','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','e','e','i','@','i','i','i','i','i','i','i'),//varchar
                          array('e','e','e','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','e','e','i','i','@','i','i','i','i','i','i'),//nchar
                          array('e','e','e','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','i','e','e','i','i','i','@','i','i','i','i','i'),//nvarchar
                          array('e','e','e','i','i','i','i','i','i','i','i','x','i','i','i','x','x','x','x','x','x','x','x','x','e','e','i','i','i','i','@','i','i','x','x'),//time
                          array('e','e','e','i','i','i','i','i','i','i','i','i','i','i','i','x','x','x','x','x','x','x','x','x','e','e','i','i','i','i','i','@','i','x','x'),//datetimeoffset
                          array('e','e','e','i','i','i','i','i','i','i','i','i','i','i','i','x','x','x','x','x','x','x','x','x','e','e','i','i','i','i','i','i','@','x','x'),//datetime2
                          array('i','i','i','i','i','i','i','i','i','i','i','x','x','x','x','c','c','i','i','i','i','i','i','i','i','i','i','i','i','i','x','x','x','c','c'),//decimal
                          array('i','i','i','i','i','i','i','i','i','i','i','x','x','x','x','c','c','i','i','i','i','i','i','i','i','i','i','i','i','i','x','x','x','c','c'),//numeric
                         );

// The conversion matrix for AE is more restrictive
// y = allowed conversion
// x = not allowed
$conversionMatrixAE = array(array('y','y','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','x','x','x','x','x','x','x','x','x'),//binary
                            array('y','y','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','x','x','x','x','x','x','x','x','x'),//varbinary
                            array('x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x'),//varbinary(max)
                            array('x','x','x','y','y','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','x','x','x','x','x','x','x'),//char
                            array('x','x','x','y','y','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','x','x','x','x','x','x','x'),//varchar
                            array('x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x'),//varchar(max)
                            array('x','x','x','x','x','x','y','y','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','x','x','x','x','x'),//nchar
                            array('x','x','x','x','x','x','y','y','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','x','x','x','x','x'),//nvarchar
                            array('x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x'),//nvarchar(max)
                            array('x','x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x'),//datetime
                            array('x','x','x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x'),//samlldatetime
                            array('x','x','x','x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x'),//date
                            array('x','x','x','x','x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x','x','x','x'),//time
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x','x','x'),//datetimeoffset
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x','x'),//datetime2
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x'),//decimal
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x'),//numeric
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x'),//float
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x'),//real
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x'),//bigint
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x'),//int
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','y','x','x','x','x','x','x','x','x','x','x','x','x','x'),//smallint
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','y','y','x','x','x','x','x','x','x','x','x','x','x','x'),//tinyint
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','y','y','y','x','x','x','x','x','x','x','x','x','x','x'),//bit
                            array('x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','x','x','x','x','x','x','x','x','x'),//binary
                            array('x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','x','x','x','x','x','x','x','x','x'),//varbinary
                            array('x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','x','x','x','x','x','x','x'),//char
                            array('x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','x','x','x','x','x','x','x'),//varchar
                            array('x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','x','x','x','x','x'),//nchar
                            array('x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','y','x','x','x','x','x'),//nvarchar
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x','x','x','x'),//time
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x','x','x'),//datetimeoffset
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x','x'),//datetime2
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y','x'),//decimal
                            array('x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','x','y'),//numeric
                           );

set_time_limit(0);
sqlsrv_configure('WarningsReturnAsErrors', 1);

$connectionInfo = array("CharacterSet"=>"UTF-8");
$conn = AE\connect($connectionInfo);
if (!$conn) {
    fatalError("Could not connect.\n");
}

$tableName = "type_conversion_table";
$columns = array();
$insertQuery = "";

formulateSetupQuery($tableName, $dataTypes, $columns, $insertQuery);

$stmt = AE\createTable($conn, $tableName, $columns);
if (!$stmt) {
    fatalError("Failed to create table $tableName\n");
}

// The data we test against is in values.php
for ($v = 0; $v < sizeof($values); ++$v) {
    // Each value must be inserted twice because the AE and non-AE column are side by side.
    $testValues = array();
    for ($i = 0; $i < sizeof($values[$v]); ++$i) {
        $testValues[] = $values[$v][$i];
        $testValues[] = $values[$v][$i];
    }

    // Insert the data using sqlsrv_prepare()
    $stmt = sqlsrv_prepare($conn, $insertQuery, $testValues);
    if ($stmt == false) {
        fatalError("sqlsrv_prepare failed\n");
    }

    if (!sqlsrv_execute($stmt)) {
        fatalError("sqlsrv_execute failed\n");
    }
    
    sqlsrv_free_stmt($stmt);

    // Formulate the matrix of SELECT queries and iterate over each index.
    $selectQuery = array();
    $selectQueryAE = array();
    formulateSelectQuery($tableName, $selectQuery, $selectQueryAE, $dataTypes);

    for ($i = 0; $i < sizeof($dataTypes); ++$i) {
        for ($j = 0; $j < sizeof($dataTypes); ++$j) {
            $stmt = sqlsrv_query($conn, $selectQuery[$i][$j]);

            if ($stmt == false) {
                $convError = sqlsrv_errors();
                
                checkErrors($convError);
                
                if (AE\isDataEncrypted()) {
                    $stmtAE = sqlsrv_query($conn, $selectQueryAE[$i][$j]);
                    $convError = sqlsrv_errors();
                    
                    // if the non-AE conversion fails, certainly the AE conversion
                    // should fail but only with error 22018 (i.e. conversion not allowed)
                    if ($stmtAE != false) {
                        fatalError("AE conversion should have failed. i=$i, j=$j, v=$v\n\n");
                    }
                    if ($convError[0][0] != '22018') {
                        fatalError("AE conversion failed with unexpected error message. i=$i, j=$j, v=$v\n");
                    }
                }
            } else {
                if ($conversionMatrix[$i][$j] == 'x') {
                    fatalError("Conversion succeeded, should have failed. i=$i, j=$j, v=$v\n");
                }
                
                if (AE\isDataEncrypted()) {
                    $stmtAE = sqlsrv_query($conn, $selectQueryAE[$i][$j]);
                    
                    // Check every combination of statement value and conversion.
                    // The last else if block covers the case where the select
                    // query worked and the retrieved values are compared.
                    if ($stmtAE == false) {
                        if ($conversionMatrixAE[$i][$j] == 'x') {
                            $convError = sqlsrv_errors();
                            if ($convError[0][0] != '22018') {
                                fatalError("AE conversion failed with unexpected error message. i=$i, j=$j, v=$v\n");
                            }
                        } else { // $conversionMatrixAE[$i][$j] == 'y'
                            fatalError("AE conversion failed, should have succeeded. i=$i, j=$j, v=$v\n");
                        }
                    } else { // query succeeded 
                        if ($conversionMatrixAE[$i][$j] == 'x') {
                            fatalError("AE conversion succeeded, should have failed. i=$i, j=$j, v=$v\n");
                        } elseif ($conversionMatrixAE[$i][$j] == 'y') {
                            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
                            $rowAE = sqlsrv_fetch_array($stmtAE, SQLSRV_FETCH_NUMERIC);
                            
                            // rtrim strips whitespace from the end of the string, which 
                            // takes care of a bug where some conversions lead to extraneous
                            // whitespace padding the end of the string
                            if (is_string($row[0])) {
                                $row[0] = rtrim($row[0]);
                                $rowAE[0] = rtrim($rowAE[0]);
                            }
                            
                            if ($row[0] != $rowAE[0]) {
                                echo "Values do not match! i=$i, j=$j, v=$v\n";
                                print_r($row[0]);
                                print_r($rowAE[0]);
                                echo "\n";
                            }
                        }
                    }
                }
            }
        }
    }
    
    $deleteQuery = "TRUNCATE TABLE $tableName";
    $stmt = sqlsrv_query($conn, $deleteQuery);
    if ($stmt == false) {
        fatalError("Delete statement failed");
    }
    
    echo "Step $v done\n";
}

dropTable($conn, $tableName);

sqlsrv_close($conn);

echo "Test successful\n";
?>
--EXPECT--
Step 0 done
Step 1 done
Step 2 done
Step 3 done
Step 4 done
Step 5 done
Step 6 done
Step 7 done
Step 8 done
Step 9 done
Step 10 done
Step 11 done
Step 12 done
Step 13 done
Step 14 done
Step 15 done
Step 16 done
Step 17 done
Step 18 done
Step 19 done
Step 20 done
Test successful

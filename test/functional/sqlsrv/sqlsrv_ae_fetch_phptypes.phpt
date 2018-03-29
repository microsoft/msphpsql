--TEST--
Test insert data and fetch as all possible php types
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
require_once('tools.inc');
require_once('values.php');

// Set up the columns and build the insert query. Each data type has an
// AE-encrypted and a non-encrypted column side by side in the table.
function FormulateSetupQuery($tableName, &$dataTypes, &$columns, &$insertQuery, $strsize, $strsize2)
{
    $columns = array();
    $queryTypes = "(";
    $queryTypesAE = "(";
    $valuesString = "VALUES (";
    $numTypes = sizeof($dataTypes);

    for ($i = 0; $i < $numTypes; ++$i) {
        // Replace parentheses for column names
        $colname = str_replace(array("(", ",", ")"), array("_", "_", ""), $dataTypes[$i]);
        $columns[] = new AE\ColumnMeta($dataTypes[$i], "c_".$colname."_AE");
        $columns[] = new AE\ColumnMeta($dataTypes[$i], "c_".$colname, null, true, true);
        $queryTypes .= "c_"."$colname, ";
        $queryTypes .= "c_"."$colname"."_AE, ";
        $valuesString .= "?, ?, ";
    }

    $queryTypes = substr($queryTypes, 0, -2).")";
    $valuesString = substr($valuesString, 0, -2).")";

    $insertQuery = "INSERT INTO $tableName ".$queryTypes." ".$valuesString;
}

$SQLSRV_PHPTYPE_CONST = array(SQLSRV_PHPTYPE_INT,
                              SQLSRV_PHPTYPE_DATETIME,
                              SQLSRV_PHPTYPE_FLOAT,
                              SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY),
                              SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR),
                              SQLSRV_PHPTYPE_STREAM("UTF-8"),
                              SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY),
                              SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR),
                              SQLSRV_PHPTYPE_STRING("UTF-8")
                              );

// Two sizes for the string types so we can test conversion from
// a shorter type to a longer type
$strsize = 256;
$strsize2 = 384;

$dataTypes = array ("binary($strsize)", "varbinary($strsize)", "varbinary(max)", "char($strsize)",
                    "varchar($strsize)", "varchar(max)", "nchar($strsize)", "nvarchar($strsize)",
                    "nvarchar(max)", "datetime", "smalldatetime", "date", "time(5)", "datetimeoffset(5)",
                    "datetime2(5)", "decimal(28,4)", "numeric(32,4)", "float", "real", "bigint", "int", 
                    "smallint", "tinyint", "bit", 
                    "binary($strsize2)", "varbinary($strsize2)", "char($strsize2)",
                    "varchar($strsize2)", "nchar($strsize2)", "nvarchar($strsize2)",
                    "time", "datetimeoffset", "datetime2", "decimal(32,4)", "numeric(36,4)"
                    );

set_time_limit(0);
sqlsrv_configure('WarningsReturnAsErrors', 1);

// Connect
$connectionInfo = array("CharacterSet"=>"UTF-8");
$conn = AE\connect($connectionInfo);
if (!$conn) {
    fatalError("Could not connect.\n");
}

$tableName = "type_conversion_table";
$columns = array();
$insertQuery = "";

FormulateSetupQuery($tableName, $dataTypes, $columns, $insertQuery, $strsize, $strsize2);

$stmt = AE\createTable($conn, $tableName, $columns);
if (!$stmt) {
    fatalError("Failed to create table $tableName\n");
}
    
// The data we test against is in values.php
for ($v = 0; $v < sizeof($values);++$v)
{
    // Each value must be inserted twice because the AE and non-AE column are side by side.
    $testValues = array();
    for ($i=0; $i<sizeof($values[$v]); ++$i) {
        $testValues[] = $values[$v][$i];
        $testValues[] = $values[$v][$i];
    }

    // Insert the data using sqlsrv_prepare()
    // Insert one set of data for each PHPTYPE
    $stmt = sqlsrv_prepare($conn, $insertQuery, $testValues);
    if ($stmt == false) {
        print_r(sqlsrv_errors());
        fatalError("sqlsrv_prepare failed\n");
    }

    for ($i = 0; $i < sizeof($SQLSRV_PHPTYPE_CONST); ++$i) {
        if (sqlsrv_execute($stmt) == false) {
            print_r(sqlsrv_errors());
            fatalError("sqlsrv_execute failed\n");
        }
    }

    $selectQuery = "SELECT * FROM $tableName";

    // Two select statements for selection using 
    // sqlsrv_get_field and sqlsrv_fetch_array
    $stmt = sqlsrv_query($conn, $selectQuery);
    if ($stmt == false) {
        print_r(sqlsrv_errors());
        fatalError("First sqlsrv_prepare failed\n");
    }
    
    $stmt2 = sqlsrv_query($conn, $selectQuery);
    if ($stmt2 == false) {
        print_r(sqlsrv_errors());
        fatalError("Second sqlsrv_prepare failed\n");
    }

    $numFields = sqlsrv_num_fields($stmt);

    $i = 0;
    $valueAE = null;
    $valueFromArrayAE = null;
    
    while ($result = sqlsrv_fetch($stmt)) {
        $dataArray = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_NUMERIC);
        
        for ($j = 0; $j < $numFields; $j++) {
            $value = sqlsrv_get_field($stmt, $j, $SQLSRV_PHPTYPE_CONST[$i]);
            $valueFromArray = $dataArray[$j];
            
            // PHPTYPE_STREAM returns a PHP resource, so check the type
            if (is_resource($value)) $value = get_resource_type($value);

            // For each type, the AE values come first and non-AE values second
            // So let's do the comparison every second field
            if ($j%2 == 0) {
                $valueAE = $value;
                $valueFromArrayAE = $valueFromArray;
            } elseif ($j%2 == 1) {
                // If returning a DateTime PHP type from a date only SQL type,
                // PHP adds the current timestamp to make a DateTime object,
                // and in this case the AE and non-AE times may be off by a 
                // fraction of a second since they are retrieved at ever-so-slightly
                // different times. This not a test-failing discrepancy, so 
                // below the DateTime objects are made equal again for the next if
                // block.
                if ($value instanceof DateTime) {
                    // date_diff returns a DateInterval object, and s is
                    // the difference in seconds. s should be zero because
                    // the difference should be just a fraction of a second.
                    $datediff = date_diff($value, $valueAE);
                    $diff = $datediff->s; 
                    
                    if ($diff == 0) {
                        $value = $valueAE;
                    }
                }
                
                if ($valueAE != $value or $valueFromArrayAE != $valueFromArray) {
                    echo "Values do not match! PHPType $i Field $j\n";
                    print_r($valueAE);echo "\n";
                    print_r($value);echo "\n";
                    print_r($valueFromArrayAE);echo "\n";
                    print_r($valueFromArray);echo "\n";
                    print_r(sqlsrv_errors());
                    fatalError("Test failed, values do not match.\n");
                }
            }
        }
        ++$i;
    }
    
    sqlsrv_free_stmt($stmt);
    sqlsrv_free_stmt($stmt2);
    
    $deleteQuery = "DELETE FROM $tableName";
    $stmt = sqlsrv_query($conn, $deleteQuery);
    if ($stmt == false) {
        print_r(sqlsrv_errors());
        fatalError("Delete statement failed");
    }
    
    sqlsrv_free_stmt($stmt);
}

dropTable($conn, $tableName);

sqlsrv_close($conn);

echo "Test successful\n";
?>
--EXPECT--
Test successful

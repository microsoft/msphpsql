<?php
require_once('MsCommon.inc');
require_once('values.php');

// Set up the columns and build the insert query. Each data type has an
// AE-encrypted and a non-encrypted column side by side in the table.
// If column encryption is not set in MsSetup.inc, this function simply
// creates two non-encrypted columns side-by-side for each type.
function formulateSetupQuery($tableName, &$dataTypes, &$columns, &$insertQuery)
{
    $columns = array();
    $queryTypes = "(";
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

// Create the table and insert the data, then retrieve it back and make
// sure the encrypted and non-encrypted values are identical.
function insertDataAndVerify($conn, $tableName, $dataTypes, $values)
{
    $columns = array();
    $insertQuery = "";

    // Generate the INSERT query
    formulateSetupQuery($tableName, $dataTypes, $columns, $insertQuery);

    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    // Duplicate all values for insertion - one is encrypted, one is not
    $testValues = array();
    for ($n = 0; $n < sizeof($values); ++$n) {
        $testValues[] = $values[$n];
        $testValues[] = $values[$n];
    }

    // Prepare the INSERT query
    // This is never expected to fail
    $stmt = sqlsrv_prepare($conn, $insertQuery, $testValues);
    if ($stmt == false) {
        print_r(sqlsrv_errors());
        fatalError("sqlsrv_prepare failed\n");
    }

    // Execute the INSERT query
    // This should not fail since our credentials are correct
    if (sqlsrv_execute($stmt) == false) {
        $errors = sqlsrv_errors();
        fatalError("INSERT query execution failed with good credentials.\n");
    } else {
        // Get the data back and compare encrypted and non-encrypted versions
        $selectQuery = "SELECT * FROM $tableName";

        $stmt1 = sqlsrv_query($conn, $selectQuery);
        $data = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_NUMERIC);

        if (sizeof($data) != 2*sizeof($dataTypes)) {
            fatalError("Incorrect number of fields returned.\n");
        }

        for ($n = 0; $n < sizeof($data); $n += 2) {
            if ($data[$n] != $data[$n + 1]) {
                echo "Failed on field $n: ".$data[$n]." ".$data[$n + 1]."\n";
                fatalError("AE and non-AE values do not match.\n");
            }
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_free_stmt($stmt1);
    }

    // Drop the table
    dropTable($conn, $tableName);
}
?>

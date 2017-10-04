--TEST--
Test for binding output parameter of encrypted values for a sample emplolyee table
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = AE\connect();

$tbname = 'employee';
$colMetaArr = array( new AE\ColumnMeta("int", "PersonID", "NOT NULL Identity (1,1)"),
                     new AE\ColumnMeta("varchar(255)", "FirstName", "NOT NULL"),
                     new AE\ColumnMeta("varchar(255)", "LastName"),
                     new AE\ColumnMeta("varchar(255)", "Address"),
                     new AE\ColumnMeta("varchar(255)", "City"));
AE\createTable($conn, $tbname, $colMetaArr);

// Create a Store Procedure
$spname = 'InOutRet_Params';
$createSpSql = "CREATE PROCEDURE $spname (
                @pPersonID int, @MatchingRecs int OUTPUT) AS
                SELECT PersonID, FirstName, LastName, Address, City
                FROM $tbname WHERE PersonID=@pPersonID;
                SELECT @MatchingRecs=count(*) FROM $tbname WHERE
                PersonID=@pPersonID;
                RETURN 100";
sqlsrv_query($conn, $createSpSql);

// Create a select Store Procedure
$sspname = 'getInfo';
$selectSpSql = "CREATE PROCEDURE $sspname (
                @FirstName varchar(255) OUTPUT, @LastName varchar(255) OUTPUT,
                @Address varchar(255) OUTPUT, @City varchar(255) OUTPUT, @PersonID int) AS
                SELECT @FirstName = FirstName, @LastName = LastName, @Address = Address, @City = City
                FROM $tbname WHERE PersonID=@PersonID";
sqlsrv_query($conn, $selectSpSql);

// Insert data
$firstNameParams = array( 'Luke', 'Tahir', 'Gwen', 'Mike', 'Sarah' );
$lastNameParams = array( 'Duke', 'Chaudry', 'Wheeler', 'Leibskind', 'Ackerman' );
$addressParams = array( '2130 Boars Nest', '83 First Street', '842 Vine Ave.', '33 Elm St.', '440 U.S. 11' );
$cityParams = array( 'Hazard Co', 'Brooklyn', 'New York', 'Binghamton', 'Roselle' );
for ($i = 0; $i < 5; $i++) {
    $inputs = array( "FirstName" => $firstNameParams[$i],
                     "LastName" => $lastNameParams[$i],
                     "Address" => $addressParams[$i],
                     "City" => $cityParams[$i] );
    $stmt = AE\insertRow($conn, $tbname, $inputs);
}

// call Store Procedure
$callSpSql = "{? = CALL $spname (?, ?)}";
$retParam = 0;
$pPersonID = 1;
$cbOutParam = 0;
$stmt = sqlsrv_prepare($conn, $callSpSql, array( array( &$retParam, SQLSRV_PARAM_OUT ), array( $pPersonID, SQLSRV_PARAM_IN ), array( &$cbOutParam, SQLSRV_PARAM_OUT )));
sqlsrv_execute($stmt);
sqlsrv_next_result($stmt);
if (sqlsrv_errors()) {
    var_dump(sqlsrv_errors());
}
print("retParam: " . $retParam . "\n");
print("pPersonID: " . $pPersonID . "\n");
print("cbOutParam: " . $cbOutParam . "\n");

// Retrieve all data through output params
$outSql = AE\getCallProcSqlPlaceholders($sspname, 5);
$firstNameOut = '';
$lastNameOut = '';
$addressOut = '';
$cityOut = '';
$pPersonID = 2;
$stmt = sqlsrv_prepare($conn, $outSql, array( array( &$firstNameOut, SQLSRV_PARAM_OUT ), array( &$lastNameOut, SQLSRV_PARAM_OUT ), array( &$addressOut, SQLSRV_PARAM_OUT ), array( &$cityOut, SQLSRV_PARAM_OUT ), array( $pPersonID, SQLSRV_PARAM_IN )));
sqlsrv_execute($stmt);
if (sqlsrv_errors()) {
    var_dump(sqlsrv_errors());
}

print("firstNameOut: " . $firstNameOut . "\n");
print("lastNameOut: " . $lastNameOut . "\n");
print("addressOut: " . $addressOut . "\n");
print("cityOut: " . $cityOut . "\n");
print("pPersonID: " . $pPersonID . "\n");

sqlsrv_query($conn, "DROP PROCEDURE $spname");
sqlsrv_query($conn, "DROP PROCEDURE $sspname");
sqlsrv_query($conn, "DROP TABLE $tbname");
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECT--
retParam: 100
pPersonID: 1
cbOutParam: 1
firstNameOut: Tahir
lastNameOut: Chaudry
addressOut: 83 First Street
cityOut: Brooklyn
pPersonID: 2

--TEST--
Test errorInfo when prepare with and without emulate prepare
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once( "MsCommon.inc" );

$conn = connect();
$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

//drop, create and insert
$tbname = "test_table";
create_table( $conn, $tbname, array( new columnMeta( "int", "c1" ), new columnMeta( "int", "c2" )));

insert_row( $conn, $tbname, array( "c1" => 1, "c2" => 10 ));
insert_row( $conn, $tbname, array( "c1" => 2, "c2" => 20 ));

echo "\n****testing with emulate prepare****\n";
// Do not support emulate prepare with Always Encrypted
if ( !is_col_encrypted() )
    $stmt = $conn->prepare( "SELECT c2 FROM $tbname WHERE c1= :int", array(PDO::ATTR_EMULATE_PREPARES => true ));
else
    $stmt = $conn->prepare( "SELECT c2 FROM $tbname WHERE c1= :int" );

$int_col = 1;
//bind param with the wrong parameter name to test for errorInfo
$stmt->bindParam(':in', $int_col);
$stmt->execute();

$stmt_error = $stmt->errorInfo();
if ( !is_col_encrypted() )
{
    if ( $stmt_error[0] != "HY093" )
    {
        echo "SQLSTATE should be HY093 when Emulate Prepare is true.\n";
        print_r( $stmt_error );
    }
}
else
{
    if ( $stmt_error[0] != "07002" )
    {
        echo "SQLSTATE should be 07002 for syntax error in a parameterized query.\n";
        print_r( $stmt_error );
    }
}

$conn_error = $conn->errorInfo();
if ( $conn_error[0] != "00000" )
{
    echo "Connection error SQLSTATE should be 00000.\n";
    print_r( $conn_error );
}

echo "\n****testing without emulate prepare****\n";
$stmt2 = $conn->prepare("SELECT c2 FROM $tbname WHERE c1= :int", array(PDO::ATTR_EMULATE_PREPARES => false));

$int_col = 2;
//bind param with the wrong parameter name to test for errorInfo
$stmt2->bindParam(':it', $int_col);
$stmt2->execute();

$stmt_error = $stmt2->errorInfo();
if ( $stmt_error[0] != "07002" )
{
    echo "SQLSTATE should be 07002 for syntax error in a parameterized query.\n";
    print_r( $stmt_error );
}

$conn_error = $conn->errorInfo();
if ( $conn_error[0] != "00000" )
{
    echo "Connection error SQLSTATE should be 00000.\n";
    print_r( $conn_error );
}

DropTable( $conn, $tbname );
unset( $stmt );
unset( $stmt2 );
unset( $conn );
?>
--EXPECTREGEX--
\*\*\*\*testing with emulate prepare\*\*\*\*

Warning: PDOStatement::execute\(\): SQLSTATE\[HY093\]: Invalid parameter number: parameter was not defined in .+(\/|\\)pdo_errorinfo_emulateprepare\.php on line [0-9]+

Warning: PDOStatement::execute\(\): SQLSTATE\[HY093\]: Invalid parameter number in .+(\/|\\)pdo_errorinfo_emulateprepare\.php on line [0-9]+

\*\*\*\*testing without emulate prepare\*\*\*\*

Warning: PDOStatement::bindParam\(\): SQLSTATE\[HY093\]: Invalid parameter number: parameter was not defined in .+(\/|\\)pdo_errorinfo_emulateprepare\.php on line [0-9]+

Warning: PDOStatement::execute\(\): SQLSTATE\[07002\]: COUNT field incorrect: 0 \[Microsoft\]\[ODBC Driver 1[0-9] for SQL Server\]COUNT field incorrect or syntax error in .+(\/|\\)pdo_errorinfo_emulateprepare\.php on line [0-9]+
--TEST--
Test for fetching integer columns with column encryption
--SKIPIF--
--FILE--
<?php
include 'MsCommon.inc';
include 'AEData.inc';
include 'MsSetup.inc';

$conn = Connect(array("ColumnEncryption"=>"Enabled"));
//$conn = Connect();

// create table
$tbname = GetTempTableName("", false);
$dataTypes = array("bigint", "int", "smallint");
$encTypes = array("norm", "det", "rand");
$dataTypes_str = "";
$col_names = array();
foreach ($dataTypes as $dataType){
    foreach ($encTypes as $encType) {
        $col_name = $encType . $dataType;
        $dataTypes_str = $dataTypes_str . "[" . $col_name . "] " . $dataType . ", ";
        array_push($col_names, $col_name);
    }
}
$dataTypes_str = rtrim($dataTypes_str, ", ");
CreateTableEx( $conn, $tbname, $dataTypes_str);
    
// populate table
$data_arr = array_merge( array_slice($bigint_params, 0, 3), array_slice($int_params, 0, 3), array_slice($smallint_params, 0, 3) );
$data_str = implode(", ", $data_arr);
sqlsrv_query( $conn, "INSERT INTO $tbname VALUES ( $data_str )");
    
// encrypt columns
$dir_name = realpath(dirname(__FILE__));
$enc_name = $dir_name . DIRECTORY_SEPARATOR . "encrypttable.ps1";
$col_name_str = implode(",", $col_names);
$runCMD = "powershell -executionPolicy Unrestricted -file " . $enc_name . " " . $server . " " . $database . " " . $userName . " " . $userPassword . " " . $tbname . " " . $col_name_str;
$retval = shell_exec($runCMD);

//Fetch encrypted values with ColumnEncryption Enabled
$sql = "SELECT * FROM $tbname";
$stmt = sqlsrv_query($conn, $sql);
$decrypted_row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);

var_dump($decrypted_row);

DropTable($conn, $tbname);
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECT--
array(9) {
  [0]=>
  string(10) "2147483648"
  [1]=>
  string(19) "-922337203685479936"
  [2]=>
  string(18) "922337203685479936"
  [3]=>
  int(32768)
  [4]=>
  int(-2147483647)
  [5]=>
  int(2147483647)
  [6]=>
  int(256)
  [7]=>
  int(-32767)
  [8]=>
  int(32767)
}
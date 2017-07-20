<?php
include 'MsCommon.inc';
include 'AEData.inc';
include 'MsSetup.inc';

try{
    $conn = Connect( array("ColumnEncryption"=>"Enabled"));

    // create table
    $tbname = GetTempTableName("", false);
    $dataTypes = array("bigint", "int", "smallint");
    $encTypes = array("norm", "det", "rand");
    $dataTypes_str = "";
    $col_names = array();
    foreach ($dataType in $dataTypes){
        foreach ($encType in $encTypes) {
            $col_name = $encType + $dataType;
            $dataTypes_str = $dataTypes_str + "[" + $col_name + "] " + $dataTypes + ", ";
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
    $col_name_str = implode($col_names);
    $runCMD = "powershell -executionPolicy Unrestricted encrypttable.ps1 " . $server . " " . $database . " " . $userName . " " . $userPassword . " " . $tbname . " " . $col_name_str;
    shell_exec($runCMD);

    DropTable($conn, $tbname);
    sqlsrv_close($conn);
}
?>
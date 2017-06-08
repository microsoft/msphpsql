<?php
function AEConnect($ae){
    $databaseName = 'tempdb';
    $uid = 'yourUsername';
    $pwd = 'yourPassword';
    $server = 'yourServer';
    $ae_str = "Disabled";
    if ($ae)
        $ae_str = "Enabled";
    $connectionInfo = array("Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd,
                            "ReturnDatesAsStrings"=>true, "CharacterSet"=>"UTF-8", "ColumnEncryption"=>$ae_str);
    $conn = sqlsrv_connect($server, $connectionInfo);
    cond_assert($conn === false, "Could not connect.\n");
    return ($conn);
}

function DropAETable($conn){
    $sql_drop = "IF OBJECT_ID('dbo.test_AE', 'U') IS NOT NULL DROP TABLE [dbo].[test_AE]";
    $stmt = sqlsrv_query($conn, $sql_drop);
    cond_assert($stmt === false, "Error in dropping table.\n");
}

function extract_type($type, &$typename, &$option) {
    $type = preg_replace('/\s+/', '', $type);
    $typename = $type;
    $option = '';
    $param_pos = strpos($type, '(');
    if ($param_pos){
        $typename = substr($type, 0, $param_pos);
        $option = substr($type, $param_pos);
    }
}

function table_str($typename, $option, $encode) {
    $encDet = "ENCRYPTED WITH (ENCRYPTION_TYPE = DETERMINISTIC, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256', COLUMN_ENCRYPTION_KEY = CEK1) NULL, ";
    $encRand = "ENCRYPTED WITH (ENCRYPTION_TYPE = RANDOMIZED, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256', COLUMN_ENCRYPTION_KEY = CEK1) NULL";
    $uctype = ucfirst($typename);
    return "[norm" . $uctype . "][" . $typename . "] " . $option . " NULL, " .
           "[encDet" . $uctype . "][" . $typename . "] " . $option . " " . $encode . " " . $encDet .
           "[encRand" . $uctype . "][" . $typename . "] " . $option . " " . $encRand;
    
}

function CreateAETableTypes($conn, $types){
    $encDet = "ENCRYPTED WITH (ENCRYPTION_TYPE = DETERMINISTIC, 
               ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256', 
               COLUMN_ENCRYPTION_KEY = CEK1) NULL, ";
    $encRand = "ENCRYPTED WITH (ENCRYPTION_TYPE = RANDOMIZED, 
                ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256', 
                COLUMN_ENCRYPTION_KEY = CEK1) NULL";
    $sql_create = "CREATE TABLE [dbo].[test_AE]([Id] [int] IDENTITY(1,1)";
    
    foreach ($types as $type) {
        $sql_create .= ", ";
        $typename = "";
        $option = "";
        extract_type($type, $typename, $option);
        switch($typename){
            case "nvarchar":
            case "nchar":
            case "char":
            case "varchar":
                $sql_create .= table_str($typename, $option, "COLLATE Latin1_General_BIN2");
                break;
            case "int":
            case "bigint":
            case "smallint":
            case "tinyint":
            case "float":
            case "real":
            case "datetime2":
            case "date":
            case "datetime":
            case "datetimeoffset":
            case "smalldatetime":
            case "time":
            case "bit":
            case "decimal":
            case "numeric":
            case "money":
            case "smallmoney":
            case "binary":
            case "varbinary":
                $sql_create .= table_str($typename, $option, NULL);
                break;
        }
    }
    
    $sql_create .= " PRIMARY KEY CLUSTERED ([Id] ASC) ON [PRIMARY]) ";
    
    $stmt = sqlsrv_query($conn, $sql_create);
    cond_assert($stmt === false, "Error in creating table.\n");
}

function CreateAETable($conn){
    CreateAETableTypes($conn, "nvarchar", "int", "float", "datetime2", "bit");
    $sql_insert = "INSERT INTO [dbo].[test_AE] ([normVarchar], [encDetVarchar], [encRandVarchar],
                                                [normInt], [encDetInt], [encRandInt],
                                                [normFloat], [encDetFloat], [encRandFloat],
                                                [normDateTime], [encDetDateTime], [encRandDateTime],
                                                [normBoolean], [encDetBoolean], [encRandBoolean]) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $params1 = array("1234-78910", "encrypted varchar", "another randomized encrypted varchar", 20, 21, 23, 30.1, 30.2, 30.3, '1970-3-4', '1971-3-4', '1972-3-4', true, true, true);
    $params2 = array("4567-78910", "additional encrypted varchar", "test randomized encrypted varchar", 10, 11, 13, 10.1, 10.2, 10.3, '1980-3-4', '1981-3-4', '1982-3-4', true, true, true);
    $stmt1 = sqlsrv_query($conn, $sql_insert, $params1);
    cond_assert($stmt1 === false, "Error in populating table.\n");
    $stmt2 = sqlsrv_query($conn, $sql_insert, $params2);
    cond_assert($stmt2 === false, "Error in populating table.\n");
}

function cond_assert($cond, $msg) {
    if ($cond) {
        echo $msg;
        die(print_r(sqlsrv_errors(), true));
    }
}
?>
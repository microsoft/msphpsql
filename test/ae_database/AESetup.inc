<?php
function AEConnect($ae){
    $databaseName = 'AEDemo';
    $uid = 'yourUsername';
    $pwd = 'yourPassword';
    $server = 'yourServer';
    $ae_str = "Disabled";
    if ($ae)
        $ae_str = "Enabled";
    $connectionInfo = array("Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd,
                            "ReturnDatesAsStrings"=>true, "CharacterSet"=>"UTF-8", "ColumnEncryption"=>$ae_str);
    $conn = sqlsrv_connect($server, $connectionInfo);
    sqlsrv_assert($conn === false, "Could not connect.\n");
    return ($conn);
}

function DropAETable($conn){
    $sql_drop = "IF OBJECT_ID('dbo.test_AE', 'U') IS NOT NULL DROP TABLE [dbo].[test_AE]";
    $stmt = sqlsrv_query($conn, $sql_drop);
    sqlsrv_assert($stmt === false, "Error in dropping table.\n");
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
        switch($type){
            case "nvarchar":
                $sql_create .= "[normNvarchar][nvarchar] (50) NOT NULL,
                                [encDetNvarchar] [nvarchar] (50) COLLATE Latin1_General_BIN2
                                $encDet
                                [encRandNvarchar] [nvarchar] (50)
                                $encRand";
                break;
            case "nvarcharmax":
                $sql_create .= "[normNvarcharmax][nvarchar] (max) NOT NULL,
                                [encDetNvarcharmax] [nvarchar] (max) COLLATE Latin1_General_BIN2
                                $encDet
                                [encRandNvarcharmax] [nvarchar] (max)
                                $encRand";
                break;
            case "nchar":
                $sql_create .= "[normNchar][nchar] (10) NULL,
                                [encDetNchar] [nchar] (10) COLLATE Latin1_General_BIN2
                                $encDet
                                [encRandNchar] [nchar] (10)
                                $encRand";
                break;
            case "char":
                $sql_create .= "[normChar][char] (10) NULL,
                                [encDetChar] [char] (10) COLLATE Latin1_General_BIN2
                                $encDet
                                [encRandChar] [char] (10)
                                $encRand";
                break;
            case "varchar":
                $sql_create .= "[normVarchar][varchar] (50) NULL,
                                [encDetVarchar] [varchar] (50) COLLATE Latin1_General_BIN2
                                $encDet
                                [encRandVarchar] [varchar] (50)
                                $encRand";
                break;
            case "varcharmax":
                $sql_create .= "[normVarcharmax][varchar] (max) NULL,
                                [encDetVarcharmax] [varchar] (max) COLLATE Latin1_General_BIN2
                                $encDet
                                [encRandVarcharmax] [varchar] (max)
                                $encRand";
                break;
            case "int":
                $sql_create .= "[normInt] [int] NULL,
                                [encDetInt] [int] 
                                $encDet
                                [encRandInt] [int] 
                                $encRand";
                break;
            case "bigint":
                $sql_create .= "[normBigint] [bigint] NULL,
                                [encDetBigint] [bigint] 
                                $encDet
                                [encRandBigint] [bigint] 
                                $encRand";
                break;
            case "smallint":
                $sql_create .= "[normSmallint] [smallint] NULL,
                                [encDetSmallint] [smallint] 
                                $encDet
                                [encRandSmallint] [smallint] 
                                $encRand";
                break;
            case "tinyint":
                $sql_create .= "[normTinyint] [tinyint] NULL,
                                [encDetTinyint] [tinyint] 
                                $encDet
                                [encRandTinyint] [tinyint] 
                                $encRand";
                break;
            case "float":
                $sql_create .= "[normFloat] [float] NULL,
                                [encDetFloat] [float]
                                $encDet
                                [encRandFloat] [float] 
                                $encRand";
                break;
            case "real":
                $sql_create .= "[normReal] [real] NULL,
                                [encDetReal] [real]
                                $encDet
                                [encRandReal] [real] 
                                $encRand";
                break;
            case "datetime2":
                $sql_create .= "[normDatetime2] [datetime2] NULL,
                                [encDetDatetime2] [datetime2] 
                                $encDet
                                [encRandDatetime2] [datetime2] 
                                $encRand";
                break;
            case "date":
                $sql_create .= "[normDate] [date] NULL,
                                [encDetDate] [date] 
                                $encDet
                                [encRandDate] [date] 
                                $encRand";
                break;
            case "datetime":
                $sql_create .= "[normDatetime] [datetime] NULL,
                                [encDetDatetime] [datetime] 
                                $encDet
                                [encRandDatetime] [datetime] 
                                $encRand";
                break;
            case "datetimeoffset":
                $sql_create .= "[normDatetimeoffset] [datetimeoffset] NULL,
                                [encDetDatetimeoffset] [datetimeoffset] 
                                $encDet
                                [encRandDatetimeoffset] [datetimeoffset] 
                                $encRand";
                break;
            case "smalldatetime":
                $sql_create .= "[normSmalldatetime] [smalldatetime] NULL,
                                [encDetSmalldatetime] [smalldatetime] 
                                $encDet
                                [encRandSmalldatetime] [smalldatetime] 
                                $encRand";
                break;
            case "time":
                $sql_create .= "[normTime] [time] NULL,
                                [encDetTime] [time] 
                                $encDet
                                [encRandTime] [time] 
                                $encRand";
                break;
            case "bit":
                $sql_create .= "[normBit] [bit] NULL,
                                [encDetBit] [bit]
                                $encDet
                                [encRandBit] [bit] 
                                $encRand";
                break;
            case "decimal":
                $sql_create .= "[normDecimal] [decimal] (18, 5) NULL,
                                [encDetDecimal] [decimal] (18, 5)
                                $encDet
                                [encRandDecimal] [decimal] (18, 5)
                                $encRand";
                break;
            case "numeric":
                $sql_create .= "[normNumeric] [numeric] (18, 5) NULL,
                                [encDetNumeric] [numeric] (18, 5)
                                $encDet
                                [encRandNumeric] [numeric] (18, 5)
                                $encRand";
                break;
            case "money":
                $sql_create .= "[normMoney] [money] NULL,
                                [encDetMoney] [money]
                                $encDet
                                [encRandMoney] [money] 
                                $encRand";
                break;
            case "smallmoney":
                $sql_create .= "[normSmallmoney] [smallmoney] NULL,
                                [encDetSmallmoney] [smallmoney]
                                $encDet
                                [encRandSmallmoney] [smallmoney] 
                                $encRand";
                break;
            case "binary":
                $sql_create .= "[normBinary] [binary] (10) NULL,
                                [encDetBinary] [binary] (10)
                                $encDet
                                [encRandBinary] [binary] (10)
                                $encRand";
                break;
            case "varbinary":
                $sql_create .= "[normVarbinary] [varbinary] (50) NULL,
                                [encDetVarbinary] [varbinary] (50)
                                $encDet
                                [encRandVarbinary] [varbinary] (50)
                                $encRand";
                break;
            case "varbinarymax":
                $sql_create .= "[normVarbinarymax] [varbinary] (max) NULL,
                                [encDetVarbinarymax] [varbinary] (max)
                                $encDet
                                [encRandVarbinarymax] [varbinary] (max)
                                $encRand";
                break;
        }
    }
    
    $sql_create .= " PRIMARY KEY CLUSTERED ([Id] ASC) ON [PRIMARY]) ";
    
    $stmt = sqlsrv_query($conn, $sql_create);
    sqlsrv_assert($stmt === false, "Error in creating table.\n");
}

function CreateAETable($conn){
    CreateAETableTypes($conn, "nvarchar", "int", "float", "datetime2", "bit");
}

function PopulateAETable($conn){
    $sql_insert = "INSERT INTO [dbo].[test_AE] ([normVarchar], [encDetVarchar], [encRandVarchar],
                                                [normInt], [encDetInt], [encRandInt],
                                                [normFloat], [encDetFloat], [encRandFloat],
                                                [normDateTime], [encDetDateTime], [encRandDateTime],
                                                [normBoolean], [encDetBoolean], [encRandBoolean]) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $params1 = array("1234-78910", "encrypted varchar", "another randomized encrypted varchar", 20, 21, 23, 30.1, 30.2, 30.3, '1970-3-4', '1971-3-4', '1972-3-4', true, true, true);
    $params2 = array("4567-78910", "additional encrypted varchar", "test randomized encrypted varchar", 10, 11, 13, 10.1, 10.2, 10.3, '1980-3-4', '1981-3-4', '1982-3-4', true, true, true);
    $stmt1 = sqlsrv_query($conn, $sql_insert, $params1);
    sqlsrv_assert($stmt1 === false, "Error in populating table.\n");
    $stmt2 = sqlsrv_query($conn, $sql_insert, $params2);
    sqlsrv_assert($stmt2 === false, "Error in populating table.\n");
}

function sqlsrv_assert($cond, $msg) {
    if ($cond) {
        echo $msg;
        die(print_r(sqlsrv_errors(), true));
    }
}
?>
--TEST--
fetch columns using fetch mode
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

include 'MsCommon.inc';

function FetchMode_GetAllColumnsEx()
{
    include("MsSetup.inc");
    
    set_time_limit(0);
    $tableName = GetTempTableName();
    
    $conn = new PDO( "sqlsrv:server=$server;database=$databaseName", $uid, $pwd);
    
    $stmt = $conn->exec("CREATE TABLE $tableName ([c1_int] int, [c2_tinyint] tinyint, [c3_smallint] smallint, [c4_bigint] bigint, [c5_bit] bit, [c6_float] float, [c7_real] real, [c8_decimal] decimal(28,4), [c9_numeric] numeric(32,4), [c10_money] money, [c11_smallmoney] smallmoney, [c12_char] char(512), [c13_varchar] varchar(512), [c14_varchar_max] varchar(max), [c15_nchar] nchar(512), [c16_nvarchar] nvarchar(512), [c17_nvarchar_max] nvarchar(max), [c18_text] text, [c19_ntext] ntext, [c20_binary] binary(512), [c21_varbinary] varbinary(512), [c22_varbinary_max] varbinary(max), [c23_image] image, [c24_uniqueidentifier] uniqueidentifier, [c25_datetime] datetime, [c26_smalldatetime] smalldatetime, [c27_timestamp] timestamp, [c28_xml] xml, [c29_time] time, [c30_date] date, [c31_datetime2] datetime2, [c32_datetimeoffset] datetimeoffset)");
    
    $numRows = 0;
    $query = GetQuery($tableName, ++$numRows);
    $stmt = $conn->query($query);
    
    $query = GetQuery($tableName, ++$numRows);
    $stmt = $conn->query($query);

    $stmt = $conn->prepare("SELECT * FROM $tableName ORDER BY c27_timestamp");   
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 0); 
    $result = $stmt->execute();   
    $meta = $stmt->getColumnMeta(0);
    $colName = $meta['name'];

    include("pdo_tools.inc");
    //  Fetching with fetch mode PDO::FETCH_ASSOC
    echo "\nComparing data in column 0 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 0);
        $value = $row[$colName];
        CompareData($stmt, $i, 0, $value, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 1); 
    $result = $stmt->execute();   
    $meta = $stmt->getColumnMeta(1);
    $colName = $meta['name'];
    
    //  Fetching with fetch mode PDO::FETCH_ASSOC
    echo "\nComparing data in column 1 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 1);
        $value = $row[$colName];
        CompareData($stmt, $i, 1, $value, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 2); 
    $result = $stmt->execute();   
    
    //  Fetching with fetch mode PDO::FETCH_NUM
    echo "\nComparing data in column 2 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 2);
        $value = $row[2];
        CompareData($stmt, $i, 2, $value, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 3); 
    $result = $stmt->execute();   
    
    //  Fetching with fetch mode PDO::FETCH_COLUMN
    echo "\nComparing data in column 3 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_COLUMN);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 3);
        CompareData($stmt, $i, 3, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 4); 
    $result = $stmt->execute();   
    
    //  Fetching with fetch mode PDO::FETCH_COLUMN
    echo "\nComparing data in column 4 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_COLUMN);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 4);
        CompareData($stmt, $i, 4, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 5); 
    $result = $stmt->execute();   
    
    //  Fetching with the default fetch mode
    echo "\nComparing data in column 5 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch();
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 5);
        CompareData($stmt, $i, 5, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 6); 
    $result = $stmt->execute();   
    
    //  Fetching with fetch mode PDO::FETCH_BOTH
    echo "\nComparing data in column 6 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_BOTH);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 6);
        $value = $row[6];
        CompareData($stmt, $i, 6, $value, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 7); 
    $result = $stmt->execute();   
    
    //  Fetching with fetch mode PDO::FETCH_COLUMN
    echo "\nComparing data in column 7 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_COLUMN);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 7);
        CompareData($stmt, $i, 7, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 8); 
    $result = $stmt->execute();   
    
    //  Fetching with fetch mode PDO::FETCH_COLUMN
    echo "\nComparing data in column 8 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_COLUMN);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 8);
        CompareData($stmt, $i, 8, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 9); 
    $result0 = $stmt->execute();  
    
    //  Fetching with fetch mode PDO::FETCH_NUM
    echo "\nComparing data in column 9 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 9);
        $value = $row[9];
        CompareData($stmt, $i, 9, $value, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 10);    
    $result1 = $stmt->execute();  
    $meta = $stmt->getColumnMeta(10);
    $colName = $meta['name'];
    
    //  Fetching with fetch mode PDO::FETCH_ASSOC
    echo "\nComparing data in column 10 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 10);
        $value = $row[$colName];
        CompareData($stmt, $i, 10, $value, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 11);    
    $result2 = $stmt->execute();  
    
    //  Fetching with fetch mode PDO::FETCH_NUM
    echo "\nComparing data in column 11 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 11);
        $value = $row[11];
        CompareData($stmt, $i, 11, $value, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 12);    
    $result3 = $stmt->execute();  
    $meta = $stmt->getColumnMeta(12);
    $colName = $meta['name'];
    
    //  Fetching with fetch mode PDO::FETCH_ASSOC
    echo "\nComparing data in column 12 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 12);
        $value = $row[$colName];
        CompareData($stmt, $i, 12, $value, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 13);    
    $result4 = $stmt->execute();  
    
    //  Fetching with the default fetch mode
    echo "\nComparing data in column 13 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch();
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 13);
        CompareData($stmt, $i, 13, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 14);    
    $result5 = $stmt->execute();  
    
    //  Fetching with the default fetch mode
    echo "\nComparing data in column 14 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch();
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 14);
        CompareData($stmt, $i, 14, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 15);    
    $result6 = $stmt->execute();  
    $meta = $stmt->getColumnMeta(15);
    $colName = $meta['name'];
    
    //  Fetching with fetch mode PDO::FETCH_ASSOC
    echo "\nComparing data in column 15 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 15);
        $value = $row[$colName];
        CompareData($stmt, $i, 15, $value, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 16);    
    $result7 = $stmt->execute();  
    $meta = $stmt->getColumnMeta(16);
    $colName = $meta['name'];
    
    //  Fetching with fetch mode PDO::FETCH_ASSOC
    echo "\nComparing data in column 16 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 16);
        $value = $row[$colName];
        CompareData($stmt, $i, 16, $value, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 17);    
    $result8 = $stmt->execute();  
    
    //  Fetching with fetch mode PDO::FETCH_COLUMN
    echo "\nComparing data in column 17 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_COLUMN);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 17);
        CompareData($stmt, $i, 17, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 18);    
    $result9 = $stmt->execute();  
    
    //  Fetching with fetch mode PDO::FETCH_NUM
    echo "\nComparing data in column 18 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 18);
        $value = $row[18];
        CompareData($stmt, $i, 18, $value, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 19);    
    $result0 = $stmt->execute();  
    
    //  Fetching with the default fetch mode
    echo "\nComparing data in column 19 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch();
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 19);
        CompareData($stmt, $i, 19, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 20);    
    $result1 = $stmt->execute();  
    $meta = $stmt->getColumnMeta(20);
    $colName = $meta['name'];
    
    //  Fetching with fetch mode PDO::FETCH_ASSOC
    echo "\nComparing data in column 20 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 20);
        $value = $row[$colName];
        CompareData($stmt, $i, 20, $value, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 21);    
    $result2 = $stmt->execute();  
    
    //  Fetching with the default fetch mode
    echo "\nComparing data in column 21 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch();
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 21);
        CompareData($stmt, $i, 21, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 22);    
    $result3 = $stmt->execute();  
    
    //  Fetching with fetch mode PDO::FETCH_COLUMN
    echo "\nComparing data in column 22 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_COLUMN);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 22);
        CompareData($stmt, $i, 22, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 23);    
    $result4 = $stmt->execute();  
    
    //  Fetching with fetch mode PDO::FETCH_COLUMN
    echo "\nComparing data in column 23 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_COLUMN);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 23);
        CompareData($stmt, $i, 23, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 24);    
    $result5 = $stmt->execute();  
    
    //  Fetching with the default fetch mode
    echo "\nComparing data in column 24 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch();
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 24);
        CompareData($stmt, $i, 24, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 25);    
    $result6 = $stmt->execute();  
    
    //  Fetching with the default fetch mode
    echo "\nComparing data in column 25 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch();
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 25);
        CompareData($stmt, $i, 25, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 26);    
    $result7 = $stmt->execute();  
    
    //  Fetching with fetch mode PDO::FETCH_COLUMN
    echo "\nComparing data in column 26 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_COLUMN);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 26);
        CompareData($stmt, $i, 26, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 27);    
    $result8 = $stmt->execute();  
    
    //  Fetching with the default fetch mode
    echo "\nComparing data in column 27 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch();
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 27);
        CompareData($stmt, $i, 27, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 28);    
    $result9 = $stmt->execute();  
    $meta = $stmt->getColumnMeta(28);
    $colName = $meta['name'];
    
    //  Fetching with fetch mode PDO::FETCH_ASSOC
    echo "\nComparing data in column 28 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 28);
        $value = $row[$colName];
        CompareData($stmt, $i, 28, $value, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 29);    
    $result0 = $stmt->execute();  
    
    //  Fetching with the default fetch mode
    echo "\nComparing data in column 29 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch();
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 29);
        CompareData($stmt, $i, 29, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 30);    
    $result1 = $stmt->execute();  
    
    //  Fetching with fetch mode PDO::FETCH_COLUMN
    echo "\nComparing data in column 30 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_COLUMN);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 30);
        CompareData($stmt, $i, 30, $row, $data);
    }
     
    $stmt->closeCursor();  
    $stmt->setFetchMode(PDO::FETCH_COLUMN, 31);    
    $result2 = $stmt->execute();  
    
    //  Fetching with fetch mode PDO::FETCH_NUM
    echo "\nComparing data in column 31 and rows \n";
    for ($i = 1; $i <= $numRows; $i++)
    {
        echo $i . "\t";
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $query = GetQuery($tableName, $i);
        $data = GetColumnData($stmt, $query, $i, 31);
        $value = $row[31];
        CompareData($stmt, $i, 31, $value, $data);
    }
    $stmt = null;  
    $conn = null;   
}

function GetQuery($tableName, $index)
{
    $query = "";
    switch ($index)
    {
        case 1:
            $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_varchar_max], [c15_nchar], [c16_nvarchar], [c17_nvarchar_max], [c18_text], [c19_ntext], [c20_binary], [c21_varbinary], [c22_varbinary_max], [c23_image], [c24_uniqueidentifier], [c25_datetime], [c26_smalldatetime], [c28_xml], [c29_time], [c30_date], [c31_datetime2], [c32_datetimeoffset]) VALUES ((-1), (null), (-13744), (1), (0), (1.79E+308), (1), (0.9132), (0.4765), (0.8474), (-214748.3648), ('£¢,å.uÐh.ý/BrýBãarýa,UåO/Üßh@<ÐuU+Oär.rvAß¢©O_ÄO,_ãýbUo.ÖO<¢.~£öUOäãßU¢ªaÐüª¢raîb¢Üý¢ÐoýrÜCað~ä:oÃ.ý©ä.hîuý*v~Ü_O~|ßß©z_aä£AC¢Ã:å@/ðßAuuu¢b.ÖãÄ£ýªã+ab£>,åüßr,aZ||<ÜhäÐo+z|O~ðÜOhýA_Üz/ßåö|ðb/aä¢ßãO+ö@håÃaÃ@:ð<BÜ©u*<@uC©îrzßBözÃ~ßbªßr<å©BzÐv/aBßvÖß@hvªbÄbzãb~b,ãåC,@ö<Ö<ß/î,'), ('uýßO_O/_>_Chobª@U+:|îÐZzvAb*ãÃÃZ~h¢bÜåä©:ªåýßzª/rð©AöðZ@Ä>ð>CBÃßãß~A:_Ð,>ßCZãZ@ö:£CðA£+©ãB,vü'), ('*îBhaÖ|üÃOv_oßý©.ýüb>ýÜÜr<ðO*o</hUÜ.Ðo@rå©:ÐÖBrää/üZýzA+C*ö_ã+£_Ðä.îA~öU>Öoã|baBvb*îOO/ßu.Z.rü,_aîaåÄZßv>ÐßUoOßbzävÜAÖ*|:~Ã¢Z_A_,¢ã£bC,üub.<,O£Aªrb+Äª>v~::C¢OüüÃvä_+OaO<Z_ü*åovö£AB:ÃßÜh|bªÃv+bB:ÜCzC©.Oåå/b@ðÃZoOühå@a<B.ßC/@Oð>åîCbößý£î|ß.Ä¢¢,z@b,îröÜ©uÄävÜ,Ã>ª£A~ur+ý@ýörb.ÜOÖCýC|~©ö*.ÐBh+COBîCüªßBZÐ.,uý~ªãöÃ_©.~öaýbuß>UãoäÜBoZß~üaÃ,hr:üÜßîo:ã<Ü@üBý@|vß<|u+u</_hB|£Ärßß_C:a~B,*.ý~rOßb.Öäð~üBÐz,+A+vãbzhZ<.BUu,OîüUåu+zAoåävuã:,h£ubAb~ðUbå+ÃZ>£|.bC>/haÐ*Börbð<aaü¢azhã,*£O>ü*å,rÐãÜzîÐ>r*Äö£Ärh/+©*å¢uUuÖoÜö@Öýu|azbÄÃCuhßü:åÖ¢/bb¢©_@ª>~_~Ðö_Ã~ÄÐbÄîbÐ©|BÐßUß*ý_+å<hzÃA.ß>oC,ZªB.Ðo©ð_a*öC:ßÜUÜrªrª£u/.AbZb~|_OßhÄäBozªßåCOrUU¢~Ðü@OÄöäüzh¢£</ß.ð¢£b,©AvªÜÖCuOÜß©>ãªAaCOäîÜAÃü+<zÜv¢ª|ObðuªðOÖÐh+ßüü~O,ð@:r>îýª¢.Zß:ðhrBZßÃß/OaCo£©åa,åh.*Co+Ähð£ü_åövruªä©ß@ª@*:B*v*Ä¢a/©b~AbÖª£Öh:©Coî/:vhÐb¢ßãßÄU,åªö*@<vr£O|CboÜ<bÃzð/ãî/>Z+AvÖU*@uÖoOBh©îÃßUÐa£ðäzÜB,zöbÃ@ärBUðîör¢ã.ªvü_©AÖb£Ã<£/oöýhüOZÄ<ÃUð|£Öîz_.ß@vbß~Ðvrabß,~ÃbýðßU/|ÄhªÄ¢C.ýo*obäîîîO~uîbÃ/ää+å|Üü@ZýUßC:Bö¢åräÄ©*ÐÜObÖua@<aÐÖäUuãrOãåbªBÜ<b¢¢AäãÄO_£B,ÄuZv<Üß.ÃUÃ,ba©åßUC©.ª©ª,>ªoa|z¢C<@ö|ªªãZ,ÜãðUýäoã.v_:üB~Ãîýo¢,hðbU~@ÐzO©v,Aãß©h>ÖýOobBÃ>.+äÃ¢Ã©ý£ðäý.+:@r¢UbövÜAÜ>/¢rýäý~BB+<>AªÄAråßhB,|©ö:ã¢_hªãðhvbß£bå/ZUbbÄ~©,ðbåu¢.Ab_A/hö~üÜ¢~îz©U|ßðÄßh<¢uäoU¢bÄ¢ZÐuüU¢£Ð>ÐvZ/hÃ+Ð©@bZðªîªîß~üÃöîbavobÃýöOOubäOrboß_ö|£z,h*,+ßbÜßß+u.å@vAÃ~åCÃ_b/ÄÖ.hoý+/ÄåAÖz£hB/:v>ößr©|>UüÖrÄªZö£bÃ¢¢ßýßÄý>_Ðb/ßÃUU|>î_Öª©Ö.©>O/:+ýB/ßß>>hBuoAÜr_ü@<+Ð<Ö@öAübªhußbb+b*+å/Öb*ßz~|å_äÃüÐbÄîuª<Üä:îýå/Bö.Crª£Aª©Cör,<b@Ð:Ubý+<ãÐîC~zo*ðå<¢</©B*Ã>AÜZ.BzÜðo|¢*C~öZ<Ä@<v>ÜazîäªBb+ð.@*Ã_*ßßß,aßz|+A_CO,ßrr|aZBr:OãBOÄöªUvÖhðÐ©î>ýÄÜ_ãbüåýBOZÜÃUU~*¢|îªªªÖÜv+b:B|zbaª*©¢Cärr¢ÜZ<~@å*,b*~,Coßbßªã_a£ýÐ©£ððÐ@ÃÃ,ýß:ZÃäoCÐ@ü<£>oå~zU,Ã¢hzÖîäü/£_©îýC**ÜCÖÃ/vhaUAoÜAor/zÄ+Ãh|ÜýOÃbüZ~£+ß|ßO@Ãv*az>åÄr,>*U~ZÜ©rr*Ü>ÐroÄÃ,ßö>+að¢:AÃßbåZýz~£bý+ªh+ªãÐr|hãªß@åUîhz*üäÐ>/C@Ö¢'), (N':ª>|åUvA+/ß,ÖÖBZðZ:ß|uCå~ßCbÄ~©Ö>,¢Ü/.ÜOB_ä<__ÐÃArãzzzðu>ªýbhO*ÜÖAî,Czä@@UÐA+£*AB/b>Ð+/o:.Zã/ÄbîrbÄ>¢*|Ð+*ßbªîäãUöýÄhãb.ªÐðÐaABßrßßvðã/Öå*h.~,~|_:~>Ä*Ü,Azîå.ßÄ*uüäÜb~ð.@uãßÄ£ãuo,O|:ªBåA:U¢ö_+b£ßoª@ß>ü_oü>@ýÃ.<©ÐA¢hå£äýÖ£*z:ýäÜoî<uÄ,åuAZ£¢+bÜãhZß'), (N'ð_Ð_bä¢uÄý¢ÐßÜðuö*h.BªýU¢Ãå>ooo_*Ãüð@O*A¢ß~£ý¢Uýîî+<OA,*_>ð+üCb|ßB.hßýÐÜaBÜZa*Uý:å~vßÄ*ZZ£.avüb>ÖAãîuCOîo~:BUB*OuððB|ÜÐBÜBäoªÄ@bbZ_ý@+Ö*Ä©BuUUbrzrß*büðüZÐhhUAz:b.Aö|+Ãåo~.åÃ@ä@b£ä.ðoaB+~ÃÜu¢b©+£härB£ü@îo<|bZßå¢B¢Oî_ð,öªý©ð©|@Ð@A+ýzöaåîAý|_zvîå¢@<><Cu>U@'), (null), (null), (N'öã|v/îZh¢Zý@ðã/vÄÖAAAbubîh@b:*~zÜ,ªÃÜý<ýz,är<¢Z£ãuö_|rªîª>,Z.UÄvÄÃÐ|UÐ@ÐvbÐ©rAªvãäuu,AÖ|ÜÄbv¢O~bÃ~@ÜåÖ.BÄuU@>öÄ~îB:_a>î©ðOÜ_©UCB<£ü¢_ß,B<C@oßUbãa@+@ÖbbrÃãäÄüä:<CoAãübbBÄ*bîüÖå©Cð_håª*/ªÜöÃüÃhª©Ab¢uuåð:vß.üouöüOªAzZª@ß¢UOh+hAr<uöÄv:îb¢Äªßö:/|aÐÜbBv,:_ßªüÄÜbhÜr@ðZUÃÐÄýÄªýÐåoß£h£+öBOä>o<ãð+ã¢<aÐö>vÖAb,C~Ü<ßî:ob£:ßßBzbßbuåÜ£äü_Ährz,,ªZ|ý¢bA,~.ÜvOO©O££/<Ãßo¢<ÄßðbÖuãÄvã>vBÄaÜÄ£<a+©Üä_@~îîÃßhö@îUBZßOð~ð+Oð_ö.ÃýoÃªCAAb|U£ðß~A+zªßÖ©/ãZbÜÖ.Ãªü£>uÄ¢:+.ärAÐ||aß+*ß¢öh©A|*._*Z¢,üZa:~ä.îüOð_£ÃvÖbý,bäüa>ãOhZbßb~ª£Öv||u@ðð©ÜrC.äb©:+ð|_ýv@¢,ÄBähC+öýöýr@ÖåoðzUZÄßvu.,Ä£ACÜaAöäßßb_£ãO.ðb<ö¢A.ª©auî~:å<Ü/Ðîåoýo~åB*äßª:_,*Oö:B¢£@BýßAZ+ö¢ÜuCv_ÜbZ+ÐöbÐå/Ü,OBÃ_*zBv+ba*|ªÐ.Ua,zUä~C£b,©ªÜr|b£BvUb:ðaaoÄAößCr©<äA:Cz@~åUö:©ªä:Zî><ho~<zUÃåbÃvU:ðZ+@ª@ÜÖÃrOü~*~ãª£î¢Ð@zZ~hå£¢>rýÃýÐZ@ðÄ,ÐÐ<ü.>ÖîCb>A+£Ð.ýbö:£hhOarv~ªv*©ö_îab*åB.ü@~ao£bvzZ:ýUÖ~ªvüOroZãÄßBãÐÄ*©OãbÃ£|oã|uZZüî©Ãß*/ß,Äåu©©äObßz~ü£vhrbðABC£ýäaäßo.A.Z|Z>üßaüÃýÜÖîÐz*£@>Ð.,ÃUbå+£ßbA©Üðv/+ßO,bü+C*åBhÄ_O£îzýîäA/B:,o£üCýü<åüã*CÃ:A~ðOßuövå,Ü:ð,|z+Bð©ßÐouîCÃß|*Zß£_åvCuZr:îÖ~+_Ab*£>UOa©z/ÃZ©|CÄ_Büß.@ZrhßÐuîü~å+bª>îäA¢¢å|ä:ö££zOu>,Ð£~b:*<<ÜÜ+äßð~ü£ßÃb+O@ö/ÜOobîÄÖB>£:a:<@~oãCra£abÃÃ,_:A_h£¢©hz,åb¢ÖzvhüO*o<O*.Ö/hOC,uzr©<Bz,ãß+UÜübªU*ÐîÐ>b,üãrÐåO_üra£Ð.BãOã~+_ßr/+*oOß:r©ÐvãÃ¢î~>£U,bÜ*zOh>|bhC@|Ö.ã.ÐýÐ_ý*ð<~.uvvbÐv/o©AaüA*åo_ªÖvörboÖ£_äb/.äªÐvZÜb.UhªÖß,Üö.©ßßzÐz<aOB*bÖ~bu¢o,Zb~.<O@C+<vßýÄhOU£BCZvvB©Ð*@~@@ou,£*b/*CuOB_îz*,ª,ßª_AbAß<ÐÖäý|ãüC|ªbßBãzüîBoÐr+.üîOÃî¢rz¢_¢Ä~vu£v|ßÖÐzßvýZ~Cýäbv++îB+hb>+Z/v>hÐÄÃ<îO.ª©£BðÄ¢bbzÜ>**u,CZC@z./î|ßã~å:@öååÜÃUa:/:/vü©Üoß>*|OÜÃBOUÖ|CåAO£ßßª<||CBãÃbåC:uÜ£ab_å*orÜ~:><z|.U£oãoÃvör:*b¢ubß@Ü>äü£Ö<£.ÃýB¢£_h£¢>Z,ªöÖC*:aB~|@©ýÃßÃ:ªîÄrÜýZä~ßßöüª~@CO_CãzZ:|ãh<:£öÄ<rA~~Oãö<vÖ¢roªüðoä@Z~ªZªU£öäß.öäB<Ü~aðð+~£~|©U.uO|ZÜ:/î¢Ðý*|åB©äßÜCÃã,ðuðhÄðAÐðaUÄÃUCo/©~oãaª,<>üÄÖîhÄ,ß£bäZÐü~ab*ZO/.Ö£+:CÃ|î£_Ãrü*ýAä_*å¢bZCöª~ãövbZb~üB|ÄªÄUÐ/z¢@aUAA<Üã_ßÃ+u>_CÄö+:,h,+å<oð££å£ý>:ªzßðßz:_ßZÖzrb~_ßbåU+¢åh~ZãråîA+ÖbðßîA|hö¢ªbhoüäbCB~CUbü/©+@<u£åU,~ohß£Ü@ÐzÖaßa.*ýoÜß:U@üö+uÃb©ÄüÐßÖ~ßÐv>*b,özü¢©öO>båbßCbOO/.B:bªßZavã_vîZ<+å~©ýÖ~ÐäO£b£a¢_£_ãbU¢z*_©¢.C©uoª¢:U>@rÃß+ªåüÐ©ý£zåÐbruÖ|uCzhO¢rü<>A~+AC*oUåA:,ßa¢ÖßÜ>B,,ÃÄv.å_£Ð,î>u£/vvÜ.îö*ÄBäzz@/ß:>+B£å©ÖÐ<¢¢o£|å~,r<_ýbr/bý¢.b|arðö/CCý_¢_böv*Ob|üÖözüZ:.o©ªîb*£îª/UÄhÖ:,ÃäaðhðBäðb:*¢ÄU¢>aü<.£|O/A©ðö+/rZ|¢BZ@Uu~Ðüýîzvåo¢rCßýübZß@BUOð~£uAbbh*ýÜÐîÃ©~vBÜßÖ*oZ/zª~ß<aoü,ß.*ÃZrßavu*bBZ,Ð|/*<:~.*ßÄ<@+åü¢Ü@äUu~ß|ýÐböü,uZrbîªß>ðuýýîªÐZ©,UÐZ~:C>ü¢Ü>Ð@<,/>Ozvý,ßv~¢vUÃrãÃbÄÃuB£äb~öübarÐ@ª>B|ý,,äöaýßäUv.öß©ßßozãzuÄb/Öv+åÖ£rBvÃ©zãvb©Ar*©~Cr|ðÐ:ovuÖÃ*:rÖ¢öC¢bZ©Ca@üåuoZA+,ä£bßüarbãböüü,b*ABBÐªv:b|/Ü|ðBöÐ*öª~Ãß'), (0xEC6077ADB74D7703D8702004DF829BCEDB649DCC4D2D2261447BAC0823FC3E796832A4884FB435E03B46CBFE47B3A046C323AECAB31AF1BE3D16683BA16EE0DC31A79CADDE127E272EBC910662231990DA6545004D73C54D40E54B394BFA3FCC66EECDA016F368B0A5879BF689EA5C1AFDD8603B28E08EBEE14F6CF8C745DB347DC4A073567879E7FD955BC6D7CEC35588313C739903A386600EBFC7866FE85FAC6779C54AE9C3845F783D9385C8E6128C176F9608679A79211A1247B9B2618EADFB197055FE0B9F6DB71420C50F019828AC3A6F), (0x5744CCFFA330D67BCBAC17AFE689AE75BC8E0BCF2851537AB311187A1F0C77F18E5BA4D4A4555ADAC9F2EC6709162977E9B3EFAC63302805F1524C92175B5CBE3426218BE5D9D0673331063664EB709DE7A1906B1CCB8E5D329EE35209C7C36D952D32E2F49E9110E288783229F2A105E1AEAB75CAF0A26997C35847F57E5E6C6D99A036B88DA84910808994525B3440FDDE715A1C4DA2A58BC94B3145491A642A0CC490516A4C530661654A660E173C8F184A170C034F20F6FBABEF427703C66525694697A3EE92911E54BAE8573E7DB20FA22CFE035727BCA49955BE7BE0B38B03E2C2EAD8B28E008FA25E0101545038E32F5C7423916799EF906D3E61EEBAFD0E85447E5AD00588E308B185A84E1D1BD2E26C4B833F68727B29FB48454F9EA0E761F37B10D2DD132AD4901BDFE1F0A172516B7F826E79CE1DD10331D4BD43936A109E475AA20B00045821AEAF4D9399395AD5F3E41E1D79E7245D3ECEAC3F6D91FCB1CE43A946D046), (0x294DCA1ED03E6AF879E750AF4BF2255F3544CC325719B31DB39BC4558F1405AC78A3F32B1C068D0063B4C586414C65DC1E49108B7938231099AD6B347A146865E1A337A7FEF9422FB7CCCEA3542DDC6635E83745037A323F80E3926F9A58854E4B5FA49D15BEDD2407E0D4AC697FBB6BD104FE92DD23616770F7261B67D3955E478057F5893E51770A123E23A586D50128269E955AF37128FD3C2D1E98C2BD334B7565E927C6E292B38787CAE43EC1EA2125F24035FF03530E2FF74C1132DE48550BD82F20ECD961430BE1D26BA643BF7A42FD73CF7CFD4B7112DAF8CE958180A83E75D96691CA344B91FC291D31E768EB38D2CE356B79D80D8CBD0E11E092AE8A612A2D316A59E878C8273E80D32E402A720D96C3B24B3CC59DCBD9D3BC236BFE4E9FBC07BA3680DFE9566D443B58395E7DE39FD3FCEBD715ED67949E7F14BCA5BB40313471396F3B8CB7D95CDDF88A44927B56D883980163C7A61953A2E25FD49AC888FB2605EC4374634644117B38F4DD4F0C03BEE321233ECE6CCB1F1C99AA5705C215708F1E8D8F13642051BC514C6E257652D55C16B6A764BC5798202357C99CED0CB923844D317F74AA7F00D2817C205DFAA646D4FBEF0B6D0BD2C6F46EEF0D57B5CF45924AA0282156E76B37A6923BDA77C328B082C7F21CADB5797B6A8A05BB72A91CB1378E125A7E257D1383D4C67896EB7DAAC75CD4EDDD28E59426ADB7CCE1B0C4FD7589A3E9B0CD325D4ADE99D8EF00E47E0D5AF315D42FD40EA87D33D06EB131D30FC9030D4794FAF92977A3FE8F0CA526C938BF87BCAF2946B49540339A62EF98ADC6C826FDD7377860FAEC5D8BD67F9BD70D0735F1AA16609FBC58B253C65FC7880A50C9447F29F2014626C2C2CCBADE15A55B8C5AB9A602AF6786407E8425999B24D48185B55757E41534AB84549ED3017A9CAD6BD26CCF634D1ADCF9E8737C208C269DF738585F4E03A84584FB7E5AC9B23F04E7782EC8B8051E379B7A5DFBA1B88BF72D00DCC8213C5F6729264A67CFBF1247A96E94A85D5EE5F11A20C4D3A6ADA3CC70862CC9D676E3AB18C79F91BBB668DC6816523B398069180DCFA1D1D0D28E), (0xAC0A1A39104D3AC0DBB7D2195FC6F99A86E7F70CDAEEAA4AE664091103ED297E9464964E1122AD619C7A5EC997FFAE08A73749EB9687B8BD60D053AB4429BDED8D5485FA30AB89643996706090E1B3E7B60069CCA43EE80CABD8ED41CF011C73BD703B437AB3D8FCADE5B1FF17582D7F1D6CF543BFF0494F6C784E57BD48943779FCA31FA5706C02286D1EBE8CE8F553AB6D23620CD9B4CC27EBD70165803E655079851AB50DD9ACFFF21B51D28AC28383FBAC5024312BA048D6F7BA996B9DA72EB86D1262DA9D9D9377631ABE62F99C28537E69D4EE411E4EE5D1DE161F1394547322191DB020A85D0EBCADCE10E2E6DC5B9145EC33D340774EC47D09BE877463CFC54A3FE07F7832D60CC53294503E02EB02BA8ECDA720A0E2A2CEECA39F9E874749FE4A98A6FCDC181316270D0EE2FE857B1DBCCCE59528A5AA5D3DB08C8F376D3EEA4A51F36A4405DE75BA91F992D3BFCF662781E2BA61B4D6A5EE49BE2B8C83DEB9CE2497AC9211D8B7EE70DA96AB17897DE12667B58B8488967B4D7BFB46B2E937B23FC4AF2723D316A82ACBF1D93FFAD6C0414A651B282E4A9C01314495DD703B87A3E46B53D956BD52510D60B896BD3B53A53B0568831B8911791326F28F81CBBA906A355B8E83E6EB8C12CA1BCD910544F5F0F0A26AD203885A5AD9C62FCBC3D041A5282C87DFC521541ADC70506D44E80DE2FC9E902850BD66AD0134AA20BBC04FFAD92768D9BC152BCF4D4FF69238EC15F0B84DCF253F19556BC5DADE9076345F1B2156941C803425E672C1DA00C6D475141EBA676DF129BBC0D1FC14E2379B5819F0DB70E158F9708428E8B37D3E55A5F065C972BCCCF129F89BD34B831F42AFD769C10FEA6393BC2549E34509763D827B56237D6D299B4DFFB2784684AEEC64E538DB147D46BB8DD20C4C72E9AB1F815D51CF2CFCC541AECD731037EA4C37775D7F60D09A2904359B95AB60DC29645392F7E933DEBF671FF62D46093683A385D4D414694B4FE7334C9991661AE1D1A40FBCA5F36304989E1740D96DE3FBFF18F2258C0714FA08430FB5E6452E0105155BA2F97ADDAF22D03C76520A757950519A7973F455CA8F15B361E3AD1E5370163BC4114001853A4303BBCB9DA7FECE956495ABB48D10C09112E4C67CCC74B2E8063BCFB068104F143E30F199FEF26DB5934D4988A8BB892A03802E1DBEBE778E09E3976C2F1D2D544596765C1E2793207611E87E2B29404BB2A4ADE8E1325680B9638F1B5A01857620F44312452C0CDBED0DA6ACD5F862E0A24CB98B6F4572133FF5A2BB9877C5758B725D36E62E058597D1FBD48BA9AAC001AF780E48FA06DA822FD24F8A504375473120639A2BF9744633F39E4A9E87954AE1BAE8FFB72D916B83B4B65BB5FB478D868C5A894726D5B504C714076890E3A83C05A8965B7590038FF7177643189038AC), ('46eb23a8-9377-41f4-8b05-9d93707c88e7'), (null), ('1952-09-02 17:15:00'), ('<XmlTestData><TestDate1>10/31/2016 11:46:28 AM</TestDate1><Letters1>The quick brown fox jumps over the lazy dog</Letters1><Digits1>0123456789</Digits1><Punctuation1>,.;:?</Punctuation1><Brackets1>[]{}()</Brackets1><MathLogic1>-+*\%^=</MathLogic1><Symbols1>~!@#_|/</Symbols1><RandomInt1>0</RandomInt1><RandomDouble1>1</RandomDouble1><TestDate2>10/31/2016 11:46:28 AM</TestDate2><Letters2>The quick brown fox jumps over the lazy dog</Letters2><Digits2>0123456789</Digits2><Punctuation2>,.;:?</Punctuation2><Brackets2>[]{}()</Brackets2><MathLogic2>-+*\%^=</MathLogic2><Symbols2>~!@#_|/</Symbols2><RandomInt2>0</RandomInt2><RandomDouble2>0</RandomDouble2></XmlTestData>'), ('06:05:55.2800727'), ('5983-05-08'), ('2001-01-01 12:00:01.0000000'), ('3303-02-12 05:25:44.5483616+00:00'))";
            break;
        case 2:
            $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_varchar_max], [c15_nchar], [c16_nvarchar], [c17_nvarchar_max], [c18_text], [c19_ntext], [c20_binary], [c21_varbinary], [c22_varbinary_max], [c23_image], [c24_uniqueidentifier], [c25_datetime], [c26_smalldatetime], [c28_xml], [c29_time], [c30_date], [c31_datetime2], [c32_datetimeoffset]) VALUES ((-831496210), (null), (-28188), (9223372036854775807), (0), (-1.79E+308), (0), (0.9942), (null), (0.8968), (-214748.3648), ('büUüî@ßOb©:+Uî>äÜÃåß.AUbC£@Är~üZ©BOÜhBbvZCªvîüý:>>.rbã.AÄOãUýbü<ÄoÄhÐÐÄÃ£¢@Z/üoh©brÐOÖußÄhböUßvrhh~ÖvãÐÜÜÄ~:*CªÐaÄªîåuvöðî:~ÃÖ:ã*zhZ./åß+:ü>oß<~|zö|r,*îä¢ã,.hBavÖBaüZ*ªãß|/vßßC/åa+ßî¢üýU:Ü~>.r+üu/Ã<ªUý*Ä/ßî£bãÄü_hÐO,ChAÐã~A*,+bZ~+/övÖ,>Orhbª.ðä@åb¢ßäZ|/å/.z,b<ã*><ZaU,+ÃOðýüC:£oß>v:@Ðbä_.*üOßoÃUvð:~@£ÄvB©+|<ÖüÜh||O,ßÖ./>äA,@,ÜzÄä@r~zã~o/bÐzb£+rAUª>ä:oboAÜ©å~vªUÖî<|~uÄ¢r.Ä©C.u©O<Coãä.+o/ZbðZãÜhb*Ðî£<aåå£b<,A¢|aÄBo/.å@B.hö£Äð|ð_bb>Ö_aBZür*åÜ,©@î~Ð¢Ð©:ußU*buoÄ@|äý|bUoBbh:©ou£ÖZCß+ßZß.¢UöZ©bbß@ã*î.B:©/£~~rßb~¢~Ü'), (' '), ('Ub~ößzä/ab£o*åÐ*OÃhðOÐuOãO+ÃBvö/hhbäão£*|r~@|¢ÐÃåU~/U/@_ã,C¢öO*ÐhuArßä@AAß,ýoÄ£r:©ßäÃ@oÄ¢_A:AA.,äã,z~_åaÐ|.<b_,CÖîov,>b¢bz~vãbaý,ýCz*z¢ðßÜChCÄ£boîÄCåCý,ßÖUvöªoÃ|.bb/bÜ:ZU>¢+î@+Ã@Cö+åCvaÖ_~b+üOª:ªÄ£~©BhUã¢¢,aåUî~:>uz©Zßb©_O.ðZzaðzßÄBäaßBA>££Ö£*hÜ@Ä,Cð.ã_ý~öäð*z,b~:O>ª>/u¢ßo.ðåäUhÜ¢£ß,,å|ä/äuCo*v©båövðröîZ¢ÃhbÄb¢Ü~ÄÖvrbzöÐb|ý@£ßhUßýZäã.ÃîbÜO/ÜÐß¢*~¢_.äß£*Oo,îßüäýãåBzðÖZb+CZC_ü*Ü,obr¢ãüO©_£uZaZß*/öãÃZ/ã*<AÐ@@ßuã¢@¢î©+ýðî©.BbÜ+ã>ßBa>bBÖuäoßuÃhÄ..Ã>Ãî©büðÜ¢AÐvÄÃîaÄÐu*_/ä©ßroÃu@ÃÃ©BäoªhAA|Z<Ð@ª<C*©:ýObhbßUåäßU|C+AªªßÐ/~ÃOßuöhoOÖA+~bÃ@ªöß++UoUzÖ+,<aäzªUäZývðC©bÃCÖåýA©@a©a,bz_ö<<ªÜÃOåÐÖ*r,_zAÃÃv~b>/ÜzÄüýrzuo|ÜAß:@î.@uAh£ãvýÃ¢zÄßÖ<<bÄvbÐOÃ._o©¢>_ªoÜäbã:vC>:üz~ý_+ßz~ÐÐUäÃz_¢¢>o©>hAhðöÃ£aÜªß¢ÖåC/aßåzðßäððOÖzã©£zÜ>o_äª.BÃ|üh<uð:.r¢ÃZ+|+orýÜözOCü<ãUÄ~|..Ü¢,ãÐ<ÃUZî£böa.öß,£¢£h+,äOý,ÃÐã£uZ_ÐO@:ÖZ_©/|ba::zîrðßÜ¢vaý_<z~|býã©>ÃÜZðåzB>ðãßO_ö+ã¢Ä:@ð:O_ðvýüB+|u*üÖ|ªZA<©C+or+ö+rÐ/v.h~uªÜa¢*h.|v|ã|OßU*ÃüäßBa*|*£Ö,ðZãO._üãv/h@Äßî£@,o/<Bª/vÜC~::vÃ.A¢ýÄ£o©ã/bUã+bzBÃAü<*îãÃ/î¢.,,ãh:BÃoîöär>ªbA*ªðÃß>Ü.zrbCßZZÐ_U:AuC,U,hOÐoÖýÖãß+ßýðªî¢/ãuA~,¢£hÃuhAßur©z©ZAu~uzvüö<Ðª©_/ßZoußÜ~BAbÖªßAC¢*¢ð¢ªOUýÄZ/©O.b|¢¢ýöb:>îÄör@>Ð<ðãã.üÖðß¢Ao@_+ðî£ýOýrbO~îhAî£ýÖ@ýã>ã*ýBßÄß,ðîðü_ßA|@ZÖUå©v@ãÄao¢v£_Ääb~£å+v/ÃUÃüO>C¢:Uru~ªOb>vBÃ¢Ö£U.vÃÄ:î¢ð<ãr.ª_©uî|OA:ZCUî@o¢vªßð¢/~ðß.o_¢vÖÖ.Ü:ö¢åßb|ªßBh/ßöÖa©åavßOüÐ/@,©/o|+äCbuý.ßÖ+a¢<Ü.~uÜªªåÖCývüå<ZÃîO£_ß|Ãä_avbvðÜ|>üaðoåã|Ã¢ÐCßUCö@b<ßüðüãå+U~*ß<rrÄ>ð:+_a+BäOã|bZî*~r:üª|ýOAß£Urîh_ðbÜr@h|:<åbÜý*ZüÃarvÖUCððU*Ð,BåübZ£a_Äß/£a>Ä>uOÄ@©~rÃäÜ©öãZUßßb+aBÐÃÃZ:üOß¢ÖÜ//Ãb¢uÐ_ÃZO_ýbÃ/|ªãöCÄ,@@AruªaåUaî_.ªrr~*ã*ß_oÃuÖ|¢OÜß£ÐbO|ªa>ð©.ö_b_vß+.Uªv,~ü**ý<b,Ã<rvß+.©ª~ãð©.ª£¢Ä>Ðvß_r+*<urvAAäZÄ>*ßÄð/ÃßÃÖ+Äo,ÄÐvýzCîöýî<åO<*_ßb,ÄCzßý|z~ýã:a£zã>_Öb_oß_*îUAuu<höZ*<v.ðZ/a_ðB_îã~*üßßÜ_rî~üåÖ_b+@Ä@ÄZã~B¢<¢_£å:ßa/:îU©+OÜC~Ã:b|åöO..AO_ÐÖåO*ha|aChäzboBã+zöOvå£|ðÐ~<<bßßåÄh,>aAÜ||~ZOußÜb~AÄu+<åu¢/./B|b+b:+AÄ+*ZO¢Ðz+,Ãðß,åaBª_Uvoåª¢*<ý@ðhÜ:Ö+uÄ.bÐÖ|äuÐ>¢Ä©bv<îCÖ|<Ð©ãoÐß/Uî>bA©zÃ_ß@ÐZ£åhÐÜz:©ÃåzýßÖüÖr~aÐ/ýßa~ÃaÖ~ðB¢Avãä_C,.+Ü~Aobrð*ö*u>ýä©bhobzÄBÖÜÄü_ðBrbOª+©.Üã@<B.£~åCb/oß,üZb©Ãßv<B©ýuÐUAÄäåÐÜãvB*b|ßãu_U.**@+öÜ>B:ð£o|bzO_ÃAZªz+bv©ª.åo_rÄÜuoäZÜb,¢*î£öÜüU£Z|@üßAý/BvoBuv,/:ßãh*Uîüîüü::zî¢hUäýÄÖürO/r>îZ.b_r>ýhBzb£|bãb_ýÜ,|öý/.ÐÄß¢öîA/bª|îðußî>bðß*hÃCo©//B+*/£C<ßO@h.ar><ZhªÜö>bîb¢b~AÃ<Z>¢zÄvCUßÖAÐüî_ßh©rãab©<¢Z/,AbåÜ>bäð.îÜOoåÜAðÜba@ßððßä:uãhÄ/ãÖrÖäUuð'), (N',UîAÃhZ¢|äu/århüü£ª¢bvO*+/©Ao©_hüå:ÃaßOzör~är.åýªåZª*/_Cb~©v<U:îzb<ýÜ<üO:vÃåÃ*hö_©/Z+ðß_+*¢:CîäÃÖÃ_CöîuhîChbÐ£/Ð/ÄÐßßo_,vð¢:ã_b_/ahaßAa£:Z,UßÖßÄîîa/Obä~hoãavßz,bª£Ä@/ð.Ãb+_ÜZª_Cð_ð@¢++o|ä¢Oý,©üaoOBBO>ü¢ÐBÄzßã~zî*U,å:UZ|ö|zrÄÄÃ~Üh//aÐbüåîU£ãÖabäBA/ªhÐðß|Uö©.A.AÖAÄhåî¢O.ý_ý£ÄO_U+ª©vü£.>¢:>CZ<åä¢BÐzîüO.uOhOöOãb..b/ÐÖÜ,z©v:hbC@©ha¢<u*,.vbß>u|:¢/Äî.:Cäz*ýUÄÖvhb£@ßou~ObÃa<ÄýîbCããÄ,uBrAoZCuBÃ©ýîÄäÄÖv,ä@ßÐ:©oz:ðCäð~Üä_zÜaö<üüa+<ßZb,+|:ää.,Ü+ýüÃzýßÖ/Cäz.ýv:,|äÄbO*A.|ÖZ<Äî<o_.aüß©ýrªbÃ:Ðý,vý<z_©ÃO©ðvÜu@vÐu,oä~v>ªîå@<'), (N'ªÃ|,A<uªð>ãu*A@îÜÃ**<b|Cßý@å.©oubý*ªrã>ß>¢<Ãa+ð:BaBãhßO|C:ªAü+OU*¢o+ÄîÄör_ãã~åC/bbÜ.~*bußÜ>Öu<ãC/r*,r~ÐOzo¢¢ü*üu*Oüå~ÜÃa_ö|ö/öÄÖÃÐ.+_Ðu>*ªB.Ä<v<ßU/A@ü_£_*äUAßÜ+<åöuöªÄªü<_ýÃZzb©BCo@©uÐrÖãråü:|ßÐöß_A@r|ßbA/_üUAÜªö©¢äß£rAOÜ>ÐaAýOö+>|£~ðªÃªrZ©ßªoB+@îråhozý*:bBba:+aå_uö,Ðaor:ð,,o*££@Bübrzu£u>|ª@räZßÄ¢å.CoOÄðÐ.¢üvÐ</>*,ðÃuýzoU<~Ãubbª<ÄÜu.öß,Z~ãaUrßCBÄ@îäA.åBüîÐCu@*_+ßAÄrB.ª|,£ozîb£:ª@_oÐ/Zåh/Abvbb><î.ß/å¢Czz£ÖÐßbäb©£ýß,¢,Ðßb©,_v*~©ãa©äAÄî/ä/ß_o*+Oýª.BÃBvabªßüÖÜAZOböbru£ðb.@rªO©ÐZO|Ãð<üÐÐ£ß/åð©B,>*B,zýÖü<aZªb.äöB'), (N'r+,ÐBuz|/rbhÐ/ßBã|*ýrßÄ<:ÃhÃuö>ßUCåAC¢aoý/*Ü/å~oUza,ÜÃ+.£îv/b£z<Ðvî¢_Üýu|oýÜ<öA~Bü/ððBa|Ü<.r*~b*CäAoüü£<zßßªÄ~öÃåÄzCu,|£U*äb,ßåýb©©ªîåªh*ü¢hv/äO_Äz£vüCÐz.ð£C~B_îã@Aî+ÜaBoÜUÜ/+ß<©_©<UÄC_öv@üöAÜäbu/ðãvoðz_BräöîABO:A/b©C.>übvAhha.ÃOöî£,,ÐhÄ¢îÃý,<äÜªÐ:a~Z@*Zb©v,ür<vÄBO|b©Ãöö@>ßýÄßO,_vüÃ¢©,ZbZbäz©ÄrÐÃ,©r_bÖb¢ÜA@z,äBa/ö@~b:ßUý+¢b¢äÜªvA/@oaOr¢aå+ÜÜðuÖzuÜÄhªðäü_ÐhýýðUðA+/ªã.ÖZüb~<<r:.*z¢aîC/>£r>:b¢AöBäÐ~ßh>ãa.O_+.>zZÐ©ÃZUAîððü¢ðZr*b¢,¢>äªOßßý>öîhuÐoo*ü*©*UA.:rh@©åB|::rCýÖßÃãb@o,vb+U@Uz~ß+rßB£ãaU|,£AuOßO_~BåßvÐåAooßvh£ßü¢býzîBý©~@h@+Aä|büÃuÃab>ðb>Ãuý.ÖäbªBßä£<BuUÖª+ÃCZUroü|v¢a@.ã¢z:ªC,©Z:ÜÐr*rC<Özî|,+,üÜuÜZÜýBz<ßäßß££rÄ,Ã_@aãÜ:ßåÃªã<Uð<ßå¢,_:©/Ö_+zÃB©ÄäýÃä<Ü.aãüC.Ä¢*ªå*ãÜ~zäuäbUZ.ChÜaîý<~býA<ª_~*vÐCrÜ:*vîC_h:a,@B¢Ðß*,ß£äaß*ßZýÜvåª+hB£Ãß>CÜ:zhb¢@,ýßÖýåî<*Ö¢a>ªhÜä|.a,aübAävAr_åÖÐvðaö_åÄ<©ÖåbÜa@£©:~£ªh,aÜÄh¢ävßååZ'), ('hßzÃÄ|ÄUBýUãÖ._bZBCh|ýOãÖBª.öýo>ßã@äCîöb/CðÜ:Zä:ÐÄªÜobCh£>öªözZCãÖAîîÃü.hbÄ/b.,:uhÃu..zÐÐ+u£ö*BCãßöÃUü|AÖO¢C,ÃbuB>,ß£bÄ/:zO|<z:ßöªªv:ÄOvª£<rÜr+AZý_<üZ>ÜB@Uößý~:ÖßoÜBßÜ.zCrýª@©uöhU<ßýä£CãOö:U>ªbA:üA~ö>rChäB>o~äaý£ÖB©Öü@©U¢ãüb¢ªð|bÄO,rÖv©ãý|¢©¢b/Uääý,Äý@ðÃå,obu>~¢@hß¢/ýßýîüüAªýÖ_ßÜÖðO+Ä©:@äå>ÜÜbbbBðª¢/åo,ßA|åBî¢¢U:Ö£/h:hoîCö@hOðý_îßðr+åÜß|©C~/üvä@zZ/¢ö><:@Öuå©ã£î|aßÄbBåä¢ªÜAª>h©£¢å¢~îö*<ÖäC|öîß>Ãäü<o<u/|a+Ä:äaª|uAüa*©b<uZuÜzÃÃü_î¢Or<h£raî~¢Aåã@ýÄää<+rîýðoý£u*ÃÖîö./*ÜîzßzÜ>är..UzýãBb|/vu_ý©|ß|îåCö:b©ðü_©b:/CÐÖ'), (N''), (0x00), (0x), (0x57CDF160B1CD42B39A4C3844CF1719168847B6FECC6079994BD995251FC8961EFFA573173276CF40D35D8FD9E1ADB125E67F2A35C3AE6CF6545715D8381C93D2B619442AA302E31277752A6FD19FB89D276F3F9F9D615A50387DC3DB60F708B6C9BD22C7688781E49B18BD4D83FD25B49F77B1C8DCF43555BEF4F2253E08B2806D74A6137DF035A0ED5B0484A30DCC3B580F3CE84640D9D587CC0874955CA593653CDF0F561136AE4210DA81F0B0B1B127B6CD2FCCC93A2D9AD18BB0E7C10B91B5701F662DBCE446D88DA6356558E5D71B574CDE8E76C79B02B275858585C7800548CB7F914A836AD286563E2304C741ECB49590742C023FA883647B475FC053C5252F1D8D6545D0D3E20C08208878FDEA3499D1F0370CD8458BD70EE6082373C50379FDD5AAB4866EC5BC9220FA1F1F0A669013C5EF628E1956D7018D37E9CABE99421D0F7A283F7F12622B7A79B84B6CB803807710ADD5A0EAE47ED3B3F836DBF2D387B7010F903D5093B38D37139F9E8B94D5346953AEEB6C7C2496AA6CCB192B4F88A530936AA87242267CFA4869D744DF471E2302719E249B4B85EBFA93E4C79961E78BF96EF8292DC1272C32F123134D51CB25ADF629DEE9819448E5BD78AE42B460E6814B4A704FF5F5404A72F1AA27719DA8C8BE1AA33809A178D9AF44704978EA7E903D9201065AD5B700E893778FEA20741FCFE68399BDAE78A9D2979FB185F7D1887319EADF9BA26DE963A250752AE9EF8ECF50799166E4D38F195834C336067BF1C2EE1BD03694842D789A361B5E037EE8A710E1357D3F218F465BC5436DAAB35C275EB33CCD1E583ACB4B18CCAEB83016D94C1B2525457382CC8C8ADBCF41C0CEE7DAF3FF50421A89B8A669312755E18D2AAF69218F8D37E1EB306F41937C6B8A8B4AE4D3C431C69631926C7EDB415B77ABFCB01FD79F3F59A211131FD3D9F53B3ADFA6DC710DC9F8E8D2F0FC21B806A9D5F89597CC4BB2EC0B8B31B21DA7C3579D3044FB9B7E059E33B83704AF4237EA0B6A49DA435D6FEE2CB9D0E6DB17DA410E985FF1740A0D56F2F7641314AE3DC85347A6B35EE9133FC055CFE2AD1FC21BDDF5F516E34675015CF2E84CF95CDA7E7C0892947027E325851F301F3B2BA9D23548361EB827E50CD1A5566B01C745647A5065A58C2DABB612CF91C31556C630491517B125BBC6552286C3CBD07724BDDACBA4EAC50346079C7F0CE89522F67BDF2522EBAF943EBE8A1083066AE0E59AABD3E949B1DB6B45CE0F14A5AFDDBF008F48ECAB55380D458DE660B5803F9DBC9A713DC238ACC903BADD1DA07F980FAC51827721F43A4572C5A3BCCB7BB5C58E72570B3DA0FDC7E27EE8F0781B6DC6F4D6B364FDC87BC8602BCAFC95F0FE8704178E2D278DD10C54BB94A20B4ACD5757DCC7F4C851C7C5F68FAB12D50032F28060EB608C3F9E6705A60F4234D9BD165E73A4B82809BFB350C83798E1BF375389A221AA5E6DCA183E60EF214184911DD295B3615B79C977A0380FC4080685466FBCB992B712B7F0A98C7AC71A9E71CB371AC64C0BD5F80FD8AA658FCFFC770B581C5C0A1C1482C4C1555D14962DD2D8DC09BD2D6410A3E9F36A82E70C63872E296577DFED2B427DD0F7C760E4B1CA226C8DB3C63F4AA747787217B2919BB75ECD2EEA31B5F2EAB0C7405C6D484205419F607A7FB0C1EFDFBD2F677A148FEB353F6993B6C6996C79A2), (0xFEED3AFF356DD613312038AD0B3E0B73868B6F9C3754262DDFAF4F7A05F400DCAEC3F1F18330310D3D7CBA1507BB8777BB26E5EE3365C7A9C70C20861E5B0D3C1F5CFF484D19EF565A625FBA9A11262327D7BEDF0E12A949FC472A760FABEEE16F7DF5ABBB3F2AA1368F93D0050C0FB5E6FC5E469D2D2E0CB2B64C06EE7946AF737D4B1C1C7B68E8AD1CA97F7FD843EF377590134CA744414FE8F805F324B2BC48E45D88C0F3996A9476998084AE8DDA735CCE79E83362CB380ACD338362F48969EC379A7BBA2F44DC257FD60EC4BDA05B1E91975539D31582914FB62565E7318110169777F25EB82C899DC3B48BBD744C2DD864D15C3330182118EA6B791E763D4D54FEAE262868BFD86ACB9B1060F6D4DB69F88D836025DCE57E55DA744DB36A27866636BFC5260470A5F8F1679408C121158F0F26E8BAED2F21C9A7D28D4A8BB761748AF1310EAC79C31A6501776DDF7CDF65DD200F1F752287F782FE8698F7B0DE659B4D695CC78C22FFB9A89A1B5B3B9D31908E02105C1796EB93398CAEF22B22B42212D29112829D50FD0F65CC886B9B4661550868AECE0983B77EC88D00740D8E86E6F949690B1E0DF0B1124CB1C963289E454C9C23BA0554084EDA94B9104F8902D746A27151DD5636FA77FCBBBCD1ED1BA0EFBB27706BCBE0A4401A308A53E062884ADA83C0B64C67F666284209994C7CEC7819209462916C7AC1CD11C325ACA7979A3272F76A5AE9A254300A4289D70BF49D43113ABFCE26B86E08FEE93BA3D1B981AB6E66D985821E2F7E3F328729F0EAC11C25E19A6DD7CC0BB988615B0067B05F50C1DE68A41A18C1F30A418EF431451713281E7EF01F13D3A25A20FC20C22E168FD3366712821C010D20A762953E1787434947D848326E31AE0AC54AFA1BA030DB2BFF21FBDD05910B1C70D078B13E644398B8D67B56CD5C64), ('99999999-9999-9999-9999-999999999999'), ('4303-03-26 08:57:46.916'), ('2079-06-06 23:59:00'), ('<XmlTestData><TestDate1>10/31/2016 11:46:28 AM</TestDate1><Letters1>The quick brown fox jumps over the lazy dog</Letters1><Digits1>0123456789</Digits1><Punctuation1>,.;:?</Punctuation1><Brackets1>[]{}()</Brackets1><MathLogic1>-+*\%^=</MathLogic1><Symbols1>~!@#_|/</Symbols1><RandomInt1>0</RandomInt1><RandomDouble1>1</RandomDouble1><TestDate2>10/31/2016 11:46:28 AM</TestDate2><Letters2>The quick brown fox jumps over the lazy dog</Letters2><Digits2>0123456789</Digits2><Punctuation2>,.;:?</Punctuation2><Brackets2>[]{}()</Brackets2><MathLogic2>-+*\%^=</MathLogic2><Symbols2>~!@#_|/</Symbols2><RandomInt2>0</RandomInt2><RandomDouble2>1</RandomDouble2></XmlTestData>'), ('02:05:50.7400842'), ('0169-06-07'), ('9999-12-31 11:59:59.9999999'), ('2669-10-09 12:27:15.2132842+00:00'))";
            break;
        default:
            break;
    }
    return $query;
}

function RunTest()
{
    StartTest("pdo_fetch_columns_fetchmode");
    echo "\nStarting test...\n";
    try
    {
        FetchMode_GetAllColumnsEx();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_fetch_columns_fetchmode");
}

RunTest();

?>
--EXPECT--

Starting test...

Comparing data in column 0 and rows 
1	2	
Comparing data in column 1 and rows 
1	2	
Comparing data in column 2 and rows 
1	2	
Comparing data in column 3 and rows 
1	2	
Comparing data in column 4 and rows 
1	2	
Comparing data in column 5 and rows 
1	2	
Comparing data in column 6 and rows 
1	2	
Comparing data in column 7 and rows 
1	2	
Comparing data in column 8 and rows 
1	2	
Comparing data in column 9 and rows 
1	2	
Comparing data in column 10 and rows 
1	2	
Comparing data in column 11 and rows 
1	2	
Comparing data in column 12 and rows 
1	2	
Comparing data in column 13 and rows 
1	2	
Comparing data in column 14 and rows 
1	2	
Comparing data in column 15 and rows 
1	2	
Comparing data in column 16 and rows 
1	2	
Comparing data in column 17 and rows 
1	2	
Comparing data in column 18 and rows 
1	2	
Comparing data in column 19 and rows 
1	2	
Comparing data in column 20 and rows 
1	2	
Comparing data in column 21 and rows 
1	2	
Comparing data in column 22 and rows 
1	2	
Comparing data in column 23 and rows 
1	2	
Comparing data in column 24 and rows 
1	2	
Comparing data in column 25 and rows 
1	2	
Comparing data in column 26 and rows 
1	2	
Comparing data in column 27 and rows 
1	2	
Comparing data in column 28 and rows 
1	2	
Comparing data in column 29 and rows 
1	2	
Comparing data in column 30 and rows 
1	2	
Comparing data in column 31 and rows 
1	2	
Done
Test "pdo_fetch_columns_fetchmode" completed successfully.

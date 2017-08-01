--TEST--
call stored procedures with inputs of ten different datatypes to get outputs of various types 
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

include 'MsCommon.inc';

function ProcFetch_BigInt($conn)
{
    $procName = GetTempProcName('bigint');
    
    $stmt = $conn->exec("CREATE PROC $procName (@p1 BIGINT, @p2 BIGINT, @p3 NCHAR(128) OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(NCHAR(128), @p1 + @p2) END");
                            
    $inValue1 = '12345678';
    $inValue2 = '11111111';
    $outValue = '0';
                            
    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindValue(2, $inValue2);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();
    
    $expected = "23456789";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) 
    {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }    
    
    $stmt = null;  
}

function ProcFetch_Decimal($conn)
{
    $procName = GetTempProcName('decimal');
    
    $stmt = $conn->exec("CREATE PROC $procName (@p1 DECIMAL, @p2 DECIMAL, @p3 CHAR(128) OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(CHAR(128), @p1 + @p2) END");
                            
    $inValue1 = '2.1';  
    $inValue2 = '5.3';  
    $outValue = '0';    
                            
    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindValue(2, $inValue2);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();
    
    $expected = "7";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) 
    {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }    
    
    $stmt = null;      
}

function ProcFetch_Float($conn)
{
    $procName = GetTempProcName('float');
    
    $stmt = $conn->exec("CREATE PROC $procName (@p1 FLOAT, @p2 FLOAT, @p3 FLOAT OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(FLOAT, @p1 + @p2) END");
                            
    $inValue1 = '2.25'; 
    $inValue2 = '5.5';  
    $outValue = '0';    
                            
    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindValue(2, $inValue2);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();
    
    $expected = "7.75";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) 
    {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }    
    
    $stmt = null;          
}

function ProcFetch_Int($conn)
{
    $procName = GetTempProcName('int');
    
    $stmt = $conn->exec("CREATE PROC $procName (@p1 INT, @p2 INT, @p3 INT OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(INT, @p1 + @p2) END");
                            
    $inValue1 = '1234'; 
    $inValue2 = '5678'; 
    $outValue = '0';    
                            
    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindValue(2, $inValue2);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();
    
    $expected = "6912";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) 
    {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }    
    
    $stmt = null;          
}

function ProcFetch_Money($conn)
{
    $procName = GetTempProcName('money');
    
    $stmt = $conn->exec("CREATE PROC $procName (@p1 MONEY, @p2 MONEY, @p3 MONEY OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(MONEY, @p1 + @p2) END");
                            
    $inValue1 = '22.3'; 
    $inValue2 = '16.1'; 
    $outValue = '0';    
                            
    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1, PDO::PARAM_STR);    
    $stmt->bindParam(2, $inValue2, PDO::PARAM_STR);    
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);   
    $stmt->execute();
    
    $expected = "38.40";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) 
    {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }    
    
    $stmt = null;          
}

function ProcFetch_Numeric($conn)
{
    $procName = GetTempProcName('numeric');
    
    $stmt = $conn->exec("CREATE PROC $procName (@p1 NUMERIC, @p2 NUMERIC, @p3 NCHAR(128) OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(NCHAR(128), @p1 + @p2) END");
                            
    $inValue1 = '2.8';  
    $inValue2 = '5.4';  
    $outValue = '0';    
                            
    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);    
    $stmt->bindParam(2, $inValue2);    
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);   
    $stmt->execute();
    
    $expected = "8";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) 
    {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }    
    
    $stmt = null;          
}

function ProcFetch_Real($conn)
{
    $procName = GetTempProcName('real');
    
    $stmt = $conn->exec("CREATE PROC $procName (@p1 REAL, @p2 REAL, @p3 REAL OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(REAL, @p1 + @p2) END");
                            
    $inValue1 = '3.4';  
    $inValue2 = '6.6';  
    $outValue = '0';    
                            
    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);    
    $stmt->bindParam(2, $inValue2);    
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);   
    $stmt->execute();
    
    $expected = "10";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) 
    {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }    
    
    $stmt = null;          
}

function ProcFetch_SmallInt($conn)
{
    $procName = GetTempProcName('smallint');
    
    $stmt = $conn->exec("CREATE PROC $procName (@p1 SMALLINT, @p2 SMALLINT, @p3 NCHAR(32) OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(NCHAR(32), @p1 + @p2) END");
                            
    $inValue1 = '34';  
    $inValue2 = '56';  
    $outValue = '0';    
                            
    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);    
    $stmt->bindParam(2, $inValue2);    
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);   
    $stmt->execute();
    
    $expected = "90";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) 
    {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }    
    
    $stmt = null;          
}

function ProcFetch_SmallMoney($conn)
{
    $procName = GetTempProcName('smallmoney');
    
    $stmt = $conn->exec("CREATE PROC $procName (@p1 SMALLMONEY, @p2 SMALLMONEY, @p3 SMALLMONEY OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(SMALLMONEY, @p1 + @p2) END");
                            
    $inValue1 = '10';  
    $inValue2 = '11.7';  
    $outValue = '0';    
                            
    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1, PDO::PARAM_STR);    
    $stmt->bindParam(2, $inValue2, PDO::PARAM_STR);    
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);   
    $stmt->execute();
    
    $expected = "21.70";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) 
    {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }    
        
    $stmt = null;              
}

function ProcFetch_TinyInt($conn)
{
    $procName = GetTempProcName('tinyint');
    
    $stmt = $conn->exec("CREATE PROC $procName (@p1 TINYINT, @p2 TINYINT, @p3 CHAR(32) OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(CHAR(32), @p1 + @p2) END");
                            
    $inValue1 = '11';  
    $inValue2 = '12';  
    $outValue = '0';    
                            
    $stmt = $conn->prepare("{CALL $procName (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);    
    $stmt->bindParam(2, $inValue2);    
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);   
    $stmt->execute();
    
    $expected = "23";
    $outValue = trim($outValue);
    if (strncasecmp($outValue, $expected, strlen($expected))) 
    {
        echo "Output value $outValue is unexpected! Expected $expected\n";
    }    
    
    $stmt = null;              
}

function RunTest()
{
    set_time_limit(0);
    StartTest("pdo_stored_proc_fetch_datatypes");
    echo "\nStarting test...\n";
    try
    {
        include("MsSetup.inc");
        $conn = new PDO( "sqlsrv:server=$server;database=$databaseName", $uid, $pwd);   

        ProcFetch_BigInt($conn);
        ProcFetch_Decimal($conn);
        ProcFetch_Float($conn);
        ProcFetch_Int($conn);
        ProcFetch_Money($conn);
        ProcFetch_Numeric($conn);
        ProcFetch_Real($conn);
        ProcFetch_SmallInt($conn);
        ProcFetch_SmallMoney($conn);
        ProcFetch_TinyInt($conn);

        $conn = null;   
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_stored_proc_fetch_datatypes");
}

RunTest();

?>
--EXPECT--

Starting test...

Done
Test "pdo_stored_proc_fetch_datatypes" completed successfully.


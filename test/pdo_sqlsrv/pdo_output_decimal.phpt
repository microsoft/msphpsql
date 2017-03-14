--TEST--
call stored procedures with inputs of ten different datatypes to get outputs of various types 
--SKIPIF--

--FILE--
<?php

include 'pdo_tools.inc';

try
{
    require_once("autonomous_setup.php");
    $database = "tempdb";
    $conn = new PDO( "sqlsrv:server=$serverName;Database=$database", $username, $password);   

    $proc_scale = GetTempProcName('scale');
    $proc_no_scale = GetTempProcName('noScale');
    
    $stmt = $conn->exec("CREATE PROC $proc_scale (@p1 DECIMAL(18, 1), @p2 DECIMAL(18, 1), @p3 CHAR(128) OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(CHAR(128), @p1 + @p2) END");
    
    $inValue1 = '2.1';  
    $inValue2 = '5.3';  
    $outValue = '0';
                            
    $stmt = $conn->prepare("{CALL $proc_scale (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindValue(2, $inValue2);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();

    $outValue = trim($outValue);
    echo "outValue with scale specified in decimal type: $outValue\n";
    
    $stmt = $conn->exec("CREATE PROC $proc_no_scale (@p1 DECIMAL, @p2 DECIMAL, @p3 CHAR(128) OUTPUT)
                            AS BEGIN SELECT @p3 = CONVERT(CHAR(128), @p1 + @p2) END");
                            
    $stmt = $conn->prepare("{CALL $proc_no_scale (?, ?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindValue(2, $inValue2);
    $stmt->bindParam(3, $outValue, PDO::PARAM_STR, 300);
    $stmt->execute();

    $outValue = trim($outValue);
    echo "outValue with no scale specified in decimal type: $outValue\n";
    
    $stmt = null;
    $conn = null;   
}
catch (Exception $e)
{
    echo $e->getMessage();
}

?>
--EXPECT--
outValue with scale specified in decimal type: 7.4
outValue with no scale specified in decimal type: 7

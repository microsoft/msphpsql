--TEST--
call a stored procedure with XML input to get output back as characters
--SKIPIF--

--FILE--
<?php

include 'pdo_tools.inc';

function StoredProc_Xml()
{
    require_once("autonomous_setup.php");
    
    set_time_limit(0);
    $database = "tempdb";
    $procName = GetTempProcName();
    
    $inValue1 = pack('H*', '3C586D6C54657374446174613E4A65207072C3A966C3A87265206C27C3A974C3A93C2F586D6C54657374446174613E');
    
    $conn = new PDO( "sqlsrv:server=$serverName;Database=$database", $username, $password);
    
    $stmt = $conn->exec("CREATE PROC $procName (@p1 XML, @p2 CHAR(512) OUTPUT)
                            AS BEGIN SELECT @p2 = CONVERT(CHAR(512), @p1) END");
                            
    $stmt = $conn->prepare("{CALL $procName (?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindParam(2, $outValue1, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 512);
    $stmt->execute();
    
    var_dump($outValue1);
    
    $stmt = null;  
    $conn = null;   
}

function Repro()
{
    StartTest("pdo_utf8_stored_proc_xml");
    try
    {
        StoredProc_Xml();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_utf8_stored_proc_xml");
}

Repro();

?>
--EXPECT--

...Starting 'pdo_utf8_stored_proc_xml' test...
string(516) "<XmlTestData>Je préfère l'été</XmlTestData>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     "

Done
...Test 'pdo_utf8_stored_proc_xml' completed successfully.


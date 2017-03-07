--TEST--
call a stored procedure with NVARCHAR input to get output back as NVARCHAR
--SKIPIF--

--FILE--
<?php

include 'pdo_tools.inc';

function StoredProc_Surrogate()
{
    require_once("autonomous_setup.php");
    
    set_time_limit(0);
    $database = "tempdb";
    $procName = GetTempProcName();
    
    $inValue1 = pack('H*', 'F2948080EFBFBDEFBFBDF48FBA83EFBFBDEFBFBDEFBFBDEFBFBDEFBFBDF48FB080EFBFBDEFBFBDEFBFBDF392A683EFBFBDF090808BF0908080F0908A83EFBFBDEFBFBDEFBFBDF48FBFBFEFBFBDEFBFBDF090808BF0908683EFBFBDF48FBFBFF2948880EFBFBDF0A08FBFEFBFBDF392A880F0A08A83F294808BF0908880EFBFBDEFBFBDEFBFBDEFBFBDF48FB080F48FB683EFBFBDF0908080EFBFBDF392AA83F48FB683EFBFBDF2948080F2948A83EFBFBDF0A08080F392A880EFBFBDF2948FBFEFBFBDEFBFBDEFBFBDEFBFBDF48FB683EFBFBDEFBFBDEFBFBDF48FBFBFF0908080EFBFBDEFBFBDEFBFBDEFBFBDF48FBFBFEFBFBDF48FB880F0908683F392A080F0908FBFEFBFBDEFBFBDEFBFBDEFBFBDEFBFBDF2948FBFEFBFBDF0908683EFBFBDF0A08A83F48FBA83EFBFBDF48FB08B');
    $outValue1 = "TEST";
    
    $conn = new PDO( "sqlsrv:server=$serverName;Database=$database", $username, $password);
    $stmt = $conn->exec("CREATE PROC $procName (@p1 NVARCHAR(1000), @p2 NVARCHAR(1000) OUTPUT)
                            AS BEGIN SELECT @p2 = CONVERT(NVARCHAR(1000), @p1) END");
                            
    $stmt = $conn->prepare("{CALL $procName (?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindParam(2, $outValue1, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 1000);
    $stmt->execute();
    
    var_dump ($outValue1);
    
    $stmt = null;  
    $conn = null;   
}

function Repro()
{
    StartTest("pdo_utf8_stored_proc_surrogate");
    try
    {
        StoredProc_Surrogate();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_utf8_stored_proc_surrogate");
}

Repro();

?>
--EXPECT--

...Starting 'pdo_utf8_stored_proc_surrogate' test...
string(304) "򔀀��􏺃�����􏰀���󒦃�𐀋𐀀𐊃���􏿿��𐀋𐆃�􏿿򔈀�𠏿�󒨀𠊃򔀋𐈀����􏰀􏶃�𐀀�󒪃􏶃�򔀀򔊃�𠀀󒨀�򔏿����􏶃���􏿿𐀀����􏿿�􏸀𐆃󒠀𐏿�����򔏿�𐆃�𠊃􏺃�􏰋"

Done
...Test 'pdo_utf8_stored_proc_surrogate' completed successfully.


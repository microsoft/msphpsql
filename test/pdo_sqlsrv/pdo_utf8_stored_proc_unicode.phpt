--TEST--
call a stored procedure with NVARCHAR input to get output back as NCHAR
--SKIPIF--

--FILE--
<?php

include 'pdo_tools.inc';

function StoredProc_Unicode()
{
    require_once("autonomous_setup.php");
    
    set_time_limit(0);
    $database = "tempdb";
    $procName = GetTempProcName();
    
    $inValue1 = pack('H*', 'E9AA8CE597BFE382A1E38381C3BDD086C39FD086C3B6C39CE3838FC3BDE8A1A4C3B6E38390C3A4C3B0C2AAE78687C3B0E2808DE6B490C4B1E385AFE382BFE9B797E9B797D79CC39FC383DAAFE382B0E597BFE382BDE382B0E58080D187C3BCE382BCE385AFD290E78687E38381C3AEE382BCE9B797E2808CC3BB69E888B3D790D291E382AFD0A7E58080C39CE69B82D291C384C3BDD196E3839DE8A1A4C3AEE382BCC3BCE8A1A4E382BFD290E2808FE38380C4B0D187C3A5E3839DE382BDE382AFC396E382B0E382BFC3B6C396D0A7E385B0E3838FC3A3C2AAD990D187C3B6C3BBC384C3B0C390D18FE382BEC4B0E382BCD086C39FE3838FE4BE83E382BCC384E382BDD79CC3BCC39FE382BFE382BCE2808DE58080E58081D196C384D794D794C3B6D18FC3AEC3B6DA98E69B82E6B490C3AEE382BEDAAFD290');
    $outValue1 = "TEST";
    
    $conn = new PDO( "sqlsrv:server=$serverName;Database=$database", $username, $password);
    $stmt = $conn->exec("CREATE PROC $procName (@p1 NVARCHAR(MAX), @p2 NCHAR(1024) OUTPUT)
                            AS BEGIN SELECT @p2 = CONVERT(NCHAR(1024), @p1) END");
                            
    $stmt = $conn->prepare("{CALL $procName (?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindParam(2, $outValue1, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 1500);
    $stmt->execute();
    
    var_dump ($outValue1);
    
    $stmt = null;  
    $conn = null;   
}

function Repro()
{
    StartTest("pdo_utf8_stored_proc_unicode");
    try
    {
        StoredProc_Unicode();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_utf8_stored_proc_unicode");
}

Repro();

?>
--EXPECT--

...Starting 'pdo_utf8_stored_proc_unicode' test...
string(1209) "验嗿ァチýІßІöÜハý衤öバäðª熇ð‍洐ıㅯタ鷗鷗לßÃگグ嗿ソグ倀чüゼㅯҐ熇チîゼ鷗‌ûi舳אґクЧ倀Ü曂ґÄýіポ衤îゼü衤タҐ‏ダİчåポソクÖグタöÖЧㅰハãªِчöûÄðÐяゾİゼІßハ侃ゼÄソלüßタゼ‍倀倁іÄההöяîöژ曂洐îゾگҐ                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                "

Done
...Test 'pdo_utf8_stored_proc_unicode' completed successfully.


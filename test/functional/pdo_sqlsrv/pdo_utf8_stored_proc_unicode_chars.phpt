--TEST--
call a stored procedure with unicode input to get output back as unicode; also test with xml data
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function storedProcXml($conn)
{
    $inValue1 = pack('H*', '3C586D6C54657374446174613E4A65207072C3A966C3A87265206C27C3A974C3A93C2F586D6C54657374446174613E');
    $outValue1 = "TEST";

    $procName = getProcName();
    $stmt = $conn->exec("CREATE PROC $procName (@p1 XML, @p2 CHAR(512) OUTPUT)
                            AS BEGIN SELECT @p2 = CONVERT(CHAR(512), @p1) END");

    $stmt = $conn->prepare("{CALL $procName (?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindParam(2, $outValue1, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 512);
    $stmt->execute();

    var_dump(trim($outValue1));

    dropProc($conn, $procName);
    unset($conn);
}

function storedProcSurrogate($conn)
{
    $inValue1 = pack('H*', 'F2948080EFBFBDEFBFBDF48FBA83EFBFBDEFBFBDEFBFBDEFBFBDEFBFBDF48FB080EFBFBDEFBFBDEFBFBDF392A683EFBFBDF090808BF0908080F0908A83EFBFBDEFBFBDEFBFBDF48FBFBFEFBFBDEFBFBDF090808BF0908683EFBFBDF48FBFBFF2948880EFBFBDF0A08FBFEFBFBDF392A880F0A08A83F294808BF0908880EFBFBDEFBFBDEFBFBDEFBFBDF48FB080F48FB683EFBFBDF0908080EFBFBDF392AA83F48FB683EFBFBDF2948080F2948A83EFBFBDF0A08080F392A880EFBFBDF2948FBFEFBFBDEFBFBDEFBFBDEFBFBDF48FB683EFBFBDEFBFBDEFBFBDF48FBFBFF0908080EFBFBDEFBFBDEFBFBDEFBFBDF48FBFBFEFBFBDF48FB880F0908683F392A080F0908FBFEFBFBDEFBFBDEFBFBDEFBFBDEFBFBDF2948FBFEFBFBDF0908683EFBFBDF0A08A83F48FBA83EFBFBDF48FB08B');
    $outValue1 = "TEST";

    $procName = getProcName();
    $stmt = $conn->exec("CREATE PROC $procName (@p1 NVARCHAR(1000), @p2 NVARCHAR(1000) OUTPUT)
                            AS BEGIN SELECT @p2 = CONVERT(NVARCHAR(1000), @p1) END");

    $stmt = $conn->prepare("{CALL $procName (?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindParam(2, $outValue1, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 1000);
    $stmt->execute();

    var_dump($outValue1 === $inValue1);

    dropProc($conn, $procName);
    unset($stmt);
}

function storedProcUnicode($conn)
{
    $inValue1 = pack('H*', 'E9AA8CE597BFE382A1E38381C3BDD086C39FD086C3B6C39CE3838FC3BDE8A1A4C3B6E38390C3A4C3B0C2AAE78687C3B0E2808DE6B490C4B1E385AFE382BFE9B797E9B797D79CC39FC383DAAFE382B0E597BFE382BDE382B0E58080D187C3BCE382BCE385AFD290E78687E38381C3AEE382BCE9B797E2808CC3BB69E888B3D790D291E382AFD0A7E58080C39CE69B82D291C384C3BDD196E3839DE8A1A4C3AEE382BCC3BCE8A1A4E382BFD290E2808FE38380C4B0D187C3A5E3839DE382BDE382AFC396E382B0E382BFC3B6C396D0A7E385B0E3838FC3A3C2AAD990D187C3B6C3BBC384C3B0C390D18FE382BEC4B0E382BCD086C39FE3838FE4BE83E382BCC384E382BDD79CC3BCC39FE382BFE382BCE2808DE58080E58081D196C384D794D794C3B6D18FC3AEC3B6DA98E69B82E6B490C3AEE382BEDAAFD290');
    $outValue1 = "TEST";

    $procName = getProcName();
    $stmt = $conn->exec("CREATE PROC $procName (@p1 NVARCHAR(MAX), @p2 NCHAR(1024) OUTPUT)
                            AS BEGIN SELECT @p2 = CONVERT(NCHAR(1024), @p1) END");

    $stmt = $conn->prepare("{CALL $procName (?, ?)}");
    $stmt->bindValue(1, $inValue1);
    $stmt->bindParam(2, $outValue1, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 1500);
    $stmt->execute();

    $outValue1 = trim($outValue1);
    var_dump($outValue1 === $inValue1);

    dropProc($conn, $procName);
    unset($stmt);
}

echo "Starting test...\n";
try {
    set_time_limit(0);
    $conn = connect();

    storedProcXml($conn);
    storedProcSurrogate($conn);
    storedProcUnicode($conn);

    unset($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "Done\n";
?>
--EXPECT--
Starting test...
string(47) "<XmlTestData>Je préfère l'été</XmlTestData>"
bool(true)
bool(true)
Done

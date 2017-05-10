--TEST--
verify github issue52 is fixed.
--DESCRIPTION--
This test only works in previous versions of SQL Servers. Full-text search features are 
deprecated starting in SQL Server 2016.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once 'MsCommon.inc';
require_once 'MsSetup.inc';

$conn = new PDO( "sqlsrv:server=$server;database=$databaseName", $uid, $pwd);

#=================check SQL Server version===============
$attr = $conn->getAttribute(constant('PDO::ATTR_SERVER_VERSION'));
$version = substr($attr, 0, 2);
if ($version >= 13)
{
    echo "Full-text search feature deprecated.\n";
    return;
}

#=================run the test===========================
$tableName = 'test_Fulltext';
$dataType = 'Pagename varchar(20) not null primary key,URL varchar(30) not null,Description text null,Keywords varchar(4000) null';

if($conn)
{
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connection established.<br/>";
    CreateTableEx($conn, $tableName, $dataType);
    PopulateTable($conn,$tableName);
    EnableFullText($conn, $tableName);
    checkActiveTransactions($conn);
    sleep(5);
    FetchData($conn, $tableName);
    DisableFullText($conn,$tableName);
    FetchData($conn,$tableName);
}   
else
{
    echo "Connection could not be established.\n";
}
#=======================================================
function FetchData($conn,$tableName){
    $query = "SELECT * FROM $tableName WHERE freetext(description,'Magazine')";
    $stmt = $conn->query($query);
    if(!$stmt)
    {
        var_dump($conn->errorInfo());
        die( "PDOConn->query failed");
    }
    
    $stmt->bindColumn('Pagename',$Pagename);
    $stmt->bindColumn('URL',$URL);
    $stmt->bindColumn('Description',$Description);
    $stmt->bindColumn('Keywords',$Keywords);
    $result = $stmt->fetch(PDO::FETCH_BOUND);
    if(!$result)
    {
        die("Fetch failed");
    }
    echo $Pagename . "\n";
    echo $URL . "\n";
    echo $Description . "\n";
}

#creating dummy data
function PopulateTable($conn,$tableName){
    $dataCols = 'Pagename,URL,Description,Keywords';
    $row1 = "'home.asp','home.asp','This is the home page','home,SQL'";
    $row2 = "'PAGETWO.asp','/page2/pagetwo.asp','NT Magazine is great','second'";
    $row3 = "'pagethree.asp','/page3/pagethree.asp','SQL Magazine is the greatest','third'";
    $dataValues = array($row1, $row2, $row3);
    foreach($dataValues as $value)
    {
        InsertRowEx($conn, $tableName, $dataCols, $value, null);
    }
}

function EnableFullText($conn,$tableName){
    echo "Enabling full-text index ... ";
    $catalogName = "fulltext_".$tableName."_catalog";
    #if the fulltext catalog exists, drop it;
    dropCatalog($conn, $catalogName);
    $query = "CREATE UNIQUE INDEX ui_ukJobCand ON $tableName(Pagename); CREATE FULLTEXT CATALOG $catalogName as default; CREATE FULLTEXT INDEX ON $tableName(URL, Description, Keywords) KEY INDEX ui_ukJobCand with stoplist = SYSTEM";
    $outcome = $conn->exec($query);
    if($outcome === false){
        die("Failed to enable FULLTEXT INDEX on $tableName");
    }
    echo "completed successfully.\n";
}

function DisableFullText($conn,$tableName){
    echo "\n Disabling full-text index ... ";
    $query = "DROP FULLTEXT INDEX ON $tableName";
    $outcome = $conn->exec($query);
    if($outcome === false){
        die("Failed to drop the FULLTEXT INDEX on $tableName");
    }
    echo "completed successfully.\n";
}
#=====================helpers==========================
function dropCatalog($conn, $catalogName){
    $catalogExists="IF EXISTS (SELECT 1 FROM sys.fulltext_catalogs WHERE [name] = '$catalogName')
        DROP FULLTEXT CATALOG $catalogName";
    $outcome = $conn->exec($catalogExists);
    if($outcome === false){
        die("Failed to drop the $catalogName");
    }
}

function checkActiveTransactions($conn){
    $isActive = $conn->inTransaction();
    if(!$isActive){
        echo "No active transactions within the driver.\n";
    }else{
        echo "A transaction is currently active.\n";
    }
}
?>

--EXPECTREGEX--
(Full-text search feature deprecated.|.*Connection established(.*Magazine.*)*
.*Disabling full-text index ... completed successfully..*
.*["message"].*Cannot use a CONTAINS or FREETEXT predicate on table or indexed view.* not full-text indexed.*)
--TEST--
verify github issue52 is fixed.
--DESCRIPTION--
This test only works in previous versions of SQL Servers. Full-text search features are
deprecated starting in SQL Server 2016.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

sqlsrv_configure('WarningsReturnAsErrors', 0);
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);
require_once('MsCommon.inc');

$conn = Connect();

$server_info = sqlsrv_server_info($conn);
if ($server_info) {
    // check SQL Server version
    $version = substr($server_info['SQLServerVersion'], 0, 2);
    if ($version >= 13) {
        echo "Full-text search feature deprecated.\n";
        return;
    }
}

$tableName = 'test_Fulltext';
$dataType = 'Pagename varchar(20) not null primary key,URL varchar(30) not null,Description text null,Keywords varchar(4000) null';
#===================================
if ($conn) {
    echo "Connection established.<br/>";
    createTableEx($conn, $tableName, $dataType);
    PopulateTable($conn, $tableName);
    EnableFullText($conn, $tableName);
    sleep(5);
    FetchData($conn, $tableName);
    DisableFullText($conn, $tableName);
    FetchData($conn, $tableName);
} else {
    echo "Connection could not be established.\n";
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_close($conn);
#====================================================================
function FetchData($conn, $tableName)
{
    $query = "SELECT * FROM $tableName WHERE freetext(description,'Magazine')";
    $rtn_qry = sqlsrv_query($conn, $query);
    if (!$rtn_qry) {
        var_dump(sqlsrv_errors());
        die("sqlsrv_query(6) failed.");
    }
    while (sqlsrv_fetch($rtn_qry)) {
        $id = sqlsrv_get_field($rtn_qry, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$id <br/>";
        $id1 = sqlsrv_get_field($rtn_qry, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$id1 <br/>";
        $id2 = sqlsrv_get_field($rtn_qry, 2, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$id2 <br/>";
        $id3 = sqlsrv_get_field($rtn_qry, 3, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$id3 <br/>";
    }
}

function insertRowEx($conn, $tableName, $dataCols, $dataValues, $dataOptions)
{
    $stmt = sqlsrv_query($conn, "INSERT INTO [$tableName] ($dataCols) VALUES ($dataValues)", $dataOptions);
    return (insertCheck($stmt));
}
 
function PopulateTable($conn, $tableName)
{
    $dataCols = 'Pagename,URL,Description,Keywords';
    $row1 = "'home.asp','home.asp','This is the home page','home,SQL'";
    $row2 = "'PAGETWO.asp','/page2/pagetwo.asp','NT Magazine is great','second'";
    $row3 = "'pagethree.asp','/page3/pagethree.asp','SQL Magazine is the greatest','third'";
    $dataValues = array($row1, $row2, $row3);
    foreach ($dataValues as $value) {
        insertRowEx($conn, $tableName, $dataCols, $value, null);
    }
}

function EnableFullText($conn, $tableName)
{
    echo "Enabling full-text index ... ";
    $catalogName = "fulltext_".$tableName."_catalog";
    #if the fulltext catalog exists, drop it;
    dropCatalog($conn, $catalogName);
    $sql = $query = "CREATE UNIQUE INDEX ui_ukJobCand ON $tableName(Pagename); CREATE FULLTEXT CATALOG $catalogName as default; CREATE FULLTEXT INDEX ON $tableName(URL, Description, Keywords) KEY INDEX ui_ukJobCand with stoplist = SYSTEM";
    $outcome = sqlsrv_query($conn, $sql);
    if (!$outcome) {
        die("Failed to enable FULLTEXT INDEX on $tableName");
    }
    echo "completed successfully.\n";
}

function DisableFullText($conn, $tableName)
{
    echo "\n Disabling full-text index ... ";
    $sql = "DROP FULLTEXT INDEX ON $tableName";
    $outcome = sqlsrv_query($conn, $sql);
    if (!$outcome) {
        die("Failed to drop the FULLTEXT INDEX on $tableName");
    }
    echo "completed successfully.\n";
}
#================helpers=====================
function dropCatalog($conn, $catalogName)
{
    $catalogExists="IF EXISTS (SELECT 1 FROM sys.fulltext_catalogs WHERE [name] = '$catalogName')
        DROP FULLTEXT CATALOG $catalogName";
    $outcome = sqlsrv_query($conn, $catalogExists);
    if (!$outcome) {
        die("Failed to drop the $catalogName");
    }
}
?>

--EXPECTREGEX--
(Full-text search feature deprecated.|.*Connection established(.*Magazine.*)*
.*Disabling full-text index ... completed successfully..*
.*["message"].*Cannot use a CONTAINS or FREETEXT predicate on table or indexed view.* not full-text indexed.*)

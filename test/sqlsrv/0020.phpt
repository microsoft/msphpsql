--TEST--
reading streams of various types with a base64 decoding filter on top of them.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

sqlsrv_configure( 'WarningsReturnAsErrors', false );
require( 'MsCommon.inc' );

function RunTest( $field_type ) {

    PrepareParams($params);
    $tableName = "dbo.B64TestTable";
    $params['fieldType'] = $field_type;
    
    ($conn = Connect())
        || die(print_r(sqlsrv_errors(), true));

    $originalStream = PopulateTestTable($conn, $params); 

    ($stmt = sqlsrv_query($conn, $params['selectQuery'])) 
        || die(print_r(sqlsrv_errors(), true));

    sqlsrv_fetch($stmt) 
        || die(print_r(sqlsrv_errors(), true));

    ($stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM("char")))
        || die(print_r(sqlsrv_errors(), true));

    stream_filter_append($originalStream, "convert.base64-encode")
        || die(print_r(error_get_last()));
        
    while (($originalLine = fread($originalStream, 80)) &&
            ($dbLine = fread($stream, 80))) {
        if( $originalLine != $dbLine )
            die( "Not identical" );
    }

    sqlsrv_free_stmt($stmt) || die(print_r(sqlsrv_errors(), true));

    sqlsrv_close($conn) || die(print_r(sqlsrv_errors(), true));
}

RunTest( "varchar(max)" );
RunTest( "varbinary(max)" );
RunTest( "nvarchar(max)" );

echo "Test successful.\n";

function PopulateTestTable($conn, $params) {
    
    DropTestTable($conn, $params);
    CreateTestTable($conn, $params);
    
    ($data = fopen($params['testImageURL'], "rb")) || die("Couldn't open image for reading.");
    
    stream_filter_append($data, "convert.base64-encode") 
        || die(print_r(error_get_last(), true));
        
    if ($stmt = sqlsrv_query($conn, $params['insertQuery'], array($data))) {
        do { 
            $read = sqlsrv_send_stream_data($stmt);
            if ($read === false) die(print_r(sqlsrv_errors(), true));
        } while ($read);
        
        fclose($data) || die(print_r(error_get_last(), true));
        
        sqlsrv_free_stmt($stmt) || die(print_r(sqlsrv_errors(), true));
    } else 
        die(print_r(sqlsrv_errors(), true));

    return fopen($params['testImageURL'], "rb");
}

function PrepareParams(&$arr) {
    $uname = php_uname();
    $phpgif = "\\php.gif";
    if (preg_match('/Win/',$uname))
    {
        $phpgif = '\\php.gif';
    } 
    else // other than Windows
    {
        $phpgif = '/php.gif';
    }
    $arr['tableName'] = $tblName = "dbo.B64TestTable";
    $arr['columnName'] = $colName = "Base64Image";
    $arr['fieldType'] = $fieldType = "nvarchar(MAX)";
    $arr['dropQuery'] = "IF OBJECT_ID(N'$tblName', N'U') IS NOT NULL DROP TABLE $tblName";
    $arr['createQuery'] = "CREATE TABLE $tblName ($colName $fieldType)";
    $arr['insertQuery'] = "INSERT INTO $tblName ($colName) VALUES (?)";
    $arr['selectQuery'] = "SELECT TOP 1 $colName FROM $tblName";
    // $arr['testImageURL'] = "http://static.php.net/www.php.net/images/php.gif";
    $arr['testImageURL'] = dirname( $_SERVER['PHP_SELF'] ).$phpgif; // use this when no http access
    $arr['MIMEType'] = "image/gif";
}

function DropTestTable($conn, $params) { RunQuery($conn, $params['dropQuery']); }
function CreateTestTable($conn, $params) { RunQuery($conn, $params['createQuery']); }
function RunQuery($conn, $query) {
    ($qStmt = sqlsrv_query($conn, $query)) && $qStmt && sqlsrv_free_stmt($qStmt) 
        || die(print_r(sqlsrv_errors(), true));
}

?>
--EXPECT--
Test successful.

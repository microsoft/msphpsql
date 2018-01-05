--TEST--
streaming large amounts of data into a database and getting it out as a string exactly the same.
--SKIPIF--
<?
// locale must be set before 1st connection
setUSAnsiLocale();
require('skipif.inc');
?>
--FILE--
<?php

require_once("MsCommon.inc");
    
function runtest()
{
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);
    sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_OFF);

    $useUTF8 = useUTF8Data();
    if ($useUTF8) {
        $conn1 = connect(array('CharacterSet'=>'UTF-8'));
    } else {
        $conn1 = connect();
    }
    if ($conn1 === false) {
        fatalError("Failed to connect");
    }

    $stmt1 = sqlsrv_query($conn1, "IF OBJECT_ID('179886', 'U') IS NOT NULL DROP TABLE [179886]");
    if ($stmt1 === true) {
        sqlsrv_free_stmt($stmt1);
    }
    $stmt2 = sqlsrv_query($conn1, "CREATE TABLE [179886] ([c1_int] int, [c2_tinyint] tinyint, [c3_smallint] smallint, [c4_bigint] bigint, [c5_bit] bit, [c6_float] float, [c7_real] real, [c8_decimal] decimal(28,4), [c9_numeric] numeric(32,0), [c10_money] money, [c11_smallmoney] smallmoney, [c12_char] char(512), [c13_varchar] varchar(512), [c14_varchar_max] varchar(max), [c15_nchar] nchar(512), [c16_nvarchar] nvarchar(512), [c17_nvarchar_max] nvarchar(max), [c18_text] text, [c19_ntext] ntext, [c20_binary] binary(512), [c21_varbinary] varbinary(512), [c22_varbinary_max] varbinary(max), [c23_image] image, [c24_uniqueidentifier] uniqueidentifier, [c25_datetime] datetime, [c26_smalldatetime] smalldatetime, [c27_timestamp] timestamp, [c28_xml] xml)");
    if ($stmt2 === false) {
        echo "sqlsrv_query(1) failed.\n";
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt2);
    sqlsrv_close($conn1);

    if ($useUTF8) {
        $conn2 = connect(array('CharacterSet'=>'UTF-8'));
    } else {
        $conn2 = connect();
    }
    if ($conn2 === false) {
        echo "sqlsrv_connect failed 2nd.\n";
        die(print_r(sqlsrv_errors(), true));
    }

    if ($useUTF8) {
        require('test_stream_large_data_UTF8.inc');
        GenerateInputUTF8Data();
    } else {
        require('test_stream_large_data.inc');
        GenerateInputData();
    }

    // stream the data into the table
    $fin1 = fopen("varchar_max.txt", "r");
    $fin2 = fopen("nvarchar_max.txt", "r");
    $fin3 = fopen("text.txt", "r");
    $fin4 = fopen("ntext.txt", "r");
    $fin5 = fopen("xml.txt", "r");

    $filesizes = array();
    $filesizes[0] = filesize("varchar_max.txt");
    $filesizes[1] = filesize("nvarchar_max.txt");
    $filesizes[2] = filesize("text.txt");
    $filesizes[3] = filesize("ntext.txt");
    $filesizes[4] = filesize("xml.txt");
    clearstatcache();   // no need to keep the file sizes in cache

    $stmt3 = sqlsrv_query($conn2, "INSERT INTO [179886] ([c1_int], [c14_varchar_max], [c17_nvarchar_max], [c18_text], [c19_ntext], [c28_xml]) VALUES(?, ?, ?, ?, ?, ?)", array(1, $fin1, $fin2, $fin3, $fin4, $fin5));
    if ($stmt3 === false) {
        echo "sqlsrv_query(2) failed.\n";
        die(print_r(sqlsrv_errors(), true));
    }
    while ($sent = sqlsrv_send_stream_data($stmt3)) {
    }
    if ($sent === false) {
        echo "sqlsrv_send_stream_data(1) failed.\n";
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt3);
    fclose($fin1);
    fclose($fin2);
    fclose($fin3);
    fclose($fin4);
    fclose($fin5);
    $fin = fopen("nvarchar_max.txt", "r");
    $stmt4 = sqlsrv_query($conn2, "INSERT INTO [179886] ([c17_nvarchar_max]) VALUES(?)", array($fin));
    if ($stmt4 === false) {
        echo "sqlsrv_query(3) failed.\n";
        die(print_r(sqlsrv_errors(), true));
    }
    while ($sent = sqlsrv_send_stream_data($stmt4)) {
    }
    if ($sent === false) {
        echo "sqlsrv_send_stream_data(2) failed.\n";
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt4);
    fclose($fin);
    $fin = fopen("text.txt", "r");
    $stmt5 = sqlsrv_query($conn2, "INSERT INTO [179886] (c18_text) VALUES(?)", array($fin));
    if ($stmt5 === false) {
        echo "sqlsrv_query(4) failed.\n";
        die(print_r(sqlsrv_errors(), true));
    }
    while ($sent = sqlsrv_send_stream_data($stmt5)) {
    }
    if ($sent === false) {
        echo "sqlsrv_send_stream_data(3) failed.\n";
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt5);
    fclose($fin);
    $fin = fopen("ntext.txt", "r");
    $stmt6 = sqlsrv_query($conn2, "INSERT INTO [179886] (c19_ntext) VALUES(?)", array($fin));
    if ($stmt6 === false) {
        echo "sqlsrv_query(5) failed.\n";
        die(print_r(sqlsrv_errors(), true));
    }
    while ($sent = sqlsrv_send_stream_data($stmt6)) {
    }
    if ($sent === false) {
        echo "sqlsrv_send_stream_data(4) failed.\n";
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt6);
    fclose($fin);
    $fin = fopen("xml.txt", "r");
    $stmt7 = sqlsrv_query($conn2, "INSERT INTO [179886] (c28_xml) VALUES(?)", array($fin));
    if ($stmt7 === false) {
        echo "sqlsrv_query(6) failed.\n";
        die(print_r(sqlsrv_errors(), true));
    }
    while ($sent = sqlsrv_send_stream_data($stmt7)) {
    }
    if ($sent === false) {
        echo "sqlsrv_send_stream_data(5) failed.\n";
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt7);
    fclose($fin);

    $lens1 = array();
    $i = 0;

    // read the data to make sure it's the right length
    $stmt8 = sqlsrv_query($conn2, "SELECT * FROM [179886] WHERE [c1_int] = 1");
    $metadata1 = sqlsrv_field_metadata($stmt8);
    $count = count($metadata1);
    sqlsrv_fetch($stmt8);
    $value1 = GetField($stmt8, 13, $useUTF8);
    $lens1[$i++] = strlen($value1) . "\n";
    $fout = fopen("varchar_max.out", "w");
    fwrite($fout, $value1);
    fclose($fout);
    $value2 = GetField($stmt8, 16, $useUTF8);
    $lens1[$i++] = strlen($value2) . "\n";
    $fout = fopen("nvarchar_max.out", "w");
    fwrite($fout, $value2);
    fclose($fout);
    $value3 = GetField($stmt8, 17, $useUTF8);
    $lens1[$i++] = strlen($value3) . "\n";
    $fout = fopen("text.out", "w");
    fwrite($fout, $value3);
    fclose($fout);
    $value4 = GetField($stmt8, 18, $useUTF8);
    $lens1[$i++] = strlen($value4) . "\n";
    $fout = fopen("ntext.out", "w");
    fwrite($fout, $value4);
    fclose($fout);
    $value5 = GetField($stmt8, 27, $useUTF8);
    $lens1[$i++] = strlen($value5) . "\n";
    $fout = fopen("xml.out", "w");
    fwrite($fout, $value5);
    fclose($fout);
    sqlsrv_free_stmt($stmt8);

    // put the data back into the database
    $stmt3 = sqlsrv_query($conn2, "INSERT INTO [179886] ([c1_int], [c14_varchar_max], [c17_nvarchar_max], [c18_text], [c19_ntext], [c28_xml]) VALUES(?, ?, ?, ?, ?, ?)", array(2, $value1, $value2, $value3, $value4, $value5));
    if ($stmt3 === false) {
        echo "sqlsrv_query(7) failed.\n";
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt3);

    $lens2 = array();
    $i = 0;

    $stmt8 = sqlsrv_query($conn2, "SELECT * FROM [179886] WHERE [c1_int] = 2");
    $metadata1 = sqlsrv_field_metadata($stmt8);
    $count = count($metadata1);
    sqlsrv_fetch($stmt8);
    $value1 = GetField($stmt8, 13, $useUTF8);
    $lens2[$i++] = strlen($value1);
    $value2 = GetField($stmt8, 16, $useUTF8);
    $lens2[$i++] = strlen($value2) . "\n";
    $value3 = GetField($stmt8, 17, $useUTF8);
    $lens2[$i++] = strlen($value3) . "\n";
    $value4 = GetField($stmt8, 18, $useUTF8);
    $lens2[$i++] = strlen($value4) . "\n";
    $value5 = GetField($stmt8, 27, $useUTF8);
    $lens2[$i++] = strlen($value5) . "\n";

    CompareLengths($filesizes, $lens1, $lens2, $i, $useUTF8);

    echo "Test finished\n";

    sqlsrv_free_stmt($stmt8);
    sqlsrv_close($conn2);
}

function GetField($stmt, $idx, $useUTF8)
{
    if ($useUTF8) {
        return sqlsrv_get_field($stmt, $idx, SQLSRV_PHPTYPE_STRING('UTF-8'));
    } else {
        return sqlsrv_get_field($stmt, $idx, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    }
}

function CompareLengths($filesizes, $lens1, $lens2, $count, $useUTF8)
{
    if ($useUTF8) {
        // in Linux or Mac, same field should return same length, and strlen() for Unicode data is different
        for ($i = 0; $i < $count; $i++) {
            $length = $filesizes[$i];
            if ($lens1[$i] != $length || $lens2[$i] != $length) {
                echo "Data length mismatched!\n";
            }
        }
    } else {
        // in Windows, all lengths are equal
        $length = 1048576;  // number of characters in the data (in ANSI encoding)
        for ($i = 0; $i < $count; $i++) {
            if ($filesizes[$i] != $length) {
                echo "File $i size unexpected\n";
            }

            if ($lens1[$i] != $length || $lens2[$i] != $length) {
                echo "Data length mismatched!\n";
                }
        }
    }
}

// locale must be set before 1st connection
setUSAnsiLocale();

// test ansi only if windows or non-UTF8 locales are supported (ODBC 17 and above)
if (isLocaleSupported()) {
    setUTF8Data(false);
    runtest();
}
else {
    echo "Test finished\n";
}

// test utf8
setUTF8Data(true);
resetLocaleToDefault();
runtest();

?>
--EXPECT--
Test finished
Test finished

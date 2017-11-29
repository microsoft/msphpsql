--TEST--
new SQL Server 2008 date types.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
date_default_timezone_set('America/Los_Angeles');
sqlsrv_configure('WarningsReturnAsErrors', 0);
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);
sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_OFF);

require_once('MsCommon.inc');

// For testing in Azure, can not switch databases
$conn = AE\connect(array('ReturnDatesAsStrings' => true));
$tableName = '2008_date_types';
$columns = array(new AE\ColumnMeta('int', 'id'),
                 new AE\ColumnMeta('date', 'c1_date'),
                 new AE\ColumnMeta('time', 'c2_time'),
                 new AE\ColumnMeta('datetimeoffset', 'c3_datetimeoffset'),
                 new AE\ColumnMeta('datetime2', 'c4_datetime2'));
$stmt = AE\createTable($conn, $tableName, $columns);
if (!$stmt) {
    fatalError("Failed to create table $tableName\n");
}

// insert new date time types as strings (this works now)
$insertSql = "INSERT INTO [$tableName] (id, [c1_date], [c2_time], [c3_datetimeoffset], [c4_datetime2]) VALUES (?, ?, ?, ?, ?)";
$stmt = AE\executeQueryParams(
    $conn, 
    $insertSql,
    array(rand(0, 99999),
               array(strftime('%Y-%m-%d'), SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('utf-8'), SQLSRV_SQLTYPE_DATE),
               array(strftime('%H:%M:%S'), SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('utf-8'), SQLSRV_SQLTYPE_TIME),
               array(date_format(date_create(), 'Y-m-d H:i:s.u P'), SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('utf-8'), SQLSRV_SQLTYPE_DATETIMEOFFSET),
               array(date_format(date_create(), 'Y-m-d H:i:s.u'), SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('utf-8'), SQLSRV_SQLTYPE_DATETIME2)),
    false,
    "Insert 1 failed"
);

// insert new date time types as DateTime objects (this works now)
$stmt = AE\executeQueryParams(
    $conn,
    $insertSql,
    array(rand(0, 99999),
          array(date_create(), SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_DATETIME, SQLSRV_SQLTYPE_DATE),
          array(date_create(), SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_DATETIME, SQLSRV_SQLTYPE_TIME),
          array(date_create(), SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_DATETIME, SQLSRV_SQLTYPE_DATETIMEOFFSET),
          array(date_create(), SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_DATETIME, SQLSRV_SQLTYPE_DATETIME2)),
    false,
    "Insert 2 failed"
);

// insert new date time types as default DateTime objects with no type information (this works now)
$stmt = AE\executeQueryParams(
    $conn,
    $insertSql,
    array(rand(0, 99999), date_create(), date_create(), date_create(), date_create()),
    false,
    "Insert 3 failed"
);

// insert new date time types as strings with no type information (this works)
$stmt = AE\executeQueryParams(
    $conn,
    $insertSql,
    array(rand(0, 99999), strftime('%Y-%m-%d'), strftime('%H:%M:%S'), date_format(date_create(), 'Y-m-d H:i:s.u P'), date_format(date_create(), 'Y-m-d H:i:s.u P')),
    false,
    "Insert 4 failed"
);

// retrieve date time fields as strings (this works)
$stmt = sqlsrv_query($conn, "SELECT * FROM [$tableName]");
while (sqlsrv_fetch($stmt)) {
    for ($i = 0; $i < sqlsrv_num_fields($stmt); ++$i) {
        $fld = sqlsrv_get_field($stmt, $i, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "field $i = $fld\n";
    }
}

// retrieve date time fields as default (should come back as DateTime objects) (this works now)
$stmt = sqlsrv_query($conn, "SELECT * FROM [$tableName]");
if ($stmt === false) {
    fatalError("Select from table failed");
}
while ($row = sqlsrv_fetch_array($stmt)) {
    var_dump($row);
}

// retrieve date itme fields as DateTime objects
$stmt = sqlsrv_query($conn, "SELECT * FROM [$tableName]");
while (sqlsrv_fetch($stmt)) {
    for ($i = 1; $i < sqlsrv_num_fields($stmt); ++$i) {
        $fld = sqlsrv_get_field($stmt, $i, SQLSRV_PHPTYPE_DATETIME);
        $str = date_format($fld, 'Y-m-d H:i:s.u P');
        echo "field $i = $str\n";
    }
}

print_r(sqlsrv_field_metadata($stmt));

sqlsrv_close($conn);

?>
--EXPECTREGEX--
field 0 = [0-9]{1,5}
field 1 = [0-9]{4}\-[0-9]{2}\-[0-9]{2}
field 2 = [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{7}
field 3 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{7} \-0[7-8]:00
field 4 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{7}
field 0 = [0-9]{1,5}
field 1 = [0-9]{4}\-[0-9]{2}\-[0-9]{2}
field 2 = [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{7}
field 3 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{7} \-0[7-8]:00
field 4 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{7}
field 0 = [0-9]{1,5}
field 1 = [0-9]{4}\-[0-9]{2}\-[0-9]{2}
field 2 = [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{7}
field 3 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{7} \-0[7-8]:00
field 4 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{7}
field 0 = [0-9]{1,5}
field 1 = [0-9]{4}\-[0-9]{2}\-[0-9]{2}
field 2 = [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{7}
field 3 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{7} \-0[7-8]:00
field 4 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{7}
array\(10\) {
  \[0\]=>
  int\([0-9]{1,5}\)
  \["id"\]=>
  int\([0-9]{1,5}\)
  \[1\]=>
  string\(10\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2}"
  \["c1_date"\]=>
  string\(10\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2}"
  \[2\]=>
  string\(16\) "[0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
  \["c2_time"\]=>
  string\(16\) "[0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
  \[3\]=>
  string\(34\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7} \-0[7-8]:00"
  \["c3_datetimeoffset"\]=>
  string\(34\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7} \-0[7-8]:00"
  \[4\]=>
  string\(27\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
  \["c4_datetime2"\]=>
  string\(27\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
}
array\(10\) {
  \[0\]=>
  int\([0-9]{1,5}\)
  \["id"\]=>
  int\([0-9]{1,5}\)
  \[1\]=>
  string\(10\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2}"
  \["c1_date"\]=>
  string\(10\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2}"
  \[2\]=>
  string\(16\) "[0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
  \["c2_time"\]=>
  string\(16\) "[0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
  \[3\]=>
  string\(34\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7} \-0[7-8]:00"
  \["c3_datetimeoffset"\]=>
  string\(34\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7} \-0[7-8]:00"
  \[4\]=>
  string\(27\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
  \["c4_datetime2"\]=>
  string\(27\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
}
array\(10\) {
  \[0\]=>
  int\([0-9]{1,5}\)
  \["id"\]=>
  int\([0-9]{1,5}\)
  \[1\]=>
  string\(10\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2}"
  \["c1_date"\]=>
  string\(10\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2}"
  \[2\]=>
  string\(16\) "[0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
  \["c2_time"\]=>
  string\(16\) "[0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
  \[3\]=>
  string\(34\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7} \-0[7-8]:00"
  \["c3_datetimeoffset"\]=>
  string\(34\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7} \-0[7-8]:00"
  \[4\]=>
  string\(27\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
  \["c4_datetime2"\]=>
  string\(27\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
}
array\(10\) {
  \[0\]=>
  int\([0-9]{1,5}\)
  \["id"\]=>
  int\([0-9]{1,5}\)
  \[1\]=>
  string\(10\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2}"
  \["c1_date"\]=>
  string\(10\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2}"
  \[2\]=>
  string\(16\) "[0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
  \["c2_time"\]=>
  string\(16\) "[0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
  \[3\]=>
  string\(34\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7} \-0[7-8]:00"
  \["c3_datetimeoffset"\]=>
  string\(34\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7} \-0[7-8]:00"
  \[4\]=>
  string\(27\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
  \["c4_datetime2"\]=>
  string\(27\) "[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]{7}"
}
field 1 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 2 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 3 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 4 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 1 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 2 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 3 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 4 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 1 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 2 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 3 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 4 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 1 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 2 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 3 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
field 4 = [0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{6} \-0[7-8]:00
Array
\(
    \[0\] => Array
        \(
            \[Name\] => id
            \[Type\] => 4
            \[Size\] => 
            \[Precision\] => 10
            \[Scale\] => 
            \[Nullable\] => 1
        \)

    \[1\] => Array
        \(
            \[Name\] => c1_date
            \[Type\] => 91
            \[Size\] => 
            \[Precision\] => 10
            \[Scale\] => 0
            \[Nullable\] => 1
        \)

    \[2\] => Array
        \(
            \[Name\] => c2_time
            \[Type\] => -154
            \[Size\] => 
            \[Precision\] => 16
            \[Scale\] => 7
            \[Nullable\] => 1
        \)

    \[3\] => Array
        \(
            \[Name\] => c3_datetimeoffset
            \[Type\] => -155
            \[Size\] => 
            \[Precision\] => 34
            \[Scale\] => 7
            \[Nullable\] => 1
        \)

    \[4\] => Array
        \(
            \[Name\] => c4_datetime2
            \[Type\] => 93
            \[Size\] => 
            \[Precision\] => 27
            \[Scale\] => 7
            \[Nullable\] => 1
        \)

\)

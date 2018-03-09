--TEST--
Test various date and time types with AE and ReturnDatesAsStrings set to true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
// Check for expected errors. These are expected in cases where the dates and
// times do not comply with ODBC standards.
function ExecutePreparedStmt($stmt)
{
    if ($stmt == false) {
        fatalError("Failure in sqlsrv_prepare");
    } else {
        $r = sqlsrv_execute($stmt);
        if ($r == false) {
            $errors = sqlsrv_errors();
            if ($errors[0]['SQLSTATE'] != '22018' and
                $errors[0]['SQLSTATE'] != '22008' and
                $errors[0]['SQLSTATE'] != '22007' and
                $errors[0]['SQLSTATE'] != '07006') {
                print_r($errors);
                fatalError("Unexpected error");
            }
        }
    }
}

// Compare dates retrieved from the database with the date used for testing.
// $testingDate is an array of strings like '2002-01-31 23:59:59.049876' and
// $retrieved_date is the string date of the format 'Y-m-d H:i:s.u', which 
// the format returned by the date_format() calls below or by retrieval as 
// strings, unless $datetimetype is date or time. In that case, if 
// $returnStrings is false and $datetimetype is 'date', the time defaults to
// 00:00:00.0000, and if $datetimetype is 'time' and $returnStrings is false,
// the date defaults to the current date. If however $returnStrings is true
// and $datetimetype is 'date', $retrieved_date is only a date,
// and if $datetimetype is 'time' and $returnStrings is true, $retrieved_date
// only a time.
// The concatenations involving zeroes below are to make direct string
// comparisons feasible. Also, because PHP maxes out at microsecond precision
// and SQL Server maxes out at 0.1 microsecond precision, the more precise 
// types require an extra 0 for some comparisons.
function CompareDateTime($datetimetype, $returnStrings, &$testingDate, $retrievedDate)
{
    $test_date_time = array();

    for ($i=0; $i<sizeof($testingDate); ++$i) {
        $test_date_time[] = explode(" ", $testingDate[$i]);
    }
    
    $ret_date_time = explode(" ", $retrievedDate);
    if (sizeof($ret_date_time)>1) {
        $datetimeonly = $ret_date_time[0]." ".$ret_date_time[1];
    }
    if (sizeof($ret_date_time)>2) {
        $timezone = $ret_date_time[2];
        $dtoffset = $ret_date_time[0]." ".substr($ret_date_time[1], 0, -1)." ".$ret_date_time[2];
    }
   
    if ($returnStrings == true) {
        switch ($datetimetype) {
            case 'date':
                if ($retrievedDate != $test_date_time[0][0]) {
                    fatalError("Dates do not match!");
                }                    
                break;
            case 'time':
                if ($retrievedDate != $test_date_time[0][1]."0" and
                    $retrievedDate != $test_date_time[1][1]."0" and
                    $retrievedDate != $test_date_time[2][1]."0" and
                    $retrievedDate != $test_date_time[3][1]."0" ) {
                        fatalError("Times do not match!");
                }
                break;
            case 'datetime':
                if ($retrievedDate."000" != $testingDate[0] and
                    $retrievedDate."000" != $testingDate[1] and
                    $retrievedDate."000" != $testingDate[2] and
                    $retrievedDate."000" != $testingDate[3] ) {
                        fatalError("Datetimes do not match!");
                }
                break;
            case 'datetime2':
                if ($retrievedDate != $testingDate[0]."0" and
                    $retrievedDate != $testingDate[1]."0" and
                    $retrievedDate != $testingDate[2]."0" and
                    $retrievedDate != $testingDate[3]."0" ) {
                        fatalError("Datetime2s do not match!");
                }
                break;
            case 'datetimeoffset':
                if ($dtoffset != $testingDate[4] and
                    $dtoffset != $testingDate[5] and
                    $dtoffset != $testingDate[6] and
                    $dtoffset != $testingDate[7] ) {
                        fatalError("Datetimeoffsets do not match!");
                }
                break;
            case 'smalldatetime':
                if ($retrievedDate.".000000" != $testingDate[0] and
                    $retrievedDate.".000000" != $testingDate[1] and
                    $retrievedDate.".000000" != $testingDate[2] and
                    $retrievedDate.".000000" != $testingDate[3] ) {
                        fatalError("Smalldatetimes do not match!");
                }
                break;
        }
    }
    else {
        switch ($datetimetype) {
            case 'date':
                if ($ret_date_time[0] != $test_date_time[0][0]) {
                    fatalError("Dates do not match!");
                }                    
                break;
            case 'time':
                if ($ret_date_time[1] != $test_date_time[0][1] and
                    $ret_date_time[1] != $test_date_time[1][1] and
                    $ret_date_time[1] != $test_date_time[2][1] and
                    $ret_date_time[1] != $test_date_time[3][1] ) {
                        fatalError("Times do not match!");
                }
                break;
            case 'datetime':
                if ($datetimeonly != $testingDate[0] and
                    $datetimeonly != $testingDate[1] and
                    $datetimeonly != $testingDate[2] and
                    $datetimeonly != $testingDate[3] ) {
                        fatalError("Datetimes do not match!");
                }
                break;
            case 'datetime2':
                if ($datetimeonly != $testingDate[0] and
                    $datetimeonly != $testingDate[1] and
                    $datetimeonly != $testingDate[2] and
                    $datetimeonly != $testingDate[3] ) {
                        fatalError("Datetime2s do not match!");
                }
                break;
            case 'datetimeoffset':
                if ($retrievedDate != $testingDate[4] and
                    $retrievedDate != $testingDate[5] and
                    $retrievedDate != $testingDate[6] and
                    $retrievedDate != $testingDate[7] ) {
                        fatalError("Datetimeoffsets do not match!");
                }
                break;
            case 'smalldatetime':
                if ($datetimeonly != $testingDate[0] and
                    $datetimeonly != $testingDate[1] and
                    $datetimeonly != $testingDate[2] and
                    $datetimeonly != $testingDate[3] ) {
                        fatalError("Smalldatetimes do not match!");
                }
                break;
        }
    }
}    

function InsertDatesAndOrTimes($conn, $datetimetype, &$formats_array, $array_size, $SQLSRV_SQLTYPE_CONST)
{
    $tableName = "table_of_$datetimetype";
    $columns = array(new AE\ColumnMeta('int', 'id'),
                     new AE\ColumnMeta("$datetimetype", "c1_$datetimetype"));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    $insertSql = "INSERT INTO [$tableName] (id, [c1_$datetimetype]) VALUES (?, ?)";
    for ($i=0; $i<$array_size; $i++)
    {
        $stmt = sqlsrv_prepare($conn, $insertSql, array($i, array($formats_array[$i], SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('utf-8'), $SQLSRV_SQLTYPE_CONST)));
        ExecutePreparedStmt($stmt);
        $stmt = sqlsrv_prepare($conn, $insertSql, array($i, $formats_array[$i]));
        ExecutePreparedStmt($stmt);

        // date_create can fail if the argument is not a format PHP recognises;
        // this is not a problem for this test.
        if (date_create($formats_array[$i]) != false) {
            $stmt = sqlsrv_prepare($conn, $insertSql, array($i, array(date_create($formats_array[$i]), SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('utf-8'), $SQLSRV_SQLTYPE_CONST)));
            ExecutePreparedStmt($stmt);
            $stmt = sqlsrv_prepare($conn, $insertSql, array($i, date_create($formats_array[$i])));
            ExecutePreparedStmt($stmt);
        }
    }
}

function FetchDatesAndOrTimes($conn, $datetimetype, &$testingDate, $returnDatesAsStrings)
{
    $tableName = "table_of_$datetimetype";
    
    echo "Select fields as strings:\n";
    
    $stmt = sqlsrv_query($conn, "SELECT * FROM [$tableName]");
    if ($stmt === false) {
        fatalError("Select from $tableName failed");
    }
    
    while (sqlsrv_fetch($stmt)) {
        $idnum = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        $datetime = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        
        if (!is_string($datetime)) {
            fatalError("sqlsrv_get_field did not return string but string was specified");
        }

        CompareDateTime($datetimetype, true, $testingDate, $datetime);
    }
    
    // retrieve date time fields as DateTime objects
    echo "Select fields as DateTime objects:\n";
    
    $stmt = sqlsrv_query($conn, "SELECT * FROM [$tableName]");
    if ($stmt === false) {
        fatalError("Select from $tableName failed");
    }
    
    while (sqlsrv_fetch($stmt)) {
        $idnum = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        $datetime = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_DATETIME);
        
        if (!($datetime instanceof DateTime)) {
            fatalError("sqlsrv_get_field did not return DateTime but DateTime was specified");
        } 
            
        $datetime = ($datetimetype == 'datetimeoffset') ? date_format($datetime, 'Y-m-d H:i:s.u P') : date_format($datetime, 'Y-m-d H:i:s.u');
        
        CompareDateTime($datetimetype, false, $testingDate, $datetime);
    }

    // retrieve date time fields without explicitly requesting the type
    echo "Select fields with no type information provided:\n";
    
    $stmt = sqlsrv_query($conn, "SELECT * FROM [$tableName]");
    if ($stmt === false) {
        fatalError("Select from $tableName failed");
    }
    
    while (sqlsrv_fetch($stmt)) {
        $idnum = sqlsrv_get_field($stmt, 0);
        $datetime = sqlsrv_get_field($stmt, 1);
        
        if ($returnDatesAsStrings == true) {
            if (!is_string($datetime)) {
                fatalError("String for date expected, not a string");
            }
            
            CompareDateTime($datetimetype, $returnDatesAsStrings, $testingDate, $datetime);
        } 
        else { // ReturnsDatesAsStrings is false
            if (!($datetime instanceof DateTime)) {
                fatalError("DateTime object expected, not a DateTime");
            }
            
            $datetime = ($datetimetype == 'datetimeoffset') ? date_format($datetime, 'Y-m-d H:i:s.u P') : date_format($datetime, 'Y-m-d H:i:s.u');
            
            CompareDateTime($datetimetype, $returnDatesAsStrings, $testingDate, $datetime);
        }
    }

    // retrieve date time fields as default
    echo "Select using fetch_array:\n";
    
    $stmt = sqlsrv_query($conn, "SELECT * FROM [$tableName]");
    if ($stmt === false) {
        fatalError("Select from $tableName failed");
    }
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)) {
        $idnum = $row[0];
        
        if ($returnDatesAsStrings == true) {
            if (!is_string($row[1])) {
                fatalError("String for date expected, not a string");
            }
            
            CompareDateTime($datetimetype, $returnDatesAsStrings, $testingDate, $row[1]);
        } 
        else {
            if (!($row[1] instanceof DateTime)) {
                fatalError("DateTime object expected, not a DateTime");
            }

            $datetime = ($datetimetype == 'datetimeoffset') ? date_format($row[1], 'Y-m-d H:i:s.u P') : date_format($row[1], 'Y-m-d H:i:s.u');
            
            CompareDateTime($datetimetype, $returnDatesAsStrings, $testingDate, $datetime);
        }
    }
    
    print_r(sqlsrv_field_metadata($stmt)[1]);

}

// The date used for the test will be Januray 31, 2002, or 2002/01/31.
// This will sidestep issues involving the use of two digit years since 
// SQL Server defaults to 19 as the first two digits.
// Time is 23:59:29.049876
// Note that smalldatetime rounds to the nearest minute, and that may cause 
// this test to fail if it rolls over to the next day.
// Incidentally, this datetime corresponds to a timestamp of 1012521599.
$year = '2002';
$month = '01';
$month_name = 'January';
$month_abbr = 'Jan';
$day = '31';
$hour = '23';
$hour12 = '11';
$meridian = 'PM';
$minute = '59';
$second = '29';
$frac = '04';
$frac2 = '9876';
$tz_correction = '+08:00';

$testingDate = array($year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".".$frac.$frac2,
                      $year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".".$frac."0000",
                      $year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".000000",
                      $year."-".$month."-".$day." ".$hour.":".$minute.":00.000000",
                      $year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".".$frac.$frac2." ".$tz_correction,
                      $year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".".$frac."0000 ".$tz_correction,
                      $year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".000000 ".$tz_correction,
                      $year."-".$month."-".$day." ".$hour.":".$minute.":00.000000 ".$tz_correction,
                      );
 
// These formats are for the ODBC driver with types specified in sqlsrv_prepare()
$date_formats = array($year."-".$month."-".$day
                      );
$time_formats = array($hour.":".$minute.":".$second,
                      $hour.":".$minute.":".$second.".".$frac,
                      $hour.":".$minute.":".$second.".".$frac.$frac2
                      );

// These formats are not accepted by either the ODBC driver or by PHP, but
// can possibly be wrangled in sqlsrv_prepare() using strings instead of
// the dedicated date and time types.
$date_formats_nonODBC = array($year."/".$month."/".$day,
                              $month."/".$day."/".$year,
                              $month."-".$day."-".$year,
                              $day."/".$month."/".$year,
                              $day."-".$month."-".$year,
                              $month_name." ".$day.", ".$year,
                              $day."-".$month_name."-".$year,
                              $day."-".$month_abbr."-".$year
                              );
$time_formats_nonODBC = array($hour12.":".$minute." ".$meridian,
                              $hour12.":".$minute.":".$second." ".$meridian,
                              $hour12.":".$minute.":".$second.".".$frac." ".$meridian,
                              $hour12.":".$minute.":".$second.".".$frac.$frac2." ".$meridian,
                              $hour.":".$minute
                              );

// Create arrays containing the ODBC-standard formats, and larger arrays
// containing the non-standard formats, for the supported SQL Server
// date and time types.
$date_formats_all = array_merge($date_formats, $date_formats_nonODBC);
$time_formats_all = array_merge($time_formats, $time_formats_nonODBC);

$datetime_formats_all = array();
$datetime2_formats_all = array();
$datetimeoffset_formats_all = array();
$datetimesmall_formats_all = array();

$SZ_TIME_all = sizeof($time_formats_all);
$SZ_DATE_all = sizeof($date_formats_all);
$SZ_DATETIME_all = $SZ_TIME_all*$SZ_DATE_all;

// Create compound date/time types. For datetime, remove the extra precision
// of $frac2. For smalldatetime, remove the extra precision of $frac and $frac2.
// If the data in $frac and/or $frac2 is found elsewhere in the date/time, the
// data will be garbled. For example, if the year is 2002 and $frac2 is 2002, 
// the code below will remove any instances of '2002' in the datetime and
// smalldatetime strings, producing garbage for those types. User must be
// cognizant of this when testing different dates and times.
for ($i=0; $i<$SZ_DATE_all; $i++)
{
    for ($j=0; $j<$SZ_TIME_all; $j++)
    {
        $datetime_formats_all[] = str_replace($frac2, "", $date_formats_all[$i]." ".$time_formats_all[$j]);
        $datetime2_formats_all[] = $date_formats_all[$i]." ".$time_formats_all[$j];
        $datetimeoffset_formats_all[] = $date_formats_all[$i]." ".$time_formats_all[$j].$tz_correction;
        if (str_replace(".".$frac.$frac2, "", $date_formats_all[$i]." ".$time_formats_all[$j]) == ($date_formats_all[$i]." ".$time_formats_all[$j])) {
            $datetimesmall_formats_all[] = str_replace(".".$frac, "", $date_formats_all[$i]." ".$time_formats_all[$j]);
        }
        else {
            $datetimesmall_formats_all[] = str_replace(".".$frac.$frac2, "", $date_formats_all[$i]." ".$time_formats_all[$j]);
        }
    }
}

date_default_timezone_set('Canada/Pacific');
sqlsrv_configure('WarningsReturnAsErrors', 0);
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);
sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_OFF);

require_once('MsCommon.inc');

$returnDatesAsStrings = true;

$conn = AE\connect(array('ReturnDatesAsStrings' => $returnDatesAsStrings));

InsertDatesAndOrTimes($conn, 'date', $date_formats_all, $SZ_DATE_all, SQLSRV_SQLTYPE_DATE);
InsertDatesAndOrTimes($conn, 'time', $time_formats_all, $SZ_TIME_all, SQLSRV_SQLTYPE_TIME);
InsertDatesAndOrTimes($conn, 'datetime', $datetime_formats_all, $SZ_DATETIME_all, SQLSRV_SQLTYPE_DATETIME);
InsertDatesAndOrTimes($conn, 'datetime2', $datetime2_formats_all, $SZ_DATETIME_all, SQLSRV_SQLTYPE_DATETIME2);
InsertDatesAndOrTimes($conn, 'datetimeoffset', $datetimeoffset_formats_all, $SZ_DATETIME_all, SQLSRV_SQLTYPE_DATETIMEOFFSET);
InsertDatesAndOrTimes($conn, 'smalldatetime', $datetimesmall_formats_all, $SZ_DATETIME_all, SQLSRV_SQLTYPE_SMALLDATETIME);

FetchDatesAndOrTimes($conn, 'date', $testingDate, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'time', $testingDate, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'datetime', $testingDate, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'datetime2', $testingDate, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'datetimeoffset', $testingDate, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'smalldatetime', $testingDate, $returnDatesAsStrings);

sqlsrv_close($conn);

$returnDatesAsStrings = false;

$conn = AE\connect(array('ReturnDatesAsStrings' => $returnDatesAsStrings));

InsertDatesAndOrTimes($conn, 'date', $date_formats_all, $SZ_DATE_all, SQLSRV_SQLTYPE_DATE);
InsertDatesAndOrTimes($conn, 'time', $time_formats_all, $SZ_TIME_all, SQLSRV_SQLTYPE_TIME);
InsertDatesAndOrTimes($conn, 'datetime', $datetime_formats_all, $SZ_DATETIME_all, SQLSRV_SQLTYPE_DATETIME);
InsertDatesAndOrTimes($conn, 'datetime2', $datetime2_formats_all, $SZ_DATETIME_all, SQLSRV_SQLTYPE_DATETIME2);
InsertDatesAndOrTimes($conn, 'datetimeoffset', $datetimeoffset_formats_all, $SZ_DATETIME_all, SQLSRV_SQLTYPE_DATETIMEOFFSET);
InsertDatesAndOrTimes($conn, 'smalldatetime', $datetimesmall_formats_all, $SZ_DATETIME_all, SQLSRV_SQLTYPE_SMALLDATETIME);

FetchDatesAndOrTimes($conn, 'date', $testingDate, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'time', $testingDate, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'datetime', $testingDate, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'datetime2', $testingDate, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'datetimeoffset', $testingDate, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'smalldatetime', $testingDate, $returnDatesAsStrings);

sqlsrv_close($conn);

?>
--EXPECT--
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Array
(
    [Name] => c1_date
    [Type] => 91
    [Size] => 
    [Precision] => 10
    [Scale] => 0
    [Nullable] => 1
)
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Array
(
    [Name] => c1_time
    [Type] => -154
    [Size] => 
    [Precision] => 16
    [Scale] => 7
    [Nullable] => 1
)
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Array
(
    [Name] => c1_datetime
    [Type] => 93
    [Size] => 
    [Precision] => 23
    [Scale] => 3
    [Nullable] => 1
)
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Array
(
    [Name] => c1_datetime2
    [Type] => 93
    [Size] => 
    [Precision] => 27
    [Scale] => 7
    [Nullable] => 1
)
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Array
(
    [Name] => c1_datetimeoffset
    [Type] => -155
    [Size] => 
    [Precision] => 34
    [Scale] => 7
    [Nullable] => 1
)
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Array
(
    [Name] => c1_smalldatetime
    [Type] => 93
    [Size] => 
    [Precision] => 16
    [Scale] => 0
    [Nullable] => 1
)
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Array
(
    [Name] => c1_date
    [Type] => 91
    [Size] => 
    [Precision] => 10
    [Scale] => 0
    [Nullable] => 1
)
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Array
(
    [Name] => c1_time
    [Type] => -154
    [Size] => 
    [Precision] => 16
    [Scale] => 7
    [Nullable] => 1
)
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Array
(
    [Name] => c1_datetime
    [Type] => 93
    [Size] => 
    [Precision] => 23
    [Scale] => 3
    [Nullable] => 1
)
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Array
(
    [Name] => c1_datetime2
    [Type] => 93
    [Size] => 
    [Precision] => 27
    [Scale] => 7
    [Nullable] => 1
)
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Array
(
    [Name] => c1_datetimeoffset
    [Type] => -155
    [Size] => 
    [Precision] => 34
    [Scale] => 7
    [Nullable] => 1
)
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Array
(
    [Name] => c1_smalldatetime
    [Type] => 93
    [Size] => 
    [Precision] => 16
    [Scale] => 0
    [Nullable] => 1
)
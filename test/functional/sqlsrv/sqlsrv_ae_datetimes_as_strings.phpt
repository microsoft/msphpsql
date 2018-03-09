--TEST--
Test various date and time types with AE and ReturnDatesAsStrings set to true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
// Check for expected errors. These are expected in cases where the dates and
// times do not comply with ODBC standards.
// 07006 Restricted data type attribute violation (Conversion failed)
// 22007 Invalid datetime format (ODBC accepts only a few formats)
// 22008 Datetime field overflow (Outside range)
// 22018 Invalid character value for cast specification
function ExecutePreparedStmt($stmt)
{
    if ($stmt == false) {
        fatalError("Failure in sqlsrv_prepare");
    } else {
        $r = sqlsrv_execute($stmt);
        if ($r == false) {
            $errors = sqlsrv_errors();
            if ($errors[0]['SQLSTATE'] != '07006' and
                $errors[0]['SQLSTATE'] != '22007' and
                $errors[0]['SQLSTATE'] != '22008' and
                $errors[0]['SQLSTATE'] != '22018') {
                print_r($errors);
                fatalError("Unexpected error");
            }
        }
    }
}

// Compare dates retrieved from the database with the date used for testing.
// $expectedDateTime is an array of strings like '2002-01-31 23:59:59.049876'
// $retrievedDateTime is date/time string of format either 'Y-m-d H:i:s.u' or
// 'Y-m-d H:i:s.u P', which is the format returned when $returnDatesAsStrings 
// is true, or by the date_format() calls in FetchDatesAndOrTimes when a PHP 
// DateTime object is retrieved (i.e. when $returnDatesAsStrings is false),
// unless $datetimetype is date or time. In those cases:
// If $datetimetype is date and $returnDatesAsStrings is false:
//   The date is as expected, the time defaults to 00:00:00.0000
// If $datetimetype is time and $returnStrings is false:
//   The date defaults to the current date, the time is as expected. 
// If $datetimetype is date and $returnStrings is true:
//   $retrievedDateTime is only a date.
// If $datetimetype is 'time' and $returnStrings is true:
//   $retrievedDateTime is only a time.
function CompareDateTime($datetimetype, $returnStrings, &$expectedDateTime, $retrievedDateTime)
{
    $expected_date_time = array();

    // Split each element of the testing date/time into
    // [0]:date, [1]:time, and possibly [2]:timezone offset 
    for ($i=0; $i<sizeof($expectedDateTime); ++$i) {
        $expected_date_time[] = explode(" ", $expectedDateTime[$i]);
    }
    
    // If $retrievedDateTime is a string of format 'Y-m-d H:i:s.u' or 'Y-m-d H:i:s.u P',
    // split it into [0]:date, [1]:time, and possibly [2]:timezone offset
    $retrieved_date_time = explode(" ", $retrievedDateTime);
   
    if ($returnStrings == true) {
        switch ($datetimetype) {
            case 'date':
                // Direct comparison of retrieved date and expected date
                if ($retrievedDateTime != $expected_date_time[0][0]) {
                    fatalError("Dates do not match!");
                }                    
                break;
            case 'time': 
                // Compare SQL time with expected time. The expected time was input
                // with an accuracy of microseconds and the SQL Servertime type has
                // accuracy to 100 ns, so times are returned with an extra zero. For
                // comparison the zero is appended to the times in expected_time_date.
                if ($retrievedDateTime != $expected_date_time[0][1]."0" and
                    $retrievedDateTime != $expected_date_time[1][1]."0" and
                    $retrievedDateTime != $expected_date_time[2][1]."0" and
                    $retrievedDateTime != $expected_date_time[3][1]."0" ) {
                        fatalError("Times do not match!");
                }
                break;
            case 'datetime':
                // Compare retrieved SQL datetime with expected date/time.
                // SQL Server's datetime type is accurate to milliseconds and
                // the expected time is accurate to microseconds, so append
                // three zeroes to the retrieved time for comparison.
                if ($retrievedDateTime."000" != $expectedDateTime[0] and
                    $retrievedDateTime."000" != $expectedDateTime[1] and
                    $retrievedDateTime."000" != $expectedDateTime[2] and
                    $retrievedDateTime."000" != $expectedDateTime[3] ) {
                        fatalError("Datetimes do not match!");
                }
                break;
            case 'datetime2':
                // Compare retrieved SQL datetime2 with expected date/time.
                // SQL Server's datetime2 type is accurate to 100 ns and
                // the expected time is accurate to microseconds, so append
                // a zero to the expected time for comparison.
                if ($retrievedDateTime != $expectedDateTime[0]."0" and
                    $retrievedDateTime != $expectedDateTime[1]."0" and
                    $retrievedDateTime != $expectedDateTime[2]."0" and
                    $retrievedDateTime != $expectedDateTime[3]."0" ) {
                        fatalError("Datetime2s do not match!");
                }
                break;
            case 'datetimeoffset':
                // Compare the SQL datetimeoffset retrieved with expected
                // date/time. datetimeoffset is accurate to 100 ns, so the
                // extra zero is removed in $dtoffset to create a format accurate 
                // to microseconds for comparison with the expected date/time/timezone.
                $dtoffset = $retrieved_date_time[0]." ".substr($retrieved_date_time[1], 0, -1)." ".$retrieved_date_time[2];
                if ($dtoffset != $expectedDateTime[4] and
                    $dtoffset != $expectedDateTime[5] and
                    $dtoffset != $expectedDateTime[6] and
                    $dtoffset != $expectedDateTime[7] ) {
                        fatalError("Datetimeoffsets do not match!");
                }
                break;
            case 'smalldatetime':
                // Compare retrieved SQL smalldatetime with expected date/time.
                // SQL Server's smalldatetime type is accurate to seconds and
                // the expected time is accurate to microseconds, so append
                // '.000000' to the expected time for comparison.
                if ($retrievedDateTime.".000000" != $expectedDateTime[0] and
                    $retrievedDateTime.".000000" != $expectedDateTime[1] and
                    $retrievedDateTime.".000000" != $expectedDateTime[2] and
                    $retrievedDateTime.".000000" != $expectedDateTime[3] ) {
                        fatalError("Smalldatetimes do not match!");
                }
                break;
        }
    }
    else {
        // Combine the retrieved date and time. 
        if (sizeof($retrieved_date_time)>1) {
            $date_time_only = $retrieved_date_time[0]." ".$retrieved_date_time[1];
        }
        
        // Times returned by SQL Server are accurate to 100 ns, but when
        // formatted using PHP's date_format() function, the times are accurate
        // to microseconds. So both retrieved and expected times are accurate
        // to microseconds, and no need for adding zeroes in any of the
        // comparisons below.
        switch ($datetimetype) {
            case 'date':
                // Comparison of dates only.
                if ($retrieved_date_time[0] != $expected_date_time[0][0]) {
                    fatalError("Dates do not match!");
                }                    
                break;
            case 'time':
                // Comparison of times only.
                if ($retrieved_date_time[1] != $expected_date_time[0][1] and
                    $retrieved_date_time[1] != $expected_date_time[1][1] and
                    $retrieved_date_time[1] != $expected_date_time[2][1] and
                    $retrieved_date_time[1] != $expected_date_time[3][1] ) {
                        fatalError("Times do not match!");
                }
                break;
            case 'datetime':
            case 'datetime2':
            case 'smalldatetime':
                // Test combined date and time. The $expectedDateTime values
                // all have a different number of trailing zeroes to match
                // the precision of different SQL types.
                if ($date_time_only != $expectedDateTime[0] and
                    $date_time_only != $expectedDateTime[1] and
                    $date_time_only != $expectedDateTime[2] and
                    $date_time_only != $expectedDateTime[3] ) {
                        fatalError("Datetimes do not match!");
                }
                break;
            case 'datetimeoffset':
                // The retrieved date/time string will have a timezone
                // correction appended to it when the returned type is
                // datetimeoffset.
                if ($retrievedDateTime != $expectedDateTime[4] and
                    $retrievedDateTime != $expectedDateTime[5] and
                    $retrievedDateTime != $expectedDateTime[6] and
                    $retrievedDateTime != $expectedDateTime[7] ) {
                        fatalError("Datetimeoffsets do not match!");
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
    for ($i=0; $i<$array_size; $i++) {
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

function FetchDatesAndOrTimes($conn, $datetimetype, &$expectedDateTime, $returnDatesAsStrings)
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

        CompareDateTime($datetimetype, true, $expectedDateTime, $datetime);
    }
    
    // retrieve date time fields as DateTime objects
    // format them as strings using date_format() for comparison
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
        
        CompareDateTime($datetimetype, false, $expectedDateTime, $datetime);
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
            
            CompareDateTime($datetimetype, $returnDatesAsStrings, $expectedDateTime, $datetime);
        } 
        else { // ReturnsDatesAsStrings is false
            if (!($datetime instanceof DateTime)) {
                fatalError("DateTime object expected, not a DateTime");
            }
            
            $datetime = ($datetimetype == 'datetimeoffset') ? date_format($datetime, 'Y-m-d H:i:s.u P') : date_format($datetime, 'Y-m-d H:i:s.u');
            
            CompareDateTime($datetimetype, $returnDatesAsStrings, $expectedDateTime, $datetime);
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
            
            CompareDateTime($datetimetype, $returnDatesAsStrings, $expectedDateTime, $row[1]);
        } 
        else {
            if (!($row[1] instanceof DateTime)) {
                fatalError("DateTime object expected, not a DateTime");
            }

            $datetime = ($datetimetype == 'datetimeoffset') ? date_format($row[1], 'Y-m-d H:i:s.u P') : date_format($row[1], 'Y-m-d H:i:s.u');
            
            CompareDateTime($datetimetype, $returnDatesAsStrings, $expectedDateTime, $datetime);
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

// This is the array of dates/times/timezones to test against. They have
// different numbers of trailing zeroes to match the precision of different
// SQL date and time types, but all go up to microseconds because that's
// how PHP formats times with date_format(), allowing direct string comparisons
// when the DateTime objects retrieved from a table are formatted as strings
// with date_format().
$expectedDateTime = array($year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".".$frac.$frac2,
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

// Create compound date/time/timezone arrays corresponding to the SQL Server
// date/time types by concatenating the dates and times from above. For the
// datetime type, remove the extra precision of $frac2. For the smalldatetime
// type, remove the extra precision of $frac and $frac2. If the numerical
// string in $frac and/or $frac2 is found elsewhere in the date/time, the data
// will be garbled. For example, if the year is 2002 and $frac2 is 2002, the
// code below will remove any instances of '2002' in the  datetime and 
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

FetchDatesAndOrTimes($conn, 'date', $expectedDateTime, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'time', $expectedDateTime, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'datetime', $expectedDateTime, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'datetime2', $expectedDateTime, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'datetimeoffset', $expectedDateTime, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'smalldatetime', $expectedDateTime, $returnDatesAsStrings);

sqlsrv_close($conn);

$returnDatesAsStrings = false;

$conn = AE\connect(array('ReturnDatesAsStrings' => $returnDatesAsStrings));

InsertDatesAndOrTimes($conn, 'date', $date_formats_all, $SZ_DATE_all, SQLSRV_SQLTYPE_DATE);
InsertDatesAndOrTimes($conn, 'time', $time_formats_all, $SZ_TIME_all, SQLSRV_SQLTYPE_TIME);
InsertDatesAndOrTimes($conn, 'datetime', $datetime_formats_all, $SZ_DATETIME_all, SQLSRV_SQLTYPE_DATETIME);
InsertDatesAndOrTimes($conn, 'datetime2', $datetime2_formats_all, $SZ_DATETIME_all, SQLSRV_SQLTYPE_DATETIME2);
InsertDatesAndOrTimes($conn, 'datetimeoffset', $datetimeoffset_formats_all, $SZ_DATETIME_all, SQLSRV_SQLTYPE_DATETIMEOFFSET);
InsertDatesAndOrTimes($conn, 'smalldatetime', $datetimesmall_formats_all, $SZ_DATETIME_all, SQLSRV_SQLTYPE_SMALLDATETIME);

FetchDatesAndOrTimes($conn, 'date', $expectedDateTime, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'time', $expectedDateTime, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'datetime', $expectedDateTime, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'datetime2', $expectedDateTime, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'datetimeoffset', $expectedDateTime, $returnDatesAsStrings);
FetchDatesAndOrTimes($conn, 'smalldatetime', $expectedDateTime, $returnDatesAsStrings);

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
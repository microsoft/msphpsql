--TEST--
Test various date and time types with AE and ReturnDatesAsStrings set to true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

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

// If there is an error in comparison, output the types, the data, and die
function mismatchError($dateTimeType, $compareType, $retrieved, $expected)
{
    echo "Error comparing dateTimeType = $dateTimeType against $compareType\n";
    if (is_array($retrieved)) {
        print_r($retrieved[$compareType]);
    } else {
        print_r($retrieved);
    }
    print_r($expected[$compareType]);
    fatalError("Retrieved and expected output do not match!");
}

// Compare dates retrieved from the database with the date used for testing
// when ReturnDatesAsStrings is true.
// $expectedDateTime is an array of date/time strings corresponding to the different SQL Server types.
// $retrievedDateTime is date/time string whose format depends on $dateTimeType.
function CompareDateTimeString($dateTimeType, &$expectedDateTime, $retrievedDateTime)
{
    switch ($dateTimeType) {
        case 'date':
            // Direct comparison of retrieved date and expected date
            if ($retrievedDateTime != $expectedDateTime['date'][0]) mismatchError('date', 'date', $retrievedDateTime, $expectedDateTime);
            break;
        case 'time': 
            // Compare SQL time with expected time. The expected time was input
            // with an accuracy of 0.000001 s and the SQL Server time type has
            // accuracy to 0.0000001 s, so retrieved times have an extra zero.
            // For comparison a zero is appended to the $expectedDateTime.
            if ($retrievedDateTime != $expectedDateTime['time'][1]."0" and
                $retrievedDateTime != $expectedDateTime['time'][2]."0" and
                $retrievedDateTime != $expectedDateTime['time'][3]."0" and
                $retrievedDateTime != $expectedDateTime['time'][4]."0") mismatchError('time', 'time', $retrievedDateTime, $expectedDateTime);
            break;
        case 'datetime':
            // Compare retrieved SQL datetime with expected date/time.
            // SQL Server's datetime type is accurate to 0.000, 0.003, or
            // 0.007 s. We have already accounted for that in 
            // $expectedDateTime['datetime'].
            if ($retrievedDateTime != $expectedDateTime['datetime'][0] and
                $retrievedDateTime != $expectedDateTime['datetime'][1] and
                $retrievedDateTime != $expectedDateTime['datetime'][2] and
                $retrievedDateTime != $expectedDateTime['datetime'][3]) mismatchError('datetime', 'datetime', $retrievedDateTime, $expectedDateTime);
            break;
        case 'datetime2':
            // Compare retrieved SQL datetime2 with expected date/time.
            // SQL Server's datetime2 type is accurate to 0.0000001 s and
            // the expected time is accurate to 0.000001 s, so append
            // a zero to the expected time for comparison.
            if ($retrievedDateTime != $expectedDateTime['datetime2'][1]."0" and
                $retrievedDateTime != $expectedDateTime['datetime2'][2]."0" and
                $retrievedDateTime != $expectedDateTime['datetime2'][3]."0" and
                $retrievedDateTime != $expectedDateTime['datetime2'][4]."0") mismatchError('datetime2', 'datetime2', $retrievedDateTime, $expectedDateTime);
            break;
        case 'datetimeoffset':
            // Compare the SQL datetimeoffset retrieved with expected
            // date/time. datetimeoffset is accurate to 0.0000001 s, so the
            // extra zero is removed in $dtoffset to create a format accurate 
            // to 0.000001 s for comparison with the expected date/time/timezone.
            $ret_date_time = explode(" ", $retrievedDateTime);
            $dtoffset = $ret_date_time[0]." ".substr($ret_date_time[1], 0, -1)." ".$ret_date_time[2];
            if ($dtoffset != $expectedDateTime['datetimeoffset'][1] and
                $dtoffset != $expectedDateTime['datetimeoffset'][2] and
                $dtoffset != $expectedDateTime['datetimeoffset'][3] and
                $dtoffset != $expectedDateTime['datetimeoffset'][4]) mismatchError('datetimeoffset', 'datetimeoffset', $dtoffset, $expectedDateTime);
            break;
        case 'smalldatetime':
            // Compare retrieved SQL smalldatetime with expected date/time.
            // SQL Server's smalldatetime type is accurate to seconds only.
            if ($retrievedDateTime != $expectedDateTime['smalldatetime'][0]) mismatchError('smalldatetime', 'smalldatetime', $retrievedDateTime, $expectedDateTime);
            break;
    }
}

// Compare dates retrieved from the database with the date used for testing
// when ReturnDatesAsStrings is false.
// $expectedDateTime is an array of date/time strings corresponding to the different SQL Server types.
// $retrievedDateTime is an array of date/time strings returned by the date_format() calls
// in FetchDatesAndOrTimes when a PHP DateTime object is retrieved. Note how
// dates and times are handled when the type is time and date:
// If $dateTimeType is 'date':
//   The date is as expected, the time defaults to 00:00:00.0000
// If $dateTimeType is 'time':
//   The date defaults to the current date, the time is as expected. 
function CompareDateTimeObject($dateTimeType, &$expectedDateTime, &$retrievedDateTime)
{
    // To compare offsets when the retrieved DateTime object defaults
    // to the default offset, take the date and time from the retrieved
    // string and append the offset from the expected date/time
    $ret_date_time = explode(" ",$retrievedDateTime['datetimeoffset']);
    $ret_date_time = $ret_date_time[0]." ".$ret_date_time[1]." ".explode(" ",$expectedDateTime['datetimeoffset'][0])[2];
    
    // Times returned by SQL Server are accurate to 0.0000001 s, but when
    // formatted using PHP's date_format() function, the times are accurate
    // to 0.000001 s. So both retrieved and expected times are accurate
    // to the same precision, and no need for adding zeroes in any of the
    // comparisons below.
    switch ($dateTimeType) {
        case 'date':
            // Comparison of dates only.
            if ($retrievedDateTime['date'] != $expectedDateTime['date'][0]) mismatchError('date', 'date', $retrievedDateTime, $expectedDateTime);
            if (explode(" ", $retrievedDateTime['datetime'])[0] != explode(" ", $expectedDateTime['datetime'][0])[0]) mismatchError('date', 'datetime', $retrievedDateTime, $expectedDateTime);
            if (explode(" ", $retrievedDateTime['datetime2'])[0] != explode(" ", $expectedDateTime['datetime2'][0])[0]) mismatchError('date', 'datetime2', $retrievedDateTime, $expectedDateTime);
            if (explode(" ", $retrievedDateTime['datetimeoffset'])[0] != explode(" ", $expectedDateTime['datetimeoffset'][0])[0]) mismatchError('date', 'datetimeoffset', $retrievedDateTime, $expectedDateTime);
            if (explode(" ", $retrievedDateTime['smalldatetime'])[0] != explode(" ", $expectedDateTime['smalldatetime'][0])[0]) mismatchError('date', 'smalldatetime', $retrievedDateTime, $expectedDateTime);
            break;
        case 'time':
            if ($retrievedDateTime['time'] != $expectedDateTime['time'][1] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][2] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][3] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][4]) mismatchError('time', 'time', $retrievedDateTime, $expectedDateTime);
            if (explode(" ", $retrievedDateTime['datetime'])[1] != explode(" ", $expectedDateTime['datetime'][0])[1] and
                explode(" ", $retrievedDateTime['datetime'])[1] != explode(" ", $expectedDateTime['datetime'][1])[1] and
                explode(" ", $retrievedDateTime['datetime'])[1] != explode(" ", $expectedDateTime['datetime'][2])[1] and
                explode(" ", $retrievedDateTime['datetime'])[1] != explode(" ", $expectedDateTime['datetime'][3])[1]) mismatchError('time', 'datetime', $retrievedDateTime, $expectedDateTime);
            if (explode(" ", $retrievedDateTime['datetime2'])[1] != explode(" ", $expectedDateTime['datetime2'][1])[1] and
                explode(" ", $retrievedDateTime['datetime2'])[1] != explode(" ", $expectedDateTime['datetime2'][2])[1] and
                explode(" ", $retrievedDateTime['datetime2'])[1] != explode(" ", $expectedDateTime['datetime2'][3])[1] and
                explode(" ", $retrievedDateTime['datetime2'])[1] != explode(" ", $expectedDateTime['datetime2'][4])[1]) mismatchError('time', 'datetime2', $retrievedDateTime, $expectedDateTime);
            if (explode(" ", $retrievedDateTime['datetimeoffset'])[1] != explode(" ", $expectedDateTime['datetimeoffset'][1])[1] and
                explode(" ", $retrievedDateTime['datetimeoffset'])[1] != explode(" ", $expectedDateTime['datetimeoffset'][2])[1] and
                explode(" ", $retrievedDateTime['datetimeoffset'])[1] != explode(" ", $expectedDateTime['datetimeoffset'][3])[1] and
                explode(" ", $retrievedDateTime['datetimeoffset'])[1] != explode(" ", $expectedDateTime['datetimeoffset'][4])[1]) mismatchError('time', 'datetimeoffset', $retrievedDateTime, $expectedDateTime);
            if (explode(" ", $retrievedDateTime['smalldatetime'])[1] != explode(" ", $expectedDateTime['smalldatetime'][0])[1]) mismatchError('time', 'smalldatetime', $retrievedDateTime, $expectedDateTime);
            break;
        case 'datetime':
            if ($retrievedDateTime['date'] != $expectedDateTime['date'][0]) mismatchError('datetime', 'date', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['time'] != $expectedDateTime['time'][0] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][1] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][2] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][3] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][4]) mismatchError('datetime', 'time', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['datetime'] != $expectedDateTime['datetime'][0] and
                $retrievedDateTime['datetime'] != $expectedDateTime['datetime'][1] and
                $retrievedDateTime['datetime'] != $expectedDateTime['datetime'][2] and
                $retrievedDateTime['datetime'] != $expectedDateTime['datetime'][3]) mismatchError('datetime', 'datetime', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][0] and
                $retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][1] and
                $retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][2] and
                $retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][3] and
                $retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][4]) mismatchError('datetime', 'datetime2', $retrievedDateTime, $expectedDateTime);
            if ($ret_date_time != $expectedDateTime['datetimeoffset'][0] and
                $ret_date_time != $expectedDateTime['datetimeoffset'][1] and
                $ret_date_time != $expectedDateTime['datetimeoffset'][2] and
                $ret_date_time != $expectedDateTime['datetimeoffset'][3] and
                $ret_date_time != $expectedDateTime['datetimeoffset'][4]) mismatchError('datetime', 'datetimeoffset', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['smalldatetime'] != $expectedDateTime['smalldatetime'][0]) mismatchError('datetime', 'smalldatetime', $retrievedDateTime, $expectedDateTime);
            break;
        case 'datetime2':
            if ($retrievedDateTime['date'] != $expectedDateTime['date'][0]) mismatchError('datetime2', 'date', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['time'] != $expectedDateTime['time'][1] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][2] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][3] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][4]) mismatchError('datetime2', 'time', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['datetime'] != $expectedDateTime['datetime'][0] and
                $retrievedDateTime['datetime'] != $expectedDateTime['datetime'][1] and
                $retrievedDateTime['datetime'] != $expectedDateTime['datetime'][2] and
                $retrievedDateTime['datetime'] != $expectedDateTime['datetime'][3]) mismatchError('datetime2', 'datetime', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][1] and
                $retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][2] and
                $retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][3] and
                $retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][4]) mismatchError('datetime2', 'datetime2', $retrievedDateTime, $expectedDateTime);
            if ($ret_date_time != $expectedDateTime['datetimeoffset'][1] and
                $ret_date_time != $expectedDateTime['datetimeoffset'][2] and
                $ret_date_time != $expectedDateTime['datetimeoffset'][3] and
                $ret_date_time != $expectedDateTime['datetimeoffset'][4]) mismatchError('datetime2', 'datetimeoffset', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['smalldatetime'] != $expectedDateTime['smalldatetime'][0]) mismatchError('datetime2', 'smalldatetime', $retrievedDateTime, $expectedDateTime);
            break;
        case 'datetimeoffset':
            if ($retrievedDateTime['date'] != $expectedDateTime['date'][0]) mismatchError('datetimeoffset', 'date', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['time'] != $expectedDateTime['time'][1] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][2] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][3] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][4]) mismatchError('datetimeoffset', 'time', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['datetime'] != $expectedDateTime['datetime'][0] and
                $retrievedDateTime['datetime'] != $expectedDateTime['datetime'][1] and
                $retrievedDateTime['datetime'] != $expectedDateTime['datetime'][2] and
                $retrievedDateTime['datetime'] != $expectedDateTime['datetime'][3]) mismatchError('datetimeoffset', 'datetime', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][1] and
                $retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][2] and
                $retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][3] and
                $retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][4]) mismatchError('datetimeoffset', 'datetime2', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['datetimeoffset'] != $expectedDateTime['datetimeoffset'][1] and
                $retrievedDateTime['datetimeoffset'] != $expectedDateTime['datetimeoffset'][2] and
                $retrievedDateTime['datetimeoffset'] != $expectedDateTime['datetimeoffset'][3] and
                $retrievedDateTime['datetimeoffset'] != $expectedDateTime['datetimeoffset'][4]) mismatchError('datetimeoffset', 'datetimeoffset', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['smalldatetime'] != $expectedDateTime['smalldatetime'][0]) mismatchError('datetimeoffset', 'smalldatetime', $retrievedDateTime, $expectedDateTime);
            break;
        case 'smalldatetime':
            if ($retrievedDateTime['date'] != $expectedDateTime['date'][0]) mismatchError('smalldatetime', 'date', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['time'] != $expectedDateTime['time'][1] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][2] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][3] and
                $retrievedDateTime['time'] != $expectedDateTime['time'][4]) mismatchError('smalldatetime', 'time', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['datetime'] != $expectedDateTime['datetime'][0] and
                $retrievedDateTime['datetime'] != $expectedDateTime['datetime'][1] and
                $retrievedDateTime['datetime'] != $expectedDateTime['datetime'][2] and
                $retrievedDateTime['datetime'] != $expectedDateTime['datetime'][3]) mismatchError('smalldatetime', 'datetime', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][1] and
                $retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][2] and
                $retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][3] and
                $retrievedDateTime['datetime2'] != $expectedDateTime['datetime2'][4]) mismatchError('smalldatetime', 'datetime2', $retrievedDateTime, $expectedDateTime);
            if ($ret_date_time != $expectedDateTime['datetimeoffset'][1] and
                $ret_date_time != $expectedDateTime['datetimeoffset'][2] and
                $ret_date_time != $expectedDateTime['datetimeoffset'][3] and
                $ret_date_time != $expectedDateTime['datetimeoffset'][4]) mismatchError('smalldatetime', 'datetimeoffset', $retrievedDateTime, $expectedDateTime);
            if ($retrievedDateTime['smalldatetime'] != $expectedDateTime['smalldatetime'][0]) mismatchError('smalldatetime', 'smalldatetime', $retrievedDateTime, $expectedDateTime);
            break;
    }
}

function InsertDatesAndOrTimes($conn, $dateTimeType, &$formats_array, $array_size, $SQLSRV_SQLTYPE_CONST)
{
    $tableName = "table_of_$dateTimeType";
    $columns = array(new AE\ColumnMeta('int', 'id'),
                     new AE\ColumnMeta("$dateTimeType", "c1_$dateTimeType"));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    $insertSql = "INSERT INTO [$tableName] (id, [c1_$dateTimeType]) VALUES (?, ?)";
    
    for ($i = 0; $i < $array_size; $i++) {
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

function FetchDatesAndOrTimes($conn, $dateTimeType, &$expectedDateTime, $returnDatesAsStrings)
{
    $tableName = "table_of_$dateTimeType";
    
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
        
        CompareDateTimeString($dateTimeType, $expectedDateTime, $datetime);
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
            
        // The formats below correspond to the SQL date and time types, 
        // but PHP allows users to format the date arbitrarily. The 
        // formats below are probably the most likely to be used.
        $datetimeArray = array('date'=>date_format($datetime, 'Y-m-d'),
                               'time'=>date_format($datetime, 'H:i:s.u'),
                               'datetime'=>date_format($datetime, 'Y-m-d H:i:s.v'),
                               'datetime2'=>date_format($datetime, 'Y-m-d H:i:s.u'),
                               'datetimeoffset'=>date_format($datetime, 'Y-m-d H:i:s.u P'),
                               'smalldatetime'=>date_format($datetime, 'Y-m-d H:i').":00",
                               );
                                       
        CompareDateTimeObject($dateTimeType, $expectedDateTime, $datetimeArray);
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
            
            CompareDateTimeString($dateTimeType, $expectedDateTime, $datetime);
        } else { // ReturnDatesAsStrings is false
            if (!($datetime instanceof DateTime)) {
                fatalError("DateTime object expected, not a DateTime");
            }
            
            $datetimeArray = array('date'=>date_format($datetime, 'Y-m-d'),
                                   'time'=>date_format($datetime, 'H:i:s.u'),
                                   'datetime'=>date_format($datetime, 'Y-m-d H:i:s.v'),
                                   'datetime2'=>date_format($datetime, 'Y-m-d H:i:s.u'),
                                   'datetimeoffset'=>date_format($datetime, 'Y-m-d H:i:s.u P'),
                                   'smalldatetime'=>date_format($datetime, 'Y-m-d H:i').":00",
                                   );

            CompareDateTimeObject($dateTimeType, $expectedDateTime, $datetimeArray);
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
            
            CompareDateTimeString($dateTimeType, $expectedDateTime, $row[1]);
        } else { // ReturnDatesAsStrings is false
            if (!($row[1] instanceof DateTime)) {
                fatalError("DateTime object expected, not a DateTime");
            }

            $datetimeArray = array('date'=>date_format($datetime, 'Y-m-d'),
                                   'time'=>date_format($datetime, 'H:i:s.u'),
                                   'datetime'=>date_format($datetime, 'Y-m-d H:i:s.v'),
                                   'datetime2'=>date_format($datetime, 'Y-m-d H:i:s.u'),
                                   'datetimeoffset'=>date_format($datetime, 'Y-m-d H:i:s.u P'),
                                   'smalldatetime'=>date_format($datetime, 'Y-m-d H:i').":00",
                                   );
            
            CompareDateTimeObject($dateTimeType, $expectedDateTime, $datetimeArray);
        }
    }
}

// The date used for the test will be Januray 31, 2002, or 2002/01/31.
// This will sidestep issues involving the use of two digit years.
// Time is 23:59:29.049876. User can substitute any values they wish for date
// and time, except for values that would cause rollovers to the next 
// second/minute/hour/day/month/year because there is no logic in this test
// to handle rollovers. This warning applies to 
// 1. datetime when $frac is '999', because datetime is accurate to .000, .003,
// or .007 s, and 999 would roll over to the next second when inserted. 
// 2. smalldatetime when $second is >= 30, because smalldatetime rounds to the
// nearest minute, and that may cause this test to fail if it rolls over to the next day.
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
$frac = '049';
$frac2 = '876';
$tz_correction = '+08:00';

// The datetime type is accurate to .000, .003, or .007 second, so adjust
// $frac appropriately for that type. Do not use '999'
$frac_rounded = $frac;
if ($frac[2] == '2' or $frac[2] == '4') $frac_rounded[2] = '3';
elseif ($frac[2] == '5' or $frac[2] == '6' or $frac[2] == '8') $frac_rounded[2] = '7';
elseif ($frac[2] == '1') $frac_rounded[2] = '0';
elseif ($frac[2] == '9') 
{
    // Get as integer and add one, then get as string back, prepend '0' if result is less than 100
    $frac_int = intval($frac); 
    $frac_int += 1;
    $frac_rounded = $frac_int < 100 ? '0'.strval($frac_int) : strval($frac_int);
}    

// This is the array of dates/times/timezones to test against. They have
// different numbers of trailing zeroes to match the precision of the
// SQL Server date and time types, but only up to microseconds (0.000001 s)
// because that is PHP's maximum precision when formatting times with
// date_format() (time, datetime2, and datetimeoffset go up to 0.0000001 s precision.)
// This allows direct string comparisons when the DateTime objects retrieved from 
// a table are formatted as strings with date_format(). However, when returning 
// dates as strings using ReturnDatesAsStrings set to true, the returned
// data defaults to SQL Server type precision, so for comparisons some zeroes
// have to be added or removed from the values below.
$expectedDateTime = array('date'=>array($year."-".$month."-".$day),
                          'time'=>array($hour.":".$minute.":".$second.".".$frac_rounded."000",
                                        $hour.":".$minute.":".$second.".".$frac.$frac2,
                                        $hour.":".$minute.":".$second.".".$frac."000",
                                        $hour.":".$minute.":".$second.".000000",
                                        $hour.":".$minute.":00.000000"),
                          'datetime'=>array($year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".".$frac_rounded,
                                            $year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".".$frac,
                                            $year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".000",
                                            $year."-".$month."-".$day." ".$hour.":".$minute.":00.000"),
                          'datetime2'=>array($year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".".$frac_rounded."000",
                                             $year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".".$frac.$frac2,
                                             $year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".".$frac."000",
                                             $year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".000000",
                                             $year."-".$month."-".$day." ".$hour.":".$minute.":00.000000"),
                          'datetimeoffset'=>array($year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".".$frac_rounded."000 ".$tz_correction,
                                                  $year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".".$frac.$frac2." ".$tz_correction,
                                                  $year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".".$frac."000 ".$tz_correction,
                                                  $year."-".$month."-".$day." ".$hour.":".$minute.":".$second.".000000 ".$tz_correction,
                                                  $year."-".$month."-".$day." ".$hour.":".$minute.":00.000000 ".$tz_correction),
                          'smalldatetime'=>array($year."-".$month."-".$day." ".$hour.":".$minute.":00"),
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
// will be garbled. For example, if the year is 2002 and $frac2 is 002, the
// code below will remove any instances of '002' in the  datetime and 
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
        } else {
            $datetimesmall_formats_all[] = str_replace(".".$frac.$frac2, "", $date_formats_all[$i]." ".$time_formats_all[$j]);
        }
    }
}

date_default_timezone_set('Canada/Pacific');
sqlsrv_configure('WarningsReturnAsErrors', 1);
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);
sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_OFF);

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
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
Select fields as strings:
Select fields as DateTime objects:
Select fields with no type information provided:
Select using fetch_array:
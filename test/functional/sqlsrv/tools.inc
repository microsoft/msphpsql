<?php

// Numeric values that corresponds to a SQL type:
define("_SQL_BIGINT", -5);
define("_SQL_BINARY", -2);
define("_SQL_BIT", -7);
define("_SQL_CHAR", 1);
define("_SQL_TYPE_DATE", 91);
define("_SQL_TYPE_TIMESTAMP", 93);
define("_SQL_DECIMAL", 3);
define("_SQL_FLOAT", 6);
define("_SQL_LONGVARBINARY", -4);
define("_SQL_INTEGER", 4);
define("_SQL_WCHAR", -8);
define("_SQL_WLONGVARCHAR", -10);
define("_SQL_NUMERIC", 2);
define("_SQL_WVARCHAR", -9);
define("_SQL_REAL", 7);
define("_SQL_SMALLINT", 5);
define("_SQL_LONGVARCHAR", -1);
define("_SQL_TINYINT", -6);
define("_SQL_VARBINARY", -3);
define("_SQL_VARCHAR", 12);
define("_SQL_SS_XML", -152);
define("_SQL_GUID", -11);
define("_SQL_SS_TIME2", -154);
define("_SQL_SS_TIMESTAMPOFFSET", -155);
define("_CHUNK_SIZE", 8192);
define("_EPSILON", 0.00001);

function verify($stmt, $metadata, $numFields, $encoding)
{
    $i = 0;
    while ($result = sqlsrv_fetch($stmt)) {
        echo "Comparing data in row " . ++$i . "\n";
        $data = getInputData($i);
        $dataArray = simplifyDataArray($data, $metadata, $i);
        for ($j = 0; $j < $numFields; $j++) {
            $value = sqlsrv_get_field($stmt, $j, SQLSRV_PHPTYPE_STRING($encoding));
            compareData($metadata, $i, $j, $value, $dataArray[$j], false, false);
        }
    }
    //  returns the number of rows fetched
    return $i;
}

function convertDataToString($type, $data)
{
    if ($data === null) {
        return "";
    }

    // Convert raw data to strings for datetime and binary types only
    // Do nothing for other types
    //
    if (isDateTimeType($type)) {
        if ($type != _SQL_SS_TIME2) {
            return date_format($data, 'Y-m-d H:i:s');
        } else {
            return date_format($data, 'H:i:s');
        }
    } elseif (isBinaryType($type)) {
        return bin2hex($data);
    } else {
        return $data;
    }
}

function compareCharacterData($actual, $expected, $errorMode = false)
{
    // $errorMode is true if an error is expected
    $matched = false;
    if ($actual === $expected) {
        $matched = true;
    } else {
        $len = strlen($expected);

        $result = strncmp($expected, $actual, $len);
        if ($result == 0) {
            $matched = true;
        }
    }

    //  echo "Expected: $expected\nActual: $actual\n";
    if ($matched) {
        if ($errorMode) {
            echo "Data corruption expected...\n";
        }
    } else {
        if (! $errorMode) {
            echo "Data corruption!! Expected: $expected\nActual: $actual\n";
        }
    }

    return $matched;
}

function compareNumericData($actual, $expected)
{
    if (! is_numeric($actual)) {
        return false;
    }

    // only compare up to the length of the expected value, especially for floats
    if (is_float($expected)) {
        $len = strlen($expected);
        $actual = substr($actual, 0, $len);
    }

    $f_actual = floatval($actual);
    $f_expected = floatval($expected);

    $matched = ($f_actual == $f_expected);
    if (! $matched) {
        // compare floating point values
        $diff = abs(($f_actual - $f_expected) / $f_expected);
        $matched = ($diff < _EPSILON);

        if (! $matched) {
            echo "Data corruption: values don't match\n";
        }
    }

    return $matched;
}

function checkData($colType, $actual, $expected)
{
    $success = true;

    if (isNumericType($colType)) {
        if (! compareNumericData($actual, $expected)) {
            $success = false;
        }
    } else {
        $actual = trim($actual);
        $len = strlen($expected);

        if (isDateTimeType($colType)) {
            if ($colType != _SQL_SS_TIME2) {
                $len = min(strlen("YYYY-MM-DD HH:mm:ss"), $len);
            } else {
                $len = min(strlen("HH:mm:ss"), $len);
            }
        }
        if (strncasecmp($actual, $expected, $len) != 0) {
            $success = false;
        }
    }
    return ($success);
}

function readBinaryStream($filename)
{
    $handle = fopen($filename, "rb");
    $contents = fread($handle, filesize($filename));
    fclose($handle);

    return $contents;
}

function toByteArray($input)
{
    // this should be int[] which can be converted
    // to byte[] in C# since values are range of 0 - 255
    return unpack('C*', $input);
}

function compareByteArrays($actual, $expected)
{
    $actualArray = toByteArray($actual);
    $expectedArray = toByteArray($expected);

    echo "Data read:\t";
    print_r($actualArray);
    echo "Expected:\t";
    print_r($expectedArray);
}

function compareDataStream($colType, $rowIndex, $colName, $stream, $expected)
{
    $len = strlen($expected);
    $matched = false;

    if ($len == 0) {
        $contents = fread($stream, _CHUNK_SIZE);
        $contents = trim($contents);    // removes all whitespace
        if (strlen($contents) == 0) {
            $matched = true;
        }
    } elseif ($len < _CHUNK_SIZE) {
        $contents = fread($stream, $len);
        $matched = compareDataValue($colType, $rowIndex, $colName, $contents, $expected);
    } else {
        $matched = true;
        $pos = 0;
        while (! feof($stream) && $pos < $len) {
            $contents = fread($stream, _CHUNK_SIZE);
            $contents = convertDataToString($colType, $contents);

            $contents_len = strlen($contents);
            $result = substr_compare($expected, $contents, $pos, $contents_len, true);
            if ($result != 0) {
                break;
                $matched = false;
            }
            $pos += $contents_len;
        }
    }

    if (! $matched) {
        echo "Data corruption on row $rowIndex column $colName\n";
    }

    return $matched;
}

function compareBinaryData($actual, $expected)
{
    // this function assumes $actual is a stream of hex
    $len = strlen($expected);
    $pos = 0;

    $matched = true;
    while (! feof($actual) && $pos < $len) {
        $contents = fread($actual, _CHUNK_SIZE);
        $result = 0;

        $str = unpack("H*", $contents);

        $contents = $str[1];
        $contents_len = strlen($contents);

        $count = $contents_len;
        if ($len < ($pos + $contents_len)) {
            $count = $len - $pos;
        }
        $result = substr_compare($expected, $contents, $pos, $count, true);
        if ($result != 0) {
            //echo "Expected: " . substr($expected, $pos, $count) . "\nActual: $contents\n";
            $matched = false;
            break;
        }

        $pos += $count;
    }

    return $matched;
}

function compareStreamData($actual, $expected, $IsUnicodeType)
{
    // this function assumes $actual is a stream of character data
    $matched = true;

    // convert $expected to UCS-2LE (Little Endian)
    $str = iconv("UTF-8", "UCS-2LE", $expected);

    // every character of $str consists of two bytes but if
    // it's unicode, the character of data is also two bytes

    $shift = 1;
    if ($IsUnicodeType) {
        $shift = 2;
    }

    $len = strlen($str);
    $pos = 0;
    while ($matched && ! feof($actual) && $pos < $len) {
        $contents = fread($actual, _CHUNK_SIZE);
        $contents_len = strlen($contents);

        $i = 0;
        while ($matched && $i < $contents_len && $pos < $len) {
            if ($contents[$i] != $str[$pos]) {
                //echo "Expected at [$pos]: $str[$pos]\nActual at [$i]: $contents[$i]\n";
                $matched = false;
                break;
            }
            $pos += 2;
            $i += $shift;
        }
    }

    return $matched;
}

function compareBinaryStream($metadata, $rowIndex, $colIndex, $actual, $expected)
{
    // unlike compareDataStream(), this method assumes $actual is a stream
    // and no non-updatable fields (timestamp column)

    if (is_null($actual)) {
        if ($expected === "") {
            return true;
        } else {
            return false;
        }
    }

    $colName = $metadata[$colIndex]['Name'];
    $colType = $metadata[$colIndex]['Type'];

    $matched = false;
    if (isBinaryType($colType)) {
        $matched = compareBinaryData($actual, $expected);
    } else {
        // stream of characters
        $matched = compareStreamData($actual, $expected, isUnicodeType($colType));
    }

    if (! $matched) {
        echo "Data not matching on row $rowIndex column $colName\n";
    }

    return $matched;
}

function compareStringToFile($rowIndex, $colName, $actual, $file, $IsBinaryType)
{
    $matched = true;
    $pos = 0;
    while ($matched && ! feof($file)) {
        $expected = fread($file, _CHUNK_SIZE);
        if ($IsBinaryType) {
            $str = unpack("H*", $expected);
            $expected = $str[1];
        }
        $len = strlen($expected);

        $contents = substr($actual, $pos, $len);
        $result = strcasecmp($expected, $contents);

        $matched = ($result == 0);
        $pos += $len;
    }

    if (! $matched) {
        echo "Data not matching on row $rowIndex column $colName\n";
    }

    return $matched;
}

function compareStreamToFile($rowIndex, $colName, $stream, $file, $colType)
{
    // This function is complicated because it's comparing two stream data
    // one from stream resource and one from a file
    // Moreover, the data can be binary or character/unicode character

    $IsBinaryType = isBinaryType($colType);

    $matched = true;
    while ($matched && ! feof($file) && ! feof($stream)) {
        $expected = fread($file, _CHUNK_SIZE);
        $actual  = fread($stream, _CHUNK_SIZE);

        if ($IsBinaryType) {
            $str = unpack("H*", $expected);
            $expected = $str[1];

            $str = unpack("H*", $actual);
            $actual = $str[1];

            $matched = ($expected == $actual);
        } else {
            // not reading binary hex data here
            // similar to compareStreamData(), except comparing two chunks at a time
            // first convert $expected to UCS-2LE (Little Endian)
            $expected = iconv("UTF-8", "UCS-2LE", $expected);

            // every character of $expected consists of two bytes but if
            // it's unicode, the character of data is also two bytes

            $shift = 1;
            if (isUnicodeType($colType)) {
                $shift = 2;
            }

            $i = 0;
            $pos = 0;
            $data_len = strlen($actual);
            while ($matched && $i < $data_len) {
                if ($actual[$i] != $expected[$pos]) {
                    echo "Expected at [$pos]: $expected[$pos]\nActual at [$i]: $actual[$i]\n";
                    $matched = false;
                    break;
                }
                $pos += 2;
                $i += $shift;
            }
        }
    }

    if (! $matched) {
        echo "Data not matching on row $rowIndex column $colName\n";
    }

    return $matched;
}

function compareDataValue($colType, $rowIndex, $colName, $actual, $expected, $bConvert = true)
{
    if ($bConvert) {
        $actual = convertDataToString($colType, $actual);
    }
    $matched = checkData($colType, $actual, $expected);
    if (! $matched) {
        echo "Data corruption on row $rowIndex column $colName\n";
        echo "Expected: $expected\nActual: $actual\n";
    }

    return $matched;
}

function compareData($metadata, $rowIndex, $colIndex, $actual, $expected, $isStream, $bConvert = true)
{
    if (is_null($actual)) {
        if ($expected === "") {
            return true;
        } else {
            return false;
        }
    }

    $colName = $metadata[$colIndex]['Name'];
    $colType = $metadata[$colIndex]['Type'];

    if ($actual === false) {
        fatalError("Field $colName of row $rowIndex is missing\n");
    }
    if (! isColumnUpdatable($colName)) {
        return true;    // do nothing for non-IsColumnUpdatable fields
    }

    if ($isStream) {
        return compareDataStream($colType, $rowIndex, $colName, $actual, $expected);
    } else {
        return compareDataValue($colType, $rowIndex, $colName, $actual, $expected, $bConvert);
    }
}

function compareLOBToFile($rowIndex, $colType, $colName, $actual, $filename, $readStream)
{
    $IsBinaryType = isBinaryType($colType);
    $mode = ($IsBinaryType)? "rb" : "r";
    $file = fopen($filename, $mode);

    echo "...reading LOB data from '$filename'...";

    $matched = false;
    if ($readStream) {
        $matched = compareStreamToFile($rowIndex, $colName, $actual, $file, $colType);
    } else {
        $matched = compareStringToFile($rowIndex, $colName, $actual, $file, $IsBinaryType);
    }

    fclose($file);

    return $matched;
}

function verifyLOBData($metadata, $rowIndex, $colIndex, $lobColumn, $actual, $expected, $filename, $readStream)
{
    $colName = $metadata[$colIndex]['Name'];
    $colType = $metadata[$colIndex]['Type'];

    $matched = false;
    if ($readStream && isStreamableType($colType)) {
        if ($colIndex != $lobColumn) {
            echo "...reading binary stream...";
            $matched = compareBinaryStream($metadata, $rowIndex, $colIndex, $actual, $expected);
        } else {
            $matched = compareLOBToFile($rowIndex, $colType, $colName, $actual, $filename, true);
        }
    } else {
        if ($colIndex != $lobColumn) {
            echo "...reading string value...";
            $matched = compareDataValue($colType, $rowIndex, $colName, $actual, $expected, false);
        } else {
            $matched = compareLOBToFile($rowIndex, $colType, $colName, $actual, $filename, false);
        }
    }

    return $matched;
}

function simplifyDataArray($data, $metadata, $row)
{
    $dataArray = array();
    $numFields = count($metadata);

    $skipCount = 0;
    for ($j = 0; $j < $numFields; $j++) {
        $colName = $metadata[$j]['Name'];
        $colType = $metadata[$j]['Type'];

        if (!isColumnUpdatable($colName)) {
            $skipCount++;
            array_push($dataArray, "");
        } else {
            // only need the first element of each array
            $value = $data[$j - $skipCount][0];
            if (is_a($value, 'DateTime')) {
                $value = date_format($value, 'Y-m-d H:i:s');
            }

            array_push($dataArray, $value);
        }
    }

    return $dataArray;
}

function insertDataToArray($query, $metadata, $row)
{
    $dataArray = array();
    $numFields = count($metadata);

    $skipCount = 0;
    for ($j = 0; $j < $numFields; $j++) {
        $colName = $metadata[$j]['Name'];
        $type = $metadata[$j]['Type'];

        $col = $j + 1;
        if (!isColumnUpdatable($colName)) {
            $skipCount++;
            array_push($dataArray, "");
        } else {
            $data = getInsertDataByType($query, $type, $row, $col, $skipCount);
            array_push($dataArray, $data);
        }
    }

    return $dataArray;
}

function getInsertDataByType($query, $colType, $rowIndex, $colIndex, $skip)
{
    $data = strstr($query, "((");
    $pos = 1;
    if ($data === false) {
        die("Failed to retrieve data on row $rowIndex");
    }
    $data = substr($data, 2);

    while ($pos < ($colIndex - $skip)) {
        $data = strstr($data, ", (");
        $pos++;

        if ($data === false) {
            die("Failed to retrieve data on column $pos");
        }
        $data = substr($data, 3);
    }

    // Is it's XML type, we can't use the closing bracket as the next delimiter
    // because a bracket can be part of the xml data, unless the data is null
    $str = ")";
    $pos = strpos($data, $str);
    if ($pos === false) {
        die("Failed to isolate data on row $rowIndex, column $pos");
    }
    $tmp = substr($data, 0, $pos);
    if ((strcasecmp($tmp, "null") == 0) || strlen($tmp) == 0) {
        $tmp = "";
    } elseif (isXmlType($colType)) {
        $str = ">')";
        $pos = strpos($data, $str);
        $tmp = substr($data, 0, $pos + 2);
    }

    $data = $tmp;

    if (isDataUnicode($colType, $data)) { // this includes unicode data type and XML data that is in Unicode
       // N'data'
        $data = substr($data, 2, strlen($data) - 3);
    } elseif (isLiteralType($colType)) {   // 'data'
        $data = substr($data, 1, strlen($data) - 2);
    } elseif (isBinaryType($colType)) {   // 0xdata
        $data = substr($data, 2);
    }

    return (trim($data));
}

function isStreamableType($type)
{
    switch ($type) {
        case _SQL_CHAR:             // char
            return true;
        case _SQL_WCHAR:            // nchar
            return true;
        case _SQL_VARCHAR:          // varchar
            return true;
        case _SQL_WVARCHAR:         // nvarchar
            return true;
        case _SQL_LONGVARCHAR:      // text
            return true;
        case _SQL_WLONGVARCHAR:     // ntext
            return true;
        case _SQL_BINARY:           // binary
            return true;
        case _SQL_VARBINARY:        // varbinary
            return true;
        case _SQL_LONGVARBINARY:    // image
            return true;
        case _SQL_SS_XML:           // xml
            return true;
        default:
            break;
    }
    return (false);
}

function isNumericType($type)
{
    switch ($type) {
        case _SQL_INTEGER:     // int
            return true;
        case _SQL_TINYINT:     // tinyint
            return true;
        case _SQL_SMALLINT:    // smallint
            return true;
        case _SQL_BIGINT:      // bigint
            return true;
        case _SQL_BIT:         // bit
            return true;
        case _SQL_FLOAT:       // float
            return true;
        case _SQL_REAL:        // real
            return true;
        case _SQL_DECIMAL:     // decimal
            return true;
        case _SQL_NUMERIC:     // numeric, money, smallmoney
            return true;
        default:    break;
    }
    return (false);
}

function isCharType($type)
{
    switch ($type) {
        case _SQL_WCHAR:            // nchar
            return true;
        case _SQL_VARCHAR:          // varchar
            return true;
        case _SQL_WVARCHAR:         // nvarchar
            return true;
        case _SQL_LONGVARCHAR:      // text
            return true;
        case _SQL_WLONGVARCHAR:     // ntext
            return true;
        case _SQL_SS_XML:           // xml
            return true;
        default:
            break;
    }
    return (false);
}

function isBinaryType($type)
{
    switch ($type) {
        case _SQL_BINARY:           // binary
            return true;
        case _SQL_VARBINARY:        // varbinary
            return true;
        case _SQL_LONGVARBINARY:    // image
            return true;
        default:
            break;
    }
    return (false);
}

function isDateTimeType($type)
{
    switch ($type) {
        case _SQL_TYPE_TIMESTAMP:       // datetime, smalldatetime
            return true;
        case _SQL_TYPE_DATE:            // date
            return true;
        case _SQL_SS_TIME2:             // time
            return true;
        case _SQL_SS_TIMESTAMPOFFSET:   // datetimeoffset
            return true;
        default:
            break;
    }
    return (false);
}

function isDataUnicode($colType, $data)
{
    if (isUnicodeType($colType)) {
        return true;
    }

    // This input string may be an XML string in unicode (i.e. // N'<xmldata>...</xmldata>')
    $letterN = 'N';
    $index = strpos($data, $letterN);

    // Note the use of ===.  Simply == would not work as expected
    // because the position of letterN 'N' may be the 0th (first) character
    // and strpos will return false if not found.
    if ($index === 0) {
        return true;
    }

    return false;
}

function isUnicodeType($type)
{
    switch ($type) {
        case _SQL_WCHAR:            // nchar
            return true;
        case _SQL_WVARCHAR:         // nvarchar
            return true;
        case _SQL_WLONGVARCHAR:     // ntext
            return true;
        default:
            break;
    }
    return (false);
}

function isXmlType($type)
{
    return ($type == _SQL_SS_XML);
}

function isColumnUpdatable($colName)
{
    $pos = strpos($colName, "_");
    $type = substr($colName, $pos + 1);

    return (strcasecmp($type, "timestamp") != 0);
}

function isLiteralType($type)
{
    switch ($type) {
        case _SQL_CHAR:                 // char
            return true;
        case _SQL_WCHAR:                // nchar
            return true;
        case _SQL_VARCHAR:              // varchar
            return true;
        case _SQL_WVARCHAR:             // nvarchar
            return true;
        case _SQL_LONGVARCHAR:          // text
            return true;
        case _SQL_WLONGVARCHAR:         // ntext
            return true;
        case _SQL_GUID:                 // uniqueidentifier
            return true;
        case _SQL_TYPE_TIMESTAMP:       // datetime, smalldatetime
            return true;
        case _SQL_TYPE_DATE:            // date
            return true;
        case _SQL_SS_TIME2:             // time
            return true;
        case _SQL_SS_TIMESTAMPOFFSET:   // datetimeoffset
            return true;
        case _SQL_SS_XML:               // xml
            return true;
        default:
            break;
    }
    return (false);
}

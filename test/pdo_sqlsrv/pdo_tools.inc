<?php

define("_EPSILON", 0.00001);
define("_CHUNK_SIZE", 8192);

function CompareNumericData($actual, $expected)
{
    if (! is_numeric($actual))
    {
        return false;
    }

    // only compare up to the length of the expected value, especially for floats
    if (is_float($expected))
    {
        $len = strlen($expected);   
        $actual = substr($actual, 0, $len);
    }

    $f_actual = floatval($actual);
    $f_expected = floatval($expected);

    $matched = ($f_actual == $f_expected);
    if (! $matched)
    {
        // compare floating point values 
        $diff = abs(($f_actual - $f_expected) / $f_expected);
        $matched = ($diff < _EPSILON);

        if (! $matched)
        {
            echo "Data corruption: values don't match\n";
        }
    }   
    
    return $matched;
}

function CheckData($colType, $actual, $expected)
{
    $success = true;
    
    if (IsBinaryType($colType))
    {           
        $actual = bin2hex($actual);
    }

    if (is_numeric($expected))
    {
        if (! CompareNumericData($actual, $expected))
        {
            $success = false;
        }
    }
    else 
    {
        $actual = trim($actual);
        $len = strlen($expected);
        
        if (IsDateTimeType($colType))
        {
            if ($colType != 'time')
                $len = min(strlen("YYYY-MM-DD HH:mm:ss"), $len);
            else
                $len = min(strlen("HH:mm:ss"), $len);
        }
        if (strncasecmp($actual, $expected, $len) != 0)
        {
            $success = false;
        }
    }
    return ($success);
}

function CompareDataValue($colType, $rowIndex, $colName, $actual, $expected)
{
    $matched = CheckData($colType, $actual, $expected);
    if (! $matched)
    {
        echo "Data corruption on row $rowIndex column $colName\n";
        echo "Expected: $expected\nActual: $actual\n";
    }
    
    return $matched;
}

function CompareData($stmt, $rowIndex, $colIndex, $actual, $expected)
{
    if (is_null($actual))
    {
        if ($expected === "")
            return true;
        else
            return false;
    }
    
    if ($actual === false)
    {
        echo ("Field $colName of row $rowIndex is missing\n");
    }

    $meta = $stmt->getColumnMeta($colIndex);
    $colType = $meta['sqlsrv:decl_type'];
    $colName = $meta['name'];

    if (! IsColumnUpdatable($colName))
    {
        return true;    // do nothing for non-IsColumnUpdatable fields
    }

    return CompareDataValue($colType, $rowIndex, $colName, $actual, $expected);
}

function CompareCharacterData($actual, $expected)
{
    $matched = false;
    if ($actual === $expected)
    {   
        $matched = true;
    }
    else
    {
        $len = strlen($expected);

        $result = strncmp($expected, $actual, $len);    
        if ($result == 0)
        {
            $matched = true;
        }
    }
    
    //echo "Expected: $expected\nActual: $actual\n";
    if (! $matched)
    {
        echo "Data corruption!! Expected: $expected\nActual: $actual\n";
    }       
    
    return $matched;
}

function DumpMetadata($stmt)
{
    $numFields = $stmt->columnCount();      
    for ($j = 0; $j < $numFields; $j++)
    {
        $meta = $stmt->getColumnMeta($j);
        var_dump($meta);
    }
}

function GetColumnData($stmt, $query, $rowIndex, $colIndex)
{
    $skipCount = 0;
    $data = "";
    for ($j = 0; $j <= $colIndex; $j++)
    {
        $meta = $stmt->getColumnMeta($j);
        $type = $meta['sqlsrv:decl_type'];
        $name = $meta['name'];
        
        if (!IsColumnUpdatable($name))
        {
            $skipCount++;
        }
        else 
        {
            if ($j == $colIndex)
            {
                $data = GetInsertDataByType($query, $type, $rowIndex, $j + 1, $skipCount);
                break;
            }
        }       
    }

    return $data;
}

function InsertDataToArray($stmt, $query, $numFields, $rowIndex)
{
    $dataArray = array();

    $skipCount = 0;
    for ($j = 0; $j < $numFields; $j++)
    {
        $meta = $stmt->getColumnMeta($j);
        $type = $meta['sqlsrv:decl_type'];
        $name = $meta['name'];
        
        $colIndex = $j + 1;
        if (!IsColumnUpdatable($name))
        {
            $skipCount++;
            array_push($dataArray, "");
        }
        else 
        {
            $data = GetInsertDataByType($query, $type, $rowIndex, $colIndex, $skipCount);
            array_push($dataArray, $data);
        }
    }

    return $dataArray;
}       

function GetInsertDataByType($query, $colType, $rowIndex, $colIndex, $skip)
{
    $data = strstr($query, "((");
    $pos = 1;
    if ($data === false)
    {
        die("Failed to retrieve data on row $rowIndex");
    }
    $data = substr($data, 2);

    while ($pos < ($colIndex - $skip))
    {
        $data = strstr($data, ", (");
        $pos++;

        if ($data === false)
        {
            die("Failed to retrieve data on column $pos");
        }
        $data = substr($data, 3);
    }
    
    // Is it's XML type, we can't use the closing bracket as the next delimiter
    // because a bracket can be part of the xml data, unless the data is null
    $str = ")";
    $pos = strpos($data, $str);
    if ($pos === false)
    {
        die("Failed to isolate data on row $rowIndex, column $pos");
    }
    $tmp = substr($data, 0, $pos);
    if ((strcasecmp($tmp, "null") == 0) || strlen($tmp) == 0)
    {
        $tmp = "";
    }
    else if (IsXmlType($colType))
    {
        $str = ">')";
        $pos = strpos($data, $str);
        $tmp = substr($data, 0, $pos + 2);
    }
    
    $data = $tmp;   

    if (IsDataUnicode($colType, $data)) // this includes unicode data type and XML data that is in Unicode
    {   // N'data'
        $data = substr($data, 2, strlen($data) - 3);
    }
    else if (IsLiteralType($colType))
    {   // 'data'
        $data = substr($data, 1, strlen($data) - 2);
    }
    else if (IsBinaryType($colType))
    {   // 0xdata
        $data = substr($data, 2);
    }

    return (trim($data));
}

function CompareBinaryStream($inputFile, $actual)
{
    // open input file first
    $stream = fopen($inputFile, "r");   

    $len = strlen($actual);
    echo "Comparing data...\n";
    $matched = true;
    $numbytes = _CHUNK_SIZE;
    
    $pos = 0;
    while (! feof($stream) && $pos < $len)
    {
        $contents = fread($stream, $numbytes);  
        
        // if $actual is empty, check if $contents is also empty
        $contents_len = strlen($contents); 
        if ($len == 0) 
        {
            $matched = ($contents_len == 0);
            break;
        }

        // Compare contents (case-sensitive)
        $count = ($contents_len < $numbytes) ? $contents_len : $numbytes;
        $result = substr_compare($actual, $contents, $pos, $count);

        if ($result != 0)
        {
            $matched = false;
            echo "Data corruption!!\nExpected: $contents\nActual:" . substr($actual, $pos, $count) . "\n";
            break;
        }

        $pos += $count;
    }
    
    // close the data stream
    fclose($stream);    
    
    return $matched;
}

function IsColumnUpdatable($colName)
{
    $pos = strpos($colName, "_");
    $type = substr($colName, $pos + 1);
    
    return (strcasecmp($type, "timestamp") != 0);
}

function IsDataUnicode($colType, $data)
{
    if (IsUnicodeType($colType))
        return true;
        
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

function IsUnicodeType($type)
{
    switch ($type)
    {
        case 'nchar' :
        case 'nvarchar' :
        case 'ntext' :
            return true;
        default:    
            break;
    }
    return (false);
}

function IsXmlType($type)
{
    return ($type == 'xml');
}

function IsBinaryType($type)
{
    switch ($type)
    {
        case 'binary': 
        case 'varbinary': 
        case 'image': 
            return true;
        default:    
            break;
    }
    return (false);
}

function IsDateTimeType($type)
{
    switch ($type)
    {
        case 'datetime' :
        case 'datetime2' :
        case 'smalldatetime' :
        case 'date' :
        case 'time' :
        case 'datetimeoffset' :
            return true;
        default:    
            break;
    }
    return (false);
}

function IsLiteralType($type)
{
    switch ($type)
    {
        case 'char' :
        case 'nchar' :
        case 'varchar' :
        case 'nvarchar' :
        case 'text' :
        case 'ntext' :
        case 'uniqueidentifier' :
        case 'datetime' :
        case 'datetime2' :
        case 'smalldatetime' :
        case 'date' :
        case 'time' :
        case 'datetimeoffset' :
        case 'xml' :
            return true;
        default:    
            break;
    }
    return (false);
}

?>
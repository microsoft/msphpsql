<?php
// exact numerics
$bigint_params = array( 2147483648, -922337203685479936, 922337203685479936, -2147583649, 461168601735364608, -461168601735364608 );
$int_params = array( 32768, -2147483647, 2147483647, -32769, 1073725440, -1073725440 );
$smallint_params = array( 256, -32767, 32767, -1, 16256, -16256 );
$tinyint_params = array( 128, 0, 255, 96, 64, 162 );
$decimal_params = array( 21474.83648, -9223372036854.80000, 9223372036854.80000, -21475.83649, -4611686017353.64608, -4611686017353.64608 );
$numeric_params = array( 0.32768, -21474.83647, 21474.83647, -0.32769, 10737.25440, -10737.25440 );
$money_params = array( 214748.3648, -92233720368548.0000, 92233720368548.0000, -214758.3649, 46116860173536.608, -46116860173536.608 );
$smallmoney_params = array( 0, -214748.3647, 214748.3647, 161061.2736, 107374.1824, -107374.1824 );
$bit_params = array( 0, 1, 0, 1, 0, 1 );
// approximate numerics
$float_params = array( 21474.83648, -9223372036.8548, 9223372036.8548, -21475, 4611686017, -4611686017 );
$real_params = array( 0, -2147.483, 2147.483, 1610, 1073, -1073 );
// date and time
$date_params = array( '1900-01-01', '0001-01-01', '9999-12-31', '5000-07-15', '2500-04-08', '7500-10-23' );
$datetime2_params = array( '1900-01-01 00:00:00', '0001-01-01 00:00:00', '9999-12-31 23:59:59.123456', '5000-07-15 12:30:30.5555', '2500-04-08 06:15:15.33', '7500-10-23 18:45:45.888888' );
$datetime_params = array( '1900-01-01 00:00:00', '1753-01-01 00:00:00', '9999-12-31 23:59:59.997', '5000-07-15 12:30:30.5', '2500-04-08 06:15:15.33', '7500-10-23 18:45:45.888' );
$datetimeoffset_params = array( '1900-01-01 00:00:00 +01:00', '0001-01-01 00:00:00 -14:00', '9999-12-31 23:59:59.123456+14:00', '5000-07-15 12:30:30.55 -03:00', '2500-04-08 06:15:15.3333 -07:00', '7500-10-23 18:45:45.888888 +07:00' );
$smalldatetime_params = array( '1900-01-01 00:00:00', '1900-01-01 00:00:00', '2079-06-05 23:59:00', '1990-07-15 12:30:00', '1945-04-08 06:15:00', '2000-10-23 18:45:00' );
$time_params = array( '00:00:00', '00:00:00.0000000', '23:59:59.123456', '12:30:30.5555', '06:15:15.33', '18:45:45.888888' );
// character strings
$char_params = array( 'Fixed', '-leng', 'th, n', 'on-Un', 'icode', 'strin' );
$varchar_params = array( 'This large row size can cause errors (such as error 512) during some normal operations, such as a clustered index key update, or sorts of the full column set, which users cannot anticipate until performing an operation.',
                           'Use varchar(max) when the sizes of the column data entries vary considerably, and the size might exceed 8,000 bytes.',
                           'Each non-null varchar(max) or nvarchar(max) column requires 24 bytes of additional fixed allocation which counts against the 8,060 byte row limit during a sort operation.',
                           'This can create an implicit limit to the number of non-null varchar(max) or nvarchar(max) columns that can be created in a table.',
                           'No special error is provided when the table is created (beyond the usual warning that the maximum row size exceeds the allowed maximum of 8060 bytes) or at the time of data insertion.',
                           'This large row size can cause errors (such as error 512) during some normal operations, such as a clustered index key update, or sorts of the full column set, which users cannot anticipate until performing an operation.' );
                           
// unicode character strings
$nchar_params = array( 'Fixed', '-leng', 'th Un', 'icode', 'strin', 'g dat' );
$nvarchar_params = array( 'max indicates that the maximum storage size is 2^31-1 bytes (2 GB).',
                            'When prefixing a string constant with the letter N, the implicit conversion will result in a Unicode string if the constant to convert does not exceed the max length for a Unicode string data type (4,000).',
                            'Otherwise, the implicit conversion will result in a Unicode large-value (max).',
                            'Each non-null varchar(max) or nvarchar(max) column requires 24 bytes of additional fixed allocation which counts against the 8,060 byte row limit during a sort operation.',
                            'This can create an implicit limit to the number of non-null varchar(max) or nvarchar(max) columns that can be created in a table.',
                            'No special error is provided when the table is created (beyond the usual warning that the maximum row size exceeds the allowed maximum of 8060 bytes) or at the time of data insertion.' );
                            
// binary strings
$binary_params = array( 'Fixed', '-leng', 'th, n', 'on-Un', 'icode', 'strin' );
$varbinary_params = array( 'Variable-length, non-', 'Unicode string data. n', 'defines the string length', 'and can be a value from 1', 'through 8,000.', 'The storage size is the' );
$varbinarymax_params = array( 'max indicates that the maximum storage size is 2^31-1 bytes (2 GB)',
                             'Use varchar(max) when the sizes of the column data entries vary considerably, and the size might exceed 8,000 bytes.',
                             'Each non-null varchar(max) or nvarchar(max) column requires 24 bytes of additional fixed allocation which counts against the 8,060 byte row limit during a sort operation.',
                             'This can create an implicit limit to the number of non-null varchar(max) or nvarchar(max) columns that can be created in a table.',
                             'No special error is provided when the table is created (beyond the usual warning that the maximum row size exceeds the allowed maximum of 8060 bytes) or at the time of data insertion.',
                             'This large row size can cause errors (such as error 512) during some normal operations, such as a clustered index key update, or sorts of the full column set, which users cannot anticipate until performing an operation.' );
// Array containing all SQLSRV_SQLTYPE_ types to pass into a bind param prepare statement      
                
$sqlTypes = array(
    'SQLSRV_SQLTYPE_BIGINT',
    'SQLSRV_SQLTYPE_BINARY',
    'SQLSRV_SQLTYPE_BIT',
    'SQLSRV_SQLTYPE_CHAR',
    'SQLSRV_SQLTYPE_DATE',
    'SQLSRV_SQLTYPE_DATETIME',
    'SQLSRV_SQLTYPE_DATETIME2',
    'SQLSRV_SQLTYPE_DATETIMEOFFSET',
    'SQLSRV_SQLTYPE_DECIMAL',
    'SQLSRV_SQLTYPE_FLOAT',
    'SQLSRV_SQLTYPE_IMAGE',
    'SQLSRV_SQLTYPE_INT',
    'SQLSRV_SQLTYPE_MONEY',
    'SQLSRV_SQLTYPE_NCHAR',
    'SQLSRV_SQLTYPE_NUMERIC',
    'SQLSRV_SQLTYPE_NVARCHAR',
    'SQLSRV_SQLTYPE_NTEXT',
    'SQLSRV_SQLTYPE_REAL',
    'SQLSRV_SQLTYPE_SMALLDATETIME',
    'SQLSRV_SQLTYPE_SMALLINT',
    'SQLSRV_SQLTYPE_SMALLMONEY',
    'SQLSRV_SQLTYPE_TEXT',
    'SQLSRV_SQLTYPE_TIME',
    'SQLSRV_SQLTYPE_TIMESTAMP',
    'SQLSRV_SQLTYPE_TINYINT',
    'SQLSRV_SQLTYPE_UNIQUEIDENTIFIER',
    'SQLSRV_SQLTYPE_VARBINARY',
    'SQLSRV_SQLTYPE_VARCHAR',
    'SQLSRV_SQLTYPE_XML'
);      
// Checks if the current error is the incompatible types error
// if so, state which sql type is incompatible with which data type
function is_incompatible_types_error( $dataType, $sqlType )
{
    $errors = sqlsrv_errors();
    foreach ($errors as $error) {
        // 22018 is the SQLSTATE for the operand crash error for incompatible types
        if ($error['SQLSTATE'] == '22018') {
            echo "Encrypted $sqlType is incompatible with encrypted $dataType\n";
        }
    }
}
function get_default_size_prec( $sqlType )
{
    if ( $sqlType == 'SQLSRV_SQLTYPE_DECIMAL' )
        $sqlType .= "(18, 5)";
    elseif ( $sqlType == 'SQLSRV_SQLTYPE_NUMERIC' )
        $sqlType .= "(10, 5)";
    elseif ( $sqlType == 'SQLSRV_SQLTYPE_CHAR' || $sqlType == 'SQLSRV_SQLTYPE_NCHAR' )
        $sqlType .= "(5)";
    return $sqlType;
}

// get sqlType constant value from string
function get_sqlType_constant( $sqlType )
{
    switch ( $sqlType ) {
        case 'SQLSRV_SQLTYPE_BIGINT':
        case 'SQLSRV_SQLTYPE_BIT':
        case 'SQLSRV_SQLTYPE_DATE':
        case 'SQLSRV_SQLTYPE_DATETIME':
        case 'SQLSRV_SQLTYPE_DATETIME2':
        case 'SQLSRV_SQLTYPE_DATETIMEOFFSET':
        case 'SQLSRV_SQLTYPE_FLOAT':
        case 'SQLSRV_SQLTYPE_IMAGE':
        case 'SQLSRV_SQLTYPE_INT':
        case 'SQLSRV_SQLTYPE_MONEY':
        case 'SQLSRV_SQLTYPE_NVARCHAR':
        case 'SQLSRV_SQLTYPE_NTEXT':
        case 'SQLSRV_SQLTYPE_REAL':
        case 'SQLSRV_SQLTYPE_SMALLDATETIME':
        case 'SQLSRV_SQLTYPE_SMALLINT':
        case 'SQLSRV_SQLTYPE_SMALLMONEY':
        case 'SQLSRV_SQLTYPE_TEXT':
        case 'SQLSRV_SQLTYPE_TIME':
        case 'SQLSRV_SQLTYPE_TIMESTAMP':
        case 'SQLSRV_SQLTYPE_TINYINT':
        case 'SQLSRV_SQLTYPE_UNIQUEIDENTIFIER':
        case 'SQLSRV_SQLTYPE_VARBINARY':
        case 'SQLSRV_SQLTYPE_VARCHAR':
        case 'SQLSRV_SQLTYPE_XML':
            return constant( $sqlType );
            break;
        case 'SQLSRV_SQLTYPE_BINARY':
            // our tests always use precision 5 for SQLSRV_SQLTYPE_BINARY
            return SQLSRV_SQLTYPE_BINARY(5);
            break;
        case 'SQLSRV_SQLTYPE_CHAR':
            // our tests always use precision 5 for SQLSRV_SQLTYPE_CHAR
            return SQLSRV_SQLTYPE_CHAR(5);
            break;
        case 'SQLSRV_SQLTYPE_DECIMAL':
            // our tests always use precision 18 scale 5 for SQLSRV_SQLTYPE_DECIMAL
            return SQLSRV_SQLTYPE_DECIMAL(18, 5);
            break;
        case 'SQLSRV_SQLTYPE_NCHAR':
            // our tests always use precision 5 for SQLSRV_SQLTYPE_NCHAR
            return SQLSRV_SQLTYPE_NCHAR(5);
            break;
        case 'SQLSRV_SQLTYPE_NUMERIC':
            // our tests always use precision 10 scale 5 for SQLSRV_SQLTYPE_NUMERIC
            return SQLSRV_SQLTYPE_NUMERIC(10, 5);
            break;
        default:
            die( "get_sqlType_constant: Invalid SQL Type $sqlType\n" );
            break;
    }
}

function isDateTimeType($sqlType)
{    
    return ($sqlType == 'SQLSRV_SQLTYPE_DATE' || 
            $sqlType == 'SQLSRV_SQLTYPE_DATETIME' ||
            $sqlType == 'SQLSRV_SQLTYPE_DATETIME2' ||
            $sqlType == 'SQLSRV_SQLTYPE_DATETIMEOFFSET' ||
            $sqlType == 'SQLSRV_SQLTYPE_SMALLDATETIME' ||
            $sqlType == 'SQLSRV_SQLTYPE_TIME');
}

function isLOBType($sqlType)
{
    return ($sqlType == 'SQLSRV_SQLTYPE_TEXT' || $sqlType == 'SQLSRV_SQLTYPE_NTEXT' || $sqlType == 'SQLSRV_SQLTYPE_IMAGE');
}

function isCompatible($compatList, $dataType, $sqlType)
{
    foreach ($compatList[$dataType] as $compatType) {
        if (stripos($compatType, $sqlType) !== false) {
            return true;
        }
    }

    return false;
}

?>

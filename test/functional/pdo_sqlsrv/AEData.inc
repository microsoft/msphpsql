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
$datetime2_params = array( '1900-01-01 00:00:00', '0001-01-01 00:00:00', '9999-12-31 23:59:59.9999999', '5000-07-15 12:30:30.5555', '2500-04-08 06:15:15.33', '7500-10-23 18:45:45.888888' );
$datetime_params = array( '1900-01-01 00:00:00', '1753-01-01 00:00:00', '9999-12-31 23:59:59.997', '5000-07-15 12:30:30.5', '2500-04-08 06:15:15.33', '7500-10-23 18:45:45.888' );
$datetimeoffset_params = array( '1900-01-01 00:00:00 +01:00', '0001-01-01 00:00:00 -14:00', '9999-12-31 23:59:59.9999999 +14:00', '5000-07-15 12:30:30.55 -03:00', '2500-04-08 06:15:15.3333 -07:00', '7500-10-23 18:45:45.888888 +07:00' );
$smalldatetime_params = array( '1900-01-01 00:00:00', '1900-01-01 00:00:00', '2079-06-05 23:59:00', '1990-07-15 12:30:00', '1945-04-08 06:15:00', '2000-10-23 18:45:00' );
$time_params = array( '00:00:00', '00:00:00.0000000', '23:59:59.9999999', '12:30:30.5555', '06:15:15.33', '18:45:45.888888' );

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

// Array containing all PDO::PARAM_ types to pass into a PDOstatement::bindParam
$pdoParamTypes = array(
    'PDO::PARAM_BOOL',
    'PDO::PARAM_NULL',
    'PDO::PARAM_INT',
    'PDO::PARAM_STR',
    'PDO::PARAM_LOB'
);

// Checks if the current error is the incompatible types error
// if so, state which sql type is incompatible with which data type
function isIncompatibleTypesError($stmt, $dataType, $pdoParamType)
{
    if ($stmt->errorInfo()[0] == "22018") {
        echo "PDO param type $pdoParamType is incompatible with encrypted $dataType\n";
    } else {
        var_dump($stmt->errorInfo());
    }
}

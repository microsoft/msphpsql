--TEST--
Insert nulls into fields of all types.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

sqlsrv_configure('WarningsReturnAsErrors', false);
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

echo "Starting\n";

require_once("MsCommon.inc");

$conn = connect();
if (!$conn) {
    fatalError("connect failed.");
}

echo "Inserting nulls into fixed size types\n";
$stmt = sqlsrv_query(
    $conn,
    "INSERT INTO test_types (bigint_type, int_type, smallint_type, tinyint_type, bit_type, decimal_type, money_type, smallmoney_type, float_type, real_type, datetime_type, smalldatetime_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                      array( null, null, null, null, null, null, null, null, null, null, null, null )
);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

echo "Inserting nulls into variable size types\n";
$stmt = sqlsrv_query(
    $conn,
    "INSERT INTO test_streamable_types (varchar_type, nvarchar_type, varbinary_type, text_type, ntext_type, image_type, xml_type, char_short_type, varchar_short_type, nchar_short_type, nvarchar_short_type, binary_short_type, varbinary_short_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
                      array( null, null,
                             array( null, null, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max')),
                             null, null,
                             null, // array( null, null, null, SQLSRV_SQLTYPE_IMAGE ),
                             null, null, null, null, null,
                             array( null, null, null, SQLSRV_SQLTYPE_BINARY(256)),
                             array( null, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY(256) ))
);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_close($conn);

echo "Test succeeded.\n";

?>
--EXPECT--
Starting
Inserting nulls into fixed size types
Inserting nulls into variable size types
Test succeeded.

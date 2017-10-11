--TEST--
LOB types as strings.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');

    $conn = connect();
    if (!$conn) {
        fatalError("Failed to connect.");
    }

    sqlsrv_query($conn, "IF OBJECT_ID('PhpCustomerTable', 'U') IS NOT NULL DROP TABLE [PhpCustomerTable]");
    sqlsrv_query($conn, "CREATE TABLE [PhpCustomerTable] ([Id] int NOT NULL Identity (100,2) PRIMARY KEY, [Field2] text, [Field3] image, [Field4] ntext, [Field5] varbinary(max), [Field6] varchar(max), [Field7] nvarchar(max))");
    sqlsrv_query($conn, "INSERT [PhpCustomerTable] ([Field2], [Field3], [Field4], [Field5], [Field6], [Field7]) VALUES ('This is field 2.', 0x010203, 'This is field 4.', 0x040506, 'This is field 6.', 'This is field 7.' )");
    $stmt = sqlsrv_query($conn, "SELECT * FROM [PhpCustomerTable]");
    sqlsrv_fetch($stmt);


    $v = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if (!$v) {
        print('Failed to get text field');
    } else {
        echo "$v<br/>\n";
    }

    $v = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if (!$v) {
        print('Failed to get text field');
    }

    echo "$v<br/>\n";

    $v = sqlsrv_get_field($stmt, 2, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if (!$v) {
        die('Failed to get image field]');
    }
    echo "$v<br/>\n";

    $v = sqlsrv_get_field($stmt, 3, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if (!$v) {
        print('Failed to get ntext field');
    }

    echo "$v<br/>\n";

    $v = sqlsrv_get_field($stmt, 4, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if (!$v) {
        print('Failed to get varbinary(max) field');
    }

    echo "$v<br/>\n";

    $v = sqlsrv_get_field($stmt, 5, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if (!$v) {
        print('Failed to get varchar(max) field');
    }

    echo "$v<br/>\n";

    $v = sqlsrv_get_field($stmt, 6, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if (!$v) {
        print('Failed to get nvarchar(max) field');
    }

    echo "$v<br/>\n";

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    echo "Test successful."
?>
--EXPECT--
100<br/>
This is field 2.<br/>
010203<br/>
This is field 4.<br/>
040506<br/>
This is field 6.<br/>
This is field 7.<br/>
Test successful.

--TEST--
output string parameter fix to make sure the correct length is set.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
    $sql = 'CREATE PROCEDURE #GetAGuid
        (@NewValue varchar(50) OUTPUT)
        AS
        BEGIN
            set @NewValue = NEWID()
            select 1
            select 2
            select 3
        END';

    require_once('MsCommon.inc');
    $conn = AE\connect();

    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
     
    $sql = '{CALL #GetAGuid (?)}';
    $guid = 'test.';
    $params = array(
                array(&$guid,
                      SQLSRV_PARAM_OUT,
                      SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR),
                      SQLSRV_SQLTYPE_VARCHAR(50)
                )
              );

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    echo 'New Guid: >'.$guid."<\n";

    while (sqlsrv_next_result($stmt) != null) {
    }

    echo 'New Guid: >'.$guid."<\n";

?>
--EXPECTREGEX--
New Guid: \>test\..*\<
New Guid: \>[0-9A-F]{8}\-[0-9A-F]{4}\-[0-9A-F]{4}\-[0-9A-F]{4}\-[0-9A-F]{12}\<

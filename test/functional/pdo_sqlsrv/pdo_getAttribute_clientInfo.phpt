--TEST--
Test client info by calling PDO::getAttribute with PDO::ATTR_CLIENT_VERSION
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();
    // An example using PDO::ATTR_CLIENT_VERSION
    print_r($conn->getAttribute(PDO::ATTR_CLIENT_VERSION));

    //free the connection
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECTREGEX--
Array
\(
    \[(DriverDllName|DriverName)\] => (msodbcsql1[1-9].dll|(libmsodbcsql-[0-9]{2}\.[0-9]\.so\.[0-9]\.[0-9]|libmsodbcsql.[0-9]{2}.dylib))
    \[DriverODBCVer\] => [0-9]{1,2}\.[0-9]{1,2}
    \[DriverVer\] => [0-9]{1,2}\.[0-9]{1,2}\.[0-9]{4}
    \[ExtensionVer\] => [0-9].[0-9]\.[0-9](-(RC[0-9]?|preview))?(\.[0-9]+)?(\+[0-9]+)?
\)

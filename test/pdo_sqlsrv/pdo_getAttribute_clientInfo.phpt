--TEST--
Test client info by calling PDO::getAttribute with PDO::ATTR_CLIENT_VERSION 
--FILE--
<?php
require_once("autonomous_setup.php");

$conn = new PDO( "sqlsrv:server=$serverName", "$username", "$password" );

// An example using PDO::ATTR_CLIENT_VERSION
print_r($conn->getAttribute( PDO::ATTR_CLIENT_VERSION ));

//free the connection
$conn=null;
?>
--EXPECTREGEX--
Array
\(
    \[(DriverDllName|DriverName)\] => (msodbcsql1[1-9].dll|libmsodbcsql-[1-9]{2}.[0-9].so.[0-9].[0-9])
    \[DriverODBCVer\] => [0-9]{1,2}\.[0-9]{1,2}
    \[DriverVer\] => [0-9]{1,2}\.[0-9]{1,2}\.[0-9]{4}
    \[ExtensionVer\] => [0-9]\.[0-9]\.[0-9](\-((rc)|(preview))(\.[0-9]+)?)?(\+[0-9]+)?
\)
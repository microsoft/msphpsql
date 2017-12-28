--TEST--
shows the PDO::ATR_ERRMODE attribute, before and after changing its value
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:Server=$server ; Database = $databaseName", "$uid", "$pwd");

$attributes1 = array( "ERRMODE" );
foreach ( $attributes1 as $val ) {
     echo "PDO::ATTR_$val: ";
     var_dump ($conn->getAttribute( constant( "PDO::ATTR_$val" ) ));
}

$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

$attributes1 = array( "ERRMODE" );
foreach ( $attributes1 as $val ) {
     echo "PDO::ATTR_$val: ";
     var_dump ($conn->getAttribute( constant( "PDO::ATTR_$val" ) ));
}

// An example using PDO::ATTR_CLIENT_VERSION
print_r($conn->getAttribute( PDO::ATTR_CLIENT_VERSION ));

//free the connection
$conn=null;
?>
--EXPECTREGEX--
PDO::ATTR_ERRMODE: int\(0\)
PDO::ATTR_ERRMODE: int\(2\)
Array
\(
    \[DriverDllName\]|\[DriverName\] => (msodbcsql[0-9]{2}\.dll|(libmsodbcsql-[0-9]{2}\.[0-9]\.so\.[0-9]\.[0-9]|libmsodbcsql.[0-9]{2}.dylib))
    \[DriverODBCVer\] => [0-9]{1,2}\.[0-9]{1,2}
    \[DriverVer\] => [0-9]{1,2}\.[0-9]{1,2}\.[0-9]{4}
    \[ExtensionVer\] => [0-9].[0-9]\.[0-9](-(RC[0-9]?|preview))?(\.[0-9]+)?(\+[0-9]+)?
\)
--TEST--
client information.
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
     echo "Could not connect.\n";
     die( print_r( sqlsrv_errors(), true));
}

if( $client_info = sqlsrv_client_info( $conn))
{
       foreach( $client_info as $key => $value)
      {
              echo $key.": ".$value."\n";
      }
}
else
{
       echo "Client info error.\n";
}

/* Close connection resources. */
sqlsrv_close( $conn);
?>
--EXPECTREGEX--
DriverDllName|DriverName: (msodbcsql[0-9]{2}\.dll|(libmsodbcsql-[0-9]{2}\.[0-9]\.so\.[0-9]\.[0-9]|libmsodbcsql.[0-9]{2}.dylib))
DriverODBCVer: [0-9]{1,2}\.[0-9]{1,2}
DriverVer: [0-9]{1,2}\.[0-9]{1,2}\.[0-9]{4}
ExtensionVer: [0-9].[0-9]\.[0-9](-(RC[0-9]?|preview))?(\.[0-9]+)?(\+[0-9]+)?
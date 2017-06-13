--TEST--
Returns information about the server.
--SKIPIF--

--FILE--
<?php
/* Connect to the local server using Windows Authentication. */
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
     echo "Could not connect.<br>";
     die( print_r( sqlsrv_errors(), true));
}

$server_info = sqlsrv_server_info( $conn);
if( $server_info )
{
      foreach( $server_info as $key => $value)
      {
             echo $key.": ".$value."<br>";
      }
}
else
{
      echo "Error in retrieving server info.<br>";
      die( print_r( sqlsrv_errors(), true));
}

/* Free connection resources. */
sqlsrv_close( $conn);
?>
--EXPECTREGEX--
CurrentDatabase: AdventureWorks.*<br>SQLServerVersion: 1[2-9].00.[0-9]{4}<br>SQLServerName: SQL.+<br>
<?php
$server = 'TARGET_SERVER';
$databaseName = 'TARGET_DATABASE';
$uid = 'TARGET_USERNAME';
$pwd = 'TARGET_PASSWORD';

// RevisionNumber in SalesOrderHeader is subject to a trigger incrementing it whenever
// changes are made to SalesOrderDetail. Since RevisionNumber is a tinyint, it can
// overflow quickly if the BVT tests often run. So we change it directly here first
// before it can overflow.
function ResetRevisionNumber( $server, $databaseName, $uid, $pwd )
{
    $connectionInfo = array( "Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd );
    $conn = sqlsrv_connect( $server, $connectionInfo);
    if( $conn === false )
    {
         echo "Could not connect.<br>";
         die( print_r( sqlsrv_errors(), true));
    }

    $stmt0 = sqlsrv_query( $conn, "UPDATE Sales.SalesOrderHeader SET RevisionNumber = 2");
    if ( !$stmt0 )
    {
        echo "Resetting the RevisionNumber failed.\n";
        die( print_r( sqlsrv_errors(), true));
    }
    
    sqlsrv_close( $conn );
}

?>

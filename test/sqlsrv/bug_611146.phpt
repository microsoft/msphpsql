--TEST--
Bug: 611146: The parmeter index is off by 1 for certain parameter related error messages.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require( 'MsCommon.inc' );
    require ('sqlsrv_test_base.inc');

    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    function bind_params($conn)
    {
        $param1 = array();
        $param2 = 6;    
     
        $params = array($param1, $param2);
        $stmt = sqlsrv_query($conn, "Select 1 Where ? < ?", $params); 
        
        if($stmt === false ) {
            print_r(sqlsrv_errors());
        }
    }

    $conn = Connect();
    bind_params($conn);
    echo "Test Succeeded";

?>

--EXPECTF--
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -9
            [code] => -9
            [2] => Parameter array 1 must have at least one value or variable.
            [message] => Parameter array 1 must have at least one value or variable.
        )

)
Test Succeeded
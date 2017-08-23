--TEST--
Connect using a custom keystore provider with some required inputs missing
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

    function connect( $server, $connectionInfo )
    {
        $conn = sqlsrv_connect( $server, $connectionInfo );
        if( $conn === false )
        {
            echo "Failed to connect.\n";
            $errors = sqlsrv_errors();
            print_r( $errors[0] );
        }
        else
        {
            echo "Connected successfully with ColumnEncryption enabled.\n";
        }
        
        return $conn;
    }
    
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );  

    require( 'MsSetup.inc' );
    require( 'AE_Ksp.inc' );
    
    $ksp_path = getKSPpath();

    echo("Connecting... with column encryption\n");
    $connectionInfo = array( "Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd,
                             "ColumnEncryption"=>"enabled");

    connect( $server, $connectionInfo );
    
    echo("Connecting... with an invalid input to CEKeystoreProvider\n");
    $connectionInfo = array( "Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd,
                             "ColumnEncryption"=>"enabled", 
                             "CEKeystoreProvider"=>1);

    connect( $server, $connectionInfo );

    echo("Connecting... with an empty path\n");
    $connectionInfo = array( "Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd,
                             "ColumnEncryption"=>"enabled", 
                             "CEKeystoreProvider"=>"",
                             "CEKeystoreName"=>$ksp_name,
                             "CEKeystoreEncryptKey"=>$encrypt_key);

    connect( $server, $connectionInfo );
    
    echo("Connecting... without a name\n");
    $connectionInfo = array( "Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd,
                             "ColumnEncryption"=>"enabled", 
                             "CEKeystoreProvider"=>$ksp_path, 
                             "CEKeystoreEncryptKey"=>$encrypt_key);

    connect( $server, $connectionInfo );

    echo("Connecting... with an empty name\n");
    $connectionInfo = array( "Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd,
                             "ColumnEncryption"=>"enabled", 
                             "CEKeystoreProvider"=>$ksp_path, 
                             "CEKeystoreName"=>"",
                             "CEKeystoreEncryptKey"=>$encrypt_key);

    connect( $server, $connectionInfo );

    echo("Connecting... without a key\n");
    $connectionInfo = array( "Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd,
                             "ColumnEncryption"=>"enabled", 
                             "CEKeystoreProvider"=>$ksp_path, 
                             "CEKeystoreName"=>$ksp_name);
                             
    connect( $server, $connectionInfo );

    echo("Connecting... with all required inputs\n");
    $connectionInfo = array( "Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd,
                             "ColumnEncryption"=>"enabled", 
                             "CEKeystoreProvider"=>$ksp_path, 
                             "CEKeystoreName"=>$ksp_name,
                             "CEKeystoreEncryptKey"=>$encrypt_key);
                             
    connect( $server, $connectionInfo );
    
    echo "Done\n";
?>
--EXPECT--
Connecting... with column encryption
Connected successfully with ColumnEncryption enabled.
Connecting... with an invalid input to CEKeystoreProvider
Failed to connect.
Array
(
    [0] => IMSSP
    [SQLSTATE] => IMSSP
    [1] => -33
    [code] => -33
    [2] => Invalid value type for option CEKeystoreProvider was specified.  String type was expected.
    [message] => Invalid value type for option CEKeystoreProvider was specified.  String type was expected.
)
Connecting... with an empty path
Failed to connect.
Array
(
    [0] => IMSSP
    [SQLSTATE] => IMSSP
    [1] => -104
    [code] => -104
    [2] => Invalid value for loading a custom keystore provider.
    [message] => Invalid value for loading a custom keystore provider.
)
Connecting... without a name
Failed to connect.
Array
(
    [0] => IMSSP
    [SQLSTATE] => IMSSP
    [1] => -101
    [code] => -101
    [2] => The name of the custom keystore provider is missing.
    [message] => The name of the custom keystore provider is missing.
)
Connecting... with an empty name
Failed to connect.
Array
(
    [0] => IMSSP
    [SQLSTATE] => IMSSP
    [1] => -104
    [code] => -104
    [2] => Invalid value for loading a custom keystore provider.
    [message] => Invalid value for loading a custom keystore provider.
)
Connecting... without a key
Failed to connect.
Array
(
    [0] => IMSSP
    [SQLSTATE] => IMSSP
    [1] => -103
    [code] => -103
    [2] => The encryption key for the custom keystore provider is missing.
    [message] => The encryption key for the custom keystore provider is missing.
)
Connecting... with all required inputs
Connected successfully with ColumnEncryption enabled.
Done
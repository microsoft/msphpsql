--TEST--
Connect using a custom keystore provider with some required inputs missing
--SKIPIF--
<?php require('skipif_not_ksp.inc'); ?>
--FILE--
<?php

    function connect( $server, $connectionInfo )
    {
        $conn = sqlsrv_connect( $server, $connectionInfo );
        if( $conn === false )
        {
            echo "Failed to connect.\n";
            $errors = sqlsrv_errors();
            foreach ( $errors[0] as $key => $error ) 
            {
                if( is_string( $key ) )
                    echo "[$key] => $error\n";
            }
            echo "\n";
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
[SQLSTATE] => IMSSP
[code] => -33
[message] => Invalid value type for option CEKeystoreProvider was specified.  String type was expected.

Connecting... with an empty path
Failed to connect.
[SQLSTATE] => IMSSP
[code] => -104
[message] => Invalid value for loading a custom keystore provider.

Connecting... without a name
Failed to connect.
[SQLSTATE] => IMSSP
[code] => -101
[message] => The name of the custom keystore provider is missing.

Connecting... with an empty name
Failed to connect.
[SQLSTATE] => IMSSP
[code] => -104
[message] => Invalid value for loading a custom keystore provider.

Connecting... without a key
Failed to connect.
[SQLSTATE] => IMSSP
[code] => -103
[message] => The encryption key for the custom keystore provider is missing.

Connecting... with all required inputs
Connected successfully with ColumnEncryption enabled.
Done
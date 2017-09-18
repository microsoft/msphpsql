--TEST--
Connect using a custom keystore provider with some required inputs missing
--SKIPIF--
<?php require('skipif_not_ksp.inc'); ?>
--FILE--
<?php
    require( 'MsSetup.inc' );
    require( 'AE_Ksp.inc' );

    function connect( $connectionInfo )
    {
        global $server, $uid, $pwd;
        
        try
        {
            $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo", $uid, $pwd );
            echo "Connected successfully with ColumnEncryption enabled and KSP specified.\n";
        }
        catch( PDOException $e )
        {
            echo "Failed to connect.\n";
            print_r( $e->getMessage() );
            echo "\n";
        }        
    }
    
    $ksp_path = getKSPpath();

    echo("Connecting... with column encryption\n");
    $connectionInfo = "Database = $databaseName; ColumnEncryption = Enabled; ";
    connect( $connectionInfo );

    echo("\nConnecting... with an invalid input to CEKeystoreProvider\n");
    $connectionInfo = "Database = $databaseName; ColumnEncryption = Enabled; ";
    $connectionInfo .= "CEKeystoreName = 1; "; 
    $connectionInfo .= "CEKeystoreProvider = $ksp_path; ";
    $connectionInfo .= "CEKeystoreEncryptKey = $encrypt_key; ";    
    connect( $connectionInfo );

    echo("\nConnecting... with an empty path\n");
    $connectionInfo = "Database = $databaseName; ColumnEncryption = Enabled; ";
    $connectionInfo .= "CEKeystoreName = $ksp_name; "; 
    $connectionInfo .= "CEKeystoreProvider = ; ";
    $connectionInfo .= "CEKeystoreEncryptKey = $encrypt_key; ";
    connect( $connectionInfo );

    echo("\nConnecting... without a path\n");
    $connectionInfo = "Database = $databaseName; ColumnEncryption = Enabled; ";
    $connectionInfo .= "CEKeystoreName = $ksp_name; "; 
    $connectionInfo .= "CEKeystoreEncryptKey = $encrypt_key;";
    connect( $connectionInfo );
    
    echo("\nConnecting... without a name\n");
    $connectionInfo = "Database = $databaseName; ColumnEncryption = Enabled; ";
    $connectionInfo .= "CEKeystoreProvider = $ksp_path; ";
    $connectionInfo .= "CEKeystoreEncryptKey = $encrypt_key; ";    
    connect( $connectionInfo );
    
    echo("\nConnecting... without a key\n");
    $connectionInfo = "Database = $databaseName; ColumnEncryption = Enabled; ";
    $connectionInfo .= "CEKeystoreProvider = $ksp_path; ";
    $connectionInfo .= "CEKeystoreName = $ksp_name; "; 
    connect( $connectionInfo );
    
    echo("\nConnecting... with all required inputs\n");
    $connectionInfo = "Database = $databaseName; ColumnEncryption = Enabled; ";
    $connectionInfo .= "CEKeystoreProvider = $ksp_path; ";
    $connectionInfo .= "CEKeystoreName = $ksp_name; "; 
    $connectionInfo .= "CEKeystoreEncryptKey = $encrypt_key; ";    
    connect( $connectionInfo );

    echo "Done\n";
?>
--EXPECTREGEX--
Connecting\.\.\. with column encryption
Connected successfully with ColumnEncryption enabled and KSP specified\.

Connecting\.\.\. with an invalid input to CEKeystoreProvider
Failed to connect.
SQLSTATE\[HY024\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Invalid attribute value

Connecting\.\.\. with an empty path
Failed to connect.
SQLSTATE\[IMSSP\]: Invalid value for loading a custom keystore provider\.

Connecting\.\.\. without a path
Failed to connect.
SQLSTATE\[IMSSP\]: The path to the custom keystore provider is missing\.

Connecting\.\.\. without a name
Failed to connect.
SQLSTATE\[IMSSP\]: The name of the custom keystore provider is missing\.

Connecting\.\.\. without a key
Failed to connect.
SQLSTATE\[IMSSP\]: The encryption key for the custom keystore provider is missing\.

Connecting\.\.\. with all required inputs
Connected successfully with ColumnEncryption enabled and KSP specified\.
Done
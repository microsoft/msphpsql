--TEST--
Test using sqlserv_query for binding parameters with ColumnEncryption enabled and a custome keystore provider
--SKIPIF--
<?php require('skipif_not_ksp.inc'); ?>
--FILE--
<?php
    function CreatePatientsTable()
    {
        global $conn;
        $tablename = 'Patients';
                
        $stmt = sqlsrv_query( $conn, "IF OBJECT_ID('$tablename', 'U') IS NOT NULL DROP TABLE $tablename" );
        sqlsrv_free_stmt( $stmt );

        $tsql = "CREATE TABLE $tablename (
        [PatientId] [int] IDENTITY(1,1) NOT NULL,
        [SSN] [char](11) COLLATE Latin1_General_BIN2 ENCRYPTED WITH (COLUMN_ENCRYPTION_KEY = CustomCEK, ENCRYPTION_TYPE = Deterministic, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256') NOT NULL,
        [FirstName] [nvarchar](50) COLLATE Latin1_General_BIN2 ENCRYPTED WITH (COLUMN_ENCRYPTION_KEY = CustomCEK, ENCRYPTION_TYPE = Deterministic, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256') NULL,
        [LastName] [nvarchar](50) COLLATE Latin1_General_BIN2 ENCRYPTED WITH (COLUMN_ENCRYPTION_KEY = CustomCEK, ENCRYPTION_TYPE = Deterministic, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256') NULL,
        [BirthDate] [date] ENCRYPTED WITH (COLUMN_ENCRYPTION_KEY = CustomCEK, ENCRYPTION_TYPE = Randomized, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256') NOT NULL)";
        
        $stmt = sqlsrv_query( $conn, $tsql );
        if (! $stmt )
        {
            echo "Failed to create test table!\n";
            die( print_r( sqlsrv_errors(), true ));
        }

        return $tablename;
    }
    
    function SelectData()
    {
        global $conn, $tablename;
        
        $stmt = sqlsrv_query($conn, "SELECT * FROM $tablename");

        while ($obj = sqlsrv_fetch_object( $stmt ))
        {
            echo $obj->PatientId . "\n";
            echo $obj->SSN . "\n";
            echo $obj->FirstName . "\n";
            echo $obj->LastName . "\n";
            echo $obj->BirthDate . "\n\n";        
        }        
    }
    
    function PrintError()
    {
        $errors = sqlsrv_errors();
        foreach ( $errors as $error )
        {
            echo "  SQLSTATE: " . $error['SQLSTATE'] . "\n";  
            echo "  code: " . $error['code'] . "\n";  
            echo "  message: " . $error['message'] . "\n\n";  
        }
    }
    
    sqlsrv_configure( 'WarningsReturnAsErrors', 1 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );  

    require_once( 'MsSetup.inc' );
    require_once( 'AE_Ksp.inc' );

    $ksp_path = getKSPpath();

    $connectionInfo = array( "Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd,
                             "ReturnDatesAsStrings"=>true, "ColumnEncryption"=>'Enabled', 
                             "CEKeystoreProvider"=>$ksp_path, 
                             "CEKeystoreName"=>$ksp_name,
                             "CEKeystoreEncryptKey"=>$encrypt_key);

    $conn = sqlsrv_connect( $server, $connectionInfo );
    if( $conn === false )
    {
        echo "Failed to connect.\n";
        PrintError();
    }
    else
    {
        echo "Connected successfully with ColumnEncryption enabled.\n\n";
    }
    
    $tablename = CreatePatientsTable();
    
    $tsql = "INSERT INTO $tablename (SSN, FirstName, LastName, BirthDate) VALUES (?, ?, ?, ?)";
    $inputs = array( '748-68-0245', 'Jeannette', 'McDonald', '2002-11-28' );
    
    //expects an error in Column Encryption enabled connection
    print_r( "Using sqlsrv_query and binding parameters with literal values:\n" );
    $stmt = sqlsrv_query( $conn, $tsql, $inputs );
    if ( !$stmt)
        PrintError();
    
    //expects an error in Column Encryption enabled connection
    print_r( "Using sqlsrv_query and binding parameters with parameter arrays and no sqltypes provided:\n" );
    $stmt = sqlsrv_query( $conn, $tsql, array( array( $inputs[0], SQLSRV_PARAM_IN ),
                                               array( $inputs[1], SQLSRV_PARAM_IN ),
                                               array( $inputs[2], SQLSRV_PARAM_IN ), 
                                               array( $inputs[3], SQLSRV_PARAM_IN )));
    if ( !$stmt)
        PrintError();
    
    //no error is expected
    print_r( "Using sqlsrv_query and binding parameters with parameter arrays and sqltypes provided:\n" );
    $stmt = sqlsrv_query( $conn, $tsql, array( array( $inputs[0], null, null, SQLSRV_SQLTYPE_CHAR(11) ), 
                                               array( $inputs[1], null, null, SQLSRV_SQLTYPE_NVARCHAR(50) ), 
                                               array( $inputs[2], null, null, SQLSRV_SQLTYPE_NVARCHAR(50) ), 
                                               array( $inputs[3], null, null, SQLSRV_SQLTYPE_DATE ) ));
    if ( !$stmt)
        PrintError();
    
    SelectData();
    
    
    echo "Done\n";
?>
--EXPECT--
Connected successfully with ColumnEncryption enabled.

Using sqlsrv_query and binding parameters with literal values:
  SQLSTATE: IMSSP
  code: -63
  message: Must specify the SQL type for each parameter in a parameterized query when using sqlsrv_query in a column encryption enabled connection.

Using sqlsrv_query and binding parameters with parameter arrays and no sqltypes provided:
  SQLSTATE: IMSSP
  code: -63
  message: Must specify the SQL type for each parameter in a parameterized query when using sqlsrv_query in a column encryption enabled connection.

Using sqlsrv_query and binding parameters with parameter arrays and sqltypes provided:
1
748-68-0245
Jeannette
McDonald
2002-11-28

Done
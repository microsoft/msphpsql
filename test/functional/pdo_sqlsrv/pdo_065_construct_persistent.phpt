--TEST--
Exception is thrown if the unsupported attribute ATTR_PERSISTENT is put into the connection options
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsSetup.inc';
try{
    echo "Testing a connection with ATTR_PERSISTENT...\n";
    // setting PDO::ATTR_PERSISTENT in PDO constructor returns an exception
    $dsn = "sqlsrv:Server = $server;database = $databaseName;";
    if ( $keystore != "none" )
    {
        $dsn .= "ColumnEncryption=Enabled;";
    }
    if ( $keystore == "ksp" )
    {   
        require( 'AE_Ksp.inc' );
        $ksp_path = getKSPPath();
        $dsn .= "CEKeystoreProvider=$ksp_path;CEKeystoreName=$ksp_name;CEKeystoreEncryptKey=$encrypt_key;";
    }
    $attr = array(PDO::ATTR_PERSISTENT => true); 
    $conn = new PDO( $dsn, $uid, $pwd, $attr); 
    
    //free the connection 
    unset( $conn );
}
catch( PDOException $e ) {
    echo "Exception from unsupported attribute (ATTR_PERSISTENT) is caught\n";
}
try{

    require_once 'MsCommon.inc';
    
    echo "\nTesting new connection after exception thrown in previous connection...\n";
    $tableName1 = GetTempTableName('tab1', false);
    $conn = connect();
    create_table( $conn, $tableName1, array( new columnMeta( "int", "c1" ), new columnMeta( "varchar(10)", "c2" )));
    insert_row( $conn, $tableName1, array( "c1" => 1, "c2" => "column2" ), "exec" );
    
    $result = select_row( $conn, $tableName1, "PDO::FETCH_ASSOC" );
    if ($result['c1'] == 1 && $result['c2'] == 'column2') {
        echo "Test successfully completed";
    }
    //free the statement and connection 
    DropTable( $conn, $tableName );
    unset( $stmt );
    unset( $conn );
}
catch( PDOException $e ) {
    var_dump( $e);
}
?> 
--EXPECT--
Testing a connection with ATTR_PERSISTENT...
Exception from unsupported attribute (ATTR_PERSISTENT) is caught

Testing new connection after exception thrown in previous connection...
Test successfully completed

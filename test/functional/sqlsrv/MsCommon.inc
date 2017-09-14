<?php
/*
    Microsoft SQL Server Driver for PHP - Unit Test Framework
    Copyright (c) Microsoft Corporation.  All rights reserved.

    Description:
        Common functions (shared by all tests).

*/

$useUTF8data = false;

function IsWindows()
{
    // This method returns TRUE when running in a Windows platform 
    // The possible values are WIN32, WINNT and Windows
    return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
}

function UseUTF8Data()
{
    global $useUTF8data;
    return $useUTF8data;        
}

function SetUTF8Data($val)
{
    global $useUTF8data;
    $useUTF8data = $val;        
}

function TestMode()
{
    $testMode = getenv('PHPT_EXEC');
    return ($testMode ? true : false);
}

function TraceMode()
{
    include 'MsSetup.inc';
    return ((!TestMode() && $traceEnabled) ? true : false);
}

function Trace($msg)
{
    if (TraceMode())
    {
        echo $msg;
    }
}

function TraceData($sqlType, $data)
{
    if (TraceMode())
    {
        $msg = strtoupper(" $sqlType:");
        echo "$msg\t";
        if (strlen($msg) <= 7)
        {
            echo "\t";
        }
        if (strlen($msg) <= 15)
        {
            echo "\t";
        }
        echo "$data\n"
;
    }
}

function IsMarsSupported()
{
    include 'MsSetup.inc';
    return ($marsMode ? true : false);
}

function IsDaasMode()
{
    include 'MsSetup.inc';
    return ($daasMode ? true : false);
}

function IsAEQualified($conn)
{
    $msodbcsql_ver = sqlsrv_client_info($conn)['DriverVer'];
    $server_ver = sqlsrv_server_info($conn)['SQLServerVersion'];
    $msodbcsql_maj = explode(".", $msodbcsql_ver)[0];
    $msodbcsql_min = explode(".", $msodbcsql_ver)[1];
    if ($msodbcsql_maj < 17 || explode('.', $server_ver)[0] < 13)
        return false;
    return true;
}

function StartTest($testName)
{
    include 'MsSetup.inc';
    if (TraceMode())
    {
        echo "$PhpDriver: starting \"$testName\" test...\n\n";
    }
    if (!extension_loaded("sqlsrv"))
    {
        die("$PhpDriver cannot be loaded.");
    }

    // Set timezone
    $tz = ini_get('date.timezone');
    if (strcmp($tz, "") == 0)
    {
        date_default_timezone_set('America/Los_Angeles');
        $tz = date_default_timezone_get();
    }
    Trace("Timezone: $tz.\n");
}

function EndTest($testName)
{
    include 'MsSetup.inc';
    if (TraceMode())
    {
        echo "\n$PhpDriver: ";
    }
    echo "Test \"$testName\" completed successfully.\n";
}


function Setup()
{
    set_time_limit(0);
    sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_OFF);
    sqlsrv_configure('WarningsReturnAsErrors', 1);
}

function Configure($param, $expected)
{
    sqlsrv_configure($param, $expected);
    $actual = sqlsrv_get_config($param);

    if ($actual == $expected)
    {
        Trace("Set configuration parameter $param = $actual.\n");
    }
    else
    {
        die("Failed to set configuration parameter $param = $expected.");   
    }
}

function ConnectUTF8()
{
    return Connect(array( 'CharacterSet'=>'UTF-8' ));
}

function Connect($options = array())
{
    include 'MsSetup.inc';

    if (sizeof($options) > 0)
    {
        $connectionOptions = array_merge($connectionOptions, $options);
    }
    
    Trace("Attempting connection to $server...");
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false)
    {
        FatalError("Failed to connect to $server.");
    }
    Trace(" successfully connected.\n\n");
    return ($conn);
}


/**
 * Connect to the database specified in MsSetup.inc; Column Encryption options automatically added when $keystore is not none
 * @param array $options : connection attributes to pass to sqlsrv_connect
 * @param bool $disableCE : flag for disabling column encryption even when keystore is NOT none
 *                          for testing fetching encrypted data when connection column encryption is off
 * @return connection resource
 */
function ae_connect( $options = array(), $disableCE = false )
{
    require  'MsSetup.inc';
    if ( sizeof( $options ) > 0 )
    {
        $connectionOptions = array_merge( $connectionOptions, $options );
    }
    if ( $keystore != "none" && !$disableCE )
    {
        $connectionOptions = array_merge( $connectionOptions, array( "ColumnEncryption" => "Enabled" ));
    }
    if ( $keystore == "ksp" && !$disableCE )
    {   
        require( 'AE_Ksp.inc' );
        $ksp_path = getKSPPath();
        $ksp_options = array( "CEKeystoreProvider"=>$ksp_path, "CEKeystoreName"=>$ksp_name, "CEKeystoreEncryptKey"=>$encrypt_key );
        $connectionOptions = array_merge( $connectionOptions, $ksp_options );
    }
    $conn = sqlsrv_connect( $server, $connectionOptions );
    if ( $conn === false )
        FatalError( "Failed to connect to $server." );
    return $conn;
}


/**
 * @return string CEK name depending on the connection keywords
 */
function getCekName()
{
    require  'MsSetup.inc';
    $cekName = '';
    switch ( $keystore ) {
        case "none":
            $cekName = '';
            break;
        case "win":
            $cekName = 'AEColumnKey';
            break;
        case "ksp":
            $cekName = 'CustomCEK';
            break;
        case "akv":
            $cekName = 'AKVColumnKey';
            break;
        default:
            echo "getCekName: Invalid keystore name.\n";
    }
    return $cekName;
}


/**
 * class for encapsulating column metadata needed for creating a table
 */
class columnMeta {
    public $colName;
    public $dataType;   //a string that includes the size of the type if necessary (e.g., decimal(10,5))
    public $encType;    //randomized or deterministic; default is deterministic
    public $options;    //a string that is null by default (e.g. NOT NULL Identity (1,1) )
        
    function __construct( $dataType, $colName = null, $options = null, $encType = "deterministic" )
    {
        if ( is_null( $colName ))
        {
            $this->colName = get_default_colname( $dataType );
        }
        else
        {
            $this->colName = $colName;
        }
        $this->dataType = $dataType;
        $this->encType = $encType;
        $this->options = $options;
    }
    /**
     * @return string column definition for creating a table
     */
    function getColDef()
    {
        require  'MsSetup.inc';
        $append = " ";
        
        // an identity column is not encrypted because a select query with identity column as the where clause is often run and the user want to have to bind parameter every time
        if ( $keystore != "none" && stripos( $this->options, "identity" ) === false )
        {
            $cekName = getCekName();
            if ( stripos( $this->dataType, "char" ) !== false )
                $append .= "COLLATE Latin1_General_BIN2 ";
            $append .= sprintf( "ENCRYPTED WITH (ENCRYPTION_TYPE = %s, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256', COLUMN_ENCRYPTION_KEY = $cekName) ", $this->encType );
        }
        $append .= $this->options;
        $colDef = "[" . $this->colName . "] " . $this->dataType . $append;
        return $colDef;
    }
}


/**
 * @return string default column name when a name is not provided in the columnMeta class
 */
function get_default_colname( $dataType )
{
    $colName = "c_" . str_replace( ",", "_", str_replace( "(", "_", $dataType ));
    $colName = rtrim( $colName, ")" );
    return $colName;
}


/**
 * Create a table
 * @param object $conn : PDO connection object
 * @param string $tbname : name of the table to be created
 * @param array $columnMetaArr : array of columnMeta objects, which contain metadata for one column
 */
function create_table( $conn, $tbname, $columnMetaArr )
{
    DropTable( $conn, $tbname );
    $colDef = "";
    foreach ( $columnMetaArr as $meta )
    {
        $colDef = $colDef . $meta->getColDef() . ", ";
    }
    $colDef = rtrim( $colDef, ", " );
    $createSql = "CREATE TABLE $tbname ( $colDef )";
    sqlsrv_query( $conn, $createSql );
}


/**
 * class for encapsulating optional parameters for binding parameters in sqlsrv_prepare
 */
class bindParamOption {
    public $index;      //1-based index of the parameter
    public $direction;  //SQLSRV_PARAM_ constant indicating the parameter direction
    public $phpType;    //SQLSRV_PHPTYPE_ constant specifying the php type of the return values
    public $sqlType;    //SQLSRV_SQLTYPE_ constant specifying the SQL type of the input
    
    function __construct( $index, $direction = null, $phpType = null, $sqlType = null )
    {
        $this->index = $index;
        $this->direction = $direction;
        $this->phpType = $phpType;
        $this->sqlType = $sqlType;
    }
    /**
     * @param resource $conn : connection resource
     * @param mix $var : variable to bind to the SQL statement parameter
     * @return array needed to bind parameter in sqlsrv_prepare
     */
    function bindParamArr( $var )
    {
        // get the constant values of direction, phpType, and sqlType
        $direction = null;
        $phpType = null;
        $sqlType = null;
        if ( $this->direction )
            $direction = constant( $this->direction );
        if ( $this->phpType )
            $phpType = constant( $this->phpType );
        if ( $this->sqlType )
        {
            // parse out the datatype name, size, precision, and/or scale from a SQLSRV_SQLTYPE_ constant
            $size = null;
            $prec = null;
            $scal = null;
            $type_size = explode( "(", $this->sqlType );
            $type = $type_size[0];
            if ( count( $type_size ) > 1 )
            {
                $size = $type_size[1];
                $prec_scal = explode( ",", $size );
                if ( count( $prec_scal ) > 1 )
                {
                    $prec = $prec_scal[0];
                    $scal = rtrim( $prec_scal[1], ")" );
                    $size = null;
                }
            }
            // get the sqlType constant
            if ( $prec && $scal )
                $sqlType = call_user_func( $type, $prec, $scal );
            elseif ( $size )
                $sqlType = call_user_func( $type, $size );
            else
                $sqlType = constant( $type );
        }
        return array( $var, $direction, $phpType, $sqlType );
    }
}


/**
 * Insert a row into a table
 * @param object $conn : PDO connection object
 * @param string $tbname : name of the table for the row to be inserted
 * @param array $inputs : an associative array column name and its value
 * @param bool $r : true if the row was successfully inserted, otherwise false. Default value is null to make this parameter optional.
 * $param string $api : SQLSRV API used for executing the insert query
 *                      accepted values: "query", "queryParamsOp", "prepare", "prepareParamsOp"
 * @param array $paramOption : an array of bindParamOptions. Should only be provided if $api is "perpareWithParamOp", if provided, has to be in the same order as $inputs
 * @return object PDOStatement object of the insert statement
 */
function insert_row( $conn, $tbname, $inputs, &$r = null, $api = "query", $paramOption = array() )
{
    require  'MsSetup.inc';
     
    $stmt = null;
    if ( $keystore == "none" && $api != "queryParamsOp" && $api != "prepareParamsOp" )
    {
        $insertSql = get_insertSql_complete( $tbname, $inputs );
        switch ( $api ) {
            case "query":
                $stmt = sqlsrv_query( $conn, $insertSql );
                break;
            case "prepare":
                $stmt = sqlsrv_prepare( $conn, $insertSql );
                $r = sqlsrv_execute( $stmt );
                break;
        }
    }
    else
    {
        // if AE is on, must bind param
        $insertSql = get_insertSql_placeholders( $tbname, $inputs );
        $params = array();
        if ( empty( $paramOption ))
        {
            foreach ( $inputs as $key => $value )
            {
                array_push($params, $inputs[$key] );
            }
        }
        else
        {
            $i = 1;
            foreach( $inputs as $key => $value )
            {
                $nooption = true;
                foreach( $paramOption as $op )
                {
                    if ( $op->index == $i )
                    {
                        array_push( $params, $op->bindParamArr( $inputs[$key] ));
                        $nooption = false;
                        $i++;
                        break;
                    }
                }
                if ( $nooption )
                {
                    array_push( $params, $inputs[$key] );
                    $i++;
                }
            }
        }
        if ( $keystore != "none" || $api == "prepareParamsOp" )
        {
            $stmt = sqlsrv_prepare( $conn, $insertSql, $params );
            $r = sqlsrv_execute( $stmt );
        }
        else
            $stmt = sqlsrv_query( $conn, $insertSql, $params );
    }
    return $stmt;
}
            

/**
 * @param string $tbname : name of the table for an insert sql
 * @param array $input : associative array containing a key value pair of column name and data to put into an insert sql string
 * @return string a complete insert sql string
 */
function get_insertSql_complete( $tbname, $inputs )
{
    $colStr = "INSERT INTO $tbname (";
    $valStr = "VALUES (";
    if ( empty( $inputs ))
    {
        echo "get_insertSql_complete: inputs for inserting a row cannot be empty.\n";
        return;
    }
    foreach( $inputs as $key => $value )
    {
        $colStr .= $key . ", ";
        if ( is_null( $value ))
            echo "get_insertSql_complete: value provided for input $value is null.\n";
        elseif ( is_string( $value ))
            $valStr .= "'" . $value . "', ";
        else
            $valStr .= $value . ", ";
    }
    $colStr = rtrim( $colStr, ", " ) . ") ";
    $valStr = rtrim( $valStr, ", " ) . ") ";
    $insertSql = $colStr . $valStr;
    return $insertSql;
}


/**
 * @param string $tbname : name of the table for an insert sql
 * @param array $inputs : associative array containing a key value pair of column name and data to put into an insert sql string
 * @return string an insert sql string with "?" placeholders for all values
 */
function get_insertSql_placeholders( $tbname, $inputs )
{
    $colStr = "INSERT INTO $tbname (";
    $valStr = "VALUES (";
    if ( empty( $inputs ))
    {
        echo "get_insertSql_placeholders: inputs for inserting a row cannot be empty.\n";
        return;
    }
    foreach( $inputs as $key => $value )
    {
        $colStr .= $key . ", ";
    }
    $colStr = rtrim( $colStr, ", " ) . ") ";
    $valStr .= get_seq_placeholders( count( $inputs )) . ") ";
    $insertSql = $colStr . $valStr;
    return $insertSql;
}


/**
 * @param string $spname : name of the stored procedure
 * @param int $num : number of parameters needed for the stored procedure
 * @return string a call stored procedure sql string with "?" placeholders for all parameters
 */
function get_callProcSql_placeholders( $spname, $num )
{
    $callStr = "{CALL $spname (";
    $callStr .= get_seq_placeholders( $num ) . ")} ";
    return $callStr;
}


/**
 * @param int $num : number of placeholders needed
 * @return string a string containing $num number of repeated "?" placeholders delimited by ", "
 */
function get_seq_placeholders( $num )
{
    if ( $num < 0 )
    {
        echo "get_seq_placeholders: num provided for creating a sequence of placeholders cannot be less than 0.\n";
        return;
    }
    $placeholderStr = str_repeat( "?, ", $num );
    $placeholderStr = rtrim( $placeholderStr, ", " );
    return $placeholderStr;
}


/**
 * Fetch all rows and all columns given a table name, and print them
 * @param resource $conn : connection resource
 * @param string $tbname : name of the table to fetch from
 */
function fetch_all( $conn, $tbname )
{
    $sql = "SELECT * FROM $tbname";
    $stmt = sqlsrv_query( $conn, $sql );
    while ( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC ))
    {
        foreach ( $row as $key => $value )
        {
            if ( is_object( $value ) )
            {
                print "$key:\n";
                foreach ( $value as $k =>$v )
                {
                    print ( "  $k: $v\n" );
                }
            }
            else
                print ( "$key: $value\n" );
        }
    }
}


/**
 * @return bool false if $keystore specified in MsSetup.inc is none, otherwise return true
 */
function is_col_enc()
{
    require  'MsSetup.inc';
    if ( $keystore == "none" )
        return false;
    else
        return true;
}


function GetTempTableName($table = '', $temporary = true)
{
    // A temporary table name with the '#' prefix will be automatically 
    // dropped once the connection is closed. Otherwise, the caller 
    // should take care of dropping the temp table afterwards.
    
    $someNumber = rand(0, 1000);
    
    $prefix = '';
    if ($temporary)
        $prefix = '#';
        
    if (strlen($table) == 0)
        $table = 'php_test_table';
        
    return $prefix . $table . '_' . $someNumber;
}

function GetTempProcName($proc = '', $temporary = true)
{
    // A temporary stored procedure name with the '#' prefix will be 
    // automatically dropped once the connection is closed. Otherwise, 
    // the caller should take care of dropping the temp procedure afterwards.    
    
    $someNumber = rand(0, 1000);

    $prefix = '';
    if ($temporary)
        $prefix = '#';
        
    if (strlen($proc) == 0)
        $proc = 'php_test_proc'; 

    return $prefix . $proc . '_' . $someNumber;
}

function ExecuteQuery($conn, $query)
{
    $stmt = sqlsrv_query($conn, $query);
    if ($stmt === false)
    {
        FatalError("Query execution failed: $query");
    }
    return ($stmt);
}


function PrepareQuery($conn, $query)
{
    $stmt = sqlsrv_prepare($conn, $query);
    if ($stmt === false)
    {
        FatalError("Query preparation failed: $query");
    }
    return ($stmt);
}


function ExecuteQueryEx($conn, $query, $modeDirect)
{
    if ($modeDirect)
    {   // direct execution
        $stmt = sqlsrv_query($conn, $query);
    }
    else
    {
        $stmt = PrepareQuery($conn, $query);
        sqlsrv_execute($stmt);
    }
    return ($stmt);
}


function GetSqlType($k)
{
    switch ($k)
    {
        case 1:     return ("int");
        case 2:     return ("tinyint");
        case 3:     return ("smallint");
        case 4:     return ("bigint");
        case 5:     return ("bit");
        case 6:     return ("float");
        case 7:     return ("real");
        case 8:     return ("decimal(28,4)");
        case 9:     return ("numeric(32,4)");
        case 10:    return ("money");
        case 11:    return ("smallmoney");
        case 12:    return ("char(512)");
        case 13:    return ("varchar(512)");
        case 14:    return ("varchar(max)");
        case 15:    return ("nchar(512)");
        case 16:    return ("nvarchar(512)");
        case 17:    return ("nvarchar(max)");
        case 18:    return ("text");
        case 19:    return ("ntext");
        case 20:    return ("binary(512)");
        case 21:    return ("varbinary(512)");
        case 22:    return ("varbinary(max)");
        case 23:    return ("image");
        case 24:    return ("uniqueidentifier");
        case 25:    return ("datetime");
        case 26:    return ("smalldatetime");
        case 27:    return ("timestamp");
        case 28:    return ("xml");
        default:    break;
    }
    return ("udt");
}

function GetDriverType($k, $dataSize)
{
    switch ($k)
    {
        case 1:     return (SQLSRV_SQLTYPE_INT);
        case 2:     return (SQLSRV_SQLTYPE_TINYINT);
        case 3:     return (SQLSRV_SQLTYPE_SMALLINT);
        case 4:     return (SQLSRV_SQLTYPE_BIGINT);
        case 5:     return (SQLSRV_SQLTYPE_BIT);
        case 6:     return (SQLSRV_SQLTYPE_FLOAT);
        case 7:     return (SQLSRV_SQLTYPE_REAL);
        case 8:     return (SQLSRV_SQLTYPE_DECIMAL(28, 4));
        case 9:     return (SQLSRV_SQLTYPE_NUMERIC(32, 4));
        case 10:    return (SQLSRV_SQLTYPE_MONEY);
        case 11:    return (SQLSRV_SQLTYPE_SMALLMONEY);
        case 12:    return (SQLSRV_SQLTYPE_CHAR($dataSize));
        case 13:    return (SQLSRV_SQLTYPE_VARCHAR($dataSize));
        case 14:    return (SQLSRV_SQLTYPE_VARCHAR('max'));
        case 15:    return (SQLSRV_SQLTYPE_NCHAR($dataSize));
        case 16:    return (SQLSRV_SQLTYPE_NVARCHAR($dataSize));
        case 17:    return (SQLSRV_SQLTYPE_NVARCHAR('max'));
        case 18:    return (SQLSRV_SQLTYPE_TEXT);
        case 19:    return (SQLSRV_SQLTYPE_NTEXT);
        case 20:    return (SQLSRV_SQLTYPE_BINARY($dataSize));
        case 21:    return (SQLSRV_SQLTYPE_VARBINARY($dataSize));
        case 22:    return (SQLSRV_SQLTYPE_VARBINARY('max'));
        case 23:    return (SQLSRV_SQLTYPE_IMAGE);
        case 24:    return (SQLSRV_SQLTYPE_UNIQUEIDENTIFIER);
        case 25:    return (SQLSRV_SQLTYPE_DATETIME);
        case 26:    return (SQLSRV_SQLTYPE_SMALLDATETIME);
        case 27:    return (SQLSRV_SQLTYPE_TIMESTAMP);
        case 28:    return (SQLSRV_SQLTYPE_XML);
        default:    break;
    }
    return (SQLSRV_SQLTYPE_UDT);
}

function IsXml($k)
{
    switch ($k)
    {
        case 28:    return (true);  // xml
        default:    break;
    }
    return (false);
}

function IsStreamable($k)
{
    switch ($k)
    {
        case 12:    return (true);  // nchar(512)
        case 13:    return (true);  // varchar(512) 
        case 14:    return (true);  // varchar(max) 
        case 15:    return (true);  // nchar(512) 
        case 16:    return (true);  // nvarchar(512) 
        case 17:    return (true);  // nvarchar(max) 
        case 18:    return (true);  // text
        case 19:    return (true);  // ntext
        case 20:    return (true);  // binary
        case 21:    return (true);  // varbinary(512)
        case 22:    return (true);  // varbinary(max)
        case 23:    return (true);  // image
        case 28:    return (true);  // xml
        default:    break;
    }
    return (false);
}

function IsNumeric($k)
{
    switch ($k)
    {
        case 1:     return (true);  // int
        case 2:     return (true);  // tinyint
        case 3:     return (true);  // smallint
        case 4:     return (true);  // bigint
        case 5:     return (true);  // bit
        case 6:     return (true);  // float
        case 7:     return (true);  // real
        case 8:     return (true);  // decimal(28,4)
        case 9:     return (true);  // numeric(32,4)
        case 10:    return (true);  // money
        case 11:    return (true);  // smallmoney
        default:    break;
    }
    return (false);
}

function IsChar($k)
{
    switch ($k)
    {
        case 12:    return (true);  // nchar(512)
        case 13:    return (true);  // varchar(512) 
        case 14:    return (true);  // varchar(max) 
        case 15:    return (true);  // nchar(512) 
        case 16:    return (true);  // nvarchar(512) 
        case 17:    return (true);  // nvarchar(max) 
        case 18:    return (true);  // text
        case 19:    return (true);  // ntext
        case 28:    return (true);  // xml
        default:    break;
    }
    return (false);
}

function IsBinary($k)
{
    switch ($k)
    {
        case 20:    return (true);  // binary
        case 21:    return (true);  // varbinary(512)
        case 22:    return (true);  // varbinary(max)
        case 23:    return (true);  // image
        default:    break;
    }
    return (false);
}

function IsDateTime($k)
{
    switch ($k)
    {
        case 25:    return (true);  // datetime
        case 26:    return (true);  // smalldatetime
        case 27:    return (true);  // timestamp
        default:    break;
    }
    return (false);
}

function IsUnicode($k)
{
    switch ($k)
    {
        case 15:    return (true);  // nchar(512) 
        case 16:    return (true);  // nvarchar(512) 
        case 17:    return (true);  // nvarchar(max) 
        case 19:    return (true);  // ntext
        default:    break;
    }
    return (false);
}

function IsUpdatable($k)
{
    switch ($k)
    {
        case 27:    return (false); // timestamp
        default:    break;
    }
    return (true);
}

function IsLiteral($k)
{
    switch ($k)
    {
        case 12:    return (true);  // nchar(512)
        case 13:    return (true);  // varchar(512) 
        case 14:    return (true);  // varchar(max) 
        case 15:    return (true);  // nchar(512) 
        case 16:    return (true);  // nvarchar(512) 
        case 17:    return (true);  // nvarchar(max) 
        case 18:    return (true);  // text
        case 19:    return (true);  // ntext
        case 24:    return (true);  // uniqueidentifier
        case 25:    return (true);  // datetime
        case 26:    return (true);  // smalldatetime
        case 28:    return (true);  // xml
        default:    break;
    }
    return (false);
}

function GetMetadata($k, $info)
{
    if (strcasecmp($info, 'Name') == 0)
    {
        return (getColName($k));
    }
    if (strcasecmp($info, 'Size') == 0)
    {
        return (getColSize($k));
    }
    if (strcasecmp($info, 'Precision') == 0)
    {
        return (getColPrecision($k));
    }
    if (strcasecmp($info, 'Scale') == 0)
    {
        return (getColScale($k));
    }
    if (strcasecmp($info, 'Nullable') == 0)
    {
        return (getColNullable($k));
    }
    return ("");
}

function GetColName($k)
{
    switch ($k)
    {
        case 1:     return ("c1_int");
        case 2:     return ("c2_tinyint");
        case 3:     return ("c3_smallint");
        case 4:     return ("c4_bigint");
        case 5:     return ("c5_bit");
        case 6:     return ("c6_float");
        case 7:     return ("c7_real");
        case 8:     return ("c8_decimal");
        case 9:     return ("c9_numeric");
        case 10:    return ("c10_money");
        case 11:    return ("c11_smallmoney");
        case 12:    return ("c12_char");
        case 13:    return ("c13_varchar");
        case 14:    return ("c14_varchar_max");
        case 15:    return ("c15_nchar");
        case 16:    return ("c16_nvarchar");
        case 17:    return ("c17_nvarchar_max");
        case 18:    return ("c18_text");
        case 19:    return ("c19_ntext");
        case 20:    return ("c20_binary");
        case 21:    return ("c21_varbinary");
        case 22:    return ("c22_varbinary_max");
        case 23:    return ("c23_image");
        case 24:    return ("c24_uniqueidentifier");
        case 25:    return ("c25_datetime");
        case 26:    return ("c26_smalldatetime");
        case 27:    return ("c27_timestamp");
        case 28:    return ("c28_xml");
        default:    break;
    }
    return ("");
}


function GetColSize($k)
{
    switch ($k)
    {
        case 12:    return ("512");
        case 13:    return ("512");
        case 14:    return ("0");
        case 15:    return ("512");
        case 16:    return ("512");
        case 17:    return ("0");
        case 18:    return ("2147483647");
        case 19:    return ("1073741823");
        case 20:    return ("512");
        case 21:    return ("512");
        case 22:    return ("0)");
        case 23:    return ("2147483647");
        case 24:    return ("36");
        //case 25:  return ("23");
        //case 26:  return ("16");
        case 27:    return ("8");
        case 28:    return ("0");
        default:    break;
    }
    return ("");
}

function GetColPrecision($k)
{
    switch ($k)
    {
        case 1:     return ("10");
        case 2:     return ("3");
        case 3:     return ("5");
        case 4:     return ("19");
        case 5:     return ("1");
        case 6:     return ("53");
        case 7:     return ("24");
        case 8:     return ("28");
        case 9:     return ("32");
        case 10:    return ("19");
        case 11:    return ("10");
        case 25:    return ("23");
        case 26:    return ("16");
        default:    break;
    }
    return ("");
}

function GetColScale($k)
{
    switch ($k)
    {
        case 8:     return ("4");
        case 9:     return ("4");
        case 10:    return ("4");
        case 11:    return ("4");
        case 25:    return ("3");
        case 26:    return ("0");
        default:    break;
    }
    return ("");
}

function GetColNullable($k)
{
    return (IsUpdatable($k) ? "1" : "0");
}

function GetSampleData($k)
{
    switch ($k)
    {
        case 1: // int
            return ("123456789");

        case 2: // tinyint
            return ("234");

        case 3: // smallint
            return ("5678");
    
        case 4: // bigint
            return ("123456789987654321");


        case 5: // bit
            return ("1");

        case 6: // float
            return ("123.456");

        case 7: // real
            return ("789.012");

        case 8: // decimal
            return ("12.34");

        case 9: // numeric
            return ("567.89");

        case 10:// money
            return ("321.54");

        case 11:// smallmoney
            return ("67.89");

        case 12:// char
        case 15:// nchar
            return ("The quick brown fox jumps over the lazy dog");

        case 13:// varchar
        case 16:// nvarchar
            return ("The quick brown fox jumps over the lazy dog 9876543210");

        case 14:// varchar(max)
        case 17:// nvarchar(max)
            return ("The quick brown fox jumps over the lazy dog 0123456789");

        case 18:// text
        case 19:// ntext
            return ("0123456789 The quick brown fox jumps over the lazy dog");

        case 20:// binary
            return ("0123456789");

        case 21:// varbinary
            return ("01234567899876543210");

        case 22:// varbinary(max)
            return ("98765432100123456789");

        case 23:// image
            return ("01234567899876543210");

        case 24:// uniqueidentifier
            return ("12345678-9012-3456-7890-123456789012");

        case 25:// datetime
        case 26:// smalldatetime
            return (date("Y-m-d"));

        case 27:// timestamp
            return (null);

        case 28:// xml
            return ("<XmlTestData><Letters1>The quick brown fox jumps over the lazy dog</Letters1><Digits1>0123456789</Digits1></XmlTestData>");

        default:
            break;
    }

    return (null);
}


function CreateTable($conn, $tableName)
{
    Trace("Creating table $tableName ...");

    $dataType = "[c1_int] int, [c2_tinyint] tinyint, [c3_smallint] smallint, [c4_bigint] bigint, [c5_bit] bit, [c6_float] float, [c7_real] real, [c8_decimal] decimal(28,4), [c9_numeric] numeric(32,4), [c10_money] money, [c11_smallmoney] smallmoney, [c12_char] char(512), [c13_varchar] varchar(512), [c14_varchar_max] varchar(max), [c15_nchar] nchar(512), [c16_nvarchar] nvarchar(512), [c17_nvarchar_max] nvarchar(max), [c18_text] text, [c19_ntext] ntext, [c20_binary] binary(512), [c21_varbinary] varbinary(512), [c22_varbinary_max] varbinary(max), [c23_image] image, [c24_uniqueidentifier] uniqueidentifier, [c25_datetime] datetime, [c26_smalldatetime] smalldatetime, [c27_timestamp] timestamp, [c28_xml] xml";
    CreateTableEx($conn, $tableName, $dataType);

    Trace(" completed successfully.\n");
}

function CreateTableEx($conn, $tableName, $dataType)
{
    $sql = "CREATE TABLE [$tableName] ($dataType)";
    DropTable($conn,$tableName);
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false)
    {
        FatalError("Failed to create test table: ".$sql);
    }
    sqlsrv_free_stmt($stmt);
}


function CreateTableIndex($conn, $tableName, $colIndex)
{
    include 'MsSetup.inc';
    CreateTableIndexEx($conn, $tableName, $tableIndex, $colIndex);
}


function CreateTableIndexEx($conn, $tableName, $tableIndex, $colIndex)
{
    Trace("Creating table index for $tableName ...");
    $sqlIndex = "CREATE CLUSTERED INDEX [$tableIndex] ON [$tableName]($colIndex)";
    $stmt = sqlsrv_query($conn, $sqlIndex);
    if ($stmt === false)
    {
        FatalError("Failed to create clustered index for test table: ".$sqlIndex);
    }
    sqlsrv_free_stmt($stmt);
    Trace(" completed successfully.\n");
}

function CreateUniqueIndex($conn, $tableName, $colIndex)
{
    include 'MsSetup.inc';
    CreateUniqueIndexEx($conn, $tableName, $tableIndex, $colIndex);
}

function CreateUniqueIndexEx($conn, $tableName, $tableIndex, $colIndex)
{
    Trace("Creating unique table index for $tableName ...");
    $sqlIndex = "CREATE UNIQUE INDEX [$tableIndex] ON [$tableName]($colIndex)";
    $stmt = sqlsrv_query($conn, $sqlIndex);
    if ($stmt === false)
    {
        FatalError("Failed to create unique index for test table: ".$sqlIndex);
    }
    sqlsrv_free_stmt($stmt);
    Trace(" completed successfully.\n");
}

function DropTable($conn, $tableName)
{
    $stmt = sqlsrv_query($conn, "IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'" . $tableName . "') AND type in (N'U')) DROP TABLE $tableName");
    if ($stmt === false)
    {
    }
    else
    {
        sqlsrv_free_stmt($stmt);
    }
}


function SelectFromTable($conn, $tableName)
{
    return (SelectFromTableEx($conn, $tableName, null));
}

function SelectFromTableEx($conn, $tableName, $cond)
{
    if (($cond != null) && (strlen($cond) > 0))
    {
        return (SelectQuery($conn, "SELECT * FROM [$tableName] WHERE $cond"));
    }
    else
    {
        return (SelectQuery($conn, "SELECT * FROM [$tableName]"));
    }
}

function SelectQuery($conn, $query)
{
    return (SelectQueryEx($conn, $query, null));
}

function SelectQueryEx($conn, $query, $options)
{
    if ($options != null)
    {
        $stmt = sqlsrv_query($conn, $query, null, $options);
    }
    else
    {
        $stmt = sqlsrv_query($conn, $query);
    }
    if ($stmt === false)
    {
        FatalError("Failed to query test table");
    }   

    $numFields = sqlsrv_num_fields($stmt);
    if ($numFields <= 0)
    {
        die("Unexpected number of fields: ".$numFields);
    }
    return ($stmt);
}

function RowCount($stmt)
{
    $rowCount = 0;
    while (sqlsrv_fetch($stmt))
    {
        $rowCount++;
    }
    return ($rowCount);
}


function NumRows($conn, $tableName)
{
    $stmt = SelectFromTable($conn, $tableName);
    $rowCount = RowCount($stmt);
    sqlsrv_free_stmt($stmt);

    return ($rowCount);
}

function InsertQueryData($tableName, $index)
{
    if (UseUTF8data())
    {
        include_once 'MsData_UTF8.inc';
        return (InsertQueryExUTF8($tableName, $index));
    }
    else 
    {
        include_once 'MsData.inc';
        return (InsertQueryEx($tableName, $index));
    }
}

function InsertQuery($tableName)
{
    return (InsertQueryData($tableName, rand(1, 20)));
}

function InsertRows($conn, $tableName, $rowCount)
{
    Trace("Inserting $rowCount rows into $tableName ...");
    $count = 0;
    for($i = 0; $i < $rowCount; $i++)
    {
        if (InsertRow($conn, $tableName))
        {
            $count++;
        }
    }
    Trace(" completed successfully.\n");
    if ($count != $rowCount)
    {
        die("$count rows inserted instead of $rowCount\n"); 
    }
    return ($count);
}

function InsertRowsByRange($conn, $tableName, $minIndex, $maxIndex)
{
    $rowCount = $maxIndex - $minIndex + 1;
    if ($rowCount > 0)
    {
        Trace("Inserting $rowCount rows into $tableName ...");
        for($i = $minIndex; $i <= $maxIndex; $i++)
        {
            InsertRowByIndex($conn, $tableName, $i);
        }
        Trace(" completed successfully.\n");
    } 
}

function InsertRow($conn, $tableName)
{
    $query = InsertQuery($tableName);
    $stmt = sqlsrv_query($conn, $query);
    return (InsertCheck($stmt));
}

function InsertRowEx($conn, $tableName, $dataCols, $dataValues, $dataOptions)
{
    $stmt = sqlsrv_query($conn, "INSERT INTO [$tableName] ($dataCols) VALUES ($dataValues)", $dataOptions);
    return (InsertCheck($stmt));
}

function InsertRowByIndex($conn, $tableName, $index)
{
    $query = InsertQueryData($tableName, $index);
    $stmt = sqlsrv_query($conn, $query);
    return (InsertCheck($stmt));
}

function InsertStream($conn, $tableName, $dataCols, $dataValues, $dataOptions, $atExec)
{
    if ($atExec)
    {
        $stmt = sqlsrv_query($conn, "INSERT INTO [$tableName] ($dataCols) VALUES ($dataValues)", $dataOptions, array('SendStreamParamsAtExec' => 1));
    }
    else
    {
        $stmt = sqlsrv_query($conn, "INSERT INTO [$tableName] ($dataCols) VALUES ($dataValues)", $dataOptions);
        if ($stmt)
        {
            while (sqlsrv_send_stream_data($stmt))
            {
            }
        }
    }
    return (InsertCheck($stmt));
}


function InsertCheck($stmt)
{
    if ($stmt === false)
    {
        FatalError("Failed to insert row into test table");
    }
    $numRows = sqlsrv_rows_affected($stmt);
    sqlsrv_free_stmt($stmt);
    if ($numRows != 1)
    {
        die("Unexpected row count at insert: ".$numRows);
    }
    return (true);
}

function GetInsertData($rowIndex, $colIndex, $skip)
{
    $query = InsertQueryData("TestTable", $rowIndex);
    $data = strstr($query, "((");
    $pos = 1;
    if ($data === false)
    {
        die("Failed to retrieve data on row $rowIndex");
    }
    $data = substr($data, 2);

    while ($pos < ($colIndex - $skip))
    {
        $data = strstr($data, ", (");
        $pos++;
        if ($data === false)
        {
            die("Failed to retrieve data on row $rowIndex, column $pos");
        }
        $data = substr($data, 3);
    }

    // Is it's XML type, we can't use the closing bracket as the next delimiter
    // because a bracket can be part of the xml data, unless the data is null
    $pos = strpos($data, ")");
    if ($pos === false)
    {
        die("Failed to isolate data on row $rowIndex, column $pos");
    }
    $tmp = substr($data, 0, $pos);      // don't replace $data in case it's xml data
    if (strcasecmp($tmp, "null") == 0 || strlen($tmp) == 0) // data can actually be blank for null
    {
        $tmp = "";
    }
    else if (IsXml($colIndex))
    {
        $str = ">')";   // use the XML closing angle bracket as the delimiter
        $pos = strpos($data, $str);
        $tmp = substr($data, 0, $pos + 2);  
    }
    $data = $tmp;   // update $data
    if (IsUnicode($colIndex))
    {   // N'data'
        $data = substr($data, 2, strlen($data) - 3);
    }
    else if (IsLiteral($colIndex))
    {   // 'data'
        $data = substr($data, 1, strlen($data) - 2);
    }
    else if (IsBinary($colIndex))
    {   // 0xdata
        $data = substr($data, 2);
    }
    return (trim($data));
}

function CreateProc($conn, $procName, $procArgs, $procCode)
{
    DropProc($conn,$procName);
    $stmt = sqlsrv_query($conn, "CREATE PROC [$procName] ($procArgs) AS BEGIN $procCode END");
    if ($stmt === false)
    {
        FatalError("Failed to create test procedure");
    }
    sqlsrv_free_stmt($stmt);
}

function DropProc($conn, $procName)
{
    $stmt = sqlsrv_query($conn, "DROP PROC [$procName]");
    if ($stmt === false)
    {
    }
    else
    {
        sqlsrv_free_stmt($stmt);
    }
}

function CallProc($conn, $procName, $procArgs, $procValues)
{
    $stmt = CallProcEx($conn, $procName, "", $procArgs, $procValues);
    sqlsrv_free_stmt($stmt);
}

function CallProcEx($conn, $procName, $procPrefix, $procArgs, $procValues)
{
    $stmt = sqlsrv_query($conn, "{ $procPrefix CALL [$procName] ($procArgs)}", $procValues);
    if ($stmt === false)
    {
        FatalError("Failed to call test procedure");
    }
    return ($stmt);
}


function CreateFunc($conn, $funcName, $funcArgs, $retType, $funcCode)
{
    DropFunc($conn,$funcName);
    $stmt = sqlsrv_query($conn, "CREATE FUNCTION [$funcName] ($funcArgs) RETURNS $retType AS BEGIN $funcCode END");
    if ($stmt === false)
    {
        FatalError("Failed to create test function");
    }
    sqlsrv_free_stmt($stmt);
}

function DropFunc($conn, $funcName)
{
    $stmt = sqlsrv_query($conn, "DROP FUNCTION [$funcName]");
    if ($stmt === false)
    {
    }
    else
    {
        sqlsrv_free_stmt($stmt);
    }
}


function CallFunc($conn, $funcName, $funcArgs, $funcValues)
{
    $stmt = sqlsrv_query($conn, "{ ? = CALL [$funcName]($funcArgs)}", $funcValues);
    if ($stmt === false)
    {
        FatalError("Failed to call test function");
    }
    sqlsrv_free_stmt($stmt);
}

function FatalError($errorMsg)
{
    SetUTF8Data(false);
    handle_errors();
    die($errorMsg."\n");
}

function PrintErrors($message = "")
{
    if (strlen($message) > 0)
    {
        echo $message . "\n";
    }

    $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
    if(count($errors) == 0)
    {
        $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
    }
    $count = count($errors);
    for($i = 0; $i < $count; $i++)
    {
        echo $errors[$i]['message'] . "\n";
    }
}

function handle_errors()
{
    $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
    $count = count($errors);
    if($count == 0)
    {
        $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
        $count = count($errors);
    }
    if($count > 0)
    {
        for($i = 0; $i < $count; $i++)
        {
            trace ($errors[$i]['message']."\n");
        }
    }
}

?>

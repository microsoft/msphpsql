<?php
/*
    Microsoft SQL Server Driver for PHP - Unit Test Framework
    Copyright (c) Microsoft Corporation.  All rights reserved.

    Description:
        Helper classes and functions with Always Encrypted features incorporated.

*/

namespace AE {

require_once('MsSetup.inc');

const KEYSTORE_NONE = 'none';
const KEYSTORE_WIN = 'win';
const KEYSTORE_KSP = 'ksp';
const KEYSTORE_AKV = 'akv';

const INSERT_QUERY = 1;
const INSERT_PREPARE = 2;
const INSERT_QUERY_PARAMS = 3;
const INSERT_PREPARE_PARAMS = 4;

/**
 * class for encapsulating column metadata needed for creating a table
 */
class ColumnMeta
{
    public      $dataType;      // a string that includes the size of the type if necessary (e.g., decimal(10,5))
    public      $colName;       // column name
    public      $encType;       // randomized or deterministic; default is deterministic
    public      $options;       // a string that is null by default (e.g. NOT NULL Identity (1,1) )

    protected   $encryptable;   // whether Always Encrypted supports this column

    public function __construct($dataType, $colName = null, $options = null, $deterministic = true, $noEncrypt = false)
    {
        if (is_null($colName)) {
            $this->colName = getDefaultColname($dataType);
        } else {
            $this->colName = $colName;
        }

        $this->encType = ($deterministic ? "deterministic" : "randomized");
        if (empty($dataType)) {
            echo "Data type can not be null or empty!\n";
        }
        $this->dataType = $dataType;
        $this->options = $options;

        if (!$noEncrypt) {
            $this->checkIfUnsupported();
        } else {
            $this->encryptable = false;
        }
    }

    /**
     * if Always Encrypted not supported for this data type set $noEncryption to false
     * @return void
     */
    protected function checkIfUnsupported()
    {
        // Always Encrypted is not supported for columns with the IDENTITY property
        // or any column using one of the following datatypes:
        //
        // xml, timestamp, image, ntext, text, sql_variant, hierarchyid, geography, geometry, alias,
        // user defined-types.
        // https://docs.microsoft.com/en-us/sql/relational-databases/security/encryption/always-encrypted-database-engine

        $unsupported = array("xml", "timestamp", "image", "ntext", "text", "sql_variant", "hierarchyid", "geography", "geometry", "alias");

        if (stripos($this->options, "identity") !== false) {
            $this->encryptable = false;
        } elseif (in_array(strtolower($this->dataType), $unsupported)) {
            $this->encryptable = false;
        } else {
            $this->encryptable = true;
        }
    }
    /**
     * @return string column definition for creating a table
     */
    public function getColDef()
    {
        $append = " ";

        if ($this->encryptable && isDataEncrypted()) {

            $cekName = getCekName();
            if (stripos($this->dataType, "char") !== false) {
                $append .= "COLLATE Latin1_General_BIN2 ";
            }
            $append .= sprintf("ENCRYPTED WITH (ENCRYPTION_TYPE = %s, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256', COLUMN_ENCRYPTION_KEY = $cekName) ", $this->encType);
        }
        $append .= $this->options;
        $colDef = "[" . $this->colName . "] " . $this->dataType . $append;
        return $colDef;
    }
}

/**
 * class for encapsulating optional parameters for binding parameters in sqlsrv_prepare
 */
class BindParamOption
{
    public $value;      // the param value
    public $direction;  // SQLSRV_PARAM_ constant indicating the parameter direction
    public $phpType;    // SQLSRV_PHPTYPE_ constant specifying the php type of the return values
    public $sqlType;    // SQLSRV_SQLTYPE_ constant specifying the SQL type of the input

    public function __construct($value, $direction = null, $phpType = null, $sqlType = null)
    {
        $this->value = $value;
        $this->direction = $direction;
        $this->phpType = $phpType;
        $this->sqlType = $sqlType;
    }

    /**
     * @return array needed to bind parameter in sqlsrv_prepare
     */
    public function bindParamArr()
    {
        // get the constant values of direction, phpType, and sqlType
        $direction = null;
        $phpType = null;
        $sqlType = null;
        if ($this->direction) {
            if (in_array($this->direction, array(SQLSRV_PARAM_IN, SQLSRV_PARAM_OUT, SQLSRV_PARAM_INOUT))) {
                $direction = constant($this->direction);
            } else {
                echo "BindParamOption: invalid direction for parameter!\n";
            }
        }
        if ($this->phpType) {
            try {
                $arr = explode("(", $this->phpType);
                $type = $arr[0];
                if (count($arr) > 1) {
                    $enc = rtrim($arr[1], ")");
                    $phpType = call_user_func($type, constant($enc));
                } else {
                    $phpType = constant($this->phpType);
                }
            } catch (Exception $e) {
                // there's something wrong with the input php type
                echo $e->getMessage();
            }
        }
        if ($this->sqlType) {
            // parse the datatype name, size, precision, and/or scale from a SQLSRV_SQLTYPE_ constant
            $size = null;
            $prec = null;
            $scal = null;
            $type_size = explode("(", $this->sqlType);
            $type = $type_size[0];
            if (count($type_size) > 1) {
                $size = rtrim($type_size[1], ")");
                $prec_scal = explode(",", $size);
                if (count($prec_scal) > 1) {
                    $prec = $prec_scal[0];
                    $scal = $prec_scal[1];
                    $size = null;
                }
                if (strpos($size, "max") !== false) {
                    $size = trim($size, "'");
                }
            }
            // get the sqlType constant
            try {
                if ($prec && $scal) {
                    $sqlType = call_user_func($type, $prec, $scal);
                } elseif ($size) {
                    $sqlType = call_user_func($type, $size);
                } else {
                    $sqlType = constant($type);
                }
            } catch (Exception $e) {
                // there's something wrong with the input SQL type
                echo $e->getMessage();
            }
        }
        return array($this->value, $direction, $phpType, $sqlType);
    }
}

/**
 * @return string CEK name depending on the connection keywords
 */
function getCekName()
{
    global $keystore;

    $cekName = '';
    switch ($keystore) {
        case KEYSTORE_NONE:
            $cekName = '';
            break;
        case KEYSTORE_WIN:
            $cekName = 'AEColumnKey';
            break;
        case KEYSTORE_KSP:
            $cekName = 'CustomCEK';
            break;
        case KEYSTORE_AKV:
            $cekName = 'AKVColumnKey';
            break;
        default:
            echo "getCekName: Invalid keystore name.\n";
    }
    return $cekName;
}

/**
 * @return string default column name when a name is not provided in the ColumnMeta class
 */
function getDefaultColname($dataType)
{
    $colName = "c_" . str_replace(",", "_", str_replace("(", "_", $dataType));
    $colName = rtrim($colName, ")");
    return $colName;
}

/**
 * @param  string  $tbname : name of the table for an insert sql
 * @param  array  $input : associative array containing a key value pair of column name and data to put into an insert sql string
 * @return string a complete insert sql string
 */
function getInsertSqlComplete($tbname, $inputs)
{
    $colStr = "INSERT INTO [$tbname] (";
    $valStr = "VALUES (";
    if (empty($inputs)) {
        echo "getInsertSqlComplete: inputs for inserting a row cannot be empty.\n";
        return;
    }
    foreach ($inputs as $key => $value) {
        $colStr .= $key . ", ";
        if (is_null($value)) {
            $valStr .= "null, ";
        } elseif (is_string($value)) {
            $valStr .= "'" . $value . "', ";
        } else {
            $valStr .= $value . ", ";
        }
    }
    $colStr = rtrim($colStr, ", ") . ") ";
    $valStr = rtrim($valStr, ", ") . ") ";
    $insertSql = $colStr . $valStr;
    return $insertSql;
}

/**
 * @param  string  $tbname : name of the table for an insert sql
 * @param  array  $inputs : associative array containing a key value pair of column name and data to put into an insert sql string
 * @return string an insert sql string with "?" placeholders for all values
 */
function getInsertSqlPlaceholders($tbname, $inputs)
{
    $colStr = "INSERT INTO [$tbname] (";
    $valStr = "VALUES (";
    if (empty($inputs)) {
        echo "getInsertSqlPlaceholders: inputs for inserting a row cannot be empty.\n";
        return;
    }
    foreach ($inputs as $key => $value) {
        $colStr .= $key . ", ";
    }
    $colStr = rtrim($colStr, ", ") . ") ";
    $valStr .= getSeqPlaceholders(count($inputs)) . ") ";
    $insertSql = $colStr . $valStr;
    return $insertSql;
}

/**
 * @param  string  $spname : name of the stored procedure
 * @param  int  $num : number of parameters needed for the stored procedure
 * @return string a call stored procedure sql string with "?" placeholders for all parameters
 */
function getCallProcSqlPlaceholders($spname, $num)
{
    $callStr = "{CALL [$spname] (";
    $callStr .= getSeqPlaceholders($num) . ")} ";
    return $callStr;
}

/**
 * @param  int  $num : number of placeholders needed
 * @return string a string containing $num number of repeated "?" placeholders delimited by ", "
 */
function getSeqPlaceholders($num)
{
    if ($num <= 0) {
        echo "getSeqPlaceholders: num provided for creating a sequence of placeholders cannot be less than 0.\n";
        return;
    }
    $placeholderStr = str_repeat("?, ", $num);
    $placeholderStr = rtrim($placeholderStr, ", ");
    return $placeholderStr;
}

/**
 * @return bool false if $keystore specified in MsSetup.inc is none; return true otherwise
 */
function isColEncrypted()
{
    global $keystore;
    return ($keystore !== KEYSTORE_NONE);
}

/**
 * @return bool false if $keystore specified in MsSetup.inc is none or data not encrypted;
 *              return true otherwise 
 */
function isDataEncrypted()
{
    global $keystore, $dataEncrypted;
    return ($keystore !== KEYSTORE_NONE && $dataEncrypted);
}

/**
 * @return bool false if ODBC version is less than 17 or SQL Server older than 2016
 */
function isQualified($conn)
{
    $msodbcsql_ver = sqlsrv_client_info($conn)['DriverVer'];
    if (explode(".", $msodbcsql_ver)[0] < 17) {
        return false;
    }
    global $daasMode;
    if ($daasMode) {
        // running against Azure
        return true;
    }
    // if not Azure, check the server version
    $server_ver = sqlsrv_server_info($conn)['SQLServerVersion'];
    if (explode('.', $server_ver)[0] < 13) {
        return false;
    }
    return true;
}

/**
 * Connect to the database specified in MsSetup.inc; Column Encryption options automatically added when $keystore is not none
 * @param  array  $options : connection attributes to pass to sqlsrv_connect
 * @param  bool  $disableCE : flag for disabling column encryption even when keystore is NOT none
 *                          for testing fetching encrypted data when connection column encryption is off
 * @return connection resource
 */
function connect($options = array(), $disableCE = false)
{
    require('MsSetup.inc');
    if (!empty($options)) {
        $connectionOptions = array_merge($connectionOptions, $options);
    }
    if (!$disableCE) {
        if (isColEncrypted()) {
            $connectionOptions = array_merge($connectionOptions, array("ColumnEncryption" => "Enabled"));
        }
        if ($keystore == 'akv') {
            $akv_options = array("KeyStoreAuthentication"=>$AKVKeyStoreAuthentication);
            if ($AKVKeyStoreAuthentication == "KeyVaultPassword") {
                $akv_options["KeyStorePrincipalId"] = $AKVPrincipalName;
                $akv_options["KeyStoreSecret"] = $AKVPassword;
            } else if ($AKVKeyStoreAuthentication == "KeyVaultClientSecret") {
                $akv_options["KeyStorePrincipalId"] = $AKVClientID;
                $akv_options["KeyStoreSecret"] = $AKVSecret;
            }
            $connectionOptions = array_merge($connectionOptions, $akv_options);
        }
    }
    $conn = sqlsrv_connect($server, $connectionOptions);
    if ($conn === false) {
        fatalError("Failed to connect to $server.");
    }
    return $conn;
}

/**
 * Create a table
 * @param  resource  $conn : sqlsrv connection resource
 * @param  string  $tbname : name of the table to be created
 * @param  array  $columnMetaArr : array of ColumnMeta objects, which contain metadata for one column
 * @return resource sqlsrv statement resource
 */
function createTable($conn, $tbname, $columnMetaArr)
{
    require_once("MsCommon.inc");
    dropTable($conn, $tbname);
    $colDef = "";
    foreach ($columnMetaArr as $meta) {
        $colDef = $colDef . $meta->getColDef() . ", ";
    }
    $colDef = rtrim($colDef, ", ");
    $createSql = "CREATE TABLE [$tbname] ( $colDef )";
    return sqlsrv_query($conn, $createSql);
}

/**
 * Insert a row into a table
 * @param  resource  $conn : sqlsrv connection resource
 * @param  string  $tbname : name of the table for the row to be inserted
 * @param  array  $inputs : an associative array column name and its value, which may be a
 *                          literal or a BindParamOption object
 * @param  bool  $r : true if the row was successfully inserted, otherwise false. Default value is null to make this parameter optional.
 * $param  int  $api : SQLSRV API used for executing the insert query
 *                        accepted values: INSERT_QUERY, INSERT_PREPARE, INSERT_QUERY_PARAMS, INSERT_PREPARE_PARAMS
 * @return resource sqlsrv statement resource of the insert statement
 */
function insertRow($conn, $tbname, $inputs, &$r = null, $api = INSERT_QUERY)
{
    $stmt = null;
    if (!isColEncrypted() && $api < INSERT_QUERY_PARAMS) {
        $insertSql = getInsertSqlComplete($tbname, $inputs);
        switch ($api) {
            case INSERT_QUERY:
                $stmt = sqlsrv_query($conn, $insertSql);
                break;
            case INSERT_PREPARE:
                $stmt = sqlsrv_prepare($conn, $insertSql);
                if ($stmt) {
                    $r = sqlsrv_execute($stmt);
                }
                break;
        }
    } else {
        // must bind param
        $insertSql = getInsertSqlPlaceholders($tbname, $inputs);
        $params = array();
        foreach ($inputs as $key => $input) {
            if (is_object($input)) {
                array_push($params, $input->bindParamArr());
            } else {
                array_push($params, $inputs[$key]);
            }
        }

        // use prepare for inserts when AE is enabled
        if (isColEncrypted() || $api == INSERT_PREPARE_PARAMS) {
            $stmt = sqlsrv_prepare($conn, $insertSql, $params);
            if ($stmt) {
                $r = sqlsrv_execute($stmt);
            } else {
                fatalError("insertRow: failed to prepare insert query!");
            }
        } else {
            $stmt = sqlsrv_query($conn, $insertSql, $params);
        }
    }

    return $stmt;
}

/**
 * Perform a simple select from a table with or without where condition(s)
 * @param  resource  $conn : connection resource
 * @param  string  $tbname : name of the table
 * @param  string  $conds : string of condition(s) with placeholders, null by default
 * @param  array  $values : array of parameters, null by default
 * @return resource sqlsrv statement upon success or false otherwise
 */
function selectFromTable($conn, $tbname, $conds = null, $values = null)
{
    $sql = "SELECT * FROM $tbname";
    return executeQuery($conn, $sql, $conds, $values);
}

/**
 * Perform a query from a table with or without where condition(s)
 * @param  resource  $conn : connection resource
 * @param  string  $sql : T-SQL query
 * @param  string  $conds : string of condition(s) possibly with placeholders, null by default
 * @param  array  $values : array of parameters for placeholders in $conds, null by default
 * @param  array  $options : array of query options, null by default
 * @return resource sqlsrv statement upon success or false otherwise
 */
function executeQuery($conn, $sql, $conds = null, $values = null, $options = null)
{
    if (!isColEncrypted()) {
        // if not encrypted replace placeholders ('?') with values, if array not empty
        if (!empty($values)) {
            $tmpArray = explode('?', $conds);
            $i = 0;
            foreach ($values as $value) {
                if (!is_numeric($value)) {
                    $tmpArray[$i++] .= "'$value'";
                } else {
                    $tmpArray[$i++] .= "$value";
                }
            }
            $clause = implode($tmpArray);
            $sql = $sql . " WHERE $clause ";
        } elseif (!empty($conds)) {
            $sql = $sql . " WHERE $conds ";
        }

        $stmt = sqlsrv_query($conn, $sql, null, $options);
    } else {
        // with AE enabled, use sqlsrv_prepare() in case there are
        // fields with unlimited size
        if (empty($conds)) {
            $stmt = sqlsrv_prepare($conn, $sql, null, $options);
        } else {
            $sql = $sql . " WHERE $conds ";
            // pass $values to sqlsrv_prepare whether the array is null, empty or filled
            $stmt = sqlsrv_prepare($conn, $sql, $values, $options);
        }
        if ($stmt) {
            $r = sqlsrv_execute($stmt);
            if (!$r) {
                fatalError("executeQuery: failed to execute \'$sql\'!");
            }
        }
    }
    if (!$stmt) {
        fatalError("executeQuery: failed to run query \'$sql\'!");
    }

    return $stmt;
}

/**
 * Similar to executeQuery() but with query options
 * @return resource sqlsrv statement upon success or false otherwise
 */
function executeQueryEx($conn, $sql, $options)
{
    // when AE is enabled, use sqlsrv_prepare() to handle fields with unlimited size
    if (!isColEncrypted()) {
        $stmt = sqlsrv_query($conn, $sql, null, $options);
    } else {
        $stmt = sqlsrv_prepare($conn, $sql, null, $options);
        if ($stmt) {
            $r = sqlsrv_execute($stmt);
            if (!$r) {
                fatalError("executeQueryEx: failed to execute \'$sql\'!");
            }
        }
    }
    if (!$stmt) {
        fatalError("executeQueryEx: failed to run query \'$sql\'!");
    }

    return $stmt;
}

/**
 * Similar to executeQuery() but with params for binding and 
 * whether this query is expected to fail
 * @return resource sqlsrv statement upon success or false otherwise
 */
function executeQueryParams($conn, $sql, $params, $expectedToFail = false, $message = '')
{
    $res = true;
    if (isColEncrypted()) {
        $stmt = sqlsrv_prepare($conn, $sql, $params);
        if ($stmt) {
            $res = sqlsrv_execute($stmt);
        }
    } else {
        $stmt = sqlsrv_query($conn, $sql, $params);
    }
    
    if ($stmt === false || !$res ) {
        if (! $expectedToFail) {
            fatalError($message);
        } else {
            print_r(sqlsrv_errors());
        }
    } else {
        if ($expectedToFail) {
            fatalError($message);
        } 
    }
    
    return $stmt;
}

/**
 * Fetch all rows and all columns given a table name, and print them
 * @param  resource  $conn : connection resource
 * @param  string $tbname : name of the table to fetch from
 */
function fetchAll($conn, $tbname)
{
    $stmt = selectFromTable($conn, $tbname);
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        foreach ($row as $key => $value) {
            if (is_object($value)) {
                print "$key:\n";
                foreach ($value as $k =>$v) {
                    print("  $k: $v\n");
                }
            } else {
                print("$key: $value\n");
            }
        }
    }
}

/**
 * Create a test table with columns of various types given a table name,
 * all deterministic if AE is enabled
 * @param  resource  $conn : connection resource
 * @param  string  $tbname : name of the table to create
 * @return resource sqlsrv statement
 */
function createTestTable($conn, $tbname)
{
    $columns = array(new ColumnMeta('int', 'c1_int'),
                     new ColumnMeta('tinyint', 'c2_tinyint'),
                     new ColumnMeta('smallint', 'c3_smallint'),
                     new ColumnMeta('bigint', 'c4_bigint'),
                     new ColumnMeta('bit', 'c5_bit'),
                     new ColumnMeta('float', 'c6_float'),
                     new ColumnMeta('real', 'c7_real'),
                     new ColumnMeta('decimal(28,4)', 'c8_decimal'),
                     new ColumnMeta('numeric(32,4)', 'c9_numeric'),
                     new ColumnMeta('money', 'c10_money', null, true, true),
                     new ColumnMeta('smallmoney', 'c11_smallmoney', null, true, true),
                     new ColumnMeta('char(512)', 'c12_char'),
                     new ColumnMeta('varchar(512)', 'c13_varchar'),
                     new ColumnMeta('varchar(max)', 'c14_varchar_max'),
                     new ColumnMeta('nchar(512)', 'c15_nchar'),
                     new ColumnMeta('nvarchar(512)', 'c16_nvarchar'),
                     new ColumnMeta('nvarchar(max)', 'c17_nvarchar_max'),
                     new ColumnMeta('text', 'c18_text'),
                     new ColumnMeta('ntext', 'c19_ntext'),
                     new ColumnMeta('binary(512)', 'c20_binary'),
                     new ColumnMeta('varbinary(512)', 'c21_varbinary'),
                     new ColumnMeta('varbinary(max)', 'c22_varbinary_max'),
                     new ColumnMeta('image', 'c23_image'),
                     new ColumnMeta('uniqueidentifier', 'c24_uniqueidentifier'),
                     new ColumnMeta('datetime', 'c25_datetime'),
                     new ColumnMeta('smalldatetime', 'c26_smalldatetime'),
                     new ColumnMeta('timestamp', 'c27_timestamp'),
                     new ColumnMeta('xml', 'c28_xml'),
                     );

    return createTable($conn, $tbname, $columns);
}

/**
 * Insert the specified number of rows with test data into the test table
 * @param  resource  $conn : connection resource
 * @param  string  $tbname : name of the table to create
 * @param  int  $rowCount : number of test rows to be inserted
 * @return number of rows inserted
 */
function insertTestRows($conn, $tbame, $rowCount)
{
    $count = 0;
    for ($i = 0; $i < $rowCount; $i++) {
        if (insertTestRow($conn, $tbame, rand(1, 20))) {
            $count++;
        }
    }

    if ($count != $rowCount) {
        die("$count rows inserted instead of $rowCount\n");
    }
    return ($count);
}

/**
 * Insert a number of rows with test data into the test table given the indexes
 * @param  resource  $conn : connection resource
 * @param  string  $tbname : name of the table to create
 * @param  int  $minIndex : index of the first row to be inserted
 * @param  int  $maxIndex : index of the last row to be inserted
 * @return number of rows inserted
 */
function insertTestRowsByRange($conn, $tbame, $minIndex, $maxIndex)
{
    $count = 0;
    $rowCount = $maxIndex - $minIndex + 1;
    if ($rowCount > 0) {
        for ($i = $minIndex; $i <= $maxIndex; $i++) {
            if (insertTestRow($conn, $tbame, $i)) {
                $count++;
            }
        }
    }
    if ($count != $rowCount) {
        die("$count rows inserted instead of $rowCount\n");
    }
    return ($count);
}

/**
 * Insert one or more specific rows of test data into the test table
 * @param  resource  $conn : connection resource
 * @param  string  $tbname : name of the table to create
 * @param  int  $index : the index of a certain row of test data
 * @return resource sqlsrv statement upon success or false otherwise
 */
function insertTestRow($conn, $tbname, $index)
{
    if ($index < 1 || $index > 20) {
        echo("Invalid row index $index for test data!\n");
        return false;
    }
    // get array of input values
    $inputArray = getInsertArray($index);
    if (empty($inputArray)) {
        fatalError("getInsertSqlComplete: inputs for inserting a row cannot be empty");
    }

    $result = null;
    if (isColEncrypted()) {
        $stmt = insertRow($conn, $tbname, $inputArray, $result);
    } else {
        // do not use the generic insertRow as the binary data needs some pre-processing
        // before calling sqlsrv_query()
        $colStr = "INSERT INTO $tbname (";
        $valStr = "VALUES (";
        $col = 1;
        foreach ($inputArray as $key => $value) {
            $colStr .= $key . ", ";

            if (is_array($value)) {
                $value = $value[0];
                // this might be an input to a decimal, a numeric or a binary field
                if (isBinary($col)) {
                    $value = "0x" . $value;    // annotate the input string as a hex string
                }
            }
            if (is_null($value)) {
                $valStr .= "null, ";
            } elseif (is_string($value) && !isBinary($col)) {
                $valStr .= "'" . $value . "', ";
            } else {
                $valStr .= $value . ", ";
            }
            $col++;
        }
        $colStr = rtrim($colStr, ", ") . ") ";
        $valStr = rtrim($valStr, ", ") . ") ";
        $insertSql = $colStr . $valStr;
        $stmt = sqlsrv_query($conn, $insertSql);
    }
    if (!$stmt || $result === false) {
        fatalError("insertTestRow: failed to insert row $index!\n");
    }
    return $stmt;
}

/**
 * Get a row of data to be inserted into the test table
 * @param  int  $index : the index of a certain row of test data
 * @return an array of key-value pairs
 */
function getInsertArray($index)
{
    if (! UseUTF8data()) {
        require_once('MsData.inc');
        return getTestData($index);
    } else {
        require_once('MsData_UTF8.inc');
        return getTestData_UTF8($index);
    }

    // get array of input values
}

/**
 * Get test data based on the row and column
 * @param  int  $rowIndex : the row index
 * @param  int  $colIndex : the column index
 * @return data of mixed type or null if something went wrong
 */
function getInsertData($rowIndex, $colIndex)
{
    // get array of input values
    $inputArray = getInsertArray($rowIndex);
    if (empty($inputArray)) {
        fatalError("getInsertData: failed to retrieve data at row $rowIndex.\n");
    }
    $count = 0;
    foreach ($inputArray as $key => $value) {
        if (++$count == $colIndex) {
            if (is_array($value)) {
                return $value[0];
            } else {
                return $value;
            }
            break;
        }
    }

    return null;
}


} // end of namespace AlwaysEncrypted

namespace {
function getSqlType($k)
{
    switch ($k) {
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

function getSqlsrvSqlType($k, $dataSize)
{
    switch ($k) {
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

function isXml($k)
{
    switch ($k) {
        case 28:    return (true);  // xml
        default:    break;
    }
    return (false);
}

function isStreamable($k)
{
    switch ($k) {
        case 12:    return (true);  // char(512)
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

function isNumeric($k)
{
    switch ($k) {
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

function isChar($k)
{
    switch ($k) {
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

function isBinary($k)
{
    switch ($k) {
        case 20:    return (true);  // binary
        case 21:    return (true);  // varbinary(512)
        case 22:    return (true);  // varbinary(max)
        case 23:    return (true);  // image
        default:    break;
    }
    return (false);
}

function isDateTime($k)
{
    switch ($k) {
        case 25:    return (true);  // datetime
        case 26:    return (true);  // smalldatetime
        case 27:    return (true);  // timestamp
        default:    break;
    }
    return (false);
}

function isUnicode($k)
{
    switch ($k) {
        case 15:    return (true);  // nchar(512)
        case 16:    return (true);  // nvarchar(512)
        case 17:    return (true);  // nvarchar(max)
        case 19:    return (true);  // ntext
        default:    break;
    }
    return (false);
}

function isUpdatable($k)
{
    switch ($k) {
        case 27:    return (false); // timestamp
        default:    break;
    }
    return (true);
}

function isLiteral($k)
{
    switch ($k) {
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

function getMetadata($k, $info)
{
    if (strcasecmp($info, 'Name') == 0) {
        return (getColName($k));
    }
    if (strcasecmp($info, 'Size') == 0) {
        return (getColSize($k));
    }
    if (strcasecmp($info, 'Precision') == 0) {
        return (getColPrecision($k));
    }
    if (strcasecmp($info, 'Scale') == 0) {
        return (getColScale($k));
    }
    if (strcasecmp($info, 'Nullable') == 0) {
        return (getColNullable($k));
    }
    return ("");
}

function getColName($k)
{
    switch ($k) {
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
        default:
            fatalError("Invalid column index $k");
            break;
    }
    return ("");
}

function isStreamData($k)
{
    switch ($k) {
        case 14:    return (true);  // varchar(max)
        case 17:    return (true);  // nvarchar(max)
        case 18:    return (true);  // text
        case 19:    return (true);  // ntext
        case 20:    return (true);  // binary
        case 21:    return (true);  // varbinary(512)
        case 22:    return (true);  // varbinary(max)
        case 23:    return (true);  // image
        case 27:    return (true);  // timestamp
        case 28:    return (true);  // xml
        default:    break;
    }
    return (false);
}

function getColSize($k)
{
    switch ($k) {
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

function getColPrecision($k)
{
    switch ($k) {
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

function getColScale($k)
{
    switch ($k) {
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

function getColNullable($k)
{
    return (isUpdatable($k) ? "1" : "0");
}

function getSampleData($k)
{
    switch ($k) {
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
}

?>

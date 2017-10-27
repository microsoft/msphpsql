--TEST--
PHP - test bind output parameters with various data types
--DESCRIPTION--
This test verifys binding output paramter for data types below except null, DateTime, or stream types which cannot be used as output parameters
1:  int
2:  tinyint
3:  smallint
4:  bigint
5:  bit
6:  float
7:  real
8:  decimal(28,4)
9:  numeric(32,4)
10: money
11: smallmoney
12: char(512)
13: varchar(512)
14: varchar(max)
15: nchar(512)
16: nvarchar(512)
17: nvarchar(max)
18: text
19: ntext
20: binary(512)
21: varbinary(512)
22: varbinary(max)
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

/**
 *  main function called by repro to set up the test, iterate through the given datatypes and test the output param.
 *  @param $minType first dataType
 *  @param $maxType last dataType
 */
function main($minType, $maxType)
{
    $testName = "BindParam - OutputParam";
    $tableName = "test_Datatypes_Output";
    $columnNames = array( "c1","c2" );
    startTest($testName);

    setup();
    $conn = AE\connect();

    for ($k = $minType; $k <= $maxType; $k++) {
        if ($k == 18 || $k == 19) {
            // skip text and ntext types; not supported as output params
            continue;
        } 
              
        $sqlType = getSqlType($k);
        // for each data type create a table with two columns, 1: dataType id 2: data type
        // $dataType = "[$columnNames[0]] int, [$columnNames[1]] $sqlType";
        if ($k == 10 || $k == 11) {
            // do not encrypt money type -- ODBC restrictions
            $noEncrypt = true;
        } else {
            $noEncrypt = false;
        }

        $columns = array(new AE\ColumnMeta('int', $columnNames[0]),
                         new AE\ColumnMeta($sqlType, $columnNames[1], null, true, $noEncrypt));
        AE\createTable($conn, $tableName, $columns);

        // data to populate the table, false since we don't want to initialize a variable using this data.
        $data = getData($k, false);

        traceData($sqlType, $data);
        $dataValues = array($k, $data);

        insertRowNoOption($conn, $tableName, $columnNames, $dataValues);

        execProc($conn, $tableName, $columnNames, $k, $data, $sqlType);
    }

    dropTable($conn, $tableName, $k);

    sqlsrv_close($conn);

    endTest($testName);
}

/**
 *  creates and executes store procedure for the given data type.
 *  @param $conn
 *  @param $tableName
 *  @param $columnNames and array containig the column names
 *  @param $k datatype id
 *  @param $data is used to get the SQLSRV_PHPTYPE_*
 *  @param $sqlType the same datatype used to create the table with
 */
function execProc($conn, $tableName, $columnNames, $k, $data, $sqlType)
{
    // With AE enabled it is stricter with data size
    $dataSize = AE\isColEncrypted() ? 512 : strlen($data);
    $phpDriverType = getSqlsrvSqlType($k, $dataSize);

    $spArgs = "@p1 int, @p2 $sqlType OUTPUT";

    $spCode = "SET @p2 = ( SELECT c2 FROM $tableName WHERE c1 = @p1 )";

    $procName = "testBindOutSp";
    createProc($conn, $procName, $spArgs, $spCode);

    $callArgs = "?, ?";
    //get data to initialize $callResult variable, this variable should be different than inserted data in the table
    $initData = getData($k, true);
    $callResult = $initData;

    $inType = AE\isColEncrypted() ? SQLSRV_SQLTYPE_INT : null;
    $params = array(array($k, SQLSRV_PARAM_IN, null, $inType),
                    array(&$callResult, SQLSRV_PARAM_OUT, null, $phpDriverType));

    callProc($conn, $procName, $callArgs, $params);
    // check if it is updated
    if ($callResult === $initData) {
        die("the result should be different");
    }
    dropProc($conn, $procName);
}

/**
 *  insert the data in the given table and columns without any option for data.
 *  @param $conn
 *  @param $tableName
 *  @param $columnNames array containig the column names
 *  @param $dataValues array of values to be insetred in the table
 */
function insertRowNoOption($conn, $tableName, $columnNames, $dataValues)
{
    $res = null;
    $stmt = AE\insertRow($conn, 
        $tableName, 
        array($columnNames[0] => $dataValues[0], $columnNames[1] => $dataValues[1]),
        $res,
        AE\INSERT_QUERY_PARAMS
    );
    
    if ($stmt === false || $res === false) {
        print_r(sqlsrv_errors());
    }
}

/**
 *  returns a data value by its datatype id
 *  @param $k  data type id, this id of each datatype are the same as the one in the MsCommon.inc file
 *  @param $initData  boolean parameter, if true it means the returned data value is used to initialize a variable.
 */
function getData($k, $initData)
{
    if (false == $initData) {
        switch ($k) {
            case 1:     // int
                return(123456789);

            case 2:     // tinyint
                return(234);

            case 3:     // smallint
                return(5678);

            case 4:     // bigint
                return(123456789987654321);

            case 5:     // bit
                return (1);

            case 6:     // float
                return (123.456);

            case 7:     // real
                return (789.012);

            case 8:     // decimal(28,4)
            case 9:     // numeric(32,4)
            case 10:    // money
            case 11:    // smallmoney
                return(987.0123);

            case 12:    // char(512)
            case 13:    // varchar(512)
            case 14:    // varchar(max)
            case 15:    // nchar(512)
            case 16:    // nvarchar(512)
            case 17:    // nvarchar(max)
            case 18:    // text
            case 19:    // ntext - deprecated
                return("HelloWorld");

            case 20:    // binary(512)
            case 21:    // varbinary(512)
            case 22:    // varbinary(max)
                return(0x0001e240); //123456
            default:
                return(null);
        } // switch
    } else {
        switch ($k) {
            case 1:     // int
            case 2:     // tinyint
            case 3:     // smallint
            case 4:     // bigint
            case 5:     // bit
                return (0);

            case 6:     // float
            case 7:     // real
            case 8:     // decimal(28,4)
            case 9:     // numeric(32,4)
            case 10:    // money
            case 11:    // smallmoney
                return(00.00);

            case 12:    // char(512)
            case 13:    // varchar(512)
            case 14:    // varchar(max)
            case 15:    // nchar(512)
            case 16:    // nvarchar(512)
            case 17:    // nvarchar(max)
            case 18:    // text
            case 19:    // ntext
                return("default");

            case 20:    // binary(512)
            case 21:    // varbinary(512)
            case 22:    // varbinary(max)
                return(0x0);

            default:
                return(null);
        } // switch
    }
}

try {
    if (AE\isColEncrypted()) {
        // TODO: fix this test to work with binary types when enabling AE
        main(1, 17);
    } else {
        main(1, 22);
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECTREGEX--

Test "BindParam - OutputParam" completed successfully.

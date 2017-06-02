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
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

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
    StartTest($testName);

    Setup();
    $conn = Connect();
    
    for ($k = $minType; $k <= $maxType; $k++)
    {
        if ($k == 18 || $k == 19)
        {
            // skip text and ntext types; not supported as output params 
            continue;
        }
        $sqlType = GetSqlType($k);
        // for each data type create a table with two columns, 1: dataType id 2: data type
        $dataType = "[$columnNames[0]] int, [$columnNames[1]] $sqlType";
        CreateTableEx($conn, $tableName, $dataType);
        
        // data to populate the table, false since we don't want to initialize a variable using this data.
        $data = GetData($k, false);
        
        TraceData($sqlType, $data);
        $dataValues = array($k, $data);
        
        InsertRowNoOption( $conn, $tableName, $columnNames, $dataValues );

        ExecProc($conn, $tableName, $columnNames, $k, $data, $sqlType);
    }
    
    DropTable($conn, $tableName, $k);
    
    sqlsrv_close($conn);

    EndTest($testName); 
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
function ExecProc($conn, $tableName, $columnNames, $k, $data, $sqlType)
{
        $phpDriverType = GetDriverType($k, strlen($data));
        
        $spArgs = "@p1 int, @p2 $sqlType OUTPUT";
        
        $spCode = "SET @p2 = ( SELECT c2 FROM $tableName WHERE c1 = @p1 )";
        
        $procName = "testBindOutSp";
        CreateProc( $conn, $procName, $spArgs, $spCode );
        
        $callArgs = "?, ?";
        //get data to initialize $callResult variable, this variable should be different than inserted data in the table
        $initData = GetData( $k , true);
        $callResult = $initData;
        
        $params = array( array( $k, SQLSRV_PARAM_IN ), 
                    array( &$callResult, SQLSRV_PARAM_OUT, null, $phpDriverType ));
    
        CallProc($conn, $procName, $callArgs, $params);
        // check if it is updated
        if( $callResult === $initData ){
            die("the result should be different");
        }
        DropProc($conn, $procName); 
}

/**
 *  insert the data in the given table and columns without any option for data.
 *  @param $conn
 *  @param $tableName
 *  @param $columnNames array containig the column names
 *  @param $dataValues array of values to be insetred in the table 
 */
function InsertRowNoOption( $conn, $tableName, $columnNames, $dataValues )
{
    $tsql = "INSERT INTO [$tableName] ($columnNames[0], $columnNames[1]) VALUES (?, ?)";    
    $stmt = sqlsrv_query( $conn, $tsql, $dataValues );
    if( false === $stmt ){
        print_r( sqlsrv_errors() );
    }
}

/**
 *  returns a data value by its datatype id
 *  @param $k  data type id, this id of each datatype are the same as the one in the MsCommon.inc file
 *  @param $initData  boolean parameter, if true it means the returned data value is used to initialize a variable.
 */
function GetData( $k , $initData)
{
    if(false == $initData)
    {
        switch ($k)
        {
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
        
    }
    else
    {
        switch ($k)
        {
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

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    try
    {
        main(1, 22);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECTREGEX--

Test "BindParam - OutputParam" completed successfully.
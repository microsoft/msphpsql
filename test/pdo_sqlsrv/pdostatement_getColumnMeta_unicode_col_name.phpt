--TEST--
Test the PDOStatement::getColumnMeta() method for Unicode column names (Note: there could be an issue about using a non-existent column index --- doesn't give any error/output/warning).
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
  
require_once 'MsCommon.inc';
  
function fetch_both( $conn )
{
    global $table1; 
    $stmt = $conn->query( "Select * from ". $table1 );

    // 1
    $meta = $stmt->getColumnMeta( 0 );
    var_dump($meta);         

    // 2
    $meta = $stmt->getColumnMeta( 1 );
    var_dump($meta);         

    // 3
    $meta = $stmt->getColumnMeta( 2 );
    var_dump($meta);        

    // 4
    $meta = $stmt->getColumnMeta( 3 );
    var_dump($meta);    
     
    // 5
    $meta = $stmt->getColumnMeta( 4 );
    var_dump($meta);        

    // 6
    $meta = $stmt->getColumnMeta( 5 );
    var_dump($meta);        

    // 7
    $meta = $stmt->getColumnMeta( 6 );
    var_dump($meta);        

    // 8
    $meta = $stmt->getColumnMeta( 7 );
    var_dump($meta);        

    // Test invalid arguments, set error mode to silent to reduce the amount of error messages generated
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

    // Test negative column number, ignore the error messages
    $meta = $stmt->getColumnMeta( -1 );
    var_dump($meta);

    // Test non-existent column number
    //$meta = $stmt->getColumnMeta( 10 );
    //var_dump($meta);
}
 
function create_and_insert_table_unicode( $conn )
{
    global $string_col, $date_col, $large_string_col, $xml_col, $binary_col, $int_col, $decimal_col, $guid_col, $null_col, $comma, $closing_brace, $table1;
   
    try
    {
      $create_query = 
      
        "IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'" . $table1 . "') AND type in (N'U'))
        DROP TABLE " . $table1 . 
        
        " CREATE TABLE [dbo].[" . $table1 . "](
          [此是後話] [int] NULL,
          [Κοντάוְאַתָּה第] [char](10) NULL,
          [NΚοντάוְאַתָּה第] [nchar](10) NULL,
          [ნომინავიiałopioБун] [datetime] NULL,
          [VarcharCol] [varchar](50) NULL,
          [NVarΚοντάוְאַתָּה第] [nvarchar](50) NULL,
          [FloatCol] [float] NULL,
        [XmlCol] [xml] NULL
        ) ON [PRIMARY]
        ";
        
        $conn->query( $create_query );
        
        for ($i = 0 ; $i <= 1; ++ $i)
        {
          $insert_query = 
                    "INSERT INTO PDO_Types_1 VALUES (".
                    $int_col[$i] . $comma . 
                    $string_col[$i] . $comma .
                    $string_col[$i] . $comma . 
                    "Convert(datetime, ". $date_col[$i] . ")" . $comma .
                    $string_col[$i] . $comma . 
                    $string_col[$i] . $comma . 
                    $decimal_col[$i] . $comma .
                    $xml_col[$i] .
                    ")";
        
          $conn->query ( $insert_query );
        }
    }
    catch(Exception $e)
    {
        var_dump( $e);
        exit;
    }
} 
try 
{      
    $db = connect();
    create_and_insert_table_unicode( $db );
    fetch_both($db);
}

catch( PDOException $e ) {

    var_dump( $e );
    exit;
}


?> 
--EXPECTF--

array(8) {
  ["flags"]=>
  int(0)
  ["sqlsrv:decl_type"]=>
  string(3) "int"
  ["native_type"]=>
  string(6) "string"
  ["table"]=>
  string(0) ""
  ["pdo_type"]=>
  int(2)
  ["name"]=>
  string(12) "此是後話"
  ["len"]=>
  int(10)
  ["precision"]=>
  int(0)
}
array(8) {
  ["flags"]=>
  int(0)
  ["sqlsrv:decl_type"]=>
  string(4) "char"
  ["native_type"]=>
  string(6) "string"
  ["table"]=>
  string(0) ""
  ["pdo_type"]=>
  int(2)
  ["name"]=>
  string(29) "Κοντάוְאַתָּה第"
  ["len"]=>
  int(10)
  ["precision"]=>
  int(0)
}
array(8) {
  ["flags"]=>
  int(0)
  ["sqlsrv:decl_type"]=>
  string(5) "nchar"
  ["native_type"]=>
  string(6) "string"
  ["table"]=>
  string(0) ""
  ["pdo_type"]=>
  int(2)
  ["name"]=>
  string(30) "NΚοντάוְאַתָּה第"
  ["len"]=>
  int(10)
  ["precision"]=>
  int(0)
}
array(8) {
  ["flags"]=>
  int(0)
  ["sqlsrv:decl_type"]=>
  string(8) "datetime"
  ["native_type"]=>
  string(6) "string"
  ["table"]=>
  string(0) ""
  ["pdo_type"]=>
  int(2)
  ["name"]=>
  string(38) "ნომინავიiałopioБун"
  ["len"]=>
  int(23)
  ["precision"]=>
  int(3)
}
array(8) {
  ["flags"]=>
  int(0)
  ["sqlsrv:decl_type"]=>
  string(7) "varchar"
  ["native_type"]=>
  string(6) "string"
  ["table"]=>
  string(0) ""
  ["pdo_type"]=>
  int(2)
  ["name"]=>
  string(10) "VarcharCol"
  ["len"]=>
  int(50)
  ["precision"]=>
  int(0)
}
array(8) {
  ["flags"]=>
  int(0)
  ["sqlsrv:decl_type"]=>
  string(8) "nvarchar"
  ["native_type"]=>
  string(6) "string"
  ["table"]=>
  string(0) ""
  ["pdo_type"]=>
  int(2)
  ["name"]=>
  string(33) "NVarΚοντάוְאַתָּה第"
  ["len"]=>
  int(50)
  ["precision"]=>
  int(0)
}
array(8) {
  ["flags"]=>
  int(0)
  ["sqlsrv:decl_type"]=>
  string(5) "float"
  ["native_type"]=>
  string(6) "string"
  ["table"]=>
  string(0) ""
  ["pdo_type"]=>
  int(2)
  ["name"]=>
  string(8) "FloatCol"
  ["len"]=>
  int(53)
  ["precision"]=>
  int(0)
}
array(8) {
  ["flags"]=>
  int(0)
  ["sqlsrv:decl_type"]=>
  string(3) "xml"
  ["native_type"]=>
  string(6) "string"
  ["table"]=>
  string(0) ""
  ["pdo_type"]=>
  int(2)
  ["name"]=>
  string(6) "XmlCol"
  ["len"]=>
  int(0)
  ["precision"]=>
  int(0)
}

Warning: PDOStatement::getColumnMeta(): SQLSTATE[42P10]: Invalid column reference: column number must be non-negative in %s on line %x
bool(false)
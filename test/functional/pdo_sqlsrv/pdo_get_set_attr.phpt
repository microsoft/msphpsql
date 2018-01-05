--TEST--
Test PDO::setAttribute() and PDO::getAttribute() methods.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

// A custom PDO statement class to test PDO::ATTR_STATEMENT_CLASS
class CustomPDOStatement extends PDOStatement
{
    protected function __construct()
    {
    }
}

function getAttr($conn, $attr)
{
    try {
        echo "Get Result $attr :\n";
        $result = $conn->getAttribute(constant($attr));
        var_dump($result);
    } catch (PDOException $e) {
        echo  $e->getMessage() . "\n";
    }
}

function setAttr($conn, $attr, $val)
{
    try {
        echo "Set Result $attr :\n";
        $result = $conn->setAttribute(constant($attr), $val);
        var_dump($result);
    } catch (PDOException $e) {
        echo  $e->getMessage() . "\n";
    }
}

function setGetAttr($testName, $conn, $attr, $val)
{
    try {
        echo "\n". $testName . ":\n";
        setAttr($conn, $attr, $val);
        getAttr($conn, $attr);
    } catch (PDOException $e) {
        var_dump($e);
    }
}

try {
    $conn = connect();
    $values = array("PDO::ATTR_ERRMODE" => 2,
                    "PDO::ATTR_SERVER_VERSION" => "whatever",
                    "PDO::ATTR_DRIVER_NAME" => "whatever",
                    "PDO::ATTR_STRINGIFY_FETCHES" => true,
                    "PDO::ATTR_CLIENT_VERSION" => "whatever",
                    "PDO::ATTR_SERVER_INFO" => "whatever",
                    "PDO::ATTR_CASE" => PDO::CASE_LOWER,
                    "PDO::SQLSRV_ATTR_ENCODING" => PDO::SQLSRV_ENCODING_SYSTEM,
                    "PDO::ATTR_DEFAULT_FETCH_MODE" => PDO::FETCH_ASSOC,
                    "PDO::ATTR_ORACLE_NULLS" => PDO::NULL_NATURAL,
                    "PDO::SQLSRV_ATTR_CLIENT_BUFFER_MAX_KB_SIZE" => 5120,
                    "PDO::SQLSRV_ATTR_DIRECT_QUERY" => true,
                    "PDO::ATTR_STATEMENT_CLASS" => array('CustomPDOStatement', array()),
                    "PDO::SQLSRV_ATTR_QUERY_TIMEOUT" => 10,
                    "PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE" => false);

    $attributes = array("PDO::ATTR_ERRMODE",
                        "PDO::ATTR_SERVER_VERSION",
                        "PDO::ATTR_DRIVER_NAME",
                        "PDO::ATTR_STRINGIFY_FETCHES",
                        "PDO::ATTR_CLIENT_VERSION",
                        "PDO::ATTR_SERVER_INFO",
                        "PDO::ATTR_CASE",
                        "PDO::SQLSRV_ATTR_ENCODING",
                        "PDO::ATTR_DEFAULT_FETCH_MODE",
                        "PDO::ATTR_ORACLE_NULLS",
                        "PDO::SQLSRV_ATTR_CLIENT_BUFFER_MAX_KB_SIZE",
                        "PDO::SQLSRV_ATTR_DIRECT_QUERY",
                        "PDO::ATTR_STATEMENT_CLASS",
                        "PDO::SQLSRV_ATTR_QUERY_TIMEOUT",
                        "PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE");
    $i = 1;
    foreach ($attributes as $attr) {
        $testName = "Test_". $i;
        $i = $i + 1;
        setGetAttr($testName, $conn, $attr, $values[$attr]);
    }
    unset($conn);
} catch (PDOException $e) {
    var_dump($e);
    exit;
}
?>

--EXPECTREGEX--

Test_1:
Set Result PDO::ATTR_ERRMODE :
bool\(true\)
Get Result PDO::ATTR_ERRMODE :
int\(2\)

Test_2:
Set Result PDO::ATTR_SERVER_VERSION :
SQLSTATE\[IMSSP\]: A read-only attribute was designated on the PDO object.
Get Result PDO::ATTR_SERVER_VERSION :
string\([0-9]*\) "[0-9]{2}.[0-9]{2}.[0-9]{4}"

Test_3:
Set Result PDO::ATTR_DRIVER_NAME :
SQLSTATE\[IMSSP\]: A read-only attribute was designated on the PDO object.
Get Result PDO::ATTR_DRIVER_NAME :
string\(6\) "sqlsrv"

Test_4:
Set Result PDO::ATTR_STRINGIFY_FETCHES :
bool\(true\)
Get Result PDO::ATTR_STRINGIFY_FETCHES :
bool\(true\)

Test_5:
Set Result PDO::ATTR_CLIENT_VERSION :
SQLSTATE\[IMSSP\]: A read-only attribute was designated on the PDO object.
Get Result PDO::ATTR_CLIENT_VERSION :
array\(4\) {
  \[\"(DriverDllName|DriverName)\"\]=>
  (string\([0-9]+\) \"msodbcsql1[1-9].dll\"|string\([0-9]+\) \"(libmsodbcsql-[0-9]{2}\.[0-9]\.so\.[0-9]\.[0-9]|libmsodbcsql.[0-9]{2}.dylib)\")
  \["DriverODBCVer"\]=>
  string\(5\) \"[0-9]{1,2}\.[0-9]{1,2}\"
  \["DriverVer"\]=>
  string\(10\) "[0-9]{2}.[0-9]{2}.[0-9]{4}"
  \["ExtensionVer"\]=>
  string\([0-9]*\) \"[0-9].[0-9]\.[0-9](-(RC[0-9]?|preview))?(\.[0-9]+)?(\+[0-9]+)?\"
}

Test_6:
Set Result PDO::ATTR_SERVER_INFO :
SQLSTATE\[IMSSP\]: A read-only attribute was designated on the PDO object.
Get Result PDO::ATTR_SERVER_INFO :
array\(3\) {
  \["CurrentDatabase"\]=>
  string\([0-9]*\) ".*"
  \["SQLServerVersion"\]=>
  string\(10\) "[0-9]{2}.[0-9]{2}.[0-9]{4}"
  \["SQLServerName"\]=>
  string\([0-9]*\) ".*"
}

Test_7:
Set Result PDO::ATTR_CASE :
bool\(true\)
Get Result PDO::ATTR_CASE :
int\(2\)

Test_8:
Set Result PDO::SQLSRV_ATTR_ENCODING :
bool\(true\)
Get Result PDO::SQLSRV_ATTR_ENCODING :
int\(3\)

Test_9:
Set Result PDO::ATTR_DEFAULT_FETCH_MODE :
bool\(true\)
Get Result PDO::ATTR_DEFAULT_FETCH_MODE :
int\(2\)

Test_10:
Set Result PDO::ATTR_ORACLE_NULLS :
bool\(true\)
Get Result PDO::ATTR_ORACLE_NULLS :
int\(0\)

Test_11:
Set Result PDO::SQLSRV_ATTR_CLIENT_BUFFER_MAX_KB_SIZE :
bool\(true\)
Get Result PDO::SQLSRV_ATTR_CLIENT_BUFFER_MAX_KB_SIZE :
int\(5120\)

Test_12:
Set Result PDO::SQLSRV_ATTR_DIRECT_QUERY :
bool\(true\)
Get Result PDO::SQLSRV_ATTR_DIRECT_QUERY :
bool\(true\)

Test_13:
Set Result PDO::ATTR_STATEMENT_CLASS :
bool\(true\)
Get Result PDO::ATTR_STATEMENT_CLASS :
array\(2\) {
  \[0\]=>
  string\(18\) "CustomPDOStatement"
  \[1\]=>
  array\(0\) {
  }
}

Test_14:
Set Result PDO::SQLSRV_ATTR_QUERY_TIMEOUT :
bool\(true\)
Get Result PDO::SQLSRV_ATTR_QUERY_TIMEOUT :
int\(10\)

Test_15:
Set Result PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE :
bool\(true\)
Get Result PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE :
bool\(false\)

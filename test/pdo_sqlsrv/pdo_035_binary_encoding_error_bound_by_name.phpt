--TEST--
GitHub Issue #35 binary encoding error when binding by name
--SKIPIF--
--FILE--
<?php
function CompareBinaryData($inputFile, $data)
{
    // open input file first
    $stream = fopen($inputFile, "rb");

    $len = strlen($data);
    $matched = true;
    $numbytes = 8192;

    $pos = 0;
    while (! feof($stream) && $pos < $len)
    {
        $contents = fread($stream, $numbytes);
        
        // if $data is empty, check if $contents is also empty
        $contents_len = strlen($contents); 
        if ($len == 0) 
        {
            $matched = ($contents_len == 0);
            break;
        }

        // Compare contents (case-sensitive)
        $count = ($contents_len < $numbytes) ? $contents_len : $numbytes;
        $result = substr_compare($data, $contents, $pos, $count);

        if ($result != 0)
        {
            $matched = false;
            echo "Data corruption!!\nExpected: $contents\nActual:" . substr($data, $pos, $count) . "\n";
            break;
        }

        $pos += $count;
    }

    // close the data stream
    fclose($stream);

    return $matched;
}

function test()
{
    require_once("autonomous_setup.php");

    // Connect
    $conn = new PDO("sqlsrv:server=$serverName", $username, $password);

    // Create a temp table
    $tableName = "#testTableIssue35";
    $sql = "CREATE TABLE $tableName (Picture varbinary(max))";
    $stmt = $conn->query($sql);

    // Insert data using bind parameters
    $sql = "INSERT INTO $tableName VALUES (?)";
    $stmt = $conn->prepare($sql);
    $file = dirname(__FILE__)."/bike.jpg";
    $stream = fopen($file, "rb");
    $stmt->setAttribute(constant('PDO::SQLSRV_ATTR_ENCODING'), PDO::SQLSRV_ENCODING_BINARY);
    $stmt->bindParam(1, $stream, PDO::PARAM_LOB); 
    $result = $stmt->execute();
    fclose($stream);

    // fetch it back
    $stmt = $conn->prepare("SELECT Picture FROM $tableName"); 
    $stmt->execute();
    $stmt->bindColumn('Picture', $image, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);  
    $stmt->fetch(PDO::FETCH_BOUND);  

    var_dump(CompareBinaryData($file, $image));
    
    // Close connection
    $stmt = null;
    $conn = null;
}

test();

print "Done";
?>
--EXPECT--
bool(true)
Done

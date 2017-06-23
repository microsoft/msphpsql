--TEST--
Test UTF8 Encoding with emulate prepare
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
try {
	$inValue1 = pack('H*', '3C586D6C54657374446174613E4A65207072C3A966C3A87265206C27C3A974C3A93C2F586D6C54657374446174613E');	
	$inValueLen = strlen($inValue1);
			
	require_once 'MsSetup.inc';
	$dsn = "sqlsrv:Server=$server ; Database = $databaseName";
	$conn = new PDO($dsn, $uid, $pwd);

	$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$conn->setAttribute( PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_SYSTEM);	
	$stmt1 = $conn->query("IF OBJECT_ID('Table_UTF', 'U') IS NOT NULL DROP TABLE [Table_UTF]");	
	$stmt1 = null;	

	$stmt2 = $conn->query("CREATE TABLE [Table_UTF] ([c1_int] int PRIMARY KEY, [c2_char] char(512))");	
	$stmt2 = null;	

	$stmt3 = $conn->prepare("INSERT INTO [Table_UTF] (c1_int, c2_char) VALUES (:var1, :var2)");	
	$stmt3->setAttribute(constant('PDO::SQLSRV_ATTR_ENCODING'), PDO::SQLSRV_ENCODING_UTF8);		
	$stmt3->bindParam(2, $inValue1);	
	$stmt3->bindValue(1, 1);	
	$stmt3->execute();	 
	$stmt3->bindValue(1, 2);	
	$stmt3->execute();	 
	$stmt3->bindValue(1, 3);	
	$stmt3->execute();	 
	$stmt3->bindValue(1, 4);	
	$stmt3->execute();	 
	$stmt3->bindValue(1, 5);	
	$stmt3->execute();	 
	$stmt3 = null;	
		
	$stmt4 = $conn->prepare("SELECT * FROM [Table_UTF]");	
	$stmt4->setAttribute(constant('PDO::SQLSRV_ATTR_ENCODING'), PDO::SQLSRV_ENCODING_UTF8);
	$outValue1 = null;	
	$stmt4->execute();	
	$row1 = $stmt4->fetch();	
	$count1 = count($row1);	
	echo ("Number of rows: $count1\n");
	$v0 = $row1[0];	
	$outValue1 = $row1[1];	
	if (strncmp($inValue1, $outValue1, $inValueLen) == 0) {
		echo "outValue is the same as inValue.\n";
	}
	$outValue1 = null;
	
	$value1 = $stmt4->fetchcolumn(1);
	$outValue1 = $value1;
	if (strncmp($inValue1, $outValue1, $inValueLen) == 0) {
		echo "outValue is the same as inValue.\n";
	}
	$outvalue1 = null;
	
	$value2 = $stmt4->fetchColumn(1);
	$outValue1 = $value2;
	if (strncmp($inValue1, $outValue1, $inValueLen) == 0) {
		echo "outValue is the same as inValue.\n";
	}
	$outValue1 = null;
	
	$value3 = $stmt4->fetchColumn(1);
	$outValue1 = $value3;
	if (strncmp($inValue1, $outValue1, $inValueLen) == 0) {
		echo "outValue is the same as inValue.\n";
	}
	$outValue1 = null;
	
	$value4 = $stmt4->fetchColumn(1);
	$outValue1 = $value4;
	if (strncmp($inValue1, $outValue1, $inValueLen) == 0) {
		echo "outValue is the same as inValue.\n";
	}
	$stmt4 = null;
	
	$stmt5 = $conn->prepare( "SELECT ? = c2_char FROM [Table_UTF]", array(PDO::ATTR_EMULATE_PREPARES => true) );	
	$stmt5->setAttribute(constant('PDO::SQLSRV_ATTR_ENCODING'), PDO::SQLSRV_ENCODING_UTF8);
	$outValue1 = "hello";	
	$stmt5->bindParam( 1, $outValue1, PDO::PARAM_STR, 1024);
	$stmt5->execute();	
	if (strncmp($inValue1, $outValue1, $inValueLen) == 0) {
		echo "outValue is the same as inValue.\n";
	} else {
		echo "outValue is $outValue1\n";
	}
	$stmt5 = null;

	$stmt6 = $conn->query("DROP TABLE [Table_UTF]");	
	$stmt6 = null;
	
	$conn = null;	
}
catch (PDOexception $e){
    print_r( ($e->errorInfo)[2] );
    echo "\n";
}
?>
--EXPECT--
Number of rows: 4
outValue is the same as inValue.
outValue is the same as inValue.
outValue is the same as inValue.
outValue is the same as inValue.
outValue is the same as inValue.
Statement with emulate prepare on does not support output or input_output parameters.





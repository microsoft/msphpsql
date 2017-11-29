--TEST--
Fix for output string parameters length prior to output being delivered
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
function insertIntoStudies($conn, $id, $intro)
{
    if (AE\isColEncrypted()) {
        $stmt = AE\insertRow($conn, 'Studies', array('studyID' => $id, 'Intro' => $intro));
    } else {
        $stmt = sqlsrv_query($conn, "INSERT INTO Studies (studyID, Intro) VALUES (" . $id . ", N'". $intro ."')");
    }
    if ($stmt === false) {
        fatalError("Failed to insert $id and $intro!\n");
    }
}

sqlsrv_configure('WarningsReturnAsErrors', 0);
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

require_once('MsCommon.inc');
$conn = AE\connect();
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

dropProc($conn, 'sp_MakeSubject78');

// Create Table 'Subjects' but do not encrypt the first column because in the stored procedure
// we rely on the server to get the current date time. With Column Encryption, all input values
// have to be provided by the client
$columns = array(new AE\ColumnMeta('datetime', 'StartTime', null, true, true),
                 new AE\ColumnMeta('nchar(32)', 'sn'),
                 new AE\ColumnMeta('nvarchar(50)', 'extref'));
$stmt = AE\createTable($conn, 'Subjects', $columns);
unset($columns);

// Create table 'sn_x_study'
$columns = array(new AE\ColumnMeta('int', 'studyID'),
                 new AE\ColumnMeta('nchar(32)', 'sn'));
$stmt = AE\createTable($conn, 'sn_x_study', $columns);
unset($columns);

// Create table 'Studies'. When AE is enabled, the sql type must match 
// the column definition, but because this test wants to convert the 
// output of column 'Intro' to nvarchar(256), we do not encrypt this second column
$columns = array(new AE\ColumnMeta('int', 'studyID'),
                 new AE\ColumnMeta('nchar(32)', 'Intro', null, true, true));
$stmt = AE\createTable($conn, 'Studies', $columns);
unset($columns);

// Insert 3 rows into table 'Studies'
insertIntoStudies($conn, 1, "Test class 1");
insertIntoStudies($conn, 2, "12345678901234567890123456789012");
insertIntoStudies($conn, 3, "Test class 3");

$proc = <<<PROC
CREATE PROCEDURE [sp_MakeSubject78] 
 -- Add the parameters for the stored procedure here
 @studyID int,
 @sn nchar(32),
 @extref nvarchar(50),
 @introText nvarchar(256) OUTPUT
AS
BEGIN

if @extref IS NULL
 begin
 insert into Subjects (StartTime,sn) values (GETDATE(),@sn)
 end
else
 begin
 insert into Subjects (StartTime,sn,extref) values (GETDATE(),@sn,@extref)
 end

insert into sn_x_study (studyID,sn) values (@studyID,@sn)

select @introText=(select Intro from Studies where studyID=@studyID)

END
PROC;

$stmt = sqlsrv_query($conn, $proc);
if ($stmt === false) {
    fatalError("Error occurred when creating stored procedure 'sp_MakeSubject78'\n");
}

$tsql_callSP = "{call sp_MakeSubject78(?,?,?,?)}";
$introText="X";
  
$params = array(
     array( 2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
     array( 'HLAB', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NCHAR(32)),
     array( 'hlabhlabhlabhlab', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(50)),
     array( &$introText, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_NCHAR(32))
);

$stmt = sqlsrv_query($conn, $tsql_callSP, $params);

if ($stmt === false) {
    fatalError("Error occurred when calling stored procedure 'sp_MakeSubject78' (1)\n");
}

print_r(strlen($introText));
echo "\n";

while (($result = sqlsrv_next_result($stmt)) != null) {
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
}
if (sqlsrv_errors() != null) {
    print_r(sqlsrv_errors());
}
echo "$introText\n";

$tsql_callSP = "{call sp_MakeSubject78(?,?,?,?)}";
$introText="X";
  
$params = array(
     array( 2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
     array( 'HLAB', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NCHAR(32)),
     array( 'hlabhlabhlabhlab', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(50)),
     array( &$introText, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NCHAR(32))
);

$stmt = sqlsrv_query($conn, $tsql_callSP, $params);

if ($stmt === false) {
    fatalError("Error occurred when calling stored procedure 'sp_MakeSubject78' (2)\n");
}

print_r(strlen($introText));
echo "\n";

while (($result = sqlsrv_next_result($stmt)) != null) {
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
}
if (sqlsrv_errors() != null) {
    print_r(sqlsrv_errors());
}

echo "$introText\n";

$tsql_callSP = "{call sp_MakeSubject78(?,?,?,?)}";
$introText="X";
  
$params = array(
     array( 2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
     array( 'HLAB', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NCHAR(32)),
     array( 'hlabhlabhlabhlab', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(50)),
     array( &$introText, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING('utf-8'), SQLSRV_SQLTYPE_NCHAR(32))
);

$stmt = sqlsrv_query($conn, $tsql_callSP, $params);

if ($stmt === false) {
    fatalError("Error occurred when calling stored procedure 'sp_MakeSubject78' (3)\n");
}

print_r(strlen($introText));
echo "\n";

while (($result = sqlsrv_next_result($stmt)) != null) {
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
}
if (sqlsrv_errors() != null) {
    print_r(sqlsrv_errors());
}

echo "$introText\n";

$tsql_callSP = "{call sp_MakeSubject78(?,?,?,?)}";
$introText="X";
  
$params = array(
     array( 2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
     array( 'HLAB', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NCHAR(32)),
     array( 'hlabhlabhlabhlab', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(50)),
     array( &$introText, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_NCHAR(32))
);

$stmt = sqlsrv_query($conn, $tsql_callSP, $params);

if ($stmt === false) {
    fatalError("Error occurred when calling stored procedure 'sp_MakeSubject78' (4)\n");
}

print_r(strlen($introText));
echo "\n";

while (($result = sqlsrv_next_result($stmt)) != null) {
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
}

echo "$introText\n";

$tsql_callSP = "{call sp_MakeSubject78(?,?,?,?)}";
$introText="X";
  
$params = array(
     array( 2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
     array( 'HLAB', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NCHAR(32)),
     array( 'hlabhlabhlabhlab', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(50)),
     array( &$introText, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NCHAR(32))
);

$stmt = sqlsrv_query($conn, $tsql_callSP, $params);

if ($stmt === false) {
    fatalError("Error occurred when calling stored procedure 'sp_MakeSubject78' (5)\n");
}

print_r(strlen($introText));
echo "\n";

while (($result = sqlsrv_next_result($stmt)) != null) {
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
}

echo "$introText\n";

$tsql_callSP = "{call sp_MakeSubject78(?,?,?,?)}";
$introText="X";
  
$params = array(
     array( 2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
     array( 'HLAB', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NCHAR(32)),
     array( 'hlabhlabhlabhlab', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(50)),
     array( &$introText, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING('utf-8'), SQLSRV_SQLTYPE_NCHAR(32))
);

$stmt = sqlsrv_query($conn, $tsql_callSP, $params);

if ($stmt === false) {
    fatalError("Error occurred when calling stored procedure 'sp_MakeSubject78' (6)\n");
}

print_r(strlen($introText));
echo "\n";

while (($result = sqlsrv_next_result($stmt)) != null) {
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
}

echo "$introText\n";

dropTable($conn, 'Subjects');
dropTable($conn, 'sn_x_study');
dropTable($conn, 'Studies');
dropProc($conn, 'sp_MakeSubject78');

sqlsrv_close($conn);

?>
--EXPECT--
64
1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 
32
12345678901234567890123456789012
64
12345678901234567890123456789012
64
1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 
32
12345678901234567890123456789012
64
12345678901234567890123456789012

--TEST--
output string parameters with rows affected return results before output parameter.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
function insertIntoStudies($conn, $id)
{
    $intro = 'Test class ' . $id;
    $stmt = AE\insertRow($conn, 'Studies', array('studyID' => $id, 'Intro' => $intro));
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
}

require_once('MsCommon.inc');
$conn = AE\connect();

// drop the procedure if exists
dropProc($conn, 'sp_MakeSubject');

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

// Create table 'Studies'
$columns = array(new AE\ColumnMeta('int', 'studyID'),
                 new AE\ColumnMeta('nvarchar(max)', 'Intro'));
$stmt = AE\createTable($conn, 'Studies', $columns);
unset($columns);

// Insert 3 rows into table 'Studies'
for ($i = 1; $i <= 3; $i++) {
    insertIntoStudies($conn, $i);
}

$proc = <<<PROC
CREATE PROCEDURE [dbo].[sp_MakeSubject] 
	-- Add the parameters for the stored procedure here
	@studyID int,
	@sn nchar(32),
	@extref nvarchar(50),
	@introText nvarchar(max) OUTPUT
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
    die(print_r(sqlsrv_errors(), true));
}

$tsql_callSP = "{call sp_MakeSubject(?,?,?,?)}";
$introText="X";

// With AE, the sql type has to match the stored procedure parameter definition
$outSQLType = AE\isColEncrypted() ? SQLSRV_SQLTYPE_NVARCHAR('max') : SQLSRV_SQLTYPE_NVARCHAR(256);
$params = array(
     array( 1, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
     array( 'BLAH', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NCHAR(32)),
     array( 'blahblahblah', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(50)),
     array( &$introText, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), $outSQLType)
);

$stmt = sqlsrv_query($conn, $tsql_callSP, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
// when 337726 is fixed, this will print out the string length of 512
// print_r( strlen( $introText ));

while (($result = sqlsrv_next_result($stmt)) != null) {
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
}

dropTable($conn, 'Subjects');
dropTable($conn, 'sn_x_study');
dropTable($conn, 'Studies');
dropProc($conn, 'sp_MakeSubject');

sqlsrv_close($conn);

echo "$introText\n";

?>
--EXPECT--
Test class 1

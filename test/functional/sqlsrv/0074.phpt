--TEST--
output string parameters with rows affected return results before output parameter.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require( 'MsCommon.inc' );
$conn = Connect();
if( $conn === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query( $conn, "IF OBJECT_ID('Subjects', 'U') IS NOT NULL DROP TABLE Subjects" );
$stmt = sqlsrv_query( $conn, "IF OBJECT_ID('sn_x_study', 'U') IS NOT NULL DROP TABLE sn_x_study" );
$stmt = sqlsrv_query( $conn, "IF OBJECT_ID('Studies', 'U') IS NOT NULL DROP TABLE Studies" );
$stmt = sqlsrv_query( $conn, "IF OBJECT_ID('sp_MakeSubject', 'P') IS NOT NULL DROP PROCEDURE sp_MakeSubject" );

$stmt = sqlsrv_query( $conn, "CREATE TABLE Subjects (StartTime datetime, sn nchar(32), extref nvarchar(50))" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$stmt = sqlsrv_query( $conn, "CREATE TABLE sn_x_study (studyID int, sn nchar(32))" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$stmt = sqlsrv_query( $conn, "CREATE TABLE Studies (studyID int, Intro nvarchar(max))" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$stmt = sqlsrv_query( $conn, "INSERT INTO Studies (studyID, Intro) VALUES (1, 'Test class 1')" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$stmt = sqlsrv_query( $conn, "INSERT INTO Studies (studyID, Intro) VALUES (2, 'Test class 2')" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$stmt = sqlsrv_query( $conn, "INSERT INTO Studies (studyID, Intro) VALUES (3, 'Test class 3')" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
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

$stmt = sqlsrv_query( $conn, $proc );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$tsql_callSP = "{call sp_MakeSubject(?,?,?,?)}";
$introText="X";
		
$params = array( 
     array( 1, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
     array( 'BLAH', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NCHAR(32)),
     array( 'blahblahblah', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(50)),
     array( &$introText, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(256))
);

$stmt = sqlsrv_query( $conn, $tsql_callSP, $params);

if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
// when 337726 is fixed, this will print out the string length of 512
// print_r( strlen( $introText ));

while(( $result = sqlsrv_next_result( $stmt )) != null ) {
    if( $result === false ) {
        die( print_r( sqlsrv_errors(),true));
    }
}

sqlsrv_query( $conn, "DROP TABLE Subjects" );
sqlsrv_query( $conn, "DROP TABLE sn_x_study" );
sqlsrv_query( $conn, "DROP TABLE Studies" );
sqlsrv_query( $conn, "DROP PROCEDURE sp_MakeSubject");

sqlsrv_close( $conn );

echo "$introText\n";

?>
--EXPECT--
Test class 1

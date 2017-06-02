--TEST--
Fix for output string parameters length prior to output being delivered
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

require( 'MsCommon.inc' );
$conn = Connect();
if( $conn === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query( $conn, "IF OBJECT_ID('Subjects', 'U') IS NOT NULL DROP TABLE Subjects" );
$stmt = sqlsrv_query( $conn, "IF OBJECT_ID('sn_x_study', 'U') IS NOT NULL DROP TABLE sn_x_study" );
$stmt = sqlsrv_query( $conn, "IF OBJECT_ID('Studies', 'U') IS NOT NULL DROP TABLE Studies" );
$stmt = sqlsrv_query( $conn, "IF OBJECT_ID('sp_MakeSubject78', 'P') IS NOT NULL DROP PROCEDURE sp_MakeSubject78" );

$stmt = sqlsrv_query( $conn, "CREATE TABLE Subjects (StartTime datetime, sn nchar(32), extref nvarchar(50))" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$stmt = sqlsrv_query( $conn, "CREATE TABLE sn_x_study (studyID int, sn nchar(32))" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$stmt = sqlsrv_query( $conn, "CREATE TABLE Studies (studyID int, Intro nchar(32))" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$stmt = sqlsrv_query( $conn, "INSERT INTO Studies (studyID, Intro) VALUES (1, N'Test class 1')" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$stmt = sqlsrv_query( $conn, "INSERT INTO Studies (studyID, Intro) VALUES (2, N'12345678901234567890123456789012')" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$stmt = sqlsrv_query( $conn, "INSERT INTO Studies (studyID, Intro) VALUES (3, N'Test class 3')" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

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

$stmt = sqlsrv_query( $conn, $proc );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$tsql_callSP = "{call sp_MakeSubject78(?,?,?,?)}";
$introText="X";
  
$params = array( 
     array( 2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
     array( 'HLAB', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NCHAR(32)),
     array( 'hlabhlabhlabhlab', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(50)),
     array( &$introText, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_NCHAR(32))
);

$stmt = sqlsrv_query( $conn, $tsql_callSP, $params);

if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

print_r( strlen( $introText ));
echo "\n";

while(( $result = sqlsrv_next_result( $stmt )) != null ) {
    if( $result === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
}
if( sqlsrv_errors() != NULL ) {
  print_r( sqlsrv_errors() );
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

$stmt = sqlsrv_query( $conn, $tsql_callSP, $params);

if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

print_r( strlen( $introText ));
echo "\n";

while(( $result = sqlsrv_next_result( $stmt )) != null ) {
    if( $result === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
}
if( sqlsrv_errors() != NULL ) {
  print_r( sqlsrv_errors() );
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

$stmt = sqlsrv_query( $conn, $tsql_callSP, $params);

if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

print_r( strlen( $introText ));
echo "\n";

while(( $result = sqlsrv_next_result( $stmt )) != null ) {
    if( $result === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
}
if( sqlsrv_errors() != NULL ) {
  print_r( sqlsrv_errors() );
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

$stmt = sqlsrv_query( $conn, $tsql_callSP, $params);

if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

print_r( strlen( $introText ));
echo "\n";

while(( $result = sqlsrv_next_result( $stmt )) != null ) {
    if( $result === false ) {
        die( print_r( sqlsrv_errors(), true ));
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

$stmt = sqlsrv_query( $conn, $tsql_callSP, $params);

if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

print_r( strlen( $introText ));
echo "\n";

while(( $result = sqlsrv_next_result( $stmt )) != null ) {
    if( $result === false ) {
        die( print_r( sqlsrv_errors(), true ));
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

$stmt = sqlsrv_query( $conn, $tsql_callSP, $params);

if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

print_r( strlen( $introText ));
echo "\n";

while(( $result = sqlsrv_next_result( $stmt )) != null ) {
    if( $result === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
}

echo "$introText\n";

sqlsrv_query( $conn, "DROP TABLE Subjects" );
sqlsrv_query( $conn, "DROP TABLE sn_x_study" );
sqlsrv_query( $conn, "DROP TABLE Studies" );
sqlsrv_query( $conn, "DROP PROCEDURE sp_MakeSubject78" );

sqlsrv_close( $conn );

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

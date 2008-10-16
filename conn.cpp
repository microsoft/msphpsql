//----------------------------------------------------------------------------------------------------------------------------------
// File: conn.cpp
//
// Copyright (c) Microsoft Corporation.  All rights reserved.
//
// Contents: Routines that use connection handles
// 
// Comments:
//
// License: This software is released under the Microsoft Public License.  A copy of the license agreement 
//          may be found online at http://www.codeplex.com/SQL2K5PHP/license.
//----------------------------------------------------------------------------------------------------------------------------------

#include "php_sqlsrv.h"
#include <psapi.h>
#include <windows.h>
#include <winver.h>

#include <string>
#include <sstream>

// *** internal variables and constants ***

namespace {

// *** internal constants ***
// current subsytem.  defined for the CHECK_* error macros
int current_log_subsystem = LOG_CONN;

// an arbitrary figure that should be large enough for most connection strings.
const int DEFAULT_CONN_STR_LEN = 2048;
// PHP streams generally return no more than 8k.
const int PHP_STREAM_BUFFER_SIZE = 8192;
// connection timeout string
const char QUERY_TIMEOUT[] = "QueryTimeout";
// option for sending streams at execute time
const char SEND_STREAMS_AT_EXEC[] = "SendStreamParamsAtExec";
// length of buffer used to retrieve information for client and server info buffers
const int INFO_BUFFER_LEN = 256;
// number of segments in a version resource
const int VERSION_SUBVERSIONS = 4;


// *** internal function prototypes ***
sqlsrv_stmt* allocate_stmt( sqlsrv_conn* conn, zval const* options_z, char const* _FN_ TSRMLS_DC );
SQLRETURN build_connection_string_and_set_conn_attr( sqlsrv_conn const* conn, const char* server, zval const* options, 
                                                     __inout std::string& connection_string TSRMLS_DC );
bool mark_params_by_reference( zval** params_zz, char const* _FN_ TSRMLS_DC );
void sqlsrv_conn_common_close( sqlsrv_conn* c, const char* function, bool check_errors TSRMLS_DC );

}

// constants for parameters used by process_params function(s)
int sqlsrv_conn::descriptor;
char* sqlsrv_conn::resource_name = "sqlsrv_conn";

// connection specific parameter proccessing.  Use the generic function specialised to return a connection
// resource.
#define PROCESS_PARAMS( rsrc, function, param_spec, ... )                                                   \
    rsrc = process_params<sqlsrv_conn>( INTERNAL_FUNCTION_PARAM_PASSTHRU, LOG_CONN, function, param_spec, __VA_ARGS__ ); \
    if( rsrc == NULL ) {                                                                                    \
        RETURN_FALSE;                                                                                       \
    }

namespace ConnOptions {

const char APP[] = "APP";
const char ConnectionPooling[] = "ConnectionPooling";
const char Database[] = "Database";
const char Encrypt[] = "Encrypt";
const char Failover_Partner[] = "Failover_Partner";
const char LoginTimeout[] = "LoginTimeout";
const char PWD[] = "PWD";
const char QuotedId[] = "QuotedId";
const char TraceFile[] = "TraceFile";
const char TraceOn[] = "TraceOn";
const char TrustServerCertificate[] = "TrustServerCertificate";
const char TransactionIsolation[] = "TransactionIsolation";
const char UID[] = "UID";
const char WSID[] = "WSID";

}


// sqlsrv_connect( string $serverName [, array $connectionInfo])
//
// Creates a connection resource and opens a connection. By default, the
// connection is attempted using Windows Authentication.
//
// Parameters
// $serverName: A string specifying the name of the server to which a connection
// is being established. An instance name (for example, "myServer\instanceName")
// or port number (for example, "myServer, 1521") can be included as part of
// this string. For a complete description of the options available for this
// parameter, see the Server keyword in the ODBC Driver Connection String
// Keywords section of Using Connection String Keywords with SQL Native Client.
//
// $connectionInfo [OPTIONAL]: An associative array that contains connection
// attributes (for example, array("Database" => "AdventureWorks")).
//
// Return Value 
// A PHP connection resource. If a connection cannot be successfully created and
// opened, false is returned

PHP_FUNCTION( sqlsrv_connect )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );


    SQLRETURN r = SQL_SUCCESS;
    int zr = SUCCESS;
    std::string conn_str;
    char const* server = NULL;
    zval  *options_z = NULL;
    int server_len;
    SQLSMALLINT output_conn_size;

    DECL_FUNC_NAME( "sqlsrv_connect" );
    LOG_FUNCTION;

    reset_errors( TSRMLS_C );

    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "s|a", &server, &server_len, 
                                &options_z ) == FAILURE ) {

        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
        RETURN_FALSE;
    }

    conn_str.reserve( DEFAULT_CONN_STR_LEN );
    emalloc_auto_ptr<sqlsrv_conn> conn;
    conn = static_cast<sqlsrv_conn*>( emalloc( sizeof( sqlsrv_conn )));
    hash_auto_ptr stmts;
    ALLOC_HASHTABLE( stmts );

    zr = zend_hash_init( stmts, 10, NULL, sqlsrv_stmt_hash_dtor, 0 );
    if( zr == FAILURE ) {
        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_ZEND_HASH TSRMLS_CC );
        RETURN_FALSE;
    }

    conn->ctx.handle = NULL;
    conn->ctx.handle_type = SQL_HANDLE_DBC;
    conn->in_transaction = false;

    SQLHANDLE henv = g_henv_cp;   // by default use the connection pooling henv
    // check the connection pooling setting to determine which henv to use to allocate the connection handle
    if( options_z ) {

        zval** option_zz = NULL;
        int zr = SUCCESS;

        zr = zend_hash_find( Z_ARRVAL_P( options_z ), const_cast<char*>( ConnOptions::ConnectionPooling ),
                sizeof( ConnOptions::ConnectionPooling ), reinterpret_cast<void**>( &option_zz ));

        // if the option was found and it's not true, then use the non pooled environment handle
        if( zr != FAILURE && !zend_is_true( *option_zz )) {
            henv = g_henv_ncp;
        }
    }

    SQLSRV_G( henv_context )->ctx.handle = henv;

    r = SQLAllocHandle( SQL_HANDLE_DBC, henv, &conn->ctx.handle );
    if( !SQL_SUCCEEDED( r )) {
        handle_error( &SQLSRV_G( henv_context )->ctx, LOG_CONN, _FN_, NULL TSRMLS_CC );
        RETURN_FALSE;
    }
    if( r == SQL_SUCCESS_WITH_INFO ) {
        handle_warning( &SQLSRV_G( henv_context )->ctx, LOG_CONN, _FN_, NULL TSRMLS_CC );
    }

    try {

        r = build_connection_string_and_set_conn_attr( conn, server, options_z, conn_str TSRMLS_CC );
        CHECK_SQL_ERROR( r, SQLSRV_G( henv_context ), _FN_, NULL,
                         memset( const_cast<char*>( conn_str.c_str()), 0, conn_str.size() );
                         conn_str.clear();
                         SQLFreeHandle( conn->ctx.handle_type, conn->ctx.handle ); 
                         conn->ctx.handle = NULL; 
                         RETURN_FALSE );
    }
    catch( std::bad_alloc& ex ) {
        memset( const_cast<char*>( conn_str.c_str()), 0, conn_str.size() );
        conn_str.clear();
        LOG( SEV_ERROR, LOG_CONN, "C++ exception returned: %s", ex.what() );
        LOG( SEV_ERROR, LOG_CONN, "C++ memory allocation failure building the connection string." );
        DIE( "C++ memory allocation failure building the connection string." );
    }
    catch( std::out_of_range const& ex ) {
        memset( const_cast<char*>( conn_str.c_str()), 0, conn_str.size() );
        conn_str.clear();
        LOG( SEV_ERROR, LOG_CONN, "C++ exception returned: %s", ex.what() );
        RETURN_FALSE;
    }
    catch( std::length_error const& ex ) {
        memset( const_cast<char*>( conn_str.c_str()), 0, conn_str.size() );
        conn_str.clear();
        LOG( SEV_ERROR, LOG_CONN, "C++ exception returned: %s", ex.what() );
        SQLFreeHandle( conn->ctx.handle_type, conn->ctx.handle );
        conn->ctx.handle = SQL_NULL_HANDLE;
        RETURN_FALSE;
    }

    SQLSRV_STATIC_ASSERT( sizeof( char ) == sizeof( SQLCHAR ));    // make sure that cast below is valid
    r = SQLDriverConnect( conn->ctx.handle, NULL, reinterpret_cast<SQLCHAR*>( const_cast<char*>( conn_str.c_str() )),
                          static_cast<SQLSMALLINT>( conn_str.length() ), NULL, 
                          0, &output_conn_size, SQL_DRIVER_NOPROMPT );
    // Would rather use std::fill here, but that gives a warning about not being able to inline
    // the iterator functions, so we use this instead.
    memset( const_cast<char*>( conn_str.c_str()), 0, conn_str.size() );
    conn_str.clear();

    CHECK_SQL_ERROR( r, conn, _FN_, NULL, 
        SQLFreeHandle( conn->ctx.handle_type, conn->ctx.handle ); conn->ctx.handle = SQL_NULL_HANDLE; RETURN_FALSE );
    CHECK_SQL_WARNING( r, conn, _FN_, NULL );

    zr = ZEND_REGISTER_RESOURCE( return_value, conn, sqlsrv_conn::descriptor );
    if( zr == FAILURE ) {
        SQLFreeHandle( conn->ctx.handle_type, conn->ctx.handle );
        conn->ctx.handle = SQL_NULL_HANDLE;
        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_REGISTER_RESOURCE TSRMLS_CC, "connection" );
        RETURN_FALSE;
    }
    conn->stmts = stmts;
    stmts.transferred();
    conn.transferred();    
}


// sqlsrv_begin_transaction( resource $conn )
//
// Begins a transaction on a specified connection. The current transaction
// includes all statements on the specified connection that were executed after
// the call to sqlsrv_begin_transaction and before any calls to sqlsrv_rollback
// or sqlsrv_commit.
//
// The SQL Server 2005 Driver for PHP is in auto-commit mode by default. This
// means that all queries are automatically committed upon success unless they
// have been designated as part of an explicit transaction by using
// sqlsrv_begin_transaction.
// 
// If sqlsrv_begin_transaction is called after a transaction has already been
// initiated on the connection but not completed by calling either sqlsrv_commit
// or sqlsrv_rollback, the call returns false and an Already in Transaction
// error is added to the error collection.
//
// Parameters
// $conn: The connection with which the transaction is associated.
//
// Return Value
// A Boolean value: true if the transaction was successfully begun. Otherwise, false.

PHP_FUNCTION( sqlsrv_begin_transaction )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    SQLRETURN rc;
    sqlsrv_conn* conn = NULL;

    DECL_FUNC_NAME( "sqlsrv_begin_transaction" );
    LOG_FUNCTION;

    PROCESS_PARAMS( conn, _FN_, "r" );
    
    CHECK_SQL_ERROR_EX( conn->in_transaction == true, conn, _FN_, SQLSRV_ERROR_ALREADY_IN_TXN, RETURN_FALSE );

    rc = SQLSetConnectAttr( conn->ctx.handle, SQL_ATTR_AUTOCOMMIT, reinterpret_cast<SQLPOINTER>( SQL_AUTOCOMMIT_OFF ), SQL_IS_UINTEGER );
    CHECK_SQL_ERROR( rc, conn, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( rc, conn, _FN_, NULL );

    conn->in_transaction = true;

    RETURN_TRUE;
}


PHP_FUNCTION( sqlsrv_client_info )
{
    SQLRETURN rc;
    int zr = SUCCESS;
    sqlsrv_conn* conn = NULL;
    zval* client_info = NULL;
    SQLSMALLINT info_len = 0;
    emalloc_auto_ptr<char> buffer;
    emalloc_auto_ptr<char> ver;
    DWORD ver_size = (~0U);
    DWORD winRC = S_OK; 
    DWORD place_holder = 0;
    UINT unused = 0;
    VS_FIXEDFILEINFO* ver_info = NULL;
    DWORD_PTR args[ VERSION_SUBVERSIONS ];

    DECL_FUNC_NAME( "sqlsrv_client_info" );
    LOG_FUNCTION;

    PROCESS_PARAMS( conn, _FN_, "r" );

    zr = array_init( return_value );
    CHECK_ZEND_ERROR( zr, NULL, RETURN_FALSE );
    
    buffer = static_cast<char*>( emalloc( INFO_BUFFER_LEN ));
    rc = SQLGetInfo( conn->ctx.handle, SQL_DRIVER_NAME, buffer,  INFO_BUFFER_LEN, &info_len );
    CHECK_SQL_ERROR( rc, conn, _FN_, NULL, RETURN_FALSE );
    MAKE_STD_ZVAL( client_info );
    ZVAL_STRINGL( client_info, buffer, info_len, 0 );
    zr = add_assoc_zval( return_value, "DriverDllName", client_info );
    CHECK_ZEND_ERROR( zr, NULL, RETURN_FALSE );
    buffer.transferred();
    
    buffer = static_cast<char*>( emalloc( INFO_BUFFER_LEN ));
    rc = SQLGetInfo( conn->ctx.handle, SQL_DRIVER_ODBC_VER, buffer,  INFO_BUFFER_LEN, &info_len );
    CHECK_SQL_ERROR( rc, conn, _FN_, NULL, RETURN_FALSE );
    MAKE_STD_ZVAL( client_info );
    ZVAL_STRINGL( client_info, buffer, info_len, 0 );
    zr = add_assoc_zval( return_value, "DriverODBCVer", client_info );
    CHECK_ZEND_ERROR( zr, NULL, RETURN_FALSE );
    buffer.transferred();

    buffer = static_cast<char*>( emalloc( INFO_BUFFER_LEN ));
    rc = SQLGetInfo( conn->ctx.handle, SQL_DRIVER_VER, buffer,  INFO_BUFFER_LEN, &info_len );
    CHECK_SQL_ERROR( rc, conn, _FN_, NULL, RETURN_FALSE );
    MAKE_STD_ZVAL( client_info );
    ZVAL_STRINGL( client_info, buffer, info_len, 0 );
    zr = add_assoc_zval( return_value, "DriverVer", client_info );
    CHECK_ZEND_ERROR( zr, NULL, RETURN_FALSE );
    buffer.transferred();

    buffer = static_cast<char*>( emalloc( MAX_PATH + 1 ));
    winRC = GetModuleFileNameEx( GetCurrentProcess(), g_sqlsrv_hmodule, buffer, MAX_PATH );
    CHECK_SQL_ERROR_EX( winRC == 0, conn, _FN_, SQLSRV_ERROR_FILE_VERSION, RETURN_FALSE );
    ver_size = GetFileVersionInfoSize( buffer, &place_holder );
    CHECK_SQL_ERROR_EX( ver_size == 0, conn, _FN_, SQLSRV_ERROR_FILE_VERSION, RETURN_FALSE );
    ver = static_cast<char*>( emalloc( ver_size ) );
    winRC = GetFileVersionInfo( buffer, 0, ver_size, ver );
    CHECK_SQL_ERROR_EX( winRC == FALSE, conn, _FN_, SQLSRV_ERROR_FILE_VERSION, RETURN_FALSE );
    winRC = VerQueryValue( ver, "\\", reinterpret_cast<LPVOID*>( &ver_info ), &unused );
    CHECK_SQL_ERROR_EX( winRC == FALSE, conn, _FN_, SQLSRV_ERROR_FILE_VERSION, RETURN_FALSE );
    args[0] = ver_info->dwFileVersionMS >> 16;
    args[1] = ver_info->dwFileVersionMS & 0xffff;
    args[2] = ver_info->dwFileVersionLS >> 16;
    args[3] = ver_info->dwFileVersionLS & 0xffff;
    winRC = FormatMessage( FORMAT_MESSAGE_FROM_STRING | FORMAT_MESSAGE_ARGUMENT_ARRAY, "%1!d!.%2!d!.%3!d!.%4!d!", 0, 0, buffer, MAX_PATH, (va_list*) args );
    CHECK_SQL_ERROR_EX( winRC == 0, conn, _FN_, SQLSRV_ERROR_FILE_VERSION, RETURN_FALSE );
    MAKE_STD_ZVAL( client_info );
    ZVAL_STRING( client_info, buffer, 0 );
    zr = add_assoc_zval( return_value, "ExtensionVer", client_info );
    CHECK_ZEND_ERROR( zr, NULL, RETURN_FALSE );
    buffer.transferred();
}


// sqlsrv_close( resource $conn )
// Closes the specified connection and releases associated resources.
//
// Parameters
// $conn: The connection to be closed.  Null is a valid value parameter for this
// parameter. This allows the function to be called multiple times in a
// script. For example, if you close a connection in an error condition and
// close it again at the end of the script, the second call to sqlsrv_close will
// return true because the first call to sqlsrv_close (in the error condition)
// sets the connection resource to null.
//
// Return Value
// The Boolean value true unless the function is called with an invalid
// parameter. If the function is called with an invalid parameter, false is
// returned.

PHP_FUNCTION( sqlsrv_close )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    zval* conn_r;
    sqlsrv_conn* conn = NULL;

    RETVAL_TRUE;

    DECL_FUNC_NAME( "sqlsrv_close" );
    LOG_FUNCTION;

    full_mem_check(MEMCHECK_SILENT);
    reset_errors( TSRMLS_C );
    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "r", &conn_r ) == FAILURE ) {

        if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "z", &conn_r ) == FAILURE ) {
            handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
            RETURN_FALSE;
        }
        if( Z_TYPE_P( conn_r ) == IS_NULL ) {
            RETURN_TRUE;
        }
        else {
            handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
            RETURN_FALSE;
        }
    }

    conn = static_cast<sqlsrv_conn*>( zend_fetch_resource( &conn_r TSRMLS_CC, -1, "sqlsrv_conn", NULL, 1, sqlsrv_conn::descriptor ));
    if( conn == NULL ) {
        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
        RETURN_FALSE;
    }

    sqlsrv_conn_common_close( conn, _FN_, true TSRMLS_CC );
    
    // cause any variables still holding a reference to this to be invalid so they cause
    // an error when passed to a sqlsrv function.  There's nothing we can do if the 
    // removal fails, so we just log it and move on.
    int zr = zend_hash_index_del( &EG( regular_list ), Z_RESVAL_P( conn_r ));
    if( zr == FAILURE ) {
        LOG( SEV_ERROR, LOG_CONN, "Failed to remove connection resource %d", Z_RESVAL_P( conn_r ));
    }
    ZVAL_NULL( conn_r );

    RETURN_TRUE;
}

// Called when a connection resource is destroyed by the Zend engine.  Set in MINIT

void __cdecl sqlsrv_conn_dtor( zend_rsrc_list_entry *rsrc TSRMLS_DC )
{
    // get the structure
    sqlsrv_conn *conn = static_cast<sqlsrv_conn*>( rsrc->ptr );
    
    DECL_FUNC_NAME( "sqlsrv_conn_dtor" );
    LOG_FUNCTION;
    
    sqlsrv_conn_common_close( conn, _FN_, false TSRMLS_CC );

    efree( conn );
    rsrc->ptr = NULL;
}

 
// sqlsrv_commit( resource $conn )
//
// Commits the current transaction on the specified connection and returns the
// connection to the auto-commit mode. The current transaction includes all
// statements on the specified connection that were executed after the call to
// sqlsrv_begin_transaction and before any calls to sqlsrv_rollback or
// sqlsrv_commit.  The SQL Server 2005 Driver for PHP is in auto-commit mode by
// default. This means that all queries are automatically committed upon success
// unless they have been designated as part of an explicit transaction by using
// sqlsrv_begin_transaction.  If sqlsrv_commit is called on a connection that is
// not in an active transaction and that was initiated with
// sqlsrv_begin_transaction, the call returns false and a Not in Transaction
// error is added to the error collection.
// 
// Parameters
// $conn: The connection on which the transaction is active.
//
// Return Value
// A Boolean value: true if the transaction was successfully committed. Otherwise, false.

PHP_FUNCTION( sqlsrv_commit )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    SQLRETURN rc;
    sqlsrv_conn* conn = NULL;

    DECL_FUNC_NAME( "sqlsrv_commit" );
    LOG_FUNCTION;

    PROCESS_PARAMS( conn, _FN_, "r" );
    
    CHECK_SQL_ERROR_EX( conn->in_transaction == false, conn, _FN_, SQLSRV_ERROR_NOT_IN_TXN, RETURN_FALSE );

    conn->in_transaction = false;

    rc = SQLEndTran( SQL_HANDLE_DBC, conn->ctx.handle, SQL_COMMIT );
    CHECK_SQL_ERROR( rc, conn, _FN_, SQLSRV_ERROR_COMMIT_FAILED, RETURN_FALSE );
    CHECK_SQL_WARNING( rc, conn, _FN_, NULL );
    
    rc = SQLSetConnectAttr( conn->ctx.handle, SQL_ATTR_AUTOCOMMIT, reinterpret_cast<SQLPOINTER>( SQL_AUTOCOMMIT_ON ), SQL_IS_UINTEGER );
    CHECK_SQL_ERROR( rc, conn, _FN_, SQLSRV_ERROR_AUTO_COMMIT_STILL_OFF, RETURN_FALSE );
    CHECK_SQL_WARNING( rc, conn, _FN_, NULL );

    RETURN_TRUE;
}


// sqlsrv_prepare( resource $conn, string $tsql [, array $params [, array $options]])
// 
// Creates a statement resource associated with the specified connection.  A statement
// resource returned by sqlsrv_prepare may be executed multiple times by sqlsrv_execute.
// In between each execution, the values may be updated by changing the value of the
// variables bound.  Output parameters cannot be relied upon to contain their results until
// all rows are processed.
//
// Parameters
// $conn: The connection resource associated with the created statement.
//
// $tsql: The Transact-SQL expression that corresponds to the created statement.
//
// $params [OPTIONAL]: An array of values that correspond to parameters in a
// parameterized query.  Each parameter may be specified as:
//  $value | array($value [, $direction [, $phpType [, $sqlType]]])
// When given just a $value, the direction is default input, and phptype is the value
// given, with the sql type inferred from the php type.
//
// $options [OPTIONAL]: An associative array that sets query properties. The
// table below lists the supported keys and corresponding values:
//   QueryTimeout
//      Sets the query timeout in seconds. By default, the driver will wait
//      indefinitely for results.
//   SendStreamParamsAtExec
//      Configures the driver to send all stream data at execution (true), or to
//      send stream data in chunks (false). By default, the value is set to
//      true. For more information, see sqlsrv_send_stream_data.
//
// Return Value
// A statement resource. If the statement resource cannot be created, false is returned.

PHP_FUNCTION( sqlsrv_prepare )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    sqlsrv_conn* conn = NULL;
    emalloc_auto_ptr<sqlsrv_stmt> stmt;
    char *sql_string = NULL;
    int sql_len = 0;
    zval* params_z = NULL;
    zval* options_z = NULL;
    SQLRETURN r;
    int next_index;

    DECL_FUNC_NAME( "sqlsrv_prepare" );
    LOG_FUNCTION;

    PROCESS_PARAMS( conn, _FN_, "rs|z!a!", &sql_string, &sql_len, &params_z, &options_z );

    stmt = allocate_stmt( conn, options_z, _FN_ TSRMLS_CC );
    if( stmt.get() == NULL ) {
        RETURN_FALSE;
    }

    SQLSRV_STATIC_ASSERT( sizeof(SQLCHAR) == sizeof(char) );
    r = SQLPrepare( stmt->ctx.handle, reinterpret_cast<SQLCHAR*>( sql_string ), SQL_NTS );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, free_odbc_resources( stmt TSRMLS_CC ); RETURN_FALSE );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );

    stmt->prepared = true;

    if( !mark_params_by_reference( &params_z, _FN_ TSRMLS_CC )) {
        RETURN_FALSE;
    }

    stmt->params_z = params_z;

    zval_auto_ptr stmt_z;
    ALLOC_INIT_ZVAL( stmt_z );
    int zr = ZEND_REGISTER_RESOURCE( stmt_z, stmt, sqlsrv_stmt::descriptor );
    if( zr == FAILURE ) {
        free_odbc_resources( stmt TSRMLS_CC );
        free_php_resources( stmt_z TSRMLS_CC );
        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_REGISTER_RESOURCE TSRMLS_CC, "statement" );
        RETURN_FALSE;
    }

    next_index = zend_hash_next_free_element( conn->stmts );
    if( zend_hash_index_update( conn->stmts, next_index, &stmt_z, sizeof( zval* ), NULL ) == FAILURE ) {
        free_odbc_resources( stmt TSRMLS_CC );
        free_php_resources( stmt_z TSRMLS_CC );
        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_ZEND_HASH TSRMLS_CC );
        RETURN_FALSE;
    }
    stmt->conn_index = next_index;
    stmt.transferred();

    zval_ptr_dtor( &return_value );
    *return_value_ptr = stmt_z;
    zval_add_ref( &stmt_z );    // two references to the zval, one returned and another in the connection
    stmt_z.transferred();
}


// sqlsrv_query( resource $conn, string $tsql [, array $params [, array $options]])
// 
// Creates a statement resource associated with the specified connection.  The statement
// is immediately executed and may not be executed again using sqlsrv_execute.
//
// Parameters
// $conn: The connection resource associated with the created statement.
//
// $tsql: The Transact-SQL expression that corresponds to the created statement.
//
// $params [OPTIONAL]: An array of values that correspond to parameters in a
// parameterized query.  Each parameter may be specified as:
//  $value | array($value [, $direction [, $phpType [, $sqlType]]])
// When given just a $value, the direction is default input, and phptype is the value
// given, with the sql type inferred from the php type.
//
// $options [OPTIONAL]: An associative array that sets query properties. The
// table below lists the supported keys and corresponding values:
//   QueryTimeout
//      Sets the query timeout in seconds. By default, the driver will wait
//      indefinitely for results.
//   SendStreamParamsAtExec
//      Configures the driver to send all stream data at execution (true), or to
//      send stream data in chunks (false). By default, the value is set to
//      true. For more information, see sqlsrv_send_stream_data.
//
// Return Value
// A statement resource. If the statement resource cannot be created, false is returned.

PHP_FUNCTION( sqlsrv_query )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    sqlsrv_conn* conn = NULL;
    emalloc_auto_ptr<sqlsrv_stmt> stmt;
    SQLCHAR *sql_string = NULL;
    int sql_len = 0;
    zval* params_z = NULL;
    zval* options_z = NULL;
    bool executed = false;
    int next_index = 0;

    DECL_FUNC_NAME( "sqlsrv_query" );
    LOG_FUNCTION;

    PROCESS_PARAMS( conn, _FN_, "rs|z!a!", &sql_string, &sql_len, &params_z, &options_z );

    stmt = allocate_stmt( conn, options_z, _FN_ TSRMLS_CC );
    if( stmt.get() == NULL ) {
        RETURN_FALSE;
    }

    if( !mark_params_by_reference( &params_z, _FN_ TSRMLS_CC )) {
        free_odbc_resources( stmt TSRMLS_CC );
        RETURN_FALSE;
    }

    stmt->params_z = params_z;

    executed = sqlsrv_stmt_common_execute( stmt, sql_string, sql_len, true, _FN_ TSRMLS_CC );

    if( !executed ) {
        free_odbc_resources( stmt TSRMLS_CC );
        RETURN_FALSE;
    }

    zval_auto_ptr stmt_z;
    ALLOC_INIT_ZVAL( stmt_z );
    int zr = ZEND_REGISTER_RESOURCE( stmt_z, stmt, sqlsrv_stmt::descriptor );
    if( zr == FAILURE ) {
        free_odbc_resources( stmt TSRMLS_CC );
        free_php_resources( stmt_z TSRMLS_CC );
        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_REGISTER_RESOURCE TSRMLS_CC, "statement" );
        RETURN_FALSE;
    }

    next_index = zend_hash_next_free_element( conn->stmts );
    if( zend_hash_index_update( conn->stmts, next_index, &stmt_z, sizeof( zval* ), NULL ) == FAILURE ) {
        free_odbc_resources( stmt TSRMLS_CC );
        free_php_resources( stmt_z TSRMLS_CC );
        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_ZEND_HASH TSRMLS_CC );
        RETURN_FALSE;
    }
    stmt->conn_index = next_index;
    stmt.transferred();

    zval_ptr_dtor( &return_value );
    *return_value_ptr = stmt_z;
    zval_add_ref( &stmt_z );    // two references to the zval, one returned and another in the connection
    stmt_z.transferred();
}


// sqlsrv_rollback( resource $conn )
//
// Rolls back the current transaction on the specified connection and returns
// the connection to the auto-commit mode. The current transaction includes all
// statements on the specified connection that were executed after the call to
// sqlsrv_begin_transaction and before any calls to sqlsrv_rollback or
// sqlsrv_commit.
// The SQL Server 2005 Driver for PHP is in auto-commit mode by default. This
// means that all queries are automatically committed upon success unless they
// have been designated as part of an explicit transaction by using
// sqlsrv_begin_transaction.
//
// If sqlsrv_rollback is called on a connection that is not in an active
// transaction that was initiated with sqlsrv_begin_transaction, the call
// returns false and a Not in Transaction error is added to the error
// collection.
// 
// Parameters
// $conn: The connection on which the transaction is active.
//
// Return Value
// A Boolean value: true if the transaction was successfully rolled back. Otherwise, false.

PHP_FUNCTION( sqlsrv_rollback )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    SQLRETURN rc;
    sqlsrv_conn* conn = NULL;

    DECL_FUNC_NAME( "sqlsrv_rollback" );
    LOG_FUNCTION;

    PROCESS_PARAMS( conn, _FN_, "r" );
    
    CHECK_SQL_ERROR_EX( conn->in_transaction == false, conn, _FN_, SQLSRV_ERROR_NOT_IN_TXN, RETURN_FALSE );

    conn->in_transaction = false;

    rc = SQLEndTran( SQL_HANDLE_DBC, conn->ctx.handle, SQL_ROLLBACK );
    CHECK_SQL_ERROR( rc, conn, _FN_, SQLSRV_ERROR_ROLLBACK_FAILED, RETURN_FALSE );
    CHECK_SQL_WARNING( rc, conn, _FN_, NULL );
    
    rc = SQLSetConnectAttr( conn->ctx.handle, SQL_ATTR_AUTOCOMMIT, reinterpret_cast<SQLPOINTER>( SQL_AUTOCOMMIT_ON ), SQL_IS_UINTEGER );
    CHECK_SQL_ERROR( rc, conn, _FN_, SQLSRV_ERROR_AUTO_COMMIT_STILL_OFF, RETURN_FALSE );
    CHECK_SQL_WARNING( rc, conn, _FN_, NULL );

    RETURN_TRUE;
}



// sqlsrv_server_info( resource $conn )
// 
// Returns information about the server.
// 
// Parameters
// $conn: The connection resource by which the client and server are connected.
//
// Return Value
// An associative array with the following keys: 
//  CurrentDatabase
//      The database currently being targeted.
//  SQLServerVersion
//      The version of SQL Server.
//  SQLServerName
//      The name of the server.

PHP_FUNCTION( sqlsrv_server_info )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    SQLRETURN r;
    int zr = SUCCESS;
    sqlsrv_conn* conn = NULL;
    zval* server_info;
    emalloc_auto_ptr<char> p;
    SQLSMALLINT info_len;

    DECL_FUNC_NAME( "sqlsrv_server_info" );
    LOG_FUNCTION;

    PROCESS_PARAMS( conn, _FN_, "r" );

    zr = array_init( return_value );
    CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_SERVER_INFO, RETURN_FALSE );
    
    p = static_cast<char*>( emalloc( INFO_BUFFER_LEN ));
    r = SQLGetInfo( conn->ctx.handle, SQL_DATABASE_NAME, p, INFO_BUFFER_LEN, &info_len );
    CHECK_SQL_ERROR( r, conn, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, conn, _FN_, NULL );
    MAKE_STD_ZVAL( server_info );
    ZVAL_STRINGL( server_info, p, info_len, 0 );
    zr = add_assoc_zval( return_value, "CurrentDatabase", server_info );
    CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_SERVER_INFO, RETURN_FALSE );
    CHECK_SQL_WARNING( r, conn, _FN_, NULL );
    p.transferred();

    p = static_cast<char*>( emalloc( INFO_BUFFER_LEN ));
    r = SQLGetInfo( conn->ctx.handle, SQL_DBMS_VER, p,  INFO_BUFFER_LEN, &info_len );
    CHECK_SQL_ERROR( r, conn, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, conn, _FN_, NULL );
    MAKE_STD_ZVAL( server_info );
    ZVAL_STRINGL( server_info, p, info_len, 0 );
    zr = add_assoc_zval( return_value, "SQLServerVersion", server_info );
    CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_SERVER_INFO, RETURN_FALSE );
    p.transferred();

    p = static_cast<char*>( emalloc( INFO_BUFFER_LEN ));
    r = SQLGetInfo( conn->ctx.handle, SQL_SERVER_NAME, p,  INFO_BUFFER_LEN, &info_len );
    CHECK_SQL_ERROR( r, conn, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, conn, _FN_, NULL );
    MAKE_STD_ZVAL( server_info );
    ZVAL_STRINGL( server_info, p, info_len, 0 );
    zr = add_assoc_zval( return_value, "SQLServerName", server_info );
    CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_SERVER_INFO, RETURN_FALSE );
    p.transferred();
}

// internal connection functions

namespace {

// common close, used by close and dtor
void sqlsrv_conn_common_close( __inout sqlsrv_conn* conn, const char* _FN_, bool check_errors TSRMLS_DC )
{
    SQLRETURN r = SQL_SUCCESS;

    if( conn->ctx.handle == NULL )
        return;

    // close the statements and roll back transactions
    if( conn->stmts ) {

        zend_hash_destroy( conn->stmts );
        FREE_HASHTABLE( conn->stmts );
        conn->stmts = NULL;
    }
    else {
        DIE( "Connection doesn't contain a statement array!" );
    }

    // rollback any transaction in progress (we don't care about the return result)
    SQLEndTran( SQL_HANDLE_DBC, conn->ctx.handle, SQL_ROLLBACK );

    // disconnect from the server
    r = SQLDisconnect( conn->ctx.handle );
    if( check_errors ) { CHECK_SQL_ERROR( r, conn, _FN_, NULL, NULL, 1==1 ); }

    // free the connection handle
    r = SQLFreeHandle( SQL_HANDLE_DBC, conn->ctx.handle );
    if( check_errors ) { CHECK_SQL_ERROR( r, conn, _FN_, NULL, 1 == 1 ); }
    CHECK_SQL_WARNING( r, conn, _FN_, NULL );
    conn->ctx.handle = NULL;
}

#define NO_ATTRIBUTE -1

// type of connection attributes
enum CONN_ATTR_TYPE {
    CONN_ATTR_INT,
    CONN_ATTR_BOOL,
    CONN_ATTR_STRING,
};

// list of valid attributes used by validate_connection_attribute below
struct connection_attribute {
    const char *        name;
    int                 name_len;
    enum CONN_ATTR_TYPE value_type;
    int                 attr;
    bool                add;
} conn_attrs[] = {
    { ConnOptions::APP, sizeof( ConnOptions::APP ), CONN_ATTR_STRING, NO_ATTRIBUTE, true },
    { ConnOptions::ConnectionPooling, sizeof( ConnOptions::ConnectionPooling ), CONN_ATTR_BOOL, NO_ATTRIBUTE, false },
    { ConnOptions::Database, sizeof( ConnOptions::Database ), CONN_ATTR_STRING, NO_ATTRIBUTE, true },
    { ConnOptions::Encrypt, sizeof( ConnOptions::Encrypt ), CONN_ATTR_BOOL, NO_ATTRIBUTE, true },
    { ConnOptions::Failover_Partner, sizeof( ConnOptions::Failover_Partner ), CONN_ATTR_STRING, NO_ATTRIBUTE, true },
    { ConnOptions::LoginTimeout, sizeof( ConnOptions::LoginTimeout ), CONN_ATTR_INT, SQL_ATTR_LOGIN_TIMEOUT, true },
    { ConnOptions::PWD, sizeof( ConnOptions::PWD ), CONN_ATTR_STRING, NO_ATTRIBUTE, true },
    { ConnOptions::QuotedId, sizeof( ConnOptions::QuotedId ), CONN_ATTR_BOOL, NO_ATTRIBUTE, true },
    { ConnOptions::TraceFile, sizeof( ConnOptions::TraceFile ), CONN_ATTR_STRING, SQL_ATTR_TRACEFILE, true },
    { ConnOptions::TraceOn, sizeof( ConnOptions::TraceOn ), CONN_ATTR_BOOL, SQL_ATTR_TRACE, true },
    { ConnOptions::TrustServerCertificate, sizeof( ConnOptions::TrustServerCertificate ), CONN_ATTR_BOOL, NO_ATTRIBUTE, true },
    { ConnOptions::TransactionIsolation, sizeof( ConnOptions::TransactionIsolation ), CONN_ATTR_INT, SQL_COPT_SS_TXN_ISOLATION, true },
    { ConnOptions::UID, sizeof( ConnOptions::UID ), CONN_ATTR_STRING, NO_ATTRIBUTE, true },
    { ConnOptions::WSID, sizeof( ConnOptions::WSID ), CONN_ATTR_STRING, NO_ATTRIBUTE, true }
};

// return structure from validate
typedef
struct _attr_return {
    bool success;   // if the attribute was validated
    // if it's a connection attribute rather than a connection string keyword, this is set to the attribute (SQL_ATTR_*)
    // or set to NO_ATTRIBUTE if it's not a connection attribute
    int  attr;
    // the value of the attribute if it's a connection attribute rather than a connection string keyword
    int  value;             
    char* str_value;        // connection string keyword if that's what it is
    unsigned int str_len;   // length of the connection string keyword (save ourselves a strlen)
    bool add;               // see build_connect_string_and_attr for this field's use
}
attr_return;


// validates a single key/value pair from the attributes given to sqlsrv_connect.
// to validate means to verify that it is a legal key from the list of keys in conn_attrs (above)
// and to verify the type.
// string attributes are scanned to make sure that all } are properly escaped as }}.

const attr_return validate_connection_attribute( sqlsrv_conn const* conn, const char* key, int key_len, zval const* value_z TSRMLS_DC )
{
    int attr_idx = 0;
    attr_return ret;

    // initialize our default return values
    ret.success = false;
    ret.attr = NO_ATTRIBUTE;
    ret.value = 0;
    ret.str_value = NULL;
    ret.str_len = 0;

    for( attr_idx = 0; attr_idx < ( sizeof( conn_attrs ) / sizeof( conn_attrs[0] )); ++attr_idx ) {
        
        if( key_len == conn_attrs[ attr_idx ].name_len && !stricmp( key, conn_attrs[ attr_idx ].name )) {

            switch( conn_attrs[ attr_idx ].value_type ) {

                case CONN_ATTR_BOOL:                        
                    // bool attributes can be either strings to be appended to the connection string
                    // as yes or no or integral connection attributes.  This will have to be reworked
                    // if we ever introduce a boolean connection option that maps to a string connection
                    // attribute.
                    ret.success = true;
                    ret.attr = conn_attrs[ attr_idx ].attr;
                    ret.add = conn_attrs[ attr_idx ].add;
                    // here we short circuit the ConnectionPooling option because it was already handled
                    if( conn_attrs[ attr_idx ].name == ConnOptions::ConnectionPooling ) {
                        return ret;
                    }
                    if( zend_is_true( const_cast<zval*>( value_z ))) {
                        if( ret.attr == NO_ATTRIBUTE ) {
                            ret.str_value = estrdup( "yes" );      // for connection strings
                            ret.str_len = 3;
                        }
                        else {
                            ret.value = true;           // for connection attributes
                        }
                    }
                    else {
                        if( ret.attr == NO_ATTRIBUTE ) {
                            ret.str_value = estrdup( "no" );  // for connection strings
                            ret.str_len = 2;
                        }
                        else {
                            ret.value = false;      // for connection attributes
                        }
                    }
                    break;
                case CONN_ATTR_INT:
                {
                    CHECK_SQL_ERROR_EX( Z_TYPE_P( value_z ) != IS_LONG, conn, "sqlsrv_connect", SQLSRV_ERROR_INVALID_OPTION, ret.success = false; return ret; );
                    ret.value = Z_LVAL_P( value_z );
                    ret.attr = conn_attrs[ attr_idx ].attr;
                    ret.success = true;
                    ret.add = conn_attrs[ attr_idx ].add;
                    return ret;
                }
                case CONN_ATTR_STRING:
                {
                    CHECK_SQL_ERROR_EX( Z_TYPE_P( value_z ) != IS_STRING, conn, "sqlsrv_connect", SQLSRV_ERROR_INVALID_OPTION, ret.success = false; return ret; );
                    char* value = Z_STRVAL_P( value_z );
                    int value_len = Z_STRLEN_P( value_z );
                    ret.success = true;
                    // if the value is already quoted, then only analyse the part inside the quotes and return it as 
                    // unquoted since we quote it when adding it to the connection string.
                    if( value_len > 0 && value[0] == '{' && value[ value_len - 1 ] == '}' ) {
                        ++value;
                        --value_len;
                        value = estrndup( value, value_len );
                        value[ value_len - 1 ] = '\0';
                    }
                    // duplicate the string so when we free it, it won't be a user string we're freeing
                    else {
                        value = estrndup( value, value_len );
                    }
                    ret.attr = conn_attrs[ attr_idx ].attr;
                    ret.add = conn_attrs[ attr_idx ].add;
                    ret.str_value = value;
                    ret.str_len = value_len;
                    // check to make sure that all right braces are escaped
                    int i = 0;
                    while( ( value[i] != '}' || ( value[i] == '}' && value[i+1] == '}' )) && i < value_len ) {
                        // skip both braces
                        if( value[i] == '}' )
                            ++i;
                        ++i;
                    }
                    if( value[i] == '}' ) {
                        handle_error( &conn->ctx, LOG_CONN, "sqlsrv_connect", SQLSRV_ERROR_CONNECT_BRACES_NOT_ESCAPED TSRMLS_CC, key );
                        efree( value );
                        ret.str_value = NULL;
                        ret.success = false;
                        return ret;
                    }
                    break;
                }
            }

            return ret;
         }
    }
    
    handle_error( &conn->ctx, LOG_CONN, "sqlsrv_connect", SQLSRV_ERROR_INVALID_OPTION TSRMLS_CC, key );
    return ret;
}


// says what it does, and does what it says
// rather than have attributes and connection strings as ODBC does, we unify them into a hash table
// passed to the connection, and then break them out ourselves and either set attributes or put the
// option in the connection string.

SQLRETURN build_connection_string_and_set_conn_attr( sqlsrv_conn const* conn, const char* server, zval const* options, 
                                                     __inout std::string& connection_string TSRMLS_DC )
{
    bool credentials_mentioned = false;
    attr_return ret;
    int zr = SUCCESS;

    DECL_FUNC_NAME( "sqlsrv_connect" );

    // put the driver and server as the first components of the connection string
    connection_string = "Driver={SQL Native Client};Server=";
    connection_string += server;
    connection_string += ";";

    // if no options were given, then we make integrated auth and MARS the defaults and return immediately.
    if( options == NULL ) {
        connection_string += "Trusted_Connection={Yes};Mars_Connection={Yes}";
        return SQL_SUCCESS;
    }

    if( Z_TYPE_P( options ) != IS_ARRAY ) { DIE( "Passed an invalid type for the options array" ); }
    
    HashTable* oht = Z_ARRVAL_P( options );

    for( zend_hash_internal_pointer_reset( oht );
          zend_hash_has_more_elements( oht ) == SUCCESS;
          zend_hash_move_forward( oht )) {
              
        int type;
        char *key;
        unsigned int key_len;
        unsigned long index;
        zval** data;

        type = zend_hash_get_current_key_ex( oht, &key, &key_len, &index, 0, NULL );
        CHECK_SQL_ERROR_EX( type != HASH_KEY_IS_STRING, conn, _FN_, SQLSRV_ERROR_INVALID_CONNECTION_KEY, return SQL_ERROR )

        zr = zend_hash_get_current_data( oht, (void**) &data );
        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, return SQL_ERROR; );

        ret = validate_connection_attribute( conn, key, key_len, *data TSRMLS_CC );
        if( !ret.success ) {
            return SQL_ERROR;
        }
 
        // if a user id is given ,then don't use a trusted connection
        if( !stricmp( key, "UID" )) {
            credentials_mentioned = true;
        }
        
        if( NO_ATTRIBUTE == ret.attr ) {
            // some options are already handled (e.g., ConnectionPooling) so don't add them to the connection string
            if( ret.add ) {
                // if it's not an attribute, then it's a connection string keyword.  Add it quoted.
                connection_string += key;
                connection_string += "={";
                connection_string += ret.str_value;
                connection_string += "};";
            }
        }
        else {
            SQLRETURN r;
            // if it's a string attribute, the str_value member will be the string to set, otherwise the str_value will be null
            // and ret.value will be the integer value to set the attribute to.
            if( ret.str_value == NULL ) {
                r = SQLSetConnectAttr( conn->ctx.handle, ret.attr, reinterpret_cast<SQLPOINTER>( ret.value ), SQL_IS_UINTEGER );
            }
            else {
                r = SQLSetConnectAttr( conn->ctx.handle, ret.attr, reinterpret_cast<SQLPOINTER>( const_cast<char*>( ret.str_value )), ret.str_len );
            }
            CHECK_SQL_ERROR( r, conn, "sqlsrv_connect", NULL, return SQL_ERROR );
            CHECK_SQL_WARNING( r, conn, "sqlsrv_connect", NULL );
        }

        if( ret.str_value != NULL ) {
            efree( ret.str_value );
        }
    }

    // trusted connection is the default if no user id was given.
    if( !credentials_mentioned ) {
        connection_string += "Trusted_Connection={Yes};";
    }
    // always have mars enabled.
    connection_string += "Mars_Connection={Yes};";

    return SQL_SUCCESS;
}


// common code to allocate a statement from either sqlsrv_prepare or sqlsv_query.  Returns either
// a valid sqlsrv_stmt or NULL if an error occurred.

sqlsrv_stmt* allocate_stmt( __in sqlsrv_conn* conn, zval const* options_z, char const* _FN_ TSRMLS_DC )
{
    SQLRETURN r = SQL_SUCCESS;
    emalloc_auto_ptr<sqlsrv_stmt> stmt;
    stmt = static_cast<sqlsrv_stmt*>( emalloc( sizeof( sqlsrv_stmt )));
    emalloc_auto_ptr<char> param_buffer;
    param_buffer = static_cast<char*>( emalloc( PHP_STREAM_BUFFER_SIZE ));

    stmt->ctx.handle_type = SQL_HANDLE_STMT;

    stmt->conn = conn;
    stmt->executed = false;
    stmt->prepared = false;
    stmt->current_parameter = NULL;
    stmt->current_parameter_read = 0;
    stmt->params_z = NULL;
    stmt->param_datetime_buffers = NULL;
    stmt->param_buffer = param_buffer;
    stmt->param_buffer_size = PHP_STREAM_BUFFER_SIZE;
    stmt->send_at_exec = true;
    stmt->conn_index = -1;
    stmt->fetch_fields = NULL;
    stmt->fetch_fields_count = 0;
    stmt->new_result_set();

    stmt->active_stream = NULL;
    r = SQLAllocHandle( SQL_HANDLE_STMT, conn->ctx.handle, &stmt->ctx.handle );
    LOG( SEV_NOTICE, LOG_STMT, "SQLAllocHandle for statement = %1!08x!", stmt->ctx.handle );
    CHECK_SQL_ERROR( r, conn, _FN_, NULL, return NULL; );
    CHECK_SQL_WARNING( r, conn, _FN_, NULL );

    // process the options array given to sqlsrv_prepare or sqlsrv_query.  see those functions
    // for valid values here.
    if( options_z ) {

        for( zend_hash_internal_pointer_reset( Z_ARRVAL_P( options_z ));
             zend_hash_has_more_elements( Z_ARRVAL_P( options_z )) == SUCCESS;
             zend_hash_move_forward( Z_ARRVAL_P( options_z )) ) {

            char *key = NULL;
            unsigned int key_len = 0;
            unsigned long index = 0;
            zval** value_z = NULL;

            int type = zend_hash_get_current_key_ex( Z_ARRVAL_P( options_z ), &key, &key_len, &index, 0, NULL );
            if( type != HASH_KEY_IS_STRING ) {
                std::ostringstream itoa;
                itoa <<  index;
                handle_error( &conn->ctx, LOG_CONN, _FN_, SQLSRV_ERROR_INVALID_OPTION_KEY TSRMLS_CC, itoa.str() );
                SQLFreeHandle( stmt->ctx.handle_type, stmt->ctx.handle );
                return NULL;
            }
 
            int zr = zend_hash_get_current_data( Z_ARRVAL_P( options_z ), (void**) &value_z );
            CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, return NULL );

            if( !stricmp( key, QUERY_TIMEOUT )) {
                if( Z_TYPE_P( *value_z ) != IS_LONG ) {
                    convert_to_string( *value_z );
                    handle_error( &conn->ctx, LOG_CONN, _FN_, SQLSRV_ERROR_INVALID_OPTION_VALUE TSRMLS_CC, Z_STRVAL_PP( value_z ), key );
                    SQLFreeHandle( stmt->ctx.handle_type, stmt->ctx.handle ); 
                    return NULL;
                }
                r = SQLSetStmtAttr( stmt->ctx.handle, SQL_ATTR_QUERY_TIMEOUT, reinterpret_cast<SQLPOINTER>( Z_LVAL_P( *value_z )), SQL_IS_UINTEGER );
                CHECK_SQL_ERROR( r, stmt, _FN_, NULL, SQLFreeHandle( stmt->ctx.handle_type, stmt->ctx.handle ); return NULL; );
                char lock_timeout_sql[ 1024 ];
                int written = sprintf_s( lock_timeout_sql, sizeof( lock_timeout_sql ), "SET LOCK_TIMEOUT %d", Z_LVAL_PP( value_z ) * 1000 );
                if( written == -1 || written == sizeof( lock_timeout_sql )) {
                    DIE( "sprintf_s failed.  Shouldn't ever fail." );
                }
                r = SQLExecDirect( stmt->ctx.handle, reinterpret_cast<SQLCHAR*>( lock_timeout_sql ), SQL_NTS );
                CHECK_SQL_ERROR( r, stmt, _FN_, NULL, SQLFreeHandle( stmt->ctx.handle_type, stmt->ctx.handle ); return NULL; );
            }
            else if( key_len == ( sizeof( SEND_STREAMS_AT_EXEC )) && !stricmp( key, SEND_STREAMS_AT_EXEC )) {
                stmt->send_at_exec = ( zend_is_true( *value_z )) ? true : false;
            }
            // if didn't match one of the standard options, then the key is an error
            else {
                handle_error( &stmt->ctx, LOG_CONN, _FN_, SQLSRV_ERROR_INVALID_OPTION_KEY TSRMLS_CC, key );
                SQLFreeHandle( stmt->ctx.handle_type, stmt->ctx.handle );
                return NULL;
            }
        }
        zend_hash_internal_pointer_end( Z_ARRVAL_P( options_z ));
    }

    sqlsrv_stmt* ret = stmt;
    stmt.transferred();
    param_buffer.transferred();

    return ret;
}


// mark parameters passed into sqlsrv_prepare and sqlsrv_query as reference parameters so that
// they may be updated later in the script and subsequent sqlsrv_execute calls will use the
// new values.

bool mark_params_by_reference( __inout zval** params_zz, char const* _FN_ TSRMLS_DC )
{
    // if it's a NULL pointer, just return with no errors
    if( !*params_zz )
        return true;    // continue with no errors

    // if it's not an array, then return an error
    if( Z_TYPE_PP( params_zz ) != IS_ARRAY ) {
        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
        return false;
    }

    // process the parameter array
    zval_add_ref( params_zz );
    HashTable* params_ht = Z_ARRVAL_PP( params_zz );
    zval** param = NULL;
    int zr = SUCCESS;

    // This code turns parameters into references.  Since the function declaration cannot 
    // pass array elements as references (without requiring & in front of each variable),
    // we have to set the reference in each of the zvals ourselves.  In the event of a 
    // parameter array (or sub array if you will) being passed in, we set the zval of the 
    // parameter array's first element.
    int i = 0;
    for( i = 1, zend_hash_internal_pointer_reset( params_ht );
         zend_hash_has_more_elements( params_ht ) == SUCCESS;
         zend_hash_move_forward( params_ht ), ++i ) {


        zr = zend_hash_get_current_data_ex( params_ht, reinterpret_cast<void**>( &param ), NULL );
        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, zval_ptr_dtor( params_zz ); return false; );

        // if it's a sole variable
        if( Z_TYPE_PP( param ) != IS_ARRAY ) {
            (*param)->is_ref = 1;   // mark it as a reference
        }
        // else mark [0] as a reference
        else {
            zval** var = NULL;
            zr = zend_hash_index_find( Z_ARRVAL_PP( param ), 0, reinterpret_cast<void**>( &var ));
            if( zr == FAILURE ) {
                handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_VAR_REQUIRED TSRMLS_CC, i );
                zval_ptr_dtor( params_zz );
                return false;
            }
            (*var)->is_ref = 1;
        }
    }

    return true;
}

}   // namespace

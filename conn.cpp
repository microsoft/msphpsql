//----------------------------------------------------------------------------------------------------------------------------------
// File: conn.cpp
//
// Copyright (c) Microsoft Corporation.  All rights reserved.
//
// Contents: Routines that use connection handles
// 
// License: This software is released under the Microsoft Public License.  A copy of the license agreement 
//          may be found online at http://www.codeplex.com/SQLSRVPHP/license.
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
// statement option for setting a query timeout
const char QUERY_TIMEOUT[] = "QueryTimeout";
// statement option for sending streams at execute time
const char SEND_STREAMS_AT_EXEC[] = "SendStreamParamsAtExec";
// query options for cursor types
const char QUERY_OPTION_SCROLLABLE_STATIC[] = "static";
const char QUERY_OPTION_SCROLLABLE_DYNAMIC[] = "dynamic";
const char QUERY_OPTION_SCROLLABLE_KEYSET[] = "keyset";
const char QUERY_OPTION_SCROLLABLE_FORWARD[] = "forward";
// statment option to create a scrollable result set
const char SCROLLABLE[] = "Scrollable";
// length of buffer used to retrieve information for client and server info buffers
const int INFO_BUFFER_LEN = 256;
// number of segments in a version resource
const int VERSION_SUBVERSIONS = 4;

// processor architectures
const char* PROCESSOR_ARCH[] = { "x86", "x64", "ia64" };

// *** internal function prototypes ***
sqlsrv_stmt* allocate_stmt( sqlsrv_conn* conn, zval const* options_z, char const* _FN_ TSRMLS_DC );
SQLRETURN build_connection_string_and_set_conn_attr( sqlsrv_conn* conn, const char* server, zval const* options, 
                                                     __inout std::string& connection_string TSRMLS_DC );
SQLRETURN determine_server_version( sqlsrv_conn* conn, const char* _FN_ TSRMLS_DC );
const char* get_processor_arch( void );
bool mark_params_by_reference( zval** params_zz, char const* _FN_ TSRMLS_DC );
void sqlsrv_conn_close_stmts( sqlsrv_conn* conn TSRMLS_DC );

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

// most of these strings are the same for both the sqlsrv_connect connection option
// and the name put into the connection string. MARS is the only one that's different.
const char APP[] = "APP";
const char CharacterSet[] = "CharacterSet";
const char ConnectionPooling[] = "ConnectionPooling";
const char Database[] = "Database";
const char DateAsString[] = "ReturnDatesAsStrings";
const char Encrypt[] = "Encrypt";
const char Failover_Partner[] = "Failover_Partner";
const char LoginTimeout[] = "LoginTimeout";
const char MARS_Option[] = "MultipleActiveResultSets";
const char MARS_ODBC[] = "MARS_Connection";
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
    sqlsrv_malloc_auto_ptr<sqlsrv_conn> conn;
    conn = new ( sqlsrv_malloc( sizeof( sqlsrv_conn ))) sqlsrv_conn;
    hash_auto_ptr stmts;
    ALLOC_HASHTABLE( stmts );

    zr = zend_hash_init( stmts, 10, NULL /*hash function*/, NULL /* dtor */, 0 /* persistent */ );
    if( zr == FAILURE ) {
        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_ZEND_HASH TSRMLS_CC );
        RETURN_FALSE;
    }

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
        LOG( SEV_ERROR, LOG_CONN, "C++ exception returned: %1!s!", ex.what() );
        LOG( SEV_ERROR, LOG_CONN, "C++ memory allocation failure building the connection string." );
        DIE( "C++ memory allocation failure building the connection string." );
    }
    catch( std::out_of_range const& ex ) {
        memset( const_cast<char*>( conn_str.c_str()), 0, conn_str.size() );
        conn_str.clear();
        LOG( SEV_ERROR, LOG_CONN, "C++ exception returned: %1!s!", ex.what() );
        RETURN_FALSE;
    }
    catch( std::length_error const& ex ) {
        memset( const_cast<char*>( conn_str.c_str()), 0, conn_str.size() );
        conn_str.clear();
        LOG( SEV_ERROR, LOG_CONN, "C++ exception returned: %1!s!", ex.what() );
        SQLFreeHandle( conn->ctx.handle_type, conn->ctx.handle );
        conn->ctx.handle = SQL_NULL_HANDLE;
        RETURN_FALSE;
    }

    // convert our connection string to UTF-16 before connecting with SQLDriverConnnectW
    wchar_t* wconn_string;
    unsigned int wconn_len = (conn_str.length() + 1) * sizeof( wchar_t );
    wconn_string = utf16_string_from_mbcs_string( conn->default_encoding, conn_str.c_str(), conn_str.length(), &wconn_len );
    if( wconn_string == NULL ) {
        handle_error( &conn->ctx, LOG_CONN, _FN_, SQLSRV_ERROR_CONNECT_STRING_ENCODING_TRANSLATE TSRMLS_CC, get_last_error_message() );
        SQLFreeHandle( conn->ctx.handle_type, conn->ctx.handle );
        conn->ctx.handle = SQL_NULL_HANDLE;
        RETURN_FALSE;
    }
    
    r = SQLDriverConnectW( conn->ctx.handle, NULL, reinterpret_cast<SQLWCHAR*>( wconn_string ),
                           static_cast<SQLSMALLINT>( wconn_len ), NULL, 
                          0, &output_conn_size, SQL_DRIVER_NOPROMPT );
    // clear the connection string from memory to remove sensitive data (such as a password).
    memset( const_cast<char*>( conn_str.c_str()), 0, conn_str.size() );
    memset( wconn_string, 0, wconn_len * sizeof( wchar_t )); // wconn_len is the number of characters, not bytes
    conn_str.clear();
    sqlsrv_free( wconn_string );

    if( !SQL_SUCCEEDED( r )) {
        SQLCHAR state[ SQL_SQLSTATE_BUFSIZE ];
        SQLSMALLINT len;
        SQLRETURN r = SQLGetDiagField( SQL_HANDLE_DBC, conn->ctx.handle, 1, SQL_DIAG_SQLSTATE, state, SQL_SQLSTATE_BUFSIZE, &len );
        // if it's a IM002, meaning that the driver is not installed
        if( SQL_SUCCEEDED( r ) && state[0] == 'I' && state[1] == 'M' && state[2] == '0' && state[3] == '0' && state[4] == '2' ) {
            const char* arch = get_processor_arch();
            handle_error( &conn->ctx, LOG_CONN, _FN_, SQLSRV_ERROR_DRIVER_NOT_INSTALLED TSRMLS_CC, arch );
            SQLFreeHandle( conn->ctx.handle_type, conn->ctx.handle );
            conn->ctx.handle = SQL_NULL_HANDLE;
            RETURN_FALSE;
        }
    }
    CHECK_SQL_ERROR( r, conn, _FN_, NULL, 
        SQLFreeHandle( conn->ctx.handle_type, conn->ctx.handle ); conn->ctx.handle = SQL_NULL_HANDLE; RETURN_FALSE );
    CHECK_SQL_WARNING( r, conn, _FN_, NULL );

    // determine the version of the server we're connected to.  The server version is left in the 
    // connection upon return.
    r = determine_server_version( conn, _FN_  TSRMLS_CC );
    if( !SQL_SUCCEEDED( r )) {
        SQLFreeHandle( conn->ctx.handle_type, conn->ctx.handle );
        conn->ctx.handle = SQL_NULL_HANDLE;
        RETURN_FALSE;
    }

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
// The SQL Server Driver for PHP is in auto-commit mode by default. This
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
    sqlsrv_malloc_auto_ptr<char> buffer;
    sqlsrv_malloc_auto_ptr<char> ver;
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
    
    buffer = static_cast<char*>( sqlsrv_malloc( INFO_BUFFER_LEN ));
    rc = SQLGetInfo( conn->ctx.handle, SQL_DRIVER_NAME, buffer,  INFO_BUFFER_LEN, &info_len );
    CHECK_SQL_ERROR( rc, conn, _FN_, NULL, RETURN_FALSE );
    MAKE_STD_ZVAL( client_info );
    ZVAL_STRINGL( client_info, buffer, info_len, 0 );
    zr = add_assoc_zval( return_value, "DriverDllName", client_info );
    CHECK_ZEND_ERROR( zr, NULL, RETURN_FALSE );
    buffer.transferred();
    
    buffer = static_cast<char*>( sqlsrv_malloc( INFO_BUFFER_LEN ));
    rc = SQLGetInfo( conn->ctx.handle, SQL_DRIVER_ODBC_VER, buffer,  INFO_BUFFER_LEN, &info_len );
    CHECK_SQL_ERROR( rc, conn, _FN_, NULL, RETURN_FALSE );
    MAKE_STD_ZVAL( client_info );
    ZVAL_STRINGL( client_info, buffer, info_len, 0 );
    zr = add_assoc_zval( return_value, "DriverODBCVer", client_info );
    CHECK_ZEND_ERROR( zr, NULL, RETURN_FALSE );
    buffer.transferred();

    buffer = static_cast<char*>( sqlsrv_malloc( INFO_BUFFER_LEN ));
    rc = SQLGetInfo( conn->ctx.handle, SQL_DRIVER_VER, buffer,  INFO_BUFFER_LEN, &info_len );
    CHECK_SQL_ERROR( rc, conn, _FN_, NULL, RETURN_FALSE );
    MAKE_STD_ZVAL( client_info );
    ZVAL_STRINGL( client_info, buffer, info_len, 0 );
    zr = add_assoc_zval( return_value, "DriverVer", client_info );
    CHECK_ZEND_ERROR( zr, NULL, RETURN_FALSE );
    buffer.transferred();

    buffer = static_cast<char*>( sqlsrv_malloc( MAX_PATH + 1 ));
    winRC = GetModuleFileNameEx( GetCurrentProcess(), g_sqlsrv_hmodule, buffer, MAX_PATH );
    CHECK_SQL_ERROR_EX( winRC == 0, conn, _FN_, SQLSRV_ERROR_FILE_VERSION, RETURN_FALSE );
    ver_size = GetFileVersionInfoSize( buffer, &place_holder );
    CHECK_SQL_ERROR_EX( ver_size == 0, conn, _FN_, SQLSRV_ERROR_FILE_VERSION, RETURN_FALSE );
    ver = static_cast<char*>( sqlsrv_malloc( ver_size ) );
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

    // cause any variables still holding a reference to this to be invalid so they cause
    // an error when passed to a sqlsrv function.  There's nothing we can do if the 
    // removal fails, so we just log it and move on.
    int zr = zend_hash_index_del( &EG( regular_list ), Z_RESVAL_P( conn_r ));
    if( zr == FAILURE ) {
        LOG( SEV_ERROR, LOG_CONN, "Failed to remove connection resource %1!d!", Z_RESVAL_P( conn_r ));
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
    
    sqlsrv_conn_close_stmts( conn TSRMLS_CC );

    // rollback any transaction in progress (we don't care about the return result)
    SQLEndTran( SQL_HANDLE_DBC, conn->ctx.handle, SQL_ROLLBACK );

    // disconnect from the server
    SQLRETURN r = SQLDisconnect( conn->ctx.handle );
    if( !SQL_SUCCEEDED( r )) { 
        LOG( SEV_ERROR, LOG_CONN, "Disconnect failed when closing the connection." );
    }

    // free the connection handle
    r = SQLFreeHandle( SQL_HANDLE_DBC, conn->ctx.handle );
    if( !SQL_SUCCEEDED( r )) { 
        LOG( SEV_ERROR, LOG_CONN, "Failed to free the connection handle when destroying the connection resource" );
    }
    conn->ctx.handle = NULL;

    sqlsrv_free( conn );
    rsrc->ptr = NULL;
}

 
// sqlsrv_commit( resource $conn )
//
// Commits the current transaction on the specified connection and returns the
// connection to the auto-commit mode. The current transaction includes all
// statements on the specified connection that were executed after the call to
// sqlsrv_begin_transaction and before any calls to sqlsrv_rollback or
// sqlsrv_commit.  The SQL Server Driver for PHP is in auto-commit mode by
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
    sqlsrv_malloc_auto_ptr<sqlsrv_stmt> stmt;
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
    wchar_t* wsql_string;
    unsigned int wsql_len = 0;
    // if the string is empty, we initialize the fields and skip since an empty string is a 
    // failure case for utf16_string_from_mbcs_string 
    if( sql_len == 0 || ( sql_string[0] == '\0' && sql_len == 1 )) {
        wsql_string = reinterpret_cast<wchar_t*>( sqlsrv_malloc( 1 ));
        wsql_string[0] = '\0';
        wsql_len = 0;
    }
    else {
        wsql_string = utf16_string_from_mbcs_string( stmt->conn->default_encoding, reinterpret_cast<const char*>( sql_string ), sql_len,
                                                     &wsql_len );
        if( wsql_string == NULL ) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_QUERY_STRING_ENCODING_TRANSLATE TSRMLS_CC, get_last_error_message() );
            free_odbc_resources( stmt TSRMLS_CC );
            RETURN_FALSE;
        }
    }
    r = SQLPrepareW( stmt->ctx.handle, reinterpret_cast<SQLWCHAR*>( wsql_string ), wsql_len );
    sqlsrv_free( wsql_string );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, free_odbc_resources( stmt TSRMLS_CC ); RETURN_FALSE );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );

    stmt->prepared = true;

    if( !mark_params_by_reference( &params_z, _FN_ TSRMLS_CC )) {
        free_odbc_resources( stmt TSRMLS_CC );
        RETURN_FALSE;
    }

    stmt->params_z = params_z;

    // register the statement with the PHP runtime
    zval_auto_ptr stmt_z;
    ALLOC_INIT_ZVAL( stmt_z );
    int zr = ZEND_REGISTER_RESOURCE( stmt_z, stmt, sqlsrv_stmt::descriptor );
    if( zr == FAILURE ) {
        free_odbc_resources( stmt TSRMLS_CC );
        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_REGISTER_RESOURCE TSRMLS_CC, "statement" );
        RETURN_FALSE;
    }

    // store the resource id with the connection so the connection can release this statement
    // when it closes.
    next_index = zend_hash_next_free_element( conn->stmts );
    long rsrc_idx = Z_RESVAL_P( stmt_z );
    if( zend_hash_index_update( conn->stmts, next_index, &rsrc_idx, sizeof( long ), NULL /*output*/ ) == FAILURE ) {
        stmt->conn = NULL;  // tell the statement that it isn't part of the connection so don't try to remove itself
        free_stmt_resource( stmt_z TSRMLS_CC );
        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_ZEND_HASH TSRMLS_CC );
        RETURN_FALSE;
    }
    stmt->conn_index = next_index;
    stmt.transferred();

    zval_ptr_dtor( &return_value );
    *return_value_ptr = stmt_z;
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
    sqlsrv_malloc_auto_ptr<sqlsrv_stmt> stmt;
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

    // if it's not a NULL pointer and not an array, return an error
    if( params_z && Z_TYPE_P( params_z ) != IS_ARRAY ) {
        free_odbc_resources( stmt TSRMLS_CC );
        stmt->free_param_data();
        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
        RETURN_FALSE;
    }

    stmt->params_z = params_z;
    // zval_add_ref released in free_odbc_resources
    if( params_z ) {
        zval_add_ref( &params_z );
    }

    executed = sqlsrv_stmt_common_execute( stmt, sql_string, sql_len, true, _FN_ TSRMLS_CC );

    if( !executed ) {
        free_odbc_resources( stmt TSRMLS_CC );
        stmt->free_param_data();
        RETURN_FALSE;
    }

    // register the statement with the PHP runtime
    zval_auto_ptr stmt_z;
    ALLOC_INIT_ZVAL( stmt_z );
    int zr = ZEND_REGISTER_RESOURCE( stmt_z, stmt, sqlsrv_stmt::descriptor );
    if( zr == FAILURE ) {
        free_odbc_resources( stmt TSRMLS_CC );
        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_REGISTER_RESOURCE TSRMLS_CC, "statement" );
        RETURN_FALSE;
    }

    // store the resource id with the connection so the connection can release this statement
    // when it closes.
    next_index = zend_hash_next_free_element( conn->stmts );
    long rsrc_idx = Z_RESVAL_P( stmt_z );
    if( zend_hash_index_update( conn->stmts, next_index, &rsrc_idx, sizeof( long ), NULL /*output*/ ) == FAILURE ) {
        stmt->conn = NULL;  // tell the statement that it isn't part of the connection so don't try to remove itself
        free_stmt_resource( stmt_z TSRMLS_CC );
        handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_ZEND_HASH TSRMLS_CC );
        RETURN_FALSE;
    }
    stmt->conn_index = next_index;
    stmt.transferred();

    zval_ptr_dtor( &return_value );
    *return_value_ptr = stmt_z;
    stmt_z.transferred();
}


// sqlsrv_rollback( resource $conn )
//
// Rolls back the current transaction on the specified connection and returns
// the connection to the auto-commit mode. The current transaction includes all
// statements on the specified connection that were executed after the call to
// sqlsrv_begin_transaction and before any calls to sqlsrv_rollback or
// sqlsrv_commit.
// The SQL Server Driver for PHP is in auto-commit mode by default. This
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
    sqlsrv_malloc_auto_ptr<char> p;
    SQLSMALLINT info_len;

    DECL_FUNC_NAME( "sqlsrv_server_info" );
    LOG_FUNCTION;

    PROCESS_PARAMS( conn, _FN_, "r" );

    zr = array_init( return_value );
    CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_SERVER_INFO, RETURN_FALSE );
    
    p = static_cast<char*>( sqlsrv_malloc( INFO_BUFFER_LEN ));
    r = SQLGetInfo( conn->ctx.handle, SQL_DATABASE_NAME, p, INFO_BUFFER_LEN, &info_len );
    CHECK_SQL_ERROR( r, conn, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, conn, _FN_, NULL );
    MAKE_STD_ZVAL( server_info );
    ZVAL_STRINGL( server_info, p, info_len, 0 );
    zr = add_assoc_zval( return_value, "CurrentDatabase", server_info );
    CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_SERVER_INFO, RETURN_FALSE );
    CHECK_SQL_WARNING( r, conn, _FN_, NULL );
    p.transferred();

    p = static_cast<char*>( sqlsrv_malloc( INFO_BUFFER_LEN ));
    r = SQLGetInfo( conn->ctx.handle, SQL_DBMS_VER, p,  INFO_BUFFER_LEN, &info_len );
    CHECK_SQL_ERROR( r, conn, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, conn, _FN_, NULL );
    MAKE_STD_ZVAL( server_info );
    ZVAL_STRINGL( server_info, p, info_len, 0 );
    zr = add_assoc_zval( return_value, "SQLServerVersion", server_info );
    CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_SERVER_INFO, RETURN_FALSE );
    p.transferred();

    p = static_cast<char*>( sqlsrv_malloc( INFO_BUFFER_LEN ));
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

// must close all statement handles opened by this connection before closing the connection
// no errors are returned, since close should always succeed

void sqlsrv_conn_close_stmts( sqlsrv_conn* conn TSRMLS_DC )
{
    // test prerequisites
    if( conn->ctx.handle == NULL ) DIE( "Connection handle is NULL.  Trying to destroy an already destroyed connection." );
    if( !conn->stmts ) DIE( "Connection doesn't contain a statement array." );

    // loop through the stmts hash table and destroy each stmt resource so we can close the 
    // ODBC connection
    for( zend_hash_internal_pointer_reset( conn->stmts );
         zend_hash_has_more_elements( conn->stmts ) == SUCCESS;
         zend_hash_move_forward( conn->stmts )) {

        long* rsrc_idx_ptr;

        // get the resource id for the next statement created with this connection
        int zr = zend_hash_get_current_data( conn->stmts, reinterpret_cast<void**>( &rsrc_idx_ptr ));
        if( zr == FAILURE ) {
            LOG( SEV_ERROR, LOG_CONN, "Failed to retrieve a statement resource from the connection" );
        }
        long rsrc_idx = *rsrc_idx_ptr;

        // see if the statement is still valid, and if not skip to the next one
        // presumably this should never happen because if it's in the list, it should still be valid
        // by virtue that a statement resource should remove itself from its connection when it is
        // destroyed in sqlsrv_stmt_dtor.  However, rather than die (assert), we simply skip this resource
        // and move to the next one.
        sqlsrv_stmt* stmt;
        int type;
        stmt = static_cast<sqlsrv_stmt*>( zend_list_find( rsrc_idx, &type ));
        if( stmt == NULL || type != sqlsrv_stmt::descriptor ) {
            LOG( SEV_ERROR, LOG_CONN, "Non existent statement found in connection.  Statements should remove themselves"
                                      " from the connection so this shouldn't be out of sync." );
            continue;
        }

        // delete the statement by deleting it from Zend's resource list, which will force its destruction
        stmt->conn = NULL;
        zr = zend_hash_index_del( &EG( regular_list ), rsrc_idx );
        if( zr == FAILURE ) {
            LOG( SEV_ERROR, LOG_CONN, "Failed to remove statement resource %1!d! when closing the connection", rsrc_idx );
        }
    }

    zend_hash_destroy( conn->stmts );
    FREE_HASHTABLE( conn->stmts );
    conn->stmts = NULL;
}

#define NO_ATTRIBUTE -1

// type of connection attributes
enum CONN_ATTR_TYPE {
    CONN_ATTR_INT,
    CONN_ATTR_BOOL,
    CONN_ATTR_STRING,
};

// a connection option that includes the callback function that handles that option (e.g., adds it to the connection string or sets an attribute)
struct connection_option {
    // the name of the option as passed in by the user
    const char *        sqlsrv_name;
    unsigned int        sqlsrv_len;
    // the name of the option in the ODBC connection string
    const char *        odbc_name;
    unsigned int        odbc_len;
    enum CONN_ATTR_TYPE value_type;
    // process the connection type
    // return whether or not the function was successful in processing the connection option
    bool                (*func)( connection_option const*, zval* value, sqlsrv_conn* conn, std::string& conn_str TSRMLS_DC );
};

// connection attribute functions
template <unsigned int Attr>
struct int_conn_attr_func {

    static bool func( connection_option const* /*option*/, zval* value, sqlsrv_conn* conn, std::string& /*conn_str*/ TSRMLS_DC )
    {
        SQLRETURN r = SQLSetConnectAttr( conn->ctx.handle, Attr, reinterpret_cast<SQLPOINTER>( Z_LVAL_P( value )), SQL_IS_UINTEGER );
        CHECK_SQL_ERROR( r, conn, "sqlsrv_connect", NULL, return false );
        return true;
    }
};

template <unsigned int Attr>
struct bool_conn_attr_func {

    static bool func( connection_option const* /*option*/, zval* value, sqlsrv_conn* conn, std::string& /*conn_str*/ TSRMLS_DC )
    {
        SQLRETURN r = SQLSetConnectAttr( conn->ctx.handle, Attr, reinterpret_cast<SQLPOINTER>( zend_is_true( value )), SQL_IS_UINTEGER );
        CHECK_SQL_ERROR( r, conn, "sqlsrv_connect", NULL, return false );
        return true;
    }
};

template <unsigned int Attr>
struct str_conn_attr_func {

    static bool func( connection_option const* /*option*/, zval* value, sqlsrv_conn* conn, std::string& /*conn_str*/ TSRMLS_DC )
    {
        SQLRETURN r = SQLSetConnectAttr( conn->ctx.handle, Attr, reinterpret_cast<SQLPOINTER>( Z_STRVAL_P( value )), Z_STRLEN_P( value ));
        CHECK_SQL_ERROR( r, conn, "sqlsrv_connect", NULL, return false );
        return true;
    }
};

    // boolean connection string
struct bool_conn_str_func {

    static bool func( connection_option const* option, zval* value, sqlsrv_conn* /*conn*/, std::string& conn_str TSRMLS_DC )
    {
        TSRMLS_C;
        char const* val_str;
        if( zend_is_true( value )) {
            val_str = "yes";
        }
        else {
            val_str = "no";
        }
        conn_str += option->odbc_name;
        conn_str += "={";
        conn_str += val_str;
        conn_str += "};";

        return true;
    }
};

    // simply add the parsed value to the connection string
struct conn_str_append_func {

    static bool func( connection_option const* option, zval* value, sqlsrv_conn* /*conn*/, std::string& conn_str TSRMLS_DC )
    {
        // wrap a connection option in a quote.  It is presumed that any charactes that need to be escaped will
        // be escaped, such as a closing }.
        TSRMLS_C;
        const char* val_str = Z_STRVAL_P( value );
        int val_len = Z_STRLEN_P( value );
        if( val_len > 0 && val_str[0] == '{' && val_str[ val_len - 1 ] == '}' ) {
            ++val_str;
            val_len -= 2;
        }
        conn_str += option->odbc_name;
        conn_str += "={";
        conn_str.append( val_str, val_len );
        conn_str += "};";

        return true;
    }
};


struct conn_char_set_func {

    static bool func( connection_option const* /*option*/, zval* value, sqlsrv_conn* conn, std::string& /*conn_str*/ TSRMLS_DC )
    {
        convert_to_string( value );
        const char* encoding = Z_STRVAL_P( value );
        unsigned int encoding_len = Z_STRLEN_P( value );

        for( zend_hash_internal_pointer_reset( SQLSRV_G( encodings ));
             zend_hash_has_more_elements( SQLSRV_G( encodings )) == SUCCESS;
             zend_hash_move_forward( SQLSRV_G( encodings ) ) ) {

            sqlsrv_encoding* table_encoding;
            zend_hash_get_current_data( SQLSRV_G( encodings ), (void**) &table_encoding );

            if( encoding_len == table_encoding->iana_len && 
                !stricmp( encoding, table_encoding->iana )) {

                if( table_encoding->not_for_connection ) {
                    handle_error( &conn->ctx, LOG_CONN, "sqlsrv_connect", SQLSRV_ERROR_CONNECT_ILLEGAL_ENCODING TSRMLS_CC, encoding );
                    return false;
                }

                conn->default_encoding = table_encoding->code_page;
                return true;
            }
        }

        handle_error( &conn->ctx, LOG_CONN, "sqlsrv_connect", SQLSRV_ERROR_CONNECT_ILLEGAL_ENCODING TSRMLS_CC, encoding );
        return false;
    }
};

struct date_as_string_func {

    static bool func( connection_option const* /*option*/, zval* value, sqlsrv_conn* conn, std::string& /*conn_str*/ TSRMLS_DC )
    {
        TSRMLS_C;   // show as used to avoid a warning
        if( zend_is_true( value )) {
            conn->date_as_string = true;
        }
        else {
            conn->date_as_string = false;
        }

        return true;
    }
};

// do nothing for connection pooling since we handled it earlier when
// deciding which environment handle to use.
struct conn_null_func {

    static bool func( connection_option const* /*option*/, zval* /*value*/, sqlsrv_conn* /*conn*/, std::string& /*conn_str*/ TSRMLS_DC )
    {
        TSRMLS_C;
        return true;
    }
};

// list of valid attributes used by validate_connection_option below
const connection_option conn_opts[] = {
    { 
        ConnOptions::APP,
        sizeof( ConnOptions::APP ),
        ConnOptions::APP,
        sizeof( ConnOptions::APP ),
        CONN_ATTR_STRING,
        conn_str_append_func::func 
    },
    {
        ConnOptions::CharacterSet,
        sizeof( ConnOptions::CharacterSet ),
        ConnOptions::CharacterSet,
        sizeof( ConnOptions::CharacterSet ),
        CONN_ATTR_STRING,
        conn_char_set_func::func
    },
    {
        ConnOptions::ConnectionPooling,
        sizeof( ConnOptions::ConnectionPooling ),
        ConnOptions::ConnectionPooling,
        sizeof( ConnOptions::ConnectionPooling ),
        CONN_ATTR_BOOL,
        conn_null_func::func
    },
    {
        ConnOptions::Database,
        sizeof( ConnOptions::Database ),
        ConnOptions::Database,
        sizeof( ConnOptions::Database ),
        CONN_ATTR_STRING,
        conn_str_append_func::func
    },
    {
        ConnOptions::DateAsString,
        sizeof( ConnOptions::DateAsString ),
        ConnOptions::DateAsString,
        sizeof( ConnOptions::DateAsString ),
        CONN_ATTR_BOOL,
        date_as_string_func::func
    },
    {
        ConnOptions::Encrypt, 
        sizeof( ConnOptions::Encrypt ),
        ConnOptions::Encrypt, 
        sizeof( ConnOptions::Encrypt ),
        CONN_ATTR_BOOL,
        bool_conn_str_func::func
    },
    { 
        ConnOptions::Failover_Partner,
        sizeof( ConnOptions::Failover_Partner ), 
        ConnOptions::Failover_Partner,
        sizeof( ConnOptions::Failover_Partner ), 
        CONN_ATTR_STRING,
        conn_str_append_func::func
    },
    {
        ConnOptions::LoginTimeout,
        sizeof( ConnOptions::LoginTimeout ),
        ConnOptions::LoginTimeout,
        sizeof( ConnOptions::LoginTimeout ),
        CONN_ATTR_INT,
        int_conn_attr_func<SQL_ATTR_LOGIN_TIMEOUT>::func 
    },
    {
        ConnOptions::MARS_Option,
        sizeof( ConnOptions::MARS_Option ),
        ConnOptions::MARS_ODBC,
        sizeof( ConnOptions::MARS_ODBC ),
        CONN_ATTR_BOOL,
        bool_conn_str_func::func
    },
    {
        ConnOptions::PWD,
        sizeof( ConnOptions::PWD ),
        ConnOptions::PWD,
        sizeof( ConnOptions::PWD ),
        CONN_ATTR_STRING,
        conn_str_append_func::func
    },
    {
        ConnOptions::QuotedId,
        sizeof( ConnOptions::QuotedId ),
        ConnOptions::QuotedId,
        sizeof( ConnOptions::QuotedId ),
        CONN_ATTR_BOOL,
        bool_conn_str_func::func
    },
    {
        ConnOptions::TraceFile,
        sizeof( ConnOptions::TraceFile ), 
        ConnOptions::TraceFile,
        sizeof( ConnOptions::TraceFile ), 
        CONN_ATTR_STRING,
        str_conn_attr_func<SQL_ATTR_TRACEFILE>::func 
    },
    {
        ConnOptions::TraceOn,
        sizeof( ConnOptions::TraceOn ),
        ConnOptions::TraceOn,
        sizeof( ConnOptions::TraceOn ),
        CONN_ATTR_BOOL,
        bool_conn_attr_func<SQL_ATTR_TRACE>::func
    },
    {
        ConnOptions::TransactionIsolation,
        sizeof( ConnOptions::TransactionIsolation ),
        ConnOptions::TransactionIsolation,
        sizeof( ConnOptions::TransactionIsolation ),
        CONN_ATTR_INT,
        int_conn_attr_func<SQL_COPT_SS_TXN_ISOLATION>::func
    },
    {
        ConnOptions::TrustServerCertificate,
        sizeof( ConnOptions::TrustServerCertificate ),
        ConnOptions::TrustServerCertificate,
        sizeof( ConnOptions::TrustServerCertificate ),
        CONN_ATTR_BOOL,
        bool_conn_str_func::func
    },
    {
        ConnOptions::UID,
        sizeof( ConnOptions::UID ),
        ConnOptions::UID,
        sizeof( ConnOptions::UID ),
        CONN_ATTR_STRING,
        conn_str_append_func::func
    },
    {
        ConnOptions::WSID,
        sizeof( ConnOptions::WSID ),
        ConnOptions::WSID,
        sizeof( ConnOptions::WSID ),
        CONN_ATTR_STRING, 
        conn_str_append_func::func
    },
};

// validates a single key/value pair from the options given to sqlsrv_connect.
// to validate means to verify that it is a legal key from the list of keys in conn_attrs (above)
// and to verify the type.
// string attributes are scanned to make sure that all } are properly escaped as }}.

connection_option const* validate_connection_option( sqlsrv_conn const* conn, const char* key, unsigned int key_len, zval const* value_z TSRMLS_DC )
{
    int opt_idx = 0;

    for( opt_idx = 0; opt_idx < ( sizeof( conn_opts ) / sizeof( conn_opts[0] )); ++opt_idx ) {
        
        if( key_len == conn_opts[ opt_idx ].sqlsrv_len && !stricmp( key, conn_opts[ opt_idx ].sqlsrv_name )) {

            switch( conn_opts[ opt_idx ].value_type ) {

                case CONN_ATTR_BOOL:                        
                    // bool attributes can be either strings to be appended to the connection string
                    // as yes or no or integral connection attributes.  This will have to be reworked
                    // if we ever introduce a boolean connection option that maps to a string connection
                    // attribute.
                    break;
                case CONN_ATTR_INT:
                {
                    CHECK_SQL_ERROR_EX( Z_TYPE_P( value_z ) != IS_LONG, conn, "sqlsrv_connect", SQLSRV_ERROR_INVALID_OPTION, return NULL; );
                    break;
                }
                case CONN_ATTR_STRING:
                {
                    CHECK_SQL_ERROR_EX( Z_TYPE_P( value_z ) != IS_STRING, conn, "sqlsrv_connect", SQLSRV_ERROR_INVALID_OPTION, return NULL );
                    char* value = Z_STRVAL_P( value_z );
                    int value_len = Z_STRLEN_P( value_z );
                    // if the value is already quoted, then only analyse the part inside the quotes and return it as 
                    // unquoted since we quote it when adding it to the connection string.
                    if( value_len > 0 && value[0] == '{' && value[ value_len - 1 ] == '}' ) {
                        ++value;
                        value_len -= 2;
                    }
                    // check to make sure that all right braces are escaped
                    int i = 0;
                    while( ( value[i] != '}' || ( value[i] == '}' && value[i+1] == '}' )) && i < value_len ) {
                        // skip both braces
                        if( value[i] == '}' )
                            ++i;
                        ++i;
                    }
                    if( i < value_len && value[i] == '}' ) {
                        handle_error( &conn->ctx, LOG_CONN, "sqlsrv_connect", SQLSRV_ERROR_CONNECT_BRACES_NOT_ESCAPED TSRMLS_CC, key );
                        return NULL;
                    }
                    break;
                }
            }

            return &conn_opts[ opt_idx ];
         }
    }
    
    handle_error( &conn->ctx, LOG_CONN, "sqlsrv_connect", SQLSRV_ERROR_INVALID_OPTION TSRMLS_CC, key );
    return NULL;
}


// says what it does, and does what it says
// rather than have attributes and connection strings as ODBC does, we unify them into a hash table
// passed to the connection, and then break them out ourselves and either set attributes or put the
// option in the connection string.

SQLRETURN build_connection_string_and_set_conn_attr( sqlsrv_conn* conn, const char* server, zval const* options, 
                                                     __inout std::string& connection_string TSRMLS_DC )
{
    bool credentials_mentioned = false;
    bool mars_mentioned = false;
    connection_option const* conn_opt;
    int zr = SUCCESS;

    DECL_FUNC_NAME( "sqlsrv_connect" );

    // put the driver and server as the first components of the connection string
    connection_string = "Driver={SQL Server Native Client 10.0};Server=";
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

        conn_opt = validate_connection_option( conn, key, key_len, *data TSRMLS_CC );
        if( !conn_opt ) {
            return SQL_ERROR;
        }
 
        // if a user id is given ,then don't use a trusted connection
        if( ( key_len == sizeof( ConnOptions::UID )) && !stricmp( key, ConnOptions::UID )) {
            credentials_mentioned = true;
        }
        if( (key_len == sizeof( ConnOptions::MARS_Option )) && !stricmp( key, ConnOptions::MARS_Option )) {
            mars_mentioned = true;
        }
        
        bool f = conn_opt->func( conn_opt, *data, conn, connection_string TSRMLS_CC );
        if( !f ) {
            return SQL_ERROR;
        }
    }

    // trusted connection is the default if no user id was given.
    if( !credentials_mentioned ) {
        connection_string += "Trusted_Connection={Yes};";
    }

    // MARS on if not explicitly turned off
    if( !mars_mentioned ) {
        connection_string += "MARS_Connection={Yes};";
    }

    return SQL_SUCCESS;
}

struct stmt_option;

struct stmt_option_functor {

    virtual bool operator()( sqlsrv_stmt* /*stmt*/, stmt_option const* /*opt*/, zval* /*value_z*/,
                             const char* /*_FN_*/ TSRMLS_DC )
    {
        TSRMLS_C;
        DIE( "Not implemented" );
        return false;
    }
};

// used to hold the table for statment options
struct stmt_option {

    const char* name;
    long name_len;
    // callback that actually handles the work of the option
    stmt_option_functor* func;
};

struct stmt_option_query_timeout : public stmt_option_functor {

    virtual bool operator()( sqlsrv_stmt* stmt, stmt_option const* opt, zval* value_z, const char* _FN_ TSRMLS_DC )
    {
        SQLRETURN r = SQL_SUCCESS;
        if( Z_TYPE_P( value_z ) != IS_LONG ) {
            convert_to_string( value_z );
            handle_error( NULL, LOG_CONN, _FN_, SQLSRV_ERROR_INVALID_OPTION_VALUE TSRMLS_CC, Z_STRVAL_P( value_z ), opt->name );
            return false;
        }
        r = SQLSetStmtAttr( stmt->ctx.handle, SQL_ATTR_QUERY_TIMEOUT, reinterpret_cast<SQLPOINTER>( Z_LVAL_P( value_z )), SQL_IS_UINTEGER );
        CHECK_SQL_ERROR( r, stmt, _FN_, NULL, return false; );
        char lock_timeout_sql[ 32 ];
        int written = sprintf_s( lock_timeout_sql, sizeof( lock_timeout_sql ), "SET LOCK_TIMEOUT %d", Z_LVAL_P( value_z ) * 1000 );
        if( written == -1 || written == sizeof( lock_timeout_sql )) {
            DIE( "sprintf_s failed.  Shouldn't ever fail." );
        }
        r = SQLExecDirect( stmt->ctx.handle, reinterpret_cast<SQLCHAR*>( lock_timeout_sql ), SQL_NTS );
        CHECK_SQL_ERROR( r, stmt, _FN_, NULL, return false; );
        return true;
    }
};

struct stmt_option_send_at_exec : public stmt_option_functor {

    virtual bool operator()( sqlsrv_stmt* stmt, stmt_option const* /*opt*/, zval* value_z, const char* /*_FN_*/ TSRMLS_DC )
    {
        TSRMLS_C;
        stmt->send_at_exec = ( zend_is_true( value_z )) ? true : false;
        return true;
    }
};

struct stmt_option_scrollable : public stmt_option_functor {

    virtual bool operator()( sqlsrv_stmt* stmt, stmt_option const* /*opt*/, zval* value_z, const char* _FN_ TSRMLS_DC )
    {
        SQLRETURN r = SQL_SUCCESS;
        if( Z_TYPE_P( value_z ) != IS_STRING ) {
            handle_error( &stmt->ctx, LOG_CONN, _FN_, SQLSRV_ERROR_INVALID_OPTION_SCROLLABLE TSRMLS_CC );
            return false;
        }
        const char* scroll_type = Z_STRVAL_P( value_z );
        // keep the flag for use by other procedures rather than have to query ODBC for the value
        stmt->scrollable = true;
        stmt->scroll_is_dynamic = false;
        // find which cursor type they would like and set the ODBC statement attribute as such
        if( !stricmp( scroll_type, QUERY_OPTION_SCROLLABLE_STATIC )) {
            r = SQLSetStmtAttr( stmt->ctx.handle, SQL_ATTR_CURSOR_TYPE, reinterpret_cast<SQLPOINTER>( SQL_CURSOR_STATIC ), SQL_IS_UINTEGER );
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, return false; );
        }
        else if( !stricmp( scroll_type, QUERY_OPTION_SCROLLABLE_DYNAMIC )) {
            stmt->scroll_is_dynamic = true;     // this cursor is dynamic
            r = SQLSetStmtAttr( stmt->ctx.handle, SQL_ATTR_CURSOR_TYPE, reinterpret_cast<SQLPOINTER>( SQL_CURSOR_DYNAMIC ), SQL_IS_UINTEGER );
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, return false; );
        }
        else if( !stricmp( scroll_type, QUERY_OPTION_SCROLLABLE_KEYSET )) {
            r = SQLSetStmtAttr( stmt->ctx.handle, SQL_ATTR_CURSOR_TYPE, reinterpret_cast<SQLPOINTER>( SQL_CURSOR_KEYSET_DRIVEN ), SQL_IS_UINTEGER );
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, return false; );
        }
        // use a boring, old (but very performant :)) forward only cursor
        else if( !stricmp( scroll_type, QUERY_OPTION_SCROLLABLE_FORWARD )) {
            stmt->scrollable = false;   // reset since forward isn't scrollable
            r = SQLSetStmtAttr( stmt->ctx.handle, SQL_ATTR_CURSOR_TYPE, reinterpret_cast<SQLPOINTER>( SQL_CURSOR_FORWARD_ONLY ), SQL_IS_UINTEGER );
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, return false; );
        }
        // didn't match any of the cursor types
        else {
            handle_error( &stmt->ctx, LOG_CONN, _FN_, SQLSRV_ERROR_INVALID_OPTION_SCROLLABLE TSRMLS_CC );
            return false;
        }
        return true;
    }
};

struct stmt_option stmt_opts[] = {
    { QUERY_TIMEOUT, sizeof( QUERY_TIMEOUT ),  new stmt_option_query_timeout },
    { SEND_STREAMS_AT_EXEC, sizeof( SEND_STREAMS_AT_EXEC ), new stmt_option_send_at_exec },
    { SCROLLABLE, sizeof( SCROLLABLE ), new stmt_option_scrollable }
};

// return the option from the stmt_opts array that matches the key.  If no option found,
// NULL is returned.
stmt_option* validate_stmt_option( const char* key, long key_len )
{
    for( int i = 0; i < sizeof( stmt_opts ) / sizeof( stmt_option ); ++i ) {

        // if we find the key we're looking for, return it
        if( key_len == stmt_opts[ i ].name_len && !stricmp( stmt_opts[ i ].name, key )) {

            return &stmt_opts[ i ];
        }
    }

    return NULL;    // no option found
}


// common code to allocate a statement from either sqlsrv_prepare or sqlsv_query.  Returns either
// a valid sqlsrv_stmt or NULL if an error occurred.

sqlsrv_stmt* allocate_stmt( __in sqlsrv_conn* conn, zval const* options_z, char const* _FN_ TSRMLS_DC )
{
    SQLRETURN r = SQL_SUCCESS;
    sqlsrv_malloc_auto_ptr<sqlsrv_stmt> stmt;
    stmt = new ( sqlsrv_malloc( sizeof( sqlsrv_stmt ))) sqlsrv_stmt;
    sqlsrv_malloc_auto_ptr<char> param_buffer;
    param_buffer = static_cast<char*>( sqlsrv_malloc( PHP_STREAM_BUFFER_SIZE ));

    // we don't put all this initialization in a constructor since it would serve little purpose
    // and would complicate the handling of the allocation/deallocation of param_buffer member.
    // The param_buffer member would have to be freed within a destructor if it were allocated
    // within the constructor, which would preclude the use of the sqlsrv_auto_ptr.  Also, the
    // instance itself would have to use the placement new or override new since we must use
    // emalloc rather than the default new.  And since this is the only place we allocate the
    // statement, it's better to just keep it localized to here.
    stmt->ctx.handle_type = SQL_HANDLE_STMT;

    stmt->conn = conn;
    stmt->executed = false;
    stmt->prepared = false;
    stmt->current_stream = NULL;
    stmt->current_stream_read = 0;
    stmt->current_stream_encoding = SQLSRV_ENCODING_CHAR;
    stmt->params_z = NULL;
    stmt->params_ind_ptr = NULL;
    stmt->param_datetime_buffers = NULL;
    stmt->param_strings = NULL;
    stmt->param_streams = NULL;
    stmt->param_buffer = param_buffer;
    stmt->param_buffer_size = PHP_STREAM_BUFFER_SIZE;
    stmt->send_at_exec = true;
    stmt->conn_index = -1;
    stmt->fetch_fields = NULL;
    stmt->fetch_fields_count = 0;
    stmt->scrollable = false;
    stmt->scroll_is_dynamic = false;
    stmt->has_rows = false;

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

            stmt_option* stmt_opt = validate_stmt_option( key, key_len );
            // if the key didn't match, then return the error to the script
            if( !stmt_opt ) {
                handle_error( &stmt->ctx, LOG_CONN, _FN_, SQLSRV_ERROR_INVALID_OPTION_KEY TSRMLS_CC, key );
                    SQLFreeHandle( stmt->ctx.handle_type, stmt->ctx.handle ); 
                    return NULL;
                }
            // perform the actions the statement option needs done
            bool attr_set = (*stmt_opt->func)( stmt, stmt_opt, *value_z, _FN_ TSRMLS_CC );
            // any errors should have been posted in the callback
            if( !attr_set ) {
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
// new values.  Marking them as references "pins" them to their memory location so that 
// the buffer we give to ODBC can be relied on to be there.

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
            if( !PZVAL_IS_REF( *param ) && Z_REFCOUNT_P( *param ) > 1 ) {
                // 10 should be sufficient for adding up to a 3 digit number to the message
                int warning_len = strlen( PHP_WARNING_VAR_NOT_REFERENCE->native_message ) + 10;
                sqlsrv_malloc_auto_ptr<char> warning;
                warning = static_cast<char*>( sqlsrv_malloc( warning_len ));
                snprintf( warning, warning_len, PHP_WARNING_VAR_NOT_REFERENCE->native_message, i );
                php_error( E_WARNING, warning );
            }
            Z_SET_ISREF_PP( param ); // mark it as a reference
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
            if( !PZVAL_IS_REF( *var ) && Z_REFCOUNT_P( *var ) > 1 ) {
                // 10 should be sufficient for adding up to a 3 digit number to the message
                int warning_len = strlen( PHP_WARNING_VAR_NOT_REFERENCE->native_message ) + 10;
                sqlsrv_malloc_auto_ptr<char> warning;
                warning = static_cast<char*>( sqlsrv_malloc( warning_len ));
                snprintf( warning, warning_len, PHP_WARNING_VAR_NOT_REFERENCE->native_message, i );
                php_error( E_WARNING, warning );
            }
            Z_SET_ISREF_PP( var ); // mark it as a reference
        }
    }

    return true;
}

const char* get_processor_arch( void )
{
    SYSTEM_INFO sys_info;
    GetSystemInfo( &sys_info);
    switch( sys_info.wProcessorArchitecture ) {

        case PROCESSOR_ARCHITECTURE_INTEL:
           return PROCESSOR_ARCH[0];

        case PROCESSOR_ARCHITECTURE_AMD64:
            return PROCESSOR_ARCH[1];

        case PROCESSOR_ARCHITECTURE_IA64:
            return PROCESSOR_ARCH[2];

        default:
            DIE( "Unknown Windows processor architecture" );
            return NULL;
    }
}

// some features require a server of a certain version or later
// this function determines the version of the server we're connected to
// and stores it in the connection.  Any errors are logged before return.
// SQL_ERROR is returned when the server version is either undetermined
// or is invalid (< 2000).

SQLRETURN determine_server_version( sqlsrv_conn* conn, const char* _FN_ TSRMLS_DC )
{
    SQLSMALLINT info_len;
    char p[ INFO_BUFFER_LEN ];
    SQLRETURN r = SQLGetInfo( conn->ctx.handle, SQL_DBMS_VER, p, INFO_BUFFER_LEN, &info_len );
    CHECK_SQL_ERROR( r, conn, _FN_, NULL, return r; );
    CHECK_SQL_WARNING( r, conn, _FN_, NULL );

    char version_major_str[ 3 ];
    SERVER_VERSION version_major;
    memcpy( version_major_str, p, 2 );
    version_major_str[ 2 ] = '\0';
    version_major = static_cast<SERVER_VERSION>( atoi( version_major_str ));

    if( version_major == 0 || errno == ERANGE || errno == EINVAL ) {
        conn->server_version = SERVER_VERSION_UNKNOWN;
        handle_error( &conn->ctx, LOG_CONN, _FN_, SQLSRV_ERROR_UNKNOWN_SERVER_VERSION TSRMLS_CC );
        return SQL_ERROR;
    }

    // SNAC won't connect to versions older than SQL Server 2000, so we know that the version is at least
    // that high
    conn->server_version = version_major;
    return SQL_SUCCESS;
}

}   // namespace

//---------------------------------------------------------------------------------------------------------------------------------
// File: conn.cpp
//
// Contents: Routines that use connection handles
//
// Copyright Microsoft Corporation
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
//
// You may obtain a copy of the License at:
// http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//---------------------------------------------------------------------------------------------------------------------------------

#include "php_sqlsrv.h"
#include <psapi.h>
#include <windows.h>
#include <winver.h>

#include <string>
#include <sstream>

// *** internal variables and constants ***

namespace {

// current subsytem.  defined for the CHECK_SQL_{ERROR|WARNING} macros
unsigned int current_log_subsystem = LOG_CONN;

struct date_as_string_func {

    static void func( connection_option const* /*option*/, zval* value, sqlsrv_conn* conn, std::string& /*conn_str*/ TSRMLS_DC )
    {
        TSRMLS_C;   // show as used to avoid a warning

        ss_sqlsrv_conn* ss_conn = static_cast<ss_sqlsrv_conn*>( conn );

        if( zend_is_true( value )) {
            ss_conn->date_as_string = true;
        }
        else {
            ss_conn->date_as_string = false;
        }
    }
};

struct conn_char_set_func {

    static void func( connection_option const* /*option*/, zval* value, sqlsrv_conn* conn, std::string& /*conn_str*/ TSRMLS_DC )
    {
         convert_to_string( value );
         const char* encoding = Z_STRVAL_P( value );
         unsigned int encoding_len = Z_STRLEN_P( value );

         for( zend_hash_internal_pointer_reset( g_ss_encodings_ht );
             zend_hash_has_more_elements( g_ss_encodings_ht ) == SUCCESS;
             zend_hash_move_forward( g_ss_encodings_ht )) {

            sqlsrv_encoding* ss_encoding;
            core::sqlsrv_zend_hash_get_current_data( *conn, g_ss_encodings_ht, (void**) &ss_encoding TSRMLS_CC );

            if( !strnicmp( encoding, ss_encoding->iana, encoding_len ) ) {

                if( ss_encoding->not_for_connection ) {
                    THROW_SS_ERROR( conn, SS_SQLSRV_ERROR_CONNECT_ILLEGAL_ENCODING, encoding );
                }

                conn->set_encoding(  static_cast<SQLSRV_ENCODING>( ss_encoding->code_page ));
                return;
            }
         }

         THROW_SS_ERROR( conn, SS_SQLSRV_ERROR_CONNECT_ILLEGAL_ENCODING, encoding );
    }
};

struct bool_conn_str_func {

    static void func( connection_option const* option, zval* value, sqlsrv_conn* /*conn*/, std::string& conn_str TSRMLS_DC )
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
    }
};

template <unsigned int Attr>
struct int_conn_attr_func {

    static void func( connection_option const* /*option*/, zval* value, sqlsrv_conn* conn, std::string& /*conn_str*/ TSRMLS_DC )
    {
        try {
        
            core::SQLSetConnectAttr( conn, Attr, reinterpret_cast<SQLPOINTER>( Z_LVAL_P( value )), 
                                     SQL_IS_UINTEGER TSRMLS_CC );
        }
        catch( core::CoreException& ) {
            throw;
        }
    }
};

template <unsigned int Attr>
struct bool_conn_attr_func {

    static void func( connection_option const* /*option*/, zval* value, sqlsrv_conn* conn, std::string& /*conn_str*/ TSRMLS_DC )
    {
         try {
        
            core::SQLSetConnectAttr( conn, Attr, reinterpret_cast<SQLPOINTER>( zend_is_true( value )), 
                                     SQL_IS_UINTEGER TSRMLS_CC );
        }
        catch( core::CoreException& ) {
            throw;
        }

    }
};

//// *** internal functions ***

void sqlsrv_conn_close_stmts( ss_sqlsrv_conn* conn TSRMLS_DC );
void validate_conn_options( sqlsrv_context& ctx, zval* user_options_z, __out char** uid, __out char** pwd, 
                            __inout HashTable* ss_conn_options_ht TSRMLS_DC );
void validate_stmt_options( sqlsrv_context& ctx, zval* stmt_options, __inout HashTable* ss_stmt_options_ht TSRMLS_DC );
void add_conn_option_key( sqlsrv_context& ctx, char* key, unsigned int key_len, 
                         HashTable* options_ht, zval** data TSRMLS_DC );
void add_stmt_option_key( sqlsrv_context& ctx, char* key, unsigned int key_len, HashTable* options_ht, zval** data TSRMLS_DC );
int get_conn_option_key( sqlsrv_context& ctx, char* key, unsigned int key_len, zval const* value_z TSRMLS_DC );   
int get_stmt_option_key( char* key, unsigned int key_len TSRMLS_DC );

}

// constants for parameters used by process_params function(s)
int ss_sqlsrv_conn::descriptor;
char* ss_sqlsrv_conn::resource_name = "ss_sqlsrv_conn";

// connection specific parameter proccessing.  Use the generic function specialised to return a connection
// resource.
#define PROCESS_PARAMS( rsrc, param_spec, calling_func, param_count, ... )                                                          \
    rsrc = process_params<ss_sqlsrv_conn>( INTERNAL_FUNCTION_PARAM_PASSTHRU, param_spec, calling_func, param_count, __VA_ARGS__ );  \
    if( rsrc == NULL ) {                                                                                                            \
        RETURN_FALSE;                                                                                                               \
    }

namespace SSStmtOptionNames {
    const char QUERY_TIMEOUT[]= "QueryTimeout";
    const char SEND_STREAMS_AT_EXEC[] = "SendStreamParamsAtExec";
    const char SCROLLABLE[] = "Scrollable";
    const char CLIENT_BUFFER_MAX_SIZE[] = INI_BUFFERED_QUERY_LIMIT;
}

namespace SSConnOptionNames {

// most of these strings are the same for both the sqlsrv_connect connection option
// and the name put into the connection string. MARS is the only one that's different.
const char APP[] = "APP";
const char ApplicationIntent[] = "ApplicationIntent";
const char AttachDBFileName[] = "AttachDbFileName";
const char CharacterSet[] = "CharacterSet";
const char ConnectionPooling[] = "ConnectionPooling";
const char Database[] = "Database";
const char DateAsString[] = "ReturnDatesAsStrings";
const char Encrypt[] = "Encrypt";
const char Failover_Partner[] = "Failover_Partner";
const char LoginTimeout[] = "LoginTimeout";
const char MARS_Option[] = "MultipleActiveResultSets";
const char MultiSubnetFailover[] = "MultiSubnetFailover";
const char PWD[] = "PWD";
const char QuotedId[] = "QuotedId";
const char TraceFile[] = "TraceFile";
const char TraceOn[] = "TraceOn";
const char TrustServerCertificate[] = "TrustServerCertificate";
const char TransactionIsolation[] = "TransactionIsolation";
const char UID[] = "UID";
const char WSID[] = "WSID";

}

enum SS_CONN_OPTIONS {
    
    SS_CONN_OPTION_DATE_AS_STRING = SQLSRV_CONN_OPTION_DRIVER_SPECIFIC,
};

//List of all statement options supported by this driver
const stmt_option SS_STMT_OPTS[] = {   
    {
        SSStmtOptionNames::QUERY_TIMEOUT, 
        sizeof( SSStmtOptionNames::QUERY_TIMEOUT ),
        SQLSRV_STMT_OPTION_QUERY_TIMEOUT, 
        new stmt_option_query_timeout
    },
    { 
        SSStmtOptionNames::SEND_STREAMS_AT_EXEC, 
        sizeof( SSStmtOptionNames::SEND_STREAMS_AT_EXEC ),
        SQLSRV_STMT_OPTION_SEND_STREAMS_AT_EXEC, 
        new stmt_option_send_at_exec
    },
    { 
        SSStmtOptionNames::SCROLLABLE, 
        sizeof( SSStmtOptionNames::SCROLLABLE ),
        SQLSRV_STMT_OPTION_SCROLLABLE, 
        new stmt_option_scrollable
    },
    { 
        SSStmtOptionNames::CLIENT_BUFFER_MAX_SIZE, 
        sizeof( SSStmtOptionNames::CLIENT_BUFFER_MAX_SIZE ),
        SQLSRV_STMT_OPTION_CLIENT_BUFFER_MAX_SIZE, 
        new stmt_option_buffered_query_limit
    },
    { NULL, 0, SQLSRV_STMT_OPTION_INVALID, NULL },
};


// List of all connection options supported by this driver.
const connection_option SS_CONN_OPTS[] = {
    { 
        SSConnOptionNames::APP,
        sizeof( SSConnOptionNames::APP ),
        SQLSRV_CONN_OPTION_APP,
        ODBCConnOptions::APP,
        sizeof( ODBCConnOptions::APP ),
        CONN_ATTR_STRING,
        conn_str_append_func::func 
    },
    { 
        SSConnOptionNames::ApplicationIntent,
        sizeof( SSConnOptionNames::ApplicationIntent ),
        SQLSRV_CONN_OPTION_APPLICATION_INTENT,
        ODBCConnOptions::ApplicationIntent,
        sizeof( ODBCConnOptions::ApplicationIntent ),
        CONN_ATTR_STRING,
        conn_str_append_func::func 
    },
    {
        SSConnOptionNames::AttachDBFileName,
        sizeof( SSConnOptionNames::AttachDBFileName ),
        SQLSRV_CONN_OPTION_ATTACHDBFILENAME,
        ODBCConnOptions::AttachDBFileName,
        sizeof( ODBCConnOptions::AttachDBFileName ),
        CONN_ATTR_STRING,
        conn_str_append_func::func
    },
    {
        SSConnOptionNames::CharacterSet,
        sizeof( SSConnOptionNames::CharacterSet ),
        SQLSRV_CONN_OPTION_CHARACTERSET,
        ODBCConnOptions::CharacterSet,
        sizeof( ODBCConnOptions::CharacterSet ),
        CONN_ATTR_STRING,
        conn_char_set_func::func
    },
    {
        SSConnOptionNames::ConnectionPooling,
        sizeof( SSConnOptionNames::ConnectionPooling ),
        SQLSRV_CONN_OPTION_CONN_POOLING,
        ODBCConnOptions::ConnectionPooling,
        sizeof( ODBCConnOptions::ConnectionPooling ),
        CONN_ATTR_BOOL,
        conn_null_func::func
    },
    {
        SSConnOptionNames::Database,
        sizeof( SSConnOptionNames::Database ),
        SQLSRV_CONN_OPTION_DATABASE,
        ODBCConnOptions::Database,
        sizeof( ODBCConnOptions::Database ),
        CONN_ATTR_STRING,
        conn_str_append_func::func
    },
    {
        SSConnOptionNames::Encrypt,
        sizeof( SSConnOptionNames::Encrypt ),
        SQLSRV_CONN_OPTION_ENCRYPT,
        ODBCConnOptions::Encrypt, 
        sizeof( ODBCConnOptions::Encrypt ),
        CONN_ATTR_BOOL,
        bool_conn_str_func::func
    },
    { 
        SSConnOptionNames::Failover_Partner,
        sizeof( SSConnOptionNames::Failover_Partner ),
        SQLSRV_CONN_OPTION_FAILOVER_PARTNER,
        ODBCConnOptions::Failover_Partner,
        sizeof( ODBCConnOptions::Failover_Partner ), 
        CONN_ATTR_STRING,
        conn_str_append_func::func
    },
    {
        SSConnOptionNames::LoginTimeout,
        sizeof( SSConnOptionNames::LoginTimeout ),
        SQLSRV_CONN_OPTION_LOGIN_TIMEOUT,
        ODBCConnOptions::LoginTimeout,
        sizeof( ODBCConnOptions::LoginTimeout ),
        CONN_ATTR_INT,
        int_conn_attr_func<SQL_ATTR_LOGIN_TIMEOUT>::func 
    },
    {
        SSConnOptionNames::MARS_Option,
        sizeof( SSConnOptionNames::MARS_Option ),
        SQLSRV_CONN_OPTION_MARS,
        ODBCConnOptions::MARS_ODBC,
        sizeof( ODBCConnOptions::MARS_ODBC ),
        CONN_ATTR_BOOL,
        bool_conn_str_func::func
    },
    {
        SSConnOptionNames::MultiSubnetFailover,
        sizeof( SSConnOptionNames::MultiSubnetFailover ),
        SQLSRV_CONN_OPTION_MULTI_SUBNET_FAILOVER,
        ODBCConnOptions::MultiSubnetFailover,
        sizeof( ODBCConnOptions::MultiSubnetFailover ),
        CONN_ATTR_BOOL,
        bool_conn_str_func::func
    },
    {
        SSConnOptionNames::QuotedId,
        sizeof( SSConnOptionNames::QuotedId ),
        SQLSRV_CONN_OPTION_QUOTED_ID,
        ODBCConnOptions::QuotedId,
        sizeof( ODBCConnOptions::QuotedId ),
        CONN_ATTR_BOOL,
        bool_conn_str_func::func
    },
    {
        SSConnOptionNames::TraceFile,
        sizeof( SSConnOptionNames::TraceFile ),
        SQLSRV_CONN_OPTION_TRACE_FILE,
        ODBCConnOptions::TraceFile,
        sizeof( ODBCConnOptions::TraceFile ), 
        CONN_ATTR_STRING,
        str_conn_attr_func<SQL_ATTR_TRACEFILE>::func 
    },
    {
        SSConnOptionNames::TraceOn,
        sizeof( SSConnOptionNames::TraceOn ),
        SQLSRV_CONN_OPTION_TRACE_ON,
        ODBCConnOptions::TraceOn,
        sizeof( ODBCConnOptions::TraceOn ),
        CONN_ATTR_BOOL,
        bool_conn_attr_func<SQL_ATTR_TRACE>::func
    },
    {
        SSConnOptionNames::TransactionIsolation,
        sizeof( SSConnOptionNames::TransactionIsolation ),
        SQLSRV_CONN_OPTION_TRANS_ISOLATION,
        ODBCConnOptions::TransactionIsolation,
        sizeof( ODBCConnOptions::TransactionIsolation ),
        CONN_ATTR_INT,
        int_conn_attr_func<SQL_COPT_SS_TXN_ISOLATION>::func
    },
    {
        SSConnOptionNames::TrustServerCertificate,
        sizeof( SSConnOptionNames::TrustServerCertificate ),
        SQLSRV_CONN_OPTION_TRUST_SERVER_CERT,
        ODBCConnOptions::TrustServerCertificate,
        sizeof( ODBCConnOptions::TrustServerCertificate ),
        CONN_ATTR_BOOL,
        bool_conn_str_func::func
    },
    {
        SSConnOptionNames::WSID,
        sizeof( SSConnOptionNames::WSID ),
        SQLSRV_CONN_OPTION_WSID,
        ODBCConnOptions::WSID,
        sizeof( ODBCConnOptions::WSID ),
        CONN_ATTR_STRING, 
        conn_str_append_func::func
    },
    {
        SSConnOptionNames::DateAsString,
        sizeof( SSConnOptionNames::DateAsString ),
        SS_CONN_OPTION_DATE_AS_STRING,
        SSConnOptionNames::DateAsString,
        sizeof( SSConnOptionNames::DateAsString ),
        CONN_ATTR_BOOL,
        date_as_string_func::func
    },
    { NULL, 0, SQLSRV_CONN_OPTION_INVALID, NULL, 0 , CONN_ATTR_INVALID, NULL },  //terminate the table
};

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

PHP_FUNCTION ( sqlsrv_connect ) 
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );
    
    LOG_FUNCTION( "sqlsrv_connect" );
    SET_FUNCTION_NAME( *g_henv_cp );
    SET_FUNCTION_NAME( *g_henv_ncp );

    reset_errors( TSRMLS_C );

    const char* server = NULL;
    zval* options_z = NULL;
    char* uid = NULL;
    char* pwd = NULL;
    unsigned int server_len = 0;

    // get the server name and connection options
    int result = zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "s|a", &server, &server_len, &options_z );
    
    CHECK_CUSTOM_ERROR(( result == FAILURE ), *g_henv_cp, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, "sqlsrv_connect" ) {
        RETURN_FALSE;
    }
    
    hash_auto_ptr ss_conn_options_ht;
    hash_auto_ptr stmts;
    ss_sqlsrv_conn* conn = NULL;

    try {

        // Initialize the options array to be passed to the core layer
        ALLOC_HASHTABLE( ss_conn_options_ht );
        
        core::sqlsrv_zend_hash_init( *g_henv_cp, ss_conn_options_ht, 10 /* # of buckets */, NULL /*hashfn*/, 
                                 ZVAL_PTR_DTOR, 0 /*persistent*/ TSRMLS_CC );
   
        // Either of g_henv_cp or g_henv_ncp can be used to propogate the error.
        ::validate_conn_options( *g_henv_cp, options_z, &uid, &pwd, ss_conn_options_ht TSRMLS_CC );   
     
        // call the core connect function  
        conn = static_cast<ss_sqlsrv_conn*>( core_sqlsrv_connect( *g_henv_cp, *g_henv_ncp, &core::allocate_conn<ss_sqlsrv_conn>,
                                                                  server, uid, pwd, ss_conn_options_ht, ss_error_handler,  
                                                                  SS_CONN_OPTS, NULL, "sqlsrv_connect" TSRMLS_CC )); 
        
        SQLSRV_ASSERT( conn != NULL, "sqlsrv_connect: Invalid connection returned.  Exception should have been thrown." );
        
        // create a bunch of statements
        ALLOC_HASHTABLE( stmts );
    
        core::sqlsrv_zend_hash_init( *g_henv_cp, stmts, 5, NULL /* hashfn */, NULL /* dtor */, 0 /* persistent */ TSRMLS_CC );

        // register the connection with the PHP runtime        
        ss::zend_register_resource( return_value, conn, ss_sqlsrv_conn::descriptor, ss_sqlsrv_conn::resource_name TSRMLS_CC );

        conn->stmts = stmts;
        stmts.transferred();
    }
    
    catch( core::CoreException& ) {
        
        if( conn != NULL ) {
         
            conn->invalidate();
        }

        RETURN_FALSE;
    }

    catch( ... ) {

        DIE("sqlsrv_connect: Unknown exception caught.");
    }
}

// sqlsrv_begin_transaction( resource $conn )
//
// Begins a transaction on a specified connection. The current transaction
// includes all statements on the specified connection that were executed after
// the call to sqlsrv_begin_transaction and before any calls to sqlsrv_rollback
// or sqlsrv_commit.
//
// The SQLSRV driver is in auto-commit mode by default. This means that all
// queries are automatically committed upon success unless they have been
// designated as part of an explicit transaction by using
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
    LOG_FUNCTION( "sqlsrv_begin_transaction" );

    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );
    
    ss_sqlsrv_conn* conn = NULL;
    PROCESS_PARAMS( conn, "r", _FN_, 0 );

    // Return false if already in transaction
    CHECK_CUSTOM_ERROR(( conn->in_transaction == true ), *conn, SS_SQLSRV_ERROR_ALREADY_IN_TXN ) {
        RETURN_FALSE;
    }

    try {

        core_sqlsrv_begin_transaction( conn TSRMLS_CC );
        conn->in_transaction = true;
        RETURN_TRUE;
    }

    catch( core::CoreException& ) {
        RETURN_FALSE;
    }        
    catch( ... ) {

        DIE("sqlsrv_begin_transaction: Unknown exception caught.");
    }
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

    LOG_FUNCTION( "sqlsrv_close" );
    
    zval* conn_r = NULL;
    ss_sqlsrv_conn* conn = NULL;
    sqlsrv_context_auto_ptr error_ctx;

    full_mem_check(MEMCHECK_SILENT);
    reset_errors( TSRMLS_C );
    
    try {

        // dummy context to pass to the error handler
        error_ctx = new (sqlsrv_malloc( sizeof( sqlsrv_context ))) sqlsrv_context( 0, ss_error_handler, NULL );
        SET_FUNCTION_NAME( *error_ctx );

        if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "r", &conn_r ) == FAILURE ) {
        
             // Check if it was a zval
            int zr = zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "z", &conn_r );
            CHECK_CUSTOM_ERROR(( zr == FAILURE ), error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ ) {

                throw ss::SSException();
            }   

            // if sqlsrv_close was called on a non-existent connection than we just return success.
            if( Z_TYPE_P( conn_r ) == IS_NULL ) {

                RETURN_TRUE;
            }
            else {
                
              THROW_CORE_ERROR( error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ );
            }
        }

        conn = static_cast<ss_sqlsrv_conn*>( zend_fetch_resource( &conn_r TSRMLS_CC, -1, ss_sqlsrv_conn::resource_name, NULL, 1, 
                                                                  ss_sqlsrv_conn::descriptor ));
        
        CHECK_CUSTOM_ERROR(( conn == NULL ), error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ ) {

            throw ss::SSException();
        }   

        SET_FUNCTION_NAME( *conn );

        // cause any variables still holding a reference to this to be invalid so they cause
        // an error when passed to a sqlsrv function.  There's nothing we can do if the 
        // removal fails, so we just log it and move on.
        int zr = zend_hash_index_del( &EG( regular_list ), Z_RESVAL_P( conn_r ));
        
        if( zr == FAILURE ) {

            LOG( SEV_ERROR, "Failed to remove connection resource %1!d!", Z_RESVAL_P( conn_r ));
        }

        ZVAL_NULL( conn_r );

        RETURN_TRUE;
    }
    catch( core::CoreException& ) {
        
        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_close: Unknown exception caught." );
    }
}

void __cdecl sqlsrv_conn_dtor( zend_rsrc_list_entry *rsrc TSRMLS_DC )
{
    LOG_FUNCTION( "sqlsrv_conn_dtor" );

    // get the structure
    ss_sqlsrv_conn *conn = static_cast<ss_sqlsrv_conn*>( rsrc->ptr );
    SQLSRV_ASSERT( conn != NULL, "sqlsrv_conn_dtor: connection was null");

    SET_FUNCTION_NAME( *conn );

    // close all statements associated with the connection.
    sqlsrv_conn_close_stmts( conn TSRMLS_CC );

    // close the connection itself.
    core_sqlsrv_close( conn TSRMLS_CC );
    
    rsrc->ptr = NULL;
}
 
// sqlsrv_commit( resource $conn )
//
// Commits the current transaction on the specified connection and returns the
// connection to the auto-commit mode. The current transaction includes all
// statements on the specified connection that were executed after the call to
// sqlsrv_begin_transaction and before any calls to sqlsrv_rollback or
// sqlsrv_commit.  

// The SQLSRV driver is in auto-commit mode by
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
    LOG_FUNCTION( "sqlsrv_commit" );

    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    ss_sqlsrv_conn* conn = NULL;

    PROCESS_PARAMS( conn, "r", _FN_, 0 );

    // Return false if not in transaction
    CHECK_CUSTOM_ERROR(( conn->in_transaction == false ), *conn, SS_SQLSRV_ERROR_NOT_IN_TXN ) {
        RETURN_FALSE;
    }
    
    try {

        conn->in_transaction = false;
        core_sqlsrv_commit( conn TSRMLS_CC );
        RETURN_TRUE;

    }
    catch( core::CoreException& ) {
        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_commit: Unknown exception caught." );
    }
}

// sqlsrv_rollback( resource $conn )
//
// Rolls back the current transaction on the specified connection and returns
// the connection to the auto-commit mode. The current transaction includes all
// statements on the specified connection that were executed after the call to
// sqlsrv_begin_transaction and before any calls to sqlsrv_rollback or
// sqlsrv_commit.
// 
// The SQLSRV driver is in auto-commit mode by default. This
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
    LOG_FUNCTION( "sqlsrv_rollback" );

    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    ss_sqlsrv_conn* conn = NULL;

    PROCESS_PARAMS( conn, "r", _FN_, 0 );
    
    // Return false if not in transaction
    CHECK_CUSTOM_ERROR(( conn->in_transaction == false ), *conn, SS_SQLSRV_ERROR_NOT_IN_TXN ) {
        RETURN_FALSE;
    }

    try {
        
        conn->in_transaction = false;
        core_sqlsrv_rollback( conn TSRMLS_CC );
        RETURN_TRUE;
    }
    catch( core::CoreException& ){
        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_rollback: Unknown exception caught." );
    }
}

// sqlsrv_client_info
// Returns the ODBC driver's dll name, version and the ODBC version. Also returns
// the version of this extension.
// Parameters:
// $conn - The connection resource by which the client and server are connected.

PHP_FUNCTION( sqlsrv_client_info )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    LOG_FUNCTION( "sqlsrv_client_info" );
    ss_sqlsrv_conn* conn = NULL;
    PROCESS_PARAMS( conn, "r", _FN_, 0 );
    
    try {

        core_sqlsrv_get_client_info( conn, return_value TSRMLS_CC );
        
        // Add the sqlsrv driver's file version
        core::sqlsrv_add_assoc_string( *conn, return_value, "ExtensionVer", VER_FILEVERSION_STR, 1 /*duplicate*/ TSRMLS_CC );
    }

    catch( core::CoreException& ) {
        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_client_info: Unknown exception caught." );
    }
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
    try {

        SQLSRV_UNUSED( return_value_used );
        SQLSRV_UNUSED( this_ptr );
        SQLSRV_UNUSED( return_value_ptr );

        LOG_FUNCTION( "sqlsrv_server_info" );
        ss_sqlsrv_conn* conn = NULL;
        PROCESS_PARAMS( conn, "r", _FN_, 0 );

        core_sqlsrv_get_server_info( conn, return_value TSRMLS_CC );
    }

    catch( core::CoreException& ) {
        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_server_info: Unknown exception caught." );
    }
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
// $value | array($value [, $direction [, $phpType [, $sqlType]]])
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

    LOG_FUNCTION( "sqlsrv_prepare" );

    sqlsrv_malloc_auto_ptr<ss_sqlsrv_stmt> stmt;
    ss_sqlsrv_conn* conn = NULL;
    char *sql = NULL;
    unsigned int sql_len = 0;
    zval* params_z = NULL;
    zval* options_z = NULL;
    hash_auto_ptr ss_stmt_options_ht;
    zval_auto_ptr stmt_z;
    ALLOC_INIT_ZVAL( stmt_z );

    PROCESS_PARAMS( conn, "rs|a!a!", _FN_, 4, &sql, &sql_len, &params_z, &options_z );

    try {

        if( options_z && zend_hash_num_elements( Z_ARRVAL_P( options_z )) > 0 ) {

            // Initialize the options array to be passed to the core layer
            ALLOC_HASHTABLE( ss_stmt_options_ht );
            core::sqlsrv_zend_hash_init( *conn , ss_stmt_options_ht, 3 /* # of buckets */, NULL /*hashfn*/, 
                                         ZVAL_PTR_DTOR, 0 /*persistent*/ TSRMLS_CC );
            
            validate_stmt_options( *conn, options_z, ss_stmt_options_ht TSRMLS_CC );
        
        }

        if( params_z && Z_TYPE_P( params_z ) != IS_ARRAY ) {
            THROW_SS_ERROR( conn, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ );
        }

        if( options_z && Z_TYPE_P( options_z ) != IS_ARRAY ) {
            THROW_SS_ERROR( conn, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ );
        }

        if( sql == NULL ) {

            DIE( "sqlsrv_query: sql string was null." );
        }

        stmt = static_cast<ss_sqlsrv_stmt*>( core_sqlsrv_create_stmt( conn, core::allocate_stmt<ss_sqlsrv_stmt>, 
                                                                      ss_stmt_options_ht, SS_STMT_OPTS, 
                                                                      ss_error_handler, NULL TSRMLS_CC ) );
       
        core_sqlsrv_prepare( stmt, sql, sql_len TSRMLS_CC );
        
        mark_params_by_reference( stmt, params_z TSRMLS_CC );

        stmt->prepared = true;

        // register the statement with the PHP runtime        
        ss::zend_register_resource( stmt_z, stmt, ss_sqlsrv_stmt::descriptor, ss_sqlsrv_stmt::resource_name TSRMLS_CC );

        // store the resource id with the connection so the connection 
        // can release this statement when it closes.
        int next_index = zend_hash_next_free_element( conn->stmts );
        long rsrc_idx = Z_RESVAL_P( stmt_z );

        core::sqlsrv_zend_hash_index_update(*conn, conn->stmts, next_index, &rsrc_idx, sizeof( long ) TSRMLS_CC );

        stmt->conn_index = next_index;

        // the statement is now registered with EG( regular_list )
        stmt.transferred();

        zval_ptr_dtor( &return_value );
        *return_value_ptr = stmt_z;
        stmt_z.transferred();

    }
    catch( core::CoreException& ) {

        if( stmt ) {
            
            stmt->conn = NULL;
            stmt->~ss_sqlsrv_stmt();
        }
        if( Z_TYPE_P( stmt_z ) != IS_NULL ) {
            free_stmt_resource( stmt_z TSRMLS_CC );
        }

        RETURN_FALSE;
    }

    catch( ... ) {

        DIE( "sqlsrv_prepare: Unknown exception caught." );
    }
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

    LOG_FUNCTION( "sqlsrv_query" );

    ss_sqlsrv_conn* conn = NULL;
    sqlsrv_malloc_auto_ptr<ss_sqlsrv_stmt> stmt;
    char* sql = NULL;
    hash_auto_ptr ss_stmt_options_ht;
    int sql_len = 0;
    zval* options_z = NULL;
    zval* params_z = NULL;
    zval_auto_ptr stmt_z;
    
    PROCESS_PARAMS( conn, "rs|a!a!", _FN_, 4, &sql, &sql_len, &params_z, &options_z );

    try {

        // check for statement options
        if( options_z && zend_hash_num_elements( Z_ARRVAL_P( options_z )) > 0 ) {

            // Initialize the options array to be passed to the core layer
            ALLOC_HASHTABLE( ss_stmt_options_ht );
            core::sqlsrv_zend_hash_init( *conn , ss_stmt_options_ht, 3 /* # of buckets */, NULL /*hashfn*/, ZVAL_PTR_DTOR, 
                                         0 /*persistent*/ TSRMLS_CC );
            
            validate_stmt_options( *conn, options_z, ss_stmt_options_ht TSRMLS_CC );    
        }

        if( params_z && Z_TYPE_P( params_z ) != IS_ARRAY ) {
            THROW_SS_ERROR( conn, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ );
        }

        if( options_z && Z_TYPE_P( options_z ) != IS_ARRAY ) {
            THROW_SS_ERROR( conn, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ );
        }

        if( sql == NULL ) {

            DIE( "sqlsrv_query: sql string was null." );
        }

        stmt = static_cast<ss_sqlsrv_stmt*>( core_sqlsrv_create_stmt( conn, core::allocate_stmt<ss_sqlsrv_stmt>, 
                                                                      ss_stmt_options_ht, SS_STMT_OPTS, 
                                                                      ss_error_handler, NULL TSRMLS_CC ) );

        stmt->params_z = params_z;
        if( params_z ) {
            zval_add_ref( &params_z );
        }

        stmt->set_func( "sqlsrv_query" );

        bind_params( stmt TSRMLS_CC );

        // execute the statement
        core_sqlsrv_execute( stmt TSRMLS_CC, sql, sql_len );

        // register the statement with the PHP runtime        
        ALLOC_INIT_ZVAL( stmt_z );
        ss::zend_register_resource( stmt_z, stmt, ss_sqlsrv_stmt::descriptor, ss_sqlsrv_stmt::resource_name TSRMLS_CC );

        // store the resource id with the connection so the connection 
        // can release this statement when it closes.
        int next_index = zend_hash_next_free_element( conn->stmts );
        long rsrc_idx = Z_RESVAL_P( stmt_z );
        core::sqlsrv_zend_hash_index_update(*conn, conn->stmts, next_index, &rsrc_idx, sizeof( long ) TSRMLS_CC );
        stmt->conn_index = next_index;
        stmt.transferred();

        zval_ptr_dtor( &return_value );
        *return_value_ptr = stmt_z;
        stmt_z.transferred();
    }

    catch( core::CoreException& ) {

        if( stmt ) {

            stmt->conn = NULL;  // tell the statement that it isn't part of the connection so it doesn't try to remove itself
            stmt->~ss_sqlsrv_stmt();
        }
        if( stmt_z ) {

            free_stmt_resource( stmt_z TSRMLS_CC );
        }

        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_query: Unknown exception caught." );
    }
}

void free_stmt_resource( zval* stmt_z TSRMLS_DC )
{
    int zr = zend_hash_index_del( &EG( regular_list ), Z_RESVAL_P( stmt_z ));
    if( zr == FAILURE ) {
        LOG( SEV_ERROR, "Failed to remove stmt resource %1!d!", Z_RESVAL_P( stmt_z ));
    }

    ZVAL_NULL( stmt_z );
    zval_ptr_dtor( &stmt_z );
}

// internal connection functions

namespace {    

// must close all statement handles opened by this connection before closing the connection
// no errors are returned, since close should always succeed

void sqlsrv_conn_close_stmts( ss_sqlsrv_conn* conn TSRMLS_DC )
{
    //pre-condition check
    SQLSRV_ASSERT(( conn->handle() != NULL ), "sqlsrv_conn_close_stmts: Connection handle is NULL. Trying to destroy an "
                  "already destroyed connection.");
    SQLSRV_ASSERT(( conn->stmts ), "sqlsrv_conn_close_stmts: Connection doesn't contain a statement array." );
    
    // loop through the stmts hash table and destroy each stmt resource so we can close the 
    // ODBC connection
    for( zend_hash_internal_pointer_reset( conn->stmts );
         zend_hash_has_more_elements( conn->stmts ) == SUCCESS;
         zend_hash_move_forward( conn->stmts )) {

        long* rsrc_idx_ptr = NULL;
        
        try {
            // get the resource id for the next statement created with this connection
            core::sqlsrv_zend_hash_get_current_data( *conn, conn->stmts, reinterpret_cast<void**>( &rsrc_idx_ptr ) TSRMLS_CC );    
        }
        catch( core::CoreException& ) {

            DIE( "sqlsrv_conn_close_stmts: Failed to retrieve a statement resource from the connection" );
        }

        // see if the statement is still valid, and if not skip to the next one
        // presumably this should never happen because if it's in the list, it should still be valid
        // by virtue that a statement resource should remove itself from its connection when it is
        // destroyed in sqlsrv_stmt_dtor.  However, rather than die (assert), we simply skip this resource
        // and move to the next one.
        ss_sqlsrv_stmt* stmt = NULL;
        int type = -1;
        stmt = static_cast<ss_sqlsrv_stmt*>( zend_list_find( *rsrc_idx_ptr, &type ));
        if( stmt == NULL || type != ss_sqlsrv_stmt::descriptor ) {
            LOG( SEV_ERROR, "Non existent statement found in connection.  Statements should remove themselves"
                                      " from the connection so this shouldn't be out of sync." );
            continue;
        }

        // delete the statement by deleting it from Zend's resource list, which will force its destruction
        stmt->conn = NULL;

        try {

            // this would call the destructor on the statement.
            core::sqlsrv_zend_hash_index_del( *conn, &EG( regular_list ), *rsrc_idx_ptr TSRMLS_CC );
        }
        catch( core::CoreException& ) {
             LOG( SEV_ERROR, "Failed to remove statement resource %1!d! when closing the connection", *rsrc_idx_ptr );
        }
    }

    zend_hash_destroy( conn->stmts );
    FREE_HASHTABLE( conn->stmts );
    conn->stmts = NULL;
}

int get_conn_option_key( sqlsrv_context& ctx, char* key, unsigned int key_len, zval const* value_z TSRMLS_DC )    
{
    for( int i=0; SS_CONN_OPTS[ i ].conn_option_key != SQLSRV_CONN_OPTION_INVALID; ++i )
    {
        if( key_len == SS_CONN_OPTS[ i ].sqlsrv_len && !stricmp( key, SS_CONN_OPTS[ i ].sqlsrv_name )) {
            

             switch( SS_CONN_OPTS[ i ].value_type ) {

                case CONN_ATTR_BOOL:                        
                    // bool attributes can be either strings to be appended to the connection string
                    // as yes or no or integral connection attributes.  This will have to be reworked
                    // if we ever introduce a boolean connection option that maps to a string connection
                    // attribute.
                    break;
                case CONN_ATTR_INT:
                {
                    CHECK_CUSTOM_ERROR( (Z_TYPE_P( value_z ) != IS_LONG ), ctx, SQLSRV_ERROR_INVALID_OPTION_TYPE_INT,
                                        SS_CONN_OPTS[ i ].sqlsrv_name ) 
                    {
                        throw ss::SSException();
                    }
                    break;
                }
                case CONN_ATTR_STRING:
                {
                    CHECK_CUSTOM_ERROR( Z_TYPE_P( value_z ) != IS_STRING, ctx, SQLSRV_ERROR_INVALID_OPTION_TYPE_STRING, 
                                        SS_CONN_OPTS[ i ].sqlsrv_name ) {

                        throw ss::SSException();
                    }

                    char* value = Z_STRVAL_P( value_z );
                    int value_len = Z_STRLEN_P( value_z );
                    bool escaped = core_is_conn_opt_value_escaped( value, value_len );

                    CHECK_CUSTOM_ERROR( !escaped, ctx, SS_SQLSRV_ERROR_CONNECT_BRACES_NOT_ESCAPED, SS_CONN_OPTS[ i ].sqlsrv_name ) {

                        throw ss::SSException();
                    }
                    break;
                }
            }

            return SS_CONN_OPTS[ i ].conn_option_key;
        }
    }
    return SQLSRV_CONN_OPTION_INVALID;
}

int get_stmt_option_key( char* key, unsigned int key_len TSRMLS_DC )
{
    for( int i = 0; SS_STMT_OPTS[ i ].key != SQLSRV_STMT_OPTION_INVALID; ++i )
    {
        if( key_len == SS_STMT_OPTS[ i ].name_len && !stricmp( key, SS_STMT_OPTS[ i ].name )) {
            
            return SS_STMT_OPTS[ i ].key;
        }
    }
    return SQLSRV_STMT_OPTION_INVALID;
}

void add_stmt_option_key( sqlsrv_context& ctx, char* key, unsigned int key_len, 
                         HashTable* options_ht, zval** data TSRMLS_DC )
{
    int option_key = ::get_stmt_option_key( key, key_len TSRMLS_CC );   
    
    CHECK_CUSTOM_ERROR((option_key == SQLSRV_STMT_OPTION_INVALID ), ctx, SQLSRV_ERROR_INVALID_OPTION_KEY, key ) {

        throw ss::SSException();
    }        

    zval_add_ref( data );      // inc the ref count since this is going into the options_ht too.
    core::sqlsrv_zend_hash_index_update( ctx, options_ht, option_key, (void**)data, sizeof(zval*) TSRMLS_CC );
}

void add_conn_option_key( sqlsrv_context& ctx, char* key, unsigned int key_len, 
                         HashTable* options_ht, zval** data TSRMLS_DC )
{
    int option_key = ::get_conn_option_key( ctx, key, key_len, *data TSRMLS_CC );    
    CHECK_CUSTOM_ERROR((option_key == SQLSRV_STMT_OPTION_INVALID ), ctx, SS_SQLSRV_ERROR_INVALID_OPTION, key ) {

        throw ss::SSException();
    }        

    zval_add_ref( data );      // inc the ref count since this is going into the options_ht too.
    core::sqlsrv_zend_hash_index_update( ctx, options_ht, option_key, (void**)data, sizeof(zval*) TSRMLS_CC );
}

// Iterates through the list of statement options provided by the user and validates them 
// against the list of supported statement options by this driver. After validation
// creates a Hashtable of statement options to be sent to the core layer for processing.

void validate_stmt_options( sqlsrv_context& ctx, zval* stmt_options, __inout HashTable* ss_stmt_options_ht TSRMLS_DC )
{
    try {
        
        if( stmt_options ) {
           
            HashTable* options_ht = Z_ARRVAL_P( stmt_options );
    
            for( zend_hash_internal_pointer_reset( options_ht ); zend_hash_has_more_elements( options_ht ) == SUCCESS;
                 zend_hash_move_forward( options_ht )) {

                int type = HASH_KEY_NON_EXISTANT;
                char *key = NULL;
                unsigned int key_len = 0;
                unsigned long int_key = -1;
                zval** data;
                zval* conn_opt = NULL;
                int result = 0;

                type = zend_hash_get_current_key_ex( options_ht, &key, &key_len, &int_key, 0, NULL );
                
                if( type != HASH_KEY_IS_STRING ) {
                    std::ostringstream itoa;
                    itoa << int_key;
                    CHECK_CUSTOM_ERROR( true , ctx, SQLSRV_ERROR_INVALID_OPTION_KEY, itoa.str() ) {
                        throw core::CoreException();    
                    }
                }
                core::sqlsrv_zend_hash_get_current_data( ctx, options_ht, (void**) &data TSRMLS_CC );                    
                add_stmt_option_key( ctx, key, key_len, ss_stmt_options_ht, data TSRMLS_CC );
            } 
        }
    }
    catch( core::CoreException& ) {

        throw;
    }
}

// Iterates through the list of connection options provided by the user and validates them 
// against the predefined list of supported connection options by this driver. After validation
// creates a Hashtable of connection options to be sent to the core layer for processing.

void validate_conn_options( sqlsrv_context& ctx, zval* user_options_z, __out char** uid, __out char** pwd, __inout HashTable* ss_conn_options_ht TSRMLS_DC )
{
    try {

        if( user_options_z ) {

            HashTable* options_ht = Z_ARRVAL_P( user_options_z );
    
            for( zend_hash_internal_pointer_reset( options_ht ); zend_hash_has_more_elements( options_ht ) == SUCCESS;
                 zend_hash_move_forward( options_ht )) {

                int type = HASH_KEY_NON_EXISTANT;
                char *key = NULL;
                unsigned int key_len = 0;
                unsigned long int_key = -1;
                zval** data = NULL;

                type = zend_hash_get_current_key_ex( options_ht, &key, &key_len, &int_key, 0, NULL );
                
                CHECK_CUSTOM_ERROR(( type != HASH_KEY_IS_STRING ), ctx, SS_SQLSRV_ERROR_INVALID_CONNECTION_KEY ) {
                    throw ss::SSException();
                }
               
                core::sqlsrv_zend_hash_get_current_data( ctx, options_ht, (void**) &data TSRMLS_CC );    
                
                if( key_len == sizeof( SSConnOptionNames::UID ) && !stricmp( key, SSConnOptionNames::UID )) { 

                    *uid = Z_STRVAL_PP( data );
                }

                else if( key_len == sizeof( SSConnOptionNames::PWD ) && !stricmp( key, SSConnOptionNames::PWD )) { 
                    
                    *pwd = Z_STRVAL_PP( data );
                }
                else {

                    ::add_conn_option_key( ctx, key, key_len, ss_conn_options_ht, data TSRMLS_CC );
                }
            }
        } 
    }
    catch( core::CoreException& ) {
        throw;
    }
}

}   // namespace

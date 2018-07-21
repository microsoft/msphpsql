//---------------------------------------------------------------------------------------------------------------------------------
// File: conn.cpp
//
// Contents: Routines that use connection handles
//
// Microsoft Drivers 5.3 for PHP for SQL Server
// Copyright(c) Microsoft Corporation
// All rights reserved.
// MIT License
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files(the ""Software""), 
//  to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, 
//  and / or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions :
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
// THE SOFTWARE IS PROVIDED *AS IS*, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
//  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
//  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS 
//  IN THE SOFTWARE.
//---------------------------------------------------------------------------------------------------------------------------------

#include "php_sqlsrv.h"

#include <string>
#include <sstream>

// *** internal variables and constants ***

namespace {

// current subsytem.  defined for the CHECK_SQL_{ERROR|WARNING} macros
unsigned int current_log_subsystem = LOG_CONN;

struct date_as_string_func {

    static void func( connection_option const* /*option*/, _In_ zval* value, _Inout_ sqlsrv_conn* conn, std::string& /*conn_str*/ TSRMLS_DC )
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

    static void func( connection_option const* /*option*/, _Inout_ zval* value, _Inout_ sqlsrv_conn* conn, std::string& /*conn_str*/ TSRMLS_DC )
    {
         convert_to_string( value );
         const char* encoding = Z_STRVAL_P( value );
         size_t encoding_len = Z_STRLEN_P( value );

         zend_ulong index = -1;
         zend_string* key = NULL;
         void* ss_encoding_temp = NULL;

         ZEND_HASH_FOREACH_KEY_PTR( g_ss_encodings_ht, index, key, ss_encoding_temp ) {
             sqlsrv_encoding* ss_encoding = reinterpret_cast<sqlsrv_encoding*>( ss_encoding_temp );
             ss_encoding_temp = NULL;
             if (!strnicmp( encoding, ss_encoding->iana, encoding_len )) {

                 if ( ss_encoding->not_for_connection ) {
                     THROW_SS_ERROR( conn, SS_SQLSRV_ERROR_CONNECT_ILLEGAL_ENCODING, encoding );
                 }

                 conn->set_encoding( static_cast<SQLSRV_ENCODING>(ss_encoding->code_page ));
                 return;
             }
         } ZEND_HASH_FOREACH_END();

         THROW_SS_ERROR( conn, SS_SQLSRV_ERROR_CONNECT_ILLEGAL_ENCODING, encoding );
    }
};

struct bool_conn_str_func {

    static void func( _In_ connection_option const* option, _In_ zval* value, sqlsrv_conn* /*conn*/, _Out_ std::string& conn_str TSRMLS_DC )
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

struct int_conn_str_func {

    static void func( _In_ connection_option const* option, _In_ zval* value, sqlsrv_conn* /*conn*/, _Out_ std::string& conn_str TSRMLS_DC )
    {
        TSRMLS_C;
        SQLSRV_ASSERT( Z_TYPE_P( value ) == IS_LONG, "An integer is expected for this keyword" ) 

        std::string val_str = std::to_string( Z_LVAL_P( value ));
        
        conn_str += option->odbc_name;
        conn_str += "={";
        conn_str += val_str;
        conn_str += "};";
    }
};

template <unsigned int Attr>
struct int_conn_attr_func {

    static void func( connection_option const* /*option*/, _In_ zval* value, _Inout_ sqlsrv_conn* conn, std::string& /*conn_str*/ TSRMLS_DC )
    {
        try {
        
            core::SQLSetConnectAttr( conn, Attr, reinterpret_cast<SQLPOINTER>( Z_LVAL_P( value )), SQL_IS_UINTEGER TSRMLS_CC );
        }
        catch( core::CoreException& ) {
            throw;
        }
    }
};

template <unsigned int Attr>
struct bool_conn_attr_func {

    static void func( connection_option const* /*option*/, _In_ zval* value, _Inout_ sqlsrv_conn* conn, std::string& /*conn_str*/ TSRMLS_DC )
    {
         try {
             core::SQLSetConnectAttr(conn, Attr, reinterpret_cast<SQLPOINTER>((zend_long)zend_is_true(value)), SQL_IS_UINTEGER TSRMLS_CC);
        
        }
        catch( core::CoreException& ) {
            throw;
        }

    }
};

//// *** internal functions ***

void sqlsrv_conn_close_stmts( _Inout_ ss_sqlsrv_conn* conn TSRMLS_DC );
void validate_conn_options( _Inout_ sqlsrv_context& ctx, _In_ zval* user_options_z, _Inout_ char** uid, _Inout_ char** pwd, 
                            _Inout_ HashTable* ss_conn_options_ht TSRMLS_DC );
void validate_stmt_options( _Inout_ sqlsrv_context& ctx, _Inout_ zval* stmt_options, _Inout_ HashTable* ss_stmt_options_ht TSRMLS_DC );
void add_conn_option_key( _Inout_ sqlsrv_context& ctx, _In_ zend_string* key, _In_ size_t key_len, 
                         _Inout_ HashTable* options_ht, _Inout_ zval* data TSRMLS_DC );
void add_stmt_option_key( _Inout_ sqlsrv_context& ctx, _In_ zend_string* key, _In_ size_t key_len, _Inout_ HashTable* options_ht, _Inout_ zval* data TSRMLS_DC );
int get_conn_option_key( _Inout_ sqlsrv_context& ctx, _In_ zend_string* key, _In_ size_t key_len, _Inout_ zval const* value_z TSRMLS_DC );   
int get_stmt_option_key( _In_ zend_string* key, _In_ size_t key_len TSRMLS_DC );

}

// constants for parameters used by process_params function(s)
int ss_sqlsrv_conn::descriptor;
const char* ss_sqlsrv_conn::resource_name = "ss_sqlsrv_conn";

// connection specific parameter proccessing.  Use the generic function specialised to return a connection
// resource.
#define PROCESS_PARAMS( rsrc, param_spec, calling_func, param_count, ... )                                                          \
    rsrc = process_params<ss_sqlsrv_conn>( INTERNAL_FUNCTION_PARAM_PASSTHRU, param_spec, calling_func, param_count, ##__VA_ARGS__ );\
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
const char Authentication[] = "Authentication";
const char CharacterSet[] = "CharacterSet";
const char ColumnEncryption[] = "ColumnEncryption";
const char ConnectionPooling[] = "ConnectionPooling";
const char ConnectRetryCount[] = "ConnectRetryCount";
const char ConnectRetryInterval[] = "ConnectRetryInterval";
const char Database[] = "Database";
const char DateAsString[] = "ReturnDatesAsStrings";
const char Driver[] = "Driver";
const char Encrypt[] = "Encrypt";
const char Failover_Partner[] = "Failover_Partner";
const char KeyStoreAuthentication[] = "KeyStoreAuthentication";
const char KeyStorePrincipalId[] = "KeyStorePrincipalId";
const char KeyStoreSecret[] = "KeyStoreSecret";
const char LoginTimeout[] = "LoginTimeout";
const char MARS_Option[] = "MultipleActiveResultSets";
const char MultiSubnetFailover[] = "MultiSubnetFailover";
const char PWD[] = "PWD";
const char QuotedId[] = "QuotedId";
const char TraceFile[] = "TraceFile";
const char TraceOn[] = "TraceOn";
const char TrustServerCertificate[] = "TrustServerCertificate";
const char TransactionIsolation[] = "TransactionIsolation";
const char TransparentNetworkIPResolution[] = "TransparentNetworkIPResolution";
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
        std::unique_ptr<stmt_option_query_timeout>( new stmt_option_query_timeout )
    },
    { 
        SSStmtOptionNames::SEND_STREAMS_AT_EXEC, 
        sizeof( SSStmtOptionNames::SEND_STREAMS_AT_EXEC ),
        SQLSRV_STMT_OPTION_SEND_STREAMS_AT_EXEC, 
        std::unique_ptr<stmt_option_send_at_exec>( new stmt_option_send_at_exec )
    },
    { 
        SSStmtOptionNames::SCROLLABLE, 
        sizeof( SSStmtOptionNames::SCROLLABLE ),
        SQLSRV_STMT_OPTION_SCROLLABLE, 
        std::unique_ptr<stmt_option_ss_scrollable>( new stmt_option_ss_scrollable )
    },
    { 
        SSStmtOptionNames::CLIENT_BUFFER_MAX_SIZE, 
        sizeof( SSStmtOptionNames::CLIENT_BUFFER_MAX_SIZE ),
        SQLSRV_STMT_OPTION_CLIENT_BUFFER_MAX_SIZE, 
        std::unique_ptr<stmt_option_buffered_query_limit>( new stmt_option_buffered_query_limit )
    },
    { NULL, 0, SQLSRV_STMT_OPTION_INVALID, std::unique_ptr<stmt_option_functor>{} },
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
        SSConnOptionNames::Authentication,
        sizeof( SSConnOptionNames::Authentication ),
        SQLSRV_CONN_OPTION_AUTHENTICATION,
        ODBCConnOptions::Authentication,
        sizeof( ODBCConnOptions::Authentication ),
        CONN_ATTR_STRING,
        conn_str_append_func::func
    },
    {
        SSConnOptionNames::ConnectionPooling,
        sizeof(SSConnOptionNames::ConnectionPooling),
        SQLSRV_CONN_OPTION_CONN_POOLING,
        ODBCConnOptions::ConnectionPooling,
        sizeof(ODBCConnOptions::ConnectionPooling),
        CONN_ATTR_BOOL,
        conn_null_func::func
    },
    {
        SSConnOptionNames::Driver,
        sizeof(SSConnOptionNames::Driver),
        SQLSRV_CONN_OPTION_DRIVER,
        ODBCConnOptions::Driver,
        sizeof(ODBCConnOptions::Driver),
        CONN_ATTR_STRING,
        driver_set_func::func
    },
    {
        SSConnOptionNames::ColumnEncryption,
        sizeof(SSConnOptionNames::ColumnEncryption),
        SQLSRV_CONN_OPTION_COLUMNENCRYPTION,
        ODBCConnOptions::ColumnEncryption,
        sizeof(ODBCConnOptions::ColumnEncryption),
        CONN_ATTR_STRING,
        column_encryption_set_func::func
    },
    {
        SSConnOptionNames::ConnectRetryCount,
        sizeof( SSConnOptionNames::ConnectRetryCount ),
        SQLSRV_CONN_OPTION_CONN_RETRY_COUNT,
        ODBCConnOptions::ConnectRetryCount,
        sizeof( ODBCConnOptions::ConnectRetryCount ),
        CONN_ATTR_INT,
        int_conn_str_func::func
    },
    {
        SSConnOptionNames::ConnectRetryInterval,
        sizeof( SSConnOptionNames::ConnectRetryInterval ),
        SQLSRV_CONN_OPTION_CONN_RETRY_INTERVAL,
        ODBCConnOptions::ConnectRetryInterval,
        sizeof( ODBCConnOptions::ConnectRetryInterval ),
        CONN_ATTR_INT,
        int_conn_str_func::func
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
        SSConnOptionNames::KeyStoreAuthentication,
        sizeof( SSConnOptionNames::KeyStoreAuthentication ),
        SQLSRV_CONN_OPTION_KEYSTORE_AUTHENTICATION,
        ODBCConnOptions::KeyStoreAuthentication,
        sizeof( ODBCConnOptions::KeyStoreAuthentication ),
        CONN_ATTR_STRING,
        ce_akv_str_set_func::func 
    },
    {
        SSConnOptionNames::KeyStorePrincipalId,
        sizeof( SSConnOptionNames::KeyStorePrincipalId ),
        SQLSRV_CONN_OPTION_KEYSTORE_PRINCIPAL_ID,
        ODBCConnOptions::KeyStorePrincipalId,
        sizeof( ODBCConnOptions::KeyStorePrincipalId ),
        CONN_ATTR_STRING,
        ce_akv_str_set_func::func 
    },
    {
        SSConnOptionNames::KeyStoreSecret,
        sizeof( SSConnOptionNames::KeyStoreSecret ),
        SQLSRV_CONN_OPTION_KEYSTORE_SECRET,
        ODBCConnOptions::KeyStoreSecret,
        sizeof( ODBCConnOptions::KeyStoreSecret ),
        CONN_ATTR_STRING,
        ce_akv_str_set_func::func
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
        SSConnOptionNames::TransparentNetworkIPResolution,
        sizeof(SSConnOptionNames::TransparentNetworkIPResolution),
        SQLSRV_CONN_OPTION_TRANSPARENT_NETWORK_IP_RESOLUTION,
        ODBCConnOptions::TransparentNetworkIPResolution,
        sizeof(ODBCConnOptions::TransparentNetworkIPResolution),
        CONN_ATTR_STRING,
        conn_str_append_func::func
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
// Keywords section of Using Connection String Keywords with ODBC Driver 11 for SQL Server.
//
// $connectionInfo [OPTIONAL]: An associative array that contains connection
// attributes (for example, array("Database" => "AdventureWorks")).
//
// Return Value 
// A PHP connection resource. If a connection cannot be successfully created and
// opened, false is returned

PHP_FUNCTION ( sqlsrv_connect ) 
{
    
    LOG_FUNCTION( "sqlsrv_connect" );
    SET_FUNCTION_NAME( *g_ss_henv_cp );
    SET_FUNCTION_NAME( *g_ss_henv_ncp );

    reset_errors( TSRMLS_C );

    const char* server = NULL;
    zval* options_z = NULL;
    char* uid = NULL;
    char* pwd = NULL;
    size_t server_len = 0;
    zval conn_z;
    ZVAL_UNDEF(&conn_z);
    // get the server name and connection options
    int result = zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "s|a", &server, &server_len, &options_z );
    
    CHECK_CUSTOM_ERROR(( result == FAILURE ), *g_ss_henv_cp, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, "sqlsrv_connect" ) {
        RETURN_FALSE;
    }
    
    hash_auto_ptr ss_conn_options_ht;
    hash_auto_ptr stmts;
    ss_sqlsrv_conn* conn = NULL;

    try {

        // Initialize the options array to be passed to the core layer
        ALLOC_HASHTABLE( ss_conn_options_ht );
        
        core::sqlsrv_zend_hash_init( *g_ss_henv_cp, ss_conn_options_ht, 10 /* # of buckets */, 
                                 ZVAL_PTR_DTOR, 0 /*persistent*/ TSRMLS_CC );
   
        // Either of g_ss_henv_cp or g_ss_henv_ncp can be used to propagate the error.
        ::validate_conn_options( *g_ss_henv_cp, options_z, &uid, &pwd, ss_conn_options_ht TSRMLS_CC );   
     
        // call the core connect function  
        conn = static_cast<ss_sqlsrv_conn*>( core_sqlsrv_connect( *g_ss_henv_cp, *g_ss_henv_ncp, &core::allocate_conn<ss_sqlsrv_conn>,
                                                                  server, uid, pwd, ss_conn_options_ht, ss_error_handler,  
                                                                  SS_CONN_OPTS, NULL, "sqlsrv_connect" TSRMLS_CC )); 
        
        SQLSRV_ASSERT( conn != NULL, "sqlsrv_connect: Invalid connection returned.  Exception should have been thrown." );
        
        // create a bunch of statements
        ALLOC_HASHTABLE( stmts );
    
        core::sqlsrv_zend_hash_init( *g_ss_henv_cp, stmts, 5, NULL /* dtor */, 0 /* persistent */ TSRMLS_CC );

        // register the connection with the PHP runtime 
        
        ss::zend_register_resource(conn_z, conn, ss_sqlsrv_conn::descriptor, ss_sqlsrv_conn::resource_name TSRMLS_CC);

        conn->stmts = stmts;
        stmts.transferred();
        RETURN_RES( Z_RES(conn_z) );
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
    LOG_FUNCTION( "sqlsrv_close" );
    
    zval* conn_r = NULL;
    ss_sqlsrv_conn* conn = NULL;
    sqlsrv_context_auto_ptr error_ctx;

    reset_errors( TSRMLS_C );
    
    try {
        
        // dummy context to pass to the error handler
        error_ctx = new (sqlsrv_malloc( sizeof( sqlsrv_context ))) sqlsrv_context( 0, ss_error_handler, NULL );
        SET_FUNCTION_NAME( *error_ctx );

        if( zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r", &conn_r) == FAILURE ) {
        
             // Check if it was a zval
            int zr = zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "z", &conn_r );
            CHECK_CUSTOM_ERROR(( zr == FAILURE ), error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ ) {
                throw ss::SSException();
            }   
            
            // if sqlsrv_close was called on a non-existent connection then we just return success.
            if( Z_TYPE_P( conn_r ) == IS_NULL ) {
                RETURN_TRUE;
            }
            else { 
              THROW_CORE_ERROR( error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ );  
            }
        }
        SQLSRV_ASSERT( conn_r != NULL, "sqlsrv_close: conn_r was null" );
        conn = static_cast<ss_sqlsrv_conn*>( zend_fetch_resource( Z_RES_P( conn_r ) TSRMLS_CC, ss_sqlsrv_conn::resource_name, ss_sqlsrv_conn::descriptor ));

        // if sqlsrv_close was called on an already closed connection then we just return success.
        if ( Z_RES_TYPE_P( conn_r ) == RSRC_INVALID_TYPE) {
            RETURN_TRUE;
        }
    
        CHECK_CUSTOM_ERROR(( conn == NULL ), error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ ) {
          
            throw ss::SSException();
        }   

        SET_FUNCTION_NAME( *conn );
        
        // cause any variables still holding a reference to this to be invalid so they cause
        // an error when passed to a sqlsrv function.  There's nothing we can do if the 
        // removal fails, so we just log it and move on.
        if( zend_list_close( Z_RES_P( conn_r ) ) == FAILURE ) {
            LOG( SEV_ERROR, "Failed to remove connection resource %1!d!", Z_RES_HANDLE_P( conn_r ));
        }

        // when conn_r is first parsed in zend_parse_parameters, conn_r becomes a zval that points to a zend_resource with a refcount of 2
        // need to DELREF here so the refcount becomes 1 and conn_r can be appropriate destroyed by the garbage collector when it goes out of scope
        // zend_list_close only destroy the resource pointed to by Z_RES_P( conn_r ), not the zend_resource itself
        Z_TRY_DELREF_P(conn_r);
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

void __cdecl sqlsrv_conn_dtor( _Inout_ zend_resource *rsrc TSRMLS_DC )
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

    LOG_FUNCTION( "sqlsrv_client_info" );
    ss_sqlsrv_conn* conn = NULL;
    PROCESS_PARAMS( conn, "r", _FN_, 0 );
    
    try {

        core_sqlsrv_get_client_info( conn, return_value TSRMLS_CC );
        
        // Add the sqlsrv driver's file version
        //Declarations below eliminate compiler warnings about string constant to char* conversions
        const char* extver = "ExtensionVer";
        std::string filever = VER_FILEVERSION_STR;
        core::sqlsrv_add_assoc_string( *conn, return_value, extver, &filever[0], 1 /*duplicate*/ TSRMLS_CC );
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

    LOG_FUNCTION( "sqlsrv_prepare" );

    sqlsrv_malloc_auto_ptr<ss_sqlsrv_stmt> stmt;
    ss_sqlsrv_conn* conn = NULL;
    char *sql = NULL;
    zend_long sql_len = 0;
    zval* params_z = NULL;
    zval* options_z = NULL;
    hash_auto_ptr ss_stmt_options_ht;
    zval stmt_z;
    ZVAL_UNDEF(&stmt_z);

    PROCESS_PARAMS( conn, "rs|a!a!", _FN_, 4, &sql, &sql_len, &params_z, &options_z );

    try {

        if( options_z && zend_hash_num_elements( Z_ARRVAL_P( options_z )) > 0 ) {

            // Initialize the options array to be passed to the core layer
            ALLOC_HASHTABLE( ss_stmt_options_ht );
            core::sqlsrv_zend_hash_init( *conn , ss_stmt_options_ht, 3 /* # of buckets */, 
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

            DIE( "sqlsrv_prepare: sql string was null." );
        }

        stmt = static_cast<ss_sqlsrv_stmt*>( core_sqlsrv_create_stmt( conn, core::allocate_stmt<ss_sqlsrv_stmt>, 
                                                                      ss_stmt_options_ht, SS_STMT_OPTS, 
                                                                      ss_error_handler, NULL TSRMLS_CC ) );
       
        core_sqlsrv_prepare( stmt, sql, sql_len TSRMLS_CC );
        
        if (params_z) {
            stmt->params_z = (zval *)sqlsrv_malloc(sizeof(zval));
            ZVAL_COPY(stmt->params_z, params_z);
        }

        stmt->prepared = true;

        // register the statement with the PHP runtime        
        ss::zend_register_resource( stmt_z, stmt, ss_sqlsrv_stmt::descriptor, ss_sqlsrv_stmt::resource_name TSRMLS_CC );

        // store the resource id with the connection so the connection 
        // can release this statement when it closes.
        zend_long next_index = zend_hash_next_free_element( conn->stmts );
  
        core::sqlsrv_zend_hash_index_update(*conn, conn->stmts, next_index, &stmt_z TSRMLS_CC);

        stmt->conn_index = next_index;

        // the statement is now registered with EG( regular_list )
        stmt.transferred();
        
        RETURN_RES(Z_RES(stmt_z));

    }
    catch( core::CoreException& ) {

        if( stmt ) {
            
            stmt->conn = NULL;
            stmt->~ss_sqlsrv_stmt();
        }
        if (!Z_ISUNDEF(stmt_z)) {
            free_stmt_resource(&stmt_z TSRMLS_CC);
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

    LOG_FUNCTION( "sqlsrv_query" );

    ss_sqlsrv_conn* conn = NULL;
    sqlsrv_malloc_auto_ptr<ss_sqlsrv_stmt> stmt;
    char* sql = NULL;
    hash_auto_ptr ss_stmt_options_ht;
    size_t sql_len = 0;
    zval* options_z = NULL;
    zval* params_z = NULL;  
    zval stmt_z;
    ZVAL_UNDEF(&stmt_z);

    PROCESS_PARAMS( conn, "rs|a!a!", _FN_, 4, &sql, &sql_len, &params_z, &options_z );

    try {

        // check for statement options
        if( options_z && zend_hash_num_elements( Z_ARRVAL_P( options_z )) > 0 ) {

            // Initialize the options array to be passed to the core layer
            ALLOC_HASHTABLE( ss_stmt_options_ht );
            core::sqlsrv_zend_hash_init( *conn , ss_stmt_options_ht, 3 /* # of buckets */, ZVAL_PTR_DTOR, 
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

        if( params_z ) {
            stmt->params_z = (zval *)sqlsrv_malloc(sizeof(zval));
            ZVAL_COPY(stmt->params_z, params_z);
        }

        stmt->set_func( "sqlsrv_query" );

        bind_params( stmt TSRMLS_CC );

        // execute the statement
        core_sqlsrv_execute( stmt TSRMLS_CC, sql, static_cast<int>( sql_len ) );
       
        // register the statement with the PHP runtime 
        ss::zend_register_resource(stmt_z, stmt, ss_sqlsrv_stmt::descriptor, ss_sqlsrv_stmt::resource_name TSRMLS_CC);
        // store the resource id with the connection so the connection 
        // can release this statement when it closes.
        zend_ulong next_index = zend_hash_next_free_element( conn->stmts );
     
        core::sqlsrv_zend_hash_index_update(*conn, conn->stmts, next_index, &stmt_z TSRMLS_CC);
        stmt->conn_index = next_index;
        stmt.transferred();

        RETURN_RES(Z_RES(stmt_z));
    }

    catch( core::CoreException& ) {

        if( stmt ) {

            stmt->conn = NULL;  // tell the statement that it isn't part of the connection so it doesn't try to remove itself
            stmt->~ss_sqlsrv_stmt();
        }
        if (!Z_ISUNDEF(stmt_z)) {
            free_stmt_resource(&stmt_z TSRMLS_CC);
        }

        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_query: Unknown exception caught." );
    }
}

void free_stmt_resource( _Inout_ zval* stmt_z TSRMLS_DC )
{
    if( FAILURE == zend_list_close( Z_RES_P( stmt_z ))) {
        LOG(SEV_ERROR, "Failed to remove stmt resource %1!d!", Z_RES_HANDLE_P(stmt_z));
    }
    ZVAL_NULL( stmt_z );
    zval_ptr_dtor(stmt_z);
}

// internal connection functions

namespace {    

// must close all statement handles opened by this connection before closing the connection
// no errors are returned, since close should always succeed

void sqlsrv_conn_close_stmts( _Inout_ ss_sqlsrv_conn* conn TSRMLS_DC )
{
    //pre-condition check
    SQLSRV_ASSERT(( conn->handle() != NULL ), "sqlsrv_conn_close_stmts: Connection handle is NULL. Trying to destroy an "
                  "already destroyed connection.");
    SQLSRV_ASSERT(( conn->stmts ), "sqlsrv_conn_close_stmts: Connection doesn't contain a statement array." );
    
    // loop through the stmts hash table and destroy each stmt resource so we can close the 
    // ODBC connection

    zval* rsrc_ptr = NULL;
    ZEND_HASH_FOREACH_VAL( conn->stmts, rsrc_ptr ) {
        try {
            int zr = ( rsrc_ptr ) != NULL ? SUCCESS : FAILURE;
            CHECK_ZEND_ERROR( zr, *conn, SQLSRV_ERROR_ZEND_HASH ) {
                throw core::CoreException();
            }
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
        stmt = static_cast<ss_sqlsrv_stmt*>( Z_RES_VAL_P( rsrc_ptr ));
        if( stmt == NULL || Z_RES_TYPE_P( rsrc_ptr ) != ss_sqlsrv_stmt::descriptor ) {
            LOG( SEV_ERROR, "Non existent statement found in connection.  Statements should remove themselves"
                " from the connection so this shouldn't be out of sync." );
            continue;
        }
        // delete the statement by deleting it from Zend's resource list, which will force its destruction
        stmt->conn = NULL;

        // this would call the destructor on the statement.
        // There's nothing we can do if the removal fails, so we just log it and move on.
        if( zend_list_close( Z_RES_P(rsrc_ptr) ) == FAILURE ) {
            LOG(SEV_ERROR, "Failed to remove statement resource %1!d! when closing the connection", Z_RES_HANDLE_P(rsrc_ptr));
        }
    } ZEND_HASH_FOREACH_END();

    zend_hash_destroy( conn->stmts );
    FREE_HASHTABLE( conn->stmts );
    conn->stmts = NULL;
}

int get_conn_option_key( _Inout_ sqlsrv_context& ctx, _In_ zend_string* key, _In_ size_t key_len, _Inout_ zval const* value_z TSRMLS_DC )    
{
    for( int i=0; SS_CONN_OPTS[ i ].conn_option_key != SQLSRV_CONN_OPTION_INVALID; ++i )
    {
        if( key_len == SS_CONN_OPTS[ i ].sqlsrv_len && !stricmp( ZSTR_VAL( key ), SS_CONN_OPTS[ i ].sqlsrv_name )) {
            

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
                    size_t value_len = Z_STRLEN_P( value_z );
                    bool escaped = core_is_conn_opt_value_escaped( value, value_len );

                    CHECK_CUSTOM_ERROR( !escaped, ctx, SS_SQLSRV_ERROR_CONNECT_BRACES_NOT_ESCAPED, SS_CONN_OPTS[ i ].sqlsrv_name ) {

                        throw ss::SSException();
                    }

                    bool valid = true;
                    if( stricmp( SS_CONN_OPTS[i].sqlsrv_name, SSConnOptionNames::Authentication ) == 0 ) {
                        valid = core_is_authentication_option_valid( value, value_len );
                    }

                    CHECK_CUSTOM_ERROR( !valid, ctx, SS_SQLSRV_ERROR_INVALID_AUTHENTICATION_OPTION, SS_CONN_OPTS[ i ].sqlsrv_name ) {

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

int get_stmt_option_key( _In_ zend_string* key, _In_ size_t key_len TSRMLS_DC )
{
    for( int i = 0; SS_STMT_OPTS[ i ].key != SQLSRV_STMT_OPTION_INVALID; ++i )
    {
        if( key_len == SS_STMT_OPTS[ i ].name_len && !stricmp( ZSTR_VAL( key ), SS_STMT_OPTS[ i ].name )) {
            return SS_STMT_OPTS[ i ].key;
        }
    }
    return SQLSRV_STMT_OPTION_INVALID;
}

void add_stmt_option_key( _Inout_ sqlsrv_context& ctx, _In_ zend_string* key, _In_ size_t key_len, 
                         _Inout_ HashTable* options_ht, _Inout_ zval* data TSRMLS_DC )
{
    int option_key = ::get_stmt_option_key( key, key_len TSRMLS_CC );   
    
    CHECK_CUSTOM_ERROR((option_key == SQLSRV_STMT_OPTION_INVALID ), ctx, SQLSRV_ERROR_INVALID_OPTION_KEY, ZSTR_VAL( key ) ) {

        throw ss::SSException();
    }        

    Z_TRY_ADDREF_P(data);      // inc the ref count since this is going into the options_ht too.
    core::sqlsrv_zend_hash_index_update( ctx, options_ht, option_key, data TSRMLS_CC );
}

void add_conn_option_key( _Inout_ sqlsrv_context& ctx, _In_ zend_string* key, _In_ size_t key_len, 
                         _Inout_ HashTable* options_ht, _Inout_ zval* data TSRMLS_DC )
{
    int option_key = ::get_conn_option_key( ctx, key, key_len, data TSRMLS_CC );    
    CHECK_CUSTOM_ERROR((option_key == SQLSRV_STMT_OPTION_INVALID ), ctx, SS_SQLSRV_ERROR_INVALID_OPTION, ZSTR_VAL( key ) ) {

        throw ss::SSException();
    }        

    Z_TRY_ADDREF_P(data);      // inc the ref count since this is going into the options_ht too.
    core::sqlsrv_zend_hash_index_update( ctx, options_ht, option_key, data TSRMLS_CC );
}

// Iterates through the list of statement options provided by the user and validates them 
// against the list of supported statement options by this driver. After validation
// creates a Hashtable of statement options to be sent to the core layer for processing.

void validate_stmt_options( _Inout_ sqlsrv_context& ctx, _Inout_ zval* stmt_options, _Inout_ HashTable* ss_stmt_options_ht TSRMLS_DC )
{
    try {
        if( stmt_options ) {
           
            HashTable* options_ht = Z_ARRVAL_P( stmt_options );
            zend_ulong int_key = -1;
            zend_string *key = NULL;
            zval* data = NULL;
            ZEND_HASH_FOREACH_KEY_VAL( options_ht, int_key, key, data ) {
                int type = HASH_KEY_NON_EXISTENT;
                size_t key_len = 0;

                type = key ? HASH_KEY_IS_STRING : HASH_KEY_IS_LONG;

                if (type != HASH_KEY_IS_STRING) {
                    CHECK_CUSTOM_ERROR(true, ctx, SQLSRV_ERROR_INVALID_OPTION_KEY, std::to_string( int_key ).c_str() ) {
                        throw core::CoreException();
                    }
                }
                else if ( key != NULL ) {
                    key_len = ZSTR_LEN( key ) + 1;
                    add_stmt_option_key( ctx, key, key_len, ss_stmt_options_ht, data TSRMLS_CC );
                }
                else {
                    DIE( "validate_stmt_options: key was null." );
                }
            } ZEND_HASH_FOREACH_END();
        }
    }
    catch( core::CoreException& ) {

        throw;
    }
}

// Iterates through the list of connection options provided by the user and validates them 
// against the predefined list of supported connection options by this driver. After validation
// creates a Hashtable of connection options to be sent to the core layer for processing.

void validate_conn_options( _Inout_ sqlsrv_context& ctx, _In_ zval* user_options_z, _Inout_ char** uid, _Inout_ char** pwd, _Inout_ HashTable* ss_conn_options_ht TSRMLS_DC )
{
    try {

        if( user_options_z ) {

            HashTable* options_ht = Z_ARRVAL_P( user_options_z );
            zend_ulong int_key = -1;
            zend_string *key = NULL;
            zval* data = NULL;
            ZEND_HASH_FOREACH_KEY_VAL( options_ht, int_key, key, data ) {
                int type = HASH_KEY_NON_EXISTENT;
                type = key ? HASH_KEY_IS_STRING : HASH_KEY_IS_LONG;

                CHECK_CUSTOM_ERROR(( Z_TYPE_P( data ) == IS_NULL || Z_TYPE_P( data ) == IS_UNDEF ), ctx, SS_SQLSRV_ERROR_INVALID_OPTION, key) {
                    throw ss::SSException();
                }

                CHECK_CUSTOM_ERROR(( type != HASH_KEY_IS_STRING ), ctx, SS_SQLSRV_ERROR_INVALID_CONNECTION_KEY ) {
                    throw ss::SSException();
                }
                if ( key != NULL ) {
                    // Length of the key string does not include the null terminator in PHP7, +1 has to be added
                    size_t key_len = ZSTR_LEN( key ) + 1;
                    if ( key_len == sizeof( SSConnOptionNames::UID ) && !stricmp( ZSTR_VAL( key ), SSConnOptionNames::UID )) {

                        *uid = Z_STRVAL_P( data );
                    }

                    else if ( key_len == sizeof( SSConnOptionNames::PWD ) && !stricmp( ZSTR_VAL( key ), SSConnOptionNames::PWD )) {

                        *pwd = Z_STRVAL_P( data );
                    }
                    else {

                        ::add_conn_option_key( ctx, key, key_len, ss_conn_options_ht, data TSRMLS_CC );
                    }
                }
                else {
                    DIE( "validate_conn_options: key was null." );
                }
            } ZEND_HASH_FOREACH_END();
        } 
    }
    catch( core::CoreException& ) {
        throw;
    }
}

}   // namespace

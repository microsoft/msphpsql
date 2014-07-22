//---------------------------------------------------------------------------------------------------------------------------------
// File: core_conn.cpp
//
// Contents: Core routines that use connection handles shared between sqlsrv and pdo_sqlsrv
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

#include "core_sqlsrv.h"

#include <php.h>
#include <psapi.h>
#include <windows.h>
#include <winver.h>

#include <string>
#include <sstream>

// *** internal variables and constants ***

namespace {

// *** internal constants ***
// an arbitrary figure that should be large enough for most connection strings.
const int DEFAULT_CONN_STR_LEN = 2048;

// length of buffer used to retrieve information for client and server info buffers
const int INFO_BUFFER_LEN = 256;

// processor architectures
const char* PROCESSOR_ARCH[] = { "x86", "x64", "ia64" };

// ODBC driver name.
const char CONNECTION_STRING_DRIVER_NAME[] = "Driver={SQL Server Native Client 11.0};";

// default options if only the server is specified
const char CONNECTION_STRING_DEFAULT_OPTIONS[] = "Mars_Connection={Yes}";

// connection option appended when no user name or password is given
const char CONNECTION_OPTION_NO_CREDENTIALS[] = "Trusted_Connection={Yes};";

// connection option appended for MARS when MARS isn't explicitly mentioned
const char CONNECTION_OPTION_MARS_ON[] = "MARS_Connection={Yes};";

// *** internal function prototypes ***

void build_connection_string_and_set_conn_attr( sqlsrv_conn* conn, const char* server, const char* uid, const char* pwd, 
                                                     HashTable* options_ht, const connection_option valid_conn_opts[], 
                                                     void* driver,__inout std::string& connection_string TSRMLS_DC );
void determine_server_version( sqlsrv_conn* conn TSRMLS_DC );
const char* get_processor_arch( void );
void get_server_version( sqlsrv_conn* conn, char** server_version, SQLSMALLINT& len TSRMLS_DC );
connection_option const* get_connection_option( sqlsrv_conn* conn, const char* key, unsigned int key_len TSRMLS_DC );
void common_conn_str_append_func( const char* odbc_name, const char* val, int val_len, std::string& conn_str TSRMLS_DC );

}

// core_sqlsrv_connect
// opens a connection and returns a sqlsrv_conn structure.
// Parameters:
// henv_cp           - connection pooled env context
// henv_ncp          - non connection pooled env context
// server            - name of the server we're connecting to
// uid               - username  
// pwd               - password
// options_ht        - zend_hash list of options
// err               - error callback to put into the connection's context
// valid_conn_opts[] - array of valid driver supported connection options.
// driver            - reference to caller
// Return
// A sqlsrv_conn structure. An exception is thrown if an error occurs

sqlsrv_conn* core_sqlsrv_connect( sqlsrv_context& henv_cp, sqlsrv_context& henv_ncp, driver_conn_factory conn_factory,
                                  const char* server, const char* uid, const char* pwd, 
                                  HashTable* options_ht, error_callback err, const connection_option valid_conn_opts[], 
				                  void* driver, const char* driver_func TSRMLS_DC )

{
    SQLRETURN r;
    std::string conn_str;
    conn_str.reserve( DEFAULT_CONN_STR_LEN );
    sqlsrv_malloc_auto_ptr<sqlsrv_conn> conn;
    sqlsrv_malloc_auto_ptr<wchar_t> wconn_string;
    unsigned int wconn_len = 0;

    try {

    sqlsrv_context* henv = &henv_cp;   // by default use the connection pooling henv

    // check the connection pooling setting to determine which henv to use to allocate the connection handle
    // we do this earlier because we have to allocate the connection handle prior to setting attributes on
    // it in build_connection_string_and_set_conn_attr.
    
    if( options_ht && zend_hash_num_elements( options_ht ) > 0 ) {

        zval** option_zz = NULL;
        int zr = SUCCESS;

        zr = zend_hash_index_find( options_ht, SQLSRV_CONN_OPTION_CONN_POOLING, reinterpret_cast<void**>( &option_zz ));
        if( zr != FAILURE ) {

            // if the option was found and it's not true, then use the non pooled environment handle
            if(( Z_TYPE_PP( option_zz ) == IS_STRING && !core_str_zval_is_true( *option_zz )) || !zend_is_true( *option_zz ) ) {
                
                henv = &henv_ncp;   
            }
        }
    }

    SQLHANDLE temp_conn_h;
    core::SQLAllocHandle( SQL_HANDLE_DBC, *henv, &temp_conn_h TSRMLS_CC );

    conn = conn_factory( temp_conn_h, err, driver TSRMLS_CC );
    conn->set_func( driver_func );


    build_connection_string_and_set_conn_attr( conn, server, uid, pwd, options_ht, valid_conn_opts, driver, 
                                               conn_str TSRMLS_CC );
    
    // We only support UTF-8 encoding for connection string.
    // Convert our UTF-8 connection string to UTF-16 before connecting with SQLDriverConnnectW
    wconn_len = (conn_str.length() + 1) * sizeof( wchar_t );
    wconn_string = utf16_string_from_mbcs_string( SQLSRV_ENCODING_UTF8, conn_str.c_str(), conn_str.length(), &wconn_len );
    CHECK_CUSTOM_ERROR( wconn_string == NULL, conn, SQLSRV_ERROR_CONNECT_STRING_ENCODING_TRANSLATE, get_last_error_message() )
    {
        throw core::CoreException();
    }
    
    SQLSMALLINT output_conn_size;
    r = SQLDriverConnectW( conn->handle(), NULL, reinterpret_cast<SQLWCHAR*>( wconn_string.get() ),
                           static_cast<SQLSMALLINT>( wconn_len ), NULL, 
                          0, &output_conn_size, SQL_DRIVER_NOPROMPT );
    // clear the connection string from memory to remove sensitive data (such as a password).
    memset( const_cast<char*>( conn_str.c_str()), 0, conn_str.size() );
    memset( wconn_string, 0, wconn_len * sizeof( wchar_t )); // wconn_len is the number of characters, not bytes
    conn_str.clear();

    if( !SQL_SUCCEEDED( r )) {
        SQLCHAR state[ SQL_SQLSTATE_BUFSIZE ];
        SQLSMALLINT len;
        SQLRETURN r = SQLGetDiagField( SQL_HANDLE_DBC, conn->handle(), 1, SQL_DIAG_SQLSTATE, state, SQL_SQLSTATE_BUFSIZE, &len );
        // if it's a IM002, meaning that the correct ODBC driver is not installed
        CHECK_CUSTOM_ERROR( SQL_SUCCEEDED( r ) && state[0] == 'I' && state[1] == 'M' && state[2] == '0' && state[3] == '0' &&
                            state[4] == '2', conn, SQLSRV_ERROR_DRIVER_NOT_INSTALLED, get_processor_arch() ) {
            throw core::CoreException();
        }
    }
    CHECK_SQL_ERROR( r, conn ) {
        throw core::CoreException();
    }

    CHECK_SQL_WARNING_AS_ERROR( r, conn ) {
        throw core::CoreException();
    }

    // determine the version of the server we're connected to.  The server version is left in the 
    // connection upon return.
    determine_server_version( conn TSRMLS_CC );

    }
    catch( std::bad_alloc& ) {
        memset( const_cast<char*>( conn_str.c_str()), 0, conn_str.size() );
        memset( wconn_string, 0, wconn_len * sizeof( wchar_t )); // wconn_len is the number of characters, not bytes
        conn->invalidate();
        DIE( "C++ memory allocation failure building the connection string." );
    }
    catch( std::out_of_range const& ex ) {
        memset( const_cast<char*>( conn_str.c_str()), 0, conn_str.size() );
        memset( wconn_string, 0, wconn_len * sizeof( wchar_t )); // wconn_len is the number of characters, not bytes
        LOG( SEV_ERROR, "C++ exception returned: %1!s!", ex.what() );
        conn->invalidate();
        throw;
    }
    catch( std::length_error const& ex ) {
        memset( const_cast<char*>( conn_str.c_str()), 0, conn_str.size() );
        memset( wconn_string, 0, wconn_len * sizeof( wchar_t )); // wconn_len is the number of characters, not bytes
        LOG( SEV_ERROR, "C++ exception returned: %1!s!", ex.what() );
        conn->invalidate();
        throw;
    }
    catch( core::CoreException&  ) {
        memset( const_cast<char*>( conn_str.c_str()), 0, conn_str.size() );
        memset( wconn_string, 0, wconn_len * sizeof( wchar_t )); // wconn_len is the number of characters, not bytes
        conn->invalidate();
        throw;        
    }

    sqlsrv_conn* return_conn = conn;
    conn.transferred();

    return return_conn;
}



// core_sqlsrv_begin_transaction
// Begins a transaction on a specified connection. The current transaction
// includes all statements on the specified connection that were executed after
// the call to core_sqlsrv_begin_transaction and before any calls to 
// core_sqlsrv_rollback or core_sqlsrv_commit.
// The default transaction mode is auto-commit. This means that all queries
// are automatically committed upon success unless they have been designated
// as part of an explicit transaction by using core_sqlsrv_begin_transaction.
// Parameters: 
// sqlsrv_conn*: The connection with which the transaction is associated.

void core_sqlsrv_begin_transaction( sqlsrv_conn* conn TSRMLS_DC )
{
    try {

        DEBUG_SQLSRV_ASSERT( conn != NULL, "core_sqlsrv_begin_transaction: connection object was null." );
      
        core::SQLSetConnectAttr( conn, SQL_ATTR_AUTOCOMMIT, reinterpret_cast<SQLPOINTER>( SQL_AUTOCOMMIT_OFF ), 
                                 SQL_IS_UINTEGER TSRMLS_CC );
    }
    catch ( core::CoreException& ) {
        throw;
    }
}

// core_sqlsrv_commit
// Commits the current transaction on the specified connection and returns the
// connection to the auto-commit mode. The current transaction includes all
// statements on the specified connection that were executed after the call to
// core_sqlsrv_begin_transaction and before any calls to core_sqlsrv_rollback or
// core_sqlsrv_commit. 
// Parameters:
// sqlsrv_conn*: The connection on which the transaction is active.

void core_sqlsrv_commit( sqlsrv_conn* conn TSRMLS_DC )
{
    try {
        
        DEBUG_SQLSRV_ASSERT( conn != NULL, "core_sqlsrv_commit: connection object was null." );
        
        core::SQLEndTran( SQL_HANDLE_DBC, conn, SQL_COMMIT TSRMLS_CC );

        core::SQLSetConnectAttr( conn, SQL_ATTR_AUTOCOMMIT, reinterpret_cast<SQLPOINTER>( SQL_AUTOCOMMIT_ON ), 
                                SQL_IS_UINTEGER TSRMLS_CC );
    }
    catch ( core::CoreException& ) {
        throw;
    }
}

// core_sqlsrv_rollback
// Rolls back the current transaction on the specified connection and returns
// the connection to the auto-commit mode. The current transaction includes all
// statements on the specified connection that were executed after the call to
// core_sqlsrv_begin_transaction and before any calls to core_sqlsrv_rollback or
// core_sqlsrv_commit.
// Parameters:
// sqlsrv_conn*: The connection on which the transaction is active.

void core_sqlsrv_rollback( sqlsrv_conn* conn TSRMLS_DC )
{
    try {

        DEBUG_SQLSRV_ASSERT( conn != NULL, "core_sqlsrv_rollback: connection object was null." );
        
        core::SQLEndTran( SQL_HANDLE_DBC, conn, SQL_ROLLBACK TSRMLS_CC );

        core::SQLSetConnectAttr( conn, SQL_ATTR_AUTOCOMMIT, reinterpret_cast<SQLPOINTER>( SQL_AUTOCOMMIT_ON ), 
                                 SQL_IS_UINTEGER TSRMLS_CC );
        
    }
    catch ( core::CoreException& ) {
        throw;
    }
}

// core_sqlsrv_close
// Called when a connection resource is destroyed by the Zend engine.  
// Parameters:
// conn - The current active connection.
void core_sqlsrv_close( sqlsrv_conn* conn TSRMLS_DC )
{
    // if the connection wasn't successful, just return.
    if( conn == NULL )
        return;

    try {
     
        // rollback any transaction in progress (we don't care about the return result)
        core::SQLEndTran( SQL_HANDLE_DBC, conn, SQL_ROLLBACK TSRMLS_CC );
    }
    catch( core::CoreException& ) {
        LOG( SEV_ERROR, "Transaction rollback failed when closing the connection." );
    }

    // disconnect from the server
    SQLRETURN r = SQLDisconnect( conn->handle() );
    if( !SQL_SUCCEEDED( r )) { 
        LOG( SEV_ERROR, "Disconnect failed when closing the connection." );
    }

    // free the connection handle
    conn->invalidate();

    sqlsrv_free( conn );
}

// core_sqlsrv_prepare
// Create a statement object and prepare the SQL query passed in for execution at a later time.
// Parameters:
// stmt - statement to be prepared
// sql - T-SQL command to prepare
// sql_len - length of the T-SQL string

void core_sqlsrv_prepare( sqlsrv_stmt* stmt, const char* sql, long sql_len TSRMLS_DC )
{
    try {

        // convert the string from its encoding to UTf-16
        // if the string is empty, we initialize the fields and skip since an empty string is a 
        // failure case for utf16_string_from_mbcs_string 
        sqlsrv_malloc_auto_ptr<wchar_t> wsql_string;
        unsigned int wsql_len = 0;
        if( sql_len == 0 || ( sql[0] == '\0' && sql_len == 1 )) {
            wsql_string = reinterpret_cast<wchar_t*>( sqlsrv_malloc( sizeof( wchar_t )));
            wsql_string[0] = L'\0';
            wsql_len = 0;
        }
        else {
            SQLSRV_ENCODING encoding = (( stmt->encoding() == SQLSRV_ENCODING_DEFAULT ) ? stmt->conn->encoding() :
                                        stmt->encoding() );
            wsql_string = utf16_string_from_mbcs_string( encoding, reinterpret_cast<const char*>( sql ),
                                                         sql_len, &wsql_len );
            CHECK_CUSTOM_ERROR( wsql_string == NULL, stmt, SQLSRV_ERROR_QUERY_STRING_ENCODING_TRANSLATE, 
                                get_last_error_message() ) {
                throw core::CoreException();
            }
        }

        // prepare our wide char query string
        core::SQLPrepareW( stmt, reinterpret_cast<SQLWCHAR*>( wsql_string.get() ), wsql_len TSRMLS_CC );
    }
    catch( core::CoreException& ) {

        throw;
    }
} 

// core_sqlsrv_get_server_version
// Determines the vesrion of the SQL Server we are connected to. Calls a helper function
// get_server_version to get the version of SQL Server.
// Parameters:
// conn             - The connection resource by which the client and server are connected.
// *server_version  - zval for returning results.

void core_sqlsrv_get_server_version( sqlsrv_conn* conn, __out zval *server_version TSRMLS_DC )
{
    try {
        
        sqlsrv_malloc_auto_ptr<char> buffer;
        SQLSMALLINT buffer_len = 0;

        get_server_version( conn, &buffer, buffer_len TSRMLS_CC );
        ZVAL_STRINGL( server_version, buffer, buffer_len, 0 );
        buffer.transferred();
    }
    
    catch( core::CoreException& ) {
        throw;
    }
}

// core_sqlsrv_get_server_info
// Returns the Database name, the name of the SQL Server we are connected to
// and the version of the SQL Server.
// Parameters:
// conn         - The connection resource by which the client and server are connected.
// *server_info - zval for returning results.

void core_sqlsrv_get_server_info( sqlsrv_conn* conn, __out zval *server_info TSRMLS_DC )
{
    try {

        sqlsrv_malloc_auto_ptr<char> buffer;
        SQLSMALLINT buffer_len = 0;

        // initialize the array
        core::sqlsrv_array_init( *conn, server_info TSRMLS_CC );
      
        // Get the database name
        buffer = static_cast<char*>( sqlsrv_malloc( INFO_BUFFER_LEN ));
        core::SQLGetInfo( conn, SQL_DATABASE_NAME, buffer, INFO_BUFFER_LEN, &buffer_len TSRMLS_CC );
        core::sqlsrv_add_assoc_string( *conn, server_info, "CurrentDatabase", buffer, 0 /*duplicate*/ TSRMLS_CC );
        buffer.transferred();
      
        // Get the server version
        get_server_version( conn, &buffer, buffer_len TSRMLS_CC );
        core::sqlsrv_add_assoc_string( *conn, server_info, "SQLServerVersion", buffer, 0 /*duplicate*/ TSRMLS_CC );
        buffer.transferred();  
        
        // Get the server name
        buffer = static_cast<char*>( sqlsrv_malloc( INFO_BUFFER_LEN ));
        core::SQLGetInfo( conn, SQL_SERVER_NAME, buffer, INFO_BUFFER_LEN, &buffer_len TSRMLS_CC );
        core::sqlsrv_add_assoc_string( *conn, server_info, "SQLServerName", buffer, 0 /*duplicate*/ TSRMLS_CC );
        buffer.transferred();      
    }
    
    catch( core::CoreException& ) {
        throw;
    }
}

// core_sqlsrv_get_client_info
// Returns the ODBC driver's dll name, version and the ODBC version.
// Parameters
// conn         - The connection resource by which the client and server are connected.
// *client_info - zval for returning the results.

void core_sqlsrv_get_client_info( sqlsrv_conn* conn, __out zval *client_info TSRMLS_DC )
{
    try {

        sqlsrv_malloc_auto_ptr<char> buffer;
        SQLSMALLINT buffer_len = 0;
        
        // initialize the array
        core::sqlsrv_array_init( *conn, client_info TSRMLS_CC );
  
        // Get the ODBC driver's dll name
        buffer = static_cast<char*>( sqlsrv_malloc( INFO_BUFFER_LEN ));
        core::SQLGetInfo( conn, SQL_DRIVER_NAME, buffer, INFO_BUFFER_LEN, &buffer_len TSRMLS_CC );
        core::sqlsrv_add_assoc_string( *conn, client_info, "DriverDllName", buffer, 0 /*duplicate*/ TSRMLS_CC );
        buffer.transferred();

        // Get the ODBC driver's ODBC version
        buffer = static_cast<char*>( sqlsrv_malloc( INFO_BUFFER_LEN ));
        core::SQLGetInfo( conn, SQL_DRIVER_ODBC_VER, buffer, INFO_BUFFER_LEN, &buffer_len TSRMLS_CC );
        core::sqlsrv_add_assoc_string( *conn, client_info, "DriverODBCVer", buffer, 0 /*duplicate*/ TSRMLS_CC );
        buffer.transferred();

        // Get the OBDC driver's version
        buffer = static_cast<char*>( sqlsrv_malloc( INFO_BUFFER_LEN ));
        core::SQLGetInfo( conn, SQL_DRIVER_VER, buffer, INFO_BUFFER_LEN, &buffer_len TSRMLS_CC );
        core::sqlsrv_add_assoc_string( *conn, client_info, "DriverVer", buffer, 0 /*duplicate*/ TSRMLS_CC );
        buffer.transferred();
    
    }

    catch( core::CoreException& ) {
        throw;
    }
}


// core_is_conn_opt_value_escaped
// determine if connection string value is properly escaped.
// Properly escaped means that any '}' should be escaped by a prior '}'.  It is assumed that 
// the value will be surrounded by { and } by the caller after it has been validated

bool core_is_conn_opt_value_escaped( const char* value, int value_len )
{
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
        return false;
    }

    return true;
}


// *** internal connection functions and classes ***

namespace {

connection_option const* get_connection_option( sqlsrv_conn* conn, unsigned long key, 
                                                     const connection_option conn_opts[] TSRMLS_DC )
{
    for( int opt_idx = 0; conn_opts[ opt_idx ].conn_option_key != SQLSRV_CONN_OPTION_INVALID; ++opt_idx ) {
        
        if( key == conn_opts[ opt_idx ].conn_option_key ) { 

            return &conn_opts[ opt_idx ];
         }
    }

    SQLSRV_ASSERT( false, "Invalid connection option, should have been validated by the driver layer." );
    return NULL;    // avoid a compiler warning
}

// says what it does, and does what it says
// rather than have attributes and connection strings as ODBC does, we unify them into a hash table
// passed to the connection, and then break them out ourselves and either set attributes or put the
// option in the connection string.

void build_connection_string_and_set_conn_attr( sqlsrv_conn* conn, const char* server, const char* uid, const char* pwd, 
                                                     HashTable* options, const connection_option valid_conn_opts[], 
                                                     void* driver,__inout std::string& connection_string TSRMLS_DC )
{
    bool credentials_mentioned = false;
    bool mars_mentioned = false;
    connection_option const* conn_opt;
    int zr = SUCCESS;

    try {

        connection_string = CONNECTION_STRING_DRIVER_NAME;
        
        // Add the server name
        common_conn_str_append_func( ODBCConnOptions::SERVER, server, strlen( server ), connection_string TSRMLS_CC );

        // if uid is not present then we use trusted connection.
        if(uid == NULL || strlen( uid ) == 0 ) {

            connection_string += "Trusted_Connection={Yes};";
        }
        else {

            bool escaped = core_is_conn_opt_value_escaped( uid, strlen( uid ));
            CHECK_CUSTOM_ERROR( !escaped, conn, SQLSRV_ERROR_UID_PWD_BRACES_NOT_ESCAPED ) {
                throw core::CoreException();
            }

            common_conn_str_append_func( ODBCConnOptions::UID, uid, strlen( uid ), connection_string TSRMLS_CC );

            // if no password was given, then don't add a password to the connection string.  Perhaps the UID
            // given doesn't have a password?
            if( pwd != NULL ) {

                escaped = core_is_conn_opt_value_escaped( pwd, strlen( pwd ));
                CHECK_CUSTOM_ERROR( !escaped, conn, SQLSRV_ERROR_UID_PWD_BRACES_NOT_ESCAPED ) {

                    throw core::CoreException();
                }
                    
                common_conn_str_append_func( ODBCConnOptions::PWD, pwd, strlen( pwd ), connection_string TSRMLS_CC );
            }
        }

        // if no options were given, then we set MARS the defaults and return immediately.
        if( options == NULL || zend_hash_num_elements( options ) == 0 ) {
            connection_string += CONNECTION_STRING_DEFAULT_OPTIONS;
            return;
        }

        // workaround for a bug in ODBC Driver Manager wherein the Driver Manager creates a 0 KB file 
        // if the TraceFile option is set, even if the "TraceOn" is not present or the "TraceOn"
        // flag is set to false.
        if( zend_hash_index_exists( options, SQLSRV_CONN_OPTION_TRACE_FILE )) {
            
            zval** trace_value = NULL;
            int zr = zend_hash_index_find( options, SQLSRV_CONN_OPTION_TRACE_ON, (void**)&trace_value );
            
            if( zr == FAILURE || !zend_is_true( *trace_value )) {
           
                zend_hash_index_del( options, SQLSRV_CONN_OPTION_TRACE_FILE );
            }
        }

        for( zend_hash_internal_pointer_reset( options );
             zend_hash_has_more_elements( options ) == SUCCESS;
             zend_hash_move_forward( options )) {
        
            int type = HASH_KEY_NON_EXISTANT;
            char *key = NULL;
            unsigned int key_len = -1;
            unsigned long index = -1;
            zval** data = NULL;

            type = zend_hash_get_current_key_ex( options, &key, &key_len, &index, 0, NULL );
            
            // The driver layer should ensure a valid key.
            DEBUG_SQLSRV_ASSERT(( type == HASH_KEY_IS_LONG ), "build_connection_string_and_set_conn_attr: invalid connection option key type." );
           
            core::sqlsrv_zend_hash_get_current_data( *conn, options, (void**) &data TSRMLS_CC );

            conn_opt = get_connection_option( conn, index, valid_conn_opts TSRMLS_CC );
    
            if( index == SQLSRV_CONN_OPTION_MARS ) {
                mars_mentioned = true;
            }
            
            conn_opt->func( conn_opt, *data, conn, connection_string TSRMLS_CC );
        }

        // MARS on if not explicitly turned off
        if( !mars_mentioned ) {
            connection_string += CONNECTION_OPTION_MARS_ON;
        }

    }
    catch( core::CoreException& ) {
        throw;
    }
}


// get_server_version
// Helper function which returns the version of the SQL Server we are connected to.

void get_server_version( sqlsrv_conn* conn, char** server_version, SQLSMALLINT& len TSRMLS_DC )
{
    try {
         
        sqlsrv_malloc_auto_ptr<char> buffer;
        SQLSMALLINT buffer_len = 0;

        buffer = static_cast<char*>( sqlsrv_malloc( INFO_BUFFER_LEN ));
        core::SQLGetInfo( conn, SQL_DBMS_VER, buffer, INFO_BUFFER_LEN, &buffer_len TSRMLS_CC );
        *server_version = buffer;
        len = buffer_len;
        buffer.transferred();
    }

    catch( core::CoreException& ) {
        throw;
    }
}


// get_processor_arch
// Calls GetSystemInfo to verify the what architecture of the processor is supported 
// and return the string of the processor name.
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
            DIE( "Unknown Windows processor architecture." );
            return NULL;
    }
}


// some features require a server of a certain version or later
// this function determines the version of the server we're connected to
// and stores it in the connection.  Any errors are logged before return.
// Exception is thrown when the server version is either undetermined
// or is invalid (< 2000).

void determine_server_version( sqlsrv_conn* conn TSRMLS_DC )
{
    SQLSMALLINT info_len;
    char p[ INFO_BUFFER_LEN ];
    core::SQLGetInfo( conn, SQL_DBMS_VER, p, INFO_BUFFER_LEN, &info_len TSRMLS_CC );

    errno = 0;
    char version_major_str[ 3 ];
    SERVER_VERSION version_major;
    memcpy( version_major_str, p, 2 );
    version_major_str[ 2 ] = '\0';
    version_major = static_cast<SERVER_VERSION>( atoi( version_major_str ));

    CHECK_CUSTOM_ERROR( version_major == 0 && ( errno == ERANGE || errno == EINVAL ), conn, SQLSRV_ERROR_UNKNOWN_SERVER_VERSION )
    {
        throw core::CoreException();
    }

    // SNAC won't connect to versions older than SQL Server 2000, so we know that the version is at least
    // that high
    conn->server_version = version_major;
}

void common_conn_str_append_func( const char* odbc_name, const char* val, int val_len, std::string& conn_str TSRMLS_DC )
{
    // wrap a connection option in a quote.  It is presumed that any character that need to be escaped will
    // be escaped, such as a closing }.
    TSRMLS_C;

    if( val_len > 0 && val[0] == '{' && val[ val_len - 1 ] == '}' ) {
        ++val;
        val_len -= 2;
    }
    conn_str += odbc_name;
    conn_str += "={";
    conn_str.append( val, val_len );
    conn_str += "};";
}

}   // namespace

// simply add the parsed value to the connection string
void conn_str_append_func::func( connection_option const* option, zval* value, sqlsrv_conn* /*conn*/, std::string& conn_str 
                                 TSRMLS_DC )
{
    const char* val_str = Z_STRVAL_P( value );
    int val_len = Z_STRLEN_P( value );
    common_conn_str_append_func( option->odbc_name, val_str, val_len, conn_str TSRMLS_CC );
}

// do nothing for connection pooling since we handled it earlier when
// deciding which environment handle to use.
void conn_null_func::func( connection_option const* /*option*/, zval* /*value*/, sqlsrv_conn* /*conn*/, std::string& /*conn_str*/ 
                      TSRMLS_DC )
{    
    TSRMLS_C;
}

// helper function to evaluate whether a string value is true or false.
// Values = ("true" or "1") are treated as true values. Everything else is treated as false.
// Returns 1 for true and 0 for false.

int core_str_zval_is_true( zval* value_z ) 
{    
    SQLSRV_ASSERT( Z_TYPE_P( value_z ) == IS_STRING, "core_str_zval_is_true: This function only accepts zval of type string." );

    char* value_in = Z_STRVAL_P( value_z );
    int val_len = Z_STRLEN_P( value_z );
    
    // strip any whitespace at the end (whitespace is the same value in ASCII and UTF-8)
    int last_char = val_len - 1;
    while( isspace( value_in[ last_char ] )) {
        value_in[ last_char ] = '\0';
        val_len = last_char;
        --last_char;
    }

    // save adjustments to the value made by stripping whitespace at the end
    ZVAL_STRINGL( value_z, value_in, val_len, 0 );

    const char VALID_TRUE_VALUE_1[] = "true";
    const char VALID_TRUE_VALUE_2[] = "1";
        
    if(( val_len == ( sizeof( VALID_TRUE_VALUE_1 ) - 1 ) && !strnicmp( value_in, VALID_TRUE_VALUE_1, val_len )) ||
       ( val_len == ( sizeof( VALID_TRUE_VALUE_2 ) - 1 ) && !strnicmp( value_in, VALID_TRUE_VALUE_2, val_len )) 
      ) {

         return 1; // true
    }

    return 0; // false
}

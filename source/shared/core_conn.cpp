//---------------------------------------------------------------------------------------------------------------------------------
// File: core_conn.cpp
//
// Contents: Core routines that use connection handles shared between sqlsrv and pdo_sqlsrv
//
// Microsoft Drivers 5.1 for PHP for SQL Server
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

#include "core_sqlsrv.h"

#include <php.h>

#ifdef _WIN32
#include <psapi.h>
#include <windows.h>
#include <winver.h>
#endif // _WIN32
#include <sstream>
#include <vector>

#ifndef _WIN32
#include <sys/utsname.h>
#include <odbcinst.h>
#endif

// *** internal variables and constants ***

namespace {

// *** internal constants ***
// an arbitrary figure that should be large enough for most connection strings.
const int DEFAULT_CONN_STR_LEN = 2048;

// length of buffer used to retrieve information for client and server info buffers
const int INFO_BUFFER_LEN = 256;

// processor architectures
const char* PROCESSOR_ARCH[] = { "x86", "x64", "ia64" };

// ODBC driver names.
// the order of this list should match the order of DRIVER_VERSION enum
std::vector<std::string> CONNECTION_STRING_DRIVER_NAME{ "Driver={ODBC Driver 13 for SQL Server};", "Driver={ODBC Driver 11 for SQL Server};", "Driver={ODBC Driver 17 for SQL Server};" };

// default options if only the server is specified
const char CONNECTION_STRING_DEFAULT_OPTIONS[] = "Mars_Connection={Yes};";

// connection option appended when no user name or password is given
const char CONNECTION_OPTION_NO_CREDENTIALS[] = "Trusted_Connection={Yes};";

// connection option appended for MARS when MARS isn't explicitly mentioned
const char CONNECTION_OPTION_MARS_ON[] = "MARS_Connection={Yes};";

// *** internal function prototypes ***

void build_connection_string_and_set_conn_attr( _Inout_ sqlsrv_conn* conn, _Inout_z_ const char* server, _Inout_opt_z_ const char* uid, _Inout_opt_z_ const char* pwd, 
                                                _Inout_opt_ HashTable* options_ht, _In_ const connection_option valid_conn_opts[], 
                                                void* driver,_Inout_ std::string& connection_string TSRMLS_DC );
void determine_server_version( _Inout_ sqlsrv_conn* conn TSRMLS_DC );
const char* get_processor_arch( void );
void get_server_version( _Inout_ sqlsrv_conn* conn, _Outptr_result_buffer_(len) char** server_version, _Out_ SQLSMALLINT& len TSRMLS_DC );
connection_option const* get_connection_option( sqlsrv_conn* conn, _In_ const char* key, _In_ SQLULEN key_len TSRMLS_DC );
void common_conn_str_append_func( _In_z_ const char* odbc_name, _In_reads_(val_len) const char* val, _Inout_ size_t val_len, _Inout_ std::string& conn_str TSRMLS_DC );
void load_configure_ksp( _Inout_ sqlsrv_conn* conn TSRMLS_DC );
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

sqlsrv_conn* core_sqlsrv_connect( _In_ sqlsrv_context& henv_cp, _In_ sqlsrv_context& henv_ncp, _In_ driver_conn_factory conn_factory,
                                  _Inout_z_ const char* server, _Inout_opt_z_ const char* uid, _Inout_opt_z_ const char* pwd, 
                                  _Inout_opt_ HashTable* options_ht, _In_ error_callback err, _In_ const connection_option valid_conn_opts[], 
                                  _In_ void* driver, _In_z_ const char* driver_func TSRMLS_DC )

{
    SQLRETURN r;
    std::string conn_str;
    conn_str.reserve( DEFAULT_CONN_STR_LEN );
    sqlsrv_malloc_auto_ptr<sqlsrv_conn> conn;
    bool is_pooled = false;

#ifdef _WIN32
    sqlsrv_context* henv = &henv_cp;   // by default use the connection pooling henv
    is_pooled = true;
#else
    sqlsrv_context* henv = &henv_ncp;  // by default do not use the connection pooling henv
    is_pooled = false;
#endif // _WIN32 

    try {
    // Due to the limitations on connection pooling in unixODBC 2.3.1 driver manager, we do not consider 
    // the connection string attributes to set (enable/disable) connection pooling. 
    // Instead, MSPHPSQL connection pooling is set according to the ODBCINST.INI file in [ODBC] section.
    
#ifndef _WIN32
        char pooling_string[ 128 ] = {0};
        SQLGetPrivateProfileString( "ODBC", "Pooling", "0", pooling_string, sizeof( pooling_string ), "ODBCINST.INI" );

        if ( pooling_string[ 0 ] == '1' || toupper( pooling_string[ 0 ] ) == 'Y' ||
            ( toupper( pooling_string[ 0 ] ) == 'O' && toupper( pooling_string[ 1 ] ) == 'N' ))
        {
            henv = &henv_cp;
            is_pooled = true;
        }
#else
    // check the connection pooling setting to determine which henv to use to allocate the connection handle
    // we do this earlier because we have to allocate the connection handle prior to setting attributes on
    // it in build_connection_string_and_set_conn_attr.
    
         if( options_ht && zend_hash_num_elements( options_ht ) > 0 ) {
         
             zval* option_z = NULL; 
             option_z = zend_hash_index_find( options_ht, SQLSRV_CONN_OPTION_CONN_POOLING );
             if ( option_z ) {
                 // if the option was found and it's not true, then use the non pooled environment handle
                 if(( Z_TYPE_P( option_z ) == IS_STRING && !core_str_zval_is_true( option_z )) || !zend_is_true( option_z ) ) {  
                henv = &henv_ncp;
                is_pooled = false;
            }
        }
    }
#endif // !_WIN32 

    SQLHANDLE temp_conn_h;
    core::SQLAllocHandle( SQL_HANDLE_DBC, *henv, &temp_conn_h TSRMLS_CC );
    conn = conn_factory( temp_conn_h, err, driver TSRMLS_CC );
    conn->set_func( driver_func );
    
    build_connection_string_and_set_conn_attr( conn, server, uid, pwd, options_ht, valid_conn_opts, driver, conn_str TSRMLS_CC );
    
    bool is_missing_driver = false;

    if ( conn->is_driver_set ) {
        r = core_odbc_connect( conn, conn_str, is_missing_driver, is_pooled );
    }
    else if ( conn->ce_option.enabled ) {
        conn_str = conn_str + CONNECTION_STRING_DRIVER_NAME[ DRIVER_VERSION::ODBC_DRIVER_17 ];
        r = core_odbc_connect( conn, conn_str, is_missing_driver, is_pooled );

        CHECK_CUSTOM_ERROR( is_missing_driver, conn, SQLSRV_ERROR_AE_DRIVER_NOT_INSTALLED, get_processor_arch()) {
            throw core::CoreException();
        }
    }
    else {

        for ( std::size_t i = DRIVER_VERSION::FIRST; i <= DRIVER_VERSION::LAST; ++i ) {
            is_missing_driver = false;
            std::string conn_str_driver = conn_str + CONNECTION_STRING_DRIVER_NAME[ DRIVER_VERSION(i) ];
            r = core_odbc_connect( conn, conn_str_driver, is_missing_driver, is_pooled );
            CHECK_CUSTOM_ERROR( is_missing_driver && ( i == DRIVER_VERSION::LAST ), conn, SQLSRV_ERROR_DRIVER_NOT_INSTALLED, get_processor_arch()) {
                throw core::CoreException();
            }
            if ( !is_missing_driver) {
                break;
            }
        } // for
    } // else ce_option enabled
        
    CHECK_SQL_ERROR( r, conn ) {
        throw core::CoreException();
    }

    CHECK_SQL_WARNING_AS_ERROR( r, conn ) {
        throw core::CoreException();
    }

    load_configure_ksp( conn );

    // determine the version of the server we're connected to.  The server version is left in the 
    // connection upon return.
    //
    // unixODBC 2.3.1:
    // SQLGetInfo works when r =  SQL_SUCCESS_WITH_INFO (non-pooled connection)
    // but fails if the connection is using a pool, i.e. r= SQL_SUCCESS.
    // Thus, in Linux, we don't call determine_server_version() for a connection that uses pool.
#ifndef _WIN32
    if ( r == SQL_SUCCESS_WITH_INFO ) {
#endif // !_WIN32 
    determine_server_version( conn TSRMLS_CC );
#ifndef _WIN32
    }
#endif // !_WIN32 
    }
    catch( std::bad_alloc& ) {
        conn_str.clear();
        conn->invalidate();
        DIE( "C++ memory allocation failure building the connection string." );
    }
    catch( std::out_of_range const& ex ) {
        conn_str.clear();
        LOG( SEV_ERROR, "C++ exception returned: %1!s!", ex.what() );
        conn->invalidate();
        throw;
    }
    catch( std::length_error const& ex ) {
        conn_str.clear();
        LOG( SEV_ERROR, "C++ exception returned: %1!s!", ex.what() );
        conn->invalidate();
        throw;
    }
    catch( core::CoreException&  ) {
        conn_str.clear();
        conn->invalidate();
        throw;        
    }

    conn_str.clear();
    sqlsrv_conn* return_conn = conn;
    conn.transferred();

    return return_conn;
}


// core_odbc_connect
// calls odbc connect API to establish the connection to server
// Parameters:
// conn                 - The connection structure on which we establish the connection 
// conn_str             - Connection string 
// missing_driver_error - indicates whether odbc driver is installed on client machine   
// is_pooled            - indicate whether it is a pooled connection 
// Return               - SQLRETURN status returned by SQLDriverConnect

SQLRETURN core_odbc_connect( _Inout_ sqlsrv_conn* conn, _Inout_ std::string& conn_str, _Inout_ bool& is_missing_driver, _In_ bool is_pooled )
{
    SQLRETURN r = SQL_SUCCESS;
    sqlsrv_malloc_auto_ptr<SQLWCHAR> wconn_string;
    unsigned int wconn_len = static_cast<unsigned int>( conn_str.length() + 1 ) * sizeof( SQLWCHAR );

    // We only support UTF-8 encoding for connection string.
    // Convert our UTF-8 connection string to UTF-16 before connecting with SQLDriverConnnectW
    wconn_string = utf16_string_from_mbcs_string( SQLSRV_ENCODING_UTF8, conn_str.c_str(), static_cast<unsigned int>( conn_str.length() ), &wconn_len );

    CHECK_CUSTOM_ERROR( wconn_string == 0, conn, SQLSRV_ERROR_CONNECT_STRING_ENCODING_TRANSLATE, get_last_error_message())
    {
        throw core::CoreException();
    }

    SQLSMALLINT output_conn_size;
#ifndef _WIN32
    // unixODBC 2.3.1 requires a non-wide SQLDriverConnect call while pooling enabled.
    // connection handle has been allocated using henv_cp, means pooling enabled in a PHP script
    if (is_pooled)
    {
        r = SQLDriverConnect( conn->handle(), NULL, (SQLCHAR*)conn_str.c_str(), SQL_NTS, NULL, 0, &output_conn_size, SQL_DRIVER_NOPROMPT );
    }
    else
    {
        r = SQLDriverConnectW( conn->handle(), NULL, wconn_string, static_cast<SQLSMALLINT>( wconn_len ), NULL, 0, &output_conn_size, SQL_DRIVER_NOPROMPT );
    }
#else
    r = SQLDriverConnectW( conn->handle(), NULL, wconn_string, static_cast<SQLSMALLINT>( wconn_len ), NULL, 0, &output_conn_size, SQL_DRIVER_NOPROMPT );
#endif // !_WIN32 

    // clear the connection string from memory to remove sensitive data (such as a password).
    memset( wconn_string, 0, wconn_len * sizeof( SQLWCHAR )); // wconn_len is the number of characters, not bytes
    conn_str.clear();

    if (!SQL_SUCCEEDED(r)) {
        SQLCHAR state[ SQL_SQLSTATE_BUFSIZE ];
        SQLSMALLINT len;
        SQLRETURN sr = SQLGetDiagField( SQL_HANDLE_DBC, conn->handle(), 1, SQL_DIAG_SQLSTATE, state, SQL_SQLSTATE_BUFSIZE, &len );
        // sql state IM002/IM003 in ODBC 17, means that the correct ODBC driver is not installed
        is_missing_driver = ( SQL_SUCCEEDED(sr) && state[0] == 'I' && state[1] == 'M' && state[2] == '0' && state[3] == '0' && (state[4] == '2' || state[4] == '3'));
    } 
    return r;
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

void core_sqlsrv_begin_transaction( _Inout_ sqlsrv_conn* conn TSRMLS_DC )
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

void core_sqlsrv_commit( _Inout_ sqlsrv_conn* conn TSRMLS_DC )
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

void core_sqlsrv_rollback( _Inout_ sqlsrv_conn* conn TSRMLS_DC )
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
void core_sqlsrv_close( _Inout_opt_ sqlsrv_conn* conn TSRMLS_DC )
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

void core_sqlsrv_prepare( _Inout_ sqlsrv_stmt* stmt, _In_reads_bytes_(sql_len) const char* sql, _In_ SQLLEN sql_len TSRMLS_DC )
{
    try {

        // convert the string from its encoding to UTf-16
        // if the string is empty, we initialize the fields and skip since an empty string is a 
        // failure case for utf16_string_from_mbcs_string 
        sqlsrv_malloc_auto_ptr<SQLWCHAR> wsql_string;
        unsigned int wsql_len = 0;
        if( sql_len == 0 || ( sql[0] == '\0' && sql_len == 1 )) {
            wsql_string = reinterpret_cast<SQLWCHAR*>( sqlsrv_malloc( sizeof( SQLWCHAR )));
            wsql_string[0] = L'\0';
            wsql_len = 0;
        } 
        else {
             if( sql_len > INT_MAX ) {
                LOG( SEV_ERROR, "Convert input parameter to utf16: buffer length exceeded.");
                throw core::CoreException();
             }

             SQLSRV_ENCODING encoding = (( stmt->encoding() == SQLSRV_ENCODING_DEFAULT ) ? stmt->conn->encoding() : stmt->encoding() );
             wsql_string = utf16_string_from_mbcs_string( encoding, reinterpret_cast<const char*>( sql ), static_cast<int>( sql_len ), &wsql_len );
             CHECK_CUSTOM_ERROR( wsql_string == 0, stmt, SQLSRV_ERROR_QUERY_STRING_ENCODING_TRANSLATE, get_last_error_message() ) {
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

void core_sqlsrv_get_server_version( _Inout_ sqlsrv_conn* conn, _Inout_ zval* server_version TSRMLS_DC )
{
    try {
        
        sqlsrv_malloc_auto_ptr<char> buffer;
        SQLSMALLINT buffer_len = 0;

        get_server_version( conn, &buffer, buffer_len TSRMLS_CC );
        core::sqlsrv_zval_stringl( server_version, buffer, buffer_len );
        if ( buffer != 0 ) {
            sqlsrv_free( buffer );
        }
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

void core_sqlsrv_get_server_info( _Inout_ sqlsrv_conn* conn, _Out_ zval *server_info TSRMLS_DC )
{
    try {

        sqlsrv_malloc_auto_ptr<char> buffer;
        SQLSMALLINT buffer_len = 0;

        // Get the database name
        buffer = static_cast<char*>( sqlsrv_malloc( INFO_BUFFER_LEN ));
        core::SQLGetInfo( conn, SQL_DATABASE_NAME, buffer, INFO_BUFFER_LEN, &buffer_len TSRMLS_CC );

        // initialize the array
        core::sqlsrv_array_init( *conn, server_info TSRMLS_CC );

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

void core_sqlsrv_get_client_info( _Inout_ sqlsrv_conn* conn, _Out_ zval *client_info TSRMLS_DC )
{
    try {

        sqlsrv_malloc_auto_ptr<char> buffer;
        SQLSMALLINT buffer_len = 0;
          
        // Get the ODBC driver's dll name
        buffer = static_cast<char*>( sqlsrv_malloc( INFO_BUFFER_LEN ));
        core::SQLGetInfo( conn, SQL_DRIVER_NAME, buffer, INFO_BUFFER_LEN, &buffer_len TSRMLS_CC );

        // initialize the array
        core::sqlsrv_array_init( *conn, client_info TSRMLS_CC );

#ifndef _WIN32
        core::sqlsrv_add_assoc_string( *conn, client_info, "DriverName", buffer, 0 /*duplicate*/ TSRMLS_CC );
#else
        core::sqlsrv_add_assoc_string( *conn, client_info, "DriverDllName", buffer, 0 /*duplicate*/ TSRMLS_CC );
#endif // !_WIN32
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

bool core_is_conn_opt_value_escaped( _Inout_ const char* value, _Inout_ size_t value_len )
{
    // if the value is already quoted, then only analyse the part inside the quotes and return it as 
    // unquoted since we quote it when adding it to the connection string.
    if( value_len > 0 && value[0] == '{' && value[ value_len - 1 ] == '}' ) {
        ++value;
        value_len -= 2;
    }
    // check to make sure that all right braces are escaped
    size_t i = 0;
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

// core_is_authentication_option_valid
// if the option for the authentication is valid, returns true. This returns false otherwise.
bool core_is_authentication_option_valid( _In_z_ const char* value, _In_ size_t value_len)
{
    if (value_len <= 0)
        return false;

    if( ! stricmp( value, AzureADOptions::AZURE_AUTH_SQL_PASSWORD ) || ! stricmp( value, AzureADOptions::AZURE_AUTH_AD_PASSWORD ) ) {
        return true;
    }

    return false;
}


// *** internal connection functions and classes ***

namespace {

connection_option const* get_connection_option( sqlsrv_conn* conn, _In_ SQLULEN key, 
                                                     _In_ const connection_option conn_opts[] TSRMLS_DC )
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

void build_connection_string_and_set_conn_attr( _Inout_ sqlsrv_conn* conn, _Inout_z_ const char* server, _Inout_opt_z_  const char* uid, _Inout_opt_z_ const char* pwd, 
                                                _Inout_opt_ HashTable* options, _In_ const connection_option valid_conn_opts[], 
                                                void* driver, _Inout_ std::string& connection_string TSRMLS_DC )
{
    bool mars_mentioned = false;
    connection_option const* conn_opt;

    try {
  
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
            
            zval* trace_value = NULL; 
            trace_value = zend_hash_index_find(options, SQLSRV_CONN_OPTION_TRACE_ON);
            
            if (trace_value == NULL || !zend_is_true(trace_value)) {
           
                zend_hash_index_del( options, SQLSRV_CONN_OPTION_TRACE_FILE );
            }
        }

        zend_string *key = NULL;
        zend_ulong index = -1;
        zval* data = NULL;

        ZEND_HASH_FOREACH_KEY_VAL( options, index, key, data ) {
            int type = HASH_KEY_NON_EXISTENT;
            type = key ? HASH_KEY_IS_STRING : HASH_KEY_IS_LONG;

            // The driver layer should ensure a valid key.
            DEBUG_SQLSRV_ASSERT(( type == HASH_KEY_IS_LONG ), "build_connection_string_and_set_conn_attr: invalid connection option key type." );

            conn_opt = get_connection_option( conn, index, valid_conn_opts TSRMLS_CC );

            if( index == SQLSRV_CONN_OPTION_MARS ) {
                mars_mentioned = true;
            }

            conn_opt->func( conn_opt, data, conn, connection_string TSRMLS_CC );
        } ZEND_HASH_FOREACH_END();

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

void get_server_version( _Inout_ sqlsrv_conn* conn, _Outptr_result_buffer_(len) char** server_version, _Out_ SQLSMALLINT& len TSRMLS_DC )
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
#ifndef _WIN32
   struct utsname sys_info;
    if ( uname(&sys_info) == -1 )
    {
        DIE( "Error retrieving system info" );
    }
    if( strcmp(sys_info.machine, "x86") == 0 ) {
        return PROCESSOR_ARCH[0];
    } else if ( strcmp(sys_info.machine, "x86_64") == 0) {
        return PROCESSOR_ARCH[1];
    } else if ( strcmp(sys_info.machine, "ia64") == 0 ) {
        return PROCESSOR_ARCH[2];
    } else {
        DIE( "Unknown processor architecture." );
    }
        return NULL;
#else
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
    return NULL;
#endif // !_WIN32
}


// some features require a server of a certain version or later
// this function determines the version of the server we're connected to
// and stores it in the connection.  Any errors are logged before return.
// Exception is thrown when the server version is either undetermined
// or is invalid (< 2000).

void determine_server_version( _Inout_ sqlsrv_conn* conn TSRMLS_DC )
{
    SQLSMALLINT info_len;
    char p[ INFO_BUFFER_LEN ];
    core::SQLGetInfo( conn, SQL_DBMS_VER, p, INFO_BUFFER_LEN, &info_len TSRMLS_CC );

    errno = 0;
    char version_major_str[ 3 ];
    SERVER_VERSION version_major;
    memcpy_s( version_major_str, sizeof( version_major_str ), p, 2 );

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

// Column Encryption feature: if a custom keystore provider is specified, 
// load and configure it when column encryption is enabled, but this step has
// to be executed after the connection has been established
void load_configure_ksp( _Inout_ sqlsrv_conn* conn TSRMLS_DC )
{
    // If column encryption is not enabled simply do nothing. Otherwise, check if a custom keystore provider
    // is required for encryption or decryption. Note, in order to load and configure a custom keystore provider, 
    // all KSP fields in conn->ce_option must be defined. 
    if ( ! conn->ce_option.enabled || ! conn->ce_option.ksp_required )
        return;
    
    // Do something like the following sample
    // use the KSP related fields in conn->ce_option
    // CEKEYSTOREDATA is defined in msodbcsql.h
    // https://docs.microsoft.com/en-us/sql/connect/odbc/custom-keystore-providers

    CHECK_CUSTOM_ERROR( conn->ce_option.ksp_name == NULL, conn, SQLSRV_ERROR_KEYSTORE_NAME_MISSING) {
        throw core::CoreException();
    }

    CHECK_CUSTOM_ERROR( conn->ce_option.ksp_path == NULL, conn, SQLSRV_ERROR_KEYSTORE_PATH_MISSING) {
        throw core::CoreException();
    }

    CHECK_CUSTOM_ERROR( conn->ce_option.key_size == 0, conn, SQLSRV_ERROR_KEYSTORE_KEY_MISSING) {
        throw core::CoreException();
    }

    char* ksp_name = Z_STRVAL_P( conn->ce_option.ksp_name );
    char* ksp_path = Z_STRVAL_P( conn->ce_option.ksp_path );
    unsigned int name_len = Z_STRLEN_P( conn->ce_option.ksp_name );
    unsigned int key_size = conn->ce_option.key_size;    

    sqlsrv_malloc_auto_ptr<unsigned char> ksp_data;

    ksp_data = reinterpret_cast<unsigned char*>( sqlsrv_malloc( sizeof( CEKEYSTOREDATA ) + key_size ) );

    CEKEYSTOREDATA *pKsd = reinterpret_cast<CEKEYSTOREDATA*>( ksp_data.get() );

    pKsd->dataSize = key_size;

    // First, convert conn->ce_option.ksp_name to a WCHAR version 
    unsigned int wname_len = 0;
    sqlsrv_malloc_auto_ptr<SQLWCHAR> wksp_name;
    wksp_name = utf16_string_from_mbcs_string( SQLSRV_ENCODING_UTF8, ksp_name, name_len, &wname_len );

    CHECK_CUSTOM_ERROR( wksp_name == 0, conn, SQLSRV_ERROR_CONNECT_STRING_ENCODING_TRANSLATE ) {
        throw core::CoreException();
    }

    pKsd->name = (wchar_t *) wksp_name.get();
    
    // Next, extract the character string from conn->ce_option.ksp_encrypt_key into encrypt_key
    char* encrypt_key = Z_STRVAL_P( conn->ce_option.ksp_encrypt_key );    
    memcpy_s( pKsd->data, key_size * sizeof( char ) , encrypt_key, key_size );

    core::SQLSetConnectAttr( conn, SQL_COPT_SS_CEKEYSTOREPROVIDER, ksp_path, SQL_NTS );
    core::SQLSetConnectAttr( conn, SQL_COPT_SS_CEKEYSTOREDATA, reinterpret_cast<SQLPOINTER>( pKsd ), SQL_IS_POINTER );
}

void common_conn_str_append_func( _In_z_ const char* odbc_name, _In_reads_(val_len) const char* val, _Inout_ size_t val_len, _Inout_ std::string& conn_str TSRMLS_DC )
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
void conn_str_append_func::func( _In_ connection_option const* option, _In_ zval* value, sqlsrv_conn* /*conn*/, _Inout_ std::string& conn_str TSRMLS_DC )
{
    const char* val_str = Z_STRVAL_P( value );
    size_t val_len = Z_STRLEN_P( value );
    common_conn_str_append_func( option->odbc_name, val_str, val_len, conn_str TSRMLS_CC );
}

// do nothing for connection pooling since we handled it earlier when
// deciding which environment handle to use.
void conn_null_func::func( connection_option const* /*option*/, zval* /*value*/, sqlsrv_conn* /*conn*/, std::string& /*conn_str*/ TSRMLS_DC )
{    
    TSRMLS_C;
}

void driver_set_func::func( _In_ connection_option const* option, _In_ zval* value, _Inout_ sqlsrv_conn* conn, _Inout_ std::string& conn_str TSRMLS_DC )
{
    const char* val_str = Z_STRVAL_P( value );
    size_t val_len = Z_STRLEN_P( value );
    std::string driver_option( "" );
    common_conn_str_append_func( option->odbc_name, val_str, val_len, driver_option TSRMLS_CC );
   
    CHECK_CUSTOM_ERROR( std::find( CONNECTION_STRING_DRIVER_NAME.begin(), CONNECTION_STRING_DRIVER_NAME.end(), driver_option) == CONNECTION_STRING_DRIVER_NAME.end(), conn, SQLSRV_ERROR_CONNECT_INVALID_DRIVER, val_str){
        throw core::CoreException();
    } 
    conn->is_driver_set = true;
    conn_str += driver_option;
}

void column_encryption_set_func::func( _In_ connection_option const* option, _In_ zval* value, _Inout_ sqlsrv_conn* conn, _Inout_ std::string& conn_str TSRMLS_DC )
{
    convert_to_string( value );
    const char* value_str = Z_STRVAL_P( value );

    // Column Encryption is disabled by default unless it is explicitly 'Enabled'
    conn->ce_option.enabled = false;
    if ( !stricmp(value_str, "enabled" )) {
        conn->ce_option.enabled = true;
    }

    conn_str += option->odbc_name;
    conn_str += "=";
    conn_str += value_str;
    conn_str += ";";
}

void ce_ksp_provider_set_func::func( _In_ connection_option const* option, _In_ zval* value, _Inout_ sqlsrv_conn* conn, _Inout_ std::string& conn_str TSRMLS_DC )
{
    SQLSRV_ASSERT( Z_TYPE_P( value ) == IS_STRING, "Wrong zval type for this keyword" )

        size_t value_len = Z_STRLEN_P( value );

    CHECK_CUSTOM_ERROR( value_len == 0, conn, SQLSRV_ERROR_KEYSTORE_INVALID_VALUE ) {
        throw core::CoreException();
    }

    switch ( option->conn_option_key ) {
    case SQLSRV_CONN_OPTION_CEKEYSTORE_PROVIDER:
        conn->ce_option.ksp_path = value;
        conn->ce_option.ksp_required = true;
        break;
    case SQLSRV_CONN_OPTION_CEKEYSTORE_NAME:
        conn->ce_option.ksp_name = value;
        conn->ce_option.ksp_required = true;
        break;
    case SQLSRV_CONN_OPTION_CEKEYSTORE_ENCRYPT_KEY:
        conn->ce_option.ksp_encrypt_key = value;
        conn->ce_option.key_size = value_len;
        conn->ce_option.ksp_required = true;
        break;
    default:
        SQLSRV_ASSERT(false, "ce_ksp_provider_set_func: Invalid KSP option!");
        break;
    }
}

// helper function to evaluate whether a string value is true or false.
// Values = ("true" or "1") are treated as true values. Everything else is treated as false.
// Returns 1 for true and 0 for false.

size_t core_str_zval_is_true( _Inout_ zval* value_z ) 
{    
    SQLSRV_ASSERT( Z_TYPE_P( value_z ) == IS_STRING, "core_str_zval_is_true: This function only accepts zval of type string." );

    char* value_in = Z_STRVAL_P( value_z );
    size_t val_len = Z_STRLEN_P( value_z );
    
    // strip any whitespace at the end (whitespace is the same value in ASCII and UTF-8)
    size_t last_char = val_len - 1;
    while( isspace(( unsigned char )value_in[ last_char ] )) {
        value_in[ last_char ] = '\0';
        val_len = last_char;
        --last_char;
    }

    // save adjustments to the value made by stripping whitespace at the end
    Z_STRLEN_P( value_z ) = val_len;

    const char VALID_TRUE_VALUE_1[] = "true";
    const char VALID_TRUE_VALUE_2[] = "1";
        
    if(( val_len == ( sizeof( VALID_TRUE_VALUE_1 ) - 1 ) && !strnicmp( value_in, VALID_TRUE_VALUE_1, val_len )) ||
       ( val_len == ( sizeof( VALID_TRUE_VALUE_2 ) - 1 ) && !strnicmp( value_in, VALID_TRUE_VALUE_2, val_len )) 
      ) {

         return 1; // true
    }

    return 0; // false
}

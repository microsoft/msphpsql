//---------------------------------------------------------------------------------------------------------------------------------
// File: core_conn.cpp
//
// Contents: Core routines that use connection handles shared between sqlsrv and pdo_sqlsrv
//
// Microsoft Drivers 5.9 for PHP for SQL Server
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

// length for name of keystore used in CEKeyStoreData
const int MAX_CE_NAME_LEN = 260;

// processor architectures
const char* PROCESSOR_ARCH[] = { "x86", "x64", "ia64" };

// ODBC driver names.
// the order of this list should match the order of DRIVER_VERSION enum
std::vector<std::string> CONNECTION_STRING_DRIVER_NAME{ "Driver={ODBC Driver 17 for SQL Server};", "Driver={ODBC Driver 13 for SQL Server};", "Driver={ODBC Driver 11 for SQL Server};" };

// default options if only the server is specified
const char CONNECTION_STRING_DEFAULT_OPTIONS[] = "Mars_Connection={Yes};";

// connection option appended when no user name or password is given
const char CONNECTION_OPTION_NO_CREDENTIALS[] = "Trusted_Connection={Yes};";

// connection option appended for MARS when MARS isn't explicitly mentioned
const char CONNECTION_OPTION_MARS_ON[] = "MARS_Connection={Yes};";

// *** internal function prototypes ***

void build_connection_string_and_set_conn_attr( _Inout_ sqlsrv_conn* conn, _Inout_z_ const char* server, _Inout_opt_z_ const char* uid, _Inout_opt_z_ const char* pwd,
                                                _Inout_opt_ HashTable* options_ht, _In_ const connection_option valid_conn_opts[],
                                                void* driver,_Inout_ std::string& connection_string );
void determine_server_version( _Inout_ sqlsrv_conn* conn );
const char* get_processor_arch( void );
connection_option const* get_connection_option( sqlsrv_conn* conn, _In_ const char* key, _In_ SQLULEN key_len );
void common_conn_str_append_func( _In_z_ const char* odbc_name, _In_reads_(val_len) const char* val, _Inout_ size_t val_len, _Inout_ std::string& conn_str );
void load_azure_key_vault( _Inout_ sqlsrv_conn* conn );
void configure_azure_key_vault( sqlsrv_conn* conn, BYTE config_attr, const DWORD config_value, size_t key_size);
void configure_azure_key_vault( sqlsrv_conn* conn, BYTE config_attr, const char* config_value, size_t key_size);
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
                                  _In_ void* driver, _In_z_ const char* driver_func )

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
        char pooling_string[128] = {'\0'};
        SQLGetPrivateProfileString( "ODBC", "Pooling", "0", pooling_string, sizeof( pooling_string ), "ODBCINST.INI" );

        if ( pooling_string[0] == '1' || toupper( pooling_string[0] ) == 'Y' ||
            ( toupper( pooling_string[0] ) == 'O' && toupper( pooling_string[1] ) == 'N' ))
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
    core::SQLAllocHandle( SQL_HANDLE_DBC, *henv, &temp_conn_h );
    conn = conn_factory( temp_conn_h, err, driver );
    conn->set_func( driver_func );

    build_connection_string_and_set_conn_attr( conn, server, uid, pwd, options_ht, valid_conn_opts, driver, conn_str );

    // If column encryption is enabled, must use ODBC driver 17
    if( conn->ce_option.enabled && conn->driver_version != ODBC_DRIVER_UNKNOWN) {
        CHECK_CUSTOM_ERROR( conn->driver_version != ODBC_DRIVER_17, conn, SQLSRV_ERROR_CE_DRIVER_REQUIRED, get_processor_arch() ) {
            throw core::CoreException();
        }
    }

    // In non-Windows environment, unixODBC 2.3.4 and unixODBC 2.3.1 return different error states when an ODBC driver exists or not
    // Therefore, it is unreliable to check for a certain sql state error
#ifndef _WIN32
    if( conn->driver_version != ODBC_DRIVER_UNKNOWN ) {
        // check if the ODBC driver actually exists, if not, throw an exception
        CHECK_CUSTOM_ERROR( ! core_search_odbc_driver_unix( conn->driver_version ), conn, SQLSRV_ERROR_SPECIFIED_DRIVER_NOT_FOUND ) {
            throw core::CoreException();
        }

        r = core_odbc_connect( conn, conn_str, is_pooled );
    }
    else {
        if( conn->ce_option.enabled ) {
            // driver not specified, so check if ODBC 17 exists
            CHECK_CUSTOM_ERROR( ! core_search_odbc_driver_unix( ODBC_DRIVER_17 ), conn, SQLSRV_ERROR_CE_DRIVER_REQUIRED, get_processor_arch()) {
                throw core::CoreException();
            }

            conn_str = conn_str + CONNECTION_STRING_DRIVER_NAME[ODBC_DRIVER_17];
            r = core_odbc_connect( conn, conn_str, is_pooled );
        }
        else {
            // skip ODBC 11 in a non-Windows environment -- only available in Red Hat / SUSE (preview)
            // https://docs.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server#microsoft-odbc-driver-11-for-sql-server-on-linux

            DRIVER_VERSION odbc_version = ODBC_DRIVER_UNKNOWN;
            if( core_search_odbc_driver_unix( ODBC_DRIVER_17 ) ) {
                odbc_version = ODBC_DRIVER_17;
            }
            else if ( core_search_odbc_driver_unix( ODBC_DRIVER_13 ) ) {
                odbc_version = ODBC_DRIVER_13;
            }

            CHECK_CUSTOM_ERROR( odbc_version == ODBC_DRIVER_UNKNOWN, conn, SQLSRV_ERROR_DRIVER_NOT_INSTALLED, get_processor_arch() ) {
                throw core::CoreException();
            }
            std::string conn_str_driver = conn_str + CONNECTION_STRING_DRIVER_NAME[odbc_version];
            r = core_odbc_connect( conn, conn_str_driver, is_pooled );
        } // else ce_option enabled
    } // else driver_version not unknown
#else
    if( conn->driver_version != ODBC_DRIVER_UNKNOWN ) {
        r = core_odbc_connect( conn, conn_str, is_pooled );

        // check if the specified ODBC driver is there
        CHECK_CUSTOM_ERROR( core_compare_error_state( conn, r, "IM002" ), conn, SQLSRV_ERROR_SPECIFIED_DRIVER_NOT_FOUND ) {
            throw core::CoreException();
        }
    }
    else {
        if( conn->ce_option.enabled ) {
            // driver not specified, so connect using ODBC 17
            conn_str = conn_str + CONNECTION_STRING_DRIVER_NAME[ODBC_DRIVER_17];
            r = core_odbc_connect( conn, conn_str, is_pooled );

            // check if the specified ODBC driver is there
            CHECK_CUSTOM_ERROR( core_compare_error_state( conn, r, "IM002" ) , conn, SQLSRV_ERROR_CE_DRIVER_REQUIRED, get_processor_arch() ) {
                throw core::CoreException();
            }
        }
        else {
            bool done = false;
            for( short i = DRIVER_VERSION::FIRST; i <= DRIVER_VERSION::LAST && ! done; ++i ) {
                std::string conn_str_driver = conn_str + CONNECTION_STRING_DRIVER_NAME[i];
                r = core_odbc_connect( conn, conn_str_driver, is_pooled );

                if( SQL_SUCCEEDED( r ) || ! core_compare_error_state( conn, r, "IM002" ) ) {
                    // something else went wrong, exit the loop now other than ODBC driver not found
                    done = true;
                }
                else {
                    // did it fail to find the last valid ODBC driver?
                    CHECK_CUSTOM_ERROR( ( i == DRIVER_VERSION::LAST ), conn, SQLSRV_ERROR_DRIVER_NOT_INSTALLED, get_processor_arch()) {
                        throw core::CoreException();
                    }
                }
            } // for
        } // else ce_option enabled
    } // else driver_version not unknown
#endif // !_WIN32

    // time to free the access token, if not null
    if (conn->azure_ad_access_token) {
        memset(conn->azure_ad_access_token->data, 0, conn->azure_ad_access_token->dataSize); // clear the memory
        conn->azure_ad_access_token.reset();
    }

    CHECK_SQL_ERROR( r, conn ) {
        throw core::CoreException();
    }

    CHECK_SQL_WARNING_AS_ERROR( r, conn ) {
        throw core::CoreException();
    }

    // After load_azure_key_vault, reset AKV related variables regardless
    load_azure_key_vault(conn);
    conn->ce_option.akv_reset();

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
    determine_server_version( conn );
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
        conn->ce_option.akv_reset();
        conn_str.clear();
        conn->invalidate();
        throw;
    }

    conn_str.clear();
    sqlsrv_conn* return_conn = conn;
    conn.transferred();

    return return_conn;
}

// core_compare_error_state
// This method compares the error state to the one specified
// Parameters:
// conn         - the connection structure on which we establish the connection
// rc           - ODBC return code
// Return       - a boolean flag that indicates if the error states are the same

bool core_compare_error_state( _In_ sqlsrv_conn* conn,  _In_ SQLRETURN rc, _In_ const char* error_state )
{
    if( SQL_SUCCEEDED( rc ) )
        return false;

    SQLCHAR state[SQL_SQLSTATE_BUFSIZE] = {'\0'};
    SQLSMALLINT len;
    SQLRETURN sr = SQLGetDiagField( SQL_HANDLE_DBC, conn->handle(), 1, SQL_DIAG_SQLSTATE, state, SQL_SQLSTATE_BUFSIZE, &len );

    return ( SQL_SUCCEEDED(sr) && ! strcmp(error_state, reinterpret_cast<char*>( state ) ) );
}

// core_search_odbc_driver_unix
// This method is meant to be used in a non-Windows environment,
// searching for a particular ODBC driver name in the odbcinst.ini file
// Parameters:
// driver_version   - a valid value in enum DRIVER_VERSION
// Return           - a boolean flag that indicates if the specified driver version is found or not

bool core_search_odbc_driver_unix( _In_ DRIVER_VERSION driver_version )
{
#ifndef _WIN32
    char szBuf[DEFAULT_CONN_STR_LEN+1] = {'\0'};     // use a large enough buffer size
    WORD cbBufMax = DEFAULT_CONN_STR_LEN;
    WORD cbBufOut;
    char *pszBuf = szBuf;

    // get all the names of the installed drivers delimited by null characters
    if(! SQLGetInstalledDrivers( szBuf, cbBufMax, &cbBufOut ) )
    {
        return false;
    }

    // extract the ODBC driver name
    std::string driver = CONNECTION_STRING_DRIVER_NAME[driver_version];
    std::size_t pos1 = driver.find_first_of("{");
    std::size_t pos2 = driver.find_first_of("}");
    std::string driver_str = driver.substr( pos1 + 1, pos2 - pos1 - 1);

    // search for the ODBC driver...
    const char* driver_name = driver_str.c_str();
    do
    {
        if( strstr( pszBuf, driver_name ) != 0  )
        {
            return true;
        }
        // get the next driver
        pszBuf = strchr( pszBuf, '\0' ) + 1;
    }
    while( pszBuf[1] != '\0' ); // end when there are two consecutive null characters
#endif // !_WIN32

    return false;
}

// core_odbc_connect
// calls odbc connect API to establish the connection to server
// Parameters:
// conn                 - The connection structure on which we establish the connection
// conn_str             - Connection string
// is_pooled            - indicate whether it is a pooled connection
// Return               - SQLRETURN status returned by SQLDriverConnect

SQLRETURN core_odbc_connect( _Inout_ sqlsrv_conn* conn, _Inout_ std::string& conn_str, _In_ bool is_pooled )
{
    SQLRETURN r = SQL_SUCCESS;
    sqlsrv_malloc_auto_ptr<SQLWCHAR> wconn_string;
    unsigned int wconn_len = static_cast<unsigned int>( conn_str.length() + 1 ) * sizeof( SQLWCHAR );

    // Set the desired data classification version before connecting, but older ODBC drivers will generate a warning message 'Driver's SQLSetConnectAttr failed'
    SQLSetConnectAttr(conn->handle(), SQL_COPT_SS_DATACLASSIFICATION_VERSION, reinterpret_cast<SQLPOINTER>(data_classification::VERSION_RANK_AVAILABLE), SQL_IS_POINTER);

    // We only support UTF-8 encoding for connection string.
    // Convert our UTF-8 connection string to UTF-16 before connecting with SQLDriverConnnectW
    wconn_string = utf16_string_from_mbcs_string( SQLSRV_ENCODING_UTF8, conn_str.c_str(), static_cast<unsigned int>( conn_str.length() ), &wconn_len, true );

    CHECK_CUSTOM_ERROR( wconn_string == 0, conn, SQLSRV_ERROR_CONNECT_STRING_ENCODING_TRANSLATE, get_last_error_message())
    {
        throw core::CoreException();
    }

    SQLSMALLINT output_conn_size;
#ifndef _WIN32
    // unixODBC 2.3.1 requires a non-wide SQLDriverConnect call while pooling enabled.
    // connection handle has been allocated using henv_cp, means pooling enabled in a PHP script
    if (is_pooled) {
        r = SQLDriverConnect( conn->handle(), NULL, (SQLCHAR*)conn_str.c_str(), SQL_NTS, NULL, 0, &output_conn_size, SQL_DRIVER_NOPROMPT );
    }
    else {
        r = SQLDriverConnectW( conn->handle(), NULL, wconn_string, static_cast<SQLSMALLINT>( wconn_len ), NULL, 0, &output_conn_size, SQL_DRIVER_NOPROMPT );
    }
#else
    r = SQLDriverConnectW( conn->handle(), NULL, wconn_string, static_cast<SQLSMALLINT>( wconn_len ), NULL, 0, &output_conn_size, SQL_DRIVER_NOPROMPT );
#endif // !_WIN32

    // clear the connection string from memory
    memset( wconn_string, 0, wconn_len * sizeof( SQLWCHAR )); // wconn_len is the number of characters, not bytes
    conn_str.clear();

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

void core_sqlsrv_begin_transaction( _Inout_ sqlsrv_conn* conn )
{
    try {

        DEBUG_SQLSRV_ASSERT( conn != NULL, "core_sqlsrv_begin_transaction: connection object was null." );

        core::SQLSetConnectAttr( conn, SQL_ATTR_AUTOCOMMIT, reinterpret_cast<SQLPOINTER>( SQL_AUTOCOMMIT_OFF ),
                                 SQL_IS_UINTEGER );
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

void core_sqlsrv_commit( _Inout_ sqlsrv_conn* conn )
{
    try {

        DEBUG_SQLSRV_ASSERT( conn != NULL, "core_sqlsrv_commit: connection object was null." );

        core::SQLEndTran( SQL_HANDLE_DBC, conn, SQL_COMMIT );

        core::SQLSetConnectAttr( conn, SQL_ATTR_AUTOCOMMIT, reinterpret_cast<SQLPOINTER>( SQL_AUTOCOMMIT_ON ),
                                SQL_IS_UINTEGER );
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

void core_sqlsrv_rollback( _Inout_ sqlsrv_conn* conn )
{
    try {

        DEBUG_SQLSRV_ASSERT( conn != NULL, "core_sqlsrv_rollback: connection object was null." );

        core::SQLEndTran( SQL_HANDLE_DBC, conn, SQL_ROLLBACK );

        core::SQLSetConnectAttr( conn, SQL_ATTR_AUTOCOMMIT, reinterpret_cast<SQLPOINTER>( SQL_AUTOCOMMIT_ON ),
                                 SQL_IS_UINTEGER );

    }
    catch ( core::CoreException& ) {
        throw;
    }
}

// core_sqlsrv_close
// Called when a connection resource is destroyed by the Zend engine.
// Parameters:
// conn - The current active connection.
void core_sqlsrv_close( _Inout_opt_ sqlsrv_conn* conn )
{
    // if the connection wasn't successful, just return.
    if( conn == NULL )
        return;

    try {

        // rollback any transaction in progress (we don't care about the return result)
        core::SQLEndTran( SQL_HANDLE_DBC, conn, SQL_ROLLBACK );
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

void core_sqlsrv_prepare( _Inout_ sqlsrv_stmt* stmt, _In_reads_bytes_(sql_len) const char* sql, _In_ SQLLEN sql_len )
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
        core::SQLPrepareW( stmt, reinterpret_cast<SQLWCHAR*>( wsql_string.get() ), wsql_len );

        stmt->param_descriptions.clear();

        // if AE is enabled, get meta data for all parameters before binding them
        if( stmt->conn->ce_option.enabled ) {
            SQLSMALLINT num_params;
            core::SQLNumParams( stmt, &num_params);
            for( int i = 0; i < num_params; i++ ) {
                param_meta_data param;

                core::SQLDescribeParam( stmt, i + 1, &( param.sql_type ), &( param.column_size ), &( param.decimal_digits ), &( param.nullable ) );

                stmt->param_descriptions.push_back( param );
            }
        }
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

void core_sqlsrv_get_server_version( _Inout_ sqlsrv_conn* conn, _Inout_ zval* server_version )
{
    try {
        char buffer[INFO_BUFFER_LEN] = "";
        SQLSMALLINT buffer_len = 0;
        core::SQLGetInfo(conn, SQL_DBMS_VER, buffer, INFO_BUFFER_LEN, &buffer_len);
        core::sqlsrv_zval_stringl(server_version, buffer, buffer_len);
    } catch( core::CoreException& ) {
        throw;
    }
}

// core_sqlsrv_get_server_info
// Returns the Database name, the name of the SQL Server we are connected to
// and the version of the SQL Server.
// Parameters:
// conn         - The connection resource by which the client and server are connected.
// *server_info - zval for returning results.

void core_sqlsrv_get_server_info( _Inout_ sqlsrv_conn* conn, _Out_ zval *server_info )
{
    try {
        char buffer[INFO_BUFFER_LEN] = "";
        SQLSMALLINT buffer_len = 0;

        // Get the database name
        core::SQLGetInfo(conn, SQL_DATABASE_NAME, buffer, INFO_BUFFER_LEN, &buffer_len);

        // initialize the array
        array_init(server_info);

        add_assoc_string(server_info, "CurrentDatabase", buffer);

        // Get the server version
        core::SQLGetInfo(conn, SQL_DBMS_VER, buffer, INFO_BUFFER_LEN, &buffer_len);
        add_assoc_string(server_info, "SQLServerVersion", buffer);

        // Get the server name
        core::SQLGetInfo(conn, SQL_SERVER_NAME, buffer, INFO_BUFFER_LEN, &buffer_len);
        add_assoc_string(server_info, "SQLServerName", buffer);
    } catch (core::CoreException&) {
        throw;
    }
}

// core_sqlsrv_get_client_info
// Returns the ODBC driver's dll name, version and the ODBC version.
// Parameters
// conn         - The connection resource by which the client and server are connected.
// *client_info - zval for returning the results.

void core_sqlsrv_get_client_info( _Inout_ sqlsrv_conn* conn, _Out_ zval *client_info )
{
    try {
        char buffer[INFO_BUFFER_LEN] = "";
        SQLSMALLINT buffer_len = 0;

        // Get the ODBC driver's dll name
        core::SQLGetInfo( conn, SQL_DRIVER_NAME, buffer, INFO_BUFFER_LEN, &buffer_len );

        // initialize the array
        array_init(client_info);

#ifndef _WIN32
        add_assoc_string(client_info, "DriverName", buffer);
#else
        add_assoc_string(client_info, "DriverDllName", buffer);
#endif // !_WIN32

        // Get the ODBC driver's ODBC version
        core::SQLGetInfo( conn, SQL_DRIVER_ODBC_VER, buffer, INFO_BUFFER_LEN, &buffer_len );
        add_assoc_string(client_info, "DriverODBCVer", buffer);

        // Get the OBDC driver's version
        core::SQLGetInfo( conn, SQL_DRIVER_VER, buffer, INFO_BUFFER_LEN, &buffer_len );
        add_assoc_string(client_info, "DriverVer", buffer);
    } catch( core::CoreException& ) {
        throw;
    }
}


// core_is_conn_opt_value_escaped
// determine if connection string value is properly escaped.
// Properly escaped means that any '}' should be escaped by a prior '}'.  It is assumed that
// the value will be surrounded by { and } by the caller after it has been validated

bool core_is_conn_opt_value_escaped( _Inout_ const char* value, _Inout_ size_t value_len )
{
    if (value_len == 0) {
        return true;
    }

    if (value_len == 1) {
        return (value[0] != '}');
    }

    const char *pstr = value;
    if (value_len > 0 && value[0] == '{' && value[value_len - 1] == '}') {
        pstr = ++value;
        value_len -= 2;
    }

    const char *pch = strchr(pstr, '}');
    size_t i = 0;

    while (pch != NULL && i < value_len) {
        i = pch - pstr + 1;

        if (i == value_len || (i < value_len && pstr[i] != '}')) {
            return false;
        }

        i++;    // skip the brace
        pch = strchr(pch + 2, '}'); // continue searching
    }

    return true;
}

namespace AzureADOptions {
    enum AAD_AUTH_TYPE {
        MIN_AAD_AUTH_TYPE = 0,
        SQL_PASSWORD = 0,
        AAD_PASSWORD,
        AAD_MSI,
        AAD_SPA,
        MAX_AAD_AUTH_TYPE
    };

    const char *AADAuths[] = { "SqlPassword", "ActiveDirectoryPassword", "ActiveDirectoryMsi", "ActiveDirectorySPA" };

    bool isAuthValid(_In_z_ const char* value, _In_ size_t value_len)
    {
        if (value_len <= 0)
            return false;

        bool isValid = false;
        for (short i = MIN_AAD_AUTH_TYPE; i < MAX_AAD_AUTH_TYPE && !isValid; i++)
        {
            if (!stricmp(value, AADAuths[i])) {
                isValid = true;
            }
        }

        return isValid;
    }

    bool isAADMsi(_In_z_ const char* value)
    {
        return (value != NULL && !stricmp(value, AADAuths[AAD_MSI]));
    }
}


// *** internal connection functions and classes ***

namespace {

connection_option const* get_connection_option( sqlsrv_conn* conn, _In_ SQLULEN key,
                                                     _In_ const connection_option conn_opts[] )
{
    for( int opt_idx = 0; conn_opts[opt_idx].conn_option_key != SQLSRV_CONN_OPTION_INVALID; ++opt_idx ) {

        if( key == conn_opts[opt_idx].conn_option_key ) {

            return &conn_opts[opt_idx];
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
                                                void* driver, _Inout_ std::string& connection_string )
{
    bool mars_mentioned = false;
    connection_option const* conn_opt;
    bool access_token_used = false;
    bool authentication_option_used = zend_hash_index_exists(options, SQLSRV_CONN_OPTION_AUTHENTICATION);

    try {
        // Since connection options access token and authentication cannot coexist, check if both of them are used.
        // If access token is specified, check UID and PWD as well.
        // No need to check the keyword Trusted_Connection because it is not among the acceptable options for SQLSRV drivers
        if (zend_hash_index_exists(options, SQLSRV_CONN_OPTION_ACCESS_TOKEN)) {
            bool invalidOptions = false;

            // UID and PWD have to be NULLs... throw an exception as long as the user has specified any of them in the connection string,
            // even if they may be empty strings. Likewise if the keyword Authentication exists
            if (uid != NULL || pwd != NULL || authentication_option_used) {
                invalidOptions = true;
            }

            CHECK_CUSTOM_ERROR(invalidOptions, conn, SQLSRV_ERROR_INVALID_OPTION_WITH_ACCESS_TOKEN ) {
                throw core::CoreException();
            }

            access_token_used = true;
        }

        // Check if Authentication is ActiveDirectoryMSI
        // https://docs.microsoft.com/en-ca/azure/active-directory/managed-identities-azure-resources/overview
        bool activeDirectoryMSI = false;
        if (authentication_option_used) {
            zval* auth_option = NULL;
            auth_option = zend_hash_index_find(options, SQLSRV_CONN_OPTION_AUTHENTICATION);

            char* option = NULL;
            if (auth_option != NULL) {
                option = Z_STRVAL_P(auth_option);
            }

            //if (option != NULL && !stricmp(option, AzureADOptions::AZURE_AUTH_AD_MSI)) {
            activeDirectoryMSI = AzureADOptions::isAADMsi(option);
            if (activeDirectoryMSI) {
                // There are two types of managed identities:
                // (1) A system-assigned managed identity: UID must be NULL
                // (2) A user-assigned managed identity: UID defined but must not be an empty string
                // In both cases, PWD must be NULL

                bool invalid = false;
                if (pwd != NULL) {
                    invalid = true;
                } else {
                    if (uid != NULL && strnlen_s(uid) == 0) {
                        invalid = true;
                    }
                }

                CHECK_CUSTOM_ERROR(invalid, conn, SQLSRV_ERROR_AAD_MSI_UID_PWD_NOT_NULL ) {
                    throw core::CoreException();
                }
            }
        }

        // Add the server name
        common_conn_str_append_func( ODBCConnOptions::SERVER, server, strnlen_s( server ), connection_string );

        // If uid is not present then we use trusted connection -- but not when access token or ActiveDirectoryMSI is used,
        // because they are incompatible
        if (!access_token_used && !activeDirectoryMSI) {
            if (uid == NULL || strnlen_s(uid) == 0) {
                connection_string += CONNECTION_OPTION_NO_CREDENTIALS;  //  "Trusted_Connection={Yes};"
            }
            else {
                bool escaped = core_is_conn_opt_value_escaped(uid, strnlen_s(uid));
                CHECK_CUSTOM_ERROR(!escaped, conn, SQLSRV_ERROR_UID_PWD_BRACES_NOT_ESCAPED) {
                    throw core::CoreException();
                }

                common_conn_str_append_func(ODBCConnOptions::UID, uid, strnlen_s(uid), connection_string);

                // if no password was given, then don't add a password to the connection string.  Perhaps the UID
                // given doesn't have a password?
                if (pwd != NULL) {
                    escaped = core_is_conn_opt_value_escaped(pwd, strnlen_s(pwd));
                    CHECK_CUSTOM_ERROR(!escaped, conn, SQLSRV_ERROR_UID_PWD_BRACES_NOT_ESCAPED) {
                        throw core::CoreException();
                    }

                    common_conn_str_append_func(ODBCConnOptions::PWD, pwd, strnlen_s(pwd), connection_string);
                }
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

            conn_opt = get_connection_option( conn, index, valid_conn_opts );

            if( index == SQLSRV_CONN_OPTION_MARS ) {
                mars_mentioned = true;
            }

            conn_opt->func( conn_opt, data, conn, connection_string );
        } ZEND_HASH_FOREACH_END();

        // MARS on if not explicitly turned off
        if( !mars_mentioned ) {
            connection_string += CONNECTION_OPTION_MARS_ON;
        }

    }
    catch( core::CoreException& ) {
        conn->ce_option.akv_reset();
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

void determine_server_version( _Inout_ sqlsrv_conn* conn )
{
    SQLSMALLINT info_len;
    char p[INFO_BUFFER_LEN] = {'\0'};
    core::SQLGetInfo( conn, SQL_DBMS_VER, p, INFO_BUFFER_LEN, &info_len );

    errno = 0;
    char version_major_str[3] = {'\0'};
    SERVER_VERSION version_major;
    memcpy_s( version_major_str, sizeof( version_major_str ), p, 2 );

    version_major_str[2] = {'\0'};
    version_major = static_cast<SERVER_VERSION>( atoi( version_major_str ));

    CHECK_CUSTOM_ERROR( version_major == 0 && ( errno == ERANGE || errno == EINVAL ), conn, SQLSRV_ERROR_UNKNOWN_SERVER_VERSION )
    {
        throw core::CoreException();
    }

    // SNAC won't connect to versions older than SQL Server 2000, so we know that the version is at least
    // that high
    conn->server_version = version_major;
}

void load_azure_key_vault(_Inout_ sqlsrv_conn* conn)
{
    // If column encryption is not enabled simply do nothing. Otherwise, check if Azure Key Vault
    // is required for encryption or decryption. Note, in order to load and configure Azure Key Vault,
    // all fields in conn->ce_option must be defined.
    if (!conn->ce_option.enabled || !conn->ce_option.akv_required)
        return;

    CHECK_CUSTOM_ERROR(conn->ce_option.akv_mode == -1, conn, SQLSRV_ERROR_AKV_AUTH_MISSING) {
        throw core::CoreException();
    }

    CHECK_CUSTOM_ERROR(!conn->ce_option.akv_id, conn, SQLSRV_ERROR_AKV_NAME_MISSING) {
        throw core::CoreException();
    }

    CHECK_CUSTOM_ERROR(!conn->ce_option.akv_secret, conn, SQLSRV_ERROR_AKV_SECRET_MISSING) {
        throw core::CoreException();
    }

    char *akv_id = conn->ce_option.akv_id.get();
    char *akv_secret = conn->ce_option.akv_secret.get();
    unsigned int id_len = strnlen_s(akv_id);
    unsigned int key_size = strnlen_s(akv_secret);

    configure_azure_key_vault(conn, AKV_CONFIG_FLAGS, conn->ce_option.akv_mode, 0);
    configure_azure_key_vault(conn, AKV_CONFIG_PRINCIPALID, akv_id, id_len);
    configure_azure_key_vault(conn, AKV_CONFIG_AUTHSECRET, akv_secret, key_size);
}

void configure_azure_key_vault(sqlsrv_conn* conn, BYTE config_attr, const DWORD config_value, size_t key_size)
{
    BYTE akv_data[sizeof(CEKEYSTOREDATA) + sizeof(DWORD) + 1];
    CEKEYSTOREDATA *pData = reinterpret_cast<CEKEYSTOREDATA*>(akv_data);

    char akv_name[] = "AZURE_KEY_VAULT";
    unsigned int name_len = 15;
    unsigned int wname_len = 0;
    sqlsrv_malloc_auto_ptr<SQLWCHAR> wakv_name;
    wakv_name = utf16_string_from_mbcs_string(SQLSRV_ENCODING_UTF8, akv_name, name_len, &wname_len);

    CHECK_CUSTOM_ERROR(wakv_name == 0, conn, SQLSRV_ERROR_CONNECT_STRING_ENCODING_TRANSLATE) {
        throw core::CoreException();
    }

    pData->name = (wchar_t *)wakv_name.get();

    pData->data[0] = config_attr;
    pData->dataSize = sizeof(config_attr) + sizeof(config_value);
    *reinterpret_cast<DWORD*>(&pData->data[1]) = config_value;

    core::SQLSetConnectAttr(conn, SQL_COPT_SS_CEKEYSTOREDATA, reinterpret_cast<SQLPOINTER>(pData), SQL_IS_POINTER);
}

void configure_azure_key_vault(sqlsrv_conn* conn, BYTE config_attr, const char* config_value, size_t key_size)
{
    BYTE akv_data[sizeof(CEKEYSTOREDATA) + MAX_CE_NAME_LEN];
    CEKEYSTOREDATA *pData = reinterpret_cast<CEKEYSTOREDATA*>(akv_data);

    char akv_name[] = "AZURE_KEY_VAULT";
    unsigned int name_len = 15;
    unsigned int wname_len = 0;
    sqlsrv_malloc_auto_ptr<SQLWCHAR> wakv_name;
    wakv_name = utf16_string_from_mbcs_string(SQLSRV_ENCODING_UTF8, akv_name, name_len, &wname_len);

    CHECK_CUSTOM_ERROR(wakv_name == 0, conn, SQLSRV_ERROR_CONNECT_STRING_ENCODING_TRANSLATE) {
        throw core::CoreException();
    }

    pData->name = (wchar_t *)wakv_name.get();

    pData->data[0] = config_attr;
    pData->dataSize = 1 + key_size;

    memcpy_s(pData->data + 1, key_size * sizeof(char), config_value, key_size);

    core::SQLSetConnectAttr(conn, SQL_COPT_SS_CEKEYSTOREDATA, reinterpret_cast<SQLPOINTER>(pData), SQL_IS_POINTER);
}

void common_conn_str_append_func( _In_z_ const char* odbc_name, _In_reads_(val_len) const char* val, _Inout_ size_t val_len, _Inout_ std::string& conn_str )
{
    // wrap a connection option in a quote.  It is presumed that any character that need to be escaped will
    // be escaped, such as a closing }.

    if( val_len > 0 && val[0] == '{' && val[val_len - 1] == '}' ) {
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
void conn_str_append_func::func( _In_ connection_option const* option, _In_ zval* value, sqlsrv_conn* /*conn*/, _Inout_ std::string& conn_str )
{
    const char* val_str = Z_STRVAL_P( value );
    size_t val_len = Z_STRLEN_P( value );
    common_conn_str_append_func( option->odbc_name, val_str, val_len, conn_str );
}

// do nothing for connection pooling since we handled it earlier when
// deciding which environment handle to use.
void conn_null_func::func( connection_option const* /*option*/, zval* /*value*/, sqlsrv_conn* /*conn*/, std::string& /*conn_str*/ )
{
}

void driver_set_func::func( _In_ connection_option const* option, _In_ zval* value, _Inout_ sqlsrv_conn* conn, _Inout_ std::string& conn_str )
{
    const char* val_str = Z_STRVAL_P( value );
    size_t val_len = Z_STRLEN_P( value );
    std::string driver_option( "" );
    common_conn_str_append_func( option->odbc_name, val_str, val_len, driver_option );

    conn->driver_version = ODBC_DRIVER_UNKNOWN;
    for ( short i = DRIVER_VERSION::FIRST; i <= DRIVER_VERSION::LAST && conn->driver_version == ODBC_DRIVER_UNKNOWN; ++i ) {
        std::string driver_name = CONNECTION_STRING_DRIVER_NAME[i];

        if (! driver_name.compare( driver_option ) ) {
            conn->driver_version = DRIVER_VERSION( i );
        }
    }

    CHECK_CUSTOM_ERROR( conn->driver_version == ODBC_DRIVER_UNKNOWN, conn, SQLSRV_ERROR_CONNECT_INVALID_DRIVER, val_str) {
        throw core::CoreException();
    }

    conn_str += driver_option;
}

void column_encryption_set_func::func( _In_ connection_option const* option, _In_ zval* value, _Inout_ sqlsrv_conn* conn, _Inout_ std::string& conn_str )
{
    convert_to_string( value );
    const char* value_str = Z_STRVAL_P( value );

    // Column Encryption is disabled by default, but if it is present and not
    // explicitly set to disabled or enabled, the ODBC driver will assume the
    // user is providing an attestation protocol and URL for enclave support.
    // For our purposes we need only set ce_option.enabled to true if not disabled.
    conn->ce_option.enabled = false;
    if ( stricmp(value_str, "disabled" )) {
        conn->ce_option.enabled = true;
    }

    conn_str += option->odbc_name;
    conn_str += "=";
    conn_str += value_str;
    conn_str += ";";
}

void ce_akv_str_set_func::func(_In_ connection_option const* option, _In_ zval* value, _Inout_ sqlsrv_conn* conn, _Inout_ std::string& conn_str)
{
    SQLSRV_ASSERT(Z_TYPE_P(value) == IS_STRING, "Azure Key Vault keywords accept only strings.");

    const char *value_str = Z_STRVAL_P(value);
    size_t value_len = Z_STRLEN_P(value);

    CHECK_CUSTOM_ERROR(value_len <= 0, conn, SQLSRV_ERROR_KEYSTORE_INVALID_VALUE) {
        throw core::CoreException();
    }

    switch (option->conn_option_key)
    {
    case SQLSRV_CONN_OPTION_KEYSTORE_AUTHENTICATION:
    {
        if (!stricmp(value_str, "KeyVaultPassword")) {
            conn->ce_option.akv_mode = AKVCFG_AUTHMODE_PASSWORD;
        } else if (!stricmp(value_str, "KeyVaultClientSecret")) {
            conn->ce_option.akv_mode = AKVCFG_AUTHMODE_CLIENTKEY;
        } else {
            CHECK_CUSTOM_ERROR(1, conn, SQLSRV_ERROR_INVALID_AKV_AUTHENTICATION_OPTION) {
                throw core::CoreException();
            }
        }

        conn->ce_option.akv_required = true;
        break;
    }
    case SQLSRV_CONN_OPTION_KEYSTORE_PRINCIPAL_ID:
    case SQLSRV_CONN_OPTION_KEYSTORE_SECRET:
    {
        // Create a new string to save a copy of the zvalue
        char *pValue = static_cast<char*>(sqlsrv_malloc(value_len + 1));
        memcpy_s(pValue, value_len + 1, value_str, value_len);
        pValue[value_len] = '\0';   // this makes sure there will be no trailing garbage

        // This will free the existing memory block before assigning the new pointer -- the user might set the value(s) more than once
        if (option->conn_option_key == SQLSRV_CONN_OPTION_KEYSTORE_PRINCIPAL_ID) {
            conn->ce_option.akv_id = pValue;
        } else {
            conn->ce_option.akv_secret = pValue;
        }
        conn->ce_option.akv_required = true;
        break;
    }
    default:
        SQLSRV_ASSERT(false, "ce_akv_str_set_func: Invalid AKV option!");
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
    while( isspace(( unsigned char )value_in[last_char] )) {
        value_in[last_char] = '\0';
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

void access_token_set_func::func( _In_ connection_option const* option, _In_ zval* value, _Inout_ sqlsrv_conn* conn, _Inout_ std::string& conn_str )
{
    SQLSRV_ASSERT(Z_TYPE_P(value) == IS_STRING, "An access token must be a byte string.");

    size_t value_len = Z_STRLEN_P(value);

    CHECK_CUSTOM_ERROR(value_len <= 0, conn, SQLSRV_ERROR_EMPTY_ACCESS_TOKEN) {
        throw core::CoreException();
    }

    const char* value_str = Z_STRVAL_P( value );

    // The SQL_COPT_SS_ACCESS_TOKEN pre-connection attribute allows the use of an access token (in the format extracted from
    // an OAuth JSON response), obtained from Azure AD for authentication instead of username and password, and also
    // bypasses the negotiation and obtaining of an access token by the driver. To use an access token, set the
    // SQL_COPT_SS_ACCESS_TOKEN connection attribute to a pointer to an ACCESSTOKEN structure
    //
    //  typedef struct AccessToken
    //  {
    //      unsigned int dataSize;
    //      char data[];
    //  } ACCESSTOKEN;
    //
    // NOTE: The ODBC Driver version 13.1 only supports this authentication on Windows.
    //
    // A valid access token byte string must be expanded so that each byte is followed by a 0 padding byte,
    // similar to a UCS-2 string containing only ASCII characters
    //
    // See https://docs.microsoft.com/sql/connect/odbc/using-azure-active-directory#authenticating-with-an-access-token

    size_t dataSize = 2 * value_len;

    sqlsrv_malloc_auto_ptr<ACCESSTOKEN> accToken;
    accToken = reinterpret_cast<ACCESSTOKEN*>(sqlsrv_malloc(sizeof(ACCESSTOKEN) + dataSize));

    ACCESSTOKEN *pAccToken = accToken.get();
    SQLSRV_ASSERT(pAccToken != NULL, "Something went wrong when trying to allocate memory for the access token.");

    pAccToken->dataSize = dataSize;

    // Expand access token with padding bytes
    for (size_t i = 0, j = 0; i < dataSize; i += 2, j++) {
        pAccToken->data[i] = value_str[j];
        pAccToken->data[i+1] = 0;
    }

    core::SQLSetConnectAttr(conn, SQL_COPT_SS_ACCESS_TOKEN, reinterpret_cast<SQLPOINTER>(pAccToken), SQL_IS_POINTER);

    // Save the pointer because SQLDriverConnect() will use it to make connection to the server
    conn->azure_ad_access_token = pAccToken;
    accToken.transferred();
}

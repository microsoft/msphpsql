#ifndef PHP_PDO_SQLSRV_INT_H
#define PHP_PDO_SQLSRV_INT_H

//---------------------------------------------------------------------------------------------------------------------------------
// File: php_pdo_sqlsrv_int.h
//
// Contents: Internal declarations for the extension
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
#include "version.h"

extern "C" {
  #include "pdo/php_pdo.h"
  #include "pdo/php_pdo_driver.h"
}

#include <vector>
#include <map>

//*********************************************************************************************************************************
// Global variables
//*********************************************************************************************************************************

// henv context for creating connections
extern sqlsrv_context* g_pdo_henv_cp;
extern sqlsrv_context* g_pdo_henv_ncp;

// used for getting the version information
extern HMODULE g_sqlsrv_hmodule;

// macros used to access the global variables.  Use these to make global variable access agnostic to threads
#ifdef ZTS
#define PDO_SQLSRV_G(v) TSRMG(pdo_sqlsrv_globals_id, zend_pdo_sqlsrv_globals *, v)
#else
#define PDO_SQLSRV_G(v) pdo_sqlsrv_globals.v
#endif

// INI settings and constants
// (these are defined as macros to allow concatenation as we do below)
#define INI_PDO_SQLSRV_CLIENT_BUFFER_MAX_SIZE "client_buffer_max_kb_size"
#define INI_PDO_SQLSRV_LOG   "log_severity"
#define INI_PDO_SQLSRV_MORE_ERRORS  "report_additional_errors"
#define INI_PREFIX           "pdo_sqlsrv."

#ifndef _WIN32
#define INI_PDO_SET_LOCALE_INFO                 "set_locale_info"
#endif

PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY( INI_PREFIX INI_PDO_SQLSRV_LOG , "0", PHP_INI_ALL, OnUpdateLong, pdo_log_severity,
                         zend_pdo_sqlsrv_globals, pdo_sqlsrv_globals )
    STD_PHP_INI_ENTRY( INI_PREFIX INI_PDO_SQLSRV_CLIENT_BUFFER_MAX_SIZE , INI_BUFFERED_QUERY_LIMIT_DEFAULT, PHP_INI_ALL, OnUpdateLong,
                       client_buffer_max_size, zend_pdo_sqlsrv_globals, pdo_sqlsrv_globals )
    STD_PHP_INI_ENTRY(INI_PREFIX INI_PDO_SQLSRV_MORE_ERRORS, "1", PHP_INI_ALL, OnUpdateLong, report_additional_errors, zend_pdo_sqlsrv_globals, pdo_sqlsrv_globals)
#ifndef _WIN32
    STD_PHP_INI_ENTRY(INI_PREFIX INI_PDO_SET_LOCALE_INFO, "2", PHP_INI_ALL, OnUpdateLong, set_locale_info,
                        zend_pdo_sqlsrv_globals, pdo_sqlsrv_globals)
#endif
PHP_INI_END()


//*********************************************************************************************************************************
// Constants and Types
//*********************************************************************************************************************************

// sqlsrv driver specific PDO attributes
enum PDO_SQLSRV_ATTR {

    // The custom attributes for this driver:
    SQLSRV_ATTR_ENCODING = PDO_ATTR_DRIVER_SPECIFIC,
    SQLSRV_ATTR_QUERY_TIMEOUT,
    SQLSRV_ATTR_DIRECT_QUERY,
    SQLSRV_ATTR_CURSOR_SCROLL_TYPE,
    SQLSRV_ATTR_CLIENT_BUFFER_MAX_KB_SIZE,
    SQLSRV_ATTR_FETCHES_NUMERIC_TYPE,
    SQLSRV_ATTR_FETCHES_DATETIME_TYPE,
    SQLSRV_ATTR_FORMAT_DECIMALS,
    SQLSRV_ATTR_DECIMAL_PLACES,
    SQLSRV_ATTR_DATA_CLASSIFICATION
};

// valid set of values for TransactionIsolation connection option
namespace PDOTxnIsolationValues {
    
    const char READ_UNCOMMITTED[] = "READ_UNCOMMITTED";
    const char READ_COMMITTED[] = "READ_COMMITTED";
    const char REPEATABLE_READ[] = "REPEATABLE_READ";
    const char SERIALIZABLE[] = "SERIALIZABLE";
    const char SNAPSHOT[] = "SNAPSHOT";
}


//*********************************************************************************************************************************
// Initialization
//*********************************************************************************************************************************

// Basic string parser
class string_parser
{
    protected:
        const char* orig_str;
        sqlsrv_context* ctx;
        int len;
        int pos;
        unsigned int current_key;
        HashTable* element_ht;
        inline bool next(void);
        inline bool is_eos(void);
        inline bool is_white_space( _In_ char c );
        bool discard_white_spaces(void);
        void add_key_value_pair( _In_reads_(len) const char* value, _In_ int len );
};


//*********************************************************************************************************************************
// PDO DSN Parser
//*********************************************************************************************************************************

// Parser class used to parse DSN connection string.
class conn_string_parser : private string_parser
{
    enum States
    {
        FirstKeyValuePair,
        Key,
        Value,
        ValueContent1,
        ValueContent2,
        RCBEncountered,
        NextKeyValuePair,
    };

    private:
        const char* current_key_name;
        int discard_trailing_white_spaces( _In_reads_(len) const char* str, _Inout_ int len );
        void validate_key( _In_reads_(key_len) const char *key, _Inout_ int key_len);

    protected:
        void add_key_value_pair( _In_reads_(len) const char* value, _In_ int len);

    public:
        conn_string_parser( _In_ sqlsrv_context& ctx, _In_ const char* dsn, _In_ int len, _In_ HashTable* conn_options_ht );
        void parse_conn_string( void );
};


//*********************************************************************************************************************************
// PDO Query Parser
//*********************************************************************************************************************************

// Parser class used to parse DSN named placeholders.
class sql_string_parser : private string_parser
{
    private:
        bool is_placeholder_char(char);
    public:
        void add_key_int_value_pair( _In_ unsigned int value );
        sql_string_parser(_In_ sqlsrv_context& ctx, _In_ const char* sql_str, _In_ int len, _In_ HashTable* placeholder_ht);
        void parse_sql_string(void);
};


//*********************************************************************************************************************************
// Connection
//*********************************************************************************************************************************

extern const connection_option PDO_CONN_OPTS[];

int pdo_sqlsrv_db_handle_factory( _Inout_ pdo_dbh_t *dbh, _In_opt_ zval *driver_options);

// a core layer pdo dbh object.  This object inherits and overrides the statement factory
struct pdo_sqlsrv_dbh : public sqlsrv_conn {

    zval* stmts;
    bool direct_query;
    long query_timeout;
    zend_long client_buffer_max_size;
    bool fetch_numeric;
    bool fetch_datetime;
    bool format_decimals;
    short decimal_places;
    short use_national_characters;

    pdo_sqlsrv_dbh( _In_ SQLHANDLE h, _In_ error_callback e, _In_ void* driver );
};


//*********************************************************************************************************************************
// Statement
//*********************************************************************************************************************************

struct stmt_option_encoding : public stmt_option_functor {
    virtual void operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /*opt*/, _In_ zval* value_z );
};

struct stmt_option_pdo_scrollable : public stmt_option_functor {
    virtual void operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /*opt*/, _In_ zval* value_z );
};

struct stmt_option_direct_query : public stmt_option_functor {
    virtual void operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /*opt*/, _In_ zval* value_z );
};

struct stmt_option_cursor_scroll_type : public stmt_option_functor {
    virtual void operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /*opt*/, _In_ zval* value_z );
};

struct stmt_option_emulate_prepares : public stmt_option_functor {
    virtual void operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /*opt*/, _In_ zval* value_z );
};

struct stmt_option_fetch_numeric : public stmt_option_functor {
    virtual void operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /*opt*/, _In_ zval* value_z );
};

struct stmt_option_fetch_datetime : public stmt_option_functor {
    virtual void operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /*opt*/, _In_ zval* value_z );
};

extern struct pdo_stmt_methods pdo_sqlsrv_stmt_methods;

// a core layer pdo stmt object. This object inherits and overrides the callbacks necessary
struct pdo_sqlsrv_stmt : public sqlsrv_stmt {
    pdo_sqlsrv_stmt( _In_ sqlsrv_conn* c, _In_ SQLHANDLE handle, _In_ error_callback e, _In_ void* drv ) :
        sqlsrv_stmt( c, handle, e, drv ), 
        direct_query( false ),
        direct_query_subst_string( NULL ),
        direct_query_subst_string_len( 0 ),
        placeholders(NULL),
        bound_column_param_types( NULL ),
        fetch_numeric( false ),
        fetch_datetime( false )
    {
        pdo_sqlsrv_dbh* db = static_cast<pdo_sqlsrv_dbh*>( c );
        direct_query = db->direct_query;
        fetch_numeric = db->fetch_numeric;
        fetch_datetime = db->fetch_datetime;
        format_decimals = db->format_decimals;
        decimal_places = db->decimal_places;
        query_timeout = db->query_timeout;
    }

    virtual ~pdo_sqlsrv_stmt( void );

    // driver specific conversion rules from a SQL Server/ODBC type to one of the SQLSRV_PHPTYPE_* constants
    // for PDO, everything is a string, so we return SQLSRV_PHPTYPE_STRING for all SQL types
    virtual sqlsrv_phptype sql_type_to_php_type( _In_ SQLINTEGER sql_type, _In_ SQLUINTEGER size, _In_ bool prefer_string_to_stream );

    bool direct_query;                        // flag set if the query should be executed directly or prepared
    const char* direct_query_subst_string;    // if the query is direct, hold the substitution string if using named parameters
    size_t direct_query_subst_string_len;        // length of query string used for direct queries
    HashTable* placeholders;                    // hashtable of named placeholders to keep track of params ordering in emulate prepare

    pdo_param_type* bound_column_param_types;
    bool fetch_numeric;
    bool fetch_datetime;
};


//*********************************************************************************************************************************
// Error Handling Functions
//*********************************************************************************************************************************

// represents the mapping between an error_code and the corresponding error message.
struct pdo_error {

    unsigned int error_code;
    sqlsrv_error_const sqlsrv_error;
};

// called when an error occurs in the core layer.  These routines are set as the error_callback in a
// context.  The context is passed to this function since it contains the function

bool pdo_sqlsrv_handle_env_error( _Inout_ sqlsrv_context& ctx, _In_opt_ unsigned int sqlsrv_error_code, _In_opt_ int warning, 
                                  _In_opt_ va_list* print_args );
bool pdo_sqlsrv_handle_dbh_error( _Inout_ sqlsrv_context& ctx, _In_opt_ unsigned int sqlsrv_error_code, _In_opt_ int warning, 
                                  _In_opt_ va_list* print_args );
bool pdo_sqlsrv_handle_stmt_error( _Inout_ sqlsrv_context& ctx, _In_opt_ unsigned int sqlsrv_error_code, _In_opt_ int warning, 
                                   _In_opt_ va_list* print_args );

// common routine to transfer a sqlsrv_context's error to a PDO zval
void pdo_sqlsrv_retrieve_context_error( _In_ sqlsrv_error const* last_error, _Out_ zval* pdo_zval );

// reset the errors from the last operation
inline void pdo_reset_dbh_error( _Inout_ pdo_dbh_t* dbh )
{
    strcpy_s( dbh->error_code, sizeof( dbh->error_code ), "00000" );    // 00000 means no error

    // release the last statement from the dbh so that error handling won't have a statement passed to it
    if( dbh->query_stmt ) {
        dbh->query_stmt = NULL;
        zval_ptr_dtor( &dbh->query_stmt_zval );
    }

    // if the driver isn't valid, just return (PDO calls close sometimes more than once?)
    if( dbh->driver_data == NULL ) {
        return;
    }

    // reset the last error on the sqlsrv_context
    sqlsrv_context* ctx = static_cast<sqlsrv_conn*>( dbh->driver_data );
    
    if( ctx->last_error() ) {
        ctx->last_error().reset();
    }
}

#define PDO_LOG_NOTICE(message) \
   core_sqlsrv_register_severity_checker(pdo_severity_check); \
   LOG(SEV_NOTICE, message);

#define PDO_RESET_DBH_ERROR     pdo_reset_dbh_error( dbh );

inline void pdo_reset_stmt_error( _Inout_ pdo_stmt_t* stmt )
{
    strcpy_s( stmt->error_code, sizeof( stmt->error_code ), "00000" );    // 00000 means no error

    // if the driver isn't valid, just return (PDO calls close sometimes more than once?)
    if( stmt->driver_data == NULL ) {
        return;
    }

    // reset the last error on the sqlsrv_context
    sqlsrv_context* ctx = static_cast<sqlsrv_stmt*>( stmt->driver_data );
    
    if( ctx->last_error() ) {
        ctx->last_error().reset();
    }
}

#define PDO_RESET_STMT_ERROR    pdo_reset_stmt_error( stmt );

// validate the driver objects
#define PDO_VALIDATE_CONN  if( dbh->driver_data == NULL ) { DIE( "Invalid driver data in PDO object." ); }
#define PDO_VALIDATE_STMT  if( stmt->driver_data == NULL ) { DIE( "Invalid driver data in PDOStatement object." ); }


//*********************************************************************************************************************************
// Utility Functions
//*********************************************************************************************************************************

// List of PDO specific error messages.
enum PDO_ERROR_CODES {
  
    PDO_SQLSRV_ERROR_INVALID_DBH_ATTR = SQLSRV_ERROR_DRIVER_SPECIFIC,
    PDO_SQLSRV_ERROR_INVALID_STMT_ATTR,
    PDO_SQLSRV_ERROR_INVALID_ENCODING,
    PDO_SQLSRV_ERROR_INVALID_DRIVER_PARAM,
    PDO_SQLSRV_ERROR_PDO_STMT_UNSUPPORTED,
    PDO_SQLSRV_ERROR_UNSUPPORTED_DBH_ATTR,
    PDO_SQLSRV_ERROR_STMT_LEVEL_ATTR,
    PDO_SQLSRV_ERROR_READ_ONLY_DBH_ATTR,
    PDO_SQLSRV_ERROR_INVALID_STMT_OPTION,
    PDO_SQLSRV_ERROR_INVALID_CURSOR_TYPE,
    PDO_SQLSRV_ERROR_FUNCTION_NOT_IMPLEMENTED,
    PDO_SQLSRV_ERROR_PARAM_PARSE,
    PDO_SQLSRV_ERROR_LAST_INSERT_ID,
    PDO_SQLSRV_ERROR_INVALID_COLUMN_DRIVER_DATA,
    PDO_SQLSRV_ERROR_COLUMN_TYPE_DOES_NOT_SUPPORT_ENCODING,
    PDO_SQLSRV_ERROR_INVALID_DRIVER_COLUMN_ENCODING,
    PDO_SQLSRV_ERROR_INVALID_DRIVER_PARAM_TYPE,
    PDO_SQLSRV_ERROR_INVALID_DRIVER_PARAM_ENCODING,
    PDO_SQLSRV_ERROR_INVALID_PARAM_DIRECTION,
    PDO_SQLSRV_ERROR_INVALID_OUTPUT_STRING_SIZE,
    PDO_SQLSRV_ERROR_CURSOR_ATTR_AT_PREPARE_ONLY,
    PDO_SQLSRV_ERROR_INVALID_DSN_STRING,
    PDO_SQLSRV_ERROR_INVALID_DSN_KEY,
    PDO_SQLSRV_ERROR_INVALID_DSN_VALUE,
    PDO_SQLSRV_ERROR_SERVER_NOT_SPECIFIED,
    PDO_SQLSRV_ERROR_DSN_STRING_ENDED_UNEXPECTEDLY,
    PDO_SQLSRV_ERROR_EXTRA_SEMI_COLON_IN_DSN_STRING,
    SQLSRV_ERROR_UNESCAPED_RIGHT_BRACE_IN_DSN,
    PDO_SQLSRV_ERROR_RCB_MISSING_IN_DSN_VALUE,
    PDO_SQLSRV_ERROR_DQ_ATTR_AT_PREPARE_ONLY,
    PDO_SQLSRV_ERROR_INVALID_COLUMN_INDEX,
    PDO_SQLSRV_ERROR_INVALID_OUTPUT_PARAM_TYPE,
    PDO_SQLSRV_ERROR_INVALID_CURSOR_WITH_SCROLL_TYPE,
    PDO_SQLSRV_ERROR_EMULATE_INOUT_UNSUPPORTED,
    PDO_SQLSRV_ERROR_INVALID_AUTHENTICATION_OPTION,
    PDO_SQLSRV_ERROR_CE_DIRECT_QUERY_UNSUPPORTED,
    PDO_SQLSRV_ERROR_CE_EMULATE_PREPARE_UNSUPPORTED,
    PDO_SQLSRV_ERROR_EXTENDED_STRING_TYPE_INVALID
};

extern pdo_error PDO_ERRORS[];

#define THROW_PDO_ERROR( ctx, custom, ... ) \
    call_error_handler( ctx, custom, false, ## __VA_ARGS__ ); \
    throw pdo::PDOException();

namespace pdo {

    // an error which occurred in our PDO driver,  NOT an exception thrown by PDO
    struct PDOException : public core::CoreException {

        PDOException() : CoreException()
        {
        }
    };

} // namespace pdo

// check the global variable of pdo_sqlsrv severity whether the message qualifies to be logged with the LOG macro
bool pdo_severity_check(_In_ unsigned int severity);

#endif  /* PHP_PDO_SQLSRV_INT_H */

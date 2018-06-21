#ifndef PHP_SQLSRV_H
#define PHP_SQLSRV_H

//---------------------------------------------------------------------------------------------------------------------------------
// File: php_sqlsrv.h
//
// Contents: Declarations for the extension
//
// Comments: Also contains "internal" declarations shared across source files. 
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

#include "core_sqlsrv.h"
#include "version.h"

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#ifdef PHP_WIN32
#define PHP_SQLSRV_API __declspec(dllexport)
#else
#define PHP_SQLSRV_API
#endif

// OACR is an internal Microsoft static code analysis tool
#if defined(OACR)
#include <oacr.h>
OACR_WARNING_PUSH
OACR_WARNING_DISABLE( ALLOC_SIZE_OVERFLOW, "Third party code." )
OACR_WARNING_DISABLE( INDEX_NEGATIVE, "Third party code." )
OACR_WARNING_DISABLE( UNANNOTATED_BUFFER, "Third party code." )
OACR_WARNING_DISABLE( INDEX_UNDERFLOW, "Third party code." )
OACR_WARNING_DISABLE( REALLOCLEAK, "Third party code." )
#endif

extern "C" {

#if defined(_MSC_VER)
#pragma warning(push)
#pragma warning( disable: 4005 4100 4127 4142 4244 4505 4530 )
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

#if _MSC_VER >= 1400
// typedef and macro to prevent a conflict between php.h and ws2tcpip.h.  
// php.h defines this constant as unsigned int which causes a compile error 
// in ws2tcpip.h. Fortunately php.h allows an override by defining
// HAVE_SOCKLEN_T. Since ws2tcpip.h isn't included until later, we define 
// socklen_t here and override the php.h version.
typedef int socklen_t;
#define HAVE_SOCKLEN_T
#endif

#if defined(_MSC_VER)
#pragma warning(pop)
#endif

#if PHP_MAJOR_VERSION < 7
#error Trying to compile "Microsoft Drivers for PHP for SQL Server (SQLSRV Driver)" with an unsupported version of PHP
#endif

#if ZEND_DEBUG
// debug build causes warning C4505 to pop up from the Zend header files
#pragma warning( disable: 4505 )
#endif

}   // extern "C"

//*********************************************************************************************************************************
// Initialization Functions
//*********************************************************************************************************************************

// module global variables (initialized in minit and freed in mshutdown)
extern HashTable* g_ss_errors_ht;
extern HashTable* g_ss_encodings_ht;
extern HashTable* g_ss_warnings_to_ignore_ht;

// variables set during initialization
extern zend_module_entry g_sqlsrv_module_entry;   // describes the extension to PHP
extern HMODULE g_sqlsrv_hmodule;                  // used for getting the version information

// henv context for creating connections
extern sqlsrv_context* g_ss_henv_cp;
extern sqlsrv_context* g_ss_henv_ncp;

extern bool isVistaOrGreater;                     // used to determine if OS is Vista or Greater

#define phpext_sqlsrv_ptr &g_sqlsrv_module_entry

// module initialization
PHP_MINIT_FUNCTION(sqlsrv);
// module shutdown function
PHP_MSHUTDOWN_FUNCTION(sqlsrv);
// request initialization function
PHP_RINIT_FUNCTION(sqlsrv);
// request shutdown function
PHP_RSHUTDOWN_FUNCTION(sqlsrv);
// module info function (info returned by phpinfo())
PHP_MINFO_FUNCTION(sqlsrv);

//*********************************************************************************************************************************
// Connection
//*********************************************************************************************************************************
PHP_FUNCTION(sqlsrv_connect);
PHP_FUNCTION(sqlsrv_begin_transaction);
PHP_FUNCTION(sqlsrv_client_info);
PHP_FUNCTION(sqlsrv_close);
PHP_FUNCTION(sqlsrv_commit);
PHP_FUNCTION(sqlsrv_query);
PHP_FUNCTION(sqlsrv_prepare);
PHP_FUNCTION(sqlsrv_rollback);
PHP_FUNCTION(sqlsrv_server_info);

struct ss_sqlsrv_conn : sqlsrv_conn
{
    HashTable*     stmts;
    bool           date_as_string;
    bool           in_transaction;     // flag set when inside a transaction and used for checking validity of tran API calls
    
    // static variables used in process_params
    static const char* resource_name;
    static int descriptor;

    // initialize with default values
    ss_sqlsrv_conn( _In_ SQLHANDLE h, _In_ error_callback e, _In_ void* drv TSRMLS_DC ) : 
        sqlsrv_conn( h, e, drv, SQLSRV_ENCODING_SYSTEM TSRMLS_CC ),
        stmts( NULL ),
        date_as_string( false ),
        in_transaction( false )
    {
    }
};

// resource destructor
void __cdecl sqlsrv_conn_dtor( _Inout_ zend_resource *rsrc TSRMLS_DC );

//*********************************************************************************************************************************
// Statement
//*********************************************************************************************************************************

// holds the field names for reuse by sqlsrv_fetch_array/object as keys
struct sqlsrv_fetch_field_name {
    char* name;
    SQLLEN len;
};

struct stmt_option_ss_scrollable : public stmt_option_functor {

    virtual void operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /*opt*/, _In_ zval* value_z TSRMLS_DC );
};

// This object inherits and overrides the callbacks necessary
struct ss_sqlsrv_stmt : public sqlsrv_stmt {

    ss_sqlsrv_stmt( _In_ sqlsrv_conn* c, _In_ SQLHANDLE handle, _In_ error_callback e, _In_ void* drv TSRMLS_DC );

    virtual ~ss_sqlsrv_stmt( void );

    void new_result_set( TSRMLS_D ); 

    // driver specific conversion rules from a SQL Server/ODBC type to one of the SQLSRV_PHPTYPE_* constants
    sqlsrv_phptype sql_type_to_php_type( _In_ SQLINTEGER sql_type, _In_ SQLUINTEGER size, _In_ bool prefer_string_to_stream );

    bool prepared;                               // whether the statement has been prepared yet (used for error messages)
	zend_ulong conn_index;						 // index into the connection hash that contains this statement structure
    zval* params_z;                              // hold parameters passed to sqlsrv_prepare but not used until sqlsrv_execute
    sqlsrv_fetch_field_name* fetch_field_names;  // field names for current results used by sqlsrv_fetch_array/object as keys
    int fetch_fields_count;

    // static variables used in process_params
    static const char* resource_name;
    static int descriptor;

};

// holds the field names for reuse by sqlsrv_fetch_array/object as keys
struct sqlsrv_fetch_field {
    char* name;
    unsigned int len;
};

// holds the stream param and the encoding that it was assigned
struct sqlsrv_stream_encoding {
    zval* stream_z;
    unsigned int encoding;

    sqlsrv_stream_encoding( _In_ zval* str_z, _In_ unsigned int enc ) :
        stream_z( str_z ), encoding( enc )
    {
    }
};

// *** statement functions ***
PHP_FUNCTION(sqlsrv_cancel);
PHP_FUNCTION(sqlsrv_execute);
PHP_FUNCTION(sqlsrv_fetch);
PHP_FUNCTION(sqlsrv_fetch_array);
PHP_FUNCTION(sqlsrv_fetch_object);
PHP_FUNCTION(sqlsrv_field_metadata);
PHP_FUNCTION(sqlsrv_free_stmt);
PHP_FUNCTION(sqlsrv_get_field);
PHP_FUNCTION(sqlsrv_has_rows);
PHP_FUNCTION(sqlsrv_next_result);
PHP_FUNCTION(sqlsrv_num_fields);
PHP_FUNCTION(sqlsrv_num_rows);
PHP_FUNCTION(sqlsrv_rows_affected);
PHP_FUNCTION(sqlsrv_send_stream_data);

// resource destructor
void __cdecl sqlsrv_stmt_dtor( _Inout_ zend_resource *rsrc TSRMLS_DC );

// "internal" statement functions shared by functions in conn.cpp and stmt.cpp
void bind_params( _Inout_ ss_sqlsrv_stmt* stmt TSRMLS_DC );
bool sqlsrv_stmt_common_execute( sqlsrv_stmt* s, const SQLCHAR* sql_string, int sql_len, bool direct, const char* function 
                                 TSRMLS_DC );
void free_odbc_resources( ss_sqlsrv_stmt* stmt TSRMLS_DC );
void free_stmt_resource( _Inout_ zval* stmt_z TSRMLS_DC );

//*********************************************************************************************************************************
// Type Functions
//*********************************************************************************************************************************

// type functions for SQL types.
// to expose SQL Server paramterized types, we use functions that return encoded integers that contain the size/precision etc.
// for example, SQLSRV_SQLTYPE_VARCHAR(4000) matches the usage of SQLSRV_SQLTYPE_INT with the size added. 
PHP_FUNCTION(SQLSRV_SQLTYPE_BINARY);
PHP_FUNCTION(SQLSRV_SQLTYPE_CHAR);
PHP_FUNCTION(SQLSRV_SQLTYPE_DECIMAL);
PHP_FUNCTION(SQLSRV_SQLTYPE_NCHAR);
PHP_FUNCTION(SQLSRV_SQLTYPE_NUMERIC);
PHP_FUNCTION(SQLSRV_SQLTYPE_NVARCHAR);
PHP_FUNCTION(SQLSRV_SQLTYPE_VARBINARY);
PHP_FUNCTION(SQLSRV_SQLTYPE_VARCHAR);

// PHP type functions
// strings and streams may have an encoding parameterized, so we use the functions
// the valid encodings are SQLSRV_ENC_BINARY and SQLSRV_ENC_CHAR.
PHP_FUNCTION(SQLSRV_PHPTYPE_STREAM);
PHP_FUNCTION(SQLSRV_PHPTYPE_STRING);

//*********************************************************************************************************************************
// Global variables
//*********************************************************************************************************************************

extern "C" {

// request level variables
ZEND_BEGIN_MODULE_GLOBALS(sqlsrv)

// global objects for errors and warnings.  These are returned by sqlsrv_errors.
zval errors;
zval warnings;

// flags for error handling and logging (set via sqlsrv_configure or php.ini)
zend_long log_severity;
zend_long log_subsystems;
zend_long current_subsystem;
zend_bool warnings_return_as_errors;
zend_long buffered_query_limit;

ZEND_END_MODULE_GLOBALS(sqlsrv)

ZEND_EXTERN_MODULE_GLOBALS(sqlsrv);

}

// macro used to access the global variables.  Use it to make global variable access agnostic to threads
#define SQLSRV_G(v) ZEND_MODULE_GLOBALS_ACCESSOR(sqlsrv, v)

#if defined(ZTS)
ZEND_TSRMLS_CACHE_EXTERN();
#endif

// INI settings and constants
// (these are defined as macros to allow concatenation as we do below)
#define INI_WARNINGS_RETURN_AS_ERRORS   "WarningsReturnAsErrors"
#define INI_LOG_SEVERITY                "LogSeverity"
#define INI_LOG_SUBSYSTEMS              "LogSubsystems"
#define INI_BUFFERED_QUERY_LIMIT        "ClientBufferMaxKBSize"
#define INI_PREFIX                      "sqlsrv."

PHP_INI_BEGIN()
    STD_PHP_INI_BOOLEAN( INI_PREFIX INI_WARNINGS_RETURN_AS_ERRORS , "1", PHP_INI_ALL, OnUpdateBool, warnings_return_as_errors,
                         zend_sqlsrv_globals, sqlsrv_globals )
    STD_PHP_INI_ENTRY( INI_PREFIX INI_LOG_SEVERITY, "0", PHP_INI_ALL, OnUpdateLong, log_severity, zend_sqlsrv_globals, 
                       sqlsrv_globals )
    STD_PHP_INI_ENTRY( INI_PREFIX INI_LOG_SUBSYSTEMS, "0", PHP_INI_ALL, OnUpdateLong, log_subsystems, zend_sqlsrv_globals, 
                       sqlsrv_globals )
    STD_PHP_INI_ENTRY( INI_PREFIX INI_BUFFERED_QUERY_LIMIT, INI_BUFFERED_QUERY_LIMIT_DEFAULT, PHP_INI_ALL, OnUpdateLong, buffered_query_limit,
                       zend_sqlsrv_globals, sqlsrv_globals )
PHP_INI_END()

//*********************************************************************************************************************************
// Configuration
//*********************************************************************************************************************************
// These functions set and retrieve configuration settings.  Configuration settings defined are:
//    WarningsReturnAsErrors - treat all ODBC warnings as errors and return false from sqlsrv APIs.
//    LogSeverity - combination of severity of messages to log (see Logging)
//    LogSubsystems - subsystems within sqlsrv to log messages (see Logging)

PHP_FUNCTION(sqlsrv_configure);
PHP_FUNCTION(sqlsrv_get_config);

//*********************************************************************************************************************************
// Errors
//*********************************************************************************************************************************

// represents the mapping between an error_code and the corresponding error message.
struct ss_error {

    unsigned int error_code;
    sqlsrv_error_const sqlsrv_error;
};

// List of all driver specific error codes.
enum SS_ERROR_CODES {
  
    SS_SQLSRV_ERROR_ALREADY_IN_TXN = SQLSRV_ERROR_DRIVER_SPECIFIC,
    SS_SQLSRV_ERROR_NOT_IN_TXN,
    SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER,
    SS_SQLSRV_ERROR_REGISTER_RESOURCE,
    SS_SQLSRV_ERROR_INVALID_CONNECTION_KEY, 
    SS_SQLSRV_ERROR_STATEMENT_NOT_PREPARED,
    SS_SQLSRV_ERROR_INVALID_FETCH_STYLE,
    SS_SQLSRV_ERROR_INVALID_FETCH_TYPE,
    SS_SQLSRV_WARNING_FIELD_NAME_EMPTY,
    SS_SQLSRV_ERROR_ZEND_OBJECT_FAILED,
    SS_SQLSRV_ERROR_ZEND_BAD_CLASS,
    SS_SQLSRV_ERROR_STATEMENT_SCROLLABLE,
    SS_SQLSRV_ERROR_STATEMENT_NOT_SCROLLABLE,
    SS_SQLSRV_ERROR_INVALID_OPTION,
    SS_SQLSRV_ERROR_PARAM_INVALID_INDEX,
    SS_SQLSRV_ERROR_INVALID_PARAMETER_PRECISION,
    SS_SQLSRV_ERROR_INVALID_PARAMETER_DIRECTION,
    SS_SQLSRV_ERROR_VAR_REQUIRED,
    SS_SQLSRV_ERROR_CONNECT_ILLEGAL_ENCODING,
    SS_SQLSRV_ERROR_CONNECT_BRACES_NOT_ESCAPED,
    SS_SQLSRV_ERROR_INVALID_OUTPUT_PARAM_TYPE,
    SS_SQLSRV_ERROR_PARAM_VAR_NOT_REF,
    SS_SQLSRV_ERROR_INVALID_AUTHENTICATION_OPTION,
    SS_SQLSRV_ERROR_AE_QUERY_SQLTYPE_REQUIRED
};

extern ss_error SS_ERRORS[];

bool ss_error_handler( _Inout_ sqlsrv_context& ctx, _In_ unsigned int sqlsrv_error_code, _In_ bool warning TSRMLS_DC, _In_opt_ va_list* print_args );

// *** extension error functions ***
PHP_FUNCTION(sqlsrv_errors);

// convert from the default encoding specified by the "CharacterSet"
// connection option to UTF-16.  mbcs_len and utf16_len are sizes in
// bytes.  The return is the number of UTF-16 characters in the string
// returned in utf16_out_string.
unsigned int convert_string_from_default_encoding( _In_ unsigned int php_encoding, _In_reads_bytes_(mbcs_len) char const* mbcs_in_string,
                                                   _In_ unsigned int mbcs_len, _Out_writes_(utf16_len) __transfer(mbcs_in_string) wchar_t* utf16_out_string,
                                                   _In_ unsigned int utf16_len );
// create a wide char string from the passed in mbcs string.  NULL is returned if the string
// could not be created.  No error is posted by this function.  utf16_len is the number of
// wchar_t characters, not the number of bytes.
SQLWCHAR* utf16_string_from_mbcs_string( _In_ unsigned int php_encoding, _In_reads_bytes_(mbcs_len) const char* mbcs_string,
                                        _In_ unsigned int mbcs_len, _Out_ unsigned int* utf16_len );

// *** internal error macros and functions ***
bool handle_error( sqlsrv_context const* ctx, int log_subsystem, const char* function, 
                   sqlsrv_error const* ssphp TSRMLS_DC, ... );
void handle_warning( sqlsrv_context const* ctx, int log_subsystem, const char* function, 
                     sqlsrv_error const* ssphp TSRMLS_DC, ... );
void __cdecl sqlsrv_error_dtor( zend_resource *rsrc TSRMLS_DC );

// release current error lists and set to NULL
inline void reset_errors( TSRMLS_D )
{
    if( Z_TYPE( SQLSRV_G( errors )) != IS_ARRAY && Z_TYPE( SQLSRV_G( errors )) != IS_NULL ) {
        DIE( "sqlsrv_errors contains an invalid type" );
    }
    if( Z_TYPE( SQLSRV_G( warnings )) != IS_ARRAY && Z_TYPE( SQLSRV_G( warnings )) != IS_NULL ) {
        DIE( "sqlsrv_warnings contains an invalid type" );
    }

    if( Z_TYPE( SQLSRV_G( errors )) == IS_ARRAY ) {
        zend_hash_destroy( Z_ARRVAL( SQLSRV_G( errors )));
        FREE_HASHTABLE( Z_ARRVAL( SQLSRV_G( errors )));
    }
    if( Z_TYPE( SQLSRV_G( warnings )) == IS_ARRAY ) {
        zend_hash_destroy( Z_ARRVAL( SQLSRV_G( warnings )));
        FREE_HASHTABLE( Z_ARRVAL( SQLSRV_G( warnings )));
    }

    ZVAL_NULL( &SQLSRV_G( errors ));
    ZVAL_NULL( &SQLSRV_G( warnings ));
}

#define THROW_SS_ERROR( ctx, error_code, ... ) \
    (void)call_error_handler( ctx, error_code TSRMLS_CC, false /*warning*/, ## __VA_ARGS__ ); \
    throw ss::SSException();


class sqlsrv_context_auto_ptr : public sqlsrv_auto_ptr< sqlsrv_context, sqlsrv_context_auto_ptr > {

public:

    sqlsrv_context_auto_ptr( void ) :
        sqlsrv_auto_ptr<sqlsrv_context, sqlsrv_context_auto_ptr >( NULL )
    {
    }

    sqlsrv_context_auto_ptr( _Inout_opt_ const sqlsrv_context_auto_ptr& src ) :
        sqlsrv_auto_ptr< sqlsrv_context, sqlsrv_context_auto_ptr >( src )
    {
    }

    // free the original pointer and assign a new pointer. Use NULL to simply free the pointer.
    void reset( _In_opt_ sqlsrv_context* ptr = NULL )
    {
        if( _ptr ) {
            _ptr->~sqlsrv_context();
            sqlsrv_free( (void*) _ptr );
        }
        _ptr = ptr;
    }

    sqlsrv_context* operator=( _In_opt_ sqlsrv_context* ptr )
    {
        return sqlsrv_auto_ptr< sqlsrv_context, sqlsrv_context_auto_ptr >::operator=( ptr );
    }

    void operator=( _Inout_opt_ sqlsrv_context_auto_ptr& src )
    {
        sqlsrv_context* p = src.get();
        src.transferred();
        this->_ptr = p;
    }
};



//*********************************************************************************************************************************
// Logging
//*********************************************************************************************************************************
#define LOG_FUNCTION( function_name ) \
   const char* _FN_ = function_name; \
   SQLSRV_G( current_subsystem ) = current_log_subsystem; \
   LOG( SEV_NOTICE, "%1!s!: entering", _FN_ ); 

#define SET_FUNCTION_NAME( context ) \
{ \
    (context).set_func( _FN_ ); \
}

// logger for ss_sqlsrv called by the core layer when it wants to log something with the LOG macro
void ss_sqlsrv_log( _In_ unsigned int severity TSRMLS_DC, _In_opt_ const char* msg, _In_opt_ va_list* print_args );

// subsystems that may report log messages.  These may be used to filter which systems write to the log to prevent noise.
enum logging_subsystems {
    LOG_INIT = 0x01,
    LOG_CONN = 0x02,
    LOG_STMT = 0x04,
    LOG_UTIL = 0x08,
    LOG_ALL  = -1,
};

//*********************************************************************************************************************************
// Common function wrappers  
//      have to place this namespace before the utility functions
//      otherwise can't compile in Linux because 'ss' not defined
//*********************************************************************************************************************************
namespace ss {

    // an error which occurred in our SQLSRV driver
    struct SSException : public core::CoreException {

        SSException()
        {
        }
    };

    inline void zend_register_resource( _Inout_ zval& rsrc_result, _Inout_ void* rsrc_pointer, _In_ int rsrc_type, _In_opt_ const char* rsrc_name TSRMLS_DC)
    {
        int zr = (NULL != (Z_RES(rsrc_result) = ::zend_register_resource(rsrc_pointer, rsrc_type)) ? SUCCESS : FAILURE);
        CHECK_CUSTOM_ERROR(( zr == FAILURE ), reinterpret_cast<sqlsrv_context*>( rsrc_pointer ), SS_SQLSRV_ERROR_REGISTER_RESOURCE,
            rsrc_name ) {
            throw ss::SSException();
        }
        Z_TYPE_INFO(rsrc_result) = IS_RESOURCE_EX;
    }
} // namespace ss

//*********************************************************************************************************************************
// Utility Functions
//*********************************************************************************************************************************

// generic function used to validate parameters to a PHP function.
// Register an invalid parameter error and returns NULL when parameters don't match the spec given.
template <typename H>
inline H* process_params( INTERNAL_FUNCTION_PARAMETERS, _In_ char const* param_spec, _In_ const char* calling_func, _In_ size_t param_count, ... )
{
    SQLSRV_UNUSED( return_value );

    zval* rsrc;
    H* h;
    
    // reset the errors from the previous API call
    reset_errors( TSRMLS_C );

    if( ZEND_NUM_ARGS() > param_count + 1 ) {
        DIE( "Param count and argument count don't match." );
        return NULL;    // for static analysis tools
    }

    try {

        if( param_count > 6 ) {
            DIE( "Param count cannot exceed 6" );
            return NULL;    // for static analysis tools
        }

        void* arr[6];
        va_list vaList;
        va_start(vaList, param_count);  //set the pointer to first argument

        for(size_t i = 0; i < param_count; ++i) {
            
            arr[i] =  va_arg(vaList, void*);
        }

        va_end(vaList);

        int result = SUCCESS;
        
        // dummy context to pass to the error handler
        sqlsrv_context error_ctx( 0, ss_error_handler, NULL );
        error_ctx.set_func( calling_func );

        switch( param_count ) {

            case 0:
                result = zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, const_cast<char*>( param_spec ), &rsrc );
                break;

            case 1:
                result = zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, const_cast<char*>( param_spec ), &rsrc, arr[0] ); 
                break;

            case 2:
                result = zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, const_cast<char*>( param_spec ), &rsrc, arr[0], 
                                                arr[1] );  
                break;

            case 3:
                result = zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, const_cast<char*>( param_spec ), &rsrc, arr[0], 
                                                arr[1], arr[2] );  
                break;
            
            case 4:
                result = zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, const_cast<char*>( param_spec ), &rsrc, arr[0], 
                                                arr[1], arr[2], arr[3] ); 
                break;

            case 5:
                result = zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, const_cast<char*>( param_spec ), &rsrc, arr[0], 
                                                arr[1], arr[2], arr[3], arr[4] );  
                break;

            case 6:
                result = zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, const_cast<char*>( param_spec ), &rsrc, arr[0], 
                                                arr[1], arr[2], arr[3], arr[4], arr[5] );  
                break;

            default:
            {
                THROW_CORE_ERROR( error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, calling_func );
                break;
            }
        }

        CHECK_CUSTOM_ERROR(( result == FAILURE ), &error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, calling_func ) {
            
            throw ss::SSException();
        }

        // get the resource registered 
        h = static_cast<H*>( zend_fetch_resource(Z_RES_P(rsrc) TSRMLS_CC, H::resource_name, H::descriptor ));
        
        CHECK_CUSTOM_ERROR(( h == NULL ), &error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, calling_func ) {

            throw ss::SSException();
        }

        h->set_func( calling_func );
    }

    catch( core::CoreException& ) {
    
        return NULL;
    }
    catch ( ... ) {
    
        DIE( "%1!s!: Unknown exception caught in process_params.", calling_func );
    }

    return h;
}

#endif	/* PHP_SQLSRV_H */

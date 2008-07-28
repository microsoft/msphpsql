#ifndef PHP_SQLSRV_H
#define PHP_SQLSRV_H

//----------------------------------------------------------------------------------------------------------------------------------
// File: php_sqlsrv.h
//
// Copyright (c) Microsoft Corporation.  All rights reserved.
//
// Contents: Declarations for the SQL Server 2005 Driver for PHP 1.0
// 
// Comments: Also contains "internal" declarations shared across source files. 
//
// License: This software is released under the Microsoft Public License.  A copy of the license agreement 
//          may be found online at http://www.codeplex.com/SQL2K5PHP/license.
//----------------------------------------------------------------------------------------------------------------------------------

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
#endif

extern "C" {

#pragma warning(push)
#pragma warning( disable: 4005 4100 4127 4142 4244 4505 4530 )

#ifdef ZTS
#include "TSRM.h"
#endif

#if _MSC_VER >= 1400
// typedef and macro to prevent a conflict between php.h and ws2tcpip.h.  
// php.h defines this  constant as unsigned int which causes a compile error 
// in ws2tcpip.h.  Fortunately php.h allows an override by defining
// HAVE_SOCKLEN_T.  Since ws2tcpip.h isn't included until later, we define 
// socklen_t here and override the php.h version.
typedef int socklen_t;
#define HAVE_SOCKLEN_T
#endif

#include "php.h"
#include "php_globals.h"
#include "php_ini.h"
#include "ext/standard/php_standard.h"
#include "ext/standard/info.h"

#pragma warning(pop)

#if ZEND_DEBUG
// debug build causes warning C4505 to pop up from the Zend header files
#pragma warning( disable: 4505 )
#endif

}   // extern "C"

#if defined(OACR)
OACR_WARNING_POP
#endif

#include <sql.h>
#include <sqlext.h>

// PHP defines inline as __forceinline, which in debug mode causes a warning to be emitted when 
// we use std::copy, which causes compilation to fail since we compile with warnings as errors.
#if defined(ZEND_DEBUG) && defined(inline)
#undef inline
#endif

#include <algorithm>

#include <cassert>
#include <strsafe.h>

// borrowed from sqlncli.h to not require the SQL Server SDK to build
#define SQL_SS_LENGTH_UNLIMITED 0
#define SQL_SS_XML (-152)
#define SQL_SS_UDT (-151)
#define SQL_COPT_SS_TXN_ISOLATION 1227
#define SQL_TXN_SS_SNAPSHOT                 0x00000020L

// static assert for enforcing compile time conditions
template <bool b>
struct sqlsrv_static_assert;

template <>
struct sqlsrv_static_assert<true> { static const int value = 1; };

#define SQLSRV_STATIC_ASSERT( c )   (sqlsrv_static_assert<(c) != 0>() )


//**********************************************************************************************************************************
// Initialization Functions
//**********************************************************************************************************************************

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

// sqlsrv_context
// a sqlsrv_context is the agnostic way to represent a handle and its type.  This is used primarily when handling errors and
// warnings.  We pass this in and the error handling can use the handle and its type to get the diagnostic records from 
// SQLGetDiagRec.
struct sqlsrv_context {
    SQLHANDLE    handle;
    SQLSMALLINT  handle_type;
};

// variables set during initialization (move these to init.cpp)
extern zend_module_entry g_sqlsrv_module_entry;   // describes the extension to PHP
extern HMODULE g_sqlsrv_hmodule;                  // used for getting the version information
extern SQLHANDLE g_henv_ncp;                      // used to create connection handles with connection pooling off
extern SQLHANDLE g_henv_cp;                       // used to create connection handles with connection pooling on


//**********************************************************************************************************************************
// Connection
//**********************************************************************************************************************************

// *** connection resource structure ***
// this is the resource structure returned when a connection is made.
struct sqlsrv_conn {

    // instance variables
    sqlsrv_context ctx;             // see sqlsrv_context
    HashTable*     stmts;           // collection of statements allocated from this connection
    bool           in_transaction;  // flag set when inside a transaction and used for checking validity of tran API calls
    
    // static variables used in process_params
    static char* resource_name; // char because const char forces casting all over the place.  Just easier to leave it char here.
    static int descriptor;
};

// environment context used by sqlsrv_connect for when a connection error occurs.
struct sqlsrv_henv {
    sqlsrv_context ctx;
};

// *** connection functions ***
PHP_FUNCTION(sqlsrv_connect);
PHP_FUNCTION(sqlsrv_begin_transaction);
PHP_FUNCTION(sqlsrv_client_info);
PHP_FUNCTION(sqlsrv_close);
PHP_FUNCTION(sqlsrv_commit);
PHP_FUNCTION(sqlsrv_query);
PHP_FUNCTION(sqlsrv_prepare);
PHP_FUNCTION(sqlsrv_rollback);
PHP_FUNCTION(sqlsrv_server_info);

// resource destructor
void __cdecl sqlsrv_conn_dtor( zend_rsrc_list_entry *rsrc TSRMLS_DC );


//**********************************************************************************************************************************
// Statement
//**********************************************************************************************************************************

// holds the field names for reuse by sqlsrv_fetch_array/object as keys
struct sqlsrv_fetch_field {
    char* name;
    unsigned int len;
};

// *** statement resource structure *** 
struct sqlsrv_stmt {

    void new_result_set( bool release_datetime_buffers = true );

    sqlsrv_context ctx;
    sqlsrv_conn*   conn;
    zval* current_parameter;
    unsigned int current_parameter_read;
    bool executed;
    bool prepared;
    bool fetch_called;
    // field names for the current result set for use by sqlsrv_fetch_array/object as keys
    sqlsrv_fetch_field* fetch_fields;
    int fetch_fields_count;
    int last_field_index;
    bool past_fetch_end;
    bool past_next_result_end;
    zval* params_z;
    zval* param_datetime_buffers;
    void* param_buffer;
    int param_buffer_size;
    bool send_at_exec;
    int conn_index;
    zval* active_stream;


    // static variables used in process_params
    static char* resource_name; // char because const char forces casting all over the place in ODBC functions
    static int descriptor;
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
PHP_FUNCTION(sqlsrv_next_result);
PHP_FUNCTION(sqlsrv_num_fields);
PHP_FUNCTION(sqlsrv_rows_affected);
PHP_FUNCTION(sqlsrv_send_stream_data);

// resource destructor
void __cdecl sqlsrv_stmt_dtor( zend_rsrc_list_entry *rsrc TSRMLS_DC );

// "internal" statement functions used by sqlsrv_query and sqlsrv_close
bool sqlsrv_stmt_common_execute( sqlsrv_stmt* s, const SQLCHAR* sql_string, int sql_len, bool direct, const char* function TSRMLS_DC );
void sqlsrv_stmt_hash_dtor( void* stmt );
void free_odbc_resources( sqlsrv_stmt* stmt TSRMLS_DC );
void free_php_resources( zval* stmt_z TSRMLS_DC );
void remove_from_connection( sqlsrv_stmt* stmt TSRMLS_DC );

// *** constants ***

// types for conversions on output parameters (though they can be used for input parameters, they are ignored)
enum SQLSRV_PHPTYPE {
    MIN_SQLSRV_PHPTYPE = 1, // lowest value for a php type
    SQLSRV_PHPTYPE_NULL = 1,
    SQLSRV_PHPTYPE_INT,
    SQLSRV_PHPTYPE_FLOAT,
    SQLSRV_PHPTYPE_STRING,
    SQLSRV_PHPTYPE_DATETIME,
    SQLSRV_PHPTYPE_STREAM,
    MAX_SQLSRV_PHPTYPE,      // highest value for a php type
    SQLSRV_PHPTYPE_INVALID = MAX_SQLSRV_PHPTYPE     // used to see if a type is invalid
};

// encodings supported by this extension.  These basically translate into the use of SQL_C_CHAR or SQL_C_BINARY when getting
// information as a string or a stream.  
enum SQLSRV_ENCODING {
    SQLSRV_ENCODING_INVALID,        // unknown or invalid encoding.  Used to initialize variables.
    SQLSRV_ENCODING_BINARY,         // use SQL_C_BINARY when using SQLGetData
    SQLSRV_ENCODING_CHAR,           // use SQL_C_CHAR when using SQLGetData
};

// the array keys used when returning a row via sqlsrv_fetch_array and sqlsrv_fetch_object.
enum SQLSRV_FETCH_TYPE {
    MIN_SQLSRV_FETCH = 1,        // lowest value for fetch type
    SQLSRV_FETCH_NUMERIC = 1,   // return an array with only numeric indices
    SQLSRV_FETCH_ASSOC = 2,     // return an array with keys made from the field names
    SQLSRV_FETCH_BOTH = 3,       // return an array indexed with both numbers and keys
    MAX_SQLSRV_FETCH = 3,       // highest value for fetch type
};

// buffer size of a sql state (including the null character)
const int SQL_SQLSTATE_BUFSIZE = SQL_SQLSTATE_SIZE + 1;


// SQL types for parameters encoded in an integer.  The type corresponds to the SQL type ODBC constants.
// The size is the column size or precision, and scale is the decimal digits for precise numeric types.

union sqlsrv_sqltype {
    struct typeinfo_t {
        int type:9;
        int size:14;
        int scale:8;
    } typeinfo;

    long value;
};


// SQLSRV PHP types (as opposed to the Zend PHP type constants).  Contains the type (see SQLSRV_PHPTYPE)
// and the encoding for strings and streams (see SQLSRV_ENCODING) 

union sqlsrv_phptype {

    struct typeinfo_t {
        unsigned type:8;
        int encoding:16;
    } typeinfo;

    long value;
};

// *** variables ***


//**********************************************************************************************************************************
// Type Functions
//**********************************************************************************************************************************

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


//**********************************************************************************************************************************
// Stream
//**********************************************************************************************************************************

// stream instance variables
struct sqlsrv_stream {
    sqlsrv_stmt* stmt;
    int stmt_index;
    SQLUSMALLINT field;
    SQLSMALLINT sql_type;
    int encoding;
};

// resource constants used when registering the stream type with PHP
#define SQLSRV_STREAM_WRAPPER "sqlsrv"
#define SQLSRV_STREAM         "sqlsrv_stream"

extern php_stream_wrapper g_sqlsrv_stream_wrapper;


//**********************************************************************************************************************************
// Global variables
//**********************************************************************************************************************************

extern "C" {

// request level variables
ZEND_BEGIN_MODULE_GLOBALS(sqlsrv)

// error context for the henv when a connection fails
sqlsrv_henv* henv_context;
// global objects for errors and warnings.  These are returned by sqlsrv_errors.
zval* errors;
zval* warnings;
// flags for error handling and logging (set via sqlsrv_configure or php.ini)
unsigned int log_severity;
unsigned int log_subsystems;
zend_bool warnings_return_as_errors;
// special list of warnings to ignore even if warnings are treated as errors
HashTable* warnings_to_ignore;

ZEND_END_MODULE_GLOBALS(sqlsrv)

ZEND_EXTERN_MODULE_GLOBALS(sqlsrv);

}

// macros used to access the global variables.  Use these to make global variable access agnostic to threads
#ifdef ZTS
#define SQLSRV_G(v) TSRMG(sqlsrv_globals_id, zend_sqlsrv_globals *, v)
#else
#define SQLSRV_G(v) sqlsrv_globals.v
#endif

// INI settings and constants
// (these are defined as macros to allow concatenation as we do below)
#define INI_WARNINGS_RETURN_AS_ERRORS   "WarningsReturnAsErrors"
#define INI_LOG_SEVERITY                "LogSeverity"
#define INI_LOG_SUBSYSTEMS              "LogSubsystems"
#define INI_PREFIX                      "sqlsrv."

PHP_INI_BEGIN()
    STD_PHP_INI_BOOLEAN( INI_PREFIX INI_WARNINGS_RETURN_AS_ERRORS , "1", PHP_INI_ALL, OnUpdateBool, warnings_return_as_errors,
                         zend_sqlsrv_globals, sqlsrv_globals )
    STD_PHP_INI_ENTRY( INI_PREFIX INI_LOG_SEVERITY, "0", PHP_INI_ALL, OnUpdateLong, log_severity, zend_sqlsrv_globals, sqlsrv_globals )
    STD_PHP_INI_ENTRY( INI_PREFIX INI_LOG_SUBSYSTEMS, "0", PHP_INI_ALL, OnUpdateLong, log_subsystems, zend_sqlsrv_globals, sqlsrv_globals )
PHP_INI_END()



//**********************************************************************************************************************************
// Logging
//**********************************************************************************************************************************
// a simple wrapper around a PHP error logging function.
void write_to_log( unsigned int severity, unsigned int subsystem TSRMLS_DC, const char* msg, ... );
// a macro to make it convenient to use the function.
#define LOG( severity, subsystem, msg, ...)    write_to_log( severity, subsystem TSRMLS_CC, msg, __VA_ARGS__ )

// subsystems that may report log messages.  These may be used to filter which systems write to the log to prevent noise.
enum logging_subsystems {
    LOG_INIT = 0x01,
    LOG_CONN = 0x02,
    LOG_STMT = 0x04,
    LOG_UTIL = 0x08,
    LOG_ALL  = -1,
};
// mask for filtering which severities are written to the log
enum logging_severity {
    SEV_ERROR = 0x01,
    SEV_WARNING = 0x02,
    SEV_NOTICE = 0x04,
    SEV_ALL = -1,
};

// a macro to declare a function name variable.  This defines a variable, _FN_, for a function's scope 
// that can be used throughout the function to reference it's name.  We don't use the predefined __FUNCTION__
// because Zend's PHP_FUNCTION macro mangles the name a bit.  The var _FN_LEN_ is also defined for when the
// length is needed also.
#define DECL_FUNC_NAME( name )             \
    const char* _FN_ = name;               \
    //    const int _FN_LEN_ = sizeof( name );

// a macro to log entering a function.  used at the top of each API function.
#define LOG_FUNCTION  LOG( SEV_NOTICE, LOG_STMT, "%1!s!: entering", _FN_ );


//**********************************************************************************************************************************
// Configuration
//**********************************************************************************************************************************
// these functions set and retrieve configuration settings.  Configuration settings defined are:
//    WarningsReturnAsErrors - treat all ODBC warnings as errors and return false from sqlsrv APIs.
//    LogSeverity - combination of severity of messages to log (see Logging)
//    LogSubsystems - subsystems within sqlsrv to log messages (see Logging)

PHP_FUNCTION(sqlsrv_configure);
PHP_FUNCTION(sqlsrv_get_config);


//**********************************************************************************************************************************
// Errors
//**********************************************************************************************************************************

// *** PHP specific errors ***
// sqlsrv errors are held in a structure of this type used by handle_errors_and_warnings 
// format is a flag that tells handle_errors_and_warnings if there are parameters to use with FormatMessage
// into the error message before returning it.
struct sqlsrv_error {
    char const* sqlstate;
    char const* native_message;
    int native_code;
    bool format;
};

// defintions for PHP specific errors returned by sqlsrv
extern sqlsrv_error SQLSRV_ERROR_INVALID_OPTION[];
extern sqlsrv_error SQLSRV_ERROR_FILE_VERSION[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_PARAM_TYPE[];
extern sqlsrv_error SQLSRV_ERROR_CONNECT_BRACES_NOT_ESCAPED[];
extern sqlsrv_error SQLSRV_ERROR_NO_DATA[];
extern sqlsrv_error SQLSRV_ERROR_STREAMABLE_TYPES_ONLY[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_OUTPUT_PARAM_TYPE[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_CONNECTION_KEY[];
extern sqlsrv_error SQLSRV_ERROR_VAR_REQUIRED[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_FETCH_TYPE[];
extern sqlsrv_error SQLSRV_ERROR_STATEMENT_NOT_EXECUTED[];
extern sqlsrv_error SQLSRV_ERROR_ALREADY_IN_TXN[];
extern sqlsrv_error SQLSRV_ERROR_NOT_IN_TXN[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_PARAMETER_DIRECTION[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_PARAMETER_SQLTYPE[];
extern sqlsrv_error SQLSRV_ERROR_FETCH_NOT_CALLED[];
extern sqlsrv_error SQLSRV_ERROR_FIELD_INDEX_ERROR[];
extern sqlsrv_error SQLSRV_ERROR_DATETIME_CONVERSION_FAILED[];
extern sqlsrv_error SQLSRV_ERROR_SERVER_INFO[];
extern sqlsrv_error SQLSRV_ERROR_FETCH_PAST_END[];
extern sqlsrv_error SQLSRV_ERROR_STATEMENT_NOT_PREPARED[];
extern sqlsrv_error SQLSRV_ERROR_ZEND_HASH[];
extern sqlsrv_error SQLSRV_ERROR_ZEND_STREAM[];
extern sqlsrv_error SQLSRV_ERROR_NEXT_RESULT_PAST_END[];
extern sqlsrv_error SQLSRV_ERROR_STREAM_CREATE[];
extern sqlsrv_error SQLSRV_ERROR_NO_FIELDS[];
extern sqlsrv_error SQLSRV_ERROR_ZEND_BAD_CLASS[];
extern sqlsrv_error SQLSRV_ERROR_ZEND_OBJECT_FAILED[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_PARAMETER_PRECISION[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_OPTION_KEY[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_OPTION_VALUE[];
extern sqlsrv_error SQLSRV_ERROR_OUTPUT_PARAM_TYPE_DOESNT_MATCH[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_TYPE[];
extern sqlsrv_error SQLSRV_ERROR_COMMIT_FAILED[];
extern sqlsrv_error SQLSRV_ERROR_ROLLBACK_FAILED[];
extern sqlsrv_error SQLSRV_ERROR_AUTO_COMMIT_STILL_OFF[];
extern sqlsrv_error SQLSRV_ERROR_REGISTER_RESOURCE[];

// definitions for PHP specific warnings returned by sqlsrv
extern sqlsrv_error SQLSRV_WARNING_FIELD_NAME_EMPTY[];

enum error_handling_flags {
    SQLSRV_ERR_ERRORS,
    SQLSRV_ERR_WARNINGS,
    SQLSRV_ERR_ALL
};

// *** extension error functions ***
PHP_FUNCTION(sqlsrv_errors);
PHP_FUNCTION(sqlsrv_warnings);

// *** internal error macros and functions ***
bool handle_error( sqlsrv_context const* ctx, int log_subsystem, const char* function, 
                   sqlsrv_error const* ssphp TSRMLS_DC, ... );
void handle_warning( sqlsrv_context const* ctx, int log_subsystem, const char* function, 
                     sqlsrv_error const* ssphp TSRMLS_DC, ... );
void __cdecl sqlsrv_error_dtor( zend_rsrc_list_entry *rsrc TSRMLS_DC );

// PHP equivalent of ASSERT.  C asserts cause a dialog to show and halt the process which
// we don't want on a web server
#define DIE( msg, ...)  php_error( E_ERROR, msg, __VA_ARGS__ );

bool check_sql_error_ex( bool condition, sqlsrv_context const* ctx, int log_subsystem,  const char* function, sqlsrv_error const* ssphp TSRMLS_DC, ... );

// this is a special function for sqlsrv internal warnings.  It emits an internal warning and treats
// it as an error if the WarningsReturnAsErrors flag is set.
bool check_sqlsrv_warnings( bool condition, sqlsrv_context const* ctx, int log_subsystem,  const char* function, sqlsrv_error const* ssphp TSRMLS_DC, ... );

// *** These functions are simplified if statements that take boilerplate code down to a single line to avoid distractions in the code.
// *** If you need to actually send variadic arguments for printing, then you'll have to call check_sql_error_ex directly.
// *** these macros rely on the variable current_log_subsystem.  This should be defined in every subsystem to
// *** one of the constants (LOG_CONN, LOG_STMT, etc.).

// check a generic condition and execute error handling code after posting the error to the error queue 
#define CHECK_SQL_ERROR_EX( condition, resource, function, ssphp, ... )                                           \
    {                                                                                                             \
        __pragma( warning( push ))                                                                                \
        __pragma( warning( disable: 4714 ))                                                                       \
        bool ignored = check_sql_error_ex( (condition), &(resource)->ctx, current_log_subsystem,                  \
                                           function, ssphp TSRMLS_CC );                                           \
        __pragma( warning( pop ))                                                                                 \
        if( !ignored ) {                                                                                          \
            __VA_ARGS__;                                                                                          \
        }                                                                                                         \
    }

// chech the SQLRETURN code after an ODBC call and post any errors to the error queue and then execute error handling code
#define CHECK_SQL_ERROR( result, resource, function, ssphp, ... )               \
    CHECK_SQL_ERROR_EX( (!SQL_SUCCEEDED( result )) ||                           \
                        ((SQLSRV_G( warnings_return_as_errors )) &&             \
                         (result == SQL_SUCCESS_WITH_INFO )),                   \
                        resource, function, ssphp, __VA_ARGS__ )
    
// check for warnings after an ODBC call.  This simply logs the warnings that may be retrieved later.
#define CHECK_SQL_WARNING( result, resource, function, ssphp )                                  \
    if( result == SQL_SUCCESS_WITH_INFO && SQLSRV_G( warnings_return_as_errors ) == false ) {   \
        handle_warning( &resource->ctx, current_log_subsystem, function, ssphp TSRMLS_CC );     \
    }

// equivalent macro that checks the result of a Zend API and fails gracefully if it failed.
#define CHECK_ZEND_ERROR( result, ssphp, ... )                                          \
    if( result == FAILURE ) {                                                           \
        __pragma( warning( push ))                                                      \
        __pragma( warning( disable: 4714 ))                                             \
        check_sql_error_ex( true, NULL, current_log_subsystem, _FN_, ssphp TSRMLS_CC ); \
        __pragma( warning( pop ))                                                       \
        __VA_ARGS__;                                                                    \
    }                                                                                   \

#define CHECK_SQLSRV_WARNING( condition, ssphp, ... )                                                          \
    {                                                                                                          \
        __pragma( warning( push ))                                                                             \
        __pragma( warning( disable: 4714 ))                                                                    \
        bool ignored = check_sqlsrv_warnings( condition, NULL, current_log_subsystem, _FN_, ssphp TSRMLS_CC ); \
        __pragma( warning( pop ))                                                                              \
        if( !ignored ) {                                                                                       \
            __VA_ARGS__;                                                                                       \
        }                                                                                                      \
    }

// release current error lists and set to NULL
inline void reset_errors( TSRMLS_D )
{
    if( Z_TYPE_P( SQLSRV_G( errors )) != IS_ARRAY && Z_TYPE_P( SQLSRV_G( errors )) != IS_NULL ) {
        DIE( "sqlsrv_errors contains an invalid type" );
    }
    if( Z_TYPE_P( SQLSRV_G( warnings )) != IS_ARRAY && Z_TYPE_P( SQLSRV_G( warnings )) != IS_NULL ) {
        DIE( "sqlsrv_warnings contains an invalid type" );
    }

    if( Z_TYPE_P( SQLSRV_G( errors )) == IS_ARRAY ) {
        zend_hash_destroy( Z_ARRVAL_P( SQLSRV_G( errors )));
        FREE_HASHTABLE( Z_ARRVAL_P( SQLSRV_G( errors )));
    }
    if( Z_TYPE_P( SQLSRV_G( warnings )) == IS_ARRAY ) {
        zend_hash_destroy( Z_ARRVAL_P( SQLSRV_G( warnings )));
        FREE_HASHTABLE( Z_ARRVAL_P( SQLSRV_G( warnings )));
    }

    ZVAL_NULL( SQLSRV_G( errors ));
    ZVAL_NULL( SQLSRV_G( warnings ));
}


//**********************************************************************************************************************************
// Utility Functions
//**********************************************************************************************************************************
// Simple macro to alleviate unused variable warnings.  These are optimized out by the compiler.
// We use this since the unused variables are buried in the PHP_FUNCTION macro.
#define SQLSRV_UNUSED( var )   var = var

// do a heap check in debug mode, but only print errors, not all of the allocations
#define MEMCHECK_SILENT 1


// check to see if the sqlstate is 01004, truncated field retrieved.  Used for retrieving large fields.
inline bool is_truncated_warning( SQLCHAR* state )
{
#if defined(ZEND_DEBUG)
    if( state == NULL || strlen( reinterpret_cast<char*>( state )) != 5 ) { DIE( "Incorrect SQLSTATE given to is_truncated_warning" ); }
#endif
    return (state[0] == '0' && state[1] == '1' && state[2] == '0' && state [3] == '0' && state [4] == '4');
}


// generic functions used to validate parameters to a PHP function.
// Register an invalid parameter error and returns NULL when parameters don't match the spec given. 
// Each function is nearly identical, except the number of parameters each accepts.  
// We do this since template functions can't be variadic.

template <typename H>
inline H* process_params( INTERNAL_FUNCTION_PARAMETERS, int log_subsystem, char const* function, char const* param_spec )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );
    SQLSRV_UNUSED( return_value );

    zval* rsrc;
    H* h;
    
    // test the integrity of the Zend heap in debug mode
    full_mem_check(MEMCHECK_SILENT);
    // reset the errors from the previous API call
    reset_errors( TSRMLS_C );

    if( ZEND_NUM_ARGS() > 1 ) {
        DIE( "Called no parameter function with parameters." );
    }

    // parse the parameters
    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, const_cast<char*>( param_spec ), &rsrc ) == FAILURE ) {
        handle_error( NULL, log_subsystem, function, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, function );
        return NULL;
    }
    
    // get the resource registered 
    h = static_cast<H*>( zend_fetch_resource( &rsrc TSRMLS_CC, -1, H::resource_name, NULL, 1, H::descriptor ));
    if( h == NULL ) {
        handle_error( NULL, log_subsystem, function, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, function );
        return NULL;
    }

    return h;
}

template <typename H>
inline H* process_params( INTERNAL_FUNCTION_PARAMETERS, int log_subsystem, char const* function, char const* param_spec, void* p1 )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );
    SQLSRV_UNUSED( return_value );

    zval* rsrc;
    H* h;
    
    // test the integrity of the Zend heap.
    full_mem_check(MEMCHECK_SILENT);
    // reset the errors from the previous API call
    reset_errors( TSRMLS_C );

    if( ZEND_NUM_ARGS() > 2 ) {
        DIE( "Called 1 parameter function with more than 1 parameter." );
    }

    // parse the parameters
    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, const_cast<char*>( param_spec ), &rsrc, p1 ) == FAILURE ) {
        handle_error( NULL, log_subsystem, function, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, function );
        return NULL;
    }
    
    // get the resource registered 
    h = static_cast<H*>( zend_fetch_resource( &rsrc TSRMLS_CC, -1, H::resource_name, NULL, 1, H::descriptor ));
    if( h == NULL ) {
        handle_error( NULL, log_subsystem, function, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, function );
        return NULL;
    }

    return h;
}

template <typename H>
inline H* process_params( INTERNAL_FUNCTION_PARAMETERS, int log_subsystem, char const* function, char const* param_spec, void* p1, void* p2 )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );
    SQLSRV_UNUSED( return_value );

    zval* rsrc;
    H* h;
    
    // test the integrity of the Zend heap in debug mode
    full_mem_check(MEMCHECK_SILENT);
    // reset the errors from the previous API call
    reset_errors( TSRMLS_C );

    if( ZEND_NUM_ARGS() > 3 ) {
        DIE( "Called 2 parameter function with more than 2 parameters." );
    }

    // parse the parameters
    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, const_cast<char*>( param_spec ), &rsrc, p1, p2 ) == FAILURE ) {
        handle_error( NULL, log_subsystem, function, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, function );
        return NULL;
    }
    
    // get the resource registered 
    h = static_cast<H*>( zend_fetch_resource( &rsrc TSRMLS_CC, -1, H::resource_name, NULL, 1, H::descriptor ));
    if( h == NULL ) {
        handle_error( NULL, log_subsystem, function, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, function );
        return NULL;
    }

    return h;
}

template <typename H>
inline H* process_params( INTERNAL_FUNCTION_PARAMETERS, int log_subsystem, char const* function, char const* param_spec, void* p1, void* p2, void* p3 )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );
    SQLSRV_UNUSED( return_value );

    zval* rsrc;
    H* h;
    
    // test the integrity of the Zend heap in debug mode
    full_mem_check(MEMCHECK_SILENT);
    // reset the errors from the previous API call
    reset_errors( TSRMLS_C );

    if( ZEND_NUM_ARGS() > 4 ) {
        DIE( "Called 3 parameter function with more than 3 parameters." );
    }

    // parse the parameters
    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, const_cast<char*>( param_spec ), &rsrc, p1, p2, p3 ) == FAILURE ) {
        handle_error( NULL, log_subsystem, function, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, function );
        return NULL;
    }
    
    // get the resource registered 
    h = static_cast<H*>( zend_fetch_resource( &rsrc TSRMLS_CC, -1, H::resource_name, NULL, 1, H::descriptor ));
    if( h == NULL ) {
        handle_error( NULL, log_subsystem, function, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, function );
        return NULL;
    }

    return h;
}


template <typename H>
inline H* process_params( INTERNAL_FUNCTION_PARAMETERS, int log_subsystem, char const* function, char const* param_spec, void* p1, void* p2, void* p3, void* p4 )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );
    SQLSRV_UNUSED( return_value );

    zval* rsrc;
    H* h;
    
    // test the integrity of the Zend heap in debug mode
    full_mem_check(MEMCHECK_SILENT);
    // reset the errors from the previous API call
    reset_errors( TSRMLS_C );

    if( ZEND_NUM_ARGS() > 5 ) {
        DIE( "Called 4 parameter function with more than 4 parameters." );
    }

    // parse the parameters
    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, const_cast<char*>( param_spec ), &rsrc, p1, p2, p3, p4 ) == FAILURE ) {
        handle_error( NULL, log_subsystem, function, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, function );
        return NULL;
    }
    
    // get the resource registered 
    h = static_cast<H*>( zend_fetch_resource( &rsrc TSRMLS_CC, -1, H::resource_name, NULL, 1, H::descriptor ));
    if( h == NULL ) {
        handle_error( NULL, log_subsystem, function, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, function );
        return NULL;
    }

    return h;
}

template <typename H>
inline H* process_params( INTERNAL_FUNCTION_PARAMETERS, int log_subsystem, char const* function, char const* param_spec, void* p1, void* p2, void* p3, void* p4, void* p5 )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );
    SQLSRV_UNUSED( return_value );

    zval* rsrc;
    H* h;
    
    // test the integrity of the Zend heap in debug mode
    full_mem_check(MEMCHECK_SILENT);
    // reset the errors from the previous API call
    reset_errors( TSRMLS_C );

    if( ZEND_NUM_ARGS() > 6 ) {
        DIE( "Called 5 parameter function with more than 5 parameters." );
    }

    // parse the parameters
    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, const_cast<char*>( param_spec ), &rsrc, p1, p2, p3, p4, p5 ) == FAILURE ) {
        handle_error( NULL, log_subsystem, function, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, function );
        return NULL;
    }
    
    // get the resource registered 
    h = static_cast<H*>( zend_fetch_resource( &rsrc TSRMLS_CC, -1, H::resource_name, NULL, 1, H::descriptor ));
    if( h == NULL ) {
        handle_error( NULL, log_subsystem, function, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, function );
        return NULL;
    }

    return h;
}


// trait class that allows us to assign const types to an auto_ptr
template <typename T>
struct remove_const {
    typedef T type;
};

template <typename T>
struct remove_const<const T*> {
    typedef T* type;
};


// base class for auto_ptrs that we define below.  It provides common operators and functions 
// used by all the classes.  
template <typename T, typename Subclass>
class sqlsrv_auto_ptr {

public:

    sqlsrv_auto_ptr( void ) : _ptr( NULL )
    {
    }

    ~sqlsrv_auto_ptr( void )
    {
        static_cast<Subclass*>(this)->reset( NULL );
    }

    // call when ownership is transferred
    void transferred( void )
    {
        _ptr = NULL;
    }

    // explicit function to get the pointer.
    T* get( void ) const
    {
        return _ptr;
    }

    // cast operator to allow auto_ptr to be used where a normal const * can be.
    operator const T* () const
    {
        return _ptr;
    }

    // cast operator to allow auto_ptr to be used where a normal pointer can be.
    operator typename remove_const<T*>::type () const
    {
        return _ptr;
    }

    // there are a number of places where we allocate a block intended to be accessed as
    // an array of elements, so this operator allows us to treat the memory as such.
    T& operator[]( int index ) const
    {
        return _ptr[ index ];
    }

    // there are a number of places where we allocate a block intended to be accessed as
    // an array of elements, so this operator allows us to treat the memory as such.
    T& operator[]( unsigned int index ) const
    {
        return _ptr[ index ];
    }

    // there are a number of places where we allocate a block intended to be accessed as
    // an array of elements, so this operator allows us to treat the memory as such.
    T& operator[]( unsigned short index ) const
    {
        return _ptr[ index ];
    }

    // access elements of a structure through the auto ptr
    T* const operator->( void ) const
    {
        return _ptr;
    }

    // cast to T** used by many functions
    T** operator&()
    {
        return &_ptr;
    }

    // value from reference operator (i.e., i = *(&i); or *i = blah;)
    T& operator*()
    {
        return *_ptr;
    }

protected:

    sqlsrv_auto_ptr( T* ptr ) :
        _ptr( ptr ) 
    {
    }

    sqlsrv_auto_ptr( sqlsrv_auto_ptr const& src )
    {
        if( _ptr ) {
            static_cast<Subclass*>(this)->reset( src._ptr );
        }
        src.transferred();
    }

    sqlsrv_auto_ptr( typename Subclass const& src )
    {
        if( _ptr ) {
            static_cast<Subclass*>( this )->reset( src._ptr );
        }
        src.transferred();
    }

    // assign a new pointer to the auto_ptr.  It will free the previous memory block
    // because ownership is deemed finished.
    T* operator=( T* ptr )
    {
        static_cast<Subclass*>( this )->reset( ptr );

        return ptr;
    }

    T* _ptr;
    
};

// an auto_ptr for emalloc/efree.  When allocating a chunk of memory using emalloc, wrap that pointer
// in a variable of emalloc_auto_ptr.  emalloc_auto_ptr will "own" that block and assure that it is
// freed until the variable is destroyed (out of scope) or ownership is transferred using the function
// "transferred".
template <typename T>
class emalloc_auto_ptr : public sqlsrv_auto_ptr<T, emalloc_auto_ptr<T> > {

public:

    emalloc_auto_ptr( void ) :
        sqlsrv_auto_ptr<T, emalloc_auto_ptr<T> >( NULL )
    {
    }

    emalloc_auto_ptr( const emalloc_auto_ptr& src )
    {
        sqlsrv_auto_ptr<T, emalloc_auto_ptr<T> >::sqlsrv_auto_ptr( src );
    }

    // free the original pointer and assign a new pointer. Use NULL to simply free the pointer.
    void reset( T* ptr = NULL )
    {
        if( _ptr )
            efree( (void*) _ptr );
        _ptr = ptr;
    }

    T* operator=( T* ptr )
    {
        return sqlsrv_auto_ptr<T, emalloc_auto_ptr<T> >::operator=( ptr );
    }
};


// auto ptr for Zend hash tables.  Used to clean up a hash table allocated when 
// something caused an early exit from the function.  This is used when the hash_table is
// allocated in a zval that itself can't be released.  Otherwise, use the zval_auto_ptr.

class hash_auto_ptr : public sqlsrv_auto_ptr<HashTable, hash_auto_ptr> {

public:

    hash_auto_ptr( void ) :
        sqlsrv_auto_ptr<HashTable, hash_auto_ptr>( NULL )
    {
    }

    // free the original pointer and assign a new pointer. Use NULL to simply free the pointer.
    void reset( HashTable* ptr = NULL )
    {
        if( _ptr ) {
            zend_hash_destroy( _ptr );
            FREE_HASHTABLE( _ptr );
        }
        _ptr = ptr;
    }

    HashTable* operator=( HashTable* ptr )
    {
        return sqlsrv_auto_ptr<HashTable, hash_auto_ptr>::operator=( ptr );
    }

private:

    hash_auto_ptr( HashTable const& hash );

    hash_auto_ptr( hash_auto_ptr const& hash );
};


// an auto_ptr for zvals.  When allocating a zval, wrap that pointer in a variable of zval_auto_ptr.  
// zval_auto_ptr will "own" that zval and assure that it is freed when the variable is destroyed 
// (out of scope) or ownership is transferred using the function "transferred".

class zval_auto_ptr : public sqlsrv_auto_ptr<zval, zval_auto_ptr> {

public:

    zval_auto_ptr( void )
    {
    }

    // free the original pointer and assign a new pointer. Use NULL to simply free the pointer.
    void reset( zval* ptr = NULL )
    {
        if( _ptr )
            zval_ptr_dtor( &_ptr );
        _ptr = ptr;
    }

    zval* operator=( zval* ptr )
    {
        return sqlsrv_auto_ptr<zval, zval_auto_ptr>::operator=( ptr );
    }

private:

    zval_auto_ptr( const zval_auto_ptr& src );
};

#endif	/* PHP_SQLSRV_H */


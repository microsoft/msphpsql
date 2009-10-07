#ifndef PHP_SQLSRV_H
#define PHP_SQLSRV_H

//----------------------------------------------------------------------------------------------------------------------------------
// File: php_sqlsrv.h
//
// Copyright (c) Microsoft Corporation.  All rights reserved.
//
// Contents: Declarations for the extension
// 
// Comments: Also contains "internal" declarations shared across source files. 
//
// License: This software is released under the Microsoft Public License.  A copy of the license agreement 
//          may be found online at http://www.codeplex.com/SQLSRVPHP/license.
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
OACR_WARNING_DISABLE( REALLOCLEAK, "Third party code." )
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

#if PHP_MAJOR_VERSION > 5 || PHP_MAJOR_VERSION < 5 || ( PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION < 2 ) || ( PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 3 )
#error Trying to compile "Microsoft SQL Server Driver for PHP" with an unsupported version of PHP
#endif

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

#if !defined(WC_ERR_INVALID_CHARS)
// imported from winnls.h as it isn't included by 5.3.0
#define WC_ERR_INVALID_CHARS      0x00000080  // error for invalid chars
#endif

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
#define SQL_SS_TIME2                        (-154)
#define SQL_SS_TIMESTAMPOFFSET              (-155)



// static assert for enforcing compile time conditions
template <bool b>
struct sqlsrv_static_assert;

template <>
struct sqlsrv_static_assert<true> { static const int value = 1; };

#define SQLSRV_STATIC_ASSERT( c )   (sqlsrv_static_assert<(c) != 0>() )

#if PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION <= 2
#define Z_SET_ISREF_P( pzval )     ((pzval)->is_ref = 1)
#define Z_SET_ISREF_PP( ppzval )   Z_SET_ISREF_P(*(ppzval))
#define Z_REFCOUNT_P( pzval )      ((pzval)->refcount)
#endif

//**********************************************************************************************************************************
// Constants and Types for sqlsrv data types and encodings
//**********************************************************************************************************************************

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
    SQLSRV_ENCODING_SYSTEM = SQLSRV_ENCODING_CHAR,         
    SQLSRV_ENCODING_DEFAULT,        // use what is the connection's default
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
        unsigned encoding:16;
    } typeinfo;

    long value;
};


// supported server versions (determined at connection time)
enum SERVER_VERSION {
    SERVER_VERSION_UNKNOWN = -1,
    SERVER_VERSION_2000 = 8,
    SERVER_VERSION_2005,
    SERVER_VERSION_2008, // use this for anything 2008 or later
};


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

    sqlsrv_context( SQLSMALLINT type ) :
        handle( SQL_NULL_HANDLE ),
        handle_type( type )
    {
    }

    sqlsrv_context( SQLHANDLE h, SQLSMALLINT t ) :
        handle( h ),
        handle_type( t )
    {
    }
};

// variables set during initialization (move these to init.cpp)
extern zend_module_entry g_sqlsrv_module_entry;   // describes the extension to PHP
extern HMODULE g_sqlsrv_hmodule;                  // used for getting the version information
extern SQLHANDLE g_henv_ncp;                      // used to create connection handles with connection pooling off
extern SQLHANDLE g_henv_cp;                       // used to create connection handles with connection pooling on
extern OSVERSIONINFO g_osversion;                 // used to determine which OS we're running in

const int SQLSRV_OS_VISTA_OR_LATER = 6;           // major version for Vista

// maps an IANA encoding to a code page
struct sqlsrv_encoding {

    const char* iana;
    unsigned int iana_len;
    unsigned int code_page;
    bool not_for_connection;

    sqlsrv_encoding( const char* iana, unsigned int code_page, bool not_for_conn = false ):
        iana( iana ), iana_len( strlen( iana )), code_page( code_page ), not_for_connection( not_for_conn )
    {
    }
};

//**********************************************************************************************************************************
// Connection
//**********************************************************************************************************************************

// *** connection resource structure ***
// this is the resource structure returned when a connection is made.
struct sqlsrv_conn {

    // instance variables
    sqlsrv_context ctx;                // see sqlsrv_context
    HashTable*     stmts;              // collection of statements allocated from this connection
    bool           in_transaction;     // flag set when inside a transaction and used for checking validity of tran API calls
    bool           date_as_string;     // date/datetime/datetimeoffset/etc. fields return as strings rather than PHP DateTime objects
    unsigned int   default_encoding;   // encoding set with the "CharSet" connection option
    SERVER_VERSION server_version;     // version of the server that we're connected to

    // initialize with default values
    sqlsrv_conn( void ) :
        ctx( SQL_HANDLE_DBC ),
        stmts( NULL ),
        in_transaction( false ),
        default_encoding( SQLSRV_ENCODING_CHAR ),
        date_as_string( false )
    {
    }

    // static variables used in process_params
    static char* resource_name; // char because const char forces casting all over the place.  Just easier to leave it char here.
    static int descriptor;
};

// environment context used by sqlsrv_connect for when a connection error occurs.
struct sqlsrv_henv {

    sqlsrv_context ctx;

    sqlsrv_henv( SQLHANDLE handle ) :
        ctx( handle, SQL_HANDLE_ENV )
    {
    }
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

// holds the stream param and the encoding that it was assigned
struct sqlsrv_stream_encoding {
    zval* stream_z;
    unsigned int encoding;

    sqlsrv_stream_encoding( zval* str_z, unsigned int enc ) :
        stream_z( str_z ), encoding( enc )
    {
    }
};

// holds the string output parameter information
struct sqlsrv_output_string {
    zval* string_z;
    unsigned int encoding;
    int param_num;  // used to index into the ind_or_len of the statement
    SQLLEN original_buffer_len; // used to make sure the returned length didn't overflow the buffer

    sqlsrv_output_string( zval* str_z, unsigned int enc, int num, SQLUINTEGER buffer_len ) :
        string_z( str_z ), encoding( enc ), param_num( num ), original_buffer_len( buffer_len )
    {
    }

};

// *** statement resource structure *** 
struct sqlsrv_stmt {

    void free_param_data( void );
    void new_result_set( void );

    sqlsrv_context ctx;                   // context that holds the statement handle
    sqlsrv_conn*   conn;                  // connection that created this statement
    zval* current_stream;                 // current stream sending data to the server as an input parameter
    unsigned int current_stream_read;     // if we read an empty PHP stream, we send an empty string to the server
    unsigned int current_stream_encoding; // code page of the stream's encoding
    bool executed;                        // whether the statement has been executed yet (used for error messages)
    bool prepared;                        // whether the statement has been prepared yet (used for error messages)
    bool fetch_called;                    // used by sqlsrv_get_field to return an informative error if fetch not yet called 
                                          // (a common mistake)
    sqlsrv_fetch_field* fetch_fields;     // field names for the current result set for use by 
                                          // sqlsrv_fetch_array/object as keys
    int fetch_fields_count;
    int last_field_index;                 // last field retrieved by sqlsrv_get_field
    bool past_fetch_end;                  // sqlsrv_fetch sets when the statement goes beyond the last row
    bool past_next_result_end;            // sqlsrv_next_resultset sets when the statement goes beyond the last results
    zval* params_z;                       // hold parameters passed to sqlsrv_prepare but not used until sqlsrv_execute
    SQLLEN* params_ind_ptr;               // buffer to hold the sizes returned by ODBC 
    zval* param_datetime_buffers;         // track which datetime parameter to convert from string to datetime objects
    zval* param_streams;                  // track which streams to send data to the server
    zval* param_strings;                  // track which output strings need to be converted to UTF-8
    void* param_buffer;                   // bufffer which param data from streams is read in and processed
    int param_buffer_size;
    bool send_at_exec;                    // determines if all the data is sent from a stream input parameter when sqlsrv_execute is called
    int conn_index;                       // index into the connection hash that contains this statement structure
    zval* active_stream;                  // the currently active stream reading data from the database
    bool scrollable;                      // determines if the statement was created with the Scrollable query attribute 
                                          // (don't have to use ODBC to find out)
    bool scroll_is_dynamic;               // if scrollable, is it a dynamic cursor.  sqlsrv_num_rows uses this information
    bool has_rows;                        // has_rows is set if there are actual rows in the row set

    sqlsrv_stmt( void ) :
        ctx( SQL_HANDLE_STMT )
    {
    }

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
PHP_FUNCTION(sqlsrv_has_rows);
PHP_FUNCTION(sqlsrv_next_result);
PHP_FUNCTION(sqlsrv_num_fields);
PHP_FUNCTION(sqlsrv_num_rows);
PHP_FUNCTION(sqlsrv_rows_affected);
PHP_FUNCTION(sqlsrv_send_stream_data);

// resource destructor
void __cdecl sqlsrv_stmt_dtor( zend_rsrc_list_entry *rsrc TSRMLS_DC );

// "internal" statement functions shared by functions in conn.cpp and stmt.cpp
bool sqlsrv_stmt_common_execute( sqlsrv_stmt* s, const SQLCHAR* sql_string, int sql_len, bool direct, const char* function TSRMLS_DC );
void free_odbc_resources( sqlsrv_stmt* stmt TSRMLS_DC );
void free_stmt_resource( zval* stmt_z TSRMLS_DC );

// *** constants ***

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

// buffer size allocated to retrieve data from a PHP stream.  This number
// was chosen since PHP doesn't return more than 8k at a time even if
// the amount requested was more.
const int PHP_STREAM_BUFFER_SIZE = 8192;

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
// encodings we understand
HashTable* encodings;

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

// new memory allocation/free debugging facilities to help us verify that all allocations are being 
// released in a timely manner and not just at the end of the script.  
// Zend has memory logging and checking, but it can generate a lot of noise for just one extension.
// It's meant for internal use but might be useful for people adding features to our extension.
// To use it, uncomment the #define below and compile in Debug NTS.  All allocations and releases
// must be done with sqlsrv_malloc and sqlsrv_free.
// #define SQLSRV_MEM_DEBUG  1
#if defined( PHP_DEBUG ) && !defined( ZTS ) && defined( SQLSRV_MEM_DEBUG )

// macro to log memory allocation and frees locations and their sizes
inline void* emalloc_trace( size_t size, const char* file, int line )
{
    void* ptr = emalloc( size );
    LOG( SEV_NOTICE, LOG_STMT, "emalloc returned %4!08x!: %1!d! bytes at %2!s!:%3!d!", size, file, line, ptr );
    return ptr;
}

inline void* erealloc_trace( void* original, size_t size, const char* file, int line )
{
    void* ptr = erealloc( original, size );
    LOG( SEV_NOTICE, LOG_STMT, "erealloc returned %5!08x! from %4!08x!: %1!d! bytes at %2!s!:%3!d!", size, file, line, ptr, original );
    return ptr;
}

inline void efree_trace( void* ptr, const char* file, int line )
{
    LOG( SEV_NOTICE, LOG_STMT, "efree %1!08x! at %2!s!:%3!d!", ptr, file, line );
    efree( ptr );
}

#define sqlsrv_malloc( size ) emalloc_trace( size, __FILE__, __LINE__ )
#define sqlsrv_realloc( buffer, size ) erealloc_trace( buffer, size, __FILE__, __LINE__ )
#define sqlsrv_free( ptr ) efree_trace( ptr, __FILE__, __LINE__ )

#else

#define sqlsrv_malloc( size ) emalloc( size )
#define sqlsrv_realloc( buffer, size ) erealloc( buffer, size )
#define sqlsrv_free( ptr )  efree( ptr )

#endif


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
extern sqlsrv_error SQLSRV_ERROR_INPUT_PARAM_ENCODING_TRANSLATE[];
extern sqlsrv_error SQLSRV_ERROR_OUTPUT_PARAM_ENCODING_TRANSLATE[];
extern sqlsrv_error SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE[];
extern sqlsrv_error SQLSRV_ERROR_INPUT_STREAM_ENCODING_TRANSLATE[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_CONN_ENCODING[];
extern sqlsrv_error SQLSRV_ERROR_QUERY_STRING_ENCODING_TRANSLATE[];
extern sqlsrv_error SQLSRV_ERROR_CONNECT_STRING_ENCODING_TRANSLATE[];
extern sqlsrv_error SQLSRV_ERROR_CONNECT_ILLEGAL_ENCODING[];
extern sqlsrv_error SQLSRV_ERROR_DRIVER_NOT_INSTALLED[];
extern sqlsrv_error SQLSRV_ERROR_MARS_OFF[];
extern sqlsrv_error SQLSRV_ERROR_STATEMENT_NOT_SCROLLABLE[];
extern sqlsrv_error SQLSRV_ERROR_STATEMENT_SCROLLABLE[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_FETCH_STYLE[];
extern sqlsrv_error SQLSRV_ERROR_INVALID_OPTION_SCROLLABLE[];
extern sqlsrv_error SQLSRV_ERROR_UNKNOWN_SERVER_VERSION[];

// definitions for PHP specific warnings returned by sqlsrv
extern sqlsrv_error SQLSRV_WARNING_FIELD_NAME_EMPTY[];

// definitions for PHP warnings returned via php_error rather than sqlsrv_errors
extern sqlsrv_error PHP_WARNING_VAR_NOT_REFERENCE[];


// flags passed to sqlsrv_errors to filter its return values
enum error_handling_flags {
    SQLSRV_ERR_ERRORS,
    SQLSRV_ERR_WARNINGS,
    SQLSRV_ERR_ALL
};

// *** extension error functions ***
PHP_FUNCTION(sqlsrv_errors);
PHP_FUNCTION(sqlsrv_warnings);

// convert from the default encoding specified by the "CharacterSet"
// connection option to UTF-16.  mbcs_len and utf16_len are sizes in
// bytes.  The return is the number of UTF-16 characters in the string
// returned in utf16_out_string.
unsigned int convert_string_from_default_encoding( unsigned int php_encoding, char const* mbcs_in_string,
                                                   unsigned int mbcs_len, __out wchar_t* utf16_out_string,
                                                   unsigned int utf16_len );
// create a wide char string from the passed in mbcs string.  NULL is returned if the string
// could not be created.  No error is posted by this function.  utf16_len is the number of
// wchar_t characters, not the number of bytes.
wchar_t* utf16_string_from_mbcs_string( unsigned int php_encoding, const char* mbcs_string, 
                                        unsigned int mbcs_len, __out unsigned int* utf16_len );

// *** internal error macros and functions ***
bool handle_error( sqlsrv_context const* ctx, int log_subsystem, const char* function, 
                   sqlsrv_error const* ssphp TSRMLS_DC, ... );
void handle_warning( sqlsrv_context const* ctx, int log_subsystem, const char* function, 
                     sqlsrv_error const* ssphp TSRMLS_DC, ... );
void __cdecl sqlsrv_error_dtor( zend_rsrc_list_entry *rsrc TSRMLS_DC );
const char* get_last_error_message( DWORD last_error = 0 );


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

    // value from reference operator (i.e., i = *(&i); or *i = blah;)
    T& operator*()
    {
        return *_ptr;
    }

    // allow the use of the address-of operator to simulate a **.
    // Note: this operator conflicts with storing these within an STL container.  If you need
    // to do that, then redefine this as getpp and change instances of &auto_ptr to auto_ptr.getpp()
    T** operator&( void )
    {
        return &_ptr;
    }

protected:

    sqlsrv_auto_ptr( T* ptr ) :
        _ptr( ptr ) 
    {
    }

    sqlsrv_auto_ptr( sqlsrv_auto_ptr& src )
    {
        if( _ptr ) {
            static_cast<Subclass*>(this)->reset( src._ptr );
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

// an auto_ptr for sqlsrv_malloc/sqlsrv_free.  When allocating a chunk of memory using sqlsrv_malloc, wrap that pointer
// in a variable of sqlsrv_malloc_auto_ptr.  sqlsrv_malloc_auto_ptr will "own" that block and assure that it is
// freed until the variable is destroyed (out of scope) or ownership is transferred using the function
// "transferred".
template <typename T>
class sqlsrv_malloc_auto_ptr : public sqlsrv_auto_ptr<T, sqlsrv_malloc_auto_ptr<T> > {

public:

    sqlsrv_malloc_auto_ptr( void ) :
        sqlsrv_auto_ptr<T, sqlsrv_malloc_auto_ptr<T> >( NULL )
    {
    }

    sqlsrv_malloc_auto_ptr( const sqlsrv_malloc_auto_ptr& src ) :
        sqlsrv_auto_ptr<T, sqlsrv_malloc_auto_ptr<T> >( src )
    {
    }

    // free the original pointer and assign a new pointer. Use NULL to simply free the pointer.
    void reset( T* ptr = NULL )
    {
        if( _ptr )
            sqlsrv_free( (void*) _ptr );
        _ptr = ptr;
    }

    T* operator=( T* ptr )
    {
        return sqlsrv_auto_ptr<T, sqlsrv_malloc_auto_ptr<T> >::operator=( ptr );
    }

    sqlsrv_malloc_auto_ptr<T> operator=( sqlsrv_malloc_auto_ptr<T>& src )
    {
        T* p = src.get();
        src.transferred();
        this->_ptr = p;
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
#if PHP_MAJOR_VERSION > 5 || (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION >= 3)
    operator zval_gc_info*( void )
    {
        return reinterpret_cast<zval_gc_info*>(_ptr);
    }
#endif

private:

    zval_auto_ptr( const zval_auto_ptr& src );
};

#endif	/* PHP_SQLSRV_H */


//----------------------------------------------------------------------------------------------------------------------------------
// File: util.cpp
//
// Copyright (c) Microsoft Corporation.  All rights reserved.
//
// Contents: Utility functions for the SQL Server Driver for PHP 1.0
// 
// Comments: Mostly error handling and some type handling
//
// License: This software is released under the Microsoft Public License.  A copy of the license agreement 
//          may be found online at http://www.codeplex.com/SQL2K5PHP/license.
//----------------------------------------------------------------------------------------------------------------------------------

#include "php_sqlsrv.h"

#include <windows.h>


namespace {

// *** internal constants ***
// current subsytem.  defined for the CHECK_SQL_{ERROR|WARNING} macros
int current_log_subsystem = LOG_UTIL;

// SQLSTATE for all internal errors 
const char IMSSP[] = "IMSSP";

// SQLSTATE for all internal warnings
const char SSPWARN[] = "01SSP";

// buffer used to hold a formatted log message prior to actually logging it.
const int LOG_MSG_SIZE = 2048;
char log_msg[ LOG_MSG_SIZE ];

// internal error that says that FormatMessage failed
const char* internal_format_error = "An internal error occurred.  FormatMessage failed writing an error message.";

// *** internal functions ***
bool handle_errors_and_warnings( sqlsrv_context const* ctx, zval** reported_chain, zval** ignored_chain, int log_severity, int log_subsystem, 
                                 const char* _FN_, sqlsrv_error const* ssphp, va_list args TSRMLS_DC );
bool ignore_warning( char const* sql_state, int native_code TSRMLS_DC );

bool sqlsrv_merge_zend_hash( __inout zval* dest_z, zval const* src_z TSRMLS_DC );
int  sqlsrv_merge_zend_hash_dtor( void* dest TSRMLS_DC );

}

// internal error defintions.  see sqlsrv_error structure definition in php_sqlsrv.h for more information
sqlsrv_error SQLSRV_ERROR_INVALID_OPTION[] = {
    { IMSSP, "Invalid option %1!s! was passed to sqlsrv_connect.", -1, true }
};
sqlsrv_error SQLSRV_ERROR_FILE_VERSION[] = {
    { IMSSP, "An error occurred when retrieving the extension version.", -2, false }
};
sqlsrv_error SQLSRV_ERROR_INVALID_PARAM_TYPE[] = {
    { IMSSP, "An unknown type for a bound parameter was specified.", -3, false }
};
sqlsrv_error SQLSRV_ERROR_CONNECT_BRACES_NOT_ESCAPED[] = {
    { IMSSP, "An unescaped right brace (}) was found in option %1!s!.", -4, true }
};
sqlsrv_error SQLSRV_ERROR_NO_DATA[] = {
    { IMSSP, "Field %1!d! returned no data.", -5, true }
};
sqlsrv_error SQLSRV_ERROR_STREAMABLE_TYPES_ONLY[] = {
    { IMSSP, "Only char, nchar, varchar, nvarchar, binary, varbinary, and large object types can be read by using streams.", -6, false}
};
sqlsrv_error SQLSRV_ERROR_INVALID_OUTPUT_PARAM_TYPE[] = {
    { IMSSP, "An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams cannot be specified as output parameters.", -7, false }
};
sqlsrv_error SQLSRV_ERROR_INVALID_CONNECTION_KEY[] = {
    { IMSSP, "An invalid connection option key type was received. Option key types must be strings.", -8, false }
};
sqlsrv_error SQLSRV_ERROR_VAR_REQUIRED[] = {
    { IMSSP, "Parameter array %1!d! must have at least one value or variable.", -9, true }
};
sqlsrv_error SQLSRV_ERROR_INVALID_FETCH_TYPE[] = {
    { IMSSP, "An invalid fetch type was specified.    SQLSRV_FETCH_NUMERIC, SQLSRV_FETCH_ARRAY, and SQLSRV_FETCH_BOTH are acceptable values.", -10, false }
};
sqlsrv_error SQLSRV_ERROR_STATEMENT_NOT_EXECUTED[] = {
    { IMSSP, "The statement must be executed before results can be retrieved.", -11, false }
};
sqlsrv_error SQLSRV_ERROR_ALREADY_IN_TXN[] = {
    { IMSSP, "Cannot begin a transaction until the current transaction has been completed by calling either sqlsrv_commit or sqlsrv_rollback.", -12, false }
};
sqlsrv_error SQLSRV_ERROR_NOT_IN_TXN[] = {
    { IMSSP, "A transaction must be started by calling sqlsrv_begin_transaction before calling sqlsrv_commit or sqlsrv_rollback.", -13, false }
};
sqlsrv_error SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER[] = {
    { IMSSP, "An invalid parameter was passed to %1!s!.", -14, true }
};
sqlsrv_error SQLSRV_ERROR_INVALID_PARAMETER_DIRECTION[] = {
    { IMSSP, "An invalid direction for parameter %1!d! was specified. SQLSRV_PARAM_IN, SQLSRV_PARAM_OUT, and SQLSRV_PARAM_INOUT are valid values.", -15, true }
};
sqlsrv_error SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE[] = {
    { IMSSP, "An invalid PHP type for parameter %1!d! was specified.", -16, true }
};
sqlsrv_error SQLSRV_ERROR_INVALID_PARAMETER_SQLTYPE[] = {
    { IMSSP, "An invalid SQL Server type for parameter %1!d! was specified.", -17, true }
};
sqlsrv_error SQLSRV_ERROR_FETCH_NOT_CALLED[] = {
    { IMSSP, "A row must be retrieved with sqlsrv_fetch before retrieving data with sqlsrv_get_field.", -18, false }
};
sqlsrv_error SQLSRV_ERROR_FIELD_INDEX_ERROR[] = {
    { IMSSP, "Fields within a row must be accessed in sequential order. The sqlsrv_get_field function cannot retrieve field %1!d! because its index is less than the index of a field that has already been retrieved (%2!d!).", -19, true }
};
sqlsrv_error SQLSRV_ERROR_DATETIME_CONVERSION_FAILED[] = {
    { IMSSP, "The retrieval of the DateTime object failed.", -20, false }
};
sqlsrv_error SQLSRV_ERROR_SERVER_INFO[] = {
    { IMSSP, "An error occurred while retrieving the server information.", -21, false }
};
sqlsrv_error SQLSRV_ERROR_FETCH_PAST_END[] = {
    { IMSSP, "There are no more rows in the active result set.", -22, false }
};
sqlsrv_error SQLSRV_ERROR_STATEMENT_NOT_PREPARED[] = {
    { IMSSP, "A statement must be prepared with sqlsrv_prepare before calling sqlsrv_execute.", -23, false }
};
sqlsrv_error SQLSRV_ERROR_ZEND_HASH[] = {
    { IMSSP, "An error occurred while creating or accessing a Zend hash table.", -24, false }
};
sqlsrv_error SQLSRV_ERROR_ZEND_STREAM[] = {
    { IMSSP, "An error occurred while reading from a PHP stream.", -25, false }
};
sqlsrv_error SQLSRV_ERROR_NEXT_RESULT_PAST_END[] = {
    { IMSSP, "There are no more results returned by the query.", -26, false }
};
sqlsrv_error SQLSRV_ERROR_STREAM_CREATE[] = {
    { IMSSP, "An error occurred while retrieving a SQL Server field as a stream.", -27, false }
};
sqlsrv_error SQLSRV_ERROR_NO_FIELDS[] = {
    { IMSSP, "The active result for the query contains no fields.", -28, false }
};
sqlsrv_error SQLSRV_ERROR_ZEND_BAD_CLASS[] = {
    { IMSSP, "Failed to find class %1!s!.", -29, true }
};
sqlsrv_error SQLSRV_ERROR_ZEND_OBJECT_FAILED[] = {
    { IMSSP, "Failed to create an instance of class %1!s!.", -30, true }
};
sqlsrv_error SQLSRV_ERROR_INVALID_PARAMETER_PRECISION[] = {
    { IMSSP, "An invalid size or precision for parameter %1!d! was specified.", -31, true }
};
sqlsrv_error SQLSRV_ERROR_INVALID_OPTION_KEY[] = {
    { IMSSP, "Option %1!s! is invalid.", -32, true }
};
sqlsrv_error SQLSRV_ERROR_INVALID_OPTION_VALUE[] = {
    { IMSSP, "Invalid value %1!s! for option %2!s! was specified.", -33, true }
};
sqlsrv_error SQLSRV_ERROR_OUTPUT_PARAM_TYPE_DOESNT_MATCH[] = {
    { IMSSP, "The type of output parameter %1!d! does not match the type specified by the SQLSRV_PHPTYPE_* constant."
             " For output parameters, the type of the variable's current value must match the SQLSRV_PHPTYPE_* constant, or be NULL. "
             "If the type is NULL, the PHP type of the output parameter is inferred from the SQLSRV_SQLTYPE_* constant.", -34, true }
};
sqlsrv_error SQLSRV_ERROR_INVALID_TYPE[] = {
    { IMSSP, "Invalid type", -35, false }
};
sqlsrv_error SQLSRV_ERROR_COMMIT_FAILED[] = {
    { IMSSP, "Transaction commit failed. Auto commit mode is still off.", -36, false }
};
sqlsrv_error SQLSRV_ERROR_ROLLBACK_FAILED[] = {
    { IMSSP, "Transaction rollback failed. Auto commit mode is still off.", -37, false }
};
sqlsrv_error SQLSRV_ERROR_AUTO_COMMIT_STILL_OFF[] = {
    { IMSSP, "The transaction completed (it was either committed or rolled back). Auto commit mode is still off.", -38, false }
};   
sqlsrv_error SQLSRV_ERROR_REGISTER_RESOURCE[] = {
    { IMSSP, "Registering the %1!s! resource failed.", -39, true }
};
sqlsrv_error SQLSRV_ERROR_DRIVER_NOT_INSTALLED[] = {
    { IMSSP, "The SQL Server Driver for PHP requires the SQL Server 2005 Native Client ODBC Driver to communicate with SQL Server.  "
             "That ODBC Driver is not currently installed.  Accessing the following URL will download the SQL Server 2005 Native Client ODBC driver for %1!s!: %2!s!", -40, true }
};


// internal warning definitions
sqlsrv_error SQLSRV_WARNING_FIELD_NAME_EMPTY[] = {
    { SSPWARN, "An empty field name was skipped by sqlsrv_fetch_object.", -100, false }
};


// This warning is special since it's reported by php_error rather than sqlsrv_errors.  That's also why it has 
// a printf format specification instead of a FormatMessage format specification.
sqlsrv_error PHP_WARNING_VAR_NOT_REFERENCE[] = {
    { SSPWARN, "Variable parameter %d not passed by reference (prefaced with an &).  Variable parameters passed to sqlsrv_prepare should be passed by reference, not by value.  For more information, see sqlsrv_prepare in the API Reference section of the product documentation.", -101, true }
};

// sqlsrv_errors( [int $errorsAndOrWarnings] )
//
// Returns extended error and/or warning information about the last sqlsrv
// operation performed.
//
// The sqlsrv_errors function can return error and/or warning information by
// calling it with one of the following parameter values below.
//
// Parameters
//
// $errorsAndOrWarnings[OPTIONAL]: A predefined constant. This parameter can
// take one of the values listed:
// 
//  SQLSRV_ERR_ALL
//      Errors and warnings generated on the last sqlsrv function call are returned.
//  SQLSRV_ERR_ERRORS
//      Errors generated on the last sqlsrv function call are returned.
//  SQLSRV_ERR_WARNINGS
//      Warnings generated on the last sqlsrv function call are returned.
//
// If no parameter value is supplied, SQLSRV_ERR_ALL is the default
//
// Return Value
// An array of arrays, or null. An example of an error returned:
// Array
// (
//     [0] => Array
//         (
//             [0] => HYT00
//             [SQLSTATE] => HYT00
//             [1] => 0
//             [code] => 0
//             [2] => [Microsoft][SQL Native Client]Query timeout expired
//             [message] => [Microsoft][SQL Native Client]Query timeout expired
//         )
// )

PHP_FUNCTION( sqlsrv_errors )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    long flags = SQLSRV_ERR_ALL;
    full_mem_check(MEMCHECK_SILENT);

    DECL_FUNC_NAME( "sqlsrv_errors" );
    LOG_FUNCTION;

    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "|l", &flags ) == FAILURE ) {
        RETURN_FALSE;
    }
    if( flags == SQLSRV_ERR_ALL ) {
        
        int result;
        zval_auto_ptr both_z;
        
        MAKE_STD_ZVAL( both_z );
        result = array_init( both_z );
        if( result == FAILURE ) {
            zval_ptr_dtor( &both_z );
            RETURN_FALSE;
        }
        Z_SET_ISREF_P( both_z );
        if( Z_TYPE_P( SQLSRV_G( errors )) == IS_ARRAY && !sqlsrv_merge_zend_hash( both_z, SQLSRV_G( errors ) TSRMLS_CC )) {
            zend_hash_destroy( Z_ARRVAL_P( both_z ));
            RETURN_FALSE;
        }
            

        if( Z_TYPE_P( SQLSRV_G( warnings )) == IS_ARRAY && !sqlsrv_merge_zend_hash( both_z, SQLSRV_G( warnings ) TSRMLS_CC )) {
            zend_hash_destroy( Z_ARRVAL_P( both_z ));
            RETURN_FALSE;
        }

        if( zend_hash_num_elements( Z_ARRVAL_P( both_z )) == 0 ) {
            RETURN_NULL();
        }

        zval_ptr_dtor( &return_value );
        *return_value_ptr = both_z;
        both_z.transferred();
    }
    else if( flags == SQLSRV_ERR_WARNINGS ) {
        zval_ptr_dtor( &return_value );
        *return_value_ptr = SQLSRV_G( warnings );
        zval_add_ref( &SQLSRV_G( warnings ));
    }
    else {
        zval_ptr_dtor( &return_value );
        *return_value_ptr = SQLSRV_G( errors );
        zval_add_ref( &SQLSRV_G( errors ));
    }
}


// sqlsrv_configure( string $setting, mixed $value )
//
// Changes the settings for error handling and logging options.
//
// Parameters
// $setting: The name of the setting to be configured. The possible implemented values are
// "WarningsReturnAsErrors", "LogSubsystems", and "LogSeverity".
//
// $value: The value to be applied to the setting specified in the $setting
// parameter. See MSDN or the MINIT function for possible values.
//
// Return Value
// If sqlsrv_configure is called with an unsupported setting or value, the
// function returns false. Otherwise, the function returns true.

PHP_FUNCTION( sqlsrv_configure )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    char* option;
    int option_len;
    zval* value_z;

    DECL_FUNC_NAME( "sqlsrv_configure" );    
    LOG_FUNCTION;

    RETVAL_FALSE;

    reset_errors( TSRMLS_C );

    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "sz", &option, &option_len, &value_z ) == FAILURE ) {
        handle_error( NULL, LOG_UTIL, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
        RETURN_FALSE;
    }

    if( !stricmp( option, INI_WARNINGS_RETURN_AS_ERRORS )) {
        if( zend_is_true( value_z )) {
            SQLSRV_G( warnings_return_as_errors ) = true;
        }
        else {
            SQLSRV_G( warnings_return_as_errors ) = false;
        }
    
        LOG( SEV_NOTICE, LOG_UTIL, INI_PREFIX INI_WARNINGS_RETURN_AS_ERRORS " = %1!s!", SQLSRV_G( warnings_return_as_errors ) ? "On" : "Off");
                                    
        RETURN_TRUE;
    }
    else if( !stricmp( option, INI_LOG_SEVERITY )) {

        if( Z_TYPE_P( value_z ) != IS_LONG ) {
            handle_error( NULL, LOG_UTIL, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
            RETURN_FALSE;
        }
    
        long severity_mask = Z_LVAL_P( value_z );
        // make sure they can't use 0 to shut off the masking in the severity
        if( severity_mask < SEV_ALL || severity_mask == 0 || severity_mask > (SEV_NOTICE + SEV_ERROR + SEV_WARNING) ) {
            RETURN_FALSE;
        }

        SQLSRV_G( log_severity ) = severity_mask;

        LOG( SEV_NOTICE, LOG_UTIL, INI_PREFIX INI_LOG_SEVERITY " = %1!d!", SQLSRV_G( log_severity ));
                                    
        RETURN_TRUE;
    }
    else if( !stricmp( option, INI_LOG_SUBSYSTEMS )) {

        if( Z_TYPE_P( value_z ) != IS_LONG ) {
            handle_error( NULL, LOG_UTIL, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
            RETURN_FALSE;
        }
    
        long subsystem_mask = Z_LVAL_P( value_z );
        LOG( SEV_NOTICE, LOG_UTIL, "subsystem_mask = %1!d!", subsystem_mask );
        if( subsystem_mask < LOG_ALL || subsystem_mask > (LOG_INIT + LOG_CONN + LOG_STMT + LOG_UTIL) ) {
            RETURN_FALSE;
        }

        SQLSRV_G( log_subsystems ) = subsystem_mask;

        LOG( SEV_NOTICE, LOG_UTIL, INI_PREFIX INI_LOG_SUBSYSTEMS " = %1!d!", SQLSRV_G( log_subsystems ));
    
        RETURN_TRUE;
    }
    else {

        LOG( SEV_ERROR, LOG_UTIL, "Invalid option given to sqlsrv_configure" );
        
        handle_error( NULL, LOG_UTIL, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );

        RETURN_FALSE;
    }
}


// sqlsrv_get_config( string $setting )
// 
// Returns the current value of the specified configuration setting.
//
// Parameters
//  $setting: The configuration setting for which the value is returned. For a
//  list of configurable settings, see sqlsrv_configure.
//
// Return Value
// The value of the setting specified by the $setting parameter. If an invalid
// setting is specified, false is returned and an error is added to the error
// collection.  Because false is a valid value for WarningsReturnAsErrors, to
// really determine if an error occurred, call sqlsrv_errors.

PHP_FUNCTION( sqlsrv_get_config )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    char* option;
    int option_len;

    DECL_FUNC_NAME( "sqlsrv_get_config" );    
    LOG_FUNCTION;

    reset_errors( TSRMLS_C );

    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "s", &option, &option_len ) == FAILURE ) {
        handle_error( NULL, LOG_UTIL, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
        RETURN_FALSE;
    }
    
    if( !stricmp( option, INI_WARNINGS_RETURN_AS_ERRORS )) {

        ZVAL_BOOL( return_value, SQLSRV_G( warnings_return_as_errors ));
        return;
    }
    else if( !stricmp( option, INI_LOG_SEVERITY )) {

        ZVAL_LONG( return_value, SQLSRV_G( log_severity ));
        return;
    }
    else if( !stricmp( option, INI_LOG_SUBSYSTEMS )) {

        ZVAL_LONG( return_value, SQLSRV_G( log_subsystems ));
        return;
    }
    else {

        LOG( SEV_ERROR, LOG_UTIL, "Invalid option given to sqlsrv_get_config." );

        handle_error( NULL, LOG_UTIL, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );

        RETURN_FALSE;
    }
}

// wrapper around handle_error that checks a condition and returns whether or not the error was ignored
bool check_sql_error_ex( bool condition, sqlsrv_context const* ctx, int log_subsystem,  const char* _FN_, sqlsrv_error const* ssphp TSRMLS_DC, ... )
{
    if( condition ) {
    
        va_list args;
#if defined(ZTS)
        va_start( args, TSRMLS_C );
#else
        va_start( args, ssphp);
#endif
        
        bool ignored = handle_error( ctx, log_subsystem, _FN_, ssphp TSRMLS_CC, args );
        if( ignored ) {
            // this will print immediately after the error information in the log
            LOG( SEV_ERROR, log_subsystem, "error ignored" );
        }

        va_end( args );

        return ignored;
    }
 
    return true;   
}

// this is a special function for sqlsrv internal warnings.  It emits an internal warning and treats
// it as an error if the WarningsReturnAsErrors flag is set.
bool check_sqlsrv_warnings( bool condition, sqlsrv_context const* ctx, int log_subsystem,  const char* _FN_, sqlsrv_error const* ssphp TSRMLS_DC, ... )
{
    // we have to have warning as there is no ODBC error or warning
    assert( ssphp != NULL );

    if( condition ) {
    
        va_list args;
#if defined(ZTS)
        va_start( args, TSRMLS_C );
#else
        va_start( args, ssphp);
#endif
        if( SQLSRV_G( warnings_return_as_errors )) {
            bool ignored = handle_error( ctx, log_subsystem, _FN_, ssphp TSRMLS_CC, args );
            if( ignored ) {
                // this will print immediately after the error information in the log
                LOG( SEV_ERROR, log_subsystem, "error ignored" );
            }

            va_end( args );

            return ignored;
        }
        else {
            handle_warning( ctx, log_subsystem, _FN_, ssphp TSRMLS_CC, args );
        }

        va_end( args );
    }
 
    return true;   
}

// wrapper for errors around the common handle_errors_and_warnings
bool handle_error( sqlsrv_context const* ctx, int log_subsystem, const char* _FN_, sqlsrv_error const* ssphp TSRMLS_DC, ... )
{
    va_list args;
#if defined(ZTS)
    va_start( args, TSRMLS_C );
#else
    va_start( args, ssphp );
#endif
    LOG( SEV_NOTICE, LOG_UTIL, "handle_error: entered for function %1!s!", _FN_ );
    // put errors (including warnings treated as errors) into errors and ignored
    // warnings into warnings
    bool ignored = handle_errors_and_warnings( ctx, &SQLSRV_G( errors ), &SQLSRV_G( warnings ), SEV_ERROR, log_subsystem, _FN_, 
                                ssphp, args TSRMLS_CC );

    va_end( args );

    return ignored;
}

// wrapper for warnings around the common handle_errors_and_warnings
void handle_warning( sqlsrv_context const* ctx, int log_subsystem, const char* _FN_, sqlsrv_error const* ssphp TSRMLS_DC, ... )
{
    va_list args;
#if defined(ZTS)
    va_start( args, TSRMLS_C );
#else
    va_start( args, ssphp );
#endif
    LOG( SEV_NOTICE, LOG_UTIL, "handle_warning: entered for function %1!s!", _FN_ );
    // put all warnings into the warnings hash table and don't ignore any.  No warnings here are treated as errors.
    handle_errors_and_warnings( ctx, &SQLSRV_G( warnings ), NULL, SEV_WARNING, log_subsystem, _FN_, 
                                ssphp, args TSRMLS_CC );

    va_end( args );
}

// write to the php log if the severity and subsystem match the filters currently set in the INI or 
// the script (sqlsrv_configure).
void write_to_log( unsigned int severity, unsigned int subsystem TSRMLS_DC, const char* msg, ...)
{
    va_list args;
    va_start( args, msg );
    
    if( (severity & SQLSRV_G( log_severity )) && (subsystem & SQLSRV_G( log_subsystems ))) {

        DWORD rc = FormatMessage( FORMAT_MESSAGE_FROM_STRING, msg, 0, 0, log_msg, LOG_MSG_SIZE, &args );
        // if an error occurs for FormatMessage, we just output an internal error occurred.
        if( rc == 0 ) {
            SQLSRV_STATIC_ASSERT( sizeof( internal_format_error ) < sizeof( log_msg ));
            std::copy( internal_format_error, internal_format_error + sizeof( internal_format_error ), log_msg );
        }

        php_log_err( log_msg TSRMLS_CC );
    }

    va_end( args );
}

// *** internal function implementations *** 

namespace {

// there are actually two error arrays (potentially) constructed by this function.
// The reported chain is that set of diagnostics that is not in the list of warnings to not report as errors.  In other words, it's
// the list of normally processed diagnostics.
// The ignored chain is that set of diagnostics which were specifically ignored and not reported.  It is possible for the caller
// to specify no ignored chain by setting the parameter to NULL.

bool handle_errors_and_warnings( sqlsrv_context const* ctx, zval** reported_chain, zval** ignored_chain, int log_severity, int log_subsystem, 
                                 const char* _FN_, sqlsrv_error const* ssphp, va_list args TSRMLS_DC )
{
    zval* ssphp_z = NULL;
    SQLSMALLINT record_number = 1;
    SQLCHAR sql_state[6];
    SQLCHAR message_text[ SQL_MAX_MESSAGE_LENGTH + 1 ];
    SQLINTEGER native_error = 0;
    SQLSMALLINT message_len = 0;
    SQLRETURN r = SQL_SUCCESS;
    zval* temp = NULL;
    bool reported_chain_was_null = false;
    bool ignored_chain_was_null = false;
    int zr = SUCCESS;
    int reported_before = 0;
    int ignored_before = 0;

    LOG( SEV_NOTICE, LOG_UTIL, "handle_errors_and_warnings: entering" );
    
    // create an array of arrays
    if( Z_TYPE_P( *reported_chain ) == IS_NULL ) {
        reported_chain_was_null = true;
        reported_before = 0;
        zr = array_init( *reported_chain );
        if( zr == FAILURE ) {
            DIE( "Fatal error in handle_errors_and_warnings" );
        }
    }
    else {
        reported_before = zend_hash_num_elements( Z_ARRVAL_PP( reported_chain ));
    }

    if( ignored_chain != NULL ) {

        if( Z_TYPE_P( *ignored_chain ) == IS_NULL ) {
            ignored_chain_was_null = true;
            ignored_before = 0;
            zr = array_init( *ignored_chain );
            if( zr == FAILURE ) {
                DIE( "Fatal error in handle_errors_and_warnings" );
            }
        }
        else {
            ignored_before = zend_hash_num_elements( Z_ARRVAL_PP( ignored_chain ));
        }
    }
    else {
        ignored_before = 0;
    }

    // add the PHP error first if there is one.
    // We use a while loop to allow the break to exit the loop and avoid having to use a goto for error handling
    // the break at the end of the loop assures that we don't get stuck here.
    while( ssphp ) {

        emalloc_auto_ptr<sqlsrv_error> ssphp_new;
        emalloc_auto_ptr<const char> ssphp_new_message;

        if( ssphp->format ) {
            ssphp_new = static_cast<sqlsrv_error*>( emalloc( sizeof( sqlsrv_error )));
            ssphp_new->native_message = ssphp_new_message = static_cast<char const*>( emalloc( SQL_MAX_MESSAGE_LENGTH ));
            ssphp_new->sqlstate = ssphp->sqlstate;
            ssphp_new->native_code = ssphp->native_code;
            DWORD rc = FormatMessage( FORMAT_MESSAGE_FROM_STRING, const_cast<LPSTR>( ssphp->native_message ), 0, 0, 
                                      const_cast<LPSTR>( ssphp_new->native_message ), SQL_MAX_MESSAGE_LENGTH, &args );
            if( rc == 0 ) {
                ssphp_new->native_message = internal_format_error;
            }
            ssphp = ssphp_new;
        }

        // log the error first in case of failures below
        LOG( log_severity, log_subsystem, "%1!s!: SQLSTATE = %2!s!", _FN_, ssphp->sqlstate );
        LOG( log_severity, log_subsystem, "%1!s!: error code = %2!d!", _FN_, ssphp->native_code );
        LOG( log_severity, log_subsystem, "%1!s!: message = %2!s!", _FN_, ssphp->native_message );

        MAKE_STD_ZVAL( ssphp_z );
        zr = array_init( ssphp_z );
        if( zr == FAILURE ) {
            zval_ptr_dtor( &ssphp_z );
            break;
        }
        // add the error info to the array
        MAKE_STD_ZVAL( temp );
        ZVAL_STRINGL( temp, const_cast<char*>( ssphp->sqlstate ), SQL_SQLSTATE_SIZE, 1 );
        zr = add_next_index_zval( ssphp_z, temp );
        if( zr == FAILURE ) {
            zval_ptr_dtor( &ssphp_z );
            break;
        }
        zr = add_assoc_zval( ssphp_z, "SQLSTATE", temp );
        if( zr == FAILURE ) {
            zval_ptr_dtor( &ssphp_z );
            break;
        }
        MAKE_STD_ZVAL( temp );
        ZVAL_LONG( temp, ssphp->native_code );
        zr = add_next_index_zval( ssphp_z, temp );
        if( zr == FAILURE ) {
            zval_ptr_dtor( &ssphp_z );
            break;
        }
        zr = add_assoc_zval( ssphp_z, "code", temp );
        if( zr == FAILURE ) {
            zval_ptr_dtor( &ssphp_z );
            break;
        }
        MAKE_STD_ZVAL( temp );
        ZVAL_STRING( temp, const_cast<char*>( ssphp->native_message ), 1 );
        zr = add_next_index_zval( ssphp_z, temp );
        if( zr == FAILURE ) {
            zval_ptr_dtor( &ssphp_z );
            break;
        }
        zr = add_assoc_zval( ssphp_z, "message", temp );
        if( zr == FAILURE ) {
            zval_ptr_dtor( &ssphp_z );
            break;
        }

        if( ignore_warning( ssphp->sqlstate, ssphp->native_code TSRMLS_CC ) && ignored_chain != NULL ) {
            zr = add_next_index_zval( *ignored_chain, ssphp_z );
            if( zr == FAILURE ) {
                zval_ptr_dtor( &ssphp_z );
                break;
            }
        }
        else {
            zr = add_next_index_zval( *reported_chain, ssphp_z );
            if( zr == FAILURE ) {
                zval_ptr_dtor( &ssphp_z );
                break;
            }
        }

        break;  // exit the "loop" always
    }

    if( ctx ) {

        SQLHANDLE h = ctx->handle;
        SQLSMALLINT h_type = ctx->handle_type;
        for( r = SQLGetDiagRec( h_type, h, record_number, sql_state, &native_error, message_text, SQL_MAX_MESSAGE_LENGTH + 1, &message_len );
             SQL_SUCCEEDED( r );
             ++record_number,
             r = SQLGetDiagRec( h_type, h, record_number, sql_state, &native_error, message_text, SQL_MAX_MESSAGE_LENGTH + 1, &message_len )) {


            // log the result first
            LOG( log_severity, log_subsystem, "%1!s!: SQLSTATE = %2!s!", _FN_, sql_state );
            LOG( log_severity, log_subsystem, "%1!s!: error code = %2!d!", _FN_, native_error );
            LOG( log_severity, log_subsystem, "%1!s!: message = %2!s!", _FN_, message_text );

            MAKE_STD_ZVAL( ssphp_z );
            zr = array_init( ssphp_z );
            if( zr == FAILURE ) {
                zval_ptr_dtor( &ssphp_z );
                continue;
            }
            // add the error info to the array
            MAKE_STD_ZVAL( temp );
            ZVAL_STRINGL( temp, reinterpret_cast<char*>( sql_state ), SQL_SQLSTATE_SIZE, 1 );
            zr = add_next_index_zval( ssphp_z, temp );
            if( zr == FAILURE ) {
                zval_ptr_dtor( &ssphp_z );
                continue;
            }
            zr = add_assoc_zval( ssphp_z, "SQLSTATE", temp );
            if( zr == FAILURE ) {
                zval_ptr_dtor( &ssphp_z );
                continue;
            }
            MAKE_STD_ZVAL( temp );
            ZVAL_LONG( temp, native_error );
            zr = add_next_index_zval( ssphp_z, temp );
            if( zr == FAILURE ) {
                zval_ptr_dtor( &ssphp_z );
                continue;
            }
            zr = add_assoc_zval( ssphp_z, "code", temp );
            if( zr == FAILURE ) {
                zval_ptr_dtor( &ssphp_z );
                continue;
            }
            MAKE_STD_ZVAL( temp );
            ZVAL_STRINGL( temp, reinterpret_cast<char*>(  message_text ), message_len, 1 );
            zr = add_next_index_zval( ssphp_z, temp );
            if( zr == FAILURE ) {
                zval_ptr_dtor( &ssphp_z );
                continue;
            }
            zr = add_assoc_zval( ssphp_z, "message", temp );
            if( zr == FAILURE ) {
                zval_ptr_dtor( &ssphp_z );
                continue;
            }

            if( ignore_warning( reinterpret_cast<const char*>( sql_state ), native_error TSRMLS_CC ) && ignored_chain != NULL ) {
                zr = add_next_index_zval( *ignored_chain, ssphp_z );
                if( zr == FAILURE ) {
                    zval_ptr_dtor( &ssphp_z );
                    continue;
                }
            }
            else {
                zr = add_next_index_zval( *reported_chain, ssphp_z );
                if( zr == FAILURE ) {
                    zval_ptr_dtor( &ssphp_z );
                    continue;
                }
            }
        }
    }

    bool all_errors_ignored = ( zend_hash_num_elements( Z_ARRVAL_PP( reported_chain )) == reported_before ) && 
                              ( ignored_chain != NULL && zend_hash_num_elements( Z_ARRVAL_PP( ignored_chain )) > ignored_before );

    // if the error array came in as NULL and didn't have anything added to it, return it as NULL
    if( reported_chain_was_null && zend_hash_num_elements( Z_ARRVAL_PP( reported_chain )) == 0 ) {
        zend_hash_destroy( Z_ARRVAL_PP( reported_chain ));
        FREE_HASHTABLE( Z_ARRVAL_PP( reported_chain ));
        ZVAL_NULL( *reported_chain );
    }
    if( ignored_chain != NULL && ignored_chain_was_null && zend_hash_num_elements( Z_ARRVAL_PP( ignored_chain )) == 0 ) {
        zend_hash_destroy( Z_ARRVAL_PP( ignored_chain ));
        FREE_HASHTABLE( Z_ARRVAL_PP( ignored_chain ));
        ZVAL_NULL( *ignored_chain );
    }

    return all_errors_ignored;
}


// return whether or not a warning should be ignored or returned as an error if WarningsReturnAsErrors is true
// see RINIT in init.cpp for information about which errors are ignored.
bool ignore_warning( char const* sql_state, int native_code TSRMLS_DC )
{
    for( zend_hash_internal_pointer_reset( SQLSRV_G( warnings_to_ignore ));
         zend_hash_has_more_elements( SQLSRV_G( warnings_to_ignore ) ) == SUCCESS;
         zend_hash_move_forward( SQLSRV_G( warnings_to_ignore ) ) ) {

        void* error_v;
        sqlsrv_error* error;
        int result;
        
        result = zend_hash_get_current_data( SQLSRV_G( warnings_to_ignore ), (void**) &error_v );
        if( result == FAILURE ) {
            return false;
        }
        
        error = static_cast<sqlsrv_error*>( error_v );
        if( !strncmp( error->sqlstate, sql_state, SQL_SQLSTATE_SIZE ) && ( error->native_code == native_code || error->native_code == -1 )) {
            return true;
        }
    }
        
    return false;
}

// used by sqlsrv_merge_zend_hash below
int  sqlsrv_merge_zend_hash_dtor( void* dest TSRMLS_DC )
{
#if defined(ZTS)
    SQLSRV_UNUSED( tsrm_ls );
#endif
    zval_ptr_dtor( reinterpret_cast<zval**>( &dest ));

    return ZEND_HASH_APPLY_REMOVE;
}

// sqlsrv_merge_zend_hash
// merge a source hash into a dest hash table and return any errors.
bool sqlsrv_merge_zend_hash( __inout zval* dest_z, zval const* src_z TSRMLS_DC )
{
#if defined(ZTS)
    SQLSRV_UNUSED( tsrm_ls );
#endif

    if( Z_TYPE_P( dest_z ) != IS_ARRAY && Z_TYPE_P( dest_z ) != IS_NULL ) DIE( "dest_z must be an array or null" );
    if( Z_TYPE_P( src_z ) != IS_ARRAY && Z_TYPE_P( src_z ) != IS_NULL ) DIE( "src_z must be an array or null" );

    if( Z_TYPE_P( src_z ) == IS_NULL ) {
        return true;
    }

    HashTable* src_ht = Z_ARRVAL_P( src_z );
    int result = SUCCESS;

    for( zend_hash_internal_pointer_reset( src_ht );
         zend_hash_has_more_elements( src_ht ) == SUCCESS;
         zend_hash_move_forward( src_ht ) ) {
         
        void * value_v;
        zval* value_z;
        result = zend_hash_get_current_data( src_ht, (void**) &value_v );
        if( result == FAILURE ) {
            zend_hash_apply( Z_ARRVAL_P( dest_z ), sqlsrv_merge_zend_hash_dtor TSRMLS_CC );
            return false;
        }
        value_z = *(static_cast<zval**>( value_v ));
        result = add_next_index_zval( dest_z, value_z );
        if( result == FAILURE ) {
            zend_hash_apply( Z_ARRVAL_P( dest_z ), sqlsrv_merge_zend_hash_dtor TSRMLS_CC );
            return false;
        }
        zval_add_ref( &value_z );
    }

    return true;
} 

}   // namespace

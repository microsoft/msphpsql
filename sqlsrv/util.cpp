//---------------------------------------------------------------------------------------------------------------------------------
// File: util.cpp
//
// Contents: Utility functions used by both connection or statement functions
//
// Comments: Mostly error handling and some type handling
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
//--------------------------------------------------------------------------------------------------------------------------------

#include "php_sqlsrv.h"

#include <windows.h>

namespace {
    
// current subsytem.  defined for the CHECK_SQL_{ERROR|WARNING} macros
unsigned int current_log_subsystem = LOG_UTIL;

// buffer used to hold a formatted log message prior to actually logging it.
const int LOG_MSG_SIZE = 2048;
char log_msg[ LOG_MSG_SIZE ];

// internal error that says that FormatMessage failed
SQLCHAR INTERNAL_FORMAT_ERROR[] = "An internal error occurred.  FormatMessage failed writing an error message.";

// *** internal functions ***
void copy_error_to_zval( zval** error_z, sqlsrv_error_const* error, zval** reported_chain, zval** ignored_chain, 
                                bool warning TSRMLS_DC );
bool ignore_warning( char* sql_state, int native_code TSRMLS_DC );
bool handle_errors_and_warnings( sqlsrv_context& ctx, zval** reported_chain, zval** ignored_chain, logging_severity log_severity, 
                                 unsigned int sqlsrv_error_code, bool warning, va_list* print_args TSRMLS_DC );

int  sqlsrv_merge_zend_hash_dtor( void* dest TSRMLS_DC );
bool sqlsrv_merge_zend_hash( __inout zval* dest_z, zval const* src_z TSRMLS_DC );

}

// List of all error messages
ss_error SS_ERRORS[] = {
          
    {
        SS_SQLSRV_ERROR_INVALID_OPTION,  
        { IMSSP, (SQLCHAR*)"Invalid option %1!s! was passed to sqlsrv_connect.", -1, true }
    },

    // no equivalent to error 2 in 2.0
    // error 3 is superceded by -16

    // these two share the same code since they are basically the same error.
    {
        SQLSRV_ERROR_UID_PWD_BRACES_NOT_ESCAPED,  
        { IMSSP, (SQLCHAR*) "An unescaped right brace (}) was found in either the user name or password.  All right braces must be"
        " escaped with another right brace (}}).", -4, false }
    },

    {
        SS_SQLSRV_ERROR_CONNECT_BRACES_NOT_ESCAPED, 
        { IMSSP, (SQLCHAR*)"An unescaped right brace (}) was found in option %1!s!.", -4, true }
    },
   
    {
        SQLSRV_ERROR_NO_DATA, 
        { IMSSP, (SQLCHAR*)"Field %1!d! returned no data.", -5, true }
    }, 

    {
        SQLSRV_ERROR_STREAMABLE_TYPES_ONLY, 
        { IMSSP, (SQLCHAR*)"Only char, nchar, varchar, nvarchar, binary, varbinary, and large object types can be read by using "
          "streams.", -6, false}
    },

    {
        SS_SQLSRV_ERROR_INVALID_OUTPUT_PARAM_TYPE,
        { IMSSP, (SQLCHAR*)"An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams cannot be "
          "specified as output parameters.", -7, false }
    },

    {
        SS_SQLSRV_ERROR_INVALID_CONNECTION_KEY,
        { IMSSP, (SQLCHAR*)"An invalid connection option key type was received. Option key types must be strings.", -8, false }
    },

    {
        SS_SQLSRV_ERROR_VAR_REQUIRED, 
        { IMSSP, (SQLCHAR*)"Parameter array %1!d! must have at least one value or variable.", -9, true }
    },
    
    {
        SS_SQLSRV_ERROR_INVALID_FETCH_TYPE, 
        { IMSSP, (SQLCHAR*)"An invalid fetch type was specified. SQLSRV_FETCH_NUMERIC, SQLSRV_FETCH_ARRAY and SQLSRV_FETCH_BOTH are acceptable values.", -10, false }
    },

    {
        SQLSRV_ERROR_STATEMENT_NOT_EXECUTED,
        { IMSSP, (SQLCHAR*)"The statement must be executed before results can be retrieved.", -11, false }
    },
    
    {
        SS_SQLSRV_ERROR_ALREADY_IN_TXN,
        { IMSSP, (SQLCHAR*)"Cannot begin a transaction until the current transaction has been completed by calling either "
          "sqlsrv_commit or sqlsrv_rollback.", -12, false }
    },  
    
    {
        SS_SQLSRV_ERROR_NOT_IN_TXN,
        { IMSSP, (SQLCHAR*)"A transaction must be started by calling sqlsrv_begin_transaction before calling sqlsrv_commit or "
          "sqlsrv_rollback.", -13, false }
    },  
    
    {
        SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER,
        { IMSSP, (SQLCHAR*)"An invalid parameter was passed to %1!s!.", -14, true }
    },  
      
    {
        SS_SQLSRV_ERROR_INVALID_PARAMETER_DIRECTION,
        { IMSSP, (SQLCHAR*)"An invalid direction for parameter %1!d! was specified. SQLSRV_PARAM_IN, SQLSRV_PARAM_OUT, and "
          "SQLSRV_PARAM_INOUT are valid values.", -15, true }
    },

    {
        SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE,
        { IMSSP, (SQLCHAR*)"An invalid PHP type for parameter %1!d! was specified.", -16, true }
    },

    {
        SQLSRV_ERROR_INVALID_PARAMETER_SQLTYPE,
        { IMSSP, (SQLCHAR*)"An invalid SQL Server type for parameter %1!d! was specified.", -17, true }
    },

    {
        SQLSRV_ERROR_FETCH_NOT_CALLED,
        { IMSSP, (SQLCHAR*)"A row must be retrieved with sqlsrv_fetch before retrieving data with sqlsrv_get_field.", -18, false }
    },    

    {
        SQLSRV_ERROR_FIELD_INDEX_ERROR,
        { IMSSP, (SQLCHAR*)"Fields within a row must be accessed in ascending order. "
          "The sqlsrv_get_field function cannot retrieve field %1!d! because its index is less "
          "than the index of a field that has already been retrieved (%2!d!).", -19, true }
    },

    {
        SQLSRV_ERROR_DATETIME_CONVERSION_FAILED,
        { IMSSP, (SQLCHAR*)"The retrieval of the DateTime object failed.", -20, false }
    },

    // no equivalent to SQLSRV_ERROR_SERVER_INFO in 2.0 so -21 is skipped

    {
        SQLSRV_ERROR_FETCH_PAST_END,
        { IMSSP, (SQLCHAR*)"There are no more rows in the active result set.  Since this result set is not scrollable, no more "
          "data may be retrieved.", -22, false } 
    },
    
    {
        SS_SQLSRV_ERROR_STATEMENT_NOT_PREPARED, 
        { IMSSP, (SQLCHAR*)"A statement must be prepared with sqlsrv_prepare before calling sqlsrv_execute.", -23, false }
    },   
   
    {
        SQLSRV_ERROR_ZEND_HASH,
        { IMSSP, (SQLCHAR*)"An error occurred while creating or accessing a Zend hash table.", -24, false }
    },

    {
        SQLSRV_ERROR_ZEND_STREAM,
        { IMSSP, (SQLCHAR*)"An error occurred while reading from a PHP stream.", -25, false }
    },

    {
        SQLSRV_ERROR_NEXT_RESULT_PAST_END,
        { IMSSP, (SQLCHAR*)"There are no more results returned by the query.", -26, false }
    },
      
    {
        SQLSRV_ERROR_STREAM_CREATE,
        { IMSSP, (SQLCHAR*)"An error occurred while retrieving a SQL Server field as a stream.", -27, false }
    },
          
    {
        SQLSRV_ERROR_NO_FIELDS,
        { IMSSP, (SQLCHAR*)"The active result for the query contains no fields.", -28, false }
    },

    {
        SS_SQLSRV_ERROR_ZEND_BAD_CLASS, 
        { IMSSP, (SQLCHAR*)"Failed to find class %1!s!.", -29, true }
    }, 

    {
      SS_SQLSRV_ERROR_ZEND_OBJECT_FAILED,
      { IMSSP, (SQLCHAR*)"Failed to create an instance of class %1!s!.", -30, true }
    },
 
    {
        SS_SQLSRV_ERROR_INVALID_PARAMETER_PRECISION,
        { IMSSP, (SQLCHAR*)"An invalid size or precision for parameter %1!d! was specified.", -31, true }
    },
        
    {
        SQLSRV_ERROR_INVALID_OPTION_KEY,
        { IMSSP, (SQLCHAR*)"Option %1!s! is invalid.", -32, true }
    },
 
    // these three errors are returned for invalid options, so they are given the same number for compatibility with 1.1
    {
        SQLSRV_ERROR_INVALID_QUERY_TIMEOUT_VALUE,
        { IMSSP, (SQLCHAR*) "Invalid value %1!s! specified for option SQLSRV_QUERY_TIMEOUT.", -33, true }
    },

    {
        SQLSRV_ERROR_INVALID_OPTION_TYPE_INT, 
        { IMSSP, (SQLCHAR*) "Invalid value type for option %1!s! was specified.  Integer type was expected.", -33, true }
    },

    {
        SQLSRV_ERROR_INVALID_OPTION_TYPE_STRING, 
        { IMSSP, (SQLCHAR*) "Invalid value type for option %1!s! was specified.  String type was expected.", -33, true }
    },
        
    {
        SQLSRV_ERROR_INPUT_OUTPUT_PARAM_TYPE_MATCH, 
        { IMSSP, (SQLCHAR*)"The type of output parameter %1!d! does not match the type specified by the SQLSRV_PHPTYPE_* constant."
           " For output parameters, the type of the variable's current value must match the SQLSRV_PHPTYPE_* constant, or be NULL. "
           "If the type is NULL, the PHP type of the output parameter is inferred from the SQLSRV_SQLTYPE_* constant.", -34, true }
    },

    {
        SQLSRV_ERROR_INVALID_TYPE,
        { IMSSP, (SQLCHAR*)"Invalid type", -35, false }
    },    
        
    // 36-38 have no equivalent 2.0 errors

    {
        SS_SQLSRV_ERROR_REGISTER_RESOURCE,
        { IMSSP, (SQLCHAR*)"Registering the %1!s! resource failed.", -39, true }
    },  

    {
        SQLSRV_ERROR_INPUT_PARAM_ENCODING_TRANSLATE,
        { IMSSP, (SQLCHAR*)"An error occurred translating string for input param %1!d! to UCS-2: %2!s!", -40, true }
    },

    {
        SQLSRV_ERROR_OUTPUT_PARAM_ENCODING_TRANSLATE,
        { IMSSP, (SQLCHAR*)"An error occurred translating string for an output param to UTF-8: %1!s!", -41, true }
    },
    
    {
        SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE,
        { IMSSP, (SQLCHAR*)"An error occurred translating string for a field to UTF-8: %1!s!", -42, true }
    },

    {
        SQLSRV_ERROR_INPUT_STREAM_ENCODING_TRANSLATE,
        { IMSSP, (SQLCHAR*)"An error occurred translating a PHP stream from UTF-8 to UTF-16: %1!s!", -43, true }
    },

    {  
        SQLSRV_ERROR_MARS_OFF,
        { IMSSP, (SQLCHAR*)"The connection cannot process this operation because there is a statement with pending results.  "
          "To make the connection available for other queries, either fetch all results or cancel or free the statement.  "
          "For more information, see the product documentation about the MultipleActiveResultSets connection option.", -44, false }
    },
  
    {
        SQLSRV_ERROR_CONN_OPTS_WRONG_TYPE, 
        { IMSSP, (SQLCHAR*) "Expected an array of options for the connection. Connection options must be passed as an array of "
        "key/value pairs.", -45, false }
    },

    {   
        SQLSRV_ERROR_QUERY_STRING_ENCODING_TRANSLATE,
        { IMSSP, (SQLCHAR*) "An error occurred translating the query string to UTF-16: %1!s!.", -46, true }   
    },
        
    {
        SQLSRV_ERROR_CONNECT_STRING_ENCODING_TRANSLATE,
        { IMSSP, (SQLCHAR*) "An error occurred translating the connection string to UTF-16: %1!s!", -47, true }
    },

    {
        SS_SQLSRV_ERROR_CONNECT_ILLEGAL_ENCODING,
        { IMSSP, (SQLCHAR*)"The encoding '%1!s!' is not a supported encoding for the CharacterSet connection option.", -48, true }
    },

    {
        SQLSRV_ERROR_DRIVER_NOT_INSTALLED,
        { IMSSP, (SQLCHAR*) "This extension requires the Microsoft SQL Server 2012 Native Client. "
        "Access the following URL to download the Microsoft SQL Server 2012 Native Client ODBC driver for %1!s!: "
        "http://go.microsoft.com/fwlink/?LinkId=163712", -49, true }
    },     

    {   
        SS_SQLSRV_ERROR_STATEMENT_NOT_SCROLLABLE,
        { IMSSP, (SQLCHAR*)"This function only works with statements that have static or keyset scrollable cursors.", -50, false }
    },
    
    {
        SS_SQLSRV_ERROR_STATEMENT_SCROLLABLE,
        { IMSSP, (SQLCHAR*)"This function only works with statements that are not scrollable.", -51, false }
    },

    // new error for 2.0, used here since 1.1 didn't have a -52
    {
        SQLSRV_ERROR_MAX_PARAMS_EXCEEDED, 
        { IMSSP, (SQLCHAR*) "Tried to bind parameter number %1!d!.  SQL Server supports a maximum of 2100 parameters.", -52, true }
    },

    {  
        SS_SQLSRV_ERROR_INVALID_FETCH_STYLE,
        { IMSSP, (SQLCHAR*)"The scroll type passed to sqlsrv_fetch, sqlsrv_fetch_array, or sqlsrv_fetch_object was not valid.  "
          "Please use one of the SQLSRV_SCROLL constants.", -53, false }
    },

    {
        SQLSRV_ERROR_INVALID_OPTION_SCROLLABLE,
        { IMSSP, (SQLCHAR*)"The value passed for the 'Scrollable' statement option is invalid.  Please use 'static', 'dynamic', "
          "'keyset', 'forward', or 'buffered'.", -54, false }
    },
 
    {
        SQLSRV_ERROR_UNKNOWN_SERVER_VERSION,
        { IMSSP, (SQLCHAR*)"Failed to retrieve the server version.  Unable to continue.", -55, false }
    },

    {
        SQLSRV_ERROR_INVALID_PARAMETER_ENCODING, 
        { IMSSP, (SQLCHAR*) "An invalid encoding was specified for parameter %1!d!.", -56, true }
    },

    {
        SS_SQLSRV_ERROR_PARAM_INVALID_INDEX,
        { IMSSP, (SQLCHAR*)"String keys are not allowed in parameters arrays.", -57, false }
    },

    {
        SQLSRV_ERROR_OUTPUT_PARAM_TRUNCATED, 
        { IMSSP, (SQLCHAR*) "String data, right truncated for output parameter %1!d!.", -58, true }
    },
    {
        SQLSRV_ERROR_BUFFER_LIMIT_EXCEEDED,
        { IMSSP, (SQLCHAR*) "Memory limit of %1!d! KB exceeded for buffered query", -59, true }
    },
    {
        SQLSRV_ERROR_INVALID_BUFFER_LIMIT,
        { IMSSP, (SQLCHAR*) "Setting for " INI_BUFFERED_QUERY_LIMIT " was non-int or non-positive.", -60, false }
    },

    // internal warning definitions
    {
        SS_SQLSRV_WARNING_FIELD_NAME_EMPTY,
        { SSPWARN, (SQLCHAR*)"An empty field name was skipped by sqlsrv_fetch_object.", -100, false }
    },

    // terminate the list of errors/warnings
    { -1, {} }
};

sqlsrv_error_const* get_error_message( unsigned int sqlsrv_error_code ) {
    
    sqlsrv_error_const *error_message = NULL;
    int zr = zend_hash_index_find( g_ss_errors_ht, sqlsrv_error_code, reinterpret_cast<void**>( &error_message ));
    if( zr == FAILURE ) {
        DIE( "get_error_message: zend_hash_index_find returned failure for sqlsrv_error_code = %1!d!", sqlsrv_error_code );   
    }
    
    SQLSRV_ASSERT( error_message != NULL, "get_error_message: error_message was null");

    return error_message;
}

// Formats an error message and finally writes it to the php log.
void ss_sqlsrv_log( unsigned int severity TSRMLS_DC, const char* msg, va_list* print_args )
{
    if(( severity & SQLSRV_G( log_severity )) && ( SQLSRV_G( current_subsystem ) & SQLSRV_G( log_subsystems ))) {

        DWORD rc = FormatMessage( FORMAT_MESSAGE_FROM_STRING, msg, 0, 0, log_msg, LOG_MSG_SIZE, print_args );
    
        // if an error occurs for FormatMessage, we just output an internal error occurred.
        if( rc == 0 ) {
            SQLSRV_STATIC_ASSERT( sizeof( INTERNAL_FORMAT_ERROR ) < sizeof( log_msg ));
            std::copy( INTERNAL_FORMAT_ERROR, INTERNAL_FORMAT_ERROR + sizeof( INTERNAL_FORMAT_ERROR ), log_msg );
        }

        php_log_err( log_msg TSRMLS_CC );
    }
}

bool ss_error_handler(sqlsrv_context& ctx, unsigned int sqlsrv_error_code, bool warning TSRMLS_DC, va_list* print_args )
{
    logging_severity severity = SEV_ERROR;
    if( warning && !SQLSRV_G( warnings_return_as_errors )) {
        severity = SEV_WARNING;
    }

    return handle_errors_and_warnings( ctx, &SQLSRV_G( errors ), &SQLSRV_G( warnings ), severity, sqlsrv_error_code, warning, 
                                       print_args TSRMLS_CC );
}

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

    LOG_FUNCTION( "sqlsrv_errors" );

    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "|l", &flags ) == FAILURE ) {

        LOG(SEV_ERROR, "An invalid parameter was passed to %1!s!.", _FN_ );
        RETURN_FALSE;
    }

    if( flags == SQLSRV_ERR_ALL ) {
        
        int result;
        zval_auto_ptr both_z;
        
        MAKE_STD_ZVAL( both_z );
        result = array_init( both_z );
        if( result == FAILURE ) {

            RETURN_FALSE;
        }

        if( Z_TYPE_P( SQLSRV_G( errors )) == IS_ARRAY && !sqlsrv_merge_zend_hash( both_z, SQLSRV_G( errors ) TSRMLS_CC )) {

            RETURN_FALSE;
        }
            
        if( Z_TYPE_P( SQLSRV_G( warnings )) == IS_ARRAY && !sqlsrv_merge_zend_hash( both_z, SQLSRV_G( warnings ) TSRMLS_CC )) {

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

    LOG_FUNCTION( "sqlsrv_configure" );

    char* option;
    int option_len;
    zval* value_z;
    sqlsrv_context_auto_ptr error_ctx;

    RETVAL_FALSE;

    reset_errors( TSRMLS_C );

    try {

        // dummy context to pass onto the error handler
        error_ctx = new ( sqlsrv_malloc( sizeof( sqlsrv_context ))) sqlsrv_context( 0, ss_error_handler, NULL );
        SET_FUNCTION_NAME( *error_ctx );
    
        int zr = zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "sz", &option, &option_len, &value_z );
        CHECK_CUSTOM_ERROR(( zr == FAILURE ), error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ ) {
            
            throw ss::SSException();
        }

        // WarningsReturnAsErrors
        if( !stricmp( option, INI_WARNINGS_RETURN_AS_ERRORS )) {

            SQLSRV_G( warnings_return_as_errors ) = zend_is_true( value_z ) ? true : false;
            LOG( SEV_NOTICE, INI_PREFIX INI_WARNINGS_RETURN_AS_ERRORS " = %1!s!", SQLSRV_G( warnings_return_as_errors ) ? "On" : "Off");
            RETURN_TRUE;
        }

        // LogSeverity
        else if( !stricmp( option, INI_LOG_SEVERITY )) {

            CHECK_CUSTOM_ERROR(( Z_TYPE_P( value_z ) != IS_LONG ), error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ ) {
            
                throw ss::SSException();
            }   
        
            long severity_mask = Z_LVAL_P( value_z );
            // make sure they can't use 0 to shut off the masking in the severity
            if( severity_mask < SEV_ALL || severity_mask == 0 || severity_mask > (SEV_NOTICE + SEV_ERROR + SEV_WARNING) ) {
                RETURN_FALSE;
            }

            SQLSRV_G( log_severity ) = static_cast<logging_severity>( severity_mask );
            LOG( SEV_NOTICE, INI_PREFIX INI_LOG_SEVERITY " = %1!d!", SQLSRV_G( log_severity ));
            RETURN_TRUE;
        }

        // LogSubsystems
        else if( !stricmp( option, INI_LOG_SUBSYSTEMS )) {

            CHECK_CUSTOM_ERROR(( Z_TYPE_P( value_z ) != IS_LONG ), error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ ) {

                throw ss::SSException();
            }
        
            long subsystem_mask = Z_LVAL_P( value_z );

            if( subsystem_mask < LOG_ALL || subsystem_mask > (LOG_INIT + LOG_CONN + LOG_STMT + LOG_UTIL) ) {
                RETURN_FALSE;
            }

            SQLSRV_G( log_subsystems ) = static_cast<logging_subsystems>( subsystem_mask );
            LOG( SEV_NOTICE, INI_PREFIX INI_LOG_SUBSYSTEMS " = %1!d!", SQLSRV_G( log_subsystems ));
            RETURN_TRUE;
        }

        else if( !stricmp( option, INI_BUFFERED_QUERY_LIMIT )) {
            
            CHECK_CUSTOM_ERROR(( Z_TYPE_P( value_z ) != IS_LONG ), error_ctx, SQLSRV_ERROR_INVALID_BUFFER_LIMIT, _FN_ ) {

                throw ss::SSException();
            }

            long buffered_query_limit = Z_LVAL_P( value_z );

            CHECK_CUSTOM_ERROR( buffered_query_limit < 0, error_ctx, SQLSRV_ERROR_INVALID_BUFFER_LIMIT, _FN_ ) {

                throw ss::SSException();
            }

            SQLSRV_G( buffered_query_limit ) = buffered_query_limit;
            LOG( SEV_NOTICE, INI_PREFIX INI_BUFFERED_QUERY_LIMIT " = %1!d!", SQLSRV_G( buffered_query_limit ));
            RETURN_TRUE;
        }

        else {

            THROW_CORE_ERROR( error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ );
        }
    }
    catch( core::CoreException& ) {

        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_configure: Unknown exception caught." );
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

    char* option = NULL;
    int option_len;
    sqlsrv_context_auto_ptr error_ctx;

    LOG_FUNCTION( "sqlsrv_get_config" );

    reset_errors( TSRMLS_C );

    try {
           
        // dummy context to pass onto the error handler
        error_ctx = new ( sqlsrv_malloc( sizeof( sqlsrv_context ))) sqlsrv_context( 0, ss_error_handler, NULL );
        SET_FUNCTION_NAME( *error_ctx );

        int zr = zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "s", &option, &option_len );
        CHECK_CUSTOM_ERROR(( zr == FAILURE ), error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ ) {

            throw ss::SSException();        
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
        else if( !stricmp( option, INI_BUFFERED_QUERY_LIMIT )) {

            ZVAL_LONG( return_value, SQLSRV_G( buffered_query_limit ));
            return;
        }
        else {
       
            THROW_CORE_ERROR( error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ );
        }
    }
    catch( core::CoreException& ) {
        
        RETURN_FALSE;
    }
    catch( ... ) {
    
        DIE( "sqlsrv_get_config: Unknown exception caught." );
    }
}


namespace {

void copy_error_to_zval( zval** error_z, sqlsrv_error_const* error, zval** reported_chain, zval** ignored_chain, 
                         bool warning TSRMLS_DC )
{
    MAKE_STD_ZVAL( *error_z );
    
    if( array_init( *error_z ) == FAILURE ) {
        DIE( "Fatal error during error processing" );
    }

    // sqlstate
    zval_auto_ptr temp;
    MAKE_STD_ZVAL( temp );
    ZVAL_STRINGL( temp, reinterpret_cast<char*>( error->sqlstate ), SQL_SQLSTATE_SIZE, 1 );
    zval_add_ref( &temp );
    if( add_next_index_zval( *error_z, temp ) == FAILURE ) {
        DIE( "Fatal error during error processing" );
    }

    if( add_assoc_zval( *error_z, "SQLSTATE", temp ) == FAILURE ) {
        DIE( "Fatal error during error processing" );
    }
    temp.transferred();

    // native_code
    if( add_next_index_long( *error_z,  error->native_code ) == FAILURE ) {
        DIE( "Fatal error during error processing" );
    }

    if( add_assoc_long( *error_z, "code", error->native_code ) == FAILURE ) {
        DIE( "Fatal error during error processing" );
    }

    // native_message
    MAKE_STD_ZVAL( temp );
    ZVAL_STRING( temp, reinterpret_cast<char*>( error->native_message), 1 );
    zval_add_ref( &temp );
    if( add_next_index_zval( *error_z, temp ) == FAILURE ) {
        DIE( "Fatal error during error processing" );
    }

    if( add_assoc_zval( *error_z, "message", temp ) == FAILURE ) {
        DIE( "Fatal error during error processing" );
    }
    temp.transferred();

    // If it is an error or if warning_return_as_errors is true than
    // add the error or warning to the reported_chain.
    if( !warning || SQLSRV_G( warnings_return_as_errors ) )
    {
        // if the warning is part of the ignored warning list than 
        // add to the ignored chain if the ignored chain is not null.
        if( warning && ignore_warning( reinterpret_cast<char*>( error->sqlstate ), error->native_code TSRMLS_CC ) && 
            ignored_chain != NULL ) {
            
            if( add_next_index_zval( *ignored_chain, *error_z ) == FAILURE ) {
                DIE( "Fatal error during error processing" );
            }          
        }
        else {

            // It is either an error or a warning which should not be ignored. 
            if( add_next_index_zval( *reported_chain, *error_z ) == FAILURE ) {
                DIE( "Fatal error during error processing" );
            }          
        }
    }
    else
    {
        // It is a warning with warning_return_as_errors as false, so simply add it to the ignored_chain list
        if( ignored_chain != NULL ) {
         
            if( add_next_index_zval( *ignored_chain, *error_z ) == FAILURE ) {
                DIE( "Fatal error during error processing" );
            }          
        }
    }
}

bool handle_errors_and_warnings( sqlsrv_context& ctx, zval** reported_chain, zval** ignored_chain, logging_severity log_severity, 
                                 unsigned int sqlsrv_error_code, bool warning, va_list* print_args TSRMLS_DC )
{
    bool result = true;
    bool errors_ignored = false;
    int prev_reported_cnt = 0;
    bool reported_chain_was_null = false;
    bool ignored_chain_was_null = false;
    int zr = SUCCESS;
    zval* error_z = NULL;
    sqlsrv_error_auto_ptr error;

    // array of reported errors
    if( Z_TYPE_P( *reported_chain ) == IS_NULL ) {

        reported_chain_was_null = true;
        zr = array_init( *reported_chain );
        if( zr == FAILURE ) {
            DIE( "Fatal error in handle_errors_and_warnings" );
        }
    }
    else {
        prev_reported_cnt = zend_hash_num_elements( Z_ARRVAL_PP( reported_chain ));
    }

    // array of ignored errors
    if( ignored_chain != NULL ) {
        
        if( Z_TYPE_P( *ignored_chain ) == IS_NULL ) {
            
           ignored_chain_was_null = true;
           zr = array_init( *ignored_chain );
            if( zr == FAILURE ) {
                DIE( "Fatal error in handle_errors_and_warnings" );
            }
        }
    }

    if( sqlsrv_error_code != SQLSRV_ERROR_ODBC ) {
        
        core_sqlsrv_format_driver_error( ctx, get_error_message( sqlsrv_error_code ), error, log_severity TSRMLS_CC, print_args );
        copy_error_to_zval( &error_z, error, reported_chain, ignored_chain, warning TSRMLS_CC );
    }
  
    SQLSMALLINT record_number = 0;
    do {

        result = core_sqlsrv_get_odbc_error( ctx, ++record_number, error, log_severity TSRMLS_CC );
        if( result ) {
            copy_error_to_zval( &error_z, error, reported_chain, ignored_chain, warning TSRMLS_CC );
        }
    } while( result );
    
    // If it were a warning, we report that warnings where ignored except if warnings_return_as_errors
    // was true and we added some warnings to the reported_chain.
    if( warning ) {

        errors_ignored = true;

        if( SQLSRV_G( warnings_return_as_errors ) ) {
            
            if( zend_hash_num_elements( Z_ARRVAL_PP( reported_chain )) > prev_reported_cnt ) {
                
                // We actually added some errors
                errors_ignored = false;
            }
        }
    }

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

    // If it was an error instead of a warning than we always return errors_ignored = false.
    return errors_ignored;
}

// return whether or not a warning should be ignored or returned as an error if WarningsReturnAsErrors is true
// see RINIT in init.cpp for information about which errors are ignored.
bool ignore_warning( char* sql_state, int native_code TSRMLS_DC )
{
    for( zend_hash_internal_pointer_reset( g_ss_warnings_to_ignore_ht );
         zend_hash_has_more_elements( g_ss_warnings_to_ignore_ht ) == SUCCESS;
         zend_hash_move_forward( g_ss_warnings_to_ignore_ht ) ) {

        void* error_v = NULL;
        
        if( zend_hash_get_current_data( g_ss_warnings_to_ignore_ht, (void**) &error_v ) == FAILURE ) {
            return false;
        }
        
        sqlsrv_error* error = static_cast<sqlsrv_error*>( error_v );
        if( !strncmp( reinterpret_cast<char*>( error->sqlstate ), sql_state, SQL_SQLSTATE_SIZE ) && 
            ( error->native_code == native_code || error->native_code == -1 )) {
                return true;
        }
    }
        
    return false;
}

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
         
        void* value_v;
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

} // namespace

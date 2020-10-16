//---------------------------------------------------------------------------------------------------------------------------------
// File: pdo_util.cpp
//
// Contents: Utility functions used by both connection or statement functions
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

extern "C" {
  #include "php_pdo_sqlsrv.h"
}

#include "php_pdo_sqlsrv_int.h"

#include "zend_exceptions.h"


// *** internal constants ***
namespace {

const char WARNING_TEMPLATE[] = "SQLSTATE: %1!s!\nError Code: %2!d!\nError Message: %3!s!\n";
const char EXCEPTION_MSG_TEMPLATE[] = "SQLSTATE[%s]: %s";
char EXCEPTION_PROPERTY_MSG[] = "message";
char EXCEPTION_PROPERTY_CODE[] = "code";
char EXCEPTION_PROPERTY_ERRORINFO[] = "errorInfo";
const int MAX_DIGITS = 11; // +-2 billion = 10 digits + 1 for the sign if negative

// the warning message is not the error message alone; it must take WARNING_TEMPLATE above into consideration without the formats
const int WARNING_MIN_LENGTH = static_cast<const int>( strlen( WARNING_TEMPLATE ) - strlen( "%1!s!%2!d!%3!s!" ));

// Returns a sqlsrv_error for a given error code.
sqlsrv_error_const* get_error_message( _In_opt_ unsigned int sqlsrv_error_code);

// build the object and throw the PDO exception
void pdo_sqlsrv_throw_exception(_In_ sqlsrv_error const* error);

void format_or_get_all_errors(_Inout_ sqlsrv_context& ctx, _In_opt_ unsigned int sqlsrv_error_code, _Inout_ sqlsrv_error_auto_ptr& error, _Inout_ char* error_code, _In_opt_ va_list* print_args);

void add_remaining_errors_to_array (_In_ sqlsrv_error const* error, _Inout_ zval* array_z);
}

// pdo driver error messages
// errors have 3 components, the SQLSTATE (always 'IMSSP'), the error message, and an error code, which for us is always < 0
pdo_error PDO_ERRORS[] = {
    
    {
        SQLSRV_ERROR_DRIVER_NOT_INSTALLED,
        { IMSSP, (SQLCHAR*) "This extension requires the Microsoft ODBC Driver for SQL Server to "
        "communicate with SQL Server. Access the following URL to download the ODBC Driver for SQL Server "
        "for %1!s!: "
        "https://go.microsoft.com/fwlink/?LinkId=163712", -1, true }
    },  
    {
        SQLSRV_ERROR_ZEND_HASH,
        { IMSSP, (SQLCHAR*) "An error occurred creating or accessing a Zend hash table.", -2, false }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_OUTPUT_PARAM_TYPE,
        { IMSSP, (SQLCHAR*) "An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams "
        "cannot be specified as output parameters.", -3, false }
    },
    {
        SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE,
        { IMSSP, (SQLCHAR*) "An invalid type for parameter %1!d! was specified.  Only booleans, integers, floating point "
          "numbers, strings, and streams may be used as parameters.", -4, true }
    },
    {
        SQLSRV_ERROR_INVALID_PARAMETER_SQLTYPE,
        { IMSSP, (SQLCHAR*) "An invalid SQL Server type for parameter %1!d! was specified.", -5, true }
    },
    {
        SQLSRV_ERROR_INVALID_PARAMETER_ENCODING,
        { IMSSP, (SQLCHAR*) "An invalid encoding was specified for parameter %1!d!.", -6, true }
    },
    {
        SQLSRV_ERROR_INPUT_PARAM_ENCODING_TRANSLATE,
        { IMSSP, (SQLCHAR*) "An error occurred translating string for input param %1!d! to UCS-2: %2!s!", -7, true }
    },
    {
        SQLSRV_ERROR_OUTPUT_PARAM_ENCODING_TRANSLATE,
        { IMSSP, (SQLCHAR*) "An error occurred translating string for an output param to UTF-8: %1!s!", -8, true }
    },
    {
        SQLSRV_ERROR_CONNECT_STRING_ENCODING_TRANSLATE,
        { IMSSP, (SQLCHAR*) "An error occurred translating the connection string to UTF-16: %1!s!", -9, true }
    },
    {
        SQLSRV_ERROR_ZEND_STREAM,
        { IMSSP, (SQLCHAR*) "An error occurred reading from a PHP stream.", -10, false }
    },
    {
        SQLSRV_ERROR_INPUT_STREAM_ENCODING_TRANSLATE,
        { IMSSP, (SQLCHAR*) "An error occurred translating a PHP stream from UTF-8 to UTF-16: %1!s!", -11, true }
    },
    {
        SQLSRV_ERROR_UNKNOWN_SERVER_VERSION,
        { IMSSP, (SQLCHAR*) "Failed to retrieve the server version.  Unable to continue.", -12, false }
    },
    {
        SQLSRV_ERROR_FETCH_PAST_END,
        { IMSSP, (SQLCHAR*) "There are no more rows in the active result set.  Since this result set is not scrollable, "
          "no more data may be retrieved.", -13, false }
    },
    {
        SQLSRV_ERROR_STATEMENT_NOT_EXECUTED,
        { IMSSP, (SQLCHAR*) "The statement must be executed before results can be retrieved.", -14, false }
    },
    {
        SQLSRV_ERROR_NO_FIELDS,
        { IMSSP, (SQLCHAR*) "The active result for the query contains no fields.", -15, false }
    },
 
    {
        SQLSRV_ERROR_FETCH_NOT_CALLED,
        { IMSSP, (SQLCHAR*) "Internal pdo_sqlsrv error: Tried to retrieve a field before one of the PDOStatement::fetch "
          "functions was called.", -16, false }
    },
    {
        SQLSRV_ERROR_NO_DATA,
        { IMSSP, (SQLCHAR*)"Field %1!d! returned no data.", -17, true }
    },
    {
        SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE,
        { IMSSP, (SQLCHAR*)"An error occurred translating string for a field to UTF-8: %1!s!", -18, true }
    },
    {
        SQLSRV_ERROR_ZEND_HASH_CREATE_FAILED,
        { IMSSP, (SQLCHAR*) "Zend returned an error when creating an associative array.", -19, false }
    },
    {
        SQLSRV_ERROR_NEXT_RESULT_PAST_END,
        { IMSSP, (SQLCHAR*)"There are no more results returned by the query.", -20, false }
    },
    {
        SQLSRV_ERROR_UID_PWD_BRACES_NOT_ESCAPED,
        { IMSSP, (SQLCHAR*) "An unescaped right brace (}) was found in either the user name or password.  All right braces must be"
        " escaped with another right brace (}}).", -21, false }
    },
    {
        SQLSRV_ERROR_UNESCAPED_RIGHT_BRACE_IN_DSN,
        { IMSSP, (SQLCHAR*) "An unescaped right brace (}) was found in the DSN string for keyword  '%1!s!'.  All right braces "
          "must be escaped with another right brace (}}).", -22, true }
    },
    {
        SQLSRV_ERROR_INVALID_OPTION_TYPE_INT,
        { IMSSP, (SQLCHAR*) "Invalid value type for option %1!s! was specified.  Integer type was expected.", -23, true }
    },
    {
        SQLSRV_ERROR_INVALID_OPTION_TYPE_STRING,
        { IMSSP, (SQLCHAR*) "Invalid value type for option %1!s! was specified.  String type was expected.", -24, true }
    },
    {
        SQLSRV_ERROR_CONN_OPTS_WRONG_TYPE,
        { IMSSP, (SQLCHAR*) "Expected an array of options for the connection. Connection options must be passed as an array of "
        "key/value pairs.", -25, false }
    },
    {
        SQLSRV_ERROR_INVALID_CONNECTION_KEY,
        { IMSSP, (SQLCHAR*) "An invalid connection option key type was received. Option key types must be strings.", -26, false }
    },
             
    {
        SQLSRV_ERROR_INVALID_TYPE,
        { IMSSP, (SQLCHAR*) "Invalid type.", -27, false }
    },

    {
        PDO_SQLSRV_ERROR_INVALID_COLUMN_INDEX,
        {IMSSP, (SQLCHAR*)"An invalid column number was specified.", -28, false }
    },

    {
        SQLSRV_ERROR_MAX_PARAMS_EXCEEDED,
        { IMSSP, (SQLCHAR*) "Tried to bind parameter number %1!d!.  SQL Server supports a maximum of 2100 parameters.", -29, true }
    },
    {
        SQLSRV_ERROR_INVALID_OPTION_KEY,
        { IMSSP, (SQLCHAR*) "Invalid option key %1!s! specified.", -30, true }
    },
    {
        SQLSRV_ERROR_INVALID_QUERY_TIMEOUT_VALUE,
        { IMSSP, (SQLCHAR*) "Invalid value %1!s! specified for option PDO::SQLSRV_ATTR_QUERY_TIMEOUT.", -31, true }
    },
    {
        SQLSRV_ERROR_INVALID_OPTION_SCROLLABLE,
        { IMSSP, (SQLCHAR*) "The value passed for the 'Scrollable' statement option is invalid.", -32, false }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_DBH_ATTR,
        { IMSSP, (SQLCHAR*) "An invalid attribute was designated on the PDO object.", -33, false }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_STMT_ATTR,
        { IMSSP, (SQLCHAR*) "An invalid attribute was designated on the PDOStatement object.", -34, false }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_ENCODING,
        { IMSSP, (SQLCHAR*) "An invalid encoding was specified for SQLSRV_ATTR_ENCODING.", -35, false }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_DRIVER_PARAM,
        { IMSSP, (SQLCHAR*) "An invalid type or value was given for the parameter driver data.  Only encoding constants "
          "such as PDO::SQLSRV_ENCODING_UTF8 may be used as parameter driver options.", -36, false }
    },
    {
        PDO_SQLSRV_ERROR_PDO_STMT_UNSUPPORTED,
        { IMSSP, (SQLCHAR*) "PDO::PARAM_STMT is not a supported parameter type.", -37, false }
    },
    {
        PDO_SQLSRV_ERROR_UNSUPPORTED_DBH_ATTR,
        { IMSSP, (SQLCHAR*) "An unsupported attribute was designated on the PDO object.", -38, false }
    },
    {
        PDO_SQLSRV_ERROR_STMT_LEVEL_ATTR,
        { IMSSP, (SQLCHAR*) "The given attribute is only supported on the PDOStatement object.", -39, false }
    },
    {
        PDO_SQLSRV_ERROR_READ_ONLY_DBH_ATTR,
        { IMSSP, (SQLCHAR*) "A read-only attribute was designated on the PDO object.", -40, false }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_DSN_STRING,
        {IMSSP, (SQLCHAR*)"An invalid DSN string was specified.", -41, false }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_DSN_KEY,
        { IMSSP, (SQLCHAR*) "An invalid keyword '%1!s!' was specified in the DSN string.", -42, true }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_STMT_OPTION,
        { IMSSP, (SQLCHAR*) "An invalid statement option was specified.", -43, false }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_CURSOR_TYPE,
        { IMSSP, (SQLCHAR*) "An invalid cursor type was specified for either PDO::ATTR_CURSOR or "
          "PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE", -44, false }
    },
    {
        PDO_SQLSRV_ERROR_PARAM_PARSE,
        { IMSSP, (SQLCHAR*) "An error occurred substituting the named parameters.", -45, false }
    },
    {
        PDO_SQLSRV_ERROR_LAST_INSERT_ID,
        { IMSSP, (SQLCHAR*) "An error occurred retrieving the last insert id.", -46, false }
    },
    {
        SQLSRV_ERROR_QUERY_STRING_ENCODING_TRANSLATE,
        { IMSSP, (SQLCHAR*) "An error occurred translating the query string to UTF-16: %1!s!.", -47, true }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_COLUMN_DRIVER_DATA,
        { IMSSP, (SQLCHAR*) "An invalid type or value was given as bound column driver data for column %1!d!.  Only "
          "encoding constants such as PDO::SQLSRV_ENCODING_UTF8 may be used as bound column driver data.", -48, true }
    },
    {
        PDO_SQLSRV_ERROR_COLUMN_TYPE_DOES_NOT_SUPPORT_ENCODING,
        { IMSSP, (SQLCHAR*) "An encoding was specified for column %1!d!.  Only PDO::PARAM_LOB and PDO::PARAM_STR column types "
          "can take an encoding option.", -49, true }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_DRIVER_COLUMN_ENCODING,
        { IMSSP, (SQLCHAR*) "Invalid encoding specified for column %1!d!.", -50, true }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_DRIVER_PARAM_TYPE,
        { IMSSP, (SQLCHAR*) "An encoding was specified for parameter %1!d!.  Only PDO::PARAM_LOB and PDO::PARAM_STR can take an "
          "encoding option.", -51, true }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_DRIVER_PARAM_ENCODING,
        { IMSSP, (SQLCHAR*) "Invalid encoding specified for parameter %1!d!.", -52, true }
    },
    {
        PDO_SQLSRV_ERROR_CURSOR_ATTR_AT_PREPARE_ONLY,
        { IMSSP, (SQLCHAR*) "The PDO::ATTR_CURSOR and PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE attributes may only be set in the "
          "$driver_options array of PDO::prepare.", -53, false }
    },
    {
        SQLSRV_ERROR_OUTPUT_PARAM_TRUNCATED,
        { IMSSP, (SQLCHAR*) "String data, right truncated for output parameter %1!d!.", -54, true }
    },
    {
        SQLSRV_ERROR_INPUT_OUTPUT_PARAM_TYPE_MATCH,
        { IMSSP, (SQLCHAR*) "Types for parameter value and PDO::PARAM_* constant must be compatible for input/output "
          "parameter %1!d!.", -55, true }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_PARAM_DIRECTION,
        { IMSSP, (SQLCHAR*) "Invalid direction specified for parameter %1!d!.  Input/output parameters must have a length.",
          -56, true }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_OUTPUT_STRING_SIZE,
        { IMSSP, (SQLCHAR*) "Invalid size for output string parameter %1!d!.  Input/output string parameters must have an "
          "explicit length.", -57, true }
    },
    {
        PDO_SQLSRV_ERROR_FUNCTION_NOT_IMPLEMENTED,
        { IMSSP, (SQLCHAR*) "This function is not implemented by this driver.", -58, false }
    },      
    {
        /* The stream related errors are not currently used in PDO, but the core layer can throw the stream related 
           errors so having a mapping here */

        SQLSRV_ERROR_STREAMABLE_TYPES_ONLY,
        { IMSSP, (SQLCHAR*) "Only char, nchar, varchar, nvarchar, binary, varbinary, and large object types can be read by using "
          "streams.", -59, false}
    },
    {
        SQLSRV_ERROR_STREAM_CREATE,
        { IMSSP, (SQLCHAR*)"An error occurred while retrieving a SQL Server field as a stream.", -60, false }
    },
    {
        SQLSRV_ERROR_MARS_OFF,
        { IMSSP, (SQLCHAR*)"The connection cannot process this operation because there is a statement with pending results.  "
          "To make the connection available for other queries, either fetch all results or cancel or free the statement.  "
          "For more information, see the product documentation about the MultipleActiveResultSets connection option.", -61, false }
    },
    {
        SQLSRV_ERROR_FIELD_INDEX_ERROR,
        { IMSSP, (SQLCHAR*)"Fields within a row must be accessed in ascending order.  Cannot retrieve field %1!d! because its "
          "index is less than the index of a field that has already been retrieved (%2!d!).", -62, true }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_DSN_VALUE,
        { IMSSP, (SQLCHAR*) "An invalid value was specified for the keyword '%1!s!' in the DSN string.", -63, true }
    },
    {
        PDO_SQLSRV_ERROR_SERVER_NOT_SPECIFIED,
        { IMSSP, (SQLCHAR*) "Server keyword was not specified in the DSN string.", -64, false }
    },
    {
        PDO_SQLSRV_ERROR_DSN_STRING_ENDED_UNEXPECTEDLY,
        { IMSSP, (SQLCHAR*) "The DSN string ended unexpectedly.", -65, false }
    },
    {
        PDO_SQLSRV_ERROR_EXTRA_SEMI_COLON_IN_DSN_STRING,
        { IMSSP, (SQLCHAR*) "An extra semi-colon was encountered in the DSN string at character (byte-count) position '%1!d!' .",
          -66, true }
    },
    {
        PDO_SQLSRV_ERROR_RCB_MISSING_IN_DSN_VALUE,
        { IMSSP, (SQLCHAR*) "An expected right brace (}) was not found in the DSN string for the value of the keyword '%1!s!'.",
          -67, true }
    },
    {
        PDO_SQLSRV_ERROR_DQ_ATTR_AT_PREPARE_ONLY,
        { IMSSP, (SQLCHAR*) "The PDO::SQLSRV_ATTR_DIRECT_QUERY attribute may only be set in the $driver_options array of "
          "PDO::prepare.", -68, false }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_CURSOR_WITH_SCROLL_TYPE,
        { IMSSP, (SQLCHAR*) "The PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE attribute may only be set when PDO::ATTR_CURSOR is set to "
          "PDO::CURSOR_SCROLL in the $driver_options array of PDO::prepare.", -69, false }
    },
    {
        SQLSRV_ERROR_INVALID_BUFFER_LIMIT,
        { IMSSP, (SQLCHAR*) "The PDO::SQLSRV_ATTR_CLIENT_BUFFER_MAX_KB_SIZE attribute is not a number or the number is not "
          "positive. Only positive numbers are valid for this attribute.", -70, false }
    },
    {
        SQLSRV_ERROR_BUFFER_LIMIT_EXCEEDED,
        { IMSSP, (SQLCHAR*) "Memory limit of %1!d! KB exceeded for buffered query", -71, true }
    },
    {
        PDO_SQLSRV_ERROR_EMULATE_INOUT_UNSUPPORTED,
        { IMSSP, (SQLCHAR*) "Statement with emulate prepare on does not support output or input_output parameters.", -72, false }
    },
    {
        PDO_SQLSRV_ERROR_INVALID_AUTHENTICATION_OPTION,
        { IMSSP, (SQLCHAR*) "Invalid option for the Authentication keyword. Only SqlPassword, ActiveDirectoryPassword, ActiveDirectoryMsi or ActiveDirectorySPA is supported.", -73, false }
    },
    {
        SQLSRV_ERROR_CE_DRIVER_REQUIRED,
        { IMSSP, (SQLCHAR*) "The Always Encrypted feature requires Microsoft ODBC Driver 17 for SQL Server.", -78, false }
    },
    {
        SQLSRV_ERROR_CONNECT_INVALID_DRIVER,
        { IMSSP, (SQLCHAR*) "Invalid value %1!s! was specified for Driver option.", -79, true }
    },
    {
        SQLSRV_ERROR_SPECIFIED_DRIVER_NOT_FOUND,
        { IMSSP, (SQLCHAR*) "The specified ODBC Driver is not found.", -80, false }
    },
    {
        PDO_SQLSRV_ERROR_CE_DIRECT_QUERY_UNSUPPORTED,
        { IMSSP, (SQLCHAR*) "Parameterized statement with attribute PDO::SQLSRV_ATTR_DIRECT_QUERY is not supported in a Column Encryption enabled Connection.", -81, false }
    },
    {
        PDO_SQLSRV_ERROR_CE_EMULATE_PREPARE_UNSUPPORTED,
        { IMSSP, (SQLCHAR*) "Parameterized statement with attribute PDO::ATTR_EMULATE_PREPARES is not supported in a Column Encryption enabled Connection.", -82, false }
    },
    {
        SQLSRV_ERROR_OUTPUT_PARAM_TYPES_NOT_SUPPORTED,
        { IMSSP, (SQLCHAR*) "Stored Procedures do not support text, ntext or image as OUTPUT parameters.", -83, false }
    },
    {
        SQLSRV_ERROR_DOUBLE_CONVERSION_FAILED,
        { IMSSP, (SQLCHAR*) "Error converting a double (value out of range) to an integer.", -84, false }
    },
    {
        SQLSRV_ERROR_INVALID_AKV_AUTHENTICATION_OPTION,
        { IMSSP, (SQLCHAR*) "Invalid option for the KeyStoreAuthentication keyword. Only KeyVaultPassword or KeyVaultClientSecret is allowed.", -85, false }
    },
    {
        SQLSRV_ERROR_AKV_AUTH_MISSING,
        { IMSSP, (SQLCHAR*) "The authentication method for Azure Key Vault is missing. KeyStoreAuthentication must be set to KeyVaultPassword or KeyVaultClientSecret.", -86, false }
    },
    {
        SQLSRV_ERROR_AKV_NAME_MISSING,
        { IMSSP, (SQLCHAR*) "The username or client Id for Azure Key Vault is missing.", -87, false }
    },
    {
        SQLSRV_ERROR_AKV_SECRET_MISSING,
        { IMSSP, (SQLCHAR*) "The password or client secret for Azure Key Vault is missing.", -88, false }
    },
    {
        SQLSRV_ERROR_KEYSTORE_INVALID_VALUE,
        { IMSSP, (SQLCHAR*) "Invalid value for loading Azure Key Vault.", -89, false}
    },
    {
        SQLSRV_ERROR_INVALID_OPTION_WITH_ACCESS_TOKEN,
        { IMSSP, (SQLCHAR*) "When using Azure AD Access Token, the connection string must not contain UID, PWD, or Authentication keywords.", -90, false}
    },
    {
        SQLSRV_ERROR_EMPTY_ACCESS_TOKEN,
        { IMSSP, (SQLCHAR*) "The Azure AD Access Token is empty. Expected a byte string.", -91, false}
    },
    {
        SQLSRV_ERROR_INVALID_DECIMAL_PLACES,
        { IMSSP, (SQLCHAR*) "Expected an integer to specify number of decimals to format the output values of decimal data types.", -92, false}
    },
    {
        SQLSRV_ERROR_AAD_MSI_UID_PWD_NOT_NULL,
        { IMSSP, (SQLCHAR*) "When using ActiveDirectoryMsi Authentication, PWD must be NULL. UID can be NULL, but if not, an empty string is not accepted.", -93, false}
    },
    {
        SQLSRV_ERROR_DATA_CLASSIFICATION_PRE_EXECUTION,
        { IMSSP, (SQLCHAR*) "The statement must be executed to retrieve Data Classification Sensitivity Metadata.", -94, false}
    },
    {
        SQLSRV_ERROR_DATA_CLASSIFICATION_NOT_AVAILABLE,
        { IMSSP, (SQLCHAR*) "Failed to retrieve Data Classification Sensitivity Metadata. If the driver and the server both support the Data Classification feature, check whether the query returns columns with classification information.", -95, false}
    },
    {
        SQLSRV_ERROR_DATA_CLASSIFICATION_FAILED,
        { IMSSP, (SQLCHAR*) "Failed to retrieve Data Classification Sensitivity Metadata: %1!s!", -96, true}
    },
    {
        PDO_SQLSRV_ERROR_EXTENDED_STRING_TYPE_INVALID,
        { IMSSP, (SQLCHAR*) "Invalid extended string type specified. PDO_ATTR_DEFAULT_STR_PARAM can be either PDO_PARAM_STR_CHAR or PDO_PARAM_STR_NATL.", -97, false}
    },

    { UINT_MAX, {} }
};

bool pdo_sqlsrv_handle_env_error( _Inout_ sqlsrv_context& ctx, _In_opt_ unsigned int sqlsrv_error_code, _In_opt_ int warning, 
                                  _In_opt_ va_list* print_args )
{
    SQLSRV_ASSERT((ctx != NULL), "pdo_sqlsrv_handle_env_error: sqlsrv_context was null");
    pdo_dbh_t* dbh = reinterpret_cast<pdo_dbh_t*>(ctx.driver());
    SQLSRV_ASSERT((dbh != NULL), "pdo_sqlsrv_handle_env_error: pdo_dbh_t was null");

    sqlsrv_error_auto_ptr error;
    format_or_get_all_errors(ctx, sqlsrv_error_code, error, dbh->error_code, print_args);

    // error_mode is valid because PDO API has already taken care of invalid ones
    if (!warning && dbh->error_mode == PDO_ERRMODE_EXCEPTION) {
        pdo_sqlsrv_throw_exception(error);
    }

    ctx.set_last_error(error);

    // we don't transfer the zval_auto_ptr since set_last_error increments the zval ref count
    // return error ignored = true for warnings.
    return (warning ? true : false);
}

// pdo error handler for the dbh context.
bool pdo_sqlsrv_handle_dbh_error( _Inout_ sqlsrv_context& ctx, _In_opt_ unsigned int sqlsrv_error_code, _In_opt_ int warning, 
                                  _In_opt_ va_list* print_args )
{
    pdo_dbh_t* dbh = reinterpret_cast<pdo_dbh_t*>( ctx.driver());
    SQLSRV_ASSERT( dbh != NULL, "pdo_sqlsrv_handle_dbh_error: Null dbh passed" );

    sqlsrv_error_auto_ptr error;
    format_or_get_all_errors(ctx, sqlsrv_error_code, error, dbh->error_code, print_args);

    // error_mode is valid because PDO API has already taken care of invalid ones
    if (!warning) {
        if (dbh->error_mode == PDO_ERRMODE_EXCEPTION) {
            pdo_sqlsrv_throw_exception(error);
        }
        else if (dbh->error_mode == PDO_ERRMODE_WARNING) {
            size_t msg_len = strnlen_s(reinterpret_cast<const char*>(error->native_message)) + SQL_SQLSTATE_BUFSIZE
                + MAX_DIGITS + WARNING_MIN_LENGTH + 1;
            sqlsrv_malloc_auto_ptr<char> msg;
            msg = static_cast<char*>(sqlsrv_malloc(msg_len));
            core_sqlsrv_format_message(msg, static_cast<unsigned int>(msg_len), WARNING_TEMPLATE, error->sqlstate, error->native_code,
                error->native_message);
            php_error(E_WARNING, "%s", msg.get());
        }
    }

    ctx.set_last_error(error);

    // return error ignored = true for warnings.
    return (warning ? true : false);
}

// PDO error handler for the statement context.
bool pdo_sqlsrv_handle_stmt_error(_Inout_ sqlsrv_context& ctx, _In_opt_ unsigned int sqlsrv_error_code, _In_opt_ int warning,
    _In_opt_ va_list* print_args)
{
    pdo_stmt_t* pdo_stmt = reinterpret_cast<pdo_stmt_t*>(ctx.driver());
    SQLSRV_ASSERT(pdo_stmt != NULL && pdo_stmt->dbh != NULL, "pdo_sqlsrv_handle_stmt_error: Null statement or dbh passed");

    sqlsrv_error_auto_ptr error;
    format_or_get_all_errors(ctx, sqlsrv_error_code, error, pdo_stmt->error_code, print_args);

    // error_mode is valid because PDO API has already taken care of invalid ones
    if (!warning && pdo_stmt->dbh->error_mode == PDO_ERRMODE_EXCEPTION) {
        pdo_sqlsrv_throw_exception(error);
    }
    ctx.set_last_error(error);

    // return error ignored = true for warnings.
    return (warning ? true : false);
}

// Transfer a sqlsrv_context's error to a PDO zval.  The standard format for a zval error is 3 elements:
// 0, native code
// 1, native message
// 2, SQLSTATE of the error (driver specific error messages are 'IMSSP')

void pdo_sqlsrv_retrieve_context_error( _In_ sqlsrv_error const* last_error, _Out_ zval* pdo_zval )
{
    if( last_error ) {
        // SQLSTATE is already present in the zval.
        add_next_index_long( pdo_zval, last_error->native_code );
        add_next_index_string( pdo_zval, reinterpret_cast<char*>( last_error->native_message ));

        add_remaining_errors_to_array (last_error, pdo_zval);
    }
}

// check the global variable of pdo_sqlsrv severity whether the message qualifies to be logged with the LOG macro
bool pdo_severity_check(_In_ unsigned int severity)
{
    return ((severity & PDO_SQLSRV_G(pdo_log_severity)));
}

namespace {

// Workaround for name collision problem between the SQLSRV and PDO_SQLSRV drivers on Mac
// Place get_error_message into the anonymous namespace in pdo_util.cpp
sqlsrv_error_const* get_error_message( _In_opt_ unsigned int sqlsrv_error_code) {

    sqlsrv_error_const *error_message = NULL;
    int zr = (error_message = reinterpret_cast<sqlsrv_error_const*>(zend_hash_index_find_ptr(g_pdo_errors_ht, sqlsrv_error_code))) != NULL ? SUCCESS : FAILURE;
    if (zr == FAILURE) {
        DIE("get_error_message: zend_hash_index_find returned failure for sqlsrv_error_code = %1!d!", sqlsrv_error_code);
    }

    SQLSRV_ASSERT(error_message != NULL, "get_error_message: error_message was null");

    return error_message;
}

void pdo_sqlsrv_throw_exception(_In_ sqlsrv_error const* error)
{
    zval ex_obj;
    ZVAL_UNDEF( &ex_obj );

    zend_class_entry* ex_class = php_pdo_get_exception();

    int zr = object_init_ex( &ex_obj, ex_class );
    SQLSRV_ASSERT( zr != FAILURE, "Failed to initialize exception object" );

#if PHP_VERSION_ID >= 80000
    zend_object *zendobj = Z_OBJ_P(&ex_obj);
#endif

    sqlsrv_malloc_auto_ptr<char> ex_msg;
    size_t ex_msg_len = strnlen_s(reinterpret_cast<const char*>(error->native_message)) + SQL_SQLSTATE_BUFSIZE +
        12 + 1; // 12 = "SQLSTATE[]: "
    ex_msg = reinterpret_cast<char*>(sqlsrv_malloc(ex_msg_len));
    snprintf(ex_msg, ex_msg_len, EXCEPTION_MSG_TEMPLATE, error->sqlstate, error->native_message);

#if PHP_VERSION_ID < 80000
    zend_update_property_string(ex_class, &ex_obj, EXCEPTION_PROPERTY_MSG, sizeof(EXCEPTION_PROPERTY_MSG) - 1, ex_msg);
    zend_update_property_string(ex_class, &ex_obj, EXCEPTION_PROPERTY_CODE, sizeof(EXCEPTION_PROPERTY_CODE) - 1, reinterpret_cast<char*>(error->sqlstate));
#else
    zend_update_property_string(ex_class, zendobj, EXCEPTION_PROPERTY_MSG, sizeof(EXCEPTION_PROPERTY_MSG) - 1, ex_msg);
    zend_update_property_string(ex_class, zendobj, EXCEPTION_PROPERTY_CODE, sizeof(EXCEPTION_PROPERTY_CODE) - 1, reinterpret_cast<char*>(error->sqlstate));
#endif

    zval ex_error_info;
    ZVAL_UNDEF( &ex_error_info );
    array_init( &ex_error_info );
    add_next_index_string( &ex_error_info, reinterpret_cast<char*>( error->sqlstate ));
    add_next_index_long( &ex_error_info, error->native_code );
    add_next_index_string( &ex_error_info, reinterpret_cast<char*>( error->native_message ));

    add_remaining_errors_to_array (error, &ex_error_info);

    //zend_update_property makes an entry in the properties_table in ex_obj point to the Z_ARRVAL( ex_error_info )
    //and the refcount of the zend_array is incremented by 1
#if PHP_VERSION_ID < 80000
    zend_update_property(ex_class, &ex_obj, EXCEPTION_PROPERTY_ERRORINFO, sizeof(EXCEPTION_PROPERTY_ERRORINFO) - 1, &ex_error_info);
#else
    zend_update_property(ex_class, zendobj, EXCEPTION_PROPERTY_ERRORINFO, sizeof(EXCEPTION_PROPERTY_ERRORINFO) - 1, &ex_error_info);
#endif

    //DELREF ex_error_info here to decrement the refcount of the zend_array is 1
    //the global hashtable EG(exception) then points to the zend_object in ex_obj in zend_throw_exception_object;
    //this ensure when EG(exception) cleans itself at php shutdown, the zend_array allocated is properly destroyed
    Z_DELREF( ex_error_info );
    zend_throw_exception_object( &ex_obj );
}

void add_remaining_errors_to_array (_In_ sqlsrv_error const* error, _Inout_ zval* array_z)
{
    if (error->next != NULL && PDO_SQLSRV_G(report_additional_errors)) {
        sqlsrv_error *p = error->next;
        while (p != NULL) {
            add_next_index_string(array_z, reinterpret_cast<char*>(p->sqlstate));
            add_next_index_long(array_z, p->native_code);
            add_next_index_string(array_z, reinterpret_cast<char*>(p->native_message));

            p = p-> next;
        }
    }
}

void format_or_get_all_errors(_Inout_ sqlsrv_context& ctx, _In_opt_ unsigned int sqlsrv_error_code, _Inout_ sqlsrv_error_auto_ptr& error, _Inout_ char* error_code, _In_opt_ va_list* print_args)
{
    if (sqlsrv_error_code != SQLSRV_ERROR_ODBC) {
        core_sqlsrv_format_driver_error(ctx, get_error_message(sqlsrv_error_code), error, SEV_ERROR, print_args);
        strcpy_s(error_code, sizeof(pdo_error_type), reinterpret_cast<const char*>(error->sqlstate));
    }
    else {
        bool result = core_sqlsrv_get_odbc_error(ctx, 1, error, SEV_ERROR, true);
        if (result) {
            // Check if there exist more errors
            int rec_number = 2;
            sqlsrv_error_auto_ptr err;
            sqlsrv_error *p = error;

            do {
                result = core_sqlsrv_get_odbc_error(ctx, rec_number++, err, SEV_ERROR, true);
                if (result) {
                    p->next = err.get();
                    err.transferred();
                    p = p->next;
                }
            } while (result);
        }
        
        // core_sqlsrv_get_odbc_error() returns the error_code of size SQL_SQLSTATE_BUFSIZE,
        // which is the same size as pdo_error_type
        strcpy_s(error_code, sizeof(pdo_error_type), reinterpret_cast<const char*>(error->sqlstate));
    }
}

}

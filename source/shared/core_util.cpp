//---------------------------------------------------------------------------------------------------------------------------------
// File: core_util.cpp
//
// Contents: Utility functions used by both connection or statement functions for both the PDO and sqlsrv drivers
// 
// Comments: Mostly error handling and some type handling
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

namespace {

severity_callback g_driver_severity;

// *** internal constants ***

// buffer used to hold a formatted log message prior to actually logging it.
const int LOG_MSG_SIZE = 2048;

// internal error that says that FormatMessage failed
SQLCHAR INTERNAL_FORMAT_ERROR[] = "An internal error occurred.  FormatMessage failed writing an error message.";

// buffer used to hold a formatted log message prior to actually logging it.
char last_err_msg[2048] = {'\0'};  // 2k to hold the error messages

// routine used by utf16_string_from_mbcs_string
unsigned int convert_string_from_default_encoding( _In_ unsigned int php_encoding, _In_reads_bytes_(mbcs_len) char const* mbcs_in_string,
                                                   _In_ unsigned int mbcs_len, 
                                                   _Out_writes_(utf16_len) __transfer( mbcs_in_string ) SQLWCHAR* utf16_out_string,
                                                   _In_ unsigned int utf16_len, bool use_strict_conversion = false );

// invoked by write_to_log() when the message severity qualifies to be logged
// msg - the message to log in a FormatMessage style formatting
// print_args - args to the message
void log_activity(_In_opt_ const char* msg, _In_opt_ va_list* print_args)
{
    char log_msg[LOG_MSG_SIZE] = { '\0' };

    DWORD rc = FormatMessage(FORMAT_MESSAGE_FROM_STRING, msg, 0, 0, log_msg, LOG_MSG_SIZE, print_args);

    // if an error occurs for FormatMessage, we just output an internal error occurred.
    if (rc == 0) {
        SQLSRV_STATIC_ASSERT(sizeof(INTERNAL_FORMAT_ERROR) < sizeof(log_msg));
        std::copy(INTERNAL_FORMAT_ERROR, INTERNAL_FORMAT_ERROR + sizeof(INTERNAL_FORMAT_ERROR), log_msg);
    }

    php_log_err(log_msg);
}

}

// SQLSTATE for all internal errors 
SQLCHAR IMSSP[] = "IMSSP";

// SQLSTATE for all internal warnings
SQLCHAR SSPWARN[] = "01SSP";

// write to the php log if the severity and subsystem match the filters currently set in the INI or 
// the script (sqlsrv_configure).
void write_to_log( _In_ unsigned int severity, _In_ const char* msg, ...)
{
    SQLSRV_ASSERT( !(g_driver_severity == NULL), "Must register a driver checker function." );
    if (!g_driver_severity(severity)) {
        return;
    }

    va_list args;
    va_start( args, msg );

    log_activity(msg, &args);

    va_end( args );
}

void core_sqlsrv_register_severity_checker(_In_ severity_callback driver_checker)
{
    g_driver_severity = driver_checker;
}

// convert a string from utf-16 to the encoding and return the new string in the pointer parameter and new
// length in the len parameter.  If no errors occurred during convertion, true is returned and the original
// utf-16 string is released by this function if no errors occurred.  Otherwise the parameters are not changed
// and false is returned.

bool convert_string_from_utf16_inplace( _In_ SQLSRV_ENCODING encoding, _Inout_updates_z_(len) char** string, _Inout_ SQLLEN& len)
{
    SQLSRV_ASSERT( string != NULL, "String must be specified" );

	if (validate_string(*string, len)) {
		return true;
	}

    char* outString = NULL;
    SQLLEN outLen = 0;

    bool result = convert_string_from_utf16( encoding, reinterpret_cast<const SQLWCHAR*>(*string), int(len / sizeof(SQLWCHAR)), &outString, outLen );

    if (result)
    {
        sqlsrv_free( *string );
        *string = outString;
        len = outLen;
    }

    return result;
}

bool validate_string( _In_ char* string, _In_ SQLLEN& len )
{
     SQLSRV_ASSERT(string != NULL, "String must be specified");

     //for the empty string, we simply returned we converted it
     if( len == 0 && string[0] == '\0') {
         return true;
     }
    if ((len / sizeof(SQLWCHAR)) > INT_MAX) {
        LOG(SEV_ERROR, "UTP-16 (wide character) string mapping: buffer length exceeded.");
        throw core::CoreException();
    }
    return false;
}

bool convert_string_from_utf16( _In_ SQLSRV_ENCODING encoding, _In_reads_bytes_(cchInLen) const SQLWCHAR* inString, _In_ SQLINTEGER cchInLen, _Inout_updates_bytes_(cchOutLen) char** outString, _Out_ SQLLEN& cchOutLen )
{
    SQLSRV_ASSERT( inString != NULL, "Input string must be specified" );
    SQLSRV_ASSERT( outString != NULL, "Output buffer pointer must be specified" );
    SQLSRV_ASSERT( *outString == NULL, "Output buffer pointer must not be set" );

    if (cchInLen == 0 && inString[0] == L'\0') {
        *outString = reinterpret_cast<char*>( sqlsrv_malloc ( 1 ) );
        *outString[0] = '\0';
        cchOutLen = 0;
        return true;
    }

#ifndef _WIN32
    // Allocate enough space to hold the largest possible number of bytes for UTF-8 conversion
    // instead of calling FromUtf16, for performance reasons
    cchOutLen = 4 * cchInLen;
#else	
    // flags set to 0 by default, which means that any invalid characters are dropped rather than causing
    // an error.   This happens only on XP.
    DWORD flags = 0;
    if( encoding == CP_UTF8 && isVistaOrGreater ) {
        // Vista (and later) will detect invalid UTF-16 characters and raise an error.
        flags = WC_ERR_INVALID_CHARS;
    }

    // Calculate the number of output bytes required - no performance hit here because
    // WideCharToMultiByte is highly optimised
    cchOutLen = WideCharToMultiByte( encoding, flags,
                                   inString, cchInLen, 
                                   NULL, 0, NULL, NULL );
#endif // !_WIN32   
	
    if( cchOutLen == 0 ) {
        return false;
    }

    // Create a buffer to fit the encoded string
    char* newString = reinterpret_cast<char*>( sqlsrv_malloc( cchOutLen + 1 /* NULL char*/ ));
    memset(newString, '\0', cchOutLen+1);
    
#ifndef _WIN32
    int rc = SystemLocale::FromUtf16Strict( encoding, inString, cchInLen, newString, static_cast<int>(cchOutLen));
#else
    int rc = WideCharToMultiByte( encoding, flags, inString, cchInLen, newString, static_cast<int>(cchOutLen), NULL, NULL );
#endif // !_WIN32
    if( rc == 0 ) {
        cchOutLen = 0;
        sqlsrv_free( newString );
        return false;
    }
    char* newString2 = reinterpret_cast<char*>( sqlsrv_malloc( rc + 1 /* NULL char*/ ));
    memset(newString2, '\0', rc+1);
    memcpy_s(newString2, rc, newString, rc);
    sqlsrv_free( newString );

    *outString = newString2;
    cchOutLen = rc;

    return true;
}

// thin wrapper around convert_string_from_default_encoding that handles
// allocation of the destination string.  An empty string passed in returns
// failure since it's a failure case for convert_string_from_default_encoding.
SQLWCHAR* utf16_string_from_mbcs_string( _In_ SQLSRV_ENCODING php_encoding, _In_reads_bytes_(mbcs_len) const char* mbcs_string, _In_ unsigned int mbcs_len,
                                        _Out_ unsigned int* utf16_len, bool use_strict_conversion )
{
    *utf16_len = (mbcs_len + 1);
    SQLWCHAR* utf16_string = reinterpret_cast<SQLWCHAR*>( sqlsrv_malloc( *utf16_len * sizeof( SQLWCHAR )));
    *utf16_len = convert_string_from_default_encoding( php_encoding, mbcs_string, mbcs_len, utf16_string, *utf16_len, use_strict_conversion );

    if( *utf16_len == 0 ) {
        // we preserve the error and reset it because sqlsrv_free resets the last error
        DWORD last_error = GetLastError();
        sqlsrv_free( utf16_string );
        SetLastError( last_error );
        return NULL;
    }

    return utf16_string;
}

// Converts an input (assuming a datetime string) to a zval containing a PHP DateTime object. 
// If the input is null, this simply returns a NULL zval. If anything wrong occurs during conversion,
// an exception will be thrown.
void convert_datetime_string_to_zval(_Inout_ sqlsrv_stmt* stmt, _In_opt_ char* input, _In_ SQLLEN length, _Inout_ zval& out_zval)
{
    if (input == NULL) {
        ZVAL_NULL(&out_zval);
        return;
    }

    zval params[1];
    zval value_temp_z;
    zval function_z;

    // Initialize all zval variables
    ZVAL_UNDEF(&out_zval);
    ZVAL_UNDEF(&value_temp_z);
    ZVAL_UNDEF(&function_z);
    ZVAL_UNDEF(params);

    // Convert the datetime string to a PHP DateTime object
    core::sqlsrv_zval_stringl(&value_temp_z, input, length);
    core::sqlsrv_zval_stringl(&function_z, "date_create", sizeof("date_create") - 1);
    params[0] = value_temp_z;

    if (call_user_function(EG(function_table), NULL, &function_z, &out_zval, 1,
                           params) == FAILURE) {
        THROW_CORE_ERROR(stmt, SQLSRV_ERROR_DATETIME_CONVERSION_FAILED);
    }

    zend_string_free(Z_STR(value_temp_z));
    zend_string_free(Z_STR(function_z));
}

// call to retrieve an error from ODBC.  This uses SQLGetDiagRec, so the
// errno is 1 based.  It returns it as an array with 3 members:
// 1/SQLSTATE) sqlstate
// 2/code) driver specific error code
// 3/message) driver specific error message
// The fetch type determines if the indices are numeric, associative, or both.

bool core_sqlsrv_get_odbc_error( _Inout_ sqlsrv_context& ctx, _In_ int record_number, _Inout_ sqlsrv_error_auto_ptr& error, _In_ logging_severity severity, _In_opt_ bool check_warning /* = false */)
{
    SQLHANDLE h = ctx.handle();
    SQLSMALLINT h_type = ctx.handle_type();

    if( h == NULL ) {
        return false;
    }

    SQLRETURN r = SQL_SUCCESS;
    SQLSMALLINT wmessage_len = 0;
    SQLWCHAR wsqlstate[SQL_SQLSTATE_BUFSIZE] = {L'\0'};
    SQLWCHAR wnative_message[SQL_MAX_ERROR_MESSAGE_LENGTH + 1] = {L'\0'};
    SQLSRV_ENCODING enc = ctx.encoding();

    switch( h_type ) {

        case SQL_HANDLE_STMT:
            {
                sqlsrv_stmt* stmt = static_cast<sqlsrv_stmt*>( &ctx );
                if( stmt->current_results != NULL ) {

                    error = stmt->current_results->get_diag_rec( record_number );
                    // don't use the CHECK* macros here since it will trigger reentry into the error handling system
                    if( error == 0 ) {
                        return false;
                    }
                    break;
                }
                // convert the error into the encoding of the context
                if( enc == SQLSRV_ENCODING_DEFAULT ) {
                    enc = stmt->conn->encoding();
                }
            }
        default:
            error = new ( sqlsrv_malloc( sizeof( sqlsrv_error ))) sqlsrv_error();
            r = SQLGetDiagRecW( h_type, h, record_number, wsqlstate, &error->native_code, wnative_message,
                                SQL_MAX_ERROR_MESSAGE_LENGTH + 1, &wmessage_len );
            // don't use the CHECK* macros here since it will trigger reentry into the error handling system
            // removed the workaround for Mac users with unixODBC 2.3.4 when connection pooling is enabled (PDO SQLSRV), for two reasons:
            // (1) not recommended to use connection pooling with unixODBC < 2.3.7
            // (2) the problem was not reproducible with unixODBC 2.3.7
            if( !SQL_SUCCEEDED( r ) || r == SQL_NO_DATA ) {
                return false;
            }

            // We need to calculate number of characters
            SQLINTEGER wsqlstate_len = sizeof( wsqlstate ) / sizeof( SQLWCHAR );
            SQLLEN sqlstate_len = 0;

            convert_string_from_utf16(enc, wsqlstate, wsqlstate_len, (char**)&error->sqlstate, sqlstate_len);
            
            SQLLEN message_len = 0;
            if (r == SQL_SUCCESS_WITH_INFO && wmessage_len > SQL_MAX_ERROR_MESSAGE_LENGTH) {
                // note that wmessage_len is the number of characters required for the error message -- 
                // create a new buffer big enough for this lengthy error message
                sqlsrv_malloc_auto_ptr<SQLWCHAR> wnative_message_str;

                SQLSMALLINT expected_len = wmessage_len * sizeof(SQLWCHAR);
                SQLSMALLINT returned_len = 0;

                wnative_message_str = reinterpret_cast<SQLWCHAR*>(sqlsrv_malloc(expected_len));
                memset(wnative_message_str, '\0', expected_len); 

                SQLRETURN rtemp = ::SQLGetDiagFieldW(h_type, h, record_number, SQL_DIAG_MESSAGE_TEXT, wnative_message_str, wmessage_len, &returned_len);
                if (!SQL_SUCCEEDED(rtemp) || returned_len != expected_len) {
                    // something went wrong
                    return false;
                }

                convert_string_from_utf16(enc, wnative_message_str, wmessage_len, (char**)&error->native_message, message_len);
            } else {
                convert_string_from_utf16(enc, wnative_message, wmessage_len, (char**)&error->native_message, message_len);
            }

            if (message_len == 0 && error->native_message == NULL) {
                // something went wrong
                return false;
            }
            break;
    }

    // Only overrides 'severity' if 'check_warning' is true (false by default)
    if (check_warning) {
        // The character string value returned for an SQLSTATE consists of a two-character class value 
        // followed by a three-character subclass value. A class value of "01" indicates a warning.
        // https://docs.microsoft.com/sql/odbc/reference/appendixes/appendix-a-odbc-error-codes?view=sql-server-ver15
        if (error->sqlstate[0] == '0' && error->sqlstate[1] == '1') {
            severity = SEV_WARNING;
        }
    }

    // log the error first
    LOG( severity, "%1!s!: SQLSTATE = %2!s!", ctx.func(), error->sqlstate );
    LOG( severity, "%1!s!: error code = %2!d!", ctx.func(), error->native_code );
    LOG( severity, "%1!s!: message = %2!s!", ctx.func(), error->native_message );

    error->format = false;

    return true;
}

// format and return a driver specfic error
void core_sqlsrv_format_driver_error( _In_ sqlsrv_context& ctx, _In_ sqlsrv_error_const const* custom_error, 
                                      _Out_ sqlsrv_error_auto_ptr& formatted_error, _In_ logging_severity severity, _In_opt_ va_list* args )
{
    // allocate space for the formatted message
    formatted_error = new (sqlsrv_malloc( sizeof( sqlsrv_error ))) sqlsrv_error();
    formatted_error->sqlstate = reinterpret_cast<SQLCHAR*>( sqlsrv_malloc( SQL_SQLSTATE_BUFSIZE ));
    formatted_error->native_message = reinterpret_cast<SQLCHAR*>( sqlsrv_malloc( SQL_MAX_ERROR_MESSAGE_LENGTH + 1 ));

    DWORD rc = FormatMessage( FORMAT_MESSAGE_FROM_STRING, reinterpret_cast<LPSTR>( custom_error->native_message ), 0, 0, 
                              reinterpret_cast<LPSTR>( formatted_error->native_message ), SQL_MAX_ERROR_MESSAGE_LENGTH, args );
    if( rc == 0 ) {
        strcpy_s( reinterpret_cast<char*>( formatted_error->native_message ), SQL_MAX_ERROR_MESSAGE_LENGTH,
                  reinterpret_cast<char*>( INTERNAL_FORMAT_ERROR ));
    }
    
    strcpy_s( reinterpret_cast<char*>( formatted_error->sqlstate ), SQL_SQLSTATE_BUFSIZE,
              reinterpret_cast<char*>( custom_error->sqlstate ));
    formatted_error->native_code = custom_error->native_code;

    // log the error
    LOG( severity, "%1!s!: SQLSTATE = %2!s!", ctx.func(), formatted_error->sqlstate );
    LOG( severity, "%1!s!: error code = %2!d!", ctx.func(), formatted_error->native_code );
    LOG( severity, "%1!s!: message = %2!s!", ctx.func(), formatted_error->native_message );
}

DWORD core_sqlsrv_format_message( _Out_ char* output_buffer, _In_ unsigned output_len, _In_opt_ const char* format, ... )
{
    va_list format_args;
    va_start( format_args, format );
    DWORD rc = FormatMessage( FORMAT_MESSAGE_FROM_STRING, format, 0, 0, static_cast<LPSTR>(output_buffer), output_len, &format_args );
    va_end( format_args );
    return rc;
}

// return an error message for GetLastError using FormatMessage.
// this function returns the msg pointer so that it may be used within
// another function call such as handle_error
const char* get_last_error_message( _Inout_ DWORD last_error )
{
    if( last_error == 0 ) {
        last_error = GetLastError();
    }

    DWORD r = FormatMessage( FORMAT_MESSAGE_FROM_SYSTEM, NULL, last_error, MAKELANGID( LANG_NEUTRAL, SUBLANG_DEFAULT ),
                             last_err_msg, sizeof( last_err_msg ), NULL );

    if( r == 0 ) {
        SQLSRV_STATIC_ASSERT( sizeof( INTERNAL_FORMAT_ERROR ) < sizeof( last_err_msg ));
        std::copy( INTERNAL_FORMAT_ERROR, INTERNAL_FORMAT_ERROR + sizeof( INTERNAL_FORMAT_ERROR ), last_err_msg );
    }

    return last_err_msg;
}


// die
// Terminate the PHP request with an error message
// We use this function rather than php_error directly because we use the FormatMessage syntax in most other
// places within the extension (e.g., LOG), whereas php_error uses the printf format syntax.  There were
// places where we were using the FormatMessage syntax inadvertently with DIE which left messages without
// proper information.  Rather than convert those messages and try and remember the difference between LOG and
// DIE, it is simpler to make the format syntax common between them.
void die( _In_opt_ const char* msg, ... )
{
    va_list format_args;
    va_start( format_args, msg );
    DWORD rc = FormatMessage( FORMAT_MESSAGE_FROM_STRING, msg, 0, 0, last_err_msg, sizeof( last_err_msg ), &format_args );
    va_end( format_args );
    if (rc == 0) {
        php_error(E_ERROR, "%s", reinterpret_cast<const char*>(INTERNAL_FORMAT_ERROR));
    }

    php_error(E_ERROR, "%s", last_err_msg);
}

namespace {

// convert from the default encoding specified by the "CharacterSet"
// connection option to UTF-16.  mbcs_len and utf16_len are sizes in
// bytes.  The return is the number of UTF-16 characters in the string
// returned in utf16_out_string.  An empty string passed in will result as
// a failure since MBTWC returns 0 for both an empty string and failure
// to convert.
unsigned int convert_string_from_default_encoding( _In_ unsigned int php_encoding, _In_reads_bytes_(mbcs_len) char const* mbcs_in_string,
                                                   _In_ unsigned int mbcs_len, _Out_writes_(utf16_len) __transfer( mbcs_in_string ) SQLWCHAR* utf16_out_string,
                                                   _In_ unsigned int utf16_len, bool use_strict_conversion )
{
    unsigned int win_encoding = CP_ACP;
    switch( php_encoding ) {
        case SQLSRV_ENCODING_CHAR:
            win_encoding = CP_ACP;
            break;
        // this shouldn't ever be set
        case SQLSRV_ENCODING_BINARY:
            DIE( "Invalid encoding." );
            break;
        default:
            win_encoding = php_encoding;
            break;
    }
#ifndef _WIN32
    unsigned int required_len;
    if (use_strict_conversion) {
        required_len = SystemLocale::ToUtf16Strict( win_encoding, mbcs_in_string, mbcs_len, utf16_out_string, utf16_len );
    }
    else {
        required_len = SystemLocale::ToUtf16( win_encoding, mbcs_in_string, mbcs_len, utf16_out_string, utf16_len );
    }
#else
    unsigned int required_len = MultiByteToWideChar( win_encoding, MB_ERR_INVALID_CHARS, mbcs_in_string, mbcs_len, utf16_out_string, utf16_len );
#endif // !_Win32
    
    if( required_len == 0 ) {
        return 0;
    }
    utf16_out_string[required_len] = '\0';

    return required_len;
}

}


namespace data_classification {
    const char* DATA_CLASS = "Data Classification";
    const char* LABEL = "Label";
    const char* INFOTYPE = "Information Type";
    const char* NAME = "name";
    const char* ID = "id";
    const char* RANK = "rank";

    void convert_sensivity_field(_Inout_ sqlsrv_stmt* stmt, _In_ SQLSRV_ENCODING encoding, _In_ unsigned char *ptr, _In_ int len, _Inout_updates_bytes_(cchOutLen) char** field_name)
    {
        sqlsrv_malloc_auto_ptr<SQLWCHAR> temp_field_name;
        int temp_field_len = len * sizeof(SQLWCHAR);
        SQLLEN field_name_len = 0;

        if (len == 0) {
            *field_name = reinterpret_cast<char*>(sqlsrv_malloc(1));
            *field_name[0] = '\0';
            return;
        }

        temp_field_name = static_cast<SQLWCHAR*>(sqlsrv_malloc((len + 1) * sizeof(SQLWCHAR)));
        memset(temp_field_name, L'\0', len + 1);
        memcpy_s(temp_field_name, temp_field_len, ptr, temp_field_len);

        bool converted = convert_string_from_utf16(encoding, temp_field_name, len, field_name, field_name_len);

        CHECK_CUSTOM_ERROR(!converted, stmt, SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE, get_last_error_message()) {
            throw core::CoreException();
        }
    }

    void name_id_pair_free(_Inout_ name_id_pair* pair)
    {
        if (pair->name) {
            pair->name.reset();
        }
        if (pair->id) {
            pair->id.reset();
        }
        sqlsrv_free(pair);
    }
    
    void parse_sensitivity_name_id_pairs(_Inout_ sqlsrv_stmt* stmt, _Inout_ USHORT& numpairs, _Inout_ std::vector<name_id_pair*, sqlsrv_allocator<name_id_pair*>>* pairs, _Inout_ unsigned char **pptr)
    {
        unsigned char *ptr = *pptr;
        unsigned short npairs;
        numpairs = npairs = *(reinterpret_cast<unsigned short*>(ptr)); 
        SQLSRV_ENCODING encoding = ((stmt->encoding() == SQLSRV_ENCODING_DEFAULT ) ? stmt->conn->encoding() : stmt->encoding());

        pairs->reserve(numpairs);
    
        ptr += sizeof(unsigned short);
        while (npairs--) {
            int namelen, idlen;
            unsigned char *nameptr, *idptr;

            sqlsrv_malloc_auto_ptr<name_id_pair> pair;
            pair = new(sqlsrv_malloc(sizeof(name_id_pair))) name_id_pair();

            sqlsrv_malloc_auto_ptr<char> name;
            sqlsrv_malloc_auto_ptr<char> id;

            namelen = *ptr++;
            nameptr = ptr;

            pair->name_len = namelen; 
            convert_sensivity_field(stmt, encoding, nameptr, namelen, (char**)&name);
            pair->name = name;

            ptr += namelen * 2;
            idlen = *ptr++;
            idptr = ptr;
            ptr += idlen * 2;

            pair->id_len = idlen;
            convert_sensivity_field(stmt, encoding, idptr, idlen, (char**)&id);
            pair->id = id;

            pairs->push_back(pair.get());
            pair.transferred();
        }
        *pptr = ptr;
    } 

    void parse_column_sensitivity_props(_Inout_ sensitivity_metadata* meta, _Inout_ unsigned char **pptr, _In_ bool getRankInfo)
    {
        unsigned char *ptr = *pptr;
        unsigned short ncols;
        int queryrank, colrank;

        // Get rank info
        if (getRankInfo) {
            queryrank = *(reinterpret_cast<long*>(ptr));
            ptr += sizeof(int);
            meta->rank = queryrank;
        }

        // Get number of columns
        meta->num_columns = ncols = *(reinterpret_cast<unsigned short*>(ptr));

        // Move forward
        ptr += sizeof(unsigned short);

        while (ncols--) {
            unsigned short npairs = *(reinterpret_cast<unsigned short*>(ptr));

            ptr += sizeof(unsigned short);

            column_sensitivity column;
            column.num_pairs = npairs;

            while (npairs--) {
                label_infotype_pair pair;

                unsigned short labelidx, typeidx;
                labelidx = *(reinterpret_cast<unsigned short*>(ptr));
                ptr += sizeof(unsigned short);
                typeidx = *(reinterpret_cast<unsigned short*>(ptr));
                ptr += sizeof(unsigned short);

                if (getRankInfo) {
                    colrank = *(reinterpret_cast<long*>(ptr));
                    ptr += sizeof(int);
                    pair.rank = colrank;
                }

                pair.label_idx = labelidx;
                pair.infotype_idx = typeidx;

                column.label_info_pairs.push_back(pair);
            }

            meta->columns_sensitivity.push_back(column);
        }

        *pptr = ptr;
    }

    USHORT fill_column_sensitivity_array(_Inout_ sqlsrv_stmt* stmt, _In_ SQLSMALLINT colno, _Inout_ zval *return_array)
    {
        sensitivity_metadata* meta = stmt->current_sensitivity_metadata;
        if (meta == NULL) {
            return 0;
        }
       
        SQLSRV_ASSERT(colno >= 0 && colno < meta->num_columns, "fill_column_sensitivity_array: column number out of bounds");

        zval data_classification;
        ZVAL_UNDEF(&data_classification);
        array_init(&data_classification);

        USHORT num_pairs = meta->columns_sensitivity[colno].num_pairs;

        if (num_pairs == 0) {
            add_assoc_zval(return_array, DATA_CLASS, &data_classification);

            return 0;
        }

        zval sensitivity_properties;
        ZVAL_UNDEF(&sensitivity_properties);
        array_init(&sensitivity_properties);

        for (USHORT j = 0; j < num_pairs; j++) {
            zval label_array, infotype_array;
            ZVAL_UNDEF(&label_array);
            ZVAL_UNDEF(&infotype_array);

            array_init(&label_array);
            array_init(&infotype_array);

            USHORT labelidx = meta->columns_sensitivity[colno].label_info_pairs[j].label_idx;
            USHORT typeidx = meta->columns_sensitivity[colno].label_info_pairs[j].infotype_idx;
            int column_rank = meta->columns_sensitivity[colno].label_info_pairs[j].rank;

            char *label = meta->labels[labelidx]->name;
            char *label_id = meta->labels[labelidx]->id;
            char *infotype = meta->infotypes[typeidx]->name;
            char *infotype_id = meta->infotypes[typeidx]->id;

            add_assoc_string(&label_array, NAME, label);
            add_assoc_string(&label_array, ID, label_id);

            add_assoc_zval(&sensitivity_properties, LABEL, &label_array);

            add_assoc_string(&infotype_array, NAME, infotype);
            add_assoc_string(&infotype_array, ID, infotype_id);

            add_assoc_zval(&sensitivity_properties, INFOTYPE, &infotype_array);

            // add column sensitivity rank info to sensitivity_properties
            if (column_rank > RANK_NOT_DEFINED) {
                add_assoc_long(&sensitivity_properties, RANK, column_rank);
            }

            // add the pair of sensitivity properties to data_classification
            add_next_index_zval(&data_classification, &sensitivity_properties);
        }

        // add query sensitivity rank info to data_classification
        int query_rank = meta->rank;
        if (query_rank > RANK_NOT_DEFINED) {
            add_assoc_long(&data_classification, RANK, query_rank);
        }

        // add data classfication as associative array
        add_assoc_zval(return_array, DATA_CLASS, &data_classification);

        return num_pairs;
    }

    void sensitivity_metadata::reset()
    {
        std::for_each(labels.begin(), labels.end(), name_id_pair_free);
        labels.clear();

        std::for_each(infotypes.begin(), infotypes.end(), name_id_pair_free);
        infotypes.clear();

        columns_sensitivity.clear();
    }
} // namespace data_classification

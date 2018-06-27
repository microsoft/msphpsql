//---------------------------------------------------------------------------------------------------------------------------------
// File: core_util.cpp
//
// Contents: Utility functions used by both connection or statement functions for both the PDO and sqlsrv drivers
// 
// Comments: Mostly error handling and some type handling
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

namespace {

// *** internal constants ***
log_callback g_driver_log;
// internal error that says that FormatMessage failed
SQLCHAR INTERNAL_FORMAT_ERROR[] = "An internal error occurred.  FormatMessage failed writing an error message.";
// buffer used to hold a formatted log message prior to actually logging it.
char last_err_msg[ 2048 ];  // 2k to hold the error messages

// routine used by utf16_string_from_mbcs_string
unsigned int convert_string_from_default_encoding( _In_ unsigned int php_encoding, _In_reads_bytes_(mbcs_len) char const* mbcs_in_string,
                                                   _In_ unsigned int mbcs_len, 
                                                   _Out_writes_(utf16_len) __transfer( mbcs_in_string ) SQLWCHAR* utf16_out_string,
                                                   _In_ unsigned int utf16_len );
}

// SQLSTATE for all internal errors 
SQLCHAR IMSSP[] = "IMSSP";

// SQLSTATE for all internal warnings
SQLCHAR SSPWARN[] = "01SSP";

// write to the php log if the severity and subsystem match the filters currently set in the INI or 
// the script (sqlsrv_configure).
void write_to_log( _In_ unsigned int severity TSRMLS_DC, _In_ const char* msg, ...)
{
    SQLSRV_ASSERT( !(g_driver_log == NULL), "Must register a driver log function." );

    va_list args;
    va_start( args, msg );

    g_driver_log( severity TSRMLS_CC, msg, &args );

    va_end( args );
}

void core_sqlsrv_register_logger( _In_ log_callback driver_logger )
{
    g_driver_log = driver_logger;
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

bool convert_zval_string_from_utf16( _In_ SQLSRV_ENCODING encoding, _Inout_ zval* value_z, _Inout_ SQLLEN& len)
{
    char* string = Z_STRVAL_P(value_z);

    if( validate_string(string, len)) {
       return true;
    }

    char* outString = NULL;
    SQLLEN outLen = 0;
    bool result = convert_string_from_utf16( encoding, reinterpret_cast<const SQLWCHAR*>(string), int(len / sizeof(SQLWCHAR)), &outString, outLen );
    if( result ) {
       core::sqlsrv_zval_stringl( value_z, outString, outLen );
       sqlsrv_free( outString );
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

    // flags set to 0 by default, which means that any invalid characters are dropped rather than causing
    // an error.   This happens only on XP.
    DWORD flags = 0;
    if( encoding == CP_UTF8 && isVistaOrGreater ) {
        // Vista (and later) will detect invalid UTF-16 characters and raise an error.
        flags = WC_ERR_INVALID_CHARS;
    }

    // calculate the number of characters needed
#ifndef _WIN32
    cchOutLen = SystemLocale::FromUtf16Strict( encoding, inString, cchInLen, NULL, 0 );
#else	
    cchOutLen = WideCharToMultiByte( encoding, flags,
                                   inString, cchInLen, 
                                   NULL, 0, NULL, NULL );
#endif // !_WIN32   
	
    if( cchOutLen == 0 ) {
        return false;
    }

    // Create a buffer to fit the encoded string
    char* newString = reinterpret_cast<char*>( sqlsrv_malloc( cchOutLen + 1 /* NULL char*/ ));
    
#ifndef _WIN32
    int rc = SystemLocale::FromUtf16( encoding, inString, cchInLen, newString, static_cast<int>(cchOutLen));
#else
    int rc = WideCharToMultiByte( encoding, flags, inString, cchInLen, newString, static_cast<int>(cchOutLen), NULL, NULL );
#endif // !_WIN32
    if( rc == 0 ) {
        cchOutLen = 0;
        sqlsrv_free( newString );
        return false;
    }

    *outString = newString;
    newString[cchOutLen] = '\0';   // null terminate the encoded string

    return true;
}

// thin wrapper around convert_string_from_default_encoding that handles
// allocation of the destination string.  An empty string passed in returns
// failure since it's a failure case for convert_string_from_default_encoding.
SQLWCHAR* utf16_string_from_mbcs_string( _In_ SQLSRV_ENCODING php_encoding, _In_reads_bytes_(mbcs_len) const char* mbcs_string, _In_ unsigned int mbcs_len,
                                        _Out_ unsigned int* utf16_len )
{
    *utf16_len = (mbcs_len + 1);
    SQLWCHAR* utf16_string = reinterpret_cast<SQLWCHAR*>( sqlsrv_malloc( *utf16_len * sizeof( SQLWCHAR )));
    *utf16_len = convert_string_from_default_encoding( php_encoding, mbcs_string, mbcs_len, utf16_string, *utf16_len );

    if( *utf16_len == 0 ) {
        // we preserve the error and reset it because sqlsrv_free resets the last error
        DWORD last_error = GetLastError();
        sqlsrv_free( utf16_string );
        SetLastError( last_error );
        return NULL;
    }

    return utf16_string;
}

// call to retrieve an error from ODBC.  This uses SQLGetDiagRec, so the
// errno is 1 based.  It returns it as an array with 3 members:
// 1/SQLSTATE) sqlstate
// 2/code) driver specific error code
// 3/message) driver specific error message
// The fetch type determines if the indices are numeric, associative, or both.

bool core_sqlsrv_get_odbc_error( _Inout_ sqlsrv_context& ctx, _In_ int record_number, _Inout_ sqlsrv_error_auto_ptr& error, _In_ logging_severity severity 
                                 TSRMLS_DC )
{
    SQLHANDLE h = ctx.handle();
    SQLSMALLINT h_type = ctx.handle_type();

    if( h == NULL ) {
        return false;
    }

    SQLRETURN r = SQL_SUCCESS;
    SQLSMALLINT wmessage_len = 0;
    SQLWCHAR wsqlstate[ SQL_SQLSTATE_BUFSIZE ] = { L'\0' };
    SQLWCHAR wnative_message[ SQL_MAX_ERROR_MESSAGE_LENGTH + 1 ] = { L'\0' };
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
            // Workaround for a bug in unixODBC 2.3.4 when connection pooling is enabled (PDO SQLSRV).
            // Instead of returning false, we return an empty error message to prevent the driver from throwing an exception.
            // To reproduce:
            // Create a connection and close it (return it to the pool)
            // Create a new connection from the pool. 
            // Prepare and execute a statement that generates an info message (such as 'USE tempdb;') 
#ifdef __APPLE__
            if( r == SQL_NO_DATA && ctx.driver() != NULL /*PDO SQLSRV*/ ) {
                r = SQL_SUCCESS;
            }
#endif // __APPLE__
            if( !SQL_SUCCEEDED( r ) || r == SQL_NO_DATA ) {
                return false;
            }

            // We need to calculate number of characters
            SQLINTEGER wsqlstate_len = sizeof( wsqlstate ) / sizeof( SQLWCHAR );
            SQLLEN sqlstate_len = 0;
            convert_string_from_utf16(enc, wsqlstate, wsqlstate_len, (char**)&error->sqlstate, sqlstate_len);

            SQLLEN message_len = 0;
            convert_string_from_utf16(enc, wnative_message, wmessage_len, (char**)&error->native_message, message_len);
            break;
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
                                      _Out_ sqlsrv_error_auto_ptr& formatted_error, _In_ logging_severity severity TSRMLS_DC, _In_opt_ va_list* args )
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
    if( rc == 0 ) {
        php_error( E_ERROR, reinterpret_cast<const char*>( INTERNAL_FORMAT_ERROR ));
    }

    php_error( E_ERROR, last_err_msg );
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
                                                   _In_ unsigned int utf16_len )
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
    unsigned int required_len = SystemLocale::ToUtf16( win_encoding, mbcs_in_string, mbcs_len, utf16_out_string, utf16_len );
#else
    unsigned int required_len = MultiByteToWideChar( win_encoding, MB_ERR_INVALID_CHARS, mbcs_in_string, mbcs_len, utf16_out_string, utf16_len );
#endif // !_Win32
    
    if( required_len == 0 ) {
        return 0;
    }
    utf16_out_string[ required_len ] = '\0';

    return required_len;
}

}

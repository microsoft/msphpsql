//---------------------------------------------------------------------------------------------------------------------------------
// File: core_util.cpp
//
// Contents: Utility functions used by both connection or statement functions for both the PDO and sqlsrv drivers
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
//---------------------------------------------------------------------------------------------------------------------------------

#include "core_sqlsrv.h"

#include <windows.h>

namespace {

// *** internal constants ***
log_callback g_driver_log;
// internal error that says that FormatMessage failed
SQLCHAR INTERNAL_FORMAT_ERROR[] = "An internal error occurred.  FormatMessage failed writing an error message.";
// buffer used to hold a formatted log message prior to actually logging it.
char last_err_msg[ 2048 ];  // 2k to hold the error messages

// routine used by utf16_string_from_mbcs_string
unsigned int convert_string_from_default_encoding( unsigned int php_encoding, __in_bcount(mbcs_len) char const* mbcs_in_string,
                                                   unsigned int mbcs_len, 
                                                   __out_ecount(utf16_len) __transfer( mbcs_in_string ) wchar_t* utf16_out_string,
                                                   unsigned int utf16_len );
}

// SQLSTATE for all internal errors 
SQLCHAR IMSSP[] = "IMSSP";

// SQLSTATE for all internal warnings
SQLCHAR SSPWARN[] = "01SSP";

// write to the php log if the severity and subsystem match the filters currently set in the INI or 
// the script (sqlsrv_configure).
void write_to_log( unsigned int severity TSRMLS_DC, const char* msg, ...)
{
    SQLSRV_ASSERT( !(g_driver_log == NULL), "Must register a driver log function." );

    va_list args;
    va_start( args, msg );

    g_driver_log( severity TSRMLS_CC, msg, &args );

    va_end( args );
}

void core_sqlsrv_register_logger( log_callback driver_logger )
{
    g_driver_log = driver_logger;
}


// convert a string from utf-16 to the encoding and return the new string in the pointer parameter and new
// length in the len parameter.  If no errors occurred during convertion, true is returned and the original
// utf-16 string is released by this function if no errors occurred.  Otherwise the parameters are not changed
// and false is returned.

bool convert_string_from_utf16( SQLSRV_ENCODING encoding, char** string, SQLINTEGER& len, bool free_utf16 )
{
    char* utf16_string = *string;
    unsigned int utf16_len = len / 2;  // from # of bytes to # of wchars
    char *enc_string = NULL;
    unsigned int enc_len = 0;

    // for the empty string, we simply returned we converted it
    if( len == 0 && *string[0] == '\0' ) {
        return true;
    }

    // flags set to 0 by default, which means that any invalid characters are dropped rather than causing
    // an error.   This happens only on XP.
    DWORD flags = 0;
    if( encoding == CP_UTF8 && g_osversion.dwMajorVersion >= SQLSRV_OS_VISTA_OR_LATER ) {
        // Vista (and later) will detect invalid UTF-16 characters and raise an error.
        flags = WC_ERR_INVALID_CHARS;
    }

    // calculate the number of characters needed
    enc_len = WideCharToMultiByte( encoding, flags,
                                   reinterpret_cast<LPCWSTR>( utf16_string ), utf16_len, 
                                   NULL, 0, NULL, NULL );
    if( enc_len == 0 ) {
        return false;
    }
    // we must allocate a new buffer because it is possible that a UTF-8 string is longer than
    // the corresponding UTF-16 string, so we cannot use an inplace conversion
    enc_string = reinterpret_cast<char*>( sqlsrv_malloc( enc_len + 1 /* NULL char*/ ));
    int rc = WideCharToMultiByte( encoding, flags,
                                  reinterpret_cast<LPCWSTR>( utf16_string ), utf16_len, 
                                  enc_string, enc_len, NULL, NULL );
    if( rc == 0 ) {
        return false;
    }

    enc_string[ enc_len ] = '\0';   // null terminate the encoded string
    if( free_utf16 ) {
        sqlsrv_free( utf16_string );
    }
    *string = enc_string;
    len = enc_len;

    return true;
}


// thin wrapper around convert_string_from_default_encoding that handles
// allocation of the destination string.  An empty string passed in returns
// failure since it's a failure case for convert_string_from_default_encoding.
wchar_t* utf16_string_from_mbcs_string( SQLSRV_ENCODING php_encoding, const char* mbcs_string, unsigned int mbcs_len, 
                                        unsigned int* utf16_len )
{
    *utf16_len = (mbcs_len + 1);
    wchar_t* utf16_string = reinterpret_cast<wchar_t*>( sqlsrv_malloc( *utf16_len * sizeof( wchar_t )));
    *utf16_len = convert_string_from_default_encoding( php_encoding, mbcs_string, mbcs_len, 
                                                      utf16_string, *utf16_len );
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

bool core_sqlsrv_get_odbc_error( sqlsrv_context& ctx, int record_number, sqlsrv_error_auto_ptr& error, logging_severity severity 
                                 TSRMLS_DC )
{
    SQLHANDLE h = ctx.handle();
    SQLSMALLINT h_type = ctx.handle_type();

    if( h == NULL ) {
        return false;
    }

    zval* ssphp_z = NULL;
    int zr = SUCCESS;
    zval* temp = NULL;
    SQLRETURN r = SQL_SUCCESS;
    SQLINTEGER sqlstate_len = SQL_SQLSTATE_BUFSIZE * sizeof( wchar_t );
    SQLSMALLINT wmessage_len = 0;
    SQLINTEGER message_len = 0;
    SQLWCHAR wsqlstate[ SQL_SQLSTATE_BUFSIZE ];
    SQLWCHAR wnative_message[ SQL_MAX_MESSAGE_LENGTH + 1 ];
    SQLSRV_ENCODING enc = ctx.encoding();

    switch( h_type ) {

        case SQL_HANDLE_STMT:
            {
                sqlsrv_stmt* stmt = static_cast<sqlsrv_stmt*>( &ctx );
                if( stmt->current_results != NULL ) {

                    error = stmt->current_results->get_diag_rec( record_number );
                    // don't use the CHECK* macros here since it will trigger reentry into the error handling system
                    if( error == NULL ) {
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
                                SQL_MAX_MESSAGE_LENGTH + 1, &wmessage_len );
            // don't use the CHECK* macros here since it will trigger reentry into the error handling system
            if( !SQL_SUCCEEDED( r ) || r == SQL_NO_DATA ) {
                return false;
            }

            error->sqlstate = reinterpret_cast<SQLCHAR*>( wsqlstate );
            convert_string_from_utf16( enc, reinterpret_cast<char**>( &error->sqlstate ), sqlstate_len, 
                                       false /*no free*/ );
            error->native_message = reinterpret_cast<SQLCHAR*>( wnative_message );
            message_len = wmessage_len * sizeof( wchar_t );
            convert_string_from_utf16( enc, reinterpret_cast<char**>( &error->native_message ), message_len, 
                                       false /*no free*/ );
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
void core_sqlsrv_format_driver_error( sqlsrv_context& ctx, sqlsrv_error_const const* custom_error, 
                                      sqlsrv_error_auto_ptr& formatted_error, logging_severity severity TSRMLS_DC, va_list* args )
{
    // allocate space for the formatted message
    formatted_error = new (sqlsrv_malloc( sizeof( sqlsrv_error ))) sqlsrv_error();
    formatted_error->sqlstate = reinterpret_cast<SQLCHAR*>( sqlsrv_malloc( SQL_SQLSTATE_BUFSIZE ));
    formatted_error->native_message = reinterpret_cast<SQLCHAR*>( sqlsrv_malloc( SQL_MAX_MESSAGE_LENGTH + 1 ));

    DWORD rc = FormatMessage( FORMAT_MESSAGE_FROM_STRING, reinterpret_cast<LPSTR>( custom_error->native_message ), 0, 0, 
                              reinterpret_cast<LPSTR>( formatted_error->native_message ), SQL_MAX_MESSAGE_LENGTH, args );
    if( rc == 0 ) {
        strcpy_s( reinterpret_cast<char*>( formatted_error->native_message ), SQL_MAX_MESSAGE_LENGTH,
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

DWORD core_sqlsrv_format_message( char* output_buffer, unsigned output_len, const char* format, ... )
{
    va_list format_args;
    va_start( format_args, format );

    DWORD rc = FormatMessage( FORMAT_MESSAGE_FROM_STRING, format, 0, 0, output_buffer, output_len, &format_args );

    va_end( format_args );

    return rc;
}

// return an error message for GetLastError using FormatMessage.
// this function returns the msg pointer so that it may be used within
// another function call such as handle_error
const char* get_last_error_message( DWORD last_error )
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
void die( const char* msg, ... )
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
unsigned int convert_string_from_default_encoding( unsigned int php_encoding, __in_bcount(mbcs_len) char const* mbcs_in_string,
                                                   unsigned int mbcs_len, __out_ecount(utf16_len) __transfer( mbcs_in_string ) wchar_t* utf16_out_string,
                                                   unsigned int utf16_len )
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
    unsigned int required_len = MultiByteToWideChar( win_encoding, MB_ERR_INVALID_CHARS, mbcs_in_string, mbcs_len, 
                                                     utf16_out_string, utf16_len );
    if( required_len == 0 ) {
        return 0;
    }
    utf16_out_string[ required_len ] = '\0';

    return required_len;
}

}

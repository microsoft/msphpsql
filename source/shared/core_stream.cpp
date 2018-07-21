//---------------------------------------------------------------------------------------------------------------------------------
// File: core_stream.cpp
//
// Contents: Implementation of PHP streams for reading SQL Server data
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

// close a stream and free the PHP resources used by it

int sqlsrv_stream_close( _Inout_ php_stream* stream, int /*close_handle*/ TSRMLS_DC )
{
    sqlsrv_stream* ss = static_cast<sqlsrv_stream*>( stream->abstract );
    SQLSRV_ASSERT( ss != NULL && ss->stmt != NULL, "sqlsrv_stream_close: sqlsrv_stream* ss was null." );
    
    // free the stream resources in the Zend engine
    php_stream_free( stream, PHP_STREAM_FREE_RELEASE_STREAM );

    // UNDEF the stream zval and delete our reference count to it.
    ZVAL_UNDEF( &( ss->stmt->active_stream ) );

    sqlsrv_free( ss );
    stream->abstract = NULL;

    return 0;
}


// read from a sqlsrv stream into the buffer provided by Zend.  The parameters for binary vs. char are
// set when sqlsrv_get_field is called by the user specifying which field type they want.

size_t sqlsrv_stream_read( _Inout_ php_stream* stream, _Out_writes_bytes_(count) char* buf, _Inout_ size_t count TSRMLS_DC )
{
	SQLLEN read = 0;
    SQLSMALLINT c_type = SQL_C_CHAR;
    char* get_data_buffer = buf;
    sqlsrv_malloc_auto_ptr<char> temp_buf;

    sqlsrv_stream* ss = static_cast<sqlsrv_stream*>( stream->abstract );
    SQLSRV_ASSERT( ss != NULL && ss->stmt != NULL, "sqlsrv_stream_read: sqlsrv_stream* ss is NULL." );

    try {

        if( stream->eof ) {
            return 0;
        };

        switch( ss->encoding ) {
            case SQLSRV_ENCODING_CHAR:
                c_type = SQL_C_CHAR;
                break;

            case SQLSRV_ENCODING_BINARY:
                c_type = SQL_C_BINARY;
                break;

            case CP_UTF8:
            {
                c_type = SQL_C_WCHAR;
                count /= 2;    // divide the number of bytes we read by 2 since converting to UTF-8 can cause an increase in bytes
                if( count > PHP_STREAM_BUFFER_SIZE ) {
                    count = PHP_STREAM_BUFFER_SIZE;
                }

                // use a temporary buffer to retrieve from SQLGetData since we need to translate it to UTF-8 from UTF-16
                temp_buf = static_cast<char*>( sqlsrv_malloc( PHP_STREAM_BUFFER_SIZE ));
                memset(temp_buf, 0, PHP_STREAM_BUFFER_SIZE);
                get_data_buffer = temp_buf;
                break;
            }

            default:
                DIE( "Unknown encoding type when reading from a stream" );
                break;
        }

        SQLRETURN r = SQLGetData( ss->stmt->handle(), ss->field_index + 1, c_type, get_data_buffer, count /*BufferLength*/, &read );

        CHECK_SQL_ERROR( r, ss->stmt ) {
            stream->eof = 1; 
            throw core::CoreException();
        }

        // if the stream returns either no data, NULL data, or returns data < than the count requested then
        // we are at the "end of the stream" so we mark it
        if( r == SQL_NO_DATA || read == SQL_NULL_DATA || ( static_cast<size_t>( read ) <= count && read != SQL_NO_TOTAL )) {
            stream->eof = 1;
        }

        // if ODBC returns the 01004 (truncated string) warning, then we return the count minus the null terminator
        // if it's not a binary encoded field
        if( r == SQL_SUCCESS_WITH_INFO ) {

            SQLCHAR state[SQL_SQLSTATE_BUFSIZE] = { 0 };
            SQLSMALLINT len = 0;

            ss->stmt->current_results->get_diag_field( 1, SQL_DIAG_SQLSTATE, state, SQL_SQLSTATE_BUFSIZE, &len TSRMLS_CC );

            if( read == SQL_NO_TOTAL ) {
                SQLSRV_ASSERT( is_truncated_warning( state ), "sqlsrv_stream_read: truncation warning was expected but it "
                               "did not occur." );
            }
            
        // with unixODBC connection pooling enabled the truncated state may not be returned so check the actual length read
        // with buffer length.
        #ifndef _WIN32
            if( is_truncated_warning( state ) || count < read) {
        #else
            if( is_truncated_warning( state ) ) {
        #endif // !_WIN32 
                switch( c_type ) {
                    
                    // As per SQLGetData documentation, if the length of character data exceeds the BufferLength, 
                    // SQLGetData truncates the data to BufferLength less the length of null-termination character.
                    case SQL_C_BINARY:
                        read = count;
                        break;
                    case SQL_C_WCHAR:
                        read = ( count % 2 == 0 ? count - 2 : count - 3 );                       
                        break;
                    case SQL_C_CHAR:
                        read  = count - 1;
                        break;
                    default:
                        DIE( "sqlsrv_stream_read: should have never reached in this switch case.");
                        break;
                }
            }
            else {
                CHECK_SQL_WARNING( r, ss->stmt );
            }
        }

        // if the encoding is UTF-8
        if( c_type == SQL_C_WCHAR ) {
            count *= 2;          
            // undo the shift to use the full buffer
            // flags set to 0 by default, which means that any invalid characters are dropped rather than causing
            // an error.  This happens only on XP.
            // convert to UTF-8
#ifdef _WIN32
            DWORD flags = 0;
            if( isVistaOrGreater ) {
                // Vista (and later) will detect invalid UTF-16 characters and raise an error.
                flags = WC_ERR_INVALID_CHARS;
            }
#endif // _WIN32
           if( count > INT_MAX || (read >> 1) > INT_MAX ) {
               LOG(SEV_ERROR, "UTF-16 (wide character) string mapping: buffer length exceeded.");
               throw core::CoreException();
           }

#ifndef _WIN32
            int enc_len = SystemLocale::FromUtf16( ss->encoding, reinterpret_cast<LPCWSTR>( temp_buf.get() ),
                                                   static_cast<int>(read >> 1), buf, static_cast<int>(count), NULL, NULL );
#else
            int enc_len = WideCharToMultiByte( ss->encoding, flags, reinterpret_cast<LPCWSTR>( temp_buf.get() ),
                                               static_cast<int>(read >> 1), buf, static_cast<int>(count), NULL, NULL );
#endif // !_WIN32
            if( enc_len == 0 ) {
            
                stream->eof = 1;
                THROW_CORE_ERROR( ss->stmt, SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE, get_last_error_message() );
            }

            read = enc_len;
        }

        return static_cast<size_t>( read );
    } 

    catch( core::CoreException& ) {
        
        return 0;
    }
    catch( ... ) {

        LOG( SEV_ERROR, "sqlsrv_stream_read: Unknown exception caught." );
        return 0;
    }
}

// function table for stream operations.  We only support reading and closing the stream
php_stream_ops sqlsrv_stream_ops = {
    NULL,
    sqlsrv_stream_read,
    sqlsrv_stream_close,
    NULL,
    SQLSRV_STREAM,
    NULL,
    NULL,
    NULL,
    NULL
};

// open a stream and return the sqlsrv_stream_ops function table as part of the
// return value.  There is only one valid way to open a stream, using sqlsrv_get_field on
// certain field types.  A sqlsrv stream may only be opened in read mode.
static php_stream* sqlsrv_stream_opener( _In_opt_ php_stream_wrapper* wrapper, _In_ const char*, _In_ const char* mode, 
                                         _In_opt_ int options, _In_ zend_string **, php_stream_context* STREAMS_DC TSRMLS_DC )
{

#if ZEND_DEBUG
    SQLSRV_UNUSED( __zend_orig_lineno );
    SQLSRV_UNUSED( __zend_orig_filename );
    SQLSRV_UNUSED( __zend_lineno );
    SQLSRV_UNUSED( __zend_filename );
    SQLSRV_UNUSED( __php_stream_call_depth );
#endif

    sqlsrv_malloc_auto_ptr<sqlsrv_stream> ss;

    ss = static_cast<sqlsrv_stream*>( sqlsrv_malloc( sizeof( sqlsrv_stream )));
    memset( ss, 0, sizeof( sqlsrv_stream ));

    // check for valid options
    if( options != REPORT_ERRORS ) { 
        php_stream_wrapper_log_error( wrapper, options TSRMLS_CC, "Invalid option: no options except REPORT_ERRORS may be specified with a sqlsrv stream" );
        return NULL;
    }

    // allocate the stream from PHP
    php_stream* php_str = php_stream_alloc( &sqlsrv_stream_ops, ss, 0, mode );
    if( php_str != NULL ) {
        ss.transferred();
    }

    return php_str;
}

// information structure that contains PHP stream wrapper info. We supply the minimal
// possible, including the open function and the name only.

php_stream_wrapper_ops sqlsrv_stream_wrapper_ops = {
    sqlsrv_stream_opener,
    NULL,
    NULL,
    NULL,
    NULL,
    SQLSRV_STREAM_WRAPPER,
    NULL,
    NULL,
    NULL,
    NULL
};

}

// structure used by PHP to get the function table for opening, closing, etc. of the stream
php_stream_wrapper g_sqlsrv_stream_wrapper = {
    &sqlsrv_stream_wrapper_ops,
    NULL,
    0
};

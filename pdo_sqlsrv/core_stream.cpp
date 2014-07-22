//---------------------------------------------------------------------------------------------------------------------------------
// File: core_stream.cpp
//
// Contents: Implementation of PHP streams for reading SQL Server data
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

// close a stream and free the PHP resources used by it

int sqlsrv_stream_close( php_stream* stream, int /*close_handle*/ TSRMLS_DC )
{
    sqlsrv_stream* ss = static_cast<sqlsrv_stream*>( stream->abstract );
    SQLSRV_ASSERT( ss != NULL, "sqlsrv_stream_close: sqlsrv_stream* ss was null." );
    
    // free the stream resources in the Zend engine
    php_stream_free( stream, PHP_STREAM_FREE_RELEASE_STREAM );

    // NULL out the stream zval and delete our reference count to it.
    ZVAL_NULL( ss->stmt->active_stream );

    // there is no active stream
    ss->stmt->active_stream = NULL;

    sqlsrv_free( ss );
    stream->abstract = NULL;

    return 0;
}


// read from a sqlsrv stream into the buffer provided by Zend.  The parameters for binary vs. char are
// set when sqlsrv_get_field is called by the user specifying which field type they want.

size_t sqlsrv_stream_read( php_stream* stream, __out_bcount(count) char* buf, size_t count TSRMLS_DC )
{
   
    SQLINTEGER read = 0;
    SQLSMALLINT c_type = SQL_C_CHAR;
    char* get_data_buffer = buf;
    sqlsrv_malloc_auto_ptr<char> temp_buf;

    sqlsrv_stream* ss = static_cast<sqlsrv_stream*>( stream->abstract );
    SQLSRV_ASSERT( ss != NULL, "sqlsrv_stream_read: sqlsrv_stream* ss is NULL." );

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

            SQLCHAR state[ SQL_SQLSTATE_BUFSIZE ];
            SQLSMALLINT len;

            ss->stmt->current_results->get_diag_field( 1, SQL_DIAG_SQLSTATE, state, SQL_SQLSTATE_BUFSIZE, &len TSRMLS_CC );

            if( read == SQL_NO_TOTAL ) {
                SQLSRV_ASSERT( is_truncated_warning( state ), "sqlsrv_stream_read: truncation warning was expected but it "
                               "did not occur." );
            }
            
            if( is_truncated_warning( state ) ) {
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
            
            count *= 2;    // undo the shift to use the full buffer

            // flags set to 0 by default, which means that any invalid characters are dropped rather than causing
            // an error.  This happens only on XP.
            DWORD flags = 0;

            // convert to UTF-8
            if( g_osversion.dwMajorVersion >= SQLSRV_OS_VISTA_OR_LATER ) {
                // Vista (and later) will detect invalid UTF-16 characters and raise an error.
                flags = WC_ERR_INVALID_CHARS;
            }
            int enc_len = WideCharToMultiByte( ss->encoding, flags, reinterpret_cast<LPCWSTR>( temp_buf.get() ),
                                         read >> 1, buf, count, NULL, NULL );

            if( enc_len == 0 ) {
            
                stream->eof = 1;
                THROW_CORE_ERROR( ss->stmt, SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE, get_last_error_message() );
            }

            read = enc_len;
        }

        return read;
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

static php_stream* sqlsrv_stream_opener( php_stream_wrapper* wrapper, __in char*, __in char* mode, 
                                         int options, __in char **, php_stream_context* STREAMS_DC TSRMLS_DC )
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

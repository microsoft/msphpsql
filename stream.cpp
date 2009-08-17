//----------------------------------------------------------------------------------------------------------------------------------
// File: stream.cpp
//
// Copyright (c) Microsoft Corporation.  All rights reserved.
//
// Contents: Implementation of PHP streams for reading SQL Server data
// 
// Comments: Only certain data types may be read from streams.  The data type
// are all the (var)binary and (var)(n)char types, whether sized or max types
// as well the legacy LOB types: text, ntext, and image.
//
// License: This software is released under the Microsoft Public License.  A copy of the license agreement 
//          may be found online at http://www.codeplex.com/SQLSRVPHP/license.
//----------------------------------------------------------------------------------------------------------------------------------

#include "php_sqlsrv.h"

#include <windows.h>


namespace {

// current subsytem.  defined for the CHECK_SQL_{ERROR|WARNING} Macros.  We use LOG_STMT here since
// streams are manufactured by statement functions.
int current_log_subsystem = LOG_STMT;


// close a stream and free the PHP resources used by it

int sqlsrv_stream_close( php_stream* stream, int  TSRMLS_DC )
{
    sqlsrv_stream* ss = static_cast<sqlsrv_stream*>( stream->abstract );

    if( ss == NULL ) DIE( "sqlsrv_stream* ss is NULL.  Shouldn't ever be NULL" );

    // free the stream resources in the Zend engine
    php_stream_free( stream, PHP_STREAM_FREE_RELEASE_STREAM );

    // NULL out the stream zval and delete our reference count to it.
    ZVAL_NULL( ss->stmt->active_stream );
    zval_ptr_dtor( &ss->stmt->active_stream );
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
    SQLRETURN r = SQL_SUCCESS;
    SQLINTEGER read = 0;
    sqlsrv_stream* ss = static_cast<sqlsrv_stream*>( stream->abstract );
    SQLSMALLINT c_type = SQL_C_CHAR;
    char* get_data_buffer = buf;

    LOG( SEV_NOTICE, LOG_STMT, "sqlsrv_stream_read: asking for %1!d! bytes from stmt %2!d!, field %3!d!", count, ss->stmt->ctx.handle, ss->field );

    if( stream->eof ) {
        return 0;
    };

    if( ss == NULL ) DIE( "sqlsrv_stream* ss is NULL.  Shouldn't ever be NULL" );

    switch( ss->encoding ) {
        case SQLSRV_ENCODING_CHAR:
            c_type = SQL_C_CHAR;
            break;
        case SQLSRV_ENCODING_BINARY:
            c_type = SQL_C_BINARY;
            break;
        case CP_UTF8:
            c_type = SQL_C_WCHAR;
            count /= 2;    // divide the number of bytes we read by 2 since converting to UTF-8 can cause an increase in bytes
            break;
        default:
            DIE( "Uknown encoding type when reading from a stream" );
            break;
    }

    // get the data
    if( c_type == SQL_C_WCHAR ) {
        if( count > PHP_STREAM_BUFFER_SIZE ) {
            count = PHP_STREAM_BUFFER_SIZE;
        }
        // use a temporary buffer to retrieve from SQLGetData since we need to translate it to UTF-8 from UTF-16
        get_data_buffer = reinterpret_cast<char*>( ss->stmt->param_buffer );
    }

    r = SQLGetData( ss->stmt->ctx.handle, ss->field + 1, c_type, get_data_buffer, count, &read );
    // if the stream returns either no data, NULL data, or returns data < than the count requested then
    // we are at the "end of the stream" so we mark it
    if( r == SQL_NO_DATA || read == SQL_NULL_DATA || ( read != SQL_NO_TOTAL && static_cast<size_t>( read ) <= count )) {
        stream->eof = 1;
    }

    // convert it to UTF-8 if that's what's needed
    if( c_type == SQL_C_WCHAR ) {
        count *= 2;    // undo the shift to use the full buffer
        int w = WideCharToMultiByte( ss->encoding, 0, reinterpret_cast<LPCWSTR>( ss->stmt->param_buffer ),
                                     read >> 1, buf, count, NULL, NULL );
        if( w == 0 ) {
            stream->eof = 1;
            handle_error( &ss->stmt->ctx, LOG_STMT, "sqlsrv_stream_read", 
                          SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE TSRMLS_CC, get_last_error_message() );
            return 0;
        }
        read = w;
    }

    // if ODBC returns the 01004 (truncated string) warning, then we return the count minus the null terminator
    // if it's not a binary encoded field
    if( r == SQL_SUCCESS_WITH_INFO ) {
        SQLRETURN r;
        SQLCHAR state[ SQL_SQLSTATE_BUFSIZE ];
        SQLSMALLINT len;
        r = SQLGetDiagField( SQL_HANDLE_STMT, ss->stmt->ctx.handle, 1, SQL_DIAG_SQLSTATE, state, SQL_SQLSTATE_BUFSIZE, &len );
        if( is_truncated_warning( state ) || read == SQL_NO_TOTAL ) {
            if( c_type != SQL_C_BINARY ) {
                --count;
            }
            return count;
        }
    }
#pragma warning(push)
#pragma warning( disable: 4714 )
    CHECK_SQL_ERROR( r, ss->stmt, "sqlsrv_stream_read", NULL, stream->eof = 1; return 0; );
#pragma warning( pop )
    CHECK_SQL_WARNING( r, ss->stmt, "sqlsrv_stream_read", NULL );

    return read;
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

#if defined(OACR)       // OACR is an internal Microsoft static code analysis tool.
OACR_WARNING_PUSH
OACR_WARNING_DISABLE( UNANNOTATED_BUFFER, "STREAMS_DC is a Zend macro that evals to two char buffers." )
#endif

// open a stream and return the sqlsrv_stream_ops function table as part of the
// return value.  There is only one valid way to open a stream, using sqlsrv_get_field on
// certain field types.  A sqlsrv stream may only be opened in read mode.
static php_stream* sqlsrv_stream_opener( php_stream_wrapper* wrapper, 
                                  __in char*, __in char* mode, 
                                  int options, __in char **, 
                                  php_stream_context* 
                                  STREAMS_DC TSRMLS_DC )
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
#if defined(OACR)
OACR_WARNING_POP
#endif

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

// structure used by PHP to get the function table for opening, closing, etc. the stream
php_stream_wrapper g_sqlsrv_stream_wrapper = {
    &sqlsrv_stream_wrapper_ops,
    NULL,
    0
};

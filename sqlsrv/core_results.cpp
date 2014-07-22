//---------------------------------------------------------------------------------------------------------------------------------
// File: core_results.cpp
//
// Contents: Result sets
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

#include <functional>
#include <iostream>
#include <sstream>

using namespace core;

// conversion matrix
// each entry holds a function that can perform the conversion or NULL which means the conversion isn't supported
// this is initialized the first time the buffered result set is created.
sqlsrv_buffered_result_set::conv_matrix_t sqlsrv_buffered_result_set::conv_matrix;

namespace {

// *** internal types ***

#pragma warning(disable:4200)

// *** internal constants ***

const int INITIAL_FIELD_STRING_LEN = 256;    // base allocation size when retrieving a string field

// *** internal functions ***

// return an integral type rounded up to a certain number
template <int align, typename T>
T align_to( T number )
{
    DEBUG_SQLSRV_ASSERT( (number + align) > number, "Number to align overflowed" ); 
    return ((number % align) == 0) ? number : (number + align - (number % align));
}

// return a pointer address aligned to a certain address boundary
template <int align, typename T>
T* align_to( T* ptr )
{
    size_t p_value = (size_t) ptr;
    return align_to<align, size_t>( p_value );
}

// set the nth bit of the bitstream starting at ptr
void set_bit( void* ptr, unsigned int bit )
{
    unsigned char* null_bits = reinterpret_cast<unsigned char*>( ptr );
    null_bits += bit >> 3;
    *null_bits |= 1 << ( 7 - ( bit & 0x7 ));
}

// retrieve the nth bit from the bitstream starting at ptr
bool get_bit( void* ptr, unsigned int bit )
{
    unsigned char* null_bits = reinterpret_cast<unsigned char*>( ptr );
    null_bits += bit >> 3;
    return ((*null_bits & (1 << ( 7 - ( bit & 0x07 )))) != 0);
}

// read in LOB field during buffered result creation
SQLPOINTER read_lob_field( sqlsrv_stmt* stmt, SQLUSMALLINT field_index, sqlsrv_buffered_result_set::meta_data& meta,
                           unsigned long mem_used TSRMLS_DC );

// dtor for each row in the cache
void cache_row_dtor( void* data );

// convert a number to a string using locales
// There is an extra copy here, but given the size is short (usually <20 bytes) and the complications of
// subclassing a new streambuf just to avoid the copy, it's easier to do the copy
template <typename Char, typename Number>
SQLRETURN number_to_string( Number* number_data, __out void* buffer, SQLLEN buffer_length, __out SQLLEN* out_buffer_length,
                            sqlsrv_error_auto_ptr& last_error )
{
    std::basic_ostringstream<Char> os;
    std::locale loc;
    os.imbue( loc );
    std::use_facet< std::num_put< Char > >( loc ).put( std::basic_ostream<Char>::_Iter( os.rdbuf() ), os, ' ', *number_data );
    std::basic_string<Char>& str_num = os.str();

    if( os.fail() ) {
        last_error = new ( sqlsrv_malloc( sizeof( sqlsrv_error ))) sqlsrv_error( 
            (SQLCHAR*) "IMSSP", (SQLCHAR*) "Failed to convert number to string", -1 );
        return SQL_ERROR;
    }

    if( str_num.size() * sizeof(Char) + sizeof(Char) > (size_t) buffer_length ) {
        last_error = new ( sqlsrv_malloc( sizeof( sqlsrv_error ))) sqlsrv_error( 
            (SQLCHAR*) "HY090", (SQLCHAR*) "Buffer length too small to hold number as string", -1 );
        return SQL_ERROR;
    }

    *out_buffer_length = str_num.size() * sizeof(Char) + sizeof(Char); // include NULL terminator
    memcpy( buffer, str_num.c_str(), *out_buffer_length );

    return SQL_SUCCESS;
}

template <typename Number, typename Char>
SQLRETURN string_to_number( Char* string_data, SQLLEN str_len, __out void* buffer, SQLLEN buffer_length, 
                            __out SQLLEN* out_buffer_length, sqlsrv_error_auto_ptr& last_error )
{
    Number* number_data = reinterpret_cast<Number*>( buffer ); 
    std::locale loc;    // default locale should match system
    std::basic_istringstream<Char> is;
    is.str( string_data );
    is.imbue( loc );
    std::ios_base::iostate st = 0;

    std::use_facet< std::num_get< Char > >( loc ).get( std::basic_istream<Char>::_Iter( is.rdbuf( ) ), 
                                                       std::basic_istream<Char>::_Iter(0), is, st, *number_data );

    if( st & std::ios_base::failbit ) {
        last_error = new ( sqlsrv_malloc( sizeof( sqlsrv_error ))) sqlsrv_error( 
            (SQLCHAR*) "22003", (SQLCHAR*) "Numeric value out of range", 103 );
        return SQL_ERROR;        
    }

    *out_buffer_length = sizeof( Number );

    return SQL_SUCCESS;
}

// "closure" for the hash table destructor
struct row_dtor_closure {

    sqlsrv_buffered_result_set* results;
    BYTE* row_data;

    row_dtor_closure( sqlsrv_buffered_result_set* st, BYTE* row ) :
        results( st ), row_data( row )
    {
    }                      
};

sqlsrv_error* odbc_get_diag_rec( sqlsrv_stmt* odbc, SQLSMALLINT record_number )
{
    SQLWCHAR wsql_state[ SQL_SQLSTATE_BUFSIZE ];
    SQLCHAR* sql_state = reinterpret_cast<SQLCHAR*>( wsql_state );
    SQLWCHAR wnative_message[ SQL_MAX_MESSAGE_LENGTH + 1 ];
    sqlsrv_malloc_auto_ptr<SQLCHAR> native_message;
    SQLINTEGER native_code;
    SQLINTEGER message_len;

    SQLRETURN r = SQLGetDiagRecW( SQL_HANDLE_STMT, odbc->handle(), record_number, wsql_state, &native_code, wnative_message, 
                                  SQL_MAX_MESSAGE_LENGTH + 1, NULL );
    if( !SQL_SUCCEEDED( r ) || r == SQL_NO_DATA ) {
        return NULL;
    }

    // convert the error into the encoding of the context
    SQLSRV_ENCODING enc = odbc->encoding();
    if( enc == SQLSRV_ENCODING_DEFAULT ) {
        enc = odbc->conn->encoding();
    }

    // convert the error into the encoding of the context
    message_len = SQL_SQLSTATE_BUFSIZE * sizeof(WCHAR);
    convert_string_from_utf16( enc, reinterpret_cast<char**>( &sql_state ), message_len, 
                               false /*no free*/ );
    message_len = (SQL_MAX_MESSAGE_LENGTH + 1) * sizeof( WCHAR );
    native_message = reinterpret_cast<SQLCHAR*>( wnative_message );
    convert_string_from_utf16( enc, reinterpret_cast<char**>( &native_message ), message_len, 
                               false /*no free*/ );

    return new (sqlsrv_malloc( sizeof( sqlsrv_error ))) sqlsrv_error( (SQLCHAR*) sql_state, (SQLCHAR*) native_message, 
                                                                      native_code );
}

}   // namespace

// base class result set

sqlsrv_result_set::sqlsrv_result_set( sqlsrv_stmt* stmt ) :
    odbc( stmt )
{
}


// ODBC result set
// This object simply wraps ODBC function calls

sqlsrv_odbc_result_set::sqlsrv_odbc_result_set( sqlsrv_stmt* stmt ) : 
    sqlsrv_result_set( stmt )
{
}

sqlsrv_odbc_result_set::~sqlsrv_odbc_result_set( void )
{
}

SQLRETURN sqlsrv_odbc_result_set::fetch( SQLSMALLINT orientation, SQLLEN offset TSRMLS_DC )
{
    SQLSRV_ASSERT( odbc != NULL, "Invalid statement handle" );
    return core::SQLFetchScroll( odbc, orientation, offset TSRMLS_CC );
}

SQLRETURN sqlsrv_odbc_result_set::get_data( SQLUSMALLINT field_index, SQLSMALLINT target_type,
                                            __out SQLPOINTER buffer, SQLLEN buffer_length, __out SQLLEN* out_buffer_length,
                                            bool handle_warning TSRMLS_DC )
{
    SQLSRV_ASSERT( odbc != NULL, "Invalid statement handle" );
    return core::SQLGetData( odbc, field_index, target_type, buffer, buffer_length, out_buffer_length, handle_warning TSRMLS_CC );
}

SQLRETURN sqlsrv_odbc_result_set::get_diag_field( SQLSMALLINT record_number, SQLSMALLINT diag_identifier, 
                                                  __out SQLPOINTER diag_info_buffer, SQLSMALLINT buffer_length,
                                                  __out SQLSMALLINT* out_buffer_length TSRMLS_DC )
{
    SQLSRV_ASSERT( odbc != NULL, "Invalid statement handle" );
    return core::SQLGetDiagField( odbc, record_number, diag_identifier, diag_info_buffer, buffer_length, 
                                  out_buffer_length TSRMLS_CC );
}

sqlsrv_error* sqlsrv_odbc_result_set::get_diag_rec( SQLSMALLINT record_number )
{
    SQLSRV_ASSERT( odbc != NULL, "Invalid statement handle" );
    return odbc_get_diag_rec( odbc, record_number );
}

SQLLEN sqlsrv_odbc_result_set::row_count( TSRMLS_D )
{
    SQLSRV_ASSERT( odbc != NULL, "Invalid statement handle" );
    return core::SQLRowCount( odbc TSRMLS_CC );
}


// Buffered result set
// This class holds a result set in memory

sqlsrv_buffered_result_set::sqlsrv_buffered_result_set( sqlsrv_stmt* stmt TSRMLS_DC ) :
    sqlsrv_result_set( stmt ),
    cache(NULL),
    col_count(0),
    meta(NULL),
    current(0),
    last_field_index(-1),
    read_so_far(0)
{
    // 10 is an arbitrary number for now for the initial size of the cache
    ALLOC_HASHTABLE( cache );

    core::sqlsrv_zend_hash_init( *stmt, cache, 10 /* # of buckets */, NULL /* hashfn */, cache_row_dtor /*dtor*/, 0 /*persistent*/ 
                                 TSRMLS_CC );
    col_count = core::SQLNumResultCols( stmt TSRMLS_CC );
    // there is no result set to buffer
    if( col_count == 0 ) {
        return;
    }

    SQLULEN null_bytes = ( col_count / 8 ) + 1; // number of bits to reserve at the beginning of each row for NULL flags
    meta = static_cast<sqlsrv_buffered_result_set::meta_data*>( sqlsrv_malloc( col_count * 
                                                                               sizeof( sqlsrv_buffered_result_set::meta_data )));

    // set up the conversion matrix if this is the first time we're called
    if( conv_matrix.size() == 0 ) {

        conv_matrix[ SQL_C_CHAR ][ SQL_C_CHAR ] = &sqlsrv_buffered_result_set::to_same_string;
        conv_matrix[ SQL_C_CHAR ][ SQL_C_WCHAR ] = &sqlsrv_buffered_result_set::system_to_wide_string;
        conv_matrix[ SQL_C_CHAR ][ SQL_C_BINARY ] = &sqlsrv_buffered_result_set::to_binary_string;
        conv_matrix[ SQL_C_CHAR ][ SQL_C_DOUBLE ] = &sqlsrv_buffered_result_set::string_to_double;
        conv_matrix[ SQL_C_CHAR ][ SQL_C_LONG ] = &sqlsrv_buffered_result_set::string_to_long;
        conv_matrix[ SQL_C_WCHAR ][ SQL_C_WCHAR ] = &sqlsrv_buffered_result_set::to_same_string;
        conv_matrix[ SQL_C_WCHAR ][ SQL_C_BINARY ] = &sqlsrv_buffered_result_set::to_binary_string;
        conv_matrix[ SQL_C_WCHAR ][ SQL_C_CHAR ] = &sqlsrv_buffered_result_set::wide_to_system_string;
        conv_matrix[ SQL_C_WCHAR ][ SQL_C_DOUBLE ] = &sqlsrv_buffered_result_set::wstring_to_double;
        conv_matrix[ SQL_C_WCHAR ][ SQL_C_LONG ] = &sqlsrv_buffered_result_set::wstring_to_long;
        conv_matrix[ SQL_C_BINARY ][ SQL_C_BINARY ] = &sqlsrv_buffered_result_set::to_same_string;
        conv_matrix[ SQL_C_BINARY ][ SQL_C_CHAR ] = &sqlsrv_buffered_result_set::binary_to_system_string;
        conv_matrix[ SQL_C_BINARY ][ SQL_C_WCHAR ] = &sqlsrv_buffered_result_set::binary_to_wide_string;
        conv_matrix[ SQL_C_LONG ][ SQL_C_DOUBLE ] = &sqlsrv_buffered_result_set::long_to_double;
        conv_matrix[ SQL_C_LONG ][ SQL_C_LONG ] = &sqlsrv_buffered_result_set::to_long;
        conv_matrix[ SQL_C_LONG ][ SQL_C_BINARY ] = &sqlsrv_buffered_result_set::to_long;
        conv_matrix[ SQL_C_LONG ][ SQL_C_CHAR ] = &sqlsrv_buffered_result_set::long_to_system_string;
        conv_matrix[ SQL_C_LONG ][ SQL_C_WCHAR ] = &sqlsrv_buffered_result_set::long_to_wide_string;
        conv_matrix[ SQL_C_DOUBLE ][ SQL_C_DOUBLE ] = &sqlsrv_buffered_result_set::to_double;
        conv_matrix[ SQL_C_DOUBLE ][ SQL_C_BINARY ] = &sqlsrv_buffered_result_set::to_double;
        conv_matrix[ SQL_C_DOUBLE ][ SQL_C_CHAR ] = &sqlsrv_buffered_result_set::double_to_system_string;
        conv_matrix[ SQL_C_DOUBLE ][ SQL_C_LONG ] = &sqlsrv_buffered_result_set::double_to_long;
        conv_matrix[ SQL_C_DOUBLE ][ SQL_C_WCHAR ] = &sqlsrv_buffered_result_set::double_to_wide_string;
    }

    // get the meta data and calculate the size of a row buffer
    SQLULEN offset = null_bytes;
    for( SQLSMALLINT i = 0; i < col_count; ++i ) {

        core::SQLDescribeCol( stmt, i + 1, NULL, 0, NULL, &meta[i].type, &meta[i].length, &meta[i].scale, NULL TSRMLS_CC );

        offset = align_to<4>( offset );
        meta[i].offset = offset;

        switch( meta[i].type ) {

            // these types are the display size
            case SQL_BIGINT:
            case SQL_DECIMAL:
            case SQL_GUID:
            case SQL_NUMERIC:
                core::SQLColAttribute( stmt, i + 1, SQL_DESC_DISPLAY_SIZE, NULL, 0, NULL,
                                       reinterpret_cast<SQLLEN*>( &meta[i].length ) TSRMLS_CC );
                meta[i].length += sizeof( char ) + sizeof( SQLULEN ); // null terminator space
                offset += meta[i].length;
                break;

            // these types are the column size
            case SQL_BINARY:
            case SQL_CHAR:
            case SQL_SS_UDT:
            case SQL_VARBINARY:
            case SQL_VARCHAR:
                // var* field types are length prefixed
                if( meta[i].length == sqlsrv_buffered_result_set::meta_data::SIZE_UNKNOWN ) {
                    offset += sizeof( void* );
                }
                else {
                    meta[i].length += sizeof( SQLULEN ) + sizeof( char ); // length plus null terminator space
                    offset += meta[i].length;
                }
                break;

            case SQL_WCHAR:
            case SQL_WVARCHAR:
                if( meta[i].length == sqlsrv_buffered_result_set::meta_data::SIZE_UNKNOWN ) {
                    offset += sizeof( void* );
                }
                else {
                    meta[i].length *= sizeof( WCHAR );
                    meta[i].length += sizeof( SQLULEN ) + sizeof( WCHAR ); // length plus null terminator space
                    offset += meta[i].length;
                }
                break;

            // these types are LOBs
            case SQL_LONGVARBINARY:
            case SQL_LONGVARCHAR:
            case SQL_WLONGVARCHAR:
            case SQL_SS_XML:
                meta[i].length = sqlsrv_buffered_result_set::meta_data::SIZE_UNKNOWN;
                offset += sizeof( void* );
                break;

            // these types are the ISO date size
            case SQL_DATETIME:
            case SQL_TYPE_DATE:
            case SQL_SS_TIME2:
            case SQL_SS_TIMESTAMPOFFSET:
            case SQL_TYPE_TIMESTAMP:
                core::SQLColAttribute( stmt, i + 1, SQL_DESC_DISPLAY_SIZE, NULL, 0, NULL, 
                                       reinterpret_cast<SQLLEN*>( &meta[i].length ) TSRMLS_CC );
                meta[i].length += sizeof(char) + sizeof( SQLULEN );  // null terminator space
                offset += meta[i].length;
                break;

            // these types are the native size
            case SQL_BIT:
            case SQL_INTEGER:
            case SQL_SMALLINT:
            case SQL_TINYINT:
                meta[i].length = sizeof( long );
                offset += meta[i].length;
                break;

            case SQL_REAL:
            case SQL_FLOAT:
                meta[i].length = sizeof( double );
                offset += meta[i].length;
                break;

            default:
                SQLSRV_ASSERT( false, "Unknown type in sqlsrv_buffered_query::sqlsrv_buffered_query" );
                break;
        }

        switch( meta[i].type ) {

            case SQL_BIGINT:
            case SQL_CHAR:
            case SQL_DATETIME:
            case SQL_DECIMAL:
            case SQL_GUID:
            case SQL_NUMERIC:
            case SQL_LONGVARCHAR:
            case SQL_TYPE_DATE:
            case SQL_SS_TIME2:
            case SQL_SS_TIMESTAMPOFFSET:
            case SQL_SS_XML:
            case SQL_TYPE_TIMESTAMP:
            case SQL_VARCHAR:
                meta[i].c_type = SQL_C_CHAR;
                break;

            case SQL_SS_UDT:
            case SQL_LONGVARBINARY:
            case SQL_BINARY:
            case SQL_VARBINARY:
                meta[i].c_type = SQL_C_BINARY;
                break;

            case SQL_WLONGVARCHAR:
            case SQL_WCHAR:
            case SQL_WVARCHAR:
                meta[i].c_type = SQL_C_WCHAR;
                break;

            case SQL_BIT:
            case SQL_INTEGER:
            case SQL_SMALLINT:
            case SQL_TINYINT:
                meta[i].c_type = SQL_C_LONG;
                break;

            case SQL_REAL:
            case SQL_FLOAT:
                meta[i].c_type = SQL_C_DOUBLE;
                break;

            default:
                SQLSRV_ASSERT( false, "Unknown type in sqlsrv_buffered_query::sqlsrv_buffered_query" );
                break;
        }

    }

    // read the data into the cache
    // (offset from the above loop has the size of the row buffer necessary)
    unsigned long mem_used = 0;
    unsigned long row_count = 0;

    while( core::SQLFetchScroll( stmt, SQL_FETCH_NEXT, 0 TSRMLS_CC ) != SQL_NO_DATA ) {
        
        // allocate the row buffer
        unsigned char* row = static_cast<unsigned char*>( sqlsrv_malloc( offset ));
        memset( row, 0, offset );

        // read the fields into the row buffer
        for( SQLSMALLINT i = 0; i < col_count; ++i ) {

            SQLLEN out_buffer_temp = SQL_NULL_DATA;
            SQLPOINTER buffer;
            SQLLEN* out_buffer_length = &out_buffer_temp;

            switch( meta[i].c_type ) {

                case SQL_C_CHAR:
                case SQL_C_WCHAR:
                case SQL_C_BINARY:
                    if( meta[i].length == sqlsrv_buffered_result_set::meta_data::SIZE_UNKNOWN ) {

                        out_buffer_length = &out_buffer_temp;
                        SQLPOINTER* lob_addr = reinterpret_cast<SQLPOINTER*>( &row[ meta[i].offset ] );
                        *lob_addr = read_lob_field( stmt, i, meta[i], mem_used TSRMLS_CC );
                        // a NULL pointer means NULL field
                        if( *lob_addr == NULL ) {
                            *out_buffer_length = SQL_NULL_DATA;
                        }
                        else {
                            *out_buffer_length = **reinterpret_cast<SQLLEN**>( lob_addr );
                            mem_used += *out_buffer_length;
                        }
                    }
                    else {

                        mem_used += meta[i].length;
                        CHECK_CUSTOM_ERROR( mem_used > stmt->buffered_query_limit * 1024, stmt, 
                                            SQLSRV_ERROR_BUFFER_LIMIT_EXCEEDED, stmt->buffered_query_limit ) {

                            throw core::CoreException();
                        }

                        buffer = row + meta[i].offset + sizeof( SQLULEN );
                        out_buffer_length = reinterpret_cast<SQLLEN*>( row + meta[i].offset );
                        core::SQLGetData( stmt, i + 1, meta[i].c_type, buffer, meta[i].length, out_buffer_length, 
                                          false TSRMLS_CC );
                    }
                    break;

                case SQL_C_LONG:
                case SQL_C_DOUBLE:
                    {
                        mem_used += meta[i].length;
                        CHECK_CUSTOM_ERROR( mem_used > stmt->buffered_query_limit * 1024, stmt, 
                                            SQLSRV_ERROR_BUFFER_LIMIT_EXCEEDED, stmt->buffered_query_limit ) {

                            throw core::CoreException();
                        }
                        buffer = row + meta[i].offset;
                        out_buffer_length = &out_buffer_temp;
                        core::SQLGetData( stmt, i + 1, meta[i].c_type, buffer, meta[i].length, out_buffer_length, 
                                          false TSRMLS_CC );
                    }
                    break;                        

                default:
                    SQLSRV_ASSERT( false, "Unknown C type" );
                    break;
            }

            if( *out_buffer_length == SQL_NULL_DATA ) {
                unsigned char* null_bits = reinterpret_cast<unsigned char*>( row );
                set_bit( row, i );
            }
        }

        SQLSRV_ASSERT( row_count < LONG_MAX, "Hard maximum of 2 billion rows exceeded in a buffered query" );

        // add it to the cache
        row_dtor_closure cl( this, row );
        sqlsrv_zend_hash_next_index_insert( *stmt, cache, &cl, sizeof( cl ) TSRMLS_CC );
    }

}

sqlsrv_buffered_result_set::~sqlsrv_buffered_result_set( void )
{
    // free the rows
    if( cache ) {
        zend_hash_destroy( cache );
        FREE_HASHTABLE( cache );
        cache = NULL;
    }

    // free the meta data
    if( meta ) {
        efree( meta );
        meta = NULL;
    }
}

SQLRETURN sqlsrv_buffered_result_set::fetch( SQLSMALLINT orientation, SQLLEN offset TSRMLS_DC )
{
    last_error = NULL;
    last_field_index = -1;
    read_so_far = 0;

    switch( orientation ) {

        case SQL_FETCH_NEXT:
            offset = 1;
            orientation = SQL_FETCH_RELATIVE;
            break;
        case SQL_FETCH_PRIOR:
            offset = -1;
            orientation = SQL_FETCH_RELATIVE;
            break;
    }

    switch( orientation ) {

        case SQL_FETCH_FIRST:
            current = 1;
            break;
        case SQL_FETCH_LAST:
            current = row_count( TSRMLS_C );
            break;
        case SQL_FETCH_ABSOLUTE:
            current = offset;
            break;
        case SQL_FETCH_RELATIVE:
            current += offset;
            break;
        default:
            SQLSRV_ASSERT( false, "Invalid fetch orientation.  Should have been caught before here." );
            break;
    }

    // check validity of current row
    // the cursor can never get further away than just before the first row
    if( current <= 0 && ( offset < 0 || orientation != SQL_FETCH_RELATIVE )) {
        current = 0;
        return SQL_NO_DATA;
    }

    // the cursor can never get further away than just after the last row
    if( current > row_count( TSRMLS_C ) || ( current <= 0 && offset > 0 ) /*overflow condition*/ ) {
        current = row_count( TSRMLS_C ) + 1;
        return SQL_NO_DATA;
    }

    return SQL_SUCCESS;
}

SQLRETURN sqlsrv_buffered_result_set::get_data( SQLUSMALLINT field_index, SQLSMALLINT target_type,
                                                __out SQLPOINTER buffer, SQLLEN buffer_length, __out SQLLEN* out_buffer_length,
                                                bool handle_warning TSRMLS_DC )
{
    last_error = NULL;
    field_index--;      // convert from 1 based to 0 based
    SQLSRV_ASSERT( field_index < column_count(), "Invalid field index requested" );

    if( field_index != last_field_index ) {
        last_field_index = field_index;
        read_so_far = 0;
    }

    unsigned char* row = get_row();

    // if the field is null, then return SQL_NULL_DATA
    if( get_bit( row, field_index )) {
        *out_buffer_length = SQL_NULL_DATA;
        return SQL_SUCCESS;
    }

    // check to make sure the conversion type is valid
    if( conv_matrix.find( meta[ field_index ].c_type ) == conv_matrix.end() ||
        conv_matrix.find( meta[ field_index ].c_type )->second.find( target_type ) == 
            conv_matrix.find( meta[ field_index ].c_type )->second.end() ) {

        last_error = new (sqlsrv_malloc( sizeof( sqlsrv_error ))) 
            sqlsrv_error( (SQLCHAR*) "07006", 
                          (SQLCHAR*) "Restricted data type attribute violation", 0 );
        return SQL_ERROR;
    }

    return (( this )->*( conv_matrix[ meta[ field_index ].c_type ][ target_type ] ))( field_index, buffer, buffer_length,
                                                                                      out_buffer_length );
}

SQLRETURN sqlsrv_buffered_result_set::get_diag_field( SQLSMALLINT record_number, SQLSMALLINT diag_identifier, 
                                                      __out SQLPOINTER diag_info_buffer, SQLSMALLINT buffer_length,
                                                      __out SQLSMALLINT* out_buffer_length TSRMLS_DC )
{
    SQLSRV_ASSERT( record_number == 1, "Only record number 1 can be fetched by sqlsrv_buffered_result_set::get_diag_field" );
    SQLSRV_ASSERT( diag_identifier == SQL_DIAG_SQLSTATE, 
                   "Only SQL_DIAG_SQLSTATE can be fetched by sqlsrv_buffered_result_set::get_diag_field" );
    SQLSRV_ASSERT( buffer_length >= SQL_SQLSTATE_BUFSIZE, 
                   "Buffer not big enough to return SQLSTATE in sqlsrv_buffered_result_set::get_diag_field" );

    if( last_error == NULL ) {
        return SQL_NO_DATA;
    }

    SQLSRV_ASSERT( last_error->sqlstate != NULL, 
                   "Must have a SQLSTATE in a valid last_error in sqlsrv_buffered_result_set::get_diag_field" );

    memcpy( diag_info_buffer, last_error->sqlstate, min( buffer_length, SQL_SQLSTATE_BUFSIZE ));

    return SQL_SUCCESS;
}

unsigned char* sqlsrv_buffered_result_set::get_row( void )
{
    row_dtor_closure* cl_ptr;
    int zr = zend_hash_index_find( cache, current - 1, (void**) &cl_ptr );
    SQLSRV_ASSERT( zr == SUCCESS, "Failed to find row %1!d! in the cache", current );
    return cl_ptr->row_data;
}

sqlsrv_error* sqlsrv_buffered_result_set::get_diag_rec( SQLSMALLINT record_number )
{
    // we only hold a single error if there is one, otherwise return the ODBC error(s)
    if( last_error == NULL ) {
        return odbc_get_diag_rec( odbc, record_number );
    }
    if( record_number > 1 ) {
        return NULL;
    }
    	
    return new (sqlsrv_malloc( sizeof( sqlsrv_error ))) 
        sqlsrv_error( last_error->sqlstate, last_error->native_message, last_error->native_code );
}

SQLLEN sqlsrv_buffered_result_set::row_count( TSRMLS_D )
{
    last_error = NULL;

    return zend_hash_num_elements( cache );
}

// private functions
template <typename Char>
SQLRETURN binary_to_string( SQLCHAR* field_data, SQLLEN& read_so_far,  __out void* buffer, 
                                                        SQLLEN buffer_length, __out SQLLEN* out_buffer_length, 
                                                        sqlsrv_error_auto_ptr& out_error )
{
    // hex characters for the conversion loop below
    static char hex_chars[] = "0123456789ABCDEF";

    SQLSRV_ASSERT( out_error == NULL, "Pending error for sqlsrv_buffered_results_set::binary_to_string" );

    SQLRETURN r = SQL_ERROR;

    // Set the amount of space necessary for null characters at the end of the data.
    SQLSMALLINT extra = sizeof(Char);

    SQLSRV_ASSERT( ((buffer_length - extra) % (extra * 2)) == 0, "Must be multiple of 2 for binary to system string or "
                   "multiple of 4 for binary to wide string" );

    // all fields will be treated as ODBC returns varchar(max) fields:
    // the entire length of the string is returned the first
    // call in out_buffer_len.  Successive calls return how much is
    // left minus how much has already been read by previous reads
    // *2 is for each byte to hex conversion and * extra is for either system or wide string allocation
    *out_buffer_length = (*reinterpret_cast<SQLLEN*>( field_data - sizeof( SQLULEN )) - read_so_far) * 2 * extra;

    // copy as much as we can into the buffer
    SQLLEN to_copy;
    if( buffer_length < *out_buffer_length + extra ) {
        to_copy = (buffer_length - extra);
        out_error = new ( sqlsrv_malloc( sizeof( sqlsrv_error ))) 
            sqlsrv_error( (SQLCHAR*) "01004", (SQLCHAR*) "String data, right truncated", -1 );
        r = SQL_SUCCESS_WITH_INFO;
    }
    else {
        r = SQL_SUCCESS;
        to_copy = *out_buffer_length;
    }

    // if there are bytes to copy as hex
    if( to_copy > 0 ) {
        // quick hex conversion routine
        Char* h = reinterpret_cast<Char*>( buffer );
        BYTE* b = reinterpret_cast<BYTE*>( field_data );
        // to_copy contains the number of bytes to copy, so we divide the number in half (or quarter)
        // to get the number of hex digits we can copy
        SQLLEN to_copy_hex = to_copy / (2 * extra);
        for( int i = 0; i < to_copy_hex; ++i ) {
            *h = hex_chars[ (*b & 0xf0) >> 4 ];
            h++;
            *h = hex_chars[ (*b++ & 0x0f) ];
            h++;
        }
        read_so_far += to_copy_hex;
        *h = static_cast<Char>( 0 );
    }
    else {
        reinterpret_cast<char*>( buffer )[0] = '\0';
    }

    return r;
}

SQLRETURN sqlsrv_buffered_result_set::binary_to_system_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                               __out SQLLEN* out_buffer_length )
{
    SQLCHAR* row = get_row();
    SQLCHAR* field_data = NULL;

    if( meta[ field_index ].length == sqlsrv_buffered_result_set::meta_data::SIZE_UNKNOWN ) {

        field_data = *reinterpret_cast<SQLCHAR**>( &row[ meta[ field_index ].offset ] ) + sizeof( SQLULEN );
    }
    else {

        field_data = &row[ meta[ field_index ].offset ] + sizeof( SQLULEN );
    }

    return binary_to_string<char>( field_data, read_so_far, buffer, buffer_length, out_buffer_length, last_error );
}

SQLRETURN sqlsrv_buffered_result_set::binary_to_wide_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                             __out SQLLEN* out_buffer_length )
{
    SQLCHAR* row = get_row();
    SQLCHAR* field_data = NULL;

    if( meta[ field_index ].length == sqlsrv_buffered_result_set::meta_data::SIZE_UNKNOWN ) {

        field_data = *reinterpret_cast<SQLCHAR**>( &row[ meta[ field_index ].offset ] ) + sizeof( SQLULEN );
    }
    else {

        field_data = &row[ meta[ field_index ].offset ] + sizeof( SQLULEN );
    }

    return binary_to_string<WCHAR>( field_data, read_so_far, buffer, buffer_length, out_buffer_length, last_error );
}


SQLRETURN sqlsrv_buffered_result_set::double_to_long( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                      __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( meta[ field_index ].c_type == SQL_C_DOUBLE, "Invalid conversion to long" );
    SQLSRV_ASSERT( buffer_length >= sizeof(LONG), "Buffer length must be able to find a long in "
                   "sqlsrv_buffered_result_set::double_to_long" );

    unsigned char* row = get_row();
    double* double_data = reinterpret_cast<double*>( &row[ meta[ field_index ].offset ] );
    LONG* long_data = reinterpret_cast<LONG*>( buffer );

    if( *double_data < double( LONG_MIN ) || *double_data > double( LONG_MAX )) {
        last_error = new (sqlsrv_malloc( sizeof( sqlsrv_error ))) sqlsrv_error( (SQLCHAR*) "22003", 
                                                                                (SQLCHAR*) "Numeric value out of range", 0 );
        return SQL_ERROR;
    }

    if( *double_data != floor( *double_data )) {
        last_error = new (sqlsrv_malloc( sizeof( sqlsrv_error ))) sqlsrv_error( (SQLCHAR*) "01S07", 
                                                                                (SQLCHAR*) "Fractional truncation", 0 );
        return SQL_SUCCESS_WITH_INFO;
    }

    *long_data = static_cast<LONG>( *double_data );
    *out_buffer_length = sizeof( LONG );

    return SQL_SUCCESS;
}

SQLRETURN sqlsrv_buffered_result_set::double_to_system_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                               __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( meta[ field_index ].c_type == SQL_C_DOUBLE, "Invalid conversion to system string" );
    SQLSRV_ASSERT( buffer_length > 0, "Buffer length must be > 0 in sqlsrv_buffered_result_set::double_to_system_string" );

    unsigned char* row = get_row();
    double* double_data = reinterpret_cast<double*>( &row[ meta[ field_index ].offset ] );

    return number_to_string<char>( double_data, buffer, buffer_length, out_buffer_length, last_error );
}

SQLRETURN sqlsrv_buffered_result_set::double_to_wide_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                             __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( meta[ field_index ].c_type == SQL_C_DOUBLE, "Invalid conversion to wide string" );
    SQLSRV_ASSERT( buffer_length > 0, "Buffer length must be > 0 in sqlsrv_buffered_result_set::double_to_wide_string" );

    unsigned char* row = get_row();
    double* double_data = reinterpret_cast<double*>( &row[ meta[ field_index ].offset ] );

    return number_to_string<WCHAR>( double_data, buffer, buffer_length, out_buffer_length, last_error );
}

SQLRETURN sqlsrv_buffered_result_set::long_to_double( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                      __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( meta[ field_index ].c_type == SQL_C_LONG, "Invalid conversion to long" );
    SQLSRV_ASSERT( buffer_length >= sizeof(double), "Buffer length must be able to find a long in sqlsrv_buffered_result_set::double_to_long" );

    unsigned char* row = get_row();
    double* double_data = reinterpret_cast<double*>( buffer );
    LONG* long_data = reinterpret_cast<LONG*>( &row[ meta[ field_index ].offset ] );

    *double_data = static_cast<LONG>( *long_data );
    *out_buffer_length = sizeof( double );

    return SQL_SUCCESS;
}
SQLRETURN sqlsrv_buffered_result_set::long_to_system_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                             __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( meta[ field_index ].c_type == SQL_C_LONG, "Invalid conversion to system string" );
    SQLSRV_ASSERT( buffer_length > 0, "Buffer length must be > 0 in sqlsrv_buffered_result_set::long_to_system_string" );

    unsigned char* row = get_row();
    LONG* long_data = reinterpret_cast<LONG*>( &row[ meta[ field_index ].offset ] );

    return number_to_string<char>( long_data, buffer, buffer_length, out_buffer_length, last_error );
}

SQLRETURN sqlsrv_buffered_result_set::long_to_wide_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                           __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( meta[ field_index ].c_type == SQL_C_LONG, "Invalid conversion to wide string" );
    SQLSRV_ASSERT( buffer_length > 0, "Buffer length must be > 0 in sqlsrv_buffered_result_set::long_to_wide_string" );

    unsigned char* row = get_row();
    LONG* long_data = reinterpret_cast<LONG*>( &row[ meta[ field_index ].offset ] );

    return number_to_string<WCHAR>( long_data, buffer, buffer_length, out_buffer_length, last_error );
}

SQLRETURN sqlsrv_buffered_result_set::string_to_double( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                        __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( meta[ field_index ].c_type == SQL_C_CHAR, "Invalid conversion from string to double" );
    SQLSRV_ASSERT( buffer_length >= sizeof( double ), "Buffer needs to be big enough to hold a double" );

    unsigned char* row = get_row();
    char* string_data = reinterpret_cast<char*>( &row[ meta[ field_index ].offset ] ) + sizeof( SQLULEN );

    return string_to_number<double>( string_data, meta[ field_index ].length, buffer, buffer_length, out_buffer_length, last_error );
}

SQLRETURN sqlsrv_buffered_result_set::wstring_to_double( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                         __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( meta[ field_index ].c_type == SQL_C_WCHAR, "Invalid conversion from wide string to double" );
    SQLSRV_ASSERT( buffer_length >= sizeof( double ), "Buffer needs to be big enough to hold a double" );

    unsigned char* row = get_row();
    SQLWCHAR* string_data = reinterpret_cast<SQLWCHAR*>( &row[ meta[ field_index ].offset ] ) + sizeof( SQLULEN ) / sizeof( SQLWCHAR );

    return string_to_number<double>( string_data, meta[ field_index ].length, buffer, buffer_length, out_buffer_length, last_error );
}

SQLRETURN sqlsrv_buffered_result_set::string_to_long( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                      __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( meta[ field_index ].c_type == SQL_C_CHAR, "Invalid conversion from string to long" );
    SQLSRV_ASSERT( buffer_length >= sizeof( LONG ), "Buffer needs to be big enough to hold a long" );

    unsigned char* row = get_row();
    char* string_data = reinterpret_cast<char*>( &row[ meta[ field_index ].offset ] ) + sizeof( SQLULEN );

    return string_to_number<LONG>( string_data, meta[ field_index ].length, buffer, buffer_length, out_buffer_length, last_error );
}

SQLRETURN sqlsrv_buffered_result_set::wstring_to_long( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                      __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( meta[ field_index ].c_type == SQL_C_WCHAR, "Invalid conversion from wide string to long" );
    SQLSRV_ASSERT( buffer_length >= sizeof( LONG ), "Buffer needs to be big enough to hold a long" );

    unsigned char* row = get_row();
    SQLWCHAR* string_data = reinterpret_cast<SQLWCHAR*>( &row[ meta[ field_index ].offset ] ) + sizeof( SQLULEN ) / sizeof( SQLWCHAR );

    return string_to_number<LONG>( string_data, meta[ field_index ].length, buffer, buffer_length, out_buffer_length, last_error );
}

SQLRETURN sqlsrv_buffered_result_set::system_to_wide_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                             __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( last_error == NULL, "Pending error for sqlsrv_buffered_results_set::system_to_wide_string" );
    SQLSRV_ASSERT( buffer_length % 2 == 0, "Odd buffer length passed to sqlsrv_buffered_result_set::system_to_wide_string" );

    SQLRETURN r = SQL_ERROR;
    unsigned char* row = get_row();

    SQLCHAR* field_data = NULL;
    SQLULEN field_len = NULL;

    if( meta[ field_index ].length == sqlsrv_buffered_result_set::meta_data::SIZE_UNKNOWN ) {

        field_len = **reinterpret_cast<SQLLEN**>( &row[ meta[ field_index ].offset ] );
        field_data = *reinterpret_cast<SQLCHAR**>( &row[ meta[ field_index ].offset ] ) + sizeof( SQLULEN ) + read_so_far;
    }
    else {

        field_len = *reinterpret_cast<SQLLEN*>( &row[ meta[ field_index ].offset ] );
        field_data = &row[ meta[ field_index ].offset ] + sizeof( SQLULEN ) + read_so_far;
    }

    // all fields will be treated as ODBC returns varchar(max) fields:
    // the entire length of the string is returned the first
    // call in out_buffer_len.  Successive calls return how much is
    // left minus how much has already been read by previous reads
    *out_buffer_length = (*reinterpret_cast<SQLLEN*>( field_data - sizeof( SQLULEN )) - read_so_far) * sizeof(WCHAR);

    // to_copy is the number of characters to copy, not including the null terminator
    // supposedly it will never happen that a Windows MBCS will explode to UTF-16 surrogate pair.
    SQLLEN to_copy;

    if( (size_t) buffer_length < (field_len - read_so_far + sizeof(char)) * sizeof(WCHAR)) {

        to_copy = (buffer_length - sizeof(WCHAR)) / sizeof(WCHAR);    // to_copy is the number of characters
        last_error = new ( sqlsrv_malloc( sizeof( sqlsrv_error ))) 
            sqlsrv_error( (SQLCHAR*) "01004", (SQLCHAR*) "String data, right truncated", -1 );
        r = SQL_SUCCESS_WITH_INFO;
    }
    else {

        r = SQL_SUCCESS;
        to_copy = field_len - read_so_far;
    }

    if( to_copy > 0 ) {

        bool tried_again = false;
        do {
            int ch_space = MultiByteToWideChar( CP_ACP, MB_ERR_INVALID_CHARS, (LPCSTR) field_data, to_copy, 
                                                (LPWSTR) buffer, to_copy );
            if( ch_space == 0 ) {

                switch( GetLastError() ) {

                    case ERROR_NO_UNICODE_TRANSLATION:
                        // the theory here is the conversion failed because the end of the buffer we provided contained only 
                        // half a character at the end
                        if( !tried_again ) {
                            to_copy--;
                            tried_again = true;
                            continue;
                        }
                        last_error = new ( sqlsrv_malloc( sizeof( sqlsrv_error )))
                            sqlsrv_error( (SQLCHAR*) "IMSSP", (SQLCHAR*) "Invalid Unicode translation", -1 );
                        break;
                    default:
                        SQLSRV_ASSERT( false, "Severe error translating Unicode" );
                        break;
                }

                return SQL_ERROR;
            }

            ((WCHAR*)buffer)[ to_copy ] = L'\0';
            read_so_far += to_copy;
            break;

        } while( true );
    }
    else {
        reinterpret_cast<WCHAR*>( buffer )[0] = L'\0';
    }

    return r;
}

SQLRETURN sqlsrv_buffered_result_set::to_same_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                       __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( last_error == NULL, "Pending error for sqlsrv_buffered_results_set::to_same_string" );

    SQLRETURN r = SQL_ERROR;
    unsigned char* row = get_row();

    // Set the amount of space necessary for null characters at the end of the data.
    SQLSMALLINT extra = 0;

    switch( meta[ field_index ].c_type ) {
        case SQL_C_WCHAR:
            extra = sizeof( SQLWCHAR );
            break;
        case SQL_C_BINARY:
            extra = 0;
            break;
        case SQL_C_CHAR:
            extra = sizeof( SQLCHAR );
            break;
        default:
            SQLSRV_ASSERT( false, "Invalid type in get_string_data" );
            break;
    }

    SQLCHAR* field_data = NULL;

    if( meta[ field_index ].length == sqlsrv_buffered_result_set::meta_data::SIZE_UNKNOWN ) {

        field_data = *reinterpret_cast<SQLCHAR**>( &row[ meta[ field_index ].offset ] ) + sizeof( SQLULEN );
    }
    else {

        field_data = &row[ meta[ field_index ].offset ] + sizeof( SQLULEN );
    }

    // all fields will be treated as ODBC returns varchar(max) fields:
    // the entire length of the string is returned the first
    // call in out_buffer_len.  Successive calls return how much is
    // left minus how much has already been read by previous reads
    *out_buffer_length = *reinterpret_cast<SQLLEN*>( field_data - sizeof( SQLULEN )) - read_so_far;

    // copy as much as we can into the buffer
    SQLLEN to_copy;
    if( buffer_length < *out_buffer_length + extra ) {
        to_copy = buffer_length - extra;
        last_error = new ( sqlsrv_malloc( sizeof( sqlsrv_error ))) 
            sqlsrv_error( (SQLCHAR*) "01004", (SQLCHAR*) "String data, right truncated", -1 );
        r = SQL_SUCCESS_WITH_INFO;
    }
    else {
        r = SQL_SUCCESS;
        to_copy = *out_buffer_length;
    }

    SQLSRV_ASSERT( to_copy >= 0, "Negative field length calculated in buffered result set" );

    if( to_copy > 0 ) {
        memcpy( buffer, field_data + read_so_far, to_copy );
        read_so_far += to_copy;
    }
    if( extra ) {
        OACR_WARNING_SUPPRESS( 26001, "Buffer length verified above" );
        memcpy( reinterpret_cast<SQLCHAR*>( buffer ) + to_copy, L"\0", extra ); 
    }

    return r;
}

SQLRETURN sqlsrv_buffered_result_set::wide_to_system_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                             __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( last_error == NULL, "Pending error for sqlsrv_buffered_results_set::wide_to_system_string" );

    SQLRETURN r = SQL_ERROR;
    unsigned char* row = get_row();

    SQLCHAR* field_data = NULL;
    SQLULEN field_len = NULL;

    // if this is the first time called for this field, just convert the entire string to system first then
    // use that to read from instead of converting chunk by chunk.  This is because it's impossible to know
    // the total length of the string for output_buffer_length without doing the conversion and returning
    // SQL_NO_TOTAL is not consistent with what our other conversion functions do (system_to_wide_string and
    // to_same_string).

    if( read_so_far == 0 ) {

        if( meta[ field_index ].length == sqlsrv_buffered_result_set::meta_data::SIZE_UNKNOWN ) {

            field_len = **reinterpret_cast<SQLLEN**>( &row[ meta[ field_index ].offset ] );
            field_data = *reinterpret_cast<SQLCHAR**>( &row[ meta[ field_index ].offset ] ) + sizeof( SQLULEN ) + read_so_far;
        }
        else {

            field_len = *reinterpret_cast<SQLLEN*>( &row[ meta[ field_index ].offset ] );
            field_data = &row[ meta[ field_index ].offset ] + sizeof( SQLULEN ) + read_so_far;
        }

        BOOL default_char_used = FALSE;
        char default_char = '?';

        // allocate enough to handle WC -> DBCS conversion if it happens
        temp_string = reinterpret_cast<SQLCHAR*>( sqlsrv_malloc( field_len, sizeof( char ), sizeof(char)));
        temp_length = WideCharToMultiByte( CP_ACP, 0, (LPCWSTR) field_data, field_len / sizeof(WCHAR), 
                                           (LPSTR) temp_string.get(), field_len, &default_char, &default_char_used );
        
        if( temp_length == 0 ) {

            switch( GetLastError() ) {

                case ERROR_NO_UNICODE_TRANSLATION:
                    last_error = new ( sqlsrv_malloc( sizeof( sqlsrv_error )))
                        sqlsrv_error( (SQLCHAR*) "IMSSP", (SQLCHAR*) "Invalid Unicode translation", -1 );
                    break;
                default:
                    SQLSRV_ASSERT( false, "Severe error translating Unicode" );
                    break;
            }

            return SQL_ERROR;
        }
    }

    *out_buffer_length = (temp_length - read_so_far);

    SQLLEN to_copy = 0;

    if( (size_t) buffer_length < (temp_length - read_so_far + sizeof(char))) {

        to_copy = buffer_length - sizeof(char);
        last_error = new ( sqlsrv_malloc( sizeof( sqlsrv_error ))) 
            sqlsrv_error( (SQLCHAR*) "01004", (SQLCHAR*) "String data, right truncated", -1 );
        r = SQL_SUCCESS_WITH_INFO;
    }
    else {

        to_copy = (temp_length - read_so_far);
        r = SQL_SUCCESS;
    }

    if( to_copy > 0 ) {

        memcpy( buffer, temp_string.get() + read_so_far, to_copy );
    }
    SQLSRV_ASSERT( to_copy >= 0, "Invalid field copy length" );
    OACR_WARNING_SUPPRESS( BUFFER_UNDERFLOW, "Buffer length verified above" );
    ((SQLCHAR*) buffer)[ to_copy ] = '\0';
    read_so_far += to_copy;

    return r;
}


SQLRETURN sqlsrv_buffered_result_set::to_binary_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                         __out SQLLEN* out_buffer_length )
{
    return to_same_string( field_index, buffer, buffer_length, out_buffer_length );
}

SQLRETURN sqlsrv_buffered_result_set::to_long( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( meta[ field_index ].c_type == SQL_C_LONG, "Invlid conversion to long" );
    SQLSRV_ASSERT( buffer_length >= sizeof( LONG ), "Buffer too small for SQL_C_LONG" );    // technically should ignore this

    unsigned char* row = get_row();
    LONG* long_data = reinterpret_cast<LONG*>( &row[ meta[ field_index ].offset ] );

    memcpy( buffer, long_data, sizeof( LONG ));
    *out_buffer_length = sizeof( LONG );

    return SQL_SUCCESS;
}

SQLRETURN sqlsrv_buffered_result_set::to_double( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                  __out SQLLEN* out_buffer_length )
{
    SQLSRV_ASSERT( meta[ field_index ].c_type == SQL_C_DOUBLE, "Invlid conversion to double" );
    SQLSRV_ASSERT( buffer_length >= sizeof( double ), "Buffer too small for SQL_C_DOUBLE" );  // technically should ignore this

    unsigned char* row = get_row();
    double* double_data = reinterpret_cast<double*>( &row[ meta[ field_index ].offset ] );

    memcpy( buffer, double_data, sizeof( double ));
    *out_buffer_length = sizeof( double );

    return SQL_SUCCESS;
}

namespace {

// called for each row in the cache when the cache is destroyed in the destructor
void cache_row_dtor( void* data )
{
    row_dtor_closure* cl = reinterpret_cast<row_dtor_closure*>( data );
    BYTE* row = cl->row_data;
    // don't release this here, since this is called from the destructor of the result_set
    sqlsrv_buffered_result_set* result_set = cl->results;

    for( SQLSMALLINT i = 0; i < result_set->column_count(); ++i ) {

        if( result_set->col_meta_data(i).length == sqlsrv_buffered_result_set::meta_data::SIZE_UNKNOWN ) {

            void* out_of_row_data = *reinterpret_cast<void**>( &row[ result_set->col_meta_data(i).offset ] );
            sqlsrv_free( out_of_row_data );
        }
    }

    sqlsrv_free( row );
}

SQLPOINTER read_lob_field( sqlsrv_stmt* stmt, SQLUSMALLINT field_index, sqlsrv_buffered_result_set::meta_data& meta, 
                           unsigned long mem_used TSRMLS_DC )
{
    SQLSMALLINT extra = 0;
    SQLLEN* output_buffer_len = NULL;

    // Set the amount of space necessary for null characters at the end of the data.
    switch( meta.c_type ) {
        case SQL_C_WCHAR:
            extra = sizeof( SQLWCHAR );
            break;
        case SQL_C_BINARY:
            extra = 0;
            break;
        case SQL_C_CHAR:
            extra = sizeof( SQLCHAR );
            break;
        default:
            SQLSRV_ASSERT( false, "Invalid type in read_lob_field" );
            break;
    }

    SQLLEN already_read = 0;
    SQLLEN to_read = INITIAL_FIELD_STRING_LEN;
    sqlsrv_malloc_auto_ptr<char> buffer;
    buffer = static_cast<char*>( sqlsrv_malloc( INITIAL_FIELD_STRING_LEN + extra + sizeof( SQLULEN )));
    SQLRETURN r = SQL_SUCCESS;
    SQLCHAR state[ SQL_SQLSTATE_BUFSIZE ];
    SQLLEN last_field_len = 0;
    bool full_length_returned = false;

    do {


        output_buffer_len = reinterpret_cast<SQLLEN*>( buffer.get() );
        r = core::SQLGetData( stmt, field_index + 1, meta.c_type, buffer.get() + already_read + sizeof( SQLULEN ),
                              to_read - already_read + extra, &last_field_len, false /*handle_warning*/ TSRMLS_CC );

        // if the field is NULL, then return a NULL pointer
        if( last_field_len == SQL_NULL_DATA ) {
            return NULL;
        }

        // if the last read was successful, we're done
        if( r == SQL_SUCCESS ) {
            // check to make sure we haven't overflown our memory limit
            CHECK_CUSTOM_ERROR( mem_used + last_field_len > stmt->buffered_query_limit * 1024, stmt, 
                                SQLSRV_ERROR_BUFFER_LIMIT_EXCEEDED, stmt->buffered_query_limit ) {

                throw core::CoreException();
            }
            break;
        }
        // else if it wasn't the truncated warning (01004) then we're done
        else if( r == SQL_SUCCESS_WITH_INFO ) {
            SQLSMALLINT len;
            core::SQLGetDiagField( stmt, 1, SQL_DIAG_SQLSTATE, state, SQL_SQLSTATE_BUFSIZE, &len 
                                   TSRMLS_CC );

            if( !is_truncated_warning( state )) {
                break;
            }
        }

        SQLSRV_ASSERT( SQL_SUCCEEDED( r ), "Unknown SQL error not triggered" );

        // if the type of the field returns the total to be read, we use that and preallocate the buffer
        if( last_field_len != SQL_NO_TOTAL ) {

            CHECK_CUSTOM_ERROR( mem_used + last_field_len > stmt->buffered_query_limit * 1024, stmt, 
                                SQLSRV_ERROR_BUFFER_LIMIT_EXCEEDED, stmt->buffered_query_limit ) {

                throw core::CoreException();
            }

            already_read += to_read - already_read;
            to_read = last_field_len;
            buffer.resize( to_read + extra + sizeof( SQLULEN ));
            output_buffer_len = reinterpret_cast<SQLLEN*>( buffer.get() );
            // record the size of the field since we have it available
            *output_buffer_len = last_field_len;
            full_length_returned = true;
        }
        // otherwise allocate another chunk of memory to read in
        else {
            already_read += to_read - already_read;
            to_read *=  2;
            CHECK_CUSTOM_ERROR( mem_used + to_read > stmt->buffered_query_limit * 1024, stmt, 
                                SQLSRV_ERROR_BUFFER_LIMIT_EXCEEDED, stmt->buffered_query_limit ) {

                throw core::CoreException();
            }
            buffer.resize( to_read + extra + sizeof( SQLULEN ));
            output_buffer_len = reinterpret_cast<SQLLEN*>( buffer.get() );
        }

    } while( true );

    SQLSRV_ASSERT( output_buffer_len != NULL, "Output buffer not allocated properly" );

    // most LOB field types return the total length in the last_field_len, but some field types such as XML
    // only return the amount read on the last read
    if( !full_length_returned ) {
        *output_buffer_len = already_read + last_field_len;
    }

    char* return_buffer = buffer;
    buffer.transferred();
    return return_buffer;
}

}

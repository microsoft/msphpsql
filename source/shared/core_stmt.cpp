//---------------------------------------------------------------------------------------------------------------------------------
// File: core_stmt.cpp
//
// Contents: Core routines that use statement handles shared between sqlsrv and pdo_sqlsrv
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

#include <sstream>
#include <vector>

namespace {

// certain drivers using this layer will call for repeated or out of order field retrievals.  To allow this, we cache the
// results of every field request, and if it is out of order, we cache those for preceding fields.
struct field_cache {

    void* value;
    SQLLEN len;
    sqlsrv_phptype type;

    field_cache( _In_reads_bytes_opt_(field_len) void* field_value, _In_ SQLLEN field_len, _In_ sqlsrv_phptype t )
        : type( t )
    {
        // if the value is NULL, then just record a NULL pointer
        if( field_value != NULL ) {
            value = sqlsrv_malloc( field_len );
            memcpy_s( value, field_len, field_value, field_len );
            len = field_len;
        }
        else {
            value = NULL;
            len = 0;
        }
    }

    // no destructor because we don't want to release the memory when it goes out of scope, but instead we
    // rely on the hash table destructor to free the memory
};

// Used to cache display size and SQL type of a column in get_field_as_string()
struct col_cache {
    SQLLEN sql_type;
    SQLLEN display_size;

    col_cache( _In_ SQLLEN col_sql_type, _In_ SQLLEN col_display_size )
    {
        sql_type = col_sql_type;
        display_size = col_display_size;
    }
};

const int INITIAL_FIELD_STRING_LEN = 2048;          // base allocation size when retrieving a string field

// UTF-8 tags for byte length of characters, used by streams to make sure we don't clip a character in between reads
const unsigned int UTF8_MIDBYTE_MASK = 0xc0;
const unsigned int UTF8_MIDBYTE_TAG = 0x80;
const unsigned int UTF8_2BYTESEQ_TAG1 = 0xc0;
const unsigned int UTF8_2BYTESEQ_TAG2 = 0xd0;
const unsigned int UTF8_3BYTESEQ_TAG = 0xe0;
const unsigned int UTF8_4BYTESEQ_TAG = 0xf0;
const unsigned int UTF8_NBYTESEQ_MASK = 0xf0;

// constants used to convert from a DateTime object to a string which is sent to the server.
// Using the format defined by the ODBC documentation at http://msdn2.microsoft.com/en-us/library/ms712387(VS.85).aspx
namespace DateTime {

const char DATETIME_CLASS_NAME[] = "DateTime";
const size_t DATETIME_CLASS_NAME_LEN = sizeof( DATETIME_CLASS_NAME ) - 1;
const char DATETIMEOFFSET_FORMAT[] = "Y-m-d H:i:s.u P";
const size_t DATETIMEOFFSET_FORMAT_LEN = sizeof( DATETIMEOFFSET_FORMAT );
const char DATETIME_FORMAT[] = "Y-m-d H:i:s.u";
const size_t DATETIME_FORMAT_LEN = sizeof( DATETIME_FORMAT );
const char DATE_FORMAT[] = "Y-m-d";
const size_t DATE_FORMAT_LEN = sizeof( DATE_FORMAT );

}

// *** internal functions ***
// Only declarations are put here.  Functions contain the documentation they need at their definition sites.
void calc_string_size( _Inout_ sqlsrv_stmt* stmt, _In_ SQLUSMALLINT field_index, _In_ SQLLEN sql_type, _Inout_ SQLLEN& size TSRMLS_DC );
size_t calc_utf8_missing( _Inout_ sqlsrv_stmt* stmt, _In_reads_(buffer_end) const char* buffer, _In_ size_t buffer_end TSRMLS_DC );
bool check_for_next_stream_parameter( _Inout_ sqlsrv_stmt* stmt TSRMLS_DC );
bool convert_input_param_to_utf16( _In_ zval* input_param_z, _Inout_ zval* convert_param_z );
void core_get_field_common(_Inout_ sqlsrv_stmt* stmt, _In_ SQLUSMALLINT field_index, _Inout_ sqlsrv_phptype
						   sqlsrv_php_type, _Inout_updates_bytes_(*field_len) void*& field_value, _Inout_ SQLLEN* field_len TSRMLS_DC);
// returns the ODBC C type constant that matches the PHP type and encoding given
SQLSMALLINT default_c_type( _Inout_ sqlsrv_stmt* stmt, _In_opt_ SQLULEN paramno, _In_ zval const* param_z, _In_ SQLSRV_ENCODING encoding TSRMLS_DC );
void default_sql_size_and_scale( _Inout_ sqlsrv_stmt* stmt, _In_opt_ unsigned int paramno, _In_ zval* param_z, _In_ SQLSRV_ENCODING encoding,
                                 _Out_ SQLULEN& column_size, _Out_ SQLSMALLINT& decimal_digits TSRMLS_DC );
// given a zval and encoding, determine the appropriate sql type, column size, and decimal scale (if appropriate)
void default_sql_type( _Inout_ sqlsrv_stmt* stmt, _In_opt_ SQLULEN paramno, _In_ zval* param_z, _In_ SQLSRV_ENCODING encoding,
                       _Out_ SQLSMALLINT& sql_type TSRMLS_DC );
void col_cache_dtor( _Inout_ zval* data_z );
void field_cache_dtor( _Inout_ zval* data_z );
void finalize_output_parameters( _Inout_ sqlsrv_stmt* stmt TSRMLS_DC );
void get_field_as_string( _Inout_ sqlsrv_stmt* stmt, _In_ SQLUSMALLINT field_index, _Inout_ sqlsrv_phptype sqlsrv_php_type,
						  _Inout_updates_bytes_(*field_len) void*& field_value, _Inout_ SQLLEN* field_len TSRMLS_DC );
stmt_option const* get_stmt_option( sqlsrv_conn const* conn, _In_ zend_ulong key, _In_ const stmt_option stmt_opts[] TSRMLS_DC );
bool is_valid_sqlsrv_phptype( _In_ sqlsrv_phptype type );
// assure there is enough space for the output parameter string
void resize_output_buffer_if_necessary( _Inout_ sqlsrv_stmt* stmt, _Inout_ zval* param_z, _In_ SQLULEN paramno, SQLSRV_ENCODING encoding,
                                        _In_ SQLSMALLINT c_type, _In_ SQLSMALLINT sql_type, _In_ SQLULEN column_size, _In_ SQLSMALLINT decimal_digits,
                                        _Out_writes_(buffer_len) SQLPOINTER& buffer, _Out_ SQLLEN& buffer_len TSRMLS_DC );
void adjustInputPrecision( _Inout_ zval* param_z, _In_ SQLSMALLINT decimal_digits );
void save_output_param_for_later( _Inout_ sqlsrv_stmt* stmt, _Inout_ sqlsrv_output_param& param TSRMLS_DC );
// send all the stream data
void send_param_streams( _Inout_ sqlsrv_stmt* stmt TSRMLS_DC );
// called when a bound output string parameter is to be destroyed
void sqlsrv_output_param_dtor( _Inout_ zval* data );
// called when a bound stream parameter is to be destroyed.
void sqlsrv_stream_dtor( _Inout_ zval* data );
bool is_streamable_type( _In_ SQLINTEGER sql_type );

}

// constructor for sqlsrv_stmt.  Here so that we can use functions declared earlier.
sqlsrv_stmt::sqlsrv_stmt( _In_ sqlsrv_conn* c, _In_ SQLHANDLE handle, _In_ error_callback e, _In_opt_ void* drv TSRMLS_DC ) :
    sqlsrv_context( handle, SQL_HANDLE_STMT, e, drv, SQLSRV_ENCODING_DEFAULT ),
    conn( c ),
    executed( false ),
    past_fetch_end( false ),
    current_results( NULL ),
    cursor_type( SQL_CURSOR_FORWARD_ONLY ),
    has_rows( false ),
    fetch_called( false ),
    last_field_index( -1 ),
    past_next_result_end( false ),
    query_timeout( QUERY_TIMEOUT_INVALID ),
    buffered_query_limit( sqlsrv_buffered_result_set::BUFFERED_QUERY_LIMIT_INVALID ),
    param_ind_ptrs( 10 ),    // initially hold 10 elements, which should cover 90% of the cases and only take < 100 byte
    send_streams_at_exec( true ),
    current_stream( NULL, SQLSRV_ENCODING_DEFAULT ),
    current_stream_read( 0 )
{
	ZVAL_UNDEF( &active_stream );
    // initialize the input string parameters array (which holds zvals)
    core::sqlsrv_array_init( *conn, &param_input_strings TSRMLS_CC );

    // initialize the (input only) stream parameters (which holds sqlsrv_stream structures)
    ZVAL_NEW_ARR( &param_streams );
    core::sqlsrv_zend_hash_init(*conn, Z_ARRVAL( param_streams ), 5 /* # of buckets */, sqlsrv_stream_dtor, 0 /*persistent*/ TSRMLS_CC);

    // initialize the (input only) datetime parameters of converted date time objects to strings
    array_init( &param_datetime_buffers );

    // initialize the output string parameters (which holds sqlsrv_output_param structures)
    ZVAL_NEW_ARR( &output_params );
    core::sqlsrv_zend_hash_init(*conn, Z_ARRVAL( output_params ), 5 /* # of buckets */, sqlsrv_output_param_dtor, 0 /*persistent*/ TSRMLS_CC);

    // initialize the col cache
    ZVAL_NEW_ARR( &col_cache );
    core::sqlsrv_zend_hash_init( *conn, Z_ARRVAL(col_cache), 5 /* # of buckets */, col_cache_dtor, 0 /*persistent*/ TSRMLS_CC );

    // initialize the field cache
    ZVAL_NEW_ARR( &field_cache );
    core::sqlsrv_zend_hash_init(*conn, Z_ARRVAL(field_cache), 5 /* # of buckets */, field_cache_dtor, 0 /*persistent*/ TSRMLS_CC);
}

// desctructor for sqlsrv statement.
sqlsrv_stmt::~sqlsrv_stmt( void )
{
    if( Z_TYPE( active_stream ) != IS_UNDEF ) {
        TSRMLS_FETCH();
        close_active_stream( this TSRMLS_CC );
    }

    // delete any current results
    if( current_results ) {
        current_results->~sqlsrv_result_set();
        efree( current_results );
        current_results = NULL;
    }

    invalidate();
    zval_ptr_dtor( &param_input_strings );
    zval_ptr_dtor( &output_params );
    zval_ptr_dtor( &param_streams );
    zval_ptr_dtor( &param_datetime_buffers );
    zval_ptr_dtor( &col_cache );
    zval_ptr_dtor( &field_cache );
}


// centralized place to release (without destroying the hash tables
// themselves) all the parameter data that accrues during the
// execution phase.
void sqlsrv_stmt::free_param_data( TSRMLS_D )
{
    SQLSRV_ASSERT(Z_TYPE( param_input_strings ) == IS_ARRAY && Z_TYPE( param_streams ) == IS_ARRAY,
                   "sqlsrv_stmt::free_param_data: Param zvals aren't arrays." );
    zend_hash_clean( Z_ARRVAL( param_input_strings ));
    zend_hash_clean( Z_ARRVAL( output_params ));
    zend_hash_clean( Z_ARRVAL( param_streams ));
    zend_hash_clean( Z_ARRVAL( param_datetime_buffers ));
    zend_hash_clean( Z_ARRVAL( col_cache ));
    zend_hash_clean( Z_ARRVAL( field_cache ));
}


// to be called whenever a new result set is created, such as after an
// execute or next_result.  Resets the state variables.

void sqlsrv_stmt::new_result_set( TSRMLS_D )
{
    this->fetch_called = false;
    this->has_rows = false;
    this->past_next_result_end = false;
    this->past_fetch_end = false;
    this->last_field_index = -1;

    // delete any current results
    if( current_results ) {
        current_results->~sqlsrv_result_set();
        efree( current_results );
        current_results = NULL;
    }

    // create a new result set
    if( cursor_type == SQLSRV_CURSOR_BUFFERED ) {
         sqlsrv_malloc_auto_ptr<sqlsrv_buffered_result_set> result;
        result = reinterpret_cast<sqlsrv_buffered_result_set*> ( sqlsrv_malloc( sizeof( sqlsrv_buffered_result_set ) ) );
        new ( result.get() ) sqlsrv_buffered_result_set( this TSRMLS_CC );
        current_results = result.get();
        result.transferred();
    }
    else {
        current_results = new (sqlsrv_malloc( sizeof( sqlsrv_odbc_result_set ))) sqlsrv_odbc_result_set( this );
    }
}

// core_sqlsrv_create_stmt
// Common code to allocate a statement from either driver.  Returns a valid driver statement object or
// throws an exception if an error occurs.
// Parameters:
// conn             - The connection resource by which the client and server are connected.
// stmt_factory     - factory method to create a statement.
// options_ht       - A HashTable of user provided options to be set on the statement.
// valid_stmt_opts  - An array of valid driver supported statement options.
// err              - callback for error handling
// driver           - reference to caller
// Return
// Returns the created statement

sqlsrv_stmt* core_sqlsrv_create_stmt( _Inout_ sqlsrv_conn* conn, _In_ driver_stmt_factory stmt_factory, _In_opt_ HashTable* options_ht,
                                      _In_opt_ const stmt_option valid_stmt_opts[], _In_ error_callback const err, _In_opt_ void* driver TSRMLS_DC )
{
	sqlsrv_malloc_auto_ptr<sqlsrv_stmt> stmt;
    SQLHANDLE stmt_h = SQL_NULL_HANDLE;
    sqlsrv_stmt* return_stmt = NULL;

    try {

        core::SQLAllocHandle( SQL_HANDLE_STMT, *conn, &stmt_h TSRMLS_CC );

        stmt = stmt_factory( conn, stmt_h, err, driver TSRMLS_CC );

        stmt->conn = conn;

        // handle has been set in the constructor of ss_sqlsrv_stmt, so we set it to NULL to prevent a double free
        // in the catch block below.
        stmt_h = SQL_NULL_HANDLE;

        // process the options array given to core_sqlsrv_prepare.
        if( options_ht && zend_hash_num_elements( options_ht ) > 0 && valid_stmt_opts ) {
			zend_ulong index = -1;
			zend_string *key = NULL;
			zval* value_z = NULL;

			ZEND_HASH_FOREACH_KEY_VAL( options_ht, index, key, value_z ) {

				int type = key ? HASH_KEY_IS_STRING : HASH_KEY_IS_LONG;

				// The driver layer should ensure a valid key.
				DEBUG_SQLSRV_ASSERT(( type == HASH_KEY_IS_LONG ), "allocate_stmt: Invalid statment option key provided." );

				const stmt_option* stmt_opt = get_stmt_option( stmt->conn, index, valid_stmt_opts TSRMLS_CC );

				// if the key didn't match, then return the error to the script.
				// The driver layer should ensure that the key is valid.
				DEBUG_SQLSRV_ASSERT( stmt_opt != NULL, "allocate_stmt: unexpected null value for statement option." );

				// perform the actions the statement option needs done.
				(*stmt_opt->func)( stmt, stmt_opt, value_z TSRMLS_CC );
			} ZEND_HASH_FOREACH_END();
        }

        return_stmt = stmt;
        stmt.transferred();
    }
    catch( core::CoreException& )
    {
        if( stmt ) {

            conn->set_last_error( stmt->last_error() );
            stmt->~sqlsrv_stmt();
        }

        // if allocating the handle failed before the statement was allocated, free the handle
        if( stmt_h != SQL_NULL_HANDLE) {
            ::SQLFreeHandle( SQL_HANDLE_STMT, stmt_h );
        }

        throw;
    }
    catch( ... ) {

        DIE( "core_sqlsrv_allocate_stmt: Unknown exception caught." );
    }

    return return_stmt;
}


// core_sqlsrv_bind_param
// Binds a parameter using SQLBindParameter.  It allocates memory and handles other details
// in translating between the driver and ODBC.
// Parameters:
// param_num      - number of the parameter, 0 based
// param_z        - zval of the parameter
// php_out_type   - type to return for output parameter
// sql_type       - ODBC constant for the SQL Server type (SQL_UNKNOWN_TYPE = 0 means not known, so infer defaults)
// column_size    - length of the field on the server (SQLSRV_UKNOWN_SIZE means not known, so infer defaults)
// decimal_digits - if column_size is valid and the type contains a scale, this contains the scale
// Return:
// Nothing, though an exception is thrown if an error occurs
// The php type of the parameter is taken from the zval.
// The sql type is given as a hint if the driver provides it.

void core_sqlsrv_bind_param( _Inout_ sqlsrv_stmt* stmt, _In_ SQLUSMALLINT param_num, _In_ SQLSMALLINT direction, _Inout_ zval* param_z,
                             _In_ SQLSRV_PHPTYPE php_out_type, _Inout_ SQLSRV_ENCODING encoding, _Inout_ SQLSMALLINT sql_type, _Inout_ SQLULEN column_size,
                             _Inout_ SQLSMALLINT decimal_digits TSRMLS_DC )
{
    SQLSMALLINT c_type;
    SQLPOINTER buffer = NULL;
    SQLLEN buffer_len = 0;

    SQLSRV_ASSERT( direction == SQL_PARAM_INPUT || direction == SQL_PARAM_OUTPUT || direction == SQL_PARAM_INPUT_OUTPUT,
                   "core_sqlsrv_bind_param: Invalid parameter direction." );
    SQLSRV_ASSERT( direction == SQL_PARAM_INPUT || php_out_type != SQLSRV_PHPTYPE_INVALID,
                   "core_sqlsrv_bind_param: php_out_type not set before calling core_sqlsrv_bind_param." );

    try {

    // check is only < because params are 0 based
    CHECK_CUSTOM_ERROR( param_num >= SQL_SERVER_MAX_PARAMS, stmt, SQLSRV_ERROR_MAX_PARAMS_EXCEEDED, param_num + 1 ){
        throw core::CoreException();
    }

    // resize the statements array of int_ptrs if the parameter isn't already set.
    if( stmt->param_ind_ptrs.size() < static_cast<size_t>( param_num + 1 )){
        stmt->param_ind_ptrs.resize( param_num + 1, SQL_NULL_DATA );
    }
    SQLLEN& ind_ptr = stmt->param_ind_ptrs[ param_num ];

    zval* param_ref = param_z;
    if( Z_ISREF_P( param_z )){
        ZVAL_DEREF( param_z );
    }
    bool zval_was_null = ( Z_TYPE_P( param_z ) == IS_NULL );
    bool zval_was_bool = ( Z_TYPE_P( param_z ) == IS_TRUE || Z_TYPE_P( param_z ) == IS_FALSE );
    // if the user asks for for a specific type for input and output, make sure the data type we send matches the data we
    // type we expect back, since we can only send and receive the same type.  Anything can be converted to a string, so
    // we always let that match if they want a string back.
    if( direction == SQL_PARAM_INPUT_OUTPUT ) {
        bool match = false;
        switch( php_out_type ){
            case SQLSRV_PHPTYPE_INT:
                if( zval_was_null || zval_was_bool ){
                    convert_to_long( param_z );
                }
                match = Z_TYPE_P( param_z ) == IS_LONG;
                break;
            case SQLSRV_PHPTYPE_FLOAT:
                if( zval_was_null ){
                    convert_to_double( param_z );
                }
                match = Z_TYPE_P( param_z ) == IS_DOUBLE;
                break;
            case SQLSRV_PHPTYPE_STRING:
                // anything can be converted to a string
                convert_to_string( param_z );
                match = true;
                break;
            case SQLSRV_PHPTYPE_NULL:
            case SQLSRV_PHPTYPE_DATETIME:
            case SQLSRV_PHPTYPE_STREAM:
                SQLSRV_ASSERT( false, "Invalid type for an output parameter." );
                break;
            default:
                SQLSRV_ASSERT( false, "Unknown SQLSRV_PHPTYPE_* constant given." );
                break;
        }
        CHECK_CUSTOM_ERROR( !match, stmt, SQLSRV_ERROR_INPUT_OUTPUT_PARAM_TYPE_MATCH, param_num + 1 ){
            throw core::CoreException();
        }
    }

    // If the user specifies a certain type for an output parameter, we have to convert the zval
    // to that type so that when the buffer is filled, the type is correct. But first,
    // should check if a LOB type is specified.
    CHECK_CUSTOM_ERROR( direction != SQL_PARAM_INPUT && ( sql_type == SQL_LONGVARCHAR
                        || sql_type == SQL_WLONGVARCHAR || sql_type == SQL_LONGVARBINARY ),
                        stmt, SQLSRV_ERROR_OUTPUT_PARAM_TYPES_NOT_SUPPORTED ){
        throw core::CoreException();
    }

    if( direction == SQL_PARAM_OUTPUT ){
        switch( php_out_type ) {
            case SQLSRV_PHPTYPE_INT:
                convert_to_long( param_z );
                break;
            case SQLSRV_PHPTYPE_FLOAT:
                convert_to_double( param_z );
                break;
            case SQLSRV_PHPTYPE_STRING:
                convert_to_string( param_z );
                break;
            case SQLSRV_PHPTYPE_NULL:
            case SQLSRV_PHPTYPE_DATETIME:
            case SQLSRV_PHPTYPE_STREAM:
                SQLSRV_ASSERT( false, "Invalid type for an output parameter" );
                break;
            default:
                SQLSRV_ASSERT( false, "Uknown SQLSRV_PHPTYPE_* constant given" );
                break;
        }
    }

    SQLSRV_ASSERT(( Z_TYPE_P( param_z ) != IS_STRING && Z_TYPE_P( param_z ) != IS_RESOURCE ) ||
                  ( encoding == SQLSRV_ENCODING_SYSTEM || encoding == SQLSRV_ENCODING_UTF8 ||
                    encoding == SQLSRV_ENCODING_BINARY ), "core_sqlsrv_bind_param: invalid encoding" );

    if( stmt->conn->ce_option.enabled && ( sql_type == SQL_UNKNOWN_TYPE || column_size == SQLSRV_UNKNOWN_SIZE )){
        // use the meta data only if the user has not specified the sql type or column size
        SQLSRV_ASSERT( param_num < stmt->param_descriptions.size(), "Invalid param_num passed in core_sqlsrv_bind_param!" );
        sql_type = stmt->param_descriptions[param_num].get_sql_type();
        column_size = stmt->param_descriptions[param_num].get_column_size();
        decimal_digits = stmt->param_descriptions[param_num].get_decimal_digits();

        // change long to double if the sql type is decimal
        if(( sql_type == SQL_DECIMAL || sql_type == SQL_NUMERIC ) && Z_TYPE_P(param_z) == IS_LONG )
                convert_to_double( param_z );
    }
    else{
        // if the sql type is unknown, then set the default based on the PHP type passed in
        if( sql_type == SQL_UNKNOWN_TYPE ){
            default_sql_type( stmt, param_num, param_z, encoding, sql_type TSRMLS_CC );
        }

        // if the size is unknown, then set the default based on the PHP type passed in
        if( column_size == SQLSRV_UNKNOWN_SIZE ){
            default_sql_size_and_scale( stmt, static_cast<unsigned int>(param_num), param_z, encoding, column_size, decimal_digits TSRMLS_CC );
        }
    }
    // determine the ODBC C type
    c_type = default_c_type( stmt, param_num, param_z, encoding TSRMLS_CC );

    // set the buffer based on the PHP parameter type
    switch( Z_TYPE_P( param_z )){

        case IS_NULL:
            {
                SQLSRV_ASSERT( direction == SQL_PARAM_INPUT, "Invalid output param type.  The driver layer should catch this." );
                ind_ptr = SQL_NULL_DATA;
                buffer = NULL;
                buffer_len = 0;
            }
            break;
        case IS_TRUE:
        case IS_FALSE:
        case IS_LONG:
            {
                // if it is boolean, set the lval to 0 or 1
                convert_to_long( param_z );
                buffer = &param_z->value;
                buffer_len = sizeof( Z_LVAL_P( param_z ));
                ind_ptr = buffer_len;
                if( direction != SQL_PARAM_INPUT ){
                    // save the parameter so that 1) the buffer doesn't go away, and 2) we can set it to NULL if returned
					sqlsrv_output_param output_param( param_ref, static_cast<int>( param_num ), zval_was_bool, php_out_type);
                    save_output_param_for_later( stmt, output_param TSRMLS_CC );
                }
            }
            break;
        case IS_DOUBLE:
            {
                buffer = &param_z->value;
				buffer_len = sizeof( Z_DVAL_P( param_z ));
                ind_ptr = buffer_len;
                if( direction != SQL_PARAM_INPUT ){
                    // save the parameter so that 1) the buffer doesn't go away, and 2) we can set it to NULL if returned
					sqlsrv_output_param output_param( param_ref, static_cast<int>( param_num ), zval_was_bool, php_out_type);
                    save_output_param_for_later( stmt, output_param TSRMLS_CC );
                }
            }
            break;
        case IS_STRING:
            {
                if ( sql_type == SQL_DECIMAL || sql_type == SQL_NUMERIC ) {
                    adjustInputPrecision( param_z, decimal_digits );
                }

                buffer = Z_STRVAL_P( param_z );
                buffer_len = Z_STRLEN_P( param_z );

                // if the encoding is UTF-8, translate from UTF-8 to UTF-16 (the type variables should have already been adjusted)
                if( direction == SQL_PARAM_INPUT && encoding == CP_UTF8 ){

                    zval wbuffer_z;
                    ZVAL_NULL( &wbuffer_z );

                    bool converted = convert_input_param_to_utf16( param_z, &wbuffer_z );
                    CHECK_CUSTOM_ERROR( !converted, stmt, SQLSRV_ERROR_INPUT_PARAM_ENCODING_TRANSLATE,
                                        param_num + 1, get_last_error_message() ){
                        throw core::CoreException();
                    }
                    buffer = Z_STRVAL_P( &wbuffer_z );
                    buffer_len = Z_STRLEN_P( &wbuffer_z );
                    core::sqlsrv_add_index_zval( *stmt, &( stmt->param_input_strings ), param_num, &wbuffer_z TSRMLS_CC );
                }
                ind_ptr = buffer_len;
                if( direction != SQL_PARAM_INPUT ){
                    // PHP 5.4 added interned strings, so since we obviously want to change that string here in some fashion,
                    // we reallocate the string if it's interned
                    if( ZSTR_IS_INTERNED( Z_STR_P( param_z ))){
                        core::sqlsrv_zval_stringl( param_z, static_cast<const char*>(buffer), buffer_len );
                        buffer = Z_STRVAL_P( param_z );
                        buffer_len = Z_STRLEN_P( param_z );
                    }

                    // if it's a UTF-8 input output parameter (signified by the C type being SQL_C_WCHAR)
                    // or if the PHP type is a binary encoded string with a N(VAR)CHAR/NTEXTSQL type,
                    // convert it to wchar first
                    if( direction == SQL_PARAM_INPUT_OUTPUT &&
                        ( c_type == SQL_C_WCHAR ||
                        ( c_type == SQL_C_BINARY &&
                          ( sql_type == SQL_WCHAR ||
                            sql_type == SQL_WVARCHAR ||
                            sql_type == SQL_WLONGVARCHAR )))){

                        bool converted = convert_input_param_to_utf16( param_z, param_z );
                        CHECK_CUSTOM_ERROR( !converted, stmt, SQLSRV_ERROR_INPUT_PARAM_ENCODING_TRANSLATE,
                                            param_num + 1, get_last_error_message() ){
                            throw core::CoreException();
                        }
                        buffer = Z_STRVAL_P( param_z );
                        buffer_len = Z_STRLEN_P( param_z );
                        ind_ptr = buffer_len;
                    }

                    // since this is an output string, assure there is enough space to hold the requested size and
                    // set all the variables necessary (param_z, buffer, buffer_len, and ind_ptr)
                    resize_output_buffer_if_necessary( stmt, param_z, param_num, encoding, c_type, sql_type, column_size, decimal_digits,
                                                       buffer, buffer_len TSRMLS_CC );

                    // save the parameter to be adjusted and/or converted after the results are processed
                    sqlsrv_output_param output_param( param_ref, encoding, param_num, static_cast<SQLUINTEGER>( buffer_len ) );

                    save_output_param_for_later( stmt, output_param TSRMLS_CC );

                    // For output parameters, if we set the column_size to be same as the buffer_len,
                    // then if there is a truncation due to the data coming from the server being
                    // greater than the column_size, we don't get any truncation error. In order to
                    // avoid this silent truncation, we set the column_size to be "MAX" size for
                    // string types. This will guarantee that there is no silent truncation for
                    // output parameters.
                    // if column encryption is enabled, at this point the correct column size has been set by SQLDescribeParam
                    if( direction == SQL_PARAM_OUTPUT && !stmt->conn->ce_option.enabled ){

                        switch( sql_type ){

                        case SQL_VARBINARY:
                        case SQL_VARCHAR:
                        case SQL_WVARCHAR:
                            column_size = SQL_SS_LENGTH_UNLIMITED;
                            break;

                        default:
                            break;
                        }
                    }
                }
            }
            break;
        case IS_RESOURCE:
            {
                SQLSRV_ASSERT( direction == SQL_PARAM_INPUT, "Invalid output param type.  The driver layer should catch this." );
                sqlsrv_stream stream_encoding( param_z, encoding );
                HashTable* streams_ht = Z_ARRVAL( stmt->param_streams );
                core::sqlsrv_zend_hash_index_update_mem( *stmt, streams_ht, param_num, &stream_encoding, sizeof(stream_encoding) TSRMLS_CC );
                buffer = reinterpret_cast<SQLPOINTER>( param_num );
                Z_TRY_ADDREF_P( param_z ); // so that it doesn't go away while we're using it
                buffer_len = 0;
                ind_ptr = SQL_DATA_AT_EXEC;
            }
            break;
        case IS_OBJECT:
        {
            SQLSRV_ASSERT( direction == SQL_PARAM_INPUT, "Invalid output param type.  The driver layer should catch this." );
            zval function_z;
            zval buffer_z;
            zval format_z;
            zval params[1];
			ZVAL_UNDEF( &function_z );
			ZVAL_UNDEF( &buffer_z );
			ZVAL_UNDEF( &format_z );
			ZVAL_UNDEF( params );

            bool valid_class_name_found = false;

            zend_class_entry *class_entry = Z_OBJCE_P( param_z TSRMLS_CC );

            while( class_entry != NULL ){
                SQLSRV_ASSERT( class_entry->name != NULL, "core_sqlsrv_bind_param: class_entry->name is NULL." );
                if( class_entry->name->len == DateTime::DATETIME_CLASS_NAME_LEN && class_entry->name != NULL &&
                    stricmp( class_entry->name->val, DateTime::DATETIME_CLASS_NAME ) == 0 ){
                    valid_class_name_found = true;
                    break;
                }

                else{

                    // Check the parent
                    class_entry = class_entry->parent;
                }
            }

            CHECK_CUSTOM_ERROR( !valid_class_name_found, stmt, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE, param_num + 1 ){
                throw core::CoreException();
            }

            // if the user specifies the 'date' sql type, giving it the normal format will cause a 'date overflow error'
            // meaning there is too much information in the character string.  If the user specifies the 'datetimeoffset'
            // sql type, it lacks the timezone.
            if( sql_type == SQL_SS_TIMESTAMPOFFSET ){
				core::sqlsrv_zval_stringl( &format_z, const_cast<char*>( DateTime::DATETIMEOFFSET_FORMAT ),
                              DateTime::DATETIMEOFFSET_FORMAT_LEN );
            }
            else if( sql_type == SQL_TYPE_DATE ){
				core::sqlsrv_zval_stringl( &format_z, const_cast<char*>( DateTime::DATE_FORMAT ), DateTime::DATE_FORMAT_LEN );
            }
            else{
				core::sqlsrv_zval_stringl( &format_z, const_cast<char*>( DateTime::DATETIME_FORMAT ), DateTime::DATETIME_FORMAT_LEN );
            }
            // call the DateTime::format member function to convert the object to a string that SQL Server understands
			core::sqlsrv_zval_stringl( &function_z, "format", sizeof( "format" ) - 1 );
            params[0] = format_z;
            // This is equivalent to the PHP code: $param_z->format( $format_z ); where param_z is the
            // DateTime object and $format_z is the format string.
            int zr = call_user_function( EG( function_table ), param_z, &function_z, &buffer_z, 1, params TSRMLS_CC );
			zend_string_release( Z_STR( format_z ));
			zend_string_release( Z_STR( function_z ));
            CHECK_CUSTOM_ERROR( zr == FAILURE, stmt, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE, param_num + 1 ){
                throw core::CoreException();
            }
            buffer = Z_STRVAL( buffer_z );
            zr = add_next_index_zval( &( stmt->param_datetime_buffers ), &buffer_z );
            CHECK_CUSTOM_ERROR( zr == FAILURE, stmt, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE, param_num + 1 ){
                throw core::CoreException();
            }
            buffer_len = Z_STRLEN( buffer_z ) - 1;
            ind_ptr = buffer_len;
            break;
        }
        case IS_ARRAY:
            THROW_CORE_ERROR( stmt, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE, param_num + 1 );
            break;
       default:
            DIE( "core_sqlsrv_bind_param: Unsupported PHP type.  Only string, float, int, and streams (resource) are supported. "
                 "It is the responsibilty of the driver layer to convert a parameter to one of these types." );
            break;
    }

    if( zval_was_null ){
        ind_ptr = SQL_NULL_DATA;
    }

    core::SQLBindParameter( stmt, param_num + 1, direction,
		c_type, sql_type, column_size, decimal_digits, buffer, buffer_len, &ind_ptr TSRMLS_CC );
    if ( stmt->conn->ce_option.enabled && sql_type == SQL_TYPE_TIMESTAMP )
    {
        if( decimal_digits == 3 )
            core::SQLSetDescField( stmt, param_num + 1, SQL_CA_SS_SERVER_TYPE, (SQLPOINTER)SQL_SS_TYPE_DATETIME, SQL_IS_INTEGER );
        else if (decimal_digits == 0)
            core::SQLSetDescField( stmt, param_num + 1, SQL_CA_SS_SERVER_TYPE, (SQLPOINTER)SQL_SS_TYPE_SMALLDATETIME, SQL_IS_INTEGER );
    }
    }
    catch( core::CoreException& e ){
        stmt->free_param_data( TSRMLS_C );
        SQLFreeStmt( stmt->handle(), SQL_RESET_PARAMS );
        throw e;
    }
}


// core_sqlsrv_execute
// Executes the statement previously prepared
// Parameters:
// stmt - the core sqlsrv_stmt structure that contains the ODBC handle
// Return:
// true if there is data, false if there is not

SQLRETURN core_sqlsrv_execute( _Inout_ sqlsrv_stmt* stmt TSRMLS_DC, _In_reads_bytes_(sql_len) const char* sql, _In_ int sql_len )
{
    SQLRETURN r = SQL_ERROR;

    try {

    // close the stream to release the resource
    close_active_stream( stmt TSRMLS_CC );

    if( sql ) {

        sqlsrv_malloc_auto_ptr<SQLWCHAR> wsql_string;
        unsigned int wsql_len = 0;
        if( sql_len == 0 || ( sql[0] == '\0' && sql_len == 1 )) {
            wsql_string = reinterpret_cast<SQLWCHAR*>( sqlsrv_malloc( sizeof( SQLWCHAR )));
            wsql_string[0] = L'\0';
            wsql_len = 0;
        }
        else {
            SQLSRV_ENCODING encoding = (( stmt->encoding() == SQLSRV_ENCODING_DEFAULT ) ? stmt->conn->encoding() : stmt->encoding() );
            wsql_string = utf16_string_from_mbcs_string( encoding, reinterpret_cast<const char*>( sql ),
                                                         sql_len, &wsql_len );
            CHECK_CUSTOM_ERROR( wsql_string == 0, stmt, SQLSRV_ERROR_QUERY_STRING_ENCODING_TRANSLATE,
                                get_last_error_message() ) {
                throw core::CoreException();
            }
        }
        r = core::SQLExecDirectW( stmt, wsql_string TSRMLS_CC );
    }
    else {
        r = core::SQLExecute( stmt TSRMLS_CC );
    }

    // if data is needed (streams were bound) and they should be sent at execute time, then do so now
    if( r == SQL_NEED_DATA && stmt->send_streams_at_exec ) {

        send_param_streams( stmt TSRMLS_CC );
    }

    stmt->new_result_set( TSRMLS_C );
    stmt->executed = true;

    // if all the data has been sent and no data was returned then finalize the output parameters
    if( stmt->send_streams_at_exec && ( r == SQL_NO_DATA || !core_sqlsrv_has_any_result( stmt TSRMLS_CC ))) {

        finalize_output_parameters( stmt TSRMLS_CC );
    }
    // stream parameters are sent, clean the Hashtable
    if ( stmt->send_streams_at_exec ) {
         zend_hash_clean( Z_ARRVAL( stmt->param_streams ));
    }
    return r;
    }
    catch( core::CoreException& e ) {

        // if the statement executed but failed in a subsequent operation before returning,
        // we need to cancel the statement and deref the output and stream parameters
        if ( stmt->send_streams_at_exec ) {
            finalize_output_parameters( stmt TSRMLS_CC );
            zend_hash_clean( Z_ARRVAL( stmt->param_streams ));
        }
        if( stmt->executed ) {
            SQLCancel( stmt->handle() );
            // stmt->executed = false; should this be reset if something fails?
        }

        throw e;
    }
}


// core_sqlsrv_fetch
// Moves the cursor according to the parameters (by default, moves to the next row)
// Parameters:
// stmt              - the sqlsrv_stmt of the cursor
// fetch_orientation - method to move the cursor
// fetch_offset      - if the method has a parameter (such as number of rows to move or literal row number)
// Returns:
// Nothing, exception thrown if an error.  stmt->past_fetch_end is set to true if the
// user scrolls past a non-scrollable result set

bool core_sqlsrv_fetch( _Inout_ sqlsrv_stmt* stmt, _In_ SQLSMALLINT fetch_orientation, _In_ SQLULEN fetch_offset TSRMLS_DC )
{
    // pre-condition check
    SQLSRV_ASSERT( fetch_orientation >= SQL_FETCH_NEXT || fetch_orientation <= SQL_FETCH_RELATIVE,
                   "core_sqlsrv_fetch: Invalid value provided for fetch_orientation parameter." );

    try {

        // clear the field cache of the previous fetch
        zend_hash_clean( Z_ARRVAL( stmt->field_cache ));

        CHECK_CUSTOM_ERROR( !stmt->executed, stmt, SQLSRV_ERROR_STATEMENT_NOT_EXECUTED ) {
            throw core::CoreException();
        }

        CHECK_CUSTOM_ERROR( stmt->past_fetch_end, stmt, SQLSRV_ERROR_FETCH_PAST_END ) {
            throw core::CoreException();
        }
        // First time only
        if ( !stmt->fetch_called ) {
            SQLSMALLINT has_fields = core::SQLNumResultCols( stmt TSRMLS_CC );
            CHECK_CUSTOM_ERROR( has_fields == 0, stmt, SQLSRV_ERROR_NO_FIELDS ) {
                throw core::CoreException();
            }
        }

        // close the stream to release the resource
        close_active_stream( stmt TSRMLS_CC );

        // if the statement has rows and is not scrollable but doesn't yet have
        // fetch_called, this must be the first time we've called sqlsrv_fetch.
        if( stmt->cursor_type == SQL_CURSOR_FORWARD_ONLY && stmt->has_rows && !stmt->fetch_called ) {
            stmt->fetch_called = true;
            return true;
        }

        // move to the record requested.  For absolute records, we use a 0 based offset, so +1 since
        // SQLFetchScroll uses a 1 based offset, otherwise for relative, just use the fetch_offset provided.
        SQLRETURN r = stmt->current_results->fetch( fetch_orientation, ( fetch_orientation == SQL_FETCH_RELATIVE ) ? fetch_offset : fetch_offset + 1 TSRMLS_CC );

        if( r == SQL_NO_DATA ) {
            // if this is a forward only cursor, mark that we've passed the end so future calls result in an error
            if( stmt->cursor_type == SQL_CURSOR_FORWARD_ONLY ) {
                stmt->past_fetch_end = true;
            }
            stmt->fetch_called = false; // reset this flag
            return false;
        }

        // mark that we called fetch (which get_field, et. al. uses) and reset our last field retrieved
        stmt->fetch_called = true;
        stmt->last_field_index = -1;
        stmt->has_rows = true;  // since we made it this far, we must have at least one row
    }
    catch (core::CoreException& e) {
        throw e;
    }
    catch ( ... ) {
        DIE( "core_sqlsrv_fetch: Unexpected exception occurred." );
    }

    return true;
}


// Retrieves metadata for a field of a prepared statement.
// Parameters:
// colno - the index of the field for which to return the metadata.  columns are 0 based in PDO
// Return:
// A field_meta_data* consisting of the field metadata.

field_meta_data* core_sqlsrv_field_metadata( _Inout_ sqlsrv_stmt* stmt, _In_ SQLSMALLINT colno TSRMLS_DC )
{
    // pre-condition check
    SQLSRV_ASSERT( colno >= 0, "core_sqlsrv_field_metadata: Invalid column number provided." );

    sqlsrv_malloc_auto_ptr<field_meta_data> meta_data;
    sqlsrv_malloc_auto_ptr<SQLWCHAR> field_name_temp;
    SQLSMALLINT field_len_temp = 0;
    SQLLEN field_name_len = 0;

    meta_data = new ( sqlsrv_malloc( sizeof( field_meta_data ))) field_meta_data();
    field_name_temp = static_cast<SQLWCHAR*>( sqlsrv_malloc( ( SS_MAXCOLNAMELEN + 1 ) * sizeof( SQLWCHAR ) ));
    SQLSRV_ENCODING encoding = ( (stmt->encoding() == SQLSRV_ENCODING_DEFAULT ) ? stmt->conn->encoding() : stmt->encoding());
	try{
        core::SQLDescribeColW( stmt, colno + 1, field_name_temp, SS_MAXCOLNAMELEN + 1, &field_len_temp,
                               &( meta_data->field_type ), & ( meta_data->field_size ), & ( meta_data->field_scale ),
                               &( meta_data->field_is_nullable ) TSRMLS_CC );
	}
	catch ( core::CoreException& e ) {
		throw e;
	}

    bool converted = convert_string_from_utf16( encoding, field_name_temp, field_len_temp, ( char** ) &( meta_data->field_name ), field_name_len );

    CHECK_CUSTOM_ERROR( !converted, stmt, SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE, get_last_error_message() ) {
        throw core::CoreException();
    }

    // depending on field type, we add the values into size or precision/scale.
    switch( meta_data->field_type ) {
        case SQL_DECIMAL:
        case SQL_NUMERIC:
        case SQL_TYPE_TIMESTAMP:
        case SQL_TYPE_DATE:
        case SQL_SS_TIME2:
        case SQL_SS_TIMESTAMPOFFSET:
        case SQL_BIT:
        case SQL_TINYINT:
        case SQL_SMALLINT:
        case SQL_INTEGER:
        case SQL_BIGINT:
        case SQL_REAL:
        case SQL_FLOAT:
        case SQL_DOUBLE:
        {
            meta_data->field_precision = meta_data->field_size;
            meta_data->field_size = 0;
            break;
        }
        default: {
            break;
        }
    }

    // Set the field name lenth
    meta_data->field_name_len = static_cast<SQLSMALLINT>( field_name_len );

    field_meta_data* result_field_meta_data = meta_data;
    meta_data.transferred();
    return result_field_meta_data;
}


// core_sqlsrv_get_field
// Return the value of a column from ODBC
// Parameters:
// stmt                 - the sqlsrv_stmt from which to retrieve the column
// field_index          - 0 based index for the column to retrieve
// sqlsrv_php_type_in   - sqlsrv_php_type structure that tells what format to return the data in
// field_value          - pointer to the data retrieved
// field_len            - length of the data in the field_value buffer
// Returns:
// Nothing, excpetion thrown if an error occurs

void core_sqlsrv_get_field( _Inout_ sqlsrv_stmt* stmt, _In_ SQLUSMALLINT field_index, _In_ sqlsrv_phptype sqlsrv_php_type_in, _In_ bool prefer_string,
								_Outref_result_bytebuffer_maybenull_(*field_len) void*& field_value, _Inout_ SQLLEN* field_len, _In_ bool cache_field,
								_Out_ SQLSRV_PHPTYPE *sqlsrv_php_type_out TSRMLS_DC)
{
	try {

		// close the stream to release the resource
		close_active_stream(stmt TSRMLS_CC);

		// if the field has been retrieved before, return the previous result
		field_cache* cached = NULL;
		if (NULL != ( cached = static_cast<field_cache*>( zend_hash_index_find_ptr( Z_ARRVAL( stmt->field_cache ), static_cast<zend_ulong>( field_index ))))) {
			// the field value is NULL
			if( cached->value == NULL ) {
				field_value = NULL;
				*field_len = 0;
				if( sqlsrv_php_type_out ) { *sqlsrv_php_type_out = SQLSRV_PHPTYPE_NULL; }
			}
			else {

				field_value = sqlsrv_malloc( cached->len, sizeof( char ), 1 );
				memcpy_s( field_value, ( cached->len * sizeof( char )), cached->value, cached->len );
				if( cached->type.typeinfo.type == SQLSRV_PHPTYPE_STRING) {
					// prevent the 'string not null terminated' warning
					reinterpret_cast<char*>( field_value )[ cached->len ] = '\0';
				}
				*field_len = cached->len;
				if( sqlsrv_php_type_out) { *sqlsrv_php_type_out = static_cast<SQLSRV_PHPTYPE>(cached->type.typeinfo.type); }
			}
			return;
		}

		sqlsrv_phptype sqlsrv_php_type = sqlsrv_php_type_in;

		SQLLEN sql_field_type = 0;
		SQLLEN sql_field_len = 0;

		// Make sure that the statement was executed and not just prepared.
		CHECK_CUSTOM_ERROR( !stmt->executed, stmt, SQLSRV_ERROR_STATEMENT_NOT_EXECUTED ) {
			throw core::CoreException();
		}

		// if the field is to be cached, and this field is being retrieved out of order, cache prior fields so they
		// may also be retrieved.
		if( cache_field && (field_index - stmt->last_field_index ) >= 2 ) {
                   sqlsrv_phptype invalid;
                   invalid.typeinfo.type = SQLSRV_PHPTYPE_INVALID;
                   for( int i = stmt->last_field_index + 1; i < field_index; ++i ) {
                       SQLSRV_ASSERT( reinterpret_cast<field_cache*>( zend_hash_index_find_ptr( Z_ARRVAL( stmt->field_cache ), i )) == NULL, "Field already cached." );
                       core_sqlsrv_get_field( stmt, i, invalid, prefer_string, field_value, field_len, cache_field, sqlsrv_php_type_out TSRMLS_CC );
                       // delete the value returned since we only want it cached, not the actual value
                       if( field_value ) {
                           efree( field_value );
                           field_value = NULL;
                           *field_len = 0;
		       }
		   }
		}

		// If the php type was not specified set the php type to be the default type.
		if( sqlsrv_php_type.typeinfo.type == SQLSRV_PHPTYPE_INVALID ) {

			// Get the SQL type of the field.
			core::SQLColAttributeW( stmt, field_index + 1, SQL_DESC_CONCISE_TYPE, NULL, 0, NULL, &sql_field_type TSRMLS_CC );

			// Get the length of the field.
			core::SQLColAttributeW( stmt, field_index + 1, SQL_DESC_LENGTH, NULL, 0, NULL, &sql_field_len TSRMLS_CC );

			// Get the corresponding php type from the sql type.
			sqlsrv_php_type = stmt->sql_type_to_php_type( static_cast<SQLINTEGER>( sql_field_type ), static_cast<SQLUINTEGER>( sql_field_len ), prefer_string );
		}

		// Verify that we have an acceptable type to convert.
		CHECK_CUSTOM_ERROR( !is_valid_sqlsrv_phptype( sqlsrv_php_type ), stmt, SQLSRV_ERROR_INVALID_TYPE ) {
			throw core::CoreException();
		}

		if( sqlsrv_php_type_out != NULL )
			*sqlsrv_php_type_out = static_cast<SQLSRV_PHPTYPE>( sqlsrv_php_type.typeinfo.type );

		// Retrieve the data
		core_get_field_common( stmt, field_index, sqlsrv_php_type, field_value, field_len TSRMLS_CC );

		// if the user wants us to cache the field, we'll do it
		if( cache_field ) {
			field_cache cache( field_value, *field_len, sqlsrv_php_type );
			core::sqlsrv_zend_hash_index_update_mem( *stmt, Z_ARRVAL( stmt->field_cache ), field_index, &cache, sizeof(field_cache) TSRMLS_CC );
		}
	}

	catch( core::CoreException& e ) {
		throw e;
	}
}

// core_sqlsrv_has_any_result
// return if any result set or rows affected message is waiting
// to be consumed and moved over by sqlsrv_next_result.
// Parameters:
// stmt - The statement object on which to check for results.
// Return:
// true if any results are present, false otherwise.

bool core_sqlsrv_has_any_result( _Inout_ sqlsrv_stmt* stmt TSRMLS_DC )
{
    // Use SQLNumResultCols to determine if we have rows or not.
    SQLSMALLINT num_cols = core::SQLNumResultCols( stmt TSRMLS_CC );
    // use SQLRowCount to determine if there is a rows status waiting
    SQLLEN rows_affected = core::SQLRowCount( stmt TSRMLS_CC );
    return (num_cols != 0) || (rows_affected > 0);
}

// core_sqlsrv_next_result
// Advances to the next result set from the last executed query
// Parameters
// stmt - the sqlsrv_stmt structure
// Returns
// Nothing, exception thrown if problem occurs

void core_sqlsrv_next_result( _Inout_ sqlsrv_stmt* stmt TSRMLS_DC, _In_ bool finalize_output_params, _In_ bool throw_on_errors )
{
    try {

        // make sure that the statement has been executed.
        CHECK_CUSTOM_ERROR( !stmt->executed, stmt, SQLSRV_ERROR_STATEMENT_NOT_EXECUTED ) {
            throw core::CoreException();
        }

        CHECK_CUSTOM_ERROR( stmt->past_next_result_end, stmt, SQLSRV_ERROR_NEXT_RESULT_PAST_END ) {
            throw core::CoreException();
        }

        close_active_stream( stmt TSRMLS_CC );

        //Clear column sql types and sql display sizes.
        zend_hash_clean( Z_ARRVAL( stmt->col_cache ));

        SQLRETURN r;
        if( throw_on_errors ) {
            r = core::SQLMoreResults( stmt TSRMLS_CC );
        }
        else {
            r = SQLMoreResults( stmt->handle() );
        }

        if( r == SQL_NO_DATA ) {

            if( &(stmt->output_params) && finalize_output_params ) {
                // if we're finished processing result sets, handle the output parameters
                finalize_output_parameters( stmt TSRMLS_CC );
            }

            // mark we are past the end of all results
            stmt->past_next_result_end = true;
            return;
        }

        stmt->new_result_set( TSRMLS_C );
    }
    catch( core::CoreException& e ) {

        SQLCancel( stmt->handle() );
        throw e;
    }
}


// core_sqlsrv_post_param
// Performs any actions post execution for each parameter.  For now it cleans up input parameters memory from the statement
// Parameters:
// stmt      - the sqlsrv_stmt structure
// param_num - 0 based index of the parameter
// param_z   - parameter value itself.
// Returns:
// Nothing, exception thrown if problem occurs

void core_sqlsrv_post_param( _Inout_ sqlsrv_stmt* stmt, _In_ zend_ulong param_num, zval* param_z TSRMLS_DC )
{
    SQLSRV_ASSERT( Z_TYPE( stmt->param_input_strings ) == IS_ARRAY, "Statement input parameter UTF-16 buffers array invalid." );
    SQLSRV_ASSERT( Z_TYPE( stmt->param_streams ) == IS_ARRAY, "Statement input parameter streams array invalid." );

    // if the parameter was an input string, delete it from the array holding input parameter strings
    if( zend_hash_index_exists( Z_ARRVAL( stmt->param_input_strings ), param_num )) {
        core::sqlsrv_zend_hash_index_del( *stmt, Z_ARRVAL( stmt->param_input_strings ), param_num TSRMLS_CC );
    }

    // if the parameter was an input stream, decrement our reference to it and delete it from the array holding input streams
    // PDO doesn't need the reference count, but sqlsrv does since the stream can be live after sqlsrv_execute by sending it
    // with sqlsrv_send_stream_data.
    if( zend_hash_index_exists( Z_ARRVAL( stmt->param_streams ), param_num )) {
        core::sqlsrv_zend_hash_index_del( *stmt, Z_ARRVAL( stmt->param_streams ), param_num TSRMLS_CC );
    }
}

//Calls SQLSetStmtAttr to set a cursor.
void core_sqlsrv_set_scrollable( _Inout_ sqlsrv_stmt* stmt, _In_ unsigned long cursor_type TSRMLS_DC )
{
    try {

        switch( cursor_type ) {

            case SQL_CURSOR_STATIC:
                core::SQLSetStmtAttr( stmt, SQL_ATTR_CURSOR_TYPE,
                                      reinterpret_cast<SQLPOINTER>( SQL_CURSOR_STATIC ), SQL_IS_UINTEGER TSRMLS_CC );
                break;

            case SQL_CURSOR_DYNAMIC:
                core::SQLSetStmtAttr( stmt, SQL_ATTR_CURSOR_TYPE,
                                      reinterpret_cast<SQLPOINTER>( SQL_CURSOR_DYNAMIC ), SQL_IS_UINTEGER TSRMLS_CC );
                break;

            case SQL_CURSOR_KEYSET_DRIVEN:
                core::SQLSetStmtAttr( stmt, SQL_ATTR_CURSOR_TYPE,
                                      reinterpret_cast<SQLPOINTER>( SQL_CURSOR_KEYSET_DRIVEN ), SQL_IS_UINTEGER TSRMLS_CC );
                break;

            case SQL_CURSOR_FORWARD_ONLY:
                core::SQLSetStmtAttr( stmt, SQL_ATTR_CURSOR_TYPE,
                                      reinterpret_cast<SQLPOINTER>( SQL_CURSOR_FORWARD_ONLY ), SQL_IS_UINTEGER TSRMLS_CC );
                break;

            case SQLSRV_CURSOR_BUFFERED:
                core::SQLSetStmtAttr( stmt, SQL_ATTR_CURSOR_TYPE,
                                      reinterpret_cast<SQLPOINTER>( SQL_CURSOR_FORWARD_ONLY ), SQL_IS_UINTEGER TSRMLS_CC );
                break;

            default:
                THROW_CORE_ERROR( stmt, SQLSRV_ERROR_INVALID_OPTION_SCROLLABLE );
                break;
        }

        stmt->cursor_type = cursor_type;

    }
    catch( core::CoreException& ) {
        throw;
    }
}

void core_sqlsrv_set_buffered_query_limit( _Inout_ sqlsrv_stmt* stmt, _In_ zval* value_z TSRMLS_DC )
{
    if( Z_TYPE_P( value_z ) != IS_LONG ) {

        THROW_CORE_ERROR( stmt, SQLSRV_ERROR_INVALID_BUFFER_LIMIT );
    }

    core_sqlsrv_set_buffered_query_limit( stmt, Z_LVAL_P( value_z ) TSRMLS_CC );
}

void core_sqlsrv_set_buffered_query_limit( _Inout_ sqlsrv_stmt* stmt, _In_ SQLLEN limit TSRMLS_DC )
{
    if( limit <= 0 ) {

        THROW_CORE_ERROR( stmt, SQLSRV_ERROR_INVALID_BUFFER_LIMIT );
    }

    stmt->buffered_query_limit = limit;
}


// Overloaded. Extracts the long value and calls the core_sqlsrv_set_query_timeout
// which accepts timeout parameter as a long. If the zval is not of type long
// than throws error.
void core_sqlsrv_set_query_timeout( _Inout_ sqlsrv_stmt* stmt, _Inout_ zval* value_z TSRMLS_DC )
{
    try {

        // validate the value
        if( Z_TYPE_P( value_z ) != IS_LONG || Z_LVAL_P( value_z ) < 0 ) {

            convert_to_string( value_z );
            THROW_CORE_ERROR( stmt, SQLSRV_ERROR_INVALID_QUERY_TIMEOUT_VALUE, Z_STRVAL_P( value_z ) );
        }

        core_sqlsrv_set_query_timeout( stmt, static_cast<long>( Z_LVAL_P( value_z )) TSRMLS_CC );
    }
    catch( core::CoreException& ) {
        throw;
    }
}

// Overloaded. Accepts the timeout as a long.
void core_sqlsrv_set_query_timeout( _Inout_ sqlsrv_stmt* stmt, _In_ long timeout TSRMLS_DC )
{
    try {

        DEBUG_SQLSRV_ASSERT( timeout >= 0 , "core_sqlsrv_set_query_timeout: The value of query timeout cannot be less than 0." );

        // set the statement attribute
        core::SQLSetStmtAttr( stmt, SQL_ATTR_QUERY_TIMEOUT, reinterpret_cast<SQLPOINTER>( (SQLLEN)timeout ), SQL_IS_UINTEGER TSRMLS_CC );

        // a query timeout of 0 indicates "no timeout", which means that lock_timeout should also be set to "no timeout" which
        // is represented by -1.
        int lock_timeout = (( timeout == 0 ) ? -1 : timeout * 1000 /*convert to milliseconds*/ );

        // set the LOCK_TIMEOUT on the server.
        char lock_timeout_sql[ 32 ];

        int written = snprintf( lock_timeout_sql, sizeof( lock_timeout_sql ), "SET LOCK_TIMEOUT %d", lock_timeout );
        SQLSRV_ASSERT( (written != -1 && written != sizeof( lock_timeout_sql )),
                        "stmt_option_query_timeout: snprintf failed. Shouldn't ever fail." );

        core::SQLExecDirect( stmt, lock_timeout_sql TSRMLS_CC );

        stmt->query_timeout = timeout;
    }
    catch( core::CoreException& ) {
        throw;
    }
}

void core_sqlsrv_set_send_at_exec( _Inout_ sqlsrv_stmt* stmt, _In_ zval* value_z TSRMLS_DC )
{
    TSRMLS_C;

    // zend_is_true does not fail. It either returns true or false.
    stmt->send_streams_at_exec = ( zend_is_true( value_z )) ? true : false;
}


// core_sqlsrv_send_stream_packet
// send a single packet from a stream parameter to the database using
// ODBC.  This will also handle the transition between parameters.  It
// returns true if it is not done sending, false if it is finished.
// return_value is what should be returned to the script if it is
// given.  Any errors that occur are posted here.
// Parameters:
// stmt - query to send the next packet for
// Returns:
// true if more data remains to be sent, false if all data processed

bool core_sqlsrv_send_stream_packet( _Inout_ sqlsrv_stmt* stmt TSRMLS_DC )
{
    // if there no current parameter to process, get the next one
    // (probably because this is the first call to sqlsrv_send_stream_data)
    if( stmt->current_stream.stream_z == NULL ) {

        if( check_for_next_stream_parameter( stmt TSRMLS_CC ) == false ) {

            stmt->current_stream = sqlsrv_stream( NULL, SQLSRV_ENCODING_CHAR );
            stmt->current_stream_read = 0;
            return false;
        }
    }

    try {

    // get the stream from the zval we bound
    php_stream* param_stream = NULL;
    core::sqlsrv_php_stream_from_zval_no_verify( *stmt, param_stream, stmt->current_stream.stream_z TSRMLS_CC );

    // if we're at the end, then release our current parameter
    if( php_stream_eof( param_stream )) {
        // if no data was actually sent prior, then send a NULL
        if( stmt->current_stream_read == 0 ) {
            // send an empty string, which is what a 0 length does.
            char buff[1];       // temp storage to hand to SQLPutData
            core::SQLPutData( stmt, buff, 0 TSRMLS_CC );
        }
        stmt->current_stream = sqlsrv_stream( NULL, SQLSRV_ENCODING_CHAR );
        stmt->current_stream_read = 0;
    }
    // read the data from the stream, send it via SQLPutData and track how much we've sent.
    else {
        char buffer[ PHP_STREAM_BUFFER_SIZE + 1 ];
		std::size_t buffer_size = sizeof( buffer ) - 3;   // -3 to preserve enough space for a cut off UTF-8 character
        std::size_t read = php_stream_read( param_stream, buffer, buffer_size );

		if (read > UINT_MAX)
		{
			LOG(SEV_ERROR, "PHP stream: buffer length exceeded.");
			throw core::CoreException();
		}

        stmt->current_stream_read += static_cast<unsigned int>( read );
        if( read > 0 ) {
            // if this is a UTF-8 stream, then we will use the UTF-8 encoding to determine if we're in the middle of a character
            // then read in the appropriate number more bytes and then retest the string.  This way we try at most to convert it
            // twice.
            // If we support other encondings in the future, we'll simply need to read a single byte and then retry the conversion
            // since all other MBCS supported by SQL Server are 2 byte maximum size.
            if( stmt->current_stream.encoding == CP_UTF8 ) {

                // the size of wbuffer is set for the worst case of UTF-8 to UTF-16 conversion, which is a
                // expansion of 2x the UTF-8 size.
                SQLWCHAR wbuffer[ PHP_STREAM_BUFFER_SIZE + 1 ];
                int wbuffer_size = static_cast<int>( sizeof( wbuffer ) / sizeof( SQLWCHAR ));
				DWORD last_error_code = ERROR_SUCCESS;
				// buffer_size is the # of wchars.  Since it set to stmt->param_buffer_size / 2, this is accurate
#ifndef _WIN32
                int wsize = SystemLocale::ToUtf16Strict( stmt->current_stream.encoding, buffer, static_cast<int>(read), wbuffer, wbuffer_size, &last_error_code );
#else
                int wsize = MultiByteToWideChar( stmt->current_stream.encoding, MB_ERR_INVALID_CHARS, buffer, static_cast<int>( read ), wbuffer, wbuffer_size );
                last_error_code = GetLastError();
#endif // !_WIN32

				if( wsize == 0 && last_error_code == ERROR_NO_UNICODE_TRANSLATION ) {

                    // this will calculate how many bytes were cut off from the last UTF-8 character and read that many more
                    // in, then reattempt the conversion.  If it fails the second time, then an error is returned.
                    size_t need_to_read = calc_utf8_missing( stmt, buffer, read TSRMLS_CC );
                    // read the missing bytes
                    size_t new_read = php_stream_read( param_stream, static_cast<char*>( buffer ) + read,
                                                       need_to_read );
                    // if the bytes couldn't be read, then we return an error
                    CHECK_CUSTOM_ERROR( new_read != need_to_read, stmt, SQLSRV_ERROR_INPUT_STREAM_ENCODING_TRANSLATE, get_last_error_message( ERROR_NO_UNICODE_TRANSLATION )) {
                        throw core::CoreException();
                    }
                    // try the conversion again with the complete character
#ifndef _WIN32
                    wsize = SystemLocale::ToUtf16Strict( stmt->current_stream.encoding, buffer, static_cast<int>(read + new_read), wbuffer, static_cast<int>(sizeof( wbuffer ) / sizeof( SQLWCHAR )));
#else
                    wsize = MultiByteToWideChar( stmt->current_stream.encoding, MB_ERR_INVALID_CHARS, buffer, static_cast<int>( read + new_read ), wbuffer, static_cast<int>( sizeof( wbuffer ) / sizeof( wchar_t )));
#endif //!_WIN32
                    // something else must be wrong if it failed
                    CHECK_CUSTOM_ERROR( wsize == 0, stmt, SQLSRV_ERROR_INPUT_STREAM_ENCODING_TRANSLATE, get_last_error_message( ERROR_NO_UNICODE_TRANSLATION )) {
                        throw core::CoreException();
                    }
                }
                core::SQLPutData( stmt, wbuffer, wsize * sizeof( SQLWCHAR ) TSRMLS_CC );
            }
            else {
                core::SQLPutData( stmt, buffer, read TSRMLS_CC );
            }
        }
    }

    }
    catch( core::CoreException& e ) {
        stmt->free_param_data( TSRMLS_C );
        SQLFreeStmt( stmt->handle(), SQL_RESET_PARAMS );
        SQLCancel( stmt->handle() );
        stmt->current_stream = sqlsrv_stream( NULL, SQLSRV_ENCODING_DEFAULT );
        stmt->current_stream_read = 0;
        throw e;
    }

    return true;
}

void stmt_option_functor::operator()( _Inout_ sqlsrv_stmt* /*stmt*/, stmt_option const* /*opt*/, _In_ zval* /*value_z*/ TSRMLS_DC )
{
    TSRMLS_C;

    // This implementation should never get called.
    DIE( "Not implemented." );
}

void stmt_option_query_timeout:: operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /**/, _In_ zval* value_z TSRMLS_DC )
{
    core_sqlsrv_set_query_timeout( stmt, value_z TSRMLS_CC );
}

void stmt_option_send_at_exec:: operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /*opt*/, _In_ zval* value_z TSRMLS_DC )
{
    core_sqlsrv_set_send_at_exec( stmt, value_z TSRMLS_CC );
}

void stmt_option_buffered_query_limit:: operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /*opt*/, _In_ zval* value_z TSRMLS_DC )
{
    core_sqlsrv_set_buffered_query_limit( stmt, value_z TSRMLS_CC );
}


// internal function to release the active stream.  Called by each main API function
// that will alter the statement and cancel any retrieval of data from a stream.
void close_active_stream( _Inout_ sqlsrv_stmt* stmt TSRMLS_DC )
{
    // if there is no active stream, return
    if( Z_TYPE( stmt->active_stream ) == IS_UNDEF ) {
        return;
    }

    php_stream* stream = NULL;

    // we use no verify since verify would return immediately and we want to assert, not return.
    php_stream_from_zval_no_verify( stream, &( stmt->active_stream ));

    SQLSRV_ASSERT(( stream != NULL ), "close_active_stream: Unknown resource type as our active stream." );

    php_stream_close( stream ); // this will NULL out the active stream in the statement.  We don't check for errors here.

    SQLSRV_ASSERT( Z_TYPE( stmt->active_stream ) == IS_UNDEF, "close_active_stream: Active stream not closed." );

}

// local routines not shared by other files (arranged alphabetically)

namespace {

bool is_streamable_type( _In_ SQLLEN sql_type )
{
    switch( sql_type ) {
        case SQL_CHAR:
        case SQL_WCHAR:
        case SQL_BINARY:
        case SQL_VARBINARY:
        case SQL_VARCHAR:
        case SQL_WVARCHAR:
        case SQL_SS_XML:
        case SQL_LONGVARBINARY:
        case SQL_LONGVARCHAR:
        case SQL_WLONGVARCHAR:
            return true;
    }

    return false;
}

void calc_string_size( _Inout_ sqlsrv_stmt* stmt, _In_ SQLUSMALLINT field_index, _In_ SQLLEN sql_type,  _Inout_ SQLLEN& size TSRMLS_DC )
{
    try {

        switch( sql_type ) {
            // for types that are fixed in size or for which the size is unknown, return the display size.
            case SQL_BIGINT:
            case SQL_BIT:
            case SQL_INTEGER:
            case SQL_SMALLINT:
            case SQL_TINYINT:
            case SQL_GUID:
            case SQL_FLOAT:
            case SQL_DOUBLE:
            case SQL_REAL:
            case SQL_DECIMAL:
            case SQL_NUMERIC:
            case SQL_TYPE_TIMESTAMP:
            case SQL_LONGVARBINARY:
            case SQL_LONGVARCHAR:
            case SQL_BINARY:
            case SQL_CHAR:
            case SQL_VARBINARY:
            case SQL_VARCHAR:
            case SQL_SS_XML:
            case SQL_SS_UDT:
            case SQL_WLONGVARCHAR:
            case SQL_DATETIME:
            case SQL_TYPE_DATE:
            case SQL_SS_TIME2:
            case SQL_SS_TIMESTAMPOFFSET:
            case SQL_SS_VARIANT:
            {
                // unixODBC 2.3.1 requires wide calls to support pooling
                core::SQLColAttributeW( stmt, field_index + 1, SQL_DESC_DISPLAY_SIZE, NULL, 0, NULL, &size TSRMLS_CC );
                break;
            }

            // for wide char types for which the size is known, return the octet length instead, since it will include the
            // the number of bytes necessary for the string, not just the characters
            case SQL_WCHAR:
            case SQL_WVARCHAR:
            {
                // unixODBC 2.3.1 requires wide calls to support pooling
                core::SQLColAttributeW( stmt, field_index + 1, SQL_DESC_OCTET_LENGTH, NULL, 0, NULL, &size TSRMLS_CC );
                break;
            }

            default:
                DIE ( "Unexpected SQL type encountered in calc_string_size." );
        }
    }
    catch( core::CoreException& e ) {
        throw e;
    }
}


// calculates how many characters were cut off from the end of a buffer when reading
// in UTF-8 encoded text

size_t calc_utf8_missing( _Inout_ sqlsrv_stmt* stmt, _In_reads_(buffer_end) const char* buffer, _In_ size_t buffer_end TSRMLS_DC )
{
    const char* last_char = buffer + buffer_end - 1;
    size_t need_to_read = 0;

    // rewind until we are at the byte that starts the cut off character
    while( (*last_char & UTF8_MIDBYTE_MASK ) == UTF8_MIDBYTE_TAG ) {
        --last_char;
        ++need_to_read;
    }

    // determine how many bytes we need to read in based on the number of bytes in the character
    // (# of high bits set) versus the number of bytes we've already read.
    switch( *last_char & UTF8_NBYTESEQ_MASK ) {
        case UTF8_2BYTESEQ_TAG1:
        case UTF8_2BYTESEQ_TAG2:
            need_to_read = 1 - need_to_read;
            break;
        case UTF8_3BYTESEQ_TAG:
            need_to_read = 2 - need_to_read;
            break;
        case UTF8_4BYTESEQ_TAG:
            need_to_read = 3 - need_to_read;
            break;
        default:
            THROW_CORE_ERROR( stmt, SQLSRV_ERROR_INPUT_STREAM_ENCODING_TRANSLATE,
                              get_last_error_message( ERROR_NO_UNICODE_TRANSLATION ));
            break;
    }

    return need_to_read;
}


// Caller is responsible for freeing the memory allocated for the field_value.
// The memory allocation has to happen in the core layer because otherwise
// the driver layer would have to calculate size of the field_value
// to decide the amount of memory allocation.
void core_get_field_common( _Inout_ sqlsrv_stmt* stmt, _In_ SQLUSMALLINT field_index, _Inout_ sqlsrv_phptype
                            sqlsrv_php_type, _Inout_updates_bytes_(*field_len) void*& field_value, _Inout_ SQLLEN* field_len TSRMLS_DC )
{
    try {

        close_active_stream( stmt TSRMLS_CC );

        // make sure that fetch is called before trying to retrieve.
        CHECK_CUSTOM_ERROR( !stmt->fetch_called, stmt, SQLSRV_ERROR_FETCH_NOT_CALLED ) {
            throw core::CoreException();
        }

        // make sure that fields are not retrieved incorrectly.
        CHECK_CUSTOM_ERROR( stmt->last_field_index > field_index, stmt, SQLSRV_ERROR_FIELD_INDEX_ERROR, field_index,
                            stmt->last_field_index ) {
            throw core::CoreException();
        }

        switch( sqlsrv_php_type.typeinfo.type ) {

        case SQLSRV_PHPTYPE_INT:
        {
            sqlsrv_malloc_auto_ptr<long> field_value_temp;
            field_value_temp = static_cast<long*>( sqlsrv_malloc( sizeof( long )));
            *field_value_temp = 0;

            SQLRETURN r = stmt->current_results->get_data( field_index + 1, SQL_C_LONG, field_value_temp, sizeof( long ),
                                                           field_len, true /*handle_warning*/ TSRMLS_CC );

            CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
                throw core::CoreException();
            }

            CHECK_CUSTOM_ERROR(( r == SQL_NO_DATA ), stmt, SQLSRV_ERROR_NO_DATA, field_index ) {
                throw core::CoreException();
            }

            if( *field_len == SQL_NULL_DATA ) {
                field_value = NULL;
                break;
            }

            field_value = field_value_temp;
            field_value_temp.transferred();
            break;
        }

        case SQLSRV_PHPTYPE_FLOAT:
        {
            sqlsrv_malloc_auto_ptr<double> field_value_temp;
            field_value_temp = static_cast<double*>( sqlsrv_malloc( sizeof( double )));

            SQLRETURN r = stmt->current_results->get_data( field_index + 1, SQL_C_DOUBLE, field_value_temp, sizeof( double ),
                                                           field_len, true /*handle_warning*/ TSRMLS_CC );

            CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
                throw core::CoreException();
            }

            CHECK_CUSTOM_ERROR(( r == SQL_NO_DATA ), stmt, SQLSRV_ERROR_NO_DATA, field_index ) {
                throw core::CoreException();
            }

            if( *field_len == SQL_NULL_DATA ) {
                field_value = NULL;
                break;
            }

            field_value = field_value_temp;
            field_value_temp.transferred();
            break;
        }

        case SQLSRV_PHPTYPE_STRING:
        {
            get_field_as_string( stmt, field_index, sqlsrv_php_type, field_value, field_len TSRMLS_CC );
            break;
        }

        // get the date as a string (http://msdn2.microsoft.com/en-us/library/ms712387(VS.85).aspx) and
        // convert it to a DateTime object and return the created object
        case SQLSRV_PHPTYPE_DATETIME:
        {
            char field_value_temp[ MAX_DATETIME_STRING_LEN ];
            zval params[1];
            zval field_value_temp_z;
            zval function_z;

            ZVAL_UNDEF( &field_value_temp_z );
            ZVAL_UNDEF( &function_z );
            ZVAL_UNDEF( params );

            SQLRETURN r = stmt->current_results->get_data( field_index + 1, SQL_C_CHAR, field_value_temp,
                                                           MAX_DATETIME_STRING_LEN, field_len, true TSRMLS_CC );

            CHECK_CUSTOM_ERROR(( r == SQL_NO_DATA ), stmt, SQLSRV_ERROR_NO_DATA, field_index ) {
                throw core::CoreException();
            }

            zval_auto_ptr return_value_z;
            return_value_z = ( zval * )sqlsrv_malloc( sizeof( zval ));
            ZVAL_UNDEF( return_value_z );

            if( *field_len == SQL_NULL_DATA ) {
                ZVAL_NULL( return_value_z );
                field_value = reinterpret_cast<void*>( return_value_z.get());
                return_value_z.transferred();
                break;
            }

            // Convert the string date to a DateTime object
            core::sqlsrv_zval_stringl( &field_value_temp_z, field_value_temp, *field_len );
            core::sqlsrv_zval_stringl( &function_z, "date_create", sizeof("date_create") - 1 );
            params[0] = field_value_temp_z;

            if( call_user_function( EG( function_table ), NULL, &function_z, return_value_z, 1,
                params TSRMLS_CC ) == FAILURE) {
                THROW_CORE_ERROR(stmt, SQLSRV_ERROR_DATETIME_CONVERSION_FAILED);
            }

            field_value = reinterpret_cast<void*>( return_value_z.get());
            return_value_z.transferred();
            zend_string_free( Z_STR( field_value_temp_z ));
            zend_string_free( Z_STR( function_z ));
            break;
        }

        // create a stream wrapper around the field and return that object to the PHP script.  calls to fread
        // on the stream will result in calls to SQLGetData.  This is handled in stream.cpp.  See that file
        // for how these fields are used.
        case SQLSRV_PHPTYPE_STREAM:
        {
            php_stream* stream = NULL;
            sqlsrv_stream* ss = NULL;
            SQLLEN sql_type;

            SQLRETURN r = SQLColAttributeW( stmt->handle(), field_index + 1, SQL_DESC_TYPE, NULL, 0, NULL, &sql_type );
            CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
                throw core::CoreException();
            }

            CHECK_CUSTOM_ERROR( !is_streamable_type( sql_type ), stmt, SQLSRV_ERROR_STREAMABLE_TYPES_ONLY ) {
                throw core::CoreException();
            }

            stream = php_stream_open_wrapper( "sqlsrv://sqlncli10", "r", 0, NULL );

            CHECK_CUSTOM_ERROR( !stream, stmt, SQLSRV_ERROR_STREAM_CREATE ) {
                throw core::CoreException();
            }

            ss = static_cast<sqlsrv_stream*>( stream->abstract );
            ss->stmt = stmt;
            ss->field_index = field_index;
            ss->sql_type = static_cast<SQLUSMALLINT>( sql_type );
            ss->encoding = static_cast<SQLSRV_ENCODING>( sqlsrv_php_type.typeinfo.encoding );

            zval_auto_ptr return_value_z;
            return_value_z = ( zval * )sqlsrv_malloc( sizeof( zval ));
            ZVAL_UNDEF( return_value_z );

            // turn our stream into a zval to be returned
            php_stream_to_zval( stream, return_value_z );

            field_value = reinterpret_cast<void*>( return_value_z.get());
            return_value_z.transferred();
            break;
        }

        case SQLSRV_PHPTYPE_NULL:
            field_value = NULL;
            *field_len = 0;
            break;

        default:
            DIE( "core_get_field_common: Unexpected sqlsrv_phptype provided" );
            break;
        }

        // sucessfully retrieved the field, so update our last retrieved field
        if( stmt->last_field_index < field_index ) {
            stmt->last_field_index = field_index;
        }
    }
    catch( core::CoreException& e ) {
        throw e;
    }
}


// check_for_next_stream_parameter
// see if there is another stream to be sent.  Returns true and sets the stream as current in the statement structure, otherwise
// returns false
bool check_for_next_stream_parameter( _Inout_ sqlsrv_stmt* stmt TSRMLS_DC )
{
    zend_ulong stream_index = 0;
    SQLRETURN r = SQL_SUCCESS;
    sqlsrv_stream* stream_encoding = NULL;
    zval* param_z = NULL;

    // get the index into the streams_ht from the parameter data we set in core_sqlsrv_bind_param
    r = core::SQLParamData( stmt, reinterpret_cast<SQLPOINTER*>( &stream_index ) TSRMLS_CC );
    // if no more data, we've exhausted the bound parameters, so return that we're done
    if( SQL_SUCCEEDED( r ) || r == SQL_NO_DATA ) {

        // we're all done, so return false
        return false;
    }

    HashTable* streams_ht = Z_ARRVAL( stmt->param_streams );

    // pull out the sqlsrv_encoding struct
    stream_encoding = reinterpret_cast<sqlsrv_stream*>(zend_hash_index_find_ptr(streams_ht, stream_index));
    SQLSRV_ASSERT(stream_encoding != NULL, "Stream parameter does not exist");  // if the index isn't in the hash, that's a serious error

    param_z = stream_encoding->stream_z;

    // make the next stream current
    stmt->current_stream = sqlsrv_stream( param_z, stream_encoding->encoding );
    stmt->current_stream_read = 0;

    // there are more parameters
    return true;
}


// utility routine to convert an input parameter from UTF-8 to UTF-16

bool convert_input_param_to_utf16( _In_ zval* input_param_z, _Inout_ zval* converted_param_z )
{
    SQLSRV_ASSERT( input_param_z == converted_param_z || Z_TYPE_P( converted_param_z ) == IS_NULL,
        "convert_input_param_z called with invalid parameter states" );

    const char* buffer = Z_STRVAL_P( input_param_z );
    std::size_t buffer_len = Z_STRLEN_P( input_param_z );
    int wchar_size;

	if (buffer_len > INT_MAX)
	{
		LOG(SEV_ERROR, "Convert input parameter to utf16: buffer length exceeded.");
		throw core::CoreException();
	}

    // if the string is empty, then just return that the conversion succeeded as
    // MultiByteToWideChar will "fail" on an empty string.
    if( buffer_len == 0 ) {
		core::sqlsrv_zval_stringl( converted_param_z, "", 0 );
        return true;
    }

    // if the parameter is an input parameter, calc the size of the necessary buffer from the length of the string
#ifndef _WIN32
    wchar_size = SystemLocale::ToUtf16Strict( CP_UTF8, reinterpret_cast<LPCSTR>( buffer ), static_cast<int>( buffer_len ), NULL, 0 );
#else
    wchar_size = MultiByteToWideChar( CP_UTF8, MB_ERR_INVALID_CHARS, reinterpret_cast<LPCSTR>( buffer ), static_cast<int>( buffer_len ), NULL, 0 );
#endif // !_WIN32

    // if there was a problem determining the size of the string, return false
    if( wchar_size == 0 ) {
        return false;
    }
    sqlsrv_malloc_auto_ptr<SQLWCHAR> wbuffer;
    wbuffer = reinterpret_cast<SQLWCHAR*>( sqlsrv_malloc( (wchar_size + 1) * sizeof( SQLWCHAR ) ));
    // convert the utf-8 string to a wchar string in the new buffer
#ifndef _WIN32
    int r = SystemLocale::ToUtf16Strict( CP_UTF8, reinterpret_cast<LPCSTR>( buffer ), static_cast<int>( buffer_len ), wbuffer, wchar_size );
#else
    int r = MultiByteToWideChar( CP_UTF8, MB_ERR_INVALID_CHARS, reinterpret_cast<LPCSTR>( buffer ), static_cast<int>( buffer_len ), wbuffer, wchar_size );
#endif // !_WIN32
    // if there was a problem converting the string, then free the memory and return false
    if( r == 0 ) {
        return false;
    }

    // null terminate the string, set the size within the zval, and return success
    wbuffer[ wchar_size ] = L'\0';
    core::sqlsrv_zval_stringl( converted_param_z, reinterpret_cast<char*>( wbuffer.get() ), wchar_size * sizeof( SQLWCHAR ) );
    sqlsrv_free(wbuffer);
    wbuffer.transferred();

    return true;
}

// returns the ODBC C type constant that matches the PHP type and encoding given

SQLSMALLINT default_c_type( _Inout_ sqlsrv_stmt* stmt, _In_opt_ SQLULEN paramno, _In_ zval const* param_z, _In_ SQLSRV_ENCODING encoding TSRMLS_DC )
{
    SQLSMALLINT sql_c_type = SQL_UNKNOWN_TYPE;
    int php_type = Z_TYPE_P( param_z );

    switch( php_type ) {

        case IS_NULL:
            switch( encoding ) {
                // The c type is set to match to the corresponding sql_type. For NULL cases, if the server type
                // is a binary type, than the server expects the sql_type to be binary type as well, otherwise
                // an error stating "Implicit conversion not allowed.." is thrown by the server.
                // For all other server types, setting the sql_type to sql_char works fine.
                case SQLSRV_ENCODING_BINARY:
                    sql_c_type = SQL_C_BINARY;
                    break;
                default:
                    sql_c_type = SQL_C_CHAR;
                    break;
            }
        break;
        case IS_TRUE:
        case IS_FALSE:
            sql_c_type = SQL_C_SLONG;
            break;
        case IS_LONG:
            //ODBC 64-bit long and integer type are 4 byte values.
            if ((Z_LVAL_P(param_z) < INT_MIN) || (Z_LVAL_P(param_z) > INT_MAX)) {
                sql_c_type = SQL_C_SBIGINT;
            }
            else {
                sql_c_type = SQL_C_SLONG;
            }
            break;
        case IS_DOUBLE:
            sql_c_type = SQL_C_DOUBLE;
            break;
        case IS_STRING:
        case IS_RESOURCE:
            switch( encoding ) {
                case SQLSRV_ENCODING_CHAR:
                    sql_c_type = SQL_C_CHAR;
                    break;
                case SQLSRV_ENCODING_BINARY:
                    sql_c_type = SQL_C_BINARY;
                    break;
                case CP_UTF8:
                    sql_c_type = SQL_C_WCHAR;
                    break;
                default:
                    THROW_CORE_ERROR( stmt, SQLSRV_ERROR_INVALID_PARAMETER_ENCODING, paramno );
                    break;
            }
            break;
        // it is assumed that an object is a DateTime since it's the only thing we support.
        // verification that it's a real DateTime object occurs in core_sqlsrv_bind_param.
        // we convert the DateTime to a string before sending it to the server.
        case IS_OBJECT:
            sql_c_type = SQL_C_CHAR;
            break;
        default:
            THROW_CORE_ERROR( stmt, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE, paramno );
            break;
    }

    return sql_c_type;
}


// given a zval and encoding, determine the appropriate sql type
void default_sql_type( _Inout_ sqlsrv_stmt* stmt, _In_opt_ SQLULEN paramno, _In_ zval* param_z, _In_ SQLSRV_ENCODING encoding,
                       _Out_ SQLSMALLINT& sql_type TSRMLS_DC )
{
    sql_type = SQL_UNKNOWN_TYPE;
	int php_type = Z_TYPE_P(param_z);
    switch( php_type ) {

        case IS_NULL:
            switch( encoding ) {
                // Use the encoding to guess whether the sql_type is binary type or char type. For NULL cases,
                // if the server type is a binary type, than the server expects the sql_type to be binary type
                // as well, otherwise an error stating "Implicit conversion not allowed.." is thrown by the
                // server. For all other server types, setting the sql_type to sql_char works fine.
                case SQLSRV_ENCODING_BINARY:
                    sql_type = SQL_BINARY;
                    break;
                default:
                    sql_type = SQL_CHAR;
                    break;
            }
            break;
        case IS_TRUE:
        case IS_FALSE:
            sql_type = SQL_INTEGER;
            break;
        case IS_LONG:
            //ODBC 64-bit long and integer type are 4 byte values.
            if ((Z_LVAL_P(param_z) < INT_MIN) || (Z_LVAL_P(param_z) > INT_MAX)) {
                sql_type = SQL_BIGINT;
            }
            else {
                sql_type = SQL_INTEGER;
            }
            break;
        case IS_DOUBLE:
            sql_type = SQL_FLOAT;
            break;
        case IS_RESOURCE:
        case IS_STRING:
            switch( encoding ) {
                case SQLSRV_ENCODING_CHAR:
                    sql_type = SQL_VARCHAR;
                    break;
                case SQLSRV_ENCODING_BINARY:
                    sql_type = SQL_VARBINARY;
                    break;
                case CP_UTF8:
                    sql_type = SQL_WVARCHAR;
                    break;
                default:
                    THROW_CORE_ERROR( stmt, SQLSRV_ERROR_INVALID_PARAMETER_ENCODING, paramno );
                    break;
            }
            break;
        // it is assumed that an object is a DateTime since it's the only thing we support.
        // verification that it's a real DateTime object occurs in the calling function.
        // we convert the DateTime to a string before sending it to the server.
        case IS_OBJECT:
            // if the user is sending this type to SQL Server 2005 or earlier, make it appear
            // as a SQLSRV_SQLTYPE_DATETIME, otherwise it should be SQLSRV_SQLTYPE_TIMESTAMPOFFSET
            // since these are the date types of the highest precision for their respective server versions
            if( stmt->conn->server_version <= SERVER_VERSION_2005 ) {
                sql_type = SQL_TYPE_TIMESTAMP;
            }
            else {
                sql_type = SQL_SS_TIMESTAMPOFFSET;
            }
            break;
        default:
            THROW_CORE_ERROR( stmt, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE, paramno );
            break;
    }

}


// given a zval and encoding, determine the appropriate column size, and decimal scale (if appropriate)

void default_sql_size_and_scale( _Inout_ sqlsrv_stmt* stmt, _In_opt_ unsigned int paramno, _In_ zval* param_z, _In_ SQLSRV_ENCODING encoding,
                                 _Out_ SQLULEN& column_size, _Out_ SQLSMALLINT& decimal_digits TSRMLS_DC )
{
    int php_type = Z_TYPE_P( param_z );
    column_size = 0;
    decimal_digits = 0;

    switch( php_type ) {

        case IS_NULL:
            column_size = 1;
            break;
        // size is not necessary for these types, they are inferred by ODBC
        case IS_TRUE:
        case IS_FALSE:
        case IS_LONG:
        case IS_DOUBLE:
        case IS_RESOURCE:
            break;
        case IS_STRING:
        {
            size_t char_size = (encoding == SQLSRV_ENCODING_UTF8 ) ? sizeof( SQLWCHAR ) : sizeof( char );
            SQLULEN byte_len = Z_STRLEN_P(param_z) * char_size;
            if( byte_len > SQL_SERVER_MAX_FIELD_SIZE ) {
                column_size = SQL_SERVER_MAX_TYPE_SIZE;
            }
            else {
                column_size = SQL_SERVER_MAX_FIELD_SIZE / char_size;
            }
            break;
        }
        // it is assumed that an object is a DateTime since it's the only thing we support.
        // verification that it's a real DateTime object occurs in the calling function.
        // we convert the DateTime to a string before sending it to the server.
        case IS_OBJECT:
            // if the user is sending this type to SQL Server 2005 or earlier, make it appear
            // as a SQLSRV_SQLTYPE_DATETIME, otherwise it should be SQLSRV_SQLTYPE_TIMESTAMPOFFSET
            // since these are the date types of the highest precision for their respective server versions
            if( stmt->conn->server_version <= SERVER_VERSION_2005 ) {
                column_size = SQL_SERVER_2005_DEFAULT_DATETIME_PRECISION;
                decimal_digits = SQL_SERVER_2005_DEFAULT_DATETIME_SCALE;
            }
            else {
                column_size = SQL_SERVER_2008_DEFAULT_DATETIME_PRECISION;
                decimal_digits = SQL_SERVER_2008_DEFAULT_DATETIME_SCALE;
            }
            break;
        default:
            THROW_CORE_ERROR( stmt, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE, paramno );
            break;
    }
}

void col_cache_dtor( _Inout_ zval* data_z )
{
    col_cache* cache = static_cast<col_cache*>( Z_PTR_P( data_z ));
    sqlsrv_free( cache );
}

void field_cache_dtor( _Inout_ zval* data_z )
{
    field_cache* cache = static_cast<field_cache*>( Z_PTR_P( data_z ));
    if( cache->value )
    {
        sqlsrv_free( cache->value );
    }
	sqlsrv_free( cache );
}


// To be called after all results are processed.  ODBC and SQL Server do not guarantee that all output
// parameters will be present until all results are processed (since output parameters can depend on results
// while being processed).  This function updates the lengths of output parameter strings from the ind_ptr
// parameters passed to SQLBindParameter.  It also converts output strings from UTF-16 to UTF-8 if necessary.
// For integer or float parameters, it sets those to NULL if a NULL was returned by SQL Server

void finalize_output_parameters( _Inout_ sqlsrv_stmt* stmt TSRMLS_DC )
{
    if( Z_ISUNDEF(stmt->output_params) )
        return;

    HashTable* params_ht = Z_ARRVAL( stmt->output_params );
	zend_ulong index = -1;
	zend_string* key = NULL;
	void* output_param_temp = NULL;

	ZEND_HASH_FOREACH_KEY_PTR( params_ht, index, key, output_param_temp ) {
		sqlsrv_output_param* output_param = static_cast<sqlsrv_output_param*>( output_param_temp );
		zval* value_z = Z_REFVAL_P( output_param->param_z );
        switch( Z_TYPE_P( value_z )) {
        case IS_STRING:
        {
            // adjust the length of the string to the value returned by SQLBindParameter in the ind_ptr parameter
            char* str = Z_STRVAL_P( value_z );
            SQLLEN str_len = stmt->param_ind_ptrs[ output_param->param_num ];
            if( str_len == 0 ) {
                core::sqlsrv_zval_stringl( value_z, "", 0 );
                continue;
            }
            if( str_len == SQL_NULL_DATA ) {
                zend_string_release( Z_STR_P( value_z ));
                ZVAL_NULL( value_z );
                continue;
            }

            // if there was more to output than buffer size to hold it, then throw a truncation error
            int null_size = 0;
            switch( output_param->encoding ) {
            case SQLSRV_ENCODING_UTF8:
                null_size = sizeof( SQLWCHAR );  // string isn't yet converted to UTF-8, still UTF-16
                break;
            case SQLSRV_ENCODING_SYSTEM:
                null_size = 1;
                break;
            case SQLSRV_ENCODING_BINARY:
                null_size = 0;
                break;
            default:
                SQLSRV_ASSERT( false, "Invalid encoding in output_param structure." );
                break;
            }
            CHECK_CUSTOM_ERROR( str_len > ( output_param->original_buffer_len - null_size ), stmt,
                SQLSRV_ERROR_OUTPUT_PARAM_TRUNCATED, output_param->param_num + 1 ) {
                throw core::CoreException();
            }

            // For ODBC 11+ see https://msdn.microsoft.com/en-us/library/jj219209.aspx
            // A length value of SQL_NO_TOTAL for SQLBindParameter indicates that the buffer contains up to
            // output_param->original_buffer_len data and is NULL terminated.
            // The IF statement can be true when using connection pooling with unixODBC 2.3.4.
            if ( str_len == SQL_NO_TOTAL )
            {
                str_len = output_param->original_buffer_len - null_size;
            }

            // if it's not in the 8 bit encodings, then it's in UTF-16
            if( output_param->encoding != SQLSRV_ENCODING_CHAR && output_param->encoding != SQLSRV_ENCODING_BINARY ) {
				bool converted = convert_zval_string_from_utf16(output_param->encoding, value_z, str_len);
                CHECK_CUSTOM_ERROR( !converted, stmt, SQLSRV_ERROR_OUTPUT_PARAM_ENCODING_TRANSLATE, get_last_error_message()) {
                    throw core::CoreException();
                }
            }
            else if( output_param->encoding == SQLSRV_ENCODING_BINARY && str_len < output_param->original_buffer_len ) {
                // ODBC doesn't null terminate binary encodings, but PHP complains if a string isn't null terminated
                // so we do that here if the length of the returned data is less than the original allocation.  The
                // original allocation null terminates the buffer already.
                str[ str_len ] = '\0';
                core::sqlsrv_zval_stringl(value_z, str, str_len);
            }
            else {
                core::sqlsrv_zval_stringl(value_z, str, str_len);
            }
        }
        break;
        case IS_LONG:
            // for a long or a float, simply check if NULL was returned and set the parameter to a PHP null if so
            if( stmt->param_ind_ptrs[ output_param->param_num ] == SQL_NULL_DATA ) {
                ZVAL_NULL( value_z );
            }
            else if( output_param->is_bool ) {
                convert_to_boolean( value_z );
            }
            else {
                ZVAL_LONG( value_z, static_cast<int>( Z_LVAL_P( value_z )));
            }
            break;
        case IS_DOUBLE:
            // for a long or a float, simply check if NULL was returned and set the parameter to a PHP null if so
            if (stmt->param_ind_ptrs[output_param->param_num] == SQL_NULL_DATA) {
                ZVAL_NULL(value_z);
            }
            else if (output_param->php_out_type == SQLSRV_PHPTYPE_INT) {
                // first check if its value is out of range
                double dval = Z_DVAL_P(value_z);
                if (dval > INT_MAX || dval < INT_MIN) {
                    CHECK_CUSTOM_ERROR(true, stmt, SQLSRV_ERROR_DOUBLE_CONVERSION_FAILED) {
                        throw core::CoreException();
                    }
                }
                // if the output param is a boolean, still convert to 
                // a long integer first to take care of rounding
                convert_to_long(value_z);
                if (output_param->is_bool) {
                    convert_to_boolean(value_z);
                }
            }
            break;
        default:
            DIE( "Illegal or unknown output parameter type. This should have been caught in core_sqlsrv_bind_parameter." );
            break;
        }
		value_z = NULL;
    }  ZEND_HASH_FOREACH_END();

    // empty the hash table since it's been processed
    zend_hash_clean( Z_ARRVAL( stmt->output_params ));
    return;
}

void get_field_as_string( _Inout_ sqlsrv_stmt* stmt, _In_ SQLUSMALLINT field_index, _Inout_ sqlsrv_phptype sqlsrv_php_type,
                          _Inout_updates_bytes_(*field_len) void*& field_value, _Inout_ SQLLEN* field_len TSRMLS_DC )
{
    SQLRETURN r;
    SQLSMALLINT c_type;
    SQLLEN sql_field_type = 0;
    SQLSMALLINT extra = 0;
    SQLLEN field_len_temp = 0;
    SQLLEN sql_display_size = 0;
    char* field_value_temp = NULL;
    unsigned int intial_field_len = INITIAL_FIELD_STRING_LEN;

    try {

        DEBUG_SQLSRV_ASSERT( sqlsrv_php_type.typeinfo.type == SQLSRV_PHPTYPE_STRING,
                             "Type should be SQLSRV_PHPTYPE_STRING in get_field_as_string" );

        if( sqlsrv_php_type.typeinfo.encoding == SQLSRV_ENCODING_DEFAULT ) {
            sqlsrv_php_type.typeinfo.encoding = stmt->conn->encoding();
        }

        // Set the C type and account for null characters at the end of the data.
        switch( sqlsrv_php_type.typeinfo.encoding ) {
        case CP_UTF8:
            c_type = SQL_C_WCHAR;
            extra = sizeof( SQLWCHAR );
            break;
        case SQLSRV_ENCODING_BINARY:
            c_type = SQL_C_BINARY;
            extra = 0;
            break;
        default:
            c_type = SQL_C_CHAR;
            extra = sizeof( SQLCHAR );
            break;
        }

        col_cache* cached = NULL;
        if ( NULL != ( cached = static_cast< col_cache* >( zend_hash_index_find_ptr( Z_ARRVAL( stmt->col_cache ), static_cast< zend_ulong >( field_index ))))) {
            sql_field_type = cached->sql_type;
            sql_display_size = cached->display_size;
        }
        else {
            // Get the SQL type of the field. unixODBC 2.3.1 requires wide calls to support pooling
            core::SQLColAttributeW( stmt, field_index + 1, SQL_DESC_CONCISE_TYPE, NULL, 0, NULL, &sql_field_type TSRMLS_CC );

            // Calculate the field size.
            calc_string_size( stmt, field_index, sql_field_type, sql_display_size TSRMLS_CC );

            col_cache cache( sql_field_type, sql_display_size );
            core::sqlsrv_zend_hash_index_update_mem( *stmt, Z_ARRVAL( stmt->col_cache ), field_index, &cache, sizeof( col_cache ) TSRMLS_CC );
        }

        // if this is a large type, then read the first few bytes to get the actual length from SQLGetData
        if( sql_display_size == 0 || sql_display_size == INT_MAX ||
            sql_display_size == INT_MAX >> 1 || sql_display_size == UINT_MAX - 1 ) {

            field_len_temp = intial_field_len;

            SQLLEN initiallen = field_len_temp + extra;

            field_value_temp = static_cast<char*>( sqlsrv_malloc( field_len_temp + extra + 1 ));

            r = stmt->current_results->get_data( field_index + 1, c_type, field_value_temp, ( field_len_temp + extra ),
                                                 &field_len_temp, false /*handle_warning*/ TSRMLS_CC );

            CHECK_CUSTOM_ERROR(( r == SQL_NO_DATA ), stmt, SQLSRV_ERROR_NO_DATA, field_index ) {
                throw core::CoreException();
            }

            if( field_len_temp == SQL_NULL_DATA ) {
                field_value = NULL;
                sqlsrv_free( field_value_temp );
                return;
            }

            if( r == SQL_SUCCESS_WITH_INFO ) {

                SQLCHAR state[SQL_SQLSTATE_BUFSIZE] = { 0 };
                SQLSMALLINT len = 0;

                stmt->current_results->get_diag_field( 1, SQL_DIAG_SQLSTATE, state, SQL_SQLSTATE_BUFSIZE, &len TSRMLS_CC );

                // with Linux connection pooling may not get a truncated warning back but the actual field_len_temp
                // can be greater than the initallen value.
#ifndef _WIN32
                if( is_truncated_warning( state ) || initiallen < field_len_temp) {
#else
                if( is_truncated_warning( state ) ) {
#endif // !_WIN32

                    SQLLEN dummy_field_len = 0;

                    // for XML (and possibly other conditions) the field length returned is not the real field length, so
                    // in every pass, we double the allocation size to retrieve all the contents.
                    if( field_len_temp == SQL_NO_TOTAL ) {

                        // reset the field_len_temp
                        field_len_temp = intial_field_len;

                        do {
                            SQLLEN initial_field_len = field_len_temp;
                            // Double the size.
                            field_len_temp *= 2;

                            field_value_temp = static_cast<char*>( sqlsrv_realloc( field_value_temp, field_len_temp + extra + 1 ));

                            field_len_temp -= initial_field_len;

                            // Get the rest of the data.
                            r = stmt->current_results->get_data( field_index + 1, c_type, field_value_temp + initial_field_len,
                                field_len_temp + extra, &dummy_field_len, false /*handle_warning*/ TSRMLS_CC );
                            // the last packet will contain the actual amount retrieved, not SQL_NO_TOTAL
                            // so we calculate the actual length of the string with that.
                            if ( dummy_field_len != SQL_NO_TOTAL )
                                field_len_temp += dummy_field_len;
                            else
                                field_len_temp += initial_field_len;

                            if( r == SQL_SUCCESS_WITH_INFO ) {
                                core::SQLGetDiagField( stmt, 1, SQL_DIAG_SQLSTATE, state, SQL_SQLSTATE_BUFSIZE, &len
                                                       TSRMLS_CC );
                            }

                        } while( r == SQL_SUCCESS_WITH_INFO && is_truncated_warning( state ));
                    }
                    else {
                        // the real field length is returned here, thus no need to double the allocation size here, just have to
                        // allocate field_len_temp (which is the field length retrieved from the first SQLGetData
                        field_value_temp = static_cast<char*>( sqlsrv_realloc( field_value_temp, field_len_temp + extra + 1 ));

                        // We have already received intial_field_len size data.
                        field_len_temp -= intial_field_len;

                        // Get the rest of the data.
                        r = stmt->current_results->get_data( field_index + 1, c_type, field_value_temp + intial_field_len,
                            field_len_temp + extra, &dummy_field_len, true /*handle_warning*/ TSRMLS_CC );
                        field_len_temp += intial_field_len;

                        if( dummy_field_len == SQL_NULL_DATA ) {
                            field_value = NULL;
                            sqlsrv_free( field_value_temp );
                            return;
                        }

                        CHECK_CUSTOM_ERROR(( r == SQL_NO_DATA ), stmt, SQLSRV_ERROR_NO_DATA, field_index ) {
                            throw core::CoreException();
                        }
                    }

                } // if( is_truncation_warning ( state ) )
                else {
                    CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
                        throw core::CoreException();
                    }
                }
            }  // if( r == SQL_SUCCESS_WITH_INFO )

            if( sqlsrv_php_type.typeinfo.encoding == SQLSRV_ENCODING_UTF8 ) {

                bool converted = convert_string_from_utf16_inplace( static_cast<SQLSRV_ENCODING>( sqlsrv_php_type.typeinfo.encoding ),
                                                                    &field_value_temp, field_len_temp );

                CHECK_CUSTOM_ERROR( !converted, stmt, SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE, get_last_error_message()) {
                    throw core::CoreException();
                }
            }
        } // if ( sql_display_size == 0 || sql_display_size == LONG_MAX .. )

        else if( sql_display_size >= 1 && sql_display_size <= SQL_SERVER_MAX_FIELD_SIZE ) {

            // only allow binary retrievals for char and binary types.  All others get a string converted
            // to the encoding type they asked for.

            // null terminator
            if( c_type == SQL_C_CHAR ) {
                sql_display_size += sizeof( SQLCHAR );
            }

            // For WCHAR multiply by sizeof(WCHAR) and include the null terminator
            else if( c_type == SQL_C_WCHAR ) {
                sql_display_size = (sql_display_size * sizeof(WCHAR)) + sizeof(WCHAR);
            }

            field_value_temp = static_cast<char*>( sqlsrv_malloc( sql_display_size + extra + 1 ));

            // get the data
            r = stmt->current_results->get_data( field_index + 1, c_type, field_value_temp, sql_display_size,
                                                 &field_len_temp, true /*handle_warning*/ TSRMLS_CC );
            CHECK_SQL_ERROR( r, stmt ) {
                throw core::CoreException();
            }
            CHECK_CUSTOM_ERROR(( r == SQL_NO_DATA ), stmt, SQLSRV_ERROR_NO_DATA, field_index ) {
                throw core::CoreException();
            }

            if( field_len_temp == SQL_NULL_DATA ) {
                field_value = NULL;
                sqlsrv_free( field_value_temp );
                return;
            }

            if( sqlsrv_php_type.typeinfo.encoding == CP_UTF8 ) {

                bool converted = convert_string_from_utf16_inplace( static_cast<SQLSRV_ENCODING>( sqlsrv_php_type.typeinfo.encoding ),
                                                                    &field_value_temp, field_len_temp );

                CHECK_CUSTOM_ERROR( !converted, stmt, SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE, get_last_error_message()) {
                    throw core::CoreException();
                }
            }
        } // else if( sql_display_size >= 1 && sql_display_size <= SQL_SERVER_MAX_FIELD_SIZE )

        else {

            DIE( "Invalid sql_display_size" );
            return; // to eliminate a warning
        }

field_value = field_value_temp;
*field_len = field_len_temp;

        // prevent a warning in debug mode about strings not being NULL terminated.  Even though nulls are not necessary, the PHP
        // runtime checks to see if a string is null terminated and issues a warning about it if running in debug mode.
        // SQL_C_BINARY fields don't return a NULL terminator, so we allocate an extra byte on each field and use the ternary
        // operator to set add 1 to fill the null terminator

        // with unixODBC connection pooling sometimes field_len_temp can be SQL_NO_DATA.
        // In that cause do not set null terminator and set length to 0.
        if ( field_len_temp > 0 )
        {
            field_value_temp[field_len_temp] = '\0';
        }
        else
        {
            *field_len = 0;
        }
    }

    catch( core::CoreException& ) {

        field_value = NULL;
        *field_len = 0;
        sqlsrv_free( field_value_temp );
        throw;
    }
    catch ( ... ) {

        field_value = NULL;
        *field_len = 0;
        sqlsrv_free( field_value_temp );
        throw;
    }

}


// return the option from the stmt_opts array that matches the key.  If no option found,
// NULL is returned.

stmt_option const* get_stmt_option( sqlsrv_conn const* conn, _In_ zend_ulong key, _In_ const stmt_option stmt_opts[] TSRMLS_DC )
{
    for( int i = 0; stmt_opts[ i ].key != SQLSRV_STMT_OPTION_INVALID; ++i ) {

        // if we find the key we're looking for, return it
        if( key == stmt_opts[ i ].key ) {
            return &stmt_opts[ i ];
        }
    }

    return NULL;    // no option found
}

// is_fixed_size_type
// returns true if the SQL data type is a fixed length, as opposed to a variable length data type such as varchar or varbinary

bool is_fixed_size_type( _In_ SQLINTEGER sql_type )
{
    switch( sql_type ) {

        case SQL_BINARY:
        case SQL_CHAR:
        case SQL_WCHAR:
        case SQL_VARCHAR:
        case SQL_WVARCHAR:
        case SQL_LONGVARCHAR:
        case SQL_WLONGVARCHAR:
        case SQL_VARBINARY:
        case SQL_LONGVARBINARY:
        case SQL_SS_XML:
        case SQL_SS_UDT:
            return false;
    }

    return true;
}

bool is_valid_sqlsrv_phptype( _In_ sqlsrv_phptype type )
{
    switch( type.typeinfo.type ) {

        case SQLSRV_PHPTYPE_NULL:
        case SQLSRV_PHPTYPE_INT:
        case SQLSRV_PHPTYPE_FLOAT:
        case SQLSRV_PHPTYPE_DATETIME:
            return true;
        case SQLSRV_PHPTYPE_STRING:
        case SQLSRV_PHPTYPE_STREAM:
        {
            if( type.typeinfo.encoding == SQLSRV_ENCODING_BINARY || type.typeinfo.encoding == SQLSRV_ENCODING_CHAR
                || type.typeinfo.encoding == CP_UTF8 || type.typeinfo.encoding == SQLSRV_ENCODING_DEFAULT ) {
                return true;
            }
            break;
        }
    }

    return false;
}


// verify there is enough space to hold the output string parameter, and allocate it if needed.  The param_z
// is updated to have the new buffer with the correct size and its reference is incremented.  The output
// string is place in the stmt->output_params.  param_z is modified to hold the new buffer, and buffer, buffer_len and
// stmt->param_ind_ptrs are modified to hold the correct values for SQLBindParameter

void resize_output_buffer_if_necessary( _Inout_ sqlsrv_stmt* stmt, _Inout_ zval* param_z, _In_ SQLULEN paramno, SQLSRV_ENCODING encoding,
                                        _In_ SQLSMALLINT c_type, _In_ SQLSMALLINT sql_type, _In_ SQLULEN column_size, _In_ SQLSMALLINT decimal_digits,
                                        _Out_writes_(buffer_len) SQLPOINTER& buffer, _Out_ SQLLEN& buffer_len TSRMLS_DC )
{
    SQLSRV_ASSERT( column_size != SQLSRV_UNKNOWN_SIZE, "column size should be set to a known value." );
    buffer_len = Z_STRLEN_P( param_z );
    SQLLEN expected_len;
    SQLLEN buffer_null_extra;
    SQLLEN elem_size;
    SQLLEN without_null_len;

    // calculate the size of each 'element' represented by column_size.  WCHAR is of course 2,
    // as is a n(var)char/ntext field being returned as a binary field.
    elem_size = (c_type == SQL_C_WCHAR || (c_type == SQL_C_BINARY && (sql_type == SQL_WCHAR || sql_type == SQL_WVARCHAR || sql_type == SQL_WLONGVARCHAR ))) ? 2 : 1;

    // account for the NULL terminator returned by ODBC and needed by Zend to avoid a "String not null terminated" debug warning
    SQLULEN field_size = column_size;
    // with AE on, when column_size is retrieved from SQLDescribeParam, column_size 
    // does not include the negative sign or decimal place for numeric values
    // VSO Bug 2913: without AE, the same can happen as well, in particular to decimals 
    // and numerics with precision/scale specified
    if (sql_type == SQL_DECIMAL || sql_type == SQL_NUMERIC || sql_type == SQL_BIGINT || sql_type == SQL_INTEGER || sql_type == SQL_SMALLINT) {
        // include the possible negative sign
        field_size += elem_size;
        // include the decimal for output params by adding elem_size
        if (decimal_digits > 0) {
            field_size += elem_size;
        }
    }
    if (column_size == SQL_SS_LENGTH_UNLIMITED) {
        field_size = SQL_SERVER_MAX_FIELD_SIZE / elem_size;
    }
    expected_len = field_size * elem_size + elem_size;

    // binary fields aren't null terminated, so we need to account for that in our buffer length calcuations
    buffer_null_extra = (c_type == SQL_C_BINARY) ? elem_size : 0;

    // this is the size of the string for Zend and for the StrLen parameter to SQLBindParameter
    without_null_len = field_size * elem_size;

    // increment to include the null terminator since the Zend length doesn't include the null terminator
    buffer_len += elem_size;

    // if the current buffer size is smaller than the necessary size, resize the buffer and set the zval to the new
    // length.
    if( buffer_len < expected_len ) {
        SQLSRV_ASSERT( expected_len >= expected_len - buffer_null_extra,
                       "Integer overflow/underflow caused a corrupt field length." );

        // allocate enough space to ALWAYS include the NULL regardless of the type being retrieved since
        // we set the last byte(s) to be NULL to avoid the debug build warning from the Zend engine about
        // not having a NULL terminator on a string.
		zend_string* param_z_string = zend_string_realloc( Z_STR_P(param_z), expected_len, 0 );

        // A zval string len doesn't include the null.  This calculates the length it should be
        // regardless of whether the ODBC type contains the NULL or not.

        // null terminate the string to avoid a warning in debug PHP builds
		ZSTR_VAL(param_z_string)[without_null_len] = '\0';
		ZVAL_NEW_STR(param_z, param_z_string);

		// buffer_len is the length passed to SQLBindParameter.  It must contain the space for NULL in the
		// buffer when retrieving anything but SQLSRV_ENC_BINARY/SQL_C_BINARY
		buffer_len = Z_STRLEN_P(param_z) - buffer_null_extra;

		// Zend string length doesn't include the null terminator
		ZSTR_LEN(Z_STR_P(param_z)) -= elem_size;
    }

	buffer = Z_STRVAL_P(param_z);

    // The StrLen_Ind_Ptr parameter of SQLBindParameter should contain the length of the data to send, which
    // may be less than the size of the buffer since the output may be more than the input.  If it is greater,
    // than the error 22001 is returned by ODBC.
    if( stmt->param_ind_ptrs[ paramno ] > buffer_len - (elem_size - buffer_null_extra)) {
        stmt->param_ind_ptrs[ paramno ] = buffer_len - (elem_size - buffer_null_extra);
    }
}

void adjustInputPrecision( _Inout_ zval* param_z, _In_ SQLSMALLINT decimal_digits ) {
    // 38 is the maximum length of a stringified decimal number
    size_t maxDecimalPrecision = 38;
    // 6 is derived from: 1 for '.'; 1 for sign of the number; 1 for 'e' or 'E' (scientific notation);
    //                    1 for sign of scientific exponent; 2 for length of scientific exponent
    // if the length is greater than maxDecimalStrLen, do not change the string
    size_t maxDecimalStrLen = maxDecimalPrecision + 6;
    if (Z_STRLEN_P(param_z) > maxDecimalStrLen) {
        return;
    }
    std::vector<size_t> digits;
    unsigned char* ptr = reinterpret_cast<unsigned char*>(ZSTR_VAL( Z_STR_P( param_z )));
    bool isNeg = false;
    bool isScientificNot = false;
    char scientificChar = ' ';
    short scientificExp = 0;
    if( strchr( reinterpret_cast<char*>( ptr ), 'e' ) || strchr( reinterpret_cast<char*>( ptr ), 'E' )){
        isScientificNot = true;
    }
    // parse digits in param_z into the vector digits
    if( *ptr == '+' || *ptr == '-' ){
        if( *ptr == '-' ){
            isNeg = true;
        }
        ptr++;
    }
    short numInt = 0;
    short numDec = 0;
    while( isdigit( *ptr )){
        digits.push_back( *ptr - '0' );
        ptr++;
        numInt++;
    }
    if( *ptr == '.' ){
        ptr++;
        if( !isScientificNot ){
            while( isdigit( *ptr ) && numDec < decimal_digits + 1 ){
                digits.push_back( *ptr - '0' );
                ptr++;
                numDec++;
            }
            // make sure the rest of the number are digits
            while( isdigit( *ptr )){
                ptr++;
            }
        }
        else {
            while( isdigit( *ptr )){
                digits.push_back( *ptr - '0' );
                ptr++;
                numDec++;
            }
        }
    }
    if( isScientificNot ){
        if ( *ptr == 'e' || *ptr == 'E' ) {
            scientificChar = *ptr;
        }
        ptr++;
        bool isNegExp = false;
        if( *ptr == '+' || *ptr == '-' ){
            if( *ptr == '-' ){
                isNegExp = true;
            }
            ptr++;
        }
        while( isdigit( *ptr )){
            scientificExp = scientificExp * 10 + ( *ptr - '0' );
            ptr++;
        }
        SQLSRV_ASSERT( scientificExp <= maxDecimalPrecision, "Input decimal overflow: sql decimal type only supports up to a precision of 38." );
        if( isNegExp ){
            scientificExp = scientificExp * -1;
        }
    }
    // if ptr is not pointing to a null terminator at this point, that means the decimal string input is invalid
    // do not change the string and let SQL Server handle the invalid decimal string
    if ( *ptr != '\0' ) {
        return;
    }
    // if number of decimal is less than the exponent, that means the number is a whole number, so no need to adjust the precision
    if( numDec > scientificExp ){
        int decToRemove = numDec - scientificExp - decimal_digits;
        if( decToRemove > 0 ){
            bool carryOver = false;
            short backInd = 0;
            // pop digits from the vector until there is only 1 more decimal place than required decimal_digits
            while( decToRemove != 1 && !digits.empty() ){
                digits.pop_back();
                decToRemove--;
            }
            if( !digits.empty() ){
                // check if the last digit to be popped is greater than 5, if so, the digit before it needs to round up
                carryOver = digits.back() >= 5;
                digits.pop_back();
                backInd = static_cast<short>(digits.size() - 1);
                // round up from the end until no more carry over
                while( carryOver && backInd >= 0 ){
                    if( digits.at( backInd ) != 9 ){
                        digits.at( backInd )++;
                        carryOver = false;
                    }
                    else{
                        digits.at( backInd ) = 0;
                    }
                    backInd--;
                }
            }
            std::ostringstream oss;
            if( isNeg ){
                oss << '-';
            }
            // insert 1 if carry over persist all the way to the beginning of the number
            if( carryOver && backInd == -1 ){
                oss << 1;
            }
            if( digits.empty() && !carryOver ){
                oss << 0;
            }
            else{
                short i = 0;
                for( i; i < numInt && i < digits.size(); i++ ){
                    oss << digits[i];
                }
                // fill string with 0 if the number of digits in digits is less then numInt
                if( i < numInt ){
                    for( i; i < numInt; i++ ){
                        oss << 0;
                    }
                }
                if( numInt < digits.size() ){
                    oss << '.';
                    for( i; i < digits.size(); i++ ){
                        oss << digits[i];
                    }
                }
                if( scientificExp != 0 ){
                    oss << scientificChar << std::to_string( scientificExp );
                }
            }
            std::string str = oss.str();
            zend_string* zstr = zend_string_init( str.c_str(), str.length(), 0 );
            zend_string_release( Z_STR_P( param_z ));
            ZVAL_NEW_STR( param_z, zstr );
        }
    }
}

// output parameters have their reference count incremented so that they do not disappear
// while the query is executed and processed.  They are saved in the statement so that
// their reference count may be decremented later (after results are processed)

void save_output_param_for_later( _Inout_ sqlsrv_stmt* stmt, _Inout_ sqlsrv_output_param& param TSRMLS_DC )
{
    HashTable* param_ht = Z_ARRVAL( stmt->output_params );
    zend_ulong paramno = static_cast<zend_ulong>( param.param_num );
    core::sqlsrv_zend_hash_index_update_mem(*stmt, param_ht, paramno, &param, sizeof( sqlsrv_output_param ));
    Z_TRY_ADDREF_P( param.param_z );   // we have a reference to the param
}


// send all the stream data

void send_param_streams( _Inout_ sqlsrv_stmt* stmt TSRMLS_DC )
{
    while( core_sqlsrv_send_stream_packet( stmt TSRMLS_CC )) { }
}


// called by Zend for each parameter in the sqlsrv_stmt::output_params hash table when it is cleaned/destroyed
void sqlsrv_output_param_dtor( _Inout_ zval* data )
{
    sqlsrv_output_param *output_param = static_cast<sqlsrv_output_param*>( Z_PTR_P( data ));
    zval_ptr_dtor( output_param->param_z ); // undo the reference to the string we will no longer hold
	sqlsrv_free( output_param );
}

// called by Zend for each stream in the sqlsrv_stmt::param_streams hash table when it is cleaned/destroyed
void sqlsrv_stream_dtor( _Inout_ zval* data )
{
    sqlsrv_stream* stream_encoding = static_cast<sqlsrv_stream*>( Z_PTR_P( data ));
    zval_ptr_dtor( stream_encoding->stream_z ); // undo the reference to the stream we will no longer hold
	sqlsrv_free( stream_encoding );
}

}

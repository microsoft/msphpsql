//---------------------------------------------------------------------------------------------------------------------------------
// File: core_stmt.cpp
//
// Contents: Core routines that use statement handles shared between sqlsrv and pdo_sqlsrv
//
// Microsoft Drivers 5.10 for PHP for SQL Server
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
        // field_len may be equal to SQL_NULL_DATA even when field_value is not null
        if( field_value != NULL && field_len != SQL_NULL_DATA) {
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

const char  DECIMAL_POINT = '.';
const int   SQL_SERVER_DECIMAL_MAXIMUM_PRECISION = 38;            // 38 is the maximum length of a stringified decimal number

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
// Only declarations are put here. Functions contain more explanations they need in their definitions
void calc_string_size( _Inout_ sqlsrv_stmt* stmt, _In_ SQLUSMALLINT field_index, _In_ SQLLEN sql_type, _Inout_ SQLLEN& size );
size_t calc_utf8_missing( _Inout_ sqlsrv_stmt* stmt, _In_reads_(buffer_end) const char* buffer, _In_ size_t buffer_end );
void core_get_field_common(_Inout_ sqlsrv_stmt* stmt, _In_ SQLUSMALLINT field_index, _Inout_ sqlsrv_phptype
                           sqlsrv_php_type, _Inout_updates_bytes_(*field_len) void*& field_value, _Inout_ SQLLEN* field_len);
void col_cache_dtor( _Inout_ zval* data_z );
void field_cache_dtor( _Inout_ zval* data_z );
int round_up_decimal_numbers(_Inout_ char* buffer, _In_ int decimal_pos, _In_ int decimals_places, _In_ int offset, _In_ int lastpos);
void format_decimal_numbers(_In_ SQLSMALLINT decimals_places, _In_ SQLSMALLINT field_scale, _Inout_updates_bytes_(*field_len) char*& field_value, _Inout_ SQLLEN* field_len);
void get_field_as_string( _Inout_ sqlsrv_stmt* stmt, _In_ SQLUSMALLINT field_index, _Inout_ sqlsrv_phptype sqlsrv_php_type,
                          _Inout_updates_bytes_(*field_len) void*& field_value, _Inout_ SQLLEN* field_len );
stmt_option const* get_stmt_option( sqlsrv_conn const* conn, _In_ zend_ulong key, _In_ const stmt_option stmt_opts[] );
bool is_valid_sqlsrv_phptype( _In_ sqlsrv_phptype type );
void adjustDecimalPrecision(_Inout_ zval* param_z, _In_ SQLSMALLINT decimal_digits);
bool is_a_numeric_type(_In_ SQLSMALLINT sql_type);
bool is_a_string_type(_In_ SQLSMALLINT sql_type);
}

// constructor for sqlsrv_stmt.  Here so that we can use functions declared earlier.
sqlsrv_stmt::sqlsrv_stmt( _In_ sqlsrv_conn* c, _In_ SQLHANDLE handle, _In_ error_callback e, _In_opt_ void* drv ) :
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
    column_count( ACTIVE_NUM_COLS_INVALID ),
    row_count( ACTIVE_NUM_ROWS_INVALID ),
    query_timeout( QUERY_TIMEOUT_INVALID ),
    date_as_string(false),
    format_decimals(false),       // no formatting needed
    decimal_places(NO_CHANGE_DECIMAL_PLACES),     // the default is no formatting to resultset required
    data_classification(false),
    buffered_query_limit( sqlsrv_buffered_result_set::BUFFERED_QUERY_LIMIT_INVALID ),
    send_streams_at_exec( true )
{
    ZVAL_UNDEF( &active_stream );

    // initialize the col cache
    array_init(&col_cache);
    core::sqlsrv_zend_hash_init( *conn, Z_ARRVAL(col_cache), 5 /* # of buckets */, col_cache_dtor, 0 /*persistent*/ );

    // initialize the field cache
    array_init(&field_cache);
    core::sqlsrv_zend_hash_init(*conn, Z_ARRVAL(field_cache), 5 /* # of buckets */, field_cache_dtor, 0 /*persistent*/);
}

// desctructor for sqlsrv statement.
sqlsrv_stmt::~sqlsrv_stmt( void )
{
    if( Z_TYPE( active_stream ) != IS_UNDEF ) {
        close_active_stream( this );
    }

    // delete any current results
    if( current_results ) {
        current_results->~sqlsrv_result_set();
        efree( current_results );
        current_results = NULL;
    }

    // delete sensivity data
    clean_up_sensitivity_metadata();

    // clean up metadata 
    clean_up_results_metadata();

    invalidate();
    zval_ptr_dtor( &col_cache );
    zval_ptr_dtor( &field_cache );
}


// centralized place to release (without destroying the hash tables
// themselves) all the parameter data that accrues during the
// execution phase.
void sqlsrv_stmt::free_param_data( void )
{
    params_container.clean_up_param_data();

    zend_hash_clean( Z_ARRVAL( col_cache ));
    zend_hash_clean( Z_ARRVAL( field_cache ));
}


// to be called whenever a new result set is created, such as after an
// execute or next_result.  Resets the state variables.

void sqlsrv_stmt::new_result_set( void )
{
    this->fetch_called = false;
    this->has_rows = false;
    this->past_next_result_end = false;
    this->past_fetch_end = false;
    this->last_field_index = -1;
    this->column_count = ACTIVE_NUM_COLS_INVALID;
    this->row_count = ACTIVE_NUM_ROWS_INVALID;

    // delete any current results
    if( current_results ) {
        current_results->~sqlsrv_result_set();
        efree( current_results );
        current_results = NULL;
    }

    // delete sensivity data
    clean_up_sensitivity_metadata();

    // reset sqlsrv php type in meta data
    size_t num_fields = this->current_meta_data.size();
    for (size_t f = 0; f < num_fields; f++) {
        this->current_meta_data[f]->reset_php_type();
    }

    // create a new result set
    if( cursor_type == SQLSRV_CURSOR_BUFFERED ) {
         sqlsrv_malloc_auto_ptr<sqlsrv_buffered_result_set> result;
        result = reinterpret_cast<sqlsrv_buffered_result_set*> ( sqlsrv_malloc( sizeof( sqlsrv_buffered_result_set ) ) );
        new ( result.get() ) sqlsrv_buffered_result_set( this );
        current_results = result.get();
        result.transferred();
    }
    else {
        current_results = new (sqlsrv_malloc( sizeof( sqlsrv_odbc_result_set ))) sqlsrv_odbc_result_set( this );
    }
}

// free sensitivity classification metadata
void sqlsrv_stmt::clean_up_sensitivity_metadata()
{
    if (current_sensitivity_metadata) {
        current_sensitivity_metadata->~sensitivity_metadata();
        current_sensitivity_metadata.reset();
    }
}

// internal helper function to free meta data structures allocated
void meta_data_free(_Inout_ field_meta_data* meta)
{
    meta->field_name.reset();
    sqlsrv_free(meta);
}

void sqlsrv_stmt::clean_up_results_metadata()
{
    std::for_each(current_meta_data.begin(), current_meta_data.end(), meta_data_free);
    current_meta_data.clear();

    column_count = ACTIVE_NUM_COLS_INVALID;
    row_count = ACTIVE_NUM_ROWS_INVALID;
}

void sqlsrv_stmt::set_query_timeout()
{
    if (query_timeout == QUERY_TIMEOUT_INVALID || query_timeout < 0) {
        return;
    }

    core::SQLSetStmtAttr(this, SQL_ATTR_QUERY_TIMEOUT, reinterpret_cast<SQLPOINTER>((SQLLEN)query_timeout), SQL_IS_UINTEGER);
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
                                      _In_opt_ const stmt_option valid_stmt_opts[], _In_ error_callback const err, _In_opt_ void* driver )
{
    sqlsrv_malloc_auto_ptr<sqlsrv_stmt> stmt;
    SQLHANDLE stmt_h = SQL_NULL_HANDLE;
    sqlsrv_stmt* return_stmt = NULL;

    try {

        core::SQLAllocHandle( SQL_HANDLE_STMT, *conn, &stmt_h );

        stmt = stmt_factory( conn, stmt_h, err, driver );

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

                const stmt_option* stmt_opt = get_stmt_option( stmt->conn, index, valid_stmt_opts );

                // if the key didn't match, then return the error to the script.
                // The driver layer should ensure that the key is valid.
                DEBUG_SQLSRV_ASSERT( stmt_opt != NULL, "allocate_stmt: unexpected null value for statement option." );

                // perform the actions the statement option needs done.
                (*stmt_opt->func)( stmt, stmt_opt, value_z );
            } ZEND_HASH_FOREACH_END();
        }

        // The query timeout setting is inherited from the corresponding connection attribute, but
        // the user may override that the query timeout setting using the statement option.
        // In any case, set query timeout using the latest value
        stmt->set_query_timeout();

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
                             _Inout_ SQLSMALLINT decimal_digits)
{
    // check is only < because params are 0 based
    CHECK_CUSTOM_ERROR(param_num >= SQL_SERVER_MAX_PARAMS, stmt, SQLSRV_ERROR_MAX_PARAMS_EXCEEDED, param_num + 1) {
        throw core::CoreException();
    }

    // Dereference the parameter if necessary
    zval* param_ref = param_z;
    if (Z_ISREF_P(param_z)) {
        ZVAL_DEREF(param_z);
    }

    sqlsrv_param* param_ptr = stmt->params_container.find_param(param_num, (direction == SQL_PARAM_INPUT));
    try {
        if (param_ptr == NULL) {
            sqlsrv_malloc_auto_ptr<sqlsrv_param> new_param;
            if (direction == SQL_PARAM_INPUT) {
                // Check if it's a Table-Valued Parameter first
                if (Z_TYPE_P(param_z) == IS_ARRAY) {
                    new_param = new (sqlsrv_malloc(sizeof(sqlsrv_param_tvp))) sqlsrv_param_tvp(param_num, encoding, SQL_SS_TABLE, 0, 0, NULL);
                } else {
                    new_param = new (sqlsrv_malloc(sizeof(sqlsrv_param))) sqlsrv_param(param_num, direction, encoding, sql_type, column_size, decimal_digits);
                }
            } else if (direction == SQL_PARAM_OUTPUT || direction == SQL_PARAM_INPUT_OUTPUT) {
                new_param = new (sqlsrv_malloc(sizeof(sqlsrv_param_inout))) sqlsrv_param_inout(param_num, direction, encoding, sql_type, column_size, decimal_digits, php_out_type);
            } else {
                SQLSRV_ASSERT(false, "sqlsrv_params_container::insert_param - Invalid parameter direction.");
            }
            stmt->params_container.insert_param(param_num, new_param);
            param_ptr = new_param;
            new_param.transferred();
        } else if (direction == SQL_PARAM_INPUT 
                && param_ptr->sql_data_type != SQL_SS_TABLE
                && param_ptr->strlen_or_indptr == SQL_NULL_DATA) {
            // reset the followings for regular input parameters if it was bound as a null param before
            param_ptr->sql_data_type = sql_type;
            param_ptr->column_size = column_size;
            param_ptr->strlen_or_indptr = 0;
        }

        SQLSRV_ASSERT(param_ptr != NULL, "core_sqlsrv_bind_param: param_ptr is null. Something went wrong.");

        bool result = param_ptr->prepare_param(param_ref, param_z);
        if (!result && direction == SQL_PARAM_INPUT_OUTPUT) {
            CHECK_CUSTOM_ERROR(!result, stmt, SQLSRV_ERROR_INPUT_OUTPUT_PARAM_TYPE_MATCH, param_num + 1) {
                throw core::CoreException();
            }
        }

        // If Always Encrypted is enabled, transfer the known param meta data if applicable, which might alter param_z for decimal types
        if (stmt->conn->ce_option.enabled) {
            if (param_ptr->sql_data_type == SQL_UNKNOWN_TYPE || param_ptr->column_size == SQLSRV_UNKNOWN_SIZE) {
                // meta data parameters are always sorted based on parameter number
                param_ptr->copy_param_meta_ae(param_z, stmt->params_container.params_meta_ae[param_num]);
            }
        }

        // Get all necessary values to prepare for SQLBindParameter
        param_ptr->process_param(stmt, param_z);
        param_ptr->bind_param(stmt);

        // When calling SQLDescribeParam() on a parameter targeting a Datetime column, the return values for ParameterType, ColumnSize and DecimalDigits are SQL_TYPE_TIMESTAMP, 23, and 3 respectively.
        // For a parameter targeting a SmallDatetime column, the return values are SQL_TYPE_TIMESTAMP, 16, and 0. Inputting these values into SQLBindParameter() results in Operand type clash error.
        // This is because SQL_TYPE_TIMESTAMP corresponds to Datetime2 by default, and conversion of Datetime2 to Datetime and conversion of Datetime2 to SmallDatatime is not allowed with encrypted columns.
        // To fix the conversion problem, set the SQL_CA_SS_SERVER_TYPE field of the parameter to SQL_SS_TYPE_DATETIME and SQL_SS_TYPE_SMALLDATETIME respectively for a Datetime and Smalldatetime column.
        // Note this must be called after SQLBindParameter() or SQLSetDescField() may fail. 
        // VSO BUG 2693: how to correctly distinguish datetime from datetime2(3)? Both have the same decimal_digits and column_size
        if (stmt->conn->ce_option.enabled && param_ptr->sql_data_type == SQL_TYPE_TIMESTAMP) {
            if (param_ptr->decimal_digits == 3) {
                core::SQLSetDescField(stmt, param_num + 1, SQL_CA_SS_SERVER_TYPE, (SQLPOINTER)SQL_SS_TYPE_DATETIME, SQL_IS_INTEGER);
            } else if (param_ptr->decimal_digits == 0 && param_ptr->column_size == 16) {
                core::SQLSetDescField(stmt, param_num + 1, SQL_CA_SS_SERVER_TYPE, (SQLPOINTER)SQL_SS_TYPE_SMALLDATETIME, SQL_IS_INTEGER);
            }
        }
    }
    catch( core::CoreException& e ){
        stmt->free_param_data();
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

SQLRETURN core_sqlsrv_execute( _Inout_ sqlsrv_stmt* stmt, _In_reads_bytes_(sql_len) const char* sql, _In_ int sql_len )
{
    SQLRETURN r = SQL_ERROR;

    try {

    // close the stream to release the resource
    close_active_stream( stmt );

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
        r = core::SQLExecDirectW( stmt, wsql_string );
    }
    else {
        r = core::SQLExecute( stmt );
    }

    // if data is needed (streams were bound) and they should be sent at execute time, then do so now
    if( r == SQL_NEED_DATA && stmt->send_streams_at_exec ) {
        core_sqlsrv_send_stream_packet(stmt, true);
    }

    stmt->new_result_set();
    stmt->executed = true;

    // if all the data has been sent and no data was returned then finalize the output parameters
    if( stmt->send_streams_at_exec && ( r == SQL_NO_DATA || !core_sqlsrv_has_any_result( stmt ))) {
        stmt->params_container.finalize_output_parameters();
    }

    return r;
    }
    catch( core::CoreException& e ) {

        // if the statement executed but failed in a subsequent operation before returning,
        // we need to remove all the parameters and cancel the statement
        stmt->params_container.clean_up_param_data();
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

bool core_sqlsrv_fetch( _Inout_ sqlsrv_stmt* stmt, _In_ SQLSMALLINT fetch_orientation, _In_ SQLULEN fetch_offset )
{
    // pre-condition check
    SQLSRV_ASSERT( fetch_orientation >= SQL_FETCH_NEXT || fetch_orientation <= SQL_FETCH_RELATIVE,
                   "core_sqlsrv_fetch: Invalid value provided for fetch_orientation parameter." );

    try {
        // first check if the end of all results has been reached
        CHECK_CUSTOM_ERROR(stmt->past_next_result_end, stmt, SQLSRV_ERROR_NEXT_RESULT_PAST_END) {
            throw core::CoreException();
        }

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
            SQLSMALLINT has_fields;
            if (stmt->column_count != ACTIVE_NUM_COLS_INVALID) {
                has_fields = stmt->column_count;
            } else {
                has_fields = core::SQLNumResultCols( stmt );
                stmt->column_count = has_fields;
            }

            CHECK_CUSTOM_ERROR( has_fields == 0, stmt, SQLSRV_ERROR_NO_FIELDS ) {
                throw core::CoreException();
            }
        }

        // close the stream to release the resource
        close_active_stream( stmt );

        // if the statement has rows and is not scrollable but doesn't yet have
        // fetch_called, this must be the first time we've called sqlsrv_fetch.
        if( stmt->cursor_type == SQL_CURSOR_FORWARD_ONLY && stmt->has_rows && !stmt->fetch_called ) {
            stmt->fetch_called = true;
            return true;
        }

        // move to the record requested.  For absolute records, we use a 0 based offset, so +1 since
        // SQLFetchScroll uses a 1 based offset, otherwise for relative, just use the fetch_offset provided.
        SQLRETURN r = stmt->current_results->fetch( fetch_orientation, ( fetch_orientation == SQL_FETCH_RELATIVE ) ? fetch_offset : fetch_offset + 1 );

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

field_meta_data* core_sqlsrv_field_metadata( _Inout_ sqlsrv_stmt* stmt, _In_ SQLSMALLINT colno )
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
                               &( meta_data->field_is_nullable ) );
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

    if (meta_data->field_type == SQL_DECIMAL) {
        // Check if it is money type -- get the name of the data type
        char field_type_name[SS_MAXCOLNAMELEN] = {'\0'};
        SQLSMALLINT out_buff_len;
        SQLLEN not_used;
        core::SQLColAttribute(stmt, colno + 1, SQL_DESC_TYPE_NAME, field_type_name,
                              sizeof( field_type_name ), &out_buff_len, &not_used);

        if (!strcmp(field_type_name, "money") || !strcmp(field_type_name, "smallmoney")) {
            meta_data->field_is_money_type = true;
        }
    }

    // Set the field name length
    meta_data->field_name_len = static_cast<SQLSMALLINT>( field_name_len );

    field_meta_data* result_field_meta_data = meta_data;
    meta_data.transferred();
    return result_field_meta_data;
}

void core_sqlsrv_sensitivity_metadata( _Inout_ sqlsrv_stmt* stmt )
{
    sqlsrv_malloc_auto_ptr<unsigned char> dcbuf;
    DWORD dcVersion = 0;
    SQLINTEGER dclen = 0, dcIRD = 0;
    SQLINTEGER dclenout = 0;
    SQLHANDLE ird;
    SQLRETURN r;

    try {
        if (!stmt->data_classification) {
            return;
        }

        if (stmt->current_sensitivity_metadata) {
            // Already cached, so return
            return;
        }

        CHECK_CUSTOM_ERROR(!stmt->executed, stmt, SQLSRV_ERROR_DATA_CLASSIFICATION_PRE_EXECUTION) {
            throw core::CoreException();
        }

        // Reference: https://docs.microsoft.com/sql/connect/odbc/data-classification
        // To retrieve sensitivity classfication data, the first step is to retrieve the IRD(Implementation Row Descriptor) handle by
        // calling SQLGetStmtAttr with SQL_ATTR_IMP_ROW_DESC statement attribute
        r = ::SQLGetStmtAttr(stmt->handle(), SQL_ATTR_IMP_ROW_DESC, reinterpret_cast<SQLPOINTER*>(&ird), SQL_IS_POINTER, 0);
        CHECK_SQL_ERROR_OR_WARNING(r, stmt) {
            LOG(SEV_ERROR, "core_sqlsrv_sensitivity_metadata: failed in getting Implementation Row Descriptor handle." );
            throw core::CoreException();
        }

        // First call to get dclen
        r = ::SQLGetDescFieldW(ird, 0, SQL_CA_SS_DATA_CLASSIFICATION, reinterpret_cast<SQLPOINTER>(dcbuf.get()), 0, &dclen);
        if (r != SQL_SUCCESS || dclen == 0) {
            // log the error first
            LOG(SEV_ERROR, "core_sqlsrv_sensitivity_metadata: failed in calling SQLGetDescFieldW first time." );

            // If this fails, check if it is the "Invalid Descriptor Field error"
            SQLRETURN rc;
            SQLCHAR state[SQL_SQLSTATE_BUFSIZE] = {'\0'};
            SQLSMALLINT len;
            rc = ::SQLGetDiagField(SQL_HANDLE_DESC, ird, 1, SQL_DIAG_SQLSTATE, state, SQL_SQLSTATE_BUFSIZE, &len);

            CHECK_SQL_ERROR_OR_WARNING(rc, stmt) {
                throw core::CoreException();
            }

            CHECK_CUSTOM_ERROR(!strcmp("HY091", reinterpret_cast<char*>(state)), stmt, SQLSRV_ERROR_DATA_CLASSIFICATION_NOT_AVAILABLE) {
                throw core::CoreException();
            }

            CHECK_CUSTOM_ERROR(true, stmt, SQLSRV_ERROR_DATA_CLASSIFICATION_FAILED, "Check if ODBC driver or the server supports the Data Classification feature.") {
                throw core::CoreException();
            }
        }

        // Call again to read SQL_CA_SS_DATA_CLASSIFICATION data
        dcbuf = static_cast<unsigned char*>(sqlsrv_malloc(dclen * sizeof(char)));

        r = ::SQLGetDescFieldW(ird, 0, SQL_CA_SS_DATA_CLASSIFICATION, reinterpret_cast<SQLPOINTER>(dcbuf.get()), dclen, &dclenout);
        if (r != SQL_SUCCESS) {
            LOG(SEV_ERROR, "core_sqlsrv_sensitivity_metadata: failed in calling SQLGetDescFieldW again." );

            CHECK_CUSTOM_ERROR(true, stmt, SQLSRV_ERROR_DATA_CLASSIFICATION_FAILED, "SQLGetDescFieldW failed unexpectedly") {
                throw core::CoreException();
            }
        }

        // Start parsing the data (blob)
        using namespace data_classification;

        // If make it this far, must be using ODBC 17.2 or above. Prior to ODBC 17.4, checking Data Classification version will fail. 
        // When the function is successful and the version is right, rank info is available for retrieval
        bool getRankInfo = false;
        r = ::SQLGetDescFieldW(ird, 0, SQL_CA_SS_DATA_CLASSIFICATION_VERSION, reinterpret_cast<SQLPOINTER>(&dcVersion), SQL_IS_INTEGER, &dcIRD);
        if (r == SQL_SUCCESS && dcVersion >= VERSION_RANK_AVAILABLE) {
            getRankInfo = true;
        }

        // Start parsing the data (blob)
        unsigned char *dcptr = dcbuf;

        sqlsrv_malloc_auto_ptr<sensitivity_metadata> sensitivity_meta;
        sensitivity_meta = new (sqlsrv_malloc(sizeof(sensitivity_metadata))) sensitivity_metadata();

        // Parse the name id pairs for labels first then info types
        parse_sensitivity_name_id_pairs(stmt, sensitivity_meta->num_labels, &sensitivity_meta->labels, &dcptr);
        parse_sensitivity_name_id_pairs(stmt, sensitivity_meta->num_infotypes, &sensitivity_meta->infotypes, &dcptr);

        // Next parse the sensitivity properties
        parse_column_sensitivity_props(sensitivity_meta, &dcptr, getRankInfo);

        unsigned char *dcend = dcbuf;
        dcend += dclen;

        CHECK_CUSTOM_ERROR(dcptr != dcend, stmt, SQLSRV_ERROR_DATA_CLASSIFICATION_FAILED, "Metadata parsing ends unexpectedly") {
            throw core::CoreException();
        }

        stmt->current_sensitivity_metadata = sensitivity_meta;
        sensitivity_meta.transferred();
    } catch (core::CoreException& e) {
        throw e;
    }
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
                                _Out_ SQLSRV_PHPTYPE *sqlsrv_php_type_out)
{
    try {

        // close the stream to release the resource
        close_active_stream(stmt);

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
                    reinterpret_cast<char*>( field_value )[cached->len] = '\0';
                }
                *field_len = cached->len;
                if( sqlsrv_php_type_out) { *sqlsrv_php_type_out = static_cast<SQLSRV_PHPTYPE>(cached->type.typeinfo.type); }
            }
            return;
        }

        sqlsrv_phptype sqlsrv_php_type = sqlsrv_php_type_in;

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
               core_sqlsrv_get_field( stmt, i, invalid, prefer_string, field_value, field_len, cache_field, sqlsrv_php_type_out );
               // delete the value returned since we only want it cached, not the actual value
               if( field_value ) {
                   efree( field_value );
                   field_value = NULL;
                   *field_len = 0;
               }
            }
        }

        // If the php type was not specified set the php type to be the default type.
        if (sqlsrv_php_type.typeinfo.type == SQLSRV_PHPTYPE_INVALID) {
            SQLSRV_ASSERT(stmt->current_meta_data.size() > field_index, "core_sqlsrv_get_field - meta data vector not in sync" );

            // Get the corresponding php type from the sql type and then save the result for later
            if (stmt->current_meta_data[field_index]->sqlsrv_php_type.typeinfo.type == SQLSRV_PHPTYPE_INVALID) {
                SQLLEN sql_field_type = 0;
                SQLLEN sql_field_len = 0;

                sql_field_type = stmt->current_meta_data[field_index]->field_type;
                if (stmt->current_meta_data[field_index]->field_precision > 0) {
                    sql_field_len = stmt->current_meta_data[field_index]->field_precision;
                }
                else {
                    sql_field_len = stmt->current_meta_data[field_index]->field_size;
                }
                sqlsrv_php_type = stmt->sql_type_to_php_type(static_cast<SQLINTEGER>(sql_field_type), static_cast<SQLUINTEGER>(sql_field_len), prefer_string);
                stmt->current_meta_data[field_index]->sqlsrv_php_type = sqlsrv_php_type;
            }
            else {
                // use the previously saved php type
                sqlsrv_php_type = stmt->current_meta_data[field_index]->sqlsrv_php_type;
            }
        }

        // Verify that we have an acceptable type to convert.
        CHECK_CUSTOM_ERROR(!is_valid_sqlsrv_phptype(sqlsrv_php_type), stmt, SQLSRV_ERROR_INVALID_TYPE) {
            throw core::CoreException();
        }

        if( sqlsrv_php_type_out != NULL )
            *sqlsrv_php_type_out = static_cast<SQLSRV_PHPTYPE>( sqlsrv_php_type.typeinfo.type );

        // Retrieve the data
        core_get_field_common( stmt, field_index, sqlsrv_php_type, field_value, field_len );

        // if the user wants us to cache the field, we'll do it
        if( cache_field ) {
            field_cache cache( field_value, *field_len, sqlsrv_php_type );
            core::sqlsrv_zend_hash_index_update_mem( *stmt, Z_ARRVAL( stmt->field_cache ), field_index, &cache, sizeof(field_cache) );
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

bool core_sqlsrv_has_any_result( _Inout_ sqlsrv_stmt* stmt )
{
    SQLSMALLINT num_cols;
    SQLLEN rows_affected;

    if (stmt->column_count != ACTIVE_NUM_COLS_INVALID) {
        num_cols = stmt->column_count;
    }
    else {
        // Use SQLNumResultCols to determine if we have rows or not
        num_cols = core::SQLNumResultCols( stmt );
        stmt->column_count = num_cols;
    }

    if (stmt->row_count != ACTIVE_NUM_ROWS_INVALID) {
        rows_affected = stmt->row_count;
    }
    else {
        // Use SQLRowCount to determine if there is a rows status waiting
        rows_affected = core::SQLRowCount( stmt );
        stmt->row_count = rows_affected;
    }

    return (num_cols != 0) || (rows_affected > 0);
}

// core_sqlsrv_next_result
// Advances to the next result set from the last executed query
// Parameters
// stmt - the sqlsrv_stmt structure
// Returns
// Nothing, exception thrown if problem occurs

void core_sqlsrv_next_result( _Inout_ sqlsrv_stmt* stmt, _In_ bool finalize_output_params, _In_ bool throw_on_errors )
{
    try {

        // make sure that the statement has been executed.
        CHECK_CUSTOM_ERROR( !stmt->executed, stmt, SQLSRV_ERROR_STATEMENT_NOT_EXECUTED ) {
            throw core::CoreException();
        }

        CHECK_CUSTOM_ERROR( stmt->past_next_result_end, stmt, SQLSRV_ERROR_NEXT_RESULT_PAST_END ) {
            throw core::CoreException();
        }

        close_active_stream( stmt );

        //Clear column sql types and sql display sizes.
        zend_hash_clean( Z_ARRVAL( stmt->col_cache ));

        SQLRETURN r;
        if( throw_on_errors ) {
            r = core::SQLMoreResults( stmt );
        }
        else {
            r = SQLMoreResults( stmt->handle() );
        }

        if( r == SQL_NO_DATA ) {

            if( finalize_output_params ) {
                // if we're finished processing result sets, handle the output parameters
                stmt->params_container.finalize_output_parameters();
            }

            // mark we are past the end of all results
            stmt->past_next_result_end = true;
            return;
        }

        stmt->new_result_set();
    }
    catch( core::CoreException& e ) {

        SQLCancel( stmt->handle() );
        throw e;
    }
}

//Calls SQLSetStmtAttr to set a cursor.
void core_sqlsrv_set_scrollable( _Inout_ sqlsrv_stmt* stmt, _In_ unsigned long cursor_type )
{
    try {

        switch( cursor_type ) {

            case SQL_CURSOR_STATIC:
                core::SQLSetStmtAttr( stmt, SQL_ATTR_CURSOR_TYPE,
                                      reinterpret_cast<SQLPOINTER>( SQL_CURSOR_STATIC ), SQL_IS_UINTEGER );
                break;

            case SQL_CURSOR_DYNAMIC:
                core::SQLSetStmtAttr( stmt, SQL_ATTR_CURSOR_TYPE,
                                      reinterpret_cast<SQLPOINTER>( SQL_CURSOR_DYNAMIC ), SQL_IS_UINTEGER );
                break;

            case SQL_CURSOR_KEYSET_DRIVEN:
                core::SQLSetStmtAttr( stmt, SQL_ATTR_CURSOR_TYPE,
                                      reinterpret_cast<SQLPOINTER>( SQL_CURSOR_KEYSET_DRIVEN ), SQL_IS_UINTEGER );
                break;

            case SQL_CURSOR_FORWARD_ONLY:
                core::SQLSetStmtAttr( stmt, SQL_ATTR_CURSOR_TYPE,
                                      reinterpret_cast<SQLPOINTER>( SQL_CURSOR_FORWARD_ONLY ), SQL_IS_UINTEGER );
                break;

            case SQLSRV_CURSOR_BUFFERED:
                core::SQLSetStmtAttr( stmt, SQL_ATTR_CURSOR_TYPE,
                                      reinterpret_cast<SQLPOINTER>( SQL_CURSOR_FORWARD_ONLY ), SQL_IS_UINTEGER );
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

void core_sqlsrv_set_buffered_query_limit( _Inout_ sqlsrv_stmt* stmt, _In_ zval* value_z )
{
    if( Z_TYPE_P( value_z ) != IS_LONG ) {

        THROW_CORE_ERROR( stmt, SQLSRV_ERROR_INVALID_BUFFER_LIMIT );
    }

    core_sqlsrv_set_buffered_query_limit( stmt, Z_LVAL_P( value_z ) );
}

void core_sqlsrv_set_buffered_query_limit( _Inout_ sqlsrv_stmt* stmt, _In_ SQLLEN limit )
{
    if( limit <= 0 ) {

        THROW_CORE_ERROR( stmt, SQLSRV_ERROR_INVALID_BUFFER_LIMIT );
    }

    stmt->buffered_query_limit = limit;
}


// Extracts the long value and calls the core_sqlsrv_set_query_timeout
// which accepts timeout parameter as a long. If the zval is not of type long
// than throws error.
void core_sqlsrv_set_query_timeout( _Inout_ sqlsrv_stmt* stmt, _Inout_ zval* value_z )
{
    try {

        // validate the value
        if( Z_TYPE_P( value_z ) != IS_LONG || Z_LVAL_P( value_z ) < 0 ) {

            convert_to_string( value_z );
            THROW_CORE_ERROR( stmt, SQLSRV_ERROR_INVALID_QUERY_TIMEOUT_VALUE, Z_STRVAL_P( value_z ) );
        }

        // Save the query timeout setting for processing later
        stmt->query_timeout = static_cast<long>(Z_LVAL_P(value_z));
    }
    catch( core::CoreException& ) {
        throw;
    }
}

void core_sqlsrv_set_decimal_places(_Inout_ sqlsrv_stmt* stmt, _In_ zval* value_z)
{
    try {
        // first check if the input is an integer
        CHECK_CUSTOM_ERROR(Z_TYPE_P(value_z) != IS_LONG, stmt, SQLSRV_ERROR_INVALID_DECIMAL_PLACES) {
            throw core::CoreException();
        }

        zend_long decimal_places = Z_LVAL_P(value_z);
        if (decimal_places < 0 || decimal_places  > SQL_SERVER_MAX_MONEY_SCALE) {
            // ignore decimal_places because it is out of range
            decimal_places = NO_CHANGE_DECIMAL_PLACES;
        }

        stmt->decimal_places = static_cast<short>(decimal_places);
    }
    catch( core::CoreException& ) {
        throw;
    }
}

// core_sqlsrv_send_stream_packet
// send a single packet from a stream parameter to the database using
// ODBC.  This will also handle the transition between parameters.  It
// returns true if it is not done sending, false if it is finished.
// return_value is what should be returned to the script if it is
// given.  Any errors that occur will be thrown.
// Parameters:
// stmt - query to send the next packet for
// get_all - send stream data all at once (false by default)
// Returns:
// true if more data remains to be sent, false if all data processed

bool core_sqlsrv_send_stream_packet( _Inout_ sqlsrv_stmt* stmt, _In_opt_ bool get_all /*= false*/)
{
    bool bMore = false;

    try {
        if (get_all) {
            // send stream data all at once (so no more after this)
            stmt->params_container.send_all_packets(stmt);
        } else {
            bMore = stmt->params_container.send_next_packet(stmt);
        }

        if (!bMore) {
            // All resources parameters are sent, so it's time to clean up
            stmt->params_container.clean_up_param_data(true);
        }
    } catch (core::CoreException& e) {
        stmt->free_param_data();
        SQLFreeStmt(stmt->handle(), SQL_RESET_PARAMS);
        SQLCancel(stmt->handle());
        throw e;
    }

    return bMore;
}

void stmt_option_functor::operator()( _Inout_ sqlsrv_stmt* /*stmt*/, stmt_option const* /*opt*/, _In_ zval* /*value_z*/ )
{
    // This implementation should never get called.
    DIE( "Not implemented." );
}

void stmt_option_query_timeout:: operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /**/, _In_ zval* value_z )
{
    core_sqlsrv_set_query_timeout( stmt, value_z );
}

void stmt_option_send_at_exec:: operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /*opt*/, _In_ zval* value_z )
{
    // zend_is_true does not fail. It either returns true or false.
    stmt->send_streams_at_exec = (zend_is_true(value_z));
}

void stmt_option_buffered_query_limit:: operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /*opt*/, _In_ zval* value_z )
{
    core_sqlsrv_set_buffered_query_limit( stmt, value_z );
}

void stmt_option_date_as_string:: operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /**/, _In_ zval* value_z )
{
    stmt->date_as_string = zend_is_true(value_z);
}

void stmt_option_format_decimals:: operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /**/, _In_ zval* value_z )
{
    stmt->format_decimals = zend_is_true(value_z);
}

void stmt_option_decimal_places:: operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /**/, _In_ zval* value_z )
{
    core_sqlsrv_set_decimal_places(stmt, value_z);
}

void stmt_option_data_classification:: operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /**/, _In_ zval* value_z )
{
    stmt->data_classification = zend_is_true(value_z);
}

// internal function to release the active stream.  Called by each main API function
// that will alter the statement and cancel any retrieval of data from a stream.
void close_active_stream( _Inout_ sqlsrv_stmt* stmt )
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

bool is_streamable_type( _In_ SQLSMALLINT sql_type )
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

bool is_a_numeric_type(_In_ SQLSMALLINT sql_type)
{
    switch (sql_type) {
        case SQL_BIGINT:
        case SQL_BIT:
        case SQL_INTEGER:
        case SQL_SMALLINT:
        case SQL_TINYINT:
        case SQL_FLOAT:
        case SQL_DOUBLE:
        case SQL_REAL:
        case SQL_DECIMAL:
        case SQL_NUMERIC:
            return true;
    }

    return false;
}

bool is_a_string_type(_In_ SQLSMALLINT sql_type)
{
    switch (sql_type) {
    case SQL_BIGINT:
    case SQL_DECIMAL:
    case SQL_NUMERIC:
    case SQL_SS_VARIANT:
    case SQL_SS_UDT:
    case SQL_GUID:
    case SQL_SS_XML:
    case SQL_CHAR:
    case SQL_WCHAR:
    case SQL_VARCHAR:
    case SQL_WVARCHAR:
    case SQL_LONGVARCHAR:
    case SQL_WLONGVARCHAR:
        return true;
    }

    return false;
}

void calc_string_size( _Inout_ sqlsrv_stmt* stmt, _In_ SQLUSMALLINT field_index, _In_ SQLLEN sql_type,  _Inout_ SQLLEN& size )
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
                core::SQLColAttributeW( stmt, field_index + 1, SQL_DESC_DISPLAY_SIZE, NULL, 0, NULL, &size );
                break;
            }

            // for wide char types for which the size is known, return the octet length instead, since it will include the
            // the number of bytes necessary for the string, not just the characters
            case SQL_WCHAR:
            case SQL_WVARCHAR:
            {
                // unixODBC 2.3.1 requires wide calls to support pooling
                core::SQLColAttributeW( stmt, field_index + 1, SQL_DESC_OCTET_LENGTH, NULL, 0, NULL, &size );
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
size_t calc_utf8_missing( _Inout_ sqlsrv_stmt* stmt, _In_reads_(buffer_end) const char* buffer, _In_ size_t buffer_end )
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
                            sqlsrv_php_type, _Inout_updates_bytes_(*field_len) void*& field_value, _Inout_ SQLLEN* field_len )
{
    try {

        close_active_stream( stmt );

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
            sqlsrv_malloc_auto_ptr<SQLLEN> field_value_temp;
            field_value_temp = static_cast<SQLLEN*>( sqlsrv_malloc( sizeof( SQLLEN )));
            *field_value_temp = 0;

            SQLRETURN r = stmt->current_results->get_data( field_index + 1, SQL_C_LONG, field_value_temp, sizeof( SQLLEN ),
                                                           field_len, true /*handle_warning*/ );

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
            *field_value_temp = 0.0;

            SQLRETURN r = stmt->current_results->get_data( field_index + 1, SQL_C_DOUBLE, field_value_temp, sizeof( double ),
                                                           field_len, true /*handle_warning*/ );

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
            get_field_as_string( stmt, field_index, sqlsrv_php_type, field_value, field_len );
            break;
        }

        // Reference: https://docs.microsoft.com/sql/odbc/reference/appendixes/sql-to-c-timestamp
        // Retrieve the datetime data as a string, which may be cached for later use.
        // The string is converted to a DateTime object only when it is required to
        // be returned as a zval.
        case SQLSRV_PHPTYPE_DATETIME:
        {
            sqlsrv_malloc_auto_ptr<char> field_value_temp;
            SQLLEN field_len_temp = 0;

            field_value_temp = static_cast<char*>(sqlsrv_malloc(MAX_DATETIME_STRING_LEN));
            memset(field_value_temp, '\0', MAX_DATETIME_STRING_LEN);

            SQLRETURN r = stmt->current_results->get_data(field_index + 1, SQL_C_CHAR, field_value_temp, MAX_DATETIME_STRING_LEN, &field_len_temp, true);

            if (r == SQL_NO_DATA || field_len_temp == SQL_NULL_DATA) {
                field_value_temp.reset();
                field_len_temp = 0;
            }

            CHECK_CUSTOM_ERROR((r == SQL_NO_DATA), stmt, SQLSRV_ERROR_NO_DATA, field_index) {
                throw core::CoreException();
            }

            field_value = field_value_temp;
            field_value_temp.transferred();
            *field_len = field_len_temp;

            break;
        }

        // create a stream wrapper around the field and return that object to the PHP script.  calls to fread
        // on the stream will result in calls to SQLGetData.  This is handled in stream.cpp.  See that file
        // for how these fields are used.
        case SQLSRV_PHPTYPE_STREAM:
        {
            php_stream* stream = NULL;
            sqlsrv_stream* ss = NULL;
            SQLSMALLINT sql_type;

            SQLSRV_ASSERT(stmt->current_meta_data.size() > field_index, "core_get_field_common - meta data vector not in sync" );
            sql_type = stmt->current_meta_data[field_index]->field_type;

            CHECK_CUSTOM_ERROR( !is_streamable_type( sql_type ), stmt, SQLSRV_ERROR_STREAMABLE_TYPES_ONLY ) {
                throw core::CoreException();
            }

            // For a sqlsrv stream, only REPORT_ERRORS may be used. For "mode", the 'b' option 
            // is ignored on POSIX systems, which treat text and binary files the same. Yet, the
            // 'b' option might be important in other systems.
            // For details check https://www.php.net/manual/en/internals2.ze1.streams.php
            stream = php_stream_open_wrapper("sqlsrv://sqlncli10", "rb", REPORT_ERRORS, NULL);

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

// To be called for formatting decimal / numeric fetched values from finalize_output_parameters() and/or get_field_as_string()
void format_decimal_numbers(_In_ SQLSMALLINT decimals_places, _In_ SQLSMALLINT field_scale, _Inout_updates_bytes_(*field_len) char*& field_value, _Inout_ SQLLEN* field_len)
{
    // In SQL Server, the default maximum precision of numeric and decimal data types is 38
    //
    // Note: decimals_places is NO_CHANGE_DECIMAL_PLACES by default, which means no formatting on decimal data is necessary
    // This function assumes stmt->format_decimals is true, so it first checks if it is necessary to add the leading zero.
    //
    // Likewise, if decimals_places is larger than the field scale, decimals_places wil be ignored. This is to ensure the
    // number of decimals adheres to the column field scale. If smaller, the output value may be rounded up.
    //
    // Note: it's possible that the decimal data does not contain a decimal point because the field scale is 0.
    // Thus, first check if the decimal point exists. If not, no formatting necessary, regardless of
    // format_decimals and decimals_places
    //

    // Check if it's a negative number and if necessary to add the leading zero
    short is_negative = (*field_value == '-') ? 1 : 0;
    char *src = field_value + is_negative;
    bool add_leading_zero = false;

    // If the decimal point is not found, simply return
    char *pt = strchr(src, DECIMAL_POINT);
    if (pt == NULL) {
        return;
    }
    else if (pt == src) {
        add_leading_zero = true;
    }

    SQLSMALLINT scale = decimals_places;
    if (scale > field_scale) {
        scale = field_scale;
    }

    char buffer[50] = "  ";             // A buffer with TWO blank spaces, as leeway
    int offset = 1 + is_negative;       // for cases like 9.* to 10.* and the minus sign if needed
    int src_length = strnlen_s(src);

    if (add_leading_zero) {
        buffer[offset++] = '0';         // leading zero added
    }
    // Copy the original numerical value to the buffer
    memcpy_s(buffer + offset, src_length, src, src_length);

    int last_pos = src_length + offset;

    // If no need to adjust decimal places, skip formatting
    if (decimals_places != NO_CHANGE_DECIMAL_PLACES) {
        int num_decimals = src_length - (pt - src) - 1;

        if (num_decimals > scale) {
            last_pos = round_up_decimal_numbers(buffer, (pt - src) + offset, scale, offset, last_pos);
        }
    }  

    // Remove the extra white space if not used. For a negative number,
    // the first pos is always a space
    offset = is_negative;
    char *p = buffer + offset;
    while (*p++ == ' ') {
        offset++;
    }
    if (is_negative) {
        buffer[--offset] = '-';
    }
    
    int len = last_pos - offset;
    memcpy_s(field_value, len, buffer + offset, len);
    field_value[len] = '\0';
    *field_len = len;
}

void get_field_as_string(_Inout_ sqlsrv_stmt *stmt, _In_ SQLUSMALLINT field_index, _Inout_ sqlsrv_phptype sqlsrv_php_type,
                         _Inout_updates_bytes_(*field_len) void *&field_value, _Inout_ SQLLEN *field_len)
{
    SQLRETURN r;
    SQLSMALLINT c_type;
    SQLSMALLINT sql_field_type = 0;
    SQLSMALLINT extra = 0;
    SQLLEN field_len_temp = 0;
    SQLLEN sql_display_size = 0;
    char* field_value_temp = NULL;
    unsigned int initial_field_len = INITIAL_FIELD_STRING_LEN;

    try {

        DEBUG_SQLSRV_ASSERT( sqlsrv_php_type.typeinfo.type == SQLSRV_PHPTYPE_STRING,
                             "Type should be SQLSRV_PHPTYPE_STRING in get_field_as_string" );

        col_cache* cached = NULL;
        if ( NULL != ( cached = static_cast< col_cache* >( zend_hash_index_find_ptr( Z_ARRVAL( stmt->col_cache ), static_cast< zend_ulong >( field_index ))))) {
            sql_field_type = cached->sql_type;
            sql_display_size = cached->display_size;
        }
        else {
            SQLSRV_ASSERT(stmt->current_meta_data.size() > field_index, "get_field_as_string - meta data vector not in sync" );
            sql_field_type = stmt->current_meta_data[field_index]->field_type;

            // Calculate the field size.
            calc_string_size( stmt, field_index, sql_field_type, sql_display_size );

            col_cache cache( sql_field_type, sql_display_size );
            core::sqlsrv_zend_hash_index_update_mem( *stmt, Z_ARRVAL( stmt->col_cache ), field_index, &cache, sizeof( col_cache ) );
        }

        // Determine the correct encoding
        if( sqlsrv_php_type.typeinfo.encoding == SQLSRV_ENCODING_DEFAULT ) {
            sqlsrv_php_type.typeinfo.encoding = stmt->conn->encoding();
        }
        // Set the C type and account for null characters at the end of the data.
        if (sqlsrv_php_type.typeinfo.encoding == SQLSRV_ENCODING_BINARY) {
            c_type = SQL_C_BINARY;
            extra = 0;
        } else {
            c_type = SQL_C_CHAR;
            extra = sizeof(SQLCHAR);
            
            // For numbers, no need to convert
            if (sqlsrv_php_type.typeinfo.encoding == CP_UTF8 && !is_a_numeric_type(sql_field_type)) {
                c_type = SQL_C_WCHAR;
                extra = sizeof(SQLWCHAR);

                sql_display_size = (sql_display_size * sizeof(SQLWCHAR));
            }
        }

        // If this is a large type, then read the first chunk to get the actual length from SQLGetData
        // The user may use "SET TEXTSIZE" to specify the size of varchar(max), nvarchar(max), 
        // varbinary(max), text, ntext, and image data returned by a SELECT statement. 
        // For varbinary(max), varchar(max) and nvarchar(max), sql_display_size will be 0, regardless
        if (sql_display_size == 0 ||
            (sql_field_type == SQL_WLONGVARCHAR || sql_field_type == SQL_LONGVARCHAR || sql_field_type == SQL_LONGVARBINARY)) {

            field_len_temp = initial_field_len;
            field_value_temp = static_cast<char*>(sqlsrv_malloc(field_len_temp + extra + 1));
            r = stmt->current_results->get_data(field_index + 1, c_type, field_value_temp, (field_len_temp + extra), &field_len_temp, false /*handle_warning*/);
        } else {
            field_len_temp = sql_display_size;
            field_value_temp = static_cast<char*>(sqlsrv_malloc(sql_display_size + extra + 1));

            // get the data
            r = stmt->current_results->get_data(field_index + 1, c_type, field_value_temp, sql_display_size + extra, &field_len_temp, false /*handle_warning*/);
        }

        CHECK_CUSTOM_ERROR((r == SQL_NO_DATA), stmt, SQLSRV_ERROR_NO_DATA, field_index) {
            throw core::CoreException();
        }

        if (field_len_temp == SQL_NULL_DATA) {
            field_value = NULL;
            sqlsrv_free(field_value_temp);
            return;
        }

        if (r == SQL_SUCCESS_WITH_INFO) {
            SQLCHAR state[SQL_SQLSTATE_BUFSIZE] = { L'\0' };
            SQLSMALLINT len = 0;

            stmt->current_results->get_diag_field(1, SQL_DIAG_SQLSTATE, state, SQL_SQLSTATE_BUFSIZE, &len);
            if (is_truncated_warning(state)) {
                SQLLEN chunk_field_len = 0;

                // for XML (and possibly other conditions) the field length returned is not the real field length, so
                // in every pass, we double the allocation size to retrieve all the contents.
                if (field_len_temp == SQL_NO_TOTAL) {

                    // reset the field_len_temp
                    field_len_temp = initial_field_len;

                    do {
                        SQLLEN buffer_len = field_len_temp;
                        // Double the size.
                        field_len_temp *= 2;

                        field_value_temp = static_cast<char*>(sqlsrv_realloc(field_value_temp, field_len_temp + extra + 1));

                        field_len_temp -= buffer_len;

                        // Get the rest of the data
                        r = stmt->current_results->get_data(field_index + 1, c_type, field_value_temp + buffer_len,
                            field_len_temp + extra, &chunk_field_len, false /*handle_warning*/);
                        // the last packet will contain the actual amount retrieved, not SQL_NO_TOTAL
                        // so we calculate the actual length of the string with that.
                        if (chunk_field_len != SQL_NO_TOTAL)
                            field_len_temp += chunk_field_len;
                        else
                            field_len_temp += buffer_len;

                        if (r == SQL_SUCCESS_WITH_INFO) {
                            core::SQLGetDiagField(stmt, 1, SQL_DIAG_SQLSTATE, state, SQL_SQLSTATE_BUFSIZE, &len);
                        }
                    } while (r == SQL_SUCCESS_WITH_INFO && is_truncated_warning(state));
                } // if (field_len_temp == SQL_NO_TOTAL)
                else {
                    // The field length (or its estimate) is returned, thus no need to double the allocation size. 
                    // Allocate field_len_temp (which is the field length retrieved from the first SQLGetData) but with some padding
                    // because there is a chance that the estimated field_len_temp is not accurate enough
                    SQLLEN buffer_len = 50;
                    field_value_temp = static_cast<char*>(sqlsrv_realloc(field_value_temp, field_len_temp + buffer_len + 1));
                    field_len_temp -= initial_field_len;

                    // Get the rest of the data
                    r = stmt->current_results->get_data(field_index + 1, c_type, field_value_temp + initial_field_len,
                        field_len_temp + buffer_len, &chunk_field_len, false /*handle_warning*/);
                    field_len_temp = initial_field_len + chunk_field_len;

                    CHECK_SQL_ERROR_OR_WARNING(r, stmt) {
                        throw core::CoreException();
                    }

                    // Reallocate field_value_temp next
                    field_value_temp = static_cast<char*>(sqlsrv_realloc(field_value_temp, field_len_temp + extra + 1));
                }
            } // if (is_truncated_warning(state))
        } // if (r == SQL_SUCCESS_WITH_INFO)

        CHECK_SQL_ERROR_OR_WARNING(r, stmt) {
            throw core::CoreException();
        }

        if (c_type == SQL_C_WCHAR) {
            bool converted = convert_string_from_utf16_inplace(static_cast<SQLSRV_ENCODING>(sqlsrv_php_type.typeinfo.encoding),
                &field_value_temp, field_len_temp);

            CHECK_CUSTOM_ERROR(!converted, stmt, SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE, get_last_error_message()) {
                throw core::CoreException();
            }
        }

        if (stmt->format_decimals && (sql_field_type == SQL_DECIMAL || sql_field_type == SQL_NUMERIC)) {
            // number of decimal places only affect money / smallmoney fields
            SQLSMALLINT decimal_places = (stmt->current_meta_data[field_index]->field_is_money_type) ? stmt->decimal_places : NO_CHANGE_DECIMAL_PLACES;
            format_decimal_numbers(decimal_places, stmt->current_meta_data[field_index]->field_scale, field_value_temp, &field_len_temp);
        }

        // finalized the returned values and set field_len to 0 if field_len_temp is negative (which may happen with unixODBC connection pooling)
        field_value = field_value_temp;
        *field_len = (field_len_temp > 0) ? field_len_temp : 0;

        // prevent a warning in debug mode about strings not being NULL terminated.  Even though nulls are not necessary, the PHP
        // runtime checks to see if a string is null terminated and issues a warning about it if running in debug mode.
        // SQL_C_BINARY fields don't return a NULL terminator, so we allocate an extra byte on each field and add 1 to fill the null terminator
        if (field_len_temp > 0) {
            field_value_temp[field_len_temp] = '\0';
        }
    }
    catch (core::CoreException&) {
        field_value = NULL;
        *field_len = 0;
        sqlsrv_free(field_value_temp);
        throw;
    } catch (...) {
        field_value = NULL;
        *field_len = 0;
        sqlsrv_free(field_value_temp);
        throw;
    }
}

// return the option from the stmt_opts array that matches the key.  If no option found,
// NULL is returned.

stmt_option const* get_stmt_option( sqlsrv_conn const* conn, _In_ zend_ulong key, _In_ const stmt_option stmt_opts[] )
{
    for( int i = 0; stmt_opts[i].key != SQLSRV_STMT_OPTION_INVALID; ++i ) {

        // if we find the key we're looking for, return it
        if( key == stmt_opts[i].key ) {
            return &stmt_opts[i];
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
        case SQLSRV_PHPTYPE_TABLE:
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

void adjustDecimalPrecision(_Inout_ zval* param_z, _In_ SQLSMALLINT decimal_digits) 
{
    char* value = Z_STRVAL_P(param_z);
    int value_len = Z_STRLEN_P(param_z);
        
    // If the length is greater than maxDecimalStrLen, do not convert the string
    // 6 is derived from: 1 for the decimal point; 1 for sign of the number; 1 for 'e' or 'E' (scientific notation);
    //                    1 for sign of scientific exponent; 2 for length of scientific exponent
    const int MAX_DECIMAL_STRLEN = SQL_SERVER_DECIMAL_MAXIMUM_PRECISION + 6;
    if (value_len > MAX_DECIMAL_STRLEN) {
        return;
    }

    // If std::stold() succeeds, 'index' is the position of the first character after the numerical value
    long double d = 0;
    size_t index;
    try {
        d = std::stold(std::string(value), &index);
    }
    catch (const std::logic_error& ) {
        return;		// invalid input caused the conversion to throw an exception
    }
    if (index < value_len) {
        return;		// the input contains something else apart from the numerical value 
    }

    // Navigate to the first digit or the decimal point
    short is_negative = (d < 0) ? 1 : 0;
    char *src = value + is_negative;
    while (*src != DECIMAL_POINT && !isdigit(static_cast<unsigned int>(*src))) {
        src++;
    }

    // Check if the value is in scientific notation
    char *exp = strchr(src, 'E');
    if (exp == NULL) {
        exp = strchr(src, 'e');
    }

    // Find the decimal point
    char *pt = strchr(src, DECIMAL_POINT);

    char buffer[50] = "  ";             // A buffer with 2 blank spaces, as leeway
    int offset = 1 + is_negative;     // The position to start copying the original numerical value

    if (exp == NULL) {
		if (pt == NULL) {
			return;		// decimal point not found
		}

        int src_length = strnlen_s(src);
        int num_decimals = src_length - (pt - src) - 1;
		if (num_decimals <= decimal_digits) {
			return;     // no need to adjust number of decimals
		}

        memcpy_s(buffer + offset, src_length, src, src_length);
        round_up_decimal_numbers(buffer, (pt - src) + offset, decimal_digits, offset, src_length + offset);
    }
    else {
        int power = atoi(exp+1);
        if (abs(power) > SQL_SERVER_DECIMAL_MAXIMUM_PRECISION) {
            return;     // Out of range, so let the server handle this
        }

        int num_decimals = 0;
        if (power == 0) {
            // Simply chop off the exp part
            int length = (exp - src);
            memcpy_s(buffer + offset, length, src, length);

            if (pt != NULL) {
                // Adjust decimal places only if decimal point is found and number of decimals more than decimal_digits
                num_decimals = exp - pt - 1;
                if (num_decimals > decimal_digits) {
                    round_up_decimal_numbers(buffer, (pt - src) + offset, decimal_digits, offset, length + offset);
                }
            }            
        } else {
            int oldpos = 0;
            if (pt == NULL) {
                oldpos = exp - src;     // Decimal point not found, use the exp sign
            }
            else {
                oldpos = pt - src;
                num_decimals = exp - pt - 1;
                if (power > 0 && num_decimals <= power) {
                    return;             // The result will be a whole number, do nothing and return
                }
            }

            // Derive the new position for the decimal point in the buffer
            int newpos = oldpos + power;
            if (power > 0) {
                newpos = newpos + offset;
                if (num_decimals == 0) {
                    memset(buffer + offset + oldpos, '0', power);    // Fill parts of the buffer with zeroes first
                }
                else {
                    buffer[newpos] = DECIMAL_POINT;
                }
            }
            else {
                // The negative "power" part shows exactly how many places to move the decimal point.
                // Whether to pad zeroes depending on the original position of the decimal point pos.
                if (newpos <= 0) {
                    // If newpos is negative or zero, pad zeroes (size of '0.' + places to move) in the buffer
                    short numzeroes = 2 + abs(newpos);
                    memset(buffer + offset, '0', numzeroes);
                    newpos = offset + 1;                    // The new decimal position should be offset + '0'
                    buffer[newpos] = DECIMAL_POINT;			// Replace that '0' with the decimal point
                    offset = numzeroes + offset;            // Short offset now in the buffer
                }
                else {
                    newpos = newpos + offset;
                    buffer[newpos] = DECIMAL_POINT;
                }
            }

            // Start copying the content to the buffer until the exp sign or one more digit after decimal_digits 
            char *p = src;
            int idx = offset;
            int lastpos = newpos + decimal_digits + 1;
            while (p != exp && idx <= lastpos) {
                if (*p == DECIMAL_POINT) {
                    p++;
                    continue;
                }
                if (buffer[idx] == DECIMAL_POINT) {
                    idx++;
                }
                buffer[idx++] = *p;
                p++;
            }
            // Round up is required only when number of decimals is more than decimal_digits
            num_decimals = idx - newpos - 1;
            if (num_decimals > decimal_digits) {
                round_up_decimal_numbers(buffer, newpos, decimal_digits, offset, idx);
            }
        }      
    }

    // Set the minus sign if negative
    if (is_negative) {
        buffer[0] = '-';
    }

    zend_string* zstr = zend_string_init(buffer, strnlen_s(buffer), 0);
    zend_string_release(Z_STR_P(param_z));
    ZVAL_NEW_STR(param_z, zstr);
}

int round_up_decimal_numbers(_Inout_ char* buffer, _In_ int decimal_pos, _In_ int num_decimals, _In_ int offset, _In_ int lastpos)
{
    // This helper method assumes the 'buffer' has some extra blank spaces at the beginning without the minus '-' sign.
    // We want the rounding to be consistent with php number_format(), http://php.net/manual/en/function.number-format.php
    // as well as SQL Server Management studio, such that the least significant digit will be rounded up if it is
    // followed by 5 or above.

    int pos = decimal_pos + num_decimals + 1;
    if (pos < lastpos) {
        short n = buffer[pos] - '0';
        if (n >= 5) {
            // Start rounding up - starting from the digit left of pos all the way to the first digit
            bool carry_over = true;
            for (short p = pos - 1; p >= offset && carry_over; p--) {
                if (buffer[p] == DECIMAL_POINT) {
                    continue;
                }
                n = buffer[p] - '0';
                carry_over = (++n == 10);
                if (n == 10) {
                    n = 0;
                }
                buffer[p] = '0' + n;
            }
            if (carry_over) {
                buffer[offset - 1] = '1';
            }
        }
        if (num_decimals == 0) {
            buffer[decimal_pos] = '\0';
            return decimal_pos;
        }
        else {
            buffer[pos] = '\0';
            return pos;
        }
    } 

    // Do nothing and just return
    return lastpos;
}
} // end of anonymous namespace

////////////////////////////////////////////////////////////////////////////////////////////////
//
// *** implementations of structures used for SQLBindParameter ***
//
void sqlsrv_param::release_data()
{
    if (Z_TYPE(placeholder_z) == IS_STRING) {
        zend_string_release(Z_STR(placeholder_z));
    }

    ZVAL_UNDEF(&placeholder_z);

    buffer = NULL;
    param_stream = NULL;
    num_bytes_read = 0;
    param_ptr_z = NULL;
}

void sqlsrv_param::copy_param_meta_ae(_Inout_ zval* param_z, _In_ param_meta_data& meta)
{
    // Always Encrypted (AE) enabled - copy the meta data from SQLDescribeParam()
    sql_data_type = meta.sql_type;
    column_size = meta.column_size;
    decimal_digits = meta.decimal_digits;

    // Due to strict rules of AE, convert long to double if the sql type is decimal (numeric)
    if (Z_TYPE_P(param_z) == IS_LONG && (sql_data_type == SQL_DECIMAL || sql_data_type == SQL_NUMERIC)) {
        convert_to_double(param_z);
    }
}

bool sqlsrv_param::prepare_param(_In_ zval* param_ref, _Inout_ zval* param_z)
{
    // For input parameters, check if the original parameter was null
    was_null = (Z_TYPE_P(param_z) == IS_NULL);

    return true;
}

// Derives the ODBC C type constant that matches the PHP type and/or the encoding given
// If SQL type or column size is unknown, derives the appropriate values as well using the provided param zval and encoding
void sqlsrv_param::process_param(_Inout_ sqlsrv_stmt* stmt, _Inout_ zval* param_z)
{
    // Get param php type 
    param_php_type = Z_TYPE_P(param_z);
    
    switch (param_php_type) {
    case IS_NULL:
        process_null_param(param_z);
        break;
    case IS_TRUE:
    case IS_FALSE:
        process_bool_param(param_z);
        break;
    case IS_LONG:
        process_long_param(param_z);
        break;
    case IS_DOUBLE:
        process_double_param(param_z);
        break;
    case IS_STRING:
        process_string_param(stmt, param_z);
        break;
    case IS_RESOURCE:
        process_resource_param(param_z);
        break;
    case IS_OBJECT:
        process_object_param(stmt, param_z);
        break;
    case IS_ARRAY:
    default:
        THROW_CORE_ERROR(stmt, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE, param_pos + 1);
        break;
    }
}

void sqlsrv_param::process_null_param(_Inout_ zval* param_z)
{
    // Derive the param SQL type only if it is unknown
    if (sql_data_type == SQL_UNKNOWN_TYPE) {
        // Use the encoding to guess whether the sql_type is binary type or char type. For NULL cases,
        // if the server type is a binary type, then the server expects the sql_type to be binary type
        // as well, otherwise an error stating "Implicit conversion not allowed.." is thrown by the
        // server. For all other server types, setting the sql_type to sql_varchar works fine.
        // It must be varchar with column size 0 for ISNULL to work properly.
        sql_data_type = (encoding == SQLSRV_ENCODING_BINARY) ? SQL_BINARY : SQL_VARCHAR;
    }

    c_data_type = (encoding == SQLSRV_ENCODING_BINARY) ? SQL_C_BINARY : SQL_C_CHAR;

    if (column_size == SQLSRV_UNKNOWN_SIZE) {
        column_size = (encoding == SQLSRV_ENCODING_BINARY) ? 1 : 0;
        decimal_digits = 0;
    }
    buffer = NULL;
    buffer_length = 0;
    strlen_or_indptr = SQL_NULL_DATA;
}

void sqlsrv_param::process_bool_param(_Inout_ zval* param_z)
{
    // Derive the param SQL type only if it is unknown
    if (sql_data_type == SQL_UNKNOWN_TYPE) {
        sql_data_type = SQL_INTEGER;
    }

    c_data_type = SQL_C_SLONG;

    // The column size and decimal digits are by default 0
    // Ignore column_size and decimal_digits because they will be inferred by ODBC
    // Convert the lval to 0 or 1
    convert_to_long(param_z);
    buffer = &param_z->value;
    buffer_length = sizeof(Z_LVAL_P(param_z));
    strlen_or_indptr = buffer_length;
}

void sqlsrv_param::process_long_param(_Inout_ zval* param_z)
{
    // Derive the param SQL type only if it is unknown
    if (sql_data_type == SQL_UNKNOWN_TYPE) {
        //ODBC 64-bit long and integer type are 4 byte values.
        if ((Z_LVAL_P(param_z) < INT_MIN) || (Z_LVAL_P(param_z) > INT_MAX)) {
            sql_data_type = SQL_BIGINT;
        } else {
            sql_data_type = SQL_INTEGER;
        }
    }

    // When binding any integer, the zend_long value and its length are used as the buffer 
    // and buffer length. When the buffer is 8 bytes use the corresponding C type for 
    // 8-byte integers
#ifdef ZEND_ENABLE_ZVAL_LONG64
    c_data_type = SQL_C_SBIGINT;
#else
    c_data_type = SQL_C_SLONG;
#endif

    // The column size and decimal digits are by default 0
    // Ignore column_size and decimal_digits because they will be inferred by ODBC
    buffer = &param_z->value;
    buffer_length = sizeof(Z_LVAL_P(param_z));
    strlen_or_indptr = buffer_length;
}

void sqlsrv_param::process_double_param(_Inout_ zval* param_z)
{
    // Derive the param SQL type only if it is unknown
    if (sql_data_type == SQL_UNKNOWN_TYPE) {
        sql_data_type = SQL_FLOAT;
    }
    // The column size and decimal digits are by default 0
    // Ignore column_size and decimal_digits because they will be inferred by ODBC
    c_data_type = SQL_C_DOUBLE;

    buffer = &param_z->value;
    buffer_length = sizeof(Z_DVAL_P(param_z));
    strlen_or_indptr = buffer_length;
}

bool sqlsrv_param::derive_string_types_sizes(_In_ zval* param_z)
{
    SQLSRV_ASSERT(encoding == SQLSRV_ENCODING_CHAR || encoding == SQLSRV_ENCODING_UTF8 || encoding == SQLSRV_ENCODING_BINARY, "Invalid encoding in sqlsrv_param::derive_string_types_sizes");

    // Derive the param SQL type only if it is unknown
    if (sql_data_type == SQL_UNKNOWN_TYPE) {
        switch (encoding) {
        case SQLSRV_ENCODING_CHAR:
            sql_data_type = SQL_VARCHAR;
            break;
        case SQLSRV_ENCODING_BINARY:
            sql_data_type = SQL_VARBINARY;
            break;
        case SQLSRV_ENCODING_UTF8:
            sql_data_type = SQL_WVARCHAR;
            break;
        default:
            break;
        }
    }

    bool is_numeric = is_a_numeric_type(sql_data_type);

    // Derive the C Data type next
    switch (encoding) {
    case SQLSRV_ENCODING_CHAR:
        c_data_type = SQL_C_CHAR;
        break;
    case SQLSRV_ENCODING_BINARY:
        c_data_type = SQL_C_BINARY;
        break;
    case SQLSRV_ENCODING_UTF8:
        c_data_type = is_numeric ? SQL_C_CHAR : SQL_C_WCHAR;
        break;
    default:
        break;
    }

    // Derive the column size also only if it is unknown
    if (column_size == SQLSRV_UNKNOWN_SIZE) {
        size_t char_size = (encoding == SQLSRV_ENCODING_UTF8) ? sizeof(SQLWCHAR) : sizeof(char);
        SQLULEN byte_len = Z_STRLEN_P(param_z) * char_size;

        if (byte_len > SQL_SERVER_MAX_FIELD_SIZE) {
            column_size = SQL_SERVER_MAX_TYPE_SIZE;
        } else {
            column_size = SQL_SERVER_MAX_FIELD_SIZE / char_size;
        }
    }

    return is_numeric;
}

bool sqlsrv_param::convert_input_str_to_utf16(_Inout_ sqlsrv_stmt* stmt, _In_ zval* param_z)
{
    // This converts the string in param_z and stores the wide string in the member placeholder_z
    char* str = Z_STRVAL_P(param_z);
    SQLLEN str_length = Z_STRLEN_P(param_z);

    if (str_length > 0) {
        sqlsrv_malloc_auto_ptr<SQLWCHAR> wide_buffer;
        unsigned int wchar_size = 0;

        wide_buffer = utf16_string_from_mbcs_string(encoding, reinterpret_cast<const char*>(str), static_cast<int>(str_length), &wchar_size, true);
        if (wide_buffer == 0) {
            return false;
        }
        wide_buffer[wchar_size] = L'\0';
        core::sqlsrv_zval_stringl(&placeholder_z, reinterpret_cast<char*>(wide_buffer.get()), wchar_size * sizeof(SQLWCHAR));
    } else {
        // If the string is empty, then nothing needs to be done
        core::sqlsrv_zval_stringl(&placeholder_z, "", 0);
    }

    return true;
}

void sqlsrv_param::process_string_param(_Inout_ sqlsrv_stmt* stmt, _Inout_ zval* param_z)
{
    bool is_numeric = derive_string_types_sizes(param_z);

    // With AE, the precision of the decimal or numeric inputs have to match exactly as defined in the columns.
    // Without AE, the derived default sql types will not be this specific. Thus, if sql_type is SQL_DECIMAL 
    // or SQL_NUMERIC, the user must have clearly specified it (using the SQLSRV driver) as SQL_DECIMAL or SQL_NUMERIC.
    // In either case, the input passed into SQLBindParam requires matching scale (i.e., number of decimal digits).
    if (sql_data_type == SQL_DECIMAL || sql_data_type == SQL_NUMERIC) {
        adjustDecimalPrecision(param_z, decimal_digits);
    }

    if (!is_numeric && encoding == CP_UTF8) {
        // Convert the input param value to wide string and save it for later
        if (Z_STRLEN_P(param_z) > INT_MAX) {
            LOG(SEV_ERROR, "Convert input parameter to utf16: buffer length exceeded.");
            throw core::CoreException();
        }
        // This changes the member placeholder_z to hold the wide string
        bool converted = convert_input_str_to_utf16(stmt, param_z);
        CHECK_CUSTOM_ERROR(!converted, stmt, SQLSRV_ERROR_INPUT_PARAM_ENCODING_TRANSLATE, param_pos + 1, get_last_error_message()) {
            throw core::CoreException();
        }

        // Bind the wide string in placeholder_z
        buffer = Z_STRVAL(placeholder_z);
        buffer_length = Z_STRLEN(placeholder_z);
    } else {
        buffer = Z_STRVAL_P(param_z);
        buffer_length = Z_STRLEN_P(param_z);
    }

    strlen_or_indptr = buffer_length;
}

void sqlsrv_param::process_resource_param(_Inout_ zval* param_z)
{
    SQLSRV_ASSERT(encoding == SQLSRV_ENCODING_CHAR || encoding == SQLSRV_ENCODING_UTF8 || encoding == SQLSRV_ENCODING_BINARY, "Invalid encoding in sqlsrv_param::get_resource_param_info");

    // Derive the param SQL type only if it is unknown
    if (sql_data_type == SQL_UNKNOWN_TYPE) {
        switch (encoding) {
        case SQLSRV_ENCODING_CHAR:
            sql_data_type = SQL_VARCHAR;
            break;
        case SQLSRV_ENCODING_BINARY:
            sql_data_type = SQL_VARBINARY;
            break;
        case SQLSRV_ENCODING_UTF8:
            sql_data_type = SQL_WVARCHAR;
            break;
        default:
            break;
        }
    }

    // The column_size will be inferred by ODBC unless it is SQLSRV_UNKNOWN_SIZE
    if (column_size == SQLSRV_UNKNOWN_SIZE) {
        column_size = 0;
    }

    switch (encoding) {
    case SQLSRV_ENCODING_CHAR:
        c_data_type = SQL_C_CHAR;
        break;
    case SQLSRV_ENCODING_BINARY:
        c_data_type = SQL_C_BINARY;
        break;
    case SQLSRV_ENCODING_UTF8:
        c_data_type = SQL_C_WCHAR;
        break;
    default:
        break;
    }

    param_ptr_z = param_z;
    buffer = reinterpret_cast<SQLPOINTER>(this);
    buffer_length = 0;
    strlen_or_indptr = SQL_DATA_AT_EXEC;
}

bool sqlsrv_param::convert_datetime_to_string(_Inout_ sqlsrv_stmt* stmt, _In_ zval* param_z)
{
    // This changes the member placeholder_z to hold the converted string of the datetime object
    zval function_z;
    zval format_z;
    zval params[1];
    ZVAL_UNDEF(&function_z);
    ZVAL_UNDEF(&format_z);
    ZVAL_UNDEF(params);

    // If the user specifies the 'date' sql type, giving it the normal format will cause a 'date overflow error'
    // meaning there is too much information in the character string.  If the user specifies the 'datetimeoffset'
    // sql type, it lacks the timezone.
    if (sql_data_type == SQL_SS_TIMESTAMPOFFSET) {
        ZVAL_STRINGL(&format_z, DateTime::DATETIMEOFFSET_FORMAT, DateTime::DATETIMEOFFSET_FORMAT_LEN);
    } else if (sql_data_type == SQL_TYPE_DATE) {
        ZVAL_STRINGL(&format_z, DateTime::DATE_FORMAT, DateTime::DATE_FORMAT_LEN);
    } else {
        ZVAL_STRINGL(&format_z, DateTime::DATETIME_FORMAT, DateTime::DATETIME_FORMAT_LEN);
    }

    // call the DateTime::format member function to convert the object to a string that SQL Server understands
    ZVAL_STRINGL(&function_z, "format", sizeof("format") - 1);
    //core::sqlsrv_zval_stringl(&function_z, "format", sizeof("format") - 1);
    params[0] = format_z;

    // If placeholder_z is a string, release it first before assigning a new string value
    if (Z_TYPE(placeholder_z) == IS_STRING && Z_STR(placeholder_z) != NULL) {
        zend_string_release(Z_STR(placeholder_z));
    }

    // This is equivalent to the PHP code: $param_z->format($format_z); where param_z is the
    // DateTime object and $format_z is the format string.
    int zr = call_user_function(EG(function_table), param_z, &function_z, &placeholder_z, 1, params);

    zend_string_release(Z_STR(format_z));
    zend_string_release(Z_STR(function_z));

    return (zr != FAILURE);
}

bool sqlsrv_param::preprocess_datetime_object(_Inout_ sqlsrv_stmt* stmt, _In_ zval* param_z)
{
    bool valid_class_name_found = false;
    zend_class_entry *class_entry = Z_OBJCE_P(param_z);

    while (class_entry != NULL) {
        SQLSRV_ASSERT(class_entry->name != NULL, "sqlsrv_param::get_object_param_info -- class_entry->name is NULL.");
        if (class_entry->name->len == DateTime::DATETIME_CLASS_NAME_LEN && class_entry->name != NULL &&
            stricmp(class_entry->name->val, DateTime::DATETIME_CLASS_NAME) == 0) {
            valid_class_name_found = true;
            break;
        } else {
            // Check the parent
            class_entry = class_entry->parent;
        }
    }

    if (!valid_class_name_found) {
        return false;
    }

    // Derive the param SQL type only if it is unknown
    if (sql_data_type == SQL_UNKNOWN_TYPE) {
        // For SQL Server 2005 or earlier, make it a SQLSRV_SQLTYPE_DATETIME.
        // Otherwise it should be SQLSRV_SQLTYPE_TIMESTAMPOFFSET because these
        // are the date types of the highest precision for the server
        if (stmt->conn->server_version <= SERVER_VERSION_2005) {
            sql_data_type = SQL_TYPE_TIMESTAMP;
        } else {
            sql_data_type = SQL_SS_TIMESTAMPOFFSET;
        }
    }

    c_data_type = SQL_C_CHAR;

    // Derive the column size also only if it is unknown
    if (column_size == SQLSRV_UNKNOWN_SIZE) {
        if (stmt->conn->server_version <= SERVER_VERSION_2005) {
            column_size = SQL_SERVER_2005_DEFAULT_DATETIME_PRECISION;
            decimal_digits = SQL_SERVER_2005_DEFAULT_DATETIME_SCALE;
        } else {
            column_size = SQL_SERVER_2008_DEFAULT_DATETIME_PRECISION;
            decimal_digits = SQL_SERVER_2008_DEFAULT_DATETIME_SCALE;
        }
    }

    return true;
}

void sqlsrv_param::process_object_param(_Inout_ sqlsrv_stmt* stmt, _Inout_ zval* param_z)
{
    // Assume the param refers to a DateTime object since it's the only type the drivers support.
    // Verification occurs in the calling function as the drivers convert the DateTime object
    // to a string before sending it to the server.
    bool succeeded = preprocess_datetime_object(stmt, param_z);
    if (succeeded) {
        succeeded = convert_datetime_to_string(stmt, param_z);
    }
    CHECK_CUSTOM_ERROR(!succeeded, stmt, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE, param_pos + 1) {
        throw core::CoreException();
    }

    buffer = Z_STRVAL(placeholder_z);
    buffer_length = Z_STRLEN(placeholder_z) - 1;
    strlen_or_indptr = buffer_length;
}

void sqlsrv_param::bind_param(_Inout_ sqlsrv_stmt* stmt)
{
    if (was_null) {
        strlen_or_indptr = SQL_NULL_DATA;
    }

    core::SQLBindParameter(stmt, param_pos + 1, direction, c_data_type, sql_data_type, column_size, decimal_digits, buffer, buffer_length, &strlen_or_indptr);
}

void sqlsrv_param::init_data_from_zval(_Inout_ sqlsrv_stmt* stmt)
{
    // Get the stream from the param zval value
    num_bytes_read = 0;
    param_stream = NULL;
    core::sqlsrv_php_stream_from_zval_no_verify(*stmt, param_stream, param_ptr_z);
}

bool sqlsrv_param::send_data_packet(_Inout_ sqlsrv_stmt* stmt)
{
    // Check EOF first
    if (php_stream_eof(param_stream)) {
        // But return to the very beginning of param_stream since SQLParamData() may ask for the same data again
        int ret = php_stream_seek(param_stream, 0, SEEK_SET);
        if (ret != 0) {
            LOG(SEV_ERROR, "PHP stream: stream seek failed.");
            throw core::CoreException();
        }
        // Reset num_bytes_read
        num_bytes_read = 0;

        return false;
    } else {
        // Read the data from the stream, send it via SQLPutData and track how much is already sent.
        char buffer[PHP_STREAM_BUFFER_SIZE + 1] = { '\0' };
        std::size_t buffer_size = sizeof(buffer) - 3;   // -3 to preserve enough space for a cut off UTF-8 character
        std::size_t read = php_stream_read(param_stream, buffer, buffer_size);

        if (read > UINT_MAX) {
            LOG(SEV_ERROR, "PHP stream: buffer length exceeded.");
            throw core::CoreException();
        }

        num_bytes_read += read;
        if (read == 0) {
            // Send an empty string, which is what a 0 length does.
            char buff[1];       // Temp storage to hand to SQLPutData
            core::SQLPutData(stmt, buff, 0);
        } else if (read > 0) {
            // If this is a UTF-8 stream, then we will use the UTF-8 encoding to determine if we're in the middle of a character
            // then read in the appropriate number more bytes and then retest the string.  This way we try at most to convert it
            // twice.
            // If we support other encondings in the future, we'll simply need to read a single byte and then retry the conversion
            // since all other MBCS supported by SQL Server are 2 byte maximum size.

            if (encoding == CP_UTF8) {
                // The size of wbuffer is set for the worst case of UTF-8 to UTF-16 conversion, which is an
                // expansion of 2x the UTF-8 size.
                SQLWCHAR wbuffer[PHP_STREAM_BUFFER_SIZE + 1] = { L'\0' };
                int wbuffer_size = static_cast<int>(sizeof(wbuffer) / sizeof(SQLWCHAR));
                DWORD last_error_code = ERROR_SUCCESS;
                    
                // The buffer_size is the # of wchars.  Set to buffer_size / 2
#ifndef _WIN32
                int wsize = SystemLocale::ToUtf16Strict(encoding, buffer, static_cast<int>(read), wbuffer, wbuffer_size, &last_error_code);
#else
                int wsize = MultiByteToWideChar(encoding, MB_ERR_INVALID_CHARS, buffer, static_cast<int>(read), wbuffer, wbuffer_size);
                last_error_code = GetLastError();
#endif // !_WIN32

                if (wsize == 0 && last_error_code == ERROR_NO_UNICODE_TRANSLATION) {
                    // This will calculate how many bytes were cut off from the last UTF-8 character and read that many more
                    // in, then reattempt the conversion.  If it fails the second time, then an error is returned.
                    size_t need_to_read = calc_utf8_missing(stmt, buffer, read);
                    // read the missing bytes
                    size_t new_read = php_stream_read(param_stream, static_cast<char*>(buffer) + read, need_to_read);
                    // if the bytes couldn't be read, then we return an error
                    CHECK_CUSTOM_ERROR(new_read != need_to_read, stmt, SQLSRV_ERROR_INPUT_STREAM_ENCODING_TRANSLATE, get_last_error_message(ERROR_NO_UNICODE_TRANSLATION)) {
                        throw core::CoreException();
                    }

                    // Try the conversion again with the complete character
#ifndef _WIN32
                    wsize = SystemLocale::ToUtf16Strict(encoding, buffer, static_cast<int>(read + new_read), wbuffer, static_cast<int>(sizeof(wbuffer) / sizeof(SQLWCHAR)));
#else
                    wsize = MultiByteToWideChar(encoding, MB_ERR_INVALID_CHARS, buffer, static_cast<int>(read + new_read), wbuffer, static_cast<int>(sizeof(wbuffer) / sizeof(wchar_t)));
#endif //!_WIN32
                    // something else must be wrong if it failed
                    CHECK_CUSTOM_ERROR(wsize == 0, stmt, SQLSRV_ERROR_INPUT_STREAM_ENCODING_TRANSLATE, get_last_error_message(ERROR_NO_UNICODE_TRANSLATION)) {
                        throw core::CoreException();
                    }
                }
                core::SQLPutData(stmt, wbuffer, wsize * sizeof(SQLWCHAR));
            }
            else {
                core::SQLPutData(stmt, buffer, read);
            } // NOT UTF8
        } // read > 0
        return true;
    } // NOT EOF
}

bool sqlsrv_param_inout::prepare_param(_In_ zval* param_ref, _Inout_ zval* param_z)
{
    // Save the output param reference now
    param_ptr_z = param_ref;
    
    int type = Z_TYPE_P(param_z);
    was_null = (type == IS_NULL);
    was_bool = (type == IS_TRUE || type == IS_FALSE);

    if (direction == SQL_PARAM_INPUT_OUTPUT) {
        // If the user asks for for a specific type for input and output, make sure the data type we send matches the data we
        // type we expect back, since we can only send and receive the same type. Anything can be converted to a string, so
        // we always let that match if they want a string back.
        bool matched = false;

        switch (php_out_type) {
        case SQLSRV_PHPTYPE_INT:
            if (was_null || was_bool) {
                convert_to_long(param_z);
            }
            matched = (Z_TYPE_P(param_z) == IS_LONG);
            break;
        case SQLSRV_PHPTYPE_FLOAT:
            if (was_null) {
                convert_to_double(param_z);
            }
            matched = (Z_TYPE_P(param_z) == IS_DOUBLE);
            break;
        case SQLSRV_PHPTYPE_STRING:
            // anything can be converted to a string
            convert_to_string(param_z);
            matched = true;
            break;
        case SQLSRV_PHPTYPE_NULL:
        case SQLSRV_PHPTYPE_DATETIME:
        case SQLSRV_PHPTYPE_STREAM:
        default:
            SQLSRV_ASSERT(false, "sqlsrv_param_inout::prepare_param -- invalid type for an output parameter.");
            break;
        }

        return matched;
    } else if (direction == SQL_PARAM_OUTPUT) {
        // If the user specifies a certain type for an output parameter, we have to convert the zval
        // to that type so that when the buffer is filled, the type is correct. But first,
        // should check if a LOB type is specified.
        switch (php_out_type) {
        case SQLSRV_PHPTYPE_INT:
            convert_to_long(param_z);
            break;
        case SQLSRV_PHPTYPE_FLOAT:
            convert_to_double(param_z);
            break;
        case SQLSRV_PHPTYPE_STRING:
            convert_to_string(param_z);
            break;
        case SQLSRV_PHPTYPE_NULL:
        case SQLSRV_PHPTYPE_DATETIME:
        case SQLSRV_PHPTYPE_STREAM:
        default:
            SQLSRV_ASSERT(false, "sqlsrv_param_inout::prepare_param -- invalid type for an output parameter");
            break;
        }

        return true;
    } else {
        SQLSRV_ASSERT(false, "sqlsrv_param_inout::prepare_param -- wrong param direction.");
    }
    return false;
}

// Derives the ODBC C type constant that matches the PHP type and/or the encoding given
// If SQL type or column size is unknown, derives the appropriate values as well using the provided param zval and encoding
void sqlsrv_param_inout::process_param(_Inout_ sqlsrv_stmt* stmt, zval* param_z)
{
    // Get param php type NOW because the original parameter might have been converted beforehand 
    param_php_type = Z_TYPE_P(param_z);

    switch (param_php_type) {
    case IS_LONG:
        process_long_param(param_z);
        break;
    case IS_DOUBLE:
        process_double_param(param_z);
        break;
    case IS_STRING:
        process_string_param(stmt, param_z);
        break;
    default:
        THROW_CORE_ERROR(stmt, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE, param_pos + 1);
        break;
    }

    // Save the pointer to the statement object
    this->stmt = stmt;
}

void sqlsrv_param_inout::process_string_param(_Inout_ sqlsrv_stmt* stmt, _Inout_ zval* param_z)
{
    bool is_numeric_type = derive_string_types_sizes(param_z);

    buffer = Z_STRVAL_P(param_z);
    buffer_length = Z_STRLEN_P(param_z);

    if (ZSTR_IS_INTERNED(Z_STR_P(param_z))) {
        // PHP 5.4 added interned strings, and since we obviously want to change that string here in some fashion,
        // we reallocate the string if it's interned
        core::sqlsrv_zval_stringl(param_z, static_cast<const char*>(buffer), buffer_length);

        // reset buffer and its length
        buffer = Z_STRVAL_P(param_z);
        buffer_length = Z_STRLEN_P(param_z);
    }

    // If it's a UTF-8 input output parameter (signified by the C type being SQL_C_WCHAR)
    // or if the PHP type is a binary encoded string with a N(VAR)CHAR/NTEXT SQL type,
    // convert it to wchar first
    if (direction == SQL_PARAM_INPUT_OUTPUT &&
        (c_data_type == SQL_C_WCHAR ||
            (c_data_type == SQL_C_BINARY &&
                (sql_data_type == SQL_WCHAR || sql_data_type == SQL_WVARCHAR || sql_data_type == SQL_WLONGVARCHAR)))) {

        if (buffer_length > 0) {
            sqlsrv_malloc_auto_ptr<SQLWCHAR> wide_buffer;
            unsigned int wchar_size = 0;

            wide_buffer = utf16_string_from_mbcs_string(SQLSRV_ENCODING_UTF8, reinterpret_cast<const char*>(buffer), static_cast<int>(buffer_length), &wchar_size);
            CHECK_CUSTOM_ERROR(wide_buffer == 0, stmt, SQLSRV_ERROR_INPUT_PARAM_ENCODING_TRANSLATE, param_pos + 1, get_last_error_message()) {
                throw core::CoreException();
            }
            wide_buffer[wchar_size] = L'\0';
            core::sqlsrv_zval_stringl(param_z, reinterpret_cast<char*>(wide_buffer.get()), wchar_size * sizeof(SQLWCHAR));
            buffer = Z_STRVAL_P(param_z);
            buffer_length = Z_STRLEN_P(param_z);
        }
    } 

    strlen_or_indptr = buffer_length;

    // Since this is an output string, assure there is enough space to hold the requested size and
    // update all the variables accordingly (param_z, buffer, buffer_length, and strlen_or_indptr)
    resize_output_string_buffer(param_z, is_numeric_type);
    if (is_numeric_type) {
        encoding = SQLSRV_ENCODING_CHAR;
    }

    // For output parameters, if we set the column_size to be same as the buffer_len,
    // then if there is a truncation due to the data coming from the server being
    // greater than the column_size, we don't get any truncation error. In order to
    // avoid this silent truncation, we set the column_size to be "MAX" size for
    // string types. This will guarantee that there is no silent truncation for
    // output parameters.
    // if column encryption is enabled, at this point the correct column size has been set by SQLDescribeParam
    if (direction == SQL_PARAM_OUTPUT && !stmt->conn->ce_option.enabled) {

        switch (sql_data_type) {
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

// Called when the output parameter is ready to be finalized, using the value stored in param_ptr_z
void sqlsrv_param_inout::finalize_output_value()
{
    if (param_ptr_z == NULL) {
        return;
    }

    zval* value_z = Z_REFVAL_P(param_ptr_z);

    switch (Z_TYPE_P(value_z)) {
    case IS_STRING:
        finalize_output_string();
        break;
    case IS_LONG:
        // For a long or a float, simply check if NULL was returned and if so, set the parameter to a PHP null
        if (strlen_or_indptr == SQL_NULL_DATA) {
            ZVAL_NULL(value_z);
        } else if (was_bool) {
            convert_to_boolean(value_z);
        } else {
            ZVAL_LONG(value_z, static_cast<int>(Z_LVAL_P(value_z)));
        }
        break;
    case IS_DOUBLE:
        // For a long or a float, simply check if NULL was returned and if so, set the parameter to a PHP null
        if (strlen_or_indptr == SQL_NULL_DATA) {
            ZVAL_NULL(value_z);
        } else if (php_out_type == SQLSRV_PHPTYPE_INT) {
            // First check if its value is out of range
            double dval = Z_DVAL_P(value_z);
            if (dval > INT_MAX || dval < INT_MIN) {
                CHECK_CUSTOM_ERROR(true, stmt, SQLSRV_ERROR_DOUBLE_CONVERSION_FAILED) {
                    throw core::CoreException();
                }
            }
            // Even if the output param is a boolean, still convert to a long 
            // integer first to take care of rounding
            convert_to_long(value_z);
            if (was_bool) {
                convert_to_boolean(value_z);
            }
        }
        break;
    default:
        SQLSRV_ASSERT(false, "Should not have reached here - invalid output parameter type in sqlsrv_param_inout::finalize_output_value.");
        break;
    }

    value_z = NULL;
    param_ptr_z = NULL; // Do not keep the reference now that the output param has been processed
}

// A helper method called by finalize_output_value() to finalize output string parameters
void sqlsrv_param_inout::finalize_output_string()
{
    zval* value_z = Z_REFVAL_P(param_ptr_z);

    // Adjust the length of the string to the value returned by SQLBindParameter in the strlen_or_indptr argument
    if (strlen_or_indptr == 0) {
        core::sqlsrv_zval_stringl(value_z, "", 0);
        return;
    }
    if (strlen_or_indptr == SQL_NULL_DATA) {
        zend_string_release(Z_STR_P(value_z));
        ZVAL_NULL(value_z);
        return;
    }

    // If there was more to output than buffer size to hold it, then throw a truncation error
    SQLLEN str_len = strlen_or_indptr;
    char* str = Z_STRVAL_P(value_z);
    int null_size = 0;

    switch (encoding) {
    case SQLSRV_ENCODING_UTF8:
        null_size = sizeof(SQLWCHAR);  // The string isn't yet converted to UTF-8, still UTF-16
        break;
    case SQLSRV_ENCODING_SYSTEM:
        null_size = sizeof(SQLCHAR);
        break;
    case SQLSRV_ENCODING_BINARY:
        null_size = 0;
        break;
    default:
        SQLSRV_ASSERT(false, "Should not have reached here - invalid encoding in sqlsrv_param_inout::process_output_string.");
        break;
    }

    CHECK_CUSTOM_ERROR(str_len > (buffer_length - null_size), stmt, SQLSRV_ERROR_OUTPUT_PARAM_TRUNCATED, param_pos + 1) {
        throw core::CoreException();
    }

    // For ODBC 11+ see https://docs.microsoft.com/sql/relational-databases/native-client/features/odbc-driver-behavior-change-when-handling-character-conversions
    // A length value of SQL_NO_TOTAL for SQLBindParameter indicates that the buffer contains data up to the
    // original buffer_length and is NULL terminated.
    // The IF statement can be true when using connection pooling with unixODBC 2.3.4.
    if (str_len == SQL_NO_TOTAL) {
        str_len = buffer_length - null_size;
    }

    if (encoding == SQLSRV_ENCODING_BINARY) {
        // ODBC doesn't null terminate binary encodings, but PHP complains if a string isn't null terminated
        // so we do that here if the length of the returned data is less than the original allocation. The
        // original allocation null terminates the buffer already.
        if (str_len < buffer_length) {
            str[str_len] = '\0';
        }
        core::sqlsrv_zval_stringl(value_z, str, str_len);
    }
    else {
        if (encoding != SQLSRV_ENCODING_CHAR) {
            char* outString = NULL;
            SQLLEN outLen = 0;

            bool result = convert_string_from_utf16(encoding, reinterpret_cast<const SQLWCHAR*>(str), int(str_len / sizeof(SQLWCHAR)), &outString, outLen);
            CHECK_CUSTOM_ERROR(!result, stmt, SQLSRV_ERROR_OUTPUT_PARAM_ENCODING_TRANSLATE, get_last_error_message()) {
                throw core::CoreException();
            }

            if (stmt->format_decimals && (sql_data_type == SQL_DECIMAL || sql_data_type == SQL_NUMERIC)) {
                format_decimal_numbers(NO_CHANGE_DECIMAL_PLACES, decimal_digits, outString, &outLen);
            }

            core::sqlsrv_zval_stringl(value_z, outString, outLen);
            sqlsrv_free(outString);
        }
        else {
            if (stmt->format_decimals && (sql_data_type == SQL_DECIMAL || sql_data_type == SQL_NUMERIC)) {
                format_decimal_numbers(NO_CHANGE_DECIMAL_PLACES, decimal_digits, str, &str_len);
            }

            core::sqlsrv_zval_stringl(value_z, str, str_len);
        }
    }

    value_z = NULL;
}

void sqlsrv_param_inout::resize_output_string_buffer(_Inout_ zval* param_z, _In_ bool is_numeric_type)
{
    // Prerequisites: buffer, buffer_length, column_size, and strlen_or_indptr have been set to a known value 
    // Purpose: 
    // Verify there is enough space to hold the output string parameter, and allocate if necessary. The param_z
    // is updated to contain the new buffer with the correct size and its reference is incremented, and all required 
    // values for SQLBindParameter will also be updated. 
    SQLLEN original_len = buffer_length;
    SQLLEN expected_len;
    SQLLEN buffer_null_extra;
    SQLLEN elem_size;

    // Calculate the size of each 'element' represented by column_size.  WCHAR is the size of a wide char (2), and so is 
    // a N(VAR)CHAR/NTEXT field being returned as a binary field.
    elem_size = (c_data_type == SQL_C_WCHAR || 
                (c_data_type == SQL_C_BINARY && 
                    (sql_data_type == SQL_WCHAR || sql_data_type == SQL_WVARCHAR || sql_data_type == SQL_WLONGVARCHAR))) ? sizeof(SQLWCHAR) : sizeof(SQLCHAR);

    // account for the NULL terminator returned by ODBC and needed by Zend to avoid a "String not null terminated" debug warning
    SQLULEN field_size = column_size;

    // With AE enabled, column_size is already retrieved from SQLDescribeParam, but column_size
    // does not include the negative sign or decimal place for numeric values
    // VSO Bug 2913: without AE, the same can happen as well, in particular to decimals
    // and numerics with precision/scale specified
    if (is_numeric_type) {
        // Include the possible negative sign
        field_size += elem_size;
        // Include the decimal dot for output params by adding elem_size
        if (decimal_digits > 0) {
            field_size += elem_size;
        }
    }

    if (column_size == SQL_SS_LENGTH_UNLIMITED) {
        field_size = SQL_SERVER_MAX_FIELD_SIZE / elem_size;
    }
    expected_len = field_size * elem_size + elem_size;

    // Binary fields aren't null terminated, so we need to account for that in our buffer length calcuations
    buffer_null_extra = (c_data_type == SQL_C_BINARY) ? elem_size : 0;

    // Increment to include the null terminator since the Zend length doesn't include the null terminator
    buffer_length += elem_size;

    // if the current buffer size is smaller than the necessary size, resize the buffer and set the zval to the new
    // length.
    if (buffer_length < expected_len) {
        SQLSRV_ASSERT(expected_len >= expected_len - buffer_null_extra, "Integer overflow/underflow caused a corrupt field length.");

        // allocate enough space to ALWAYS include the NULL regardless of the type being retrieved since
        // we set the last byte(s) to be NULL to avoid the debug build warning from the Zend engine about
        // not having a NULL terminator on a string.
        zend_string* param_z_string = zend_string_realloc(Z_STR_P(param_z), expected_len, 0);

        // A zval string len doesn't include the null.  This calculates the length it should be
        // regardless of whether the ODBC type contains the NULL or not.

        // initialize the newly allocated space
        char *p = ZSTR_VAL(param_z_string);
        p = p + original_len;
        memset(p, '\0', expected_len - original_len);
        ZVAL_NEW_STR(param_z, param_z_string);

        // buffer_len is the length passed to SQLBindParameter.  It must contain the space for NULL in the
        // buffer when retrieving anything but SQLSRV_ENC_BINARY/SQL_C_BINARY
        buffer_length = Z_STRLEN_P(param_z) - buffer_null_extra;

        // Zend string length doesn't include the null terminator
        ZSTR_LEN(Z_STR_P(param_z)) -= elem_size;
    }

    buffer = Z_STRVAL_P(param_z);

    // The StrLen_Ind_Ptr parameter of SQLBindParameter should contain the length of the data to send, which
    // may be less than the size of the buffer since the output may be more than the input.  If it is greater,
    // then the error 22001 is returned by ODBC.
    if (strlen_or_indptr > buffer_length - (elem_size - buffer_null_extra)) {
        strlen_or_indptr = buffer_length - (elem_size - buffer_null_extra);
    }
}

// Change the column encoding based on the sql data type
/*static*/ void sqlsrv_param_tvp::sql_type_to_encoding(_In_ SQLSMALLINT sql_type, _Inout_ SQLSRV_ENCODING* encoding)
{
    switch (sql_type) {
    case SQL_BIGINT:
    case SQL_DECIMAL:
    case SQL_NUMERIC:
    case SQL_BIT:
    case SQL_INTEGER:
    case SQL_SMALLINT:
    case SQL_TINYINT:
    case SQL_FLOAT:
    case SQL_REAL:
        *encoding = SQLSRV_ENCODING_CHAR;
        break;
    case SQL_BINARY:
    case SQL_LONGVARBINARY:
    case SQL_VARBINARY:
    case SQL_SS_UDT:
        *encoding = SQLSRV_ENCODING_BINARY;
        break;
    default:
        // Do nothing
        break;
    }
}

void sqlsrv_param_tvp::get_tvp_metadata(_In_ sqlsrv_stmt* stmt, _In_ zend_string* table_type_name, _In_ zend_string* schema_name)
{
    SQLHANDLE   chstmt = SQL_NULL_HANDLE;
    SQLRETURN   rc;
    SQLSMALLINT data_type, dec_digits;
    SQLINTEGER  col_size;
    SQLLEN      cb_data_type, cb_col_size, cb_dec_digits;
    char*       table_type = ZSTR_VAL(table_type_name);

    core::SQLAllocHandle(SQL_HANDLE_STMT, *(stmt->conn), &chstmt);

    rc = SQLSetStmtAttr(chstmt, SQL_SOPT_SS_NAME_SCOPE, (SQLPOINTER)SQL_SS_NAME_SCOPE_TABLE_TYPE, SQL_IS_UINTEGER);
    CHECK_CUSTOM_ERROR(!SQL_SUCCEEDED(rc), stmt, SQLSRV_ERROR_TVP_FETCH_METADATA, param_pos + 1) {
        throw core::CoreException();
    }

    // Check table type name and see if the schema is specified. Otherwise, assume DBO
    if (schema_name != NULL) {
        char* schema = ZSTR_VAL(schema_name);
        rc = SQLColumns(chstmt, NULL, 0, reinterpret_cast<SQLCHAR*>(schema), SQL_NTS, reinterpret_cast<SQLCHAR*>(table_type), SQL_NTS, NULL, 0);
    } else {
        rc = SQLColumns(chstmt, NULL, 0, NULL, SQL_NTS, reinterpret_cast<SQLCHAR*>(table_type), SQL_NTS, NULL, 0);
    }

    CHECK_CUSTOM_ERROR(!SQL_SUCCEEDED(rc), stmt, SQLSRV_ERROR_TVP_FETCH_METADATA, param_pos + 1) {
        throw core::CoreException();
    }

    SQLSRV_ENCODING stmt_encoding = (stmt->encoding() == SQLSRV_ENCODING_DEFAULT) ? stmt->conn->encoding() : stmt->encoding();

    if (rc == SQL_SUCCESS || rc == SQL_SUCCESS_WITH_INFO) {
        SQLBindCol(chstmt, 5, SQL_C_SSHORT, &data_type, 0, &cb_data_type);
        SQLBindCol(chstmt, 7, SQL_C_SLONG, &col_size, 0, &cb_col_size);
        SQLBindCol(chstmt, 9, SQL_C_SSHORT, &dec_digits, 0, &cb_dec_digits);

        SQLUSMALLINT pos = 0;
        while (SQL_SUCCESS == rc) {
            rc = SQLFetch(chstmt);
            if (rc == SQL_NO_DATA) {
                CHECK_CUSTOM_ERROR(tvp_columns.size() == 0, stmt, SQLSRV_ERROR_TVP_FETCH_METADATA, param_pos + 1) {
                    throw core::CoreException();
                }
                break;
            }

            sqlsrv_malloc_auto_ptr<sqlsrv_param_tvp> param_ptr;

            // The SQL data type is used to derive the column encoding
            SQLSRV_ENCODING column_encoding = stmt_encoding;
            sql_type_to_encoding(data_type, &column_encoding);

            param_ptr = new (sqlsrv_malloc(sizeof(sqlsrv_param_tvp))) sqlsrv_param_tvp(pos, column_encoding, data_type, col_size, dec_digits, this);
            param_ptr->num_rows = this->num_rows;   // Each column inherits the number of rows from the TVP

            tvp_columns[pos] = param_ptr.get();
            param_ptr.transferred();

            pos++;
        }
    } else {
        THROW_CORE_ERROR(stmt, SQLSRV_ERROR_TVP_FETCH_METADATA, param_pos + 1);
    }

    SQLCloseCursor(chstmt);
    SQLFreeHandle(SQL_HANDLE_STMT, chstmt);
}

void sqlsrv_param_tvp::release_data()
{
    // Clean up tvp_columns
    std::map<SQLUSMALLINT, sqlsrv_param_tvp*>::iterator it;
    for (it = tvp_columns.begin(); it != tvp_columns.end(); ++it) {
        sqlsrv_param_tvp* ptr = it->second;
        if (ptr) {
            ptr->release_data();
            sqlsrv_free(ptr);
        }
    }
    tvp_columns.clear();

    sqlsrv_param::release_data();
}

void sqlsrv_param_tvp::process_param(_Inout_ sqlsrv_stmt* stmt, _Inout_ zval* param_z)
{
    if (sql_data_type == SQL_SS_TABLE) {
        // This is a table-valued parameter
        param_php_type = IS_ARRAY;
        c_data_type = SQL_C_DEFAULT;

        // The decimal_digits must be 0 for TVP
        decimal_digits = 0;

        // The column_size for a TVP is the row array size
        // The following method will verify the input array and also derive num_rows
        this->num_rows = 0;
        int num_columns = parse_tv_param_arrays(stmt, param_z);
        column_size = num_rows;
        
        strlen_or_indptr = (num_columns == 0)? SQL_DEFAULT_PARAM : SQL_DATA_AT_EXEC;
    } else {
        // This is one of the constituent columns of the table-valued parameter
        // The column value of the first row is already saved in member variable param_ptr_z
        process_param_column_value(stmt);
    }
}

int sqlsrv_param_tvp::parse_tv_param_arrays(_Inout_ sqlsrv_stmt* stmt, _Inout_ zval* param_z)
{
    // If this is not a table-valued parameter, simply return
    if (sql_data_type != SQL_SS_TABLE) {
        return 0;
    }

    // This method verifies if the table-valued parameter (i.e. param_z) provided by the user is valid.
    // The number of columns in the given table-valued parameter is returned, which may be zero.
    HashTable* inputs_ht = Z_ARRVAL_P(param_z);
    zend_string *tvp_name = NULL;
    zend_string *schema_name = NULL;
    zval *tvp_data_z = NULL;
    HashPosition pos;
    
    zend_hash_internal_pointer_reset_ex(inputs_ht, &pos);
    if (zend_hash_has_more_elements_ex(inputs_ht, &pos) == SUCCESS) {

        zend_ulong num_index = -1;
        size_t key_len = 0;

        int key_type = zend_hash_get_current_key(inputs_ht, &tvp_name, &num_index);
        if (key_type == HASH_KEY_IS_STRING) {
            key_len = ZSTR_LEN(tvp_name);
            tvp_data_z = zend_hash_get_current_data_ex(inputs_ht, &pos);
        } 

        CHECK_CUSTOM_ERROR((key_type == HASH_KEY_IS_LONG || key_len == 0), stmt, SQLSRV_ERROR_TVP_INVALID_TABLE_TYPE_NAME, param_pos + 1) {
            throw core::CoreException();
        }
    } 

    // TODO: Find the docs page somewhere that says a TVP can not be null but it may have null columns??
    CHECK_CUSTOM_ERROR(tvp_data_z == NULL || Z_TYPE_P(tvp_data_z) == IS_NULL || Z_TYPE_P(tvp_data_z) != IS_ARRAY, stmt, SQLSRV_ERROR_TVP_INVALID_INPUTS, param_pos + 1) {
        throw core::CoreException();
    }

    // Save the TVP type name for SQLSetDescField later
    buffer = ZSTR_VAL(tvp_name);
    buffer_length = SQL_NTS;
    
    // Check if schema is provided by the user
    if (zend_hash_move_forward_ex(inputs_ht, &pos) == SUCCESS) {
        zval *schema_z = zend_hash_get_current_data_ex(inputs_ht, &pos);
        if (schema_z != NULL && Z_TYPE_P(schema_z) == IS_STRING) {
            schema_name = Z_STR_P(schema_z);
            ZVAL_NEW_STR(&placeholder_z, schema_name);
        }
    }

    // Save the TVP multi-dim array data, which should be something like this
    // [ 
    //   [r1c1, r1c2, r1c3],
    //   [r2c1, r2c2, r2c3],
    //   [r3c1, r3c2, r3c3]
    // ]
    param_ptr_z = tvp_data_z;
    HashTable* rows_ht = Z_ARRVAL_P(tvp_data_z);
    this->num_rows = zend_hash_num_elements(rows_ht);
    if (this->num_rows == 0) {
        // TVP has no data
        return 0;
    }

    // Given the table type name, get its column meta data next
    size_t total_num_columns = 0;
    get_tvp_metadata(stmt, tvp_name, schema_name);
    total_num_columns = tvp_columns.size();

    // (1) Is the array empty?
    // (2) Check individual rows and see if their sizes are consistent?
    zend_ulong id = -1;
    zend_string *key = NULL;
    zval* row_z = NULL;
    int num_columns = 0;
    int type = HASH_KEY_NON_EXISTENT;

    // Loop through the rows to check the number of columns
    ZEND_HASH_FOREACH_KEY_VAL(rows_ht, id, key, row_z) {
        type = key ? HASH_KEY_IS_STRING : HASH_KEY_IS_LONG;
        CHECK_CUSTOM_ERROR(type == HASH_KEY_IS_STRING, stmt, SQLSRV_ERROR_TVP_STRING_KEYS, param_pos + 1) {
            throw core::CoreException();
        }

        if (Z_ISREF_P(row_z)) {
            ZVAL_DEREF(row_z);
        }

        // Individual row must be an array
        CHECK_CUSTOM_ERROR(Z_TYPE_P(row_z) != IS_ARRAY, stmt, SQLSRV_ERROR_TVP_ROW_NOT_ARRAY, param_pos + 1) {
            throw core::CoreException();
        }

        // Are all the TVP's rows the same size
        num_columns = zend_hash_num_elements(Z_ARRVAL_P(row_z));
        CHECK_CUSTOM_ERROR(num_columns != total_num_columns, stmt, SQLSRV_ERROR_TVP_ROWS_UNEXPECTED_SIZE, param_pos + 1, total_num_columns) {
            throw core::CoreException();
        }
    } ZEND_HASH_FOREACH_END();

    // Return the number of columns
    return num_columns;
}

void sqlsrv_param_tvp::process_param_column_value(_Inout_ sqlsrv_stmt* stmt)
{
    // This is one of the constituent columns of the table-valued parameter
    // The corresponding column value of the TVP's first row is already saved in 
    // the member variable param_ptr_z, which may be a NULL value
    zval *data_z = param_ptr_z;
    param_php_type = is_a_string_type(sql_data_type) ? IS_STRING : Z_TYPE_P(data_z);

    switch (param_php_type) {
    case IS_TRUE:
    case IS_FALSE:
    case IS_LONG:
    case IS_DOUBLE:
        sqlsrv_param::process_param(stmt, data_z);
        buffer = &placeholder_z.value;      // use placeholder zval for binding later
        break;
    case IS_RESOURCE:
        sqlsrv_param::process_resource_param(data_z);
        break;
    case IS_STRING:
    case IS_OBJECT:
        if (param_php_type == IS_STRING) {
            derive_string_types_sizes(data_z);
        } else {
            // If preprocessing a datetime object fails, throw an error of invalid php type
            bool succeeded = preprocess_datetime_object(stmt, data_z);
            CHECK_CUSTOM_ERROR(!succeeded, stmt, SQLSRV_ERROR_TVP_INVALID_COLUMN_PHPTYPE, parent_tvp->param_pos + 1, param_pos + 1) {
                throw core::CoreException();
            }
        }
        buffer = reinterpret_cast<SQLPOINTER>(this);
        buffer_length = 0;
        strlen_or_indptr = SQL_DATA_AT_EXEC;
        break;
    case IS_NULL:
        process_null_param_value(stmt);
        break;
    default:
        THROW_CORE_ERROR(stmt, SQLSRV_ERROR_TVP_INVALID_COLUMN_PHPTYPE, parent_tvp->param_pos + 1, param_pos + 1);
        break;
    }

    // Release the reference
    param_ptr_z = NULL;
}

void sqlsrv_param_tvp::process_null_param_value(_Inout_ sqlsrv_stmt* stmt)
{
    // This is one of the constituent columns of the table-valued parameter
    // This method is called when the corresponding column value of the TVP's first row is NULL
    // So keep looking in the subsequent rows and find the first non-NULL value in the same column
    HashTable* rows_ht = Z_ARRVAL_P(parent_tvp->param_ptr_z);
    zval* row_z = NULL;
    zval* value_z = NULL;
    int php_type = IS_NULL;
    int row_id = 1;     // Start from the second row

    while ((row_z = zend_hash_index_find(rows_ht, row_id++)) != NULL) {
        if (Z_ISREF_P(row_z)) {
            ZVAL_DEREF(row_z);
        }

        value_z = zend_hash_index_find(Z_ARRVAL_P(row_z), param_pos);
        php_type = Z_TYPE_P(value_z);
        if (php_type != IS_NULL) {
            // Save this non-NULL value before calling process_param_column_value()
            param_ptr_z = value_z;
            process_param_column_value(stmt);
            break;
        }
    }

    if (php_type == IS_NULL) {
        // This means that the entire column contains nothing but NULLs
        sqlsrv_param::process_null_param(param_ptr_z);
    }
}

void sqlsrv_param_tvp::bind_param(_Inout_ sqlsrv_stmt* stmt)
{
    core::SQLBindParameter(stmt, param_pos + 1, direction, c_data_type, sql_data_type, column_size, decimal_digits, buffer, buffer_length, &strlen_or_indptr);

    // No need to continue if this is one of the constituent columns of the table-valued parameter
    if (sql_data_type != SQL_SS_TABLE) {
        return;
    }

    if (num_rows == 0) {
        // TVP has no data
        return;
    }

    // Set Table-Valued parameter type name (and the schema where it is defined)
    SQLHDESC hIpd = NULL;
    core::SQLGetStmtAttr(stmt, SQL_ATTR_IMP_PARAM_DESC, &hIpd, 0, 0);

    if (buffer != NULL) {
        // SQL_CA_SS_TYPE_NAME is optional for stored procedure calls, but it must be 
        // specified for SQL statements that are not procedure calls to enable the 
        // server to determine the type of the table-valued parameter.
        char *tvp_name = reinterpret_cast<char *>(buffer);
        SQLRETURN r = ::SQLSetDescField(hIpd, param_pos + 1, SQL_CA_SS_TYPE_NAME, reinterpret_cast<SQLCHAR*>(tvp_name), SQL_NTS);
        CHECK_SQL_ERROR_OR_WARNING(r, stmt) {
            throw core::CoreException();
        }
    }
    if (Z_TYPE(placeholder_z) == IS_STRING) {
        // If the table type for the table-valued parameter is defined in a different 
        // schema than the default, SQL_CA_SS_SCHEMA_NAME must be specified. If not, 
        // the server will not be able to determine the type of the table-valued parameter.
        char * schema_name = Z_STRVAL(placeholder_z);
        SQLRETURN r = ::SQLSetDescField(hIpd, param_pos + 1, SQL_CA_SS_SCHEMA_NAME, reinterpret_cast<SQLCHAR*>(schema_name), SQL_NTS);
        CHECK_SQL_ERROR_OR_WARNING(r, stmt) {
            throw core::CoreException();
        }
        // Free and reset the placeholder_z
        zend_string_release(Z_STR(placeholder_z));
        ZVAL_UNDEF(&placeholder_z);
    }

    // Bind the TVP columns one by one
    // Register this object first using SQLSetDescField() for sending TVP data post execution
    SQLHDESC desc;
    core::SQLGetStmtAttr(stmt, SQL_ATTR_APP_PARAM_DESC, &desc, 0, 0);
    SQLRETURN r = ::SQLSetDescField(desc, param_pos + 1, SQL_DESC_DATA_PTR, reinterpret_cast<SQLPOINTER>(this), 0);
    CHECK_SQL_ERROR_OR_WARNING(r, stmt) {
        throw core::CoreException();
    }

    // First set focus on this parameter
    size_t ordinal = param_pos + 1;
    core::SQLSetStmtAttr(stmt, SQL_SOPT_SS_PARAM_FOCUS, reinterpret_cast<SQLPOINTER>(ordinal), SQL_IS_INTEGER);

    // Bind the TVP columns
    HashTable* rows_ht = Z_ARRVAL_P(param_ptr_z);
    zval* row_z = zend_hash_index_find(rows_ht, 0);

    if (Z_ISREF_P(row_z)) {
        ZVAL_DEREF(row_z);
    }

    HashTable* cols_ht = Z_ARRVAL_P(row_z);
    zend_ulong id = -1;
    zend_string *key = NULL;
    zval* data_z = NULL;
    int num_columns = 0;

    // In case there are null values in the first row, have to loop 
    // through the entire first row of column values using the Zend macros. 
    ZEND_HASH_FOREACH_KEY_VAL(cols_ht, id, key, data_z) {
        int type = key ? HASH_KEY_IS_STRING : HASH_KEY_IS_LONG;
        CHECK_CUSTOM_ERROR(type == HASH_KEY_IS_STRING, stmt, SQLSRV_ERROR_TVP_STRING_KEYS, param_pos + 1) {
            throw core::CoreException();
        }

        // Assume the user has supplied data for all columns in the right order
        SQLUSMALLINT pos = static_cast<SQLUSMALLINT>(id);
        sqlsrv_param* column_param = tvp_columns[pos];
        SQLSRV_ASSERT(column_param != NULL, "sqlsrv_param_tvp::bind_param -- column param should not be null");

        // If data_z is NULL, will need to keep looking in the subsequent rows of 
        // the same column until a non-null value is found. Since Zend macros must be 
        // used to traverse the array items, nesting Zend macros in different directions
        // does not work.
        // Therefore, save data_z for later processing and binding.
        column_param->param_ptr_z = data_z;
        num_columns++;
    } ZEND_HASH_FOREACH_END();

    // Process the columns and bind each of them using the saved data
    for (int i = 0; i < num_columns; i++) {
        sqlsrv_param* column_param = tvp_columns[i];

        column_param->process_param(stmt, NULL);
        column_param->bind_param(stmt);
    }

    // Reset focus
    core::SQLSetStmtAttr(stmt, SQL_SOPT_SS_PARAM_FOCUS, reinterpret_cast<SQLPOINTER>(0), SQL_IS_INTEGER);
}

// For each of the constituent columns of the table-valued parameter, check its PHP type
// For pure scalar types, map the cell value (based on current_row and ordinal) to the
// member placeholder_z
void sqlsrv_param_tvp::populate_cell_placeholder(_Inout_ sqlsrv_stmt* stmt, _In_ int ordinal)
{
    if (sql_data_type == SQL_SS_TABLE || ordinal >= num_rows) {
        return;
    }

    zval* row_z = NULL;
    HashTable* values_ht = NULL;
    zval* value_z = NULL;
    int type = IS_NULL;

    switch (param_php_type) {
    case IS_TRUE:
    case IS_FALSE:
    case IS_LONG:
    case IS_DOUBLE:
        // Find the row from the TVP data based on ordinal
        row_z = zend_hash_index_find(Z_ARRVAL_P(parent_tvp->param_ptr_z), ordinal);
        if (Z_ISREF_P(row_z)) {
            ZVAL_DEREF(row_z);
        }
        // Now find the column value based on param_pos
        value_z = zend_hash_index_find(Z_ARRVAL_P(row_z), param_pos);
        type = Z_TYPE_P(value_z);

        // First check if value_z is NULL
        if (type == IS_NULL) {
            ZVAL_NULL(&placeholder_z);
            strlen_or_indptr = SQL_NULL_DATA;
        } else {
            // Once the placeholder is bound with the correct value from the array, update current_row
            if (param_php_type == IS_DOUBLE) {
                if (type != IS_DOUBLE) {
                    // If value_z type is different from param_php_type convert first
                    convert_to_double(value_z);
                }
                strlen_or_indptr = sizeof(Z_DVAL_P(value_z));
                ZVAL_DOUBLE(&placeholder_z, Z_DVAL_P(value_z));
            } else {
                if (type != IS_LONG) {
                    // If value_z type is different from param_php_type convert first
                    // Even for boolean values
                    convert_to_long(value_z);
                }
                strlen_or_indptr = sizeof(Z_LVAL_P(value_z));
                ZVAL_LONG(&placeholder_z, Z_LVAL_P(value_z));
            }
        }
        current_row++;
        break;
    default:
        // Do nothing for non-scalar types
        break;
    }
}

// If this is the table-valued parameter, loop through each parameter column 
// and populate the cell's placeholder_z.
// If this is one of the constituent columns of the table-valued parameter, 
// call SQLPutData() to send the cell value to the server (based on current_row 
// and param_pos)
bool sqlsrv_param_tvp::send_data_packet(_Inout_ sqlsrv_stmt* stmt)
{
    if (sql_data_type != SQL_SS_TABLE) {
        // This is one of the constituent columns of the table-valued parameter
        // Check current_row first
        if (current_row >= num_rows) {
            return false;
        }

        // Find the row from the TVP data based on current_row
        zval* row_z = zend_hash_index_find(Z_ARRVAL_P(parent_tvp->param_ptr_z), current_row);
        if (Z_ISREF_P(row_z)) {
            ZVAL_DEREF(row_z);
        }
        // Now find the column value based on param_pos
        zval* value_z = zend_hash_index_find(Z_ARRVAL_P(row_z), param_pos);

        // First check if value_z is NULL
        if (Z_TYPE_P(value_z) == IS_NULL) {
            core::SQLPutData(stmt, NULL, SQL_NULL_DATA);
            current_row++;
        } else {
            switch (param_php_type) {
            case IS_RESOURCE:
                {
                    num_bytes_read = 0;
                    param_stream = NULL;

                    // Get the stream from the zval value
                    core::sqlsrv_php_stream_from_zval_no_verify(*stmt, param_stream, value_z);
                    // Keep sending the packets until EOF is reached
                    while (sqlsrv_param::send_data_packet(stmt)) {
                    }
                    current_row++;
                }
                break;
            case IS_OBJECT:
                {
                    // This method updates placeholder_z as a string
                    bool succeeded = convert_datetime_to_string(stmt, value_z);

                    // Conversion failed so assume the input was an invalid PHP type
                    CHECK_CUSTOM_ERROR(!succeeded, stmt, SQLSRV_ERROR_TVP_INVALID_COLUMN_PHPTYPE, parent_tvp->param_pos + 1, param_pos + 1) {
                        throw core::CoreException();
                    }

                    core::SQLPutData(stmt, Z_STRVAL(placeholder_z), SQL_NTS);
                    current_row++;
                }
                break;
            case IS_STRING:
                {
                    int type = Z_TYPE_P(value_z);
                    if (type != IS_STRING) {
                        convert_to_string(value_z);
                    }
                    SQLLEN value_len = Z_STRLEN_P(value_z);
                    if (value_len == 0) {
                        // If it's an empty string
                        core::SQLPutData(stmt, Z_STRVAL_P(value_z), 0);
                    } else {
                        if (encoding == CP_UTF8 && !is_a_numeric_type(sql_data_type)) {
                            if (value_len > INT_MAX) {
                                LOG(SEV_ERROR, "Convert input parameter to utf16: buffer length exceeded.");
                                throw core::CoreException();
                            }
                            // This method would change the member placeholder_z
                            bool succeeded = convert_input_str_to_utf16(stmt, value_z);
                            CHECK_CUSTOM_ERROR(!succeeded, stmt, SQLSRV_ERROR_TVP_STRING_ENCODING_TRANSLATE, parent_tvp->param_pos + 1, param_pos + 1, get_last_error_message()) {
                                throw core::CoreException();
                            }

                            send_string_data_in_batches(stmt, &placeholder_z);
                        } else {
                            send_string_data_in_batches(stmt, value_z);
                        }
                    }
                    current_row++;
                }
                break;
            default:
                // Do nothing for basic types as they should be processed elsewhere
                break;
            }
        } // else not IS_NULL
    } else {
        // This is the table-valued parameter
        if (current_row < num_rows) {
            // Loop through the table parameter columns and populate each cell's placeholder whenever applicable
            for (size_t i = 0; i < tvp_columns.size(); i++) {
                tvp_columns[i]->populate_cell_placeholder(stmt, current_row);
            }

            // This indicates a TVP row is available
            core::SQLPutData(stmt, reinterpret_cast<SQLPOINTER>(1), 1);
            current_row++;
        } else {
            // This indicates there is no more TVP row
            core::SQLPutData(stmt, reinterpret_cast<SQLPOINTER>(0), 0);
        }
    }

    // Return false to indicate that the current row has been sent
    return false;
}

// A helper method for sending large string data in batches
void sqlsrv_param_tvp::send_string_data_in_batches(_Inout_ sqlsrv_stmt* stmt, _In_ zval* value_z)
{
    SQLLEN len = Z_STRLEN_P(value_z);
    SQLLEN batch = (encoding == CP_UTF8) ? PHP_STREAM_BUFFER_SIZE / sizeof(SQLWCHAR) : PHP_STREAM_BUFFER_SIZE;

    char* p = Z_STRVAL_P(value_z);
    while (len > batch) {
        core::SQLPutData(stmt, p, batch);
        len -= batch;
        p += batch;
    }

    // Put final batch
    core::SQLPutData(stmt, p, len);
}

void sqlsrv_params_container::clean_up_param_data(_In_opt_ bool only_input/* = false*/)
{
    current_param = NULL;
    remove_params(input_params);
    if (!only_input) {
        remove_params(output_params);
    }
}

// To be called after all results are processed. ODBC and SQL Server do not guarantee that all output
// parameters will be present until all results are processed (since output parameters can depend on results
// while being processed). This function updates the lengths of output parameter strings from the strlen_or_indptr
// argument passed to SQLBindParameter. It also converts output strings from UTF-16 to UTF-8 if necessary.
// If a NULL was returned by SQL Server to any output parameter, set the parameter to NULL as well
void sqlsrv_params_container::finalize_output_parameters()
{
    std::map<SQLUSMALLINT, sqlsrv_param*>::iterator it;
    for (it = output_params.begin(); it != output_params.end(); ++it) {
        sqlsrv_param_inout* ptr = dynamic_cast<sqlsrv_param_inout*>(it->second);
        if (ptr) {
            ptr->finalize_output_value();
        }
    }
}

sqlsrv_param* sqlsrv_params_container::find_param(_In_ SQLUSMALLINT param_num, _In_ bool is_input)
{
    try {
        if (is_input) {
            return input_params.at(param_num);
        } else {
            return output_params.at(param_num);
        }
    } catch (std::out_of_range&) {
        // not found
        return NULL;
    }
}

bool sqlsrv_params_container::get_next_parameter(_Inout_ sqlsrv_stmt* stmt)
{
    // Get the param ptr when binding the resource parameter
    SQLPOINTER param = NULL;
    SQLRETURN r = core::SQLParamData(stmt, &param);

    // If no more data, all the bound parameters have been exhausted, so return false (done)
    if (SQL_SUCCEEDED(r) || r == SQL_NO_DATA) {
        // Done now, reset current_param 
        current_param = NULL;
        return false;
    } else if (r == SQL_NEED_DATA) {
        if (param != NULL) {
            current_param = reinterpret_cast<sqlsrv_param*>(param);
            SQLSRV_ASSERT(current_param != NULL, "sqlsrv_params_container::get_next_parameter - The parameter requested is missing!");
            current_param->init_data_from_zval(stmt);
        } else {
            // Do not reset current_param when param is NULL, because 
            // it means that data is expected from the existing current_param
        }
    }

    return true;
}

// The following helper method sends one stream packet at a time, if available
bool sqlsrv_params_container::send_next_packet(_Inout_ sqlsrv_stmt* stmt)
{
    if (current_param == NULL) {
        // If current_stream is NULL, either this is the first time checking or the previous parameter
        // is done. In either case, MUST call get_next_parameter() to see if there is any more
        // parameter requested by ODBC. Otherwise, "Function sequence error" will result, meaning the
        // ODBC functions are called out of the order required by the ODBC Specification
        if (get_next_parameter(stmt) == false) {
            return false;
        }
    }

    // The helper method send_stream_packet() returns false when EOF is reached
    if (current_param && current_param->send_data_packet(stmt) == false) {
        // Now that EOF has been reached, reset current_param for next round 
        // Bear in mind that SQLParamData might request the same stream resource again
        current_param = NULL;
    }

    // Returns true regardless such that either get_next_parameter() will be called or next packet will be sent
    return true;
}

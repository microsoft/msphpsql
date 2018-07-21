//---------------------------------------------------------------------------------------------------------------------------------
// File: stmt.cpp
//
// Contents: Routines that use statement handles
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

// *** header files ***
#include "php_sqlsrv.h"
#ifdef _WIN32
#include <sal.h>
#endif // _WIN32

//
// *** internal variables and constants ***
//
// our resource descriptor assigned in minit
int ss_sqlsrv_stmt::descriptor = 0;
const char* ss_sqlsrv_stmt::resource_name = "ss_sqlsrv_stmt";

namespace {

// current subsytem.  defined for the CHECK_SQL_{ERROR|WARNING} macros
unsigned int current_log_subsystem = LOG_STMT;

// constants used as invalid types for type errors
const zend_uchar PHPTYPE_INVALID = SQLSRV_PHPTYPE_INVALID;
const int SQLTYPE_INVALID = 0;
const int SQLSRV_INVALID_PRECISION = -1;
const SQLUINTEGER SQLSRV_INVALID_SIZE = (~1U);
const int SQLSRV_INVALID_SCALE = -1;
const int SQLSRV_SIZE_MAX_TYPE = -1;

// constants for maximums in SQL Server
const int SQL_SERVER_MAX_FIELD_SIZE = 8000;
const int SQL_SERVER_MAX_PRECISION = 38;
const int SQL_SERVER_DEFAULT_PRECISION = 18;
const int SQL_SERVER_DEFAULT_SCALE = 0;

// default class used when no class is specified by sqlsrv_fetch_object
const char STDCLASS_NAME[] = "stdclass";
const char STDCLASS_NAME_LEN = sizeof( STDCLASS_NAME ) - 1;

// map a Zend PHP type constant to our constant type
enum SQLSRV_PHPTYPE zend_to_sqlsrv_phptype[] = {
	SQLSRV_PHPTYPE_INVALID,
    SQLSRV_PHPTYPE_NULL,
	SQLSRV_PHPTYPE_INVALID,
	SQLSRV_PHPTYPE_INVALID,
    SQLSRV_PHPTYPE_INT,
    SQLSRV_PHPTYPE_FLOAT,
	SQLSRV_PHPTYPE_STRING,
    SQLSRV_PHPTYPE_INVALID,
    SQLSRV_PHPTYPE_DATETIME,
    SQLSRV_PHPTYPE_STREAM,
    SQLSRV_PHPTYPE_INVALID,
	SQLSRV_PHPTYPE_INVALID,
    SQLSRV_PHPTYPE_INVALID
};

// constant strings used for the field metadata results
// (char to avoid having to cast them where they are used)
namespace FieldMetaData {

const char* NAME = "Name";
const char* TYPE = "Type";
const char* SIZE = "Size";
const char* PREC = "Precision";
const char* SCALE = "Scale";
const char* NULLABLE = "Nullable";

}

// warning message printed when a parameter variable is not passed by reference 
const char SS_SQLSRV_WARNING_PARAM_VAR_NOT_REF[] = "Variable parameter %d not passed by reference (prefaced with an &).  "
    "Variable parameters passed to sqlsrv_prepare or sqlsrv_query should be passed by reference, not by value.  "
    "For more information, see sqlsrv_prepare or sqlsrv_query in the API Reference section of the product documentation.";

/* internal functions */

void convert_to_zval( _Inout_ sqlsrv_stmt* stmt, _In_ SQLSRV_PHPTYPE sqlsrv_php_type, _In_opt_ void* in_val, _In_ SQLLEN field_len, _Inout_ zval& out_zval );

void fetch_fields_common( _Inout_ ss_sqlsrv_stmt* stmt, _In_ zend_long fetch_type, _Out_ zval& fields, _In_ bool allow_empty_field_names
						TSRMLS_DC );
bool determine_column_size_or_precision( sqlsrv_stmt const* stmt, _In_ sqlsrv_sqltype sqlsrv_type, _Inout_ SQLULEN* column_size,
 _Out_ SQLSMALLINT* decimal_digits );
sqlsrv_phptype determine_sqlsrv_php_type( sqlsrv_stmt const* stmt, SQLINTEGER sql_type, SQLUINTEGER size, bool prefer_string );
void determine_stmt_has_rows( _Inout_ ss_sqlsrv_stmt* stmt TSRMLS_DC );
bool is_valid_sqlsrv_phptype( _In_ sqlsrv_phptype type );
bool is_valid_sqlsrv_sqltype( _In_ sqlsrv_sqltype type );
void parse_param_array( _Inout_ ss_sqlsrv_stmt* stmt, _Inout_ zval* param_array, zend_ulong index, _Out_ SQLSMALLINT& direction,
                        _Out_ SQLSRV_PHPTYPE& php_out_type, _Out_ SQLSRV_ENCODING& encoding, _Out_ SQLSMALLINT& sql_type, 
                        _Out_ SQLULEN& column_size, _Out_ SQLSMALLINT& decimal_digits TSRMLS_DC );
void type_and_encoding( INTERNAL_FUNCTION_PARAMETERS, _In_ int type );
void type_and_size_calc( INTERNAL_FUNCTION_PARAMETERS, _In_ int type );
void type_and_precision_calc( INTERNAL_FUNCTION_PARAMETERS, _In_ int type );
bool verify_and_set_encoding( _In_ const char* encoding_string, _Inout_ sqlsrv_phptype& phptype_encoding TSRMLS_DC );

}

// query options for cursor types
namespace SSCursorTypes {

    const char QUERY_OPTION_SCROLLABLE_STATIC[] = "static";
    const char QUERY_OPTION_SCROLLABLE_DYNAMIC[] = "dynamic";
    const char QUERY_OPTION_SCROLLABLE_KEYSET[] = "keyset";
    const char QUERY_OPTION_SCROLLABLE_FORWARD[] = "forward";
    const char QUERY_OPTION_SCROLLABLE_BUFFERED[] = "buffered";
}

ss_sqlsrv_stmt::ss_sqlsrv_stmt( _In_ sqlsrv_conn* c, _In_ SQLHANDLE handle, _In_ error_callback e, _In_ void* drv TSRMLS_DC ) :
    sqlsrv_stmt( c, handle, e, drv TSRMLS_CC ),
    prepared( false ),
    conn_index( -1 ),
    params_z( NULL ),
    fetch_field_names( NULL ),
    fetch_fields_count ( 0 )
{
    core_sqlsrv_set_buffered_query_limit( this, SQLSRV_G( buffered_query_limit ) TSRMLS_CC );
}

ss_sqlsrv_stmt::~ss_sqlsrv_stmt( void )
{
    if( fetch_field_names != NULL ) {

        for( int i=0; i < fetch_fields_count; ++i ) {
            
            sqlsrv_free( fetch_field_names[ i ].name );
        }
        sqlsrv_free( fetch_field_names );
    }
    if( params_z ) {
        zval_ptr_dtor( params_z );
		sqlsrv_free(params_z);
    }
}    

// to be called whenever a new result set is created, such as after an
// execute or next_result.  Resets the state variables and calls the subclass.
void ss_sqlsrv_stmt::new_result_set( TSRMLS_D ) 
{
    if( fetch_field_names != NULL ) {

        for( int i=0; i < fetch_fields_count; ++i ) {
            
            sqlsrv_free( fetch_field_names[ i ].name );
        }
        sqlsrv_free( fetch_field_names );
    }

    fetch_field_names = NULL;
    fetch_fields_count = 0;
    sqlsrv_stmt::new_result_set( TSRMLS_C );
}

// Returns a php type for a given sql type. Also sets the encoding wherever applicable. 
sqlsrv_phptype ss_sqlsrv_stmt::sql_type_to_php_type( _In_ SQLINTEGER sql_type, _In_ SQLUINTEGER size, _In_ bool prefer_string_to_stream )
{
    sqlsrv_phptype ss_phptype;
    ss_phptype.typeinfo.type = SQLSRV_PHPTYPE_INVALID;
    ss_phptype.typeinfo.encoding = SQLSRV_ENCODING_INVALID;

    switch( sql_type ) {

        case SQL_BIGINT:
        case SQL_CHAR:
        case SQL_DECIMAL:
        case SQL_GUID:
        case SQL_NUMERIC:
        case SQL_WCHAR:
        case SQL_SS_VARIANT:
            ss_phptype.typeinfo.type = SQLSRV_PHPTYPE_STRING;
            ss_phptype.typeinfo.encoding = this->conn->encoding();
            break;

        case SQL_VARCHAR:
        case SQL_WVARCHAR:
        case SQL_LONGVARCHAR:
        case SQL_WLONGVARCHAR:
        case SQL_SS_XML:
            if( prefer_string_to_stream || size != SQL_SS_LENGTH_UNLIMITED ) {
                ss_phptype.typeinfo.type = SQLSRV_PHPTYPE_STRING;
                ss_phptype.typeinfo.encoding = this->conn->encoding();
            }
            else {
                ss_phptype.typeinfo.type = SQLSRV_PHPTYPE_STREAM;
                ss_phptype.typeinfo.encoding = this->conn->encoding();
            }
            break;

        case SQL_BIT:
        case SQL_INTEGER:
        case SQL_SMALLINT:
        case SQL_TINYINT:
            ss_phptype.typeinfo.type = SQLSRV_PHPTYPE_INT;
            break;

        case SQL_BINARY:
        case SQL_LONGVARBINARY:
        case SQL_VARBINARY:
        case SQL_SS_UDT:
            if( prefer_string_to_stream ) {
                ss_phptype.typeinfo.type = SQLSRV_PHPTYPE_STRING;
                ss_phptype.typeinfo.encoding = SQLSRV_ENCODING_BINARY;
            }
            else {
                ss_phptype.typeinfo.type = SQLSRV_PHPTYPE_STREAM;
                ss_phptype.typeinfo.encoding = SQLSRV_ENCODING_BINARY;
            }
            break;

        case SQL_FLOAT:
        case SQL_REAL:
            ss_phptype.typeinfo.type = SQLSRV_PHPTYPE_FLOAT;
            break;

        case SQL_TYPE_DATE:
        case SQL_SS_TIMESTAMPOFFSET:
        case SQL_SS_TIME2:
        case SQL_TYPE_TIMESTAMP:
            if( reinterpret_cast<ss_sqlsrv_conn*>( this->conn )->date_as_string ) { 
                ss_phptype.typeinfo.type = SQLSRV_PHPTYPE_STRING;
                ss_phptype.typeinfo.encoding = this->conn->encoding();
            }
            else {
                ss_phptype.typeinfo.type = SQLSRV_PHPTYPE_DATETIME;
            }
            break;

        default:
            ss_phptype.typeinfo.type = SQLSRV_PHPTYPE_INVALID;
            break;
    }

    return ss_phptype;
}

// statement specific parameter proccessing.  Uses the generic function specialised to return a statement
// resource.
#define PROCESS_PARAMS( rsrc, param_spec, calling_func, param_count, ... )                                                        \
    rsrc = process_params<ss_sqlsrv_stmt>( INTERNAL_FUNCTION_PARAM_PASSTHRU, param_spec, calling_func, param_count, ## __VA_ARGS__ );\
    if( rsrc == NULL ) {                                                                                                          \
        RETURN_FALSE;                                                                                                             \
    }

// sqlsrv_execute( resource $stmt )
// 
// Executes a previously prepared statement. See sqlsrv_prepare for information
// on preparing a statement for execution.
// 
// This function is ideal for executing a prepared statement multiple times with
// different parameter values.  See the MSDN documentation
// 
// Parameters
// $stmt: A resource specifying the statement to be executed. For more
// information about how to create a statement resource, see sqlsrv_prepare.
//
// Return Value
// A Boolean value: true if the statement was successfully executed. Otherwise, false.

PHP_FUNCTION( sqlsrv_execute )
{
    LOG_FUNCTION( "sqlsrv_execute" );
    
    ss_sqlsrv_stmt* stmt = NULL;
    
    try {

        PROCESS_PARAMS( stmt, "r", _FN_, 0 );    
        CHECK_CUSTOM_ERROR(( !stmt->prepared ), stmt, SS_SQLSRV_ERROR_STATEMENT_NOT_PREPARED ) {
            throw ss::SSException();
        }

        // prepare for the next execution by flushing anything remaining in the result set
        if( stmt->executed ) {

            // to prepare to execute the next statement, we skip any remaining results (and skip parameter finalization too)
            while( stmt->past_next_result_end == false ) {

                core_sqlsrv_next_result( stmt TSRMLS_CC, false, false );
            }
        }

        // bind parameters before executing
        bind_params( stmt TSRMLS_CC );

        core_sqlsrv_execute( stmt TSRMLS_CC );
		
        RETURN_TRUE;
    }
    catch( core::CoreException& ) {
        
        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_execute: Unknown exception caught." );
    }
}


// sqlsrv_fetch( resource $stmt )
//
// Makes the next row of a result set available for reading. Use
// sqlsrv_get_field to read fields of the row.
//
// Parameters
// $stmt: A statement resource corresponding to an executed statement.  A
// statement must be executed before results can be retrieved. For information
// on executing a statement, see sqlsrv_query and sqlsrv_execute.
//
// Return Value
// If the next row of the result set was successfully retrieved, true is
// returned. If there are no more results in the result set, null is
// returned. If an error occured, false is returned

PHP_FUNCTION( sqlsrv_fetch )
{
    LOG_FUNCTION( "sqlsrv_fetch" );

    ss_sqlsrv_stmt* stmt = NULL;
    // NOTE: zend_parse_parameter expect zend_long when the type spec is 'l',and core_sqlsrv_fetch expect short int
	zend_long fetch_style = SQL_FETCH_NEXT;   // default value for parameter if one isn't supplied
    zend_long fetch_offset = 0;               // default value for parameter if one isn't supplied

    // take only the statement resource
    PROCESS_PARAMS( stmt, "r|ll", _FN_, 2, &fetch_style, &fetch_offset );

    try {
    
        CHECK_CUSTOM_ERROR(( fetch_style < SQL_FETCH_NEXT || fetch_style > SQL_FETCH_RELATIVE ), stmt, 
                           SS_SQLSRV_ERROR_INVALID_FETCH_STYLE ) {
            throw ss::SSException();
        }

        bool result = core_sqlsrv_fetch( stmt, static_cast<SQLSMALLINT>(fetch_style), fetch_offset TSRMLS_CC );
        if( !result ) {
            RETURN_NULL();
        }

        RETURN_TRUE;
    }

    catch( core::CoreException& ) {
        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_fetch: Unknown exception caught." );
    }
}

// sqlsrv_fetch_array( resource $stmt [, int $fetchType] )
// 
// Retrieves the next row of data as an array.
//
// Parameters
// $stmt: A statement resource corresponding to an executed statement.
// $fetchType [OPTIONAL]: A predefined constant. See SQLSRV_FETCH_TYPE in php_sqlsrv.h
//
// Return Value
// If a row of data is retrieved, an array is returned. If there are no more
// rows to retrieve, null is returned. If an error occurs, false is returned.
// Based on the value of the $fetchType parameter, the returned array can be a
// numerically indexed array, an associative array, or both. By default, an
// array with both numeric and associative keys is returned. The data type of a
// value in the returned array will be the default PHP data type. For
// information about default PHP data types, see Default PHP Data Types.

PHP_FUNCTION( sqlsrv_fetch_array )
{
    LOG_FUNCTION( "sqlsrv_fetch_array" );
    
    ss_sqlsrv_stmt* stmt = NULL;
    zend_long fetch_type = SQLSRV_FETCH_BOTH; // default value for parameter if one isn't supplied
	zend_long fetch_style = SQL_FETCH_NEXT;   // default value for parameter if one isn't supplied
    zend_long fetch_offset = 0;               // default value for parameter if one isn't supplied

    // retrieve the statement resource and optional fetch type (see enum SQLSRV_FETCH_TYPE),
    // fetch style (see SQLSRV_SCROLL_* constants) and fetch offset
    PROCESS_PARAMS( stmt, "r|lll", _FN_, 3, &fetch_type, &fetch_style, &fetch_offset );

    try {
    
        CHECK_CUSTOM_ERROR(( fetch_type < MIN_SQLSRV_FETCH || fetch_type > MAX_SQLSRV_FETCH ), stmt, 
                           SS_SQLSRV_ERROR_INVALID_FETCH_TYPE ) {
            throw ss::SSException();
        }

        CHECK_CUSTOM_ERROR(( fetch_style < SQL_FETCH_NEXT || fetch_style > SQL_FETCH_RELATIVE ), stmt, 
                           SS_SQLSRV_ERROR_INVALID_FETCH_STYLE ) {
            throw ss::SSException();
        }

        bool result = core_sqlsrv_fetch( stmt, static_cast<SQLSMALLINT>(fetch_style), fetch_offset TSRMLS_CC );
        if( !result ) {
            RETURN_NULL();
        }
		zval fields;
		ZVAL_UNDEF( &fields );
        fetch_fields_common( stmt, fetch_type, fields, true /*allow_empty_field_names*/ TSRMLS_CC );
		RETURN_ARR( Z_ARRVAL( fields ));
    }

    catch( core::CoreException& ) {
        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_fetch_array: Unknown exception caught." );
    }
}

// sqlsrv_field_metadata( resource $stmt )
// 
// Retrieves metadata for the fields of a prepared statement. For information
// about preparing a statement, see sqlsrv_query or sqlsrv_prepare. Note that
// sqlsrv_field_metadata can be called on any prepared statement, pre- or
// post-execution.
//
// Parameters
// $stmt: A statement resource for which field metadata is sought.
//
// Return Value
// retrieve an array of metadata for the current result set on a statement.  Each element of the 
// array is a sub-array containing 5 elements accessed by key:
//  name - name of the field.
//  type - integer of the type.  Can be compared against the SQLSRV_SQLTYPE constants.
//  size - length of the field.  null if the field uses precision and scale instead.
//  precision - number of digits in a numeric field.  null if the field uses size.
//  scale - number of decimal digits in a numeric field.  null if the field uses sizes.
//  is_nullable - if the field may contain a NULL instead of a value
// false is returned if an error occurs retrieving the metadata

PHP_FUNCTION( sqlsrv_field_metadata )
{
    sqlsrv_stmt* stmt = NULL;
    SQLSMALLINT num_cols = -1;

    LOG_FUNCTION( "sqlsrv_field_metadata" );

    PROCESS_PARAMS( stmt, "r", _FN_, 0 );

    try {

    // get the number of fields in the resultset
    num_cols = core::SQLNumResultCols( stmt TSRMLS_CC );

    zval result_meta_data;
    ZVAL_UNDEF( &result_meta_data );
    core::sqlsrv_array_init( *stmt, &result_meta_data TSRMLS_CC );
    
    for( SQLSMALLINT f = 0; f < num_cols; ++f ) {
    
        sqlsrv_malloc_auto_ptr<field_meta_data> core_meta_data;
        core_meta_data = core_sqlsrv_field_metadata( stmt, f TSRMLS_CC );

        // initialize the array
        zval field_array;
        ZVAL_UNDEF( &field_array );
        core::sqlsrv_array_init( *stmt, &field_array TSRMLS_CC );

        core::sqlsrv_add_assoc_string( *stmt, &field_array, FieldMetaData::NAME, 
                                       reinterpret_cast<char*>( core_meta_data->field_name.get() ), 0 TSRMLS_CC );

        core_meta_data->field_name.transferred();

        core::sqlsrv_add_assoc_long( *stmt, &field_array, FieldMetaData::TYPE, core_meta_data->field_type TSRMLS_CC );

        switch( core_meta_data->field_type ) {
            case SQL_DECIMAL:
            case SQL_NUMERIC:
            case SQL_TYPE_TIMESTAMP:
            case SQL_TYPE_DATE:
            case SQL_SS_TIME2:
            case SQL_SS_TIMESTAMPOFFSET:
                core::sqlsrv_add_assoc_null( *stmt, &field_array, FieldMetaData::SIZE TSRMLS_CC );
                core::sqlsrv_add_assoc_long( *stmt, &field_array, FieldMetaData::PREC, core_meta_data->field_precision TSRMLS_CC );
                core::sqlsrv_add_assoc_long( *stmt, &field_array, FieldMetaData::SCALE, core_meta_data->field_scale TSRMLS_CC );
                break;
            case SQL_BIT:
            case SQL_TINYINT:
            case SQL_SMALLINT:
            case SQL_INTEGER:
            case SQL_BIGINT:
            case SQL_REAL:
            case SQL_FLOAT:
            case SQL_DOUBLE:
                core::sqlsrv_add_assoc_null( *stmt, &field_array, FieldMetaData::SIZE TSRMLS_CC );
                core::sqlsrv_add_assoc_long( *stmt, &field_array, FieldMetaData::PREC, core_meta_data->field_precision TSRMLS_CC );
                core::sqlsrv_add_assoc_null( *stmt, &field_array, FieldMetaData::SCALE TSRMLS_CC );
                break;
            default:
                core::sqlsrv_add_assoc_long( *stmt, &field_array, FieldMetaData::SIZE, core_meta_data->field_size TSRMLS_CC );
                core::sqlsrv_add_assoc_null( *stmt, &field_array, FieldMetaData::PREC TSRMLS_CC );
                core::sqlsrv_add_assoc_null( *stmt, &field_array, FieldMetaData::SCALE TSRMLS_CC );
                break;
        }

        // add the nullability to the array
        core::sqlsrv_add_assoc_long( *stmt, &field_array, FieldMetaData::NULLABLE, core_meta_data->field_is_nullable
                                          TSRMLS_CC );
       
        // add this field's meta data to the result set meta data
        core::sqlsrv_add_next_index_zval( *stmt, &result_meta_data, &field_array TSRMLS_CC );

        // always good to call destructor for allocations done through placement new operator.
        core_meta_data->~field_meta_data();
    }

    // return our built collection and transfer ownership
    RETURN_ZVAL(&result_meta_data, 1, 1);

    }
    catch( core::CoreException& ) {

        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_field_metadata: Unknown exception caught." );
    }
}


// sqlsrv_next_result( resource $stmt )
//  
// Makes the next result (result set, row count, or output parameter) of the
// specified statement active.  The first (or only) result returned by a batch
// query or stored procedure is active without a call to sqlsrv_next_result.
// Any output parameters bound are only available after sqlsrv_next_result returns
// null as per ODBC Driver 11 for SQL Server specs: http://msdn.microsoft.com/en-us/library/ms403283.aspx
//
// Parameters
// $stmt: The executed statement on which the next result is made active.
//
// Return Value
// If the next result was successfully made active, the Boolean value true is
// returned. If an error occurred in making the next result active, false is
// returned. If no more results are available, null is returned.

PHP_FUNCTION( sqlsrv_next_result )
{
    LOG_FUNCTION( "sqlsrv_next_result" );

    ss_sqlsrv_stmt* stmt = NULL;

    PROCESS_PARAMS( stmt, "r", _FN_, 0 );

    try {

        core_sqlsrv_next_result( stmt TSRMLS_CC, true );

        if( stmt->past_next_result_end ) {

            RETURN_NULL();
        }

        RETURN_TRUE;
    }
    catch( core::CoreException& ) {
        
        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_next_result: Unknown exception caught." );
    }
}

// sqlsrv_rows_affected( resource $stmt )
//
// Returns the number of rows modified by the last statement executed. This
// function does not return the number of rows returned by a SELECT statement.
//
// Parameters
// $stmt: A statement resource corresponding to an executed statement.
//
// Return Value
// An integer indicating the number of rows modified by the last executed
// statement. If no rows were modified, zero (0) is returned. If no information
// about the number of modified rows is available, negative one (-1) is
// returned. If an error occurred in retrieving the number of modified rows,
// false is returned.  See SQLRowCount in the MSDN ODBC documentation.

PHP_FUNCTION( sqlsrv_rows_affected )
{
    LOG_FUNCTION( "sqlsrv_rows_affected" );
    ss_sqlsrv_stmt* stmt = NULL;
    SQLLEN rows = -1;

    PROCESS_PARAMS( stmt, "r", _FN_, 0 );

     try {

        // make sure that the statement has already been executed.
        CHECK_CUSTOM_ERROR( !stmt->executed, stmt, SQLSRV_ERROR_STATEMENT_NOT_EXECUTED ) {
            throw ss::SSException();
        }

        // make sure it is not scrollable.  This function should only work for inserts, updates, and deletes,
        // but this is the best we can do to enforce that.
        CHECK_CUSTOM_ERROR( stmt->cursor_type != SQL_CURSOR_FORWARD_ONLY, stmt, SS_SQLSRV_ERROR_STATEMENT_SCROLLABLE ) {
            throw ss::SSException();
        }

        rows = stmt->current_results->row_count( TSRMLS_C );
        RETURN_LONG( rows );
    }

    catch( core::CoreException& ) {

        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_rows_affected: Unknown exception caught." );
    }
}

// sqlsrv_num_rows( resource $stmt )
//
// Retrieves the number of rows in an active result set. The statement must
// have been created with the Scrollable attribute set to 'static'.
//
// Parameters
// $stmt: The statement on which the targeted result set is active.
//
// Return Value
// An integer value that represents the number of rows in the active result
// set. If an error occurs, the boolean value false is returned.

PHP_FUNCTION( sqlsrv_num_rows )
{
    LOG_FUNCTION( "sqlsrv_num_rows" );

    ss_sqlsrv_stmt* stmt = NULL;
    SQLLEN rows = -1;

    PROCESS_PARAMS( stmt, "r", _FN_, 0 );

    try {

        // make sure that the statement has already been executed.
        CHECK_CUSTOM_ERROR( !stmt->executed, stmt, SQLSRV_ERROR_STATEMENT_NOT_EXECUTED ) {
            throw ss::SSException();
        }

        // make sure that the statement is scrollable and the cursor is not dynamic.
        // if the cursor is dynamic, then the number of rows returned is always -1.
        CHECK_CUSTOM_ERROR( stmt->cursor_type == SQL_CURSOR_FORWARD_ONLY || stmt->cursor_type == SQL_CURSOR_DYNAMIC, stmt, 
                            SS_SQLSRV_ERROR_STATEMENT_NOT_SCROLLABLE ) {
            throw ss::SSException();
        }

        rows = stmt->current_results->row_count( TSRMLS_C );
        RETURN_LONG( rows );
    }

    catch( core::CoreException& ) {

        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_num_rows: Unknown exception caught." );
    }
}

// sqlsrv_num_fields( resource $stmt )
//
// Retrieves the number of fields in an active result set. Note that
// sqlsrv_num_fields can be called on any prepared statement, before or after
// execution.
//
// Parameters
// $stmt: The statement on which the targeted result set is active.
//
// Return Value
// An integer value that represents the number of fields in the active result
// set. If an error occurs, the boolean value false is returned.

PHP_FUNCTION( sqlsrv_num_fields )
{
    LOG_FUNCTION( "sqlsrv_num_fields" );

    ss_sqlsrv_stmt* stmt = NULL;
    SQLSMALLINT fields = -1;

    PROCESS_PARAMS( stmt, "r", _FN_, 0 );

    try {
    
        // retrieve the number of columns from ODBC
        fields = core::SQLNumResultCols( stmt TSRMLS_CC );
   
        RETURN_LONG( fields );
    }

    catch( ss::SSException& ) {

        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_num_fields: Unknown exception caught." );
    }
}

// sqlsrv_fetch_object( resource $stmt [, string $className [, array $ctorParams ]])
// 
// Retrieves the next row of data as a PHP object.
//
// Parameters
// $stmt: A statement resource corresponding to an executed statement.
//
// $className [OPTIONAL]: A string specifying the name of the class to
// instantiate. If a value for the $className parameter is not specified, an
// instance of the PHP stdClass is instantiated.
//
// $ctorParams [OPTIONAL]: An array that contains values passed to the
// constructor of the class specified with the $className parameter. If the
// constructor of the specified class accepts parameter values, the $ctorParams
// parameter must be used when calling sqlsrv_fetch_object.
//
// Return Value
// A PHP object with properties that correspond to result set field
// names. Property values are populated with the corresponding result set field
// values. If the class specified with the optional $className parameter does
// not exist or if there is no active result set associated with the specified
// statement, false is returned.
// The data type of a value in the returned object will be the default PHP data
// type. For information on default PHP data types, see Default PHP Data Types.
//
// Remarks
// If a class name is specified with the optional $className parameter, an
// object of this class type is instantiated. If the class has properties whose
// names match the result set field names, the corresponding result set values
// are applied to the properties. If a result set field name does not match a
// class property, a property with the result set field name is added to the
// object and the result set value is applied to the property. For more
// information about calling sqlsrv_fetch_object with the $className parameter,
// see How to: Retrieve Data as an Object (Microsoft Drivers for PHP for SQL Server).
// 
// If a field with no name is returned, sqlsrv_fetch_object will discard the
// field value and issue a warning.

PHP_FUNCTION( sqlsrv_fetch_object )
{
    LOG_FUNCTION( "sqlsrv_fetch_object" );

    ss_sqlsrv_stmt* stmt = NULL;
	zval* class_name_z = NULL;
	zval* ctor_params_z = NULL;
    zend_long fetch_style = SQL_FETCH_NEXT;   // default value for parameter if one isn't supplied
    zend_long fetch_offset = 0;               // default value for parameter if one isn't supplied

    // stdClass is the name of the system's default base class in PHP
    char* class_name = const_cast<char*>( STDCLASS_NAME );
    std::size_t class_name_len = STDCLASS_NAME_LEN;
    HashTable* properties_ht = NULL;
	zval retval_z;
	ZVAL_UNDEF( &retval_z );

    // retrieve the statement resource and optional fetch type (see enum SQLSRV_FETCH_TYPE),
    // fetch style (see SQLSRV_SCROLL_* constants) and fetch offset
    // we also use z! instead of s and a so that null may be passed in as valid values for 
    // the class name and ctor params
    PROCESS_PARAMS( stmt, "r|z!z!ll", _FN_, 4, &class_name_z, &ctor_params_z, &fetch_style, &fetch_offset );
    
    try {
               
        CHECK_CUSTOM_ERROR(( fetch_style < SQL_FETCH_NEXT || fetch_style > SQL_FETCH_RELATIVE ), stmt, 
                            SS_SQLSRV_ERROR_INVALID_FETCH_STYLE ) {
            throw ss::SSException();
        }

        if( class_name_z ) {
            
            CHECK_CUSTOM_ERROR(( Z_TYPE_P( class_name_z ) != IS_STRING ), stmt, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ ) {
                throw ss::SSException();
            }
            class_name = Z_STRVAL( *class_name_z );
            class_name_len = Z_STRLEN( *class_name_z );
        }

        if( ctor_params_z && Z_TYPE_P( ctor_params_z ) != IS_ARRAY ) {
            THROW_SS_ERROR( stmt, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ );
        }
        
        // fetch the data
        bool result = core_sqlsrv_fetch( stmt, static_cast<SQLSMALLINT>(fetch_style), fetch_offset TSRMLS_CC );
        if( !result ) {
            RETURN_NULL();
        }

        fetch_fields_common( stmt, SQLSRV_FETCH_ASSOC, retval_z, false /*allow_empty_field_names*/ TSRMLS_CC );
        properties_ht = Z_ARRVAL( retval_z );         
        
        // find the zend_class_entry of the class the user requested (stdClass by default) for use below
        zend_class_entry* class_entry = NULL;
        zend_string* class_name_str_z = zend_string_init( class_name, class_name_len, 0 );
        int zr = ( NULL != ( class_entry = zend_lookup_class( class_name_str_z TSRMLS_CC ))) ? SUCCESS : FAILURE;
		zend_string_release( class_name_str_z );
        CHECK_ZEND_ERROR( zr, stmt, SS_SQLSRV_ERROR_ZEND_BAD_CLASS, class_name ) {
            throw ss::SSException();
        }

        // create an instance of the object with its default properties
        // we pass NULL for the properties so that the object will be populated by its default properties
        zr = object_and_properties_init( &retval_z, class_entry, NULL /*properties*/ );
        CHECK_ZEND_ERROR( zr, stmt, SS_SQLSRV_ERROR_ZEND_OBJECT_FAILED, class_name ) {
            throw ss::SSException();
        }

        // merge in the "properties" (associative array) returned from the fetch doing this vice versa
        // since putting properties_ht into object_and_properties_init and merging the default properties
        // causes duplicate properties when the visibilities are different and also references the
        // default parameters directly in the object, meaning the default property value is changed when
        // the object's property is changed.
        zend_merge_properties( &retval_z, properties_ht TSRMLS_CC );
		zend_hash_destroy( properties_ht );
		FREE_HASHTABLE( properties_ht );

        // find and call the object's constructor

        // The header files (zend.h and zend_API.h) declare
        // these functions and structures, so by working with those, we were able to 
        // develop this as a suitable snippet for calling constructors.  Some observations:
        // params must be an array of zval**, not a zval** to an array as we originally
        // thought.  Also, a constructor doesn't show up in the function table, but
        // is put into the "magic methods" section of the class entry.
        // 
        // The default values of the fci and fcic structures were determined by 
        // calling zend_fcall_info_init with a test callable.

        // if there is a constructor (e.g., stdClass doesn't have one)
        if( class_entry->constructor ) {

            // take the parameters given as our last argument and put them into a sequential array
			sqlsrv_malloc_auto_ptr<zval> params_m;
            zval ctor_retval_z;
            ZVAL_UNDEF( &ctor_retval_z );
            int num_params = 0;

            if ( ctor_params_z ) {
                HashTable* ctor_params_ht = Z_ARRVAL( *ctor_params_z );
                num_params = zend_hash_num_elements( ctor_params_ht );
                params_m = reinterpret_cast<zval*>( sqlsrv_malloc( num_params * sizeof( zval ) ));

				int i = 0;
				zval* value_z = NULL;
				ZEND_HASH_FOREACH_VAL( ctor_params_ht, value_z ) {
					zr = ( value_z ) ? SUCCESS : FAILURE;
					CHECK_ZEND_ERROR( zr, stmt, SS_SQLSRV_ERROR_ZEND_OBJECT_FAILED, class_name ) {
						throw ss::SSException();
					}
					ZVAL_COPY_VALUE(&params_m[i], value_z);
					i++;
				} ZEND_HASH_FOREACH_END();
            } //if( !Z_ISUNDEF( ctor_params_z ))
      
            // call the constructor function itself.
            zend_fcall_info fci;
            zend_fcall_info_cache fcic;

            memset( &fci, 0, sizeof( fci ));
            fci.size = sizeof( fci );
#if PHP_VERSION_ID < 70100
            fci.function_table = &( class_entry )->function_table;
#endif
            ZVAL_UNDEF( &( fci.function_name ) );
            fci.retval = &ctor_retval_z;
            fci.param_count = num_params;
            fci.params = params_m;  // purposefully not transferred since ownership isn't actually transferred.
            
            fci.object = Z_OBJ_P( &retval_z );

            memset( &fcic, 0, sizeof( fcic ));
            fcic.initialized = 1;
            fcic.function_handler = class_entry->constructor;
            fcic.calling_scope = class_entry;

            fcic.object = Z_OBJ_P( &retval_z );

            zr = zend_call_function( &fci, &fcic TSRMLS_CC );
            CHECK_ZEND_ERROR( zr, stmt, SS_SQLSRV_ERROR_ZEND_OBJECT_FAILED, class_name ) {
                throw ss::SSException();
            }

         } //if( class_entry->constructor ) 
		RETURN_ZVAL( &retval_z, 1, 1 );
    }

    catch( core::CoreException& ) {

        if( properties_ht != NULL ) {

            zend_hash_destroy( properties_ht );
            FREE_HASHTABLE( properties_ht );
        }
		else if ( Z_TYPE( retval_z ) == IS_ARRAY ) {
			zend_hash_destroy( Z_ARRVAL( retval_z ));
			FREE_HASHTABLE( Z_ARRVAL( retval_z ));
		}

        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_fetch_object: Unknown exception caught." );
    }
}


// sqlsrv_has_rows( resource $stmt )
//
// Parameters
// $stmt: The statement on which the targeted result set is active.
//
// Return Value
// Returns whether or not there are rows waiting to be processed.  There are two scenarios
// for using a function like this:
//  1) To know if there are any actual rows, not just a result set (empty or not).  Use sqlsrv_has_rows to determine this.
//     The guarantee is that if sqlsrv_has_rows returns true immediately after a query, that sqlsrv_fetch_* will return at least
//     one row of data.  
//  2) To know if there is any sort of result set, empty or not, that has to be bypassed to get to something else, such as
//     output parameters being returned.  Use sqlsrv_num_fields > 0 to check if there is any result set that must be bypassed
//     until sqlsrv_fetch returns NULL.
// The last caveat is that this function can still return FALSE if there is an error, which is fine since an error
// most likely means that there is no result data anyways.
// If this functions returs true one time, then it will return true even after the result set is exhausted
//   (sqlsrv_fetch returns null)

PHP_FUNCTION( sqlsrv_has_rows )
{
    LOG_FUNCTION( "sqlsrv_has_rows" );
    ss_sqlsrv_stmt* stmt = NULL;

    try {

        PROCESS_PARAMS( stmt, "r", _FN_, 0 );

        CHECK_CUSTOM_ERROR( !stmt->executed, stmt, SQLSRV_ERROR_STATEMENT_NOT_EXECUTED ) {
            throw ss::SSException();
        }

        if( !stmt->has_rows && !stmt->fetch_called ) {

            determine_stmt_has_rows( stmt TSRMLS_CC );
        }

        if( stmt->has_rows ) {

            RETURN_TRUE;
        }
    }
    catch( core::CoreException& ) {
    }
    catch( ... ) {

        DIE( "sqlsrv_has_rows: Unknown exception caught." );
    }

    RETURN_FALSE;
}


// sqlsrv_send_stream_data( resource $stmt )
//
// Sends data from parameter streams to the server. Up to eight kilobytes (8K)
// of data is sent with each call to sqlsrv_send_stream_data.
// By default, all stream data is sent to the server when a query is
// executed. If this default behavior is not changed, you do not have to use
// sqlsrv_send_stream_data to send stream data to the server. For information
// about changing the default behavior, see the Parameters section of
// sqlsrv_query or sqlsrv_prepare.
//
// Parameters
// $stmt: A statement resource corresponding to an executed statement.
//
// Return Value
// true if there is more data to be sent. null, if all the data has been sent,
// and false if an error occurred

PHP_FUNCTION( sqlsrv_send_stream_data )
{
    sqlsrv_stmt* stmt = NULL;
    
    LOG_FUNCTION( "sqlsrv_send_stream_data" );    

    // get the statement resource that we've bound streams to
    PROCESS_PARAMS( stmt, "r", _FN_, 0 );

    try {

        // if everything was sent at execute time, just return that there is nothing more to send.
        if( stmt->send_streams_at_exec ) {
            RETURN_NULL();
        }

        // send the next packet
        bool more = core_sqlsrv_send_stream_packet( stmt TSRMLS_CC );

        // if more to send, return true
        if( more ) {
            RETURN_TRUE;
        }
        // otherwise we're done, so return null
        else {
            RETURN_NULL();
        }
    }
    catch( core::CoreException& ) {

        // return false if an error occurred
        RETURN_FALSE;
    }
    catch( ... ) {
        
        DIE( "sqlsrv_send_stream_data: Unknown exception caught." );
    }
}


// sqlsrv_get_field( resource $stmt, int $fieldIndex [, int $getAsType] )
// 
// Retrieves data from the specified field of the current row. Field data must
// be accessed in order. For example, data from the first field cannot be
// accessed after data from the second field has been accessed.
//
// Parameters
// $stmt: A statement resource corresponding to an executed statement.
// $fieldIndex: The index of the field to be retrieved. Indexes begin at zero.
// $getAsType [OPTIONAL]: A SQLSRV constant (SQLSRV_PHPTYPE) that determines
// the PHP data type for the returned data. For information about supported data
// types, see SQLSRV Constants (Microsoft Drivers for PHP for SQL Server). If no return
// type is specified, a default PHP type will be returned. For information about
// default PHP types, see Default PHP Data Types. For information about
// specifying PHP data types, see How to: Specify PHP Data Types.
//
// Return Value
// The field data. You can specify the PHP data type of the returned data by
// using the $getAsType parameter. If no return data type is specified, the
// default PHP data type will be returned. For information about default PHP
// types, see Default PHP Data Types. For information about specifying PHP data
// types, see How to: Specify PHP Data Types.

PHP_FUNCTION( sqlsrv_get_field )
{
    LOG_FUNCTION( "sqlsrv_get_field" );
    
    ss_sqlsrv_stmt* stmt = NULL;
    sqlsrv_phptype sqlsrv_php_type;
    sqlsrv_php_type.typeinfo.type = SQLSRV_PHPTYPE_INVALID;
    SQLSRV_PHPTYPE sqlsrv_php_type_out = SQLSRV_PHPTYPE_INVALID;
    void* field_value = NULL;
    zend_long field_index = -1;
    SQLLEN field_len = -1;
    zval retval_z;
    ZVAL_UNDEF(&retval_z);
   
    // get the statement, the field index and the optional type
    PROCESS_PARAMS( stmt, "rl|l", _FN_, 2, &field_index, &sqlsrv_php_type );

    try {

        // validate that the field index is within range
        int num_cols = core::SQLNumResultCols( stmt TSRMLS_CC );

        if( field_index < 0 || field_index >= num_cols ) {
            THROW_SS_ERROR( stmt, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ );
        }

        core_sqlsrv_get_field( stmt, static_cast<SQLUSMALLINT>( field_index ), sqlsrv_php_type, false, field_value, &field_len, false/*cache_field*/,
                               &sqlsrv_php_type_out TSRMLS_CC );
        convert_to_zval( stmt, sqlsrv_php_type_out, field_value, field_len, retval_z );		
        sqlsrv_free( field_value );
        RETURN_ZVAL( &retval_z, 1, 1 );
    }

    catch( core::CoreException& ) {
        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_get_field: Unknown exception caught." );
    }
}


// ** type functions. **
// When specifying PHP and SQL Server types that take parameters, such as VARCHAR(2000), we use functions
// to match that notation and return a specially encoded integer that tells us what type and size/precision
// are.  For PHP types specifically we munge the type and encoding into the integer.
// As is easily seen, since they are so similar, we delegate the actual encoding to helper methods defined
// below.

// takes an encoding of the stream
PHP_FUNCTION( SQLSRV_PHPTYPE_STREAM )
{
    type_and_encoding( INTERNAL_FUNCTION_PARAM_PASSTHRU, SQLSRV_PHPTYPE_STREAM );
}

// takes an encoding of the string
PHP_FUNCTION( SQLSRV_PHPTYPE_STRING )
{
    type_and_encoding( INTERNAL_FUNCTION_PARAM_PASSTHRU, SQLSRV_PHPTYPE_STRING );
}

// takes the size of the binary field
PHP_FUNCTION(SQLSRV_SQLTYPE_BINARY)
{
    type_and_size_calc( INTERNAL_FUNCTION_PARAM_PASSTHRU, SQL_BINARY );
}

// takes the size of the char field
PHP_FUNCTION(SQLSRV_SQLTYPE_CHAR)
{
    type_and_size_calc( INTERNAL_FUNCTION_PARAM_PASSTHRU, SQL_CHAR );
}

// takes the precision and scale of the decimal field
PHP_FUNCTION(SQLSRV_SQLTYPE_DECIMAL)
{
    type_and_precision_calc( INTERNAL_FUNCTION_PARAM_PASSTHRU, SQL_DECIMAL );
}

// takes the size of the nchar field
PHP_FUNCTION(SQLSRV_SQLTYPE_NCHAR)
{
    type_and_size_calc( INTERNAL_FUNCTION_PARAM_PASSTHRU, SQL_WCHAR );
}

// takes the precision and scale of the numeric field
PHP_FUNCTION(SQLSRV_SQLTYPE_NUMERIC)
{
    type_and_precision_calc( INTERNAL_FUNCTION_PARAM_PASSTHRU, SQL_NUMERIC );
}

// takes the size (in characters, not bytes) of the nvarchar field
PHP_FUNCTION(SQLSRV_SQLTYPE_NVARCHAR)
{
    type_and_size_calc( INTERNAL_FUNCTION_PARAM_PASSTHRU, SQL_WVARCHAR );
}

// takes the size of the varbinary field
PHP_FUNCTION(SQLSRV_SQLTYPE_VARBINARY)
{
    type_and_size_calc( INTERNAL_FUNCTION_PARAM_PASSTHRU, SQL_VARBINARY );
}

// takes the size of the varchar field
PHP_FUNCTION(SQLSRV_SQLTYPE_VARCHAR)
{
    type_and_size_calc( INTERNAL_FUNCTION_PARAM_PASSTHRU, SQL_VARCHAR );
}

void bind_params( _Inout_ ss_sqlsrv_stmt* stmt TSRMLS_DC )
{
    // if there's nothing to do, just return
    if( stmt->params_z == NULL ) {
        return;
    }

    try {

        stmt->free_param_data( TSRMLS_C );

        stmt->executed = false;

        zval* params_z = stmt->params_z;
        
        HashTable* params_ht = Z_ARRVAL_P( params_z );
     
		zend_ulong index = -1;
		zend_string *key = NULL;
		zval* param_z = NULL;

		ZEND_HASH_FOREACH_KEY_VAL( params_ht, index, key, param_z ) {
			zval* value_z = NULL;
			SQLSMALLINT direction = SQL_PARAM_INPUT;
			SQLSRV_ENCODING encoding = stmt->encoding();
			if( stmt->encoding() == SQLSRV_ENCODING_DEFAULT ) {
				encoding = stmt->conn->encoding();
			}
			SQLSMALLINT sql_type = SQL_UNKNOWN_TYPE;
			SQLULEN column_size = SQLSRV_UNKNOWN_SIZE;
			SQLSMALLINT decimal_digits = 0;
			SQLSRV_PHPTYPE php_out_type = SQLSRV_PHPTYPE_INVALID;

			// make sure it's an integer index
			int type = key ? HASH_KEY_IS_STRING : HASH_KEY_IS_LONG;
			CHECK_CUSTOM_ERROR( type != HASH_KEY_IS_LONG, stmt, SS_SQLSRV_ERROR_PARAM_INVALID_INDEX ) {
				throw ss::SSException();
			}
            
            // if it's a parameter array
            if( Z_TYPE_P( param_z ) == IS_ARRAY ) {

                zval* var = NULL;
                int zr = ( NULL != ( var = zend_hash_index_find( Z_ARRVAL_P( param_z ), 0 ))) ? SUCCESS : FAILURE;
                CHECK_CUSTOM_ERROR( zr == FAILURE, stmt, SS_SQLSRV_ERROR_VAR_REQUIRED, index + 1 ) {
                    throw ss::SSException();
                }

                // parse the parameter array that the user gave
                parse_param_array( stmt, param_z, index, direction, php_out_type, encoding, sql_type, column_size,
                    decimal_digits TSRMLS_CC );
                value_z = var;
            }
            else {
                CHECK_CUSTOM_ERROR( !stmt->prepared && stmt->conn->ce_option.enabled, stmt, SS_SQLSRV_ERROR_AE_QUERY_SQLTYPE_REQUIRED ) {
                    throw ss::SSException();
                }
                value_z = param_z;
            }
            // bind the parameter
            SQLSRV_ASSERT( value_z != NULL, "bind_params: value_z is null." );
            core_sqlsrv_bind_param( stmt, static_cast<SQLUSMALLINT>( index ), direction, value_z, php_out_type, encoding, sql_type, column_size, 
                decimal_digits TSRMLS_CC );

		} ZEND_HASH_FOREACH_END();
    }
    catch( core::CoreException& ) {
        SQLFreeStmt( stmt->handle(), SQL_RESET_PARAMS );
        zval_ptr_dtor( stmt->params_z );
		sqlsrv_free( stmt->params_z );
        stmt->params_z = NULL;
        throw;
    }
}

// sqlsrv_cancel( resource $stmt )
// 
// Cancels a statement. This means that any pending results for the statement
// are discarded.  After this function is called, the statement can be
// re-executed if it was prepared with sqlsrv_prepare. Calling this function is
// not necessary if all the results associated with the statement have been
// consumed.
//
// Parameters
// $stmt: The statement to be canceled.
//
// Return Value
// A Boolean value: true if the operation was successful. Otherwise, false.

PHP_FUNCTION( sqlsrv_cancel )
{

    LOG_FUNCTION( "sqlsrv_cancel" );
    ss_sqlsrv_stmt* stmt = NULL;
    PROCESS_PARAMS( stmt, "r", _FN_, 0 );
    
    try {

        // close the stream to release the resource
        close_active_stream( stmt TSRMLS_CC );
        
        SQLRETURN r = SQLCancel( stmt->handle() );
        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw ss::SSException();
        }

        RETURN_TRUE;
    }
    catch( core::CoreException& ) {

        RETURN_FALSE;
    }
    catch( ... ) {

        DIE( "sqlsrv_cancel: Unknown exception caught." );
    }
}

void __cdecl sqlsrv_stmt_dtor( _Inout_ zend_resource *rsrc TSRMLS_DC )
{
    LOG_FUNCTION( "sqlsrv_stmt_dtor" );

    // get the structure
    ss_sqlsrv_stmt *stmt = static_cast<ss_sqlsrv_stmt*>( rsrc->ptr );
    if( stmt->conn ) {
        int zr = zend_hash_index_del( static_cast<ss_sqlsrv_conn*>( stmt->conn )->stmts, stmt->conn_index );
        if( zr == FAILURE ) {
            LOG( SEV_ERROR, "Failed to remove statement reference from the connection" );
        }
    }

    stmt->~ss_sqlsrv_stmt();
    sqlsrv_free( stmt );
    rsrc->ptr = NULL;
}

// sqlsrv_free_stmt( resource $stmt )
//
// Frees all resources associated with the specified statement. The statement
// cannot be used again after this function has been called.
//
// Parameters
// $stmt: The statement to be closed. 
//
// Return Value
// The Boolean value true unless the function is called with an invalid
// parameter. If the function is called with an invalid parameter, false is
// returned.
//
// Null is a valid parameter for this function. This allows the function to be
// called multiple times in a script. For example, if you free a statement in an
// error condition and free it again at the end of the script, the second call
// to sqlsrv_free_stmt will return true because the first call to
// sqlsrv_free_stmt (in the error condition) sets the statement resource to
// null.

PHP_FUNCTION( sqlsrv_free_stmt )
{

    LOG_FUNCTION( "sqlsrv_free_stmt" );

    zval* stmt_r = NULL;
    ss_sqlsrv_stmt* stmt = NULL;
    sqlsrv_context_auto_ptr error_ctx;

    reset_errors( TSRMLS_C );
    
    try {

        // dummy context to pass to the error handler
        error_ctx = new (sqlsrv_malloc( sizeof( sqlsrv_context ))) sqlsrv_context( 0, ss_error_handler, NULL );
        SET_FUNCTION_NAME( *error_ctx );

        // take only the statement resource
        if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "r", &stmt_r ) == FAILURE ) {
          
            // Check if it was a zval
            int zr = zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "z", &stmt_r );
            CHECK_CUSTOM_ERROR(( zr == FAILURE ), error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ ) {

                throw ss::SSException();
            }   
            
            if( Z_TYPE_P( stmt_r ) == IS_NULL ) {

                RETURN_TRUE;
            }
            else {

                THROW_CORE_ERROR( error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ );
            }
        }

        // verify the resource so we know we're deleting a statement
        stmt = static_cast<ss_sqlsrv_stmt*>(zend_fetch_resource_ex(stmt_r TSRMLS_CC, ss_sqlsrv_stmt::resource_name, ss_sqlsrv_stmt::descriptor));
        
		// if sqlsrv_free_stmt was called on an already closed statment then we just return success.
		// zend_list_close sets the type of the closed statment to -1.
        SQLSRV_ASSERT( stmt_r != NULL, "sqlsrv_free_stmt: stmt_r is null." );
		if ( Z_RES_TYPE_P( stmt_r ) == RSRC_INVALID_TYPE ) {
			RETURN_TRUE;
		}
	
        if( stmt == NULL ) {

            THROW_CORE_ERROR( error_ctx, SS_SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER, _FN_ );
        }
 
        // delete the resource from Zend's master list, which will trigger the statement's destructor
        if( zend_list_close( Z_RES_P(stmt_r) ) == FAILURE ) {
            LOG( SEV_ERROR, "Failed to remove stmt resource %1!d!", Z_RES_P( stmt_r )->handle);
        }

        // when stmt_r is first parsed in zend_parse_parameters, stmt_r becomes a zval that points to a zend_resource with a refcount of 2
        // need to DELREF here so the refcount becomes 1 and stmt_r can be appropriate destroyed by the garbage collector when it goes out of scope
        // zend_list_close only destroy the resource pointed to by Z_RES_P( stmt_r ), not the zend_resource itself
        Z_TRY_DELREF_P(stmt_r);
        ZVAL_NULL( stmt_r );
		
        RETURN_TRUE;
    
    }
    catch( core::CoreException& ) {
    
        RETURN_FALSE;
    }
    
    catch( ... ) {
    
        DIE( "sqlsrv_free_stmt: Unknown exception caught." );
    }
}

void stmt_option_ss_scrollable:: operator()( _Inout_ sqlsrv_stmt* stmt, stmt_option const* /*opt*/, _In_ zval* value_z TSRMLS_DC )
{
    CHECK_CUSTOM_ERROR(( Z_TYPE_P( value_z ) != IS_STRING ), stmt, SQLSRV_ERROR_INVALID_OPTION_SCROLLABLE ) {
        throw ss::SSException();
    }
    
    const char* scroll_type = Z_STRVAL_P( value_z );
    unsigned long cursor_type = -1;
    
    // find which cursor type they would like and set the ODBC statement attribute as such
    if( !stricmp( scroll_type, SSCursorTypes::QUERY_OPTION_SCROLLABLE_STATIC )) {   

        cursor_type = SQL_CURSOR_STATIC;
    }
    
    else if( !stricmp( scroll_type, SSCursorTypes::QUERY_OPTION_SCROLLABLE_DYNAMIC )) {

        cursor_type = SQL_CURSOR_DYNAMIC;
    }

    else if( !stricmp( scroll_type, SSCursorTypes::QUERY_OPTION_SCROLLABLE_KEYSET )) {

        cursor_type = SQL_CURSOR_KEYSET_DRIVEN;
    }

    else if( !stricmp( scroll_type, SSCursorTypes::QUERY_OPTION_SCROLLABLE_FORWARD )) {
        
        cursor_type = SQL_CURSOR_FORWARD_ONLY;
    }

    else if( !stricmp( scroll_type, SSCursorTypes::QUERY_OPTION_SCROLLABLE_BUFFERED )) {
        
        cursor_type = SQLSRV_CURSOR_BUFFERED;
    }

    else {

        THROW_SS_ERROR( stmt, SQLSRV_ERROR_INVALID_OPTION_SCROLLABLE );
    }

    core_sqlsrv_set_scrollable( stmt, cursor_type TSRMLS_CC );

}

namespace {

void convert_to_zval( _Inout_ sqlsrv_stmt* stmt, _In_ SQLSRV_PHPTYPE sqlsrv_php_type, _In_opt_ void* in_val, _In_ SQLLEN field_len, _Inout_ zval& out_zval)
{
	if ( in_val == NULL ) {
		ZVAL_NULL( &out_zval);
		return;
	}

	switch (sqlsrv_php_type) {

	case SQLSRV_PHPTYPE_INT:
	case SQLSRV_PHPTYPE_FLOAT:
	{
		if (sqlsrv_php_type == SQLSRV_PHPTYPE_INT) {
			ZVAL_LONG( &out_zval, *(static_cast<int*>( in_val )));
		}
		else {
			ZVAL_DOUBLE( &out_zval, *(static_cast<double*>( in_val )));
		}
		break;
	}

	case SQLSRV_PHPTYPE_STRING:
	{
		ZVAL_STRINGL( &out_zval, static_cast<const char*>( in_val ), field_len);
		break;
	}

	case SQLSRV_PHPTYPE_STREAM:
	{
		out_zval = *( static_cast<zval*>( in_val ));
		stmt->active_stream = out_zval;
		//addref here because deleting out_zval later will decrement the refcount
		Z_TRY_ADDREF( out_zval );
		break;
	}
	case SQLSRV_PHPTYPE_DATETIME:
	{
		out_zval = *( static_cast<zval*>( in_val ));
		break;
	}

	case SQLSRV_PHPTYPE_NULL:
		ZVAL_NULL(&out_zval);
		break;

	default:
		DIE("Unknown php type");
		break;
	}
	return;
}


// put in the column size and scale/decimal digits of the sql server type
// these values are taken from the MSDN page at http://msdn2.microsoft.com/en-us/library/ms711786(VS.85).aspx
// for SQL_VARBINARY, SQL_VARCHAR, and SQL_WLONGVARCHAR types, see https://msdn.microsoft.com/en-CA/library/ms187993.aspx
bool determine_column_size_or_precision( sqlsrv_stmt const* stmt, _In_ sqlsrv_sqltype sqlsrv_type, _Inout_ SQLULEN* column_size, 
                                         _Out_ SQLSMALLINT* decimal_digits )
{
    *decimal_digits = 0;

    switch( sqlsrv_type.typeinfo.type ) {
        case SQL_BIGINT:
            *column_size = 19;
            break;
        case SQL_BIT:
            *column_size = 1;
            break;
        case SQL_INTEGER:
            *column_size = 10;
            break;
        case SQL_SMALLINT:
            *column_size = 5;
            break;
        case SQL_TINYINT:
            *column_size = 3;
            break;
        case SQL_GUID:
            *column_size = 36;
            break;
        case SQL_FLOAT:
            *column_size = 53;
            break;
        case SQL_REAL:
            *column_size = 24;
            break;
        case SQL_LONGVARBINARY:
        case SQL_LONGVARCHAR:
            *column_size = INT_MAX;
            break;
        case SQL_WLONGVARCHAR:
            *column_size = INT_MAX >> 1;
            break;
        case SQL_SS_XML:
            *column_size = SQL_SS_LENGTH_UNLIMITED;
            break;
        case SQL_BINARY:
        case SQL_CHAR:
        case SQL_VARBINARY:
        case SQL_VARCHAR:
        case SQL_SS_VARIANT:
            *column_size = sqlsrv_type.typeinfo.size;
            if( *column_size == SQLSRV_SIZE_MAX_TYPE ) {
                *column_size = SQL_SS_LENGTH_UNLIMITED;
            }
            else if( *column_size > SQL_SERVER_MAX_FIELD_SIZE || *column_size == SQLSRV_INVALID_SIZE ) {
                *column_size = SQLSRV_INVALID_SIZE;
                return false;
            }
            break;
        case SQL_WCHAR:
        case SQL_WVARCHAR: 
            *column_size = sqlsrv_type.typeinfo.size;
            if( *column_size == SQLSRV_SIZE_MAX_TYPE ) {
                *column_size = SQL_SS_LENGTH_UNLIMITED;
                break;
            }
            if( *column_size > SQL_SERVER_MAX_FIELD_SIZE || *column_size == SQLSRV_INVALID_SIZE ) {
                *column_size = SQLSRV_INVALID_SIZE;
                return false;
            }
            break;
        case SQL_DECIMAL:
        case SQL_NUMERIC:
            *column_size = sqlsrv_type.typeinfo.size;
            *decimal_digits = sqlsrv_type.typeinfo.scale;
            // if there was something wrong with the values given on type_and_precision_calc, these are set to invalid precision
            if( *column_size == SQLSRV_INVALID_PRECISION || *decimal_digits == SQLSRV_INVALID_PRECISION ) {
                *column_size = SQLSRV_INVALID_SIZE;
                return false;
            }
            break;
        // this can represent one of three data types: smalldatetime, datetime, and datetime2
        // we present the largest for the version and let SQL Server downsize it
        case SQL_TYPE_TIMESTAMP:
            *column_size = sqlsrv_type.typeinfo.size;
            *decimal_digits = sqlsrv_type.typeinfo.scale;
            break;
        case SQL_SS_TIMESTAMPOFFSET:
            *column_size = 34;
            *decimal_digits = 7;
            break;
        case SQL_TYPE_DATE:
            *column_size = 10;
            *decimal_digits = 0;
            break;
        case SQL_SS_TIME2:
            *column_size = 16;
            *decimal_digits = 7;
            break;
        default:
            // an invalid sql type should have already been dealt with, so we assert here.
            DIE( "Trying to determine column size for an invalid type.  Type should have already been verified." );
            return false;
    }

    return true;
}


// given a SQL Server type, return a sqlsrv php type
sqlsrv_phptype determine_sqlsrv_php_type( _In_ ss_sqlsrv_stmt const* stmt, _In_ SQLINTEGER sql_type, _In_ SQLUINTEGER size, _In_ bool prefer_string )
{
    sqlsrv_phptype sqlsrv_phptype;
    sqlsrv_phptype.typeinfo.type = PHPTYPE_INVALID;
    sqlsrv_phptype.typeinfo.encoding = SQLSRV_ENCODING_INVALID;

    switch( sql_type ) {
        case SQL_BIGINT:
        case SQL_CHAR:
        case SQL_DECIMAL:
        case SQL_GUID:
        case SQL_NUMERIC:
        case SQL_WCHAR:
            sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STRING;
            sqlsrv_phptype.typeinfo.encoding = stmt->encoding();
            break;
        case SQL_VARCHAR:
        case SQL_WVARCHAR:
        case SQL_SS_VARIANT:
            if( prefer_string || size != SQL_SS_LENGTH_UNLIMITED ) {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STRING;
                sqlsrv_phptype.typeinfo.encoding = stmt->encoding();
            }
            else {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STREAM;
                sqlsrv_phptype.typeinfo.encoding = stmt->encoding();
            }
            break;
        case SQL_BIT:
        case SQL_INTEGER:
        case SQL_SMALLINT:
        case SQL_TINYINT:
            sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_INT;
            break;
        case SQL_BINARY:
        case SQL_LONGVARBINARY:
        case SQL_VARBINARY:
        case SQL_SS_UDT:
            if( prefer_string ) {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STRING;
                sqlsrv_phptype.typeinfo.encoding = SQLSRV_ENCODING_BINARY;
            }
            else {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STREAM;
                sqlsrv_phptype.typeinfo.encoding = SQLSRV_ENCODING_BINARY;
            }
            break;
        case SQL_LONGVARCHAR:
        case SQL_WLONGVARCHAR:
        case SQL_SS_XML:
            if( prefer_string ) {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STRING;
                sqlsrv_phptype.typeinfo.encoding = stmt->encoding();
            }
            else {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STREAM;
                sqlsrv_phptype.typeinfo.encoding = stmt->encoding();
            }
            break;
        case SQL_FLOAT:
        case SQL_REAL:
            sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_FLOAT;
            break;
        case SQL_TYPE_DATE:
        case SQL_SS_TIMESTAMPOFFSET:
        case SQL_SS_TIME2:
        case SQL_TYPE_TIMESTAMP:
        {
            ss_sqlsrv_conn* c = static_cast<ss_sqlsrv_conn*>( stmt->conn );
            if( c->date_as_string ) {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STRING;
                sqlsrv_phptype.typeinfo.encoding = stmt->encoding();
            }
            else {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_DATETIME;
            }
            break;
        }
        default:
            sqlsrv_phptype.typeinfo.type = PHPTYPE_INVALID;
            break;
    }

    // if an encoding hasn't been set for the statement, then use the connection's encoding
    if( sqlsrv_phptype.typeinfo.encoding == SQLSRV_ENCODING_DEFAULT ) {
        sqlsrv_phptype.typeinfo.encoding = stmt->conn->encoding();
    }
    
    return sqlsrv_phptype;
}


// determine if a query returned any rows of data.  It does this by actually fetching the first row
// (though not retrieving the data) and setting the has_rows flag in the stmt the fetch was successful.
// The return value simply states whether or not if an error occurred during the determination.
// (All errors are posted here before returning.)

void determine_stmt_has_rows( _Inout_ ss_sqlsrv_stmt* stmt TSRMLS_DC )
{
    SQLRETURN r = SQL_SUCCESS;

    if( stmt->fetch_called ) {

        return;
    }

    // default condition
    stmt->has_rows = false;

    // if there are no columns then there are no rows
    if( core::SQLNumResultCols( stmt TSRMLS_CC ) == 0 ) {

        return;
    }

    // if the statement is scrollable, our work is easier though less performant.  We simply
    // fetch the first row, and then roll the cursor back to be prior to the first row
    if( stmt->cursor_type != SQL_CURSOR_FORWARD_ONLY ) {

        r = stmt->current_results->fetch( SQL_FETCH_FIRST, 0 TSRMLS_CC );
        if( SQL_SUCCEEDED( r )) {

            stmt->has_rows = true;
            CHECK_SQL_WARNING( r, stmt );
            // restore the cursor to its original position.
            r = stmt->current_results->fetch( SQL_FETCH_ABSOLUTE, 0 TSRMLS_CC );
            SQLSRV_ASSERT(( r == SQL_NO_DATA ), "core_sqlsrv_has_rows: Should have scrolled the cursor to the beginning "
                          "of the result set." );
        }
    }
    else {
        
        // otherwise, we fetch the first row, but record that we did.  sqlsrv_fetch checks this
        // flag and simply skips the first fetch, knowing it was already done.  It records its own 
        // flags to know if it should fetch on subsequent calls.

        r = core::SQLFetchScroll( stmt, SQL_FETCH_NEXT, 0 TSRMLS_CC );
        if( SQL_SUCCEEDED( r )) {

            stmt->has_rows = true;
            CHECK_SQL_WARNING( r, stmt );
            return;
        }
    }
}

void fetch_fields_common( _Inout_ ss_sqlsrv_stmt* stmt, _In_ zend_long fetch_type, _Out_ zval& fields, _In_ bool allow_empty_field_names
						TSRMLS_DC )
{
	void* field_value = NULL;
	sqlsrv_phptype sqlsrv_php_type;
	sqlsrv_php_type.typeinfo.type = SQLSRV_PHPTYPE_INVALID;
	SQLSRV_PHPTYPE sqlsrv_php_type_out = SQLSRV_PHPTYPE_INVALID;

	// make sure that the fetch type is legal
	CHECK_CUSTOM_ERROR((fetch_type < MIN_SQLSRV_FETCH || fetch_type > MAX_SQLSRV_FETCH), stmt, SS_SQLSRV_ERROR_INVALID_FETCH_TYPE, stmt->func()) {
		throw ss::SSException();
	}

	// get the numer of columns in the result set
	SQLSMALLINT num_cols = core::SQLNumResultCols(stmt TSRMLS_CC);

	// if this is the first fetch in a new result set, then get the field names and
	// store them off for successive fetches.
	if(( fetch_type & SQLSRV_FETCH_ASSOC ) && stmt->fetch_field_names == NULL ) {

        SQLLEN field_name_len = 0;
        SQLSMALLINT field_name_len_w = 0;
        SQLWCHAR field_name_w[( SS_MAXCOLNAMELEN + 1 ) * 2 ] = { L'\0' };
        sqlsrv_malloc_auto_ptr<char> field_name;
        sqlsrv_malloc_auto_ptr<sqlsrv_fetch_field_name> field_names;
        field_names = static_cast<sqlsrv_fetch_field_name*>( sqlsrv_malloc( num_cols * sizeof( sqlsrv_fetch_field_name )));
        SQLSRV_ENCODING encoding = (( stmt->encoding() == SQLSRV_ENCODING_DEFAULT ) ? stmt->conn->encoding() : stmt->encoding());
        for( int i = 0; i < num_cols; ++i ) {

            core::SQLColAttributeW ( stmt, i + 1, SQL_DESC_NAME, field_name_w, ( SS_MAXCOLNAMELEN + 1 ) * 2, &field_name_len_w, NULL TSRMLS_CC );

            //Conversion function expects size in characters
            field_name_len_w = field_name_len_w / sizeof ( SQLWCHAR );
            bool converted = convert_string_from_utf16( encoding, field_name_w,
                field_name_len_w, ( char** ) &field_name, field_name_len );

            CHECK_CUSTOM_ERROR( !converted, stmt, SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE, get_last_error_message() ) {
                throw core::CoreException();
            }

            field_names[i].name = static_cast<char*>( sqlsrv_malloc( field_name_len, sizeof( char ), 1 ));
            memcpy_s(( void* )field_names[i].name, ( field_name_len * sizeof( char )) , ( void* ) field_name, field_name_len );
            field_names[i].name[field_name_len] = '\0';  // null terminate the field name since SQLColAttribute doesn't.
            field_names[i].len = field_name_len + 1;
            field_name.reset();
        }
		
        stmt->fetch_field_names = field_names;
        stmt->fetch_fields_count = num_cols;
        field_names.transferred();
    }

    int zr = array_init( &fields );
	CHECK_ZEND_ERROR( zr, stmt, SQLSRV_ERROR_ZEND_HASH ) {
		throw ss::SSException();
	}

	for( int i = 0; i < num_cols; ++i ) {
		SQLLEN field_len = -1;

		core_sqlsrv_get_field( stmt, i, sqlsrv_php_type, true /*prefer string*/,
									field_value, &field_len, false /*cache_field*/, &sqlsrv_php_type_out TSRMLS_CC );

		zval field;
		ZVAL_UNDEF( &field );
		convert_to_zval( stmt, sqlsrv_php_type_out, field_value, field_len, field );
		sqlsrv_free( field_value );
		if( fetch_type & SQLSRV_FETCH_NUMERIC ) {

			zr = add_next_index_zval( &fields, &field );
			CHECK_ZEND_ERROR( zr, stmt, SQLSRV_ERROR_ZEND_HASH ) {
				throw ss::SSException();
			}
		}

		if( fetch_type & SQLSRV_FETCH_ASSOC ) {

			CHECK_CUSTOM_WARNING_AS_ERROR(( stmt->fetch_field_names[i].len == 1 && !allow_empty_field_names ), stmt,
											SS_SQLSRV_WARNING_FIELD_NAME_EMPTY) {
				throw ss::SSException();
			}

			if( stmt->fetch_field_names[ i ].len > 1 || allow_empty_field_names ) {

				zr = add_assoc_zval( &fields, stmt->fetch_field_names[i].name, &field );
				CHECK_ZEND_ERROR( zr, stmt, SQLSRV_ERROR_ZEND_HASH ) {
					throw ss::SSException();
				}
			}
		}
		//only addref when the fetch_type is BOTH because this is the only case when fields(hashtable)
		//has 2 elements pointing to field. Do not addref if the type is NUMERIC or ASSOC because 
		//fields now only has 1 element pointing to field and we want the ref count to be only 1
		if (fetch_type == SQLSRV_FETCH_BOTH) {
			Z_TRY_ADDREF(field);
		}
	} //for loop

}

void parse_param_array( _Inout_ ss_sqlsrv_stmt* stmt, _Inout_ zval* param_array, zend_ulong index, _Out_ SQLSMALLINT& direction,
                        _Out_ SQLSRV_PHPTYPE& php_out_type, _Out_ SQLSRV_ENCODING& encoding, _Out_ SQLSMALLINT& sql_type, 
                        _Out_ SQLULEN& column_size, _Out_ SQLSMALLINT& decimal_digits TSRMLS_DC )

{
    zval* var_or_val = NULL;
    zval* temp = NULL;
    HashTable* param_ht = Z_ARRVAL_P( param_array );
    sqlsrv_sqltype sqlsrv_sql_type;
    HashPosition pos;

    try {

    bool php_type_param_was_null = true;
    bool sql_type_param_was_null = true;

    php_out_type = SQLSRV_PHPTYPE_INVALID;
    encoding = SQLSRV_ENCODING_INVALID;

    // handle the array parameters that contain the value/var, direction, php_type, sql_type
    zend_hash_internal_pointer_reset_ex( param_ht, &pos );
    if( zend_hash_has_more_elements_ex( param_ht, &pos ) == FAILURE || 
        (var_or_val = zend_hash_get_current_data_ex(param_ht, &pos)) == NULL) {

        THROW_SS_ERROR( stmt, SS_SQLSRV_ERROR_VAR_REQUIRED, index + 1 );
    }

    // if the direction is included, then use what they gave, otherwise INPUT is assumed
    if ( zend_hash_move_forward_ex( param_ht, &pos ) == SUCCESS && ( temp = zend_hash_get_current_data_ex( param_ht, &pos )) != NULL &&
            Z_TYPE_P( temp ) != IS_NULL ) {

        CHECK_CUSTOM_ERROR( Z_TYPE_P( temp ) != IS_LONG, stmt, SS_SQLSRV_ERROR_INVALID_PARAMETER_DIRECTION, index + 1 ) {

            throw ss::SSException();
        }
        direction = static_cast<SQLSMALLINT>( Z_LVAL_P( temp ));
        CHECK_CUSTOM_ERROR( direction != SQL_PARAM_INPUT && direction != SQL_PARAM_OUTPUT && direction != SQL_PARAM_INPUT_OUTPUT,
                            stmt, SS_SQLSRV_ERROR_INVALID_PARAMETER_DIRECTION, index + 1 ) {
            throw ss::SSException();
        }

        CHECK_CUSTOM_ERROR( !Z_ISREF_P( var_or_val ) && ( direction == SQL_PARAM_OUTPUT || direction == SQL_PARAM_INPUT_OUTPUT ), stmt, SS_SQLSRV_ERROR_PARAM_VAR_NOT_REF, index + 1 ) {
            throw ss::SSException();
        }
       
    }
    else {
        direction = SQL_PARAM_INPUT;
    }

    // extract the php type and encoding from the 3rd parameter
    if ( zend_hash_move_forward_ex( param_ht, &pos ) == SUCCESS && ( temp = zend_hash_get_current_data_ex( param_ht, &pos )) != NULL &&
            Z_TYPE_P( temp ) != IS_NULL ) {
                
        php_type_param_was_null = false;
        sqlsrv_phptype sqlsrv_phptype;

        CHECK_CUSTOM_ERROR( Z_TYPE_P( temp ) != IS_LONG, stmt, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE, index + 1 ) {

            throw ss::SSException();
        }

        sqlsrv_phptype.value = Z_LVAL_P( temp );

        CHECK_CUSTOM_ERROR( !is_valid_sqlsrv_phptype( sqlsrv_phptype ), stmt, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE, 
                            index + 1 ) {

            throw ss::SSException();
        }

        php_out_type = static_cast<SQLSRV_PHPTYPE>( sqlsrv_phptype.typeinfo.type );
        encoding = ( SQLSRV_ENCODING ) sqlsrv_phptype.typeinfo.encoding;
        // if the call has a SQLSRV_PHPTYPE_STRING/STREAM('default'), then the stream is in the encoding established 
        // by the connection
        if( encoding == SQLSRV_ENCODING_DEFAULT ) {
            encoding = stmt->conn->encoding();
        }
    }
    // set default for php type and encoding if not supplied
    else {
                    
        php_type_param_was_null = true;

        if ( Z_ISREF_P( var_or_val )){
            php_out_type = zend_to_sqlsrv_phptype[Z_TYPE_P( Z_REFVAL_P( var_or_val ))];
        }
        else{
            php_out_type = zend_to_sqlsrv_phptype[Z_TYPE_P( var_or_val )];
        }
        encoding = stmt->encoding();
        if( encoding == SQLSRV_ENCODING_DEFAULT ) {
            encoding = stmt->conn->encoding();
        }        
    }

    // get the server type, column size/precision and the decimal digits if provided
    if ( zend_hash_move_forward_ex( param_ht, &pos ) == SUCCESS && ( temp = zend_hash_get_current_data_ex( param_ht, &pos )) != NULL &&
            Z_TYPE_P( temp ) != IS_NULL ) {

        sql_type_param_was_null = false;

        CHECK_CUSTOM_ERROR( Z_TYPE_P( temp ) != IS_LONG, stmt, SQLSRV_ERROR_INVALID_PARAMETER_SQLTYPE, index + 1 ) {

            throw ss::SSException();
        }

        sqlsrv_sql_type.value = Z_LVAL_P( temp );

        // since the user supplied this type, make sure it's valid
        CHECK_CUSTOM_ERROR( !is_valid_sqlsrv_sqltype( sqlsrv_sql_type ), stmt, SQLSRV_ERROR_INVALID_PARAMETER_SQLTYPE, 
                            index + 1 ) {

            throw ss::SSException();
        }             
        
		bool size_okay = determine_column_size_or_precision( stmt, sqlsrv_sql_type, &column_size, &decimal_digits );

        CHECK_CUSTOM_ERROR( !size_okay, stmt, SS_SQLSRV_ERROR_INVALID_PARAMETER_PRECISION, index + 1 ) {

            throw ss::SSException();
        }

        sql_type = sqlsrv_sql_type.typeinfo.type;
    }
    // else the sql type and size are unknown, so tell the core layer to use its defaults
    else {
        CHECK_CUSTOM_ERROR( !stmt->prepared && stmt->conn->ce_option.enabled, stmt, SS_SQLSRV_ERROR_AE_QUERY_SQLTYPE_REQUIRED ) {
            throw ss::SSException();
        }
        sql_type_param_was_null = true;

        sql_type = SQL_UNKNOWN_TYPE;
        column_size = SQLSRV_UNKNOWN_SIZE;
        decimal_digits = 0;
    }

    // if the user for some reason provides an inout / output parameter with a null phptype and a specified
    // sql server type, infer the php type from the sql server type.
    if( direction != SQL_PARAM_INPUT && php_type_param_was_null && !sql_type_param_was_null ) {

        sqlsrv_phptype sqlsrv_phptype;

        sqlsrv_phptype = determine_sqlsrv_php_type( stmt, sql_type, (SQLUINTEGER)column_size, true );
        
        // we DIE here since everything should have been validated already and to return the user an error
        // for our own logic error would be confusing/misleading.
        SQLSRV_ASSERT( sqlsrv_phptype.typeinfo.type != PHPTYPE_INVALID, "An invalid php type was returned with (supposed) "
                       "validated sql type and column_size" );

        php_out_type = static_cast<SQLSRV_PHPTYPE>( sqlsrv_phptype.typeinfo.type );
        encoding = static_cast<SQLSRV_ENCODING>( sqlsrv_phptype.typeinfo.encoding );
    }

    // verify that the parameter is a valid output param type
    if( direction == SQL_PARAM_OUTPUT ) {

        switch( php_out_type ) {
            case SQLSRV_PHPTYPE_NULL:
            case SQLSRV_PHPTYPE_DATETIME:
            case SQLSRV_PHPTYPE_STREAM:
                THROW_CORE_ERROR( stmt, SS_SQLSRV_ERROR_INVALID_OUTPUT_PARAM_TYPE );
                break;
            default:
                break;
        }

    }

    }
    catch( core::CoreException& ) {

        SQLFreeStmt( stmt->handle(), SQL_RESET_PARAMS );
        throw;
    }
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

// return if the type is a valid sql server type not including
// size, precision or scale.  Use determine_precision_and_scale for that.
bool is_valid_sqlsrv_sqltype( _In_ sqlsrv_sqltype sql_type )
{
    switch( sql_type.typeinfo.type ) {
        case SQL_BIGINT:
        case SQL_BIT:
        case SQL_INTEGER:
        case SQL_SMALLINT:
        case SQL_TINYINT:
        case SQL_GUID:
        case SQL_FLOAT:
        case SQL_REAL:
        case SQL_LONGVARBINARY:
        case SQL_LONGVARCHAR:
        case SQL_WLONGVARCHAR:
        case SQL_SS_XML:
        case SQL_BINARY:
        case SQL_CHAR:
        case SQL_WCHAR:
        case SQL_WVARCHAR: 
        case SQL_VARBINARY:
        case SQL_VARCHAR:
        case SQL_DECIMAL:
        case SQL_NUMERIC:
        case SQL_TYPE_TIMESTAMP:
        case SQL_TYPE_DATE:
        case SQL_SS_TIME2:
        case SQL_SS_TIMESTAMPOFFSET:
            break;
        default:
            return false;
    }

    return true;
}

// verify an encoding given to type_and_encoding by looking through the list
// of standard encodings created at module initialization time
bool verify_and_set_encoding( _In_ const char* encoding_string, _Inout_ sqlsrv_phptype& phptype_encoding TSRMLS_DC )
{
	void* encoding_temp = NULL;
	zend_ulong index = -1;
	zend_string* key = NULL;
	ZEND_HASH_FOREACH_KEY_PTR( g_ss_encodings_ht, index, key, encoding_temp ) {
        if (encoding_temp) {
            sqlsrv_encoding* encoding = reinterpret_cast<sqlsrv_encoding*>(encoding_temp);
            encoding_temp = NULL;
            if (!stricmp(encoding_string, encoding->iana)) {
                phptype_encoding.typeinfo.encoding = encoding->code_page;
                return true;
            }
        }
        else {
            DIE("Fatal: Error retrieving encoding from encoding hash table.");
        }
	} ZEND_HASH_FOREACH_END();

    return false;
}

// called when one of the SQLSRV_SQLTYPE type functions is called.  Encodes the type and size
// into a sqlsrv_sqltype bit fields (see php_sqlsrv.h).
void type_and_size_calc( INTERNAL_FUNCTION_PARAMETERS, _In_ int type )
{
    char* size_p = NULL;
    size_t size_len = 0;
    int size = 0;

    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "s", &size_p, &size_len ) == FAILURE ) {
               
        return;
    }
    if (size_p) {
        if (!strnicmp("max", size_p, sizeof("max") / sizeof(char))) {
            size = SQLSRV_SIZE_MAX_TYPE;
        }
        else {
#ifndef _WIN32
            errno = 0;
#else
            _set_errno(0);  // reset errno for atol
#endif // !_WIN32
            size = atol(size_p);
            if (errno != 0) {
                size = SQLSRV_INVALID_SIZE;
            }
        }
    }
    else {
        DIE("type_and_size_calc: size_p is null.");
    }

    int max_size = SQL_SERVER_MAX_FIELD_SIZE;
    // size is actually the number of characters, not the number of bytes, so if they ask for a 
    // 2 byte per character type, then we half the maximum size allowed.
    if( type == SQL_WVARCHAR || type == SQL_WCHAR ) {
        max_size >>= 1;
    }

    if( size > max_size || size < SQLSRV_SIZE_MAX_TYPE || size == 0 ) {
        LOG( SEV_ERROR, "invalid size.  size must be > 0 and <= %1!d! characters or 'max'", max_size );
        size = SQLSRV_INVALID_SIZE;
    }
    
    sqlsrv_sqltype sql_type;
    sql_type.typeinfo.type = type;
    sql_type.typeinfo.size = size;
    sql_type.typeinfo.scale = SQLSRV_INVALID_SCALE;

    ZVAL_LONG( return_value, sql_type.value );
}

// called when the user gives SQLSRV_SQLTYPE_DECIMAL or SQLSRV_SQLTYPE_NUMERIC sql types as the type of the
// field.  encodes these into a sqlsrv_sqltype structure (see php_sqlsrv.h)
void type_and_precision_calc( INTERNAL_FUNCTION_PARAMETERS, _In_ int type )
{
    zend_long prec = SQLSRV_INVALID_PRECISION;
    zend_long scale = SQLSRV_INVALID_SCALE;

    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "|ll", &prec, &scale ) == FAILURE ) {
                                    
        return;
    }
    
    if( prec > SQL_SERVER_MAX_PRECISION ) {
        LOG( SEV_ERROR, "Invalid precision.  Precision can't be > 38" );
        prec = SQLSRV_INVALID_PRECISION;
    }
    
    if( prec < 0 ) {
        LOG( SEV_ERROR, "Invalid precision.  Precision can't be negative" );
        prec = SQLSRV_INVALID_PRECISION;
    }

    if( scale > prec ) {
        LOG( SEV_ERROR, "Invalid scale.  Scale can't be > precision" );
        scale = SQLSRV_INVALID_SCALE;
    }

    sqlsrv_sqltype sql_type;
    sql_type.typeinfo.type = type;
    sql_type.typeinfo.size = prec;
    sql_type.typeinfo.scale = scale;

    ZVAL_LONG( return_value, sql_type.value );
}

// common code for SQLSRV_PHPTYPE_STREAM and SQLSRV_PHPTYPE_STRING php types given as parameters.
// encodes the type and encoding into a sqlsrv_phptype structure (see php_sqlsrv.h)
void type_and_encoding( INTERNAL_FUNCTION_PARAMETERS, _In_ int type )
{

    SQLSRV_ASSERT(( type == SQLSRV_PHPTYPE_STREAM || type == SQLSRV_PHPTYPE_STRING ), "type_and_encoding: Invalid type passed." ); 

    char* encoding_param;
    size_t encoding_param_len = 0;
    
    // set the default encoding values to invalid so that
    // if the encoding isn't validated, it will return the invalid setting.
    sqlsrv_phptype sqlsrv_php_type;
    sqlsrv_php_type.typeinfo.type = type;
    sqlsrv_php_type.typeinfo.encoding = SQLSRV_ENCODING_INVALID;

    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "s", &encoding_param, &encoding_param_len ) == FAILURE ) {
       
        ZVAL_LONG( return_value, sqlsrv_php_type.value );
    }

    if( !verify_and_set_encoding( encoding_param, sqlsrv_php_type TSRMLS_CC )) {
        LOG( SEV_ERROR, "Invalid encoding for php type." );
    }

    ZVAL_LONG( return_value, sqlsrv_php_type.value );
}

}

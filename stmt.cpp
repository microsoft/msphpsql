//----------------------------------------------------------------------------------------------------------------------------------
// File: stmt.cpp
//
// Copyright (c) Microsoft Corporation.  All rights reserved.
//
// Contents: Routines that use statement handles
//
// License: This software is released under the Microsoft Public License.  A copy of the license agreement 
//          may be found online at http://www.codeplex.com/SQL2K5PHP/license.
//----------------------------------------------------------------------------------------------------------------------------------

// *** header files ***
#include "php_sqlsrv.h"

#include <sal.h>

// *** internal variables and constants ***

// our resource descriptor assigned in minit
int sqlsrv_stmt::descriptor;
char* sqlsrv_stmt::resource_name = "sqlsrv_stmt";   // not const for a reason.  see sqlsrv_stmt in php_sqlsrv.h

namespace {

// current subsytem.  defined for the CHECK_SQL_{ERROR|WARNING} macros
int current_log_subsystem = LOG_STMT;

// constants used as invalid types for type errors
const zend_uchar PHPTYPE_INVALID = SQLSRV_PHPTYPE_INVALID;
const int SQLTYPE_INVALID = 0;
const int SQLSRV_INVALID_PRECISION = -1;
const SQLUINTEGER SQLSRV_INVALID_SIZE = (~1U);
const int SQLSRV_INVALID_SCALE = -1;
const int SQLSRV_SIZE_MAX_TYPE = -1;

// constants used to convert from a DateTime object to a string which is sent to the server.
// Using the format defined by the ODBC documentation at http://msdn2.microsoft.com/en-us/library/ms712387(VS.85).aspx
const char DATETIME_CLASS_NAME[] = "DateTime";
const size_t DATETIME_CLASS_NAME_LEN = sizeof( DATETIME_CLASS_NAME ) - 1;
const char DATETIME_FORMAT[] = "Y-m-d H:i:s.u";
const size_t DATETIME_FORMAT_LEN = sizeof( DATETIME_FORMAT );

// constants for maximums in SQL Server 
const int SQL_SERVER_MAX_FIELD_SIZE = 8000;
const int SQL_SERVER_MAX_PRECISION = 38;
const int SQL_SERVER_DEFAULT_PRECISION = 18;
const int SQL_SERVER_DEFAULT_SCALE = 0;

// base allocation size when retrieving a string field
const int INITIAL_FIELD_STRING_LEN = 256;
// max size of a date time string when converting from a DateTime object to a string
const int MAX_DATETIME_STRING_LEN = 256;

// this must align 1:1 with the SQLSRV_PHPTYPE enum in php_sqlsrv.h
const zend_uchar sqlsrv_to_php_type[] = {
    IS_NULL,
    IS_LONG,
    IS_DOUBLE,
    IS_STRING,
    IS_OBJECT,
    IS_RESOURCE,
    SQLSRV_PHPTYPE_INVALID
};

// default class used when no class is specified by sqlsrv_fetch_object
const char STDCLASS_NAME[] = "stdclass";
const char STDCLASS_NAME_LEN = sizeof( STDCLASS_NAME ) - 1;

// *** internal function prototypes ***

// These are arranged alphabetically.  They are all used by the sqlsrv statement functions.
bool adjust_output_string_lengths( sqlsrv_stmt* stmt, const char* _FN_ TSRMLS_DC );
SQLSMALLINT binary_or_char_encoding( SQLSMALLINT c_type );
bool check_for_next_stream_parameter( sqlsrv_stmt* stmt, zval* return_value TSRMLS_DC );
void close_active_stream( sqlsrv_stmt* s TSRMLS_DC );
SQLSMALLINT determine_c_type( int php_type, int encoding );
bool determine_column_size_or_precision( sqlsrv_sqltype sqlsrv_type, SQLUINTEGER* column_size, SQLSMALLINT* decimal_digits );
bool determine_param_defaults( sqlsrv_stmt const* stmt, const char* _FN_, zval const* param_z, int param_num, zend_uchar& php_type, int& direction, 
                               sqlsrv_sqltype& sql_type, SQLSMALLINT& sql_c_type, SQLUINTEGER& column_size, SQLSMALLINT& decimal_digits TSRMLS_DC );
sqlsrv_phptype determine_sqlsrv_php_type( SQLINTEGER sql_type, SQLUINTEGER size, bool prefer_string );
sqlsrv_sqltype determine_sql_type( int php_type, int encoding, zval const* value );
void fetch_common( sqlsrv_stmt* stmt, int fetch_type, zval* return_value, const char* _FN_, bool allow_empty_field_names TSRMLS_DC );
void get_field_common( sqlsrv_stmt* s, const char* _FN_, sqlsrv_phptype sqlsrv_phptype, SQLUSMALLINT field_index, zval**field_value TSRMLS_DC );
void get_field_as_string( sqlsrv_stmt const* s, SQLSMALLINT c_type, SQLUSMALLINT field_index, zval* return_value, const char* _FN_ TSRMLS_DC );
SQLRETURN has_rows( sqlsrv_stmt* stmt, bool& rows_present );
bool is_fixed_size_type( SQLINTEGER sql_type );
bool is_streamable_type( SQLINTEGER sql_type );
bool is_valid_sqlsrv_sqltype( sqlsrv_sqltype type );
bool is_valid_sqlsrv_phptype( sqlsrv_phptype type );
zval* parse_param_array( sqlsrv_stmt const* stmt, const char* _FN_, const zval* param_array, SQLSMALLINT param_num, int& direction, zend_uchar& php_type, SQLSMALLINT& sql_c_type, sqlsrv_sqltype& sql_type,
                         SQLUINTEGER& column_size, SQLSMALLINT& decimal_digits TSRMLS_DC );
bool send_stream_packet( sqlsrv_stmt* stmt, zval* return_value, char const* _FN_ TSRMLS_DC );
void type_and_size_calc( INTERNAL_FUNCTION_PARAMETERS, int type );
void type_and_precision_calc( INTERNAL_FUNCTION_PARAMETERS, int type );
void type_and_encoding( INTERNAL_FUNCTION_PARAMETERS, int type );

}

// statement specific parameter proccessing.  Uses the generic function specialised to return a statement
// resource.
#define PROCESS_PARAMS( rsrc, function, param_spec, ... )                                                       \
    rsrc = process_params<sqlsrv_stmt>( INTERNAL_FUNCTION_PARAM_PASSTHRU, LOG_STMT, const_cast<char*>( function ), param_spec, __VA_ARGS__ ); \
    if( rsrc == NULL ) {                                                                                        \
        RETURN_FALSE;                                                                                           \
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
    SQLRETURN r = SQL_SUCCESS;
    sqlsrv_stmt* stmt = NULL;

    DECL_FUNC_NAME( "sqlsrv_cancel" );
    LOG_FUNCTION;

    // take only the statement resource
    PROCESS_PARAMS( stmt, _FN_, "r" );

    // close the stream to release the resource
    close_active_stream( stmt TSRMLS_CC );

    r = SQLCancel( stmt->ctx.handle );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );

    RETURN_TRUE;
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
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    zval* stmt_r = NULL;
    sqlsrv_stmt* stmt = NULL;

    RETVAL_TRUE;

    DECL_FUNC_NAME( "sqlsrv_free_stmt" );
    LOG_FUNCTION;

    // we do this manually instead of with PROCESS_PARAMS because we return TRUE even if there is a parameter error.
    full_mem_check(MEMCHECK_SILENT);
    reset_errors( TSRMLS_C );

    // take only the statement resource
    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "r", &stmt_r ) == FAILURE ) {

        if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "z", &stmt_r ) == FAILURE ) {
            handle_error( NULL, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
            RETURN_FALSE;
        }
        if( Z_TYPE_P( stmt_r ) == IS_NULL ) {
            RETURN_TRUE;
        }
        else {
            handle_error( NULL, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
            RETURN_FALSE;
        }
    }

    stmt = static_cast<sqlsrv_stmt*>( zend_fetch_resource( &stmt_r TSRMLS_CC, -1, "sqlsrv_stmt", NULL, 1, sqlsrv_stmt::descriptor ));
    if( stmt == NULL ) {
        handle_error( NULL, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
        RETURN_FALSE;
    }

    free_odbc_resources( stmt TSRMLS_CC );

    // this frees up the php resources as well
    remove_from_connection( stmt TSRMLS_CC );

    RETURN_TRUE;
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
    sqlsrv_stmt* stmt = NULL;
    bool executed = false;

    DECL_FUNC_NAME( "sqlsrv_execute" );
    LOG_FUNCTION;

    // take only the statement resource
    PROCESS_PARAMS( stmt, _FN_, "r" );

    CHECK_SQL_ERROR_EX( stmt->prepared == false, stmt, _FN_, SQLSRV_ERROR_STATEMENT_NOT_PREPARED, RETURN_FALSE );

    // reset the parameter currently sending
    stmt->current_parameter = NULL;

    // execute the prepared statement (false means use SQLExecute rather than SQLExecDirect
    executed = sqlsrv_stmt_common_execute( stmt, NULL, 0, false, _FN_ TSRMLS_CC );
    if( !executed ) {
        RETURN_FALSE;
    }
    
    RETURN_TRUE;
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
    SQLRETURN r = SQL_SUCCESS;
    sqlsrv_stmt* stmt = NULL;
    SQLSMALLINT num_cols;

    DECL_FUNC_NAME( "sqlsrv_fetch" );
    LOG_FUNCTION;

    // take only the statement resource
    PROCESS_PARAMS( stmt, _FN_, "r" );
    
    // make sure the statement has been executed
    CHECK_SQL_ERROR_EX( !stmt->executed, stmt, _FN_, SQLSRV_ERROR_STATEMENT_NOT_EXECUTED, RETURN_FALSE );
    CHECK_SQL_ERROR_EX( stmt->past_fetch_end, stmt, _FN_, SQLSRV_ERROR_FETCH_PAST_END, RETURN_FALSE );

    r = SQLNumResultCols( stmt->ctx.handle, &num_cols );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
    CHECK_SQL_ERROR_EX( num_cols == 0, stmt, _FN_, SQLSRV_ERROR_NO_FIELDS, RETURN_FALSE );

    close_active_stream( stmt TSRMLS_CC );

    // move to the next record
    r = SQLFetch( stmt->ctx.handle );
    // return Zend NULL if we're at the end of the result set.
    if( r == SQL_NO_DATA ) {
        stmt->past_fetch_end = true;
        RETURN_NULL();
    }
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );

    // mark that we called fetch (which get_field, et. al. uses) and reset our last field retrieved
    stmt->fetch_called = true;
    stmt->last_field_index = -1;

    RETURN_TRUE;
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
    sqlsrv_stmt* stmt = NULL;
    int fetch_type = SQLSRV_FETCH_BOTH;

    DECL_FUNC_NAME( "sqlsrv_fetch_array" );
    LOG_FUNCTION;

    // retrieve the statement resource and optional fetch type (see enum SQLSRV_FETCH_TYPE)
    PROCESS_PARAMS( stmt, _FN_, "r|l", &fetch_type );

    // retrieve the hash table directly into the return_value variable for return.  Any errors
    // are handled directly in fetch_common.
    fetch_common( stmt, fetch_type, return_value, _FN_, true TSRMLS_CC );
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
// see How to: Retrieve Data as an Object (SQL Server Driver for PHP).
// 
// If a field with no name is returned, sqlsrv_fetch_object will discard the
// field value and issue a warning.

PHP_FUNCTION( sqlsrv_fetch_object )
{
    sqlsrv_stmt* stmt = NULL;
    int zr = SUCCESS;
    // stdClass is the name of the system's default base class in PHP
    emalloc_auto_ptr<char> stdclass_name;
    stdclass_name = estrdup( STDCLASS_NAME );
    char* class_name = stdclass_name;
    int class_name_len = STDCLASS_NAME_LEN;
    zval* ctor_params_z = NULL;

    DECL_FUNC_NAME( "sqlsrv_fetch_object" );
    LOG_FUNCTION;

    // retrieve the statement resource and optional fetch type (see enum SQLSRV_FETCH_TYPE)
    PROCESS_PARAMS( stmt, _FN_, "r|sa", &class_name, &class_name_len, &ctor_params_z );

    // fetch the fields into an associative hash table
    fetch_common( stmt, SQLSRV_FETCH_ASSOC, return_value, _FN_, false TSRMLS_CC );
    if( Z_TYPE_P( return_value ) != IS_ARRAY ) {
        return;
    }
    HashTable* properties_ht = Z_ARRVAL_P( return_value );

    // find the zend_class_entry of the class the user requested (stdClass by default) for use below
    zend_class_entry** class_entry;
    zend_str_tolower( class_name, class_name_len );
    zr = zend_lookup_class( class_name, class_name_len, &class_entry TSRMLS_CC );
    if( zr == FAILURE ) {
        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_ZEND_BAD_CLASS TSRMLS_CC, class_name );
        zend_hash_destroy( Z_ARRVAL_P( return_value ));
        FREE_HASHTABLE( Z_ARRVAL_P( return_value ));
        RETURN_FALSE;
    }

    // create the instance of the object
    // create an instance of the object with its default properties
    // we pass NULL for the properties so that the object will be populated by its default properties
    zr = object_and_properties_init( return_value, *class_entry, NULL );
    if( zr == FAILURE ) {
        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_ZEND_OBJECT_FAILED TSRMLS_CC, class_name );
        zend_hash_destroy( properties_ht );
        FREE_HASHTABLE( properties_ht );
        RETURN_FALSE;
    }

    // merge in the "properties" (associative array) returned from the fetch doing this vice versa
    // (putting properties_ht into object_and_properties_init and merging the default properties)
    // causes duplicate properties when the visibilities are different and also references the
    // default parameters directly in the object, meaning the default property value is changed when
    // the object's property is changed.
    zend_merge_properties( return_value, properties_ht, 1 TSRMLS_CC );

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
    if( (*class_entry)->constructor ) {

        // take the parameters given as our last argument and put them into a sequential array
        emalloc_auto_ptr<zval**> params_m;
        zval_auto_ptr ctor_retval_z;
        ALLOC_INIT_ZVAL( ctor_retval_z );
        int num_params = 0;
        if( ctor_params_z != NULL ) {
            HashTable* ctorp_ht = Z_ARRVAL_P( ctor_params_z );
            num_params = zend_hash_num_elements( ctorp_ht );
            params_m = reinterpret_cast<zval***>( emalloc( num_params * sizeof( zval**) ));

            int i;
            for( i = 0, zend_hash_internal_pointer_reset( ctorp_ht );
                 zend_hash_has_more_elements( ctorp_ht ) == SUCCESS;
                 zend_hash_move_forward( ctorp_ht ), ++i ) {

                zr = zend_hash_get_current_data_ex( ctorp_ht, reinterpret_cast<void**>(&(params_m[ i ])), NULL );
                if( zr == FAILURE ) {
                    zval_ptr_dtor( &return_value );
                    handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_ZEND_OBJECT_FAILED TSRMLS_CC, class_name );
                    zend_hash_destroy( properties_ht );
                    FREE_HASHTABLE( properties_ht );
                    RETURN_FALSE;
                }
            }
        }
        
        // call the constructor function itself.
        zend_fcall_info fci;
        zend_fcall_info_cache fcic;
        memset( &fci, 0, sizeof( fci ));
        fci.size = sizeof( fci );
        fci.function_table = &(*class_entry)->function_table;
        fci.function_name = NULL;
        fci.retval_ptr_ptr = &ctor_retval_z;
        fci.param_count = num_params;
        fci.params = params_m;  // purposefully not transferred since ownership isn't actually transferred.
#if PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION <= 2
        fci.object_pp = &return_value;
#else
        fci.object_ptr = return_value;
#endif
        memset( &fcic, 0, sizeof( fcic ));
        fcic.initialized = 1;
        fcic.function_handler = (*class_entry)->constructor;
        fcic.calling_scope = *class_entry;
#if PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION <= 2
        fcic.object_pp = &return_value;
#else
        fcic.object_ptr = return_value;
#endif
        zr = zend_call_function( &fci, &fcic TSRMLS_CC );
        if( zr == FAILURE ) {
            zval_ptr_dtor( &return_value );
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_ZEND_OBJECT_FAILED TSRMLS_CC, class_name );
            zend_hash_destroy( properties_ht );
            FREE_HASHTABLE( properties_ht );
            RETURN_FALSE;
        }
    }
}


// sqlsrv_field_metadata( resource $stmt)
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
    SQLRETURN r = SQL_SUCCESS;
    int zr = SUCCESS;
    sqlsrv_stmt* stmt = NULL;
    SQLSMALLINT num_cols = -1;
    SQLUSMALLINT field_name_len_max = USHRT_MAX;
    SQLSMALLINT temp = 0;
    emalloc_auto_ptr<char> field_name_temp;
    SQLSMALLINT field_name_len = -1;
    SQLSMALLINT field_type = 0;
    SQLULEN field_size = ULONG_MAX;
    SQLSMALLINT field_scale = -1;
    SQLSMALLINT field_is_nullable = 0;
    
    DECL_FUNC_NAME( "sqlsrv_field_metadata" );
    LOG_FUNCTION;

    // take the statement resource only
    PROCESS_PARAMS( stmt, _FN_, "r" );

    // get the number of fields in the resultset
    r = SQLNumResultCols( stmt->ctx.handle, &num_cols );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
    
    // get the maximum length of a field name
    r = SQLGetInfo( stmt->conn->ctx.handle, SQL_MAX_COLUMN_NAME_LEN, &field_name_len_max, sizeof( field_name_len_max ), &temp );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );

    zval_auto_ptr result_meta_data;
    ALLOC_INIT_ZVAL( result_meta_data );
    zr = array_init( result_meta_data );
    CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );

    for( SQLSMALLINT f = 1; f <= num_cols; ++f ) {
        
        field_name_temp = static_cast<char*>( emalloc( field_name_len_max + 1 ));

        // retrieve the field information
        SQLSRV_STATIC_ASSERT( sizeof( char ) == sizeof( SQLCHAR ));
        r = SQLDescribeCol( stmt->ctx.handle, f, reinterpret_cast<SQLCHAR*>( field_name_temp.get()), field_name_len_max, &field_name_len,
            &field_type, &field_size, &field_scale, &field_is_nullable );
        CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE; );
        CHECK_SQL_WARNING( r, stmt, _FN_, NULL );

        zval_auto_ptr field_meta_data;
        ALLOC_INIT_ZVAL( field_meta_data );
        zr = array_init( field_meta_data );
        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );

        // add the name to the array
        zr = add_assoc_string( field_meta_data, "Name", field_name_temp, 0 );
        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
        field_name_temp.transferred();

        // add the type to the array
        zr = add_assoc_long( field_meta_data, "Type", field_type );
        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );

        // depending on field type, we add the values into size or precision/scale and NULL out the other fields
        switch( field_type ) {
            case SQL_DECIMAL:
            case SQL_NUMERIC:
                zr = add_assoc_null( field_meta_data, "Size" );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                zr = add_assoc_long( field_meta_data, "Precision", field_size );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                zr = add_assoc_long( field_meta_data, "Scale", field_scale );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                break;
            case SQL_BIT:
            case SQL_TINYINT:
            case SQL_SMALLINT:
            case SQL_INTEGER:
            case SQL_BIGINT:
            case SQL_REAL:
            case SQL_FLOAT:
            case SQL_DOUBLE:
                zr = add_assoc_null( field_meta_data, "Size" );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                zr = add_assoc_long( field_meta_data, "Precision", field_size );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                zr = add_assoc_null( field_meta_data, "Scale" );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                break;
            default:
                zr = add_assoc_long( field_meta_data, "Size", field_size );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                zr = add_assoc_null( field_meta_data, "Precision" );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                zr = add_assoc_null( field_meta_data, "Scale" );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                break;
        }

        // add the nullability to the array
        zr = add_assoc_long( field_meta_data, "Nullable", field_is_nullable );
        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
       
        // add this field's meta data to the result set meta data
        zr = add_next_index_zval( result_meta_data, field_meta_data );
        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
        field_meta_data.transferred();
    }

    // return our built collection and transfer ownership
    zval_ptr_dtor( &return_value );
    *return_value_ptr = result_meta_data;
    result_meta_data.transferred();
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
// types, see SQLSRV Constants (SQL Server Driver for PHP). If no return
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
    SQLRETURN r;
    sqlsrv_stmt* stmt = NULL;
    sqlsrv_phptype sqlsrv_php_type;
    sqlsrv_php_type.typeinfo.type = PHPTYPE_INVALID;
    long field_index = -1;
    SQLLEN field_type = 0;
    SQLLEN field_len = -1;

    DECL_FUNC_NAME( "sqlsrv_get_field" );
    LOG_FUNCTION;

    // get the statement, the field index, and the optional type to return it as
    PROCESS_PARAMS( stmt, _FN_, "rl|l", &field_index, &sqlsrv_php_type );

    // validate the field index is within range
    SQLSMALLINT num_cols = 0;
    r = SQLNumResultCols( stmt->ctx.handle, &num_cols );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE; );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );    
    if( field_index < 0 || field_index >= num_cols ) {
        handle_error( &stmt->ctx, current_log_subsystem, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
        RETURN_FALSE;
    }

    // make sure the statement was actually executed and not just prepared
    CHECK_SQL_ERROR_EX( !stmt->executed, stmt, _FN_, SQLSRV_ERROR_STATEMENT_NOT_EXECUTED, RETURN_FALSE );

    // if they didn't specify a type as a parameter (so it still equals its initial value of PHPTYPE_INVALID)
    if( sqlsrv_php_type.typeinfo.type == PHPTYPE_INVALID ) {
        // get the SQL type of the field
        r = SQLColAttribute( stmt->ctx.handle, static_cast<SQLUSMALLINT>( field_index + 1 ), SQL_DESC_CONCISE_TYPE, NULL, 0, NULL, &field_type );
        CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
        CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
        // also get its length (to separate var*(max) fields from normal var* fields
        r = SQLColAttribute( stmt->ctx.handle, static_cast<SQLUSMALLINT>( field_index + 1 ), SQL_DESC_LENGTH, NULL, 0, NULL, &field_len );
        CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
        CHECK_SQL_WARNING( r, stmt, _FN_, NULL );

        // get the default type to return
        sqlsrv_php_type = determine_sqlsrv_php_type( field_type, field_len, false );
    }

    // verify that we have an acceptable type to convert.
    CHECK_SQL_ERROR_EX( !is_valid_sqlsrv_phptype( sqlsrv_php_type ), stmt, _FN_, SQLSRV_ERROR_INVALID_TYPE, RETURN_FALSE );

    // retrieve the data
    get_field_common( stmt, _FN_, sqlsrv_php_type, static_cast<SQLUSMALLINT>( field_index ), &return_value TSRMLS_CC );
}


// sqlsrv_next_result( resource $stmt )
//  
// Makes the next result (result set, row count, or output parameter) of the
// specified statement active.  The first (or only) result returned by a batch
// query or stored procedure is active without a call to sqlsrv_next_result.
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
    SQLRETURN r = SQL_SUCCESS;
    sqlsrv_stmt* stmt = NULL;

    DECL_FUNC_NAME( "sqlsrv_next_result" );
    LOG_FUNCTION;

    // get the statement resource
    PROCESS_PARAMS( stmt, _FN_, "r" );
    
    // make sure that the statement has at least been executed
    CHECK_SQL_ERROR_EX( !stmt->executed, stmt, _FN_, SQLSRV_ERROR_STATEMENT_NOT_EXECUTED, RETURN_FALSE );
    CHECK_SQL_ERROR_EX( stmt->past_next_result_end, stmt, _FN_, SQLSRV_ERROR_NEXT_RESULT_PAST_END, RETURN_FALSE );

    close_active_stream( stmt TSRMLS_CC );

    // call the ODBC API that does what we want
    r = SQLMoreResults( stmt->ctx.handle );
    if( r == SQL_NO_DATA ) {

        if( stmt->param_output_strings ) {
            if( !adjust_output_string_lengths( stmt, _FN_ TSRMLS_CC )) {
                RETURN_FALSE;
            }
        }

        // if we're at the end, then return NULL
        stmt->past_next_result_end = true;
        RETURN_NULL();
    }
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE; );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );

    stmt->new_result_set();

    RETURN_TRUE;
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
    SQLRETURN r = SQL_SUCCESS;
    sqlsrv_stmt* stmt = NULL;
    SQLSMALLINT fields = -1;

    DECL_FUNC_NAME( "sqlsrv_num_fields" );
    LOG_FUNCTION;

    // get the statement resource
    PROCESS_PARAMS( stmt, _FN_, "r" );

    // retrieve the number of columns from ODBC
    r = SQLNumResultCols( stmt->ctx.handle, &fields );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE; );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
    
    // return it to the script
    RETURN_LONG( fields );
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
    SQLRETURN r = SQL_SUCCESS;
    sqlsrv_stmt* stmt = NULL;
    SQLINTEGER rows = -1;

    DECL_FUNC_NAME( "sqlsrv_rows_affected" );
    LOG_FUNCTION;

    // get the statement resource
    PROCESS_PARAMS( stmt, _FN_, "r" );
    
    // make sure it was executed
    CHECK_SQL_ERROR_EX( !stmt->executed, stmt, _FN_, SQLSRV_ERROR_STATEMENT_NOT_EXECUTED, RETURN_FALSE );

    // get the row count from ODBC...
    r = SQLRowCount( stmt->ctx.handle, &rows );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE; );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
    
    // and return it
    RETURN_LONG( rows );
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
    
    DECL_FUNC_NAME( "sqlsrv_send_stream_data" );    
    LOG_FUNCTION;

    // get the statement resource that we've bound streams to
    PROCESS_PARAMS( stmt, _FN_, "r" );

    // if everything was sent at execute time, just return that there is nothing more to send.
    if( stmt->send_at_exec ) {
        RETURN_NULL();
    }

    // send the next packet.  The return_value parameter will be set to whatever the result was,
    // so we just forward that as is.
    send_stream_packet( stmt, return_value, _FN_ TSRMLS_CC );
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


// *** helper functions ***
// These are functions that deal with statement objects but are called from other modules, such as connection (conn.cpp)

bool sqlsrv_stmt_common_execute( sqlsrv_stmt* stmt, const SQLCHAR* sql_string, int /*sql_len*/, bool direct, const char* _FN_ TSRMLS_DC )
{
    SQLRETURN r;
    SQLSMALLINT i;

    close_active_stream( stmt TSRMLS_CC );

    if( stmt->executed ) {
        do {
            r = SQLMoreResults( stmt->ctx.handle );
            CHECK_SQL_ERROR_EX( r == SQL_ERROR, stmt, _FN_, NULL, return false; );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
        } while( r != SQL_NO_DATA );
    }

    stmt->free_param_data();

    stmt->executed = false;

    if( stmt->params_z ) {

    
        HashTable* params_ht = Z_ARRVAL_P( stmt->params_z );

        // allocate the buffer size array used by SQLBindParameter if it wasn't allocated by a 
        // previous execution.  The size of the array cannot change because the number of parameters
        // cannot change in between executions.
        if( stmt->params_ind_ptr == NULL ) {
            stmt->params_ind_ptr = static_cast<SQLINTEGER*>( emalloc( zend_hash_num_elements( params_ht ) * sizeof( SQLINTEGER )));
        }

        for( i = 1, zend_hash_internal_pointer_reset( params_ht );
             zend_hash_has_more_elements( params_ht ) == SUCCESS;
             zend_hash_move_forward( params_ht ), ++i ) {

            zval** param_zz = NULL;
            zval *param_z = NULL;
            int success = FAILURE;
            SQLSMALLINT sql_c_type = SQL_C_BINARY;
            SQLUINTEGER column_size = 0;
            SQLSMALLINT decimal_digits = 0;
            SQLPOINTER buffer = NULL;
            SQLUINTEGER buffer_len = 0;
            sqlsrv_sqltype sql_type;
            sql_type.typeinfo.type = SQL_BINARY;
            sql_type.typeinfo.size = SQLSRV_INVALID_SIZE;
            sql_type.typeinfo.scale = SQLSRV_INVALID_SCALE;
            int direction = SQL_PARAM_INPUT;
            zend_uchar php_type = PHPTYPE_INVALID;

            success = zend_hash_get_current_data( params_ht, (void**) &param_zz );
            CHECK_SQL_ERROR_EX( success == FAILURE, stmt, _FN_, SQLSRV_ERROR_VAR_REQUIRED, return false; );
            param_z = *static_cast<zval**>( param_zz );

            // if the user gave us a parameter array
            if( Z_TYPE_P( param_z ) == IS_ARRAY ) {

                param_z = parse_param_array( stmt, _FN_, param_z, i, direction, php_type, sql_c_type, sql_type,
                                             column_size, decimal_digits TSRMLS_CC );
                // an error occurred, so return false
                if( param_z == NULL ) {
                            return false;
                        }
                    }
            // otherwise use the defaults
            else {
            
                bool success = determine_param_defaults( stmt, _FN_, param_z, i, php_type, direction, sql_type, 
                                                         sql_c_type, column_size, decimal_digits TSRMLS_CC );
                if( !success ) {
                    return false;
                }
            }

            switch( php_type ) {
            
                case IS_NULL:
                    CHECK_SQL_ERROR_EX( direction == SQL_PARAM_INPUT_OUTPUT || direction == SQL_PARAM_OUTPUT, stmt, _FN_,
                        SQLSRV_ERROR_INVALID_OUTPUT_PARAM_TYPE, SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS ); return false; );
                    buffer = NULL;
                    buffer_len = 0;
                    stmt->params_ind_ptr[ i-1 ] = SQL_NULL_DATA;
                    break;
                case IS_LONG:
                    buffer = &param_z->value;
                    buffer_len = sizeof( param_z->value.lval );
                    stmt->params_ind_ptr[ i-1 ] = buffer_len;
                    break;
                case IS_DOUBLE:
                    buffer = &param_z->value;
                    buffer_len = sizeof( param_z->value.dval );
                    stmt->params_ind_ptr[ i-1 ] = buffer_len;
                    break;
                case IS_STRING:
                    buffer = Z_STRVAL_PP( &param_z );
                    buffer_len = Z_STRLEN_PP( &param_z );
                    // resize the buffer to match the column size if it's smaller
                    // than the buffer given already
                    if(( direction == SQL_PARAM_INPUT_OUTPUT || direction == SQL_PARAM_OUTPUT )) {
                        if( stmt->param_output_strings == NULL ) {
                            ALLOC_INIT_ZVAL( stmt->param_output_strings );
                            int zr = array_init( stmt->param_output_strings );
                            CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, return false );
                        }
                        // if we don't have enough space, then reallocate the buffer (and copy its contents)
                        // the string keeps its original content until it is updated by the output, at which
                        // time its length will be set to match the output in adjust_output_string_parameters
                        if( buffer_len < column_size ) {
                        buffer = static_cast<char*>( erealloc( buffer, column_size + 1 ));
                        buffer_len = column_size + 1;
                            reinterpret_cast<char*>( buffer )[ column_size ] = '\0';
                            ZVAL_STRINGL( param_z, reinterpret_cast<char*>( buffer ), Z_STRLEN_P( param_z ), 0 );
                        }
                        // register the output string so that it will be updated when adjust_output_string_parameters is called
                        sqlsrv_output_string output_string( param_z, i - 1 );
                        HashTable* strings_ht = Z_ARRVAL_P( stmt->param_output_strings );
                        int next_index = zend_hash_next_free_element( strings_ht );
                        int zr = zend_hash_index_update( strings_ht, next_index, &output_string, sizeof( output_string ), NULL );
                        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, return false );
                        zval_add_ref( &param_z );   // we have a reference to the param in the statement
                    }
                    stmt->params_ind_ptr[ i-1 ] = buffer_len;
                    break;
                case IS_OBJECT:
                {
                    char* class_name;
                    zend_uint class_name_len;
                    zval_auto_ptr function_z;
                    zval_auto_ptr buffer_z;
                    zval_auto_ptr format_z;
                    zval* params[2];
                    int result;

                    // verify this is a DateTime object
                    if( zend_get_object_classname( param_z, &class_name, &class_name_len TSRMLS_CC ) == FAILURE ) {

                        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE TSRMLS_CC, i );
                        SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
                        return false;
                    }
                    if( class_name_len != DATETIME_CLASS_NAME_LEN || stricmp( class_name, DATETIME_CLASS_NAME )) {

                        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE TSRMLS_CC, i );
                        SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
                        return false;
                    }
                    CHECK_SQL_ERROR_EX( direction == SQL_PARAM_INPUT_OUTPUT || direction == SQL_PARAM_OUTPUT, stmt, _FN_,
                        SQLSRV_ERROR_INVALID_OUTPUT_PARAM_TYPE, SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS ); return false; );
                    // call the PHP function date_format to convert the object to a string that SQL Server understands
                    ALLOC_INIT_ZVAL( buffer_z );
                    ALLOC_INIT_ZVAL( function_z );
                    ALLOC_INIT_ZVAL( format_z );
                    ZVAL_STRINGL( function_z, "date_format", sizeof( "date_format" ) - 1, 1 );
                    ZVAL_STRINGL( format_z, const_cast<char*>( DATETIME_FORMAT ), DATETIME_FORMAT_LEN, 1 );
                    params[0] = param_z;
                    params[1] = format_z;
                    result = call_user_function( EG( function_table ), NULL, function_z, buffer_z, 2, params TSRMLS_CC );
                    if( result == FAILURE ) {

                        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE TSRMLS_CC, i );
                        SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
                        zval_ptr_dtor( &buffer_z );
                        return false;
                    }
                    buffer = Z_STRVAL_P( buffer_z );
                    // save the buffer we allocated for the date time string conversion
                    if( stmt->param_datetime_buffers == NULL ) {
                        ALLOC_INIT_ZVAL( stmt->param_datetime_buffers );
                        int zr = array_init( stmt->param_datetime_buffers );
                        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, return false );
                    }
                    int zr = add_next_index_zval( stmt->param_datetime_buffers, buffer_z );
                    CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, return false );
                    buffer_len = Z_STRLEN_P( buffer_z );
                    buffer_z.transferred();
                    stmt->params_ind_ptr[ i-1 ] = buffer_len;
                    break;
                }
                case IS_RESOURCE:
                {
                    CHECK_SQL_ERROR_EX( direction == SQL_PARAM_INPUT_OUTPUT || direction == SQL_PARAM_OUTPUT, stmt, _FN_,
                        SQLSRV_ERROR_INVALID_OUTPUT_PARAM_TYPE, SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS ); return false; );
                    php_stream* param_stream = NULL;
                    php_stream_from_zval_no_verify( param_stream, &param_z );
                    buffer = param_z;
                    zval_add_ref( &param_z ); // so that it doesn't go away while we're using it
                    buffer_len = 0;
                    stmt->params_ind_ptr[ i-1 ] = SQL_DATA_AT_EXEC;
                    break;                    
                }
                default:
                    handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAM_TYPE TSRMLS_CC );
                    SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
                    return false;
            }

            if( direction  < 0 || direction > 0xffff ) DIE( "direction not valid SQLSMALLINT" );
            r = SQLBindParameter( stmt->ctx.handle, i, static_cast<SQLSMALLINT>( direction ), sql_c_type, sql_type.typeinfo.type, column_size, decimal_digits,
                                  buffer, buffer_len, &stmt->params_ind_ptr[ i-1 ] );
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS ); return false; );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
        }
    }

    // execute the statement
    if( direct ) {
        if( sql_string == NULL ) DIE( "sqlsrv_stmt_common_execute: sql_string must be valid when direct = true");
        r = SQLExecDirect( stmt->ctx.handle, const_cast<SQLCHAR*>( sql_string ), SQL_NTS /*sql_len*/  );
    }
    else {
        if( sql_string != NULL ) DIE( "sqlsrv_stmt_common_execute: sql_string must be NULL when direct = false");
        r = SQLExecute( stmt->ctx.handle );
    }

    // if stream parameters were bound
    if( r == SQL_NEED_DATA ) {

        // if they are to be sent at execute time, then send them now.
        if( stmt->send_at_exec == true ) {

            zval return_value;
            while( send_stream_packet( stmt, &return_value, _FN_ TSRMLS_CC )) { }
            if( Z_TYPE( return_value ) != IS_NULL ) {
                return false;
            }
        }
    }
    // if no result set was generated just adjust any output string parameter lengths
    else if( r == SQL_NO_DATA ) {
        bool adjusted = adjust_output_string_lengths( stmt, _FN_ TSRMLS_CC );
        if( !adjusted ) {
            return false;
        }
    }
    else {

        CHECK_SQL_ERROR( r, stmt, _FN_, NULL, SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS ); return false; );
        CHECK_SQL_WARNING( r, stmt, _FN_, NULL );

        // if we succeeded and are still here, then handle output string parameters
        if( SQL_SUCCEEDED( r )) {

            bool rows_present;
            r = has_rows( stmt, rows_present );
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS ); return false; );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
            if( !rows_present ) {
                bool adjusted = adjust_output_string_lengths( stmt, _FN_ TSRMLS_CC );
                if( !adjusted ) {
                    return false;
                }
            }
        }
    }
    
    stmt->new_result_set();
    stmt->executed = true;

    return true;
}

void free_odbc_resources( sqlsrv_stmt* stmt TSRMLS_DC )
{
    SQLRETURN r = SQL_SUCCESS;

    // release any cached field data we had
    stmt->new_result_set();

    // release a parameter array if we had one
    if( stmt->params_z ) {
        zval_ptr_dtor( &stmt->params_z );
        stmt->params_z = NULL;
        // free the parameter size buffer if we had one (if the statement was executed)
        if( stmt->params_ind_ptr ) {
            efree( stmt->params_ind_ptr );
        }
    }

    close_active_stream( stmt TSRMLS_CC );

    if( stmt->param_buffer != NULL ) {
        efree( stmt->param_buffer );
        stmt->param_buffer = NULL;
    }

    r = SQLFreeHandle( SQL_HANDLE_STMT, stmt->ctx.handle );
    
    // we don't handle errors here because the error log may have already gone away.  We just log them.
    if( !SQL_SUCCEEDED( r ) ) {
        LOG( SEV_ERROR, LOG_STMT, "Failed to free statement handle %1!d!", stmt->ctx.handle );
    }

    // mark the statement as closed
    stmt->ctx.handle = SQL_NULL_HANDLE;
}

void free_php_resources( zval* stmt_z TSRMLS_DC )
{
    sqlsrv_stmt* stmt = NULL;

    stmt = static_cast<sqlsrv_stmt*>( zend_fetch_resource( &stmt_z TSRMLS_CC, -1, "sqlsrv_stmt", NULL, 1, sqlsrv_stmt::descriptor ));
    if( stmt == NULL ) {
        LOG( SEV_WARNING, current_log_subsystem, "Statement resource %1!d! already released", Z_RESVAL_P( stmt_z ));
        return;
    }

    stmt->free_param_data();

    // cause any variables still holding a reference to this to be invalid so
    // they cause an error when passed to a sqlsrv function.  If the removal fails, 
    // we log it.
    int zr = zend_hash_index_del( &EG( regular_list ), Z_RESVAL_P( stmt_z ));
    if( zr == FAILURE ) {
        LOG( SEV_ERROR, LOG_STMT, "Failed to remove stmt resource %1!d!", Z_RESVAL_P( stmt_z ));
    }
    else {

        // stmt won't leak if this isn't hit, since Zend cleans up the heap at the end of each request/script
        efree( stmt );
    }

    ZVAL_NULL( stmt_z );
    zval_ptr_dtor( &stmt_z );
}

void remove_from_connection( sqlsrv_stmt* stmt TSRMLS_DC )
{
    if( stmt->ctx.handle != NULL ) DIE( "Statement ODBC resources not released prior to removing from the connection" );

    // delete the entry in the connection's statement list
    LOG( SEV_NOTICE, LOG_STMT, "Deleting statement index %1!d! from the connection", stmt->conn_index );
    if( zend_hash_index_del( stmt->conn->stmts, stmt->conn_index ) == FAILURE ) {
        DIE( "Couldn't delete statement index %d from the connection", stmt->conn_index );
    }

    // set the connection to released
    stmt->conn = NULL;
}

// sqlsrv_stmt_hash_dtor
// called when the entry in the connection's list of statements is deleted

void sqlsrv_stmt_hash_dtor( void* stmt_ptr )
{
    zval* stmt_z = *(static_cast<zval**>( stmt_ptr ));

    if( Z_REFCOUNT_P( stmt_z ) <= 0 ) {
        DIE( "Statement refcount should be > 0 when deleting from the connection's statement list" );
    }

    sqlsrv_stmt* stmt = NULL;
    
    TSRMLS_FETCH();

    stmt = static_cast<sqlsrv_stmt*>( zend_fetch_resource( &stmt_z TSRMLS_CC, -1, "sqlsrv_stmt", NULL, 1, sqlsrv_stmt::descriptor ));
    if( !stmt )
        return;

    stmt->conn = NULL;  // remove the connection so the statement resource destructor won't try to remove itself from the connection's list

    if( stmt->ctx.handle != SQL_NULL_HANDLE ) {
        free_odbc_resources( stmt TSRMLS_CC );
    }

    free_php_resources( stmt_z TSRMLS_CC );
}

void __cdecl sqlsrv_stmt_dtor( zend_rsrc_list_entry *rsrc TSRMLS_DC )
{
    // get the structure
    sqlsrv_stmt *stmt = static_cast<sqlsrv_stmt*>( rsrc->ptr );
    LOG( SEV_NOTICE, LOG_STMT, "sqlsrv_stmt_dtor: entering" );

    if( stmt->ctx.handle != SQL_NULL_HANDLE ) {
        free_odbc_resources( stmt TSRMLS_CC );
    }

    if( stmt->conn ) {
        remove_from_connection( stmt TSRMLS_CC );
    }
}

// centralized place to release all the parameter data that accrues during the execution
// phase.
void sqlsrv_stmt::free_param_data( void )
{
    // if we allocated any output string parameters in a previous execution, release them here.
    if( param_output_strings ) {
        zval_ptr_dtor( &param_output_strings );
        param_output_strings = NULL;
    }

    // if we allocated any datetime strings in a previous execution, release them here.
    if( param_datetime_buffers ) {
        zval_ptr_dtor( &param_datetime_buffers );
        param_datetime_buffers = NULL;
    }
}


// to be called whenever a new result set is created, such as after an
// execute or next_result.  Resets the state variables.
void sqlsrv_stmt::new_result_set( void )
{
    fetch_called = false;
    if( fetch_fields ) {
        for( int i = 0; i < fetch_fields_count; ++i ) {
            efree( fetch_fields[ i ].name );
        }
        efree( fetch_fields );
    }
    fetch_fields = NULL;
    fetch_fields_count = 0;
    last_field_index = -1;
    past_fetch_end = false;
    past_next_result_end = false;
}

// *** internal functions ***

namespace {

SQLRETURN has_rows( sqlsrv_stmt* stmt, bool& rows_present )
{
    // Use SQLNumResultCols to determine if we have rows or not.
    SQLRETURN r;
    SQLSMALLINT num_cols;
    r = SQLNumResultCols( stmt->ctx.handle, &num_cols );
    rows_present = (num_cols != 0);
    return r;
}

// adjust_output_string_lengths
// called after all result sets are consumed or if there are no results sets, this function adjusts the length
// of any output string parameters to the length returned by ODBC in the ind_ptr buffer passed as to SQLBindParameter
bool adjust_output_string_lengths( sqlsrv_stmt* stmt, const char* _FN_ TSRMLS_DC )
{
    if( stmt->param_output_strings == NULL ) 
        return true;

    HashTable* params_ht = Z_ARRVAL_P( stmt->param_output_strings );

    for( zend_hash_internal_pointer_reset( params_ht );
         zend_hash_has_more_elements( params_ht ) == SUCCESS;
         zend_hash_move_forward( params_ht ) ) {

        sqlsrv_output_string *output_string;
        int zr = zend_hash_get_current_data( params_ht, (void**) &output_string );
        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, zval_ptr_dtor( &stmt->param_output_strings ); return false; );
        char* str = Z_STRVAL_P( output_string->string_z );
        int str_len = stmt->params_ind_ptr[ output_string->param_num ];
        ZVAL_STRINGL( output_string->string_z, str, str_len, 0 );
    }

    zval_ptr_dtor( &stmt->param_output_strings );
    stmt->param_output_strings = NULL;

    return true;
}

// check_for_next_stream_parameter
// check for the next stream parameter.  Returns true if another parameter is ready, false if either an error
// or there are no more parameters.
bool check_for_next_stream_parameter( __inout sqlsrv_stmt* stmt, __out zval* return_value TSRMLS_DC )
{
    zval* param_z = NULL;
    SQLRETURN r = SQL_SUCCESS;

    RETVAL_TRUE;

    r = SQLParamData( stmt->ctx.handle, reinterpret_cast<SQLPOINTER*>( &param_z ));
    // if there is a waiting parameter, make it current
    if( r == SQL_NEED_DATA ) {
        stmt->current_parameter = param_z;
        stmt->current_parameter_read = 0;
    }
    // otherwise if it wasn't an error, we've exhausted the bound parameters, so return that we're done
    else if( SQL_SUCCEEDED( r ) || r == SQL_NO_DATA ) {
        CHECK_SQL_WARNING( r, stmt, "sqlsrv_send_stream_data", NULL );             
        RETVAL_NULL();
        return false;
    }
    // otherwise, record the error and return false
    else {
        CHECK_SQL_ERROR( r, stmt, "sqlsrv_send_stream_data", NULL, SQLCancel( stmt->ctx.handle ); RETVAL_FALSE; return false; );
    }

    // there are more parameters
    return true;
}


// get_field_common
// common code shared between sqlsrv_get_field and sqlsrv_fetch_array.  The "return value" is transferred via the field_value
// parameter, FALSE being when an error occurs.
void get_field_common( __inout sqlsrv_stmt* stmt, const char* _FN_, sqlsrv_phptype sqlsrv_phptype, SQLUSMALLINT field_index, __out zval**field_value TSRMLS_DC )
{
    SQLRETURN r;

    close_active_stream( stmt TSRMLS_CC );

    // make sure that fetch is called before trying to retrieve to return a helpful sqlsrv error
    CHECK_SQL_ERROR_EX( !stmt->fetch_called, stmt, _FN_, SQLSRV_ERROR_FETCH_NOT_CALLED, ZVAL_FALSE( *field_value ); return; );

    // make sure they're not trying to retrieve fields incorrectly.  Otherwise return a helpful sqlsrv error
    if( stmt->last_field_index > field_index ) {
        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_FIELD_INDEX_ERROR TSRMLS_CC, field_index, stmt->last_field_index );
        ZVAL_FALSE( *field_value );
        return;
    }

    // what we do is based on the PHP type to be returned.
    switch( sqlsrv_phptype.typeinfo.type ) {

        // call a refactored routine get_field_as_string
        case SQLSRV_PHPTYPE_STRING:
        {
            SQLSMALLINT c_type = ( sqlsrv_phptype.typeinfo.encoding == SQLSRV_ENCODING_CHAR ) ? SQL_C_CHAR : SQL_C_BINARY;
            get_field_as_string( stmt, c_type, field_index, *field_value, _FN_ TSRMLS_CC );
            if( Z_TYPE_PP( field_value ) == IS_BOOL && Z_LVAL_PP( field_value ) == 0 ) {
                return;
            }
        }
        break;

        // create a stream wrapper around the field and return that object to the PHP script.  calls to fread on the stream
        // will result in calls to SQLGetData.  This is handled in stream.cpp.  See that file for how these fields are used.
        case SQLSRV_PHPTYPE_STREAM:
        {
            php_stream* stream;
            sqlsrv_stream* ss;
            SQLINTEGER sql_type;
            SQLINTEGER sql_display_size;
            
            r = SQLColAttribute( stmt->ctx.handle, field_index + 1, SQL_DESC_TYPE, NULL, 0, NULL, &sql_type );                
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, ZVAL_FALSE( *field_value ); return; );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );            
            r = SQLColAttribute( stmt->ctx.handle, field_index + 1, SQL_DESC_DISPLAY_SIZE, NULL, 0, NULL, &sql_display_size );
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, ZVAL_FALSE( *field_value ); return; );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );            
            CHECK_SQL_ERROR_EX( !is_streamable_type( sql_type ), stmt, _FN_, SQLSRV_ERROR_STREAMABLE_TYPES_ONLY, ZVAL_FALSE( *field_value ); return; );


            stream = php_stream_open_wrapper( "sqlsrv://teststuff", "r", 0, NULL );
            CHECK_SQL_ERROR_EX( !stream, stmt, _FN_, SQLSRV_ERROR_STREAM_CREATE, ZVAL_FALSE( *field_value ); return; );
            ss = static_cast<sqlsrv_stream*>( stream->abstract );
            ss->stmt = stmt;
            ss->field = field_index;
            if( sql_type > SHRT_MAX || sql_type < SHRT_MIN ) DIE( "sql_type out of range for short integer.  SQLColAttribute probably returned a bad result." );
            ss->sql_type = static_cast<SQLUSMALLINT>( sql_type );
            ss->encoding = sqlsrv_phptype.typeinfo.encoding;
            // turn our stream into a zval to be returned
            php_stream_to_zval( stream, *field_value );
            zval_add_ref( field_value );   // this is released in sqlsrv_stream_close
            // mark this as our active stream
            stmt->active_stream = *field_value;
        }
        break;

        // Get the integer from SQLGetData and return it in a zval
        case SQLSRV_PHPTYPE_INT:
        {
            SQLRETURN r;
            SQLINTEGER field_len;
            long value;
            r = SQLGetData( stmt->ctx.handle, field_index + 1, SQL_C_LONG, &value, 0, &field_len );
            if( r == SQL_NO_DATA ) {
                handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_NO_DATA TSRMLS_CC, field_index );
                ZVAL_FALSE( *field_value );
                return;
            }
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, ZVAL_FALSE( *field_value ); return; );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
            // if the integer field was NULL, return a zval NULL
            if( field_len == SQL_NULL_DATA ) {
                ZVAL_NULL( *field_value );
                break;
            }
            ZVAL_LONG( *field_value, value );
            break;
        }

        // Get the double from SQLGetData and return it in a zval
        case SQLSRV_PHPTYPE_FLOAT:
        {
            SQLRETURN r;
            SQLINTEGER field_len;
            double value;
            r = SQLGetData( stmt->ctx.handle, field_index + 1, SQL_C_DOUBLE, &value, 0, &field_len );
            if( r == SQL_NO_DATA ) {
                handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_NO_DATA TSRMLS_CC, field_index );
                ZVAL_FALSE( *field_value );
                return;
            }
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, ZVAL_FALSE( *field_value ); return; );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
            // if the double was a NULL, return a zval NULL
            if( field_len == SQL_NULL_DATA ) {
                ZVAL_NULL( *field_value );
                break;
            }
            ZVAL_DOUBLE( *field_value, value );
            break;
        }

        // get the date as a string (http://msdn2.microsoft.com/en-us/library/ms712387(VS.85).aspx) and convert it to
        // a DateTime object and return the created object
        case SQLSRV_PHPTYPE_DATETIME:
        {
           SQLRETURN r;
           SQLINTEGER field_len;
           char value[ MAX_DATETIME_STRING_LEN ];
           zval_auto_ptr value_z;
           zval_auto_ptr function_z;
           zval* params[1];
           
           ALLOC_INIT_ZVAL( value_z );
           ALLOC_INIT_ZVAL( function_z );
           ZVAL_STRINGL( function_z, "date_create", sizeof( "date_create" ) - 1, 1 );
           
           r = SQLGetData( stmt->ctx.handle, field_index + 1, SQL_C_CHAR, value, MAX_DATETIME_STRING_LEN, &field_len );
           if( r == SQL_NO_DATA ) {
               handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_NO_DATA TSRMLS_CC, field_index );
               ZVAL_FALSE( *field_value );
               return;
           }
           CHECK_SQL_ERROR( r, stmt, _FN_, NULL, ZVAL_FALSE( *field_value ); return; );
           CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
           if( field_len == SQL_NULL_DATA ) {
               ZVAL_NULL( *field_value );
               break;
           }   
           ZVAL_STRINGL( value_z, value, field_len, 1 );
           params[0] = value_z;
           // to convert the string date to a DateTime object, we call the "date_create" PHP function
           if( call_user_function( EG( function_table ), NULL, function_z, *field_value, 1, params TSRMLS_CC ) == FAILURE ) {
               handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_DATETIME_CONVERSION_FAILED TSRMLS_CC );
               ZVAL_FALSE( *field_value );
           }
           else {
               stmt->last_field_index = field_index;
           }

           return;
        }
        
        // an unknown type was passed in.  This should have been caught before reaching here.
        default:
            DIE( "Shouldn't reach here with an invalid type.  Should have been caught before." );
            break;
    }

    // record the last field was the one retrieved.
    stmt->last_field_index = field_index;        
}


// returns the ODBC C type constant that matches the PHP type and encoding given
// SQLTYPE_INVALID is returned when an invalid type is given when nothing matches
SQLSMALLINT determine_c_type( int php_type, int encoding )
{
    SQLSMALLINT sql_c_type = SQLTYPE_INVALID;

    switch( php_type ) {
    
        case IS_NULL:
            sql_c_type = SQL_C_CHAR;
            break;
        case IS_LONG:
            sql_c_type = SQL_C_LONG;
            break;
        case IS_DOUBLE:
            sql_c_type = SQL_C_DOUBLE;
            break;
        case IS_STRING:
        case IS_RESOURCE:
            if( encoding == SQLSRV_ENCODING_CHAR ) {
                sql_c_type = SQL_C_CHAR;
            }
            else if( encoding == SQLSRV_ENCODING_BINARY ) {
                sql_c_type = SQL_C_BINARY;
            }
            else {
                sql_c_type = SQLTYPE_INVALID;
            }
            break;
        // it is assumed that an object is a DateTime since it's the only thing we support.
        // verification that it's a real DateTime object occurs in sqlsrv_common_execute.
        // we convert the DateTime to a string before sending it to the server.
        case IS_OBJECT:
            sql_c_type = SQL_C_CHAR;
            break;
        default:
            sql_c_type = SQLTYPE_INVALID;
            break;
    }
    
    return sql_c_type;
}

// returns the SQL type constant that matches the PHP type and encoding given
// SQLTYPE_INVALID is returned when an invalid type is given since no SQL constant matches
sqlsrv_sqltype determine_sql_type( int php_type, int encoding, zval const* value )
{
    sqlsrv_sqltype sql_type;
    sql_type.typeinfo.type = SQLTYPE_INVALID;
    sql_type.typeinfo.size = SQLSRV_INVALID_SIZE;
    sql_type.typeinfo.scale = SQLSRV_INVALID_SCALE;

    switch( php_type ) {
    
        case IS_NULL:
            sql_type.typeinfo.type = SQL_CHAR;
            sql_type.typeinfo.size = 1;
            break;
        case IS_LONG:
            sql_type.typeinfo.type = SQL_INTEGER;
            break;
        case IS_DOUBLE:
            sql_type.typeinfo.type = SQL_FLOAT;
            break;
        case IS_RESOURCE:
        case IS_STRING:
            if( encoding == SQLSRV_ENCODING_CHAR ) {
                sql_type.typeinfo.type = SQL_VARCHAR;
            }
            else {
                sql_type.typeinfo.type = SQL_VARBINARY;
            }
            if( Z_STRLEN_P( value ) > SQL_SERVER_MAX_FIELD_SIZE ) {
                sql_type.typeinfo.size = SQLSRV_SIZE_MAX_TYPE;
            }
            else {
                sql_type.typeinfo.size = Z_STRLEN_P( value );   // TODO: this might need -1 added.
            }
            break;
        // it is assumed that an object is a DateTime since it's the only thing we support.
        // verification that it's a real DateTime object occurs in sqlsrv_common_execute.
        // we convert the DateTime to a string before sending it to the server.
        case IS_OBJECT:
            sql_type.typeinfo.type = SQL_TYPE_TIMESTAMP;
            break;
        default:
            // this comes from the user, so we can't assert here
            sql_type.typeinfo.type = SQLTYPE_INVALID;
            break;
    }
    
    return sql_type;
}

// given a SQL Server type, return a sqlsrv php type
sqlsrv_phptype determine_sqlsrv_php_type( SQLINTEGER sql_type, SQLUINTEGER size, bool prefer_string )
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
            sqlsrv_phptype.typeinfo.encoding = SQLSRV_ENCODING_CHAR;
            break;
        case SQL_VARCHAR:
        case SQL_WVARCHAR:
            if( prefer_string || size != SQL_SS_LENGTH_UNLIMITED ) {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STRING;
                sqlsrv_phptype.typeinfo.encoding = SQLSRV_ENCODING_CHAR;
            }
            else {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STREAM;
                sqlsrv_phptype.typeinfo.encoding = SQLSRV_ENCODING_CHAR;
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
                sqlsrv_phptype.typeinfo.encoding = SQLSRV_ENCODING_CHAR;
            }
            else {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STREAM;
                sqlsrv_phptype.typeinfo.encoding = SQLSRV_ENCODING_CHAR;
            }
            break;
        case SQL_FLOAT:
        case SQL_REAL:
            sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_FLOAT;
            break;
        case SQL_TYPE_TIMESTAMP:
            sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_DATETIME;
            break;
        default:
            sqlsrv_phptype.typeinfo.type = PHPTYPE_INVALID;
            break;
    }
    
    return sqlsrv_phptype;
}

// put in the column size and scale/decimal digits of the sql server type
// these values are taken from the MSDN page at http://msdn2.microsoft.com/en-us/library/ms711786(VS.85).aspx
bool determine_column_size_or_precision( sqlsrv_sqltype sqlsrv_type, __out SQLUINTEGER* column_size, __out SQLSMALLINT* decimal_digits )
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
            *column_size = LONG_MAX;
            break;
        case SQL_WLONGVARCHAR:
            *column_size = LONG_MAX >> 1;
            break;
        case SQL_SS_XML:
            *column_size = SQL_SS_LENGTH_UNLIMITED;
            break;
        case SQL_BINARY:
        case SQL_CHAR:
        case SQL_WCHAR:
        case SQL_WVARCHAR: 
        case SQL_VARBINARY:
        case SQL_VARCHAR:
            *column_size = sqlsrv_type.typeinfo.size;
            if( *column_size == SQLSRV_SIZE_MAX_TYPE ) {
                *column_size = SQL_SS_LENGTH_UNLIMITED;
            }
            else if( *column_size > SQL_SERVER_MAX_FIELD_SIZE || *column_size == SQLSRV_INVALID_SIZE ) {
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
        case SQL_TYPE_TIMESTAMP:
            *column_size = 23;
            *decimal_digits = 3;
            break;
        default:
            // an invalid sql type should have already been dealt with, so we assert here.
            DIE( "Trying to determine column size for an invalid type.  Type should have already been verified." );
            return false;
    }

    return true;
}

// return if the type is a valid sql server type not including
// size, precision or scale.  Use determine_precision_and_scale for that.
bool is_valid_sqlsrv_sqltype( sqlsrv_sqltype sql_type )
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
            break;
        default:
            return false;
    }

    return true;
}

bool is_valid_sqlsrv_phptype( sqlsrv_phptype type )
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
            if( type.typeinfo.encoding == SQLSRV_ENCODING_BINARY || type.typeinfo.encoding == SQLSRV_ENCODING_CHAR ) {
                return true;
            }
            break;
        }
    }

    return false;
}

bool is_streamable_type( SQLINTEGER sql_type )
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

SQLSMALLINT binary_or_char_encoding( SQLSMALLINT c_type )
{
    switch( c_type ) {
        
        case SQL_C_BINARY:
            return SQLSRV_ENCODING_BINARY;
        // we return character encoding for LONG and DOUBLE as well since by default the encoding is always character,
        // even though it won't mean anything for these data types.
        case SQL_C_CHAR:
        case SQL_C_LONG:
        case SQL_C_DOUBLE:
            break;
        default:
            DIE( "Invalid c_type in binary_or_char_encoding" );
    };

    return SQLSRV_ENCODING_CHAR;
}

bool is_fixed_size_type( SQLINTEGER sql_type )
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

// internal function to release the active stream.  Called by each main API function
// that will alter the statement and cancel any retrieval of data from a stream.
void close_active_stream( __inout sqlsrv_stmt* stmt TSRMLS_DC )
{
    // if there is no active stream, return
    if( stmt->active_stream == NULL ) {
        return;
    }

    // fetch the stream
    php_stream* stream;
    // we use no verify since verify would return immediately and we want to assert, not return.
    php_stream_from_zval_no_verify( stream, &stmt->active_stream );
    if( stream == NULL ) {
        DIE( "Unknown resource type as our active stream." );
    }
    php_stream_close( stream ); // this will NULL out the active stream in the statement.  We don't check for errors here.
    if( stmt->active_stream != NULL ) {
        DIE( "Active stream not closed." );
    }
}

// a refactoring since the clause in get_field_common was becoming too large.
void get_field_as_string( sqlsrv_stmt const* s, SQLSMALLINT c_type, SQLUSMALLINT field_index, __out zval* return_value, const char* _FN_ TSRMLS_DC )
{
    SQLRETURN r;
    char* field;
    SQLINTEGER field_len;
    SQLUINTEGER sql_display_size;
    SQLINTEGER sql_type;
    int initial_field_len = INITIAL_FIELD_STRING_LEN + (( c_type == SQL_C_CHAR ) ? 1 : 0);
    
    if( c_type != SQL_C_CHAR && c_type != SQL_C_BINARY ) DIE( "get_field_as_string requires C type to be either SQL_C_CHAR or SQL_C_BINARY" );

    r = SQLColAttribute( s->ctx.handle, field_index + 1, SQL_DESC_DISPLAY_SIZE, NULL, 0, NULL, &sql_display_size );
    CHECK_SQL_ERROR( r, s, _FN_, NULL, RETURN_FALSE; );
    CHECK_SQL_WARNING( r, s, _FN_, NULL );
    r = SQLColAttribute( s->ctx.handle, field_index + 1, SQL_DESC_TYPE, NULL, 0, NULL, &sql_type );                
    CHECK_SQL_ERROR( r, s, _FN_, NULL, RETURN_FALSE; );
    CHECK_SQL_WARNING( r, s, _FN_, NULL );            
    // if this is a large type, then read the first few bytes to get the actual length from SQLGetData
    if( sql_display_size == 0 || sql_display_size == LONG_MAX || sql_display_size == LONG_MAX >> 1 || sql_display_size == ULONG_MAX - 1 ) {

        field_len = initial_field_len;
        field = static_cast<char*>( emalloc( field_len + 1 ));
        r = SQLGetData( s->ctx.handle, field_index + 1, c_type, field, field_len, &field_len );
        if( field_len == SQL_NULL_DATA ) {
            efree( field );
            RETURN_NULL();
        }
        if( r == SQL_NO_DATA ) {
            handle_error( &s->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_NO_DATA TSRMLS_CC, field_index );
            efree( field );
            RETURN_FALSE;
            return;
        }
        if( r == SQL_SUCCESS_WITH_INFO ) {
            SQLRETURN r;
            SQLCHAR state[ SQL_SQLSTATE_BUFSIZE ];
            SQLSMALLINT len;
            r = SQLGetDiagField( SQL_HANDLE_STMT, s->ctx.handle, 1, SQL_DIAG_SQLSTATE, state, SQL_SQLSTATE_BUFSIZE, &len );
            if( is_truncated_warning( state ) ) {
                // for XML (and possibly other conditions) the field length returned is not the real field length, so
                // we do a power of two increasing size allocation to retrieve all the contents
                if( field_len == SQL_NO_TOTAL ) {
                    SQLINTEGER dummy_field_len;
                    field_len = initial_field_len - ((c_type == SQL_C_CHAR) ? 1 : 0);
                    do {
                        initial_field_len = field_len;
                        field_len *= 2;
                        field = static_cast<char*>( erealloc( field, field_len + 1 ));
                        field_len -= initial_field_len;
                        r = SQLGetData( s->ctx.handle, field_index + 1, c_type, field + initial_field_len,
                                        field_len + ((c_type == SQL_C_CHAR ) ? 1 : 0), &dummy_field_len );

                        // the last packet will contain the actual amount retrieved, not SQL_NO_TOTAL
                        // so we calculate the actual length of the string with that.
                        if( dummy_field_len != SQL_NO_TOTAL )
                            field_len += dummy_field_len;
                        else
                            field_len += initial_field_len;
                        if( r == SQL_SUCCESS_WITH_INFO ) {
                            SQLGetDiagField( SQL_HANDLE_STMT, s->ctx.handle, 1, SQL_DIAG_SQLSTATE, state, 6, &len );
                        }
                    } while( r == SQL_SUCCESS_WITH_INFO && is_truncated_warning( state ));
                    CHECK_SQL_ERROR( r, s, _FN_, NULL, efree( field ); RETURN_FALSE; );
                }
                else {
                    field = static_cast<char*>( erealloc( field, field_len + 1 ));
                    field_len -= INITIAL_FIELD_STRING_LEN;
                    r = SQLGetData( s->ctx.handle, field_index + 1, c_type, field + INITIAL_FIELD_STRING_LEN,
                                    field_len + (( c_type == SQL_C_CHAR ) ? 1 : 0), &field_len );
                    if( field_len == SQL_NULL_DATA ) {
                        efree( field );
                        RETURN_NULL();
                    }
                    if( r == SQL_NO_DATA ) {
                        handle_error( &s->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_NO_DATA TSRMLS_CC, field_index );
                        efree( field );
                        RETURN_FALSE;
                        return;
                    }
                    CHECK_SQL_ERROR( r, s, _FN_, NULL, efree( field ); RETURN_FALSE; );
                    field_len += INITIAL_FIELD_STRING_LEN;
                    field[ field_len ] = '\0';  // NULL terminate the string
                }
            }
            else {
                handle_warning( &s->ctx, LOG_STMT, _FN_, NULL TSRMLS_CC );
            }
        }
        else {
            CHECK_SQL_ERROR( r, s, _FN_, NULL, efree( field ); RETURN_FALSE; );
        }
    }
    else if( sql_display_size >= 1 && sql_display_size <= SQL_SERVER_MAX_FIELD_SIZE ) {
        // only allow binary retrievals for char and binary types.  All others get a char type automatically.
        if( is_fixed_size_type( sql_type )) {
            c_type = SQL_C_CHAR;
        }

        if( c_type == SQL_C_BINARY && ( sql_type == SQL_WCHAR || sql_type == SQL_WVARCHAR )) {
            sql_display_size = (sql_display_size * sizeof(WCHAR)) + sizeof(WCHAR);  // include the null terminator
        }
        else {
            ++sql_display_size;
        }
        field = static_cast<char*>( emalloc( sql_display_size + 1 ));
         // get the data
        r = SQLGetData( s->ctx.handle, field_index + 1, c_type, field, sql_display_size, &field_len );
        if( field_len == SQL_NULL_DATA ) {
            efree( field );
            RETURN_NULL();
        }
        if( r == SQL_NO_DATA ) {
            handle_error( &s->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_NO_DATA TSRMLS_CC, field_index );
            efree( field );
            RETURN_FALSE;
            return;
        }
        CHECK_SQL_ERROR( r, s, _FN_, NULL, efree( field ); RETURN_FALSE; );
        CHECK_SQL_WARNING( r, s, _FN_, NULL );
    }
    else {
        DIE( "Invalid sql_display_size" );
        return; // to eliminate a warning
    }

    field[ field_len ] = '\0';  // prevent a warning in debug mode
    ZVAL_STRINGL( return_value, field, field_len, 0 );
    return;
}


// called when one of the SQLSRV_SQLTYPE type functions is called.  Encodes the type and size
// into a sqlsrv_sqltype bit fields (see php_sqlsrv.h).
void type_and_size_calc( INTERNAL_FUNCTION_PARAMETERS, int type )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    char* size_p;
    int size_len;
    long size;

    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "s", &size_p, &size_len ) == FAILURE ) {
                                    
        return;
    }
    
    if( !strnicmp( "max", size_p, sizeof( "max" ) / sizeof(char)) ) {
        size = SQLSRV_SIZE_MAX_TYPE;
    }
    else {
        _set_errno( 0 );  // reset errno for atol
        size = atol( size_p );
        if( errno != 0 ) {
            size = SQLSRV_INVALID_SIZE;
        }
    }

    int max_size = SQL_SERVER_MAX_FIELD_SIZE;
    // size is actually the number of characters, not the number of bytes, so if they ask for a 
    // 2 byte per character type, then we half the maximum size allowed.
    if( type == SQL_WVARCHAR || type == SQL_WCHAR ) {
        max_size >>= 1;
    }

    if( size > max_size || size < SQLSRV_SIZE_MAX_TYPE || size == 0 ) {
        LOG( SEV_ERROR, LOG_STMT, "invalid size.  size must be > 0 and <= %1!d! characters or 'max'", max_size );
        size = SQLSRV_INVALID_SIZE;
    }
    
    sqlsrv_sqltype sql_type;
    sql_type.typeinfo.type = type;
    sql_type.typeinfo.size = size;
    sql_type.typeinfo.scale = SQLSRV_INVALID_SCALE;

    ZVAL_LONG( return_value, sql_type.value );
}


// called when the user gives SQLSRV_SQLTYPE_DECIMAL or SQLSRV_SQLTYPE_NUMERIC sql types as the type of the field.
// encodes these into a sqlsrv_sqltype structure (see php_sqlsrv.h)
void type_and_precision_calc( INTERNAL_FUNCTION_PARAMETERS, int type )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    long prec = SQL_SERVER_DEFAULT_PRECISION;
    long scale = SQL_SERVER_DEFAULT_SCALE;

    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "|ll", &prec, &scale ) == FAILURE ) {
                                    
        return;
    }
    
    if( prec > SQL_SERVER_MAX_PRECISION ) {
        LOG( SEV_ERROR, LOG_STMT, "Invalid precision.  Precision can't be > 38" );
        prec = SQLSRV_INVALID_PRECISION;
    }
    
    if( prec < 0 ) {
        LOG( SEV_ERROR, LOG_STMT, "Invalid precision.  Precision can't be negative" );
        prec = SQLSRV_INVALID_PRECISION;
    }

    if( scale > prec ) {
        LOG( SEV_ERROR, LOG_STMT, "Invalid scale.  Scale can't be > precision" );
        scale = SQLSRV_INVALID_PRECISION;
    }

    sqlsrv_sqltype sql_type;
    sql_type.typeinfo.type = type;
    sql_type.typeinfo.size = prec;
    sql_type.typeinfo.scale = scale;

    ZVAL_LONG( return_value, sql_type.value );
}


// common code for SQLSRV_PHPTYPE_STREAM and SQLSRV_PHPTYPE_STRING php types given as parameters.
// encodes the type and encoding into a sqlsrv_phptype structure (see php_sqlsrv.h)
void type_and_encoding( INTERNAL_FUNCTION_PARAMETERS, int type )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    char* encoding_param;
    sqlsrv_phptype sqlsrv_phptype;
    sqlsrv_phptype.typeinfo.type = type;
    sqlsrv_phptype.typeinfo.encoding = SQLSRV_ENCODING_INVALID;
    int encoding_param_len = 0;

    if( type != SQLSRV_PHPTYPE_STREAM && type != SQLSRV_PHPTYPE_STRING ) {
        DIE( "Invalid type passed to type_and_encoding" );
    }

    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "s", &encoding_param, &encoding_param_len ) == FAILURE ) {
        return;
    }

    if( !strnicmp( encoding_param, "binary", sizeof( "binary" ) / sizeof(char))) {
        sqlsrv_phptype.typeinfo.encoding = SQLSRV_ENCODING_BINARY;
    }
    else if( !strnicmp( encoding_param, "char", sizeof( "char" ) / sizeof(char))) {
        sqlsrv_phptype.typeinfo.encoding = SQLSRV_ENCODING_CHAR;
    }
    else {
        LOG( SEV_ERROR, LOG_STMT, "Invalid encoding.  Must be either \"binary\" (SQLSRV_ENC_BINARY) or \"char\" (SQLSRV_ENC_CHAR)" );
    }
    
    ZVAL_LONG( return_value, sqlsrv_phptype.value );
}


// fetch_common
// the common code shared between fetch_array and fetch_object.  This returns a hash_table into return_value
// containing the fields either indexed by number and/or field name as determined by fetch_type.
void fetch_common( __inout sqlsrv_stmt* stmt, int fetch_type, __out zval* return_value, const char* _FN_, bool allow_empty_field_names TSRMLS_DC )
{
    SQLRETURN r = SQL_SUCCESS;
    int zr = SUCCESS;
    SQLSMALLINT num_cols = 0;
    SQLUSMALLINT field_name_len_max = 0;
    SQLSMALLINT unused = -1;

    // make sure that the fetch type is legal
    CHECK_SQL_ERROR_EX( fetch_type < MIN_SQLSRV_FETCH || fetch_type > MAX_SQLSRV_FETCH, stmt, _FN_, 
                        SQLSRV_ERROR_INVALID_FETCH_TYPE, RETURN_FALSE );
    // make sure the statement has been executed 
    CHECK_SQL_ERROR_EX( !stmt->executed, stmt, _FN_, SQLSRV_ERROR_STATEMENT_NOT_EXECUTED, RETURN_FALSE );
    CHECK_SQL_ERROR_EX( stmt->past_fetch_end, stmt, _FN_, SQLSRV_ERROR_FETCH_PAST_END, RETURN_FALSE );

    // get the numer of columns in the result set
    r = SQLNumResultCols( stmt->ctx.handle, &num_cols );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
    CHECK_SQL_ERROR_EX( num_cols == 0, stmt, _FN_, SQLSRV_ERROR_NO_FIELDS, RETURN_FALSE );

    // get the maximum size for a field name
    r = SQLGetInfo( stmt->conn->ctx.handle, SQL_MAX_COLUMN_NAME_LEN, &field_name_len_max, sizeof( field_name_len_max ), &unused );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );

    // move to the next record
    r = SQLFetch( stmt->ctx.handle );
    // return a Zend NULL if we're at the end of the result set.
    if( r == SQL_NO_DATA ) {
    	stmt->past_fetch_end = true;
        RETURN_NULL();
    }
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );    

    // make it legal to retrieve fields
    stmt->fetch_called = true;
    stmt->last_field_index = -1;

    zval_auto_ptr fields;
    MAKE_STD_ZVAL( fields );
    zr = array_init( fields );
    CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );

    // if this is the first fetch in a new result set, then get the field names and
    // store them off for successive fetches.
    if( fetch_type & SQLSRV_FETCH_ASSOC && stmt->fetch_fields == NULL ) {

        char* field_name_temp = static_cast<char*>( alloca( field_name_len_max ));
        SQLSMALLINT field_name_len;
        emalloc_auto_ptr<sqlsrv_fetch_field> field_names;
        field_names = static_cast<sqlsrv_fetch_field*>( emalloc( num_cols * sizeof( sqlsrv_fetch_field )));

        for( SQLUSMALLINT f = 0; f < num_cols; ++f ) {
            r = SQLColAttribute( stmt->ctx.handle, f + 1, SQL_DESC_NAME, field_name_temp, field_name_len_max, &field_name_len, &unused );
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
            field_names[ f ].name = static_cast<char*>( emalloc( field_name_len + 1 ));
            memcpy( (void*) field_names[ f ].name, field_name_temp, field_name_len );
            field_names[ f ].name[ field_name_len ] = '\0';  // null terminate the field name since SQLColAttribute doesn't.
            field_names[ f ].len = field_name_len + 1;
        }
        stmt->fetch_fields = field_names;
        stmt->fetch_fields_count = num_cols;
        field_names.transferred();
    }

    for( SQLUSMALLINT f = 0; f < num_cols; ++f ) {
        
        SQLLEN field_type;
        SQLLEN field_len;
        sqlsrv_phptype sqlsrv_php_type;
        // we don't use a zend_auto_ptr because ownership is transferred to the fields hash table
        // that will be destroyed if an error occurs.
        zval_auto_ptr field;
        
        MAKE_STD_ZVAL( field );

        // get the field type and length so we can determine the approppriate PHP type to map this to.
        r = SQLColAttribute( stmt->ctx.handle, f + 1, SQL_DESC_CONCISE_TYPE, NULL, 0, NULL, &field_type );
        CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
        CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
        r = SQLColAttribute( stmt->ctx.handle, f + 1, SQL_DESC_LENGTH, NULL, 0, NULL, &field_len );
        CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
        CHECK_SQL_WARNING( r, stmt, _FN_, NULL );

        // map from the SQL Server type to a PHP type for the field
        sqlsrv_php_type = determine_sqlsrv_php_type( field_type, field_len, true );
        if( sqlsrv_php_type.typeinfo.type == PHPTYPE_INVALID ) { DIE( "Couldn't understand type returned by ODBC" ); }

        // get the value
        get_field_common( stmt, _FN_, sqlsrv_php_type, f, &field TSRMLS_CC );
        if( Z_TYPE_P( field ) == IS_BOOL && Z_LVAL_P( field ) == 0 ) {
            RETURN_FALSE;
        }

        // if the fetch type is numeric, add an integer key for this field
        if( fetch_type & SQLSRV_FETCH_NUMERIC ) {
            zr = add_next_index_zval( fields, field );
            CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
            zval_add_ref( &field );
        }

        // if the fetch type is associative add the field with the field name as the key
        // (unnamed fields are permitted if the flag is passed in)
        if( fetch_type & SQLSRV_FETCH_ASSOC ) {

            CHECK_SQLSRV_WARNING( stmt->fetch_fields[ f ].len == 1 && !allow_empty_field_names, SQLSRV_WARNING_FIELD_NAME_EMPTY, RETURN_FALSE; );

            if( stmt->fetch_fields[ f ].len > 1 || allow_empty_field_names ) {
                zr = add_assoc_zval( fields, stmt->fetch_fields[ f ].name, field );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                zval_add_ref( &field );
            }
        }
    }

    *return_value = *fields;
    ZVAL_NULL( fields );
    zval_ptr_dtor( &fields );
    fields.transferred();
}

// send a single packet from a stream parameter to the database using ODBC.  This will also
// handle the transition between parameters.  It returns true if it is not done sending,
// false if it is finished or an error occurred.  return_value is what should be returned
// to the script if it is given.  Any errors that occur are posted here.

bool send_stream_packet( __inout sqlsrv_stmt* stmt, __out zval* return_value, char const* _FN_ TSRMLS_DC )
{
    SQLRETURN r = SQL_SUCCESS;

    // if there no current parameter to process, get the next one 
    // (probably because this is the first call to sqlsrv_send_stream_data)
    if( stmt->current_parameter == NULL ) {
        if( check_for_next_stream_parameter( stmt, return_value TSRMLS_CC ) == false ) {
            // done.  return_value is already set
            return false;
        }
    }

    // get the stream from the zval we bound
    php_stream* param_stream = NULL;
    php_stream_from_zval_no_verify( param_stream, &stmt->current_parameter );
    CHECK_SQL_ERROR_EX( param_stream == NULL, stmt, _FN_, SQLSRV_ERROR_ZEND_STREAM, 
        zval_ptr_dtor( &stmt->current_parameter );
        stmt->current_parameter = NULL;
        stmt->current_parameter_read = 0;
        SQLCancel( stmt->ctx.handle );
        RETVAL_FALSE;
        return false;);

    // if we're at the end, then release our current parameter
    if( php_stream_eof( param_stream )) {
        // if no data was actually sent prior, then send a NULL
        if( stmt->current_parameter_read == 0 ) {
            // send an empty string, which is what a 0 length does.
            r = SQLPutData( stmt->ctx.handle, stmt->param_buffer, 0 );
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, SQLCancel( stmt->ctx.handle ); RETVAL_FALSE; return false; );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
        }
        zval_ptr_dtor( &stmt->current_parameter );
        stmt->current_parameter = NULL;
        stmt->current_parameter_read = 0;
    }
    // read the data from the stream, send it via SQLPutData and track how much we've sent.  
    else {
        size_t read = php_stream_read( param_stream, static_cast<char*>( stmt->param_buffer ), stmt->param_buffer_size );
        stmt->current_parameter_read += read;
        if( read > 0 ) {
            r = SQLPutData( stmt->ctx.handle, stmt->param_buffer, read );
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, SQLCancel( stmt->ctx.handle ); RETVAL_FALSE; return false; );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
        }
    }

    RETVAL_TRUE;
    return true;
}

zval* parse_param_array( sqlsrv_stmt const* stmt, const char* _FN_, const zval* param_array, SQLSMALLINT param_num, __out int& direction, __out zend_uchar& php_type, SQLSMALLINT& sql_c_type, __out sqlsrv_sqltype& sql_type,
                         __out SQLUINTEGER& column_size, __out SQLSMALLINT& decimal_digits TSRMLS_DC )
{
    zval** var_or_val;
    zval** temp;
    
    bool php_type_param_was_null = true;
    bool sql_type_param_was_null = true;

    // handle the array parameters that contain the value/var, direction, php_type, sql_type
    zend_hash_internal_pointer_reset( Z_ARRVAL_P( param_array ) );
    if( zend_hash_has_more_elements( Z_ARRVAL_P( param_array )) == FAILURE || zend_hash_get_current_data( Z_ARRVAL_P( param_array ), (void**) &var_or_val ) == FAILURE ) {
        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_VAR_REQUIRED TSRMLS_CC, param_num );
        SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
        return NULL;
    }

    // if the direction is included, then use what they gave, otherwise INPUT is assumed
    if( zend_hash_move_forward( Z_ARRVAL_P( param_array )) == SUCCESS && 
        zend_hash_get_current_data( Z_ARRVAL_P( param_array ), (void**) &temp ) == SUCCESS && 
        Z_TYPE_PP( temp ) != IS_NULL ) {

        if( Z_TYPE_PP( temp ) != IS_LONG ) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_DIRECTION TSRMLS_CC, param_num );
            SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
            return NULL;
        }
        direction = Z_LVAL_PP( temp );
        if( direction != SQL_PARAM_INPUT && direction != SQL_PARAM_OUTPUT && direction != SQL_PARAM_INPUT_OUTPUT ) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_DIRECTION TSRMLS_CC, param_num );
            SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
            return NULL;
        }
    }
    else {
        direction = SQL_PARAM_INPUT;
    }

    // use the PHP type given rather than the type built in, since this could be an output parameter.
    // determine the C type to pass to SQLBindParameter
    if( zend_hash_move_forward( Z_ARRVAL_P( param_array ) ) == SUCCESS &&  
        zend_hash_get_current_data( Z_ARRVAL_P( param_array ), (void**) &temp ) == SUCCESS && 
        Z_TYPE_PP( temp ) != IS_NULL ) {
                
        php_type_param_was_null = false;

        sqlsrv_phptype sqlsrv_phptype;
        int encoding;
        sqlsrv_phptype.typeinfo.type = PHPTYPE_INVALID;
        sqlsrv_phptype.typeinfo.encoding = SQLSRV_ENCODING_INVALID;
        if( Z_TYPE_PP( temp ) != IS_LONG ) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE TSRMLS_CC, param_num );
            SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
            return NULL;
        }
        sqlsrv_phptype.value = Z_LVAL_PP( temp );
        if( !is_valid_sqlsrv_phptype( sqlsrv_phptype )) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE TSRMLS_CC, param_num );
            SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
            return NULL;
        }
        // make sure the enums are 1:1 
        SQLSRV_STATIC_ASSERT( MAX_SQLSRV_PHPTYPE == ( sizeof( sqlsrv_to_php_type ) / sizeof( zend_uchar )) );
        php_type = sqlsrv_to_php_type[ sqlsrv_phptype.typeinfo.type - 1 ];
        encoding = sqlsrv_phptype.typeinfo.encoding;
        sql_c_type = determine_c_type( php_type, encoding );
        if( sql_c_type == SQLTYPE_INVALID ) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE TSRMLS_CC, param_num );
            SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
            return NULL;  
        }
    }
    // use the PHP type to determine the C type for SQLBindParameter
    else {
                    
        php_type_param_was_null = true;
        php_type = Z_TYPE_PP( var_or_val );
        sql_c_type = determine_c_type( php_type, SQLSRV_ENCODING_CHAR );
        if( sql_c_type == SQLTYPE_INVALID ) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE TSRMLS_CC, param_num );
            SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
            return NULL;
        }                    
    }

    // get the server type, column size/precision and the decimal digits if provided
    if( zend_hash_move_forward( Z_ARRVAL_P( param_array ) ) == SUCCESS &&  
        zend_hash_get_current_data( Z_ARRVAL_P( param_array ), (void**) &temp ) == SUCCESS && 
        Z_TYPE_PP( temp ) != IS_NULL ) {

        sql_type_param_was_null = false;

        if( Z_TYPE_PP( temp ) != IS_LONG ) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_SQLTYPE TSRMLS_CC, param_num );
            SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
            return NULL;
        };
        sql_type.value = Z_LVAL_PP( temp );
        // since the user supplied this type, make sure it's valid
        if( !is_valid_sqlsrv_sqltype( sql_type )) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_SQLTYPE TSRMLS_CC, param_num );
            SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
            return NULL;                            
        }                    
        if( determine_column_size_or_precision( sql_type, &column_size, &decimal_digits ) == false ) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PRECISION TSRMLS_CC, param_num );
            SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
            return NULL;
        }
    }
    // else use the type of the variable to infer the sql type, using a default character encoding
    // (the default encoding really should be something in the INI)
    else {
        sql_type_param_was_null = true;
        sql_type = determine_sql_type( php_type, binary_or_char_encoding( sql_c_type ), *var_or_val);
        if( sql_type.typeinfo.type == SQLTYPE_INVALID ) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_SQLTYPE TSRMLS_CC, param_num );
            SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
            return NULL;
        }
        if( determine_column_size_or_precision( sql_type, &column_size, &decimal_digits ) == false ) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PRECISION TSRMLS_CC, param_num );
            SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
            return NULL;
        }
    }

    // if the user has given us a value of NULL as an input parameter, then use the default types.
    if( Z_TYPE_PP( var_or_val ) == IS_NULL && direction == SQL_PARAM_INPUT && php_type != IS_NULL ) {

        // if the user has given us a varbinary field, then set the c type to binary and the php type to NULL
        if( sql_type.typeinfo.type == SQL_VARBINARY ) {
            sql_c_type = SQL_C_BINARY;
            php_type = IS_NULL;
            return *var_or_val;
        }
        
        // otherwise, just set the defaults as if they had not given us a full parameter array but just put in null
        bool success = determine_param_defaults( stmt, _FN_, *var_or_val, param_num, php_type, direction, sql_type,
                                                 sql_c_type, column_size, decimal_digits TSRMLS_CC );
        if( !success ) {
            return NULL;
        }
        return *var_or_val;
    }

    // if the user for some reason provides an output parameter with a null phptype and a specified
    // sql server type, infer the php type from the sql server type.
    if( direction == SQL_PARAM_OUTPUT && php_type_param_was_null && !sql_type_param_was_null ) {

        sqlsrv_phptype sqlsrv_php_type;
        int encoding;
        sqlsrv_php_type = determine_sqlsrv_php_type( sql_type.typeinfo.type, column_size, true );
        // we DIE here since everything should have been validated already and to return the user an error
        // for our own logic error would be confusing/misleading.
        if( sqlsrv_php_type.typeinfo.type == PHPTYPE_INVALID ) DIE( "An invalid php type was returned with (supposed) validated sql type and column_sze" );

        SQLSRV_STATIC_ASSERT( MAX_SQLSRV_PHPTYPE == ( sizeof( sqlsrv_to_php_type ) / sizeof( zend_uchar )) );
        php_type = sqlsrv_to_php_type[ sqlsrv_php_type.typeinfo.type - 1 ];
        encoding = sqlsrv_php_type.typeinfo.encoding;
        sql_c_type = determine_c_type( php_type, encoding );
    }

    // if the parameter is output and the user gave us a variable that isn't of the output
    // type requested, then we set the variable given to us to the type requested if the variable's
    // type/value is null, otherwise we throw an error 
    if( direction == SQL_PARAM_OUTPUT && Z_TYPE_PP( var_or_val ) != php_type ) {

        // make sure it's not one of the invalid output param types before we handle it
        switch( php_type ) {
            case IS_NULL:
            case IS_RESOURCE:
            case IS_OBJECT:
                handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_OUTPUT_PARAM_TYPE TSRMLS_CC );
                SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
                return NULL;
                break;
        }
                    
        // if the user gives us a NULL for an output parameter, we convert the variable to the appropriate type
        if( Z_TYPE_PP( var_or_val ) == IS_NULL ) {
            Z_TYPE_PP( var_or_val ) = php_type;
            if( php_type == IS_STRING ) {
                ZVAL_STRINGL( *var_or_val, static_cast<char*>( emalloc( column_size )), column_size, 0 /* don't dup the string */ );
            }
        }
        else {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_OUTPUT_PARAM_TYPE_DOESNT_MATCH TSRMLS_CC, param_num );
            SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
            return NULL;
        }
    }

    // return the variable/value
    return *var_or_val;
}

// common code for when just a variable/value is given as a parameter or when NULL is given
bool determine_param_defaults( sqlsrv_stmt const* stmt, const char* _FN_, zval const* param_z, int param_num, __out zend_uchar& php_type, __out int& direction, 
                               __out sqlsrv_sqltype& sql_type, __out SQLSMALLINT& sql_c_type, __out SQLUINTEGER& column_size, __out SQLSMALLINT& decimal_digits TSRMLS_DC )
{
    direction = SQL_PARAM_INPUT;

    php_type = Z_TYPE_P( param_z );
    sql_type = determine_sql_type( php_type, SQLSRV_ENCODING_CHAR, param_z );
    if( sql_type.typeinfo.type == SQLTYPE_INVALID ) {
        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_SQLTYPE TSRMLS_CC, param_num );
        SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
        return false;
    }
    sql_c_type = determine_c_type( php_type, SQLSRV_ENCODING_CHAR );
    if( sql_c_type == SQLTYPE_INVALID ) {
        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE TSRMLS_CC, param_num );
        SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
        return false;
    }
    if( determine_column_size_or_precision( sql_type, &column_size, &decimal_digits ) == false ) {
        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PRECISION TSRMLS_CC, param_num );
        SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
        return false;
    }

    return true;
}

}  // namespace

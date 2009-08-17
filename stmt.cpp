//----------------------------------------------------------------------------------------------------------------------------------
// File: stmt.cpp
//
// Copyright (c) Microsoft Corporation.  All rights reserved.
//
// Contents: Routines that use statement handles
//
// License: This software is released under the Microsoft Public License.  A copy of the license agreement 
//          may be found online at http://www.codeplex.com/SQLSRVPHP/license.
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
const char DATETIMEOFFSET_FORMAT[] = "Y-m-d H:i:s.u P";
const size_t DATETIMEOFFSET_FORMAT_LEN = sizeof( DATETIMEOFFSET_FORMAT );
const char DATETIME_FORMAT[] = "Y-m-d H:i:s.u";
const size_t DATETIME_FORMAT_LEN = sizeof( DATETIME_FORMAT );
const char DATE_FORMAT[] = "Y-m-d";
const size_t DATE_FORMAT_LEN = sizeof( DATE_FORMAT );

// constants for maximums in SQL Server
const int SQL_SERVER_MAX_FIELD_SIZE = 8000;
const int SQL_SERVER_MAX_PRECISION = 38;
const int SQL_SERVER_DEFAULT_PRECISION = 18;
const int SQL_SERVER_DEFAULT_SCALE = 0;

// constant strings used for the field metadata results
// (char to avoid having to cast them where they are used)
char* FIELD_METADATA_NAME = "Name";
char* FIELD_METADATA_TYPE = "Type";
char* FIELD_METADATA_SIZE = "Size";
char* FIELD_METADATA_PREC = "Precision";
char* FIELD_METADATA_SCALE = "Scale";
char* FIELD_METADATA_NULLABLE = "Nullable";

// base allocation size when retrieving a string field
const int INITIAL_FIELD_STRING_LEN = 256;
// max size of a date time string when converting from a DateTime object to a string
const int MAX_DATETIME_STRING_LEN = 256;

// this must align 1:1 with the SQLSRV_PHPTYPE enum in php_sqlsrv.h
const zend_uchar sqlsrv_to_zend_phptype[] = {
    IS_NULL,
    IS_LONG,
    IS_DOUBLE,
    IS_STRING,
    IS_OBJECT,
    IS_RESOURCE,
    SQLSRV_PHPTYPE_INVALID
};

// map a Zend PHP type constant to our constant type
enum SQLSRV_PHPTYPE zend_to_sqlsrv_phptype[] = {
    SQLSRV_PHPTYPE_NULL,
    SQLSRV_PHPTYPE_INT,
    SQLSRV_PHPTYPE_FLOAT,
    SQLSRV_PHPTYPE_INVALID,
    SQLSRV_PHPTYPE_INVALID,
    SQLSRV_PHPTYPE_DATETIME,
    SQLSRV_PHPTYPE_STRING,
    SQLSRV_PHPTYPE_STREAM,
    SQLSRV_PHPTYPE_INVALID,
    SQLSRV_PHPTYPE_INVALID
};

// default class used when no class is specified by sqlsrv_fetch_object
const char STDCLASS_NAME[] = "stdclass";
const char STDCLASS_NAME_LEN = sizeof( STDCLASS_NAME ) - 1;

// UTF-8 tags for byte length of characters
const unsigned int UTF8_MIDBYTE_MASK = 0xc0;
const unsigned int UTF8_MIDBYTE_TAG = 0x80;
const unsigned int UTF8_2BYTESEQ_TAG1 = 0xc0;
const unsigned int UTF8_2BYTESEQ_TAG2 = 0xd0;
const unsigned int UTF8_3BYTESEQ_TAG = 0xe0;
const unsigned int UTF8_4BYTESEQ_TAG = 0xf0;
const unsigned int UTF8_NBYTESEQ_MASK = 0xf0;

// the message returned by SQL Native Client
const char CONNECTION_BUSY_ODBC_ERROR[] = "[Microsoft][SQL Server Native Client 10.0]Connection is busy with results for another command";


// *** internal function prototypes ***

// These are arranged alphabetically.  They are all used by the sqlsrv statement functions.
bool adjust_output_lengths_and_encodings( sqlsrv_stmt* stmt, const char* _FN_ TSRMLS_DC );
SQLSMALLINT binary_or_char_encoding( SQLSMALLINT c_type );
bool calc_string_size( sqlsrv_stmt const* s, SQLUSMALLINT field_index, SQLUINTEGER& size, const char* _FN_ TSRMLS_DC );
bool check_for_next_stream_parameter( sqlsrv_stmt* stmt, zval* return_value, const char* _FN_ TSRMLS_DC );
void close_active_stream( sqlsrv_stmt* s TSRMLS_DC );
bool convert_input_param_to_utf16( zval* input_param_z, zval* convert_param_z );
bool convert_string_from_utf16( sqlsrv_phptype sqlsrv_phptype, char** string, SQLINTEGER& len );
SQLSMALLINT determine_c_type( int php_type, int encoding );
bool determine_column_size_or_precision( sqlsrv_stmt const* stmt, sqlsrv_sqltype sqlsrv_type, SQLUINTEGER* column_size, SQLSMALLINT* decimal_digits );
bool determine_param_defaults( sqlsrv_stmt const* stmt, const char* _FN_, zval const* param_z, int param_num, zend_uchar& php_type, int& direction, 
                               sqlsrv_sqltype& sql_type, SQLSMALLINT& sql_c_type, SQLUINTEGER& column_size, SQLSMALLINT& decimal_digits,
                               sqlsrv_phptype& sqlsrv_phptype TSRMLS_DC );
sqlsrv_phptype determine_sqlsrv_php_type( sqlsrv_stmt const* stmt, SQLINTEGER sql_type, SQLUINTEGER size, bool prefer_string );
sqlsrv_sqltype determine_sql_type( zval const* value, int encoding, SERVER_VERSION server_version );
bool determine_stmt_has_rows( sqlsrv_stmt* stmt, const char* _FN_ TSRMLS_DC );
void fetch_common( sqlsrv_stmt* stmt, int fetch_type, long fetch_style, long fetch_offset, zval* return_value, const char* _FN_, bool allow_empty_field_names TSRMLS_DC );
void get_field_common( sqlsrv_stmt* s, const char* _FN_, sqlsrv_phptype sqlsrv_phptype, SQLUSMALLINT field_index, zval**field_value TSRMLS_DC );
void get_field_as_string( sqlsrv_stmt const* s, sqlsrv_phptype sqlsrv_phptype, SQLUSMALLINT field_index, zval* return_value, const char* _FN_ TSRMLS_DC );
SQLRETURN has_result_columns( sqlsrv_stmt* stmt, bool& result_present );
SQLRETURN has_any_result( sqlsrv_stmt* stmt, bool& result_present );
bool is_fixed_size_type( SQLINTEGER sql_type );
bool is_streamable_type( SQLINTEGER sql_type );
bool is_valid_sqlsrv_sqltype( sqlsrv_sqltype type );
bool is_valid_sqlsrv_phptype( sqlsrv_phptype type );
zval* parse_param_array( sqlsrv_stmt const* stmt, const char* _FN_, const zval* param_array, SQLSMALLINT param_num, int& direction, zend_uchar& php_type, SQLSMALLINT& sql_c_type, sqlsrv_sqltype& sql_type,
                         SQLUINTEGER& column_size, SQLSMALLINT& decimal_digits, sqlsrv_phptype& sqlsrv_phptype TSRMLS_DC );
bool send_stream_packet( sqlsrv_stmt* stmt, zval* return_value, char const* _FN_ TSRMLS_DC );
bool should_be_converted_from_utf16( SQLINTEGER sql_type );
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

    // verify the resource so we know we're deleting a statement
    stmt = static_cast<sqlsrv_stmt*>( zend_fetch_resource( &stmt_r TSRMLS_CC, -1, "sqlsrv_stmt", NULL, 1, sqlsrv_stmt::descriptor ));
    if( stmt == NULL ) {
        handle_error( NULL, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
        RETURN_FALSE;
    }

    // delete the resource from Zend's master list, which will trigger the statement's destructor
    int zr = zend_hash_index_del( &EG( regular_list ), Z_RESVAL_P( stmt_r ));
    if( zr == FAILURE ) {
        LOG( SEV_ERROR, LOG_STMT, "Failed to remove stmt resource %1!d!", Z_RESVAL_P( stmt_r ));
    }

    ZVAL_NULL( stmt_r );

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
    stmt->current_stream = NULL;

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
    long fetch_style = SQL_FETCH_NEXT;   // default value for parameter if one isn't supplied
    long fetch_offset = 0;              // default value for parameter if one isn't supplied

    DECL_FUNC_NAME( "sqlsrv_fetch" );
    LOG_FUNCTION;

    // take only the statement resource
    PROCESS_PARAMS( stmt, _FN_, "r|ll", &fetch_style, &fetch_offset );

    // verify the fetch style
    CHECK_SQL_ERROR_EX( fetch_style < SQL_FETCH_NEXT || fetch_style > SQL_FETCH_RELATIVE,
                        stmt, _FN_, SQLSRV_ERROR_INVALID_FETCH_STYLE, RETURN_FALSE );
    
    // make sure the statement has been executed
    CHECK_SQL_ERROR_EX( !stmt->executed, stmt, _FN_, SQLSRV_ERROR_STATEMENT_NOT_EXECUTED, RETURN_FALSE );
    CHECK_SQL_ERROR_EX( stmt->past_fetch_end, stmt, _FN_, SQLSRV_ERROR_FETCH_PAST_END, RETURN_FALSE );

    bool has_result_set;
    r = has_result_columns( stmt, has_result_set );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
    CHECK_SQL_ERROR_EX( !has_result_set, stmt, _FN_, SQLSRV_ERROR_NO_FIELDS, RETURN_FALSE );

    close_active_stream( stmt TSRMLS_CC );

    // if the statement has rows and is not scrollable but doesn't yet have
    // fetch_called, this must be the first time we've called sqlsrv_fetch.  
    // Since we called we called SQLFetch in determine_stmt_has_rows, we're alreeady at the first row,
    // so just return true and mark fetch_called as true so it will skip this clause next time
    if( !stmt->scrollable && stmt->has_rows && !stmt->fetch_called ) {
        stmt->fetch_called = true;
        RETURN_TRUE;
    }

    // move to the record requested.  For absolute records, we use a 0 based offset, so +1 since 
    // SQLFetchScroll uses a 1 based offset, otherwise for relative, just use the number they give us
    r = SQLFetchScroll( stmt->ctx.handle, static_cast<SQLSMALLINT>( fetch_style ), 
                        ( fetch_style == SQL_FETCH_RELATIVE ) ? fetch_offset : fetch_offset + 1 );
    // return Zend NULL if we're at the end of the result set.
    if( r == SQL_NO_DATA ) {
        // if this is a forward only cursor, mark that we've passed the end so future calls result in an error
        if( !stmt->scrollable ) {
            stmt->past_fetch_end = true;
        }
        RETURN_NULL();
    }
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );

    // mark that we called fetch (which get_field, et. al. uses) and reset our last field retrieved
    stmt->fetch_called = true;
    stmt->last_field_index = -1;
    stmt->has_rows = true;  // since we made it his far, we must have at least one row

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
    long fetch_style = SQL_FETCH_NEXT;   // default value for parameter if one isn't supplied
    long fetch_offset = 0;              // default value for parameter if one isn't supplied

    DECL_FUNC_NAME( "sqlsrv_fetch_array" );
    LOG_FUNCTION;

    // retrieve the statement resource and optional fetch type (see enum SQLSRV_FETCH_TYPE),
    // fetch style (see SQLSRV_SCROLL_* constants) and fetch offset
    PROCESS_PARAMS( stmt, _FN_, "r|lll", &fetch_type, &fetch_style, &fetch_offset );

    // retrieve the hash table directly into the return_value variable for return.  Any errors
    // are handled directly in fetch_common.
    fetch_common( stmt, fetch_type, fetch_style, fetch_offset, return_value, _FN_, true TSRMLS_CC );
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
    sqlsrv_malloc_auto_ptr<char> stdclass_name;
    stdclass_name = estrdup( STDCLASS_NAME );
    zval* class_name_z = NULL;
    char* class_name = stdclass_name;
    int class_name_len = STDCLASS_NAME_LEN;
    zval* ctor_params_z = NULL;
    long fetch_style = SQL_FETCH_NEXT;   // default value for parameter if one isn't supplied
    long fetch_offset = 0;              // default value for parameter if one isn't supplied

    DECL_FUNC_NAME( "sqlsrv_fetch_object" );
    LOG_FUNCTION;

    // retrieve the statement resource and optional fetch type (see enum SQLSRV_FETCH_TYPE),
    // fetch style (see SQLSRV_SCROLL_* constants) and fetch offset
    // we also use z! instead of s and a so that null may be passed in as valid values for 
    // the class name and ctor params
    PROCESS_PARAMS( stmt, _FN_, "r|z!z!ll", &class_name_z, &ctor_params_z, 
                    &fetch_style, &fetch_offset );
    // if the class name isn't null, then it must be a string, and set the class name to the string passed
    if( class_name_z ) {
        if( Z_TYPE_P( class_name_z ) != IS_STRING ) {
            handle_error( NULL, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
            RETURN_FALSE;
        }
        else {
            class_name = Z_STRVAL_P( class_name_z );
            class_name_len = Z_STRLEN_P( class_name_z );
        }
    }
    // if the constructor parameters array is not null, then it must be an array
    if( ctor_params_z && Z_TYPE_P( ctor_params_z ) != IS_ARRAY ) {
        handle_error( NULL, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_FUNCTION_PARAMETER TSRMLS_CC, _FN_ );
        RETURN_FALSE;
    }

    // fetch the fields into an associative hash table
    fetch_common( stmt, SQLSRV_FETCH_ASSOC, fetch_style, fetch_offset, return_value, _FN_, false TSRMLS_CC );
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
        sqlsrv_malloc_auto_ptr<zval**> params_m;
        zval_auto_ptr ctor_retval_z;
        ALLOC_INIT_ZVAL( ctor_retval_z );
        int num_params = 0;
        if( ctor_params_z != NULL ) {
            HashTable* ctorp_ht = Z_ARRVAL_P( ctor_params_z );
            num_params = zend_hash_num_elements( ctorp_ht );
            params_m = reinterpret_cast<zval***>( sqlsrv_malloc( num_params * sizeof( zval**) ));

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
    sqlsrv_malloc_auto_ptr<char> field_name_temp;
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
        
        field_name_temp = static_cast<char*>( sqlsrv_malloc( field_name_len_max + 1 ));

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
        zr = add_assoc_string( field_meta_data, FIELD_METADATA_NAME, field_name_temp, 0 );
        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
        field_name_temp.transferred();

        // add the type to the array
        zr = add_assoc_long( field_meta_data, FIELD_METADATA_TYPE, field_type );
        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );

        // depending on field type, we add the values into size or precision/scale and NULL out the other fields
        switch( field_type ) {
            case SQL_DECIMAL:
            case SQL_NUMERIC:
            case SQL_TYPE_TIMESTAMP:
            case SQL_TYPE_DATE:
            case SQL_SS_TIME2:
            case SQL_SS_TIMESTAMPOFFSET:
                zr = add_assoc_null( field_meta_data, FIELD_METADATA_SIZE );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                zr = add_assoc_long( field_meta_data, FIELD_METADATA_PREC, field_size );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                zr = add_assoc_long( field_meta_data, FIELD_METADATA_SCALE, field_scale );
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
                zr = add_assoc_null( field_meta_data, FIELD_METADATA_SIZE );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                zr = add_assoc_long( field_meta_data, FIELD_METADATA_PREC, field_size );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                zr = add_assoc_null( field_meta_data, FIELD_METADATA_SCALE );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                break;
            default:
                zr = add_assoc_long( field_meta_data, FIELD_METADATA_SIZE, field_size );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                zr = add_assoc_null( field_meta_data, FIELD_METADATA_PREC );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                zr = add_assoc_null( field_meta_data, FIELD_METADATA_SCALE );
                CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );
                break;
        }

        // add the nullability to the array
        zr = add_assoc_long( field_meta_data, FIELD_METADATA_NULLABLE, field_is_nullable );
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
        sqlsrv_php_type = determine_sqlsrv_php_type( stmt, field_type, field_len, false );
    }

    // verify that we have an acceptable type to convert.
    CHECK_SQL_ERROR_EX( !is_valid_sqlsrv_phptype( sqlsrv_php_type ), stmt, _FN_, SQLSRV_ERROR_INVALID_TYPE, RETURN_FALSE );

    // retrieve the data
    get_field_common( stmt, _FN_, sqlsrv_php_type, static_cast<SQLUSMALLINT>( field_index ), &return_value TSRMLS_CC );
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
// If this functions returs true one time, then it will return true even after the result set is exhausted (sqlsrv_fetch returns null)

PHP_FUNCTION( sqlsrv_has_rows )
{
    sqlsrv_stmt* stmt = NULL;

    DECL_FUNC_NAME( "sqlsrv_has_rows" );
    LOG_FUNCTION;

    // get the statement resource
    PROCESS_PARAMS( stmt, _FN_, "r" );

    if( !stmt->fetch_called ) {
        determine_stmt_has_rows( stmt, _FN_ TSRMLS_CC );
    }

    if( stmt->has_rows ) {

        RETURN_TRUE;
    }
    else {
        
        RETURN_FALSE;
    }
}


// sqlsrv_next_result( resource $stmt )
//  
// Makes the next result (result set, row count, or output parameter) of the
// specified statement active.  The first (or only) result returned by a batch
// query or stored procedure is active without a call to sqlsrv_next_result.
// Any output parameters bound are only available after sqlsrv_next_result returns
// null as per SQL Native Client specs: http://msdn.microsoft.com/en-us/library/ms403283.aspx
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

    stmt->has_rows = false;

    // call the ODBC API that does what we want
    r = SQLMoreResults( stmt->ctx.handle );
    if( r == SQL_NO_DATA ) {
        
        if( stmt->param_strings ) {

            // if we're finished processing result sets, handle the output string parameters
            bool converted = adjust_output_lengths_and_encodings( stmt, _FN_ TSRMLS_CC );
            if( !converted ) {
                handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_OUTPUT_PARAM_ENCODING_TRANSLATE TSRMLS_CC, get_last_error_message() );
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
    SQLRETURN r = SQL_SUCCESS;
    sqlsrv_stmt* stmt = NULL;
    SQLINTEGER rows = -1;

    DECL_FUNC_NAME( "sqlsrv_num_rows" );
    LOG_FUNCTION;

    // get the statement resource
    PROCESS_PARAMS( stmt, _FN_, "r" );

    // make sure it was created as scrollable and already executed
    // if the cursor is dynamic, then the number of rows returned is always -1, so we issue an error if the cursor is dynamic
    CHECK_SQL_ERROR_EX( !stmt->scrollable || stmt->scroll_is_dynamic, stmt, _FN_, SQLSRV_ERROR_STATEMENT_NOT_SCROLLABLE, RETURN_FALSE );
    CHECK_SQL_ERROR_EX( !stmt->executed, stmt, _FN_, SQLSRV_ERROR_STATEMENT_NOT_EXECUTED, RETURN_FALSE );

    // retrieve the number of columns from ODBC
    r = SQLRowCount( stmt->ctx.handle, &rows );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE; );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
    
    // return it to the script
    RETURN_LONG( rows );
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
    // make sure it is not scrollable.  This function should only work for inserts, updates, and deletes,
    // but this is the best we can do to enforce that.
    CHECK_SQL_ERROR_EX( stmt->scrollable, stmt, _FN_, SQLSRV_ERROR_STATEMENT_SCROLLABLE, RETURN_FALSE );

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
// These are functions that deal with statement objects but are called from other modules, such as connection
// (conn.cpp)

class common_exec_error_handler {

public:

    common_exec_error_handler( sqlsrv_stmt* stmt ) :
        _stmt( stmt ), _success( false )
    {
    }

    ~common_exec_error_handler( void )
    {
        if( !_success ) {
            _stmt->free_param_data();
            SQLFreeStmt( _stmt->ctx.handle, SQL_RESET_PARAMS );
        }
    }

    void successful( void )
    {
        _success = true;
    }

 private:

    bool _success;
    sqlsrv_stmt* _stmt;

};

bool sqlsrv_stmt_common_execute( sqlsrv_stmt* stmt, const SQLCHAR* sql_string, int sql_len, bool direct, const char* _FN_ TSRMLS_DC )
{
    SQLRETURN r;
    SQLSMALLINT i;

    common_exec_error_handler error_exit_handler( stmt );

    // hold the buffers for UTF-16 encoded buffers bound as input parameters.  These buffers are released by the zval
    // upon function exit.
    zval_auto_ptr wbuffer_allocs;
    MAKE_STD_ZVAL( wbuffer_allocs );
    array_init( wbuffer_allocs );

    close_active_stream( stmt TSRMLS_CC );

    if( stmt->executed ) {
        do {
            r = SQLMoreResults( stmt->ctx.handle );
            CHECK_SQL_ERROR_EX( r == SQL_ERROR, stmt, _FN_, NULL, return false; );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
        } while( r != SQL_NO_DATA );

        // since we're done, clean up output parameters
        bool converted = adjust_output_lengths_and_encodings( stmt, _FN_ TSRMLS_CC );
        if( !converted ) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_OUTPUT_PARAM_ENCODING_TRANSLATE TSRMLS_CC, get_last_error_message() );
            return false;
        }            
    }

    stmt->free_param_data();

    stmt->executed = false;

    if( stmt->params_z ) {

        HashTable* params_ht = Z_ARRVAL_P( stmt->params_z );

        // allocate the buffer size array used by SQLBindParameter if it wasn't allocated by a 
        // previous execution.  The size of the array cannot change because the number of parameters
        // cannot change in between executions.
        if( stmt->params_ind_ptr == NULL ) {
            stmt->params_ind_ptr = static_cast<SQLINTEGER*>( sqlsrv_malloc( zend_hash_num_elements( params_ht ) * sizeof( SQLINTEGER )));
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
            sqlsrv_phptype sqlsrv_phptype;
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
                                             column_size, decimal_digits, sqlsrv_phptype TSRMLS_CC );
                // an error occurred, so return false
                if( param_z == NULL ) {
                    return false;
                }
            }
            // otherwise use the defaults
            else {

                bool success = determine_param_defaults( stmt, _FN_, param_z, i, php_type, direction, sql_type, 
                                                         sql_c_type, column_size, decimal_digits, sqlsrv_phptype TSRMLS_CC );
                if( !success ) {
                    return false;
                }
            }

            if( sqlsrv_phptype.typeinfo.encoding == SQLSRV_ENCODING_DEFAULT ) {
                sqlsrv_phptype.typeinfo.encoding = stmt->conn->default_encoding;
            }

            // set the buffer and buffer_len
            switch( php_type ) {
            
                case IS_NULL:
                    CHECK_SQL_ERROR_EX( direction == SQL_PARAM_INPUT_OUTPUT || direction == SQL_PARAM_OUTPUT, stmt, _FN_,
                        SQLSRV_ERROR_INVALID_OUTPUT_PARAM_TYPE, return false; );
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
                    if( direction == SQL_PARAM_INPUT && sqlsrv_phptype.typeinfo.encoding == CP_UTF8 ) {

                        zval_auto_ptr wbuffer_z;
                        ALLOC_INIT_ZVAL( wbuffer_z );

                        bool converted = convert_input_param_to_utf16( param_z, wbuffer_z );
                        if( !converted ) {
                            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INPUT_PARAM_ENCODING_TRANSLATE TSRMLS_CC, i, get_last_error_message() );
                            return false;
                        }
                        buffer = Z_STRVAL_P( wbuffer_z );
                        buffer_len = Z_STRLEN_P( wbuffer_z );
                        // memory added here is released upon function exit
                        add_next_index_zval( wbuffer_allocs, wbuffer_z );
                        wbuffer_z.transferred();
                    }
                    // if the output params zval in the statement isn't initialized, do so
                    if( direction != SQL_PARAM_INPUT && stmt->param_strings == NULL ) {
                        ALLOC_INIT_ZVAL( stmt->param_strings );
                        int zr = array_init( stmt->param_strings );
                        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, return false );
                    }
                    if( direction == SQL_PARAM_OUTPUT ) {
                        // if the current buffer size is smaller than the necessary size, resize the buffer and set the zval to use it.
                        if( buffer_len < column_size ) {
                        buffer = static_cast<char*>( sqlsrv_realloc( buffer, column_size + 1 ));
                            buffer_len = column_size + 1;
                            ZVAL_STRINGL( param_z, reinterpret_cast<char*>( buffer ), buffer_len, 0 );
                        }
                        // save the parameter to be adjusted and/or converted after the results are processed
                        sqlsrv_output_string output_string( param_z, sqlsrv_phptype.typeinfo.encoding, i - 1 );
                        HashTable* strings_ht = Z_ARRVAL_P( stmt->param_strings );
                        int next_index = zend_hash_next_free_element( strings_ht );
                        int zr = zend_hash_index_update( strings_ht, next_index, &output_string, sizeof( output_string ), NULL );
                        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, return false );
                        zval_add_ref( &param_z );   // we have a reference to the param
                    }
                    if( direction == SQL_PARAM_INPUT_OUTPUT ) {

                        buffer = Z_STRVAL_PP( &param_z );
                        buffer_len = Z_STRLEN_PP( &param_z );

                        if( sqlsrv_phptype.typeinfo.encoding == CP_UTF8 ) {

                            bool converted = convert_input_param_to_utf16( param_z, param_z );
                            if( !converted ) {
                                handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INPUT_PARAM_ENCODING_TRANSLATE TSRMLS_CC, i, get_last_error_message() );
                                return false;
                            }
                            // free the original buffer and set to our converted buffer
                            sqlsrv_free( buffer );
                            buffer = Z_STRVAL_PP( &param_z );
                            buffer_len = Z_STRLEN_PP( &param_z );
                            // save the parameter to be adjusted and/or converted after the results are processed
                            sqlsrv_output_string output_string( param_z, sqlsrv_phptype.typeinfo.encoding, i - 1 );
                            HashTable* strings_ht = Z_ARRVAL_P( stmt->param_strings );
                            int next_index = zend_hash_next_free_element( strings_ht );
                            int zr = zend_hash_index_update( strings_ht, next_index, &output_string, sizeof( output_string ), NULL );
                            CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, return false );
                            zval_add_ref( &param_z );
                        }
                        // if the current buffer size is smaller than the necessary size, resize the buffer and set the zval to use it.
                        if( buffer_len < column_size ) {
                            buffer = static_cast<char*>( sqlsrv_realloc( buffer, column_size + 1 ));
                            buffer_len = column_size;
                            ZVAL_STRINGL( param_z, reinterpret_cast<char*>( buffer ), buffer_len, 0 );
                        }
                    }
                    // correct the column size to be number of characters, not the number of bytes.
                    if( sql_type.typeinfo.type == SQL_WCHAR || sql_type.typeinfo.type == SQL_WVARCHAR ) {
                        column_size /= sizeof( wchar_t );
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
                        return false;
                    }
                    if( class_name_len != DATETIME_CLASS_NAME_LEN || stricmp( class_name, DATETIME_CLASS_NAME )) {

                        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE TSRMLS_CC, i );
                        return false;
                    }
                    CHECK_SQL_ERROR_EX( direction == SQL_PARAM_INPUT_OUTPUT || direction == SQL_PARAM_OUTPUT, stmt, _FN_,
                        SQLSRV_ERROR_INVALID_OUTPUT_PARAM_TYPE, return false; );
                    // call the PHP function date_format to convert the object to a string that SQL Server understands
                    ALLOC_INIT_ZVAL( buffer_z );
                    ALLOC_INIT_ZVAL( function_z );
                    ALLOC_INIT_ZVAL( format_z );
                    ZVAL_STRINGL( function_z, "date_format", sizeof( "date_format" ) - 1, 1 );
                    // if the user specifies the 'date' sql type, giving it the normal format will cause a 'date overflow error'
                    // meaning there is too much information in the character string.  If the user specifies the 'datetimeoffset'
                    // sql type, it lacks the timezone.  These conversions are only used when the specific type is specified
                    // by the user in the param array.
                    if( sql_type.typeinfo.type == SQL_SS_TIMESTAMPOFFSET ) {
                        ZVAL_STRINGL( format_z, const_cast<char*>( DATETIMEOFFSET_FORMAT ), DATETIMEOFFSET_FORMAT_LEN, 1 /* dup */ );
                    }
                    else if( sql_type.typeinfo.type == SQL_TYPE_DATE ) {
                        ZVAL_STRINGL( format_z, const_cast<char*>( DATE_FORMAT ), DATE_FORMAT_LEN, 1 /* dup */ );
                    }
                    else {
                        ZVAL_STRINGL( format_z, const_cast<char*>( DATETIME_FORMAT ), DATETIME_FORMAT_LEN, 1 /* dup */);
                    }
                    params[0] = param_z;
                    params[1] = format_z;
                    result = call_user_function( EG( function_table ), NULL, function_z, buffer_z, 2, params TSRMLS_CC );
                    if( result == FAILURE ) {

                        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE TSRMLS_CC, i );
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
                        SQLSRV_ERROR_INVALID_OUTPUT_PARAM_TYPE, return false; );
                    if( stmt->param_streams == NULL ) {
                        ALLOC_INIT_ZVAL( stmt->param_streams );
                        int zr = array_init( stmt->param_streams );
                        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, return false );
                    }
                    sqlsrv_stream_encoding stream_encoding( param_z, sqlsrv_phptype.typeinfo.encoding );
                    HashTable* streams_ht = Z_ARRVAL_P( stmt->param_streams );
                    int next_index = zend_hash_next_free_element( streams_ht );
                    int zr = zend_hash_index_update( streams_ht, next_index, &stream_encoding, sizeof( stream_encoding ), NULL );
                    CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, return false );
                    buffer = reinterpret_cast<SQLPOINTER>( next_index );
                    zval_add_ref( &param_z ); // so that it doesn't go away while we're using it
                    buffer_len = 0;
                    stmt->params_ind_ptr[ i-1 ] = SQL_DATA_AT_EXEC;
                    break;                    
                }
                default:
                    handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAM_TYPE TSRMLS_CC );
                    return false;
            }

            if( direction  < 0 || direction > 0xffff ) DIE( "direction not valid SQLSMALLINT" );
            r = SQLBindParameter( stmt->ctx.handle, i, static_cast<SQLSMALLINT>( direction ), sql_c_type, sql_type.typeinfo.type, column_size, decimal_digits,
                                  buffer, buffer_len, &stmt->params_ind_ptr[ i-1 ] );
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, return false; );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
        }
    }

    // execute the statement
    if( direct ) {
        if( sql_string == NULL ) DIE( "sqlsrv_stmt_common_execute: sql_string must be valid when direct = true");
        wchar_t* wsql_string;
        unsigned int wsql_len = 0;
        // if the string is empty, we initialize the fields and skip since an empty string is a 
        // failure case for utf16_string_from_mbcs_string 
        if( sql_len == 0 || ( sql_string[0] == '\0' && sql_len == 1 )) {
            wsql_string = reinterpret_cast<wchar_t*>( sqlsrv_malloc( 1 ));
            wsql_string[0] = '\0';
            wsql_len = 0;
        }
        else {
            wsql_string = utf16_string_from_mbcs_string( stmt->conn->default_encoding, reinterpret_cast<const char*>( sql_string ), sql_len, &wsql_len );
            if( wsql_string == NULL ) {
                handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_QUERY_STRING_ENCODING_TRANSLATE TSRMLS_CC, get_last_error_message() );
                return false;
            }
        }
        r = SQLExecDirectW( stmt->ctx.handle, const_cast<SQLWCHAR*>( wsql_string ), SQL_NTS );
        sqlsrv_free( wsql_string );
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
    else if( r == SQL_NO_DATA ) {

        // if no data was returned, then handle the output string parameters immediately
        stmt->has_rows = false;
        bool converted = adjust_output_lengths_and_encodings( stmt, _FN_ TSRMLS_CC );
        if( !converted ) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_OUTPUT_PARAM_ENCODING_TRANSLATE TSRMLS_CC, get_last_error_message() );
            return false;
        }
    }
    // if results should have been returned, check for errors
    else if( !SQL_SUCCEEDED( r )) {

        SQLCHAR err_msg[ SQL_MAX_MESSAGE_LENGTH + 1 ];
        SQLSMALLINT len;
        SQLRETURN dr = SQLGetDiagField( SQL_HANDLE_STMT, stmt->ctx.handle, 1, SQL_DIAG_MESSAGE_TEXT, err_msg, SQL_MAX_MESSAGE_LENGTH, &len );

        // before general error checking, we check for the 'connection busy' error caused by having MultipleActiveResultSets off
        // and return a more helpful message prepended to the ODBC errors if that error occurs
        if( SQL_SUCCEEDED( dr ) && len == sizeof( CONNECTION_BUSY_ODBC_ERROR ) - 1 &&
            !strcmp( reinterpret_cast<const char*>( err_msg ), CONNECTION_BUSY_ODBC_ERROR )) {
            handle_error( &stmt->ctx, LOG_CONN, _FN_, SQLSRV_ERROR_MARS_OFF TSRMLS_CC );
            return false;
        }

        CHECK_SQL_ERROR( r, stmt, _FN_, NULL, return false; );
    }
    else {

        // this CHECK_SQL_ERROR is to return warnings as errors if that configuration setting is true
        CHECK_SQL_ERROR( r, stmt, _FN_, NULL, return false; );
        CHECK_SQL_WARNING( r, stmt, _FN_, NULL );

        stmt->has_rows = false;

        // check for no columns, which means that there are no rows
        bool result_present;
        r = has_any_result( stmt, result_present );
        CHECK_SQL_ERROR( r, stmt, _FN_, NULL, return false; );
        CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
        if( !result_present ) {
            // if there are no rows, then adjust the output parameters
            bool adjusted = adjust_output_lengths_and_encodings( stmt, _FN_ TSRMLS_CC );
            if( !adjusted ) {
                handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_OUTPUT_PARAM_ENCODING_TRANSLATE TSRMLS_CC, get_last_error_message() );
                return false;
            }
        }
    }
    
    stmt->new_result_set();
    stmt->executed = true;

    error_exit_handler.successful();

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
            sqlsrv_free( stmt->params_ind_ptr );
            stmt->params_ind_ptr = NULL;
        }
    }

    close_active_stream( stmt TSRMLS_CC );

    if( stmt->param_buffer != NULL ) {
        sqlsrv_free( stmt->param_buffer );
        stmt->param_buffer = NULL;
    }

    stmt->free_param_data();

    r = SQLFreeHandle( SQL_HANDLE_STMT, stmt->ctx.handle );
    
    // we don't handle errors here because the error log may have already gone away.  We just log them.
    if( !SQL_SUCCEEDED( r ) ) {
        LOG( SEV_ERROR, LOG_STMT, "Failed to free handle for stmt resource %1!d!", stmt->conn_index );
    }

    // mark the statement as closed
    stmt->ctx.handle = SQL_NULL_HANDLE;
}

void free_stmt_resource( zval* stmt_z TSRMLS_DC )
{
    int zr = zend_hash_index_del( &EG( regular_list ), Z_RESVAL_P( stmt_z ));
    if( zr == FAILURE ) {
        LOG( SEV_ERROR, LOG_STMT, "Failed to remove stmt resource %1!d!", Z_RESVAL_P( stmt_z ));
    }

    ZVAL_NULL( stmt_z );
    zval_ptr_dtor( &stmt_z );
}

void __cdecl sqlsrv_stmt_dtor( zend_rsrc_list_entry *rsrc TSRMLS_DC )
{
    // get the structure
    sqlsrv_stmt *stmt = static_cast<sqlsrv_stmt*>( rsrc->ptr );
    LOG( SEV_NOTICE, LOG_STMT, "sqlsrv_stmt_dtor: entering" );

    free_odbc_resources( stmt TSRMLS_CC );

    if( stmt->conn ) {
        int zr = zend_hash_index_del( stmt->conn->stmts, stmt->conn_index );
        if( zr == FAILURE ) {
            LOG( SEV_ERROR, LOG_STMT, "Failed to remove statement reference from the connection" );
        }
    }

    sqlsrv_free( stmt );
    rsrc->ptr = NULL;
}

// centralized place to release all the parameter data that accrues during the execution
// phase.
void sqlsrv_stmt::free_param_data( void )
{
    // if we allocated any output string parameters in a previous execution, release them here.
    if( param_strings ) {
        zval_ptr_dtor( &param_strings );
        param_strings = NULL;
    }

    // if we allocated any datetime strings in a previous execution, release them here.
    if( param_datetime_buffers ) {
        zval_ptr_dtor( &param_datetime_buffers );
        param_datetime_buffers = NULL;
    }

    // if we allocated any streams in a previous execution, release them here.
    if( param_streams ) {
        zval_ptr_dtor( &param_streams );
        param_streams = NULL;
    }
}

// to be called whenever a new result set is created, such as after an
// execute or next_result.  Resets the state variables.
void sqlsrv_stmt::new_result_set( void )
{
    fetch_called = false;
    if( fetch_fields ) {
        for( int i = 0; i < fetch_fields_count; ++i ) {
            sqlsrv_free( fetch_fields[ i ].name );
        }
        sqlsrv_free( fetch_fields );
    }
    fetch_fields = NULL;
    fetch_fields_count = 0;
    last_field_index = -1;
    past_fetch_end = false;
    past_next_result_end = false;
    has_rows = false;
}

// *** internal functions ***

namespace {

// loop through the output string parameters and adjust their lengths and their
// encoding from UTF-16 if necessary.
bool adjust_output_lengths_and_encodings( sqlsrv_stmt* stmt, const char* _FN_ TSRMLS_DC )
{
    if( stmt->param_strings == NULL ) 
        return true;

    bool converted = true;
    HashTable* params_ht = Z_ARRVAL_P( stmt->param_strings );

    for( zend_hash_internal_pointer_reset( params_ht );
         zend_hash_has_more_elements( params_ht ) == SUCCESS;
         zend_hash_move_forward( params_ht ) ) {

        sqlsrv_output_string *output_string;
        int zr = zend_hash_get_current_data( params_ht, (void**) &output_string );
        CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, zval_ptr_dtor( &stmt->param_strings ); return false; );

        // adjust the length of the string to the value returned by SQLBindParameter in the ind_ptr parameter
        char* str = Z_STRVAL_P( output_string->string_z );
        int str_len = stmt->params_ind_ptr[ output_string->param_num ];

        if( output_string->encoding != SQLSRV_ENCODING_CHAR && output_string->encoding != SQLSRV_ENCODING_BINARY ) {

            str_len >>= 1; // from # of bytes to # of wchars
            ++str_len;     // include the NULL character

            // get the size of the wide char string
            int enc_size = WideCharToMultiByte( output_string->encoding, 0, reinterpret_cast<wchar_t*>( str ), str_len, NULL, 0, NULL, NULL );
            // if no errors occurred
            if( enc_size != 0 ) {
                // allocate a buffer large enough
                char* enc_buffer = reinterpret_cast<char*>( sqlsrv_malloc( enc_size + 1 ));
                // convert the string
                int r = WideCharToMultiByte( CP_UTF8, 0, reinterpret_cast<wchar_t*>( str ), str_len, enc_buffer, enc_size, NULL, NULL );
                // if an error occurred during conversion
                if( r == 0 ) {
                    // free the UTF-8 string and leave the current output param alone
                    sqlsrv_free( enc_buffer );
                    converted = false;
                }
                else {
                    --enc_size;
                    // swap the converted string for the original string
                    enc_buffer[ enc_size ] = '\0';
                    ZVAL_STRINGL( output_string->string_z, enc_buffer, enc_size, 0 );
                    sqlsrv_free( str );
                }
            }
            else {
                converted = false;
            }
        }
        else {
            ZVAL_STRINGL( output_string->string_z, str, str_len, 0 );
            str[ str_len ] = '\0';  // null terminate the string to avoid the debug php warning
        }
    }

    zval_ptr_dtor( &stmt->param_strings );
    stmt->param_strings = NULL;

    return converted;
}


// utility routine to convert an input parameter from UTF-8 to UTF-16
bool convert_input_param_to_utf16( zval* input_param_z, zval* converted_param_z )
{
    if( input_param_z != converted_param_z && Z_TYPE_P( converted_param_z ) != IS_NULL ) {
        DIE( "convert_input_param_z called with unknown parameter state" );
    }

    const char* buffer = Z_STRVAL_P( input_param_z );
    int buffer_len = Z_STRLEN_P( input_param_z );
    int wchar_size;

    // if the string is empty, then just return that the conversion succeeded as
    // MultiByteToWideChar will "fail" on an empty string.
    if( buffer_len == 0 ) {
        ZVAL_STRINGL( converted_param_z, "", 0, 1 );
        return true;
    }

    // if the parameter is an input parameter, calc the size of the necessary buffer from the length of the string
    wchar_size = MultiByteToWideChar( CP_UTF8, MB_ERR_INVALID_CHARS, reinterpret_cast<LPCSTR>( buffer ), buffer_len, NULL, 0 );
    // if there was a problem determining the size of the string, return false
    if( wchar_size == 0 ) {
        return false;
    }
    wchar_t* wbuffer = reinterpret_cast<wchar_t*>( sqlsrv_malloc( (wchar_size + 1) * sizeof( wchar_t ) ));
    // convert the utf-8string to a wchar string in the new buffer
    int r = MultiByteToWideChar( CP_UTF8, MB_ERR_INVALID_CHARS, (LPCSTR) buffer, buffer_len, wbuffer, wchar_size );
    // if there was a problem converting the string, then return false
    if( r == 0 ) {
        sqlsrv_free( wbuffer );
        return false;
    }
    wbuffer[ wchar_size ] = '\0';
    ZVAL_STRINGL( converted_param_z, reinterpret_cast<char*>( wbuffer ),
                  wchar_size * sizeof( wchar_t ), 0 );
    return true;
}


// check_for_next_stream_parameter
// check for the next stream parameter.  Returns true if another parameter is ready, false if either an error
// or there are no more parameters.
bool check_for_next_stream_parameter( __inout sqlsrv_stmt* stmt, __out zval* return_value, const char* _FN_ TSRMLS_DC )
{
    int stream_index = 0;
    sqlsrv_stream_encoding* stream_encoding;
    zval* param_z = NULL;
    SQLRETURN r = SQL_SUCCESS;
    HashTable* streams_ht = Z_ARRVAL_P( stmt->param_streams );

    RETVAL_TRUE;

    r = SQLParamData( stmt->ctx.handle, reinterpret_cast<SQLPOINTER*>( &stream_index ));
    int zr = zend_hash_index_find( streams_ht, stream_index, (void**) &stream_encoding );
    CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETVAL_NULL(); return false; );

    param_z = stream_encoding->stream_z;

    // if there is a waiting parameter, make it current
    if( r == SQL_NEED_DATA ) {
        stmt->current_stream = param_z;
        stmt->current_stream_read = 0;
        stmt->current_stream_encoding = stream_encoding->encoding;
    }
    // otherwise if it wasn't an error, we've exhausted the bound parameters, so return that we're done
    else if( SQL_SUCCEEDED( r ) || r == SQL_NO_DATA ) {
        CHECK_SQL_WARNING( r, stmt, _FN_, NULL );             
        RETVAL_NULL();
        return false;
    }
    // otherwise, record the error and return false
    else {
        CHECK_SQL_ERROR( r, stmt, _FN_, NULL, SQLCancel( stmt->ctx.handle ); RETVAL_FALSE; return false; );
    }

    // there are more parameters
    return true;
}


// get_field_common
// common code shared between sqlsrv_get_field and sqlsrv_fetch_array.  The "return value" is transferred via
// the field_value parameter, FALSE being when an error occurs.
void get_field_common( __inout sqlsrv_stmt* stmt, const char* _FN_, sqlsrv_phptype sqlsrv_phptype, SQLUSMALLINT field_index, __out zval**field_value TSRMLS_DC )
{
    SQLRETURN r;

    close_active_stream( stmt TSRMLS_CC );

    // make sure that fetch is called before trying to retrieve to return a helpful sqlsrv error
    CHECK_SQL_ERROR_EX( !stmt->fetch_called, stmt, _FN_, SQLSRV_ERROR_FETCH_NOT_CALLED, ZVAL_FALSE( *field_value ); return; );

    // make sure they're not trying to retrieve fields incorrectly.  Otherwise return a helpful sqlsrv error
    if( !stmt->scrollable && stmt->last_field_index > field_index ) {
        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_FIELD_INDEX_ERROR TSRMLS_CC, field_index, stmt->last_field_index );
        ZVAL_FALSE( *field_value );
        return;
    }

    // what we do is based on the PHP type to be returned.
    switch( sqlsrv_phptype.typeinfo.type ) {

        // call a refactored routine get_field_as_string
        case SQLSRV_PHPTYPE_STRING:
        {
            get_field_as_string( stmt, sqlsrv_phptype, field_index, *field_value, _FN_ TSRMLS_CC );
            if( Z_TYPE_PP( field_value ) == IS_BOOL && Z_LVAL_PP( field_value ) == 0 ) {
                return;
            }
        }
        break;

        // create a stream wrapper around the field and return that object to the PHP script.  calls to fread
        // on the stream will result in calls to SQLGetData.  This is handled in stream.cpp.  See that file
        // for how these fields are used.
        case SQLSRV_PHPTYPE_STREAM:
        {
            php_stream* stream;
            sqlsrv_stream* ss;
            SQLINTEGER sql_type;
            
            r = SQLColAttribute( stmt->ctx.handle, field_index + 1, SQL_DESC_TYPE, NULL, 0, NULL, &sql_type );                
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, ZVAL_FALSE( *field_value ); return; );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );            
            CHECK_SQL_ERROR_EX( !is_streamable_type( sql_type ), stmt, _FN_, SQLSRV_ERROR_STREAMABLE_TYPES_ONLY, ZVAL_FALSE( *field_value ); return; );


            stream = php_stream_open_wrapper( "sqlsrv://sqlncli10", "r", 0, NULL );
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

        // get the date as a string (http://msdn2.microsoft.com/en-us/library/ms712387(VS.85).aspx) and
        // convert it to a DateTime object and return the created object
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
                sql_c_type = SQLTYPE_INVALID;
                    break;
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
sqlsrv_sqltype determine_sql_type( zval const* value, int encoding, SERVER_VERSION server_version )
{
    sqlsrv_sqltype sql_type;
    sql_type.typeinfo.type = SQLTYPE_INVALID;
    sql_type.typeinfo.size = SQLSRV_INVALID_SIZE;
    sql_type.typeinfo.scale = SQLSRV_INVALID_SCALE;
    int php_type = Z_TYPE_P( value );

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
            switch( encoding ) {
                case SQLSRV_ENCODING_CHAR:
                sql_type.typeinfo.type = SQL_VARCHAR;
                    break;
                case SQLSRV_ENCODING_BINARY:
                sql_type.typeinfo.type = SQL_VARBINARY;
                    break;
                case CP_UTF8:
                    sql_type.typeinfo.type = SQL_WVARCHAR;
                    break;
                default:
                    DIE( "Illegal encoding in determine_sql_type" );
                    break;
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
            // if the user is sending this type to SQL Server 2005 or earlier, make it appear
            // as a SQLSRV_SQLTYPE_DATETIME, otherwise it should be SQLSRV_SQLTYPE_TIMESTAMPOFFSET
            // since these are the date types of the highest precision for their respective server versions
            if( server_version <= SERVER_VERSION_2005 ) {
                sql_type.typeinfo.type = SQL_TYPE_TIMESTAMP;
                sql_type.typeinfo.size = 23;
                sql_type.typeinfo.scale = 3;
            }
            else {
                sql_type.typeinfo.type = SQL_SS_TIMESTAMPOFFSET;
                sql_type.typeinfo.size = 34;
                sql_type.typeinfo.scale = 7;
            }
            break;
        default:
            // this comes from the user, so we can't assert here
            sql_type.typeinfo.type = SQLTYPE_INVALID;
            break;
    }
    
    return sql_type;
}

// given a SQL Server type, return a sqlsrv php type
sqlsrv_phptype determine_sqlsrv_php_type( sqlsrv_stmt const* stmt, SQLINTEGER sql_type, SQLUINTEGER size, bool prefer_string )
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
            sqlsrv_phptype.typeinfo.encoding = stmt->conn->default_encoding;
            break;
        case SQL_VARCHAR:
        case SQL_WVARCHAR:
            if( prefer_string || size != SQL_SS_LENGTH_UNLIMITED ) {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STRING;
                sqlsrv_phptype.typeinfo.encoding = stmt->conn->default_encoding;
            }
            else {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STREAM;
                sqlsrv_phptype.typeinfo.encoding = stmt->conn->default_encoding;
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
                sqlsrv_phptype.typeinfo.encoding = stmt->conn->default_encoding;
            }
            else {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STREAM;
                sqlsrv_phptype.typeinfo.encoding = stmt->conn->default_encoding;
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
            if( stmt->conn->date_as_string ) {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_STRING;
                sqlsrv_phptype.typeinfo.encoding = stmt->conn->default_encoding;
            }
            else {
                sqlsrv_phptype.typeinfo.type = SQLSRV_PHPTYPE_DATETIME;
            }
            break;
        default:
            sqlsrv_phptype.typeinfo.type = PHPTYPE_INVALID;
            break;
    }
    
    return sqlsrv_phptype;
}

// put in the column size and scale/decimal digits of the sql server type
// these values are taken from the MSDN page at http://msdn2.microsoft.com/en-us/library/ms711786(VS.85).aspx
// column_size is actually the size of the field in bytes, so a nvarchar(4000) is 8000 bytes
bool determine_column_size_or_precision( sqlsrv_stmt const* stmt, sqlsrv_sqltype sqlsrv_type, __out SQLUINTEGER* column_size, __out SQLSMALLINT* decimal_digits )
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
        case SQL_WCHAR:
        case SQL_WVARCHAR: 
            *column_size = sqlsrv_type.typeinfo.size;
            if( *column_size == SQLSRV_SIZE_MAX_TYPE ) {
                *column_size = SQL_SS_LENGTH_UNLIMITED;
                break;
            }
            *column_size *= 2;  // convert to byte size from wchar size
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


// return whether or not an execution returned a result set with
// columns or not in result_present.  The actual return value is
// either success or an error determining the presence of rows.

SQLRETURN has_result_columns( sqlsrv_stmt* stmt, bool& result_present )
{
    // Use SQLNumResultCols to determine if we have rows or not.
    SQLRETURN r;
    SQLSMALLINT num_cols;
    r = SQLNumResultCols( stmt->ctx.handle, &num_cols );
    result_present = (num_cols != 0);
    return r;
}

// return if any result set or rows affected message is waiting
// to be consumed and moved over by sqlsrv_next_result.  The return
// value is if an error occurred determining.  Whether
// or not results are present that must be consumed is returned
// in the result_present paramter.

SQLRETURN has_any_result( sqlsrv_stmt* stmt, bool& result_present )
{
    // Use SQLNumResultCols to determine if we have rows or not.
    SQLRETURN r;
    r = has_result_columns( stmt, result_present );
    if( !SQL_SUCCEEDED( r ))
        return r;
    // use SQLRowCount to determine if there is a rows status waiting
    SQLLEN rows_affected;
    r = SQLRowCount( stmt->ctx.handle, &rows_affected );
    result_present = (result_present) || (rows_affected != -1);
    return r;
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
        case SQL_TYPE_DATE:
        case SQL_SS_TIME2:
        case SQL_SS_TIMESTAMPOFFSET:
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
            if( type.typeinfo.encoding == SQLSRV_ENCODING_BINARY || type.typeinfo.encoding == SQLSRV_ENCODING_CHAR 
                || type.typeinfo.encoding == CP_UTF8 || type.typeinfo.encoding == SQLSRV_ENCODING_DEFAULT ) {
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
        // we return character encoding for LONG and DOUBLE as well since by default the encoding is always
        // character, even though it won't mean anything for these data types.
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

bool should_be_converted_from_utf16( SQLINTEGER sql_type )
{
    switch( sql_type ) {

        case SQL_BINARY:
        case SQL_VARBINARY:
        case SQL_LONGVARBINARY:
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

// convert a string from utf-16 to another encoding and return the new string.  The utf-16 string is released
// by this function if no errors occurred.  Otherwise the parameters are not changed.

bool convert_string_from_utf16( sqlsrv_phptype sqlsrv_phptype, char** string, SQLINTEGER& len )
{
    char* utf16_string = *string;
    unsigned int utf16_len = len / 2;  // from # of bytes to # of wchars
    char *enc_string = NULL;
    unsigned int enc_len = 0;

    ++utf16_len;     // include the NULL character

    // calculate the number of characters needed
    enc_len = WideCharToMultiByte( sqlsrv_phptype.typeinfo.encoding, 0,
                                   reinterpret_cast<LPCWSTR>( utf16_string ), utf16_len, 
                                   NULL, 0, NULL, NULL );
    if( enc_len == 0 ) {
        return false;
    }
    // we must allocate a new buffer because it is possible that a UTF-8 string is longer than
    // the corresponding UTF-16 string, so we cannot use an inplace conversion
    enc_string = reinterpret_cast<char*>( sqlsrv_malloc( enc_len + 1 ));
    int rc = WideCharToMultiByte( sqlsrv_phptype.typeinfo.encoding, 0,
                                  reinterpret_cast<LPCWSTR>( utf16_string ), utf16_len, 
                                  enc_string, enc_len, NULL, NULL );
    if( rc == 0 ) {
        return false;
    }

    sqlsrv_free( utf16_string );
    *string = enc_string;
    len = enc_len - 1;

    return true;
}

bool calc_string_size( sqlsrv_stmt const* s, SQLUSMALLINT field_index, SQLUINTEGER& size, const char* _FN_ TSRMLS_DC )
{
    SQLRETURN r;
    SQLINTEGER sql_type;

    r = SQLColAttribute( s->ctx.handle, field_index + 1, SQL_DESC_TYPE, NULL, 0, NULL, &sql_type );
    CHECK_SQL_ERROR( r, s, _FN_, NULL, return false; );
    CHECK_SQL_WARNING( r, s, _FN_, NULL );

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
            r = SQLColAttribute( s->ctx.handle, field_index + 1, SQL_DESC_DISPLAY_SIZE, NULL, 0, NULL, &size );
            CHECK_SQL_ERROR( r, s, _FN_, NULL, return false );
            CHECK_SQL_WARNING( r, s, _FN_, NULL );
            return true;
        // for wide char types for which the size is known, return the octet length instead, since it will include the
        // the number of bytes necessary for the string, not just the characters
        case SQL_WCHAR:
        case SQL_WVARCHAR: 
            r = SQLColAttribute( s->ctx.handle, field_index + 1, SQL_DESC_OCTET_LENGTH, NULL, 0, NULL, &size );
            CHECK_SQL_ERROR( r, s, _FN_, NULL, return false; );
            CHECK_SQL_WARNING( r, s, _FN_, NULL );
            return true;
            break;
        default:
            return false;
    }
}

// a refactoring since the clause in get_field_common was becoming too large.
void get_field_as_string( sqlsrv_stmt const* s, sqlsrv_phptype sqlsrv_phptype, SQLUSMALLINT field_index, __out zval* return_value, const char* _FN_ TSRMLS_DC )
{
    SQLRETURN r;
    char* field;
    SQLINTEGER field_len;
    SQLUINTEGER sql_display_size;
    SQLINTEGER sql_type;
    SQLSMALLINT c_type;
    SQLSMALLINT extra;
    
    if( sqlsrv_phptype.typeinfo.type != SQLSRV_PHPTYPE_STRING ) {
        DIE( "type should be SQLSRV_PHPTYPE_STRING in get_field_as_string" );
    }

    if( sqlsrv_phptype.typeinfo.encoding == SQLSRV_ENCODING_DEFAULT ) {
        sqlsrv_phptype.typeinfo.encoding = s->conn->default_encoding;
    }

    // set the C type and account for null characters at the end of the data
    switch( sqlsrv_phptype.typeinfo.encoding ) {
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

    r = SQLColAttribute( s->ctx.handle, field_index + 1, SQL_DESC_TYPE, NULL, 0, NULL, &sql_type );                
    CHECK_SQL_ERROR( r, s, _FN_, NULL, RETURN_FALSE; );
    CHECK_SQL_WARNING( r, s, _FN_, NULL );            
    bool success = calc_string_size( s, field_index, sql_display_size, _FN_ TSRMLS_CC );
    // errors already posted
    if( !success ) {
        RETURN_FALSE;
    }

    // if this is a large type, then read the first few bytes to get the actual length from SQLGetData
    if( sql_display_size == 0 || sql_display_size == LONG_MAX || sql_display_size == LONG_MAX >> 1 || sql_display_size == ULONG_MAX - 1 ) {

        field_len = INITIAL_FIELD_STRING_LEN;
        field = static_cast<char*>( sqlsrv_malloc( field_len + extra + 1 ));
        r = SQLGetData( s->ctx.handle, field_index + 1, c_type, field, field_len + extra, &field_len );
        if( field_len == SQL_NULL_DATA ) {
            sqlsrv_free( field );
            RETURN_NULL();
        }
        if( r == SQL_NO_DATA ) {
            handle_error( &s->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_NO_DATA TSRMLS_CC, field_index );
            sqlsrv_free( field );
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
                    field_len = INITIAL_FIELD_STRING_LEN;
                    do {
                        SQLINTEGER initial_field_len = field_len;
                        field_len *= 2;
                        field = static_cast<char*>( sqlsrv_realloc( field, field_len + extra + 1 ));
                        field_len -= initial_field_len;
                        r = SQLGetData( s->ctx.handle, field_index + 1, c_type, field + initial_field_len,
                                        field_len + extra, &dummy_field_len );

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
                    CHECK_SQL_ERROR( r, s, _FN_, NULL, sqlsrv_free( field ); RETURN_FALSE; );
                }
                else {
                    SQLINTEGER last_field_len;
                    field = static_cast<char*>( sqlsrv_realloc( field, field_len + extra + 1 ));
                    field_len -= INITIAL_FIELD_STRING_LEN;
                    r = SQLGetData( s->ctx.handle, field_index + 1, c_type, field + INITIAL_FIELD_STRING_LEN,
                                    field_len + extra, &last_field_len );
                    if( last_field_len == SQL_NULL_DATA ) {
                        sqlsrv_free( field );
                        RETURN_NULL();
                    }
                    if( r == SQL_NO_DATA ) {
                        handle_error( &s->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_NO_DATA TSRMLS_CC, field_index );
                        sqlsrv_free( field );
                        RETURN_FALSE;
                        return;
                    }
                    CHECK_SQL_ERROR( r, s, _FN_, NULL, sqlsrv_free( field ); RETURN_FALSE; );
                    field_len += INITIAL_FIELD_STRING_LEN;
                }
            }
            else {
                handle_warning( &s->ctx, LOG_STMT, _FN_, NULL TSRMLS_CC );
            }
        }
        else {
            CHECK_SQL_ERROR( r, s, _FN_, NULL, sqlsrv_free( field ); RETURN_FALSE; );
        }

        if( c_type == SQL_C_WCHAR ) {
            bool converted = convert_string_from_utf16( sqlsrv_phptype, &field, field_len );
            if( !converted ) {
                handle_error( &s->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE TSRMLS_CC, get_last_error_message() );
                sqlsrv_free( field );
                RETURN_FALSE;
            }
        }
    }
    else if( sql_display_size >= 1 && sql_display_size <= SQL_SERVER_MAX_FIELD_SIZE ) {

        // only allow binary retrievals for char and binary types.  All others get a string converted
        // to the encoding type they asked for.
        if( is_fixed_size_type( sql_type )) {
            c_type = SQL_C_WCHAR;
            extra = sizeof( WCHAR );
        }

        if( c_type == SQL_C_CHAR ) {
            ++sql_display_size;
        }
        else if( c_type == SQL_C_WCHAR ) {
            sql_display_size = (sql_display_size * sizeof(WCHAR)) + sizeof(WCHAR);  // include the null terminator
        }
        field = static_cast<char*>( sqlsrv_malloc( sql_display_size + extra + 1 ));
         // get the data
        r = SQLGetData( s->ctx.handle, field_index + 1, c_type, field, sql_display_size, &field_len );
        if( field_len == SQL_NULL_DATA ) {
            sqlsrv_free( field );
            RETURN_NULL();
        }
        if( r == SQL_NO_DATA ) {
            handle_error( &s->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_NO_DATA TSRMLS_CC, field_index );
            sqlsrv_free( field );
            RETURN_FALSE;
        }
        CHECK_SQL_ERROR( r, s, _FN_, NULL, sqlsrv_free( field ); RETURN_FALSE; );
        CHECK_SQL_WARNING( r, s, _FN_, NULL );

        if( c_type == SQL_C_WCHAR ) {
            
            bool converted = convert_string_from_utf16( sqlsrv_phptype, &field, field_len );
            if( !converted ) {
                handle_error( &s->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE TSRMLS_CC, get_last_error_message() );
                sqlsrv_free( field );
                RETURN_FALSE;
            }
        }
    }
    else {
        DIE( "Invalid sql_display_size" );
        return; // to eliminate a warning
    }

    ZVAL_STRINGL( return_value, field, field_len, 0 );
    // prevent a warning in debug mode about strings not being NULL terminated.  Even though nulls are not necessary, the PHP
    // runtime checks to see if a string is null terminated and issues a warning about it if running in debug mode.
    // SQL_C_BINARY fields don't return a NULL terminator, so we allocate an extra byte on each field and use the ternary
    // operator to set add 1 to fill the null terminator
    field[ field_len ] = '\0';
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


// called when the user gives SQLSRV_SQLTYPE_DECIMAL or SQLSRV_SQLTYPE_NUMERIC sql types as the type of the
// field.  encodes these into a sqlsrv_sqltype structure (see php_sqlsrv.h)
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


// verify an encoding given to type_and_encoding by looking through the list
// of standard encodings created at module initialization time
bool verify_encoding( const char* encoding_string, __out sqlsrv_phptype& phptype_encoding TSRMLS_DC )
{
    for( zend_hash_internal_pointer_reset( SQLSRV_G( encodings ));
         zend_hash_has_more_elements( SQLSRV_G( encodings )) == SUCCESS;
         zend_hash_move_forward( SQLSRV_G( encodings ) ) ) {

        sqlsrv_encoding* encoding;
        int zr = zend_hash_get_current_data( SQLSRV_G( encodings ), (void**) &encoding );
        if( zr == FAILURE ) {
            DIE( "Fatal: Error retrieving encoding from encoding hash table." );
        }

        if( !stricmp( encoding_string, encoding->iana )) {
            phptype_encoding.typeinfo.encoding = encoding->code_page;
            return true;
        }
    }

    return false;
}

// common code for SQLSRV_PHPTYPE_STREAM and SQLSRV_PHPTYPE_STRING php types given as parameters.
// encodes the type and encoding into a sqlsrv_phptype structure (see php_sqlsrv.h)
void type_and_encoding( INTERNAL_FUNCTION_PARAMETERS, int type )
{
    SQLSRV_UNUSED( return_value_used );
    SQLSRV_UNUSED( this_ptr );
    SQLSRV_UNUSED( return_value_ptr );

    if( type != SQLSRV_PHPTYPE_STREAM && type != SQLSRV_PHPTYPE_STRING ) {
        DIE( "Invalid type passed to type_and_encoding" );
    }

    char* encoding_param;
    int encoding_param_len = 0;

    if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "s", &encoding_param, &encoding_param_len ) == FAILURE ) {
        return;
    }

    // set the default encoding values to invalid so that
    // if the encoding isn't validated, it will return the invalid setting.
    sqlsrv_phptype sqlsrv_phptype;
    sqlsrv_phptype.typeinfo.type = type;
    sqlsrv_phptype.typeinfo.encoding = SQLSRV_ENCODING_INVALID;

    if( !verify_encoding( encoding_param, sqlsrv_phptype TSRMLS_CC )) {
        LOG( SEV_ERROR, LOG_STMT, "Invalid encoding for php type." );
    }

    ZVAL_LONG( return_value, sqlsrv_phptype.value );
}


// fetch_common
// the common code shared between fetch_array and fetch_object.  This returns a hash_table into return_value
// containing the fields either indexed by number and/or field name as determined by fetch_type.
void fetch_common( __inout sqlsrv_stmt* stmt, int fetch_type, long fetch_style, long fetch_offset, 
                   __out zval* return_value, const char* _FN_, bool allow_empty_field_names TSRMLS_DC )
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
    // verify the fetch style
    CHECK_SQL_ERROR_EX( fetch_style < SQL_FETCH_NEXT || fetch_style > SQL_FETCH_RELATIVE,
                        stmt, _FN_, SQLSRV_ERROR_INVALID_FETCH_STYLE, RETURN_FALSE );

    // get the numer of columns in the result set
    r = SQLNumResultCols( stmt->ctx.handle, &num_cols );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
    CHECK_SQL_ERROR_EX( num_cols == 0, stmt, _FN_, SQLSRV_ERROR_NO_FIELDS, RETURN_FALSE );

    // get the maximum size for a field name
    r = SQLGetInfo( stmt->conn->ctx.handle, SQL_MAX_COLUMN_NAME_LEN, &field_name_len_max, sizeof( field_name_len_max ), &unused );
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );

    // if the statement is scrollable or has rows and is not scrollable then don't skip calling
    // SQLFetchScroll the first time sqlsrv_fetch_array or sqlsrv_fetch_object is called
    if( stmt->scrollable || !( stmt->has_rows && !stmt->fetch_called )) {
        // move to the next record
        r = SQLFetchScroll( stmt->ctx.handle, static_cast<SQLSMALLINT>( fetch_style ), ( fetch_style == SQL_FETCH_RELATIVE ) ? fetch_offset : fetch_offset + 1 );
        // return a Zend NULL if we're at the end of the result set.
        if( r == SQL_NO_DATA ) {
            // if this is a forward only cursor, mark that we've passed the end so future calls result in an error
            if( !stmt->scrollable ) {
                stmt->past_fetch_end = true;
            }
            RETURN_NULL();
        }
        CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
        CHECK_SQL_WARNING( r, stmt, _FN_, NULL );    
    }

    // make it legal to retrieve fields
    stmt->fetch_called = true;
    stmt->last_field_index = -1;
    stmt->has_rows = true;  // since we made it his far, we must have at least one row

    zval_auto_ptr fields;
    MAKE_STD_ZVAL( fields );
    zr = array_init( fields );
    CHECK_ZEND_ERROR( zr, SQLSRV_ERROR_ZEND_HASH, RETURN_FALSE );

    // if this is the first fetch in a new result set, then get the field names and
    // store them off for successive fetches.
    if( fetch_type & SQLSRV_FETCH_ASSOC && stmt->fetch_fields == NULL ) {

        char* field_name_temp = static_cast<char*>( alloca( field_name_len_max ));
        SQLSMALLINT field_name_len;
        sqlsrv_malloc_auto_ptr<sqlsrv_fetch_field> field_names;
        field_names = static_cast<sqlsrv_fetch_field*>( sqlsrv_malloc( num_cols * sizeof( sqlsrv_fetch_field )));

        for( SQLUSMALLINT f = 0; f < num_cols; ++f ) {
            r = SQLColAttribute( stmt->ctx.handle, f + 1, SQL_DESC_NAME, field_name_temp, field_name_len_max, &field_name_len, &unused );
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETURN_FALSE );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
            field_names[ f ].name = static_cast<char*>( sqlsrv_malloc( field_name_len + 1 ));
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
        sqlsrv_php_type = determine_sqlsrv_php_type( stmt, field_type, field_len, true );
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


// determine if a query returned any rows of data.  It does this by actually fetching the first row
// (though not retrieving the data) and setting the has_rows flag in the stmt the fetch was successful.
// The return value simply states whether or not if an error occurred during the determination.
// (All errors are posted here before returning.)

bool determine_stmt_has_rows( sqlsrv_stmt* stmt, const char* _FN_ TSRMLS_DC )
{
    stmt->has_rows = false;
    SQLRETURN r = SQL_SUCCESS;

    // if the statement is scrollable, our work is easier though less performant.  We simply
    // fetch the first row, and then roll the cursor back to be prior to the first row
    if( stmt->scrollable ) {

        r = SQLFetchScroll( stmt->ctx.handle, SQL_FETCH_FIRST, 0 );
        if( SQL_SUCCEEDED( r )) {

            stmt->has_rows = true;
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
            r = SQLFetchScroll( stmt->ctx.handle, SQL_FETCH_ABSOLUTE, 0 );
            if( r != SQL_NO_DATA ) DIE( "Should have scrolled the cursor to the beginning of the result set." );
            return true;
        }
    }
    // otherwise, we fetch the first row, but record that we did and then sqlsrv_fetch checks this
    // flag and simply skips the first fetch, knowing it was already done.  It records its own 
    // flags to know if it should fetch on subsequent calls.
    else {

        r = SQLFetch( stmt->ctx.handle );
        if( SQL_SUCCEEDED( r )) {

            stmt->has_rows = true;
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
            return true;
        }
    }

    if( r == SQL_NO_DATA ) {
        return true;
    }
    CHECK_SQL_ERROR( r, stmt, _FN_, NULL, return false );
    CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
    return true;    // there are no rows, but no errors occurred, so return true
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
    if( stmt->current_stream == NULL ) {
        if( check_for_next_stream_parameter( stmt, return_value, _FN_ TSRMLS_CC ) == false ) {

            // done with sending parameters, so see if there is a result set or not, and if not, adjust
            // the output string parameters, otherwise see if there is at least one row.
            // return_value is already set (unless changed below)
            if( Z_TYPE_P( return_value ) == IS_BOOL && zend_is_true( return_value )) {

                // check for no columns, which means that there are no rows
                bool result_present;
                r = has_result_columns( stmt, result_present );
                CHECK_SQL_ERROR( r, stmt, _FN_, NULL, RETVAL_FALSE; );
                CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
                if( !result_present ) {
                    // if there are no rows, then adjust the output parameters
                    bool adjusted = adjust_output_lengths_and_encodings( stmt, _FN_ TSRMLS_CC );
                    if( !adjusted ) {
                        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_OUTPUT_PARAM_ENCODING_TRANSLATE TSRMLS_CC, get_last_error_message() );
                        RETVAL_FALSE;
                    }
                }
            }
            stmt->current_stream = NULL;
            stmt->current_stream_read = 0;
            stmt->current_stream_encoding = SQLSRV_ENCODING_CHAR;
            return false;
        }
    }

    // get the stream from the zval we bound
    php_stream* param_stream = NULL;
    php_stream_from_zval_no_verify( param_stream, &stmt->current_stream );
    CHECK_SQL_ERROR_EX( param_stream == NULL, stmt, _FN_, SQLSRV_ERROR_ZEND_STREAM, 
        zval_ptr_dtor( &stmt->current_stream );
        stmt->current_stream = NULL;
        stmt->current_stream_read = 0;
        stmt->current_stream_encoding = SQLSRV_ENCODING_CHAR;
        SQLCancel( stmt->ctx.handle );
        RETVAL_FALSE;
        return false;);

    // if we're at the end, then release our current parameter
    if( php_stream_eof( param_stream )) {
        // if no data was actually sent prior, then send a NULL
        if( stmt->current_stream_read == 0 ) {
            // send an empty string, which is what a 0 length does.
            r = SQLPutData( stmt->ctx.handle, stmt->param_buffer, 0 );
            CHECK_SQL_ERROR( r, stmt, _FN_, NULL, SQLCancel( stmt->ctx.handle ); RETVAL_FALSE; return false; );
            CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
        }
        stmt->current_stream = NULL;
        stmt->current_stream_read = 0;
        stmt->current_stream_encoding = SQLSRV_ENCODING_CHAR;
    }
    // read the data from the stream, send it via SQLPutData and track how much we've sent.  
    else {
        size_t buffer_size = stmt->param_buffer_size - 3;   // -3 to preserve enough space for a cut off UTF-8 character
        size_t read = php_stream_read( param_stream, static_cast<char*>( stmt->param_buffer ), buffer_size );
        stmt->current_stream_read += read;
        if( read > 0 ) {
            // if this is a UTF-8 stream, then we will use the UTF-8 encoding to determine if we're in the middle of a character
            // then read in the appropriate number more bytes and then retest the string.  This way we try at most to convert it
            // twice.
            // If we support other encondings in the future, we'll simply need to read a single byte and then retry the conversion
            // since all other MBCS supported by SQL Server are 2 byte maximum size.
            if( stmt->current_stream_encoding == CP_UTF8 ) {

                // the size of wbuffer is set for the worst case of UTF-8 to UTF-16 conversion, which is a 
                // expansion of 2x the UTF-8 size.
                wchar_t wbuffer[ PHP_STREAM_BUFFER_SIZE + 1 ];
                // buffer_size is the # of wchars.  Since it set to stmt->param_buffer_size / 2, this is accurate
                int wsize = MultiByteToWideChar( stmt->current_stream_encoding, MB_ERR_INVALID_CHARS, reinterpret_cast<char*>( stmt->param_buffer ),
                                                 read, wbuffer, sizeof( wbuffer ) / sizeof( wchar_t ));
                if( wsize == 0 && GetLastError() == ERROR_NO_UNICODE_TRANSLATION ) {

                    // this will calculate how many bytes were cut off from the last UTF-8 character and read that many more
                    // in, then reattempt the conversion.  If it fails the second time, then an error is returned.
                    char* last_char = static_cast<char*>( stmt->param_buffer ) + read - 1;
                    size_t need_to_read = 0;
                    // rewind until we are at the byte that starts the cut off character
                    while( (*last_char & UTF8_MIDBYTE_MASK ) == UTF8_MIDBYTE_TAG ) {
                        --last_char;
                        ++need_to_read;
                    }
                    // determine how many bytes we need to read in based on the number of bytes in the character (# of high bits set)
                    // versus the number of bytes we've already read.
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
                            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INPUT_STREAM_ENCODING_TRANSLATE TSRMLS_CC, get_last_error_message( ERROR_NO_UNICODE_TRANSLATION ) );
                            SQLCancel( stmt->ctx.handle );
                            RETVAL_FALSE;
                            return false;
                            break;
                    }
                    // read the missing bytes
                    size_t new_read = php_stream_read( param_stream, static_cast<char*>( stmt->param_buffer ) + read, need_to_read );
                    // if the bytes couldn't be read, then we return an error
                    if( new_read != need_to_read ) {
                        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INPUT_STREAM_ENCODING_TRANSLATE TSRMLS_CC, get_last_error_message( ERROR_NO_UNICODE_TRANSLATION ) );
                        SQLCancel( stmt->ctx.handle );
                        RETVAL_FALSE;
                        return false;
                    }
                    // try the conversion again with the complete character
                    wsize = MultiByteToWideChar( stmt->current_stream_encoding, MB_ERR_INVALID_CHARS, reinterpret_cast<char*>( stmt->param_buffer ),
                                                 read + new_read, wbuffer, sizeof( wbuffer ) / sizeof( wchar_t ));
                    // something else must be wrong if it failed
                    if( wsize == 0 ) {
                        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INPUT_STREAM_ENCODING_TRANSLATE TSRMLS_CC, get_last_error_message() );
                        SQLCancel( stmt->ctx.handle );
                        RETVAL_FALSE;
                        return false;
                    }
                }
                r = SQLPutData( stmt->ctx.handle, wbuffer, wsize * sizeof( wchar_t ));
                CHECK_SQL_ERROR( r, stmt, _FN_, NULL, SQLCancel( stmt->ctx.handle ); RETVAL_FALSE; return false; );
                CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
            }
            else {
                r = SQLPutData( stmt->ctx.handle, stmt->param_buffer, read );
                CHECK_SQL_ERROR( r, stmt, _FN_, NULL, SQLCancel( stmt->ctx.handle ); RETVAL_FALSE; return false; );
                CHECK_SQL_WARNING( r, stmt, _FN_, NULL );
            }
        }
    }

    RETVAL_TRUE;
    return true;
}

zval* parse_param_array( sqlsrv_stmt const* stmt, const char* _FN_, const zval* param_array, SQLSMALLINT param_num, __out int& direction, __out zend_uchar& php_type, SQLSMALLINT& sql_c_type,
                         __out sqlsrv_sqltype& sql_type, __out SQLUINTEGER& column_size, __out SQLSMALLINT& decimal_digits, __out sqlsrv_phptype& sqlsrv_phptype TSRMLS_DC )
{
    zval** var_or_val;
    zval** temp;
    
    bool php_type_param_was_null = true;
    bool sql_type_param_was_null = true;

    sqlsrv_phptype.typeinfo.type = PHPTYPE_INVALID;
    sqlsrv_phptype.typeinfo.encoding = SQLSRV_ENCODING_INVALID;

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

    // use the SQLSRV_PHPTYPE type given rather than the type built in, since this could be an output parameter.
    // determine the C type to pass to SQLBindParameter
    if( zend_hash_move_forward( Z_ARRVAL_P( param_array ) ) == SUCCESS &&  
        zend_hash_get_current_data( Z_ARRVAL_P( param_array ), (void**) &temp ) == SUCCESS && 
        Z_TYPE_PP( temp ) != IS_NULL ) {
                
        php_type_param_was_null = false;

        int encoding;
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
        SQLSRV_STATIC_ASSERT( MAX_SQLSRV_PHPTYPE == ( sizeof( sqlsrv_to_zend_phptype ) / sizeof( zend_uchar )) );
        php_type = sqlsrv_to_zend_phptype[ sqlsrv_phptype.typeinfo.type - 1 ];
        encoding = sqlsrv_phptype.typeinfo.encoding;
        // if the call has a SQLSRV_PHPTYPE_STRING/STREAM('default'), then the stream is in the encoding established 
        // by the connection attribute CharacterSet
        if( encoding == SQLSRV_ENCODING_DEFAULT ) {
            encoding = stmt->conn->default_encoding;
        }
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
        sqlsrv_phptype.typeinfo.type = zend_to_sqlsrv_phptype[ php_type ];
        sqlsrv_phptype.typeinfo.encoding = stmt->conn->default_encoding;
        sql_c_type = determine_c_type( php_type, stmt->conn->default_encoding );
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
        if( determine_column_size_or_precision( stmt, sql_type, &column_size, &decimal_digits ) == false ) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PRECISION TSRMLS_CC, param_num );
            SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
            return NULL;
        }
    }
    // else use the type of the variable to infer the sql type, using a default character encoding
    // (the default encoding really should be something in the INI)
    else {
        sql_type_param_was_null = true;
        // TODO UTF-8: When the default encoding may be set, change this to use the default encoding rather than
        // binary_or_char_encoding of the C type
        sql_type = determine_sql_type( *var_or_val, sqlsrv_phptype.typeinfo.encoding, stmt->conn->server_version );
        if( sql_type.typeinfo.type == SQLTYPE_INVALID ) {
            handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_SQLTYPE TSRMLS_CC, param_num );
            SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
            return NULL;
        }
        if( determine_column_size_or_precision( stmt, sql_type, &column_size, &decimal_digits ) == false ) {
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
                                                 sql_c_type, column_size, decimal_digits, sqlsrv_phptype TSRMLS_CC );
        if( !success ) {
            return NULL;
        }
        return *var_or_val;
    }

    // if the user for some reason provides an output parameter with a null phptype and a specified
    // sql server type, infer the php type from the sql server type.
    if( direction == SQL_PARAM_OUTPUT && php_type_param_was_null && !sql_type_param_was_null ) {

        int encoding;
        sqlsrv_phptype = determine_sqlsrv_php_type( stmt, sql_type.typeinfo.type, column_size, true );
        // we DIE here since everything should have been validated already and to return the user an error
        // for our own logic error would be confusing/misleading.
        if( sqlsrv_phptype.typeinfo.type == PHPTYPE_INVALID ) DIE( "An invalid php type was returned with (supposed) validated sql type and column_sze" );

        SQLSRV_STATIC_ASSERT( MAX_SQLSRV_PHPTYPE == ( sizeof( sqlsrv_to_zend_phptype ) / sizeof( zend_uchar )) );
        php_type = sqlsrv_to_zend_phptype[ sqlsrv_phptype.typeinfo.type - 1 ];
        encoding = sqlsrv_phptype.typeinfo.encoding;
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
                ZVAL_STRINGL( *var_or_val, static_cast<char*>( sqlsrv_malloc( column_size )), column_size, 0 /* don't dup the string */ );
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
                               __out sqlsrv_sqltype& sql_type, __out SQLSMALLINT& sql_c_type, __out SQLUINTEGER& column_size, __out SQLSMALLINT& decimal_digits,
                               __out sqlsrv_phptype& sqlsrv_phptype TSRMLS_DC )
{
    direction = SQL_PARAM_INPUT;
    php_type = Z_TYPE_P( param_z );
    sql_type = determine_sql_type( param_z, stmt->conn->default_encoding, stmt->conn->server_version );

    sqlsrv_phptype.typeinfo.type = php_type;
    sqlsrv_phptype.typeinfo.encoding = stmt->conn->default_encoding;

    if( sql_type.typeinfo.type == SQLTYPE_INVALID ) {
        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_SQLTYPE TSRMLS_CC, param_num );
        SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
        return false;
    }
    sql_c_type = determine_c_type( php_type, stmt->conn->default_encoding );
    if( sql_c_type == SQLTYPE_INVALID ) {
        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE TSRMLS_CC, param_num );
        SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
        return false;
    }
    if( determine_column_size_or_precision( stmt, sql_type, &column_size, &decimal_digits ) == false ) {
        handle_error( &stmt->ctx, LOG_STMT, _FN_, SQLSRV_ERROR_INVALID_PARAMETER_PRECISION TSRMLS_CC, param_num );
        SQLFreeStmt( stmt->ctx.handle, SQL_RESET_PARAMS );
        return false;
    }

    return true;
}

}  // namespace

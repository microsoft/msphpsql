//---------------------------------------------------------------------------------------------------------------------------------
// File: init.cpp
// Contents: initialization routines for the extension
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

#include "php_sqlsrv.h"
ZEND_GET_MODULE(g_sqlsrv)

extern "C" {

ZEND_DECLARE_MODULE_GLOBALS(sqlsrv);

}

// module global variables (initialized in minit and freed in mshutdown)
HashTable* g_ss_errors_ht = NULL;
// special list of warnings to ignore even if warnings are treated as errors
HashTable* g_ss_warnings_to_ignore_ht = NULL;
// encodings we understand
HashTable* g_ss_encodings_ht = NULL;

// henv context for creating connections
sqlsrv_context* g_henv_cp;
sqlsrv_context* g_henv_ncp;

namespace {

// current subsytem.  defined for the CHECK_SQL_{ERROR|WARNING} macros
unsigned int current_log_subsystem = LOG_INIT;
}

// argument info structures for functions, arranged alphabetically.
// see zend_API.h in the PHP sources for more information about these macros
ZEND_BEGIN_ARG_INFO_EX( sqlsrv_begin_transaction_arginfo, 0, 0, 1 )
    ZEND_ARG_INFO( 0, conn )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO( sqlsrv_cancel_arginfo, 0 )
    ZEND_ARG_INFO( 0, stmt )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_close_arginfo, 0, 0, 1 )
    ZEND_ARG_INFO( 0, conn )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_client_info_arginfo, 0, 0, 1 )
    ZEND_ARG_INFO( 0, conn )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_commit_arginfo, 0, 0, 1 )
    ZEND_ARG_INFO( 0, conn )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_configure_arginfo, 0, 0, 2 )
    ZEND_ARG_INFO( 0, setting )
    ZEND_ARG_INFO( 0, value )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_connect_arginfo, 0, 0, 1 )
    ZEND_ARG_INFO( 0, server_name )
    ZEND_ARG_ARRAY_INFO( 0, connection_info, 0 )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_errors_arginfo, 0, 1, 0 )
    ZEND_ARG_INFO( 0, errors_and_or_warnings )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_execute_arginfo, 0, 0, 1 )
    ZEND_ARG_INFO( 0, stmt )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_fetch_arginfo, 0, 0, 1 )
    ZEND_ARG_INFO( 0, stmt )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_fetch_array_arginfo, 0, 1, 1 )
    ZEND_ARG_INFO( 0, stmt )
    ZEND_ARG_INFO( 0, fetch_type )
    ZEND_ARG_INFO( 0, row )
    ZEND_ARG_INFO( 0, offset )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_fetch_object_arginfo, 0, 1, 1 )
    ZEND_ARG_INFO( 0, stmt )
    ZEND_ARG_INFO( 0, class_name )
    ZEND_ARG_INFO( 0, ctor_params )
    ZEND_ARG_INFO( 0, row )
    ZEND_ARG_INFO( 0, offset )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_field_metadata_arginfo, 0, 1, 1 )
    ZEND_ARG_INFO( 0, stmt )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO( sqlsrv_free_stmt_arginfo, 0 )
    ZEND_ARG_INFO( 0, stmt )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_get_config_arginfo, 0, 0, 1 )
    ZEND_ARG_INFO( 0, setting )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_get_field_arginfo, 0, 1, 2 )
    ZEND_ARG_INFO( 0, stmt )
    ZEND_ARG_INFO( 0, field_index )
    ZEND_ARG_INFO( 0, get_as_type )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO( sqlsrv_has_rows_arginfo, 0 )
    ZEND_ARG_INFO( 0, stmt )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO( sqlsrv_next_result_arginfo, 0 )
    ZEND_ARG_INFO( 0, stmt )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO( sqlsrv_num_fields_arginfo, 0 )
    ZEND_ARG_INFO( 0, stmt )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO( sqlsrv_num_rows_arginfo, 0 )
    ZEND_ARG_INFO( 0, stmt )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_prepare_arginfo, 0, 1, 2 )
    ZEND_ARG_INFO( 0, conn )
    ZEND_ARG_INFO( 0, tsql )
    ZEND_ARG_INFO( 0, params )
    ZEND_ARG_INFO( 0, options )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_query_arginfo, 0, 1, 2 )
    ZEND_ARG_INFO( 0, conn )
    ZEND_ARG_INFO( 0, tsql )
    ZEND_ARG_INFO( 0, params )
    ZEND_ARG_INFO( 0, options )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX( sqlsrv_rollback_arginfo, 0, 0, 1 )
    ZEND_ARG_INFO( 0, conn )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO( sqlsrv_rows_affected_arginfo, 0 )
    ZEND_ARG_INFO( 0, stmt )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO( sqlsrv_send_stream_data_arginfo, 0 )
    ZEND_ARG_INFO( 0, stmt )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO( sqlsrv_server_info_arginfo, 0 )
    ZEND_ARG_INFO( 0, stmt )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO( sqlsrv_sqltype_size_arginfo, 0 )
    ZEND_ARG_INFO( 0, size )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO( sqlsrv_sqltype_precision_scale_arginfo, 0 )
    ZEND_ARG_INFO( 0, precision )
    ZEND_ARG_INFO( 0, scale )
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO( sqlsrv_phptype_encoding_arginfo, 0 )
    ZEND_ARG_INFO( 0, encoding )
ZEND_END_ARG_INFO()

// function table with associated arginfo structures
zend_function_entry sqlsrv_functions[] = {
    PHP_FE( sqlsrv_connect, sqlsrv_connect_arginfo )
    PHP_FE( sqlsrv_close, sqlsrv_close_arginfo )
    PHP_FE( sqlsrv_commit, sqlsrv_commit_arginfo )
    PHP_FE( sqlsrv_begin_transaction, sqlsrv_begin_transaction_arginfo )
    PHP_FE( sqlsrv_rollback, sqlsrv_rollback_arginfo )
    PHP_FE( sqlsrv_errors, sqlsrv_errors_arginfo )
    PHP_FE( sqlsrv_configure, sqlsrv_configure_arginfo )
    PHP_FE( sqlsrv_get_config, sqlsrv_get_config_arginfo )
    PHP_FE( sqlsrv_prepare, sqlsrv_prepare_arginfo )
    PHP_FE( sqlsrv_execute, sqlsrv_execute_arginfo )
    PHP_FE( sqlsrv_query, sqlsrv_query_arginfo )
    PHP_FE( sqlsrv_fetch, sqlsrv_fetch_arginfo )
    PHP_FE( sqlsrv_get_field, sqlsrv_get_field_arginfo )
    PHP_FE( sqlsrv_fetch_array, sqlsrv_fetch_array_arginfo )
    PHP_FE( sqlsrv_fetch_object, sqlsrv_fetch_object_arginfo )
    PHP_FE( sqlsrv_has_rows, sqlsrv_has_rows_arginfo )
    PHP_FE( sqlsrv_num_fields, sqlsrv_num_fields_arginfo )
    PHP_FE( sqlsrv_next_result, sqlsrv_next_result_arginfo )
    PHP_FE( sqlsrv_num_rows, sqlsrv_num_rows_arginfo )
    PHP_FE( sqlsrv_rows_affected, sqlsrv_rows_affected_arginfo )
    PHP_FE( SQLSRV_PHPTYPE_STREAM, sqlsrv_phptype_encoding_arginfo )
    PHP_FE( SQLSRV_PHPTYPE_STRING, sqlsrv_phptype_encoding_arginfo )
    PHP_FE( sqlsrv_client_info, sqlsrv_client_info_arginfo )
    PHP_FE( sqlsrv_server_info, sqlsrv_server_info_arginfo )
    PHP_FE( sqlsrv_cancel, sqlsrv_cancel_arginfo )
    PHP_FE( sqlsrv_free_stmt, sqlsrv_close_arginfo )
    PHP_FE( sqlsrv_field_metadata, sqlsrv_field_metadata_arginfo )
    PHP_FE( sqlsrv_send_stream_data, sqlsrv_send_stream_data_arginfo ) 
    PHP_FE( SQLSRV_SQLTYPE_BINARY, sqlsrv_sqltype_size_arginfo )
    PHP_FE( SQLSRV_SQLTYPE_CHAR, sqlsrv_sqltype_size_arginfo )
    PHP_FE( SQLSRV_SQLTYPE_DECIMAL, sqlsrv_sqltype_precision_scale_arginfo )
    PHP_FE( SQLSRV_SQLTYPE_NCHAR, sqlsrv_sqltype_size_arginfo )
    PHP_FE( SQLSRV_SQLTYPE_NUMERIC, sqlsrv_sqltype_precision_scale_arginfo )
    PHP_FE( SQLSRV_SQLTYPE_NVARCHAR, sqlsrv_sqltype_size_arginfo )
    PHP_FE( SQLSRV_SQLTYPE_VARBINARY, sqlsrv_sqltype_size_arginfo )
    PHP_FE( SQLSRV_SQLTYPE_VARCHAR, sqlsrv_sqltype_size_arginfo )

    {NULL, NULL, NULL}   // end of the table
};

// the structure returned to Zend that exposes the extension to the Zend engine.
// this structure is defined in zend_modules.h in the PHP sources

zend_module_entry g_sqlsrv_module_entry = 
{
    STANDARD_MODULE_HEADER,
    "sqlsrv", 
    sqlsrv_functions,                   // exported function table
    // initialization and shutdown functions
    PHP_MINIT(sqlsrv),
    PHP_MSHUTDOWN(sqlsrv), 
    PHP_RINIT(sqlsrv), 
    PHP_RSHUTDOWN(sqlsrv), 
    PHP_MINFO(sqlsrv),
    // version of the extension.  Matches the version resource of the extension dll
    VER_FILEVERSION_STR,
    PHP_MODULE_GLOBALS(sqlsrv),
    NULL,           
    NULL,
    NULL,
    STANDARD_MODULE_PROPERTIES_EX
};

// Module initialization
// This function is called once per execution of the Zend engine
// We use it to:
// 1) Register our constants.  See MSDN or the function below for the exact constants
//    we register.
// 2) Register our resource types (connection, statement, and stream types)
// 3) Allocate the environment handles for ODBC connections (1 for non pooled
// connections and 1 for pooled connections)
// 4) Register our INI entries.  See MSDN or php_sqlsrv.h for our supported INI entries

PHP_MINIT_FUNCTION(sqlsrv)
{
    SQLSRV_UNUSED( type );
    
    core_sqlsrv_register_logger( ss_sqlsrv_log );

    // our global variables are initialized in the RINIT function
#if defined(ZTS)
    if( ts_allocate_id( &sqlsrv_globals_id,
                    sizeof( zend_sqlsrv_globals ),
                    (ts_allocate_ctor) NULL,
                    (ts_allocate_dtor) NULL ) == 0 )
        return FAILURE;
#endif

    SQLSRV_STATIC_ASSERT( sizeof( sqlsrv_sqltype ) == sizeof( long ));
    SQLSRV_STATIC_ASSERT( sizeof( sqlsrv_phptype ) == sizeof( long ));

    REGISTER_INI_ENTRIES();

    LOG_FUNCTION( "PHP_MINIT_FUNCTION for php_sqlsrv" );

    REGISTER_LONG_CONSTANT( "SQLSRV_ERR_ERRORS",   SQLSRV_ERR_ERRORS, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_ERR_WARNINGS", SQLSRV_ERR_WARNINGS, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_ERR_ALL", SQLSRV_ERR_ALL, CONST_PERSISTENT | CONST_CS );

    REGISTER_LONG_CONSTANT( "SQLSRV_LOG_SYSTEM_OFF", 0, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_LOG_SYSTEM_INIT", LOG_INIT, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_LOG_SYSTEM_CONN", LOG_CONN, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_LOG_SYSTEM_STMT", LOG_STMT, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_LOG_SYSTEM_UTIL", LOG_UTIL, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_LOG_SYSTEM_ALL", -1, CONST_PERSISTENT | CONST_CS ); // -1 so that all the bits are set

    REGISTER_LONG_CONSTANT( "SQLSRV_LOG_SEVERITY_ERROR", SEV_ERROR, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_LOG_SEVERITY_WARNING", SEV_WARNING, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_LOG_SEVERITY_NOTICE", SEV_NOTICE, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_LOG_SEVERITY_ALL", -1, CONST_PERSISTENT | CONST_CS ); // -1 so that all the bits are set

    // register connection resource
    ss_sqlsrv_conn::descriptor = zend_register_list_destructors_ex( sqlsrv_conn_dtor, NULL, "SQL Server Connection", 
                                                                    module_number );

    if( ss_sqlsrv_conn::descriptor == FAILURE ) {
        LOG( SEV_ERROR, "%1!s!: connection resource registration failed", _FN_ );
        return FAILURE;
    }
    
    // register statement resources
    ss_sqlsrv_stmt::descriptor = zend_register_list_destructors_ex( sqlsrv_stmt_dtor, NULL, "SQL Server Statement", module_number );

    if( ss_sqlsrv_stmt::descriptor == FAILURE ) {
        LOG( SEV_ERROR, "%1!s!: statement resource regisration failed", _FN_ );
        return FAILURE;
    }
    
    sqlsrv_sqltype constant_type;

    REGISTER_LONG_CONSTANT( "SQLSRV_FETCH_NUMERIC", SQLSRV_FETCH_NUMERIC, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_FETCH_ASSOC",   SQLSRV_FETCH_ASSOC, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_FETCH_BOTH",    SQLSRV_FETCH_BOTH, CONST_PERSISTENT | CONST_CS );
    
    REGISTER_LONG_CONSTANT( "SQLSRV_PHPTYPE_NULL",     SQLSRV_PHPTYPE_NULL, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_PHPTYPE_INT",      SQLSRV_PHPTYPE_INT, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_PHPTYPE_FLOAT",    SQLSRV_PHPTYPE_FLOAT, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_PHPTYPE_DATETIME", SQLSRV_PHPTYPE_DATETIME, CONST_PERSISTENT | CONST_CS );

    REGISTER_STRING_CONSTANT( "SQLSRV_ENC_BINARY", "binary", CONST_PERSISTENT | CONST_CS );
    REGISTER_STRING_CONSTANT( "SQLSRV_ENC_CHAR",   "char", CONST_PERSISTENT | CONST_CS );
    
    REGISTER_LONG_CONSTANT( "SQLSRV_NULLABLE_NO",      0, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_NULLABLE_YES",     1, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_NULLABLE_UNKNOWN", 2, CONST_PERSISTENT | CONST_CS );

    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_BIGINT",           SQL_BIGINT, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_BIT",              SQL_BIT, CONST_PERSISTENT | CONST_CS );
    constant_type.typeinfo.type = SQL_TYPE_TIMESTAMP;
    constant_type.typeinfo.size = 23;
    constant_type.typeinfo.scale = 3;
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_DATETIME",         constant_type.value, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_FLOAT",            SQL_FLOAT, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_IMAGE",            SQL_LONGVARBINARY, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_INT",              SQL_INTEGER, CONST_PERSISTENT | CONST_CS );
    constant_type.typeinfo.type = SQL_DECIMAL;
    constant_type.typeinfo.size = 19;
    constant_type.typeinfo.scale = 4;
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_MONEY",            constant_type.value, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_NTEXT",            SQL_WLONGVARCHAR, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_TEXT",             SQL_LONGVARCHAR, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_REAL",             SQL_REAL, CONST_PERSISTENT | CONST_CS );
    constant_type.typeinfo.type = SQL_TYPE_TIMESTAMP;
    constant_type.typeinfo.size = 16;
    constant_type.typeinfo.scale = 0;
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_SMALLDATETIME",    constant_type.value, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_SMALLINT",         SQL_SMALLINT, CONST_PERSISTENT | CONST_CS );
    constant_type.typeinfo.type = SQL_DECIMAL;
    constant_type.typeinfo.size = 10;
    constant_type.typeinfo.scale = 4;
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_SMALLMONEY",       constant_type.value, CONST_PERSISTENT | CONST_CS );
    constant_type.typeinfo.type = SQL_BINARY;
    constant_type.typeinfo.size = 8;
    constant_type.typeinfo.scale = 0;
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_TIMESTAMP",        constant_type.value, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_TINYINT",          SQL_TINYINT, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_UDT",              SQL_SS_UDT, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_UNIQUEIDENTIFIER", SQL_GUID, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_XML",              SQL_SS_XML, CONST_PERSISTENT | CONST_CS );
    constant_type.typeinfo.type = SQL_TYPE_DATE;
    constant_type.typeinfo.size = 10;
    constant_type.typeinfo.scale = 0;
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_DATE",             constant_type.value, CONST_PERSISTENT | CONST_CS );
    constant_type.typeinfo.type = SQL_SS_TIME2;
    constant_type.typeinfo.size = 16;
    constant_type.typeinfo.scale = 7;
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_TIME",             constant_type.value, CONST_PERSISTENT | CONST_CS );
    constant_type.typeinfo.type = SQL_SS_TIMESTAMPOFFSET;
    constant_type.typeinfo.size = 34;
    constant_type.typeinfo.scale = 7;
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_DATETIMEOFFSET",   constant_type.value, CONST_PERSISTENT | CONST_CS );
    constant_type.typeinfo.type = SQL_TYPE_TIMESTAMP;
    constant_type.typeinfo.size = 27;
    constant_type.typeinfo.scale = 7;
    REGISTER_LONG_CONSTANT( "SQLSRV_SQLTYPE_DATETIME2",        constant_type.value, CONST_PERSISTENT | CONST_CS );

    REGISTER_LONG_CONSTANT( "SQLSRV_PARAM_IN",        SQL_PARAM_INPUT, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_PARAM_OUT",       SQL_PARAM_OUTPUT, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_PARAM_INOUT",     SQL_PARAM_INPUT_OUTPUT, CONST_PERSISTENT | CONST_CS );

    REGISTER_LONG_CONSTANT( "SQLSRV_TXN_READ_UNCOMMITTED", SQL_TXN_READ_UNCOMMITTED, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_TXN_READ_COMMITTED",   SQL_TXN_READ_COMMITTED, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_TXN_REPEATABLE_READ",  SQL_TXN_REPEATABLE_READ, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_TXN_SERIALIZABLE",     SQL_TXN_SERIALIZABLE, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_TXN_SNAPSHOT",         SQL_TXN_SS_SNAPSHOT, CONST_PERSISTENT | CONST_CS );

    REGISTER_LONG_CONSTANT( "SQLSRV_SCROLL_NEXT",     SQL_FETCH_NEXT, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SCROLL_PRIOR",    SQL_FETCH_PRIOR, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SCROLL_FIRST",    SQL_FETCH_FIRST, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SCROLL_LAST",     SQL_FETCH_LAST, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SCROLL_ABSOLUTE", SQL_FETCH_ABSOLUTE, CONST_PERSISTENT | CONST_CS );
    REGISTER_LONG_CONSTANT( "SQLSRV_SCROLL_RELATIVE", SQL_FETCH_RELATIVE, CONST_PERSISTENT | CONST_CS );

    REGISTER_STRING_CONSTANT( "SQLSRV_CURSOR_FORWARD", "forward", CONST_PERSISTENT | CONST_CS );
    REGISTER_STRING_CONSTANT( "SQLSRV_CURSOR_STATIC",  "static", CONST_PERSISTENT | CONST_CS );
    REGISTER_STRING_CONSTANT( "SQLSRV_CURSOR_DYNAMIC", "dynamic", CONST_PERSISTENT | CONST_CS );
    REGISTER_STRING_CONSTANT( "SQLSRV_CURSOR_KEYSET",  "keyset", CONST_PERSISTENT | CONST_CS );
    REGISTER_STRING_CONSTANT( "SQLSRV_CURSOR_CLIENT_BUFFERED",  "buffered", CONST_PERSISTENT | CONST_CS );

    try {

        // initialize list of warnings to ignore
        g_ss_warnings_to_ignore_ht = reinterpret_cast<HashTable*>( pemalloc( sizeof( HashTable ), 1 ));
        int zr = ::zend_hash_init( g_ss_warnings_to_ignore_ht, 6, NULL, NULL, 1 );
        if( zr == FAILURE ) {
            throw ss::SSException();
        }

        sqlsrv_error_const error_to_ignore;

        // changed database warning
        error_to_ignore.sqlstate = (SQLCHAR*)"01000";
        error_to_ignore.native_message = NULL;
        error_to_ignore.native_code = 5701;
        error_to_ignore.format = false;
        zr = zend_hash_next_index_insert( g_ss_warnings_to_ignore_ht, &error_to_ignore, sizeof( sqlsrv_error_const ), NULL );
        if( zr == FAILURE ) {
            throw ss::SSException();     
        }

        // changed language warning
        error_to_ignore.sqlstate = (SQLCHAR*)"01000";
        error_to_ignore.native_message = NULL;
        error_to_ignore.native_code = 5703;
        error_to_ignore.format = false;
        zr = zend_hash_next_index_insert( g_ss_warnings_to_ignore_ht, &error_to_ignore, sizeof( sqlsrv_error_const ), NULL );
        if( zr == FAILURE ) {
           throw ss::SSException();     
        }

        // option value changed
        error_to_ignore.sqlstate = (SQLCHAR*)"01S02";
        error_to_ignore.native_message = NULL;
        error_to_ignore.native_code = -1;
        error_to_ignore.format = false;
        zr = zend_hash_next_index_insert( g_ss_warnings_to_ignore_ht, &error_to_ignore, sizeof( sqlsrv_error_const ), NULL );
        if( zr == FAILURE ) {
            throw ss::SSException();     
        }

        // cursor operation conflict
        error_to_ignore.sqlstate = (SQLCHAR*)"01001";
        error_to_ignore.native_message = NULL;
        error_to_ignore.native_code = -1;
        error_to_ignore.format = false;
        zr = zend_hash_next_index_insert( g_ss_warnings_to_ignore_ht, &error_to_ignore, sizeof( sqlsrv_error_const ), NULL );
        if( zr == FAILURE ) {
            throw ss::SSException();     
        }

        // null value eliminated in set function
        error_to_ignore.sqlstate = (SQLCHAR*)"01003";
        error_to_ignore.native_message = NULL;
        error_to_ignore.native_code = -1;
        error_to_ignore.format = false;
        zr = zend_hash_next_index_insert( g_ss_warnings_to_ignore_ht, &error_to_ignore, sizeof( sqlsrv_error_const ), NULL );
        if( zr == FAILURE ) {
            throw ss::SSException();     
        }
        
        // SQL Azure warning: This session has been assigned a tracing id of ..
        error_to_ignore.sqlstate = (SQLCHAR*)"01000";
        error_to_ignore.native_message = NULL;
        error_to_ignore.native_code = 40608;
        error_to_ignore.format = false;
        zr = zend_hash_next_index_insert( g_ss_warnings_to_ignore_ht, &error_to_ignore, sizeof( sqlsrv_error_const ), NULL );
        if( zr == FAILURE ) {
           throw ss::SSException();     
        }
    }
    catch( ss::SSException& ) {

        LOG( SEV_ERROR, "PHP_MINIT: warnings hash table failure" );
        return FAILURE;
    }

     try {
    
        // supported encodings
        g_ss_encodings_ht = reinterpret_cast<HashTable*>( pemalloc( sizeof( HashTable ), 1 ));
        int zr = zend_hash_init( g_ss_encodings_ht, 3, NULL /*use standard hash function*/, NULL /*no resource destructor*/, 1 /*persistent*/ );
        if( zr == FAILURE ) {
            throw ss::SSException();     
        }

        sqlsrv_encoding sql_enc_char( "char", SQLSRV_ENCODING_CHAR );
        zr = zend_hash_next_index_insert( g_ss_encodings_ht, &sql_enc_char, sizeof( sqlsrv_encoding ), NULL /*no pointer to the new value necessasry*/ );
        if( zr == FAILURE ) {
            throw ss::SSException();     
        }
        
        sqlsrv_encoding sql_enc_bin( "binary", SQLSRV_ENCODING_BINARY, true );
        zr = zend_hash_next_index_insert( g_ss_encodings_ht, &sql_enc_bin, sizeof( sqlsrv_encoding ), NULL  /*no pointer to the new value necessasry*/ );
        if( zr == FAILURE ) {
            throw ss::SSException();     
        }

        sqlsrv_encoding sql_enc_utf8( "utf-8", CP_UTF8 );
        zr = zend_hash_next_index_insert( g_ss_encodings_ht, &sql_enc_utf8, sizeof( sqlsrv_encoding ), NULL  /*no pointer to the new value necessasry*/ );
        if( zr == FAILURE ) {
            throw ss::SSException();     
        }
    }
    catch( ss::SSException& ) {
        
        LOG( SEV_ERROR, "PHP_RINIT: encodings hash table failure" );
        return FAILURE;
    }

    // initialize list of sqlsrv errors
    g_ss_errors_ht = reinterpret_cast<HashTable*>( pemalloc( sizeof( HashTable ), 1 ));
    int zr = ::zend_hash_init( g_ss_errors_ht, 50, NULL, NULL, 1 );
    if( zr == FAILURE ) {
        LOG( SEV_ERROR, "%1!s!: Failed to initialize the sqlsrv errors hashtable.", _FN_ );
        return FAILURE;
    }

    for( int i = 0; SS_ERRORS[ i ].error_code != -1; ++i ) {
        
        zr = ::zend_hash_index_update( g_ss_errors_ht, SS_ERRORS[ i ].error_code, 
                                       &( SS_ERRORS[ i ].sqlsrv_error ), sizeof( SS_ERRORS[ i ].sqlsrv_error ), NULL );
        if( zr == FAILURE ) {
                
            LOG( SEV_ERROR, "%1!s!: Failed to insert data into sqlsrv errors hashtable.", _FN_ );
            return FAILURE;
        }
    }

    if( php_register_url_stream_wrapper( SQLSRV_STREAM_WRAPPER, &g_sqlsrv_stream_wrapper TSRMLS_CC ) == FAILURE ) {
        LOG( SEV_ERROR, "%1!s!: stream registration failed", _FN_ );
        return FAILURE;
    }

    try {

        // retrieve the handles for the environments
        core_sqlsrv_minit( &g_henv_cp, &g_henv_ncp, ss_error_handler, "PHP_MINIT_FUNCTION for sqlsrv" TSRMLS_CC );
    }

    catch( core::CoreException& ) {
        return FAILURE;
    }

    catch( ... ) {

        LOG( SEV_ERROR, "PHP_RINIT: Unknown exception caught." );
        return FAILURE;
    }

    return SUCCESS;
}


// Module shutdown function
// Free the environment handles allocated in MINIT and unregister our stream wrapper.
// Resource types and constants are automatically released since we don't flag them as
// persistent when they are registered.

PHP_MSHUTDOWN_FUNCTION(sqlsrv)
{
    SQLSRV_UNUSED( type );
    
    UNREGISTER_INI_ENTRIES();

    // clean up the list of sqlsrv errors
    zend_hash_destroy( g_ss_errors_ht );
    pefree( g_ss_errors_ht, 1 /*persistent*/ );

    zend_hash_destroy( g_ss_warnings_to_ignore_ht );
    pefree( g_ss_warnings_to_ignore_ht, 1 );
    
    zend_hash_destroy( g_ss_encodings_ht );
    pefree( g_ss_encodings_ht, 1 );

    core_sqlsrv_mshutdown( *g_henv_cp, *g_henv_ncp );

    if( php_unregister_url_stream_wrapper( SQLSRV_STREAM_WRAPPER TSRMLS_CC ) == FAILURE ) {
        return FAILURE;
    }

    return SUCCESS;
}


// Request initialization function
// This function is called once per PHP script execution
// Initialize request globals used in the request, including those that correspond to INI entries.
// Also, we allocate a list of warnings "to ignore", meaning that they are warnings that do not
// trigger errors when WarningsReturnAsErrors is true.  If you have warnings that you want ignored
// (such as return values from stored procedures), add them to this collection and they won't be
// returned as errors.  Or you could just set WarningsReturnAsErrors to false.

PHP_RINIT_FUNCTION(sqlsrv)
{
    SQLSRV_UNUSED( module_number );
    SQLSRV_UNUSED( type );
   
    SQLSRV_G( warnings_return_as_errors ) = true;
    ALLOC_INIT_ZVAL( SQLSRV_G( errors ));
    Z_SET_ISREF_P( SQLSRV_G( errors ));
    ALLOC_INIT_ZVAL( SQLSRV_G( warnings ));
    Z_SET_ISREF_P( SQLSRV_G( warnings ));
   
    LOG_FUNCTION( "PHP_RINIT for php_sqlsrv" );

    // read INI settings
    SQLSRV_G( warnings_return_as_errors ) = INI_BOOL( INI_PREFIX INI_WARNINGS_RETURN_AS_ERRORS );
    SQLSRV_G( log_severity ) = INI_INT( INI_PREFIX INI_LOG_SEVERITY );
    SQLSRV_G( log_subsystems ) = INI_INT( INI_PREFIX INI_LOG_SUBSYSTEMS );
    SQLSRV_G( buffered_query_limit ) = INI_INT( INI_PREFIX INI_BUFFERED_QUERY_LIMIT );

    LOG( SEV_NOTICE, INI_PREFIX INI_WARNINGS_RETURN_AS_ERRORS " = %1!s!", SQLSRV_G( warnings_return_as_errors ) ? "On" : "Off");
    LOG( SEV_NOTICE, INI_PREFIX INI_LOG_SEVERITY " = %1!d!", SQLSRV_G( log_severity ));
    LOG( SEV_NOTICE, INI_PREFIX INI_LOG_SUBSYSTEMS " = %1!d!", SQLSRV_G( log_subsystems ));
    LOG( SEV_NOTICE, INI_PREFIX INI_BUFFERED_QUERY_LIMIT " = %1!d!", SQLSRV_G( buffered_query_limit ));

    // verify memory at the end of the request (in debug mode only)
    full_mem_check(MEMCHECK_SILENT);
    return SUCCESS;
}


// Request shutdown
// Called at the end of a script's execution
// Simply releases the variables allocated during request initialization.

PHP_RSHUTDOWN_FUNCTION(sqlsrv)
{
    SQLSRV_UNUSED( module_number );
    SQLSRV_UNUSED( type );

    LOG_FUNCTION( "PHP_RSHUTDOWN for php_sqlsrv" );
    reset_errors( TSRMLS_C );

    zval_ptr_dtor( &SQLSRV_G( errors ));
    zval_ptr_dtor( &SQLSRV_G( warnings ));

    // verify memory at the end of the request (in debug mode only)	
    full_mem_check(MEMCHECK_SILENT);

    return SUCCESS;
}

// Called for php_info();  Displays the INI settings registered and their current values
PHP_MINFO_FUNCTION(sqlsrv)
{
#if defined(ZTS)
    SQLSRV_UNUSED( tsrm_ls );
#endif

    php_info_print_table_start();
    php_info_print_table_header(2, "sqlsrv support", "enabled");
    php_info_print_table_end();
    DISPLAY_INI_ENTRIES();
}

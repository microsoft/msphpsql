//---------------------------------------------------------------------------------------------------------------------------------
// File: pdo_init.cpp
//
// Contents: initialization routines for PDO_SQLSRV
//
// Microsoft Drivers 5.9 for PHP for SQL Server
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

extern "C" {
  #include "php_pdo_sqlsrv.h"
}

#include "php_pdo_sqlsrv_int.h"

#ifdef COMPILE_DL_PDO_SQLSRV
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE();
#endif
ZEND_GET_MODULE(g_pdo_sqlsrv)
#endif

extern "C" {

ZEND_DECLARE_MODULE_GLOBALS(pdo_sqlsrv);

}

// module global variables (initialized in minit and freed in mshutdown)
HashTable* g_pdo_errors_ht = NULL;

// henv context for creating connections
sqlsrv_context* g_pdo_henv_cp;
sqlsrv_context* g_pdo_henv_ncp;

namespace {

pdo_driver_t pdo_sqlsrv_driver = {

    PDO_DRIVER_HEADER(sqlsrv),
    pdo_sqlsrv_db_handle_factory
};

// functions to register SQLSRV constants with the PDO class
// (It's in all CAPS so it looks like the Zend macros that do similar work)
void REGISTER_PDO_SQLSRV_CLASS_CONST_LONG( _In_z_ char const* name, _In_ long value );
void REGISTER_PDO_SQLSRV_CLASS_CONST_STRING( _In_z_ char const* name, _In_z_ char const* value );

struct sqlsrv_attr_pdo_constant {
    const char *name;
    int value;
};

// forward decl for table
extern sqlsrv_attr_pdo_constant pdo_attr_constants[];

}

static zend_module_dep pdo_sqlsrv_depends[] = {
    ZEND_MOD_REQUIRED("pdo")
    {NULL, NULL, NULL}
};


// argument info structures for functions, arranged alphabetically.
// see zend_API.h in the PHP sources for more information about these macros

// function table with associated arginfo structures
zend_function_entry pdo_sqlsrv_functions[] = {
    {NULL, NULL, NULL}   // no functions directly defined by this driver
};

// the structure returned to Zend that exposes the extension to the Zend engine.
// this structure is defined in zend_modules.h in the PHP sources

zend_module_entry g_pdo_sqlsrv_module_entry = 
{
    STANDARD_MODULE_HEADER_EX,
    NULL,
    pdo_sqlsrv_depends,
    "pdo_sqlsrv", 
    pdo_sqlsrv_functions,           // exported function table
    // initialization and shutdown functions
    PHP_MINIT(pdo_sqlsrv),
    PHP_MSHUTDOWN(pdo_sqlsrv), 
    PHP_RINIT(pdo_sqlsrv), 
    PHP_RSHUTDOWN(pdo_sqlsrv), 
    PHP_MINFO(pdo_sqlsrv),
    // version of the extension.  Matches the version resource of the extension dll
    VER_FILEVERSION_STR,
    PHP_MODULE_GLOBALS(pdo_sqlsrv),
    NULL,           
    NULL,
    NULL,
    STANDARD_MODULE_PROPERTIES_EX
};

// called by Zend for each parameter in the g_pdo_errors_ht hash table when it is destroyed
void pdo_error_dtor( _Inout_ zval* elem ) {
    pdo_error* error_to_ignore = reinterpret_cast<pdo_error*>( Z_PTR_P( elem ) );
    pefree( error_to_ignore, 1 );
}

// Module initialization
// This function is called once per execution of the Zend engine

PHP_MINIT_FUNCTION(pdo_sqlsrv)
{
    // SQLSRV_UNUSED( type );

    // our global variables are initialized in the RINIT function
#if defined(ZTS)
    if( ts_allocate_id( &pdo_sqlsrv_globals_id,
                        sizeof( zend_pdo_sqlsrv_globals ),
                        (ts_allocate_ctor) NULL,
                        (ts_allocate_dtor) NULL ) == 0 )
        return FAILURE;
    ZEND_TSRMLS_CACHE_UPDATE();
#endif

    core_sqlsrv_register_severity_checker(pdo_severity_check);

    REGISTER_INI_ENTRIES();
    
    PDO_LOG_NOTICE("pdo_sqlsrv: entering minit");

    // initialize list of pdo errors
    g_pdo_errors_ht = reinterpret_cast<HashTable*>( pemalloc( sizeof( HashTable ), 1 ));
    ::zend_hash_init( g_pdo_errors_ht, 50, NULL, pdo_error_dtor /*pDestructor*/, 1 );

    for( int i = 0; PDO_ERRORS[i].error_code != -1; ++i ) {
        
        void* zr = ::zend_hash_index_update_mem( g_pdo_errors_ht, PDO_ERRORS[i].error_code, 
                                       &( PDO_ERRORS[i].sqlsrv_error ), sizeof( PDO_ERRORS[i].sqlsrv_error ) );
        if( zr == NULL ) {
                
            LOG( SEV_ERROR, "Failed to insert data into PDO errors hashtable." );
            return FAILURE;
        }
    }

    try {

    // register all attributes supported by this driver.
    for( int i= 0; pdo_attr_constants[i].name != NULL; ++i ) {
        
        REGISTER_PDO_SQLSRV_CLASS_CONST_LONG( pdo_attr_constants[i].name, pdo_attr_constants[i].value );
    
    }

    REGISTER_PDO_SQLSRV_CLASS_CONST_STRING( "SQLSRV_TXN_READ_UNCOMMITTED", PDOTxnIsolationValues::READ_UNCOMMITTED  );
    REGISTER_PDO_SQLSRV_CLASS_CONST_STRING( "SQLSRV_TXN_READ_COMMITTED", PDOTxnIsolationValues::READ_COMMITTED );
    REGISTER_PDO_SQLSRV_CLASS_CONST_STRING( "SQLSRV_TXN_REPEATABLE_READ", PDOTxnIsolationValues::REPEATABLE_READ );
    REGISTER_PDO_SQLSRV_CLASS_CONST_STRING( "SQLSRV_TXN_SERIALIZABLE", PDOTxnIsolationValues::SERIALIZABLE );
    REGISTER_PDO_SQLSRV_CLASS_CONST_STRING( "SQLSRV_TXN_SNAPSHOT", PDOTxnIsolationValues::SNAPSHOT );

    // retrieve the handles for the environments
    core_sqlsrv_minit( &g_pdo_henv_cp, &g_pdo_henv_ncp, pdo_sqlsrv_handle_env_error, "PHP_MINIT_FUNCTION for pdo_sqlsrv" );

    }
    catch( ... ) {

        return FAILURE;
    }

    php_pdo_register_driver( &pdo_sqlsrv_driver );

    return SUCCESS;
}

// Module shutdown function
// This function is called once per execution of the Zend engine

PHP_MSHUTDOWN_FUNCTION(pdo_sqlsrv)
{
    try {

        // SQLSRV_UNUSED( type );

        UNREGISTER_INI_ENTRIES();

        php_pdo_unregister_driver( &pdo_sqlsrv_driver );

        // clean up the list of pdo errors
        zend_hash_destroy( g_pdo_errors_ht );
        pefree( g_pdo_errors_ht, 1 /*persistent*/ );

        core_sqlsrv_mshutdown( *g_pdo_henv_cp, *g_pdo_henv_ncp );

    }
    catch( ... ) {

        PDO_LOG_NOTICE("Unknown exception caught in PHP_MSHUTDOWN_FUNCTION(pdo_sqlsrv)");
        return FAILURE;
    }

    return SUCCESS;
}


// Request initialization function
// This function is called once per PHP script execution

PHP_RINIT_FUNCTION(pdo_sqlsrv)
{
    // SQLSRV_UNUSED( module_number );
    // SQLSRV_UNUSED( type );

#if defined(ZTS) 
    ZEND_TSRMLS_CACHE_UPDATE();
#endif

#ifndef _WIN32
    // if necessary, set locale from the environment for ODBC, which MUST be done before any connection
    int set_locale = PDO_SQLSRV_G(set_locale_info);
    if (set_locale == 2) {
        setlocale(LC_ALL, "");
        PDO_LOG_NOTICE("pdo_sqlsrv: setlocale LC_ALL");
    }
    else if (set_locale == 1) {
        setlocale(LC_CTYPE, "");
        PDO_LOG_NOTICE("pdo_sqlsrv: setlocale LC_CTYPE");
    } 
    else {
        PDO_LOG_NOTICE("pdo_sqlsrv: setlocale NONE");
    }
#endif

    PDO_LOG_NOTICE("pdo_sqlsrv: entering rinit");
 
    return SUCCESS;
}


// Request shutdown
// Called at the end of a script's execution

PHP_RSHUTDOWN_FUNCTION(pdo_sqlsrv)
{
    // SQLSRV_UNUSED( module_number );
    // SQLSRV_UNUSED( type );

    PDO_LOG_NOTICE("pdo_sqlsrv: entering rshutdown");

    return SUCCESS;
}

// Called for php_info();  
// Displays the INI settings registered and their current values

PHP_MINFO_FUNCTION(pdo_sqlsrv)
{
    php_info_print_table_start();
    php_info_print_table_header(2, "pdo_sqlsrv support", "enabled");
    php_info_print_table_row(2, "ExtensionVer", VER_FILEVERSION_STR);
    php_info_print_table_end();
    DISPLAY_INI_ENTRIES();
}

// *** internal init functions ***

namespace {

    // mimic the functionality of the REGISTER_PDO_CLASS_CONST_LONG.  We use this instead of the macro because
    // we dynamically link the pdo_get_dbh_class function rather than use the static php_pdo_get_dbh_ce (see MINIT)

    void REGISTER_PDO_SQLSRV_CLASS_CONST_LONG( _In_z_ char const* name, _In_ long value )
    {
        zend_class_entry* zend_class = php_pdo_get_dbh_ce(); 
        
        SQLSRV_ASSERT( zend_class != NULL, "REGISTER_PDO_SQLSRV_CLASS_CONST_LONG: php_pdo_get_dbh_ce failed");
        zend_declare_class_constant_long(zend_class, const_cast<char*>(name), strlen(name), value);
    }

    void REGISTER_PDO_SQLSRV_CLASS_CONST_STRING( _In_z_  char const* name, _In_z_ char const* value )
    {
        zend_class_entry* zend_class = php_pdo_get_dbh_ce(); 

        SQLSRV_ASSERT( zend_class != NULL, "REGISTER_PDO_SQLSRV_CLASS_CONST_STRING: php_pdo_get_dbh_ce failed");
        zend_declare_class_constant_string(zend_class, const_cast<char*>(name), strlen(name), const_cast<char*>(value));
    }

    // array of pdo constants.
    sqlsrv_attr_pdo_constant pdo_attr_constants[] = {

        // driver specific attributes
        { "SQLSRV_ATTR_ENCODING"            , SQLSRV_ATTR_ENCODING },
        { "SQLSRV_ATTR_QUERY_TIMEOUT"       , SQLSRV_ATTR_QUERY_TIMEOUT },
        { "SQLSRV_ATTR_DIRECT_QUERY"        , SQLSRV_ATTR_DIRECT_QUERY },
        { "SQLSRV_ATTR_CURSOR_SCROLL_TYPE"  , SQLSRV_ATTR_CURSOR_SCROLL_TYPE },
        { "SQLSRV_ATTR_CLIENT_BUFFER_MAX_KB_SIZE", SQLSRV_ATTR_CLIENT_BUFFER_MAX_KB_SIZE },
        { "SQLSRV_ATTR_FETCHES_NUMERIC_TYPE", SQLSRV_ATTR_FETCHES_NUMERIC_TYPE },
        { "SQLSRV_ATTR_FETCHES_DATETIME_TYPE", SQLSRV_ATTR_FETCHES_DATETIME_TYPE },
        { "SQLSRV_ATTR_FORMAT_DECIMALS"     , SQLSRV_ATTR_FORMAT_DECIMALS },
        { "SQLSRV_ATTR_DECIMAL_PLACES"      , SQLSRV_ATTR_DECIMAL_PLACES },
        { "SQLSRV_ATTR_DATA_CLASSIFICATION" , SQLSRV_ATTR_DATA_CLASSIFICATION },

        // used for the size for output parameters: PDO::PARAM_INT and PDO::PARAM_BOOL use the default size of int,
        // PDO::PARAM_STR uses the size of the string in the variable
        { "SQLSRV_PARAM_OUT_DEFAULT_SIZE"   , -1 },

        // encoding attributes
        { "SQLSRV_ENCODING_DEFAULT"         , SQLSRV_ENCODING_DEFAULT },
        { "SQLSRV_ENCODING_SYSTEM"          , SQLSRV_ENCODING_SYSTEM },
        { "SQLSRV_ENCODING_BINARY"          , SQLSRV_ENCODING_BINARY },
        { "SQLSRV_ENCODING_UTF8"            , SQLSRV_ENCODING_UTF8 },

        // cursor types (can be assigned to SQLSRV_ATTR_CURSOR_SCROLL_TYPE
        { "SQLSRV_CURSOR_STATIC"            , SQL_CURSOR_STATIC },
        { "SQLSRV_CURSOR_DYNAMIC"           , SQL_CURSOR_DYNAMIC },
        { "SQLSRV_CURSOR_KEYSET"            , SQL_CURSOR_KEYSET_DRIVEN },
        { "SQLSRV_CURSOR_BUFFERED"          , static_cast<int>(SQLSRV_CURSOR_BUFFERED) },

        { NULL , 0 } // terminate the table
    };
}

// DllMain for the extension.  
#ifdef _WIN32
// Only needed if extension is built shared
#ifdef COMPILE_DL_PDO_SQLSRV
BOOL WINAPI DllMain( _In_ HINSTANCE hinstDLL, _In_ DWORD fdwReason, LPVOID )
{
    switch( fdwReason ) {
        case DLL_PROCESS_ATTACH:
            // store the module handle for use by client_info and server_info
            g_sqlsrv_hmodule = hinstDLL;
            break;
        default:
            break;
    }

    return TRUE;
}
#endif
#endif

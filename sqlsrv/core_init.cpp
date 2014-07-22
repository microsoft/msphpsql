//---------------------------------------------------------------------------------------------------------------------------------
// File: core_init.cpp
//
// Contents: common initialization routines shared by PDO and sqlsrv
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


// module global variables (initialized in minit and freed in mshutdown)
HMODULE g_sqlsrv_hmodule = NULL;
OSVERSIONINFO g_osversion;


// core_sqlsrv_minit
// Module initialization
// This function is called once per execution by the driver layer's MINIT function.
// The primary responsibility of this function is to allocate the two environment 
// handles used by core_sqlsrv_connect to allocate either a pooled or non pooled ODBC 
// connection handle.
// Parameters:
// henv_cp  - Environment handle for pooled connection.
// henv_ncp - Environment handle for non-pooled connection.
// err      - Driver specific error handler which handles any errors during initialization.
void core_sqlsrv_minit( sqlsrv_context** henv_cp, sqlsrv_context** henv_ncp, error_callback err, const char* driver_func TSRMLS_DC )
{
    SQLSRV_STATIC_ASSERT( sizeof( sqlsrv_sqltype ) == sizeof( long ));
    SQLSRV_STATIC_ASSERT( sizeof( sqlsrv_phptype ) == sizeof( long ));

    *henv_cp = *henv_ncp = SQL_NULL_HANDLE; // initialize return values to NULL

    try {

    // get the version of the OS we're running on.  For now this governs certain flags used by
    // WideCharToMultiByte.  It might be relevant to other things in the future.
    g_osversion.dwOSVersionInfoSize = sizeof( g_osversion );
    BOOL ver_return = GetVersionEx( &g_osversion );
    if( !ver_return ) {
        LOG( SEV_ERROR, "Failed to retrieve Windows version information." );
        throw core::CoreException();
    }

    SQLHANDLE henv = SQL_NULL_HANDLE;
    SQLRETURN r;

    // allocate the non pooled environment handle
    // we can't use the wrapper in core_sqlsrv.h since we don't have a context on which to base errors, so
    // we use the direct ODBC function.
    r = ::SQLAllocHandle( SQL_HANDLE_ENV, SQL_NULL_HANDLE, &henv );
    if( !SQL_SUCCEEDED( r )) {
        throw core::CoreException();
    }

    *henv_ncp = new sqlsrv_context( henv, SQL_HANDLE_ENV, err, NULL );
    (*henv_ncp)->set_func( driver_func );
    
    // set to ODBC 3
    core::SQLSetEnvAttr( **henv_ncp, SQL_ATTR_ODBC_VERSION, reinterpret_cast<SQLPOINTER>( SQL_OV_ODBC3 ), SQL_IS_INTEGER 
                         TSRMLS_CC );

    // disable connection pooling
    core::SQLSetEnvAttr( **henv_ncp, SQL_ATTR_CONNECTION_POOLING, reinterpret_cast<SQLPOINTER>( SQL_CP_OFF ), 
                         SQL_IS_UINTEGER TSRMLS_CC );

    // allocate the pooled envrionment handle
    // we can't use the wrapper in core_sqlsrv.h since we don't have a context on which to base errors, so
    // we use the direct ODBC function.
    r = ::SQLAllocHandle( SQL_HANDLE_ENV, SQL_NULL_HANDLE, &henv );
    if( !SQL_SUCCEEDED( r )) {
        throw core::CoreException();
    }

    *henv_cp = new sqlsrv_context( henv, SQL_HANDLE_ENV, err, NULL );
    (*henv_cp)->set_func( driver_func );

    // set to ODBC 3
    core::SQLSetEnvAttr( **henv_cp, SQL_ATTR_ODBC_VERSION, reinterpret_cast<SQLPOINTER>( SQL_OV_ODBC3 ), SQL_IS_INTEGER TSRMLS_CC);

    // enable connection pooling
    core:: SQLSetEnvAttr( **henv_cp, SQL_ATTR_CONNECTION_POOLING, reinterpret_cast<SQLPOINTER>( SQL_CP_ONE_PER_HENV ), 
                              SQL_IS_UINTEGER TSRMLS_CC );

    }
    catch( core::CoreException& e ) {

        LOG( SEV_ERROR, "core_sqlsrv_minit: Failed to allocate environment handles." );

        if( *henv_ncp != NULL ) {
            // free the ODBC env handle allocated just above
            SQLFreeHandle( SQL_HANDLE_ENV, **henv_ncp );
            delete *henv_ncp;   // free the memory for the sqlsrv_context (it comes from the C heap, not PHP's heap)
            *henv_ncp = NULL;
        }
        if( *henv_cp != NULL ) {
            // free the ODBC env handle allocated just above
            SQLFreeHandle( SQL_HANDLE_ENV, **henv_cp );
            delete *henv_cp;   // free the memory for the sqlsrv_context (it comes from the C heap, not PHP's heap)
            *henv_cp = NULL;
        }

        throw e;        // rethrow for the driver to catch
    }
    catch( std::bad_alloc& e ) {

        LOG( SEV_ERROR, "core_sqlsrv_minit: Failed memory allocation for environment handles." );

        if( *henv_ncp != NULL ) {
            SQLFreeHandle( SQL_HANDLE_ENV, **henv_ncp );
            delete *henv_ncp;
            *henv_ncp = NULL;
        }
        if( *henv_cp ) {
            SQLFreeHandle( SQL_HANDLE_ENV, **henv_cp );
            delete *henv_cp;
            *henv_cp = NULL;
        }

        throw e;        // rethrow for the driver to catch
    }
}

// core_sqlsrv_mshutdown
// Module shutdown function
// Free the environment handles allocated in MINIT and unregister our stream wrapper.
// Resource types and constants are automatically released since we don't flag them as
// persistent when they are registered.
// Parameters:
// henv_cp -  Pooled environment handle.
// henv_ncp - Non-pooled environment handle.
void core_sqlsrv_mshutdown( sqlsrv_context& henv_cp, sqlsrv_context& henv_ncp )
{
    if( henv_ncp != SQL_NULL_HANDLE ) {

        henv_ncp.invalidate();
    }

    if( henv_cp != SQL_NULL_HANDLE ) {

        henv_cp.invalidate();
    }

    return;
}


// DllMain for the extension.  

BOOL WINAPI DllMain( HINSTANCE hinstDLL, DWORD fdwReason, LPVOID )
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

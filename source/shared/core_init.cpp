//---------------------------------------------------------------------------------------------------------------------------------
// File: core_init.cpp
//
// Contents: common initialization routines shared by PDO and sqlsrv
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

#include "core_sqlsrv.h"

// module global variables (initialized in minit and freed in mshutdown)
HMODULE g_sqlsrv_hmodule = NULL;
bool isVistaOrGreater;


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
void core_sqlsrv_minit( _Outptr_ sqlsrv_context** henv_cp, _Inout_ sqlsrv_context** henv_ncp, _In_ error_callback err, _In_z_ const char* driver_func )
{
    SQLSRV_STATIC_ASSERT( sizeof( sqlsrv_sqltype ) == sizeof( zend_long ) );
    SQLSRV_STATIC_ASSERT( sizeof( sqlsrv_phptype ) == sizeof( zend_long ));

    *henv_cp = *henv_ncp = SQL_NULL_HANDLE; // initialize return values to NULL

    try {

#ifdef _WIN32
    // get the version of the OS we're running on.  For now this governs certain flags used by
    // WideCharToMultiByte.  It might be relevant to other things in the future.
    isVistaOrGreater = IsWindowsVistaOrGreater( );
#endif //_WIN32

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
                         );

    // disable connection pooling
    core::SQLSetEnvAttr( **henv_ncp, SQL_ATTR_CONNECTION_POOLING, reinterpret_cast<SQLPOINTER>( SQL_CP_OFF ), 
                         SQL_IS_UINTEGER );

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
    core::SQLSetEnvAttr( **henv_cp, SQL_ATTR_ODBC_VERSION, reinterpret_cast<SQLPOINTER>( SQL_OV_ODBC3 ), SQL_IS_INTEGER);

    // enable connection pooling
    core:: SQLSetEnvAttr( **henv_cp, SQL_ATTR_CONNECTION_POOLING, reinterpret_cast<SQLPOINTER>( SQL_CP_ONE_PER_HENV ), 
                              SQL_IS_UINTEGER );

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
void core_sqlsrv_mshutdown( _Inout_ sqlsrv_context& henv_cp, _Inout_ sqlsrv_context& henv_ncp )
{
    if( henv_ncp != SQL_NULL_HANDLE ) {

        henv_ncp.invalidate();
    }
	delete &henv_ncp;

    if( henv_cp != SQL_NULL_HANDLE ) {

        henv_cp.invalidate();
    }
	delete &henv_cp;

    return;
}


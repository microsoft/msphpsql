#ifndef PHP_PDO_SQLSRV_H
#define PHP_PDO_SQLSRV_H

//---------------------------------------------------------------------------------------------------------------------------------
// File: php_pdo_sqlsrv.h
//
// Contents: Declarations for the extension
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

#include "php.h"

//*********************************************************************************************************************************
// Global variables
//*********************************************************************************************************************************

// request level variables
ZEND_BEGIN_MODULE_GLOBALS(pdo_sqlsrv)

unsigned int pdo_log_severity;
zend_long client_buffer_max_size;
short report_additional_errors;

#ifndef _WIN32
zend_long set_locale_info;
#endif

ZEND_END_MODULE_GLOBALS(pdo_sqlsrv)

ZEND_EXTERN_MODULE_GLOBALS(pdo_sqlsrv);


//*********************************************************************************************************************************
// Initialization
//*********************************************************************************************************************************

// module global variables (initialized in minit and freed in mshutdown)
extern HashTable* g_pdo_errors_ht;

#define phpext_pdo_sqlsrv_ptr &g_pdo_sqlsrv_module_entry

// module initialization
PHP_MINIT_FUNCTION(pdo_sqlsrv);
// module shutdown function
PHP_MSHUTDOWN_FUNCTION(pdo_sqlsrv);
// request initialization function
PHP_RINIT_FUNCTION(pdo_sqlsrv);
// request shutdown function
PHP_RSHUTDOWN_FUNCTION(pdo_sqlsrv);
// module info function (info returned by phpinfo())
PHP_MINFO_FUNCTION(pdo_sqlsrv);

extern zend_module_entry g_pdo_sqlsrv_module_entry;   // describes the extension to PHP

#endif  /* PHP_PDO_SQLSRV_H */

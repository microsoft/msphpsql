//---------------------------------------------------------------------------------------------------------------------------------
// File: xplat_intsafe.h
//
// Contents: This module defines helper functions to prevent
//			 integer overflow bugs.
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


#ifndef XPLAT_INTSAFE_H
#define XPLAT_INTSAFE_H

#if (_MSC_VER > 1000)
#pragma once
#endif

#if !defined(_W64)
#if !defined(__midl) && (defined(_X86_) || defined(_M_IX86)) && (_MSC_VER >= 1300)
#define _W64 __w64
#else
#define _W64
#endif
#endif

#include "sal_def.h"
#include <limits.h>

//
// typedefs
//
typedef char                CHAR;
typedef unsigned char       BYTE;
typedef unsigned short      USHORT;
typedef unsigned short      WORD;
typedef int                 INT;
typedef unsigned int        UINT;
typedef windowsLong_t       LONG;
typedef windowsULong_t      DWORD;
typedef windowsLongLong_t   LONGLONG;
typedef windowsULongLong_t  ULONGLONG;

typedef _W64 windowsLong_t LONG_PTR, *PLONG_PTR;
typedef _W64 windowsULong_t ULONG_PTR, *PULONG_PTR;

typedef LONG_PTR    SSIZE_T;
typedef ULONG_PTR   SIZE_T;

#define DWORD_MAX       0xffffffffUL

#endif // XPLAT_INTSAFE_H

//---------------------------------------------------------------------------------------------------------------------------------
// File: xplat_winnls.h
//
// Contents: Contains the minimal definitions to build on non-Windows platforms
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

#ifndef XPLAT_WINNLS_H
#define XPLAT_WINNLS_H

#include <stdlib.h>
#include "typedefs_for_linux.h"

struct threadlocaleinfostruct;
struct threadmbcinfostruct;
typedef struct threadlocaleinfostruct * pthreadlocinfo;
typedef struct threadmbcinfostruct * pthreadmbcinfo;

typedef struct localeinfo_struct
{
    pthreadlocinfo locinfo;
    pthreadmbcinfo mbcinfo;
} _locale_tstruct, *_locale_t;

typedef DWORD LCTYPE;

#define MB_ERR_INVALID_CHARS      0x00000008  // error for invalid chars

typedef WORD LANGID;

#define MAKELANGID(p, s)       ((((WORD  )(s)) << 10) | (WORD  )(p))
#define LANG_NEUTRAL                     0x00
#define SUBLANG_DEFAULT                             0x01    // user default

BOOL
WINAPI
IsDBCSLeadByte(
    __inn BYTE  TestChar);


#endif // XPLAT_WINNLS_H

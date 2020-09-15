//---------------------------------------------------------------------------------------------------------------------------------
// File: typedefs_for_linux.h
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

#ifndef __linux_typedefs__
#define __linux_typedefs__

#define MPLAT_UNIX

#include "xplat.h"
#include "interlockedslist.h"

#define MAKELANGID(p, s)          ((((WORD  )(s)) << 10) | (WORD  )(p))
#define LANG_NEUTRAL              0x00
#define SUBLANG_DEFAULT           0x01        // user default

DWORD FormatMessageA(
    DWORD dwFlags,
    LPCVOID lpSource,
    DWORD dwMessageId,
    DWORD dwLanguageId,
    LPTSTR lpBuffer,
    DWORD nSize,
    va_list *Arguments
    );

#define FormatMessage FormatMessageA

#define FORMAT_MESSAGE_ALLOCATE_BUFFER 0x00000100
#define FORMAT_MESSAGE_IGNORE_INSERTS  0x00000200
#define FORMAT_MESSAGE_FROM_STRING     0x00000400
#define FORMAT_MESSAGE_FROM_HMODULE    0x00000800
#define FORMAT_MESSAGE_FROM_SYSTEM     0x00001000

#define ERROR_NO_UNICODE_TRANSLATION     1113L
#define ERROR_SUCCESS 0L

typedef int errno_t;
int mplat_snprintf_s(char *str, size_t sizeOfBuffer, size_t count, const char *format, ...);
int mplat_vsnprintf( char * buffer, size_t count, const char * format, va_list args );
errno_t mplat_wctomb_s(int *pRetValue, char *mbchar, size_t sizeInBytes, WCHAR wchar);
char * mplat_cscpy(char * _Dst, const char * _Src);
BOOL IsDBCSLeadByteEx(__inn UINT CodePage, __inn BYTE TestChar);

typedef HINSTANCE HMODULE;  /* HMODULEs can be used in place of HINSTANCEs */

size_t      mplat_wcslen( const WCHAR * );

#endif // __linux_typedefs__

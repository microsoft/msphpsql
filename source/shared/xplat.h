//---------------------------------------------------------------------------------------------------------------------------------
// File: xplat.h
//
// Contents: include for definition of Windows types for non-Windows platforms
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

#ifndef __XPLAT_H__
#define __XPLAT_H__

#ifndef _WCHART_DEFINED
#define _WCHART_DEFINED 
#endif

#include <iostream>
#include <string>
#include <errno.h>
#include <sql.h>
#include <sqlext.h>
#include <stdarg.h>
#include <cstdlib>
#include <cstdio>
#include <assert.h>
#include <string.h>

// Compiler specific items
#define _cdecl
#define __cdecl
#define __fastcall
#define _inline inline
#define __inline inline
#define __forceinline inline
#define __stdcall

#define __declspec__noinline __attribute__((noinline))
#define __declspec__selectany
#define __declspec(a) __declspec__##a
#define __FUNCTION__ __func__

#define __int8 char
#define __int32 int

// __int64
// This type must be defined in a way that allows "unsigned __int64" as a valid type declaration.
// That precludes using the obvious "int64_t" from stdint.h, because "unsigned int64_t" is not allowed
// (one should use "uint64_t" for unsigned 64-bit integers). As a result, we must use compiler-specific
// types such as GCC's "long long" instead
#if defined(_LP64)
#define __int64 long
#elif defined(__GNUC__)
#define __int64 long long
#else
#error "Compiler-specific definition required for __int64 in 32-bit builds"
#endif

// GCC-specific definitions
#if defined(__GNUC__)
#define MPLAT_GCC_VERSION (__GNUC__ * 10000 + __GNUC_MINOR__ * 100 + __GNUC_PATCHLEVEL__)
#endif // defined(__GNUC__)

// Needed to use the standard library min and max
#include <algorithm>
using std::min;
using std::max;

// Deal with differences between Windows and *nix interpretations of the C/C++ 'long' data type.
//
// On 64-bit Windows, 'long' is 32 bits. On 64-bit Linux, 'long' is 64 bits. Assuming the Windows code
// depends on it being 32 bits, use a definition that provides a guaranteed 32-bit type definition.
//
// Similarly, because 'long long' (and its cousin 'unsigned long long') are not portable across
// Linux/UNIX platforms and compilers, provide common definitions for 64-bit types as well.
//
// These types are used in this file primarily to define common Windows types (DWORD, LONG, etc.)
// Cross-platform code should use either the Windows types or appropriate types from <stdint.h>.
#include <stdint.h> // Use standard bit-specific types (signed/unsigned integrated)
typedef int32_t windowsLong_t;
typedef uint32_t windowsULong_t;
typedef int64_t windowsLongLong_t;
typedef uint64_t windowsULongLong_t;
typedef windowsLong_t LONG;
typedef windowsLongLong_t LONGLONG;
typedef windowsULongLong_t ULONGLONG;

#include <assert.h>
#include <stdlib.h>
#include "xplat_intsafe.h"


//-----------------------------------------------------------------------------
// Definitions for UnixODBC Driver Manager

// Define this to enable driver code to conditionalize around UnixODBC Driver
// Manager "quirks"...
#ifndef MPLAT_WWOWH
#define UNIXODBC
#endif

// End definitions for UnixODBC SQL headers
// ----------------------------------------------------------------------------


// WinNT.h
#define CONST const
#define VOID void
#define DLL_PROCESS_ATTACH   1

// Predeclared types from windef needed for remaining WinNT types
// to break circular dependency between WinNT.h and windef.h types.
typedef unsigned char BYTE;

typedef LONG HRESULT;
typedef char CHAR;
typedef CHAR *LPSTR;
#ifdef SQL_WCHART_CONVERT
typedef wchar_t             WCHAR;
#else
typedef unsigned short 		WCHAR;
#endif
typedef WCHAR *LPWSTR;
typedef CONST WCHAR *LPCWSTR;
typedef CONST CHAR *LPCSTR;
typedef void *PVOID;
typedef PVOID HANDLE;
typedef unsigned short WORD;

#define RTL_NUMBER_OF_V1(A) (sizeof(A)/sizeof((A)[0]))
#define ARRAYSIZE(A)    RTL_NUMBER_OF_V1(A)

// windef.h
typedef VOID *LPVOID;
typedef CONST void *LPCVOID;
typedef unsigned int UINT;
typedef unsigned char BYTE;
#define _LPCBYTE_DEFINED
typedef BOOL * LPBOOL;
typedef unsigned short WORD;
typedef unsigned short USHORT;
#define WINAPI // TODO __stdcall not portable?
typedef HANDLE HINSTANCE;

// INT_PTR - http://msdn.microsoft.com/en-us/library/aa384154(VS.85).aspx
#ifdef _WIN64
typedef __int64 INT_PTR;
#else
typedef int INT_PTR;
#endif

#ifndef IN
#define IN
#endif

#ifndef OUT
#define OUT
#endif

#ifndef OPTIONAL
#define OPTIONAL
#endif


//// ntdef.h
#define __unaligned
#ifndef UNALIGNED
#define UNALIGNED
#endif

//// ??
//typedef ULONG_PTR DWORD_PTR;
#define FALSE ((BOOL)0)
#define TRUE  ((BOOL)1)

#include "xplat_winerror.h"

typedef void * HLOCAL;
HLOCAL LocalAlloc(UINT uFlags, SIZE_T uBytes);
HLOCAL LocalFree(HLOCAL hMem);

//  End of xplat.h
#endif //__XPLAT_H__

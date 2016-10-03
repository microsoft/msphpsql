//-----------------------------------------------------------------------------
// File:        xplat.h
//
// Copyright:   Copyright (c) Microsoft Corporation
//
// Contents:    include for definition of Windows types for non-Windows platforms
//
// Comments:
//    http://msdn.microsoft.com/en-us/library/aa383751(VS.85).aspx
//
// owners:
//    See source code ownership database in SqlDevDash 
//-----------------------------------------------------------------------------

#ifndef __XPLAT_H__
#define __XPLAT_H__

#ifndef _WCHART_DEFINED
#define _WCHART_DEFINED 
#endif

#include <iostream>
#include <deque>
#include <map>
#include <algorithm>
#include <limits>
#include <cassert>
#include <memory>
#include <string>
#include <errno.h>
#include <sql.h>
#include <sqlext.h>
#include <stdarg.h>
#include <cstdlib>
#include <limits.h>
#include <cstdio>
#include <assert.h>
#include <string.h>
#include "msodbcsql.h"

#if defined(_MSC_VER)
// Turned on all warnings in WwoWH projects
// These warnings need to be disabled to be build warning free
// Note that some of these should be enabled and the code fixed
#pragma warning( disable : 4668 ) // preprocessor macro not defined
#pragma warning( disable : 4820 ) // padding after data member
#pragma warning( disable : 4201 ) // nonstandard: nameless union
#pragma warning( disable : 4100 ) // unreferenced formal parameter
#pragma warning( disable : 4514 ) // unreferenced inline function
#pragma warning( disable : 4505 ) // unreferenced inline function
#pragma warning( disable : 4710 ) // function not inlined
#pragma warning( disable : 4191 ) // unsafe conversion
#pragma warning( disable : 4365 ) // signed/unsigned argument conversion
#pragma warning( disable : 4245 ) // signed/unsigned assignment conversion
#pragma warning( disable : 4389 ) // signed/unsigned ==
#pragma warning( disable : 4987 ) // nonstandard: throw(...)
#pragma warning( disable : 4510 ) // default ctor could not be generated
#pragma warning( disable : 4512 ) // operator= could not be generated
#pragma warning( disable : 4626 ) // operator= could not be generated
#pragma warning( disable : 4625 ) // copy ctor could not be generated or accessed
#pragma warning( disable : 4189 ) // unused initialized local variable
#pragma warning( disable : 4127 ) // constant conditional test
#pragma warning( disable : 4061 ) // Unused enum values in switch
#pragma warning( disable : 4062 ) // Unused enum values in switch
#pragma warning( disable : 4706 ) // assignment within conditional
#pragma warning( disable : 4610 ) // can never be instantiated
#pragma warning( disable : 4244 ) // possible loss of data in conversion
#pragma warning( disable : 4701 ) // possible use of uninitialized variable
#pragma warning( disable : 4918 ) // invalid pragma optimization parameter
#pragma warning( disable : 4702 ) // unreachable code
#pragma warning( disable : 4265 ) // class with virtual fxns has non-virtual dtor
#pragma warning( disable : 4238 ) // nonstandard: class rvalue used as lvalue
#pragma warning( disable : 4310 ) // cast truncates constant value
#pragma warning( disable : 4946 ) // reinterpret_cast between related classes
#pragma warning( disable : 4264 ) // no matching override, hides base fxn
#pragma warning( disable : 4242 ) // conversion: possible loss of data
#pragma warning( disable : 4820 ) // added padding bytes
#endif

// Compiler specific items
#define _cdecl
#define __cdecl
#define __fastcall
#define _inline inline
#define __inline inline
#define __forceinline inline
#define __stdcall

#if !defined(_MSC_VER)
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
#endif

// GCC-specific definitions
#if defined(__GNUC__)
#define MPLAT_GCC_VERSION (__GNUC__ * 10000 + __GNUC_MINOR__ * 100 + __GNUC_PATCHLEVEL__)
#endif // defined(__GNUC__)

// For compilers that don't support cross-module inlining (part of whole-program/link-time
// optimization), such as the current MPLAT compilers (GCC 4.1.2 for RHEL5 and GCC 4.4 for RHEL6),
// we must force the generation of out-of-line definitions for functions that otherwise
// are only defined inline when those functions are called from other translation units.
// There are a handful of instances of these in SNI code as well as ODBC code.
//
// To force the compiler to emit an out-of-line definition for a function, just add an otherwise
// unused global (external linkage) non-const pointer pointing to the function:
//
// #if defined(MPLAT_NO_LTO)
// void (* g_pfnMyFunctionUnused)(MyFunctionArguments *) = MyFunction;
// #endif // defined(MPLAT_NO_LTO)
//
// This works because, absent whole-program optimization, the compiler cannot determine that the
// pointers are never called through, and the out-of-line definition cannot be optimized out,
// giving calling translation units something to link to.
//
// GCC adds LTO as of version 4.5
//JL - TODO: this version check doesn't work in Ubuntu
//#if defined(__GNUC__) && MPLAT_GCC_VERSION < 40500
#define MPLAT_NO_LTO
//#endif

#ifdef MPLAT_UNIX

// Needed to use the standard library min and max
#include <algorithm>
using std::min;
using std::max;

#elif MPLAT_WWOWH

#ifndef max
#define max(a,b)            (((a) > (b)) ? (a) : (b))
#endif
#ifndef min
#define min(a,b)            (((a) < (b)) ? (a) : (b))
#endif

#endif // MPLAT_WWOWH

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
#if defined(_MSC_VER) // WwoWH
typedef long windowsLong_t;
typedef unsigned long windowsULong_t;
typedef __int64 windowsLongLong_t;
typedef unsigned __int64 windowsULongLong_t;
#else // *nix (!WwoWH)
#include <stdint.h> // Use standard bit-specific types (signed/unsigned integrated)
typedef int32_t windowsLong_t;
typedef uint32_t windowsULong_t;
typedef int64_t windowsLongLong_t;
typedef uint64_t windowsULongLong_t;
#endif
typedef windowsLong_t LONG, *PLONG, *LPLONG;
typedef windowsLongLong_t LONGLONG;
typedef windowsULongLong_t ULONGLONG;

#include <assert.h>
#include <stdlib.h>
#include <intsafe.h>

// Exclude these headers in Windows machines (just for building on Windows w/o Windows headers)
#define SPECSTRINGS_H       // specstrings.h
#define ASOSHOST_DEFINED    // asoshost.h
#define _WINDOWS_           // windows.h
#define _INC_WINDOWSX       // windowsx.h
#define _WINBASE_           // winbase.h
#define _WINNLS_            // winnls.h
#define _WINERROR_          // winerror.h
#define NETCONS_INCLUDED    // lmcons.h
#define __WINCRYPT_H__      // wincrypt.h
#define _INC_TCHAR          // tchar.h
#define _INC_FCNTL          // fcntl.h
#define _INC_SHARE          // share.h
#define _INC_IO             // io.h
#define _INC_TYPES          // sys/types.h
#define _INC_STAT           // sys/stat.h
#define _INC_TIMEB          // sys/timeb.h
#define __unknwn_h__        // unknwn.h
#define __objidl_h__        // objidl.h
#define _OBJBASE_H_         // objbase.h
#define __RPC_H__           // rpc.h
#define __RPCNDR_H__        // rpcndr.h
#define _NP_HPP_            // np.hpp (no named pipes)
#define _SM_HPP_            // sm.hpp (no shared memory)
#define VIA_HEADER          // via.hpp (no via)
#define _WINUSER_           // winuser.h

#define interface struct

// What we need from dlgattr.h
#define OPTIONON                    L"Yes"
#define OPTIONOFF                   L"No"


//-----------------------------------------------------------------------------
// Definitions for UnixODBC Driver Manager

// Define this to enable driver code to conditionalize around UnixODBC Driver
// Manager "quirks"...
#ifndef MPLAT_WWOWH
#define UNIXODBC
#endif

/* can be defined in php sources */
#ifdef  ODBCVER
#undef  ODBCVER
#endif
// Build the mplat driver as an ODBC 3.8 driver, so that all of the
// source code shared with Windows SNAC (which is ODBC 3.8) compiles.
#define ODBCVER 0x0380

// Define this to indicate that we provide our own definitions for Windows types
#define ALLREADY_HAVE_WINDOWS_TYPE

// Definitions not otherwise provided in sqltypes.h, given that we define our own Windows types
#define SQL_API
typedef signed char             SCHAR;
typedef SCHAR                   SQLSCHAR;
typedef int                     SDWORD;
typedef unsigned int            UDWORD;
typedef signed short int        SWORD;
typedef signed short            SSHORT;
typedef double                  SDOUBLE;
typedef double                  LDOUBLE;
typedef float                   SFLOAT;
typedef void*                   PTR;
typedef signed short            RETCODE;
typedef void*                   SQLHWND;

// Definitions missing from sql.h
#define SQL_PARAM_DATA_AVAILABLE    101  
#define SQL_APD_TYPE      (-100)

// Bid control bit, only for xplat 
// It traces everything we current enabled for bid.
// The correlated tracing feature is not enabled.
#define DEFAULT_BID_CORT_BIT 0xFFFFBFFFF

// End definitions for UnixODBC SQL headers
// ----------------------------------------------------------------------------

#define UNREFERENCED_PARAMETER(arg)

// From share.h
#define _SH_DENYNO      0x40    /* deny none mode */


// WinNT.h
#define CONST const
#define VOID void
#define DLL_PROCESS_ATTACH   1
#define DLL_THREAD_ATTACH    2
#define DLL_THREAD_DETACH    3
#define DLL_PROCESS_DETACH   0
#define VER_GREATER_EQUAL               3
#define VER_MINORVERSION                0x0000001
#define VER_MAJORVERSION                0x0000002
#define VER_SERVICEPACKMINOR            0x0000010
#define VER_SERVICEPACKMAJOR            0x0000020
#define VER_SET_CONDITION(_m_,_t_,_c_)  \
        ((_m_)=VerSetConditionMask((_m_),(_t_),(_c_)))


// Predeclared types from windef needed for remaining WinNT types
// to break circular dependency between WinNT.h and windef.h types.
//typedef ULONG DWORD;
typedef unsigned char BYTE;
typedef unsigned char UCHAR;
typedef UCHAR *PUCHAR;

typedef DWORD LCID;
typedef LONG HRESULT;
typedef char CHAR;
typedef CHAR *LPSTR, *PSTR;
typedef CHAR *PCHAR, *LPCH, *PCH;
typedef CONST CHAR *LPCCH, *PCCH;
#ifdef SQL_WCHART_CONVERT
typedef wchar_t             WCHAR;
#else
typedef unsigned short 		WCHAR;
#endif
typedef WCHAR *LPWSTR;
typedef WCHAR *PWSTR;
typedef CONST WCHAR *LPCWSTR;
typedef CONST WCHAR *PCWSTR;
typedef CONST CHAR *LPCSTR, *PCSTR;
typedef void *PVOID;
typedef PVOID HANDLE;
typedef BYTE BOOLEAN;
typedef BOOLEAN *PBOOLEAN;
typedef HANDLE *PHANDLE;
typedef WCHAR *PWCHAR, *LPWCH, *PWCH;
typedef CONST WCHAR *LPCWCH, *PCWCH;
typedef int HFILE;

typedef short SHORT;
typedef CONST CHAR *LPCCH, *PCCH;

typedef unsigned short WORD;

#define RTL_NUMBER_OF_V1(A) (sizeof(A)/sizeof((A)[0]))
#define ARRAYSIZE(A)    RTL_NUMBER_OF_V1(A)
#define STATUS_STACK_OVERFLOW            ((DWORD   )0xC00000FDL)    
typedef union _LARGE_INTEGER {
    struct {
        DWORD LowPart;
        LONG HighPart;
    };
    struct {
        DWORD LowPart;
        LONG HighPart;
    } u;
    LONGLONG QuadPart;
} LARGE_INTEGER;
typedef LARGE_INTEGER *PLARGE_INTEGER;
typedef void * RPC_IF_HANDLE;

typedef WORD   LANGID;      

typedef enum _HEAP_INFORMATION_CLASS {

    HeapCompatibilityInformation,
    HeapEnableTerminationOnCorruption


} HEAP_INFORMATION_CLASS;


#define REG_SZ                      ( 1 )   // Unicode nul terminated string
#define REG_DWORD                   ( 4 )   // 32-bit number

#define RTL_NUMBER_OF_V1(A) (sizeof(A)/sizeof((A)[0]))
#define RTL_NUMBER_OF(A) RTL_NUMBER_OF_V1(A)


// windef.h
typedef VOID *LPVOID;
typedef CONST void *LPCVOID;
typedef int INT;
typedef int *LPINT;
typedef unsigned int UINT;
typedef ULONGLONG UINT64;
typedef unsigned int *PUINT;
typedef unsigned char BYTE;
typedef BYTE *PBYTE;
typedef BYTE *LPBYTE;
typedef const BYTE *LPCBYTE;
#define _LPCBYTE_DEFINED
//typedef int BOOL;
typedef BOOL * LPBOOL;
typedef unsigned short WORD;
typedef WORD * LPWORD;
typedef WORD UWORD;
typedef DWORD * LPDWORD;
typedef DWORD * PDWORD;
typedef unsigned short USHORT;
#define CDECL // TODO _cdecl and cdecl not portable?
#define WINAPI // TODO __stdcall not portable?
#define MAX_PATH 260
typedef HANDLE HINSTANCE;
typedef HANDLE HGLOBAL;
typedef ULONGLONG  DWORDLONG;
typedef DWORDLONG *PDWORDLONG;
typedef float FLOAT;

typedef struct _FILETIME {
    DWORD dwLowDateTime;
    DWORD dwHighDateTime;
} FILETIME, *PFILETIME, *LPFILETIME;
typedef double              DOUBLE;
#define MAKELONG(a, b)      ((LONG)(((WORD)(((DWORD_PTR)(a)) & 0xffff)) | ((DWORD)((WORD)(((DWORD_PTR)(b)) & 0xffff))) << 16))

// INT_PTR - http://msdn.microsoft.com/en-us/library/aa384154(VS.85).aspx
#ifdef _WIN64
typedef __int64 INT_PTR;
#else
typedef int INT_PTR;
#endif

typedef INT_PTR (*FARPROC)();
typedef INT_PTR (*NEARPROC)();
typedef INT_PTR (*PROC)();


DWORD GetFileSize(
  __inn      HANDLE hFile,
  __out_opt  LPDWORD lpFileSizeHigh
);

typedef union _ULARGE_INTEGER {
    struct {
        DWORD LowPart;
        DWORD HighPart;
    };
    struct {
        DWORD LowPart;
        DWORD HighPart;
    } u;
    ULONGLONG QuadPart;
} ULARGE_INTEGER;

typedef ULARGE_INTEGER *PULARGE_INTEGER;

#ifndef IN
#define IN
#endif

#ifndef OUT
#define OUT
#endif

#ifndef OPTIONAL
#define OPTIONAL
#endif


ULONGLONG
VerSetConditionMask(
        IN  ULONGLONG   ConditionMask,
        IN  DWORD   TypeMask,
        IN  BYTE    Condition
        );



//#include <basetsd.h>

//// ntdef.h
#define __unaligned
#ifndef UNALIGNED
#define UNALIGNED
#endif
//typedef __nullterminated WCHAR UNALIGNED *LPUWSTR;

//// crtdefs.h
//#if !defined(_TRUNCATE)
//#define _TRUNCATE ((size_t)-1)
//#endif

//// ??
//typedef ULONG_PTR DWORD_PTR;
#define FALSE ((BOOL)0)
#define TRUE  ((BOOL)1)


//// asoshost.h (excluded above)
//struct ISOSHost_MemObj;
//struct ISOSHost;
//extern ISOSHost_MemObj          *g_pMO;
//extern ISOSHost                 *g_pISOSHost;
//inline HRESULT CreateSQLSOSHostInterface() { return 0; }
//inline HRESULT CreateGlobalSOSHostInterface() { return 0; }

//// These are temporary solution versions of the real files that contain the minimal declarations
//// needed to compile for non-Windows platforms.  See the special include path for the
//// location of these files.
//#include <guiddef.h>
//#include <objbase.h>
//#include <winbase.h>
//#include <winnls.h>
#include <winerror.h>
//#include <wtypes.h>
//#include <wctype.h>
//#include <winuser.h>
//#include <stdio.h>
//#include <tchar.h>
//#include <winuser.h>
//#include <wincon.h>

//#define LMEM_FIXED 0
typedef void * HLOCAL;
HLOCAL LocalAlloc(UINT uFlags, SIZE_T uBytes);
//HLOCAL LocalReAlloc(HLOCAL hMem, SIZE_T uBytes, UINT uFlags);
HLOCAL LocalFree(HLOCAL hMem);

//  End of xplat.h
#endif //__XPLAT_H__

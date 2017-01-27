//---------------------------------------------------------------------------------------------------------------------------------
// File: intsafe.h
//
// Contents: This module defines helper functions to prevent
//			 integer overflow bugs.
//
// Microsoft Drivers 4.1 for PHP for SQL Server
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

#include "sal.h"
#include <limits.h>

//
// typedefs
//
typedef char                CHAR;
typedef signed char         INT8;
typedef unsigned char       UCHAR;
typedef unsigned char       UINT8;
typedef unsigned char       BYTE;
typedef short               SHORT;
typedef signed short        INT16;
typedef unsigned short      USHORT;
typedef unsigned short      UINT16;
typedef unsigned short      WORD;
typedef int                 INT;
typedef signed int          INT32;
typedef unsigned int        UINT;
typedef unsigned int        UINT32;
typedef windowsLong_t       LONG;
typedef windowsULong_t      DWORD;
typedef windowsLongLong_t   LONGLONG;
typedef windowsLongLong_t   LONG64;
typedef windowsLongLong_t   INT64;
typedef windowsULongLong_t  ULONGLONG;
typedef windowsULongLong_t  DWORDLONG;
typedef windowsULongLong_t  ULONG64;
typedef windowsULongLong_t  DWORD64;
typedef windowsULongLong_t  UINT64;

#if (__midl > 501)
typedef [public]          __int3264 INT_PTR;
typedef [public] unsigned __int3264 UINT_PTR;
typedef [public]          __int3264 LONG_PTR;
typedef [public] unsigned __int3264 ULONG_PTR;
#else
#ifdef _WIN64
typedef __int64 INT_PTR, *PINT_PTR;
typedef unsigned __int64 UINT_PTR, *PUINT_PTR;
typedef __int64 LONG_PTR, *PLONG_PTR;
typedef unsigned __int64 ULONG_PTR, *PULONG_PTR;
#else
typedef _W64 int INT_PTR, *PINT_PTR;
typedef _W64 unsigned int UINT_PTR, *PUINT_PTR;
typedef _W64 windowsLong_t LONG_PTR, *PLONG_PTR;
typedef _W64 windowsULong_t ULONG_PTR, *PULONG_PTR;
#endif // WIN64
#endif // (__midl > 501)

typedef ULONG_PTR   DWORD_PTR;
typedef LONG_PTR    SSIZE_T;
typedef ULONG_PTR   SIZE_T;

#if defined(_AMD64_)
#ifdef __cplusplus
extern "C" {
#endif

#define UnsignedMultiply128 _umul128

ULONG64
UnsignedMultiply128 (
    __inn ULONG64  Multiplier,
    __inn ULONG64  Multiplicand,
    __outt __deref_out_range(==,Multiplier * Multiplicand) ULONG64 *HighProduct
    );
#pragma intrinsic(_umul128)

#ifdef __cplusplus
}
#endif
#endif // _AMD64_



typedef __success(return >= 0) windowsLong_t HRESULT;

#define SUCCEEDED(hr)   (((HRESULT)(hr)) >= 0)
#define FAILED(hr)      (((HRESULT)(hr)) < 0)

#define S_OK    ((HRESULT)0L)

#define INTSAFE_E_ARITHMETIC_OVERFLOW   ((HRESULT)0x80070216L)  // 0x216 = 534 = ERROR_ARITHMETIC_OVERFLOW
#ifndef SORTPP_PASS 
// compiletime asserts (failure results in error C2118: negative subscript)
#define C_ASSERT(e) typedef char __C_ASSERT__[(e)?1:-1]
#else
#define C_ASSERT(e)
#endif

//
// UInt32x32To64 macro
//
#define UInt32x32To64(a, b) ((windowsULongLong_t)(((windowsULongLong_t)(a)) * ((windowsULong_t)(b))))

//
// Min/Max type values
//
//#define INT8_MIN        (-127 - 1)
#define SHORT_MIN       (-32768)
//#define INT16_MIN       (-32767 - 1)
//#define INT_MIN         (-2147483647 - 1)
//#define INT32_MIN       (-2147483647l - 1)
//#define LONG_MIN        (-2147483647L - 1)
#define LONGLONG_MIN    (-9223372036854775807ll - 1)
#define LONG64_MIN      (-9223372036854775807ll - 1)
//#define INT64_MIN       (-9223372036854775807ll - 1)
#define INT128_MIN      (-170141183460469231731687303715884105727i128 - 1)

#ifdef _WIN64
#define INT_PTR_MIN     (-9223372036854775807ll - 1)
#define LONG_PTR_MIN    (-9223372036854775807ll - 1)
#define PTRDIFF_T_MIN   (-9223372036854775807ll - 1)
#define SSIZE_T_MIN     (-9223372036854775807ll - 1)
#else
#define INT_PTR_MIN     (-2147483647 - 1)
#define LONG_PTR_MIN    (-2147483647L - 1)
#define PTRDIFF_T_MIN   (-2147483647 - 1)
#define SSIZE_T_MIN     (-2147483647L - 1)
#endif

//#define INT8_MAX        127
//#define UINT8_MAX       0xff
#define BYTE_MAX        0xff
#define SHORT_MAX       32767
//#define INT16_MAX       32767
#define USHORT_MAX      0xffff
//#define UINT16_MAX      0xffff
#define WORD_MAX        0xffff
//#define INT_MAX         2147483647
//#define INT32_MAX       2147483647l
//#define UINT_MAX        0xffffffff
//#define UINT32_MAX      0xfffffffful
//#define LONG_MAX        2147483647L
//#define ULONG_MAX       0xffffffffUL
#define DWORD_MAX       0xffffffffUL
#define LONGLONG_MAX    9223372036854775807ll
#define LONG64_MAX      9223372036854775807ll
//#define INT64_MAX       9223372036854775807ll
#define ULONGLONG_MAX   0xffffffffffffffffull
#define DWORDLONG_MAX   0xffffffffffffffffull
#define ULONG64_MAX     0xffffffffffffffffull
#define DWORD64_MAX     0xffffffffffffffffull
//#define UINT64_MAX      0xffffffffffffffffull
#define INT128_MAX      170141183460469231731687303715884105727i128
#define UINT128_MAX     0xffffffffffffffffffffffffffffffffui128

#ifndef _I64_MIN
#define _I64_MIN INT64_MIN
#define _I64_MAX INT64_MAX
#define _UI64_MIN UINT64_MIN
#define _UI64_MAX UINT64_MAX
#endif

#undef SIZE_T_MAX

#ifdef _WIN64
#define INT_PTR_MAX     9223372036854775807ll
#define UINT_PTR_MAX    0xffffffffffffffffull
#define LONG_PTR_MAX    9223372036854775807ll
#define ULONG_PTR_MAX   0xffffffffffffffffull
#define DWORD_PTR_MAX   0xffffffffffffffffull
#define PTRDIFF_T_MAX   9223372036854775807ll
#define SIZE_T_MAX      0xffffffffffffffffull
#define SSIZE_T_MAX     9223372036854775807ll
#define _SIZE_T_MAX     0xffffffffffffffffull
#else
#define INT_PTR_MAX     2147483647 
#define UINT_PTR_MAX    0xffffffff
#define LONG_PTR_MAX    2147483647L
#define ULONG_PTR_MAX   0xffffffffUL
#define DWORD_PTR_MAX   0xffffffffUL
#define PTRDIFF_T_MAX   2147483647
#define SIZE_T_MAX      0xffffffff
#define SSIZE_T_MAX     2147483647L
#define _SIZE_T_MAX     0xffffffffUL
#endif


//
// It is common for -1 to be used as an error value
//
#define INT8_ERROR      (-1)
#define UINT8_ERROR     0xff
#define BYTE_ERROR      0xff
#define SHORT_ERROR     (-1)
#define INT16_ERROR     (-1)
#define USHORT_ERROR    0xffff
#define UINT16_ERROR    0xffff
#define WORD_ERROR      0xffff
#define INT_ERROR       (-1)
#define INT32_ERROR     (-1l)
#define UINT_ERROR      0xffffffff
#define UINT32_ERROR    0xfffffffful
#define LONG_ERROR      (-1L)
#define ULONG_ERROR     0xffffffffUL
#define DWORD_ERROR     0xffffffffUL
#define LONGLONG_ERROR  (-1ll)
#define LONG64_ERROR    (-1ll)
#define INT64_ERROR     (-1ll)
#define ULONGLONG_ERROR 0xffffffffffffffffull
#define DWORDLONG_ERROR 0xffffffffffffffffull
#define ULONG64_ERROR   0xffffffffffffffffull
#define UINT64_ERROR    0xffffffffffffffffull

#ifdef _WIN64
#define INT_PTR_ERROR   (-1ll)
#define UINT_PTR_ERROR  0xffffffffffffffffull
#define LONG_PTR_ERROR  (-1ll)
#define ULONG_PTR_ERROR 0xffffffffffffffffull
#define DWORD_PTR_ERROR 0xffffffffffffffffull
#define PTRDIFF_T_ERROR (-1ll)
#define SIZE_T_ERROR    0xffffffffffffffffull
#define SSIZE_T_ERROR   (-1ll)
#define _SIZE_T_ERROR   0xffffffffffffffffull
#else
#define INT_PTR_ERROR   (-1) 
#define UINT_PTR_ERROR  0xffffffff
#define LONG_PTR_ERROR  (-1L)
#define ULONG_PTR_ERROR 0xffffffffUL
#define DWORD_PTR_ERROR 0xffffffffUL
#define PTRDIFF_T_ERROR (-1)
#define SIZE_T_ERROR    0xffffffff
#define SSIZE_T_ERROR   (-1L)
#define _SIZE_T_ERROR   0xffffffffUL
#endif


//
// We make some assumptions about the sizes of various types. Let's be
// explicit about those assumptions and check them.
//
C_ASSERT(sizeof(USHORT) == 2);
C_ASSERT(sizeof(INT) == 4);
C_ASSERT(sizeof(UINT) == 4);
C_ASSERT(sizeof(LONG) == 4);
C_ASSERT(sizeof(ULONG) == 8);
C_ASSERT(sizeof(UINT_PTR) == sizeof(ULONG_PTR));


//=============================================================================
// Conversion functions
//
// There are three reasons for having conversion functions:
//
// 1. We are converting from a signed type to an unsigned type of the same
//    size, or vice-versa.
//
//    Since we only have unsigned math functions, this allows people to convert
//    to unsigned, do the math, and then convert back to signed.
//
// 2. We are converting to a smaller type, and we could therefore possibly
//    overflow.
//
// 3. We are converting to a bigger type, and we are signed and the type we are
//    converting to is unsigned.
//
//=============================================================================


//
// INT8 -> UCHAR conversion
//
__inline
HRESULT
Int8ToUChar(
    __inn INT8 i8Operand,
    __outt __deref_out_range(==,i8Operand) UCHAR* pch)
{
    HRESULT hr;

    if (i8Operand >= 0)
    {
        *pch = (UCHAR)i8Operand;
        hr = S_OK;
    }
    else
    {
        *pch = '\0';
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// INT8 -> UINT8 conversion
//
__inline
HRESULT
Int8ToUInt8(
    __inn INT8 i8Operand,
    __outt __deref_out_range(==,i8Operand) UINT8* pu8Result)
{
    HRESULT hr;
    
    if (i8Operand >= 0)
    {
        *pu8Result = (UINT8)i8Operand;
        hr = S_OK;
    }
    else
    {
        *pu8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// INT8 -> BYTE conversion
//
#define Int8ToByte  Int8ToUInt8

//
// INT8 -> USHORT conversion
//
__inline
HRESULT
Int8ToUShort(
    __inn INT8 i8Operand,
    __outt __deref_out_range(==,i8Operand) USHORT* pusResult)
{
    HRESULT hr;
    
    if (i8Operand >= 0)
    {
        *pusResult = (USHORT)i8Operand;
        hr = S_OK;
    }
    else
    {
        *pusResult = USHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// INT8 -> UINT16 conversion
//
#define Int8ToUInt16    Int8ToUShort

//
// INT8 -> WORD conversion
//
#define Int8ToWord  Int8ToUShort

//
// INT8 -> UINT conversion
//
__inline
HRESULT
Int8ToUInt(
    __inn INT8 i8Operand,
    __outt __deref_out_range(==,i8Operand) UINT* puResult)
{
    HRESULT hr;
    
    if (i8Operand >= 0)
    {
        *puResult = (UINT)i8Operand;
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// INT8 -> UINT32 conversion
//
#define Int8ToUInt32    Int8ToUInt

//
// INT8 -> UINT_PTR conversion
//
__inline
HRESULT
Int8ToUIntPtr(
    __inn INT8 i8Operand,
    __outt __deref_out_range(==,i8Operand) UINT_PTR* puResult)
{
    HRESULT hr;
    
    if (i8Operand >= 0)
    {
        *puResult = (UINT_PTR)i8Operand;
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// INT8 -> ULONG conversion
//
__inline
HRESULT
Int8ToULong(
    __inn INT8 i8Operand,
    __outt __deref_out_range(==,i8Operand) ULONG* pulResult)
{
    HRESULT hr;
    
    if (i8Operand >= 0)
    {
        *pulResult = (ULONG)i8Operand;
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// INT8 -> ULONG_PTR conversion
//
__inline
HRESULT
Int8ToULongPtr(
    __inn INT8 i8Operand,
    __outt __deref_out_range(==,i8Operand) ULONG_PTR* pulResult)
{
    HRESULT hr;
    
    if (i8Operand >= 0)
    {
        *pulResult = (ULONG_PTR)i8Operand;
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// INT8 -> DWORD conversion
//
#define Int8ToDWord Int8ToULong

//
// INT8 -> DWORD_PTR conversion
//
#define Int8ToDWordPtr  Int8ToULongPtr

//
// INT8 -> ULONGLONG conversion
//
__inline
HRESULT
Int8ToULongLong(
    __inn INT8 i8Operand,
    __outt __deref_out_range(==,i8Operand) ULONGLONG* pullResult)
{
    HRESULT hr;
    
    if (i8Operand >= 0)
    {
        *pullResult = (ULONGLONG)i8Operand;
        hr = S_OK;
    }
    else
    {
        *pullResult = ULONGLONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// INT8 -> DWORDLONG conversion
//
#define Int8ToDWordLong Int8ToULongLong

//
// INT8 -> ULONG64 conversion
//
#define Int8ToULong64   Int8ToULongLong

//
// INT8 -> DWORD64 conversion
//
#define Int8ToDWord64   Int8ToULongLong

//
// INT8 -> UINT64 conversion
//
#define Int8ToUInt64    Int8ToULongLong

//
// INT8 -> size_t conversion
//
#define Int8ToSizeT Int8ToUIntPtr

//
// INT8 -> SIZE_T conversion
//
#define Int8ToSIZET Int8ToULongPtr

//
// UINT8 -> INT8 conversion
//
__inline
HRESULT
UInt8ToInt8(
    __inn UINT8 u8Operand,
    __outt __deref_out_range(==,u8Operand) INT8* pi8Result)
{
    HRESULT hr;
    
    if (u8Operand <= INT8_MAX)
    {
        *pi8Result = (INT8)u8Operand;
        hr = S_OK;
    }
    else
    {
        *pi8Result = INT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// UINT8 -> CHAR conversion
//
__forceinline
HRESULT
UInt8ToChar(
    __inn UINT8 u8Operand,
    __outt __deref_out_range(==,u8Operand) CHAR* pch)
{
#ifdef _CHAR_UNSIGNED
    *pch = (CHAR)u8Operand;
    return S_OK;
#else
    return UInt8ToInt8(u8Operand, (INT8*)pch);
#endif
}

//
// BYTE -> INT8 conversion
//
__inline
HRESULT
ByteToInt8(
    __inn BYTE bOperand,
    __outt __deref_out_range(==,bOperand) INT8* pi8Result)
{
    HRESULT hr;
    
    if (bOperand <= INT8_MAX)
    {
        *pi8Result = (INT8)bOperand;
        hr = S_OK;
    }
    else
    {
        *pi8Result = INT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// BYTE -> CHAR conversion
//
__forceinline
HRESULT
ByteToChar(
    __inn BYTE bOperand,
    __outt __deref_out_range(==,bOperand) CHAR* pch)
{
#ifdef _CHAR_UNSIGNED
    *pch = (CHAR)bOperand;
    return S_OK;
#else
    return ByteToInt8(bOperand, (INT8*)pch);
#endif
}

//
// SHORT -> INT8 conversion
//
__inline
HRESULT
ShortToInt8(
    __inn SHORT sOperand,
    __outt __deref_out_range(==,sOperand) INT8* pi8Result)
{
    HRESULT hr;

    if ((sOperand >= INT8_MIN) && (sOperand <= INT8_MAX))
    {
        *pi8Result = (INT8)sOperand;
        hr = S_OK;
    }
    else
    {
        *pi8Result = INT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// SHORT -> UCHAR conversion
//
__inline
HRESULT
ShortToUChar(
    __inn SHORT sOperand,
    __outt __deref_out_range(==,sOperand) UCHAR* pch)
{
    HRESULT hr;

    if ((sOperand >= 0) && (sOperand <= 255))
    {
        *pch = (UCHAR)sOperand;
        hr = S_OK;
    }
    else
    {
        *pch = '\0';
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// SHORT -> CHAR conversion
//
__forceinline
HRESULT
ShortToChar(
    __inn SHORT sOperand,
    __outt __deref_out_range(==,sOperand) CHAR* pch)
{
#ifdef _CHAR_UNSIGNED
    return ShortToUChar(sOperand, (UCHAR*)pch);
#else
    return ShortToInt8(sOperand, (INT8*)pch);
#endif // _CHAR_UNSIGNED
}

//
// SHORT -> UINT8 conversion
//
__inline
HRESULT
ShortToUInt8(
    __inn SHORT sOperand,
    __outt __deref_out_range(==,sOperand) UINT8* pui8Result)
{
    HRESULT hr;

    if ((sOperand >= 0) && (sOperand <= UINT8_MAX))
    {
        *pui8Result = (UINT8)sOperand;
        hr = S_OK;
    }
    else
    {
        *pui8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// SHORT -> BYTE conversion
//
#define ShortToByte  ShortToUInt8

//
// SHORT -> USHORT conversion
//
__inline
HRESULT
ShortToUShort(
    __inn SHORT sOperand,
    __outt __deref_out_range(==,sOperand) USHORT* pusResult)
{
    HRESULT hr;

    if (sOperand >= 0)
    {
        *pusResult = (USHORT)sOperand;
        hr = S_OK;
    }
    else
    {
        *pusResult = USHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// SHORT -> UINT16 conversion
//
#define ShortToUInt16   ShortToUShort

//
// SHORT -> WORD conversion
//
#define ShortToWord ShortToUShort

//
// SHORT -> UINT conversion
//
__inline
HRESULT
ShortToUInt(
    __inn SHORT sOperand,
    __outt __deref_out_range(==,sOperand) UINT* puResult)
{
    HRESULT hr;

    if (sOperand >= 0)
    {
        *puResult = (UINT)sOperand;
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// SHORT -> UINT32 conversion
//
#define ShortToUInt32   ShortToUInt

//
// SHORT -> UINT_PTR conversion
//
__inline
HRESULT
ShortToUIntPtr(
    __inn SHORT sOperand,
    __outt __deref_out_range(==,sOperand) UINT_PTR* puResult)
{
    HRESULT hr;

    if (sOperand >= 0)
    {
        *puResult = (UINT_PTR)sOperand;
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// SHORT -> ULONG conversion
//
__inline
HRESULT
ShortToULong(
    __inn SHORT sOperand,
    __outt __deref_out_range(==,sOperand) ULONG* pulResult)
{
    HRESULT hr;
    
    if (sOperand >= 0)
    {
        *pulResult = (ULONG)sOperand;
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// SHORT -> ULONG_PTR conversion
//
__inline
HRESULT
ShortToULongPtr(
    __inn SHORT sOperand,
    __outt __deref_out_range(==,sOperand) ULONG_PTR* pulResult)
{
    HRESULT hr;
    
    if (sOperand >= 0)
    {
        *pulResult = (ULONG_PTR)sOperand;
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// SHORT -> DWORD conversion
//
#define ShortToDWord    ShortToULong

//
// SHORT -> DWORD_PTR conversion
//
__inline
HRESULT
ShortToDWordPtr(
    __inn SHORT sOperand,
    __outt __deref_out_range(==,sOperand) DWORD_PTR* pdwResult)
{
    HRESULT hr;
    
    if (sOperand >= 0)
    {
        *pdwResult = (DWORD_PTR)sOperand;
        hr = S_OK;
    }
    else
    {
        *pdwResult = DWORD_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// SHORT -> ULONGLONG conversion
//
__inline
HRESULT
ShortToULongLong(
    __inn SHORT sOperand,
    __outt __deref_out_range(==,sOperand) ULONGLONG* pullResult)
{
    HRESULT hr;

    if (sOperand >= 0)
    {
        *pullResult = (ULONGLONG)sOperand;
        hr = S_OK;
    }
    else
    {
        *pullResult = ULONGLONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// SHORT -> DWORDLONG conversion
//
#define ShortToDWordLong    ShortToULongLong

//
// SHORT -> ULONG64 conversion
//
#define ShortToULong64  ShortToULongLong

//
// SHORT -> DWORD64 conversion
//
#define ShortToDWord64  ShortToULongLong

//
// SHORT -> UINT64 conversion
//
#define ShortToUInt64   ShortToULongLong

//
// SHORT -> size_t conversion
//
#define ShortToSizeT    ShortToUIntPtr

//
// SHORT -> SIZE_T conversion
//
#define ShortToSIZET    ShortToULongPtr

//
// INT16 -> CHAR conversion
//
#define Int16ToChar ShortToChar

//
// INT16 -> INT8 conversion
//
#define Int16ToInt8 ShortToInt8

//
// INT16 -> UCHAR conversion
//
#define Int16ToUChar    ShortToUChar

//
// INT16 -> UINT8 conversion
//
#define Int16ToUInt8    ShortToUInt8

//
// INT16 -> BYTE conversion
//
#define Int16ToByte ShortToUInt8

//
// INT16 -> USHORT conversion
//
#define Int16ToUShort   ShortToUShort

//
// INT16 -> UINT16 conversion
//
#define Int16ToUInt16   ShortToUShort

//
// INT16 -> WORD conversion
//
#define Int16ToWord ShortToUShort

//
// INT16 -> UINT conversion
//
#define Int16ToUInt ShortToUInt

//
// INT16 -> UINT32 conversion
//
#define Int16ToUInt32   ShortToUInt

//
// INT16 -> UINT_PTR conversion
//
#define Int16ToUIntPtr  ShortToUIntPtr

//
// INT16 -> ULONG conversion
//
#define Int16ToULong    ShortToULong

//
// INT16 -> ULONG_PTR conversion
//
#define Int16ToULongPtr ShortToULongPtr

//
// INT16 -> DWORD conversion
//
#define Int16ToDWord    ShortToULong

//
// INT16 -> DWORD_PTR conversion
//
#define Int16ToDWordPtr ShortToULongPtr

//
// INT16 -> ULONGLONG conversion
//
#define Int16ToULongLong    ShortToULongLong

//
// INT16 -> DWORDLONG conversion
//
#define Int16ToDWordLong    ShortToULongLong

//
// INT16 -> ULONG64 conversion
//
#define Int16ToULong64  ShortToULongLong

//
// INT16 -> DWORD64 conversion
//
#define Int16ToDWord64  ShortToULongLong

//
// INT16 -> UINT64 conversion
//
#define Int16ToUInt64   ShortToULongLong

//
// INT16 -> size_t conversion
//
#define Int16ToSizeT    ShortToUIntPtr

//
// INT16 -> SIZE_T conversion
//
#define Int16ToSIZET    ShortToULongPtr

//
// USHORT -> INT8 conversion
//
__inline
HRESULT
UShortToInt8(
    __inn USHORT usOperand,
    __outt __deref_out_range(==,usOperand) INT8* pi8Result)
{
    HRESULT hr;
    
    if (usOperand <= INT8_MAX)
    {
        *pi8Result = (INT8)usOperand;
        hr = S_OK;
    }
    else
    {
        *pi8Result = INT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// USHORT -> UCHAR conversion
//
__inline
HRESULT
UShortToUChar(
    __inn USHORT usOperand,
    __outt __deref_out_range(==,usOperand) UCHAR* pch)
{
    HRESULT hr;

    if (usOperand <= 255)
    {
        *pch = (UCHAR)usOperand;
        hr = S_OK;
    }
    else
    {
        *pch = '\0';
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// USHORT -> CHAR conversion
//
__forceinline
HRESULT
UShortToChar(
    __inn USHORT usOperand,
    __outt __deref_out_range(==,usOperand) CHAR* pch)
{
#ifdef _CHAR_UNSIGNED
    return UShortToUChar(usOperand, (UCHAR*)pch);
#else
    return UShortToInt8(usOperand, (INT8*)pch);
#endif // _CHAR_UNSIGNED
}

//
// USHORT -> UINT8 conversion
//
__inline
HRESULT
UShortToUInt8(
    __inn USHORT usOperand,
    __outt __deref_out_range(==,usOperand) UINT8* pui8Result)
{
    HRESULT hr;
    
    if (usOperand <= UINT8_MAX)
    {
        *pui8Result = (UINT8)usOperand;
        hr = S_OK;
    }
    else
    {
        *pui8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// USHORT -> BYTE conversion
//
#define UShortToByte    UShortToUInt8

//
// USHORT -> SHORT conversion
//
__inline
HRESULT
UShortToShort(
    __inn USHORT usOperand,
    __outt __deref_out_range(==,usOperand) SHORT* psResult)
{
    HRESULT hr;

    if (usOperand <= SHORT_MAX)
    {
        *psResult = (SHORT)usOperand;
        hr = S_OK;
    }
    else
    {
        *psResult = SHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// USHORT -> INT16 conversion
//
#define UShortToInt16   UShortToShort

//
// UINT16 -> CHAR conversion
//
#define UInt16ToChar    UShortToChar

//
// UINT16 -> INT8 conversion
//
#define UInt16ToInt8    UShortToInt8

//
// UINT16 -> UCHAR conversion
//
#define UInt16ToUChar   UShortToUChar

//
// UINT16 -> UINT8 conversion
//
#define UInt16ToUInt8   UShortToUInt8

//
// UINT16 -> BYTE conversion
//
#define UInt16ToByte    UShortToUInt8

//
// UINT16 -> SHORT conversion
//
#define UInt16ToShort   UShortToShort

//
// UINT16 -> INT16 conversion
//
#define UInt16ToInt16   UShortToShort

//
// WORD -> INT8 conversion
//
#define WordToInt8  UShortToInt8

//
// WORD -> CHAR conversion
//
#define WordToChar  UShortToChar

//
// WORD -> UCHAR conversion
//
#define WordToUChar UShortToUChar

//
// WORD -> UINT8 conversion
//
#define WordToUInt8 UShortToUInt8

//
// WORD -> BYTE conversion
//
#define WordToByte  UShortToUInt8

//
// WORD -> SHORT conversion
//
#define WordToShort UShortToShort

//
// WORD -> INT16 conversion
//
#define WordToInt16 UShortToShort

//
// INT -> INT8 conversion
//
__inline
HRESULT
IntToInt8(
    __inn INT iOperand,
    __outt __deref_out_range(==,iOperand) INT8* pi8Result)
{
    HRESULT hr;
    
    if ((iOperand >= INT8_MIN) && (iOperand <= INT8_MAX))
    {
        *pi8Result = (INT8)iOperand;
        hr = S_OK;
    }
    else
    {
        *pi8Result = INT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// INT -> UCHAR conversion
//
__inline
HRESULT
IntToUChar(
    __inn INT iOperand,
    __outt __deref_out_range(==,iOperand) UCHAR* pch)
{
    HRESULT hr;

    if ((iOperand >= 0) && (iOperand <= 255))
    {
        *pch = (UCHAR)iOperand;
        hr = S_OK;
    }
    else
    {
        *pch = '\0';
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// INT -> CHAR conversion
//
__forceinline
HRESULT
IntToChar(
    __inn INT iOperand,
    __outt __deref_out_range(==,iOperand) CHAR* pch)
{
#ifdef _CHAR_UNSIGNED
    return IntToUChar(iOperand, (UCHAR*)pch);
#else
    return IntToInt8(iOperand, (INT8*)pch);
#endif // _CHAR_UNSIGNED
}

//
// INT -> BYTE conversion
//
#define IntToByte   IntToUInt8

//
// INT -> UINT8 conversion
//
__inline
HRESULT
IntToUInt8(
    __inn INT iOperand,
    __outt __deref_out_range(==,iOperand) UINT8* pui8Result)
{
    HRESULT hr;
    
    if ((iOperand >= 0) && (iOperand <= UINT8_MAX))
    {
        *pui8Result = (UINT8)iOperand;
        hr = S_OK;
    }
    else
    {
        *pui8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// INT -> SHORT conversion
//
__inline
HRESULT
IntToShort(
    __inn INT iOperand,
    __outt __deref_out_range(==,iOperand) SHORT* psResult)
{
    HRESULT hr;

    if ((iOperand >= SHORT_MIN) && (iOperand <= SHORT_MAX))
    {
        *psResult = (SHORT)iOperand;
        hr = S_OK;
    }
    else
    {
        *psResult = SHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// INT -> INT16 conversion
//
#define IntToInt16  IntToShort

//
// INT -> USHORT conversion
//
__inline
HRESULT
IntToUShort(
    __inn INT iOperand,
    __outt __deref_out_range(==,iOperand) USHORT* pusResult)
{
    HRESULT hr;

    if ((iOperand >= 0) && (iOperand <= USHORT_MAX))
    {
        *pusResult = (USHORT)iOperand;
        hr = S_OK;
    }
    else
    {
        *pusResult = USHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// INT -> UINT16 conversion
//
#define IntToUInt16  IntToUShort

//
// INT -> WORD conversion
//
#define IntToWord   IntToUShort

//
// INT -> UINT conversion
//
__inline
HRESULT
IntToUInt(
    __inn INT iOperand,
    __outt __deref_out_range(==,iOperand) UINT* puResult)
{
    HRESULT hr;

    if (iOperand >= 0)
    {
        *puResult = (UINT)iOperand;
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// INT -> UINT_PTR conversion
//
#ifdef _WIN64
#define IntToUIntPtr    IntToULongLong
#else
#define IntToUIntPtr    IntToUInt
#endif

//
// INT -> ULONG conversion
//
__inline
HRESULT
IntToULong(
    __inn INT iOperand,
    __outt __deref_out_range(==,iOperand) ULONG* pulResult)
{
    HRESULT hr;

    if (iOperand >= 0)
    {
        *pulResult = (ULONG)iOperand;
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// INT -> ULONG_PTR conversion
//
#ifdef _WIN64
#define IntToULongPtr   IntToULongLong
#else
#define IntToULongPtr   IntToULong
#endif

//
// INT -> DWORD conversion
//
#define IntToDWord  IntToULong

//
// INT -> DWORD_PTR conversion
//
#define IntToDWordPtr   IntToULongPtr

//
// INT -> ULONGLONG conversion
//
__inline
HRESULT
IntToULongLong(
    __inn INT iOperand,
    __outt __deref_out_range(==,iOperand) ULONGLONG* pullResult)
{
    HRESULT hr;

    if (iOperand >= 0)
    {
        *pullResult = (ULONGLONG)iOperand;
        hr = S_OK;
    }
    else
    {
        *pullResult = ULONGLONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// INT -> DWORDLONG conversion
//
#define IntToDWordLong  IntToULongLong

//
// INT -> ULONG64 conversion
//
#define IntToULong64    IntToULongLong

//
// INT -> DWORD64 conversion
//
#define IntToDWord64    IntToULongLong

//
// INT -> UINT64 conversion
//
#define IntToUInt64 IntToULongLong

//
// INT -> size_t conversion
//
#define IntToSizeT  IntToUIntPtr

//
// INT -> SIZE_T conversion
//
#define IntToSIZET  IntToULongPtr

//
// INT32 -> CHAR conversion
//
#define Int32ToChar IntToChar

//
// INT32 -> INT328 conversion
//
#define Int32ToInt8 IntToInt8

//
// INT32 -> UCHAR conversion
//
#define Int32ToUChar    IntToUChar

//
// INT32 -> BYTE conversion
//
#define Int32ToByte IntToUInt8

//
// INT32 -> UINT8 conversion
//
#define Int32ToUInt8    IntToUInt8

//
// INT32 -> SHORT conversion
//
#define Int32ToShort    IntToShort

//
// INT32 -> INT16 conversion
//
#define Int32ToInt16    IntToShort

//
// INT32 -> USHORT conversion
//
#define Int32ToUShort   IntToUShort

//
// INT32 -> UINT16 conversion
//
#define Int32ToUInt16   IntToUShort

//
// INT32 -> WORD conversion
//
#define Int32ToWord IntToUShort

//
// INT32 -> UINT conversion
//
#define Int32ToUInt IntToUInt

//
// INT32 -> UINT32 conversion
//
#define Int32ToUInt32   IntToUInt

//
// INT32 -> UINT_PTR conversion
//
#define Int32ToUIntPtr  IntToUIntPtr

//
// INT32 -> ULONG conversion
//
#define Int32ToULong    IntToULong

//
// INT32 -> ULONG_PTR conversion
//
#define Int32ToULongPtr IntToULongPtr

//
// INT32 -> DWORD conversion
//
#define Int32ToDWord    IntToULong

//
// INT32 -> DWORD_PTR conversion
//
#define Int32ToDWordPtr IntToULongPtr

//
// INT32 -> ULONGLONG conversion
//
#define Int32ToULongLong    IntToULongLong

//
// INT32 -> DWORDLONG conversion
//
#define Int32ToDWordLong    IntToULongLong

//
// INT32 -> ULONG64 conversion
//
#define Int32ToULong64  IntToULongLong

//
// INT32 -> DWORD64 conversion
//
#define Int32ToDWord64  IntToULongLong

//
// INT32 -> UINT64 conversion
//
#define Int32ToUInt64   IntToULongLong

//
// INT32 -> size_t conversion
//
#define Int32ToSizeT    IntToUIntPtr

//
// INT32 -> SIZE_T conversion
//
#define Int32ToSIZET    IntToULongPtr

//
// INT_PTR -> INT8 conversion
//
__inline
HRESULT
IntPtrToInt8(
    __inn INT_PTR iOperand,
    __outt __deref_out_range(==,iOperand) INT8* pi8Result)
{
    HRESULT hr;
    
    if ((iOperand >= INT8_MIN) && (iOperand <= INT8_MAX))
    {
        *pi8Result = (INT8)iOperand;
        hr = S_OK;
    }
    else
    {
        *pi8Result = INT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// INT_PTR -> UCHAR conversion
//
__inline
HRESULT
IntPtrToUChar(
    __inn INT_PTR iOperand,
    __outt __deref_out_range(==,iOperand) UCHAR* pch)
{
    HRESULT hr;

    if ((iOperand >= 0) && (iOperand <= 255))
    {
        *pch = (UCHAR)iOperand;
        hr = S_OK;
    }
    else
    {
        *pch = '\0';
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// INT_PTR -> CHAR conversion
//
__forceinline
HRESULT
IntPtrToChar(
    __inn INT_PTR iOperand,
    __outt __deref_out_range(==,iOperand) CHAR* pch)
{
#ifdef _CHAR_UNSIGNED
    return IntPtrToUChar(iOperand, (UCHAR*)pch);
#else
    return IntPtrToInt8(iOperand, (INT8*)pch);
#endif // _CHAR_UNSIGNED
}

//
// INT_PTR -> UINT8 conversion
//
__inline
HRESULT
IntPtrToUInt8(
    __inn INT_PTR iOperand,
    __outt __deref_out_range(==,iOperand) UINT8* pui8Result)
{
    HRESULT hr;
    
    if ((iOperand >= 0) && (iOperand <= UINT8_MAX))
    {
        *pui8Result = (UINT8)iOperand;
        hr = S_OK;
    }
    else
    {
        *pui8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// INT_PTR -> BYTE conversion
//
#define IntPtrToByte    IntPtrToUInt8

//
// INT_PTR -> SHORT conversion
//
__inline
HRESULT
IntPtrToShort(
    __inn INT_PTR iOperand,
    __outt __deref_out_range(==,iOperand) SHORT* psResult)
{
    HRESULT hr;

    if ((iOperand >= SHORT_MIN) && (iOperand <= SHORT_MAX))
    {
        *psResult = (SHORT)iOperand;
        hr = S_OK;
    }
    else
    {
        *psResult = SHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// INT_PTR -> INT16 conversion
//
#define IntPtrToInt16   IntPtrToShort

//
// INT_PTR -> USHORT conversion
//
__inline
HRESULT
IntPtrToUShort(
    __inn INT_PTR iOperand,
    __outt __deref_out_range(==,iOperand) USHORT* pusResult)
{
    HRESULT hr;

    if ((iOperand >= 0) && (iOperand <= USHORT_MAX))
    {
        *pusResult = (USHORT)iOperand;
        hr = S_OK;
    }
    else
    {
        *pusResult = USHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// INT_PTR -> UINT16 conversion
//
#define IntPtrToUInt16  IntPtrToUShort

//
// INT_PTR -> WORD conversion
//
#define IntPtrToWord    IntPtrToUShort

//
// INT_PTR -> INT conversion
//
#ifdef _WIN64
#define IntPtrToInt LongLongToInt
#else
__inline
HRESULT
IntPtrToInt(
    __inn INT_PTR iOperand,
    __outt __deref_out_range(==,iOperand) INT* piResult)
{
    *piResult = (INT)iOperand;
    return S_OK;
}
#endif

//
// INT_PTR -> INT32 conversion
//
#define IntPtrToInt32   IntPtrToInt

//
// INT_PTR -> UINT conversion
//
#ifdef _WIN64
#define IntPtrToUInt    LongLongToUInt
#else
__inline
HRESULT
IntPtrToUInt(
    __inn INT_PTR iOperand,
    __outt __deref_out_range(==,iOperand) UINT* puResult)
{
    HRESULT hr;

    if (iOperand >= 0)
    {
        *puResult = (UINT)iOperand;
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}
#endif

//
// INT_PTR -> UINT32 conversion
//
#define IntPtrToUInt32  IntPtrToUInt

//
// INT_PTR -> UINT_PTR conversion
//
#ifdef _WIN64
#define IntPtrToUIntPtr LongLongToULongLong
#else
__inline
HRESULT
IntPtrToUIntPtr(
    __inn INT_PTR iOperand,
    __outt __deref_out_range(==,iOperand) UINT_PTR* puResult)
{
    HRESULT hr;

    if (iOperand >= 0)
    {
        *puResult = (UINT_PTR)iOperand;
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}
#endif

//
// INT_PTR -> LONG conversion
//
#ifdef _WIN64
#define IntPtrToLong    LongLongToLong
#else
__inline
HRESULT
IntPtrToLong(
    __inn INT_PTR iOperand,
    __outt __deref_out_range(==,iOperand) LONG* plResult)
{
    *plResult = (LONG)iOperand;
    return S_OK;
}
#endif

//
// INT_PTR -> LONG_PTR conversion
//
__inline
HRESULT
IntPtrToLongPtr(
    __inn INT_PTR iOperand,
    __outt __deref_out_range(==,iOperand) LONG_PTR* plResult)
{
    *plResult = (LONG_PTR)iOperand;
    return S_OK;
}

//
// INT_PTR -> ULONG conversion
//
#ifdef _WIN64
#define IntPtrToULong   LongLongToULong
#else
__inline
HRESULT
IntPtrToULong(
    __inn INT_PTR iOperand,
    __outt __deref_out_range(==,iOperand) ULONG* pulResult)
{
    HRESULT hr;

    if (iOperand >= 0)
    {
        *pulResult = (ULONG)iOperand;
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}
#endif

//
// INT_PTR -> ULONG_PTR conversion
//
#ifdef _WIN64
#define IntPtrToULongPtr    LongLongToULongLong
#else
__inline
HRESULT
IntPtrToULongPtr(
    __inn INT_PTR iOperand,
    __outt __deref_out_range(==,iOperand) ULONG_PTR* pulResult)
{
    HRESULT hr;

    if (iOperand >= 0)
    {
        *pulResult = (ULONG_PTR)iOperand;
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}
#endif

//
// INT_PTR -> DWORD conversion
//
#define IntPtrToDWord   IntPtrToULong

//    
// INT_PTR -> DWORD_PTR conversion
//
#define IntPtrToDWordPtr    IntPtrToULongPtr

//
// INT_PTR -> ULONGLONG conversion
//
#ifdef _WIN64
#define IntPtrToULongLong   LongLongToULongLong
#else
__inline
HRESULT
IntPtrToULongLong(
    __inn INT_PTR iOperand,
    __outt __deref_out_range(==,iOperand) ULONGLONG* pullResult)
{
    HRESULT hr;

    if (iOperand >= 0)
    {
        *pullResult = (ULONGLONG)iOperand;
        hr = S_OK;
    }
    else
    {
        *pullResult = ULONGLONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}   
#endif

//
// INT_PTR -> DWORDLONG conversion
//
#define IntPtrToDWordLong   IntPtrToULongLong

//
// INT_PTR -> ULONG64 conversion
//
#define IntPtrToULong64 IntPtrToULongLong

//
// INT_PTR -> DWORD64 conversion
//
#define IntPtrToDWord64 IntPtrToULongLong

//
// INT_PTR -> UINT64 conversion
//
#define IntPtrToUInt64  IntPtrToULongLong

//
// INT_PTR -> size_t conversion
//
#define IntPtrToSizeT   IntPtrToUIntPtr

//
// INT_PTR -> SIZE_T conversion
//
#define IntPtrToSIZET   IntPtrToULongPtr

//
// UINT -> INT8 conversion
//
__inline
HRESULT
UIntToInt8(
    __inn UINT uOperand,
    __outt __deref_out_range(==,uOperand) INT8* pi8Result)
{
    HRESULT hr;
    
    if (uOperand <= INT8_MAX)
    {
        *pi8Result = (INT8)uOperand;
        hr = S_OK;
    }
    else
    {
        *pi8Result = INT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}    

//
// UINT -> UCHAR conversion
//
__inline
HRESULT
UIntToUChar(
    __inn UINT uOperand,
    __outt __deref_out_range(==,uOperand) UCHAR* pch)
{
    HRESULT hr;

    if (uOperand <= 255)
    {
        *pch = (UCHAR)uOperand;
        hr = S_OK;
    }
    else
    {
        *pch = '\0';
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// UINT -> CHAR conversion
//
__forceinline
HRESULT
UIntToChar(
    __inn UINT uOperand,
    __outt __deref_out_range(==,uOperand) CHAR* pch)
{
#ifdef _CHAR_UNSIGNED
    return UIntToUChar(uOperand, (UCHAR*)pch);
#else
    return UIntToInt8(uOperand, (INT8*)pch);
#endif
}

//
// UINT -> UINT8 conversion
//
__inline
HRESULT
UIntToUInt8(
    __inn UINT uOperand,
    __outt __deref_out_range(==,uOperand) UINT8* pui8Result)
{
    HRESULT hr;
    
    if (uOperand <= UINT8_MAX)
    {
        *pui8Result = (UINT8)uOperand;
        hr = S_OK;
    }
    else
    {
        *pui8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}    
    
//
// UINT -> BYTE conversion
//
#define UIntToByte   UIntToUInt8

//
// UINT -> SHORT conversion
//
__inline
HRESULT
UIntToShort(
    __inn UINT uOperand,
    __outt __deref_out_range(==,uOperand) SHORT* psResult)
{
    HRESULT hr;

    if (uOperand <= SHORT_MAX)
    {
        *psResult = (SHORT)uOperand;
        hr = S_OK;
    }
    else
    {
        *psResult = SHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// UINT -> INT16 conversion
//
#define UIntToInt16 UIntToShort

//
// UINT -> USHORT conversion
//
__inline
HRESULT
UIntToUShort(
    __inn UINT uOperand,
    __outt __deref_out_range(==,uOperand) USHORT* pusResult)
{
    HRESULT hr;

    if (uOperand <= USHORT_MAX)
    {
        *pusResult = (USHORT)uOperand;
        hr = S_OK;
    }
    else
    {
        *pusResult = USHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// UINT -> UINT16 conversion
//
#define UIntToUInt16    UIntToUShort

//
// UINT -> WORD conversion
//
#define UIntToWord  UIntToUShort

//
// UINT -> INT conversion
//
__inline
HRESULT
UIntToInt(
    __inn UINT uOperand,
    __outt __deref_out_range(==,uOperand) INT* piResult)
{
    HRESULT hr;

    if (uOperand <= INT_MAX)
    {
        *piResult = (INT)uOperand;
        hr = S_OK;
    }
    else
    {
        *piResult = INT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// UINT -> INT32 conversion
//
#define UIntToInt32 UIntToInt

//
// UINT -> INT_PTR conversion
//
#ifdef _WIN64
__inline
HRESULT
UIntToIntPtr(
    __inn UINT uOperand,
    __outt __deref_out_range(==,uOperand) INT_PTR* piResult)
{
    *piResult = uOperand;
    return S_OK;
}
#else
#define UIntToIntPtr    UIntToInt
#endif

//
// UINT -> LONG conversion
//
__inline
HRESULT
UIntToLong(
    __inn UINT uOperand,
    __outt __deref_out_range(==,uOperand) LONG* plResult)
{
    HRESULT hr;

    if (uOperand <= INT32_MAX)
    {
        *plResult = (LONG)uOperand;
        hr = S_OK;
    }
    else
    {
        *plResult = LONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// UINT -> LONG_PTR conversion
//
#ifdef _WIN64
__inline
HRESULT
UIntToLongPtr(
    __inn UINT uOperand,
    __outt __deref_out_range(==,uOperand) LONG_PTR* plResult)
{
    *plResult = uOperand;
    return S_OK;
}
#else
#define UIntToLongPtr   UIntToLong
#endif

//
// UINT -> ptrdiff_t conversion
//
#define UIntToPtrdiffT  UIntToIntPtr

//
// UINT -> SSIZE_T conversion
//
#define UIntToSSIZET    UIntToLongPtr

//
// UINT32 -> CHAR conversion
//
#define UInt32ToChar    UIntToChar

//
// UINT32 -> INT8 conversion
//
#define UInt32ToInt8    UIntToInt8

//
// UINT32 -> UCHAR conversion
//
#define UInt32ToUChar   UIntToUChar

//
// UINT32 -> UINT8 conversion
//
#define UInt32ToUInt8   UIntToUInt8

//
// UINT32 -> BYTE conversion
//
#define UInt32ToByte    UInt32ToUInt8

//
// UINT32 -> SHORT conversion
//
#define UInt32ToShort   UIntToShort

//
// UINT32 -> INT16 conversion
//
#define UInt32ToInt16   UIntToShort

//
// UINT32 -> USHORT conversion
//
#define UInt32ToUShort  UIntToUShort

//
// UINT32 -> UINT16 conversion
//
#define UInt32ToUInt16  UIntToUShort

//
// UINT32 -> WORD conversion
//
#define UInt32ToWord    UIntToUShort

//
// UINT32 -> INT conversion
//
#define UInt32ToInt UIntToInt

//
// UINT32 -> INT_PTR conversion
//
#define UInt32ToIntPtr  UIntToIntPtr

//
// UINT32 -> INT32 conversion
//
#define UInt32ToInt32   UIntToInt

//
// UINT32 -> LONG conversion
//
#define UInt32ToLong    UIntToLong

//
// UINT32 -> LONG_PTR conversion
//
#define UInt32ToLongPtr UIntToLongPtr

//
// UINT32 -> ptrdiff_t conversion
//
#define UInt32ToPtrdiffT    UIntToPtrdiffT

//
// UINT32 -> SSIZE_T conversion
//
#define UInt32ToSSIZET  UIntToSSIZET

//
// UINT_PTR -> INT8 conversion
//
__inline
HRESULT
UIntPtrToInt8(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) INT8* pi8Result)
{
    HRESULT hr;
    
    if (uOperand <= INT8_MAX)
    {
        *pi8Result = (INT8)uOperand;
        hr = S_OK;
    }
    else
    {
        *pi8Result = INT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// UINT_PTR -> UCHAR conversion
//
__inline
HRESULT
UIntPtrToUChar(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) UCHAR* pch)
{
    HRESULT hr;

    if (uOperand <= 255)
    {
        *pch = (UCHAR)uOperand;
        hr = S_OK;
    }
    else
    {
        *pch = '\0';
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// UINT_PTR -> CHAR conversion
//
__forceinline
HRESULT
UIntPtrToChar(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) CHAR* pch)
{
#ifdef _CHAR_UNSIGNED
    return UIntPtrToUChar(uOperand, (UCHAR*)pch);
#else
    return UIntPtrToInt8(uOperand, (INT8*)pch);
#endif
}

//
// UINT_PTR -> UINT8 conversion
//
__inline
HRESULT
UIntPtrToUInt8(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) UINT8* pu8Result)
{
    HRESULT hr;
    
    if (uOperand <= UINT8_MAX)
    {
        *pu8Result = (UINT8)uOperand;
        hr = S_OK;
    }
    else
    {
        *pu8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// UINT_PTR -> BYTE conversion
//
#define UIntPtrToByte   UIntPtrToUInt8

//
// UINT_PTR -> SHORT conversion
//
__inline
HRESULT
UIntPtrToShort(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) SHORT* psResult)
{
    HRESULT hr;

    if (uOperand <= SHORT_MAX)
    {
        *psResult = (SHORT)uOperand;
        hr = S_OK;
    }
    else
    {
        *psResult = SHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr; 
}

//
// UINT_PTR -> INT16 conversion
//
__inline
HRESULT
UIntPtrToInt16(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) INT16* pi16Result)
{
    HRESULT hr;
    
    if (uOperand <= INT16_MAX)
    {
        *pi16Result = (INT16)uOperand;
        hr = S_OK;
    }
    else
    {
        *pi16Result = INT16_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// UINT_PTR -> USHORT conversion
//
__inline
HRESULT
UIntPtrToUShort(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) USHORT* pusResult)
{
    HRESULT hr;

    if (uOperand <= USHORT_MAX)
    {
        *pusResult = (USHORT)uOperand;
        hr = S_OK;
    }
    else
    {
        *pusResult = USHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// UINT_PTR -> UINT16 conversion
//
__inline
HRESULT
UIntPtrToUInt16(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) UINT16* pu16Result)
{
    HRESULT hr;
    
    if (uOperand <= UINT16_MAX)
    {
        *pu16Result = (UINT16)uOperand;
        hr = S_OK;
    }
    else
    {
        *pu16Result = UINT16_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// UINT_PTR -> WORD conversion
//
#define UIntPtrToWord   UIntPtrToUShort

//
// UINT_PTR -> INT conversion
//
__inline
HRESULT
UIntPtrToInt(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) INT* piResult)
{
    HRESULT hr;

    if (uOperand <= INT_MAX)
    {
        *piResult = (INT)uOperand;
        hr = S_OK;
    }
    else
    {
        *piResult = INT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// UINT_PTR -> INT32 conversion
//
#define UIntPtrToInt32  UIntPtrToInt

//
// UINT_PTR -> INT_PTR conversion
//
__inline
HRESULT
UIntPtrToIntPtr(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) INT_PTR* piResult)
{
    HRESULT hr;

    if (uOperand <= INT_PTR_MAX)
    {
        *piResult = (INT_PTR)uOperand;
        hr = S_OK;
    }
    else
    {
        *piResult = INT_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// UINT_PTR -> UINT conversion
//
#ifdef _WIN64
#define UIntPtrToUInt   ULongLongToUInt
#else
__inline
HRESULT
UIntPtrToUInt(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) UINT* puResult)
{
    *puResult = (UINT)uOperand;
    return S_OK;
}
#endif

//
// UINT_PTR -> UINT32 conversion
//
#define UIntPtrToUInt32 UIntPtrToUInt

//
// UINT_PTR -> LONG conversion
//
__inline
HRESULT
UIntPtrToLong(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) LONG* plResult)
{
    HRESULT hr;

    if (uOperand <= INT32_MAX)
    {
        *plResult = (LONG)uOperand;
        hr = S_OK;
    }
    else
    {
        *plResult = LONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// UINT_PTR -> LONG_PTR conversion
//
__inline
HRESULT
UIntPtrToLongPtr(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) LONG_PTR* plResult)
{
    HRESULT hr;

    if (uOperand <= LONG_PTR_MAX)
    {
        *plResult = (LONG_PTR)uOperand;
        hr = S_OK;
    }
    else
    {
        *plResult = LONG_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// UINT_PTR -> ULONG conversion
//
#ifdef _WIN64
#define UIntPtrToULong  ULongLongToULong
#else
__inline
HRESULT
UIntPtrToULong(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) ULONG* pulResult)
{
    *pulResult = (ULONG)uOperand;
    return S_OK;
}
#endif

//
// UINT_PTR -> DWORD conversion
//
#define UIntPtrToDWord  UIntPtrToULong

//
// UINT_PTR -> LONGLONG conversion
//
#ifdef _WIN64
#define UIntPtrToLongLong   ULongLongToLongLong
#else
__inline
HRESULT
UIntPtrToLongLong(
    __inn UINT_PTR uOperand,
    __outt __deref_out_range(==,uOperand) LONGLONG* pllResult)
{
    *pllResult = (LONGLONG)uOperand;
    return S_OK;
}
#endif

//
// UINT_PTR -> LONG64 conversion
//
#define UIntPtrToLong64 UIntPtrToLongLong

//
// UINT_PTR -> INT64 conversion
//
#define UIntPtrToInt64  UIntPtrToLongLong

//
// UINT_PTR -> ptrdiff_t conversion
//
#define UIntPtrToPtrdiffT   UIntPtrToIntPtr

//
// UINT_PTR -> SSIZE_T conversion
//
#define UIntPtrToSSIZET UIntPtrToLongPtr

//
// LONG -> INT8 conversion
//
__inline
HRESULT
LongToInt8(
    __inn LONG lOperand,
    __outt __deref_out_range(==,lOperand) INT8* pi8Result)
{
    HRESULT hr;
    
    if ((lOperand >= INT8_MIN) && (lOperand <= INT8_MAX))
    {
        *pi8Result = (INT8)lOperand;
        hr = S_OK;
    }
    else
    {
        *pi8Result = INT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONG -> UCHAR conversion
//
__inline
HRESULT
LongToUChar(
    __inn LONG lOperand,
    __outt __deref_out_range(==,lOperand) UCHAR* pch)
{
    HRESULT hr;

    if ((lOperand >= 0) && (lOperand <= 255))
    {
        *pch = (UCHAR)lOperand;
        hr = S_OK;
    }
    else
    {
        *pch = '\0';
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// LONG -> CHAR conversion
//
__forceinline
HRESULT
LongToChar(
    __inn LONG lOperand,
    __outt __deref_out_range(==,lOperand) CHAR* pch)
{
#ifdef _CHAR_UNSIGNED
    return LongToUChar(lOperand, (UCHAR*)pch);
#else
    return LongToInt8(lOperand, (INT8*)pch);
#endif
}

//
// LONG -> UINT8 conversion
//
__inline
HRESULT
LongToUInt8(
    __inn LONG lOperand,
    __outt __deref_out_range(==,lOperand) UINT8* pui8Result)
{
    HRESULT hr;
    
    if ((lOperand >= 0) && (lOperand <= UINT8_MAX))
    {
        *pui8Result = (UINT8)lOperand;
        hr = S_OK;
    }
    else
    {
        *pui8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONG -> BYTE conversion
//
#define LongToByte  LongToUInt8

//
// LONG -> SHORT conversion
//
__inline
HRESULT
LongToShort(
    __inn LONG lOperand,
    __outt __deref_out_range(==,lOperand) SHORT* psResult)
{
    HRESULT hr;
     
    if ((lOperand >= SHORT_MIN) && (lOperand <= SHORT_MAX))
    {
       *psResult = (SHORT)lOperand;
       hr = S_OK;
    }
    else
    {
        *psResult = SHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
     
    return hr;
}

//
// LONG -> INT16 conversion
//
#define LongToInt16 LongToShort

//
// LONG -> USHORT conversion
//
__inline
HRESULT
LongToUShort(
    __inn LONG lOperand,
    __outt __deref_out_range(==,lOperand) USHORT* pusResult)
{
    HRESULT hr;
    
    if ((lOperand >= 0) && (lOperand <= USHORT_MAX))
    {
        *pusResult = (USHORT)lOperand;
        hr = S_OK;
    }
    else
    {
        *pusResult = USHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONG -> UINT16 conversion
//
#define LongToUInt16    LongToUShort

//   
// LONG -> WORD conversion
//
#define LongToWord  LongToUShort

//
// LONG -> INT conversion
//
__inline
HRESULT
LongToInt(
    __inn LONG lOperand,
    __outt __deref_out_range(==,lOperand) INT* piResult)
{
    C_ASSERT(sizeof(INT) == sizeof(LONG));
    *piResult = (INT)lOperand;
    return S_OK;
}

//
// LONG -> INT32 conversion
//
#define LongToInt32 LongToInt

//
// LONG -> INT_PTR conversion
//
#ifdef _WIN64
__inline
HRESULT
LongToIntPtr(
    __inn LONG lOperand,
    __outt __deref_out_range(==,lOperand) INT_PTR* piResult)
{
    *piResult = lOperand;
    return S_OK;
}
#else
#define LongToIntPtr    LongToInt
#endif

//
// LONG -> UINT conversion
//
__inline
HRESULT
LongToUInt(
    __inn LONG lOperand,
    __outt __deref_out_range(==,lOperand) UINT* puResult)
{
    HRESULT hr;
    
    if (lOperand >= 0)
    {
        *puResult = (UINT)lOperand;
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONG -> UINT32 conversion
//
#define LongToUInt32    LongToUInt

//
// LONG -> UINT_PTR conversion
//
#ifdef _WIN64
__inline
HRESULT
LongToUIntPtr(
    __inn LONG lOperand,
    __outt __deref_out_range(==,lOperand) UINT_PTR* puResult)
{
    HRESULT hr;
    
    if (lOperand >= 0)
    {
        *puResult = (UINT_PTR)lOperand;
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}
#else
#define LongToUIntPtr   LongToUInt
#endif

//
// LONG -> ULONG conversion
//
__inline
HRESULT
LongToULong(
    __inn LONG lOperand,
    __outt __deref_out_range(==,lOperand) ULONG* pulResult)
{
    HRESULT hr;
    
    if (lOperand >= 0)
    {
        *pulResult = (ULONG)lOperand;
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONG -> ULONG_PTR conversion
//
#ifdef _WIN64
__inline
HRESULT
LongToULongPtr(
    __inn LONG lOperand,
    __outt __deref_out_range(==,lOperand) ULONG_PTR* pulResult)
{
    HRESULT hr;
    
    if (lOperand >= 0)
    {
        *pulResult = (ULONG_PTR)lOperand;
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}
#else
#define LongToULongPtr  LongToULong
#endif

//
// LONG -> DWORD conversion
//
#define LongToDWord LongToULong

//
// LONG -> DWORD_PTR conversion
//
#define LongToDWordPtr  LongToULongPtr

//
// LONG -> ULONGLONG conversion
//
__inline
HRESULT
LongToULongLong(
    __inn LONG lOperand,
    __outt __deref_out_range(==,lOperand) ULONGLONG* pullResult)
{
    HRESULT hr;
    
    if (lOperand >= 0)
    {
        *pullResult = (ULONGLONG)lOperand;
        hr = S_OK;
    }
    else
    {
        *pullResult = ULONGLONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONG -> DWORDLONG conversion
//
#define LongToDWordLong LongToULongLong

//
// LONG -> ULONG64 conversion
//
#define LongToULong64   LongToULongLong

//
// LONG -> DWORD64 conversion
//
#define LongToDWord64   LongToULongLong

//
// LONG -> UINT64 conversion
//
#define LongToUInt64    LongToULongLong

//
// LONG -> ptrdiff_t conversion
//
#define LongToPtrdiffT  LongToIntPtr

//
// LONG -> size_t conversion
//
#define LongToSizeT LongToUIntPtr

//
// LONG -> SIZE_T conversion
//
#define LongToSIZET LongToULongPtr

//
// LONG_PTR -> INT8 conversion
//
__inline
HRESULT
LongPtrToInt8(
    __inn LONG_PTR lOperand,
    __outt __deref_out_range(==,lOperand) INT8* pi8Result)
{
    HRESULT hr;
    
    if ((lOperand >= INT8_MIN) && (lOperand <= INT8_MAX))
    {
        *pi8Result = (INT8)lOperand;
        hr = S_OK;
    }
    else
    {
        *pi8Result = INT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONG_PTR -> UCHAR conversion
//
__inline
HRESULT
LongPtrToUChar(
    __inn LONG_PTR lOperand,
    __outt __deref_out_range(==,lOperand) UCHAR* pch)
{
    HRESULT hr;
    
    if ((lOperand >= 0) && (lOperand <= 255))
    {
        *pch = (UCHAR)lOperand;
        hr = S_OK;
    }
    else
    {
        *pch = '\0';
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONG_PTR -> CHAR conversion
//
__forceinline
HRESULT
LongPtrToChar(
    __inn LONG_PTR lOperand,
    __outt __deref_out_range(==,lOperand) CHAR* pch)
{
#ifdef _CHAR_UNSIGNED
    return LongPtrToUChar(lOperand, (UCHAR*)pch);
#else
    return LongPtrToInt8(lOperand, (INT8*)pch);
#endif
}

//
// LONG_PTR -> UINT8 conversion
//
__inline
HRESULT
LongPtrToUInt8(
    __inn LONG_PTR lOperand,
    __outt __deref_out_range(==,lOperand) UINT8* pui8Result)
{
    HRESULT hr;
    
    if ((lOperand >= 0) && (lOperand <= UINT8_MAX))
    {
        *pui8Result = (UINT8)lOperand;
        hr = S_OK;
    }
    else
    {
        *pui8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONG_PTR -> BYTE conversion
//
#define LongPtrToByte   LongPtrToUInt8

//
// LONG_PTR -> SHORT conversion
//
__inline
HRESULT
LongPtrToShort(
    __inn LONG_PTR lOperand,
    __outt __deref_out_range(==,lOperand) SHORT* psResult)
{
    HRESULT hr;
    
    if ((lOperand >= SHORT_MIN) && (lOperand <= SHORT_MAX))
    {
        *psResult = (SHORT)lOperand;
        hr = S_OK;
    }
    else
    {
        *psResult = SHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}    

//
// LONG_PTR -> INT16 conversion
//
#define LongPtrToInt16  LongPtrToShort

//
// LONG_PTR -> USHORT conversion
//
__inline
HRESULT
LongPtrToUShort(
    __inn LONG_PTR lOperand,
    __outt __deref_out_range(==,lOperand) USHORT* pusResult)
{
    HRESULT hr;
    
    if ((lOperand >= 0) && (lOperand <= USHORT_MAX))
    {
        *pusResult = (USHORT)lOperand;
        hr = S_OK;
    }
    else
    {
        *pusResult = USHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONG_PTR -> UINT16 conversion
//
#define LongPtrToUInt16 LongPtrToUShort

//
// LONG_PTR -> WORD conversion
//
#define LongPtrToWord   LongPtrToUShort

//
// LONG_PTR -> INT conversion
//
#ifdef _WIN64
#define LongPtrToInt    LongLongToInt
#else
__inline
HRESULT
LongPtrToInt(
    __inn LONG_PTR lOperand,
    __outt __deref_out_range(==,lOperand) INT* piResult)
{
    C_ASSERT(sizeof(INT) == sizeof(LONG_PTR));
    *piResult = (INT)lOperand;
    return S_OK;
}   
#endif

//
// LONG_PTR -> INT32 conversion
//
#define LongPtrToInt32  LongPtrToInt

//
// LONG_PTR -> INT_PTR conversion
//
__inline
HRESULT
LongPtrToIntPtr(
    __inn LONG_PTR lOperand,
    __outt __deref_out_range(==,lOperand) INT_PTR* piResult)
{
    C_ASSERT(sizeof(LONG_PTR) == sizeof(INT_PTR));
    *piResult = (INT_PTR)lOperand;
    return S_OK;
}

//
// LONG_PTR -> UINT conversion
//
#ifdef _WIN64
#define LongPtrToUInt   LongLongToUInt
#else
__inline
HRESULT
LongPtrToUInt(
    __inn LONG_PTR lOperand,
    __outt __deref_out_range(==,lOperand) UINT* puResult)
{
    HRESULT hr;
    
    if (lOperand >= 0)
    {
        *puResult = (UINT)lOperand;
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}
#endif

//
// LONG_PTR -> UINT32 conversion
//
#define LongPtrToUInt32 LongPtrToUInt

//
// LONG_PTR -> UINT_PTR conversion
//
__inline
HRESULT
LongPtrToUIntPtr(
    __inn LONG_PTR lOperand,
    __outt __deref_out_range(==,lOperand) UINT_PTR* puResult)
{
    HRESULT hr;
    
    if (lOperand >= 0)
    {
        *puResult = (UINT_PTR)lOperand;
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONG_PTR -> LONG conversion
//
#ifdef _WIN64
#define LongPtrToLong   LongLongToLong
#else
__inline
HRESULT
LongPtrToLong(
    __inn LONG_PTR lOperand,
    __outt __deref_out_range(==,lOperand) LONG* plResult)
{
    *plResult = (LONG)lOperand;
    return S_OK;
}
#endif

//    
// LONG_PTR -> ULONG conversion
//
#ifdef _WIN64
#define LongPtrToULong  LongLongToULong
#else
__inline
HRESULT
LongPtrToULong(
    __inn LONG_PTR lOperand,
    __outt __deref_out_range(==,lOperand) ULONG* pulResult)
{
    HRESULT hr;
    
    if (lOperand >= 0)
    {
        *pulResult = (ULONG)lOperand;
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}
#endif

//
// LONG_PTR -> ULONG_PTR conversion
//
__inline
HRESULT
LongPtrToULongPtr(
    __inn LONG_PTR lOperand,
    __outt __deref_out_range(==,lOperand) ULONG_PTR* pulResult)
{
    HRESULT hr;
    
    if (lOperand >= 0)
    {
        *pulResult = (ULONG_PTR)lOperand;
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONG_PTR -> DWORD conversion
//
#define LongPtrToDWord  LongPtrToULong

//
// LONG_PTR -> DWORD_PTR conversion
//
#define LongPtrToDWordPtr   LongPtrToULongPtr 

//
// LONG_PTR -> ULONGLONG conversion
//
__inline
HRESULT
LongPtrToULongLong(
    __inn LONG_PTR lOperand,
    __outt __deref_out_range(==,lOperand) ULONGLONG* pullResult)
{
    HRESULT hr;
    
    if (lOperand >= 0)
    {
        *pullResult = (ULONGLONG)lOperand;
        hr = S_OK;
    }
    else
    {
        *pullResult = ULONGLONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONG_PTR -> DWORDLONG conversion
//
#define LongPtrToDWordLong  LongPtrToULongLong

//
// LONG_PTR -> ULONG64 conversion
//
#define LongPtrToULong64    LongPtrToULongLong

//
// LONG_PTR -> DWORD64 conversion
//
#define LongPtrToDWord64    LongPtrToULongLong

//
// LONG_PTR -> UINT64 conversion
//
#define LongPtrToUInt64 LongPtrToULongLong

//
// LONG_PTR -> size_t conversion
//
#define LongPtrToSizeT  LongPtrToUIntPtr

//
// LONG_PTR -> SIZE_T conversion
//
#define LongPtrToSIZET  LongPtrToULongPtr

//
// ULONG -> INT8 conversion
//
__inline
HRESULT
ULongToInt8(
    __inn ULONG ulOperand,
    __outt __deref_out_range(==,ulOperand) INT8* pi8Result)
{
    HRESULT hr;
    
    if (ulOperand <= INT8_MAX)
    {
        *pi8Result = (INT8)ulOperand;
        hr = S_OK;
    }
    else
    {
        *pi8Result = INT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONG -> UCHAR conversion
//
__inline
HRESULT
ULongToUChar(
    __inn ULONG ulOperand,
    __outt __deref_out_range(==,ulOperand) UCHAR* pch)
{
    HRESULT hr;

    if (ulOperand <= 255)
    {
        *pch = (UCHAR)ulOperand;
        hr = S_OK;
    }
    else
    {
        *pch = '\0';
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// ULONG -> CHAR conversion
//
__forceinline
HRESULT
ULongToChar(
    __inn ULONG ulOperand,
    __outt __deref_out_range(==,ulOperand) CHAR* pch)
{
#ifdef _CHAR_UNSIGNED
    return ULongToUChar(ulOperand, (UCHAR*)pch);
#else
    return ULongToInt8(ulOperand, (INT8*)pch);
#endif
}

//
// ULONG -> UINT8 conversion
//
__inline
HRESULT
ULongToUInt8(
    __inn ULONG ulOperand,
    __outt __deref_out_range(==,ulOperand) UINT8* pui8Result)
{
    HRESULT hr;
    
    if (ulOperand <= UINT8_MAX)
    {
        *pui8Result = (UINT8)ulOperand;
        hr = S_OK;
    }
    else
    {
        *pui8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}    

//
// ULONG -> BYTE conversion
//
#define ULongToByte ULongToUInt8

//
// ULONG -> SHORT conversion
//
__inline
HRESULT
ULongToShort(
    __inn ULONG ulOperand,
    __outt __deref_out_range(==,ulOperand) SHORT* psResult)
{
    HRESULT hr;

    if (ulOperand <= SHORT_MAX)
    {
        *psResult = (SHORT)ulOperand;
        hr = S_OK;
    }
    else
    {
        *psResult = SHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// ULONG -> INT16 conversion
//
#define ULongToInt16    ULongToShort

//
// ULONG -> USHORT conversion
//
__inline
HRESULT
ULongToUShort(
    __inn ULONG ulOperand,
    __outt __deref_out_range(==,ulOperand) USHORT* pusResult)
{
    HRESULT hr;

    if (ulOperand <= USHORT_MAX)
    {
        *pusResult = (USHORT)ulOperand;
        hr = S_OK;
    }
    else
    {
        *pusResult = USHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// ULONG -> UINT16 conversion
//
#define ULongToUInt16   ULongToUShort

//
// ULONG -> WORD conversion
//
#define ULongToWord ULongToUShort

//
// ULONG -> INT conversion
//
__inline
HRESULT
ULongToInt(
    __inn ULONG ulOperand,
    __outt __deref_out_range(==,ulOperand) INT* piResult)
{
    HRESULT hr;
    
    if (ulOperand <= INT_MAX)
    {
        *piResult = (INT)ulOperand;
        hr = S_OK;
    }
    else
    {
        *piResult = INT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONG -> INT32 conversion
//
#define ULongToInt32    ULongToInt

//
// ULONG -> INT_PTR conversion
//
#ifdef _WIN64
__inline
HRESULT
ULongToIntPtr(
    __inn ULONG ulOperand,
    __outt __deref_out_range(==,ulOperand) INT_PTR* piResult)
{
    *piResult = (INT_PTR)ulOperand;
    return S_OK;
}
#else
#define ULongToIntPtr   ULongToInt
#endif

//
// ULONG -> UINT conversion
//
__inline
HRESULT
ULongToUInt(
    __inn ULONG ulOperand,
    __outt __deref_out_range(==,ulOperand) UINT* puResult)
{
    //C_ASSERT(sizeof(ULONG) == sizeof(UINT));
    *puResult = (UINT)ulOperand;    
    return S_OK;
}

//
// ULONG -> UINT32 conversion
//
#define ULongToUInt32   ULongToUInt

//
// ULONG -> UINT_PTR conversion
//
#ifdef _WIN64
__inline
HRESULT
ULongToUIntPtr(
    __inn ULONG ulOperand,
    __outt __deref_out_range(==,ulOperand) UINT_PTR* puiResult)
{
    C_ASSERT(sizeof(UINT_PTR) > sizeof(ULONG));
    *puiResult = (UINT_PTR)ulOperand;
    return S_OK;
}
#else
#define ULongToUIntPtr  ULongToUInt
#endif

//
// ULONG -> LONG conversion
//
__inline
HRESULT
ULongToLong(
    __inn ULONG ulOperand,
    __outt __deref_out_range(==,ulOperand) LONG* plResult)
{
    HRESULT hr;
    
    if (ulOperand <= INT32_MAX)
    {
        *plResult = (LONG)ulOperand;
        hr = S_OK;
    }
    else
    {
        *plResult = LONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONG -> LONG_PTR conversion
//
#ifdef _WIN64
__inline
HRESULT
ULongToLongPtr(
    __inn ULONG ulOperand,
    __outt __deref_out_range(==,ulOperand) LONG_PTR* plResult)
{
    C_ASSERT(sizeof(LONG_PTR) > sizeof(ULONG));
    *plResult = (LONG_PTR)ulOperand;
    return S_OK;
}
#else
#define ULongToLongPtr  ULongToLong
#endif

//
// ULONG -> ptrdiff_t conversion
//
#define ULongToPtrdiffT ULongToIntPtr

//
// ULONG -> SSIZE_T conversion
//
#define ULongToSSIZET   ULongToLongPtr

//
// ULONG_PTR -> INT8 conversion
//
__inline
HRESULT
ULongPtrToInt8(
    __inn ULONG_PTR ulOperand,
    __outt __deref_out_range(==,ulOperand) INT8* pi8Result)
{
    HRESULT hr;
    
    if (ulOperand <= INT8_MAX)
    {
        *pi8Result = (INT8)ulOperand;
        hr = S_OK;
    }
    else
    {
        *pi8Result = INT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONG_PTR -> UCHAR conversion
//
__inline
HRESULT
ULongPtrToUChar(
    __inn ULONG_PTR ulOperand,
    __outt __deref_out_range(==,ulOperand) UCHAR* pch)
{
    HRESULT hr;

    if (ulOperand <= 255)
    {
        *pch = (UCHAR)ulOperand;
        hr = S_OK;
    }
    else
    {
        *pch = '\0';
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// ULONG_PTR -> CHAR conversion
//
__forceinline
HRESULT
ULongPtrToChar(
    __inn ULONG_PTR ulOperand,
    __outt __deref_out_range(==,ulOperand) CHAR* pch)
{
#ifdef _CHAR_UNSIGNED
    return ULongPtrToUChar(ulOperand, (UCHAR*)pch);
#else
    return ULongPtrToInt8(ulOperand, (INT8*)pch);
#endif
}

//
// ULONG_PTR -> UINT8 conversion
//
__inline
HRESULT
ULongPtrToUInt8(
    __inn ULONG_PTR ulOperand,
    __outt __deref_out_range(==,ulOperand) UINT8* pui8Result)
{
    HRESULT hr;
    
    if (ulOperand <= UINT8_MAX)
    {
        *pui8Result = (UINT8)ulOperand;
        hr = S_OK;
    }
    else
    {
        *pui8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//    
// ULONG_PTR -> BYTE conversion
//
#define ULongPtrToByte  ULongPtrToUInt8

//
// ULONG_PTR -> SHORT conversion
//
__inline
HRESULT
ULongPtrToShort(
    __inn ULONG_PTR ulOperand,
    __outt __deref_out_range(==,ulOperand) SHORT* psResult)
{
    HRESULT hr;

    if (ulOperand <= SHORT_MAX)
    {
        *psResult = (SHORT)ulOperand;
        hr = S_OK;
    }
    else
    {
        *psResult = SHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr; 
}

//
// ULONG_PTR -> INT16 conversion
//
#define ULongPtrToInt16 ULongPtrToShort

//
// ULONG_PTR -> USHORT conversion
//
__inline
HRESULT
ULongPtrToUShort(
    __inn ULONG_PTR ulOperand,
    __outt __deref_out_range(==,ulOperand) USHORT* pusResult)
{
    HRESULT hr;

    if (ulOperand <= USHORT_MAX)
    {
        *pusResult = (USHORT)ulOperand;
        hr = S_OK;
    }
    else
    {
        *pusResult = USHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// ULONG_PTR -> UINT16 conversion
//
#define ULongPtrToUInt16    ULongPtrToUShort

//
// ULONG_PTR -> WORD conversion
//
#define ULongPtrToWord  ULongPtrToUShort

//
// ULONG_PTR -> INT conversion
//
__inline
HRESULT
ULongPtrToInt(
    __inn ULONG_PTR ulOperand,
    __outt __deref_out_range(==,ulOperand) INT* piResult)
{
    HRESULT hr;
    
    if (ulOperand <= INT_MAX)
    {
        *piResult = (INT)ulOperand;
        hr = S_OK;
    }
    else
    {
        *piResult = INT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONG_PTR -> INT32 conversion
//
#define ULongPtrToInt32 ULongPtrToInt

//
// ULONG_PTR -> INT_PTR conversion
//
__inline
HRESULT
ULongPtrToIntPtr(
    __inn ULONG_PTR ulOperand,
    __outt __deref_out_range(==,ulOperand) INT_PTR* piResult)
{
    HRESULT hr;
    
    if (ulOperand <= INT_PTR_MAX)
    {
        *piResult = (INT_PTR)ulOperand;
        hr = S_OK;
    }
    else
    {
        *piResult = INT_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONG_PTR -> UINT conversion
//
#ifdef _WIN64
#define ULongPtrToUInt  ULongLongToUInt
#else
__inline
HRESULT
ULongPtrToUInt(
    __inn ULONG_PTR ulOperand,
    __outt __deref_out_range(==,ulOperand) UINT* puResult)
{
    C_ASSERT(sizeof(ULONG_PTR) == sizeof(UINT));
    *puResult = (UINT)ulOperand;    
    return S_OK;
}
#endif

//
// ULONG_PTR -> UINT32 conversion
//
#define ULongPtrToUInt32    ULongPtrToUInt

//
// ULONG_PTR -> UINT_PTR conversion
//
__inline
HRESULT
ULongPtrToUIntPtr(
    __inn ULONG_PTR ulOperand,
    __outt __deref_out_range(==,ulOperand) UINT_PTR* puResult)
{
    *puResult = (UINT_PTR)ulOperand;
    return S_OK;
}

//
// ULONG_PTR -> LONG conversion
//
__inline
HRESULT
ULongPtrToLong(
    __inn ULONG_PTR ulOperand,
    __outt __deref_out_range(==,ulOperand) LONG* plResult)
{
    HRESULT hr;
    
    if (ulOperand <= INT32_MAX)
    {
        *plResult = (LONG)ulOperand;
        hr = S_OK;
    }
    else
    {
        *plResult = LONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//        
// ULONG_PTR -> LONG_PTR conversion
//
__inline
HRESULT
ULongPtrToLongPtr(
    __inn ULONG_PTR ulOperand,
    __outt __deref_out_range(==,ulOperand) LONG_PTR* plResult)
{
    HRESULT hr;
    
    if (ulOperand <= LONG_PTR_MAX)
    {
        *plResult = (LONG_PTR)ulOperand;
        hr = S_OK;
    }
    else
    {
        *plResult = LONG_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONG_PTR -> ULONG conversion
//
#ifdef _WIN64
#define ULongPtrToULong ULongLongToULong
#else
__inline
HRESULT
ULongPtrToULong(
    __inn ULONG_PTR ulOperand,
    __outt __deref_out_range(==,ulOperand) ULONG* pulResult)
{
    *pulResult = (ULONG)ulOperand;
    return S_OK;
}
#endif    

//
// ULONG_PTR -> DWORD conversion
//
#define ULongPtrToDWord ULongPtrToULong

//
// ULONG_PTR -> LONGLONG conversion
//
#ifdef _WIN64
#define ULongPtrToLongLong  ULongLongToLongLong
#else
__inline
HRESULT
ULongPtrToLongLong(
    __inn ULONG_PTR ulOperand,
    __outt __deref_out_range(==,ulOperand) LONGLONG* pllResult)
{
    *pllResult = (LONGLONG)ulOperand;
    return S_OK;
}
#endif

//
// ULONG_PTR -> LONG64 conversion
//
#define ULongPtrToLong64    ULongPtrToLongLong

//
// ULONG_PTR -> INT64
//
#define ULongPtrToInt64 ULongPtrToLongLong

//
// ULONG_PTR -> ptrdiff_t conversion
//
#define ULongPtrToPtrdiffT  ULongPtrToIntPtr

//
// ULONG_PTR -> SSIZE_T conversion
//
#define ULongPtrToSSIZET    ULongPtrToLongPtr

//
// DWORD -> INT8 conversion
//
#define DWordToInt8 ULongToInt8

//
// DWORD -> CHAR conversion
//
#define DWordToChar ULongToChar

//
// DWORD -> UCHAR conversion
//
#define DWordToUChar    ULongToUChar

//
// DWORD -> UINT8 conversion
//
#define DWordToUInt8    ULongToUInt8

//
// DWORD -> BYTE conversion
//
#define DWordToByte ULongToUInt8

//
// DWORD -> SHORT conversion
//
#define DWordToShort    ULongToShort

//
// DWORD -> INT16 conversion
//
#define DWordToInt16    ULongToShort

//
// DWORD -> USHORT conversion
//
#define DWordToUShort   ULongToUShort

//
// DWORD -> UINT16 conversion
//
#define DWordToUInt16   ULongToUShort

//
// DWORD -> WORD conversion
//
#define DWordToWord ULongToUShort

//
// DWORD -> INT conversion
//
#define DWordToInt  ULongToInt

//
// DWORD -> INT32 conversion
//
#define DWordToInt32    ULongToInt

//
// DWORD -> INT_PTR conversion
//
#define DWordToIntPtr   ULongToIntPtr

//
// DWORD -> UINT conversion
//
#define DWordToUInt ULongToUInt

//
// DWORD -> UINT32 conversion
//
#define DWordToUInt32   ULongToUInt

//
// DWORD -> UINT_PTR conversion
//
#define DWordToUIntPtr  ULongToUIntPtr

//
// DWORD -> LONG conversion
//
#define DWordToLong ULongToLong

//
// DWORD -> LONG_PTR conversion
//
#define DWordToLongPtr  ULongToLongPtr

//
// DWORD -> ptrdiff_t conversion
//
#define DWordToPtrdiffT ULongToIntPtr

//
// DWORD -> SSIZE_T conversion
//
#define DWordToSSIZET   ULongToLongPtr

//
// DWORD_PTR -> INT8 conversion
//
#define DWordPtrToInt8  ULongPtrToInt8

//
// DWORD_PTR -> UCHAR conversion
//
#define DWordPtrToUChar ULongPtrToUChar

//
// DWORD_PTR -> CHAR conversion
//
#define DWordPtrToChar  ULongPtrToChar

//
// DWORD_PTR -> UINT8 conversion
//
#define DWordPtrToUInt8 ULongPtrToUInt8

//
// DWORD_PTR -> BYTE conversion
//
#define DWordPtrToByte  ULongPtrToUInt8

//
// DWORD_PTR -> SHORT conversion
//
#define DWordPtrToShort ULongPtrToShort

//
// DWORD_PTR -> INT16 conversion
//
#define DWordPtrToInt16 ULongPtrToShort

//
// DWORD_PTR -> USHORT conversion
//
#define DWordPtrToUShort    ULongPtrToUShort

//
// DWORD_PTR -> UINT16 conversion
//
#define DWordPtrToUInt16    ULongPtrToUShort

//
// DWORD_PTR -> WORD conversion
//
#define DWordPtrToWord  ULongPtrToUShort

//
// DWORD_PTR -> INT conversion
//
#define DWordPtrToInt   ULongPtrToInt

//
// DWORD_PTR -> INT32 conversion
//
#define DWordPtrToInt32 ULongPtrToInt

//
// DWORD_PTR -> INT_PTR conversion
//
#define DWordPtrToIntPtr    ULongPtrToIntPtr

//
// DWORD_PTR -> UINT conversion
//
#define DWordPtrToUInt  ULongPtrToUInt

//
// DWORD_PTR -> UINT32 conversion
//
#define DWordPtrToUInt32    ULongPtrToUInt

//
// DWODR_PTR -> UINT_PTR conversion
//
#define DWordPtrToUIntPtr   ULongPtrToUIntPtr

//
// DWORD_PTR -> LONG conversion
//
#define DWordPtrToLong  ULongPtrToLong

//
// DWORD_PTR -> LONG_PTR conversion
//
#define DWordPtrToLongPtr   ULongPtrToLongPtr

//
// DWORD_PTR -> ULONG conversion
//
#define DWordPtrToULong ULongPtrToULong

//
// DWORD_PTR -> DWORD conversion
//
#define DWordPtrToDWord ULongPtrToULong

//
// DWORD_PTR -> LONGLONG conversion
//
#define DWordPtrToLongLong  ULongPtrToLongLong

//
// DWORD_PTR -> LONG64 conversion
//
#define DWordPtrToLong64    ULongPtrToLongLong

//
// DWORD_PTR -> INT64 conversion
//
#define DWordPtrToInt64 ULongPtrToLongLong

//
// DWORD_PTR -> ptrdiff_t conversion
//
#define DWordPtrToPtrdiffT  ULongPtrToIntPtr

//
// DWORD_PTR -> SSIZE_T conversion
//
#define DWordPtrToSSIZET    ULongPtrToLongPtr

//
// LONGLONG -> INT8 conversion
//
__inline
HRESULT
LongLongToInt8(
    __inn LONGLONG llOperand,
    __outt __deref_out_range(==,llOperand) INT8* pi8Result)
{
    HRESULT hr;
    
    if ((llOperand >= INT8_MIN) && (llOperand <= INT8_MAX))
    {
        *pi8Result = (INT8)llOperand;
        hr = S_OK;
    }
    else
    {
        *pi8Result = INT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }

    return hr;
}

//
// LONGLONG -> UCHAR conversion
//
__inline
HRESULT
LongLongToUChar(
    __inn LONGLONG llOperand,
    __outt __deref_out_range(==,llOperand) UCHAR* pch)
{
    HRESULT hr;

    if ((llOperand >= 0) && (llOperand <= 255))
    {
        *pch = (UCHAR)llOperand;
        hr = S_OK;
    }
    else
    {
        *pch = '\0';
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONGLONG -> CHAR conversion
//
__forceinline
HRESULT
LongLongToChar(
    __inn LONGLONG llOperand,
    __outt __deref_out_range(==,llOperand) CHAR* pch)
{
#ifdef _CHAR_UNSIGNED
    return LongLongToUChar(llOperand, (UCHAR*)pch);
#else
    return LongLongToInt8(llOperand, (INT8*)pch);
#endif
}

//
// LONGLONG -> UINT8 conversion
//
__inline
HRESULT
LongLongToUInt8(
    __inn LONGLONG llOperand,
    __outt __deref_out_range(==,llOperand) UINT8* pu8Result)
{
    HRESULT hr;
    
    if ((llOperand >= 0) && (llOperand <= UINT8_MAX))
    {
        *pu8Result = (UINT8)llOperand;
        hr = S_OK;
    }
    else
    {
        *pu8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONGLONG -> BYTE conversion
//
#define LongLongToByte  LongLongToUInt8

//
// LONGLONG -> SHORT conversion
//
__inline
HRESULT
LongLongToShort(
    __inn LONGLONG llOperand,
    __outt __deref_out_range(==,llOperand) SHORT* psResult)
{
    HRESULT hr;
    
    if ((llOperand >= SHORT_MIN) && (llOperand <= SHORT_MAX))
    {
        *psResult = (SHORT)llOperand;
        hr = S_OK;
    }
    else
    {
        *psResult = SHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONGLONG -> INT16 conversion
//
#define LongLongToInt16 LongLongToShort

//
// LONGLONG -> USHORT conversion
//
__inline
HRESULT
LongLongToUShort(
    __inn LONGLONG llOperand,
    __outt __deref_out_range(==,llOperand) USHORT* pusResult)
{
    HRESULT hr;
    
    if ((llOperand >= 0) && (llOperand <= USHORT_MAX))
    {
        *pusResult = (USHORT)llOperand;
        hr = S_OK;
    }
    else
    {
        *pusResult = USHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONGLONG -> UINT16 conversion
//
#define LongLongToUInt16    LongLongToUShort

//
// LONGLONG -> WORD conversion
//
#define LongLongToWord  LongLongToUShort

//
// LONGLONG -> INT conversion
//
__inline
HRESULT
LongLongToInt(
    __inn LONGLONG llOperand,
    __outt __deref_out_range(==,llOperand) INT* piResult)
{
    HRESULT hr;
    
    if ((llOperand >= INT_MIN) && (llOperand <= INT_MAX))
    {
        *piResult = (INT)llOperand;
        hr = S_OK;
    }
    else
    {
        *piResult = INT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// LONGLONG -> INT32 conversion
//
#define LongLongToInt32 LongLongToInt

//
// LONGLONG -> INT_PTR conversion
//
#ifdef _WIN64
__inline
HRESULT
LongLongToIntPtr(
    __inn LONGLONG llOperand,
    __outt __deref_out_range(==,llOperand) INT_PTR* piResult)
{
    *piResult = llOperand;
    return S_OK;
}
#else
#define LongLongToIntPtr   LongLongToInt
#endif

//
// LONGLONG -> UINT conversion
//
__inline
HRESULT
LongLongToUInt(
    __inn LONGLONG llOperand,
    __outt __deref_out_range(==,llOperand) UINT* puResult)
{
    HRESULT hr;
    
    if ((llOperand >= 0) && (llOperand <= UINT_MAX))
    {
        *puResult = (UINT)llOperand;
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;    
}

//
// LONGLONG -> UINT32 conversion
//
#define LongLongToUInt32    LongLongToUInt

//
// LONGLONG -> UINT_PTR conversion
//
#ifdef _WIN64
#define LongLongToUIntPtr  LongLongToULongLong
#else
#define LongLongToUIntPtr  LongLongToUInt
#endif

//
// LONGLONG -> LONG conversion
//
__inline
HRESULT
LongLongToLong(
    __inn LONGLONG llOperand,
    __outt __deref_out_range(==,llOperand) LONG* plResult)
{
    HRESULT hr;
    
    if ((llOperand >= INT32_MIN) && (llOperand <= INT32_MAX))
    {
        *plResult = (LONG)llOperand;
        hr = S_OK;
    }
    else
    {
        *plResult = LONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;    
}

//
// LONGLONG -> LONG_PTR conversion
//
#ifdef _WIN64
__inline
HRESULT
LongLongToLongPtr(
    __inn LONGLONG llOperand,
    __outt __deref_out_range(==,llOperand) LONG_PTR* plResult)
{
    *plResult = (LONG_PTR)llOperand;
    return S_OK;
}    
#else
#define LongLongToLongPtr  LongLongToLong
#endif

//
// LONGLONG -> ULONG conversion
//
__inline
HRESULT
LongLongToULong(
    __inn LONGLONG llOperand,
    __outt __deref_out_range(==,llOperand) ULONG* pulResult)
{
    HRESULT hr;
    
    if ((llOperand >= 0) && (llOperand <= (LONGLONG)(ULONGLONG)UINT32_MAX))
    {
        *pulResult = (ULONG)llOperand;
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;    
}

//
// LONGLONG -> ULONG_PTR conversion
//
#ifdef _WIN64
#define LongLongToULongPtr LongLongToULongLong
#else
#define LongLongToULongPtr LongLongToULong
#endif

//
// LONGLONG -> DWORD conversion
//
#define LongLongToDWord    LongLongToULong

//
// LONGLONG -> DWORD_PTR conversion
//
#define LongLongToDWordPtr LongLongToULongPtr

//
// LONGLONG -> ULONGLONG conversion
//
__inline
HRESULT
LongLongToULongLong(
    __inn LONGLONG llOperand,
    __outt __deref_out_range(==,llOperand) ULONGLONG* pullResult)
{
    HRESULT hr;
    
    if (llOperand >= 0)
    {
        *pullResult = (ULONGLONG)llOperand;
        hr = S_OK;
    }
    else
    {
        *pullResult = ULONGLONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr; 
}

//
// LONGLONG -> DWORDLONG conversion
//
#define LongLongToDWordLong LongLongToULongLong

//
// LONGLONG -> ULONG64 conversion
//
#define LongLongToULong64   LongLongToULongLong

//
// LONGLONG -> DWORD64 conversion
//
#define LongLongToDWord64   LongLongToULongLong

//
// LONGLONG -> UINT64 conversion
//
#define LongLongToUInt64    LongLongToULongLong

//
// LONGLONG -> ptrdiff_t conversion
//
#define LongLongToPtrdiffT LongLongToIntPtr

//
// LONGLONG -> size_t conversion
//
#define LongLongToSizeT    LongLongToUIntPtr

//
// LONGLONG -> SSIZE_T conversion
//
#define LongLongToSSIZET   LongLongToLongPtr

//
// LONGLONG -> SIZE_T conversion
//
#define LongLongToSIZET    LongLongToULongPtr

//
// LONG64 -> CHAR conversion
//
#define Long64ToChar    LongLongToChar

//
// LONG64 -> INT8 conversion
//
#define Long64ToInt8    LongLongToInt8

//
// LONG64 -> UCHAR conversion
//
#define Long64ToUChar   LongLongToUChar

//
// LONG64 -> UINT8 conversion
//
#define Long64ToUInt8   LongLongToUInt8

//
// LONG64 -> BYTE conversion
//
#define Long64ToByte    LongLongToUInt8

//
// LONG64 -> SHORT conversion
//
#define Long64ToShort   LongLongToShort

//
// LONG64 -> INT16 conversion
//
#define Long64ToInt16   LongLongToShort

//
// LONG64 -> USHORT conversion
//
#define Long64ToUShort  LongLongToUShort

//
// LONG64 -> UINT16 conversion
//
#define Long64ToUInt16  LongLongToUShort

//
// LONG64 -> WORD conversion
//
#define Long64ToWord    LongLongToUShort

//
// LONG64 -> INT conversion
//
#define Long64ToInt LongLongToInt

//
// LONG64 -> INT32 conversion
//
#define Long64ToInt32   LongLongToInt

//
// LONG64 -> INT_PTR conversion
//
#define Long64ToIntPtr  LongLongToIntPtr

//
// LONG64 -> UINT conversion
//
#define Long64ToUInt    LongLongToUInt

//
// LONG64 -> UINT32 conversion
//
#define Long64ToUInt32  LongLongToUInt

//
// LONG64 -> UINT_PTR conversion
//
#define Long64ToUIntPtr LongLongToUIntPtr

//
// LONG64 -> LONG conversion
//
#define Long64ToLong    LongLongToLong

//
// LONG64 -> LONG_PTR conversion
//
#define Long64ToLongPtr LongLongToLongPtr

//
// LONG64 -> ULONG conversion
//
#define Long64ToULong   LongLongToULong

//
// LONG64 -> ULONG_PTR conversion
//
#define Long64ToULongPtr    LongLongToULongPtr

//
// LONG64 -> DWORD conversion
//
#define Long64ToDWord   LongLongToULong

//
// LONG64 -> DWORD_PTR conversion
//
#define Long64ToDWordPtr    LongLongToULongPtr  

//
// LONG64 -> ULONGLONG conversion
//
#define Long64ToULongLong   LongLongToULongLong

//
// LONG64 -> ptrdiff_t conversion
//
#define Long64ToPtrdiffT    LongLongToIntPtr

//
// LONG64 -> size_t conversion
//
#define Long64ToSizeT   LongLongToUIntPtr

//
// LONG64 -> SSIZE_T conversion
//
#define Long64ToSSIZET  LongLongToLongPtr

//
// LONG64 -> SIZE_T conversion
//
#define Long64ToSIZET   LongLongToULongPtr

//
// INT64 -> CHAR conversion
//
#define Int64ToChar LongLongToChar

//
// INT64 -> INT8 conversion
//
#define Int64ToInt8 LongLongToInt8

//
// INT64 -> UCHAR conversion
//
#define Int64ToUChar    LongLongToUChar

//
// INT64 -> UINT8 conversion
//
#define Int64ToUInt8    LongLongToUInt8

//
// INT64 -> BYTE conversion
//
#define Int64ToByte LongLongToUInt8

//
// INT64 -> SHORT conversion
//
#define Int64ToShort    LongLongToShort

//
// INT64 -> INT16 conversion
//
#define Int64ToInt16    LongLongToShort

//
// INT64 -> USHORT conversion
//
#define Int64ToUShort   LongLongToUShort

//
// INT64 -> UINT16 conversion
//
#define Int64ToUInt16   LongLongToUShort

//
// INT64 -> WORD conversion
//
#define Int64ToWord LongLongToUShort

//
// INT64 -> INT conversion
//
#define Int64ToInt  LongLongToInt

//
// INT64 -> INT32 conversion
//
#define Int64ToInt32    LongLongToInt

//
// INT64 -> INT_PTR conversion
//
#define Int64ToIntPtr   LongLongToIntPtr

//
// INT64 -> UINT conversion
//
#define Int64ToUInt LongLongToUInt

//
// INT64 -> UINT32 conversion
//
#define Int64ToUInt32   LongLongToUInt

//
// INT64 -> UINT_PTR conversion
//
#define Int64ToUIntPtr  LongLongToUIntPtr

//
// INT64 -> LONG conversion
//
#define Int64ToLong LongLongToLong

//
// INT64 -> LONG_PTR conversion
//
#define Int64ToLongPtr  LongLongToLongPtr

//
// INT64 -> ULONG conversion
//
#define Int64ToULong    LongLongToULong

//
// INT64 -> ULONG_PTR conversion
//
#define Int64ToULongPtr LongLongToULongPtr

//
// INT64 -> DWORD conversion
//
#define Int64ToDWord    LongLongToULong

//
// INT64 -> DWORD_PTR conversion
//
#define Int64ToDWordPtr LongLongToULongPtr

//
// INT64 -> ULONGLONG conversion
//
#define Int64ToULongLong    LongLongToULongLong

//
// INT64 -> DWORDLONG conversion
//
#define Int64ToDWordLong    LongLongToULongLong

//
// INT64 -> ULONG64 conversion
//
#define Int64ToULong64  LongLongToULongLong

//
// INT64 -> DWORD64 conversion
//
#define Int64ToDWord64  LongLongToULongLong

//
// INT64 -> UINT64 conversion
//
#define Int64ToUInt64   LongLongToULongLong

//
// INT64 -> ptrdiff_t conversion
//
#define Int64ToPtrdiffT LongLongToIntPtr

//
// INT64 -> size_t conversion
//
#define Int64ToSizeT    LongLongToUIntPtr

//
// INT64 -> SSIZE_T conversion
//
#define Int64ToSSIZET   LongLongToLongPtr

//
// INT64 -> SIZE_T conversion
//
#define Int64ToSIZET    LongLongToULongPtr

//
// ULONGLONG -> INT8 conversion
//
__inline
HRESULT
ULongLongToInt8(
    __inn ULONGLONG ullOperand,
    __outt __deref_out_range(==,ullOperand) INT8* pi8Result)
{
    HRESULT hr;
    
    if (ullOperand <= INT8_MAX)
    {
        *pi8Result = (INT8)ullOperand;
        hr = S_OK;
    }
    else
    {
        *pi8Result = INT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONGLONG -> UCHAR conversion
//
__inline
HRESULT
ULongLongToUChar(
    __inn ULONGLONG ullOperand,
    __outt __deref_out_range(==,ullOperand) UCHAR* pch)
{
    HRESULT hr;
    
    if (ullOperand <= 255)
    {
        *pch = (UCHAR)ullOperand;
        hr = S_OK;
    }
    else
    {
        *pch = '\0';
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONGLONG -> CHAR conversion
//
__forceinline
HRESULT
ULongLongToChar(
    __inn ULONGLONG ullOperand,
    __outt __deref_out_range(==,ullOperand) CHAR* pch)
{
#ifdef _CHAR_UNSIGNED
    return ULongLongToUChar(ullOperand, (UCHAR*)pch);
#else
    return ULongLongToInt8(ullOperand, (INT8*)pch);
#endif
}

//
// ULONGLONG -> UINT8 conversion
//
__inline
HRESULT
ULongLongToUInt8(
    __inn ULONGLONG ullOperand,
    __outt __deref_out_range(==,ullOperand) UINT8* pu8Result)
{
    HRESULT hr;
    
    if (ullOperand <= UINT8_MAX)
    {
        *pu8Result = (UINT8)ullOperand;
        hr = S_OK;
    }
    else
    {
        *pu8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONGLONG -> BYTE conversion
//
#define ULongLongToByte ULongLongToUInt8

//
// ULONGLONG -> SHORT conversion
//
__inline
HRESULT
ULongLongToShort(
    __inn ULONGLONG ullOperand,
    __outt __deref_out_range(==,ullOperand) SHORT* psResult)
{
    HRESULT hr;
    
    if (ullOperand <= SHORT_MAX)
    {
        *psResult = (SHORT)ullOperand;
        hr = S_OK;
    }
    else
    {
        *psResult = SHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONGLONG -> INT16 conversion
//
#define ULongLongToInt16    ULongLongToShort

//
// ULONGLONG -> USHORT conversion
//
__inline
HRESULT
ULongLongToUShort(
    __inn ULONGLONG ullOperand,
    __outt __deref_out_range(==,ullOperand) USHORT* pusResult)
{
    HRESULT hr;
    
    if (ullOperand <= USHORT_MAX)
    {
        *pusResult = (USHORT)ullOperand;
        hr = S_OK;
    }
    else
    {
        *pusResult = USHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONGLONG -> UINT16 conversion
//
#define ULongLongToUInt16   ULongLongToUShort

//
// ULONGLONG -> WORD conversion
//
#define ULongLongToWord ULongLongToUShort

//
// ULONGLONG -> INT conversion
//
__inline
HRESULT
ULongLongToInt(
    __inn ULONGLONG ullOperand,
    __outt __deref_out_range(==,ullOperand) INT* piResult)
{
    HRESULT hr;
    
    if (ullOperand <= INT_MAX)
    {
        *piResult = (INT)ullOperand;
        hr = S_OK;
    }
    else
    {
        *piResult = INT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONGLONG -> INT32 conversion
//
#define ULongLongToInt32    ULongLongToInt

//
// ULONGLONG -> INT_PTR conversion
//
#ifdef _WIN64
#define ULongLongToIntPtr   ULongLongToLongLong
#else
#define ULongLongToIntPtr   ULongLongToInt
#endif

//
// ULONGLONG -> UINT conversion
//
__inline
HRESULT
ULongLongToUInt(
    __inn ULONGLONG ullOperand,
    __outt __deref_out_range(==,ullOperand) UINT* puResult)
{
    HRESULT hr;
    
    if (ullOperand <= UINT_MAX)
    {
        *puResult = (UINT)ullOperand;
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONGLONG -> UINT32 conversion
//
#define ULongLongToUInt32   ULongLongToUInt

//
// ULONGLONG -> UINT_PTR conversion
//
#ifdef _WIN64
__inline
HRESULT
ULongLongToUIntPtr(
    __inn ULONGLONG ullOperand,
    __outt __deref_out_range(==,ullOperand) UINT_PTR* puResult)
{
    *puResult = ullOperand;
    return S_OK;
}
#else    
#define ULongLongToUIntPtr  ULongLongToUInt
#endif

//
// ULONGLONG -> LONG conversion
//
__inline
HRESULT
ULongLongToLong(
    __inn ULONGLONG ullOperand,
    __outt __deref_out_range(==,ullOperand) LONG* plResult)
{
    HRESULT hr;
    
    if (ullOperand <= INT32_MAX)
    {
        *plResult = (LONG)ullOperand;
        hr = S_OK;
    }
    else
    {
        *plResult = LONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONGLONG -> LONG_PTR conversion
//
__inline
HRESULT
ULongLongToLongPtr(
    __inn ULONGLONG ullOperand,
    __outt __deref_out_range(==,ullOperand) LONG_PTR* plResult)
{
    HRESULT hr;
    
    if (ullOperand <= LONG_PTR_MAX)
    {
        *plResult = (LONG_PTR)ullOperand;
        hr = S_OK;
    }
    else
    {
        *plResult = LONG_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONGLONG -> ULONG conversion
//
__inline
HRESULT
ULongLongToULong(
    __inn ULONGLONG ullOperand,
    __outt __deref_out_range(==,ullOperand) ULONG* pulResult)
{
    HRESULT hr;
    
    if (ullOperand <= UINT32_MAX)
    {
        *pulResult = (ULONG)ullOperand;
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONGLONG -> ULONG_PTR conversion
//
#ifdef _WIN64
__inline
HRESULT
ULongLongToULongPtr(
    __inn ULONGLONG ullOperand,
    __outt __deref_out_range(==,ullOperand) ULONG_PTR* pulResult)
{
    *pulResult = ullOperand;
    return S_OK;
}
#else
#define ULongLongToULongPtr ULongLongToULong
#endif

//
// ULONGLONG -> DWORD conversion
//
#define ULongLongToDWord    ULongLongToULong

//
// ULONGLONG -> DWORD_PTR conversion
//
#define ULongLongToDWordPtr ULongLongToULongPtr

//
// ULONGLONG -> LONGLONG conversion
//
__inline
HRESULT
ULongLongToLongLong(
    __inn ULONGLONG ullOperand,
    __outt __deref_out_range(==,ullOperand) LONGLONG* pllResult)
{
    HRESULT hr;
    
    if (ullOperand <= LONGLONG_MAX)
    {
        *pllResult = (LONGLONG)ullOperand;
        hr = S_OK;
    }
    else
    {
        *pllResult = LONGLONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONGLONG -> INT64 conversion
//
#define ULongLongToInt64    ULongLongToLongLong

//
// ULONGLONG -> LONG64 conversion
//
#define ULongLongToLong64   ULongLongToLongLong

//
// ULONGLONG -> ptrdiff_t conversion
//
#define ULongLongToPtrdiffT ULongLongToIntPtr

//
// ULONGLONG -> size_t conversion
//
#define ULongLongToSizeT    ULongLongToUIntPtr

//
// ULONGLONG -> SSIZE_T conversion
//
#define ULongLongToSSIZET   ULongLongToLongPtr

//
// ULONGLONG -> SIZE_T conversion
//
#define ULongLongToSIZET    ULongLongToULongPtr

//
// DWORDLONG -> CHAR conversion
//
#define DWordLongToChar ULongLongToChar

//
// DWORDLONG -> INT8 conversion
//
#define DWordLongToInt8 ULongLongToInt8

//
// DWORDLONG -> UCHAR conversion
//
#define DWordLongToUChar    ULongLongToUChar

//
// DWORDLONG -> UINT8 conversion
//
#define DWordLongToUInt8    ULongLongToUInt8

//
// DWORDLONG -> BYTE conversion
//
#define DWordLongToByte ULongLongToUInt8

//
// DWORDLONG -> SHORT conversion
//
#define DWordLongToShort    ULongLongToShort

//
// DWORDLONG -> INT16 conversion
//
#define DWordLongToInt16    ULongLongToShort

//
// DWORDLONG -> USHORT conversion
//
#define DWordLongToUShort   ULongLongToUShort

//
// DWORDLONG -> UINT16 conversion
//
#define DWordLongToUInt16   ULongLongToUShort

//
// DWORDLONG -> WORD conversion
//
#define DWordLongToWord ULongLongToUShort

//
// DWORDLONG -> INT conversion
//
#define DWordLongToInt  ULongLongToInt

//
// DWORDLONG -> INT32 conversion
//
#define DWordLongToInt32    ULongLongToInt

//
// DWORDLONG -> INT_PTR conversion
//
#define DWordLongToIntPtr   ULongLongToIntPtr

//
// DWORDLONG -> UINT conversion
//
#define DWordLongToUInt ULongLongToUInt

//
// DWORDLONG -> UINT32 conversion
//
#define DWordLongToUInt32   ULongLongToUInt

//
// DWORDLONG -> UINT_PTR conversion
//
#define DWordLongToUIntPtr  ULongLongToUIntPtr

//
// DWORDLONG -> LONG conversion
//
#define DWordLongToLong ULongLongToLong

//
// DWORDLONG -> LONG_PTR conversion
//
#define DWordLongToLongPtr  ULongLongToLongPtr

//
// DWORDLONG -> ULONG conversion
//
#define DWordLongToULong    ULongLongToULong

//
// DWORDLONG -> ULONG_PTR conversion
//
#define DWordLongToULongPtr ULongLongToULongPtr

//
// DWORDLONG -> DWORD conversion
//
#define DWordLongToDWord    ULongLongToULong

//
// DWORDLONG -> DWORD_PTR conversion
//
#define DWordLongToDWordPtr ULongLongToULongPtr

//
// DWORDLONG -> LONGLONG conversion
//
#define DWordLongToLongLong ULongLongToLongLong

//
// DWORDLONG -> LONG64 conversion
//
#define DWordLongToLong64   ULongLongToLongLong

//
// DWORDLONG -> INT64 conversion
//
#define DWordLongToInt64    ULongLongToLongLong

//
// DWORDLONG -> ptrdiff_t conversion
//
#define DWordLongToPtrdiffT ULongLongToIntPtr

//
// DWORDLONG -> size_t conversion
//
#define DWordLongToSizeT    ULongLongToUIntPtr

//
// DWORDLONG -> SSIZE_T conversion
//
#define DWordLongToSSIZET   ULongLongToLongPtr

//
// DWORDLONG -> SIZE_T conversion
//
#define DWordLongToSIZET    ULongLongToULongPtr

//
// ULONG64 -> CHAR conversion
//
#define ULong64ToChar   ULongLongToChar

//
// ULONG64 -> INT8 conversion
//
#define ULong64ToInt8   ULongLongToInt8

//
// ULONG64 -> UCHAR conversion
//
#define ULong64ToUChar  ULongLongToUChar

//
// ULONG64 -> UINT8 conversion
//
#define ULong64ToUInt8  ULongLongToUInt8

//
// ULONG64 -> BYTE conversion
//
#define ULong64ToByte   ULongLongToUInt8

//
// ULONG64 -> SHORT conversion
//
#define ULong64ToShort  ULongLongToShort

//
// ULONG64 -> INT16 conversion
//
#define ULong64ToInt16  ULongLongToShort

//
// ULONG64 -> USHORT conversion
//
#define ULong64ToUShort ULongLongToUShort

//
// ULONG64 -> UINT16 conversion
//
#define ULong64ToUInt16 ULongLongToUShort

//
// ULONG64 -> WORD conversion
//
#define ULong64ToWord   ULongLongToUShort

//
// ULONG64 -> INT conversion
//
#define ULong64ToInt    ULongLongToInt

//
// ULONG64 -> INT32 conversion
//
#define ULong64ToInt32  ULongLongToInt

//
// ULONG64 -> INT_PTR conversion
//
#define ULong64ToIntPtr ULongLongToIntPtr

//
// ULONG64 -> UINT conversion
//
#define ULong64ToUInt   ULongLongToUInt

//
// ULONG64 -> UINT32 conversion
//
#define ULong64ToUInt32 ULongLongToUInt

//
// ULONG64 -> UINT_PTR conversion
//
#define ULong64ToUIntPtr    ULongLongToUIntPtr

//
// ULONG64 -> LONG conversion
//
#define ULong64ToLong   ULongLongToLong

//
// ULONG64 -> LONG_PTR conversion
//
#define ULong64ToLongPtr    ULongLongToLongPtr

//
// ULONG64 -> ULONG conversion
//
#define ULong64ToULong  ULongLongToULong

//
// ULONG64 -> ULONG_PTR conversion
//
#define ULong64ToULongPtr   ULongLongToULongPtr

//
// ULONG64 -> DWORD conversion
//
#define ULong64ToDWord  ULongLongToULong

//
// ULONG64 -> DWORD_PTR conversion
//
#define ULong64ToDWordPtr   ULongLongToULongPtr

//
// ULONG64 -> LONGLONG conversion
//
#define ULong64ToLongLong   ULongLongToLongLong

//
// ULONG64 -> LONG64 conversion
//
#define ULong64ToLong64 ULongLongToLongLong

//
// ULONG64 -> INT64 conversion
//
#define ULong64ToInt64  ULongLongToLongLong

//
// ULONG64 -> ptrdiff_t conversion
//
#define ULong64ToPtrdiffT   ULongLongToIntPtr

//
// ULONG64 -> size_t conversion
//
#define ULong64ToSizeT  ULongLongToUIntPtr

//
// ULONG64 -> SSIZE_T conversion
//
#define ULong64ToSSIZET ULongLongToLongPtr

//
// ULONG64 -> SIZE_T conversion
//
#define ULong64ToSIZET  ULongLongToULongPtr

//
// DWORD64 -> CHAR conversion
//
#define DWord64ToChar   ULongLongToChar

//
// DWORD64 -> INT8 conversion
//
#define DWord64ToInt8   ULongLongToInt8

//
// DWORD64 -> UCHAR conversion
//
#define DWord64ToUChar  ULongLongToUChar

//
// DWORD64 -> UINT8 conversion
//
#define DWord64ToUInt8  ULongLongToUInt8

//
// DWORD64 -> BYTE conversion
//
#define DWord64ToByte   ULongLongToUInt8

//
// DWORD64 -> SHORT conversion
//
#define DWord64ToShort  ULongLongToShort

//
// DWORD64 -> INT16 conversion
//
#define DWord64ToInt16  ULongLongToShort

//
// DWORD64 -> USHORT conversion
//
#define DWord64ToUShort ULongLongToUShort

//
// DWORD64 -> UINT16 conversion
//
#define DWord64ToUInt16 ULongLongToUShort

//
// DWORD64 -> WORD conversion
//
#define DWord64ToWord   ULongLongToUShort

//
// DWORD64 -> INT conversion
//
#define DWord64ToInt    ULongLongToInt

//
// DWORD64 -> INT32 conversion
//
#define DWord64ToInt32  ULongLongToInt

//
// DWORD64 -> INT_PTR conversion
//
#define DWord64ToIntPtr ULongLongToIntPtr

//
// DWORD64 -> UINT conversion
//
#define DWord64ToUInt   ULongLongToUInt

//
// DWORD64 -> UINT32 conversion
//
#define DWord64ToUInt32 ULongLongToUInt

//
// DWORD64 -> UINT_PTR conversion
//
#define DWord64ToUIntPtr    ULongLongToUIntPtr

//
// DWORD64 -> LONG conversion
//
#define DWord64ToLong   ULongLongToLong

//
// DWORD64 -> LONG_PTR conversion
//
#define DWord64ToLongPtr    ULongLongToLongPtr

//
// DWORD64 -> ULONG conversion
//
#define DWord64ToULong  ULongLongToULong

//
// DWORD64 -> ULONG_PTR conversion
//
#define DWord64ToULongPtr   ULongLongToULongPtr

//
// DWORD64 -> DWORD conversion
//
#define DWord64ToDWord  ULongLongToULong

//
// DWORD64 -> DWORD_PTR conversion
//
#define DWord64ToDWordPtr   ULongLongToULongPtr

//
// DWORD64 -> LONGLONG conversion
//
#define DWord64ToLongLong   ULongLongToLongLong

//
// DWORD64 -> LONG64 conversion
//
#define DWord64ToLong64 ULongLongToLongLong

//
// DWORD64 -> INT64 conversion
//
#define DWord64ToInt64  ULongLongToLongLong

//
// DWORD64 -> ptrdiff_t conversion
//
#define DWord64ToPtrdiffT   ULongLongToIntPtr

//
// DWORD64 -> size_t conversion
//
#define DWord64ToSizeT  ULongLongToUIntPtr

//
// DWORD64 -> SSIZE_T conversion
//
#define DWord64ToSSIZET ULongLongToLongPtr

//
// DWORD64 -> SIZE_T conversion
//
#define DWord64ToSIZET  ULongLongToULongPtr

//
// UINT64 -> CHAR conversion
//
#define UInt64ToChar    ULongLongToChar

//
// UINT64 -> INT8 conversion
//
#define UInt64ToInt8    ULongLongToInt8

//
// UINT64 -> UCHAR conversion
//
#define UInt64ToUChar   ULongLongToUChar

//
// UINT64 -> UINT8 conversion
//
#define UInt64ToUInt8   ULongLongToUInt8

//
// UINT64 -> BYTE conversion
//
#define UInt64ToByte    ULongLongToUInt8

//
// UINT64 -> SHORT conversion
//
#define UInt64ToShort   ULongLongToShort

//
// UINT64 -> INT16 conversion
//
//
#define UInt64ToInt16   ULongLongToShort

//
// UINT64 -> USHORT conversion
//
#define UInt64ToUShort  ULongLongToUShort

//
// UINT64 -> UINT16 conversion
//
#define UInt64ToUInt16  ULongLongToUShort

//
// UINT64 -> WORD conversion
//
#define UInt64ToWord    ULongLongToUShort

//
// UINT64 -> INT conversion
//
#define UInt64ToInt ULongLongToInt

//
// UINT64 -> INT32 conversion
//
#define UInt64ToInt32   ULongLongToInt

//
// UINT64 -> INT_PTR conversion
//
#define UInt64ToIntPtr  ULongLongToIntPtr

//
// UINT64 -> UINT conversion
//
#define UInt64ToUInt    ULongLongToUInt

//
// UINT64 -> UINT32 conversion
//
#define UInt64ToUInt32  ULongLongToUInt

//
// UINT64 -> UINT_PTR conversion
//
#define UInt64ToUIntPtr ULongLongToUIntPtr

//
// UINT64 -> LONG conversion
//
#define UInt64ToLong    ULongLongToLong

//
// UINT64 -> LONG_PTR conversion
//
#define UInt64ToLongPtr ULongLongToLongPtr

//
// UINT64 -> ULONG conversion
//
#define UInt64ToULong   ULongLongToULong

//
// UINT64 -> ULONG_PTR conversion
//
#define UInt64ToULongPtr    ULongLongToULongPtr

//
// UINT64 -> DWORD conversion
//
#define UInt64ToDWord   ULongLongToULong

//
// UINT64 -> DWORD_PTR conversion
//
#define UInt64ToDWordPtr    ULongLongToULongPtr

//
// UINT64 -> LONGLONG conversion
//
#define UInt64ToLongLong    ULongLongToLongLong

//
// UINT64 -> LONG64 conversion
//
#define UInt64ToLong64  ULongLongToLongLong

//
// UINT64 -> INT64 conversion
//
#define UInt64ToInt64   ULongLongToLongLong

//
// UINT64 -> ptrdiff_t conversion
//
#define UInt64ToPtrdiffT    ULongLongToIntPtr

//
// UINT64 -> size_t conversion
//
#define UInt64ToSizeT   ULongLongToUIntPtr

//
// UINT64 -> SSIZE_T conversion
//
#define UInt64ToSSIZET  ULongLongToLongPtr

//
// UINT64 -> SIZE_T conversion
//
#define UInt64ToSIZET  ULongLongToULongPtr

//
// ptrdiff_t -> CHAR conversion
//
#define PtrdiffTToChar  IntPtrToChar

//
// ptrdiff_t -> INT8 conversion
//
#define PtrdiffTToInt8  IntPtrToInt8

//
// ptrdiff_t -> UCHAR conversion
//
#define PtrdiffTToUChar IntPtrToUChar

//
// ptrdiff_t -> UINT8 conversion
//
#define PtrdiffTToUInt8 IntPtrToUInt8

//
// ptrdiff_t -> BYTE conversion
//
#define PtrdiffTToByte  IntPtrToUInt8

//
// ptrdiff_t -> SHORT conversion
//
#define PtrdiffTToShort IntPtrToShort

//
// ptrdiff_t -> INT16 conversion
//
#define PtrdiffTToInt16 IntPtrToShort

//
// ptrdiff_t -> USHORT conversion
//
#define PtrdiffTToUShort    IntPtrToUShort

//
// ptrdiff_t -> UINT16 conversion
//
#define PtrdiffTToUInt16    IntPtrToUShort

//
// ptrdiff_t -> WORD conversion
//
#define PtrdiffTToWord  IntPtrToUShort

//
// ptrdiff_t -> INT conversion
//
#define PtrdiffTToInt   IntPtrToInt

//
// ptrdiff_t -> INT32 conversion
//
#define PtrdiffTToInt32 IntPtrToInt

//
// ptrdiff_t -> UINT conversion
//
#define PtrdiffTToUInt  IntPtrToUInt

//
// ptrdiff_t -> UINT32 conversion
//
#define PtrdiffTToUInt32    IntPtrToUInt

//
// ptrdiff_t -> UINT_PTR conversion
//
#define PtrdiffTToUIntPtr   IntPtrToUIntPtr

//
// ptrdiff_t -> LONG conversion
//
#define PtrdiffTToLong  IntPtrToLong

//
// ptrdiff_t -> LONG_PTR conversion
//
#define PtrdiffTToLongPtr   IntPtrToLongPtr

//
// ptrdiff_t -> ULONG conversion
//
#define PtrdiffTToULong IntPtrToULong

//
// ptrdiff_t -> ULONG_PTR conversion
//
#define PtrdiffTToULongPtr  IntPtrToULongPtr

//
// ptrdiff_t -> DWORD conversion
//
#define PtrdiffTToDWord IntPtrToULong

//
// ptrdiff_t -> DWORD_PTR conversion
//
#define PtrdiffTToDWordPtr  IntPtrToULongPtr

//
// ptrdiff_t -> ULONGLONG conversion
//
#define PtrdiffTToULongLong IntPtrToULongLong

//
// ptrdiff_t -> DWORDLONG conversion
//
#define PtrdiffTToDWordLong IntPtrToULongLong

//
// ptrdiff_t -> ULONG64 conversion
//
#define PtrdiffTToULong64   IntPtrToULongLong

//
// ptrdiff_t -> DWORD64 conversion
//
#define PtrdiffTToDWord64   IntPtrToULongLong

//
// ptrdiff_t -> UINT64 conversion
//
#define PtrdiffTToUInt64    IntPtrToULongLong

//
// ptrdiff_t -> size_t conversion
//
#define PtrdiffTToSizeT IntPtrToUIntPtr

//
// ptrdiff_t -> SIZE_T conversion
//
#define PtrdiffTToSIZET IntPtrToULongPtr

//
// size_t -> INT8 conversion
//
#define SizeTToInt8 UIntPtrToInt8

//
// size_t -> UCHAR conversion
//
#define SizeTToUChar    UIntPtrToUChar

//
// size_t -> CHAR conversion
//
#define SizeTToChar UIntPtrToChar

//
// size_t -> UINT8 conversion
//
#define SizeTToUInt8    UIntPtrToUInt8

//
// size_t -> BYTE conversion
//
#define SizeTToByte UIntPtrToUInt8

//
// size_t -> SHORT conversion
//
#define SizeTToShort    UIntPtrToShort

//
// size_t -> INT16 conversion
//
#define SizeTToInt16    UIntPtrToShort

//
// size_t -> USHORT conversion
//
#define SizeTToUShort   UIntPtrToUShort

//
// size_t -> UINT16 conversion
//
#define SizeTToUInt16   UIntPtrToUShort

//
// size_t -> WORD
//
#define SizeTToWord UIntPtrToUShort

//
// size_t -> INT conversion
//
#define SizeTToInt  UIntPtrToInt

//
// size_t -> INT32 conversion
//
#define SizeTToInt32    UIntPtrToInt

//
// size_t -> INT_PTR conversion
//
#define SizeTToIntPtr   UIntPtrToIntPtr

//
// size_t -> UINT conversion
//
#define SizeTToUInt UIntPtrToUInt

//
// size_t -> UINT32 conversion
//
#define SizeTToUInt32   UIntPtrToUInt

//
// size_t -> LONG conversion
//
#define SizeTToLong UIntPtrToLong

//
// size_t -> LONG_PTR conversion
//
#define SizeTToLongPtr  UIntPtrToLongPtr

//
// size_t -> ULONG conversion
//
#define SizeTToULong    UIntPtrToULong

//
// size_t -> DWORD conversion
//
#define SizeTToDWord    UIntPtrToULong

//
// size_t -> LONGLONG conversion
//
#define SizeTToLongLong UIntPtrToLongLong

//
// size_t -> LONG64 conversion
//
#define SizeTToLong64   UIntPtrToLongLong

//
// size_t -> INT64
//
#define SizeTToInt64    UIntPtrToLongLong

//   
// size_t -> ptrdiff_t conversion
//
#define SizeTToPtrdiffT UIntPtrToIntPtr

//
// size_t -> SSIZE_T conversion
//
#define SizeTToSSIZET   UIntPtrToLongPtr

//
// SSIZE_T -> INT8 conversion
//
#define SSIZETToInt8    LongPtrToInt8

//
// SSIZE_T -> UCHAR conversion
//
#define SSIZETToUChar   LongPtrToUChar

//
// SSIZE_T -> CHAR conversion
//
#define SSIZETToChar    LongPtrToChar

//
// SSIZE_T -> UINT8 conversion
//
#define SSIZETToUInt8   LongPtrToUInt8

//
// SSIZE_T -> BYTE conversion
//
#define SSIZETToByte    LongPtrToUInt8

//
// SSIZE_T -> SHORT conversion
//
#define SSIZETToShort   LongPtrToShort

//
// SSIZE_T -> INT16 conversion
//
#define SSIZETToInt16   LongPtrToShort

//
// SSIZE_T -> USHORT conversion
//
#define SSIZETToUShort  LongPtrToUShort

//
// SSIZE_T -> UINT16 conversion
//
#define SSIZETToUInt16  LongPtrToUShort

//
// SSIZE_T -> WORD conversion
//
#define SSIZETToWord    LongPtrToUShort

//
// SSIZE_T -> INT conversion
//
#define SSIZETToInt LongPtrToInt

//
// SSIZE_T -> INT32 conversion
//
#define SSIZETToInt32   LongPtrToInt

//
// SSIZE_T -> INT_PTR conversion
//
#define SSIZETToIntPtr  LongPtrToIntPtr

//
// SSIZE_T -> UINT conversion
//
#define SSIZETToUInt    LongPtrToUInt

//
// SSIZE_T -> UINT32 conversion
//
#define SSIZETToUInt32  LongPtrToUInt

//
// SSIZE_T -> UINT_PTR conversion
//
#define SSIZETToUIntPtr LongPtrToUIntPtr

//
// SSIZE_T -> LONG conversion
//
#define SSIZETToLong    LongPtrToLong

//
// SSIZE_T -> ULONG conversion
//
#define SSIZETToULong   LongPtrToULong

//
// SSIZE_T -> ULONG_PTR conversion
//
#define SSIZETToULongPtr    LongPtrToULongPtr

//
// SSIZE_T -> DWORD conversion
//
#define SSIZETToDWord   LongPtrToULong

//
// SSIZE_T -> DWORD_PTR conversion
//
#define SSIZETToDWordPtr    LongPtrToULongPtr

//
// SSIZE_T -> ULONGLONG conversion
//
#define SSIZETToULongLong   LongPtrToULongLong

//
// SSIZE_T -> DWORDLONG conversion
//
#define SSIZETToDWordLong   LongPtrToULongLong

//
// SSIZE_T -> ULONG64 conversion
//
#define SSIZETToULong64 LongPtrToULongLong

//
// SSIZE_T -> DWORD64 conversion
//
#define SSIZETToDWord64 LongPtrToULongLong

//
// SSIZE_T -> UINT64 conversion
//
#define SSIZETToUInt64  LongPtrToULongLong

//
// SSIZE_T -> size_t conversion
//
#define SSIZETToSizeT   LongPtrToUIntPtr

//
// SSIZE_T -> SIZE_T conversion
//
#define SSIZETToSIZET   LongPtrToULongPtr

//
// SIZE_T -> INT8 conversion
//
#define SIZETToInt8 ULongPtrToInt8

//
// SIZE_T -> UCHAR conversion
//
#define SIZETToUChar    ULongPtrToUChar

//
// SIZE_T -> CHAR conversion
//
#define SIZETToChar ULongPtrToChar

//
// SIZE_T -> UINT8 conversion
//
#define SIZETToUInt8    ULongPtrToUInt8

//
// SIZE_T -> BYTE conversion
//
#define SIZETToByte ULongPtrToUInt8

//
// SIZE_T -> SHORT conversion
//
#define SIZETToShort    ULongPtrToShort

//
// SIZE_T -> INT16 conversion
//
#define SIZETToInt16    ULongPtrToShort

//
// SIZE_T -> USHORT conversion
//
#define SIZETToUShort   ULongPtrToUShort

//
// SIZE_T -> UINT16 conversion
//
#define SIZETToUInt16   ULongPtrToUShort

//
// SIZE_T -> WORD
//
#define SIZETToWord ULongPtrToUShort

//
// SIZE_T -> INT conversion
//
#define SIZETToInt  ULongPtrToInt

//
// SIZE_T -> INT32 conversion
//
#define SIZETToInt32    ULongPtrToInt

//
// SIZE_T -> INT_PTR conversion
//
#define SIZETToIntPtr   ULongPtrToIntPtr

//
// SIZE_T -> UINT conversion
//
#define SIZETToUInt ULongPtrToUInt

//
// SIZE_T -> UINT32 conversion
//
#define SIZETToUInt32   ULongPtrToUInt

//
// SIZE_T -> UINT_PTR conversion
//
#define SIZETToUIntPtr  ULongPtrToUIntPtr

//
// SIZE_T -> LONG conversion
//
#define SIZETToLong ULongPtrToLong

//
// SIZE_T -> LONG_PTR conversion
//
#define SIZETToLongPtr  ULongPtrToLongPtr

//
// SIZE_T -> ULONG conversion
//
#define SIZETToULong    ULongPtrToULong

//
// SIZE_T -> DWORD conversion
//
#define SIZETToDWord    ULongPtrToULong

//
// SIZE_T -> LONGLONG conversion
//
#define SIZETToLongLong ULongPtrToLongLong

//
// SIZE_T -> LONG64 conversion
//
#define SIZETToLong64   ULongPtrToLongLong

//
// SIZE_T -> INT64
//
#define SIZETToInt64    ULongPtrToLongLong

//
// SIZE_T -> ptrdiff_t conversion
//
#define SIZETToPtrdiffT ULongPtrToIntPtr

//
// SIZE_T -> SSIZE_T conversion
//
#define SIZETToSSIZET   ULongPtrToLongPtr


//=============================================================================
// Addition functions
//=============================================================================

//
// UINT8 addition
//
__inline
HRESULT
UInt8Add(
    __inn UINT8 u8Augend,
    __inn UINT8 u8Addend,
    __outt __deref_out_range(==,u8Augend + u8Addend) UINT8* pu8Result)
{
    HRESULT hr;

    if (((UINT8)(u8Augend + u8Addend)) >= u8Augend)
    {
        *pu8Result = (UINT8)(u8Augend + u8Addend);
        hr = S_OK;
    }
    else
    {
        *pu8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// USHORT addition
//
__inline
HRESULT
UShortAdd(
    __inn USHORT usAugend,
    __inn USHORT usAddend,
    __outt __deref_out_range(==,usAugend + usAddend) USHORT* pusResult)
{
    HRESULT hr;

    if (((USHORT)(usAugend + usAddend)) >= usAugend)
    {
        *pusResult = (USHORT)(usAugend + usAddend);
        hr = S_OK;
    }
    else
    {
        *pusResult = USHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// UINT16 addition
//
#define UInt16Add   UShortAdd

//
// WORD addtition
//
#define WordAdd     UShortAdd

//
// UINT addition
//
__inline
HRESULT
UIntAdd(
    __inn UINT uAugend,
    __inn UINT uAddend,
    __outt __deref_out_range(==,uAugend + uAddend) UINT* puResult)
{
    HRESULT hr;

    if ((uAugend + uAddend) >= uAugend)
    {
        *puResult = (uAugend + uAddend);
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// UINT32 addition
//
#define UInt32Add   UIntAdd

//
// UINT_PTR addition
//
#ifdef _WIN64
#define UIntPtrAdd      ULongLongAdd
#else
__inline
HRESULT
UIntPtrAdd(
    __inn UINT_PTR uAugend,
    __inn UINT_PTR uAddend,
    __outt __deref_out_range(==,uAugend + uAddend) UINT_PTR* puResult)
{
    HRESULT hr;

    if ((uAugend + uAddend) >= uAugend)
    {
        *puResult = (uAugend + uAddend);
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}
#endif // _WIN64

//
// ULONG addition
//
__inline
HRESULT
ULongAdd(
    __inn ULONG ulAugend,
    __inn ULONG ulAddend,
    __outt __deref_out_range(==,ulAugend + ulAddend) ULONG* pulResult)
{
    HRESULT hr;

    if ((ulAugend + ulAddend) >= ulAugend)
    {
        *pulResult = (ulAugend + ulAddend);
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONG_PTR addition
//
#ifdef _WIN64
#define ULongPtrAdd     ULongLongAdd
#else
__inline
HRESULT
ULongPtrAdd(
    __inn ULONG_PTR ulAugend,
    __inn ULONG_PTR ulAddend,
    __outt __deref_out_range(==,ulAugend + ulAddend) ULONG_PTR* pulResult)
{
    HRESULT hr;

    if ((ulAugend + ulAddend) >= ulAugend)
    {
        *pulResult = (ulAugend + ulAddend);
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}
#endif // _WIN64

//
// DWORD addition
//
#define DWordAdd        ULongAdd

//
// DWORD_PTR addition
//
#ifdef _WIN64
#define DWordPtrAdd     ULongLongAdd
#else
__inline
HRESULT
DWordPtrAdd(
    __inn DWORD_PTR dwAugend,
    __inn DWORD_PTR dwAddend,
    __outt __deref_out_range(==,dwAugend + dwAddend) DWORD_PTR* pdwResult)
{
    HRESULT hr;

    if ((dwAugend + dwAddend) >= dwAugend)
    {
        *pdwResult = (dwAugend + dwAddend);
        hr = S_OK;
    }
    else
    {
        *pdwResult = DWORD_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}
#endif // _WIN64

//
// size_t addition
//
__inline
HRESULT
SizeTAdd(
    __inn size_t Augend,
    __inn size_t Addend,
    __outt __deref_out_range(==,Augend + Addend) size_t* pResult)
{
    HRESULT hr;

    if ((Augend + Addend) >= Augend)
    {
        *pResult = (Augend + Addend);
        hr = S_OK;
    }
    else
    {
        *pResult = SIZE_T_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// SIZE_T addition
//
#ifdef _WIN64
#define SIZETAdd      ULongLongAdd
#else
__inline
HRESULT
SIZETAdd(
    __inn SIZE_T Augend,
    __inn SIZE_T Addend,
    __outt __deref_out_range(==,Augend + Addend) SIZE_T* pResult)
{
    HRESULT hr;

    if ((Augend + Addend) >= Augend)
    {
        *pResult = (Augend + Addend);
        hr = S_OK;
    }
    else
    {
        *pResult = _SIZE_T_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}
#endif // _WIN64

//
// ULONGLONG addition
//
__inline
HRESULT
ULongLongAdd(
    __inn ULONGLONG ullAugend,
    __inn ULONGLONG ullAddend,
    __outt __deref_out_range(==,ullAugend + ullAddend) ULONGLONG* pullResult)
{
    HRESULT hr;

    if ((ullAugend + ullAddend) >= ullAugend)
    {
        *pullResult = (ullAugend + ullAddend);
        hr = S_OK;
    }
    else
    {
        *pullResult = ULONGLONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// DWORDLONG addition
//
#define DWordLongAdd    ULongLongAdd

//
// ULONG64 addition
//
#define ULong64Add  ULongLongAdd

//
// DWORD64 addition
//
#define DWord64Add  ULongLongAdd

//
// UINT64 addition
//
#define UInt64Add   ULongLongAdd


//=============================================================================
// Subtraction functions
//=============================================================================

//
// UINT8 subtraction
//
__inline
HRESULT
UInt8Sub(
    __inn UINT8 u8Minuend,
    __inn UINT8 u8Subtrahend,
    __outt __deref_out_range(==,u8Minuend - u8Subtrahend) UINT8* pu8Result)
{
    HRESULT hr;
    
    if (u8Minuend >= u8Subtrahend)
    {
        *pu8Result = (UINT8)(u8Minuend - u8Subtrahend);
        hr = S_OK;
    }
    else
    {
        *pu8Result = UINT8_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// USHORT subtraction
//
__inline
HRESULT
UShortSub(
    __inn USHORT usMinuend,
    __inn USHORT usSubtrahend,
    __outt __deref_out_range(==,usMinuend - usSubtrahend) USHORT* pusResult)
{
    HRESULT hr;

    if (usMinuend >= usSubtrahend)
    {
        *pusResult = (USHORT)(usMinuend - usSubtrahend);
        hr = S_OK;
    }
    else
    {
        *pusResult = USHORT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// UINT16 subtraction
//
#define UInt16Sub   UShortSub

//
// WORD subtraction
//
#define WordSub     UShortSub


//
// UINT subtraction
//
__inline
HRESULT
UIntSub(
    __inn UINT uMinuend,
    __inn UINT uSubtrahend,
    __outt __deref_out_range(==,uMinuend - uSubtrahend) UINT* puResult)
{
    HRESULT hr;

    if (uMinuend >= uSubtrahend)
    {
        *puResult = (uMinuend - uSubtrahend);
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// UINT32 subtraction
//
#define UInt32Sub   UIntSub

//
// UINT_PTR subtraction
//
#ifdef _WIN64
#define UIntPtrSub  ULongLongSub
#else
__inline
HRESULT
UIntPtrSub(
    __inn UINT_PTR uMinuend,
    __inn UINT_PTR uSubtrahend,
    __outt __deref_out_range(==,uMinuend - uSubtrahend) UINT_PTR* puResult)
{
    HRESULT hr;

    if (uMinuend >= uSubtrahend)
    {
        *puResult = (uMinuend - uSubtrahend);
        hr = S_OK;
    }
    else
    {
        *puResult = UINT_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}
#endif // _WIN64

//
// ULONG subtraction
//
__inline
HRESULT
ULongSub(
    __inn ULONG ulMinuend,
    __inn ULONG ulSubtrahend,
    __outt __deref_out_range(==,ulMinuend - ulSubtrahend) ULONG* pulResult)
{
    HRESULT hr;

    if (ulMinuend >= ulSubtrahend)
    {
        *pulResult = (ulMinuend - ulSubtrahend);
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// ULONG_PTR subtraction
//
#ifdef _WIN64
#define ULongPtrSub ULongLongSub
#else
__inline
HRESULT
ULongPtrSub(
    __inn ULONG_PTR ulMinuend,
    __inn ULONG_PTR ulSubtrahend,
    __outt __deref_out_range(==,ulMinuend - ulSubtrahend) ULONG_PTR* pulResult)
{
    HRESULT hr;

    if (ulMinuend >= ulSubtrahend)
    {
        *pulResult = (ulMinuend - ulSubtrahend);
        hr = S_OK;
    }
    else
    {
        *pulResult = ULONG_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}
#endif // _WIN64


//
// DWORD subtraction
//
#define DWordSub        ULongSub

//
// DWORD_PTR subtraction
//
#ifdef _WIN64
#define DWordPtrSub     ULongLongSub
#else
__inline
HRESULT
DWordPtrSub(
    __inn DWORD_PTR dwMinuend,
    __inn DWORD_PTR dwSubtrahend,
    __outt __deref_out_range(==,dwMinuend - dwSubtrahend) DWORD_PTR* pdwResult)
{
    HRESULT hr;

    if (dwMinuend >= dwSubtrahend)
    {
        *pdwResult = (dwMinuend - dwSubtrahend);
        hr = S_OK;
    }
    else
    {
        *pdwResult = DWORD_PTR_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
} 
#endif // _WIN64

//
// size_t subtraction
//
__inline
HRESULT
SizeTSub(
    __inn size_t Minuend,
    __inn size_t Subtrahend,
    __outt __deref_out_range(==,Minuend - Subtrahend) size_t* pResult)
{
    HRESULT hr;

    if (Minuend >= Subtrahend)
    {
        *pResult = (Minuend - Subtrahend);
        hr = S_OK;
    }
    else
    {
        *pResult = SIZE_T_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// SIZE_T subtraction
//
#ifdef _WIN64
#define SIZETSub    ULongLongSub
#else
__inline
HRESULT
SIZETSub(
    __inn SIZE_T Minuend,
    __inn SIZE_T Subtrahend,
    __outt __deref_out_range(==,Minuend - Subtrahend) SIZE_T* pResult)
{
    HRESULT hr;

    if (Minuend >= Subtrahend)
    {
        *pResult = (Minuend - Subtrahend);
        hr = S_OK;
    }
    else
    {
        *pResult = _SIZE_T_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}
#endif // _WIN64

//
// ULONGLONG subtraction
//
__inline
HRESULT
ULongLongSub(
    __inn ULONGLONG ullMinuend,
    __inn ULONGLONG ullSubtrahend,
    __outt __deref_out_range(==,ullMinuend - ullSubtrahend) ULONGLONG* pullResult)
{
    HRESULT hr;

    if (ullMinuend >= ullSubtrahend)
    {
        *pullResult = (ullMinuend - ullSubtrahend);
        hr = S_OK;
    }
    else
    {
        *pullResult = ULONGLONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
    
    return hr;
}

//
// DWORDLONG subtraction
//
#define DWordLongSub    ULongLongSub

//
// ULONG64 subtraction
//
#define ULong64Sub  ULongLongSub

//
// DWORD64 subtraction
//
#define DWord64Sub  ULongLongSub

//
// UINT64 subtraction
//
#define UInt64Sub   ULongLongSub


//=============================================================================
// Multiplication functions
//=============================================================================

//
// UINT8 multiplication
//
__inline
HRESULT
UInt8Mult(
    __inn UINT8 u8Multiplicand,
    __inn UINT8 u8Multiplier,
    __outt __deref_out_range(==,u8Multiplier * u8Multiplicand) UINT8* pu8Result)
{
    UINT uResult = ((UINT)u8Multiplicand) * ((UINT)u8Multiplier);
    
    return UIntToUInt8(uResult, pu8Result);
}    

//
// USHORT multiplication
//
__inline
HRESULT
UShortMult(
    __inn USHORT usMultiplicand,
    __inn USHORT usMultiplier,
    __outt __deref_out_range(==,usMultiplier * usMultiplicand)USHORT* pusResult)
{
    ULONG ulResult = ((ULONG)usMultiplicand) * ((ULONG)usMultiplier);
    
    return ULongToUShort(ulResult, pusResult);
}

//
// UINT16 multiplication
//
#define UInt16Mult  UShortMult

//
// WORD multiplication
//
#define WordMult    UShortMult

//
// UINT multiplication
//
__inline
HRESULT
UIntMult(
    __inn UINT uMultiplicand,
    __inn UINT uMultiplier,
    __outt __deref_out_range(==,uMultiplier * uMultiplicand) UINT* puResult)
{
    ULONGLONG ull64Result = UInt32x32To64(uMultiplicand, uMultiplier);

    return ULongLongToUInt(ull64Result, puResult);
}

//
// UINT32 multiplication
//
#define UInt32Mult  UIntMult

//
// UINT_PTR multiplication
//
#ifdef _WIN64
#define UIntPtrMult     ULongLongMult
#else
__inline
HRESULT
UIntPtrMult(
    __inn UINT_PTR uMultiplicand,
    __inn UINT_PTR uMultiplier,
    __outt __deref_out_range(==,uMultiplier * uMultiplicand) UINT_PTR* puResult)
{
    ULONGLONG ull64Result = UInt32x32To64(uMultiplicand, uMultiplier);

    return ULongLongToUIntPtr(ull64Result, puResult);
}
#endif // _WIN64

//
// ULONG multiplication
//
__inline
HRESULT
ULongMult(
    __inn ULONG ulMultiplicand,
    __inn ULONG ulMultiplier,
    __outt __deref_out_range(==,ulMultiplier * ulMultiplicand) ULONG* pulResult)
{
    ULONGLONG ull64Result = UInt32x32To64(ulMultiplicand, ulMultiplier);
    
    return ULongLongToULong(ull64Result, pulResult);
}

//
// ULONG_PTR multiplication
//
#ifdef _WIN64
#define ULongPtrMult    ULongLongMult
#else
__inline
HRESULT
ULongPtrMult(
    __inn ULONG_PTR ulMultiplicand,
    __inn ULONG_PTR ulMultiplier,
    __outt __deref_out_range(==,ulMultiplier * ulMultiplicand) ULONG_PTR* pulResult)
{
    ULONGLONG ull64Result = UInt32x32To64(ulMultiplicand, ulMultiplier);
    
    return ULongLongToULongPtr(ull64Result, (unsigned long *)pulResult);
}
#endif // _WIN64

//
// DWORD multiplication
//
#define DWordMult       ULongMult

//
// DWORD_PTR multiplication
//
#ifdef _WIN64
#define DWordPtrMult    ULongLongMult
#else
__inline
HRESULT
DWordPtrMult(
    __inn DWORD_PTR dwMultiplicand,
    __inn DWORD_PTR dwMultiplier,
    __outt __deref_out_range(==,dwMultiplier * dwMultiplicand) DWORD_PTR* pdwResult)
{
    ULONGLONG ull64Result = UInt32x32To64(dwMultiplicand, dwMultiplier);
    
    return ULongLongToDWordPtr(ull64Result, (unsigned long *)pdwResult);
}
#endif // _WIN64

//
// size_t multiplication
//

#ifdef _WIN64
#define SizeTMult       ULongLongMult
#else
__inline
HRESULT
SizeTMult(
    __inn size_t Multiplicand,
    __inn size_t Multiplier,
    __outt __deref_out_range(==,Multiplier * Multiplicand) UINT* pResult)
{
    ULONGLONG ull64Result = UInt32x32To64(Multiplicand, Multiplier);

    return ULongLongToSizeT(ull64Result, pResult);
}
#endif // _WIN64

//
// SIZE_T multiplication
//
#ifdef _WIN64
#define SIZETMult       ULongLongMult
#else
__inline
HRESULT
SIZETMult(
    __inn SIZE_T Multiplicand,
    __inn SIZE_T Multiplier,
    __outt __deref_out_range(==,Multiplier * Multiplicand) SIZE_T* pResult)
{
    ULONGLONG ull64Result = UInt32x32To64(Multiplicand, Multiplier);
    
    return ULongLongToSIZET(ull64Result, (unsigned long *)pResult);
}
#endif // _WIN64

//
// ULONGLONG multiplication
//
__inline
HRESULT
ULongLongMult(
    __inn ULONGLONG ullMultiplicand,
    __inn ULONGLONG ullMultiplier,
    __outt __deref_out_range(==,ullMultiplier * ullMultiplicand) ULONGLONG* pullResult)
{
    HRESULT hr;
#ifdef _AMD64_
    ULONGLONG u64ResultHigh;
    ULONGLONG u64ResultLow;
    
    u64ResultLow = UnsignedMultiply128(ullMultiplicand, ullMultiplier, &u64ResultHigh);
    if (u64ResultHigh == 0)
    {
        *pullResult = u64ResultLow;
        hr = S_OK;
    }
    else
    {
        *pullResult = ULONGLONG_ERROR;
        hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    }
#else
    // 64x64 into 128 is like 32.32 x 32.32.
    //
    // a.b * c.d = a*(c.d) + .b*(c.d) = a*c + a*.d + .b*c + .b*.d
    // back in non-decimal notation where A=a*2^32 and C=c*2^32:  
    // A*C + A*d + b*C + b*d
    // So there are four components to add together.
    //   result = (a*c*2^64) + (a*d*2^32) + (b*c*2^32) + (b*d)
    //
    // a * c must be 0 or there would be bits in the high 64-bits
    // a * d must be less than 2^32 or there would be bits in the high 64-bits
    // b * c must be less than 2^32 or there would be bits in the high 64-bits
    // then there must be no overflow of the resulting values summed up.
    
    ULONG dw_a;
    ULONG dw_b;
    ULONG dw_c;
    ULONG dw_d;
    ULONGLONG ad = 0;
    ULONGLONG bc = 0;
    ULONGLONG bd = 0;
    ULONGLONG ullResult = 0;

    hr = INTSAFE_E_ARITHMETIC_OVERFLOW;
    
    dw_a = (ULONG)(ullMultiplicand >> 32);
    dw_c = (ULONG)(ullMultiplier >> 32);

    // common case -- if high dwords are both zero, no chance for overflow
    if ((dw_a == 0) && (dw_c == 0))
    {
        dw_b = (DWORD)ullMultiplicand;
        dw_d = (DWORD)ullMultiplier;

        *pullResult = (((ULONGLONG)dw_b) * (ULONGLONG)dw_d);
        hr = S_OK;
    }
    else
    {
        // a * c must be 0 or there would be bits set in the high 64-bits
        if ((dw_a == 0) ||
            (dw_c == 0))
        {
            dw_d = (DWORD)ullMultiplier;

            // a * d must be less than 2^32 or there would be bits set in the high 64-bits
            ad = (((ULONGLONG)dw_a) * (ULONGLONG)dw_d);
            if ((ad & 0xffffffff00000000LL) == 0)
            {
                dw_b = (DWORD)ullMultiplicand;

                // b * c must be less than 2^32 or there would be bits set in the high 64-bits
                bc = (((ULONGLONG)dw_b) * (ULONGLONG)dw_c);
                if ((bc & 0xffffffff00000000LL) == 0)
                {
                    // now sum them all up checking for overflow.
                    // shifting is safe because we already checked for overflow above
                    if (SUCCEEDED(ULongLongAdd(bc << 32, ad << 32, &ullResult)))                        
                    {
                        // b * d
                        bd = (((ULONGLONG)dw_b) * (ULONGLONG)dw_d);
                    
                        if (SUCCEEDED(ULongLongAdd(ullResult, bd, &ullResult)))
                        {
                            *pullResult = ullResult;
                            hr = S_OK;
                        }
                    }
                }
            }
        }
    }

    if (FAILED(hr))
    {
        *pullResult = ULONGLONG_ERROR;
    }
#endif // _AMD64_  
    
    return hr;
}

//
// DWORDLONG multiplication
//
#define DWordLongMult   ULongLongMult

//
// ULONG64 multiplication
//
#define ULong64Mult ULongLongMult

//
// DWORD64 multiplication
//
#define DWord64Mult ULongLongMult

//
// UINT64 multiplication
//
#define UInt64Mult  ULongLongMult

//
// Macros that are no longer used in this header but which clients may
// depend on being defined here.
//
#define LOWORD(_dw)     ((WORD)(((DWORD_PTR)(_dw)) & 0xffff))
#define HIWORD(_dw)     ((WORD)((((DWORD_PTR)(_dw)) >> 16) & 0xffff))
#define LODWORD(_qw)    ((DWORD)(_qw))
#define HIDWORD(_qw)    ((DWORD)(((_qw) >> 32) & 0xffffffff))

#endif // XPLAT_INTSAFE_H

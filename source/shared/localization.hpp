//---------------------------------------------------------------------------------------------------------------------------------
// File: Localization.hpp
//
// Contents: Contains portable classes for localization
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

#ifndef __LOCALIZATION_HPP__
#define __LOCALIZATION_HPP__

#include <time.h>
#include <assert.h>
#include "typedefs_for_linux.h"

#ifdef MPLAT_UNIX
#include <locale>
#endif


#define CP_UTF8  65001
#define CP_UTF16 1200
#define CP_UTF32 12000
#define CP_ACP  0           // default to ANSI code page

// This class provides allocation policies for the SystemLocale and AutoArray classes.
// This is primarily needed for the self-allocating ToUtf16/FromUtf16 methods.
// SNI needs all its allocations to use its own allocator so it would create a separate
// class that obeys this interface and provide it as a template parameter.
template< typename ArrayT >
struct ArrayTAllocator
{
    static ArrayT * Alloc( size_t cch )
    {
        return reinterpret_cast< ArrayT * >( malloc(cch*sizeof(ArrayT)) );
    }
    // Realloc will free the 'old' memory if new memory was successfully allocated
    // and copied to.
    static ArrayT * Realloc( ArrayT * old, size_t cchNewSize )
    {
        return reinterpret_cast< ArrayT * >( realloc(old, cchNewSize*sizeof(ArrayT)) );
    }
    static void Free( ArrayT * mem )
    {
        free( mem );
    }
};

// This is an auto_ptr-like class that is used with the SystemLocale.
// It allows for automatic freeing of the memory using the allocator policy.
// Callers would not normally use this class directly but would use one of the
// two specializations: AutoCharArray AutoWCharArray.
template< typename ArrayT, typename AllocT = ArrayTAllocator< ArrayT > >
struct AutoArray
{
    size_t m_cchSize;
    ArrayT * m_ptr;

    AutoArray( const AutoArray & );
    AutoArray & operator=( const AutoArray & );

    AutoArray()
        :   m_cchSize( 0 ), m_ptr( NULL )
    {
    }
    explicit AutoArray( size_t cchSize )
        :   m_cchSize( cchSize ), m_ptr( AllocT::Alloc(cchSize) )
    {
    }
    virtual ~AutoArray()
    {
        Free();
    }
    void Free()
    {
        if ( NULL != m_ptr )
        {
            AllocT::Free( m_ptr );
            m_ptr = NULL;
            m_cchSize = 0;
        }
    }
    bool Realloc( size_t cchSize )
    {
        ArrayT * newPtr = AllocT::Realloc( m_ptr, cchSize );
        if ( NULL != newPtr )
        {
            // Safe to overwrite since Realloc freed m_ptr.
            m_ptr = newPtr;
            m_cchSize = cchSize;
            return true;
        }
        return false;
    }
    ArrayT * Detach()
    {
        ArrayT * oldPtr = m_ptr;
        m_ptr = NULL;
        m_cchSize = 0;
        return oldPtr;
    }
};


class SystemLocale
{
public:
    // -----------------------------------------------------------------------
    // Public Static Functions
#ifdef MPLAT_UNIX
    static const SystemLocale & Singleton();
#else
    // Windows returns by value since this is an empty class
    static const SystemLocale Singleton();
#endif

#ifdef MPLAT_UNIX

    static const int MINS_PER_HOUR = 60;
    static const int MINS_PER_DAY = 24 * MINS_PER_HOUR;

#endif

    // Multi-byte UTF8 code points start with '11xx xxxx'
    static bool IsUtf8LeadByte( BYTE utf8 )
    {
        return (0xC0 == (utf8 & 0xC0));
    }

    // Maximum number of storage units (char or WCHAR)
    // for a code page (e.g. UTF16 == 2 for surrogates)
    static UINT MaxCharCchSize( UINT codepage );

    // Inspects the byte at start, and returns the start
    // of the next code point (possibly multiple bytes later).
    // If NULL or start points at null terminator, than start is returned.
    // If start points at a dangling UTF8 trail byte, then (start+1) is
    // returned since we can't know how large this code point is.
    static char * NextChar( UINT codepage, const char * start );
#ifdef MPLAT_UNIX
    // This version is for non-null terminated strings.
    // Last ptr will be one past end of buffer.
    static char * NextChar( UINT codepage, const char * start, size_t cchBytesLeft );
#endif

    // For all transcoding functions
    // Returns zero on error.  Do not call GetLastError() since that is not portable (pErrorCode has result of GetLastError()).
    // pHasDataLoss will be true if an unrecognized code point was encountered in the source and a default output instead.
    // Replaces calls to MultiByteToWideChar and WideCharToMultiByte

    // Transcode between a code page and UTF16
    static size_t ToUtf16( UINT srcCodePage, const char * src, SSIZE_T cchSrc,
                           __out_ecount_opt(cchDest) WCHAR * dest, size_t cchDest,
                           DWORD * pErrorCode = NULL );
    static size_t ToUtf16Strict( UINT srcCodePage, const char * src, SSIZE_T cchSrc,
                                 __out_ecount_opt(cchDest) WCHAR * dest, size_t cchDest,
                                 DWORD * pErrorCode = NULL );
    static size_t FromUtf16( UINT destCodePage, const WCHAR * src, SSIZE_T cchSrc,
                             __out_ecount_opt(cchDest) char * dest, size_t cchDest,
                             bool * pHasDataLoss = NULL, DWORD * pErrorCode = NULL );
    static size_t FromUtf16Strict(UINT destCodePage, const WCHAR * src, SSIZE_T cchSrc,
                                  __out_ecount_opt(cchDest) char * dest, size_t cchDest,
                                  bool * pHasDataLoss = NULL, DWORD * pErrorCode = NULL);



    // -----------------------------------------------------------------------
    // Public Member Functions

    // The Ansi code page, always UTF8 for Linux
    UINT AnsiCP() const;
    // Used for files (e.g. returns 437 on US Windows, UTF8 for Linux) 


private:
    // Prevent copying.
    // Also prevents misuse of return from Singleton() method.
    // Since return types are different on Windows vs Linux,
    // callers should not cache the result of Singleton().
    SystemLocale( const SystemLocale & );
    SystemLocale & operator=( const SystemLocale & );

#ifdef MPLAT_UNIX
// MPLAT_UNIX ----------------------------------------------------------------

    std::locale * m_pLocale;

    explicit SystemLocale( const char * localeName );
    ~SystemLocale();

    static UINT ExpandSpecialCP( UINT codepage )
    {
        // Convert CP_ACP, CP_OEM to CP_UTF8
        return (codepage < 2 ? CP_UTF8 : codepage);
    }

// MPLAT_UNIX ----------------------------------------------------------------
#else
// !MPLAT_UNIX ---------------------------------------------------------------

    SystemLocale() {}

    static size_t ReturnCchResult( SSIZE_T cch, DWORD * pErrorCode )
    {
        if ( cch < 0 )
        {
            cch = 0;
        }
        if ( NULL != pErrorCode )
        {
            *pErrorCode = (0 == cch ? GetLastError() : ERROR_SUCCESS);
        }
        return static_cast<size_t>(cch);
    }

    static size_t FastAsciiMultiByteToWideChar
        (
            UINT        CodePage,
            __in_ecount(cch) const char  *pch,  // IN   | source string
            SSIZE_T     cch,                    // IN   | count of characters or -1
            __out_ecount_opt(cwch) PWCHAR pwch, // IN   | Result string
            size_t      cwch,                   // IN   | count of wchars of result buffer or 0
            DWORD*      pErrorCode,             // OUT  | optional pointer to return error code
            bool        bStrict = false         // IN   | Return error if invalid chars in src
         );
    static size_t FastAsciiWideCharToMultiByte
        (
            UINT        CodePage,
            const WCHAR *pwch,              // IN   | source string
            SSIZE_T     cwch,               // IN   | count of characters or -1
            __out_bcount(cch) char *pch,    // IN   | Result string
            size_t      cch,                // IN   | Length of result buffer or 0  
            BOOL        *pfDataLoss,        // OUT  | True if there was data loss during CP conversion
            DWORD       *pErrorCode         // OUT  | optional pointer to return error code
        );

// !MPLAT_UNIX ---------------------------------------------------------------
#endif

    // Returns the number of bytes this UTF8 code point expects
    static UINT CchUtf8CodePt( BYTE codept )
    {
        assert( IsUtf8LeadByte(codept) );

        // Initial byte of utf8 sequence indicates its length
        // 110x xxxx = 2 bytes
        // 1110 xxxx = 3 bytes
        // 1111 0xxx = 4 bytes
        // 1111 10xx = 5 bytes, future Unicode extension not covered by this logic
        // 1111 110x = 6 bytes, future Unicode extension not covered by this logic
        UINT expected_size = (0xC0 == (codept & 0xE0)) ? 2 : (0xE0 == (codept & 0xF0)) ? 3 : 4;

        // Verify constraints
        assert( 4 == MaxCharCchSize(CP_UTF8) );

        return expected_size;
    }

};


// ---------------------------------------------------------------------------
// Inlines that vary by platform

#if defined(MPLAT_UNIX)
// MPLAT_UNIX ----------------------------------------------------------------

#include "globalization.h"

inline UINT SystemLocale::AnsiCP() const
{
    return CP_UTF8;
}

inline UINT SystemLocale::MaxCharCchSize( UINT codepage )
{
    codepage = ExpandSpecialCP( codepage );
    switch ( codepage )
    {
    case CP_UTF8:
        return 4;
    case 932:
    case 936:
    case 949:
    case 950:
    case CP_UTF16:
        return 2;
    default:
        return 1;
    }
}

// MPLAT_UNIX ----------------------------------------------------------------
#else
// ! MPLAT_UNIX ----------------------------------------------------------------


inline const SystemLocale SystemLocale::Singleton()
{
    // On Windows, Localization is an empty class so creation of this
    // should be optimized away.  Empty classes have a sizeof 1 so there's
    // something to take the address of.
    C_ASSERT( 1 == sizeof(SystemLocale) );
    return SystemLocale();
}

inline UINT SystemLocale::AnsiCP() const
{
    return GetACP();
}

inline UINT SystemLocale::MaxCharCchSize( UINT codepage )
{
    CPINFO cpinfo;
    BOOL rc = GetCPInfo( codepage, &cpinfo );
    return (rc ? cpinfo.MaxCharSize : 0);
}

inline char * SystemLocale::NextChar( UINT codepage,  const char * start )
{
    return CharNextExA( (WORD)codepage, start, 0 );
}

inline size_t SystemLocale::ToUtf16( UINT srcCodePage, const char * src, SSIZE_T cchSrc, WCHAR * dest, size_t cchDest, DWORD * pErrorCode )
{
    return FastAsciiMultiByteToWideChar( srcCodePage, src, cchSrc, dest, cchDest, pErrorCode );
}

inline size_t SystemLocale::ToUtf16Strict( UINT srcCodePage, const char * src, SSIZE_T cchSrc, WCHAR * dest, size_t cchDest, DWORD * pErrorCode )
{
    return FastAsciiMultiByteToWideChar( srcCodePage, src, cchSrc, dest, cchDest, pErrorCode, true );
}

inline size_t SystemLocale::FromUtf16( UINT destCodePage, const WCHAR * src, SSIZE_T cchSrc, char * dest, size_t cchDest, bool * pHasDataLoss, DWORD * pErrorCode )
{
    BOOL dataloss = FALSE;
    size_t cchCvt = FastAsciiWideCharToMultiByte( destCodePage, src, cchSrc, dest, cchDest, &dataloss, pErrorCode );
    if ( NULL != pHasDataLoss )
    {
        *pHasDataLoss = (FALSE != dataloss);
    }
    return cchCvt;
}

// ! MPLAT_UNIX ----------------------------------------------------------------
#endif

#endif // __LOCALIZATION_HPP__

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
namespace std
{
    // Forward reference
    class locale;
}
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
    void UpdateSize()
    {
        if ( NULL == m_ptr )
        {
            m_cchSize = 0;
        }
        else
        {
            // XPLAT_ODBC_TODO VSTS 819733 MPlat: Reconcile std c++ usage between platforms
            // Should use char_traits<ArrayT>::length
            ArrayT * end = m_ptr;
            while ( (ArrayT)0 != *end++ )
                ;
            // Want the null terminator included
            m_cchSize = end - m_ptr;
        }
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
    int GetResourcePath( char * buffer, size_t cchBuffer ) const;

    static const int MINS_PER_HOUR = 60;
    static const int MINS_PER_DAY = 24 * MINS_PER_HOUR;

    // Returns the bias between the supplied utc and local times.
    // utc = local + bias
    static int BiasInMinutes( const struct tm & utc, const struct tm & local )
    {
        int bias = 0;
        if ( utc.tm_mon != local.tm_mon )
        {
            // Offset crosses month boundary so one of two must be first day of month
            if ( 1 == utc.tm_mday )
                bias += MINS_PER_DAY;
            else
            {
                assert( 1 == local.tm_mday );
                bias -= MINS_PER_DAY;
            }
        }
        else
        {
            bias += MINS_PER_DAY * (utc.tm_mday - local.tm_mday);
        }

        bias += MINS_PER_HOUR * (utc.tm_hour - local.tm_hour);
        bias += (utc.tm_min - local.tm_min);

        // Round based on diff in secs, in case utc/local straddle a day with leap seconds
        int secs_diff = (utc.tm_sec - local.tm_sec);
        if ( 29 < secs_diff )
            ++bias;
        else if ( secs_diff < -29 )
            --bias;

        return bias;
    }

    // Returns both standard and daylight savings biases for the current year
    // utc = local + bias
    // Both might be equal if DST is not honored
    // If platform doesn't know if bias is DST or standard (ie. unknown)
    // then standard time is assumed.
    // Note that applying current year's biases to dates from other years may result
    // in incorrect time adjustments since regions change their rules over time.
    // The current SNAC driver code uses this approach as well so we are doing this
    // to preserve consistent behavior.  If SNAC changes to lookup the offsets that
    // were effective for a given date then we should update our logic here as well.
    static DWORD TimeZoneBiases( int * stdInMinutes, int * dstInMinutes )
    {
        struct tm local, utc;
        // Find current year
        time_t now = time( NULL );
        if ( (time_t)(-1) == now || NULL == localtime_r(&now, &local) )
            return ERROR_INVALID_DATA;

        // Find bias for first of each month until both STD and DST are found
        // Possible perf improvements (can wait until perf tests indicate a need):
        //      Just use Dec 21 and Jun 21 (near the two soltices)
        //      Or calc once and cache (must be thread safe)
        bool foundUNK = false;
        bool foundSTD = false;
        bool foundDST = false;
        int std_bias = 0;
        int dst_bias = 0;

        local.tm_mday = 1;
        for ( int mon = 0; mon < 12; ++mon )
        {
            local.tm_mon = mon;
            if ( (time_t)(-1) == (now = mktime(&local)) || NULL == gmtime_r(&now, &utc) )
                return ERROR_INVALID_DATA;

            if ( 0 < local.tm_isdst )
            {
                if ( !foundDST )
                {
                    dst_bias = BiasInMinutes( utc, local );
                    foundDST = true;
                    if ( foundSTD )
                        break; // Done checking when both STD & DST are found
                }
            }
            else
            {
                // Time is STD or unknown, put in STD
                if ( !foundSTD )
                {
                    std_bias = BiasInMinutes( utc, local );
                    if ( local.tm_isdst < 0 )
                        foundUNK = true;
                    else
                    {
                        foundSTD = true;
                        if ( foundDST )
                            break; // Done checking when both STD and DST are found
                    }
                }
            }
        }

        // At least one of STD, DST, or unknown must have been set
        assert( foundSTD || foundDST || foundUNK );

        // For zones that don't observe DST (somewhat common),
        // report DST bias as the same as STD
        if ( !foundDST )
            dst_bias = std_bias;

        // For zones that ONLY observe DST (extremely rare if at all),
        // report STD bias as the same as DST
        if ( !foundSTD && !foundUNK )
            std_bias = dst_bias;

        *stdInMinutes = std_bias;
        *dstInMinutes = dst_bias;

        return ERROR_SUCCESS;
    }
#endif

    static DWORD CurrentLocalTime( LPSYSTEMTIME pTime );

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

    // Given the start byte, how many total bytes are expected for
    // this code point.  If start is a UTF8 trail byte, then 1 is returned.
    static UINT CchExpectedNextChar( UINT codepage, BYTE start )
    {
        if ( 0 == (start & (char)0x80) )
            return 1; // ASCII
        else if ( CP_UTF8 == codepage )
            return IsUtf8LeadByte(start) ? CchUtf8CodePt(start) : 1;
        else if ( IsDBCSLeadByteEx(codepage, start) )
            return 2;
        else
            return 1;
    }

    // Returns the number of bytes that need to be trimmed to avoid splitting
    // a multi-byte code point sequence at the end of the buffer.
    // Returns zero if a trailing UTF8 code value is found but no
    // matching lead byte was found for it (ie. invalid, dangling trail byte).
    _Ret_range_(0, cchBuffer) static UINT TrimPartialCodePt( UINT codepage, _In_count_(cchBuffer) const BYTE * buffer, size_t cchBuffer )
    {
        if ( 0 == cchBuffer )
            return 0;

        if ( CP_UTF8 == codepage )
        {
            return TrimPartialUtf8CodePt( buffer, cchBuffer );
        }
        else
        {
            size_t i = cchBuffer;
            for ( ; 0 < i; --i )
            {
                if ( !IsDBCSLeadByteEx( codepage, buffer[i-1] ) )
                    break;
            }
            // If odd, then last byte is truly a lead byte so return 1 byte to trim
            return ((cchBuffer-i) & 1) ? 1 : 0;
        }
    }

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
    // Allocates destination buffer to match required size
    // Template is used so call can provide allocation policy
    // Used instead of the Windows API pattern of calling with zero dest buffer size to find
    // required buffer size, followed by second call with newly allocated buffer.
    template< typename AllocT >
    static size_t ToUtf16( UINT srcCodePage, const char * src, SSIZE_T cchSrc, __deref_out_ecount(1) WCHAR ** dest, DWORD * pErrorCode = NULL );
    template< typename AllocT >
    static size_t ToUtf16Strict( UINT srcCodePage, const char * src, SSIZE_T cchSrc, __deref_out_ecount(1) WCHAR ** dest, DWORD * pErrorCode = NULL );
    template< typename AllocT >
    static size_t FromUtf16( UINT destCodePage, const WCHAR * src, SSIZE_T cchSrc, __deref_out_ecount(1) char ** dest, bool * pHasDataLoss = NULL, DWORD * pErrorCode = NULL );
	template< typename AllocT >
	static size_t FromUtf16Strict(UINT destCodePage, const WCHAR * src, SSIZE_T cchSrc, __deref_out_ecount(1) char ** dest, bool * pHasDataLoss = NULL, DWORD * pErrorCode = NULL);



    // -----------------------------------------------------------------------
    // Public Member Functions

#ifndef TIME_ZONE_ID_UNKNOWN
    #define TIME_ZONE_ID_UNKNOWN  0
    #define TIME_ZONE_ID_STANDARD 1
    #define TIME_ZONE_ID_DAYLIGHT 2
#endif
    // pTZInfo, if supplied, holds one of the above defined values
    DWORD CurrentTimeZoneBias( LONG * offsetInMinutes, DWORD * pTZInfo = NULL ) const;

    // The Ansi code page, always UTF8 for Linux
    UINT AnsiCP() const;
    // Used for files (e.g. returns 437 on US Windows, UTF8 for Linux) 
    UINT OemCP() const;
    // Returns UTF-16LE for all platforms (LE == Little Endian)
    UINT WideCP() const
    {
        return CP_UTF16;
    }

    // Performs case folding to lower case using the current system locale
    // Replaces calls to LCMapStringA
    size_t ToLower( const char * src, SSIZE_T cchSrc, __out_ecount_opt(cchDest) char * dest, size_t cchDest, DWORD * pErrorCode = NULL ) const;

#ifndef CSTR_ERROR
    #define CSTR_ERROR                0           // compare failed
    #define CSTR_LESS_THAN            1           // string 1 less than string 2
    #define CSTR_EQUAL                2           // string 1 equal to string 2
    #define CSTR_GREATER_THAN         3           // string 1 greater than string 2
#endif
    // String comparison using the rules of the current system locale.
    // Replaces calls to CompareString
    // Ignoring width (Bing for "Full Width Characters") has no affect on Linux
    // Return value is one of the above defined values.
    // On error, pErrorCode has result of GetLastError() (do not call GetLastError directly since it isn't portable).
    int Compare( const char * left, SSIZE_T cchLeft, const char * right, SSIZE_T cchRight, DWORD * pErrorCode = NULL ) const;
    int CompareIgnoreCase( const char * left, SSIZE_T cchLeft, const char * right, SSIZE_T cchRight, DWORD * pErrorCode = NULL ) const;
    int CompareIgnoreWidth( const char * left, SSIZE_T cchLeft, const char * right, SSIZE_T cchRight, DWORD * pErrorCode = NULL ) const;
    int CompareIgnoreCaseAndWidth( const char * left, SSIZE_T cchLeft, const char * right, SSIZE_T cchRight, DWORD * pErrorCode = NULL ) const;




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

    static int CompareWithFlags( DWORD flags, const char * left, SSIZE_T cchLeft, const char * right, SSIZE_T cchRight, DWORD * pErrorCode = NULL );

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

    // Returns the number of bytes that need to be trimmed to avoid splitting
    // a UTF8 code point sequence at the end of the buffer.
    // Returns zero for ASCII.
    // Also returns zero if a trailing UTF8 code value is found but no
    // matching lead byte was found for it (ie. invalid, dangling trail byte).
    static UINT TrimPartialUtf8CodePt( const BYTE * buffer, size_t cchBuffer )
    {
        if ( 0 == cchBuffer )
            return 0;

        if ( 0 == (buffer[cchBuffer-1] & 0x80) )
        {
            // Last char is ASCII so no trim needed
            return 0;
        }

        // Last char is non-initial byte of multibyte utf8 sequence
        // Need to determine if it is the last (ie. no trim need)
        UINT cchMax = MaxCharCchSize( CP_UTF8 );
        for ( UINT i = 1; 0 < cchBuffer && i <= cchMax; --cchBuffer, ++i )
        {
            if ( IsUtf8LeadByte(buffer[cchBuffer-1]) )
            {
                // Found initial byte, verify size of sequence
                UINT cchExpected = CchUtf8CodePt( buffer[cchBuffer-1] );
                if ( i == cchExpected )
                    return 0; // utf8 sequence is complete so no trim needed
                else
                {
                    assert( i <= cchBuffer );
                    return i; // trim the incomplete sequence
                }
            }
        }

        // Did not find initial utf8 byte so trim nothing
        return 0;
    }
};



// Convenience wrapper for converting from UTF16 into a newly
// allocated char[].  Class behaves like auto_ptr (will free in dtor,
// but has Release method so caller can take ownership of memory).
template< typename AllocT = ArrayTAllocator< char > >
struct AutoCharArray : public AutoArray< char, AllocT >
{
    size_t AllocConvertFromUtf16( UINT destCodePage, const WCHAR * src, SSIZE_T cchSrc, bool * pHasDataLoss = NULL, DWORD * pErrorCode = NULL )
    {
        char * converted = NULL;
        size_t cchCvt = SystemLocale::FromUtf16< AllocT >( destCodePage, src, cchSrc, &converted, pHasDataLoss, pErrorCode );
        if ( 0 < cchCvt )
        {
            this->Free();
            this->m_ptr = converted;
            this->m_cchSize = cchCvt;
        }
        return cchCvt;
    }
};

// Convenience wrapper for converting to UTF16 into a newly
// allocated WCHAR[].  Class behaves like auto_ptr (will free in dtor,
// but has Release method so caller can take ownership of memory).
template< typename AllocT = ArrayTAllocator< WCHAR > >
struct AutoWCharArray : public AutoArray< WCHAR, AllocT >
{
    size_t AllocConvertToUtf16( UINT destCodePage, const char * src, SSIZE_T cchSrc, bool * pHasDataLoss = NULL, DWORD * pErrorCode = NULL )
    {
        WCHAR * converted = NULL;
        size_t cchCvt = SystemLocale::ToUtf16< AllocT >( destCodePage, src, cchSrc, &converted, pErrorCode );
        if ( 0 < cchCvt )
        {
            this->Free();
            this->m_ptr = converted;
            this->m_cchSize = cchCvt;
        }
        return cchCvt;
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

inline UINT SystemLocale::OemCP() const
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

inline int SystemLocale::CompareIgnoreWidth( const char * left, SSIZE_T cchLeft, const char * right, SSIZE_T cchRight, DWORD * pErrorCode ) const
{
    // XPLAT_ODBC_TODO: VSTS 806013 MPLAT: Support IgnoreWidth for SNI string comparisons
    return Compare( left, cchLeft, right, cchRight, pErrorCode );
}

inline int SystemLocale::CompareIgnoreCaseAndWidth( const char * left, SSIZE_T cchLeft, const char * right, SSIZE_T cchRight, DWORD * pErrorCode ) const
{
    // XPLAT_ODBC_TODO: VSTS 806013 MPLAT: Support IgnoreWidth for SNI string comparisons
    return CompareIgnoreCase( left, cchLeft, right, cchRight, pErrorCode );
}

template< typename AllocT >
inline size_t SystemLocale::ToUtf16( UINT srcCodePage, const char * src, SSIZE_T cchSrc, WCHAR ** dest, DWORD * pErrorCode )
{
    srcCodePage = ExpandSpecialCP( srcCodePage );
    EncodingConverter cvt( CP_UTF16, srcCodePage );
    if ( !cvt.Initialize() )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_INVALID_PARAMETER;
        return 0;
    }
    size_t cchSrcActual = (cchSrc < 0 ? (1+strlen(src)) : cchSrc);
    bool hasLoss;
    return cvt.Convert< WCHAR, char, AllocT >( dest, src, cchSrcActual, false, &hasLoss, pErrorCode );
}

template< typename AllocT >
inline size_t SystemLocale::ToUtf16Strict( UINT srcCodePage, const char * src, SSIZE_T cchSrc, WCHAR ** dest, DWORD * pErrorCode )
{
    srcCodePage = ExpandSpecialCP( srcCodePage );
    EncodingConverter cvt( CP_UTF16, srcCodePage );
    if ( !cvt.Initialize() )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_INVALID_PARAMETER;
        return 0;
    }
    size_t cchSrcActual = (cchSrc < 0 ? (1+strlen(src)) : cchSrc);
    bool hasLoss;
    return cvt.Convert< WCHAR, char, AllocT >( dest, src, cchSrcActual, true, &hasLoss, pErrorCode );
}

template< typename AllocT >
inline size_t SystemLocale::FromUtf16( UINT destCodePage, const WCHAR * src, SSIZE_T cchSrc, char ** dest, bool * pHasDataLoss, DWORD * pErrorCode )
{
    destCodePage = ExpandSpecialCP( destCodePage );
    EncodingConverter cvt( destCodePage, CP_UTF16 );
    if ( !cvt.Initialize() )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_INVALID_PARAMETER;
        return 0;
    }
    size_t cchSrcActual = (cchSrc < 0 ? (1+mplat_wcslen(src)) : cchSrc);
    bool hasLoss;
    return cvt.Convert< char, WCHAR, AllocT >( dest, src, cchSrcActual, false, &hasLoss, pErrorCode );
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

inline DWORD SystemLocale::CurrentTimeZoneBias( LONG * offsetInMinutes, DWORD * pTZInfo ) const
{
    TIME_ZONE_INFORMATION tzi;
    DWORD tzInfo;
    if ( NULL == offsetInMinutes )
        return ERROR_INVALID_PARAMETER;
    else if ( TIME_ZONE_ID_INVALID == (tzInfo = GetTimeZoneInformation(&tzi)) )
        return GetLastError();
    else
    {
        *offsetInMinutes = tzi.Bias;
        if ( NULL != pTZInfo )
            *pTZInfo = tzInfo;

        return ERROR_SUCCESS;
    }
}

inline DWORD SystemLocale::CurrentLocalTime( LPSYSTEMTIME pTime )
{
    GetLocalTime( pTime );
    return ERROR_SUCCESS;
}

inline UINT SystemLocale::AnsiCP() const
{
    return GetACP();
}

inline UINT SystemLocale::OemCP() const
{
    return GetOEMCP();
}

inline UINT SystemLocale::MaxCharCchSize( UINT codepage )
{
    CPINFO cpinfo;
    BOOL rc = GetCPInfo( codepage, &cpinfo );
    return (rc ? cpinfo.MaxCharSize : 0);
}

inline size_t SystemLocale::ToLower( const char * src, SSIZE_T cchSrc, char * dest, size_t cchDest, DWORD * pErrorCode ) const
{
    // Windows API takes 'int' sized parameters
    if ( cchSrc < -1 || 0x7FFFFFF < cchSrc || 0x7FFFFFF < cchDest )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_INVALID_PARAMETER;

        return 0;
    }

OACR_WARNING_PUSH
OACR_WARNING_DISABLE(SYSTEM_LOCALE_MISUSE , " INTERNATIONALIZATION BASELINE AT KATMAI RTM. FUTURE ANALYSIS INTENDED. ")
OACR_WARNING_DISABLE(ANSI_APICALL, " Keeping the ANSI API for now. ")
    int cch = LCMapStringA(
        LOCALE_SYSTEM_DEFAULT,
        LCMAP_LOWERCASE,
        src,
        (int)cchSrc,
        dest,
        (int)cchDest );
OACR_WARNING_POP

    return ReturnCchResult( cch, pErrorCode );
}

inline int SystemLocale::CompareWithFlags( DWORD flags, const char * left, SSIZE_T cchLeft, const char * right, SSIZE_T cchRight, DWORD * pErrorCode )
{
    // Windows API takes 'int' sized parameters
    if ( cchLeft < -1 || 0x7FFFFFF < cchLeft || cchRight < -1 || 0x7FFFFFF < cchRight )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_INVALID_PARAMETER;

        return 0;
    }

OACR_WARNING_PUSH
OACR_WARNING_DISABLE(SYSTEM_LOCALE_MISUSE , " INTERNATIONALIZATION BASELINE AT KATMAI RTM. FUTURE ANALYSIS INTENDED. ")
    int cmp = CompareStringA( LOCALE_SYSTEM_DEFAULT, flags, left, (int)cchLeft, right, (int)cchRight );
OACR_WARNING_POP
    if ( NULL != pErrorCode )
    {
        *pErrorCode = (CSTR_ERROR == cmp ? GetLastError() : ERROR_SUCCESS);
    }
    return cmp;
}

inline int SystemLocale::Compare( const char * left, SSIZE_T cchLeft, const char * right, SSIZE_T cchRight, DWORD * pErrorCode ) const
{
    return CompareWithFlags( 0, left, cchLeft, right, cchRight, pErrorCode );
}

inline int SystemLocale::CompareIgnoreCase( const char * left, SSIZE_T cchLeft, const char * right, SSIZE_T cchRight, DWORD * pErrorCode ) const
{
    return CompareWithFlags( NORM_IGNORECASE, left, cchLeft, right, cchRight, pErrorCode );
}

inline int SystemLocale::CompareIgnoreCaseAndWidth( const char * left, SSIZE_T cchLeft, const char * right, SSIZE_T cchRight, DWORD * pErrorCode ) const
{
    return CompareWithFlags( NORM_IGNORECASE|NORM_IGNOREWIDTH, left, cchLeft, right, cchRight, pErrorCode );
}

inline int SystemLocale::CompareIgnoreWidth( const char * left, SSIZE_T cchLeft, const char * right, SSIZE_T cchRight, DWORD * pErrorCode ) const
{
    return CompareWithFlags( NORM_IGNOREWIDTH, left, cchLeft, right, cchRight, pErrorCode );
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

template< typename AllocT >
inline size_t SystemLocale::ToUtf16( UINT srcCodePage, const char * src, SSIZE_T cchSrc, WCHAR ** dest, DWORD * pErrorCode )
{
    size_t cchCvt = FastAsciiMultiByteToWideChar( srcCodePage, src, cchSrc, NULL, 0, pErrorCode );
    if ( 0 < cchCvt )
    {
        AutoArray< WCHAR, AllocT > newDestBuffer( cchCvt );
        cchCvt = FastAsciiMultiByteToWideChar( srcCodePage, src, cchSrc, newDestBuffer.m_ptr, cchCvt, pErrorCode );
        if ( 0 < cchCvt )
            *dest = newDestBuffer.Detach();
    }
    return cchCvt;
}

template< typename AllocT >
inline size_t SystemLocale::ToUtf16Strict( UINT srcCodePage, const char * src, SSIZE_T cchSrc, WCHAR ** dest, DWORD * pErrorCode )
{
    size_t cchCvt = FastAsciiMultiByteToWideChar( srcCodePage, src, cchSrc, NULL, 0, pErrorCode, true );
    if ( 0 < cchCvt )
    {
        AutoArray< WCHAR, AllocT > newDestBuffer( cchCvt );
        cchCvt = FastAsciiMultiByteToWideChar( srcCodePage, src, cchSrc, newDestBuffer.m_ptr, cchCvt, pErrorCode, true );
        if ( 0 < cchCvt )
            *dest = newDestBuffer.Detach();
    }
    return cchCvt;
}

template< typename AllocT >
inline size_t SystemLocale::FromUtf16( UINT destCodePage, const WCHAR * src, SSIZE_T cchSrc, char ** dest, bool * pHasDataLoss, DWORD * pErrorCode )
{
    BOOL dataloss = FALSE;
    size_t cchCvt = FastAsciiWideCharToMultiByte( destCodePage, src, cchSrc, NULL, 0, &dataloss, pErrorCode );
    if ( 0 < cchCvt )
    {
        AutoArray< char, AllocT > newDestBuffer( cchCvt );
        cchCvt = FastAsciiWideCharToMultiByte( destCodePage, src, cchSrc, newDestBuffer.m_ptr, cchCvt, &dataloss, pErrorCode );
        if ( 0 < cchCvt )
            *dest = newDestBuffer.Detach();
    }
    if ( NULL != pHasDataLoss )
    {
        *pHasDataLoss = (FALSE != dataloss);
    }
    return cchCvt;
}

// ! MPLAT_UNIX ----------------------------------------------------------------
#endif

#endif // __LOCALIZATION_HPP__

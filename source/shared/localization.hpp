//---------------------------------------------------------------------------------------------------------------------------------
// File: Localization.hpp
//
// Contents: Contains portable classes for localization
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

#ifndef __LOCALIZATION_HPP__
#define __LOCALIZATION_HPP__

#include <time.h>
#include <assert.h>
#include "typedefs_for_linux.h"

#include <locale>

#define CP_UTF8  65001
#define CP_ISO8859_1 28591
#define CP_ISO8859_2 28592
#define CP_ISO8859_3 28593
#define CP_ISO8859_4 28594
#define CP_ISO8859_5 28595
#define CP_ISO8859_6 28596
#define CP_ISO8859_7 28597
#define CP_ISO8859_8 28598
#define CP_ISO8859_9 28599
#define CP_ISO8859_13 28603
#define CP_ISO8859_15 28605
#define CP_UTF16 1200
#define CP_ACP  0           // default to ANSI code page

bool _setLocale(const char * localeName, std::locale ** pLocale);
void setDefaultLocale(const char ** localeName, std::locale ** pLocale);

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
    static const SystemLocale & Singleton();

    static const int MINS_PER_HOUR = 60;
    static const int MINS_PER_DAY = 24 * MINS_PER_HOUR;

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

    // This version is for non-null terminated strings.
    // Last ptr will be one past end of buffer.
    static char * NextChar( UINT codepage, const char * start, size_t cchBytesLeft );

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
    // CP1252 to UTF16 conversion which does not involve iconv
    static size_t CP1252ToUtf16( const char *src, SSIZE_T cchSrc, WCHAR *dest, size_t cchDest, DWORD *pErrorCode );

    // UTF8/16 conversion which does not involve iconv
    static size_t Utf8To16( const char *src, SSIZE_T cchSrc, WCHAR *dest, size_t cchDest, DWORD *pErrorCode );
    static size_t Utf8From16( const WCHAR *src, SSIZE_T cchSrc, char *dest, size_t cchDest, DWORD *pErrorCode );
    static size_t Utf8To16Strict( const char *src, SSIZE_T cchSrc, WCHAR *dest, size_t cchDest, DWORD *pErrorCode );
    static size_t Utf8From16Strict( const WCHAR *src, SSIZE_T cchSrc, char *dest, size_t cchDest, DWORD *pErrorCode );

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

    std::locale * m_pLocale;
    UINT m_uAnsiCP;

    explicit SystemLocale( const char * localeName );
    ~SystemLocale();

    static UINT ExpandSpecialCP( UINT codepage )
    {
        // skip SQLSRV_ENCODING_CHAR
        return (codepage <= 3 ? Singleton().m_uAnsiCP : codepage);
    }

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
// Inlines

#include "globalization.h"

inline UINT SystemLocale::AnsiCP() const
{
    return m_uAnsiCP;
}

inline UINT SystemLocale::MaxCharCchSize( UINT codepage )
{
    codepage = ExpandSpecialCP( codepage );
    switch ( codepage )
    {
    case CP_UTF8:
    case 54936:
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

#endif // __LOCALIZATION_HPP__

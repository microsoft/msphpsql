//---------------------------------------------------------------------------------------------------------------------------------
// File: LocalizationImpl.hpp
//
// Contents: Contains non-inline code for the SystemLocale class
//			 Must be included in one c/cpp file per binary
//			 A build error will occur if this inclusion policy is not followed
//
// Microsoft Drivers 4.0 for PHP for SQL Server
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

#include "localization.hpp"

#include "globalization.h"
#include "StringFunctions.h"

struct cp_iconv
{
    UINT CodePage;
    const char * IConvEncoding;

    static const cp_iconv g_cp_iconv[];
    static const size_t g_cp_iconv_count;

    static int GetIndex( UINT codepage )
    {
        for ( size_t idx = 0; idx < g_cp_iconv_count; ++idx )
        {
            if ( g_cp_iconv[idx].CodePage == codepage )
                return (int)idx;
        }
        // Should never be an unknown code page
        assert( false );
        return -1;
    }
};

// Array of CodePage-to-IConvEncoding mappings
// First few elements are most commonly used
const cp_iconv cp_iconv::g_cp_iconv[] = {
    { 65001, "UTF-8" },
    {  1200, "UTF-16LE" },
    {     3, "UTF-8" },
    {  1252, "CP1252//TRANSLIT" },
    {   850, "CP850//TRANSLIT" },
    {   437, "CP437//TRANSLIT" },
    {   874, "CP874//TRANSLIT" },
    {   932, "CP932//TRANSLIT" },
    {   936, "CP936//TRANSLIT" },
    {   949, "CP949//TRANSLIT" },
    {   950, "CP950//TRANSLIT" },
    {  1250, "CP1250//TRANSLIT" },
    {  1251, "CP1251//TRANSLIT" },
    {  1253, "CP1253//TRANSLIT" },
    {  1254, "CP1254//TRANSLIT" },
    {  1255, "CP1255//TRANSLIT" },
    {  1256, "CP1256//TRANSLIT" },
    {  1257, "CP1257//TRANSLIT" },
    {  1258, "CP1258//TRANSLIT" },
    { 12000, "UTF-32LE" }
};
const size_t cp_iconv::g_cp_iconv_count = ARRAYSIZE(cp_iconv::g_cp_iconv);

#ifdef MPLAT_UNIX

class IConvCachePool
{
    SLIST_HEADER m_Pool[cp_iconv::g_cp_iconv_count][cp_iconv::g_cp_iconv_count];

    IConvCachePool( const IConvCachePool & );
    IConvCachePool & operator=( const IConvCachePool & );

    // This bool indicates that the iconv pool is no longer available.
    // For the driver,lis flag indicates the pool can no longer be used.
    // Global destructors will be called by a single thread so this flag does not
    // need thread synch protection.
    static bool s_PoolDestroyed;

    IConvCachePool()
    {
        for ( int dstIdx = 0; dstIdx < cp_iconv::g_cp_iconv_count; ++dstIdx )
        {
            for ( int srcIdx = 0; srcIdx < cp_iconv::g_cp_iconv_count; ++srcIdx )
            {
                InitializeSListHead( &m_Pool[dstIdx][srcIdx] );
            }
        }
    }

    ~IConvCachePool()
    {
        IConvCachePool::s_PoolDestroyed = true;

        // Clean up remaining nodes
        for ( int dstIdx = 0; dstIdx < cp_iconv::g_cp_iconv_count; ++dstIdx )
        {
            for ( int srcIdx = 0; srcIdx < cp_iconv::g_cp_iconv_count; ++srcIdx )
            {
		        IConvCache * pNode = static_cast<IConvCache*>( InterlockedFlushSList(&m_Pool[dstIdx][srcIdx]) );
                while ( NULL != pNode )
                {
                    IConvCache * pNext = static_cast<IConvCache*>( pNode->Next );
                    delete pNode;
                    pNode = pNext;
                }
            }
        }
    }

    USHORT Depth( int dstIdx, int srcIdx )
    {
        assert( 0 <= dstIdx && dstIdx < cp_iconv::g_cp_iconv_count );
        assert( 0 <= srcIdx && srcIdx < cp_iconv::g_cp_iconv_count );
        return QueryDepthSList( &m_Pool[dstIdx][srcIdx] );
    }

    // If this returns NULL, then caller must allocate their own iconv_t.
    // It will return NULL if allocation for a new instance failed (out of memory).
    const IConvCache * Borrow( int dstIdx, int srcIdx )
    {
        assert( 0 <= dstIdx && dstIdx < cp_iconv::g_cp_iconv_count );
        assert( 0 <= srcIdx && srcIdx < cp_iconv::g_cp_iconv_count );

        const IConvCache * pCache = static_cast<const IConvCache*>( InterlockedPopEntrySList(&m_Pool[dstIdx][srcIdx]) );
        if ( NULL == pCache )
        {
            const IConvCache * pNewCache = new IConvCache( dstIdx, srcIdx );
            if ( NULL != pNewCache )
            {
                if ( INVALID_ICONV != pNewCache->GetIConv() )
                    pCache = pNewCache;
                else
                    delete pNewCache;
            }
        }
        return pCache;
    }

    void Return( const IConvCache * pCache, int dstIdx, int srcIdx )
    {
        assert( pCache );
        assert( 0 <= dstIdx && dstIdx < cp_iconv::g_cp_iconv_count );
        assert( 0 <= srcIdx && srcIdx < cp_iconv::g_cp_iconv_count );

        // Setting an arbitrary limit to prevent unbounded memory use by the pool.
        // Want this to be large enough for a substantial number of concurrent threads.
        const USHORT MAX_POOL_SIZE = 1024;

        if ( INVALID_ICONV != pCache->GetIConv() && Depth(dstIdx, srcIdx) < MAX_POOL_SIZE )
        {
            SLIST_ENTRY * pNode = const_cast<IConvCache*>( pCache );
            InterlockedPushEntrySList( &m_Pool[dstIdx][srcIdx], pNode );
        }
        else
        {
            delete pCache;
        }
    }

    static IConvCachePool & Singleton()
    {
        // GCC ensures that function scoped static initializers are threadsafe
        // We must not use the -fno-threadsafe-statics compiler option
#if !defined(__GNUC__) || defined(NO_THREADSAFE_STATICS)
        #error "Relying on GCC's threadsafe initialization of local statics."
#endif
        static IConvCachePool s_Pool;
        return s_Pool;
    }

public:
    static const IConvCache * BorrowCache( UINT dstCP, UINT srcCP )
    {
        int dstIdx = cp_iconv::GetIndex(dstCP);
        int srcIdx = cp_iconv::GetIndex(srcCP);

        if ( -1 == dstIdx || -1 == srcIdx )
            return NULL;
        else if ( !s_PoolDestroyed )
            return Singleton().Borrow( dstIdx, srcIdx );
        else
            return new IConvCache( dstIdx, srcIdx );
    }

    static void ReturnCache( const IConvCache * pCache, UINT dstCP, UINT srcCP )
    {
        int dstIdx = cp_iconv::GetIndex(dstCP);
        int srcIdx = cp_iconv::GetIndex(srcCP);

        if ( -1 != dstIdx && -1 != srcIdx && !s_PoolDestroyed )
            Singleton().Return( pCache, dstIdx, srcIdx );
        else
            delete pCache;
    }

    static USHORT Depth( UINT dstCP, UINT srcCP )
    {
        if ( IConvCachePool::s_PoolDestroyed )
            return 0;
        else
        {
            int dstIdx = cp_iconv::GetIndex(dstCP);
            int srcIdx = cp_iconv::GetIndex(srcCP);

            if ( -1 == dstIdx || -1 == srcIdx )
                return 0;
            else
                return Singleton().Depth( dstIdx, srcIdx );
        }
    }
};


bool IConvCachePool::s_PoolDestroyed = false;

#ifdef DEBUG
// This is only used by unit tests.
// Product code should directly use IConvCachePool::Depth from
// within this translation unit.
USHORT GetIConvCachePoolDepth( UINT dstCP, UINT srcCP )
{
    return IConvCachePool::Depth( dstCP, srcCP );
}
#endif // DEBUG

IConvCache::IConvCache( int dstIdx, int srcIdx )
    :   m_iconv( iconv_open(
            cp_iconv::g_cp_iconv[dstIdx].IConvEncoding,
            cp_iconv::g_cp_iconv[srcIdx].IConvEncoding) )
{
}

IConvCache::~IConvCache()
{
    if ( INVALID_ICONV != m_iconv )
        iconv_close( m_iconv );
}

#endif // MPLAT_UNIX

EncodingConverter::EncodingConverter( UINT dstCodePage, UINT srcCodePage )
    :   m_dstCodePage( dstCodePage ),
        m_srcCodePage( srcCodePage )
#ifdef MPLAT_UNIX
        , m_pCvtCache( NULL )
#endif
{
}

EncodingConverter::~EncodingConverter()
{
#ifdef MPLAT_UNIX
    if ( NULL != m_pCvtCache )
    {
        IConvCachePool::ReturnCache( m_pCvtCache, m_dstCodePage, m_srcCodePage );
    }
#endif
}

bool EncodingConverter::Initialize()
{
#if defined(MPLAT_UNIX)
    if ( !IsValidIConv() )
    {
        m_pCvtCache = IConvCachePool::BorrowCache( m_dstCodePage, m_srcCodePage );
    }
    return IsValidIConv();
#elif defined(MPLAT_WWOWH)
    return true;
#endif
}

//#endif

#ifdef MPLAT_UNIX
// MPLAT_UNIX ----------------------------------------------------------------
#include <locale>

using namespace std;



SystemLocale::SystemLocale( const char * localeName )
    :   m_pLocale( new std::locale(localeName) )
{
}

SystemLocale::~SystemLocale()
{
    delete m_pLocale;
}

const SystemLocale & SystemLocale::Singleton()
{
    // GCC ensures that function scoped static initializers are threadsafe
    // We must not use the -fno-threadsafe-statics compiler option
#if !defined(__GNUC__) || defined(NO_THREADSAFE_STATICS)
    #error "Relying on GCC's threadsafe initialization of local statics."
#endif
    static const SystemLocale s_Default( "en_US.utf8" );
    return s_Default;
}

int SystemLocale::GetResourcePath( char * buffer, size_t cchBuffer ) const
{
    // XPLAT_ODBC_TODO: VSTS 718708 Localization
    // Also need to use AdjustLCID logic when handling more locales
    return snprintf( buffer, cchBuffer, "/opt/microsoft/msodbcsql/share/resources/en_US/");
}

DWORD SystemLocale::CurrentTimeZoneBias( LONG * offsetInMinutes, DWORD * tzinfo ) const
{
    if ( NULL == offsetInMinutes )
        return ERROR_INVALID_PARAMETER;

	time_t now = time( NULL );
    if ( (time_t)(-1) == now )
        return ERROR_NOT_SUPPORTED;

	struct tm utc, local;
    if ( NULL == gmtime_r(&now, &utc) || NULL == localtime_r(&now, &local) )
        return ERROR_INVALID_DATA;
    
    *offsetInMinutes = BiasInMinutes( utc, local );

    if ( NULL != tzinfo )
    {
        *tzinfo = (0 == local.tm_isdst ? TIME_ZONE_ID_STANDARD : (0 < local.tm_isdst ? TIME_ZONE_ID_DAYLIGHT : TIME_ZONE_ID_UNKNOWN));
    }

    return ERROR_SUCCESS;
}

DWORD SystemLocale::CurrentLocalTime( LPSYSTEMTIME pTime )
{
    if ( NULL == pTime )
        return ERROR_INVALID_PARAMETER;

    memset( pTime, 0, sizeof(SYSTEMTIME) );

	time_t now = time( NULL );
    if ( (time_t)(-1) == now )
        return ERROR_NOT_SUPPORTED;

    struct tm local;
    if ( NULL == localtime_r(&now, &local) )
        return ERROR_INVALID_DATA;

    pTime->wYear         = local.tm_year + 1900;
    pTime->wMonth        = local.tm_mon + 1;
    pTime->wDay          = local.tm_mday;
    pTime->wHour         = local.tm_hour;
    pTime->wMinute       = local.tm_min;
    pTime->wSecond       = local.tm_sec;
    pTime->wMilliseconds = 0;
    pTime->wDayOfWeek    = local.tm_wday;

    return ERROR_SUCCESS;
}

size_t SystemLocale::ToUtf16( UINT srcCodePage, const char * src, SSIZE_T cchSrc, WCHAR * dest, size_t cchDest, DWORD * pErrorCode )
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
    return cvt.Convert( dest, cchDest, src, cchSrcActual, false, &hasLoss, pErrorCode );
}

size_t SystemLocale::ToUtf16Strict( UINT srcCodePage, const char * src, SSIZE_T cchSrc, WCHAR * dest, size_t cchDest, DWORD * pErrorCode )
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
    return cvt.Convert( dest, cchDest, src, cchSrcActual, true, &hasLoss, pErrorCode );
}

size_t SystemLocale::FromUtf16( UINT destCodePage, const WCHAR * src, SSIZE_T cchSrc, char * dest, size_t cchDest, bool * pHasDataLoss, DWORD * pErrorCode )
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
    return cvt.Convert( dest, cchDest, src, cchSrcActual, false, &hasLoss, pErrorCode );
}

size_t SystemLocale::FromUtf16Strict(UINT destCodePage, const WCHAR * src, SSIZE_T cchSrc, char * dest, size_t cchDest, bool * pHasDataLoss, DWORD * pErrorCode)
{
	destCodePage = ExpandSpecialCP(destCodePage);
	EncodingConverter cvt(destCodePage, CP_UTF16);
	if (!cvt.Initialize())
	{
		if (NULL != pErrorCode)
			*pErrorCode = ERROR_INVALID_PARAMETER;
		return 0;
	}
	size_t cchSrcActual = (cchSrc < 0 ? (1 + mplat_wcslen(src)) : cchSrc);
	bool hasLoss;
	return cvt.Convert(dest, cchDest, src, cchSrcActual, true, &hasLoss, pErrorCode);
}

size_t SystemLocale::ToLower( const char * src, SSIZE_T cchSrc, char * dest, size_t cchDest, DWORD * pErrorCode ) const
{
    size_t cchSrcActual = (cchSrc < 0 ? (1+strlen(src)) : cchSrc);
    if ( 0 == cchSrcActual )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_INVALID_PARAMETER;
        return 0;
    }
    if ( 0 == cchDest )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_SUCCESS;
        return cchSrcActual;
    }
    else if ( cchDest < cchSrcActual )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
        return 0;
    }
	memcpy_s( dest, cchSrcActual, src, cchSrcActual );

    use_facet< ctype< char > >(*m_pLocale).tolower( dest, dest+cchSrcActual );
    if ( NULL != pErrorCode )
        *pErrorCode = ERROR_SUCCESS;
    return cchSrcActual;
}

int SystemLocale::Compare( const char * left, SSIZE_T cchLeft, const char * right, SSIZE_T cchRight, DWORD * pErrorCode ) const
{
    if ( NULL == left || NULL == right || 0 == cchLeft || 0 == cchRight )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_INVALID_PARAMETER;
        return CSTR_ERROR;
    }

    size_t cchLeftActual = (cchLeft < 0 ? strlen(left) : cchLeft);
    size_t cchRightActual = (cchRight < 0 ? strlen(right) : cchRight);

    int cmp = strncmp( left, right, min(cchLeftActual, cchRightActual) );
    if ( 0 == cmp )
    {
        if ( cchLeftActual < cchRightActual )
            cmp = -1;
        else if ( cchLeftActual > cchRightActual )
            cmp = 1;
    }
    else if ( cmp < 0 )
        cmp = 1; // CompareString is inverse of strcmp
    else
        cmp = -1; // CompareString is inverse of strcmp

    if ( NULL != pErrorCode )
        *pErrorCode = ERROR_SUCCESS;
    return cmp+2;
}

int SystemLocale::CompareIgnoreCase( const char * left, SSIZE_T cchLeft, const char * right, SSIZE_T cchRight, DWORD * pErrorCode ) const
{
    if ( NULL == left || NULL == right || 0 == cchLeft || 0 == cchRight )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_INVALID_PARAMETER;
        return CSTR_ERROR;
    }

    size_t cchLeftActual = (cchLeft < 0 ? strlen(left) : cchLeft);
    size_t cchRightActual = (cchRight < 0 ? strlen(right) : cchRight);

    int cmp = strncasecmp( left, right, min(cchLeftActual, cchRightActual) );
    if ( 0 == cmp )
    {
        if ( cchLeftActual < cchRightActual )
            cmp = -1;
        else if ( cchLeftActual > cchRightActual )
            cmp = 1;
    }
    else if ( cmp < 0 )
        cmp = 1; // CompareString is inverse of strcmp
    else
        cmp = -1; // CompareString is inverse of strcmp

    if ( NULL != pErrorCode )
        *pErrorCode = ERROR_SUCCESS;
    return cmp+2;
}

char * SystemLocale::NextChar( UINT codepage, const char * start, size_t cchBytesLeft )
{
    if ( NULL == start || '\0' == *start || 0 == cchBytesLeft )
        return const_cast<char *>( start );

    char first = *start;
    codepage = ExpandSpecialCP( codepage );
    if ( CP_UTF8 != codepage )
    {
        if ( !IsDBCSLeadByteEx(codepage, first) || '\0' == *(start+1) )
            return const_cast<char *>( start+1 ); // single byte char or truncated double byte char
        else
            return const_cast<char *>( start+2 ); // double byte char
    }

    // CP_UTF8
    // MB utf8 sequences have this format
    //  Lead byte starts with 2 set bits, '11'
    //  Rest of bytes start with one set and one not, '10'

    // ASCII or not first of utf8 sequence
    // If this isn't the first byte of a utf8 sequence, just move one byte at a time
    // since we don't know where the correct boundary is located.
    if ( (char)0 == (first & (char)0x80) || !SystemLocale::IsUtf8LeadByte((BYTE)first) )
        return const_cast<char *>( start+1 );
    else
    {
        // Initial char tells us how many bytes are supposed to be in this sequence
        UINT cchExpectedSize = SystemLocale::CchUtf8CodePt( (BYTE)first );

        // Skip lead bye
        ++start;
        --cchExpectedSize;
        --cchBytesLeft;

        // Proceed to end of utf8 sequence, null term, or end of expected size
        while ( 0 < cchExpectedSize && 0 < cchBytesLeft && (char)0x80 == (*start & (char)0xC0) )
        {
            ++start;
            --cchExpectedSize;
            --cchBytesLeft;
        }
        return const_cast<char *>( start );
    }
}

char * SystemLocale::NextChar( UINT codepage, const char * start )
{
    // Just assume some large max buffer size since caller is saying
    // start is null terminated.
    return NextChar( codepage, start, DWORD_MAX );
}

// MPLAT_UNIX ----------------------------------------------------------------
#else
// !MPLAT_UNIX ----------------------------------------------------------------

//-----------------------------------------------------------------------------------
// IsW2CZeroFlagCodePage
//
// @func Does this code page need special handling for WideCharToMultiByte or
//      MultiByteToWideChar to avoid error code as ERROR_INVALID_PARAMETER to be returned
//
// @rdesc bool
// @flag TRUE  | needs special handling
// @flag FALSE | doesn't need special handling
//-----------------------------------------------------------------------------------

#define IsW2CZeroFlagCodePage(codePage) (((codePage) < 50220) ? FALSE : _IsW2CZeroFlagCodePage(codePage))

inline BOOL _IsW2CZeroFlagCodePage
(
    UINT CodePage
)
{
    assert(CodePage >= 50220);

    // According to MSDN, these code pages need special handling
    // during WideCharToMultiByte call w/r its parameter flags
    if (CodePage == 50220 ||
        CodePage == 50221 ||
        CodePage == 50222 ||
        CodePage == 50225 ||
        CodePage == 50227 ||
        CodePage == 50229 ||
        CodePage == 52936 ||
        CodePage == 54936 ||
        CodePage == 65000 ||
        CodePage == 65001 ||
        CodePage >= 57002 && CodePage <= 57011)
    {
        return TRUE;
    }

    return FALSE;
}

//-------------------------------------------------------------------
// Custom version of MultiByteToWideChar (faster for all ASCII strings)
//      Convert ASCII data (0x00-0x7f) until first non-ASCII data, 
//          calling OS MultiByteToWideChar in that case.
//
size_t SystemLocale::FastAsciiMultiByteToWideChar(
    UINT        CodePage,
    __in_ecount(cch) const char  *pch,  // IN   | source string
    SSIZE_T     cch,                    // IN   | count of characters or -1
    __out_ecount_opt(cwch) PWCHAR pwch, // IN   | Result string
    size_t      cwch,                   // IN   | counter of wcharacters of result buffer or 0
    DWORD*      pErrorCode,             // OUT  | optional pointer to return error code
    bool        bStrict                 // IN   | Return error if invalid chars in src
)
{
    if ( 0 == cch || cch < -1 || NULL == pch )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_INVALID_PARAMETER;

        return 0;
    }

    const char *pchStart = pch;

    // Divide into
    //  Case 1a: cch, do convert
    //  Case 1b: cch, just count
    //  Case 2a: null-term, do convert
    //  Case 2b: null-term, just count 
    if (-1 != cch)
    {
        // 0 <= cch
        //
        // Case 1: We have counter of characters
        if (0 != cch)
        {
            if (0 != cwch)
            {
                // Case 1a: Have to convert, not just calculate necessary space

                // Optimization: When converting first cwch characters, it's not
                // necessary to check for buffer overflow. Also, loop is unrolled.
                size_t cquads = min((size_t)cch, cwch) >> 2;

                while (0 != cquads)
                {
                    unsigned quad = *(unsigned UNALIGNED *)pch;

                    if (quad & 0x80808080)
                        goto general;

                    OACR_WARNING_SUPPRESS ( INCORRECT_VALIDATION, "Due to performance, we suppress this PREFast warning" );
                    *(unsigned UNALIGNED *)pwch = (quad & 0x7F) | ((quad & 0x7F00) << 8);

                    quad >>= 16;

                    OACR_WARNING_SUPPRESS ( POTENTIAL_BUFFER_OVERFLOW_HIGH_PRIORITY, "PREFast incorrectly warns of buffer overrun for cwch < 4, which won't enter this loop." );
                    *(unsigned UNALIGNED *)(pwch+2) = (quad & 0x7F) | ((quad & 0x7F00) << 8);
                    
                    pch += 4;
                    pwch += 4;
                    cch -= 4;
                    cquads --;
                }
                
                // Convert end of string - slower, but the loop will be executed 3 times max
                if (0 != cch)
                {
                    const char *pchEnd = pchStart + cwch;

                    do
                    {
                        unsigned ch = (unsigned)*pch;

                        if (pch == pchEnd)
                        {
                            if ( NULL != pErrorCode )
                                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                            return 0;   // Not enough space
                        }

                        if (ch > 0x7F)
                            goto general;

                        *(pwch++) = (WCHAR)ch;
                        
                        pch++;
                        cch--;
                    } while (0 != cch);

                }
            }
            else
            {
                // Case 1b: Have to calculate necessary space only
                if (SystemLocale::MaxCharCchSize(CodePage) == 1) // SBCS code pages 1char = 1 unc char
                {
                    if ( NULL != pErrorCode )
                        *pErrorCode = ERROR_SUCCESS;
                    return static_cast<size_t>(cch);
                }

                do
                {
                    if ((unsigned)*pch > 0x7F)
                        goto general;

                    pch++;
                } while (0 != --cch);
            }
        }
        
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_SUCCESS;
        return static_cast<size_t>(pch - pchStart);
    }
    else
    {
        // Case 2: zero-terminated string
        if (0 != cwch)
        {
            // Case 2a: Have to convert, not just calculate necessary space
            const char *pchEnd = pch + cwch;

            do
            {
                unsigned ch = (unsigned)*pch;

                if (ch > 0x7F)
                    goto general;
                else
                {
                    *pwch = (WCHAR)ch;
                    pch ++;
                    if (0 == ch)
                    {
                        if ( NULL != pErrorCode )
                            *pErrorCode = ERROR_SUCCESS;
                        return static_cast<size_t>(pch - pchStart);
                    }
                    pwch ++;
                }
            } while (pch != pchEnd);
            
            if ( NULL != pErrorCode )
                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
            return 0;   // Not enough space
        }
        else
        {
            // Case 2b: Have to calculate necessary space
            unsigned ch;

            do
            {
                ch = (unsigned)*pch;

                if (ch > 0x7F)
                    goto general;
                pch ++;
            } while (0 != ch);

            if ( NULL != pErrorCode )
                *pErrorCode = ERROR_SUCCESS;
            return static_cast<size_t>(pch - pchStart);
        }
    }

    // Have to call Win32 API
general:
    {
        size_t cwchConverted;
        size_t cwchUnicode;

        cwchConverted = (pch - pchStart);

        if ( cwch > cwchConverted )
            cwch -= cwchConverted;
        else
            cwch = 0;

        // Windows MBtoWC takes int inputs
        if ( INT32_MAX < cch || INT32_MAX < cwch )
        {
            if ( NULL != pErrorCode )
                *pErrorCode = ERROR_INVALID_PARAMETER;

            return 0;
        }

        cwchUnicode = (UINT)MultiByteToWideChar(
                                    CodePage, 
                                    (IsW2CZeroFlagCodePage(CodePage) ? 0 : MB_PRECOMPOSED)
                                        | (bStrict ? MB_ERR_INVALID_CHARS : 0),
                                    pch, 
                                    (int)cch, 
                                    pwch, 
                                    (int)cwch);

        if ( 0 == cwchUnicode )
        {
            if ( NULL != pErrorCode )
                *pErrorCode = GetLastError();
            return 0;
        }
        else
        {
            if ( NULL != pErrorCode )
                *pErrorCode = ERROR_SUCCESS;
            return (cwchConverted + cwchUnicode);
        }
    }
}

//-------------------------------------------------------------------
// Custom version of WideCharToMultiByte (faster for all ASCII strings)
//      Convert ASCII data (0x00-0x7f) until first non-ASCII data, 
//          calling OS WideCharToMultiByte in that case.
size_t SystemLocale::FastAsciiWideCharToMultiByte
(
    UINT        CodePage,
    const WCHAR *pwch,              // IN   | source string
    SSIZE_T     cwch,               // IN   | count of characters or -1
    __out_ecount(cch) char *pch,    // IN   | Result string
    size_t      cch,                // IN   | Length of result buffer or 0  
    BOOL        *pfDataLoss,        // IN   | True if there was data loss during CP conversion
    DWORD       *pErrorCode
)
{
    if ( 0 == cwch || NULL == pwch || cwch < -1 )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_INVALID_PARAMETER;

        return 0;
    }

    const WCHAR *pwchStart = pwch;
	const char *pchStart = pch;

    // Divide into
    //  Case 1a: cwch, do convert
    //  Case 1b: cwch, just count
    //  Case 2a: null-term, do convert
    //  Case 2b: null-term, just count 
    if (-1 != cwch)
    {
        // Case 1: We have counter of characters
        if (0 != cwch)
        {
            if (0 != cch)
            {
                // Case 1a: Have to convert, not just calculate necessary space

                // Optimization: When converting first cch characters, it's not
                // necessary to check for buffer overflow. Also, loop is unrolled.
                size_t cquads = cch >> 2;

                while (0 != cquads && 4 <= cwch)
                {
                    unsigned pairLo = *(unsigned UNALIGNED *)pwch;
                    unsigned pairHi = *(unsigned UNALIGNED *)(pwch+2);

                    if ((pairLo | pairHi) & 0xFF80FF80)
                        goto general;

                    *(unsigned UNALIGNED *)pch =  (pairLo & 0x7F) | 
                                                    ((pairLo >> 8) & 0x7F00) |
                                                    ((pairHi & 0x7F) << 16) |
                                                    ((pairHi & 0x7F0000) << 8);
                    pch     += 4;
                    pwch    += 4;
                    cwch    -= 4;
                    cquads  --;
                }
                // Convert end of string - slower, but the loop will be executed 3 times max
                if (0 != cwch)
                {
                    const char *pchEnd = pchStart + cch;

                    do
                    {
                        unsigned wch = (unsigned)*pwch;

                        if (pch == pchEnd)
                        {
                            if ( NULL != pErrorCode )
                                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                            return 0;   // Not enough space
                        }
                        
                        if ((unsigned)*pwch > 0x7F)
                            goto general;
                        
                        *(pch ++) = (char) wch;
                        pwch ++;
                        cwch --;
                     } while (0 != cwch);
                }
            }
            else
            {
                // Case 1b: Have to calculate necessary space
                do
                {
                    if ((unsigned)*pwch > 0x7F)
                        goto general;
                    
                    pwch ++;
                    cwch --;
                } while (0 != cwch);
            }
        }
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_SUCCESS;
        return static_cast<size_t>(pwch - pwchStart);
    }
    else
    {
        // Case 2: zero-terminated string
        if (0 != cch)
        {
            // Case 2a: Have to convert, not just calculate necessary space
			const char *pchEnd = pch + cch;

            do
            {
                unsigned wch = (unsigned)*pwch;

                if (wch > 0x7F)
                    goto general;
                else
                {
                    *pch = (char) wch;
                    pwch ++;
                    if (0 == wch)
                    {
                        if ( NULL != pErrorCode )
                            *pErrorCode = ERROR_SUCCESS;
                        return static_cast<size_t>(pwch - pwchStart);
                    }
                    pch ++;
                }
            } while (pch != pchEnd);

            if ( NULL != pErrorCode )
                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
            return 0;   // Not enough space
        }
        else
        {
            // Case 2b: Have to calculate necessary space
            unsigned wch;

            do
            {
                wch = (unsigned)*pwch;
                if (wch > 0x7F)
                    goto general;
                pwch ++;
            } while (0 != wch);

            if ( NULL != pErrorCode )
                *pErrorCode = ERROR_SUCCESS;
            return static_cast<size_t>(pwch - pwchStart);
        }
    }

    // Have to call Win32 API
general:
    {
        size_t cchConverted;
        size_t cchUnicode;

        // initialize output param if any
        if (pfDataLoss)
            *pfDataLoss = FALSE;

        cchConverted = (pwch - pwchStart);
        
        if ( cch > cchConverted )
            cch -= cchConverted;
        else
            cch = 0;

        // Windows MBtoWC takes int inputs
        if ( INT32_MAX < cch || INT32_MAX < cwch )
        {
            if ( NULL != pErrorCode )
                *pErrorCode = ERROR_INVALID_PARAMETER;

            return 0;
        }

        cchUnicode = (UINT)WideCharToMultiByte (
                                    CodePage, 
                                    0, 
                                    pwch, 
                                    (int)cwch,
                                    pch, 
                                    (int)cch, 
                                    NULL, 
                                    IsW2CZeroFlagCodePage(CodePage) ? NULL : pfDataLoss);

        if ( 0 == cchUnicode )
        {
            if ( NULL != pErrorCode )
                *pErrorCode = GetLastError();
            return 0;
        }
        else
        {
            if ( NULL != pErrorCode )
                *pErrorCode = ERROR_SUCCESS;
            return (cchConverted + cchUnicode);
        }
    }
}

// !MPLAT_UNIX ----------------------------------------------------------------
#endif


//---------------------------------------------------------------------------------------------------------------------------------
// File: localizationimpl.cpp
//
// Contents: Contains non-inline code for the SystemLocale class
//           Must be included in one c/cpp file per binary
//           A build error will occur if this inclusion policy is not followed
//
// Microsoft Drivers 5.3 for PHP for SQL Server
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
// CodePage 2 corresponds to binary. If the attribute PDO::SQLSRV_ENCODING_BINARY
// is set, GetIndex() above hits the assert(false) directive unless we include
// CodePage 2 below and assign an empty string to it.
const cp_iconv cp_iconv::g_cp_iconv[] = {
    { 65001, "UTF-8" },
    {  1200, "UTF-16LE" },
    {     3, "UTF-8" },
    {     2, "" },
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
    { CP_ISO8859_1, "ISO8859-1//TRANSLIT" },
    { CP_ISO8859_2, "ISO8859-2//TRANSLIT" },
    { CP_ISO8859_3, "ISO8859-3//TRANSLIT" },
    { CP_ISO8859_4, "ISO8859-4//TRANSLIT" },
    { CP_ISO8859_5, "ISO8859-5//TRANSLIT" },
    { CP_ISO8859_6, "ISO8859-6//TRANSLIT" },
    { CP_ISO8859_7, "ISO8859-7//TRANSLIT" },
    { CP_ISO8859_8, "ISO8859-8//TRANSLIT" },
    { CP_ISO8859_9, "ISO8859-9//TRANSLIT" },
    { CP_ISO8859_13, "ISO8859-13//TRANSLIT" },
    { CP_ISO8859_15, "ISO8859-15//TRANSLIT" },
    { 12000, "UTF-32LE" }
};
const size_t cp_iconv::g_cp_iconv_count = ARRAYSIZE(cp_iconv::g_cp_iconv);

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


EncodingConverter::EncodingConverter( UINT dstCodePage, UINT srcCodePage )
    :   m_dstCodePage( dstCodePage ),
        m_srcCodePage( srcCodePage ),
        m_pCvtCache( NULL )
{
}

EncodingConverter::~EncodingConverter()
{
    if ( NULL != m_pCvtCache )
    {
        IConvCachePool::ReturnCache( m_pCvtCache, m_dstCodePage, m_srcCodePage );
    }
}

bool EncodingConverter::Initialize()
{
    if ( !IsValidIConv() )
    {
        m_pCvtCache = IConvCachePool::BorrowCache( m_dstCodePage, m_srcCodePage );
    }
    return IsValidIConv();
}


#include <locale>

using namespace std;

SystemLocale::SystemLocale( const char * localeName )
    :   m_pLocale( new std::locale(localeName) )
    , m_uAnsiCP(CP_UTF8)
{
    struct LocaleCP
    {
        const char* localeName;
        UINT codePage;
    };
#define CPxxx(cp) { "CP" #cp, cp }
#define ISO8859(n) { "ISO-8859-" #n, CP_ISO8859_ ## n }, \
                   { "8859_" #n, CP_ISO8859_ ## n }, \
                   { "ISO8859-" #n, CP_ISO8859_ ## n }, \
                   { "ISO8859" #n, CP_ISO8859_ ## n }, \
                   { "ISO_8859-" #n, CP_ISO8859_ ## n }, \
                   { "ISO_8859_" #n, CP_ISO8859_ ## n }
    const LocaleCP lcpTable[] = {
        { "utf8", CP_UTF8 },
        { "UTF-8", CP_UTF8 },
        CPxxx(1252), CPxxx(850), CPxxx(437), CPxxx(874), CPxxx(932), CPxxx(936), CPxxx(949), CPxxx(950),
        CPxxx(1250), CPxxx(1251), CPxxx(1253), CPxxx(1254), CPxxx(1255), CPxxx(1256), CPxxx(1257), CPxxx(1258),
        ISO8859(1), ISO8859(2), ISO8859(3), ISO8859(4), ISO8859(5), ISO8859(6),
        ISO8859(7), ISO8859(8), ISO8859(9), ISO8859(13), ISO8859(15),
        { "UTF-32LE", 12000 }
    };
    if (localeName)
    {
        const char *charsetName = strchr(localeName, '.');
        charsetName = charsetName ? charsetName + 1 : localeName;
        for (const LocaleCP& lcp : lcpTable)
        {
           if (!strncasecmp(lcp.localeName, charsetName, strnlen_s(lcp.localeName)))
            {
                m_uAnsiCP = lcp.codePage;
                return;
            }
        }
    }
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
    // get locale from environment and set as default
    static const SystemLocale s_Default(setlocale(LC_ALL, NULL));
    return s_Default;
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
    size_t cchSrcActual = (cchSrc < 0 ? (1+strnlen_s(src)) : cchSrc);
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
    size_t cchSrcActual = (cchSrc < 0 ? (1+strnlen_s(src)) : cchSrc);
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

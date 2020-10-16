//---------------------------------------------------------------------------------------------------------------------------------
// File: localizationimpl.cpp
//
// Contents: Contains non-inline code for the SystemLocale class
//           Must be included in one c/cpp file per binary
//           A build error will occur if this inclusion policy is not followed
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
#ifdef __MUSL__
#define TRANSLIT ""
#else
#define TRANSLIT "//TRANSLIT"
#endif

const cp_iconv cp_iconv::g_cp_iconv[] = {
    { 65001, "UTF-8" },
    {  1200, "UTF-16LE" },
    {     3, "UTF-8" },
    {     2, "" },
    {  1252, "CP1252" TRANSLIT },
    {   850, "CP850" TRANSLIT },
    {   437, "CP437" TRANSLIT },
    {   874, "CP874" TRANSLIT },
    {   932, "CP932" TRANSLIT },
    {   936, "CP936" TRANSLIT },
    {   949, "CP949" TRANSLIT },
    {   950, "CP950" TRANSLIT },
    {  1250, "CP1250" TRANSLIT },
    {  1251, "CP1251" TRANSLIT },
    {  1253, "CP1253" TRANSLIT },
    {  1254, "CP1254" TRANSLIT },
    {  1255, "CP1255" TRANSLIT },
    {  1256, "CP1256" TRANSLIT },
    {  1257, "CP1257" TRANSLIT },
    {  1258, "CP1258" TRANSLIT },
    { 54936, "GB18030" TRANSLIT},
    { CP_ISO8859_1, "ISO8859-1" TRANSLIT },
    { CP_ISO8859_2, "ISO8859-2" TRANSLIT },
    { CP_ISO8859_3, "ISO8859-3" TRANSLIT },
    { CP_ISO8859_4, "ISO8859-4" TRANSLIT },
    { CP_ISO8859_5, "ISO8859-5" TRANSLIT },
    { CP_ISO8859_6, "ISO8859-6" TRANSLIT },
    { CP_ISO8859_7, "ISO8859-7" TRANSLIT },
    { CP_ISO8859_8, "ISO8859-8" TRANSLIT },
    { CP_ISO8859_9, "ISO8859-9" TRANSLIT },
    { CP_ISO8859_13, "ISO8859-13" TRANSLIT },
    { CP_ISO8859_15, "ISO8859-15" TRANSLIT },
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

#ifndef _countof
  #define _countof(obj)     (sizeof(obj)/sizeof(obj[0]))
#endif

const char* DEFAULT_LOCALES[] = {"en_US.UTF-8", "C"};

bool _setLocale(const char * localeName, std::locale ** pLocale)
{
    try
    {
        *pLocale = new std::locale(localeName);
    }
    catch(const std::exception& e)
    {
        return false;
    }

    return true;
}

void setDefaultLocale(const char ** localeName, std::locale ** pLocale)
{
    if(!localeName || !_setLocale(*localeName, pLocale))
    {
        int count = 0;
        while(!_setLocale(DEFAULT_LOCALES[count], pLocale) && count < _countof(DEFAULT_LOCALES))
        {
            count++;
        }
        
        if(localeName)
            *localeName = count < _countof(DEFAULT_LOCALES)?DEFAULT_LOCALES[count]:NULL;
    }
}

SystemLocale::SystemLocale( const char * localeName )
    : m_uAnsiCP(CP_UTF8)
    , m_pLocale(NULL)
{
    setDefaultLocale(&localeName, &m_pLocale);

    // Mapping from locale charset to codepage
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
        { "BIG5", 950 },
        { "BIG5-HKSCS", 950 },
        { "gb18030", 54936 },
        { "gb2312", 936 },
        { "gbk", 936 },
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
    static const SystemLocale s_Default(setlocale(LC_CTYPE, NULL));
    return s_Default;
}


// Convert CP1252 to UTF-16 without requiring iconv or taking a lock.
// This is trivial because, except for the 80-9F range, CP1252 bytes
// directly map to the corresponding UTF-16 codepoint.
size_t SystemLocale::CP1252ToUtf16( const char *src, SSIZE_T cchSrc, WCHAR *dest, size_t cchDest, DWORD *pErrorCode )
{
    const static WCHAR s_1252Map[] =
    {
        0x20AC, 0x003F, 0x201A, 0x0192, 0x201E, 0x2026, 0x2020, 0x2021, 0x02C6, 0x2030, 0x0160, 0x2039, 0x0152, 0x003F, 0x017D, 0x003F,
        0x003F, 0x2018, 0x2019, 0x201C, 0x201D, 0x2022, 0x2013, 0x2014, 0x02DC, 0x2122, 0x0161, 0x203A, 0x0153, 0x003F, 0x017E, 0x0178
    };
    const unsigned char *usrc = reinterpret_cast<const unsigned char*>(src);
    const unsigned char *srcEnd = usrc + cchSrc;
    const WCHAR *destEnd = dest + cchDest;

    while(usrc < srcEnd && dest < destEnd)
    {
        DWORD ucode = *usrc++;
        *dest++ = (ucode <= 127 || ucode >= 160) ? ucode : s_1252Map[ucode - 128];
    }
    pErrorCode && (*pErrorCode = (dest == destEnd && usrc != srcEnd) ? ERROR_INSUFFICIENT_BUFFER : ERROR_SUCCESS);
    return cchDest - (destEnd - dest);
}

// Convert UTF-8 to UTF-16 without requiring iconv or taking a lock.
// 0abcdefg                            -> 0abcdefg 00000000
// 110abcde 10fghijk                   -> defghijk 00000abc
// 1110abcd 10efghij 10klmnop          -> ijklmnop abcdefgh
// 11110abc 10defghi 10jklmno 10pqrstu -> cdfghijk 110110ab nopqrstu 11011lm
size_t SystemLocale::Utf8To16( const char *src, SSIZE_T cchSrc, WCHAR *dest, size_t cchDest, DWORD *pErrorCode )
{
    const unsigned char *usrc = reinterpret_cast<const unsigned char*>(src);
    const unsigned char *srcEnd = usrc + cchSrc;
    const WCHAR *destEnd = dest + cchDest;
    DWORD dummyError;
    if (!pErrorCode)
    {
        pErrorCode = &dummyError;
    }
    *pErrorCode = 0;

    while(usrc < srcEnd && dest < destEnd)
    {
        DWORD ucode = *usrc++;
        if(ucode <= 127) // Most common case for ASCII
        {
            *dest++ = ucode;
        }
        else if(ucode < 0xC0) // unexpected trailing byte 10xxxxxx
        {
            goto Invalid;
        }
        else if(ucode < 0xE0) // 110abcde 10fghijk
        {
            if (usrc >= srcEnd || *usrc < 0x80 || *usrc > 0xBF ||
                (*dest = (ucode & 0x1F)<<6 | (*usrc++ & 0x3F)) < 0x80)
            {
                *dest = 0xFFFD;
            }
            dest++;
        }
        else if(ucode < 0xF0) // 1110abcd 10efghij 10klmnop
        {
            if (usrc >= srcEnd)
            {
                goto Invalid;
            }
            DWORD c1 = *usrc;
            if (c1 < 0x80 || c1 > 0xBF)
            {
                goto Invalid;
            }
            usrc++;
            if (usrc >= srcEnd)
            {
                goto Invalid;
            }
            DWORD c2 = *usrc;
            if (c2 < 0x80 || c2 > 0xBF)
            {
                goto Invalid;
            }
            usrc++;
            ucode = (ucode&15)<<12 | (c1&0x3F)<<6 | (c2&0x3F);
            if (ucode < 0x800 || (ucode >= 0xD800 && ucode <= 0xDFFF))
            {
                goto Invalid;
            }
            *dest++ = ucode;
        }
        else if(ucode < 0xF8) // 11110abc 10defghi 10jklmno 10pqrstu
        {
            if (usrc >= srcEnd)
            {
                goto Invalid;
            }
            DWORD c1 = *usrc;
            if (c1 < 0x80 || c1 > 0xBF)
            {
                goto Invalid;
            }
            usrc++;
            if (usrc >= srcEnd)
            {
                goto Invalid;
            }
            DWORD c2 = *usrc;
            if (c2 < 0x80 || c2 > 0xBF)
            {
                goto Invalid;
            }
            usrc++;
            if (usrc >= srcEnd)
            {
                goto Invalid;
            }
            DWORD c3 = *usrc;
            if (c3 < 0x80 || c3 > 0xBF)
            {
                goto Invalid;
            }
            usrc++;
            ucode = (ucode&7)<<18 | (c1&0x3F)<<12 | (c2&0x3F)<<6 | (c3&0x3F);

            if (ucode < 0x10000   // overlong encoding
             || ucode > 0x10FFFF  // exceeds Unicode range
             || (ucode >= 0xD800 && ucode <= 0xDFFF)) // surrogate pairs
            {
                goto Invalid;
            }
            if (dest >= destEnd - 1)
            {
                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                return cchDest - (destEnd - dest);
            }
            ucode -= 0x10000;
            // Lead surrogate
            *dest++ = 0xD800 + (ucode >> 10);
            // Trail surrogate
            *dest++ = 0xDC00 + (ucode & 0x3FF);
        }
        else // invalid
        {
        Invalid:
            *dest++ = 0xFFFD;
        }
    }
    if (!*pErrorCode)
    {
        *pErrorCode = (dest == destEnd && usrc != srcEnd) ? ERROR_INSUFFICIENT_BUFFER : ERROR_SUCCESS;
    }
    return cchDest - (destEnd - dest);
}

size_t SystemLocale::Utf8To16Strict( const char *src, SSIZE_T cchSrc, WCHAR *dest, size_t cchDest, DWORD *pErrorCode )
{
    const unsigned char *usrc = reinterpret_cast<const unsigned char*>(src);
    const unsigned char *srcEnd = usrc + cchSrc;
    const WCHAR *destEnd = dest + cchDest;
    DWORD dummyError;
    if (!pErrorCode)
    {
        pErrorCode = &dummyError;
    }
    *pErrorCode = 0;

    while(usrc < srcEnd && dest < destEnd)
    {
        DWORD ucode = *usrc++;
        if(ucode <= 127) // Most common case for ASCII
        {
            *dest++ = ucode;
        }
        else if(ucode < 0xC0) // unexpected trailing byte 10xxxxxx
        {
            goto Invalid;
        }
        else if(ucode < 0xE0) // 110abcde 10fghijk
        {
            if (usrc >= srcEnd || *usrc < 0x80 || *usrc > 0xBF ||
                (*dest = (ucode & 0x1F)<<6 | (*usrc++ & 0x3F)) < 0x80)
            {
                goto Invalid;
            }
            dest++;
        }
        else if(ucode < 0xF0) // 1110abcd 10efghij 10klmnop
        {
            if (usrc >= srcEnd)
            {
                goto Invalid;
            }
            DWORD c1 = *usrc;
            if (c1 < 0x80 || c1 > 0xBF)
            {
                goto Invalid;
            }
            usrc++;
            if (usrc >= srcEnd)
            {
                goto Invalid;
            }
            DWORD c2 = *usrc;
            if (c2 < 0x80 || c2 > 0xBF)
            {
                goto Invalid;
            }
            usrc++;
            ucode = (ucode&15)<<12 | (c1&0x3F)<<6 | (c2&0x3F);
            if (ucode < 0x800 || (ucode >= 0xD800 && ucode <= 0xDFFF))
            {
                goto Invalid;
            }
            *dest++ = ucode;
        }
        else if(ucode < 0xF8) // 11110abc 10defghi 10jklmno 10pqrstu
        {
            if (usrc >= srcEnd)
            {
                goto Invalid;
            }
            DWORD c1 = *usrc;
            if (c1 < 0x80 || c1 > 0xBF)
            {
                goto Invalid;
            }
            usrc++;
            if (usrc >= srcEnd)
            {
                goto Invalid;
            }
            DWORD c2 = *usrc;
            if (c2 < 0x80 || c2 > 0xBF)
            {
                goto Invalid;
            }
            usrc++;
            if (usrc >= srcEnd)
            {
                goto Invalid;
            }
            DWORD c3 = *usrc;
            if (c3 < 0x80 || c3 > 0xBF)
            {
                goto Invalid;
            }
            usrc++;
            ucode = (ucode&7)<<18 | (c1&0x3F)<<12 | (c2&0x3F)<<6 | (c3&0x3F);

            if (ucode < 0x10000   // overlong encoding
             || ucode > 0x10FFFF  // exceeds Unicode range
             || (ucode >= 0xD800 && ucode <= 0xDFFF)) // surrogate pairs
            {
                goto Invalid;
            }
            if (dest >= destEnd - 1)
            {
                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                return cchDest - (destEnd - dest);
            }
            ucode -= 0x10000;
            // Lead surrogate
            *dest++ = 0xD800 + (ucode >> 10);
            // Trail surrogate
            *dest++ = 0xDC00 + (ucode & 0x3FF);
        }
        else // invalid
        {
        Invalid:
            *pErrorCode = ERROR_NO_UNICODE_TRANSLATION;
            return 0 ;
        }
    }
    if (!*pErrorCode)
    {
        *pErrorCode = (dest == destEnd && usrc != srcEnd) ? ERROR_INSUFFICIENT_BUFFER : ERROR_SUCCESS;
    }
    return cchDest - (destEnd - dest);
}

size_t SystemLocale::ToUtf16( UINT srcCodePage, const char * src, SSIZE_T cchSrc, WCHAR * dest, size_t cchDest, DWORD * pErrorCode )
{
    srcCodePage = ExpandSpecialCP( srcCodePage );
    if ( dest )
    {
        if ( srcCodePage == CP_UTF8 )
        {
            return SystemLocale::Utf8To16( src, cchSrc < 0 ? (1+strlen(src)) : cchSrc, dest, cchDest, pErrorCode );
        }
        else if ( srcCodePage == 1252 )
        {
            return SystemLocale::CP1252ToUtf16( src, cchSrc < 0 ? (1+strlen(src)) : cchSrc, dest, cchDest, pErrorCode );
        }
    }
    EncodingConverter cvt( CP_UTF16, srcCodePage );
    if ( !cvt.Initialize() )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_INVALID_PARAMETER;
        return 0;
    }
    size_t cchSrcActual = (cchSrc < 0 ? (1+strnlen_s(src)) : cchSrc);
    bool hasLoss = false;
    return cvt.Convert( dest, cchDest, src, cchSrcActual, false, &hasLoss, pErrorCode );
}

size_t SystemLocale::ToUtf16Strict( UINT srcCodePage, const char * src, SSIZE_T cchSrc, WCHAR * dest, size_t cchDest, DWORD * pErrorCode )
{
    srcCodePage = ExpandSpecialCP( srcCodePage );
    if ( dest )
    {
        if ( srcCodePage == CP_UTF8 )
        {
            return SystemLocale::Utf8To16Strict( src, cchSrc < 0 ? (1+strlen(src)) : cchSrc, dest, cchDest, pErrorCode );
        }
        else if ( srcCodePage == 1252 )
        {
            return SystemLocale::CP1252ToUtf16( src, cchSrc < 0 ? (1+strlen(src)) : cchSrc, dest, cchDest, pErrorCode );
        }
    }
    EncodingConverter cvt( CP_UTF16, srcCodePage );
    if ( !cvt.Initialize() )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_INVALID_PARAMETER;
        return 0;
    }
    size_t cchSrcActual = (cchSrc < 0 ? (1+strnlen_s(src)) : cchSrc);
    bool hasLoss = false;
    return cvt.Convert( dest, cchDest, src, cchSrcActual, true, &hasLoss, pErrorCode );
}

size_t SystemLocale::Utf8From16( const WCHAR *src, SSIZE_T cchSrc, char *dest, size_t cchDest, DWORD *pErrorCode )
{
    const WCHAR *srcEnd = src + cchSrc;
    char *destEnd = dest + cchDest;
    DWORD dummyError;
    if (!pErrorCode)
    {
        pErrorCode = &dummyError;
    }
    *pErrorCode = 0;

    // null dest is a special mode to calculate the output size required.
    if (!dest)
    {
        size_t cbOut = 0;
        while (src < srcEnd)
        {
            DWORD wch = *src++;
            if (wch < 128) // most common case.
            {
                cbOut++;
            }
            else if (wch < 0x800) // 127 to 2047: 2 bytes
            {
                cbOut += 2;
            }
            else if (wch < 0xD800 || wch > 0xDFFF) // 2048 to 55295 and 57344 to 65535: 3 bytes
            {
                cbOut += 3;
            }
            else if (wch < 0xDC00) // 65536 to end of Unicode: 4 bytes
            {
                if (src >= srcEnd)
                {
                    cbOut += 3; // lone surrogate at end
                }
                else if (*src < 0xDC00 || *src > 0xDFFF)
                {
                    cbOut += 3; // low surrogate not followed by high
                }
                else
                {
                    cbOut += 4;
                }
            }
            else // unexpected trail surrogate
            {
                cbOut += 3;
            }
        }
        return cbOut;
    }
    while ( src < srcEnd && dest < destEnd )
    {
        DWORD wch = *src++;
        if (wch < 128) // most common case.
        {
            *dest++ = wch;
        }
        else if (wch < 0x800) // 127 to 2047: 2 bytes
        {
            if (destEnd - dest < 2)
            {
                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                return 0;
            }
            *dest++ = 0xC0 | (wch >> 6);
            *dest++ = 0x80 | (wch & 0x3F);
        }
        else if (wch < 0xD800 || wch > 0xDFFF) // 2048 to 55295 and 57344 to 65535: 3 bytes
        {
            if (destEnd - dest < 3)
            {
                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                return 0;
            }
            *dest++ = 0xE0 | (wch >> 12);
            *dest++ = 0x80 | ((wch >> 6)&0x3F);
            *dest++ = 0x80 | (wch &0x3F);
        }
        else if (wch < 0xDC00) // 65536 to end of Unicode: 4 bytes
        {
            if (src >= srcEnd)
            {
                *pErrorCode = ERROR_NO_UNICODE_TRANSLATION; // lone surrogate at end
                if (destEnd - dest < 3)
                {
                    *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                    return 0;
                }
                *dest++ = 0xEF;
                *dest++ = 0xBF;
                *dest++ = 0xBD;
                continue;
            }
            if (*src < 0xDC00 || *src > 0xDFFF)
            {
                // low surrogate not followed by high
                if (destEnd - dest < 3)
                {
                    *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                    return 0;
                }
                *dest++ = 0xEF;
                *dest++ = 0xBF;
                *dest++ = 0xBD;
                continue;
            }
            wch = 0x10000 + ((wch - 0xD800)<<10) + *src++ - 0xDC00;
            if (destEnd - dest < 4)
            {
                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                return 0;
            }
            *dest++ = 0xF0 | (wch >> 18);
            *dest++ = 0x80 | ((wch >>12)&0x3F);
            *dest++ = 0x80 | ((wch >> 6)&0x3F);
            *dest++ = 0x80 | (wch&0x3F);
        }
        else // unexpected trail surrogate
        {
            *pErrorCode = ERROR_NO_UNICODE_TRANSLATION; // lone surrogate at end
            if (destEnd - dest < 3)
            {
                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                return 0;
            }
            *dest++ = 0xEF;
            *dest++ = 0xBF;
            *dest++ = 0xBD;
        }
    }
    if (!*pErrorCode)
    {
        *pErrorCode = (dest == destEnd && src != srcEnd) ? ERROR_INSUFFICIENT_BUFFER : ERROR_SUCCESS;
    }
    return *pErrorCode == ERROR_INSUFFICIENT_BUFFER ? 0 : cchDest - (destEnd - dest);
}

size_t SystemLocale::Utf8From16Strict( const WCHAR *src, SSIZE_T cchSrc, char *dest, size_t cchDest, DWORD *pErrorCode )
{
    const WCHAR *srcEnd = src + cchSrc;
    char *destEnd = dest + cchDest;
    DWORD dummyError;
    if (!pErrorCode)
    {
        pErrorCode = &dummyError;
    }
    *pErrorCode = 0;

    // null dest is a special mode to calculate the output size required.
    if (!dest)
    {
        size_t cbOut = 0;
        while (src < srcEnd)
        {
            DWORD wch = *src++;
            if (wch < 128) // most common case.
            {
                cbOut++;
            }
            else if (wch < 0x800) // 127 to 2047: 2 bytes
            {
                cbOut += 2;
            }
            else if (wch < 0xD800 || wch > 0xDFFF) // 2048 to 55295 and 57344 to 65535: 3 bytes
            {
                cbOut += 3;
            }
            else if (wch < 0xDC00) // 65536 to end of Unicode: 4 bytes
            {
                if (src >= srcEnd)
                {
                    cbOut += 3; // lone surrogate at end
                }
                else if (*src < 0xDC00 || *src > 0xDFFF)
                {
                    cbOut += 3; // low surrogate not followed by high
                }
                else
                {
                    cbOut += 4;
                }
            }
            else // unexpected trail surrogate
            {
                cbOut += 3;
            }
        }
        return cbOut;
    }
    while ( src < srcEnd && dest < destEnd )
    {
        DWORD wch = *src++;
        if (wch < 128) // most common case.
        {
            *dest++ = wch;
        }
        else if (wch < 0x800) // 127 to 2047: 2 bytes
        {
            if (destEnd - dest < 2)
            {
                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                return 0;
            }
            *dest++ = 0xC0 | (wch >> 6);
            *dest++ = 0x80 | (wch & 0x3F);
        }
        else if (wch < 0xD800 || wch > 0xDFFF) // 2048 to 55295 and 57344 to 65535: 3 bytes
        {
            if (destEnd - dest < 3)
            {
                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                return 0;
            }
            *dest++ = 0xE0 | (wch >> 12);
            *dest++ = 0x80 | ((wch >> 6)&0x3F);
            *dest++ = 0x80 | (wch &0x3F);
        }
        else if (wch < 0xDC00) // 65536 to end of Unicode: 4 bytes
        {
            if (src >= srcEnd)
            {
                *pErrorCode = ERROR_NO_UNICODE_TRANSLATION; // lone surrogate at end
                if (destEnd - dest < 3)
                {
                    *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                }
                
                return 0;
            }
            if (*src < 0xDC00 || *src > 0xDFFF)
            {
                *pErrorCode = ERROR_NO_UNICODE_TRANSLATION; // low surrogate not followed by high
                if (destEnd - dest < 3)
                {
                    *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                }
                return 0;
            }
            wch = 0x10000 + ((wch - 0xD800)<<10) + *src++ - 0xDC00;
            if (destEnd - dest < 4)
            {
                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                return 0;
            }
            *dest++ = 0xF0 | (wch >> 18);
            *dest++ = 0x80 | ((wch >>12)&0x3F);
            *dest++ = 0x80 | ((wch >> 6)&0x3F);
            *dest++ = 0x80 | (wch&0x3F);
        }
        else // unexpected trail surrogate
        {
            *pErrorCode = ERROR_NO_UNICODE_TRANSLATION; // lone surrogate at end
            if (destEnd - dest < 3)
            {
                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
            }
            return 0;
        }
    }
    if (!*pErrorCode)
    {
        *pErrorCode = (dest == destEnd && src != srcEnd) ? ERROR_INSUFFICIENT_BUFFER : ERROR_SUCCESS;
    }
    return *pErrorCode == ERROR_INSUFFICIENT_BUFFER ? 0 : cchDest - (destEnd - dest);
}

size_t SystemLocale::FromUtf16( UINT destCodePage, const WCHAR * src, SSIZE_T cchSrc, char * dest, size_t cchDest, bool * pHasDataLoss, DWORD * pErrorCode )
{
    destCodePage = ExpandSpecialCP( destCodePage );
    if ( destCodePage == CP_UTF8 )
    {
        pHasDataLoss && (*pHasDataLoss = 0);
        return SystemLocale::Utf8From16( src, cchSrc < 0 ? 1+mplat_wcslen(src) : cchSrc, dest, cchDest, pErrorCode );
    }
    EncodingConverter cvt( destCodePage, CP_UTF16 );
    if ( !cvt.Initialize() )
    {
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_INVALID_PARAMETER;
        return 0;
    }
    size_t cchSrcActual = (cchSrc < 0 ? (1+mplat_wcslen(src)) : cchSrc);
    bool hasLoss = false;
    return cvt.Convert( dest, cchDest, src, cchSrcActual, false, &hasLoss, pErrorCode );
}

size_t SystemLocale::FromUtf16Strict(UINT destCodePage, const WCHAR * src, SSIZE_T cchSrc, char * dest, size_t cchDest, bool * pHasDataLoss, DWORD * pErrorCode)
{
    destCodePage = ExpandSpecialCP(destCodePage);
    if ( destCodePage == CP_UTF8 )
    {
        pHasDataLoss && (*pHasDataLoss = 0);
        return SystemLocale::Utf8From16Strict( src, cchSrc < 0 ? 1+mplat_wcslen(src) : cchSrc, dest, cchDest, pErrorCode );
    }
    EncodingConverter cvt(destCodePage, CP_UTF16);
    if (!cvt.Initialize())
    {
        if (NULL != pErrorCode)
            *pErrorCode = ERROR_INVALID_PARAMETER;
        return 0;
    }
    size_t cchSrcActual = (cchSrc < 0 ? (1 + mplat_wcslen(src)) : cchSrc);
    bool hasLoss = false;
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

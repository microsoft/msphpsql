//-----------------------------------------------------------------------------
// File:        Globalization.h
//
// Copyright:   Copyright (c) Microsoft Corporation
//
// Contents:    Contains functions for handling Windows format strings
//              and UTF-16 on non-Windows platforms
//
// Comments:
//
// owners:
//    See source code ownership database in SqlDevDash 
//-----------------------------------------------------------------------------

#if !defined(_GLOBALIZATION_H_)
#define _GLOBALIZATION_H_

#include "xplat.h"
#include "typedefs_for_linux.h"
#include <errno.h>

#if defined(MPLAT_UNIX)
#include <iconv.h>

const iconv_t INVALID_ICONV = (iconv_t)(-1);

class IConvCache : public SLIST_ENTRY
{
    iconv_t m_iconv;

    // Prevent copying
    IConvCache( const IConvCache & );
    IConvCache & operator=( const IConvCache & );

public:
    IConvCache( int dstIdx, int srcIdx );
    ~IConvCache();

    iconv_t GetIConv() const
    {
        return m_iconv;
    }
};

#endif


class EncodingConverter
{
    UINT m_dstCodePage;
    UINT m_srcCodePage;
#if defined(MPLAT_UNIX)
    const IConvCache * m_pCvtCache;

    bool IsValidIConv() const
    {
        return (NULL != m_pCvtCache && INVALID_ICONV != m_pCvtCache->GetIConv());
    }

    template< typename T >
    struct iconv_buffer
    {
        char * m_pBytes;
        size_t m_nBytesLeft;

        iconv_buffer( char * buffer, size_t cchSize )
            : m_pBytes(buffer), m_nBytesLeft(sizeof(T)*cchSize) {}
        ~iconv_buffer() {}

        void Reset( char * buffer, size_t cchSize )
        {
            m_pBytes = buffer;
            m_nBytesLeft = cchSize;
        }

        void SkipSingleCh()
        {
            assert( sizeof(T) <= m_nBytesLeft );
            m_nBytesLeft -= sizeof(T);
            m_pBytes += sizeof(T);
        }
        void SkipDoubleCh()
        {
            assert( 2*sizeof(T) <= m_nBytesLeft );
            SkipSingleCh();
            // Only skip second half if it is non-NULL
            if ( 0 != *(UNALIGNED T *)m_pBytes )
                SkipSingleCh();
        }
        void SkipUtf8Ch()
        {
            assert( 1 == sizeof(T) );
            const char * pNext = SystemLocale::NextChar( CP_UTF8, m_pBytes, m_nBytesLeft );
            assert( m_pBytes < pNext && (size_t)(pNext-m_pBytes) <= SystemLocale::MaxCharCchSize(CP_UTF8) );

            UINT toTrim = (UINT)(pNext - m_pBytes);
            assert( toTrim <= m_nBytesLeft );
            assert( 0 < toTrim );

            m_nBytesLeft -= toTrim;
            m_pBytes += toTrim;
        }

        static char DefaultChar( UINT srcDataCP )
        {
            return 0x3f;
        }
        static WCHAR DefaultWChar( UINT srcDataCP )
        {
            return (CP_UTF8 == srcDataCP ? 0xfffd   // Unicode to Unicode, use Unicode default char
                : (932 == srcDataCP ? 0x30fb        // 932 to Unicode has special default char
                    : 0x003f));                     // WCP source, use '?'
        }
        void AssignDefault( UINT srcDataCP )
        {
            assert( sizeof(T) <= m_nBytesLeft );
            if ( 1 == sizeof(T) )
            {
                *m_pBytes = DefaultChar( srcDataCP );
                --m_nBytesLeft;
                ++m_pBytes;
            }
            else
            {
                *(UNALIGNED T *)m_pBytes = DefaultWChar( srcDataCP );
                m_nBytesLeft -= sizeof(T);
                m_pBytes += sizeof(T);
            }
        }
        bool AssignDefaultUtf8( UINT srcDataCP )
        {
            // This is a utf8 buffer so T must be char
            assert( 1 == sizeof(T) );
            if ( CP_UTF16 == srcDataCP )
            {
                // If source codepage is UTF16 then use Unicode default char
                // UTF8 default char is 3 bytes long
                if ( m_nBytesLeft < 3 )
                    return false;

                *m_pBytes++ = (T)0xef;
                *m_pBytes++ = (T)0xbf;
                *m_pBytes++ = (T)0xbd;
                m_nBytesLeft -= 3;
            }
            else if ( 932 == srcDataCP )
            {
                // If source codepage is 932 then use special default char
                // UTF8 default char for 932 is 3 bytes long
                if ( m_nBytesLeft < 3 )
                    return false;

                *m_pBytes++ = (T)0xe3;
                *m_pBytes++ = (T)0x83;
                *m_pBytes++ = (T)0xbb;
                m_nBytesLeft -= 3;
            }
            else
            {
                *m_pBytes = DefaultChar( srcDataCP );
                ++m_pBytes;
                --m_nBytesLeft;
            }
            return true;
        }

        // Prevent compiler from generating these
        iconv_buffer();
        iconv_buffer( const iconv_buffer & other );
        iconv_buffer & operator=( const iconv_buffer & other );
    };

    template< class DestType >
    bool AddDefault( iconv_buffer<DestType> * dest, bool * pHasLoss, DWORD * pErrorCode ) const
    {
        if ( NULL != pHasLoss )
            *pHasLoss = true;

        if ( CP_UTF8 != m_dstCodePage )
            dest->AssignDefault( m_srcCodePage );
        else if ( !dest->AssignDefaultUtf8(m_srcCodePage) )
        {
            // Not enough room for the default char
            if ( NULL != pErrorCode )
                *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
            return false;
        }
        return true;
    }

    template< class DestType, class SrcType >
    size_t Convert(
        iconv_buffer<DestType> & dest,
        iconv_buffer<SrcType> & src,
        bool failIfLossy = false, bool * pHasLoss = NULL, DWORD * pErrorCode = NULL ) const
    {
        if ( !IsValidIConv() )
            return 0;

        size_t iconv_ret;
        size_t cchDest = dest.m_nBytesLeft/sizeof(DestType);

        if ( NULL != pHasLoss )
            *pHasLoss = false;
        if ( NULL != pErrorCode )
            *pErrorCode = ERROR_SUCCESS;

        while ( 0 < dest.m_nBytesLeft && 0 < src.m_nBytesLeft )
        {
            // First clear any intermediate state left over from previous conversions
            iconv_ret = iconv( m_pCvtCache->GetIConv(), NULL, NULL, NULL, NULL );
            assert( 0 == iconv_ret );

            // Now attempt conversion
            iconv_ret = iconv( m_pCvtCache->GetIConv(), &src.m_pBytes, &src.m_nBytesLeft, &dest.m_pBytes, &dest.m_nBytesLeft );
            if ( iconv_ret == (size_t)(-1) )
            {
                // If there's no dest bytes left, then treat as E2BIG even if the error
                // is EILSEQ, etc.  We want E2BIG to take precedence like Windows.
                int err = (0 < dest.m_nBytesLeft ? errno : E2BIG);
                if ( E2BIG != err && failIfLossy )
                {
                    if ( NULL != pErrorCode )
                        *pErrorCode = ERROR_NO_UNICODE_TRANSLATION;
                    return 0;
                }

                switch ( err )
                {
                case EILSEQ: // Invalid multibyte sequence in input
                    if ( CP_UTF8 == m_srcCodePage )
                        src.SkipUtf8Ch();
                    else if ( 1 == sizeof(SrcType) )
                        src.SkipDoubleCh(); // DBCS
                    else
                        src.SkipSingleCh(); // utf32 or incomplate utf16 surrogate

                    if ( !AddDefault(&dest, pHasLoss, pErrorCode) )
                        return 0;

                    break;
                case EINVAL: // Incomplete multibyte sequence in input
                    if ( CP_UTF8 == m_srcCodePage )
                        src.SkipUtf8Ch();
                    else
                        src.SkipSingleCh();

                    if ( !AddDefault(&dest, pHasLoss, pErrorCode) )
                        return 0;

                    break;
                case E2BIG: // Output buffer is out of room
                    if ( NULL != pErrorCode )
                        *pErrorCode = ERROR_INSUFFICIENT_BUFFER;
                    return 0;
                default:
                    if ( NULL != pErrorCode )
                        *pErrorCode = ERROR_INVALID_PARAMETER;
                    return 0;
                }
            }
        }

        return cchDest - (dest.m_nBytesLeft / sizeof(DestType));
    }

#elif defined(MPLAT_WWOWH)

    size_t ReturnCchResult( int cch, DWORD * pErrorCode ) const
    {
        if ( cch < 0 )
            cch = 0;

        if ( NULL != pErrorCode )
            *pErrorCode = (0 == cch ? GetLastError() : ERROR_SUCCESS);

        return cch;
    }

#endif // defined(MPLAT_WWOWH)


public:
    EncodingConverter( UINT dstCodePage, UINT srcCodePage );
    ~EncodingConverter();

    bool Initialize();

    // Performs an encoding conversion.
    // Returns the number of dest chars written.
    // Input and output buffers should not overlap.
    template< class DestType, class SrcType, class AllocT >
    size_t Convert(
        DestType ** destBuffer,
        const SrcType * srcBuffer,  size_t cchSource,
        bool failIfLossy = false, bool * pHasLoss = NULL, DWORD * pErrorCode = NULL ) const
    {
#if defined(MPLAT_UNIX)

        if ( !IsValidIConv() )
            return 0;

        iconv_buffer<SrcType> src(
            reinterpret_cast< char * >( const_cast< SrcType * >(srcBuffer) ),
            cchSource );

        size_t cchDest = cchSource;
        AutoArray< DestType, AllocT > newDestBuffer( cchDest );

        iconv_buffer<DestType> dest(
            reinterpret_cast< char * >(newDestBuffer.m_ptr),
            cchDest );

        size_t cchPrevCvt = 0;
        DWORD rcCvt;
        while ( true )
        {
            size_t cchCvt = Convert( dest, src, failIfLossy, pHasLoss, &rcCvt );
            if ( 0 == cchCvt )
            {
                if ( ERROR_INSUFFICIENT_BUFFER == rcCvt )
                {
                    // Alloc more and continue
                    cchPrevCvt = cchDest;
                    cchDest *= 2;
                    if ( !newDestBuffer.Realloc(cchDest) )
                    {
                        if ( NULL != pErrorCode )
                            *pErrorCode = ERROR_NOT_ENOUGH_MEMORY;
                        return 0;
                    }
                    // Fill newly allocated part of buffer
                    dest.Reset( reinterpret_cast< char * >(newDestBuffer.m_ptr+cchPrevCvt), cchDest );
                }
                else
                {
                    if ( NULL != pErrorCode )
                        *pErrorCode = rcCvt;
                    return 0;
                }
            }
            else
            {
                if ( NULL != pErrorCode )
                    *pErrorCode = rcCvt;
                *destBuffer = newDestBuffer.Detach();
                return cchPrevCvt + cchCvt;
            }
        }

#elif defined(MPLAT_WWOWH)
        // WWOWH unit testing code
        // Can only convert between ansi and utf16
        if ( 1 == sizeof(DestType) && 2 == sizeof(SrcType) )
        {
            // utf16 to ansi
            const wchar_t * srcPtr = reinterpret_cast< const wchar_t * >( srcBuffer );
            BOOL loss = FALSE;
            int converted = WideCharToMultiByte(
                m_dstCodePage, 0,
                srcPtr, (int)cchSource,
                NULL, 0,
                NULL, &loss );

            if ( 0 < converted )
            {
                AutoArray< char, AllocT > newDestBuffer( converted );
                char * dstPtr = newDestBuffer.m_ptr;
                converted = WideCharToMultiByte(
                    m_dstCodePage, 0,
                    srcPtr, (int)cchSource,
                    newDestBuffer.m_ptr, converted,
                    NULL, &loss );
                if ( 0 < converted )
                    *destBuffer = newDestBuffer.Detach();
                if ( NULL != pHasLoss )
                    *pHasLoss = (FALSE != loss);
            }
            return ReturnCchResult( converted, pErrorCode );
        }
        else if ( 2 == sizeof(DestType) && 1 == sizeof(SrcType) )
        {
            // ansi to utf16
            const char * srcPtr = reinterpret_cast< const char * >( srcBuffer );
            int converted = MultiByteToWideChar(
                m_srcCodePage, (failIfLossy ? MB_ERR_INVALID_CHARS : 0),
                srcPtr, (int)cchSource,
                NULL, 0 );

            if ( 0 < converted )
            {
                AutoArray< WCHAR, AllocT > newDestBuffer( converted );
                converted = MultiByteToWideChar(
                    m_srcCodePage, (failIfLossy ? MB_ERR_INVALID_CHARS : 0),
                    srcPtr, (int)cchSource,
                    newDestBuffer.m_ptr, converted );
                if ( 0 < converted )
                    *destBuffer = newDestBuffer.Detach();
                if ( NULL != pHasLoss )
                    *pHasLoss = false;
            }
            return ReturnCchResult( converted, pErrorCode );
        }
        else
        {
            assert( false );
            if ( NULL != pErrorCode )
                *pErrorCode = ERROR_NOT_SUPPORTED;
            return 0;
        }

#endif // defined(MPLAT_WWOWH)
    }
    // Performs an encoding conversion.
    // Returns the number of dest chars written.
    // Input and output buffers should not overlap.
    template< class DestType, class SrcType >
    size_t Convert(
        DestType * destBuffer,      size_t cchDest,
        const SrcType * srcBuffer,  size_t cchSource,
        bool failIfLossy = false, bool * pHasLoss = NULL, DWORD * pErrorCode = NULL ) const
    {
#if defined(MPLAT_UNIX)

        if ( !IsValidIConv() )
            return 0;

        iconv_buffer<SrcType> src(
            reinterpret_cast< char * >( const_cast< SrcType * >(srcBuffer) ),
            cchSource );
        if ( 0 < cchDest )
        {
            iconv_buffer<DestType> dest(
                reinterpret_cast< char * >(destBuffer),
                cchDest );
            return Convert( dest, src, failIfLossy, pHasLoss, pErrorCode );
        }
        else
        {
            // Use fixed size buffer iteratively to determine final required length
            const size_t CCH_FIXED_SIZE = 256;
            char fixed_buf[ CCH_FIXED_SIZE*sizeof(DestType) ];
            iconv_buffer<DestType> dest(
                &fixed_buf[0],
                CCH_FIXED_SIZE );

            bool hasLoss = false;
            DWORD rcCvt = ERROR_SUCCESS;
            size_t cchOnce = 0;
            size_t cchCumulative = 0;

            while ( 0 < src.m_nBytesLeft
                && 0 == (cchOnce = Convert(dest, src, failIfLossy, &hasLoss, &rcCvt))
                && ERROR_INSUFFICIENT_BUFFER == rcCvt )
            {
                cchCumulative += CCH_FIXED_SIZE;
                dest.Reset( &fixed_buf[0], CCH_FIXED_SIZE );
            }
            if ( 0 < cchOnce )
                cchCumulative += cchOnce;
            if ( NULL != pErrorCode )
                *pErrorCode = (0 < cchCumulative ? ERROR_SUCCESS : rcCvt);
            if ( NULL != pHasLoss )
                *pHasLoss |= hasLoss;
            return cchCumulative;
        }

#elif defined(MPLAT_WWOWH)
        // WWOWH unit testing code
        // Can only convert between ansi and utf16
        if ( 1 == sizeof(DestType) && 2 == sizeof(SrcType) )
        {
            // utf16 to ansi
            char * dstPtr = reinterpret_cast< char * >( destBuffer );
            const wchar_t * srcPtr = reinterpret_cast< const wchar_t * >( srcBuffer );
            BOOL loss = FALSE;
            int converted = WideCharToMultiByte(
                m_dstCodePage, 0,
                srcPtr, (int)cchSource,
                dstPtr, (int)cchDest,
                NULL, &loss );
            if ( NULL != pHasLoss )
                *pHasLoss = (FALSE != loss);
            return ReturnCchResult( converted, pErrorCode );
        }
        else if ( 2 == sizeof(DestType) && 1 == sizeof(SrcType) )
        {
            // ansi to utf16
            wchar_t * dstPtr = reinterpret_cast< wchar_t * >( destBuffer );
            const char * srcPtr = reinterpret_cast< const char * >( srcBuffer );
            int converted = MultiByteToWideChar(
                m_srcCodePage, (failIfLossy ? MB_ERR_INVALID_CHARS : 0),
                srcPtr, (int)cchSource,
                dstPtr, (int)cchDest );
            if ( NULL != pHasLoss )
                *pHasLoss = false;
            return ReturnCchResult( converted, pErrorCode );
        }
        else
        {
            assert( false );
            if ( NULL != pErrorCode )
                *pErrorCode = ERROR_NOT_SUPPORTED;
            return 0;
        }

#endif // defined(MPLAT_WWOWH)
    }
};

#endif // _GLOBALIZATION_H_

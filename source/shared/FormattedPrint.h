//-----------------------------------------------------------------------------
// File:        FormattedPrint.h
//
// Contents: Contains functions for handling Windows format strings
//			 and UTF-16 on non-Windows platforms
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

#ifndef _FORMATTEDPRINT_H_
#define _FORMATTEDPRINT_H_

#include "xplat_winnls.h"
#include "localization.hpp"


template< typename T >
struct IFormattedPrintOutput
{
    /*
    Method names are all CAPS to match the original code for formatted print from the Windows CRT.

    pCumulativeOutputCount
        Used to track running total of output storage units.
        Note that the count is the memory size in sizeof(TCHAR) and not the display char count.
        For example, a UTF-8 char+diacritical mark is two chars in memory but only one for display
        so pCumulativeOutputCount will be incremented by 2 after output.
        If an error is encountered, then set this to -1.
        If the value is -1 upon entry of any callback, simply return and don't output anything.
    */

    // Writes a single character to the output.
    virtual void WRITE_CHAR( T ch , int * pCumulativeOutputCount ) = 0;

    // Repeatedly writes a single character to the output.  If there isn't enough room, writes to end of buffer.
    // If repeatCount is <=0, then don't output anything and leave pCumulativeOutputCount as is.
    virtual void WRITE_MULTI_CHAR( T ch, int repeatCount, int * pCumulativeOutputCount ) = 0;

    // Writes the supplied string to the output.  If there isn't enough room, writes to end of buffer.
    // If count is <=0, then don't output anything and leave pCumulativeOutputCount as is.
    virtual void WRITE_STRING( const T * pch, int count, int * pCumulativeOutputCount ) = 0;

    // Ensure dtors are virtual
    virtual ~IFormattedPrintOutput() { }
};

template< typename T >
class FormattedOutput : public IFormattedPrintOutput<T>
{
protected:
    bool ShouldOutput( const int * pCumulativeOutputCount, int count ) const
    {
        assert( NULL != pCumulativeOutputCount );
        return ( (0 <= *pCumulativeOutputCount) && (0 < count) );
    }
};

int FormattedPrintA( IFormattedPrintOutput<char> * output, const char *format, va_list argptr );

template< typename T >
class BufferOutput : public FormattedOutput<T>
{
    T * m_buffer;
    size_t m_countRemainingInBuffer;

    bool CanOutput() const
    {
        return ( 0 < m_countRemainingInBuffer );
    }

    // Stop these from being available
    BufferOutput();
    BufferOutput( const BufferOutput & );
    BufferOutput & operator=( const BufferOutput & );

public:
    BufferOutput( T * pcb, size_t bufsize )
        :   m_buffer( pcb ),
            m_countRemainingInBuffer( bufsize )
    {
        assert( NULL != m_buffer );
        if ( m_countRemainingInBuffer < INT_MAX )
        {
            memset( m_buffer, 0, m_countRemainingInBuffer * sizeof(T) );
        }
    }

    virtual void WRITE_CHAR(T ch, int * pCumulativeOutputCount)
    {
        if ( FormattedOutput<T>::ShouldOutput( pCumulativeOutputCount, 1 ) )
        {
            if ( CanOutput() )
            {
                ++(*pCumulativeOutputCount);
                --m_countRemainingInBuffer;
                *m_buffer++ = ch;
            }
            else
            {
                *pCumulativeOutputCount = -1;
            }
        }
    }
    virtual void WRITE_MULTI_CHAR(T ch, int repeatCount, int * pCumulativeOutputCount)
    {
        if ( FormattedOutput<T>::ShouldOutput( pCumulativeOutputCount, repeatCount ) )
        {
            if ( CanOutput() )
            {
                while ( 0 != m_countRemainingInBuffer && 0 != repeatCount )
                {
                    *m_buffer++ = ch;
                    --m_countRemainingInBuffer;
                    --repeatCount;
                    ++(*pCumulativeOutputCount);
                }
                if ( 0 != repeatCount )
                {
                    // Not enough room in buffer
                    *pCumulativeOutputCount = -1;
                }
            }
            else
            {
                *pCumulativeOutputCount = -1;
            }
        }
    }
    virtual void WRITE_STRING(const T * pch, int count, int * pCumulativeOutputCount)
    {
        assert( NULL != pch );
        if ( FormattedOutput<T>::ShouldOutput( pCumulativeOutputCount, count ) )
        {
            if ( CanOutput() )
            {
                while ( 0 != m_countRemainingInBuffer && 0 != count )
                {
                    *m_buffer++ = *pch++;
                    --m_countRemainingInBuffer;
                    --count;
                    ++(*pCumulativeOutputCount);
                }
                if ( 0 != count )
                {
                    // Not enough room in buffer
                    *pCumulativeOutputCount = -1;
                }
            }
            else
            {
                *pCumulativeOutputCount = -1;
            }
        }
    }
};


template< typename T >
class FileOutput : public FormattedOutput<T>
{
    FILE * m_file;

    // Stop these from being available
    FileOutput();
    FileOutput( const FileOutput & );
    FileOutput & operator=( const FileOutput & );

public:
    FileOutput( FILE * file )
        :   m_file( file )
    {
        assert( NULL != m_file );
    }

    virtual void WRITE_CHAR(T ch, int * pCumulativeOutputCount)
    {
        if ( FormattedOutput<T>::ShouldOutput( pCumulativeOutputCount, 1 ) )
        {
            ++(*pCumulativeOutputCount);
            if ( fputc( ch, m_file ) != ch )
                *pCumulativeOutputCount = -1;
        }
    }
    virtual void WRITE_MULTI_CHAR(T ch, int repeatCount, int * pCumulativeOutputCount)
    {
        if ( FormattedOutput<T>::ShouldOutput( pCumulativeOutputCount, repeatCount ) )
        {
            *pCumulativeOutputCount += repeatCount;
            while ( 0 < repeatCount-- )
            {
                if ( fputc( ch, m_file ) != ch )
                {
                    *pCumulativeOutputCount = -1;
                    return;
                }
            }
        }
    }
    virtual void WRITE_STRING(const T * pch, int count, int * pCumulativeOutputCount)
    {
        if ( FormattedOutput<T>::ShouldOutput( pCumulativeOutputCount, count ) )
        {
            assert( NULL != pch );
            *pCumulativeOutputCount += count;
            if ( (size_t)count != fwrite( pch, sizeof(T), count, m_file ) )
                *pCumulativeOutputCount = -1;
        }
    }
};



#endif // _FORMATTEDPRINT_H_

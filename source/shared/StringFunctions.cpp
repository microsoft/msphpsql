//---------------------------------------------------------------------------------------------------------------------------------
// File: StringFunctions.cpp
//
// Contents: Contains functions for handling UTF-16 on non-Windows platforms
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

#include "StringFunctions.h"

// Tools\vc\src\crt\amd64\memcpy_s.c
int mplat_memcpy_s( void * dest, size_t destSize, const void * src, size_t count )
{
    if ( 0 == count )
    {
        // nothing to do 
        return 0;
    }

    // validation section 
    if ( NULL == dest )
    {
        errno = EINVAL;
        return EINVAL;
    }

    if ( src == NULL || destSize < count )
    {
        // zeroes the destination buffer 
        memset(dest, 0, destSize*sizeof(char));

        if ( NULL == src )
        {
            errno = EINVAL;
            return EINVAL;
        }
        if ( destSize < count )
        {
            errno = ERANGE;
            return ERANGE;
        }

        return EINVAL;
    }

    memcpy(dest, src, count*sizeof(char));
    return 0;
}

// Tools\vc\src\crt\amd64\strcpy_s.c
int mplat_strcpy_s( char * dest, size_t destSize, const char * src )
{
    char * p;
    size_t available;
 
    // validation section 
    if ( NULL == dest || 0 == destSize )
    {
        errno = EINVAL;
        return EINVAL;
    }
    if ( NULL == src )
    {
        *dest = 0;
        errno = EINVAL;
        return EINVAL;
    }
 
    p = dest;
    available = destSize;
    while ( (*p++ = *src++) != 0 && --available > 0 )
    {
    }
 
    if ( 0 == available )
    {
        *dest = 0;
        errno = ERANGE;
        return ERANGE;
    }
    return 0;
}

// Tools\vc\src\crt\amd64\strcat_s.c
int mplat_strcat_s( char * dest, size_t destSize, const char * src )
{
    char *p;
    size_t available;
 
    // validation section 
    if ( NULL == dest || 0 == destSize )
    {
        errno = EINVAL;
        return EINVAL;
    }
    if ( NULL == src )
    {
        *dest = 0;
        errno = EINVAL;
        return EINVAL;
    }
 
    p = dest;
    available = destSize;
    while (available > 0 && *p != 0)
    {
        p++;
        available--;
    }
 
    if (available == 0)
    {
        *dest = 0;
        errno = EINVAL;
        return EINVAL;
    }
 
    while ((*p++ = *src++) != 0 && --available > 0)
    {
    }
 
    if (available == 0)
    {
        *dest = 0;
        errno = ERANGE;
        return ERANGE;
    }
    return 0;
}

size_t strnlen_s(const char * _Str, size_t _MaxCount)
{
    return (_Str==0) ? 0 : strnlen(_Str, _MaxCount);
}

//
// End copy functions
//----------------------------------------------------------------------------


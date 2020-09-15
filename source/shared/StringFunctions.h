//---------------------------------------------------------------------------------------------------------------------------------
// File: StringFunctions.h
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

#if !defined(_STRINGFUNCTIONS_H_)
#define _STRINGFUNCTIONS_H_

#include "xplat_winnls.h"

// ---------------------------------------------------------------------------
// Declare internal versions of string handling functions
// Only the functions implemented are declared here

// Copy
int         mplat_memcpy_s(void *_S1, size_t _N1, const void *_S2, size_t _N);
int         mplat_strcat_s( char *strDestination, size_t numberOfElements, const char *strSource );
int         mplat_strcpy_s(char * _Dst, size_t _SizeInBytes, const char * _Src);

size_t      strnlen_s(const char * _Str, size_t _MaxCount = INT_MAX);

// Copy
#define memcpy_s        mplat_memcpy_s
#define strcat_s        mplat_strcat_s
#define strcpy_s        mplat_strcpy_s

#endif // _STRINGFUNCTIONS_H_

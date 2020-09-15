//---------------------------------------------------------------------------------------------------------------------------------
// File: InterlockedAtomic.h
//
// Contents: Contains a portable abstraction for interlocked, atomic
// 			 operations on int32_t and pointer types.
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

#ifndef __INTERLOCKEDATOMIC_H__
#define __INTERLOCKEDATOMIC_H__

// Forward references and contract specifications
//

// Always returns old value
// Sets to new value if old value equals compareTo
LONG InterlockedCompareExchange( LONG volatile * atomic, LONG newValue, LONG compareTo );


// Use conditional compilation to load the implementation
//
#if defined(_MSC_VER)
#include "InterlockedAtomic_WwoWH.h"
#elif defined(__GNUC__)
#include "interlockedatomic_gcc.h"
#else
#error "Unsupported compiler"
#endif

#endif // __INTERLOCKEDATOMIC_H__

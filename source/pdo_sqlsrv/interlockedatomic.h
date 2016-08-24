//-----------------------------------------------------------------------------
// File:        InterlockedAtomic.h
//
// Copyright:   Copyright (c) Microsoft Corporation
//
// Contents:    Contains a portable abstraction for interlocked, atomic
//              operations on int32_t and pointer types.
//
// Comments:    Implementation for Windows is not real, just a description
//              of the contract for the API.  This header will only be used
//              by non-Windows builds (except for WwoWH).
//
// owners:
//    See source code ownership database in SqlDevDash 
//-----------------------------------------------------------------------------

#ifndef __INTERLOCKEDATOMIC_H__
#define __INTERLOCKEDATOMIC_H__

// Forward references and contract specifications
//

// Increments and returns new value
LONG InterlockedIncrement( LONG volatile * atomic );

// Decrements and returns new value
LONG InterlockedDecrement( LONG volatile * atomic );

// Always returns old value
// Sets to new value if old value equals compareTo
LONG InterlockedCompareExchange( LONG volatile * atomic, LONG newValue, LONG compareTo );

// Sets to new value and returns old value
LONG InterlockedExchange( LONG volatile * atomic, LONG newValue );

// Sets to new value and returns old value
PVOID InterlockedExchangePointer( PVOID volatile * atomic, PVOID newValue);

// Adds the amount and returns the old value
LONG InterlockedExchangeAdd( LONG volatile * atomic, LONG add );

// Always returns the old value
// Sets the new value if old value equals compareTo
PVOID InterlockedCompareExchangePointer( PVOID volatile * atomic, PVOID newValue, PVOID compareTo );



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

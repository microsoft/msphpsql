//-----------------------------------------------------------------------------
// File:        InterlockedAtomic_gcc.h
//
// Copyright:   Copyright (c) Microsoft Corporation
//
// Contents:    Contains a portable abstraction for interlocked, atomic
//              operations on int32_t and pointer types.
//
// Comments:    This implementation is for GCC and relies on its builtin
//              atomic component syntax.
//
// owners:
//    See source code ownership database in SqlDevDash 
//-----------------------------------------------------------------------------

#ifndef __INTERLOCKEDATOMIC_GCC_H__
#define __INTERLOCKEDATOMIC_GCC_H__

#if !defined(__GNUC__)
#error "Incorrect compiler configuration in InterlockedAtomic.h.  Was expecting GCC."
#endif

inline LONG InterlockedIncrement( LONG volatile * atomic )
{
    return __sync_add_and_fetch( atomic, 1 );
}

inline LONG InterlockedDecrement( LONG volatile * atomic )
{
    return __sync_sub_and_fetch( atomic, 1 );
}

inline LONG InterlockedCompareExchange( LONG volatile * atomic, LONG newValue, LONG compareTo )
{
    return __sync_val_compare_and_swap( atomic, compareTo, newValue );
}

inline LONG InterlockedExchange( LONG volatile * atomic, LONG newValue )
{
    return __sync_lock_test_and_set( atomic, newValue );
}

inline PVOID InterlockedExchangePointer( PVOID volatile * atomic, PVOID newValue)
{
    return __sync_lock_test_and_set( atomic, newValue );
}

inline LONG InterlockedExchangeAdd( LONG volatile * atomic, LONG add )
{
    return __sync_fetch_and_add( atomic, add );
}

inline PVOID InterlockedCompareExchangePointer( PVOID volatile * atomic, PVOID newValue, PVOID compareTo )
{
    return __sync_val_compare_and_swap( atomic, compareTo, newValue );
}

#endif // __INTERLOCKEDATOMIC_GCC_H__

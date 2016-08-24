//-----------------------------------------------------------------------------
// File:        InterlockedSList.h
//
// Copyright:   Copyright (c) Microsoft Corporation
//
// Contents:    Contains a portable abstraction for interlocked, singly
//              linked list.
//
// Comments:    This implementation is not fully atomic but it is non-blocking.
//              It only provides eventual consistency (at a given point in time
//              Depth may not exactly match the node list).  Drift between
//              the node list and the depth count will be small if the OS provides
//              reasonable fairness to the executing threads.  If the user's app has
//              threads with different priorities then that might cause wider drift.
//
// owners:
//    See source code ownership database in SqlDevDash 
//-----------------------------------------------------------------------------

#ifndef __INTERLOCKEDSLIST_H__
#define __INTERLOCKEDSLIST_H__

#include "interlockedatomic.h"

#define SLIST_ENTRY SINGLE_LIST_ENTRY

#define PSLIST_ENTRY PSINGLE_LIST_ENTRY

typedef struct _SINGLE_LIST_ENTRY {
    // Want a volatile pointer to non-volatile data so place after all type info
    struct _SINGLE_LIST_ENTRY * volatile Next;
} SINGLE_LIST_ENTRY, *PSINGLE_LIST_ENTRY;

typedef union _SLIST_HEADER {
    // Provides 8 byte alignment for 32-bit builds.  Technically, not needed for
    // current implementation below but leaving for future use.
    ULONGLONG Alignment;
    struct {
        // Want a volatile pointer to non-volatile data so place after all type info
        PSLIST_ENTRY volatile Head;
        volatile LONG Depth;
        volatile LONG Mutex;
    } List;
} SLIST_HEADER, *PSLIST_HEADER;


inline VOID InitializeSListHead( PSLIST_HEADER slist )
{
    assert( NULL != slist );

    slist->List.Head = NULL;
    slist->List.Depth = 0;
    slist->List.Mutex = 0;
}

inline PSLIST_ENTRY InterlockedPopEntrySList( PSLIST_HEADER slist )
{
    assert( NULL != slist );

    // Exit prior to 'mutex' if we think it is empty
    // Some callers (like sqlncli/msdart/dll/dynslist.h) rely on a NULL
    // result from Pop to indicate the list is empty.  This early exit
    // is an optimization and not technically needed for correctness.
    PSLIST_ENTRY oldHead = slist->List.Head;
    if ( NULL == oldHead )
    {
        return NULL;
    }

    while ( 0 != slist->List.Mutex || 0 != InterlockedCompareExchange( &slist->List.Mutex, 1, 0 ) )
    {
        // Spin until 'mutex' is free
    }

    // We have the 'mutex' so proceed with update
    oldHead = slist->List.Head;
    if ( NULL != oldHead )
    {
        slist->List.Head = oldHead->Next;
        --(slist->List.Depth);
        assert( 0 <= slist->List.Depth );
    }

    // Free the 'mutex'
    slist->List.Mutex = 0;

    return oldHead;
}

inline PSLIST_ENTRY InterlockedPushEntrySList( PSLIST_HEADER slist, PSLIST_ENTRY newEntry )
{
    assert( NULL != slist );

    while ( 0 != slist->List.Mutex || 0 != InterlockedCompareExchange( &slist->List.Mutex, 1, 0 ) )
    {
        // Spin until 'mutex' is free
    }

    // We have the 'mutex' so proceed with update
    PSLIST_ENTRY oldHead = slist->List.Head;
    newEntry->Next = oldHead;
    slist->List.Head = newEntry;
    ++(slist->List.Depth);

    // Free the 'mutex'
    slist->List.Mutex = 0;

    return oldHead;
}

inline PSLIST_ENTRY InterlockedFlushSList( PSLIST_HEADER slist )
{
    assert( NULL != slist );

    while ( 0 != slist->List.Mutex || 0 != InterlockedCompareExchange( &slist->List.Mutex, 1, 0 ) )
    {
        // Spin until 'mutex' is free
    }

    // We have the 'mutex' so proceed with update
    PSLIST_ENTRY oldHead = slist->List.Head;
    slist->List.Head = NULL;
    slist->List.Depth = 0;

    // Free the 'mutex'
    slist->List.Mutex = 0;

    return oldHead;
}

// If the list has more than USHORT nodes then this method
// will not return reliable results.
inline USHORT QueryDepthSList( PSLIST_HEADER slist )
{
    assert( NULL != slist );

    return static_cast<USHORT>(slist->List.Depth);
}


#endif // __INTERLOCKEDSLIST_H__

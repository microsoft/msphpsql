#ifndef CORE_SQLSRV_H
#define CORE_SQLSRV_H

//---------------------------------------------------------------------------------------------------------------------------------
// File: core_sqlsrv.h
//
// Contents: Core routines and constants shared by the Microsoft Drivers for PHP for SQL Server
//
// Copyright Microsoft Corporation
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
//
// You may obtain a copy of the License at:
// http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//---------------------------------------------------------------------------------------------------------------------------------


//*********************************************************************************************************************************
// Includes
//*********************************************************************************************************************************

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#ifdef PHP_WIN32
#define PHP_SQLSRV_API __declspec(dllexport)
#else
#define PHP_SQLSRV_API
#endif

// OACR is an internal Microsoft static code analysis tool
#if defined(OACR)
#include <oacr.h>
OACR_WARNING_PUSH
OACR_WARNING_DISABLE( ALLOC_SIZE_OVERFLOW, "Third party code." )
OACR_WARNING_DISABLE( INDEX_NEGATIVE, "Third party code." )
OACR_WARNING_DISABLE( UNANNOTATED_BUFFER, "Third party code." )
OACR_WARNING_DISABLE( INDEX_UNDERFLOW, "Third party code." )
OACR_WARNING_DISABLE( REALLOCLEAK, "Third party code." )
OACR_WARNING_DISABLE( ALLOC_SIZE_OVERFLOW_WITH_ACCESS, "Third party code." )
#else
// define to eliminate static analysis hints in the code
#define OACR_WARNING_SUPPRESS( warning, msg )
#endif

extern "C" {

#pragma warning(push)
#pragma warning( disable: 4005 4100 4127 4142 4244 4505 4530 )

#ifdef ZTS
#include "TSRM.h"
#endif

#if _MSC_VER >= 1400
// typedef and macro to prevent a conflict between php.h and ws2tcpip.h.  
// php.h defines this  constant as unsigned int which causes a compile error 
// in ws2tcpip.h.  Fortunately php.h allows an override by defining
// HAVE_SOCKLEN_T.  Since ws2tcpip.h isn't included until later, we define 
// socklen_t here and override the php.h version.
typedef int socklen_t;
#define HAVE_SOCKLEN_T
#endif

#include "php.h"
#include "php_globals.h"
#include "php_ini.h"
#include "ext/standard/php_standard.h"
#include "ext/standard/info.h"

#pragma warning(pop)

#if ZEND_DEBUG
// debug build causes warning C4505 to pop up from the Zend header files
#pragma warning( disable: 4505 )
#endif

}   // extern "C"

#if defined(OACR)
OACR_WARNING_POP
#endif

#include <sql.h>
#include <sqlext.h>

#if !defined(WC_ERR_INVALID_CHARS)
// imported from winnls.h as it isn't included by 5.3.0
#define WC_ERR_INVALID_CHARS      0x00000080  // error for invalid chars
#endif

// PHP defines inline as __forceinline, which in debug mode causes a warning to be emitted when 
// we use std::copy, which causes compilation to fail since we compile with warnings as errors.
#if defined(ZEND_DEBUG) && defined(inline)
#undef inline
#endif

#include <deque>
#include <map>
#include <algorithm>
#include <limits>
#include <cassert>
#include <strsafe.h>

// included for SQL Server specific constants
#include "sqlncli.h"

//*********************************************************************************************************************************
// Constants and Types
//*********************************************************************************************************************************

// constants for maximums in SQL Server
const int SS_MAXCOLNAMELEN = 128;
const int SQL_SERVER_MAX_FIELD_SIZE = 8000;
const int SQL_SERVER_MAX_PRECISION = 38;
const int SQL_SERVER_MAX_TYPE_SIZE = 0;
const int SQL_SERVER_MAX_PARAMS = 2100;

// max size of a date time string when converting from a DateTime object to a string
const int MAX_DATETIME_STRING_LEN = 256;

// precision and scale for the date time types between servers
const int SQL_SERVER_2005_DEFAULT_DATETIME_PRECISION = 23;
const int SQL_SERVER_2005_DEFAULT_DATETIME_SCALE = 3;
const int SQL_SERVER_2008_DEFAULT_DATETIME_PRECISION = 34;
const int SQL_SERVER_2008_DEFAULT_DATETIME_SCALE = 7;   

// types for conversions on output parameters (though they can be used for input parameters, they are ignored)
enum SQLSRV_PHPTYPE {
    MIN_SQLSRV_PHPTYPE = 1, // lowest value for a php type
    SQLSRV_PHPTYPE_NULL = 1,
    SQLSRV_PHPTYPE_INT,
    SQLSRV_PHPTYPE_FLOAT,
    SQLSRV_PHPTYPE_STRING,
    SQLSRV_PHPTYPE_DATETIME,
    SQLSRV_PHPTYPE_STREAM,
    MAX_SQLSRV_PHPTYPE,      // highest value for a php type
    SQLSRV_PHPTYPE_INVALID = MAX_SQLSRV_PHPTYPE     // used to see if a type is invalid
};

// encodings supported by this extension.  These basically translate into the use of SQL_C_CHAR or SQL_C_BINARY when getting
// information as a string or a stream.  
enum SQLSRV_ENCODING {
    SQLSRV_ENCODING_INVALID,        // unknown or invalid encoding.  Used to initialize variables.
    SQLSRV_ENCODING_DEFAULT,        // use what is the connection's default for a statement, use system if a connection
    SQLSRV_ENCODING_BINARY,         // use SQL_C_BINARY when using SQLGetData
    SQLSRV_ENCODING_CHAR,           // use SQL_C_CHAR when using SQLGetData
    SQLSRV_ENCODING_SYSTEM = SQLSRV_ENCODING_CHAR,
    SQLSRV_ENCODING_UTF8 = CP_UTF8,
};

// the array keys used when returning a row via sqlsrv_fetch_array and sqlsrv_fetch_object.
enum SQLSRV_FETCH_TYPE {
    MIN_SQLSRV_FETCH = 1,        // lowest value for fetch type
    SQLSRV_FETCH_NUMERIC = 1,    // return an array with only numeric indices
    SQLSRV_FETCH_ASSOC = 2,      // return an array with keys made from the field names
    SQLSRV_FETCH_BOTH = 3,       // return an array indexed with both numbers and keys
    MAX_SQLSRV_FETCH = 3,        // highest value for fetch type
};

// buffer size of a sql state (including the null character)
const int SQL_SQLSTATE_BUFSIZE = SQL_SQLSTATE_SIZE + 1;

// buffer size allocated to retrieve data from a PHP stream.  This number
// was chosen since PHP doesn't return more than 8k at a time even if
// the amount requested was more.
const int PHP_STREAM_BUFFER_SIZE = 8192;

// SQL types for parameters encoded in an integer.  The type corresponds to the SQL type ODBC constants.
// The size is the column size or precision, and scale is the decimal digits for precise numeric types.

union sqlsrv_sqltype {
    struct typeinfo_t {
        int type:9;
        int size:14;
        int scale:8;
    } typeinfo;

    long value;
};


// SQLSRV PHP types (as opposed to the Zend PHP type constants).  Contains the type (see SQLSRV_PHPTYPE)
// and the encoding for strings and streams (see SQLSRV_ENCODING) 

union sqlsrv_phptype {

    struct typeinfo_t {
        unsigned type:8;
        unsigned encoding:16;
    } typeinfo;

    long value;
};

// static assert for enforcing compile time conditions
template <bool b>
struct sqlsrv_static_assert;

template <>
struct sqlsrv_static_assert<true> { static const int value = 1; };

#define SQLSRV_STATIC_ASSERT( c )   (sqlsrv_static_assert<(c) != 0>() )


//*********************************************************************************************************************************
// Logging
//*********************************************************************************************************************************
// log_callback
// a driver specific callback for logging messages
// severity - severity of the message: notice, warning, or error
// msg - the message to log in a FormatMessage style formatting
// print_args - args to the message
typedef void (*log_callback)( unsigned int severity TSRMLS_DC, const char* msg, va_list* print_args );

// each driver must register a log callback.  This should be the first thing a driver does.
void core_sqlsrv_register_logger( log_callback );

// a simple wrapper around a PHP error logging function.
void write_to_log( unsigned int severity TSRMLS_DC, const char* msg, ... );

// a macro to make it convenient to use the function.
#define LOG( severity, msg, ...)    write_to_log( severity TSRMLS_CC, msg, __VA_ARGS__ )

// mask for filtering which severities are written to the log
enum logging_severity {
    SEV_ERROR = 0x01,
    SEV_WARNING = 0x02,
    SEV_NOTICE = 0x04,
    SEV_ALL = -1,
};

// Kill the PHP process and log the message to PHP
void die( const char* msg, ... );
#define DIE( msg, ... ) { die( msg, __VA_ARGS__ ); }


//*********************************************************************************************************************************
// Resource/Memory Management
//*********************************************************************************************************************************

// the macro max is defined and overrides the call to max in the allocator class
#pragma push_macro( "max" )
#undef max

// new memory allocation/free debugging facilities to help us verify that all allocations are being 
// released in a timely manner and not just at the end of the script.  
// Zend has memory logging and checking, but it can generate a lot of noise for just one extension.
// It's meant for internal use but might be useful for people adding features to our extension.
// To use it, uncomment the #define below and compile in Debug NTS.  All allocations and releases
// must be done with sqlsrv_malloc and sqlsrv_free.
// #define SQLSRV_MEM_DEBUG  1
#if defined( PHP_DEBUG ) && !defined( ZTS ) && defined( SQLSRV_MEM_DEBUG )

inline void* sqlsrv_malloc_trace( size_t size, const char* file, int line )
{
    void* ptr = emalloc( size );
    LOG( SEV_NOTICE, "emalloc returned %4!08x!: %1!d! bytes at %2!s!:%3!d!", size, file, line, ptr );
    return ptr;
}

inline void* sqlsrv_malloc_trace( size_t element_count, size_t element_size, size_t extra, const char* file, int line )
{
    OACR_WARNING_SUPPRESS( ALLOC_SIZE_OVERFLOW_IN_ALLOC_WRAPPER, "Overflow verified below" );

    if(( element_count > 0 && element_size > 0 ) &&
        ( element_count > element_size * element_count || element_size > element_size * element_count )) {
          DIE( "Integer overflow in sqlsrv_malloc" );
    }

    if( element_size * element_count > element_size * element_count + extra ) {
        DIE( "Integer overflow in sqlsrv_malloc" );
    }

    if( element_size * element_count + extra == 0 ) {
        DIE( "Allocation size must be more than 0" );
    }

    void* ptr = emalloc( element_size * element_count + extra );
    LOG( SEV_NOTICE, "emalloc returned %4!08x!: %1!d! bytes at %2!s!:%3!d!", size, file, line, ptr );
    return ptr;
}

inline void* sqlsrv_realloc_trace( void* buffer, size_t size, const char* file, int line )
{
    void* ptr = erealloc( original, size );
    LOG( SEV_NOTICE, "erealloc returned %5!08x! from %4!08x!: %1!d! bytes at %2!s!:%3!d!", size, file, line, ptr, original );
    return ptr;
}

inline void sqlsrv_free_trace( void* ptr, const char* file, int line )
{
    LOG( SEV_NOTICE, "efree %1!08x! at %2!s!:%3!d!", ptr, file, line );
    efree( ptr );
}

#define sqlsrv_malloc( size ) sqlsrv_malloc_trace( size, __FILE__, __LINE__ )
#define sqlsrv_malloc( count, size, extra ) sqlsrv_malloc_trace( count, size, extra, __FILE__, __LINE__ )
#define sqlsrv_realloc( buffer, size ) sqlsrv_realloc_trace( buffer, size, __FILE__, __LINE__ )
#define sqlsrv_free( ptr ) sqlsrv_free_trace( ptr, __FILE__, __LINE__ )

#else

inline void* sqlsrv_malloc( size_t size )
{
    return emalloc( size );
}

inline void* sqlsrv_malloc( size_t element_count, size_t element_size, size_t extra )
{
    OACR_WARNING_SUPPRESS( ALLOC_SIZE_OVERFLOW_IN_ALLOC_WRAPPER, "Overflow verified below" );

    if(( element_count > 0 && element_size > 0 ) &&
        ( element_count > element_size * element_count || element_size > element_size * element_count )) {
          DIE( "Integer overflow in sqlsrv_malloc" );
    }

    if( element_size * element_count > element_size * element_count + extra ) {
        DIE( "Integer overflow in sqlsrv_malloc" );
    }

    if( element_size * element_count + extra == 0 ) {
        DIE( "Allocation size must be more than 0" );
    }

    return emalloc( element_size * element_count + extra );
}

inline void* sqlsrv_realloc( void* buffer, size_t size )
{
    return erealloc( buffer, size );
}

inline void sqlsrv_free( void* ptr )
{
    efree( ptr );
}

#endif

// trait class that allows us to assign const types to an auto_ptr
template <typename T>
struct remove_const {
    typedef T type;
};

template <typename T>
struct remove_const<const T*> {
    typedef T* type;
};

// allocator that uses the zend memory manager to manage memory
// this allows us to use STL classes that still work with Zend objects
template<typename T>
struct sqlsrv_allocator {
   
    // typedefs used by the STL classes
    typedef T value_type;
    typedef value_type* pointer;
    typedef const value_type* const_pointer;
    typedef value_type& reference;
    typedef const value_type& const_reference;
    typedef std::size_t size_type;
    typedef std::ptrdiff_t difference_type;

    // conversion typedef (used by list and other STL classes)
    template<typename U>
    struct rebind {
        typedef sqlsrv_allocator<U> other;
    };

    inline sqlsrv_allocator() {}
    inline ~sqlsrv_allocator() {}
    inline sqlsrv_allocator( sqlsrv_allocator const& ) {}
    template<typename U>
    inline sqlsrv_allocator( sqlsrv_allocator<U> const& ) {}

    // address (doesn't work if the class defines operator&)
    inline pointer address( reference r )
    { 
        return &r; 
    }

    inline const_pointer address( const_reference r )
    {
        return &r;
    }

    // memory allocation/deallocation
    inline pointer allocate( size_type cnt, 
                             typename std::allocator<void>::const_pointer = 0 )
    {
        return reinterpret_cast<pointer>( sqlsrv_malloc(cnt, sizeof (T), 0)); 
    }

    inline void deallocate( pointer p, size_type ) 
    { 
        sqlsrv_free(p); 
    }

    // size
    inline size_type max_size( void ) const 
    { 
        return std::numeric_limits<size_type>::max() / sizeof(T);
    }

    // object construction/destruction
    inline void construct( pointer p, const T& t )
    {
        new(p) T(t);
    }

    inline void destroy(pointer p)
    {
        p->~T();
    }

    // equality operators
    inline bool operator==( sqlsrv_allocator const& )
    {
        return true;
    }

    inline bool operator!=( sqlsrv_allocator const& a )
    {
        return !operator==(a);
    }
}; 


// base class for auto_ptrs that we define below.  It provides common operators and functions 
// used by all the classes.  
template <typename T, typename Subclass>
class sqlsrv_auto_ptr {

public:

    sqlsrv_auto_ptr( void ) : _ptr( NULL )
    {
    }

    ~sqlsrv_auto_ptr( void )
    {
        static_cast<Subclass*>(this)->reset( NULL );
    }

    // call when ownership is transferred
    void transferred( void )
    {
        _ptr = NULL;
    }

    // explicit function to get the pointer.
    T* get( void ) const
    {
        return _ptr;
    }

    // cast operator to allow auto_ptr to be used where a normal const * can be.
    operator const T* () const
    {
        return _ptr;
    }

    // cast operator to allow auto_ptr to be used where a normal pointer can be.
    operator typename remove_const<T*>::type () const
    {
        return _ptr;
    }

    operator bool() const
    {
        return _ptr != NULL;
    }

    // there are a number of places where we allocate a block intended to be accessed as
    // an array of elements, so this operator allows us to treat the memory as such.
    T& operator[]( int index ) const
    {
        return _ptr[ index ];
    }

    // there are a number of places where we allocate a block intended to be accessed as
    // an array of elements, so this operator allows us to treat the memory as such.
    T& operator[]( unsigned int index ) const
    {
        return _ptr[ index ];
    }

    // there are a number of places where we allocate a block intended to be accessed as
    // an array of elements, so this operator allows us to treat the memory as such.
    T& operator[]( long index ) const
    {
        return _ptr[ index ];
    }

    // there are a number of places where we allocate a block intended to be accessed as
    // an array of elements, so this operator allows us to treat the memory as such.
    T& operator[]( unsigned short index ) const
    {
        return _ptr[ index ];
    }

    // access elements of a structure through the auto ptr
    T* const operator->( void ) const
    {
        return _ptr;
    }

    // value from reference operator (i.e., i = *(&i); or *i = blah;)
    T& operator*() const
    {
        return *_ptr;
    }

    // allow the use of the address-of operator to simulate a **.
    // Note: this operator conflicts with storing these within an STL container.  If you need
    // to do that, then redefine this as getpp and change instances of &auto_ptr to auto_ptr.getpp()
    T** operator&( void )
    {
        return &_ptr;
    }

protected:

    sqlsrv_auto_ptr( T* ptr ) :
        _ptr( ptr ) 
    {
    }

    sqlsrv_auto_ptr( sqlsrv_auto_ptr& src )
    {
        if( _ptr ) {
            static_cast<Subclass*>(this)->reset( src._ptr );
        }
        src.transferred();
    }

    // assign a new pointer to the auto_ptr.  It will free the previous memory block
    // because ownership is deemed finished.
    T* operator=( T* ptr )
    {
        static_cast<Subclass*>( this )->reset( ptr );

        return ptr;
    }

    T* _ptr;   
};

// an auto_ptr for sqlsrv_malloc/sqlsrv_free.  When allocating a chunk of memory using sqlsrv_malloc, wrap that pointer
// in a variable of sqlsrv_malloc_auto_ptr.  sqlsrv_malloc_auto_ptr will "own" that block and assure that it is
// freed until the variable is destroyed (out of scope) or ownership is transferred using the function
// "transferred".
// DO NOT CALL sqlsrv_realloc with a sqlsrv_malloc_auto_ptr.  Use the resize member function.

template <typename T>
class sqlsrv_malloc_auto_ptr : public sqlsrv_auto_ptr<T, sqlsrv_malloc_auto_ptr<T> > {

public:

    sqlsrv_malloc_auto_ptr( void ) :
        sqlsrv_auto_ptr<T, sqlsrv_malloc_auto_ptr<T> >( NULL )
    {
    }

    sqlsrv_malloc_auto_ptr( const sqlsrv_malloc_auto_ptr& src ) :
        sqlsrv_auto_ptr<T, sqlsrv_malloc_auto_ptr<T> >( src )
    {
    }

    // free the original pointer and assign a new pointer. Use NULL to simply free the pointer.
    void reset( T* ptr = NULL )
    {
        if( _ptr )
            sqlsrv_free( (void*) _ptr );
        _ptr = ptr;
    }

    T* operator=( T* ptr )
    {
        return sqlsrv_auto_ptr<T, sqlsrv_malloc_auto_ptr<T> >::operator=( ptr );
    }

    void operator=( sqlsrv_malloc_auto_ptr<T>& src )
    {
        T* p = src.get();
        src.transferred();
        this->_ptr = p;
    }

    // DO NOT CALL sqlsrv_realloc with a sqlsrv_malloc_auto_ptr.  Use the resize member function.
    // has the same parameter list as sqlsrv_realloc: new_size is the size in bytes of the newly allocated buffer
    void resize( size_t new_size )
    {
        _ptr = reinterpret_cast<T*>( sqlsrv_realloc( _ptr, new_size ));
    }
};


// auto ptr for Zend hash tables.  Used to clean up a hash table allocated when 
// something caused an early exit from the function.  This is used when the hash_table is
// allocated in a zval that itself can't be released.  Otherwise, use the zval_auto_ptr.

class hash_auto_ptr : public sqlsrv_auto_ptr<HashTable, hash_auto_ptr> {

public:

    hash_auto_ptr( void ) :
        sqlsrv_auto_ptr<HashTable, hash_auto_ptr>( NULL )
    {
    }

    // free the original pointer and assign a new pointer. Use NULL to simply free the pointer.
    void reset( HashTable* ptr = NULL )
    {
        if( _ptr ) {
            zend_hash_destroy( _ptr );
            FREE_HASHTABLE( _ptr );
        }
        _ptr = ptr;
    }

    HashTable* operator=( HashTable* ptr )
    {
        return sqlsrv_auto_ptr<HashTable, hash_auto_ptr>::operator=( ptr );
    }

private:

    hash_auto_ptr( HashTable const& hash );

    hash_auto_ptr( hash_auto_ptr const& hash );
};


// an auto_ptr for zvals.  When allocating a zval, wrap that pointer in a variable of zval_auto_ptr.  
// zval_auto_ptr will "own" that zval and assure that it is freed when the variable is destroyed 
// (out of scope) or ownership is transferred using the function "transferred".

class zval_auto_ptr : public sqlsrv_auto_ptr<zval, zval_auto_ptr> {

public:

    zval_auto_ptr( void )
    {
    }

    // free the original pointer and assign a new pointer. Use NULL to simply free the pointer.
    void reset( zval* ptr = NULL )
    {
        if( _ptr )
            zval_ptr_dtor( &_ptr );
        _ptr = ptr;
    }

    zval* operator=( zval* ptr )
    {
        return sqlsrv_auto_ptr<zval, zval_auto_ptr>::operator=( ptr );
    }
#if PHP_MAJOR_VERSION > 5 || (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION >= 3)
    operator zval_gc_info*( void )
    {
        return reinterpret_cast<zval_gc_info*>(_ptr);
    }
#endif

private:

    zval_auto_ptr( const zval_auto_ptr& src );
};

#pragma pop_macro( "max" )


//*********************************************************************************************************************************
// sqlsrv_error
//*********************************************************************************************************************************

// *** PHP specific errors ***
// sqlsrv errors are held in a structure of this type used by the driver handle_error functions
// format is a flag that tells the driver error handler functions if there are parameters to use with FormatMessage
// into the error message before returning it.

// base class which can be instatiated with aggregates (see error constants)
struct sqlsrv_error_const {

    SQLCHAR* sqlstate;
    SQLCHAR* native_message;
    SQLINTEGER native_code;
    bool format;
};

// subclass which is used by the core layer to instantiate ODBC errors
struct sqlsrv_error : public sqlsrv_error_const {

    sqlsrv_error( void )
    {
        sqlstate = NULL;
        native_message = NULL;
        native_code = -1;
        format = false;
    }

    sqlsrv_error( SQLCHAR* sql_state, SQLCHAR* message, SQLINTEGER code, bool printf_format = false )
    {
        sqlstate = reinterpret_cast<SQLCHAR*>( sqlsrv_malloc( SQL_SQLSTATE_BUFSIZE ));
        native_message = reinterpret_cast<SQLCHAR*>( sqlsrv_malloc( SQL_MAX_MESSAGE_LENGTH + 1 ));
        strcpy_s( reinterpret_cast<char*>( sqlstate ), SQL_SQLSTATE_BUFSIZE, reinterpret_cast<const char*>( sql_state ));
        strcpy_s( reinterpret_cast<char*>( native_message ), SQL_MAX_MESSAGE_LENGTH + 1, reinterpret_cast<const char*>( message ));
        native_code = code;
        format = printf_format;
    }
    
    sqlsrv_error( sqlsrv_error_const const& prototype )
    {
        sqlsrv_error( prototype.sqlstate, prototype.native_message, prototype.native_code, prototype.format );
    }

    ~sqlsrv_error( void )
    {
        if( sqlstate != NULL ) {
            sqlsrv_free( sqlstate );
        }
        if( native_message != NULL ) {
            sqlsrv_free( native_message );
        }
    }
};


// an auto_ptr for sqlsrv_errors.  These call the destructor explicitly rather than call delete
class sqlsrv_error_auto_ptr : public sqlsrv_auto_ptr<sqlsrv_error, sqlsrv_error_auto_ptr > {

public:

    sqlsrv_error_auto_ptr( void ) :
        sqlsrv_auto_ptr<sqlsrv_error, sqlsrv_error_auto_ptr >( NULL )
    {
    }

    sqlsrv_error_auto_ptr( sqlsrv_error_auto_ptr const& src ) :
        sqlsrv_auto_ptr<sqlsrv_error, sqlsrv_error_auto_ptr >( (sqlsrv_error_auto_ptr&) src )
    {
    }

    // free the original pointer and assign a new pointer. Use NULL to simply free the pointer.
    void reset( sqlsrv_error* ptr = NULL )
    {
        if( _ptr ) {
            _ptr->~sqlsrv_error();
            sqlsrv_free( (void*) _ptr );
        }
        _ptr = ptr;
    }

    sqlsrv_error* operator=( sqlsrv_error* ptr )
    {
        return sqlsrv_auto_ptr<sqlsrv_error, sqlsrv_error_auto_ptr >::operator=( ptr );
    }

    // unlike traditional assignment operators, the chained assignment of an auto_ptr doesn't make much
    // sense.  Only the last one would have anything in it.
    void operator=( sqlsrv_error_auto_ptr& src )
    {
        sqlsrv_error* p = src.get();
        src.transferred();
        this->_ptr = p;
    }
};


//*********************************************************************************************************************************
// Context
//*********************************************************************************************************************************

class sqlsrv_context;
struct sqlsrv_conn;

// error_callback
// a driver specific callback for processing errors. 
// ctx - the context holding the handles 
// sqlsrv_error_code - specific error code to return.
typedef bool (*error_callback)( sqlsrv_context& ctx, unsigned int sqlsrv_error_code, bool error TSRMLS_DC, va_list* print_args );

// sqlsrv_context
// a context holds relevant information to be passed with a connection and statement objects.

class sqlsrv_context {

 public:

    sqlsrv_context( SQLSMALLINT type, error_callback e, void* drv, SQLSRV_ENCODING encoding = SQLSRV_ENCODING_INVALID ) :
        handle_( SQL_NULL_HANDLE ),
        handle_type_( type ),
        err_( e ),
        name_( NULL ),
        driver_( drv ),
        last_error_(),
        encoding_( encoding )
    {
    }

    sqlsrv_context( SQLHANDLE h, SQLSMALLINT t, error_callback e, void* drv, SQLSRV_ENCODING encoding = SQLSRV_ENCODING_INVALID ) :
        handle_( h ),
        handle_type_( t ),
        err_( e ),
        name_( NULL ),
        driver_( drv ),
        last_error_(),
        encoding_( encoding )
    {
    }

    sqlsrv_context( sqlsrv_context const& ctx ) :
        handle_( ctx.handle_ ),
        handle_type_( ctx.handle_type_ ),
        err_( ctx.err_ ),
        name_( ctx.name_ ),
        driver_( ctx.driver_ ),
        last_error_( ctx.last_error_ )
    {
    }

    void set_func( const char* f )
    {
        name_ = f;
    }

    void set_last_error( sqlsrv_error_auto_ptr& last_error )
    {
        last_error_ = last_error;
    }

    sqlsrv_error_auto_ptr& last_error( void )
    {
        return last_error_;
    }
    
    // since the primary responsibility of a context is to hold an ODBC handle, we
    // provide these convenience operators for using them interchangeably
    operator SQLHANDLE ( void ) const
    {
        return handle_;
    }

    error_callback error_handler( void ) const
    {
        return err_;
    }

    SQLHANDLE handle( void ) const
    {
        return handle_;
    }

    SQLSMALLINT handle_type( void ) const
    {
        return handle_type_;
    }

    const char* func( void ) const
    {
        return name_;
    }

    void* driver( void ) const
    {
        return driver_;
    }

    void set_driver( void* driver )
    {
        this->driver_ = driver;
    }

    void invalidate( void )
    {
        if( handle_ != SQL_NULL_HANDLE ) {
            ::SQLFreeHandle( handle_type_, handle_ );
        }
        handle_ = SQL_NULL_HANDLE;
    }

    bool valid( void )
    {
        return handle_ != SQL_NULL_HANDLE;
    }

    SQLSRV_ENCODING encoding( void ) const
    {
        return encoding_;
    }

    void set_encoding( SQLSRV_ENCODING e )
    {
        encoding_ = e;
    }

 private:
    SQLHANDLE              handle_;          // ODBC handle for this context
    SQLSMALLINT            handle_type_;     // type of the ODBC handle
    const char*            name_;            // function name currently executing this context
    error_callback         err_;             // driver error callback if error occurs in core layer
    void*                  driver_;          // points back to the driver for PDO
    sqlsrv_error_auto_ptr  last_error_;      // last error that happened on this object
    SQLSRV_ENCODING        encoding_;        // encoding of the context   
};

const int SQLSRV_OS_VISTA_OR_LATER = 6;           // major version for Vista

// maps an IANA encoding to a code page
struct sqlsrv_encoding {

    const char* iana;
    unsigned int iana_len;
    unsigned int code_page;
    bool not_for_connection;

    sqlsrv_encoding( const char* iana, unsigned int code_page, bool not_for_conn = false ):
        iana( iana ), iana_len( strlen( iana )), code_page( code_page ), not_for_connection( not_for_conn )
    {
    }
};


//*********************************************************************************************************************************
// Initialization
//*********************************************************************************************************************************

// variables set during initialization
extern OSVERSIONINFO g_osversion;                 // used to determine which OS we're running in
extern HashTable* g_encodings;                    // encodings supported by this driver

void core_sqlsrv_minit( sqlsrv_context** henv_cp, sqlsrv_context** henv_ncp, error_callback err, const char* driver_func TSRMLS_DC );
void core_sqlsrv_mshutdown( sqlsrv_context& henv_cp, sqlsrv_context& henv_ncp );

// environment context used by sqlsrv_connect for when a connection error occurs.
struct sqlsrv_henv {

    sqlsrv_context ctx;

    sqlsrv_henv( SQLHANDLE handle, error_callback e, void* drv  ) :
        ctx( handle, SQL_HANDLE_ENV, e, drv )
    {
    }
};


//*********************************************************************************************************************************
// Connection
//*********************************************************************************************************************************

// supported server versions (determined at connection time)
enum SERVER_VERSION {
    SERVER_VERSION_UNKNOWN = -1,
    SERVER_VERSION_2000 = 8,
    SERVER_VERSION_2005,
    SERVER_VERSION_2008, // use this for anything 2008 or later
};

// forward decl
struct sqlsrv_stmt;
struct stmt_option;

// *** connection resource structure ***
// this is the resource structure returned when a connection is made.
struct sqlsrv_conn : public sqlsrv_context {

    // instance variables
    SERVER_VERSION server_version;  // version of the server that we're connected to

    // initialize with default values
    sqlsrv_conn( SQLHANDLE h, error_callback e, void* drv, SQLSRV_ENCODING encoding  TSRMLS_DC ) :
        sqlsrv_context( h, SQL_HANDLE_DBC, e, drv, encoding )
    {
    }

    // sqlsrv_conn has no destructor since its allocated using placement new, which requires that the destructor be 
    // called manually.  Instead, we leave it to the allocator to invalidate the handle when an error occurs allocating
    // the sqlsrv_conn with a connection.
};

enum SQLSRV_STMT_OPTIONS {
   
   SQLSRV_STMT_OPTION_INVALID,
   SQLSRV_STMT_OPTION_QUERY_TIMEOUT,
   SQLSRV_STMT_OPTION_SEND_STREAMS_AT_EXEC,
   SQLSRV_STMT_OPTION_SCROLLABLE,
   SQLSRV_STMT_OPTION_CLIENT_BUFFER_MAX_SIZE,

   // Driver specific connection options
   SQLSRV_STMT_OPTION_DRIVER_SPECIFIC = 1000,

};

namespace ODBCConnOptions {

const char APP[] = "APP";
const char ApplicationIntent[] = "ApplicationIntent";
const char AttachDBFileName[] = "AttachDbFileName";
const char CharacterSet[] = "CharacterSet";
const char ConnectionPooling[] = "ConnectionPooling";
const char Database[] = "Database";
const char Encrypt[] = "Encrypt";
const char Failover_Partner[] = "Failover_Partner";
const char LoginTimeout[] = "LoginTimggeout";
const char MARS_ODBC[] = "MARS_Connection";
const char MultiSubnetFailover[] = "MultiSubnetFailover";
const char QuotedId[] = "QuotedId";
const char TraceFile[] = "TraceFile";
const char TraceOn[] = "TraceOn";
const char TrustServerCertificate[] = "TrustServerCertificate";
const char TransactionIsolation[] = "TransactionIsolation";
const char WSID[] = "WSID";
const char UID[] = "UID";
const char PWD[] = "PWD";
const char SERVER[] = "Server";

}

enum SQLSRV_CONN_OPTIONS {
   
   SQLSRV_CONN_OPTION_INVALID,
   SQLSRV_CONN_OPTION_APP,
   SQLSRV_CONN_OPTION_CHARACTERSET,
   SQLSRV_CONN_OPTION_CONN_POOLING,
   SQLSRV_CONN_OPTION_DATABASE,
   SQLSRV_CONN_OPTION_ENCRYPT,
   SQLSRV_CONN_OPTION_FAILOVER_PARTNER,
   SQLSRV_CONN_OPTION_LOGIN_TIMEOUT,
   SQLSRV_CONN_OPTION_MARS,
   SQLSRV_CONN_OPTION_QUOTED_ID,
   SQLSRV_CONN_OPTION_TRACE_FILE,
   SQLSRV_CONN_OPTION_TRACE_ON,
   SQLSRV_CONN_OPTION_TRANS_ISOLATION,
   SQLSRV_CONN_OPTION_TRUST_SERVER_CERT,
   SQLSRV_CONN_OPTION_WSID,
   SQLSRV_CONN_OPTION_ATTACHDBFILENAME,
   SQLSRV_CONN_OPTION_APPLICATION_INTENT,
   SQLSRV_CONN_OPTION_MULTI_SUBNET_FAILOVER,

   // Driver specific connection options
   SQLSRV_CONN_OPTION_DRIVER_SPECIFIC = 1000,
   
};


#define NO_ATTRIBUTE -1

// type of connection attributes
enum CONN_ATTR_TYPE {
    CONN_ATTR_INT,
    CONN_ATTR_BOOL,
    CONN_ATTR_STRING,
    CONN_ATTR_INVALID,
};

// a connection option that includes the callback function that handles that option (e.g., adds it to the connection string or
// sets an attribute)
struct connection_option {
    // the name of the option as passed in by the user
    const char *        sqlsrv_name;
    unsigned int        sqlsrv_len;
    
    unsigned int        conn_option_key;
    // the name of the option in the ODBC connection string
    const char *        odbc_name;
    unsigned int        odbc_len;
    enum CONN_ATTR_TYPE value_type;

    // process the connection type
    // return whether or not the function was successful in processing the connection option
    void                (*func)( connection_option const*, zval* value, sqlsrv_conn* conn, std::string& conn_str TSRMLS_DC );
};

// connection attribute functions
template <unsigned int Attr>
struct str_conn_attr_func {

    static void func( connection_option const* /*option*/, zval* value, sqlsrv_conn* conn, std::string& /*conn_str*/ TSRMLS_DC )
    {
        try {
        
            core::SQLSetConnectAttr( conn, Attr, reinterpret_cast<SQLPOINTER>( Z_STRVAL_P( value )),
                                     Z_STRLEN_P( value ) TSRMLS_CC );
        }
        catch( core::CoreException& ) {
            throw;
        }
    }
};

// simply add the parsed value to the connection string
struct conn_str_append_func {

    static void func( connection_option const* option, zval* value, sqlsrv_conn* /*conn*/, std::string& conn_str TSRMLS_DC );
};

struct conn_null_func {

    static void func( connection_option const* /*option*/, zval* /*value*/, sqlsrv_conn* /*conn*/, std::string& /*conn_str*/ 
                      TSRMLS_DC );
};

// factory to create a connection (since they are subclassed to instantiate statements)
typedef sqlsrv_conn* (*driver_conn_factory)( SQLHANDLE h, error_callback e, void* drv TSRMLS_DC );

// *** connection functions ***
sqlsrv_conn* core_sqlsrv_connect( sqlsrv_context& henv_cp, sqlsrv_context& henv_ncp, driver_conn_factory conn_factory,
                                  const char* server, const char* uid, const char* pwd, 
                                  HashTable* options_ht, error_callback err, const connection_option driver_conn_opt_list[], 
                                  void* driver, const char* driver_func TSRMLS_DC );
void core_sqlsrv_close( sqlsrv_conn* conn TSRMLS_DC );
void core_sqlsrv_prepare( sqlsrv_stmt* stmt, const char* sql, long sql_len TSRMLS_DC );
void core_sqlsrv_begin_transaction( sqlsrv_conn* conn TSRMLS_DC );
void core_sqlsrv_commit( sqlsrv_conn* conn TSRMLS_DC );
void core_sqlsrv_rollback( sqlsrv_conn* conn TSRMLS_DC );
void core_sqlsrv_get_server_info( sqlsrv_conn* conn, __out zval* server_info TSRMLS_DC );
void core_sqlsrv_get_server_version( sqlsrv_conn* conn, __out zval *server_version TSRMLS_DC );
void core_sqlsrv_get_client_info( sqlsrv_conn* conn, __out zval *client_info TSRMLS_DC );
bool core_is_conn_opt_value_escaped( const char* value, int value_len );
int core_str_zval_is_true( zval* str_zval );

//*********************************************************************************************************************************
// Statement
//*********************************************************************************************************************************

struct stmt_option_functor {

    virtual void operator()( sqlsrv_stmt* /*stmt*/, stmt_option const* /*opt*/, zval* /*value_z*/ TSRMLS_DC );
};

struct stmt_option_query_timeout : public stmt_option_functor {

    virtual void operator()( sqlsrv_stmt* stmt, stmt_option const* opt, zval* value_z TSRMLS_DC );
};

struct stmt_option_send_at_exec : public stmt_option_functor {

    virtual void operator()( sqlsrv_stmt* stmt, stmt_option const* opt, zval* value_z TSRMLS_DC );
};

struct stmt_option_buffered_query_limit : public stmt_option_functor {

    virtual void operator()( sqlsrv_stmt* stmt, stmt_option const* opt, zval* value_z TSRMLS_DC );
};

// used to hold the table for statment options
struct stmt_option {

    const char *         name;        // name of the statement option
    unsigned int         name_len;    // name length
    unsigned int         key;         
    stmt_option_functor* func;        // callback that actually handles the work of the option
    
};

// holds the stream param and the encoding that it was assigned
struct sqlsrv_stream {

    zval* stream_z;
    SQLSRV_ENCODING encoding;
    SQLUSMALLINT field_index;
    SQLSMALLINT sql_type;
    sqlsrv_stmt* stmt;
    int stmt_index;

    sqlsrv_stream( zval* str_z, SQLSRV_ENCODING enc ) :
        stream_z( str_z ), encoding( enc )
    {
    }

    sqlsrv_stream() : stream_z( NULL ), encoding( SQLSRV_ENCODING_INVALID ), stmt( NULL )
    {
    }
};

// close any active stream
void close_active_stream( __inout sqlsrv_stmt* stmt TSRMLS_DC );

extern php_stream_wrapper g_sqlsrv_stream_wrapper;

// resource constants used when registering the stream type with PHP
#define SQLSRV_STREAM_WRAPPER "sqlsrv"
#define SQLSRV_STREAM         "sqlsrv_stream"

// holds the output parameter information.  Strings also need the encoding and other information for
// after processing.  Only integer, float, and strings are allowable output parameters.
struct sqlsrv_output_param {

    zval* param_z;
    SQLSRV_ENCODING encoding;
    int param_num;  // used to index into the ind_or_len of the statement
    SQLLEN original_buffer_len; // used to make sure the returned length didn't overflow the buffer
    bool is_bool;

    // string output param constructor
    sqlsrv_output_param( zval* p_z, SQLSRV_ENCODING enc, int num, SQLUINTEGER buffer_len ) :
        param_z( p_z ), encoding( enc ), param_num( num ), original_buffer_len( buffer_len ), is_bool( false )
    {
    }

    // every other type output parameter constructor
    sqlsrv_output_param( zval* p_z, int num, bool is_bool ) :
        param_z( p_z ),
        param_num( num ),
        encoding( SQLSRV_ENCODING_INVALID ),
        original_buffer_len( -1 ),
        is_bool( is_bool )
    {
    }
};

// forward decls
struct sqlsrv_result_set;

// *** Statement resource structure *** 
struct sqlsrv_stmt : public sqlsrv_context {

    void free_param_data( TSRMLS_D );
    virtual void new_result_set( TSRMLS_D );

    sqlsrv_conn*   conn;                  // Connection that created this statement
   
    bool executed;                        // Whether the statement has been executed yet (used for error messages)
    bool past_fetch_end;                  // Core_sqlsrv_fetch sets this field when the statement goes beyond the last row
    sqlsrv_result_set* current_results;   // Current result set
    SQLULEN cursor_type;                  // Type of cursor for the current result set
    bool has_rows;                        // Has_rows is set if there are actual rows in the row set
    bool fetch_called;                    // Used by core_sqlsrv_get_field to return an informative error if fetch not yet called 
    int last_field_index;                 // last field retrieved by core_sqlsrv_get_field
    bool past_next_result_end;            // core_sqlsrv_next_result sets this to true when the statement goes beyond the 
                                          // last results
    unsigned long query_timeout;          // maximum allowed statement execution time
    unsigned long buffered_query_limit;   // maximum allowed memory for a buffered query (measured in KB)

    // holds output pointers for SQLBindParameter
    // We use a deque because it 1) provides the at/[] access in constant time, and 2) grows dynamically without moving
    // memory, which is important because we pass the pointer to an element of the deque to SQLBindParameter to hold
    std::deque<SQLLEN>   param_ind_ptrs;  // output pointers for lengths for calls to SQLBindParameter
    zval* param_input_strings;            // hold all UTF-16 input strings that aren't managed by PHP
    zval* output_params;                  // hold all the output parameters
    zval* param_streams;                  // track which streams to send data to the server
    zval* param_datetime_buffers;         // datetime strings to be converted back to DateTime objects
    bool send_streams_at_exec;            // send all stream data right after execution before returning
    sqlsrv_stream current_stream;         // current stream sending data to the server as an input parameter
    unsigned int current_stream_read;     // # of bytes read so far. (if we read an empty PHP stream, we send an empty string 
                                          // to the server)
    zval* field_cache;                    // cache for a single row of fields, to allow multiple and out of order retrievals
    zval* active_stream;                  // the currently active stream reading data from the database

    sqlsrv_stmt( sqlsrv_conn* c, SQLHANDLE handle, error_callback e, void* drv TSRMLS_DC );
    virtual ~sqlsrv_stmt( void );

    // driver specific conversion rules from a SQL Server/ODBC type to one of the SQLSRV_PHPTYPE_* constants
    virtual sqlsrv_phptype sql_type_to_php_type( SQLINTEGER sql_type, SQLUINTEGER size, bool prefer_string_to_stream ) = 0;

};

// *** field metadata struct ***
struct field_meta_data {

    sqlsrv_malloc_auto_ptr<SQLCHAR> field_name;
    SQLSMALLINT field_name_len;
    SQLSMALLINT field_type;
    SQLULEN field_size;
    SQLULEN field_precision;
    SQLSMALLINT field_scale;     
    SQLSMALLINT field_is_nullable;

    field_meta_data() : field_name_len(0), field_type(0), field_size(0), field_precision(0),
                        field_scale (0), field_is_nullable(0)
    {
    }

    ~field_meta_data() 
    {
    }
};

// *** statement constants ***
// unknown column size used by core_sqlsrv_bind_param when the user doesn't supply a value
const SQLULEN SQLSRV_UNKNOWN_SIZE = 0xffffffff;
const int SQLSRV_DEFAULT_SIZE = -1;     // size given for an output parameter that doesn't really need one (e.g., int)

// uninitialized query timeout value
const unsigned int QUERY_TIMEOUT_INVALID = 0xffffffff;

// special buffered query constant
const size_t SQLSRV_CURSOR_BUFFERED = 0xfffffffeUL; // arbitrary number that doesn't map to any other SQL_CURSOR_* constant

// factory to create a statement
typedef sqlsrv_stmt* (*driver_stmt_factory)( sqlsrv_conn* conn, SQLHANDLE h, error_callback e, void* drv TSRMLS_DC );

// *** statement functions ***
sqlsrv_stmt* core_sqlsrv_create_stmt( sqlsrv_conn* conn, driver_stmt_factory stmt_factory, HashTable* options_ht, 
                                      const stmt_option valid_stmt_opts[], error_callback const err, void* driver TSRMLS_DC );
void core_sqlsrv_bind_param( sqlsrv_stmt* stmt, unsigned int param_num, int direction, zval* param_z, 
                             SQLSRV_PHPTYPE php_out_type, SQLSRV_ENCODING encoding, SQLSMALLINT sql_type, SQLULEN column_size,
                             SQLSMALLINT decimal_digits TSRMLS_DC );
void core_sqlsrv_execute( sqlsrv_stmt* stmt TSRMLS_DC, const char* sql = NULL, int sql_len = 0 );
field_meta_data* core_sqlsrv_field_metadata( sqlsrv_stmt* stmt, SQLSMALLINT colno TSRMLS_DC );
bool core_sqlsrv_fetch( sqlsrv_stmt* stmt, SQLSMALLINT fetch_orientation, SQLLEN fetch_offset TSRMLS_DC );
void core_sqlsrv_get_field( sqlsrv_stmt* stmt, SQLUSMALLINT field_index, sqlsrv_phptype sqlsrv_phptype, bool prefer_string,
                            __out void** field_value, __out SQLLEN* field_length,  bool cache_field, 
                            __out SQLSRV_PHPTYPE *sqlsrv_php_type_out TSRMLS_DC );
bool core_sqlsrv_has_any_result( sqlsrv_stmt* stmt TSRMLS_DC );
void core_sqlsrv_next_result( sqlsrv_stmt* stmt TSRMLS_DC, bool finalize_output_params = true, bool throw_on_errors = true );
void core_sqlsrv_post_param( sqlsrv_stmt* stmt, unsigned int paramno, zval* param_z TSRMLS_DC );
void core_sqlsrv_set_scrollable( sqlsrv_stmt* stmt, unsigned int cursor_type TSRMLS_DC );
void core_sqlsrv_set_query_timeout( sqlsrv_stmt* stmt, long timeout TSRMLS_DC );
void core_sqlsrv_set_query_timeout( sqlsrv_stmt* stmt, zval* value_z TSRMLS_DC );
void core_sqlsrv_set_send_at_exec( sqlsrv_stmt* stmt, zval* value_z TSRMLS_DC );
bool core_sqlsrv_send_stream_packet( sqlsrv_stmt* stmt TSRMLS_DC );
void core_sqlsrv_set_buffered_query_limit( sqlsrv_stmt* stmt, zval* value_z TSRMLS_DC );
void core_sqlsrv_set_buffered_query_limit( sqlsrv_stmt* stmt, long limit TSRMLS_DC );


//*********************************************************************************************************************************
// Result Set
//*********************************************************************************************************************************

// Abstract the result set so that a result set can either be used as is from ODBC or buffered.
// This is not a complete abstraction of a result set.  Only enough is abstracted to allow for
// information and capabilities normally not available when a result set is not buffered
// (e.g., forward only vs buffered means row count is available and cursor movement is possible).
// Otherwise, normal ODBC calls are still valid and should be used to get information about the
// result set (e.g., SQLNumResultCols).

struct sqlsrv_result_set {

    sqlsrv_stmt* odbc;

    explicit sqlsrv_result_set( sqlsrv_stmt* );
    virtual ~sqlsrv_result_set( void ) { }

    virtual bool cached( int field_index ) = 0;
    virtual SQLRETURN fetch( SQLSMALLINT fetch_orientation, SQLLEN fetch_offset TSRMLS_DC ) = 0;
    virtual SQLRETURN get_data( SQLUSMALLINT field_index, SQLSMALLINT target_type,
                                __out void* buffer, SQLLEN buffer_length, __out SQLLEN* out_buffer_length,
                                bool handle_warning TSRMLS_DC )= 0;
    virtual SQLRETURN get_diag_field( SQLSMALLINT record_number, SQLSMALLINT diag_identifier, 
                                      __out SQLPOINTER diag_info_buffer, SQLSMALLINT buffer_length,
                                      __out SQLSMALLINT* out_buffer_length TSRMLS_DC ) = 0;
    virtual sqlsrv_error* get_diag_rec( SQLSMALLINT record_number ) = 0;
    virtual SQLLEN row_count( TSRMLS_D ) = 0;
};

struct sqlsrv_odbc_result_set : public sqlsrv_result_set {

    explicit sqlsrv_odbc_result_set( sqlsrv_stmt* );
    virtual ~sqlsrv_odbc_result_set( void );

    virtual bool cached( int field_index ) { return false; }
    virtual SQLRETURN fetch( SQLSMALLINT fetch_orientation, SQLLEN fetch_offset TSRMLS_DC );
    virtual SQLRETURN get_data( SQLUSMALLINT field_index, SQLSMALLINT target_type,
                                __out void* buffer, SQLLEN buffer_length, __out SQLLEN* out_buffer_length,
                                bool handle_warning TSRMLS_DC );
    virtual SQLRETURN get_diag_field( SQLSMALLINT record_number, SQLSMALLINT diag_identifier, 
                                      __out SQLPOINTER diag_info_buffer, SQLSMALLINT buffer_length,
                                      __out SQLSMALLINT* out_buffer_length TSRMLS_DC );
    virtual sqlsrv_error* get_diag_rec( SQLSMALLINT record_number );
    virtual SQLLEN row_count( TSRMLS_D );

 private:
    // prevent invalid instantiations and assignments
    sqlsrv_odbc_result_set( void );
    sqlsrv_odbc_result_set( sqlsrv_odbc_result_set& );
    sqlsrv_odbc_result_set& operator=( sqlsrv_odbc_result_set& );
};

struct sqlsrv_buffered_result_set : public sqlsrv_result_set {

    struct meta_data {
        SQLSMALLINT type;
        SQLSMALLINT c_type;     // convenience
        SQLULEN offset;         // in bytes
        SQLULEN length;         // in bytes
        SQLSMALLINT scale;

        static const SQLULEN SIZE_UNKNOWN = 0;
    };

    // default maximum amount of memory that a buffered query can consume
    #define INI_BUFFERED_QUERY_LIMIT_DEFAULT    "10240" // default used by the php.ini settings
    static const unsigned long BUFFERED_QUERY_LIMIT_DEFAULT = 10240;   // measured in KB
    static const long BUFFERED_QUERY_LIMIT_INVALID = 0;

    explicit sqlsrv_buffered_result_set( sqlsrv_stmt* odbc TSRMLS_DC );
    virtual ~sqlsrv_buffered_result_set( void );

    virtual bool cached( int field_index ) { return true; }
    virtual SQLRETURN fetch( SQLSMALLINT fetch_orientation, SQLLEN fetch_offset TSRMLS_DC );
    virtual SQLRETURN get_data( SQLUSMALLINT field_index, SQLSMALLINT target_type,
                                __out void* buffer, SQLLEN buffer_length, __out SQLLEN* out_buffer_length,
                                bool handle_warning TSRMLS_DC );
    virtual SQLRETURN get_diag_field( SQLSMALLINT record_number, SQLSMALLINT diag_identifier, 
                                      __out SQLPOINTER diag_info_buffer, SQLSMALLINT buffer_length,
                                      __out SQLSMALLINT* out_buffer_length TSRMLS_DC );
    virtual sqlsrv_error* get_diag_rec( SQLSMALLINT record_number );
    virtual SQLLEN row_count( TSRMLS_D );

    // buffered result set specific 
    SQLSMALLINT column_count( void )
    {
        return col_count;
    }

    struct meta_data& col_meta_data( SQLSMALLINT i )
    {
        return meta[i];
    }

 private:
    // prevent invalid instantiations and assignments
    sqlsrv_buffered_result_set( void );
    sqlsrv_buffered_result_set( sqlsrv_buffered_result_set& );
    sqlsrv_buffered_result_set& operator=( sqlsrv_buffered_result_set& );

    HashTable* cache;                   // rows of data kept in index based hash table
    SQLSMALLINT col_count;            // number of columns in the current result set
    meta_data* meta;                    // metadata for fields in the cache
    SQLLEN current;                     // 1 based, 0 means before first row
    sqlsrv_error_auto_ptr last_error;   // if an error occurred, it is kept here
    SQLUSMALLINT last_field_index;      // the last field data retrieved from
    SQLLEN read_so_far;                 // position within string to read from (for partial reads of strings)
    sqlsrv_malloc_auto_ptr<SQLCHAR> temp_string;   // temp buffer to hold a converted field while in use
    SQLLEN temp_length;                 // number of bytes in the temp conversion buffer

    typedef SQLRETURN (sqlsrv_buffered_result_set::*conv_fn)( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                                              __out SQLLEN* out_buffer_length );
    typedef std::map< SQLINTEGER, std::map< SQLINTEGER, conv_fn > > conv_matrix_t;

    // two dimentional sparse matrix that holds the [from][to] functions that do conversions
    static conv_matrix_t conv_matrix;

    // string conversion functions
    SQLRETURN binary_to_wide_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                     __out SQLLEN* out_buffer_length );
    SQLRETURN binary_to_system_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                       __out SQLLEN* out_buffer_length );
    SQLRETURN system_to_wide_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                     __out SQLLEN* out_buffer_length );
    SQLRETURN to_binary_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                __out SQLLEN* out_buffer_length );
    SQLRETURN to_same_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                               __out SQLLEN* out_buffer_length );
    SQLRETURN wide_to_system_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                     __out SQLLEN* out_buffer_length );

    // long conversion functions
    SQLRETURN to_long( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, __out SQLLEN* out_buffer_length );
    SQLRETURN long_to_system_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                     __out SQLLEN* out_buffer_length );
    SQLRETURN long_to_wide_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                     __out SQLLEN* out_buffer_length );
    SQLRETURN long_to_double( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                              __out SQLLEN* out_buffer_length );

    // double conversion functions
    SQLRETURN to_double( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, __out SQLLEN* out_buffer_length );
    SQLRETURN double_to_system_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                       __out SQLLEN* out_buffer_length );
    SQLRETURN double_to_wide_string( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                     __out SQLLEN* out_buffer_length );
    SQLRETURN double_to_long( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                              __out SQLLEN* out_buffer_length );

    // string to number conversion functions
    // Future: See if these can be converted directly to template member functions
    SQLRETURN string_to_double( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                 __out SQLLEN* out_buffer_length );
    SQLRETURN string_to_long( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                              __out SQLLEN* out_buffer_length );
    SQLRETURN wstring_to_double( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                                 __out SQLLEN* out_buffer_length );
    SQLRETURN wstring_to_long( SQLSMALLINT field_index, __out void* buffer, SQLLEN buffer_length, 
                               __out SQLLEN* out_buffer_length );

    // utility functions for conversions
    unsigned char* get_row( void );
};

//*********************************************************************************************************************************
// Utility
//*********************************************************************************************************************************

// Simple macro to alleviate unused variable warnings.  These are optimized out by the compiler.
// We use this since the unused variables are buried in the PHP_FUNCTION macro.
#define SQLSRV_UNUSED( var )   var;

// do a heap check in debug mode, but only print errors, not all of the allocations
#define MEMCHECK_SILENT 1

// utility functions shared by multiple callers across files
bool convert_string_from_utf16( SQLSRV_ENCODING encoding, char** string, SQLINTEGER& len, bool free_utf16 = true );
wchar_t* utf16_string_from_mbcs_string( SQLSRV_ENCODING php_encoding, const char* mbcs_string, 
                                        unsigned int mbcs_len, __out unsigned int* utf16_len );


//*********************************************************************************************************************************
// Error handling routines and Predefined Errors
//*********************************************************************************************************************************

enum SQLSRV_ERROR_CODES {

    SQLSRV_ERROR_ODBC,
    SQLSRV_ERROR_DRIVER_NOT_INSTALLED,
    SQLSRV_ERROR_ZEND_HASH,
    SQLSRV_ERROR_INVALID_PARAMETER_PHPTYPE,
    SQLSRV_ERROR_INVALID_PARAMETER_SQLTYPE,
    SQLSRV_ERROR_INVALID_PARAMETER_ENCODING,
    SQLSRV_ERROR_INPUT_PARAM_ENCODING_TRANSLATE,
    SQLSRV_ERROR_OUTPUT_PARAM_ENCODING_TRANSLATE,
    SQLSRV_ERROR_CONNECT_STRING_ENCODING_TRANSLATE,
    SQLSRV_ERROR_ZEND_STREAM, 
    SQLSRV_ERROR_INPUT_STREAM_ENCODING_TRANSLATE,
    SQLSRV_ERROR_UNKNOWN_SERVER_VERSION,
    SQLSRV_ERROR_FETCH_PAST_END,
    SQLSRV_ERROR_STATEMENT_NOT_EXECUTED,
    SQLSRV_ERROR_NO_FIELDS,
    SQLSRV_ERROR_INVALID_TYPE,
    SQLSRV_ERROR_FETCH_NOT_CALLED,
    SQLSRV_ERROR_NO_DATA,
    SQLSRV_ERROR_FIELD_ENCODING_TRANSLATE,
    SQLSRV_ERROR_ZEND_HASH_CREATE_FAILED,
    SQLSRV_ERROR_NEXT_RESULT_PAST_END,
    SQLSRV_ERROR_UID_PWD_BRACES_NOT_ESCAPED,
    SQLSRV_ERROR_INVALID_OPTION_TYPE_INT,
    SQLSRV_ERROR_INVALID_OPTION_TYPE_STRING,
    SQLSRV_ERROR_CONN_OPTS_WRONG_TYPE,
    SQLSRV_ERROR_INVALID_CONNECTION_KEY,
    SQLSRV_ERROR_MAX_PARAMS_EXCEEDED,
    SQLSRV_ERROR_INVALID_OPTION_KEY,
    SQLSRV_ERROR_INVALID_QUERY_TIMEOUT_VALUE,
    SQLSRV_ERROR_INVALID_OPTION_SCROLLABLE,
    SQLSRV_ERROR_QUERY_STRING_ENCODING_TRANSLATE,
    SQLSRV_ERROR_OUTPUT_PARAM_TRUNCATED,
    SQLSRV_ERROR_INPUT_OUTPUT_PARAM_TYPE_MATCH,
    SQLSRV_ERROR_DATETIME_CONVERSION_FAILED,
    SQLSRV_ERROR_STREAMABLE_TYPES_ONLY,
    SQLSRV_ERROR_STREAM_CREATE,
    SQLSRV_ERROR_MARS_OFF,
    SQLSRV_ERROR_FIELD_INDEX_ERROR,
    SQLSRV_ERROR_BUFFER_LIMIT_EXCEEDED,
    SQLSRV_ERROR_INVALID_BUFFER_LIMIT,

    // Driver specific error codes starts from here.
    SQLSRV_ERROR_DRIVER_SPECIFIC = 1000,

};

// the message returned by SQL Native Client
const char CONNECTION_BUSY_ODBC_ERROR[] = "[Microsoft][SQL Server Native Client 11.0]Connection is busy with results for "
    "another command";

// SQLSTATE for all internal errors
extern SQLCHAR IMSSP[];

// SQLSTATE for all internal warnings
extern SQLCHAR SSPWARN[];

// flags passed to sqlsrv_errors to filter its return values
enum error_handling_flags {
    SQLSRV_ERR_ERRORS,
    SQLSRV_ERR_WARNINGS,
    SQLSRV_ERR_ALL
};

// *** internal error macros and functions ***
// call to retrieve an error from ODBC.  This uses SQLGetDiagRec, so the
// errno is 1 based.  It returns it as an array with 3 members:
// 1/SQLSTATE) sqlstate
// 2/code) driver specific error code
// 3/message) driver specific error message
// The fetch type determines if the indices are numeric, associative, or both.
bool core_sqlsrv_get_odbc_error( sqlsrv_context& ctx, int record_number, __out sqlsrv_error_auto_ptr& error, 
                                 logging_severity severity TSRMLS_DC );

// format and return a driver specfic error
void core_sqlsrv_format_driver_error( sqlsrv_context& ctx, sqlsrv_error_const const* custom_error, 
                                      sqlsrv_error_auto_ptr& formatted_error, logging_severity severity TSRMLS_DC, va_list* args );


// return the message for the HRESULT returned by GetLastError.  Some driver errors use this to
// return the Windows error, e.g, when a UTF-8 <-> UTF-16 conversion fails.
const char* get_last_error_message( DWORD last_error = 0 );

// a wrapper around FormatMessage that can take variadic args rather than a a va_arg pointer
DWORD core_sqlsrv_format_message( char* output_buffer, unsigned output_len, const char* format, ... );

// convenience functions that overload either a reference or a pointer so we can use
// either in the CHECK_* functions.
inline bool call_error_handler( sqlsrv_context& ctx, unsigned int sqlsrv_error_code TSRMLS_DC, bool warning, ... )
{
    va_list print_params;
    va_start( print_params, warning );
    bool ignored = ctx.error_handler()( ctx, sqlsrv_error_code, warning TSRMLS_CC, &print_params );
    va_end( print_params );
    return ignored;
}

inline bool call_error_handler( sqlsrv_context* ctx, unsigned int sqlsrv_error_code TSRMLS_DC, bool warning, ... )
{
    va_list print_params;
    va_start( print_params, warning );
    bool ignored = ctx->error_handler()( *ctx, sqlsrv_error_code, warning TSRMLS_CC, &print_params );
    va_end( print_params );
    return ignored;
}

// PHP equivalent of ASSERT.  C asserts cause a dialog to show and halt the process which
// we don't want on a web server

#define SQLSRV_ASSERT( condition, msg, ...)  if( !(condition)) DIE( msg, __VA_ARGS__ );
 
#if defined( PHP_DEBUG )                             

#define DEBUG_SQLSRV_ASSERT( condition, msg, ... )    \
    if( !(condition)) {                               \
        DIE (msg, __VA_ARGS__ );                      \
    }                 

#else

    #define DEBUG_SQLSRV_ASSERT( condition, msg, ... ) ((void)0)

#endif 

// check to see if the sqlstate is 01004, truncated field retrieved.  Used for retrieving large fields.
inline bool is_truncated_warning( SQLCHAR* state )
{
#if defined(ZEND_DEBUG)
    if( state == NULL || strlen( reinterpret_cast<char*>( state )) != 5 ) { \
        DIE( "Incorrect SQLSTATE given to is_truncated_warning." ); \
    }
#endif
    return (state[0] == '0' && state[1] == '1' && state[2] == '0' && state [3] == '0' && state [4] == '4');
}

// Macros for handling errors. These macros are simplified if statements that take boilerplate
// code down to a single line to avoid distractions in the code.

#define CHECK_ERROR_EX( unique, condition, context, ssphp, ... )     \
    bool flag##unique = (condition);                                 \
    bool ignored##unique = true;                                       \
    if (flag##unique) {                                              \
        ignored##unique = call_error_handler( context, ssphp TSRMLS_CC, /*warning*/false, __VA_ARGS__ ); \
    }  \
    if( !ignored##unique )
    
#define CHECK_ERROR_UNIQUE( unique, condition, context, ssphp, ...) \
    CHECK_ERROR_EX( unique, condition, context, ssphp, __VA_ARGS__ )

#define CHECK_ERROR( condition, context, ... )  \
    CHECK_ERROR_UNIQUE( __COUNTER__, condition, context, NULL, __VA_ARGS__ )

#define CHECK_CUSTOM_ERROR( condition, context, ssphp, ... )  \
    CHECK_ERROR_UNIQUE( __COUNTER__, condition, context, ssphp, __VA_ARGS__ )

#define CHECK_SQL_ERROR( result, context, ... )  \
    SQLSRV_ASSERT( result != SQL_INVALID_HANDLE, "Invalid handle returned." ); \
    CHECK_ERROR( result == SQL_ERROR, context, __VA_ARGS__ )

#define CHECK_WARNING_AS_ERROR_UNIQUE(  unique, condition, context, ssphp, ... )   \
    bool ignored##unique = true;    \
    if( condition ) { \
        ignored##unique = call_error_handler( context, ssphp TSRMLS_CC, /*warning*/true, __VA_ARGS__ ); \
    }   \
    if( !ignored##unique ) 

#define CHECK_SQL_WARNING_AS_ERROR( result, context, ... ) \
    CHECK_WARNING_AS_ERROR_UNIQUE( __COUNTER__,( result == SQL_SUCCESS_WITH_INFO ), context, SQLSRV_ERROR_ODBC, __VA_ARGS__ )
    
#define CHECK_SQL_WARNING( result, context, ... )        \
    if( result == SQL_SUCCESS_WITH_INFO ) {              \
        (void)call_error_handler( context, NULL TSRMLS_CC, /*warning*/ true, __VA_ARGS__ ); \
    }                                                    

#define CHECK_CUSTOM_WARNING_AS_ERROR( condition, context, ssphp, ... ) \
    CHECK_WARNING_AS_ERROR_UNIQUE( __COUNTER__, condition, context, ssphp, __VA_ARGS__ )
                 
#define CHECK_ZEND_ERROR( zr, ctx, error, ... )  \
    CHECK_ERROR_UNIQUE( __COUNTER__, ( zr == FAILURE ), ctx, error, __VA_ARGS__ )  \

#define CHECK_SQL_ERROR_OR_WARNING( result, context, ... ) \
    SQLSRV_ASSERT( result != SQL_INVALID_HANDLE, "Invalid handle returned." );  \
    bool ignored = true;                                   \
    if( result == SQL_ERROR ) {                            \
        ignored = call_error_handler( context, SQLSRV_ERROR_ODBC TSRMLS_CC, false, __VA_ARGS__ ); \
    }                                                      \
    else if( result == SQL_SUCCESS_WITH_INFO ) {           \
        ignored = call_error_handler( context, SQLSRV_ERROR_ODBC TSRMLS_CC, true TSRMLS_CC, __VA_ARGS__ ); \
    }                                                      \
    if( !ignored )
  
// throw an exception after it has been hooked into the custom error handler
#define THROW_CORE_ERROR( ctx, custom, ... ) \
  (void)call_error_handler( ctx, custom TSRMLS_CC, /*warning*/ false, __VA_ARGS__ ); \
  throw core::CoreException();

//*********************************************************************************************************************************
// ODBC/Zend function wrappers
//*********************************************************************************************************************************

namespace core {

    // base exception for the driver
    struct CoreException : public std::exception {

      CoreException()
      {
      }
    };

    inline void check_for_mars_error( sqlsrv_stmt* stmt, SQLRETURN r TSRMLS_DC )
    {
        // We check for the 'connection busy' error caused by having MultipleActiveResultSets off
        // and return a more helpful message prepended to the ODBC errors if that error occurs
        if( !SQL_SUCCEEDED( r )) {

            SQLCHAR err_msg[ SQL_MAX_MESSAGE_LENGTH + 1 ];
            SQLSMALLINT len = 0;
            
            SQLRETURN r = ::SQLGetDiagField( stmt->handle_type(), stmt->handle(), 1, SQL_DIAG_MESSAGE_TEXT, 
                                             err_msg, SQL_MAX_MESSAGE_LENGTH, &len );

            CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
         
                throw CoreException();
            }

            if(( len == sizeof( CONNECTION_BUSY_ODBC_ERROR ) - 1 ) && 
               !strcmp( reinterpret_cast<const char*>( err_msg ), CONNECTION_BUSY_ODBC_ERROR )) {
             
                THROW_CORE_ERROR( stmt, SQLSRV_ERROR_MARS_OFF );
            }
        }
    }

    // *** ODBC wrappers ***

    // wrap the ODBC functions to throw exceptions rather than use the return value to signal errors
    // some of the signatures have been altered to be more convenient since the return value is no longer
    // required to return the status of the call (e.g., SQLNumResultCols).
    // These functions take the sqlsrv_context type.  However, since the error handling code can alter
    // the context to hold the error, they are not passed as const.

    inline SQLRETURN SQLGetDiagField( sqlsrv_context* ctx, SQLSMALLINT record_number, SQLSMALLINT diag_identifier, 
                                      __out SQLPOINTER diag_info_buffer, SQLSMALLINT buffer_length,
                                      __out SQLSMALLINT* out_buffer_length TSRMLS_DC )
    {
        SQLRETURN r = ::SQLGetDiagField( ctx->handle_type(), ctx->handle(), record_number, diag_identifier, 
                                       diag_info_buffer, buffer_length, out_buffer_length );

        CHECK_SQL_ERROR_OR_WARNING( r, ctx ) {
            throw CoreException();
        }

        return r;
    }

    inline void SQLAllocHandle( SQLSMALLINT HandleType, sqlsrv_context& InputHandle, 
                                __out_ecount(1) SQLHANDLE* OutputHandlePtr TSRMLS_DC )
    {
        SQLRETURN r;
        r = ::SQLAllocHandle( HandleType, InputHandle.handle(), OutputHandlePtr );
        CHECK_SQL_ERROR_OR_WARNING( r, InputHandle ) {
            throw CoreException();
        }
    }

    inline void SQLBindParameter( sqlsrv_stmt*          stmt, 
                                  SQLUSMALLINT          ParameterNumber,
                                  SQLSMALLINT           InputOutputType,
                                  SQLSMALLINT           ValueType,
                                  SQLSMALLINT           ParameterType,
                                  SQLULEN               ColumnSize,
                                  SQLSMALLINT           DecimalDigits,
                                  __inout SQLPOINTER    ParameterValuePtr,
                                  SQLLEN                BufferLength,
                                  __inout SQLLEN *      StrLen_Or_IndPtr
                                  TSRMLS_DC )
    {
        SQLRETURN r;
        r = ::SQLBindParameter( stmt->handle(), ParameterNumber, InputOutputType, ValueType, ParameterType, ColumnSize, 
                                DecimalDigits, ParameterValuePtr, BufferLength, StrLen_Or_IndPtr );
        
        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw CoreException();
        }
    }


    inline void SQLColAttribute( sqlsrv_stmt* stmt, SQLUSMALLINT field_index, SQLUSMALLINT field_identifier, 
                                      __out SQLPOINTER field_type_char, SQLSMALLINT buffer_length, 
                                      __out SQLSMALLINT* out_buffer_length, __out SQLLEN* field_type_num TSRMLS_DC )
    {
        SQLRETURN r = ::SQLColAttribute( stmt->handle(), field_index, field_identifier, field_type_char,
                                         buffer_length, out_buffer_length, field_type_num );

        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw CoreException();
        }
    }


    inline void SQLDescribeCol( sqlsrv_stmt* stmt, SQLSMALLINT colno, __out_z SQLCHAR* col_name, SQLSMALLINT col_name_length, 
                                __out SQLSMALLINT* col_name_length_out, SQLSMALLINT* data_type, __out SQLULEN* col_size, 
                                __out SQLSMALLINT* decimal_digits, __out SQLSMALLINT* nullable TSRMLS_DC )
    {
        SQLRETURN r;
        r = ::SQLDescribeCol( stmt->handle(), colno, col_name, col_name_length, col_name_length_out, 
                              data_type, col_size, decimal_digits, nullable);
         
        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw CoreException();
        }
    }


    inline void SQLEndTran( SQLSMALLINT handleType, sqlsrv_conn* conn, SQLSMALLINT completionType TSRMLS_DC )
    {
        SQLRETURN r = ::SQLEndTran( handleType, conn->handle(), completionType );
        
        CHECK_SQL_ERROR_OR_WARNING( r, conn ) {
            throw CoreException();
        }
    }

    // SQLExecDirect returns the status code since it returns either SQL_NEED_DATA or SQL_NO_DATA besides just errors/success    
    inline SQLRETURN SQLExecDirect( sqlsrv_stmt* stmt, char* sql TSRMLS_DC )
    {
        SQLRETURN r = ::SQLExecDirect( stmt->handle(), reinterpret_cast<SQLCHAR*>( sql ), SQL_NTS );
        
        check_for_mars_error( stmt, r TSRMLS_CC );

        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {

            throw CoreException();
        }
        return r;
    }

    inline SQLRETURN SQLExecDirectW( sqlsrv_stmt* stmt, wchar_t* wsql TSRMLS_DC )
    {
        SQLRETURN r;
        r = ::SQLExecDirectW( stmt->handle(), reinterpret_cast<SQLWCHAR*>( wsql ), SQL_NTS );

        check_for_mars_error( stmt, r TSRMLS_CC );

        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw CoreException();
        }
        return r;
    }

    // SQLExecute returns the status code since it returns either SQL_NEED_DATA or SQL_NO_DATA besides just errors/success
    inline SQLRETURN SQLExecute( sqlsrv_stmt* stmt TSRMLS_DC )
    {
        SQLRETURN r;
        r = ::SQLExecute( stmt->handle() );
   
        check_for_mars_error( stmt, r TSRMLS_CC );

        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw CoreException();
        }

        return r;
    }

    inline SQLRETURN SQLFetchScroll( sqlsrv_stmt* stmt, SQLSMALLINT fetch_orientation, SQLLEN fetch_offset TSRMLS_DC )
    {
        SQLRETURN r = ::SQLFetchScroll( stmt->handle(), fetch_orientation, fetch_offset );
        
        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw CoreException();
        }
        return r;
    }


    // wrap SQLFreeHandle and report any errors, but don't actually signal an error to the calling routine
    inline void SQLFreeHandle( sqlsrv_context& ctx TSRMLS_DC )
    {
        SQLRETURN r;
        r = ::SQLFreeHandle( ctx.handle_type(), ctx.handle() );
        CHECK_SQL_ERROR_OR_WARNING( r, ctx ) {}
    }

    inline SQLRETURN SQLGetData( sqlsrv_stmt* stmt, SQLUSMALLINT field_index, SQLSMALLINT target_type,
                                 __out void* buffer, SQLLEN buffer_length, __out SQLLEN* out_buffer_length,
                                 bool handle_warning TSRMLS_DC )
    {
        SQLRETURN r = ::SQLGetData( stmt->handle(), field_index, target_type, buffer, buffer_length, out_buffer_length );

        if( r == SQL_NO_DATA )
            return r;
        
        CHECK_SQL_ERROR( r, stmt ) {
            throw CoreException();
        }

        if( handle_warning ) {
            CHECK_SQL_WARNING_AS_ERROR( r, stmt ) {
                throw CoreException();
            }
        }

        return r;
    }

   
    inline void SQLGetInfo( sqlsrv_conn* conn, SQLUSMALLINT info_type, __out SQLPOINTER info_value, SQLSMALLINT buffer_len,
                     __out SQLSMALLINT* str_len TSRMLS_DC )
    {
        SQLRETURN r;
        r = ::SQLGetInfo( conn->handle(), info_type, info_value, buffer_len, str_len );
        
        CHECK_SQL_ERROR_OR_WARNING( r, conn ) {
            throw CoreException();
        }
    }


    inline void SQLGetTypeInfo( sqlsrv_stmt* stmt, SQLUSMALLINT data_type TSRMLS_DC )
    {
        SQLRETURN r;
        r = ::SQLGetTypeInfo( stmt->handle(), data_type );
        
        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw CoreException();
        }
    }


    // SQLMoreResults returns the status code since it returns SQL_NO_DATA when there is no more data in a result set.
    inline SQLRETURN SQLMoreResults( sqlsrv_stmt* stmt TSRMLS_DC )
    {
        SQLRETURN r = ::SQLMoreResults( stmt->handle() );

        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw CoreException();
        }

        return r;
    }

    inline SQLSMALLINT SQLNumResultCols( sqlsrv_stmt* stmt TSRMLS_DC )
    {
        SQLRETURN r;
        SQLSMALLINT num_cols;
        r = ::SQLNumResultCols( stmt->handle(), &num_cols );
        
        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw CoreException();
        }
        
        return num_cols;
    }

    // SQLParamData returns the status code since it returns either SQL_NEED_DATA or SQL_NO_DATA when there are more
    // parameters or when the parameters are all processed.
    inline SQLRETURN SQLParamData( sqlsrv_stmt* stmt, __out SQLPOINTER* value_ptr_ptr TSRMLS_DC )
    {
        SQLRETURN r;
        r = ::SQLParamData( stmt->handle(), value_ptr_ptr );
        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw CoreException();
        }
        return r;
    }

    inline void SQLPrepareW( sqlsrv_stmt* stmt, SQLWCHAR * sql, SQLINTEGER sql_len TSRMLS_DC )
    {
        SQLRETURN r;
        r = ::SQLPrepareW( stmt->handle(), sql, sql_len );
        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw CoreException();
        }
    }


    inline void SQLPutData( sqlsrv_stmt* stmt, SQLPOINTER data_ptr, SQLLEN strlen_or_ind TSRMLS_DC )
    {
        SQLRETURN r;
        r = ::SQLPutData( stmt->handle(), data_ptr, strlen_or_ind );
        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw CoreException();
        }
    }


    inline SQLLEN SQLRowCount( sqlsrv_stmt* stmt TSRMLS_DC )
    {
        SQLRETURN r;
        SQLLEN rows_affected;

        r = ::SQLRowCount( stmt->handle(), &rows_affected );
        
        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw CoreException();
        }

        return rows_affected;
    }


    inline void SQLSetConnectAttr( sqlsrv_context& ctx, SQLINTEGER attr, SQLPOINTER value_ptr, SQLINTEGER str_len TSRMLS_DC )
    {
        SQLRETURN r;
        r = ::SQLSetConnectAttr( ctx.handle(), attr, value_ptr, str_len );
        
        CHECK_SQL_ERROR_OR_WARNING( r, ctx ) {
            throw CoreException();
        }
    }


    inline void SQLSetEnvAttr( sqlsrv_context& ctx, SQLINTEGER attr, SQLPOINTER value_ptr, SQLINTEGER str_len TSRMLS_DC )
    {
        SQLRETURN r;
        r = ::SQLSetEnvAttr( ctx.handle(), attr, value_ptr, str_len );
        CHECK_SQL_ERROR_OR_WARNING( r, ctx ) {
            throw CoreException();
        }
    }

    inline void SQLSetConnectAttr( sqlsrv_conn* conn, SQLINTEGER attribute, SQLPOINTER value_ptr, SQLINTEGER value_len TSRMLS_DC )
    {
        SQLRETURN r = ::SQLSetConnectAttr( conn->handle(), attribute, value_ptr, value_len ); 
        
        CHECK_SQL_ERROR_OR_WARNING( r, conn ) {
            throw CoreException();
        }
    }
        
    inline void SQLSetStmtAttr( sqlsrv_stmt* stmt, SQLINTEGER attr, SQLPOINTER value_ptr, SQLINTEGER str_len TSRMLS_DC )
    {
        SQLRETURN r;
        r = ::SQLSetStmtAttr( stmt->handle(), attr, value_ptr, str_len );
        CHECK_SQL_ERROR_OR_WARNING( r, stmt ) {
            throw CoreException();
        }
    }


    // *** zend wrappers ***
    // exception thrown when a zend function wrapped here fails.

    // wrappers for the zend functions called by our driver.  These functions hook into the error reporting of our driver and throw
    // exceptions when an error occurs.  They are prefaced with sqlsrv_<zend_function_name> because many of the zend functions are
    // actually macros that call other functions, so the sqlsrv_ is necessary to differentiate them from the macro system.
    // If there is a zend function in the source that isn't found here, it is because it returns void and there is no error
    // that can be thrown from it.

    inline void sqlsrv_add_index_zval( sqlsrv_context& ctx, zval* array, unsigned int index, zval* value TSRMLS_DC) 
    {
        int zr = ::add_index_zval( array, index, value );
        CHECK_ZEND_ERROR( zr, ctx, SQLSRV_ERROR_ZEND_HASH ) {
            throw CoreException();
        }
    }

    inline void sqlsrv_add_next_index_zval( sqlsrv_context& ctx, zval* array, zval* value TSRMLS_DC) 
    {
        int zr = ::add_next_index_zval( array, value );
        CHECK_ZEND_ERROR( zr, ctx, SQLSRV_ERROR_ZEND_HASH ) {
            throw CoreException();
        }
    }

    inline void sqlsrv_add_assoc_null( sqlsrv_context& ctx, zval* array_z, char* key TSRMLS_DC )
    {
        int zr = ::add_assoc_null( array_z, key );
        CHECK_ZEND_ERROR (zr, ctx, SQLSRV_ERROR_ZEND_HASH ) {
            throw CoreException();
        }
    }

    inline void sqlsrv_add_assoc_long( sqlsrv_context& ctx, zval* array_z, char* key, long val TSRMLS_DC )
    {
        int zr = ::add_assoc_long( array_z, key, val );
        CHECK_ZEND_ERROR (zr, ctx, SQLSRV_ERROR_ZEND_HASH ) {
            throw CoreException();
        }
    }

    inline void sqlsrv_add_assoc_string( sqlsrv_context& ctx, zval* array_z, char* key, char* val, bool duplicate TSRMLS_DC )
    {
        int zr = ::add_assoc_string( array_z, key, val, duplicate );
        CHECK_ZEND_ERROR (zr, ctx, SQLSRV_ERROR_ZEND_HASH ) {
            throw CoreException();
        }
    }

    inline void sqlsrv_array_init( sqlsrv_context& ctx, __out zval* new_array TSRMLS_DC) 
    {
        int zr = ::array_init( new_array );
        CHECK_ZEND_ERROR( zr, ctx, SQLSRV_ERROR_ZEND_HASH ) {
            throw CoreException();
        }
    }

    inline void sqlsrv_php_stream_from_zval_no_verify( sqlsrv_context& ctx, php_stream*& stream, zval** stream_z TSRMLS_DC )
    {
        // this duplicates the macro php_stream_from_zval_no_verify, which we can't use because it has an assignment
        php_stream_from_zval_no_verify( stream, stream_z );
        CHECK_CUSTOM_ERROR( stream == NULL, ctx, SQLSRV_ERROR_ZEND_STREAM ) {
            throw CoreException();
        }
    }

    inline void sqlsrv_zend_hash_get_current_data( sqlsrv_context& ctx, HashTable* ht, __out void** output_data TSRMLS_DC )
    {
        int zr = ::zend_hash_get_current_data( ht, output_data );
        CHECK_ZEND_ERROR( zr, ctx, SQLSRV_ERROR_ZEND_HASH ) {
            throw CoreException();
        }        
    }

    inline void sqlsrv_zend_hash_index_del( sqlsrv_context& ctx, HashTable* ht, int index TSRMLS_DC )
    {
        int zr = ::zend_hash_index_del( ht, index );
        CHECK_ZEND_ERROR( zr, ctx, SQLSRV_ERROR_ZEND_HASH ) {
            throw CoreException();
        }
    }

    inline void sqlsrv_zend_hash_index_update( sqlsrv_context& ctx, HashTable* ht, unsigned long index, void* data, 
                                               uint data_size TSRMLS_DC )
    {
        int zr = ::zend_hash_index_update( ht, index, data, data_size, NULL );
        CHECK_ZEND_ERROR( zr, ctx, SQLSRV_ERROR_ZEND_HASH ) {
            throw CoreException();
        }
    }

    inline void sqlsrv_zend_hash_next_index_insert( sqlsrv_context& ctx, HashTable* ht, void* data, 
                                                    uint data_size TSRMLS_DC )
    {
        int zr = ::zend_hash_next_index_insert( ht, data, data_size, NULL );
        CHECK_ZEND_ERROR( zr, ctx, SQLSRV_ERROR_ZEND_HASH ) {
            throw CoreException();
        }
    }

    inline void sqlsrv_zend_hash_init( sqlsrv_context& ctx, HashTable* ht, unsigned int initial_size, hash_func_t hash_fn,
                                       dtor_func_t dtor_fn, zend_bool persistent TSRMLS_DC )
    {
        int zr = ::zend_hash_init( ht, initial_size, hash_fn, dtor_fn, persistent );
        CHECK_ZEND_ERROR( zr, ctx, SQLSRV_ERROR_ZEND_HASH ) {
            throw CoreException();
        }
    }

    inline void sqlsrv_zend_hash_add( sqlsrv_context& ctx, HashTable* ht, char* key, unsigned int key_len, void** data, 
                                      unsigned int data_size, void **pDest TSRMLS_DC )
    {
        int zr = ::zend_hash_add( ht, key, key_len, data, data_size, pDest );
        CHECK_ZEND_ERROR( zr, ctx, SQLSRV_ERROR_ZEND_HASH ) {
            throw CoreException();
        }
    }

template <typename Statement>
sqlsrv_stmt* allocate_stmt( sqlsrv_conn* conn, SQLHANDLE h, error_callback e, void* driver TSRMLS_DC )
{
    return new ( sqlsrv_malloc( sizeof( Statement ))) Statement( conn, h, e, driver TSRMLS_CC );
}

template <typename Connection>
sqlsrv_conn* allocate_conn( SQLHANDLE h, error_callback e, void* driver TSRMLS_DC )
{
    return new ( sqlsrv_malloc( sizeof( Connection ))) Connection( h, e, driver TSRMLS_CC );
}

} // namespace core

#endif  // CORE_SQLSRV_H

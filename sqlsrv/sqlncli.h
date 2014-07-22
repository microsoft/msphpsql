

/* this ALWAYS GENERATED file contains the definitions for the interfaces */


 /* File created by MIDL compiler version 7.00.0474 */
/* Compiler settings for sqlncli.idl:
    Oicf, W1, Zp8, env=Win32 (32b run)
    protocol : dce , ms_ext, c_ext, robust
    error checks: allocation ref bounds_check enum stub_data 
    VC __declspec() decoration level: 
         __declspec(uuid()), __declspec(selectany), __declspec(novtable)
         DECLSPEC_UUID(), MIDL_INTERFACE()
*/
//@@MIDL_FILE_HEADING(  )

#pragma warning( disable: 4049 )  /* more than 64k source lines */


/* verify that the <rpcndr.h> version is high enough to compile this file*/
#ifndef __REQUIRED_RPCNDR_H_VERSION__
#define __REQUIRED_RPCNDR_H_VERSION__ 475
#endif

#include "rpc.h"
#include "rpcndr.h"

#ifndef __RPCNDR_H_VERSION__
#error this stub requires an updated version of <rpcndr.h>
#endif // __RPCNDR_H_VERSION__

#ifndef COM_NO_WINDOWS_H
#include "windows.h"
#include "ole2.h"
#endif /*COM_NO_WINDOWS_H*/

#ifndef __sqlncli_h__
#define __sqlncli_h__

#if defined(_MSC_VER) && (_MSC_VER >= 1020)
#pragma once
#endif

/* Forward Declarations */ 

#ifndef __ICommandWithParameters_FWD_DEFINED__
#define __ICommandWithParameters_FWD_DEFINED__
typedef interface ICommandWithParameters ICommandWithParameters;
#endif 	/* __ICommandWithParameters_FWD_DEFINED__ */


#ifndef __IUMSInitialize_FWD_DEFINED__
#define __IUMSInitialize_FWD_DEFINED__
typedef interface IUMSInitialize IUMSInitialize;
#endif 	/* __IUMSInitialize_FWD_DEFINED__ */


#ifndef __ISQLServerErrorInfo_FWD_DEFINED__
#define __ISQLServerErrorInfo_FWD_DEFINED__
typedef interface ISQLServerErrorInfo ISQLServerErrorInfo;
#endif 	/* __ISQLServerErrorInfo_FWD_DEFINED__ */


#ifndef __IRowsetFastLoad_FWD_DEFINED__
#define __IRowsetFastLoad_FWD_DEFINED__
typedef interface IRowsetFastLoad IRowsetFastLoad;
#endif 	/* __IRowsetFastLoad_FWD_DEFINED__ */


#ifndef __ISchemaLock_FWD_DEFINED__
#define __ISchemaLock_FWD_DEFINED__
typedef interface ISchemaLock ISchemaLock;
#endif 	/* __ISchemaLock_FWD_DEFINED__ */


#ifndef __IBCPSession_FWD_DEFINED__
#define __IBCPSession_FWD_DEFINED__
typedef interface IBCPSession IBCPSession;
#endif 	/* __IBCPSession_FWD_DEFINED__ */


#ifndef __ISSAbort_FWD_DEFINED__
#define __ISSAbort_FWD_DEFINED__
typedef interface ISSAbort ISSAbort;
#endif 	/* __ISSAbort_FWD_DEFINED__ */


#ifndef __ISSCommandWithParameters_FWD_DEFINED__
#define __ISSCommandWithParameters_FWD_DEFINED__
typedef interface ISSCommandWithParameters ISSCommandWithParameters;
#endif 	/* __ISSCommandWithParameters_FWD_DEFINED__ */


#ifndef __IDBAsynchStatus_FWD_DEFINED__
#define __IDBAsynchStatus_FWD_DEFINED__
typedef interface IDBAsynchStatus IDBAsynchStatus;
#endif 	/* __IDBAsynchStatus_FWD_DEFINED__ */


#ifndef __ISSAsynchStatus_FWD_DEFINED__
#define __ISSAsynchStatus_FWD_DEFINED__
typedef interface ISSAsynchStatus ISSAsynchStatus;
#endif 	/* __ISSAsynchStatus_FWD_DEFINED__ */


/* header files for imported files */
#include "unknwn.h"
#include "oaidl.h"

#ifdef __cplusplus
extern "C"{
#endif 


/* interface __MIDL_itf_sqlncli_0000_0000 */
/* [local] */ 

//-----------------------------------------------------------------------------
// File:        sqlncli.h
//
// Copyright:   Copyright (c) Microsoft Corporation
//
// Contents:    SQL Server Native Client OLEDB provider and ODBC driver specific
//              definitions.
//
//-----------------------------------------------------------------------------

#if !defined(SQLNCLI_VER)
#define SQLNCLI_VER 1000
#endif

#if SQLNCLI_VER >= 1000

#define SQLNCLI_PRODUCT_NAME_FULL_VER_ANSI      "Microsoft SQL Server Native Client 10.0"
#define SQLNCLI_PRODUCT_NAME_FULL_ANSI          "Microsoft SQL Server Native Client"
#define SQLNCLI_PRODUCT_NAME_SHORT_VER_ANSI     "SQL Server Native Client 10.0"
#define SQLNCLI_PRODUCT_NAME_SHORT_ANSI         "SQL Server Native Client"

#define SQLNCLI_FILE_NAME_ANSI                  "sqlncli"
#define SQLNCLI_FILE_NAME_VER_ANSI              "sqlncli10"
#define SQLNCLI_FILE_NAME_FULL_ANSI             "sqlncli10.dll"

#define SQLNCLI_PRODUCT_NAME_FULL_VER_UNICODE   L"Microsoft SQL Server Native Client 10.0"
#define SQLNCLI_PRODUCT_NAME_FULL_UNICODE       L"Microsoft SQL Server Native Client"
#define SQLNCLI_PRODUCT_NAME_SHORT_VER_UNICODE  L"SQL Server Native Client 10.0"
#define SQLNCLI_PRODUCT_NAME_SHORT_UNICODE      L"SQL Server Native Client"

#define SQLNCLI_FILE_NAME_UNICODE               L"sqlncli"
#define SQLNCLI_FILE_NAME_VER_UNICODE           L"sqlncli10"
#define SQLNCLI_FILE_NAME_FULL_UNICODE          L"sqlncli10.dll"

#if defined(_SQLNCLI_OLEDB_) || !defined(_SQLNCLI_ODBC_)

#define SQLNCLI_VI_PROG_ID_ANSI                 "SQLNCLI10"
#define SQLNCLI_VI_ERROR_LOOKUP_PROG_ID_ANSI    "SQLNCLI10 ErrorLookup"
#define SQLNCLI_VI_ENUMERATOR_PROG_ID_ANSI      "SQLNCLI10 Enumerator"

#define SQLNCLI_PROG_ID_ANSI                    "SQLNCLI10.1"
#define SQLNCLI_ERROR_LOOKUP_PROG_ID_ANSI       "SQLNCLI10 ErrorLookup.1"
#define SQLNCLI_ENUMERATOR_PROG_ID_ANSI         "SQLNCLI10 Enumerator.1"

#define SQLNCLI_VI_PROG_ID_UNICODE              L"SQLNCLI10"
#define SQLNCLI_VI_ERROR_LOOKUP_PROG_ID_UNICODE L"SQLNCLI10 ErrorLookup"
#define SQLNCLI_VI_ENUMERATOR_PROG_ID_UNICODE   L"SQLNCLI10 Enumerator"

#define SQLNCLI_PROG_ID_UNICODE                 L"SQLNCLI10.1"
#define SQLNCLI_ERROR_LOOKUP_PROG_ID_UNICODE    L"SQLNCLI10 ErrorLookup.1"
#define SQLNCLI_ENUMERATOR_PROG_ID_UNICODE      L"SQLNCLI10 Enumerator.1"

#define SQLNCLI_CLSID                           CLSID_SQLNCLI10
#define SQLNCLI_ERROR_CLSID                     CLSID_SQLNCLI10_ERROR
#define SQLNCLI_ENUMERATOR_CLSID                CLSID_SQLNCLI10_ENUMERATOR

#endif // defined(_SQLNCLI_OLEDB_) || !defined(_SQLNCLI_ODBC_)

#else  // SQLNCLI_VER >= 1000

#define SQLNCLI_PRODUCT_NAME_FULL_VER_ANSI      "Microsoft SQL Server Native Client"
#define SQLNCLI_PRODUCT_NAME_FULL_ANSI          "Microsoft SQL Server Native Client"
#define SQLNCLI_PRODUCT_NAME_SHORT_VER_ANSI     "SQL Native Client"
#define SQLNCLI_PRODUCT_NAME_SHORT_ANSI         "SQL Native Client"

#define SQLNCLI_FILE_NAME_ANSI                  "sqlncli"
#define SQLNCLI_FILE_NAME_VER_ANSI              "sqlncli"
#define SQLNCLI_FILE_NAME_FULL_ANSI             "sqlncli.dll"

#define SQLNCLI_PRODUCT_NAME_FULL_VER_UNICODE   L"Microsoft SQL Server Native Client"
#define SQLNCLI_PRODUCT_NAME_FULL_UNICODE       L"Microsoft SQL Server Native Client"
#define SQLNCLI_PRODUCT_NAME_SHORT_VER_UNICODE  L"SQL Native Client"
#define SQLNCLI_PRODUCT_NAME_SHORT_UNICODE      L"SQL Native Client"

#define SQLNCLI_FILE_NAME_UNICODE               L"sqlncli"
#define SQLNCLI_FILE_NAME_VER_UNICODE           L"sqlncli"
#define SQLNCLI_FILE_NAME_FULL_UNICODE          L"sqlncli.dll"

#if defined(_SQLNCLI_OLEDB_) || !defined(_SQLNCLI_ODBC_)

#define SQLNCLI_VI_PROG_ID_ANSI                 "SQLNCLI"
#define SQLNCLI_VI_ERROR_LOOKUP_PROG_ID_ANSI    "SQLNCLI ErrorLookup"
#define SQLNCLI_VI_ENUMERATOR_PROG_ID_ANSI      "SQLNCLI Enumerator"

#define SQLNCLI_PROG_ID_ANSI                    "SQLNCLI.1"
#define SQLNCLI_ERROR_LOOKUP_PROG_ID_ANSI       "SQLNCLI ErrorLookup.1"
#define SQLNCLI_ENUMERATOR_PROG_ID_ANSI         "SQLNCLI Enumerator.1"

#define SQLNCLI_VI_PROG_ID_UNICODE              L"SQLNCLI"
#define SQLNCLI_VI_ERROR_LOOKUP_PROG_ID_UNICODE L"SQLNCLI ErrorLookup"
#define SQLNCLI_VI_ENUMERATOR_PROG_ID_UNICODE   L"SQLNCLI Enumerator"

#define SQLNCLI_PROG_ID_UNICODE                 L"SQLNCLI.1"
#define SQLNCLI_ERROR_LOOKUP_PROG_ID_UNICODE    L"SQLNCLI ErrorLookup.1"
#define SQLNCLI_ENUMERATOR_PROG_ID_UNICODE      L"SQLNCLI Enumerator.1"

#define SQLNCLI_CLSID                           CLSID_SQLNCLI
#define SQLNCLI_ERROR_CLSID                     CLSID_SQLNCLI_ERROR
#define SQLNCLI_ENUMERATOR_CLSID                CLSID_SQLNCLI_ENUMERATOR

#endif  // defined(_SQLNCLI_OLEDB_) || !defined(_SQLNCLI_ODBC_)

#endif  // SQLNCLI_VER >= 1000

// define the character type agnostic constants
#if defined(_UNICODE) || defined(UNICODE)

#define SQLNCLI_PRODUCT_NAME_FULL_VER           SQLNCLI_PRODUCT_NAME_FULL_VER_UNICODE
#define SQLNCLI_PRODUCT_NAME_FULL               SQLNCLI_PRODUCT_NAME_FULL_UNICODE
#define SQLNCLI_PRODUCT_NAME_SHORT_VER          SQLNCLI_PRODUCT_NAME_SHORT_VER_UNICODE
#define SQLNCLI_PRODUCT_NAME_SHORT              SQLNCLI_PRODUCT_NAME_SHORT_UNICODE

#define SQLNCLI_FILE_NAME                       SQLNCLI_FILE_NAME_UNICODE
#define SQLNCLI_FILE_NAME_VER                   SQLNCLI_FILE_NAME_VER_UNICODE
#define SQLNCLI_FILE_NAME_FULL                  SQLNCLI_FILE_NAME_FULL_UNICODE

#if defined(_SQLNCLI_OLEDB_) || !defined(_SQLNCLI_ODBC_)

#define SQLNCLI_VI_PROG_ID                      SQLNCLI_VI_PROG_ID_UNICODE
#define SQLNCLI_VI_ERROR_LOOKUP_PROG_ID         SQLNCLI_VI_ERROR_LOOKUP_PROG_ID_UNICODE
#define SQLNCLI_VI_ENUMERATOR_PROG_ID           SQLNCLI_VI_ENUMERATOR_PROG_ID_UNICODE

#define SQLNCLI_PROG_ID                         SQLNCLI_PROG_ID_UNICODE
#define SQLNCLI_ERROR_LOOKUP_PROG_ID            SQLNCLI_ERROR_LOOKUP_PROG_ID_UNICODE
#define SQLNCLI_ENUMERATOR_PROG_ID              SQLNCLI_ENUMERATOR_PROG_ID_UNICODE

#endif  // defined(_SQLNCLI_OLEDB_) || !defined(_SQLNCLI_ODBC_)

#else   // _UNICODE || UNICODE

#define SQLNCLI_PRODUCT_NAME_FULL_VER           SQLNCLI_PRODUCT_NAME_FULL_VER_ANSI
#define SQLNCLI_PRODUCT_NAME_FULL               SQLNCLI_PRODUCT_NAME_FULL_ANSI
#define SQLNCLI_PRODUCT_NAME_SHORT_VER          SQLNCLI_PRODUCT_NAME_SHORT_VER_ANSI
#define SQLNCLI_PRODUCT_NAME_SHORT              SQLNCLI_PRODUCT_NAME_SHORT_ANSI

#define SQLNCLI_FILE_NAME                       SQLNCLI_FILE_NAME_ANSI
#define SQLNCLI_FILE_NAME_VER                   SQLNCLI_FILE_NAME_VER_ANSI
#define SQLNCLI_FILE_NAME_FULL                  SQLNCLI_FILE_NAME_FULL_ANSI

#if defined(_SQLNCLI_OLEDB_) || !defined(_SQLNCLI_ODBC_)

#define SQLNCLI_VI_PROG_ID                      SQLNCLI_VI_PROG_ID_ANSI
#define SQLNCLI_VI_ERROR_LOOKUP_PROG_ID         SQLNCLI_VI_ERROR_LOOKUP_PROG_ID_ANSI
#define SQLNCLI_VI_ENUMERATOR_PROG_ID           SQLNCLI_VI_ENUMERATOR_PROG_ID_ANSI

#define SQLNCLI_PROG_ID                         SQLNCLI_PROG_ID_ANSI
#define SQLNCLI_ERROR_LOOKUP_PROG_ID            SQLNCLI_ERROR_LOOKUP_PROG_ID_ANSI
#define SQLNCLI_ENUMERATOR_PROG_ID              SQLNCLI_ENUMERATOR_PROG_ID_ANSI

#endif  // defined(_SQLNCLI_OLEDB_) || !defined(_SQLNCLI_ODBC_)

#endif  // _UNICODE || UNICODE

#if defined(_SQLNCLI_ODBC_) || !defined(_SQLNCLI_OLEDB_)

#define SQLNCLI_DRIVER_NAME                     SQLNCLI_PRODUCT_NAME_SHORT_VER

#endif

// OLEDB part of SQL Server Native Client header - begin here
#if defined(_SQLNCLI_OLEDB_) || !defined(_SQLNCLI_ODBC_)
#ifndef  __oledb_h__
#include <oledb.h>
#endif  /*__oledb_h__*/

#if 0        // This is already defined in oledb.h

#ifdef _WIN64

// Length of a non-character object, size
typedef ULONGLONG			DBLENGTH;

// Offset within a rowset
typedef LONGLONG				DBROWOFFSET;

// Number of rows
typedef LONGLONG				DBROWCOUNT;

typedef ULONGLONG			DBCOUNTITEM;

// Ordinal (column number, etc.)
typedef ULONGLONG			DBORDINAL;

typedef LONGLONG				DB_LORDINAL;

// Bookmarks
typedef ULONGLONG			DBBKMARK;
// Offset in the buffer

typedef ULONGLONG			DBBYTEOFFSET;
// Reference count of each row/accessor  handle

typedef ULONG				DBREFCOUNT;

// Parameters
typedef ULONGLONG			DB_UPARAMS;

typedef LONGLONG				DB_LPARAMS;

// hash values corresponding to the elements (bookmarks)
typedef DWORDLONG			DBHASHVALUE;

// For reserve
typedef DWORDLONG			DB_DWRESERVE;

typedef LONGLONG				DB_LRESERVE;

typedef ULONGLONG			DB_URESERVE;

#else //_WIN64

// Length of a non-character object, size
typedef ULONG DBLENGTH;

// Offset within a rowset
typedef LONG DBROWOFFSET;

// Number of rows
typedef LONG DBROWCOUNT;

typedef ULONG DBCOUNTITEM;

// Ordinal (column number, etc.)
typedef ULONG DBORDINAL;

typedef LONG DB_LORDINAL;

// Bookmarks
typedef ULONG DBBKMARK;

// Offset in the buffer
typedef ULONG DBBYTEOFFSET;

// Reference count of each row handle
typedef ULONG DBREFCOUNT;

// Parameters
typedef ULONG DB_UPARAMS;

typedef LONG DB_LPARAMS;

// hash values corresponding to the elements (bookmarks)
typedef DWORD DBHASHVALUE;

// For reserve
typedef DWORD DB_DWRESERVE;

typedef LONG DB_LRESERVE;

typedef ULONG DB_URESERVE;

#endif	// _WIN64
typedef DWORD DBKIND;


enum DBKINDENUM
    {	DBKIND_GUID_NAME	= 0,
	DBKIND_GUID_PROPID	= ( DBKIND_GUID_NAME + 1 ) ,
	DBKIND_NAME	= ( DBKIND_GUID_PROPID + 1 ) ,
	DBKIND_PGUID_NAME	= ( DBKIND_NAME + 1 ) ,
	DBKIND_PGUID_PROPID	= ( DBKIND_PGUID_NAME + 1 ) ,
	DBKIND_PROPID	= ( DBKIND_PGUID_PROPID + 1 ) ,
	DBKIND_GUID	= ( DBKIND_PROPID + 1 ) 
    } ;
typedef struct tagDBID
    {
    union 
        {
        GUID guid;
        GUID *pguid;
         /* Empty union arm */ 
        } 	uGuid;
    DBKIND eKind;
    union 
        {
        LPOLESTR pwszName;
        ULONG ulPropid;
         /* Empty union arm */ 
        } 	uName;
    } 	DBID;

typedef struct tagDB_NUMERIC
    {
    BYTE precision;
    BYTE scale;
    BYTE sign;
    BYTE val[ 16 ];
    } 	DB_NUMERIC;

typedef struct tagDBDATE
    {
    SHORT year;
    USHORT month;
    USHORT day;
    } 	DBDATE;

typedef struct tagDBTIME
    {
    USHORT hour;
    USHORT minute;
    USHORT second;
    } 	DBTIME;

typedef struct tagDBTIMESTAMP
    {
    SHORT year;
    USHORT month;
    USHORT day;
    USHORT hour;
    USHORT minute;
    USHORT second;
    ULONG fraction;
    } 	DBTIMESTAMP;

typedef struct tagDBOBJECT
    {
    DWORD dwFlags;
    IID iid;
    } 	DBOBJECT;

typedef WORD DBTYPE;

typedef ULONG_PTR HACCESSOR;

typedef ULONG_PTR HCHAPTER;

typedef DWORD DBPARAMFLAGS;

typedef struct tagDBPARAMINFO
    {
    DBPARAMFLAGS dwFlags;
    DBORDINAL iOrdinal;
    LPOLESTR pwszName;
    ITypeInfo *pTypeInfo;
    DBLENGTH ulParamSize;
    DBTYPE wType;
    BYTE bPrecision;
    BYTE bScale;
    } 	DBPARAMINFO;

typedef DWORD DBPROPID;

typedef struct tagDBPROPIDSET
    {
    DBPROPID *rgPropertyIDs;
    ULONG cPropertyIDs;
    GUID guidPropertySet;
    } 	DBPROPIDSET;

typedef DWORD DBPROPFLAGS;

typedef DWORD DBPROPOPTIONS;

typedef DWORD DBPROPSTATUS;

typedef struct tagDBPROP
    {
    DBPROPID dwPropertyID;
    DBPROPOPTIONS dwOptions;
    DBPROPSTATUS dwStatus;
    DBID colid;
    VARIANT vValue;
    } 	DBPROP;

typedef struct tagDBPROPSET
    {
    DBPROP *rgProperties;
    ULONG cProperties;
    GUID guidPropertySet;
    } 	DBPROPSET;



extern RPC_IF_HANDLE __MIDL_itf_sqlncli_0000_0000_v0_0_c_ifspec;
extern RPC_IF_HANDLE __MIDL_itf_sqlncli_0000_0000_v0_0_s_ifspec;

#ifndef __ICommandWithParameters_INTERFACE_DEFINED__
#define __ICommandWithParameters_INTERFACE_DEFINED__

/* interface ICommandWithParameters */
/* [unique][uuid][object][local] */ 

typedef struct tagDBPARAMBINDINFO
    {
    LPOLESTR pwszDataSourceType;
    LPOLESTR pwszName;
    DBLENGTH ulParamSize;
    DBPARAMFLAGS dwFlags;
    BYTE bPrecision;
    BYTE bScale;
    } 	DBPARAMBINDINFO;


EXTERN_C const IID IID_ICommandWithParameters;

#if defined(__cplusplus) && !defined(CINTERFACE)
    
    MIDL_INTERFACE("0c733a64-2a1c-11ce-ade5-00aa0044773d")
    ICommandWithParameters : public IUnknown
    {
    public:
        virtual /* [local] */ HRESULT STDMETHODCALLTYPE GetParameterInfo( 
            /* [out][in] */ DB_UPARAMS *pcParams,
            /* [size_is][size_is][out] */ DBPARAMINFO **prgParamInfo,
            /* [out] */ OLECHAR **ppNamesBuffer) = 0;
        
        virtual /* [local] */ HRESULT STDMETHODCALLTYPE MapParameterNames( 
            /* [in] */ DB_UPARAMS cParamNames,
            /* [size_is][in] */ const OLECHAR *rgParamNames[  ],
            /* [size_is][out] */ DB_LPARAMS rgParamOrdinals[  ]) = 0;
        
        virtual /* [local] */ HRESULT STDMETHODCALLTYPE SetParameterInfo( 
            /* [in] */ DB_UPARAMS cParams,
            /* [size_is][unique][in] */ const DB_UPARAMS rgParamOrdinals[  ],
            /* [size_is][unique][in] */ const DBPARAMBINDINFO rgParamBindInfo[  ]) = 0;
        
    };
    
#else 	/* C style interface */

    typedef struct ICommandWithParametersVtbl
    {
        BEGIN_INTERFACE
        
        HRESULT ( STDMETHODCALLTYPE *QueryInterface )( 
            ICommandWithParameters * This,
            /* [in] */ REFIID riid,
            /* [iid_is][out] */ 
            __RPC__deref_out  void **ppvObject);
        
        ULONG ( STDMETHODCALLTYPE *AddRef )( 
            ICommandWithParameters * This);
        
        ULONG ( STDMETHODCALLTYPE *Release )( 
            ICommandWithParameters * This);
        
        /* [local] */ HRESULT ( STDMETHODCALLTYPE *GetParameterInfo )( 
            ICommandWithParameters * This,
            /* [out][in] */ DB_UPARAMS *pcParams,
            /* [size_is][size_is][out] */ DBPARAMINFO **prgParamInfo,
            /* [out] */ OLECHAR **ppNamesBuffer);
        
        /* [local] */ HRESULT ( STDMETHODCALLTYPE *MapParameterNames )( 
            ICommandWithParameters * This,
            /* [in] */ DB_UPARAMS cParamNames,
            /* [size_is][in] */ const OLECHAR *rgParamNames[  ],
            /* [size_is][out] */ DB_LPARAMS rgParamOrdinals[  ]);
        
        /* [local] */ HRESULT ( STDMETHODCALLTYPE *SetParameterInfo )( 
            ICommandWithParameters * This,
            /* [in] */ DB_UPARAMS cParams,
            /* [size_is][unique][in] */ const DB_UPARAMS rgParamOrdinals[  ],
            /* [size_is][unique][in] */ const DBPARAMBINDINFO rgParamBindInfo[  ]);
        
        END_INTERFACE
    } ICommandWithParametersVtbl;

    interface ICommandWithParameters
    {
        CONST_VTBL struct ICommandWithParametersVtbl *lpVtbl;
    };

    

#ifdef COBJMACROS


#define ICommandWithParameters_QueryInterface(This,riid,ppvObject)	\
    ( (This)->lpVtbl -> QueryInterface(This,riid,ppvObject) ) 

#define ICommandWithParameters_AddRef(This)	\
    ( (This)->lpVtbl -> AddRef(This) ) 

#define ICommandWithParameters_Release(This)	\
    ( (This)->lpVtbl -> Release(This) ) 


#define ICommandWithParameters_GetParameterInfo(This,pcParams,prgParamInfo,ppNamesBuffer)	\
    ( (This)->lpVtbl -> GetParameterInfo(This,pcParams,prgParamInfo,ppNamesBuffer) ) 

#define ICommandWithParameters_MapParameterNames(This,cParamNames,rgParamNames,rgParamOrdinals)	\
    ( (This)->lpVtbl -> MapParameterNames(This,cParamNames,rgParamNames,rgParamOrdinals) ) 

#define ICommandWithParameters_SetParameterInfo(This,cParams,rgParamOrdinals,rgParamBindInfo)	\
    ( (This)->lpVtbl -> SetParameterInfo(This,cParams,rgParamOrdinals,rgParamBindInfo) ) 

#endif /* COBJMACROS */


#endif 	/* C style interface */



/* [call_as] */ HRESULT STDMETHODCALLTYPE ICommandWithParameters_RemoteGetParameterInfo_Proxy( 
    ICommandWithParameters * This,
    /* [out][in] */ DB_UPARAMS *pcParams,
    /* [size_is][size_is][out] */ DBPARAMINFO **prgParamInfo,
    /* [size_is][size_is][out] */ DBBYTEOFFSET **prgNameOffsets,
    /* [out][in] */ DBLENGTH *pcbNamesBuffer,
    /* [size_is][size_is][unique][out][in] */ OLECHAR **ppNamesBuffer,
    /* [out] */ IErrorInfo **ppErrorInfoRem);


void __RPC_STUB ICommandWithParameters_RemoteGetParameterInfo_Stub(
    IRpcStubBuffer *This,
    IRpcChannelBuffer *_pRpcChannelBuffer,
    PRPC_MESSAGE _pRpcMessage,
    DWORD *_pdwStubPhase);


/* [call_as] */ HRESULT STDMETHODCALLTYPE ICommandWithParameters_RemoteMapParameterNames_Proxy( 
    ICommandWithParameters * This,
    /* [in] */ DB_UPARAMS cParamNames,
    /* [size_is][in] */ LPCOLESTR *rgParamNames,
    /* [size_is][out] */ DB_LPARAMS *rgParamOrdinals,
    /* [out] */ IErrorInfo **ppErrorInfoRem);


void __RPC_STUB ICommandWithParameters_RemoteMapParameterNames_Stub(
    IRpcStubBuffer *This,
    IRpcChannelBuffer *_pRpcChannelBuffer,
    PRPC_MESSAGE _pRpcMessage,
    DWORD *_pdwStubPhase);


/* [call_as] */ HRESULT STDMETHODCALLTYPE ICommandWithParameters_RemoteSetParameterInfo_Proxy( 
    ICommandWithParameters * This,
    /* [in] */ DB_UPARAMS cParams,
    /* [size_is][unique][in] */ const DB_UPARAMS *rgParamOrdinals,
    /* [size_is][unique][in] */ const DBPARAMBINDINFO *rgParamBindInfo,
    /* [out] */ IErrorInfo **ppErrorInfoRem);


void __RPC_STUB ICommandWithParameters_RemoteSetParameterInfo_Stub(
    IRpcStubBuffer *This,
    IRpcChannelBuffer *_pRpcChannelBuffer,
    PRPC_MESSAGE _pRpcMessage,
    DWORD *_pdwStubPhase);



#endif 	/* __ICommandWithParameters_INTERFACE_DEFINED__ */


/* interface __MIDL_itf_sqlncli_0000_0001 */
/* [local] */ 

typedef DWORD DBASYNCHOP;

typedef DWORD DBASYNCHPHASE;

#endif       // This is already defined in oledb.h

//-------------------------------------------------------------------
// Variant Access macros, similar to ole automation.
//-------------------------------------------------------------------
#define V_SS_VT(X)               ((X)->vt)
#define V_SS_UNION(X, Y)         ((X)->Y)

#define V_SS_UI1(X)              V_SS_UNION(X, bTinyIntVal)
#define V_SS_I2(X)               V_SS_UNION(X, sShortIntVal)
#define V_SS_I4(X)               V_SS_UNION(X, lIntVal)
#define V_SS_I8(X)               V_SS_UNION(X, llBigIntVal)

#define V_SS_R4(X)               V_SS_UNION(X, fltRealVal)
#define V_SS_R8(X)               V_SS_UNION(X, dblFloatVal)
#define V_SS_UI4(X)              V_SS_UNION(X, ulVal)

#define V_SS_MONEY(X)            V_SS_UNION(X, cyMoneyVal)
#define V_SS_SMALLMONEY(X)       V_SS_UNION(X, cyMoneyVal)

#define V_SS_WSTRING(X)          V_SS_UNION(X, NCharVal)
#define V_SS_WVARSTRING(X)       V_SS_UNION(X, NCharVal)

#define V_SS_STRING(X)           V_SS_UNION(X, CharVal)
#define V_SS_VARSTRING(X)        V_SS_UNION(X, CharVal)

#define V_SS_BIT(X)              V_SS_UNION(X, fBitVal)
#define V_SS_GUID(X)             V_SS_UNION(X, rgbGuidVal)

#define V_SS_NUMERIC(X)          V_SS_UNION(X, numNumericVal)
#define V_SS_DECIMAL(X)          V_SS_UNION(X, numNumericVal)

#define V_SS_BINARY(X)           V_SS_UNION(X, BinaryVal)
#define V_SS_VARBINARY(X)        V_SS_UNION(X, BinaryVal)

#define V_SS_DATETIME(X)         V_SS_UNION(X, tsDateTimeVal)
#define V_SS_SMALLDATETIME(X)    V_SS_UNION(X, tsDateTimeVal)

#define V_SS_UNKNOWN(X)          V_SS_UNION(X, UnknownType)

//Text and image types.
#define V_SS_IMAGE(X)            V_SS_UNION(X, ImageVal)
#define V_SS_TEXT(X)             V_SS_UNION(X, TextVal)
#define V_SS_NTEXT(X)            V_SS_UNION(X, NTextVal)

//Microsoft SQL Server 2008 datetime.
#define V_SS_DATE(X)             V_SS_UNION(X, dDateVal)
#define V_SS_TIME2(X)            V_SS_UNION(X, Time2Val)
#define V_SS_DATETIME2(X)        V_SS_UNION(X, DateTimeVal)
#define V_SS_DATETIMEOFFSET(X)   V_SS_UNION(X, DateTimeOffsetVal)

//-------------------------------------------------------------------
// define SQL Server specific types.
//-------------------------------------------------------------------
typedef enum DBTYPEENUM EOledbTypes;
#define DBTYPE_XML               ((EOledbTypes) 141) // introduced in SQL 2005
#define DBTYPE_TABLE             ((EOledbTypes) 143) // introduced in SQL 2008
#define DBTYPE_DBTIME2           ((EOledbTypes) 145) // introduced in SQL 2008
#define DBTYPE_DBTIMESTAMPOFFSET ((EOledbTypes) 146) // introduced in SQL 2008
#ifdef  _SQLOLEDB_H_
#undef DBTYPE_SQLVARIANT
#endif //_SQLOLEDB_H_
#define DBTYPE_SQLVARIANT        ((EOledbTypes) 144) // introduced in MDAC 2.5


#ifndef  _SQLOLEDB_H_
enum SQLVARENUM
    {
    VT_SS_EMPTY = DBTYPE_EMPTY,
    VT_SS_NULL = DBTYPE_NULL,
    VT_SS_UI1 = DBTYPE_UI1,
    VT_SS_I2 = DBTYPE_I2,
    VT_SS_I4 = DBTYPE_I4,
    VT_SS_I8 = DBTYPE_I8,

    //Floats
    VT_SS_R4  = DBTYPE_R4,
    VT_SS_R8 = DBTYPE_R8,

    //Money
    VT_SS_MONEY = DBTYPE_CY,
    VT_SS_SMALLMONEY  = 200,

    //Strings
    VT_SS_WSTRING    = 201,
    VT_SS_WVARSTRING = 202,

    VT_SS_STRING     = 203,
    VT_SS_VARSTRING  = 204,

    //Bit
    VT_SS_BIT        = DBTYPE_BOOL,

    //Guid
    VT_SS_GUID       = DBTYPE_GUID,

    //Exact precision
    VT_SS_NUMERIC    = DBTYPE_NUMERIC,
    VT_SS_DECIMAL    = 205,

    //Datetime
    VT_SS_DATETIME      = DBTYPE_DBTIMESTAMP,
    VT_SS_SMALLDATETIME =206,

    //Binary
    VT_SS_BINARY =207,
    VT_SS_VARBINARY = 208,
    //Future
    VT_SS_UNKNOWN   = 209,

    //Additional datetime
    VT_SS_DATE = DBTYPE_DBDATE,
    VT_SS_TIME2 = DBTYPE_DBTIME2,
    VT_SS_DATETIME2 = 212,
    VT_SS_DATETIMEOFFSET = DBTYPE_DBTIMESTAMPOFFSET,
    };
typedef unsigned short SSVARTYPE;


enum DBPARAMFLAGSENUM_SS_100
    {	DBPARAMFLAGS_SS_ISVARIABLESCALE	= 0x40000000
    } ;
enum DBCOLUMNFLAGSENUM_SS_100
    {   DBCOLUMNFLAGS_SS_ISVARIABLESCALE    = 0x40000000,
        DBCOLUMNFLAGS_SS_ISCOLUMNSET        = 0x80000000
    } ;

//-------------------------------------------------------------------
// Class Factory Interface used to initialize pointer to UMS.
//-------------------------------------------------------------------


extern RPC_IF_HANDLE __MIDL_itf_sqlncli_0000_0001_v0_0_c_ifspec;
extern RPC_IF_HANDLE __MIDL_itf_sqlncli_0000_0001_v0_0_s_ifspec;

#ifndef __IUMSInitialize_INTERFACE_DEFINED__
#define __IUMSInitialize_INTERFACE_DEFINED__

/* interface IUMSInitialize */
/* [unique][uuid][object][local] */ 


EXTERN_C const IID IID_IUMSInitialize;

#if defined(__cplusplus) && !defined(CINTERFACE)
    
    MIDL_INTERFACE("5cf4ca14-ef21-11d0-97e7-00c04fc2ad98")
    IUMSInitialize : public IUnknown
    {
    public:
        virtual HRESULT STDMETHODCALLTYPE Initialize( 
            /* [in] */ void *pUMS) = 0;
        
    };
    
#else 	/* C style interface */

    typedef struct IUMSInitializeVtbl
    {
        BEGIN_INTERFACE
        
        HRESULT ( STDMETHODCALLTYPE *QueryInterface )( 
            IUMSInitialize * This,
            /* [in] */ REFIID riid,
            /* [iid_is][out] */ 
            __RPC__deref_out  void **ppvObject);
        
        ULONG ( STDMETHODCALLTYPE *AddRef )( 
            IUMSInitialize * This);
        
        ULONG ( STDMETHODCALLTYPE *Release )( 
            IUMSInitialize * This);
        
        HRESULT ( STDMETHODCALLTYPE *Initialize )( 
            IUMSInitialize * This,
            /* [in] */ void *pUMS);
        
        END_INTERFACE
    } IUMSInitializeVtbl;

    interface IUMSInitialize
    {
        CONST_VTBL struct IUMSInitializeVtbl *lpVtbl;
    };

    

#ifdef COBJMACROS


#define IUMSInitialize_QueryInterface(This,riid,ppvObject)	\
    ( (This)->lpVtbl -> QueryInterface(This,riid,ppvObject) ) 

#define IUMSInitialize_AddRef(This)	\
    ( (This)->lpVtbl -> AddRef(This) ) 

#define IUMSInitialize_Release(This)	\
    ( (This)->lpVtbl -> Release(This) ) 


#define IUMSInitialize_Initialize(This,pUMS)	\
    ( (This)->lpVtbl -> Initialize(This,pUMS) ) 

#endif /* COBJMACROS */


#endif 	/* C style interface */




#endif 	/* __IUMSInitialize_INTERFACE_DEFINED__ */


/* interface __MIDL_itf_sqlncli_0000_0002 */
/* [local] */ 


// the structure returned by  ISQLServerErrorInfo::GetSQLServerInfo
typedef struct tagSSErrorInfo
    {
    LPOLESTR pwszMessage;
    LPOLESTR pwszServer;
    LPOLESTR pwszProcedure;
    LONG lNative;
    BYTE bState;
    BYTE bClass;
    WORD wLineNumber;
    } 	SSERRORINFO;



extern RPC_IF_HANDLE __MIDL_itf_sqlncli_0000_0002_v0_0_c_ifspec;
extern RPC_IF_HANDLE __MIDL_itf_sqlncli_0000_0002_v0_0_s_ifspec;

#ifndef __ISQLServerErrorInfo_INTERFACE_DEFINED__
#define __ISQLServerErrorInfo_INTERFACE_DEFINED__

/* interface ISQLServerErrorInfo */
/* [unique][uuid][object][local] */ 


EXTERN_C const IID IID_ISQLServerErrorInfo;

#if defined(__cplusplus) && !defined(CINTERFACE)
    
    MIDL_INTERFACE("5CF4CA12-EF21-11d0-97E7-00C04FC2AD98")
    ISQLServerErrorInfo : public IUnknown
    {
    public:
        virtual HRESULT STDMETHODCALLTYPE GetErrorInfo( 
            /* [out] */ SSERRORINFO **ppErrorInfo,
            /* [out] */ OLECHAR **ppStringsBuffer) = 0;
        
    };
    
#else 	/* C style interface */

    typedef struct ISQLServerErrorInfoVtbl
    {
        BEGIN_INTERFACE
        
        HRESULT ( STDMETHODCALLTYPE *QueryInterface )( 
            ISQLServerErrorInfo * This,
            /* [in] */ REFIID riid,
            /* [iid_is][out] */ 
            __RPC__deref_out  void **ppvObject);
        
        ULONG ( STDMETHODCALLTYPE *AddRef )( 
            ISQLServerErrorInfo * This);
        
        ULONG ( STDMETHODCALLTYPE *Release )( 
            ISQLServerErrorInfo * This);
        
        HRESULT ( STDMETHODCALLTYPE *GetErrorInfo )( 
            ISQLServerErrorInfo * This,
            /* [out] */ SSERRORINFO **ppErrorInfo,
            /* [out] */ OLECHAR **ppStringsBuffer);
        
        END_INTERFACE
    } ISQLServerErrorInfoVtbl;

    interface ISQLServerErrorInfo
    {
        CONST_VTBL struct ISQLServerErrorInfoVtbl *lpVtbl;
    };

    

#ifdef COBJMACROS


#define ISQLServerErrorInfo_QueryInterface(This,riid,ppvObject)	\
    ( (This)->lpVtbl -> QueryInterface(This,riid,ppvObject) ) 

#define ISQLServerErrorInfo_AddRef(This)	\
    ( (This)->lpVtbl -> AddRef(This) ) 

#define ISQLServerErrorInfo_Release(This)	\
    ( (This)->lpVtbl -> Release(This) ) 


#define ISQLServerErrorInfo_GetErrorInfo(This,ppErrorInfo,ppStringsBuffer)	\
    ( (This)->lpVtbl -> GetErrorInfo(This,ppErrorInfo,ppStringsBuffer) ) 

#endif /* COBJMACROS */


#endif 	/* C style interface */




#endif 	/* __ISQLServerErrorInfo_INTERFACE_DEFINED__ */


#ifndef __IRowsetFastLoad_INTERFACE_DEFINED__
#define __IRowsetFastLoad_INTERFACE_DEFINED__

/* interface IRowsetFastLoad */
/* [unique][uuid][object][local] */ 


EXTERN_C const IID IID_IRowsetFastLoad;

#if defined(__cplusplus) && !defined(CINTERFACE)
    
    MIDL_INTERFACE("5CF4CA13-EF21-11d0-97E7-00C04FC2AD98")
    IRowsetFastLoad : public IUnknown
    {
    public:
        virtual HRESULT STDMETHODCALLTYPE InsertRow( 
            /* [in] */ HACCESSOR hAccessor,
            /* [in] */ void *pData) = 0;
        
        virtual HRESULT STDMETHODCALLTYPE Commit( 
            /* [in] */ BOOL fDone) = 0;
        
    };
    
#else 	/* C style interface */

    typedef struct IRowsetFastLoadVtbl
    {
        BEGIN_INTERFACE
        
        HRESULT ( STDMETHODCALLTYPE *QueryInterface )( 
            IRowsetFastLoad * This,
            /* [in] */ REFIID riid,
            /* [iid_is][out] */ 
            __RPC__deref_out  void **ppvObject);
        
        ULONG ( STDMETHODCALLTYPE *AddRef )( 
            IRowsetFastLoad * This);
        
        ULONG ( STDMETHODCALLTYPE *Release )( 
            IRowsetFastLoad * This);
        
        HRESULT ( STDMETHODCALLTYPE *InsertRow )( 
            IRowsetFastLoad * This,
            /* [in] */ HACCESSOR hAccessor,
            /* [in] */ void *pData);
        
        HRESULT ( STDMETHODCALLTYPE *Commit )( 
            IRowsetFastLoad * This,
            /* [in] */ BOOL fDone);
        
        END_INTERFACE
    } IRowsetFastLoadVtbl;

    interface IRowsetFastLoad
    {
        CONST_VTBL struct IRowsetFastLoadVtbl *lpVtbl;
    };

    

#ifdef COBJMACROS


#define IRowsetFastLoad_QueryInterface(This,riid,ppvObject)	\
    ( (This)->lpVtbl -> QueryInterface(This,riid,ppvObject) ) 

#define IRowsetFastLoad_AddRef(This)	\
    ( (This)->lpVtbl -> AddRef(This) ) 

#define IRowsetFastLoad_Release(This)	\
    ( (This)->lpVtbl -> Release(This) ) 


#define IRowsetFastLoad_InsertRow(This,hAccessor,pData)	\
    ( (This)->lpVtbl -> InsertRow(This,hAccessor,pData) ) 

#define IRowsetFastLoad_Commit(This,fDone)	\
    ( (This)->lpVtbl -> Commit(This,fDone) ) 

#endif /* COBJMACROS */


#endif 	/* C style interface */




#endif 	/* __IRowsetFastLoad_INTERFACE_DEFINED__ */


/* interface __MIDL_itf_sqlncli_0000_0004 */
/* [local] */ 

#include <pshpack8.h>    // 8-byte structure packing

typedef struct tagDBTIME2
    {
    USHORT hour;
    USHORT minute;
    USHORT second;
    ULONG fraction;
    } 	DBTIME2;

typedef struct tagDBTIMESTAMPOFFSET
    {
    SHORT year;
    USHORT month;
    USHORT day;
    USHORT hour;
    USHORT minute;
    USHORT second;
    ULONG fraction;
    SHORT timezone_hour;
    SHORT timezone_minute;
    } 	DBTIMESTAMPOFFSET;

#include <poppack.h>     // restore original structure packing

struct SSVARIANT
    {
    SSVARTYPE vt;
    DWORD dwReserved1;
    DWORD dwReserved2;
    union 
        {
        BYTE bTinyIntVal;
        SHORT sShortIntVal;
        LONG lIntVal;
        LONGLONG llBigIntVal;
        FLOAT fltRealVal;
        DOUBLE dblFloatVal;
        CY cyMoneyVal;
        VARIANT_BOOL fBitVal;
        BYTE rgbGuidVal[ 16 ];
        DB_NUMERIC numNumericVal;
        DBDATE dDateVal;
        DBTIMESTAMP tsDateTimeVal;
        struct _Time2Val
            {
            DBTIME2 tTime2Val;
            BYTE bScale;
            } 	Time2Val;
        struct _DateTimeVal
            {
            DBTIMESTAMP tsDateTimeVal;
            BYTE bScale;
            } 	DateTimeVal;
        struct _DateTimeOffsetVal
            {
            DBTIMESTAMPOFFSET tsoDateTimeOffsetVal;
            BYTE bScale;
            } 	DateTimeOffsetVal;
        struct _NCharVal
            {
            SHORT sActualLength;
            SHORT sMaxLength;
            WCHAR *pwchNCharVal;
            BYTE rgbReserved[ 5 ];
            DWORD dwReserved;
            WCHAR *pwchReserved;
            } 	NCharVal;
        struct _CharVal
            {
            SHORT sActualLength;
            SHORT sMaxLength;
            CHAR *pchCharVal;
            BYTE rgbReserved[ 5 ];
            DWORD dwReserved;
            WCHAR *pwchReserved;
            } 	CharVal;
        struct _BinaryVal
            {
            SHORT sActualLength;
            SHORT sMaxLength;
            BYTE *prgbBinaryVal;
            DWORD dwReserved;
            } 	BinaryVal;
        struct _UnknownType
            {
            DWORD dwActualLength;
            BYTE rgMetadata[ 16 ];
            BYTE *pUnknownData;
            } 	UnknownType;
        struct _BLOBType
            {
            DBOBJECT dbobj;
            IUnknown *pUnk;
            } 	BLOBType;
        } 	;
    } ;
typedef DWORD LOCKMODE;


enum LOCKMODEENUM
    {	LOCKMODE_INVALID	= 0,
	LOCKMODE_EXCLUSIVE	= ( LOCKMODE_INVALID + 1 ) ,
	LOCKMODE_SHARED	= ( LOCKMODE_EXCLUSIVE + 1 ) 
    } ;


extern RPC_IF_HANDLE __MIDL_itf_sqlncli_0000_0004_v0_0_c_ifspec;
extern RPC_IF_HANDLE __MIDL_itf_sqlncli_0000_0004_v0_0_s_ifspec;

#ifndef __ISchemaLock_INTERFACE_DEFINED__
#define __ISchemaLock_INTERFACE_DEFINED__

/* interface ISchemaLock */
/* [unique][uuid][object][local] */ 


EXTERN_C const IID IID_ISchemaLock;

#if defined(__cplusplus) && !defined(CINTERFACE)
    
    MIDL_INTERFACE("4C2389FB-2511-11d4-B258-00C04F7971CE")
    ISchemaLock : public IUnknown
    {
    public:
        virtual HRESULT STDMETHODCALLTYPE GetSchemaLock( 
            /* [in] */ DBID *pTableID,
            /* [in] */ LOCKMODE lmMode,
            /* [out] */ HANDLE *phLockHandle,
            /* [out] */ ULONGLONG *pTableVersion) = 0;
        
        virtual HRESULT STDMETHODCALLTYPE ReleaseSchemaLock( 
            /* [in] */ HANDLE hLockHandle) = 0;
        
    };
    
#else 	/* C style interface */

    typedef struct ISchemaLockVtbl
    {
        BEGIN_INTERFACE
        
        HRESULT ( STDMETHODCALLTYPE *QueryInterface )( 
            ISchemaLock * This,
            /* [in] */ REFIID riid,
            /* [iid_is][out] */ 
            __RPC__deref_out  void **ppvObject);
        
        ULONG ( STDMETHODCALLTYPE *AddRef )( 
            ISchemaLock * This);
        
        ULONG ( STDMETHODCALLTYPE *Release )( 
            ISchemaLock * This);
        
        HRESULT ( STDMETHODCALLTYPE *GetSchemaLock )( 
            ISchemaLock * This,
            /* [in] */ DBID *pTableID,
            /* [in] */ LOCKMODE lmMode,
            /* [out] */ HANDLE *phLockHandle,
            /* [out] */ ULONGLONG *pTableVersion);
        
        HRESULT ( STDMETHODCALLTYPE *ReleaseSchemaLock )( 
            ISchemaLock * This,
            /* [in] */ HANDLE hLockHandle);
        
        END_INTERFACE
    } ISchemaLockVtbl;

    interface ISchemaLock
    {
        CONST_VTBL struct ISchemaLockVtbl *lpVtbl;
    };

    

#ifdef COBJMACROS


#define ISchemaLock_QueryInterface(This,riid,ppvObject)	\
    ( (This)->lpVtbl -> QueryInterface(This,riid,ppvObject) ) 

#define ISchemaLock_AddRef(This)	\
    ( (This)->lpVtbl -> AddRef(This) ) 

#define ISchemaLock_Release(This)	\
    ( (This)->lpVtbl -> Release(This) ) 


#define ISchemaLock_GetSchemaLock(This,pTableID,lmMode,phLockHandle,pTableVersion)	\
    ( (This)->lpVtbl -> GetSchemaLock(This,pTableID,lmMode,phLockHandle,pTableVersion) ) 

#define ISchemaLock_ReleaseSchemaLock(This,hLockHandle)	\
    ( (This)->lpVtbl -> ReleaseSchemaLock(This,hLockHandle) ) 

#endif /* COBJMACROS */


#endif 	/* C style interface */




#endif 	/* __ISchemaLock_INTERFACE_DEFINED__ */


#ifndef __IBCPSession_INTERFACE_DEFINED__
#define __IBCPSession_INTERFACE_DEFINED__

/* interface IBCPSession */
/* [unique][uuid][object][local] */ 


EXTERN_C const IID IID_IBCPSession;

#if defined(__cplusplus) && !defined(CINTERFACE)
    
    MIDL_INTERFACE("88352D80-42D1-42f0-A170-AB0F8B45B939")
    IBCPSession : public IUnknown
    {
    public:
        virtual HRESULT STDMETHODCALLTYPE BCPColFmt( 
            /* [in] */ DBORDINAL idxUserDataCol,
            /* [in] */ int eUserDataType,
            /* [in] */ int cbIndicator,
            /* [in] */ int cbUserData,
            /* [size_is][in] */ BYTE *pbUserDataTerm,
            /* [in] */ int cbUserDataTerm,
            /* [in] */ DBORDINAL idxServerCol) = 0;
        
        virtual HRESULT STDMETHODCALLTYPE BCPColumns( 
            /* [in] */ DBCOUNTITEM nColumns) = 0;
        
        virtual HRESULT STDMETHODCALLTYPE BCPControl( 
            /* [in] */ int eOption,
            /* [in] */ void *iValue) = 0;
        
        virtual HRESULT STDMETHODCALLTYPE BCPDone( void) = 0;
        
        virtual HRESULT STDMETHODCALLTYPE BCPExec( 
            /* [out] */ DBROWCOUNT *pRowsCopied) = 0;
        
        virtual HRESULT STDMETHODCALLTYPE BCPInit( 
            /* [string][in] */ const wchar_t *pwszTable,
            /* [string][in] */ const wchar_t *pwszDataFile,
            /* [string][in] */ const wchar_t *pwszErrorFile,
            /* [in] */ int eDirection) = 0;
        
        virtual HRESULT STDMETHODCALLTYPE BCPReadFmt( 
            /* [string][in] */ const wchar_t *pwszFormatFile) = 0;
        
        virtual HRESULT STDMETHODCALLTYPE BCPWriteFmt( 
            /* [string][in] */ const wchar_t *pwszFormatFile) = 0;
        
    };
    
#else 	/* C style interface */

    typedef struct IBCPSessionVtbl
    {
        BEGIN_INTERFACE
        
        HRESULT ( STDMETHODCALLTYPE *QueryInterface )( 
            IBCPSession * This,
            /* [in] */ REFIID riid,
            /* [iid_is][out] */ 
            __RPC__deref_out  void **ppvObject);
        
        ULONG ( STDMETHODCALLTYPE *AddRef )( 
            IBCPSession * This);
        
        ULONG ( STDMETHODCALLTYPE *Release )( 
            IBCPSession * This);
        
        HRESULT ( STDMETHODCALLTYPE *BCPColFmt )( 
            IBCPSession * This,
            /* [in] */ DBORDINAL idxUserDataCol,
            /* [in] */ int eUserDataType,
            /* [in] */ int cbIndicator,
            /* [in] */ int cbUserData,
            /* [size_is][in] */ BYTE *pbUserDataTerm,
            /* [in] */ int cbUserDataTerm,
            /* [in] */ DBORDINAL idxServerCol);
        
        HRESULT ( STDMETHODCALLTYPE *BCPColumns )( 
            IBCPSession * This,
            /* [in] */ DBCOUNTITEM nColumns);
        
        HRESULT ( STDMETHODCALLTYPE *BCPControl )( 
            IBCPSession * This,
            /* [in] */ int eOption,
            /* [in] */ void *iValue);
        
        HRESULT ( STDMETHODCALLTYPE *BCPDone )( 
            IBCPSession * This);
        
        HRESULT ( STDMETHODCALLTYPE *BCPExec )( 
            IBCPSession * This,
            /* [out] */ DBROWCOUNT *pRowsCopied);
        
        HRESULT ( STDMETHODCALLTYPE *BCPInit )( 
            IBCPSession * This,
            /* [string][in] */ const wchar_t *pwszTable,
            /* [string][in] */ const wchar_t *pwszDataFile,
            /* [string][in] */ const wchar_t *pwszErrorFile,
            /* [in] */ int eDirection);
        
        HRESULT ( STDMETHODCALLTYPE *BCPReadFmt )( 
            IBCPSession * This,
            /* [string][in] */ const wchar_t *pwszFormatFile);
        
        HRESULT ( STDMETHODCALLTYPE *BCPWriteFmt )( 
            IBCPSession * This,
            /* [string][in] */ const wchar_t *pwszFormatFile);
        
        END_INTERFACE
    } IBCPSessionVtbl;

    interface IBCPSession
    {
        CONST_VTBL struct IBCPSessionVtbl *lpVtbl;
    };

    

#ifdef COBJMACROS


#define IBCPSession_QueryInterface(This,riid,ppvObject)	\
    ( (This)->lpVtbl -> QueryInterface(This,riid,ppvObject) ) 

#define IBCPSession_AddRef(This)	\
    ( (This)->lpVtbl -> AddRef(This) ) 

#define IBCPSession_Release(This)	\
    ( (This)->lpVtbl -> Release(This) ) 


#define IBCPSession_BCPColFmt(This,idxUserDataCol,eUserDataType,cbIndicator,cbUserData,pbUserDataTerm,cbUserDataTerm,idxServerCol)	\
    ( (This)->lpVtbl -> BCPColFmt(This,idxUserDataCol,eUserDataType,cbIndicator,cbUserData,pbUserDataTerm,cbUserDataTerm,idxServerCol) ) 

#define IBCPSession_BCPColumns(This,nColumns)	\
    ( (This)->lpVtbl -> BCPColumns(This,nColumns) ) 

#define IBCPSession_BCPControl(This,eOption,iValue)	\
    ( (This)->lpVtbl -> BCPControl(This,eOption,iValue) ) 

#define IBCPSession_BCPDone(This)	\
    ( (This)->lpVtbl -> BCPDone(This) ) 

#define IBCPSession_BCPExec(This,pRowsCopied)	\
    ( (This)->lpVtbl -> BCPExec(This,pRowsCopied) ) 

#define IBCPSession_BCPInit(This,pwszTable,pwszDataFile,pwszErrorFile,eDirection)	\
    ( (This)->lpVtbl -> BCPInit(This,pwszTable,pwszDataFile,pwszErrorFile,eDirection) ) 

#define IBCPSession_BCPReadFmt(This,pwszFormatFile)	\
    ( (This)->lpVtbl -> BCPReadFmt(This,pwszFormatFile) ) 

#define IBCPSession_BCPWriteFmt(This,pwszFormatFile)	\
    ( (This)->lpVtbl -> BCPWriteFmt(This,pwszFormatFile) ) 

#endif /* COBJMACROS */


#endif 	/* C style interface */




#endif 	/* __IBCPSession_INTERFACE_DEFINED__ */


/* interface __MIDL_itf_sqlncli_0000_0006 */
/* [local] */ 


#endif //_SQLOLEDB_H_

#define ISOLATIONLEVEL_SNAPSHOT          ((ISOLATIONLEVEL)(0x01000000)) // Changes made in other transactions can not be seen.

#define DBPROPVAL_TI_SNAPSHOT            0x01000000L



extern RPC_IF_HANDLE __MIDL_itf_sqlncli_0000_0006_v0_0_c_ifspec;
extern RPC_IF_HANDLE __MIDL_itf_sqlncli_0000_0006_v0_0_s_ifspec;

#ifndef __ISSAbort_INTERFACE_DEFINED__
#define __ISSAbort_INTERFACE_DEFINED__

/* interface ISSAbort */
/* [unique][uuid][object][local] */ 


EXTERN_C const IID IID_ISSAbort;

#if defined(__cplusplus) && !defined(CINTERFACE)
    
    MIDL_INTERFACE("5CF4CA15-EF21-11d0-97E7-00C04FC2AD98")
    ISSAbort : public IUnknown
    {
    public:
        virtual HRESULT STDMETHODCALLTYPE Abort( void) = 0;
        
    };
    
#else 	/* C style interface */

    typedef struct ISSAbortVtbl
    {
        BEGIN_INTERFACE
        
        HRESULT ( STDMETHODCALLTYPE *QueryInterface )( 
            ISSAbort * This,
            /* [in] */ REFIID riid,
            /* [iid_is][out] */ 
            __RPC__deref_out  void **ppvObject);
        
        ULONG ( STDMETHODCALLTYPE *AddRef )( 
            ISSAbort * This);
        
        ULONG ( STDMETHODCALLTYPE *Release )( 
            ISSAbort * This);
        
        HRESULT ( STDMETHODCALLTYPE *Abort )( 
            ISSAbort * This);
        
        END_INTERFACE
    } ISSAbortVtbl;

    interface ISSAbort
    {
        CONST_VTBL struct ISSAbortVtbl *lpVtbl;
    };

    

#ifdef COBJMACROS


#define ISSAbort_QueryInterface(This,riid,ppvObject)	\
    ( (This)->lpVtbl -> QueryInterface(This,riid,ppvObject) ) 

#define ISSAbort_AddRef(This)	\
    ( (This)->lpVtbl -> AddRef(This) ) 

#define ISSAbort_Release(This)	\
    ( (This)->lpVtbl -> Release(This) ) 


#define ISSAbort_Abort(This)	\
    ( (This)->lpVtbl -> Abort(This) ) 

#endif /* COBJMACROS */


#endif 	/* C style interface */




#endif 	/* __ISSAbort_INTERFACE_DEFINED__ */


/* interface __MIDL_itf_sqlncli_0000_0007 */
/* [local] */ 


enum DBBINDFLAGENUM90
    {	DBBINDFLAG_OBJECT	= 0x2
    } ;

enum SSACCESSORFLAGS
    {	SSACCESSOR_ROWDATA	= 0x100
    } ;

enum DBPROPFLAGSENUM90
    {	DBPROPFLAGS_PARAMETER	= 0x10000
    } ;
typedef struct tagSSPARAMPROPS
    {
    DBORDINAL iOrdinal;
    ULONG cPropertySets;
    DBPROPSET *rgPropertySets;
    } 	SSPARAMPROPS;



extern RPC_IF_HANDLE __MIDL_itf_sqlncli_0000_0007_v0_0_c_ifspec;
extern RPC_IF_HANDLE __MIDL_itf_sqlncli_0000_0007_v0_0_s_ifspec;

#ifndef __ISSCommandWithParameters_INTERFACE_DEFINED__
#define __ISSCommandWithParameters_INTERFACE_DEFINED__

/* interface ISSCommandWithParameters */
/* [unique][uuid][object][local] */ 


EXTERN_C const IID IID_ISSCommandWithParameters;

#if defined(__cplusplus) && !defined(CINTERFACE)
    
    MIDL_INTERFACE("eec30162-6087-467c-b995-7c523ce96561")
    ISSCommandWithParameters : public ICommandWithParameters
    {
    public:
        virtual /* [local] */ HRESULT STDMETHODCALLTYPE GetParameterProperties( 
            /* [out][in] */ DB_UPARAMS *pcParams,
            /* [size_is][size_is][out] */ SSPARAMPROPS **prgParamProperties) = 0;
        
        virtual /* [local] */ HRESULT STDMETHODCALLTYPE SetParameterProperties( 
            /* [in] */ DB_UPARAMS cParams,
            /* [size_is][unique][in] */ SSPARAMPROPS rgParamProperties[  ]) = 0;
        
    };
    
#else 	/* C style interface */

    typedef struct ISSCommandWithParametersVtbl
    {
        BEGIN_INTERFACE
        
        HRESULT ( STDMETHODCALLTYPE *QueryInterface )( 
            ISSCommandWithParameters * This,
            /* [in] */ REFIID riid,
            /* [iid_is][out] */ 
            __RPC__deref_out  void **ppvObject);
        
        ULONG ( STDMETHODCALLTYPE *AddRef )( 
            ISSCommandWithParameters * This);
        
        ULONG ( STDMETHODCALLTYPE *Release )( 
            ISSCommandWithParameters * This);
        
        /* [local] */ HRESULT ( STDMETHODCALLTYPE *GetParameterInfo )( 
            ISSCommandWithParameters * This,
            /* [out][in] */ DB_UPARAMS *pcParams,
            /* [size_is][size_is][out] */ DBPARAMINFO **prgParamInfo,
            /* [out] */ OLECHAR **ppNamesBuffer);
        
        /* [local] */ HRESULT ( STDMETHODCALLTYPE *MapParameterNames )( 
            ISSCommandWithParameters * This,
            /* [in] */ DB_UPARAMS cParamNames,
            /* [size_is][in] */ const OLECHAR *rgParamNames[  ],
            /* [size_is][out] */ DB_LPARAMS rgParamOrdinals[  ]);
        
        /* [local] */ HRESULT ( STDMETHODCALLTYPE *SetParameterInfo )( 
            ISSCommandWithParameters * This,
            /* [in] */ DB_UPARAMS cParams,
            /* [size_is][unique][in] */ const DB_UPARAMS rgParamOrdinals[  ],
            /* [size_is][unique][in] */ const DBPARAMBINDINFO rgParamBindInfo[  ]);
        
        /* [local] */ HRESULT ( STDMETHODCALLTYPE *GetParameterProperties )( 
            ISSCommandWithParameters * This,
            /* [out][in] */ DB_UPARAMS *pcParams,
            /* [size_is][size_is][out] */ SSPARAMPROPS **prgParamProperties);
        
        /* [local] */ HRESULT ( STDMETHODCALLTYPE *SetParameterProperties )( 
            ISSCommandWithParameters * This,
            /* [in] */ DB_UPARAMS cParams,
            /* [size_is][unique][in] */ SSPARAMPROPS rgParamProperties[  ]);
        
        END_INTERFACE
    } ISSCommandWithParametersVtbl;

    interface ISSCommandWithParameters
    {
        CONST_VTBL struct ISSCommandWithParametersVtbl *lpVtbl;
    };

    

#ifdef COBJMACROS


#define ISSCommandWithParameters_QueryInterface(This,riid,ppvObject)	\
    ( (This)->lpVtbl -> QueryInterface(This,riid,ppvObject) ) 

#define ISSCommandWithParameters_AddRef(This)	\
    ( (This)->lpVtbl -> AddRef(This) ) 

#define ISSCommandWithParameters_Release(This)	\
    ( (This)->lpVtbl -> Release(This) ) 


#define ISSCommandWithParameters_GetParameterInfo(This,pcParams,prgParamInfo,ppNamesBuffer)	\
    ( (This)->lpVtbl -> GetParameterInfo(This,pcParams,prgParamInfo,ppNamesBuffer) ) 

#define ISSCommandWithParameters_MapParameterNames(This,cParamNames,rgParamNames,rgParamOrdinals)	\
    ( (This)->lpVtbl -> MapParameterNames(This,cParamNames,rgParamNames,rgParamOrdinals) ) 

#define ISSCommandWithParameters_SetParameterInfo(This,cParams,rgParamOrdinals,rgParamBindInfo)	\
    ( (This)->lpVtbl -> SetParameterInfo(This,cParams,rgParamOrdinals,rgParamBindInfo) ) 


#define ISSCommandWithParameters_GetParameterProperties(This,pcParams,prgParamProperties)	\
    ( (This)->lpVtbl -> GetParameterProperties(This,pcParams,prgParamProperties) ) 

#define ISSCommandWithParameters_SetParameterProperties(This,cParams,rgParamProperties)	\
    ( (This)->lpVtbl -> SetParameterProperties(This,cParams,rgParamProperties) ) 

#endif /* COBJMACROS */


#endif 	/* C style interface */




#endif 	/* __ISSCommandWithParameters_INTERFACE_DEFINED__ */


#ifndef __IDBAsynchStatus_INTERFACE_DEFINED__
#define __IDBAsynchStatus_INTERFACE_DEFINED__

/* interface IDBAsynchStatus */
/* [unique][uuid][object][local] */ 


EXTERN_C const IID IID_IDBAsynchStatus;

#if defined(__cplusplus) && !defined(CINTERFACE)
    
    MIDL_INTERFACE("0c733a95-2a1c-11ce-ade5-00aa0044773d")
    IDBAsynchStatus : public IUnknown
    {
    public:
        virtual /* [local] */ HRESULT STDMETHODCALLTYPE Abort( 
            /* [in] */ HCHAPTER hChapter,
            /* [in] */ DBASYNCHOP eOperation) = 0;
        
        virtual /* [local] */ HRESULT STDMETHODCALLTYPE GetStatus( 
            /* [in] */ HCHAPTER hChapter,
            /* [in] */ DBASYNCHOP eOperation,
            /* [out] */ DBCOUNTITEM *pulProgress,
            /* [out] */ DBCOUNTITEM *pulProgressMax,
            /* [out] */ DBASYNCHPHASE *peAsynchPhase,
            /* [out] */ LPOLESTR *ppwszStatusText) = 0;
        
    };
    
#else 	/* C style interface */

    typedef struct IDBAsynchStatusVtbl
    {
        BEGIN_INTERFACE
        
        HRESULT ( STDMETHODCALLTYPE *QueryInterface )( 
            IDBAsynchStatus * This,
            /* [in] */ REFIID riid,
            /* [iid_is][out] */ 
            __RPC__deref_out  void **ppvObject);
        
        ULONG ( STDMETHODCALLTYPE *AddRef )( 
            IDBAsynchStatus * This);
        
        ULONG ( STDMETHODCALLTYPE *Release )( 
            IDBAsynchStatus * This);
        
        /* [local] */ HRESULT ( STDMETHODCALLTYPE *Abort )( 
            IDBAsynchStatus * This,
            /* [in] */ HCHAPTER hChapter,
            /* [in] */ DBASYNCHOP eOperation);
        
        /* [local] */ HRESULT ( STDMETHODCALLTYPE *GetStatus )( 
            IDBAsynchStatus * This,
            /* [in] */ HCHAPTER hChapter,
            /* [in] */ DBASYNCHOP eOperation,
            /* [out] */ DBCOUNTITEM *pulProgress,
            /* [out] */ DBCOUNTITEM *pulProgressMax,
            /* [out] */ DBASYNCHPHASE *peAsynchPhase,
            /* [out] */ LPOLESTR *ppwszStatusText);
        
        END_INTERFACE
    } IDBAsynchStatusVtbl;

    interface IDBAsynchStatus
    {
        CONST_VTBL struct IDBAsynchStatusVtbl *lpVtbl;
    };

    

#ifdef COBJMACROS


#define IDBAsynchStatus_QueryInterface(This,riid,ppvObject)	\
    ( (This)->lpVtbl -> QueryInterface(This,riid,ppvObject) ) 

#define IDBAsynchStatus_AddRef(This)	\
    ( (This)->lpVtbl -> AddRef(This) ) 

#define IDBAsynchStatus_Release(This)	\
    ( (This)->lpVtbl -> Release(This) ) 


#define IDBAsynchStatus_Abort(This,hChapter,eOperation)	\
    ( (This)->lpVtbl -> Abort(This,hChapter,eOperation) ) 

#define IDBAsynchStatus_GetStatus(This,hChapter,eOperation,pulProgress,pulProgressMax,peAsynchPhase,ppwszStatusText)	\
    ( (This)->lpVtbl -> GetStatus(This,hChapter,eOperation,pulProgress,pulProgressMax,peAsynchPhase,ppwszStatusText) ) 

#endif /* COBJMACROS */


#endif 	/* C style interface */



/* [call_as] */ HRESULT STDMETHODCALLTYPE IDBAsynchStatus_RemoteAbort_Proxy( 
    IDBAsynchStatus * This,
    /* [in] */ HCHAPTER hChapter,
    /* [in] */ DBASYNCHOP eOperation,
    /* [out] */ IErrorInfo **ppErrorInfoRem);


void __RPC_STUB IDBAsynchStatus_RemoteAbort_Stub(
    IRpcStubBuffer *This,
    IRpcChannelBuffer *_pRpcChannelBuffer,
    PRPC_MESSAGE _pRpcMessage,
    DWORD *_pdwStubPhase);


/* [call_as] */ HRESULT STDMETHODCALLTYPE IDBAsynchStatus_RemoteGetStatus_Proxy( 
    IDBAsynchStatus * This,
    /* [in] */ HCHAPTER hChapter,
    /* [in] */ DBASYNCHOP eOperation,
    /* [unique][out][in] */ DBCOUNTITEM *pulProgress,
    /* [unique][out][in] */ DBCOUNTITEM *pulProgressMax,
    /* [unique][out][in] */ DBASYNCHPHASE *peAsynchPhase,
    /* [unique][out][in] */ LPOLESTR *ppwszStatusText,
    /* [out] */ IErrorInfo **ppErrorInfoRem);


void __RPC_STUB IDBAsynchStatus_RemoteGetStatus_Stub(
    IRpcStubBuffer *This,
    IRpcChannelBuffer *_pRpcChannelBuffer,
    PRPC_MESSAGE _pRpcMessage,
    DWORD *_pdwStubPhase);



#endif 	/* __IDBAsynchStatus_INTERFACE_DEFINED__ */


#ifndef __ISSAsynchStatus_INTERFACE_DEFINED__
#define __ISSAsynchStatus_INTERFACE_DEFINED__

/* interface ISSAsynchStatus */
/* [unique][uuid][object][local] */ 


EXTERN_C const IID IID_ISSAsynchStatus;

#if defined(__cplusplus) && !defined(CINTERFACE)
    
    MIDL_INTERFACE("1FF1F743-8BB0-4c00-ACC4-C10E43B08FC1")
    ISSAsynchStatus : public IDBAsynchStatus
    {
    public:
        virtual /* [local] */ HRESULT STDMETHODCALLTYPE WaitForAsynchCompletion( 
            /* [in] */ DWORD dwMillisecTimeOut) = 0;
        
    };
    
#else 	/* C style interface */

    typedef struct ISSAsynchStatusVtbl
    {
        BEGIN_INTERFACE
        
        HRESULT ( STDMETHODCALLTYPE *QueryInterface )( 
            ISSAsynchStatus * This,
            /* [in] */ REFIID riid,
            /* [iid_is][out] */ 
            __RPC__deref_out  void **ppvObject);
        
        ULONG ( STDMETHODCALLTYPE *AddRef )( 
            ISSAsynchStatus * This);
        
        ULONG ( STDMETHODCALLTYPE *Release )( 
            ISSAsynchStatus * This);
        
        /* [local] */ HRESULT ( STDMETHODCALLTYPE *Abort )( 
            ISSAsynchStatus * This,
            /* [in] */ HCHAPTER hChapter,
            /* [in] */ DBASYNCHOP eOperation);
        
        /* [local] */ HRESULT ( STDMETHODCALLTYPE *GetStatus )( 
            ISSAsynchStatus * This,
            /* [in] */ HCHAPTER hChapter,
            /* [in] */ DBASYNCHOP eOperation,
            /* [out] */ DBCOUNTITEM *pulProgress,
            /* [out] */ DBCOUNTITEM *pulProgressMax,
            /* [out] */ DBASYNCHPHASE *peAsynchPhase,
            /* [out] */ LPOLESTR *ppwszStatusText);
        
        /* [local] */ HRESULT ( STDMETHODCALLTYPE *WaitForAsynchCompletion )( 
            ISSAsynchStatus * This,
            /* [in] */ DWORD dwMillisecTimeOut);
        
        END_INTERFACE
    } ISSAsynchStatusVtbl;

    interface ISSAsynchStatus
    {
        CONST_VTBL struct ISSAsynchStatusVtbl *lpVtbl;
    };

    

#ifdef COBJMACROS


#define ISSAsynchStatus_QueryInterface(This,riid,ppvObject)	\
    ( (This)->lpVtbl -> QueryInterface(This,riid,ppvObject) ) 

#define ISSAsynchStatus_AddRef(This)	\
    ( (This)->lpVtbl -> AddRef(This) ) 

#define ISSAsynchStatus_Release(This)	\
    ( (This)->lpVtbl -> Release(This) ) 


#define ISSAsynchStatus_Abort(This,hChapter,eOperation)	\
    ( (This)->lpVtbl -> Abort(This,hChapter,eOperation) ) 

#define ISSAsynchStatus_GetStatus(This,hChapter,eOperation,pulProgress,pulProgressMax,peAsynchPhase,ppwszStatusText)	\
    ( (This)->lpVtbl -> GetStatus(This,hChapter,eOperation,pulProgress,pulProgressMax,peAsynchPhase,ppwszStatusText) ) 


#define ISSAsynchStatus_WaitForAsynchCompletion(This,dwMillisecTimeOut)	\
    ( (This)->lpVtbl -> WaitForAsynchCompletion(This,dwMillisecTimeOut) ) 

#endif /* COBJMACROS */


#endif 	/* C style interface */




#endif 	/* __ISSAsynchStatus_INTERFACE_DEFINED__ */


/* interface __MIDL_itf_sqlncli_0000_0010 */
/* [local] */ 

//----------------------------------------------------------------------------
// Values for STATUS bitmask for DBSCHEMA_TABLES & DBSCHEMA_TABLES_INFO
#define TABLE_HAS_UPDATE_INSTEAD_OF_TRIGGER     0x00000001 //table has IOT defined
#define TABLE_HAS_DELETE_INSTEAD_OF_TRIGGER     0x00000002 //table has IOT defined
#define TABLE_HAS_INSERT_INSTEAD_OF_TRIGGER     0x00000004 //table has IOT defined
#define TABLE_HAS_AFTER_UPDATE_TRIGGER          0x00000008 //table has update trigger
#define TABLE_HAS_AFTER_DELETE_TRIGGER          0x00000010 //table has delete trigger
#define TABLE_HAS_AFTER_INSERT_TRIGGER          0x00000020 //table has insert trigger
#define TABLE_HAS_CASCADE_UPDATE                0x00000040 //table has cascade update
#define TABLE_HAS_CASCADE_DELETE                0x00000080 //table has cascade delete

//----------------------------------------------------------------------------
// PropIds for DBPROP_INIT_GENERALTIMEOUT
#if (OLEDBVER >= 0x0210)
#define DBPROP_INIT_GENERALTIMEOUT      0x11cL
#endif

//----------------------------------------------------------------------------
// PropIds for DBPROPSET_SQLSERVERDATASOURCE
#define SSPROP_ENABLEFASTLOAD           2
#define SSPROP_ENABLEBULKCOPY           3

//----------------------------------------------------------------------------
// PropIds for DBPROPSET_SQLSERVERDATASOURCEINFO
#define SSPROP_UNICODELCID                       2
#define SSPROP_UNICODECOMPARISONSTYLE            3
#define SSPROP_COLUMNLEVELCOLLATION              4
#define SSPROP_CHARACTERSET                      5
#define SSPROP_SORTORDER                         6
#define SSPROP_CURRENTCOLLATION                  7
#define SSPROP_INTEGRATEDAUTHENTICATIONMETHOD    8
#define SSPROP_MUTUALLYAUTHENTICATED             9

//----------------------------------------------------------------------------
// PropIds for DBPROPSET_SQLSERVERDBINIT
#define SSPROP_INIT_CURRENTLANGUAGE              4
#define SSPROP_INIT_NETWORKADDRESS               5
#define SSPROP_INIT_NETWORKLIBRARY               6
#define SSPROP_INIT_USEPROCFORPREP               7
#define SSPROP_INIT_AUTOTRANSLATE                8
#define SSPROP_INIT_PACKETSIZE                   9
#define SSPROP_INIT_APPNAME                      10
#define SSPROP_INIT_WSID                         11
#define SSPROP_INIT_FILENAME                     12
#define SSPROP_INIT_ENCRYPT                      13
#define SSPROP_AUTH_REPL_SERVER_NAME             14
#define SSPROP_INIT_TAGCOLUMNCOLLATION           15
#define SSPROP_INIT_MARSCONNECTION               16
#define SSPROP_INIT_FAILOVERPARTNER              18
#define SSPROP_AUTH_OLD_PASSWORD                 19
#define SSPROP_INIT_DATATYPECOMPATIBILITY        20
#define SSPROP_INIT_TRUST_SERVER_CERTIFICATE     21
#define SSPROP_INIT_SERVERSPN                    22
#define SSPROP_INIT_FAILOVERPARTNERSPN           23

//-----------------------------------------------------------------------------
// Values for SSPROP_INIT_USEPROCFORPREP
#define SSPROPVAL_USEPROCFORPREP_OFF        0
#define SSPROPVAL_USEPROCFORPREP_ON         1
#define SSPROPVAL_USEPROCFORPREP_ON_DROP    2

//-----------------------------------------------------------------------------
// Values for SSPROP_INIT_DATATYPECOMPATIBILITY
#define SSPROPVAL_DATATYPECOMPATIBILITY_SQL2000  80
#define SSPROPVAL_DATATYPECOMPATIBILITY_DEFAULT  0

//----------------------------------------------------------------------------
// PropIds for DBPROPSET_SQLSERVERSESSION
#define SSPROP_QUOTEDCATALOGNAMES       2
#define SSPROP_ALLOWNATIVEVARIANT       3
#define SSPROP_SQLXMLXPROGID            4
#define SSPROP_ASYNCH_BULKCOPY          5

//----------------------------------------------------------------------------
// PropIds for DBPROPSET_SQLSERVERROWSET
#define SSPROP_MAXBLOBLENGTH            8
#define SSPROP_FASTLOADOPTIONS          9
#define SSPROP_FASTLOADKEEPNULLS        10
#define SSPROP_FASTLOADKEEPIDENTITY     11
#define SSPROP_CURSORAUTOFETCH          12
#define SSPROP_DEFERPREPARE             13
#define SSPROP_IRowsetFastLoad          14
#define SSPROP_QP_NOTIFICATION_TIMEOUT  17
#define SSPROP_QP_NOTIFICATION_MSGTEXT  18
#define SSPROP_QP_NOTIFICATION_OPTIONS  19
#define SSPROP_NOCOUNT_STATUS           20
#define SSPROP_COMPUTE_ID               21
#define SSPROP_COLUMN_ID                22
#define SSPROP_COMPUTE_BYLIST           23
#define SSPROP_ISSAsynchStatus          24

//-----------------------------------------------------------------------------
// Values for SSPROP_QP_NOTIFICATION_TIMEOUT
#define SSPROPVAL_DEFAULT_NOTIFICATION_TIMEOUT  432000 /* in sec */
#define SSPROPVAL_MAX_NOTIFICATION_TIMEOUT      0x7FFFFFFF /* in sec */
#define MAX_NOTIFICATION_LEN                    2000 /* NVARCHAR [2000] for both ID & DELIVERY_QUEUE */

//----------------------------------------------------------------------------
// PropIds for DBPROPSET_SQLSERVERCOLUMN
#define SSPROP_COL_COLLATIONNAME                         14
#define SSPROP_COL_UDT_CATALOGNAME                       31
#define SSPROP_COL_UDT_SCHEMANAME                        32
#define SSPROP_COL_UDT_NAME                              33
#define SSPROP_COL_XML_SCHEMACOLLECTION_CATALOGNAME      34
#define SSPROP_COL_XML_SCHEMACOLLECTION_SCHEMANAME       35
#define SSPROP_COL_XML_SCHEMACOLLECTIONNAME              36
#define SSPROP_COL_COMPUTED                              37


//----------------------------------------------------------------------------
// PropIds for DBPROPSET_SQLSERVERSTREAM
#define SSPROP_STREAM_XMLROOT                            19

//----------------------------------------------------------------------------
// PropIds for DBPROPSET_SQLSERVERPARAMETER
#define SSPROP_PARAM_XML_SCHEMACOLLECTION_CATALOGNAME    24
#define SSPROP_PARAM_XML_SCHEMACOLLECTION_SCHEMANAME     25
#define SSPROP_PARAM_XML_SCHEMACOLLECTIONNAME            26
#define SSPROP_PARAM_UDT_CATALOGNAME                     27
#define SSPROP_PARAM_UDT_SCHEMANAME                      28
#define SSPROP_PARAM_UDT_NAME                            29
#define SSPROP_PARAM_TYPE_CATALOGNAME                    38
#define SSPROP_PARAM_TYPE_SCHEMANAME                     39
#define SSPROP_PARAM_TYPE_TYPENAME                       40
#define SSPROP_PARAM_TABLE_DEFAULT_COLUMNS               41
#define SSPROP_PARAM_TABLE_COLUMN_SORT_ORDER             42

//----------------------------------------------------------------------------
// PropIds for DBPROPSET_SQLSERVERINDEX
#define SSPROP_INDEX_XML         1

//-----------------------------------------------------------------------------
//
#define BCP_TYPE_DEFAULT         0x00
#define BCP_TYPE_SQLTEXT         0x23
#define BCP_TYPE_SQLVARBINARY    0x25
#define BCP_TYPE_SQLINTN         0x26
#define BCP_TYPE_SQLVARCHAR      0x27
#define BCP_TYPE_SQLBINARY       0x2d
#define BCP_TYPE_SQLIMAGE        0x22
#define BCP_TYPE_SQLCHARACTER    0x2f
#define BCP_TYPE_SQLINT1         0x30
#define BCP_TYPE_SQLBIT          0x32
#define BCP_TYPE_SQLINT2         0x34
#define BCP_TYPE_SQLINT4         0x38
#define BCP_TYPE_SQLMONEY        0x3c
#define BCP_TYPE_SQLDATETIME     0x3d
#define BCP_TYPE_SQLFLT8         0x3e
#define BCP_TYPE_SQLFLTN         0x6d
#define BCP_TYPE_SQLMONEYN       0x6e
#define BCP_TYPE_SQLDATETIMN     0x6f
#define BCP_TYPE_SQLFLT4         0x3b
#define BCP_TYPE_SQLMONEY4       0x7a
#define BCP_TYPE_SQLDATETIM4     0x3a
#define BCP_TYPE_SQLDECIMAL      0x6a
#define BCP_TYPE_SQLNUMERIC      0x6c
#define BCP_TYPE_SQLUNIQUEID     0x24
#define BCP_TYPE_SQLBIGCHAR      0xaf
#define BCP_TYPE_SQLBIGVARCHAR   0xa7
#define BCP_TYPE_SQLBIGBINARY    0xad
#define BCP_TYPE_SQLBIGVARBINARY 0xa5
#define BCP_TYPE_SQLBITN         0x68
#define BCP_TYPE_SQLNCHAR        0xef
#define BCP_TYPE_SQLNVARCHAR     0xe7
#define BCP_TYPE_SQLNTEXT        0x63
#define BCP_TYPE_SQLDECIMALN     0x6a
#define BCP_TYPE_SQLNUMERICN     0x6c
#define BCP_TYPE_SQLINT8         0x7f
#define BCP_TYPE_SQLVARIANT      0x62
#define BCP_TYPE_SQLUDT          0xf0
#define BCP_TYPE_SQLXML          0xf1
#define BCP_TYPE_SQLDATE         0x28
#define BCP_TYPE_SQLTIME         0x29
#define BCP_TYPE_SQLDATETIME2    0x2a
#define BCP_TYPE_SQLDATETIMEOFFSET 0x2b

#define BCP_DIRECTION_IN            1
#define BCP_DIRECTION_OUT           2

#define BCP_OPTION_MAXERRS          1
#define BCP_OPTION_FIRST            2
#define BCP_OPTION_LAST             3
#define BCP_OPTION_BATCH            4
#define BCP_OPTION_KEEPNULLS        5
#define BCP_OPTION_ABORT            6
#define BCP_OPTION_KEEPIDENTITY     8
#define BCP_OPTION_HINTSA           10
#define BCP_OPTION_HINTSW           11
#define BCP_OPTION_FILECP           12
#define BCP_OPTION_UNICODEFILE      13
#define BCP_OPTION_TEXTFILE         14
#define BCP_OPTION_FILEFMT          15
#define BCP_OPTION_FMTXML           16
#define BCP_OPTION_FIRSTEX          17
#define BCP_OPTION_LASTEX           18
#define BCP_OPTION_ROWCOUNT         19

#define BCP_FILECP_ACP              0
#define BCP_FILECP_OEMCP            1
#define BCP_FILECP_RAW              (-1)

#ifdef UNICODE
#define BCP_OPTION_HINTS             BCP_OPTION_HINTSW
#else
#define BCP_OPTION_HINTS             BCP_OPTION_HINTSA
#endif

#define BCP_PREFIX_DEFAULT           (-10)

#define BCP_LENGTH_NULL              (-1)
#define BCP_LENGTH_VARIABLE          (-10)
//
//-----------------------------------------------------------------------------


//----------------------------------------------------------------------------
// Provider-specific Class Ids
//

#if SQLNCLI_VER >= 1000

extern const GUID OLEDBDECLSPEC CLSID_SQLNCLI10                = {0x8F4A6B68L,0x4F36,0x4e3c,{0xBE,0x81,0xBC,0x7C,0xA4,0xE9,0xC4,0x5C}};
extern const GUID OLEDBDECLSPEC CLSID_SQLNCLI10_ERROR          = {0x53F9C3BCL,0x275F,0x4FA5,{0xB3,0xE6,0x25,0xED,0xCD,0x51,0x20,0x23}};
extern const GUID OLEDBDECLSPEC CLSID_SQLNCLI10_ENUMERATOR     = {0x91E4F2A5L,0x1B07,0x45f6,{0x86,0xBF,0x92,0x03,0xC7,0xC7,0x2B,0xE3}};

#endif

extern const GUID OLEDBDECLSPEC CLSID_SQLNCLI                = {0x85ecafccL,0xbdd9,0x4b03,{0x97,0xa8,0xfa,0x65,0xcb,0xe3,0x85,0x9b}};
extern const GUID OLEDBDECLSPEC CLSID_SQLNCLI_ERROR          = {0xe8bc0a7aL,0xea71,0x4263,{0x8c,0xda,0x94,0xf3,0x88,0xb8,0xed,0x10}};
extern const GUID OLEDBDECLSPEC CLSID_SQLNCLI_ENUMERATOR     = {0x4898ad37L,0xfe05,0x42df,{0x92,0xf9,0xe8,0x57,0xdd,0xfe,0xe7,0x30}};
extern const GUID OLEDBDECLSPEC CLSID_ROWSET_TVP             = {0xc7ef28d5L,0x7bee,0x443f,{0x86,0xda,0xe3,0x98,0x4f,0xcd,0x4d,0xf9}};

//----------------------------------------------------------------------------
// Provider-specific Interface Ids
//
#ifndef  _SQLOLEDB_H_
extern const GUID OLEDBDECLSPEC IID_ISQLServerErrorInfo      = {0x5cf4ca12,0xef21,0x11d0,{0x97,0xe7,0x0,0xc0,0x4f,0xc2,0xad,0x98}};
extern const GUID OLEDBDECLSPEC IID_IRowsetFastLoad          = {0x5cf4ca13,0xef21,0x11d0,{0x97,0xe7,0x0,0xc0,0x4f,0xc2,0xad,0x98}};
extern const GUID OLEDBDECLSPEC IID_IUMSInitialize           = {0x5cf4ca14,0xef21,0x11d0,{0x97,0xe7,0x0,0xc0,0x4f,0xc2,0xad,0x98}};
extern const GUID OLEDBDECLSPEC IID_ISchemaLock              = {0x4c2389fb,0x2511,0x11d4,{0xb2,0x58,0x0,0xc0,0x4f,0x79,0x71,0xce}};
extern const GUID OLEDBDECLSPEC IID_ISQLXMLHelper            = {0xd22a7678L,0xf860,0x40cd,{0xa5,0x67,0x15,0x63,0xde,0xb4,0x6d,0x49}};
#endif //_SQLOLEDB_H_
extern const GUID OLEDBDECLSPEC IID_ISSAbort                 = {0x5cf4ca15,0xef21,0x11d0,{0x97,0xe7,0x0,0xc0,0x4f,0xc2,0xad,0x98}};
extern const GUID OLEDBDECLSPEC IID_IBCPSession              = {0x88352D80,0x42D1,0x42f0,{0xA1,0x70,0xAB,0x0F,0x8B,0x45,0xB9,0x39}};
extern const GUID OLEDBDECLSPEC IID_ISSCommandWithParameters = {0xeec30162,0x6087,0x467c,{0xb9,0x95,0x7c,0x52,0x3c,0xe9,0x65,0x61}};
extern const GUID OLEDBDECLSPEC IID_ISSAsynchStatus          = {0x1FF1F743,0x8BB0, 0x4c00,{0xAC,0xC4,0xC1,0x0E,0x43,0xB0,0x8F,0xC1}};


//----------------------------------------------------------------------------
// Provider-specific schema rowsets
//
#ifndef  _SQLOLEDB_H_
extern const GUID OLEDBDECLSPEC DBSCHEMA_LINKEDSERVERS               = {0x9093caf4,0x2eac,0x11d1,{0x98,0x9,0x0,0xc0,0x4f,0xc2,0xad,0x98}};
#endif //_SQLOLEDB_H_
extern const GUID OLEDBDECLSPEC DBSCHEMA_SQL_ASSEMBLIES              = {0x7c1112c8, 0xc2d3, 0x4f6e, {0x94, 0x9a, 0x98, 0x3d, 0x38, 0xa5, 0x8f, 0x46}};
extern const GUID OLEDBDECLSPEC DBSCHEMA_SQL_ASSEMBLY_DEPENDENCIES   = {0xcb0f837b, 0x974c, 0x41b8, {0x90, 0x9d, 0x64, 0x9c, 0xaf, 0x45, 0xad, 0x2f}};
extern const GUID OLEDBDECLSPEC DBSCHEMA_SQL_USER_TYPES              = {0xf1198bd8, 0xa424, 0x4ea3, {0x8d, 0x4c, 0x60, 0x7e, 0xee, 0x2b, 0xab, 0x60}};
extern const GUID OLEDBDECLSPEC DBSCHEMA_XML_COLLECTIONS             = {0x56bfad8c, 0x6e8f, 0x480d, {0x91, 0xde, 0x35, 0x16, 0xd9, 0x9a, 0x5d, 0x10}};
extern const GUID OLEDBDECLSPEC DBSCHEMA_SQL_TABLE_TYPES             = {0x4e26cde7, 0xaaa4, 0x41ed, {0x93, 0xdd, 0x37, 0x6e, 0x6d, 0x40, 0x9c, 0x17}};
extern const GUID OLEDBDECLSPEC DBSCHEMA_SQL_TABLE_TYPE_PRIMARY_KEYS = {0x9738faea, 0x31e8, 0x4f63, {0xae,  0xd, 0x61, 0x33, 0x16, 0x41, 0x8c, 0xdd}};
extern const GUID OLEDBDECLSPEC DBSCHEMA_SQL_TABLE_TYPE_COLUMNS      = {0xa663d94b, 0xddf7, 0x4a7f, {0xa5, 0x37, 0xd6, 0x1f, 0x12, 0x36, 0x5d, 0x7c}};
extern const GUID OLEDBDECLSPEC DBSCHEMA_COLUMNS_EXTENDED            = {0x66462f01, 0x633a, 0x44d9, {0xb0, 0xd0, 0xfe, 0x66, 0xf2, 0x1a, 0x0d, 0x24}};
extern const GUID OLEDBDECLSPEC DBSCHEMA_SPARSE_COLUMN_SET           = {0x31a4837c, 0xf9ff, 0x405f, {0x89, 0x82, 0x02, 0x19, 0xaa, 0xaa, 0x4a, 0x12}};


#ifndef CRESTRICTIONS_DBSCHEMA_LINKEDSERVERS
#define CRESTRICTIONS_DBSCHEMA_LINKEDSERVERS    1
#endif

#ifndef CRESTRICTIONS_DBSCHEMA_ASSEMBLIES
#define CRESTRICTIONS_DBSCHEMA_SQL_ASSEMBLIES       4
#endif

#ifndef CRESTRICTIONS_DBSCHEMA_ASSEMBLY_DEPENDENCIES
#define CRESTRICTIONS_DBSCHEMA_SQL_ASSEMBLY_DEPENDENCIES 4
#endif

#ifndef CRESTRICTIONS_DBSCHEMA_USER_TYPES
#define CRESTRICTIONS_DBSCHEMA_SQL_USER_TYPES   3
#endif

#ifndef CRESTRICTIONS_DBSCHEMA_XML_COLLECTIONS
#define CRESTRICTIONS_DBSCHEMA_XML_COLLECTIONS   4
#endif

#ifndef CRESTRICTIONS_DBSCHEMA_SQL_TABLE_TYPES
#define CRESTRICTIONS_DBSCHEMA_SQL_TABLE_TYPES    3
#endif

#ifndef CRESTRICTIONS_DBSCHEMA_SQL_TABLE_TYPE_PRIMARY_KEYS
#define CRESTRICTIONS_DBSCHEMA_SQL_TABLE_TYPE_PRIMARY_KEYS    3
#endif

#ifndef CRESTRICTIONS_DBSCHEMA_SQL_TABLE_TYPE_COLUMNS
#define CRESTRICTIONS_DBSCHEMA_SQL_TABLE_TYPE_COLUMNS    4
#endif

#ifndef CRESTRICTIONS_DBSCHEMA_COLUMNS_EXTENDED
#define CRESTRICTIONS_DBSCHEMA_COLUMNS_EXTENDED    4
#endif

#ifndef CRESTRICTIONS_DBSCHEMA_SPARSE_COLUMN_SET
#define CRESTRICTIONS_DBSCHEMA_SPARSE_COLUMN_SET    4
#endif


//----------------------------------------------------------------------------
// Provider-specific property sets
//
#ifndef  _SQLOLEDB_H_
extern const GUID OLEDBDECLSPEC DBPROPSET_SQLSERVERDATASOURCE    = {0x28efaee4,0x2d2c,0x11d1,{0x98,0x7,0x0,0xc0,0x4f,0xc2,0xad,0x98}};
extern const GUID OLEDBDECLSPEC DBPROPSET_SQLSERVERDATASOURCEINFO= {0xdf10cb94,0x35f6,0x11d2,{0x9c,0x54,0x0,0xc0,0x4f,0x79,0x71,0xd3}};
extern const GUID OLEDBDECLSPEC DBPROPSET_SQLSERVERDBINIT        = {0x5cf4ca10,0xef21,0x11d0,{0x97,0xe7,0x0,0xc0,0x4f,0xc2,0xad,0x98}};
extern const GUID OLEDBDECLSPEC DBPROPSET_SQLSERVERROWSET        = {0x5cf4ca11,0xef21,0x11d0,{0x97,0xe7,0x0,0xc0,0x4f,0xc2,0xad,0x98}};
extern const GUID OLEDBDECLSPEC DBPROPSET_SQLSERVERSESSION       = {0x28efaee5,0x2d2c,0x11d1,{0x98,0x7,0x0,0xc0,0x4f,0xc2,0xad,0x98}};
extern const GUID OLEDBDECLSPEC DBPROPSET_SQLSERVERCOLUMN        = {0x3b63fb5e,0x3fbb,0x11d3,{0x9f,0x29,0x0,0xc0,0x4f,0x8e,0xe9,0xdc}};
extern const GUID OLEDBDECLSPEC DBPROPSET_SQLSERVERSTREAM        = {0x9f79c073,0x8a6d,0x4bca,{0xa8,0xa8,0xc9,0xb7,0x9a,0x9b,0x96,0x2d}};
#endif //_SQLOLEDB_H_
extern const GUID OLEDBDECLSPEC DBPROPSET_SQLSERVERPARAMETER     = {0xfee09128,0xa67d,0x47ea,{0x8d,0x40,0x24,0xa1,0xd4,0x73,0x7e,0x8d}};
extern const GUID OLEDBDECLSPEC DBPROPSET_SQLSERVERINDEX         = {0xE428B84E,0xA6B7,0x413a,{0x94,0x65,0x56,0x23,0x2E,0x0D,0x2B,0xEB}};
extern const GUID OLEDBDECLSPEC DBPROPSET_PARAMETERALL           = {0x2cd2b7d8,0xe7c2,0x4f6c,{0x9b,0x30,0x75,0xe2,0x58,0x46,0x10,0x97}};


//----------------------------------------------------------------------------
// Provider-specific columns for IColumnsRowset
//
#define DBCOLUMN_SS_X_GUID {0x627bd890,0xed54,0x11d2,{0xb9,0x94,0x0,0xc0,0x4f,0x8c,0xa8,0x2c}}
//
#ifndef  _SQLOLEDB_H_
extern const DBID OLEDBDECLSPEC DBCOLUMN_SS_COMPFLAGS        = {DBCOLUMN_SS_X_GUID, DBKIND_GUID_PROPID, (LPOLESTR)100};
extern const DBID OLEDBDECLSPEC DBCOLUMN_SS_SORTID           = {DBCOLUMN_SS_X_GUID, DBKIND_GUID_PROPID, (LPOLESTR)101};
extern const DBID OLEDBDECLSPEC DBCOLUMN_BASETABLEINSTANCE   = {DBCOLUMN_SS_X_GUID, DBKIND_GUID_PROPID, (LPOLESTR)102};
extern const DBID OLEDBDECLSPEC DBCOLUMN_SS_TDSCOLLATION     = {DBCOLUMN_SS_X_GUID, DBKIND_GUID_PROPID, (LPOLESTR)103};
#endif //_SQLOLEDB_H_
extern const DBID OLEDBDECLSPEC DBCOLUMN_BASESERVERNAME      = {DBCOLUMN_SS_X_GUID, DBKIND_GUID_PROPID, (LPOLESTR)104};
extern const DBID OLEDBDECLSPEC DBCOLUMN_SS_XML_SCHEMACOLLECTION_CATALOGNAME= {DBCOLUMN_SS_X_GUID, DBKIND_GUID_PROPID, (LPOLESTR)105};
extern const DBID OLEDBDECLSPEC DBCOLUMN_SS_XML_SCHEMACOLLECTION_SCHEMANAME = {DBCOLUMN_SS_X_GUID, DBKIND_GUID_PROPID, (LPOLESTR)106};
extern const DBID OLEDBDECLSPEC DBCOLUMN_SS_XML_SCHEMACOLLECTIONNAME        = {DBCOLUMN_SS_X_GUID, DBKIND_GUID_PROPID, (LPOLESTR)107};
extern const DBID OLEDBDECLSPEC DBCOLUMN_SS_UDT_CATALOGNAME  = {DBCOLUMN_SS_X_GUID, DBKIND_GUID_PROPID, (LPOLESTR)108};
extern const DBID OLEDBDECLSPEC DBCOLUMN_SS_UDT_SCHEMANAME   = {DBCOLUMN_SS_X_GUID, DBKIND_GUID_PROPID, (LPOLESTR)109};
extern const DBID OLEDBDECLSPEC DBCOLUMN_SS_UDT_NAME         = {DBCOLUMN_SS_X_GUID, DBKIND_GUID_PROPID, (LPOLESTR)110};
extern const DBID OLEDBDECLSPEC DBCOLUMN_SS_ASSEMBLY_TYPENAME= {DBCOLUMN_SS_X_GUID, DBKIND_GUID_PROPID, (LPOLESTR)111};

// OLEDB part of SQL Server Native Client header - end here!
#endif // defined(_SQLNCLI_OLEDB_) || !defined(_SQLNCLI_ODBC_)

// ODBC part of SQL Server Native Client header - begin here!
#if defined(_SQLNCLI_ODBC_) || !defined(_SQLNCLI_OLEDB_)
#ifdef ODBCVER

// max SQL Server identifier length
#define SQL_MAX_SQLSERVERNAME                       128

// SQLSetConnectAttr driver specific defines.
// Microsoft has 1200 thru 1249 reserved for Microsoft SQL Server Native Client driver usage.
// Connection attributes
#define SQL_COPT_SS_BASE                                1200
#define SQL_COPT_SS_REMOTE_PWD                          (SQL_COPT_SS_BASE+1) // dbrpwset SQLSetConnectOption only
#define SQL_COPT_SS_USE_PROC_FOR_PREP                   (SQL_COPT_SS_BASE+2) // Use create proc for SQLPrepare
#define SQL_COPT_SS_INTEGRATED_SECURITY                 (SQL_COPT_SS_BASE+3) // Force integrated security on login
#define SQL_COPT_SS_PRESERVE_CURSORS                    (SQL_COPT_SS_BASE+4) // Preserve server cursors after SQLTransact
#define SQL_COPT_SS_USER_DATA                           (SQL_COPT_SS_BASE+5) // dbgetuserdata/dbsetuserdata
#define SQL_COPT_SS_ENLIST_IN_DTC                       SQL_ATTR_ENLIST_IN_DTC // Enlist in a DTC transaction
#define SQL_COPT_SS_ENLIST_IN_XA                        SQL_ATTR_ENLIST_IN_XA // Enlist in a XA transaction
#define SQL_COPT_SS_FALLBACK_CONNECT                    (SQL_COPT_SS_BASE+10) // Enables FallBack connections
#define SQL_COPT_SS_PERF_DATA                           (SQL_COPT_SS_BASE+11) // Used to access SQL Server ODBC driver performance data
#define SQL_COPT_SS_PERF_DATA_LOG                       (SQL_COPT_SS_BASE+12) // Used to set the logfile name for the Performance data
#define SQL_COPT_SS_PERF_QUERY_INTERVAL                 (SQL_COPT_SS_BASE+13) // Used to set the query logging threshold in milliseconds.
#define SQL_COPT_SS_PERF_QUERY_LOG                      (SQL_COPT_SS_BASE+14) // Used to set the logfile name for saving queryies.
#define SQL_COPT_SS_PERF_QUERY                          (SQL_COPT_SS_BASE+15) // Used to start and stop query logging.
#define SQL_COPT_SS_PERF_DATA_LOG_NOW                   (SQL_COPT_SS_BASE+16) // Used to make a statistics log entry to disk.
#define SQL_COPT_SS_QUOTED_IDENT                        (SQL_COPT_SS_BASE+17) // Enable/Disable Quoted Identifiers
#define SQL_COPT_SS_ANSI_NPW                            (SQL_COPT_SS_BASE+18) // Enable/Disable ANSI NULL, Padding and Warnings
#define SQL_COPT_SS_BCP                                 (SQL_COPT_SS_BASE+19) // Allow BCP usage on connection
#define SQL_COPT_SS_TRANSLATE                           (SQL_COPT_SS_BASE+20) // Perform code page translation
#define SQL_COPT_SS_ATTACHDBFILENAME                    (SQL_COPT_SS_BASE+21) // File name to be attached as a database
#define SQL_COPT_SS_CONCAT_NULL                         (SQL_COPT_SS_BASE+22) // Enable/Disable CONCAT_NULL_YIELDS_NULL
#define SQL_COPT_SS_ENCRYPT                             (SQL_COPT_SS_BASE+23) // Allow strong encryption for data
#define SQL_COPT_SS_MARS_ENABLED                        (SQL_COPT_SS_BASE+24) // Multiple active result set per connection
#define SQL_COPT_SS_FAILOVER_PARTNER                    (SQL_COPT_SS_BASE+25) // Failover partner server
#define SQL_COPT_SS_OLDPWD                              (SQL_COPT_SS_BASE+26) // Old Password, used when changing password during login
#define SQL_COPT_SS_TXN_ISOLATION                       (SQL_COPT_SS_BASE+27) // Used to set/get any driver-specific or ODBC-defined TXN iso level
#define SQL_COPT_SS_TRUST_SERVER_CERTIFICATE            (SQL_COPT_SS_BASE+28) // Trust server certificate
#define SQL_COPT_SS_SERVER_SPN                          (SQL_COPT_SS_BASE+29) // Server SPN
#define SQL_COPT_SS_FAILOVER_PARTNER_SPN                (SQL_COPT_SS_BASE+30) // Failover partner server SPN
#define SQL_COPT_SS_INTEGRATED_AUTHENTICATION_METHOD    (SQL_COPT_SS_BASE+31) // The integrated authentication method used for the connection
#define SQL_COPT_SS_MUTUALLY_AUTHENTICATED              (SQL_COPT_SS_BASE+32) // Used to decide if the connection is mutually authenticated
#define SQL_COPT_SS_MAX_USED                            SQL_COPT_SS_MUTUALLY_AUTHENTICATED
// Define old names
#define SQL_REMOTE_PWD                              SQL_COPT_SS_REMOTE_PWD
#define SQL_USE_PROCEDURE_FOR_PREPARE               SQL_COPT_SS_USE_PROC_FOR_PREP
#define SQL_INTEGRATED_SECURITY                     SQL_COPT_SS_INTEGRATED_SECURITY
#define SQL_PRESERVE_CURSORS                        SQL_COPT_SS_PRESERVE_CURSORS

// SQLSetStmtAttr SQL Server Native Client driver specific defines.
// Statement attributes
#define SQL_SOPT_SS_BASE                            1225
#define SQL_SOPT_SS_TEXTPTR_LOGGING                 (SQL_SOPT_SS_BASE+0) // Text pointer logging
#define SQL_SOPT_SS_CURRENT_COMMAND                 (SQL_SOPT_SS_BASE+1) // dbcurcmd SQLGetStmtOption only
#define SQL_SOPT_SS_HIDDEN_COLUMNS                  (SQL_SOPT_SS_BASE+2) // Expose FOR BROWSE hidden columns
#define SQL_SOPT_SS_NOBROWSETABLE                   (SQL_SOPT_SS_BASE+3) // Set NOBROWSETABLE option
#define SQL_SOPT_SS_REGIONALIZE                     (SQL_SOPT_SS_BASE+4) // Regionalize output character conversions
#define SQL_SOPT_SS_CURSOR_OPTIONS                  (SQL_SOPT_SS_BASE+5) // Server cursor options
#define SQL_SOPT_SS_NOCOUNT_STATUS                  (SQL_SOPT_SS_BASE+6) // Real vs. Not Real row count indicator
#define SQL_SOPT_SS_DEFER_PREPARE                   (SQL_SOPT_SS_BASE+7) // Defer prepare until necessary
#define SQL_SOPT_SS_QUERYNOTIFICATION_TIMEOUT       (SQL_SOPT_SS_BASE+8) // Notification timeout
#define SQL_SOPT_SS_QUERYNOTIFICATION_MSGTEXT       (SQL_SOPT_SS_BASE+9) // Notification message text
#define SQL_SOPT_SS_QUERYNOTIFICATION_OPTIONS       (SQL_SOPT_SS_BASE+10)// SQL service broker name
#define SQL_SOPT_SS_PARAM_FOCUS                     (SQL_SOPT_SS_BASE+11)// Direct subsequent calls to parameter related methods to set properties on constituent columns/parameters of container types
#define SQL_SOPT_SS_NAME_SCOPE                      (SQL_SOPT_SS_BASE+12)// Sets name scope for subsequent catalog function calls
#define SQL_SOPT_SS_MAX_USED                        SQL_SOPT_SS_NAME_SCOPE
// Define old names
#define SQL_TEXTPTR_LOGGING                         SQL_SOPT_SS_TEXTPTR_LOGGING
#define SQL_COPT_SS_BASE_EX                         1240
#define SQL_COPT_SS_BROWSE_CONNECT                  (SQL_COPT_SS_BASE_EX+1) // Browse connect mode of operation
#define SQL_COPT_SS_BROWSE_SERVER                   (SQL_COPT_SS_BASE_EX+2) // Single Server browse request.
#define SQL_COPT_SS_WARN_ON_CP_ERROR                (SQL_COPT_SS_BASE_EX+3) // Issues warning when data from the server had a loss during code page conversion.
#define SQL_COPT_SS_CONNECTION_DEAD                 (SQL_COPT_SS_BASE_EX+4) // dbdead SQLGetConnectOption only. It will try to ping the server. Expensive connection check
#define SQL_COPT_SS_BROWSE_CACHE_DATA               (SQL_COPT_SS_BASE_EX+5) // Determines if we should cache browse info. Used when returned buffer is greater then ODBC limit (32K)
#define SQL_COPT_SS_RESET_CONNECTION                (SQL_COPT_SS_BASE_EX+6) // When this option is set, we will perform connection reset on next packet
#define SQL_COPT_SS_EX_MAX_USED                     SQL_COPT_SS_RESET_CONNECTION

// SQLColAttributes driver specific defines.
// SQLSetDescField/SQLGetDescField driver specific defines.
// Microsoft has 1200 thru 1249 reserved for Microsoft SQL Server Native Client driver usage.
#define SQL_CA_SS_BASE                              1200
#define SQL_CA_SS_COLUMN_SSTYPE                     (SQL_CA_SS_BASE+0)   //  dbcoltype/dbalttype
#define SQL_CA_SS_COLUMN_UTYPE                      (SQL_CA_SS_BASE+1)   //  dbcolutype/dbaltutype
#define SQL_CA_SS_NUM_ORDERS                        (SQL_CA_SS_BASE+2)   //  dbnumorders
#define SQL_CA_SS_COLUMN_ORDER                      (SQL_CA_SS_BASE+3)   //  dbordercol
#define SQL_CA_SS_COLUMN_VARYLEN                    (SQL_CA_SS_BASE+4)   //  dbvarylen
#define SQL_CA_SS_NUM_COMPUTES                      (SQL_CA_SS_BASE+5)   //  dbnumcompute
#define SQL_CA_SS_COMPUTE_ID                        (SQL_CA_SS_BASE+6)   //  dbnextrow status return
#define SQL_CA_SS_COMPUTE_BYLIST                    (SQL_CA_SS_BASE+7)   //  dbbylist
#define SQL_CA_SS_COLUMN_ID                         (SQL_CA_SS_BASE+8)   //  dbaltcolid
#define SQL_CA_SS_COLUMN_OP                         (SQL_CA_SS_BASE+9)   //  dbaltop
#define SQL_CA_SS_COLUMN_SIZE                       (SQL_CA_SS_BASE+10)  //  dbcollen
#define SQL_CA_SS_COLUMN_HIDDEN                     (SQL_CA_SS_BASE+11)  //  Column is hidden (FOR BROWSE)
#define SQL_CA_SS_COLUMN_KEY                        (SQL_CA_SS_BASE+12)  //  Column is key column (FOR BROWSE)
//#define SQL_DESC_BASE_COLUMN_NAME_OLD             (SQL_CA_SS_BASE+13)  //  This is defined at another location.
#define SQL_CA_SS_COLUMN_COLLATION                  (SQL_CA_SS_BASE+14)  //  Column collation (only for chars)
#define SQL_CA_SS_VARIANT_TYPE                      (SQL_CA_SS_BASE+15)
#define SQL_CA_SS_VARIANT_SQL_TYPE                  (SQL_CA_SS_BASE+16)
#define SQL_CA_SS_VARIANT_SERVER_TYPE               (SQL_CA_SS_BASE+17)

// XML, CLR UDT, and table valued parameter related metadata
#define SQL_CA_SS_UDT_CATALOG_NAME                  (SQL_CA_SS_BASE+18) //  UDT catalog name
#define SQL_CA_SS_UDT_SCHEMA_NAME                   (SQL_CA_SS_BASE+19) //  UDT schema name
#define SQL_CA_SS_UDT_TYPE_NAME                     (SQL_CA_SS_BASE+20) //  UDT type name
#define SQL_CA_SS_UDT_ASSEMBLY_TYPE_NAME            (SQL_CA_SS_BASE+21) //  Qualified name of the assembly containing the UDT class
#define SQL_CA_SS_XML_SCHEMACOLLECTION_CATALOG_NAME (SQL_CA_SS_BASE+22) //  Name of the catalog that contains XML Schema collection
#define SQL_CA_SS_XML_SCHEMACOLLECTION_SCHEMA_NAME  (SQL_CA_SS_BASE+23) //  Name of the schema that contains XML Schema collection
#define SQL_CA_SS_XML_SCHEMACOLLECTION_NAME         (SQL_CA_SS_BASE+24) //  Name of the XML Schema collection
#define SQL_CA_SS_CATALOG_NAME                      (SQL_CA_SS_BASE+25) //  Catalog name
#define SQL_CA_SS_SCHEMA_NAME                       (SQL_CA_SS_BASE+26) //  Schema name
#define SQL_CA_SS_TYPE_NAME                         (SQL_CA_SS_BASE+27) //  Type name

// table valued parameter related metadata
#define SQL_CA_SS_COLUMN_COMPUTED                   (SQL_CA_SS_BASE+29) //  column is computed
#define SQL_CA_SS_COLUMN_IN_UNIQUE_KEY              (SQL_CA_SS_BASE+30) //  column is part of a unique key
#define SQL_CA_SS_COLUMN_SORT_ORDER                 (SQL_CA_SS_BASE+31) //  column sort order
#define SQL_CA_SS_COLUMN_SORT_ORDINAL               (SQL_CA_SS_BASE+32) //  column sort ordinal
#define SQL_CA_SS_COLUMN_HAS_DEFAULT_VALUE          (SQL_CA_SS_BASE+33) //  column has default value for all rows of the table valued parameter

// sparse column related metadata
#define SQL_CA_SS_IS_COLUMN_SET                     (SQL_CA_SS_BASE+34) //  column is a column-set column for sparse columns

// Legacy datetime related metadata
#define SQL_CA_SS_SERVER_TYPE                       (SQL_CA_SS_BASE+35) //  column type to send on the wire for datetime types

#define SQL_CA_SS_MAX_USED                          (SQL_CA_SS_BASE+36)

// Defines returned by SQL_ATTR_CURSOR_TYPE/SQL_CURSOR_TYPE
#define SQL_CURSOR_FAST_FORWARD_ONLY        8            //  Only returned by SQLGetStmtAttr/Option
// Defines for use with SQL_COPT_SS_USE_PROC_FOR_PREP
#define SQL_UP_OFF                          0L           //  Procedures won't be used for prepare
#define SQL_UP_ON                           1L           //  Procedures will be used for prepare
#define SQL_UP_ON_DROP                      2L           //  Temp procedures will be explicitly dropped
#define SQL_UP_DEFAULT                      SQL_UP_ON
// Defines for use with SQL_COPT_SS_INTEGRATED_SECURITY - Pre-Connect Option only
#define SQL_IS_OFF                          0L           //  Integrated security isn't used
#define SQL_IS_ON                           1L           //  Integrated security is used
#define SQL_IS_DEFAULT                      SQL_IS_OFF
// Defines for use with SQL_COPT_SS_PRESERVE_CURSORS
#define SQL_PC_OFF                          0L           //  Cursors are closed on SQLTransact
#define SQL_PC_ON                           1L           //  Cursors remain open on SQLTransact
#define SQL_PC_DEFAULT                      SQL_PC_OFF
// Defines for use with SQL_COPT_SS_USER_DATA
#define SQL_UD_NOTSET                       NULL         //  No user data pointer set
// Defines for use with SQL_COPT_SS_TRANSLATE
#define SQL_XL_OFF                          0L           //  Code page translation is not performed
#define SQL_XL_ON                           1L           //  Code page translation is performed
#define SQL_XL_DEFAULT                      SQL_XL_ON
// Defines for use with SQL_COPT_SS_FALLBACK_CONNECT - Pre-Connect Option only
#define SQL_FB_OFF                          0L           //  FallBack connections are disabled
#define SQL_FB_ON                           1L           //  FallBack connections are enabled
#define SQL_FB_DEFAULT                      SQL_FB_OFF
// Defines for use with SQL_COPT_SS_BCP - Pre-Connect Option only
#define SQL_BCP_OFF                         0L           //  BCP is not allowed on connection
#define SQL_BCP_ON                          1L           //  BCP is allowed on connection
#define SQL_BCP_DEFAULT                     SQL_BCP_OFF
// Defines for use with SQL_COPT_SS_QUOTED_IDENT
#define SQL_QI_OFF                          0L           //  Quoted identifiers are enable
#define SQL_QI_ON                           1L           //  Quoted identifiers are disabled
#define SQL_QI_DEFAULT                      SQL_QI_ON
// Defines for use with SQL_COPT_SS_ANSI_NPW - Pre-Connect Option only
#define SQL_AD_OFF                          0L           //  ANSI NULLs, Padding and Warnings are enabled
#define SQL_AD_ON                           1L           //  ANSI NULLs, Padding and Warnings are disabled
#define SQL_AD_DEFAULT                      SQL_AD_ON
// Defines for use with SQL_COPT_SS_CONCAT_NULL - Pre-Connect Option only
#define SQL_CN_OFF                          0L           //  CONCAT_NULL_YIELDS_NULL is off
#define SQL_CN_ON                           1L           //  CONCAT_NULL_YIELDS_NULL is on
#define SQL_CN_DEFAULT                      SQL_CN_ON
// Defines for use with SQL_SOPT_SS_TEXTPTR_LOGGING
#define SQL_TL_OFF                          0L           //  No logging on text pointer ops
#define SQL_TL_ON                           1L           //  Logging occurs on text pointer ops
#define SQL_TL_DEFAULT                      SQL_TL_ON
// Defines for use with SQL_SOPT_SS_HIDDEN_COLUMNS
#define SQL_HC_OFF                          0L           //  FOR BROWSE columns are hidden
#define SQL_HC_ON                           1L           //  FOR BROWSE columns are exposed
#define SQL_HC_DEFAULT                      SQL_HC_OFF
// Defines for use with SQL_SOPT_SS_NOBROWSETABLE
#define SQL_NB_OFF                          0L           //  NO_BROWSETABLE is off
#define SQL_NB_ON                           1L           //  NO_BROWSETABLE is on
#define SQL_NB_DEFAULT                      SQL_NB_OFF
// Defines for use with SQL_SOPT_SS_REGIONALIZE
#define SQL_RE_OFF                          0L           //  No regionalization occurs on output character conversions
#define SQL_RE_ON                           1L           //  Regionalization occurs on output character conversions
#define SQL_RE_DEFAULT                      SQL_RE_OFF
// Defines for use with SQL_SOPT_SS_CURSOR_OPTIONS
#define SQL_CO_OFF                          0L           //  Clear all cursor options
#define SQL_CO_FFO                          1L           //  Fast-forward cursor will be used
#define SQL_CO_AF                           2L           //  Autofetch on cursor open
#define SQL_CO_FFO_AF                       (SQL_CO_FFO|SQL_CO_AF)  //  Fast-forward cursor with autofetch
#define SQL_CO_FIREHOSE_AF                  4L           //  Auto fetch on fire-hose cursors
#define SQL_CO_DEFAULT                      SQL_CO_OFF
//SQL_SOPT_SS_NOCOUNT_STATUS 
#define SQL_NC_OFF                          0L
#define SQL_NC_ON                           1L
//SQL_SOPT_SS_DEFER_PREPARE 
#define SQL_DP_OFF                          0L
#define SQL_DP_ON                           1L
//SQL_SOPT_SS_NAME_SCOPE
#define SQL_SS_NAME_SCOPE_TABLE             0L
#define SQL_SS_NAME_SCOPE_TABLE_TYPE        1L
#define SQL_SS_NAME_SCOPE_EXTENDED          2L
#define SQL_SS_NAME_SCOPE_SPARSE_COLUMN_SET 3L
#define SQL_SS_NAME_SCOPE_DEFAULT           SQL_SS_NAME_SCOPE_TABLE
//SQL_COPT_SS_ENCRYPT 
#define SQL_EN_OFF                          0L
#define SQL_EN_ON                           1L
//SQL_COPT_SS_TRUST_SERVER_CERTIFICATE
#define SQL_TRUST_SERVER_CERTIFICATE_NO     0L
#define SQL_TRUST_SERVER_CERTIFICATE_YES    1L
//SQL_COPT_SS_BROWSE_CONNECT 
#define SQL_MORE_INFO_NO                    0L
#define SQL_MORE_INFO_YES                   1L
//SQL_COPT_SS_BROWSE_CACHE_DATA 
#define SQL_CACHE_DATA_NO                   0L
#define SQL_CACHE_DATA_YES                  1L
//SQL_COPT_SS_RESET_CONNECTION 
#define SQL_RESET_YES                       1L
//SQL_COPT_SS_WARN_ON_CP_ERROR 
#define SQL_WARN_NO                         0L
#define SQL_WARN_YES                        1L
//SQL_COPT_SS_MARS_ENABLED 
#define SQL_MARS_ENABLED_NO                 0L
#define SQL_MARS_ENABLED_YES                1L
/* SQL_TXN_ISOLATION_OPTION bitmasks */
#define SQL_TXN_SS_SNAPSHOT                 0x00000020L

// The following are defines for SQL_CA_SS_COLUMN_SORT_ORDER
#define SQL_SS_ORDER_UNSPECIFIED            0L
#define SQL_SS_DESCENDING_ORDER             1L
#define SQL_SS_ASCENDING_ORDER              2L
#define SQL_SS_ORDER_DEFAULT                SQL_SS_ORDER_UNSPECIFIED

// Driver specific SQL data type defines.
// Microsoft has -150 thru -199 reserved for Microsoft SQL Server Native Client driver usage.
#define SQL_SS_VARIANT                      (-150)
#define SQL_SS_UDT                          (-151)
#define SQL_SS_XML                          (-152)
#define SQL_SS_TABLE                        (-153)
#define SQL_SS_TIME2                        (-154)
#define SQL_SS_TIMESTAMPOFFSET              (-155)

// Local types to be used with SQL_CA_SS_SERVER_TYPE
#define SQL_SS_TYPE_DEFAULT                         0L
#define SQL_SS_TYPE_SMALLDATETIME                   1L
#define SQL_SS_TYPE_DATETIME                        2L

#ifndef SQLNCLI_NO_BCP
// Define the symbol SQLNCLI_NO_BCP if you are not using BCP in your application
// and you want to exclude the BCP-related definitions in this header file.

// SQL Server Data Type defines.
// New types for SQL 6.0 and later servers
#define SQLTEXT                             0x23
#define SQLVARBINARY                        0x25
#define SQLINTN                             0x26
#define SQLVARCHAR                          0x27
#define SQLBINARY                           0x2d
#define SQLIMAGE                            0x22
#define SQLCHARACTER                        0x2f
#define SQLINT1                             0x30
#define SQLBIT                              0x32
#define SQLINT2                             0x34
#define SQLINT4                             0x38
#define SQLMONEY                            0x3c
#define SQLDATETIME                         0x3d
#define SQLFLT8                             0x3e
#define SQLFLTN                             0x6d
#define SQLMONEYN                           0x6e
#define SQLDATETIMN                         0x6f
#define SQLFLT4                             0x3b
#define SQLMONEY4                           0x7a
#define SQLDATETIM4                         0x3a
// New types for SQL 6.0 and later servers
#define SQLDECIMAL                          0x6a
#define SQLNUMERIC                          0x6c
// New types for SQL 7.0 and later servers
#define SQLUNIQUEID                         0x24
#define SQLBIGCHAR                          0xaf
#define SQLBIGVARCHAR                       0xa7
#define SQLBIGBINARY                        0xad
#define SQLBIGVARBINARY                     0xa5
#define SQLBITN                             0x68
#define SQLNCHAR                            0xef
#define SQLNVARCHAR                         0xe7
#define SQLNTEXT                            0x63
// New types for SQL 2000 and later servers
#define SQLINT8                             0x7f
#define SQLVARIANT                          0x62
// New types for SQL 2005 and later servers
#define SQLUDT                              0xf0
#define SQLXML                              0xf1
// New types for SQL 2008 and later servers
#define SQLTABLE                            0xf3
#define SQLDATEN                            0x28
#define SQLTIMEN                            0x29
#define SQLDATETIME2N                       0x2a
#define SQLDATETIMEOFFSETN                  0x2b
// Define old names
#define SQLDECIMALN                         0x6a
#define SQLNUMERICN                         0x6c
#endif // SQLNCLI_NO_BCP

// SQL_SS_LENGTH_UNLIMITED is used to describe the max length of
// VARCHAR(max), VARBINARY(max), NVARCHAR(max), and XML columns
#define SQL_SS_LENGTH_UNLIMITED             0

// User Data Type definitions.
// Returned by SQLColAttributes/SQL_CA_SS_COLUMN_UTYPE.
#define SQLudtBINARY                        3
#define SQLudtBIT                           16
#define SQLudtBITN                          0
#define SQLudtCHAR                          1
#define SQLudtDATETIM4                      22
#define SQLudtDATETIME                      12
#define SQLudtDATETIMN                      15
#define SQLudtDECML                         24
#define SQLudtDECMLN                        26
#define SQLudtFLT4                          23
#define SQLudtFLT8                          8
#define SQLudtFLTN                          14
#define SQLudtIMAGE                         20
#define SQLudtINT1                          5
#define SQLudtINT2                          6
#define SQLudtINT4                          7
#define SQLudtINTN                          13
#define SQLudtMONEY                         11
#define SQLudtMONEY4                        21
#define SQLudtMONEYN                        17
#define SQLudtNUM                           10
#define SQLudtNUMN                          25
#define SQLudtSYSNAME                       18
#define SQLudtTEXT                          19
#define SQLudtTIMESTAMP                     80
#define SQLudtUNIQUEIDENTIFIER              0
#define SQLudtVARBINARY                     4
#define SQLudtVARCHAR                       2
#define MIN_USER_DATATYPE                   256
// Aggregate operator types.
// Returned by SQLColAttributes/SQL_CA_SS_COLUMN_OP.
#define SQLAOPSTDEV                         0x30    // Standard deviation
#define SQLAOPSTDEVP                        0x31    // Standard deviation population
#define SQLAOPVAR                           0x32    // Variance
#define SQLAOPVARP                          0x33    // Variance population
#define SQLAOPCNT                           0x4b    // Count
#define SQLAOPSUM                           0x4d    // Sum
#define SQLAOPAVG                           0x4f    // Average
#define SQLAOPMIN                           0x51    // Min
#define SQLAOPMAX                           0x52    // Max
#define SQLAOPANY                           0x53    // Any
#define SQLAOPNOOP                          0x56    // None
// SQLGetInfo driver specific defines.
// Microsoft has 1151 thru 1200 reserved for Microsoft SQL Server Native Client driver usage.
#define SQL_INFO_SS_FIRST                   1199
#define SQL_INFO_SS_NETLIB_NAMEW            (SQL_INFO_SS_FIRST+0) //  dbprocinfo
#define SQL_INFO_SS_NETLIB_NAMEA            (SQL_INFO_SS_FIRST+1) //  dbprocinfo
#define SQL_INFO_SS_MAX_USED                SQL_INFO_SS_NETLIB_NAMEA
#ifdef UNICODE
#define SQL_INFO_SS_NETLIB_NAME             SQL_INFO_SS_NETLIB_NAMEW
#else
#define SQL_INFO_SS_NETLIB_NAME             SQL_INFO_SS_NETLIB_NAMEA
#endif
// SQLGetDiagField driver specific defines.
// Microsoft has -1150 thru -1199 reserved for Microsoft SQL Server Native Client driver usage.
#define SQL_DIAG_SS_BASE                    (-1150)
#define SQL_DIAG_SS_MSGSTATE                (SQL_DIAG_SS_BASE)
#define SQL_DIAG_SS_SEVERITY                (SQL_DIAG_SS_BASE-1)
#define SQL_DIAG_SS_SRVNAME                 (SQL_DIAG_SS_BASE-2)
#define SQL_DIAG_SS_PROCNAME                (SQL_DIAG_SS_BASE-3)
#define SQL_DIAG_SS_LINE                    (SQL_DIAG_SS_BASE-4)
// SQLGetDiagField/SQL_DIAG_DYNAMIC_FUNCTION_CODE driver specific defines.
// Microsoft has -200 thru -299 reserved for Microsoft SQL Server Native Client driver usage.
#define SQL_DIAG_DFC_SS_BASE                (-200)
#define SQL_DIAG_DFC_SS_ALTER_DATABASE      (SQL_DIAG_DFC_SS_BASE-0)
#define SQL_DIAG_DFC_SS_CHECKPOINT          (SQL_DIAG_DFC_SS_BASE-1)
#define SQL_DIAG_DFC_SS_CONDITION           (SQL_DIAG_DFC_SS_BASE-2)
#define SQL_DIAG_DFC_SS_CREATE_DATABASE     (SQL_DIAG_DFC_SS_BASE-3)
#define SQL_DIAG_DFC_SS_CREATE_DEFAULT      (SQL_DIAG_DFC_SS_BASE-4)
#define SQL_DIAG_DFC_SS_CREATE_PROCEDURE    (SQL_DIAG_DFC_SS_BASE-5)
#define SQL_DIAG_DFC_SS_CREATE_RULE         (SQL_DIAG_DFC_SS_BASE-6)
#define SQL_DIAG_DFC_SS_CREATE_TRIGGER      (SQL_DIAG_DFC_SS_BASE-7)
#define SQL_DIAG_DFC_SS_CURSOR_DECLARE      (SQL_DIAG_DFC_SS_BASE-8)
#define SQL_DIAG_DFC_SS_CURSOR_OPEN         (SQL_DIAG_DFC_SS_BASE-9)
#define SQL_DIAG_DFC_SS_CURSOR_FETCH        (SQL_DIAG_DFC_SS_BASE-10)
#define SQL_DIAG_DFC_SS_CURSOR_CLOSE        (SQL_DIAG_DFC_SS_BASE-11)
#define SQL_DIAG_DFC_SS_DEALLOCATE_CURSOR   (SQL_DIAG_DFC_SS_BASE-12)
#define SQL_DIAG_DFC_SS_DBCC                (SQL_DIAG_DFC_SS_BASE-13)
#define SQL_DIAG_DFC_SS_DISK                (SQL_DIAG_DFC_SS_BASE-14)
#define SQL_DIAG_DFC_SS_DROP_DATABASE       (SQL_DIAG_DFC_SS_BASE-15)
#define SQL_DIAG_DFC_SS_DROP_DEFAULT        (SQL_DIAG_DFC_SS_BASE-16)
#define SQL_DIAG_DFC_SS_DROP_PROCEDURE      (SQL_DIAG_DFC_SS_BASE-17)
#define SQL_DIAG_DFC_SS_DROP_RULE           (SQL_DIAG_DFC_SS_BASE-18)
#define SQL_DIAG_DFC_SS_DROP_TRIGGER        (SQL_DIAG_DFC_SS_BASE-19)
#define SQL_DIAG_DFC_SS_DUMP_DATABASE       (SQL_DIAG_DFC_SS_BASE-20)
#define SQL_DIAG_DFC_SS_BACKUP_DATABASE     (SQL_DIAG_DFC_SS_BASE-20)
#define SQL_DIAG_DFC_SS_DUMP_TABLE          (SQL_DIAG_DFC_SS_BASE-21)
#define SQL_DIAG_DFC_SS_DUMP_TRANSACTION    (SQL_DIAG_DFC_SS_BASE-22)
#define SQL_DIAG_DFC_SS_BACKUP_TRANSACTION  (SQL_DIAG_DFC_SS_BASE-22)
#define SQL_DIAG_DFC_SS_GOTO                (SQL_DIAG_DFC_SS_BASE-23)
#define SQL_DIAG_DFC_SS_INSERT_BULK         (SQL_DIAG_DFC_SS_BASE-24)
#define SQL_DIAG_DFC_SS_KILL                (SQL_DIAG_DFC_SS_BASE-25)
#define SQL_DIAG_DFC_SS_LOAD_DATABASE       (SQL_DIAG_DFC_SS_BASE-26)
#define SQL_DIAG_DFC_SS_RESTORE_DATABASE    (SQL_DIAG_DFC_SS_BASE-26)
#define SQL_DIAG_DFC_SS_LOAD_HEADERONLY     (SQL_DIAG_DFC_SS_BASE-27)
#define SQL_DIAG_DFC_SS_RESTORE_HEADERONLY  (SQL_DIAG_DFC_SS_BASE-27)
#define SQL_DIAG_DFC_SS_LOAD_TABLE          (SQL_DIAG_DFC_SS_BASE-28)
#define SQL_DIAG_DFC_SS_LOAD_TRANSACTION    (SQL_DIAG_DFC_SS_BASE-29)
#define SQL_DIAG_DFC_SS_RESTORE_TRANSACTION (SQL_DIAG_DFC_SS_BASE-29)
#define SQL_DIAG_DFC_SS_PRINT               (SQL_DIAG_DFC_SS_BASE-30)
#define SQL_DIAG_DFC_SS_RAISERROR           (SQL_DIAG_DFC_SS_BASE-31)
#define SQL_DIAG_DFC_SS_READTEXT            (SQL_DIAG_DFC_SS_BASE-32)
#define SQL_DIAG_DFC_SS_RECONFIGURE         (SQL_DIAG_DFC_SS_BASE-33)
#define SQL_DIAG_DFC_SS_RETURN              (SQL_DIAG_DFC_SS_BASE-34)
#define SQL_DIAG_DFC_SS_SELECT_INTO         (SQL_DIAG_DFC_SS_BASE-35)
#define SQL_DIAG_DFC_SS_SET                 (SQL_DIAG_DFC_SS_BASE-36)
#define SQL_DIAG_DFC_SS_SET_IDENTITY_INSERT (SQL_DIAG_DFC_SS_BASE-37)
#define SQL_DIAG_DFC_SS_SET_ROW_COUNT       (SQL_DIAG_DFC_SS_BASE-38)
#define SQL_DIAG_DFC_SS_SET_STATISTICS      (SQL_DIAG_DFC_SS_BASE-39)
#define SQL_DIAG_DFC_SS_SET_TEXTSIZE        (SQL_DIAG_DFC_SS_BASE-40)
#define SQL_DIAG_DFC_SS_SETUSER             (SQL_DIAG_DFC_SS_BASE-41)
#define SQL_DIAG_DFC_SS_SHUTDOWN            (SQL_DIAG_DFC_SS_BASE-42)
#define SQL_DIAG_DFC_SS_TRANS_BEGIN         (SQL_DIAG_DFC_SS_BASE-43)
#define SQL_DIAG_DFC_SS_TRANS_COMMIT        (SQL_DIAG_DFC_SS_BASE-44)
#define SQL_DIAG_DFC_SS_TRANS_PREPARE       (SQL_DIAG_DFC_SS_BASE-45)
#define SQL_DIAG_DFC_SS_TRANS_ROLLBACK      (SQL_DIAG_DFC_SS_BASE-46)
#define SQL_DIAG_DFC_SS_TRANS_SAVE          (SQL_DIAG_DFC_SS_BASE-47)
#define SQL_DIAG_DFC_SS_TRUNCATE_TABLE      (SQL_DIAG_DFC_SS_BASE-48)
#define SQL_DIAG_DFC_SS_UPDATE_STATISTICS   (SQL_DIAG_DFC_SS_BASE-49)
#define SQL_DIAG_DFC_SS_UPDATETEXT          (SQL_DIAG_DFC_SS_BASE-50)
#define SQL_DIAG_DFC_SS_USE                 (SQL_DIAG_DFC_SS_BASE-51)
#define SQL_DIAG_DFC_SS_WAITFOR             (SQL_DIAG_DFC_SS_BASE-52)
#define SQL_DIAG_DFC_SS_WRITETEXT           (SQL_DIAG_DFC_SS_BASE-53)
#define SQL_DIAG_DFC_SS_DENY                (SQL_DIAG_DFC_SS_BASE-54)
#define SQL_DIAG_DFC_SS_SET_XCTLVL          (SQL_DIAG_DFC_SS_BASE-55)
#define SQL_DIAG_DFC_SS_MERGE               (SQL_DIAG_DFC_SS_BASE-56)

// Severity codes for SQL_DIAG_SS_SEVERITY
#define EX_ANY          0
#define EX_INFO         10
#define EX_MAXISEVERITY EX_INFO
#define EX_MISSING      11
#define EX_TYPE         12
#define EX_DEADLOCK     13
#define EX_PERMIT       14
#define EX_SYNTAX       15
#define EX_USER         16
#define EX_RESOURCE     17
#define EX_INTOK        18
#define MAXUSEVERITY    EX_INTOK
#define EX_LIMIT        19
#define EX_CMDFATAL     20
#define MINFATALERR     EX_CMDFATAL
#define EX_DBFATAL      21
#define EX_TABCORRUPT   22
#define EX_DBCORRUPT    23
#define EX_HARDWARE     24
#define EX_CONTROL      25
// Internal server datatypes - used when binding to SQL_C_BINARY
#ifndef MAXNUMERICLEN   // Resolve ODS/DBLib conflicts
// DB-Library datatypes
#define DBMAXCHAR       (8000+1)                    // Max length of DBVARBINARY and DBVARCHAR, etc. +1 for zero byte
#define MAXNAME         (SQL_MAX_SQLSERVERNAME+1)   // Max server identifier length including zero byte
#ifdef UNICODE
typedef wchar_t  DBCHAR;
#else
typedef char DBCHAR;

#endif
typedef short SQLSMALLINT;

typedef unsigned short SQLUSMALLINT;

typedef long SQLINTEGER;

typedef unsigned long SQLUINTEGER;

typedef unsigned char DBBINARY;

typedef unsigned char DBTINYINT;

typedef short DBSMALLINT;

typedef unsigned short DBUSMALLINT;

typedef double DBFLT8;

typedef unsigned char DBBIT;

typedef unsigned char DBBOOL;

typedef float DBFLT4;

typedef DBFLT4 DBREAL;

typedef UINT DBUBOOL;

typedef struct dbmoney
    {
    LONG mnyhigh;
    ULONG mnylow;
    } 	DBMONEY;

typedef struct dbdatetime
    {
    LONG dtdays;
    ULONG dttime;
    } 	DBDATETIME;

typedef struct dbdatetime4
    {
    USHORT numdays;
    USHORT nummins;
    } 	DBDATETIM4;

typedef LONG DBMONEY4;

#include <pshpack8.h>    // 8-byte structure packing

// New Date Time Structures
// New Structure for TIME2
typedef struct tagSS_TIME2_STRUCT
    {
    SQLUSMALLINT hour;
    SQLUSMALLINT minute;
    SQLUSMALLINT second;
    SQLUINTEGER fraction;
    } 	SQL_SS_TIME2_STRUCT;

// New Structure for TIMESTAMPOFFSET
typedef struct tagSS_TIMESTAMPOFFSET_STRUCT
    {
    SQLSMALLINT year;
    SQLUSMALLINT month;
    SQLUSMALLINT day;
    SQLUSMALLINT hour;
    SQLUSMALLINT minute;
    SQLUSMALLINT second;
    SQLUINTEGER fraction;
    SQLSMALLINT timezone_hour;
    SQLSMALLINT timezone_minute;
    } 	SQL_SS_TIMESTAMPOFFSET_STRUCT;

#include <poppack.h>     // restore original structure packing

// Money value *10,000
#define DBNUM_PREC_TYPE BYTE
#define DBNUM_SCALE_TYPE BYTE
#define DBNUM_VAL_TYPE BYTE

#if (ODBCVER < 0x0300)
#define MAXNUMERICLEN 16
typedef struct dbnumeric         // Internal representation of NUMERIC data type
{
    DBNUM_PREC_TYPE precision;   // Precision
    DBNUM_SCALE_TYPE scale;      // Scale
    BYTE sign;                   // Sign (1 if positive, 0 if negative)
    DBNUM_VAL_TYPE val[MAXNUMERICLEN];// Value
} DBNUMERIC;
typedef DBNUMERIC DBDECIMAL;// Internal representation of DECIMAL data type
#else //  Use ODBC 3.0 definitions since same as DBLib
#define MAXNUMERICLEN SQL_MAX_NUMERIC_LEN
typedef SQL_NUMERIC_STRUCT DBNUMERIC;
typedef SQL_NUMERIC_STRUCT DBDECIMAL;
#endif // ODCBVER
#endif // MAXNUMERICLEN

#ifndef INT
typedef int     INT;
typedef long    DBINT;
typedef DBINT * LPDBINT;
#ifndef _LPCBYTE_DEFINED
#define _LPCBYTE_DEFINED
typedef BYTE const* LPCBYTE;
#endif //_LPCBYTE_DEFINED
#endif // INT
/************************************************************************** 
This struct is a global used for gathering statistical data on the driver.
Access to this structure is controlled via the pStatCrit;
***************************************************************************/ 
typedef struct sqlperf
{
    // Application Profile Statistics
    DWORD TimerResolution;
    DWORD SQLidu;
    DWORD SQLiduRows;
    DWORD SQLSelects;
    DWORD SQLSelectRows;
    DWORD Transactions;
    DWORD SQLPrepares;
    DWORD ExecDirects;
    DWORD SQLExecutes;
    DWORD CursorOpens;
    DWORD CursorSize;
    DWORD CursorUsed;
    LDOUBLE PercentCursorUsed;
    LDOUBLE AvgFetchTime;
    LDOUBLE AvgCursorSize;
    LDOUBLE AvgCursorUsed;
    DWORD SQLFetchTime;
    DWORD SQLFetchCount;
    DWORD CurrentStmtCount;
    DWORD MaxOpenStmt;
    DWORD SumOpenStmt;
    // Connection Statistics
    DWORD CurrentConnectionCount;
    DWORD MaxConnectionsOpened;
    DWORD SumConnectionsOpened;
    DWORD SumConnectiontime;
    LDOUBLE AvgTimeOpened;
    // Network Statistics
    DWORD ServerRndTrips;
    DWORD BuffersSent;
    DWORD BuffersRec;
    DWORD BytesSent;
    DWORD BytesRec;
    // Time Statistics;
    DWORD msExecutionTime;
    DWORD msNetWorkServerTime;
} SQLPERF;
// The following are options for SQL_COPT_SS_PERF_DATA and SQL_COPT_SS_PERF_QUERY
#define SQL_PERF_START          1           // Starts the driver sampling performance data.
#define SQL_PERF_STOP           2           // Stops the counters from sampling performance data.
// The following are defines for SQL_COPT_SS_PERF_DATA_LOG
#define SQL_SS_DL_DEFAULT       TEXT("STATS.LOG")
// The following are defines for SQL_COPT_SS_PERF_QUERY_LOG
#define SQL_SS_QL_DEFAULT       TEXT("QUERY.LOG")
// The following are defines for SQL_COPT_SS_PERF_QUERY_INTERVAL
#define SQL_SS_QI_DEFAULT       30000   //  30,000 milliseconds

#ifndef SQLNCLI_NO_BCP
// Define the symbol SQLNCLI_NO_BCP if you are not using BCP in your application
// and you want to exclude the BCP-related definitions in this header file.

// ODBC BCP prototypes and defines
// Return codes
#define SUCCEED                 1
#define FAIL                    0
#define SUCCEED_ABORT           2
#define SUCCEED_ASYNC           3
// Transfer directions
#define DB_IN                   1   // Transfer from client to server
#define DB_OUT                  2   // Transfer from server to client
// bcp_control option
#define BCPMAXERRS              1   // Sets max errors allowed
#define BCPFIRST                2   // Sets first row to be copied out
#define BCPLAST                 3   // Sets number of rows to be copied out
#define BCPBATCH                4   // Sets input batch size
#define BCPKEEPNULLS            5   // Sets to insert NULLs for empty input values
#define BCPABORT                6   // Sets to have bcpexec return SUCCEED_ABORT
#define BCPODBC                 7   // Sets ODBC canonical character output
#define BCPKEEPIDENTITY         8   // Sets IDENTITY_INSERT on
#if SQLNCLI_VER < 1000
#define BCP6xFILEFMT            9   // DEPRECATED: Sets 6x file format on
#endif
#define BCPHINTSA               10  // Sets server BCP hints (ANSI string)
#define BCPHINTSW               11  // Sets server BCP hints (UNICODE string)
#define BCPFILECP               12  // Sets clients code page for the file
#define BCPUNICODEFILE          13  // Sets that the file contains unicode header
#define BCPTEXTFILE             14  // Sets BCP mode to expect a text file and to detect Unicode or ANSI automatically
#define BCPFILEFMT              15  // Sets file format version
#define BCPFMTXML               16  // Sets the format file type to xml
#define BCPFIRSTEX              17  // Starting Row for BCP operation (64 bit)
#define BCPLASTEX               18  // Ending Row for BCP operation (64 bit)
#define BCPROWCOUNT             19  // Total Number of Rows Copied (64 bit)
// BCPFILECP values
// Any valid code page that is installed on the client can be passed plus:
#define BCPFILECP_ACP           0   // Data in file is in Windows code page
#define BCPFILECP_OEMCP         1   // Data in file is in OEM code page (default)
#define BCPFILECP_RAW           (-1)// Data in file is in Server code page (no conversion)
// bcp_collen definition
#define SQL_VARLEN_DATA (-10)   // Use default length for column
// BCP column format properties
#define BCP_FMT_TYPE            0x01
#define BCP_FMT_INDICATOR_LEN   0x02
#define BCP_FMT_DATA_LEN        0x03
#define BCP_FMT_TERMINATOR      0x04
#define BCP_FMT_SERVER_COL      0x05
#define BCP_FMT_COLLATION       0x06
#define BCP_FMT_COLLATION_ID    0x07
// BCP functions
DBINT SQL_API bcp_batch (HDBC);
RETCODE SQL_API bcp_bind (HDBC, LPCBYTE, INT, DBINT, LPCBYTE, INT, INT, INT);
RETCODE SQL_API bcp_colfmt (HDBC, INT, BYTE, INT, DBINT, LPCBYTE, INT, INT);
RETCODE SQL_API bcp_collen (HDBC, DBINT, INT);
RETCODE SQL_API bcp_colptr (HDBC, LPCBYTE, INT);
RETCODE SQL_API bcp_columns (HDBC, INT);
RETCODE SQL_API bcp_control (HDBC, INT, void *);
DBINT SQL_API bcp_done (HDBC);
RETCODE SQL_API bcp_exec (HDBC, LPDBINT);
RETCODE SQL_API bcp_getcolfmt (HDBC, INT, INT, void *, INT, INT *);
RETCODE SQL_API bcp_initA (HDBC, LPCSTR, LPCSTR, LPCSTR, INT);
RETCODE SQL_API bcp_initW (HDBC, LPCWSTR, LPCWSTR, LPCWSTR, INT);
RETCODE SQL_API bcp_moretext (HDBC, DBINT, LPCBYTE);
RETCODE SQL_API bcp_readfmtA (HDBC, LPCSTR);
RETCODE SQL_API bcp_readfmtW (HDBC, LPCWSTR);
RETCODE SQL_API bcp_sendrow (HDBC);
RETCODE SQL_API bcp_setcolfmt (HDBC, INT, INT, void *, INT);
RETCODE SQL_API bcp_writefmtA (HDBC, LPCSTR);
RETCODE SQL_API bcp_writefmtW (HDBC, LPCWSTR);
CHAR* SQL_API dbprtypeA (INT);
WCHAR* SQL_API dbprtypeW (INT);
CHAR* SQL_API bcp_gettypenameA (INT, DBBOOL);
WCHAR* SQL_API bcp_gettypenameW (INT, DBBOOL);
#ifdef UNICODE
#define bcp_init        bcp_initW
#define bcp_readfmt     bcp_readfmtW
#define bcp_writefmt    bcp_writefmtW
#define dbprtype        dbprtypeW
#define bcp_gettypename bcp_gettypenameW
#define BCPHINTS        BCPHINTSW
#else
#define bcp_init        bcp_initA
#define bcp_readfmt     bcp_readfmtA
#define bcp_writefmt    bcp_writefmtA
#define dbprtype        dbprtypeA
#define bcp_gettypename bcp_gettypenameA
#define BCPHINTS        BCPHINTSA
#endif // UNICODE

#endif // SQLNCLI_NO_BCP

// The following options have been deprecated
#define SQL_FAST_CONNECT                (SQL_COPT_SS_BASE+0)
// Defines for use with SQL_FAST_CONNECT - only useable before connecting
#define SQL_FC_OFF                      0L          //  Fast connect is off
#define SQL_FC_ON                       1L          //  Fast connect is on
#define SQL_FC_DEFAULT                  SQL_FC_OFF
#define SQL_COPT_SS_ANSI_OEM            (SQL_COPT_SS_BASE+6)
#define SQL_AO_OFF                      0L
#define SQL_AO_ON                       1L
#define SQL_AO_DEFAULT                  SQL_AO_OFF
#define SQL_CA_SS_BASE_COLUMN_NAME      SQL_DESC_BASE_COLUMN_NAME

#endif // ODBCVER
#endif // defined(_SQLNCLI_ODBC_) || !defined(_SQLNCLI_OLEDB_)
// ODBC part of SQL Server Native Client header - end here!

//The following facilitates opening a handle to a SQL filestream
typedef enum _SQL_FILESTREAM_DESIRED_ACCESS {
            SQL_FILESTREAM_READ        = 0,
            SQL_FILESTREAM_WRITE       = 1,
            SQL_FILESTREAM_READWRITE   = 2
} SQL_FILESTREAM_DESIRED_ACCESS;
#define SQL_FILESTREAM_OPEN_FLAG_ASYNC               0x00000001L
#define SQL_FILESTREAM_OPEN_FLAG_NO_BUFFERING        0x00000002L
#define SQL_FILESTREAM_OPEN_FLAG_NO_WRITE_THROUGH    0x00000004L
#define SQL_FILESTREAM_OPEN_FLAG_SEQUENTIAL_SCAN     0x00000008L
#define SQL_FILESTREAM_OPEN_FLAG_RANDOM_ACCESS       0x00000010L
HANDLE __stdcall OpenSqlFilestream (
           LPCWSTR                        FilestreamPath,
           SQL_FILESTREAM_DESIRED_ACCESS  DesiredAccess,
           ULONG                          OpenOptions,
           LPBYTE                         FilestreamTransactionContext,
           SSIZE_T                        FilestreamTransactionContextLength,
           PLARGE_INTEGER                 AllocationSize);
#define FSCTL_SQL_FILESTREAM_FETCH_OLD_CONTENT       CTL_CODE(FILE_DEVICE_FILE_SYSTEM, 2392, METHOD_BUFFERED, FILE_ANY_ACCESS)



extern RPC_IF_HANDLE __MIDL_itf_sqlncli_0000_0010_v0_0_c_ifspec;
extern RPC_IF_HANDLE __MIDL_itf_sqlncli_0000_0010_v0_0_s_ifspec;

/* Additional Prototypes for ALL interfaces */

/* end of Additional Prototypes */

#ifdef __cplusplus
}
#endif

#endif



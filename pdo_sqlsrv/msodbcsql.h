//-----------------------------------------------------------------------------
// File:        msodbcsql.h
//
// Copyright:   Copyright (c) Microsoft Corporation
//
// Contents:    ODBC driver for SQL Server specific definitions.
//
//-----------------------------------------------------------------------------
#ifndef __msodbcsql_h__
#define __msodbcsql_h__

#if !defined(SQLODBC_VER)
#define SQLODBC_VER 1100
#endif

#if SQLODBC_VER >= 1100

#define SQLODBC_PRODUCT_NAME_FULL_VER_ANSI      "Microsoft ODBC Driver 11 for SQL Server"
#define SQLODBC_PRODUCT_NAME_FULL_ANSI          "Microsoft ODBC Driver for SQL Server"
#define SQLODBC_PRODUCT_NAME_SHORT_VER_ANSI     "ODBC Driver 11 for SQL Server"
#define SQLODBC_PRODUCT_NAME_SHORT_ANSI         "ODBC Driver for SQL Server"

#define SQLODBC_FILE_NAME_ANSI                  "msodbcsql"
#define SQLODBC_FILE_NAME_VER_ANSI              "msodbcsql11"
#define SQLODBC_FILE_NAME_FULL_ANSI             "msodbcsql11.dll"

#define SQLODBC_PRODUCT_NAME_FULL_VER_UNICODE   L"Microsoft ODBC Driver 11 for SQL Server"
#define SQLODBC_PRODUCT_NAME_FULL_UNICODE       L"Microsoft ODBC Driver for SQL Server"
#define SQLODBC_PRODUCT_NAME_SHORT_VER_UNICODE  L"ODBC Driver 11 for SQL Server"
#define SQLODBC_PRODUCT_NAME_SHORT_UNICODE      L"ODBC Driver for SQL Server"

#define SQLODBC_FILE_NAME_UNICODE               L"msodbcsql"
#define SQLODBC_FILE_NAME_VER_UNICODE           L"msodbcsql11"
#define SQLODBC_FILE_NAME_FULL_UNICODE          L"msodbcsql11.dll"

// define the character type agnostic constants
#if defined(_UNICODE) || defined(UNICODE)

#define SQLODBC_PRODUCT_NAME_FULL_VER           SQLODBC_PRODUCT_NAME_FULL_VER_UNICODE
#define SQLODBC_PRODUCT_NAME_FULL               SQLODBC_PRODUCT_NAME_FULL_UNICODE
#define SQLODBC_PRODUCT_NAME_SHORT_VER          SQLODBC_PRODUCT_NAME_SHORT_VER_UNICODE
#define SQLODBC_PRODUCT_NAME_SHORT              SQLODBC_PRODUCT_NAME_SHORT_UNICODE

#define SQLODBC_FILE_NAME                       SQLODBC_FILE_NAME_UNICODE
#define SQLODBC_FILE_NAME_VER                   SQLODBC_FILE_NAME_VER_UNICODE
#define SQLODBC_FILE_NAME_FULL                  SQLODBC_FILE_NAME_FULL_UNICODE

#else   // _UNICODE || UNICODE

#define SQLODBC_PRODUCT_NAME_FULL_VER           SQLODBC_PRODUCT_NAME_FULL_VER_ANSI
#define SQLODBC_PRODUCT_NAME_FULL               SQLODBC_PRODUCT_NAME_FULL_ANSI
#define SQLODBC_PRODUCT_NAME_SHORT_VER          SQLODBC_PRODUCT_NAME_SHORT_VER_ANSI
#define SQLODBC_PRODUCT_NAME_SHORT              SQLODBC_PRODUCT_NAME_SHORT_ANSI

#define SQLODBC_FILE_NAME                       SQLODBC_FILE_NAME_ANSI
#define SQLODBC_FILE_NAME_VER                   SQLODBC_FILE_NAME_VER_ANSI
#define SQLODBC_FILE_NAME_FULL                  SQLODBC_FILE_NAME_FULL_ANSI

#endif  // _UNICODE || UNICODE

#define SQLODBC_DRIVER_NAME                     SQLODBC_PRODUCT_NAME_SHORT_VER

#endif  // SQLODBC_VER

#ifndef __sqlncli_h__

#if !defined(SQLNCLI_VER)
#define SQLNCLI_VER 1100
#endif

#if SQLNCLI_VER >= 1100

#define SQLNCLI_PRODUCT_NAME_FULL_VER_ANSI      "Microsoft SQL Server Native Client 11.0"
#define SQLNCLI_PRODUCT_NAME_FULL_ANSI          "Microsoft SQL Server Native Client"
#define SQLNCLI_PRODUCT_NAME_SHORT_VER_ANSI     "SQL Server Native Client 11.0"
#define SQLNCLI_PRODUCT_NAME_SHORT_ANSI         "SQL Server Native Client"

#define SQLNCLI_FILE_NAME_ANSI                  "sqlncli"
#define SQLNCLI_FILE_NAME_VER_ANSI              "sqlncli11"
#define SQLNCLI_FILE_NAME_FULL_ANSI             "sqlncli11.dll"

#define SQLNCLI_PRODUCT_NAME_FULL_VER_UNICODE   L"Microsoft SQL Server Native Client 11.0"
#define SQLNCLI_PRODUCT_NAME_FULL_UNICODE       L"Microsoft SQL Server Native Client"
#define SQLNCLI_PRODUCT_NAME_SHORT_VER_UNICODE  L"SQL Server Native Client 11.0"
#define SQLNCLI_PRODUCT_NAME_SHORT_UNICODE      L"SQL Server Native Client"

#define SQLNCLI_FILE_NAME_UNICODE               L"sqlncli"
#define SQLNCLI_FILE_NAME_VER_UNICODE           L"sqlncli11"
#define SQLNCLI_FILE_NAME_FULL_UNICODE          L"sqlncli11.dll"

#elif SQLNCLI_VER >= 1000

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

#else

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

#endif  // SQLNCLI_VER >= 1100

// define the character type agnostic constants
#if defined(_UNICODE) || defined(UNICODE)

#define SQLNCLI_PRODUCT_NAME_FULL_VER           SQLNCLI_PRODUCT_NAME_FULL_VER_UNICODE
#define SQLNCLI_PRODUCT_NAME_FULL               SQLNCLI_PRODUCT_NAME_FULL_UNICODE
#define SQLNCLI_PRODUCT_NAME_SHORT_VER          SQLNCLI_PRODUCT_NAME_SHORT_VER_UNICODE
#define SQLNCLI_PRODUCT_NAME_SHORT              SQLNCLI_PRODUCT_NAME_SHORT_UNICODE

#define SQLNCLI_FILE_NAME                       SQLNCLI_FILE_NAME_UNICODE
#define SQLNCLI_FILE_NAME_VER                   SQLNCLI_FILE_NAME_VER_UNICODE
#define SQLNCLI_FILE_NAME_FULL                  SQLNCLI_FILE_NAME_FULL_UNICODE


#else   // _UNICODE || UNICODE

#define SQLNCLI_PRODUCT_NAME_FULL_VER           SQLNCLI_PRODUCT_NAME_FULL_VER_ANSI
#define SQLNCLI_PRODUCT_NAME_FULL               SQLNCLI_PRODUCT_NAME_FULL_ANSI
#define SQLNCLI_PRODUCT_NAME_SHORT_VER          SQLNCLI_PRODUCT_NAME_SHORT_VER_ANSI
#define SQLNCLI_PRODUCT_NAME_SHORT              SQLNCLI_PRODUCT_NAME_SHORT_ANSI

#define SQLNCLI_FILE_NAME                       SQLNCLI_FILE_NAME_ANSI
#define SQLNCLI_FILE_NAME_VER                   SQLNCLI_FILE_NAME_VER_ANSI
#define SQLNCLI_FILE_NAME_FULL                  SQLNCLI_FILE_NAME_FULL_ANSI

#endif  // _UNICODE || UNICODE

#define SQLNCLI_DRIVER_NAME                     SQLNCLI_PRODUCT_NAME_SHORT_VER


#ifdef ODBCVER

#ifdef __cplusplus
extern "C" {
#endif

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
#define SQL_COPT_SS_CLIENT_CONNECTION_ID                (SQL_COPT_SS_BASE+33) // Post connection attribute used to get the ConnectionID
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
#define SQL_COPT_SS_APPLICATION_INTENT              (SQL_COPT_SS_BASE_EX+7) // Application Intent
#define SQL_COPT_SS_MULTISUBNET_FAILOVER            (SQL_COPT_SS_BASE_EX+8) // Multi-subnet Failover
#define SQL_COPT_SS_EX_MAX_USED                     SQL_COPT_SS_MULTISUBNET_FAILOVER

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

// Extended C Types range 4000 and above. Range of -100 thru 200 is reserved by Driver Manager.
#define SQL_C_TYPES_EXTENDED                0x04000L
#define SQL_C_SS_TIME2                         (SQL_C_TYPES_EXTENDED+0)
#define SQL_C_SS_TIMESTAMPOFFSET               (SQL_C_TYPES_EXTENDED+1)

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
        SQLUSMALLINT   hour;
        SQLUSMALLINT   minute;
        SQLUSMALLINT   second;
        SQLUINTEGER    fraction;
} SQL_SS_TIME2_STRUCT;
// New Structure for TIMESTAMPOFFSET
typedef struct tagSS_TIMESTAMPOFFSET_STRUCT
{
        SQLSMALLINT    year;
        SQLUSMALLINT   month;
        SQLUSMALLINT   day;
        SQLUSMALLINT   hour;
        SQLUSMALLINT   minute;
        SQLUSMALLINT   second;
        SQLUINTEGER    fraction;
        SQLSMALLINT    timezone_hour;
        SQLSMALLINT    timezone_minute;
} SQL_SS_TIMESTAMPOFFSET_STRUCT;

typedef struct tagDBTIME2
{
    USHORT hour;
    USHORT minute;
    USHORT second;
    ULONG fraction;
}   DBTIME2;

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
}   DBTIMESTAMPOFFSET;

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
typedef LONG    DBINT;
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
#define BCPDELAYREADFMT         20  // Delay reading format file unil bcp_exec
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
// bcp_setbulkmode properties
#define BCP_OUT_CHARACTER_MODE      0x01
#define BCP_OUT_WIDE_CHARACTER_MODE 0x02
#define BCP_OUT_NATIVE_TEXT_MODE    0x03
#define BCP_OUT_NATIVE_MODE         0x04



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
RETCODE SQL_API bcp_setbulkmode (HDBC, INT, __in_bcount(cbField) void*, INT cbField, __in_bcount(cbRow) void *, INT cbRow);
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


#ifdef __cplusplus
} // extern "C"
#endif

#endif // ODBCVER



#ifdef __cplusplus
extern "C" {
#endif
#include <windows.h>

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
           __in_bcount(FilestreamTransactionContextLength)
           LPBYTE                         FilestreamTransactionContext,
           SSIZE_T                        FilestreamTransactionContextLength,
           PLARGE_INTEGER                 AllocationSize);
#define FSCTL_SQL_FILESTREAM_FETCH_OLD_CONTENT       CTL_CODE(FILE_DEVICE_FILE_SYSTEM, 2392, METHOD_BUFFERED, FILE_ANY_ACCESS)

#ifdef __cplusplus
} // extern "C"
#endif


#endif //__sqlncli_h__

#define SQL_COPT_SS_CONNECT_RETRY_COUNT                 (SQL_COPT_SS_BASE+34) // Post connection attribute used to get ConnectRetryCount
#define SQL_COPT_SS_CONNECT_RETRY_INTERVAL              (SQL_COPT_SS_BASE+35) // Post connection attribute used to get ConnectRetryInterval
#ifdef SQL_COPT_SS_MAX_USED
#undef  SQL_COPT_SS_MAX_USED
#endif // SQL_COPT_SS_MAX_USED
#define SQL_COPT_SS_MAX_USED                            SQL_COPT_SS_CONNECT_RETRY_INTERVAL


#ifndef _SQLUSERINSTANCE_H_
#define _SQLUSERINSTANCE_H_

#include <windows.h>

#ifdef __cplusplus
extern "C" {
#endif


//  Recommended buffer size to store a LocalDB connection string 
#define LOCALDB_MAX_SQLCONNECTION_BUFFER_SIZE 260

// type definition for LocalDBCreateInstance function
typedef HRESULT __cdecl FnLocalDBCreateInstance (
		// I			the LocalDB version (e.g. 11.0 or 11.0.1094.2)
		__in_z			PCWSTR	wszVersion,
		// I			the instance name
		__in_z			PCWSTR	pInstanceName,
		// I			reserved for the future use. Currently should be set to 0.
		__in			DWORD	dwFlags
);

// type definition for pointer to LocalDBCreateInstance function
typedef FnLocalDBCreateInstance* PFnLocalDBCreateInstance;

// type definition for LocalDBStartInstance function
typedef HRESULT __cdecl FnLocalDBStartInstance (
		// I			the LocalDB instance name
		__in_z									PCWSTR	pInstanceName,
		// I			reserved for the future use. Currently should be set to 0.
		__in									DWORD	dwFlags,
		// O			the buffer to store the connection string to the LocalDB instance
		__out_ecount_z_opt(*lpcchSqlConnection)	LPWSTR	wszSqlConnection,
		// I/O			on input has the size of the wszSqlConnection buffer in characters. On output, if the given buffer size is 
		//				too small, has the buffer size required, in characters, including trailing null.
		__inout_opt								LPDWORD	lpcchSqlConnection
);

// type definition for pointer to LocalDBStartInstance function
typedef FnLocalDBStartInstance* PFnLocalDBStartInstance;

// Flags for the LocalDBFormatMessage function
#define LOCALDB_TRUNCATE_ERR_MESSAGE		0x0001L

// type definition for LocalDBFormatMessage function
typedef HRESULT __cdecl FnLocalDBFormatMessage(
			// I		the LocalDB error code
			__in							HRESULT	hrLocalDB,
			// I		Available flags:
			//			LOCALDB_TRUNCATE_ERR_MESSAGE - if the input buffer is too short,
			//			the error message will be truncated to fit into the buffer 
			__in							DWORD	dwFlags,
			// I		Language desired (LCID) or 0 (in which case Win32 FormatMessage order is used)
			__in							DWORD	dwLanguageId,
			// O		the buffer to store the LocalDB error message
			__out_ecount_z(*lpcchMessage)	LPWSTR	wszMessage,
			// I/O		on input has the size of the wszMessage buffer in characters. On output, if the given buffer size is 
			//			too small, has the buffer size required, in characters, including trailing null. If the function succeeds
			//			contains the number of characters in the message, excluding the trailing null
			__inout							LPDWORD	lpcchMessage
);

// type definition for function pointer to LocalDBFormatMessage function
typedef FnLocalDBFormatMessage* PFnLocalDBFormatMessage;


// MessageId: LOCALDB_ERROR_NOT_INSTALLED
//
// MessageText:
//
// LocalDB is not installed.
//
#define LOCALDB_ERROR_NOT_INSTALLED            ((HRESULT)0x89C50116L)

//---------------------------------------------------------------------
// Function: LocalDBCreateInstance
//
// Description: This function will create the new LocalDB instance.
// 
// Available Flags:
//	No flags available. Reserved for future use.
//
// Return Values:
//	S_OK, if the function succeeds
//	LOCALDB_ERROR_INVALID_PARAM_INSTANCE_NAME, if the instance name parameter is invalid
//	LOCALDB_ERROR_INVALID_PARAM_VERSION, if the version parameter is invalid
//	LOCALDB_ERROR_INVALID_PARAM_FLAGS, if the flags are invalid
//	LOCALDB_ERROR_INVALID_OPERATION, if the user tries to create a default instance
//	LOCALDB_ERROR_INSTANCE_FOLDER_PATH_TOO_LONG, if the path where instance should be stored is longer than MAX_PATH
//	LOCALDB_ERROR_VERSION_REQUESTED_NOT_INSTALLED, if the specified service level is not installed
//	LOCALDB_ERROR_INSTANCE_FOLDER_ALREADY_EXISTS, if the instance folder already exists and is not empty
//	LOCALDB_ERROR_INSTANCE_EXISTS_WITH_LOWER_VERSION,	if the specified instance already exists but with lower version
//	LOCALDB_ERROR_CANNOT_CREATE_INSTANCE_FOLDER, if a folder cannot be created under %userprofile%
//	LOCALDB_ERROR_CANNOT_GET_USER_PROFILE_FOLDER, if a user profile folder cannot be retrieved
//	LOCALDB_ERROR_CANNOT_ACCESS_INSTANCE_FOLDER, if a instance folder cannot be accessed
//	LOCALDB_ERROR_CANNOT_ACCESS_INSTANCE_REGISTRY, if a instance registry cannot be accessed
//	LOCALDB_ERROR_INTERNAL_ERROR, if an unexpected error occurred. See event log for details
//	LOCALDB_ERROR_CANNOT_MODIFY_INSTANCE_REGISTRY, if an instance registry cannot be modified
//	LOCALDB_ERROR_CANNOT_CREATE_SQL_PROCESS, if a process for Sql Server cannot be created
//	LOCALDB_ERROR_SQL_SERVER_STARTUP_FAILED, if a Sql Server process is started but Sql Server startup failed.
//	LOCALDB_ERROR_INSTANCE_CONFIGURATION_CORRUPT, if a instance configuration is corrupted
//
FnLocalDBCreateInstance LocalDBCreateInstance;

//---------------------------------------------------------------------
// Function: LocalDBStartInstance
// 
// Description: This function will start the given LocalDB instance.
//
// Return Values:
//	S_OK, if the function succeeds
//	LOCALDB_ERROR_UNKNOWN_INSTANCE, if the specified instance doesn't exist
//	LOCALDB_ERROR_INVALID_PARAM_INSTANCE_NAME, if the instance name parameter is invalid
//	LOCALDB_ERROR_INVALID_PARAM_CONNECTION, if the wszSqlConnection parameter is NULL
//	LOCALDB_ERROR_INVALID_PARAM_FLAGS, if the flags are invalid
//	LOCALDB_ERROR_INSUFFICIENT_BUFFER, if the buffer wszSqlConnection is too small
//	LOCALDB_ERROR_INSTANCE_FOLDER_PATH_TOO_LONG, if the path where instance should be stored is longer than MAX_PATH

//	LOCALDB_ERROR_CANNOT_GET_USER_PROFILE_FOLDER, if a user profile folder cannot be retrieved
//	LOCALDB_ERROR_CANNOT_ACCESS_INSTANCE_FOLDER, if a instance folder cannot be accessed
//	LOCALDB_ERROR_CANNOT_ACCESS_INSTANCE_REGISTRY, if a instance registry cannot be accessed
//	LOCALDB_ERROR_INTERNAL_ERROR, if an unexpected error occurred. See event log for details
//	LOCALDB_ERROR_CANNOT_MODIFY_INSTANCE_REGISTRY, if an instance registry cannot be modified
//	LOCALDB_ERROR_CANNOT_CREATE_SQL_PROCESS, if a process for Sql Server cannot be created
//	LOCALDB_ERROR_SQL_SERVER_STARTUP_FAILED, if a Sql Server process is started but Sql Server startup failed.
//	LOCALDB_ERROR_INSTANCE_CONFIGURATION_CORRUPT, if a instance configuration is corrupted
//
FnLocalDBStartInstance LocalDBStartInstance;

// type definition for LocalDBStopInstance function
typedef HRESULT __cdecl FnLocalDBStopInstance (
		// I			the LocalDB instance name
		__in_z			PCWSTR	pInstanceName,
		// I			Available flags:
		//				LOCALDB_SHUTDOWN_KILL_PROCESS	- force the instance to stop immediately
		//				LOCALDB_SHUTDOWN_WITH_NOWAIT	- shutdown the instance with NOWAIT option
		__in			DWORD	dwFlags,
		// I			the time in seconds to wait this operation to complete. If this value is 0, this function will return immediately
		//				without waiting for LocalDB instance to stop
		__in			ULONG	ulTimeout
);

// type definition for pointer to LocalDBStopInstance function
typedef FnLocalDBStopInstance* PFnLocalDBStopInstance;

// Flags for the StopLocalDBInstance function
#define LOCALDB_SHUTDOWN_KILL_PROCESS		0x0001L
#define LOCALDB_SHUTDOWN_WITH_NOWAIT		0x0002L

//---------------------------------------------------------------------
// Function: LocalDBStopInstance
//
// Description: This function will shutdown the given LocalDB instance. 
// If the flag LOCALDB_SHUTDOWN_KILL_PROCESS is set, the LocalDB instance will be killed immediately.
// IF the flag LOCALDB_SHUTDOWN_WITH_NOWAIT is set, the LocalDB instance will shutdown with NOWAIT option.
//
// Return Values:
//	S_OK, if the function succeeds
//	LOCALDB_ERROR_UNKNOWN_INSTANCE, if the specified instance doesn't exist
//	LOCALDB_ERROR_INVALID_PARAM_INSTANCE_NAME, if the instance name parameter is invalid
//	LOCALDB_ERROR_INVALID_PARAM_FLAGS, if the flags are invalid
//	LOCALDB_ERROR_WAIT_TIMEOUT - if this function has not finished in given time
//	LOCALDB_ERROR_INTERNAL_ERROR, if an unexpected error occurred. See event log for details
//
FnLocalDBStopInstance LocalDBStopInstance;

// type definition for LocalDBDeleteInstance function
typedef HRESULT __cdecl FnLocalDBDeleteInstance (
		// I			the LocalDB instance name
		__in_z			PCWSTR	pInstanceName,
		// I			reserved for the future use. Currently should be set to 0.
		__in			DWORD	dwFlags
);

// type definition for pointer to LocalDBDeleteInstance function
typedef FnLocalDBDeleteInstance* PFnLocalDBDeleteInstance;

//---------------------------------------------------------------------
// Function: LocalDBDeleteInstance
//
// Description: This function will remove the given LocalDB instance. If the given instance is running this function will
// fail with the error code LOCALDB_ERROR_INSTANCE_BUSY.
//
// Return Values:
//	S_OK, if the function succeeds
//	LOCALDB_ERROR_INVALID_PARAM_INSTANCE_NAME, if the instance name parameter is invalid
//	LOCALDB_ERROR_INVALID_PARAM_FLAGS, if the flags are invalid
//	LOCALDB_ERROR_UNKNOWN_INSTANCE, if the specified instance doesn't exist
//	LOCALDB_ERROR_INSTANCE_BUSY, if the given instance is running
//	LOCALDB_ERROR_INTERNAL_ERROR, if an unexpected error occurred. See event log for details
//
FnLocalDBDeleteInstance LocalDBDeleteInstance;

// Function: LocalDBFormatMessage
//
// Description: This function will return the localized textual description for the given LocalDB error
//
// Available Flags:
//	LOCALDB_TRUNCATE_ERR_MESSAGE - the error message should be truncated to fit into the provided buffer
//
// Return Value:
//	S_OK, if the function succeeds
//
//	LOCALDB_ERROR_UNKNOWN_HRESULT,		if the given HRESULT is unknown
//	LOCALDB_ERROR_UNKNOWN_LANGUAGE_ID,	if the given language id is unknown (0 is recommended for the //	default language)
//	LOCALDB_ERROR_UNKNOWN_ERROR_CODE,	if the LocalDB error code is unknown
//	LOCALDB_ERROR_INVALID_PARAM_FLAGS,	if the flags are invalid
//	LOCALDB_ERROR_INSUFFICIENT_BUFFER,	if the input buffer is too short and LOCALDB_TRUNCATE_ERR_MESSAGE flag 
//										is not set
//	LOCALDB_ERROR_INTERNAL_ERROR,		if an unexpected error occurred. See event log for details
//
FnLocalDBFormatMessage LocalDBFormatMessage;

#define MAX_LOCALDB_INSTANCE_NAME_LENGTH 128
#define MAX_LOCALDB_PARENT_INSTANCE_LENGTH MAX_INSTANCE_NAME

typedef WCHAR TLocalDBInstanceName[MAX_LOCALDB_INSTANCE_NAME_LENGTH + 1];
typedef TLocalDBInstanceName* PTLocalDBInstanceName;

// type definition for LocalDBGetInstances function
typedef HRESULT __cdecl FnLocalDBGetInstances(
		// O					buffer for a LocalDB instance names
		__out					PTLocalDBInstanceName	pInstanceNames,
		// I/O					on input has the number slots for instance names in the pInstanceNames buffer. On output, 
		//						has the number of existing LocalDB instances
		__inout					LPDWORD					lpdwNumberOfInstances
);

// type definition for pointer to LocalDBGetInstances function
typedef FnLocalDBGetInstances* PFnLocalDBGetInstances;

// Function: LocalDBGetInstances
// 
// Description: This function returns names for all existing Local DB instances
//
// Usage Example:
//	DWORD dwN = 0;
//	LocalDBGetInstances(NULL, &dwN);

//	PTLocalDBInstanceName insts = (PTLocalDBInstanceName) malloc(dwN * sizeof(TLocalDBInstanceName));
//	LocalDBGetInstances(insts, &dwN);

//	for (int i = 0; i < dwN; i++)
//		wprintf(L"%s\n", insts[i]);
//
// Return values:
//	S_OK, if the function succeeds
//
// LOCALDB_ERROR_INSUFFICIENT_BUFFER,			the given buffer is to small
// LOCALDB_ERROR_INTERNAL_ERROR,		if an unexpected error occurred. See event log for details
//
FnLocalDBGetInstances LocalDBGetInstances;

// SID string format: S - Revision(1B) - Authority ID (6B) {- Sub authority ID (4B)} * max 15 sub-authorities = 1 + 1 + 3 + 1 + 15 + (1 + 10) * 15
#define MAX_STRING_SID_LENGTH 186

#pragma pack(push)
#pragma pack(8)

// DEVNOTE: If you want to modify this structure please read DEVNOTEs on top of function LocalDBGetInstanceInfo in sqluserinstance.cpp file.
//
typedef struct _LocalDBInstanceInfo
{
	DWORD					cbLocalDBInstanceInfoSize;
	TLocalDBInstanceName	wszInstanceName;
	BOOL					bExists;
	BOOL					bConfigurationCorrupted;
	BOOL					bIsRunning;
	DWORD					dwMajor;
	DWORD					dwMinor;
	DWORD					dwBuild;
	DWORD					dwRevision;
	FILETIME				ftLastStartDateUTC;
	WCHAR					wszConnection[LOCALDB_MAX_SQLCONNECTION_BUFFER_SIZE];
	BOOL					bIsShared;
	TLocalDBInstanceName	wszSharedInstanceName;
	WCHAR					wszOwnerSID[MAX_STRING_SID_LENGTH + 1];
	BOOL					bIsAutomatic;
} LocalDBInstanceInfo;

#pragma pack(pop)

typedef LocalDBInstanceInfo* PLocalDBInstanceInfo;

// type definition for LocalDBGetInstanceInfo function
typedef HRESULT __cdecl FnLocalDBGetInstanceInfo(
		// I		the LocalDB instance name
		__in_z		PCWSTR					wszInstanceName, 
		// O		instance information
		__out		PLocalDBInstanceInfo	pInfo,
		// I		Size of LocalDBInstanceInfo structure in bytes
		__in		DWORD					cbInfo);

// type definition for pointer to LocalDBGetInstances function
typedef FnLocalDBGetInstanceInfo* PFnLocalDBGetInstanceInfo;

// Function: LocalDBGetInstanceInfo
//
// Description: This function returns information about the given instance.
//
// Return values:
//	S_OK, if the function succeeds
//
// ERROR_INVALID_PARAMETER, if some of the parameters is invalid
// LOCALDB_ERROR_INTERNAL_ERROR,		if an unexpected error occurred. See event log for details
// 
FnLocalDBGetInstanceInfo LocalDBGetInstanceInfo;

// Version has format: Major.Minor[.Build[.Revision]]. Each of components is 32bit integer which is at most 40 digits and 3 dots
//
#define MAX_LOCALDB_VERSION_LENGTH 43

typedef WCHAR TLocalDBVersion[MAX_LOCALDB_VERSION_LENGTH + 1];
typedef TLocalDBVersion* PTLocalDBVersion;

// type definition for LocalDBGetVersions function
typedef HRESULT __cdecl FnLocalDBGetVersions(
		// O					buffer for installed LocalDB versions
		__out					PTLocalDBVersion	pVersions,
		// I/O					on input has the number slots for versions in the pVersions buffer. On output, 
		//						has the number of existing LocalDB versions
		__inout					LPDWORD				lpdwNumberOfVersions
);

// type definition for pointer to LocalDBGetVersions function
typedef FnLocalDBGetVersions* PFnLocalDBGetVersions;

// Function: LocalDBGetVersions
// 
// Description: This function returns all installed LocalDB versions. Returned versions will be in format Major.Minor
//
// Usage Example:
//	DWORD dwN = 0;
//	LocalDBGetVersions(NULL, &dwN);

//	PTLocalDBVersion versions = (PTLocalDBVersion) malloc(dwN * sizeof(TLocalDBVersion));
//	LocalDBGetVersions(insts, &dwN);

//	for (int i = 0; i < dwN; i++)
//		wprintf(L"%s\n", insts[i]);
//
// Return values:
//	S_OK, if the function succeeds
//
// LOCALDB_ERROR_INSUFFICIENT_BUFFER,			the given buffer is to small
// LOCALDB_ERROR_INTERNAL_ERROR,				if an unexpected error occurs.
//
FnLocalDBGetVersions LocalDBGetVersions;

#pragma pack(push)
#pragma pack(8)

// DEVNOTE: If you want to modify this structure please read DEVNOTEs on top of function LocalDBGetVersionInfo in sqluserinstance.cpp file.
//
typedef struct _LocalDBVersionInfo
{
	DWORD				cbLocalDBVersionInfoSize;
	TLocalDBVersion		wszVersion;
	BOOL				bExists;
	DWORD				dwMajor;
	DWORD				dwMinor;
	DWORD				dwBuild;
	DWORD				dwRevision;
} LocalDBVersionInfo;

#pragma pack(pop)

typedef LocalDBVersionInfo* PLocalDBVersionInfo;

// type definition for LocalDBGetVersionInfo function
typedef HRESULT __cdecl FnLocalDBGetVersionInfo(
		// I			LocalDB version string
		__in_z			PCWSTR					wszVersion,
		// O			version information
		__out			PLocalDBVersionInfo		pVersionInfo,
		// I			Size of LocalDBVersionInfo structure in bytes
		__in			DWORD					cbVersionInfo
);

// type definition for pointer to LocalDBGetVersionInfo function
typedef FnLocalDBGetVersionInfo* PFnLocalDBGetVersionInfo;

// Function: LocalDBGetVersionInfo
// 
// Description: This function returns information about the given LocalDB version
//
// Return values:
//	S_OK, if the function succeeds
//	LOCALDB_ERROR_INTERNAL_ERROR, if some internal error occurred
//	LOCALDB_ERROR_INVALID_PARAMETER, if a input parameter is invalid
//
FnLocalDBGetVersionInfo LocalDBGetVersionInfo;

typedef HRESULT __cdecl FnLocalDBStartTracing();
typedef FnLocalDBStartTracing* PFnLocalDBStartTracing;

// Function: LocalDBStartTracing
//
// Description: This function will write in registry that Tracing sessions should be started for the current user.
//
// Return values:
//	S_OK - on success
//	Propper HRESULT in case of failure
//
FnLocalDBStartTracing LocalDBStartTracing;

typedef HRESULT __cdecl FnLocalDBStopTracing();
typedef FnLocalDBStopTracing* PFnFnLocalDBStopTracing;

// Function: LocalDBStopTracing
//
// Description: This function will write in registry that Tracing sessions should be stopped for the current user.
//
// Return values:
//	S_OK - on success
//	Propper HRESULT in case of failure
//
FnLocalDBStopTracing LocalDBStopTracing;

// type definition for LocalDBShareInstance function
typedef HRESULT __cdecl FnLocalDBShareInstance(
		// I		the SID of the LocalDB instance owner
		__in_opt	PSID 					pOwnerSID, 
		// I		the private name of LocalDB instance which should be shared
		__in_z		PCWSTR					wszPrivateLocalDBInstanceName,
		// I		the public shared name
		__in_z		PCWSTR					wszSharedName,
		// I		reserved for the future use. Currently should be set to 0.
		__in		DWORD	dwFlags);

// type definition for pointer to LocalDBShareInstance function
typedef FnLocalDBShareInstance* PFnLocalDBShareInstance;

// Function: LocalDBShareInstance
//
// Description: This function will share the given private instance of the given user with the given shared name.
// This function has to be executed elevated.
//
// Return values:
//	HRESULT
//
FnLocalDBShareInstance LocalDBShareInstance;

// type definition for LocalDBUnshareInstance function
typedef HRESULT __cdecl FnLocalDBUnshareInstance(
		// I		the LocalDB instance name
		__in_z		PCWSTR					pInstanceName,
		// I		reserved for the future use. Currently should be set to 0.
		__in		DWORD	dwFlags);

// type definition for pointer to LocalDBUnshareInstance function
typedef FnLocalDBUnshareInstance* PFnLocalDBUnshareInstance;

// Function: LocalDBUnshareInstance
//
// Description: This function unshares the given LocalDB instance.
// If a shared name is given then that shared instance will be unshared.
// If a private name is given then we will check if the caller
// shares a private instance with the given private name and unshare it.
//
// Return values:
//	HRESULT
//
FnLocalDBUnshareInstance LocalDBUnshareInstance;

#ifdef __cplusplus
} // extern "C"
#endif

#if defined(LOCALDB_DEFINE_PROXY_FUNCTIONS)
//---------------------------------------------------------------------
// The following section is enabled only if the constant LOCALDB_DEFINE_PROXY_FUNCTIONS
// is defined. It provides an implementation of proxies for each of the LocalDB APIs.
// The proxy implementations use a common function to bind to entry points in the
// latest installed SqlUserInstance DLL, and then forward the requests.
//
// The current implementation loads the SqlUserInstance DLL on the first call into
// a proxy function. There is no provision for unloading the DLL. Note that if the
// process includes multiple binaries (EXE and one or more DLLs), each of them could
// load a separate instance of the SqlUserInstance DLL.
//
// For future consideration: allow the SqlUserInstance DLL to be unloaded dynamically.
//
// WARNING: these functions must not be called in DLL initialization, since a deadlock
// could result loading dependent DLLs.
//---------------------------------------------------------------------

// This macro provides the body for each proxy function.
//
#define LOCALDB_PROXY(LocalDBFn) static Fn##LocalDBFn* pfn##LocalDBFn = NULL; if (!pfn##LocalDBFn) {HRESULT hr = LocalDBGetPFn(#LocalDBFn, (FARPROC *)&pfn##LocalDBFn); if (FAILED(hr)) return hr;} return (*pfn##LocalDBFn)

// Structure and function to parse the "Installed Versions" registry subkeys
//
typedef struct {
    DWORD dwComponent[2];
    WCHAR wszKeyName[256];
} Version;

// The following algorithm is intended to match, in part, the .NET Version class.
// A maximum of two components are allowed, which must be separated with a period.
// Valid: "11", "11.0"
// Invalid: "", ".0", "11.", "11.0."
//
static BOOL ParseVersion(Version * pVersion)
{
    pVersion->dwComponent[0] = 0;
    pVersion->dwComponent[1] = 0;
    WCHAR * pwch = pVersion->wszKeyName;

    for (int i = 0; i<2; i++)
    {
        LONGLONG llVal = 0;
        BOOL fHaveDigit = FALSE;

        while (*pwch >= L'0' && *pwch <= L'9')
        {
            llVal = llVal * 10 + (*pwch++ - L'0');
            fHaveDigit = TRUE;

            if (llVal > 0x7fffffff)
            {
                return FALSE;
            }
        }

        if (!fHaveDigit)
            return FALSE;

        pVersion->dwComponent[i] = (DWORD) llVal;

        if (*pwch == L'\0')
            return TRUE;

        if (*pwch != L'.')
            return FALSE;

        pwch++;
    }
    // If we get here, the version string was terminated with L'.', which is not valid
    //
    return FALSE;
}

#include <assert.h>

// This function loads the correct LocalDB API DLL (if required) and returns a pointer to a procedure.
// Note that the first-loaded API DLL for the process will be used until process termination: installation of
//  a new version of the API will not be recognized after first load.
//
static HRESULT LocalDBGetPFn(LPCSTR szLocalDBFn, FARPROC *pfnLocalDBFn)
{
    static volatile HMODULE hLocalDBDll = NULL;

    if (!hLocalDBDll)
    {
        LONG    ec;
        HKEY    hkeyVersions = NULL;
        HKEY    hkeyVersion = NULL;
        Version verHigh = {0};
        Version verCurrent;
        DWORD   cchKeyName;
        DWORD   dwValueType;
        WCHAR   wszLocalDBDll[MAX_PATH+1];
        DWORD   cbLocalDBDll = sizeof(wszLocalDBDll) - sizeof(WCHAR); // to deal with RegQueryValueEx null-termination quirk
        HMODULE hLocalDBDllTemp = NULL;

        if (ERROR_SUCCESS != (ec = RegOpenKeyExW(HKEY_LOCAL_MACHINE, L"SOFTWARE\\Microsoft\\Microsoft SQL Server Local DB\\Installed Versions", 0, KEY_READ, &hkeyVersions)))
        {
            goto Cleanup;
        }

        for (int i = 0; ; i++)
        {
            cchKeyName = 256;
            if (ERROR_SUCCESS != (ec = RegEnumKeyExW(hkeyVersions, i, verCurrent.wszKeyName, &cchKeyName, 0, NULL, NULL, NULL)))
            {
                if (ERROR_NO_MORE_ITEMS == ec)
                {
                    break;
                }
                goto Cleanup;
            }

            if (!ParseVersion(&verCurrent))
            {
                continue; // invalid version syntax
            }

            if (verCurrent.dwComponent[0] > verHigh.dwComponent[0] ||
                (verCurrent.dwComponent[0] == verHigh.dwComponent[0] && verCurrent.dwComponent[1] > verHigh.dwComponent[1]))
            {
                verHigh = verCurrent;
            }
        }
        if (!verHigh.wszKeyName[0])
        {
            // ec must be ERROR_NO_MORE_ITEMS here
            //
            assert(ec == ERROR_NO_MORE_ITEMS);

            // We will change the error code to ERROR_FILE_NOT_FOUND in order to indicate that
            // LocalDB instalation is not found. Registry key "SOFTWARE\\Microsoft\\Microsoft SQL Server Local DB\\Installed Versions" exists
            // but it is empty.
            //
            ec = ERROR_FILE_NOT_FOUND;
            goto Cleanup;
        }

        if (ERROR_SUCCESS != (ec = RegOpenKeyExW(hkeyVersions, verHigh.wszKeyName, 0, KEY_READ, &hkeyVersion)))
        {
            goto Cleanup;
        }
        if (ERROR_SUCCESS != (ec = RegQueryValueExW(hkeyVersion, L"InstanceAPIPath", NULL, &dwValueType, (PBYTE) wszLocalDBDll, &cbLocalDBDll)))
        {
            goto Cleanup;
        }
        if (dwValueType != REG_SZ)
        {
            ec = ERROR_INVALID_DATA;
            goto Cleanup;
        }
        // Ensure string value null-terminated
        // Note that we left a spare character in the output buffer for RegQueryValueEx for this purpose
        //
        wszLocalDBDll[cbLocalDBDll/sizeof(WCHAR)] = L'\0';

        hLocalDBDllTemp = LoadLibraryW(wszLocalDBDll);
        if (NULL == hLocalDBDllTemp)
        {
            ec = GetLastError();
            goto Cleanup;
        }
        if (NULL == InterlockedCompareExchangePointer((volatile PVOID *)&hLocalDBDll, hLocalDBDllTemp, NULL))
        {
            // We were the winner: we gave away our DLL handle
            //
            hLocalDBDllTemp = NULL;
        }
        ec = ERROR_SUCCESS;
Cleanup:
        if (hLocalDBDllTemp)
            FreeLibrary(hLocalDBDllTemp);
        if (hkeyVersion)
            RegCloseKey(hkeyVersion);
        if (hkeyVersions)
            RegCloseKey(hkeyVersions);

        // Error code ERROR_FILE_NOT_FOUND can occure if registry hive with installed LocalDB versions is missing.
        // In that case we should return the LocalDB specific error code
        //
        if (ec == ERROR_FILE_NOT_FOUND)
            return LOCALDB_ERROR_NOT_INSTALLED;

        if (ec != ERROR_SUCCESS)
            return HRESULT_FROM_WIN32(ec);
    }

    FARPROC pfn = GetProcAddress(hLocalDBDll, szLocalDBFn);

    if (!pfn)
    {
       return HRESULT_FROM_WIN32(GetLastError());
    }
    *pfnLocalDBFn = pfn;
    return S_OK;
}

// The following proxy functions forward calls to the latest LocalDB API DLL.
//

HRESULT __cdecl
LocalDBCreateInstance (
		// I			the LocalDB version (e.g. 11.0 or 11.0.1094.2)
		__in_z			PCWSTR	wszVersion,
		// I			the instance name
		__in_z			PCWSTR	pInstanceName,
		// I			reserved for the future use. Currently should be set to 0.
		__in			DWORD	dwFlags
)
{
	LOCALDB_PROXY(LocalDBCreateInstance)(wszVersion, pInstanceName, dwFlags);
}

HRESULT __cdecl
LocalDBStartInstance(
		// I			the instance name
		__in_z										PCWSTR	pInstanceName,
		// I			reserved for the future use. Currently should be set to 0.
		__in										DWORD	dwFlags,
		// O			the buffer to store the connection string to the LocalDB instance
		__out_ecount_z_opt(*lpcchSqlConnection)		LPWSTR	wszSqlConnection,
		// I/O			on input has the size of the wszSqlConnection buffer in characters. On output, if the given buffer size is 
		//				too small, has the buffer size required, in characters, including trailing null.
		__inout_opt									LPDWORD	lpcchSqlConnection
)
{
	LOCALDB_PROXY(LocalDBStartInstance)(pInstanceName, dwFlags, wszSqlConnection, lpcchSqlConnection);
}

HRESULT __cdecl
LocalDBStopInstance (
		// I			the instance name
		__in_z			PCWSTR	pInstanceName,
		// I			Available flags:
		//				LOCALDB_SHUTDOWN_KILL_PROCESS		- force the instance to stop immediately
		//				LOCALDB_SHUTDOWN_WITH_NOWAIT	- shutdown the instance with NOWAIT option
		__in			DWORD	dwFlags,
		// I			the time in seconds to wait this operation to complete. If this value is 0, this function will return immediately
		//				without waiting for LocalDB instance to stop
		__in			ULONG	ulTimeout
)
{
	LOCALDB_PROXY(LocalDBStopInstance)(pInstanceName, dwFlags, ulTimeout);
}

HRESULT __cdecl
LocalDBDeleteInstance (
		// I			the instance name
		__in_z			PCWSTR	pInstanceName,
		//				reserved for the future use. Currently should be set to 0.
		__in			DWORD	dwFlags
)
{
	LOCALDB_PROXY(LocalDBDeleteInstance)(pInstanceName, dwFlags);
}

HRESULT __cdecl
LocalDBFormatMessage(
			// I		the LocalDB error code
			__in								HRESULT	hrLocalDB,
			// I		Available flags:
			//			LOCALDB_TRUNCATE_ERR_MESSAGE - if the input buffer is too short,
			//			the error message will be truncated to fit into the buffer 
			__in								DWORD	dwFlags,
			// I		Language desired (LCID) or 0 (in which case Win32 FormatMessage order is used)
			__in								DWORD	dwLanguageId,
			// O		the buffer to store the LocalDB error message
			__out_ecount_z(*lpcchMessage)		LPWSTR	wszMessage,
			// I/O		on input has the size of the wszMessage buffer in characters. On output, if the given buffer size is 
			//			too small, has the buffer size required, in characters, including trailing null. If the function succeeds
			//			contains the number of characters in the message, excluding the trailing null
			__inout								LPDWORD	lpcchMessage
)
{
	LOCALDB_PROXY(LocalDBFormatMessage)(hrLocalDB, dwFlags, dwLanguageId, wszMessage, lpcchMessage);
}

HRESULT __cdecl
LocalDBGetInstances(
		// O					buffer with instance names
		__out					PTLocalDBInstanceName	pInstanceNames,
		// I/O					on input has the number slots for instance names in the pInstanceNames buffer. On output, 
		//						has the number of existing LocalDB instances
		__inout					LPDWORD					lpdwNumberOfInstances
)
{
	LOCALDB_PROXY(LocalDBGetInstances)(pInstanceNames, lpdwNumberOfInstances);
}

HRESULT __cdecl
LocalDBGetInstanceInfo(
		// I		the instance name
		__in_z		PCWSTR					wszInstanceName, 
		// O		instance information
		__out		PLocalDBInstanceInfo	pInfo,
		// I		Size of LocalDBInstanceInfo structure in bytes
		__in		DWORD					cbInfo
)
{
	LOCALDB_PROXY(LocalDBGetInstanceInfo)(wszInstanceName, pInfo, cbInfo);
}

HRESULT __cdecl
LocalDBStartTracing()
{
	LOCALDB_PROXY(LocalDBStartTracing)();
}

HRESULT __cdecl
LocalDBStopTracing()
{
	LOCALDB_PROXY(LocalDBStopTracing)();
}

HRESULT __cdecl 
LocalDBShareInstance(
		// I		the SID of the LocalDB instance owner
		__in_opt	PSID 					pOwnerSID, 
		// I		the private name of LocalDB instance which should be shared
		__in_z		PCWSTR					wszLocalDBInstancePrivateName,
		// I		the public shared name
		__in_z		PCWSTR					wszSharedName,
		// I		reserved for the future use. Currently should be set to 0.
		__in		DWORD	dwFlags)
{
	LOCALDB_PROXY(LocalDBShareInstance)(pOwnerSID, wszLocalDBInstancePrivateName, wszSharedName, dwFlags);
}

HRESULT __cdecl
LocalDBGetVersions(
		// O					buffer for installed LocalDB versions
		__out					PTLocalDBVersion	pVersions,
		// I/O					on input has the number slots for versions in the pVersions buffer. On output, 
		//						has the number of existing LocalDB versions
		__inout					LPDWORD				lpdwNumberOfVersions
)
{
	LOCALDB_PROXY(LocalDBGetVersions)(pVersions, lpdwNumberOfVersions);
}

HRESULT __cdecl
LocalDBUnshareInstance(
		// I		the LocalDB instance name
		__in_z		PCWSTR					pInstanceName,
		// I		reserved for the future use. Currently should be set to 0.
		__in		DWORD	dwFlags)
{
	LOCALDB_PROXY(LocalDBUnshareInstance)(pInstanceName, dwFlags);
}

HRESULT __cdecl 
LocalDBGetVersionInfo(
		// I			LocalDB version string
		__in_z			PCWSTR						wszVersion,
		// O			version information
		__out			PLocalDBVersionInfo			pVersionInfo,
		// I			Size of LocalDBVersionInfo structure in bytes
		__in			DWORD						cbVersionInfo)
{
	LOCALDB_PROXY(LocalDBGetVersionInfo)(wszVersion, pVersionInfo, cbVersionInfo);
}

#endif

#endif	// _SQLUSERINSTANCE_H_

//-----------------------------------------------------------------------------
// File:			sqluserinstancemsgs.mc
//
// Copyright:		Copyright (c) Microsoft Corporation
//-----------------------------------------------------------------------------
#ifndef _LOCALDB_MESSAGES_H_
#define _LOCALDB_MESSAGES_H_
// Header section
//
// Section with the LocalDB messages
//
//
//  Values are 32 bit values laid out as follows:
//
//   3 3 2 2 2 2 2 2 2 2 2 2 1 1 1 1 1 1 1 1 1 1
//   1 0 9 8 7 6 5 4 3 2 1 0 9 8 7 6 5 4 3 2 1 0 9 8 7 6 5 4 3 2 1 0
//  +-+-+-+-+-+---------------------+-------------------------------+
//  |S|R|C|N|r|    Facility         |               Code            |
//  +-+-+-+-+-+---------------------+-------------------------------+
//
//  where
//
//      S - Severity - indicates success/fail
//
//          0 - Success
//          1 - Fail (COERROR)
//
//      R - reserved portion of the facility code, corresponds to NT's
//              second severity bit.
//
//      C - reserved portion of the facility code, corresponds to NT's
//              C field.
//
//      N - reserved portion of the facility code. Used to indicate a
//              mapped NT status value.
//
//      r - reserved portion of the facility code. Reserved for internal
//              use. Used to indicate HRESULT values that are not status
//              values, but are instead message ids for display strings.
//
//      Facility - is the facility code
//
//      Code - is the facility's status code
//
//
// Define the facility codes
//
#define FACILITY_LOCALDB                 0x9C5


//
// Define the severity codes
//
#define LOCALDB_SEVERITY_SUCCESS         0x0
#define LOCALDB_SEVERITY_ERROR           0x2


//
// MessageId: LOCALDB_ERROR_CANNOT_CREATE_INSTANCE_FOLDER
//
// MessageText:
//
// Cannot create folder for the LocalDB instance at: %%LOCALAPPDATA%%\Microsoft\Microsoft SQL Server Local DB\Instances\<instance name>.
//
#define LOCALDB_ERROR_CANNOT_CREATE_INSTANCE_FOLDER ((HRESULT)0x89C50100L)

//
// MessageId: LOCALDB_ERROR_INVALID_PARAMETER
//
// MessageText:
//
// The parameter for the LocalDB Instance API method is incorrect. Consult the API documentation.
//
#define LOCALDB_ERROR_INVALID_PARAMETER  ((HRESULT)0x89C50101L)

//
// MessageId: LOCALDB_ERROR_INSTANCE_EXISTS_WITH_LOWER_VERSION
//
// MessageText:
//
// Unable to create the LocalDB instance with specified version. An instance with the same name already exists, but it has lower version than the specified version.
//
#define LOCALDB_ERROR_INSTANCE_EXISTS_WITH_LOWER_VERSION ((HRESULT)0x89C50102L)

//
// MessageId: LOCALDB_ERROR_CANNOT_GET_USER_PROFILE_FOLDER
//
// MessageText:
//
// Cannot access the user profile folder for local application data (%%LOCALAPPDATA%%).
//
#define LOCALDB_ERROR_CANNOT_GET_USER_PROFILE_FOLDER ((HRESULT)0x89C50103L)

//
// MessageId: LOCALDB_ERROR_INSTANCE_FOLDER_PATH_TOO_LONG
//
// MessageText:
//
// The full path length of the LocalDB instance folder is longer than MAX_PATH. The instance must be stored in folder: %%LOCALAPPDATA%%\Microsoft\Microsoft SQL Server Local DB\Instances\<instance name>.
//
#define LOCALDB_ERROR_INSTANCE_FOLDER_PATH_TOO_LONG ((HRESULT)0x89C50104L)

//
// MessageId: LOCALDB_ERROR_CANNOT_ACCESS_INSTANCE_FOLDER
//
// MessageText:
//
// Cannot access LocalDB instance folder: %%LOCALAPPDATA%%\Microsoft\Microsoft SQL Server Local DB\Instances\<instance name>.
//
#define LOCALDB_ERROR_CANNOT_ACCESS_INSTANCE_FOLDER ((HRESULT)0x89C50105L)

//
// MessageId: LOCALDB_ERROR_CANNOT_ACCESS_INSTANCE_REGISTRY
//
// MessageText:
//
// Unexpected error occurred while trying to access the LocalDB instance registry configuration. See the Windows Application event log for error details.
//
#define LOCALDB_ERROR_CANNOT_ACCESS_INSTANCE_REGISTRY ((HRESULT)0x89C50106L)

//
// MessageId: LOCALDB_ERROR_UNKNOWN_INSTANCE
//
// MessageText:
//
// The specified LocalDB instance does not exist.
//
#define LOCALDB_ERROR_UNKNOWN_INSTANCE   ((HRESULT)0x89C50107L)

//
// MessageId: LOCALDB_ERROR_INTERNAL_ERROR
//
// MessageText:
//
// Unexpected error occurred inside a LocalDB instance API method call. See the Windows Application event log for error details.
//
#define LOCALDB_ERROR_INTERNAL_ERROR     ((HRESULT)0x89C50108L)

//
// MessageId: LOCALDB_ERROR_CANNOT_MODIFY_INSTANCE_REGISTRY
//
// MessageText:
//
// Unexpected error occurred while trying to modify the registry configuration for the LocalDB instance. See the Windows Application event log for error details.
//
#define LOCALDB_ERROR_CANNOT_MODIFY_INSTANCE_REGISTRY ((HRESULT)0x89C50109L)

//
// MessageId: LOCALDB_ERROR_SQL_SERVER_STARTUP_FAILED
//
// MessageText:
//
// Error occurred during LocalDB instance startup: SQL Server process failed to start.
//
#define LOCALDB_ERROR_SQL_SERVER_STARTUP_FAILED ((HRESULT)0x89C5010AL)

//
// MessageId: LOCALDB_ERROR_INSTANCE_CONFIGURATION_CORRUPT
//
// MessageText:
//
// LocalDB instance is corrupted. See the Windows Application event log for error details.
//
#define LOCALDB_ERROR_INSTANCE_CONFIGURATION_CORRUPT ((HRESULT)0x89C5010BL)

//
// MessageId: LOCALDB_ERROR_CANNOT_CREATE_SQL_PROCESS
//
// MessageText:
//
// Error occurred during LocalDB instance startup: unable to create the SQL Server process.
//
#define LOCALDB_ERROR_CANNOT_CREATE_SQL_PROCESS ((HRESULT)0x89C5010CL)

//
// MessageId: LOCALDB_ERROR_UNKNOWN_VERSION
//
// MessageText:
//
// The specified LocalDB version is not available on this computer.
//
#define LOCALDB_ERROR_UNKNOWN_VERSION    ((HRESULT)0x89C5010DL)

//
// MessageId: LOCALDB_ERROR_UNKNOWN_LANGUAGE_ID
//
// MessageText:
//
// Error getting the localized error message. The language specified by 'Language ID' parameter is unknown.
//
#define LOCALDB_ERROR_UNKNOWN_LANGUAGE_ID ((HRESULT)0x89C5010EL)

//
// MessageId: LOCALDB_ERROR_INSTANCE_STOP_FAILED
//
// MessageText:
//
// Stop operation for LocalDB instance failed to complete within the specified time.
//
#define LOCALDB_ERROR_INSTANCE_STOP_FAILED ((HRESULT)0x89C5010FL)

//
// MessageId: LOCALDB_ERROR_UNKNOWN_ERROR_CODE
//
// MessageText:
//
// Error getting the localized error message. The specified error code is unknown.
//
#define LOCALDB_ERROR_UNKNOWN_ERROR_CODE ((HRESULT)0x89C50110L)

//
// MessageId: LOCALDB_ERROR_VERSION_REQUESTED_NOT_INSTALLED
//
// MessageText:
//
// The LocalDB version available on this workstation is lower than the requested LocalDB version.
//
#define LOCALDB_ERROR_VERSION_REQUESTED_NOT_INSTALLED ((HRESULT)0x89C50111L)

//
// MessageId: LOCALDB_ERROR_INSTANCE_BUSY
//
// MessageText:
//
// Requested operation on LocalDB instance cannot be performed because specified instance is currently in use. Stop the instance and try again.
//
#define LOCALDB_ERROR_INSTANCE_BUSY      ((HRESULT)0x89C50112L)

//
// MessageId: LOCALDB_ERROR_INVALID_OPERATION
//
// MessageText:
//
// Default LocalDB instances cannot be created, stopped or deleted manually.
//
#define LOCALDB_ERROR_INVALID_OPERATION  ((HRESULT)0x89C50113L)

//
// MessageId: LOCALDB_ERROR_INSUFFICIENT_BUFFER
//
// MessageText:
//
// The buffer passed to the LocalDB instance API method has insufficient size.
//
#define LOCALDB_ERROR_INSUFFICIENT_BUFFER ((HRESULT)0x89C50114L)

//
// MessageId: LOCALDB_ERROR_WAIT_TIMEOUT
//
// MessageText:
//
// Timeout occurred inside the LocalDB instance API method.
//
#define LOCALDB_ERROR_WAIT_TIMEOUT       ((HRESULT)0x89C50115L)

// MessageId=0x0116 message id is reserved. This message ID will be used for error LOCALDB_ERROR_NOT_INSTALLED.
// This message is specific since it has to be present in SqlUserIntsnace.h because it can be returned by discovery API.
//
//
// MessageId: LOCALDB_ERROR_XEVENT_FAILED
//
// MessageText:
//
// Failed to start XEvent engine within the LocalDB Instance API.
//
#define LOCALDB_ERROR_XEVENT_FAILED      ((HRESULT)0x89C50117L)

//
// MessageId: LOCALDB_ERROR_AUTO_INSTANCE_CREATE_FAILED
//
// MessageText:
//
// Cannot create an automatic instance. See the Windows Application event log for error details.
//
#define LOCALDB_ERROR_AUTO_INSTANCE_CREATE_FAILED ((HRESULT)0x89C50118L)

//
// MessageId: LOCALDB_ERROR_SHARED_NAME_TAKEN
//
// MessageText:
//
// Cannot create a shared instance. The specified shared instance name is already in use.
//
#define LOCALDB_ERROR_SHARED_NAME_TAKEN  ((HRESULT)0x89C50119L)

//
// MessageId: LOCALDB_ERROR_CALLER_IS_NOT_OWNER
//
// MessageText:
//
// API caller is not LocalDB instance owner.
//
#define LOCALDB_ERROR_CALLER_IS_NOT_OWNER ((HRESULT)0x89C5011AL)

//
// MessageId: LOCALDB_ERROR_INVALID_INSTANCE_NAME
//
// MessageText:
//
// Specified LocalDB instance name is invalid.
//
#define LOCALDB_ERROR_INVALID_INSTANCE_NAME ((HRESULT)0x89C5011BL)

//
// MessageId: LOCALDB_ERROR_INSTANCE_ALREADY_SHARED
//
// MessageText:
//
// The specified LocalDB instance is already shared with different shared name.
//
#define LOCALDB_ERROR_INSTANCE_ALREADY_SHARED ((HRESULT)0x89C5011CL)

//
// MessageId: LOCALDB_ERROR_INSTANCE_NOT_SHARED
//
// MessageText:
//
// The specified LocalDB instance is not shared.
//
#define LOCALDB_ERROR_INSTANCE_NOT_SHARED ((HRESULT)0x89C5011DL)

//
// MessageId: LOCALDB_ERROR_ADMIN_RIGHTS_REQUIRED
//
// MessageText:
//
// Administrator privileges are required in order to execute this operation.
//
#define LOCALDB_ERROR_ADMIN_RIGHTS_REQUIRED ((HRESULT)0x89C5011EL)

//
// MessageId: LOCALDB_ERROR_TOO_MANY_SHARED_INSTANCES
//
// MessageText:
//
// There are too many shared instance and we cannot generate unique User Instance Name. Unshare some of the existing shared instances.
//
#define LOCALDB_ERROR_TOO_MANY_SHARED_INSTANCES ((HRESULT)0x89C5011FL)

//
// MessageId: LOCALDB_ERROR_CANNOT_GET_LOCAL_APP_DATA_PATH
//
// MessageText:
//
// Cannot get a local application data path. Most probably a user profile is not loaded. If LocalDB is executed under IIS, make sure that profile loading is enabled for the current user.
//
#define LOCALDB_ERROR_CANNOT_GET_LOCAL_APP_DATA_PATH ((HRESULT)0x89C50120L)

//
// MessageId: LOCALDB_ERROR_CANNOT_LOAD_RESOURCES
//
// MessageText:
//
// Cannot load resources for this DLL. Resources for this DLL should be stored in a subfolder Resources, with the same file name as this DLL and the extension ".RLL".
//
#define LOCALDB_ERROR_CANNOT_LOAD_RESOURCES ((HRESULT)0x89C50121L)

 // Detailed error descriptions
//
// MessageId: LOCALDB_EDETAIL_DATADIRECTORY_IS_MISSING
//
// MessageText:
//
// The "DataDirectory" registry value is missing in the LocalDB instance registry key: %1
//
#define LOCALDB_EDETAIL_DATADIRECTORY_IS_MISSING ((HRESULT)0x89C50200L)

//
// MessageId: LOCALDB_EDETAIL_CANNOT_ACCESS_INSTANCE_FOLDER
//
// MessageText:
//
// Cannot access LocalDB instance folder: %1
//
#define LOCALDB_EDETAIL_CANNOT_ACCESS_INSTANCE_FOLDER ((HRESULT)0x89C50201L)

//
// MessageId: LOCALDB_EDETAIL_DATADIRECTORY_IS_TOO_LONG
//
// MessageText:
//
// The "DataDirectory" registry value is too long in the LocalDB instance registry key: %1
//
#define LOCALDB_EDETAIL_DATADIRECTORY_IS_TOO_LONG ((HRESULT)0x89C50202L)

//
// MessageId: LOCALDB_EDETAIL_PARENT_INSTANCE_IS_MISSING
//
// MessageText:
//
// The "Parent Instance" registry value is missing in the LocalDB instance registry key: %1
//
#define LOCALDB_EDETAIL_PARENT_INSTANCE_IS_MISSING ((HRESULT)0x89C50203L)

//
// MessageId: LOCALDB_EDETAIL_PARENT_INSTANCE_IS_TOO_LONG
//
// MessageText:
//
// The "Parent Instance" registry value is too long in the LocalDB instance registry key: %1
//
#define LOCALDB_EDETAIL_PARENT_INSTANCE_IS_TOO_LONG ((HRESULT)0x89C50204L)

//
// MessageId: LOCALDB_EDETAIL_DATA_DIRECTORY_INVALID
//
// MessageText:
//
// Data directory for LocalDB instance is invalid: %1
//
#define LOCALDB_EDETAIL_DATA_DIRECTORY_INVALID ((HRESULT)0x89C50205L)

//
// MessageId: LOCALDB_EDETAIL_XEVENT_ASSERT
//
// MessageText:
//
// LocalDB instance API: XEvent engine assert: %1 in %2:%3 (%4)
//
#define LOCALDB_EDETAIL_XEVENT_ASSERT    ((HRESULT)0x89C50206L)

//
// MessageId: LOCALDB_EDETAIL_XEVENT_ERROR
//
// MessageText:
//
// LocalDB instance API: XEvent error: %1
//
#define LOCALDB_EDETAIL_XEVENT_ERROR     ((HRESULT)0x89C50207L)

//
// MessageId: LOCALDB_EDETAIL_INSTALLATION_CORRUPTED
//
// MessageText:
//
// LocalDB installation is corrupted. Reinstall the LocalDB.
//
#define LOCALDB_EDETAIL_INSTALLATION_CORRUPTED ((HRESULT)0x89C50208L)

//
// MessageId: LOCALDB_EDETAIL_CANNOT_GET_PROGRAM_FILES_LOCATION
//
// MessageText:
//
// LocalDB XEvent error: cannot determine %ProgramFiles% folder location.
//
#define LOCALDB_EDETAIL_CANNOT_GET_PROGRAM_FILES_LOCATION ((HRESULT)0x89C50209L)

//
// MessageId: LOCALDB_EDETAIL_XEVENT_CANNOT_INITIALIZE
//
// MessageText:
//
// LocalDB XEvent error: Cannot initialize XEvent engine.
//
#define LOCALDB_EDETAIL_XEVENT_CANNOT_INITIALIZE ((HRESULT)0x89C5020AL)

//
// MessageId: LOCALDB_EDETAIL_XEVENT_CANNOT_FIND_CONF_FILE
//
// MessageText:
//
// LocalDB XEvent error: Cannot find XEvents configuration file: %1
//
#define LOCALDB_EDETAIL_XEVENT_CANNOT_FIND_CONF_FILE ((HRESULT)0x89C5020BL)

//
// MessageId: LOCALDB_EDETAIL_XEVENT_CANNOT_CONFIGURE
//
// MessageText:
//
// LocalDB XEvent error: Cannot configure XEvents engine with the configuration file: %1
// HRESULT returned: %2
//
#define LOCALDB_EDETAIL_XEVENT_CANNOT_CONFIGURE ((HRESULT)0x89C5020CL)

//
// MessageId: LOCALDB_EDETAIL_XEVENT_CONF_FILE_NAME_TOO_LONG
//
// MessageText:
//
// LocalDB XEvent error: XEvents engine configuration file too long
//
#define LOCALDB_EDETAIL_XEVENT_CONF_FILE_NAME_TOO_LONG ((HRESULT)0x89C5020DL)

//
// MessageId: LOCALDB_EDETAIL_COINITIALIZEEX_FAILED
//
// MessageText:
//
// CoInitializeEx API failed. HRESULT returned: %1
//
#define LOCALDB_EDETAIL_COINITIALIZEEX_FAILED ((HRESULT)0x89C5020EL)

//
// MessageId: LOCALDB_EDETAIL_PARENT_INSTANCE_VERSION_INVALID
//
// MessageText:
//
// LocalDB parent instance version is invalid: %1
//
#define LOCALDB_EDETAIL_PARENT_INSTANCE_VERSION_INVALID ((HRESULT)0x89C5020FL)

//
// MessageId: LOCALDB_EDETAIL_WINAPI_ERROR
//
// MessageText:
//
// Windows API call %1 returned error code: %2. Windows system error message is: %3Reported at line: %4. %5
//
#define LOCALDB_EDETAIL_WINAPI_ERROR     ((HRESULT)0xC9C50210L)

//
// MessageId: LOCALDB_EDETAIL_UNEXPECTED_RESULT
//
// MessageText:
//
// Function %1 returned %2 at line %3.
//
#define LOCALDB_EDETAIL_UNEXPECTED_RESULT ((HRESULT)0x89C50211L)

//
#endif // _LOCALDB_MESSAGES_H_

#endif //__msodbcsql_h__

# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 

## 5.3.0 - 2018-07-20
Updated PECL release packages. Here is the list of updates:

### Added
- Added support for Azure Key Vault for Always Encrypted functionality. Always Encrypted functionality is supported on Linux and macOS through Azure Key Vault
- Added support for connection resiliency on Linux and macOS (requires version 17.2 or higher of the [ODBC driver](https://docs.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-2017))
- Added support for macOS High Sierra (requires version 17 or higher of the [ODBC driver](https://docs.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-2017))
- Added support for Ubuntu 18.04 (requires version 17.2 or higher of the [ODBC driver](https://docs.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-2017))

### Fixed
- Issue [#577](https://github.com/Microsoft/msphpsql/issues/577) - Idle Connection Resiliency doesn't work with Column Encryption enabled connections (fixed in MS ODBC Driver 17.1)
- Issue [#678](https://github.com/Microsoft/msphpsql/issues/678) - Idle Connection Resiliency doesn't work with Connection Pooling (fixed in MS ODBC Driver 17.1)
- Issue [#699](https://github.com/Microsoft/msphpsql/issues/699) - Binding output parameters fails when the query in the stored procedure returns no data. The test case has been added to the test lab.
- Issue [#705](https://github.com/Microsoft/msphpsql/issues/705) - Always Encrypted - Retrieving a negative decimal value (edge case) as output parameter causes truncation
- Issue [#706](https://github.com/Microsoft/msphpsql/issues/706) - Always Encrypted - Cannot insert double with precision and scale (38, 38)
- Issue [#707](https://github.com/Microsoft/msphpsql/issues/707) - Always Encrypted - Fetching decimals / numerics as output parameters bound to PDO::PARAM_BOOL or PDO::PARAM_INT returns floats, not integers 
- Issue [#735](https://github.com/Microsoft/msphpsql/issues/735) - Extended the buffer size for PDO::lastInsertId so that data types other than integers can be supported
- Pull Request [#759](https://github.com/Microsoft/msphpsql/pull/759) - Removed the limitation of binding a binary as inout param as PDO::PARAM_STR with SQLSRV_ENCODING_BINARY
- Pull Request [#775](https://github.com/Microsoft/msphpsql/pull/775) - Fixed the truncation problem for output params with SQL types specified as SQLSRV_SQLTYPE_DECIMAL or SQLSRV_SQLTYPE_NUMERIC

### Limitations
- No support for inout / output params when using sql_variant type
- In Linux and macOS, setlocale() only takes effect if it is invoked before the first connection. Attempting to set the locale after connecting will not work
- Always Encrypted requires [MS ODBC Driver 17+](https://docs.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-2017)
  - Only Windows Certificate Store and Azure Key Vault are supported. Custom Keystores are not yet supported
  - Issue [#716](https://github.com/Microsoft/msphpsql/issues/716) - With Always Encrypted enabled, named parameters in subqueries are not supported
  - [Always Encrypted limitations](https://docs.microsoft.com/en-us/sql/connect/php/using-always-encrypted-php-drivers?view=sql-server-2017#limitations-of-the-php-drivers-when-using-always-encrypted)

### Known Issues
- Connection pooling on Linux or macOS is not recommended with [unixODBC](http://www.unixodbc.org/) < 2.3.6
- When pooling is enabled in Linux or macOS
  - unixODBC <= 2.3.4 (Linux and macOS) might not return proper diagnostic information, such as error messages, warnings and informative messages
  - due to this unixODBC bug, fetch large data (such as xml, binary) as streams as a workaround. See the examples [here](https://github.com/Microsoft/msphpsql/wiki/Features#pooling)
- With ColumnEncryption enabled, calling stored procedures with XML parameters does not work (Issue [#674](https://github.com/Microsoft/msphpsql/issues/674))

## 5.2.1-preview - 2018-06-01
Updated PECL release packages. Here is the list of updates:

### Added
- Added support for Azure Key Vault for Always Encrypted for basic CRUD functionalities such that Always Encrypted feature is also available to Linux or macOS users 
- Added support for macOS High Sierra (requires [MS ODBC Driver 17+](https://docs.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-2017))

### Fixed
- Issue [#577](https://github.com/Microsoft/msphpsql/issues/577) - Idle Connection Resiliency doesn't work with Column Encryption enabled connection
- Issue [#678](https://github.com/Microsoft/msphpsql/issues/678) - Idle Connection Resiliency doesn't work with Connection Pooling bug
- Issue [#699](https://github.com/Microsoft/msphpsql/issues/699) - Binding output parameter failed when the query in the stored procedure returned no data. The test case has been added to the test lab.
- Issue [#705](https://github.com/Microsoft/msphpsql/issues/705) - AE - Retrieving a negative decimal value (edge case) as output parameter causes truncation
- Issue [#706](https://github.com/Microsoft/msphpsql/issues/706) - AE - Cannot insert double with precision and scale (38, 38)
- Issue [#707](https://github.com/Microsoft/msphpsql/issues/707) - AE - Fetching decimals / numerics as output parameters bound to PDO::PARAM_BOOL or PDO::PARAM_INT returns floats, not integers 
- Issue [#735](https://github.com/Microsoft/msphpsql/issues/735) - Extended the buffer size for PDO lastInsertId such that data types other than integers can be supported
- Pull Request [#759](https://github.com/Microsoft/msphpsql/pull/759) - Removed the limitation of binding a binary as inout param as PDO::PARAM_STR with SQLSRV_ENCODING_BINARY
- Pull Request [#775](https://github.com/Microsoft/msphpsql/pull/775) - Fixed the problem for output params with SQL types specified as SQLSRV_SQLTYPE_DECIMAL or SQLSRV_SQLTYPE_NUMERIC

### Limitations
- No support for inout / output params when using sql_variant type
- In Linux and macOS, setlocale() only takes effect if it is invoked before the first connection. Attempting to set the locale after connection will not work
- Always Encrypted feature, which requires [MS ODBC Driver 17+](https://docs.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-2017)
  - only Windows Certificate Store and Azure Key Vault are supported
  - Issue [#716](https://github.com/Microsoft/msphpsql/issues/716) - With Always Encrypted feature enabled, Named Parameters in Sub Queries are not supported
  - [Always Encrypted limitations](https://docs.microsoft.com/en-us/sql/connect/php/using-always-encrypted-php-drivers?view=sql-server-2017#limitations-of-the-php-drivers-when-using-always-encrypted)

### Known Issues
- Connection pooling on Linux or macOS not recommended with [unixODBC](http://www.unixodbc.org/) < 2.3.6
- When pooling is enabled in Linux or macOS
  - unixODBC <= 2.3.4 (Linux and macOS) might not return proper diagnostics information, such as error messages, warnings and informative messages
  - due to this unixODBC bug, fetch large data (such as xml, binary) as streams as a workaround. See the examples [here](https://github.com/Microsoft/msphpsql/wiki/Features#pooling)
- With ColumnEncryption enabled, calling stored procedures with XML parameters does not work (Issue [#674](https://github.com/Microsoft/msphpsql/issues/674))


## Windows/Linux/macOS 5.2.0 - 2018-03-23
Updated PECL release packages. Here is the list of updates:

### Added
- Added support for Always Encrypted with basic CRUD functionalities (see [here](https://github.com/Microsoft/msphpsql/wiki/Features#aebindparam))
  - Support for Windows Certificate Store (use connection keyword ColumnEncryption)
  - Support for inserting into and modifying an encrypted column
  - Support for fetching from an encrypted column
- Added support for PHP 7.2
- Added support for MS ODBC Driver 17
- Added support for Ubuntu 17 (requires MS ODBC Driver 17)
- Added support for Debian 9 (requires MS ODBC Driver 17)
- Added support for SUSE 12
- Added Driver option to set the MS ODBC driver, Added "Driver" option, valid values are "ODBC Driver 17 for SQL Server", "ODBC Driver 13 for SQL Server", and "ODBC Driver 11 for SQL Server"
  - The default driver is ODBC Driver 17 for SQL Server

### Changed
- Implementation of PDO::lastInsertId($name) to return the last inserted sequence number if the sequence name is supplied to the function ([lastInsertId](https://github.com/Microsoft/msphpsql/wiki/Features#lastinsertid))
- Added immediate binding for security, making it necessary to load PDO before PDO_SQLSRV; full install instructions [here](https://github.com/Microsoft/msphpsql/blob/master/Linux-mac-install.md) and [here](https://docs.microsoft.com/sql/connect/php/loading-the-php-sql-driver)

### Fixed
- Issue [#555](https://github.com/Microsoft/msphpsql/issues/555) - Hebrew strings truncation (requires MS ODBC Driver 17)
- Adjusted precisions for numeric/decimal inputs with Always Encrypted
- Support for non-UTF8 locales in Linux and macOS
- Fixed crash caused by executing an invalid query in a transaction (Issue [#434](https://github.com/Microsoft/msphpsql/issues/434))
- Added error handling for using PDO::SQLSRV_ATTR_DIRECT_QUERY or PDO::ATTR_EMULATE_PREPARES in a Column Encryption enabled connection
- Added error handling for binding TEXT, NTEXT or IMAGE as output parameter (Issue [#231](https://github.com/Microsoft/msphpsql/issues/231))
- PDO::quote with string containing ASCII NUL character (Issue [#538]( https://github.com/Microsoft/msphpsql/issues/538))
- Decimal types with no decimals are correctly handled when AE is enabled (PR [#544](https://github.com/Microsoft/msphpsql/pull/544))
- BIGINT as an output param no longer results in value out of range exception when the returned value is larger than a maximum integer ([PR #567](https://github.com/Microsoft/msphpsql/pull/567))

### Removed
- Dropped support for Ubuntu 15
- Supplying tablename into PDO::lastInsertId($name) no longer return the last inserted row ([lastInsertId](https://github.com/Microsoft/msphpsql/wiki/Features#lastinsertid))

### Limitations
- Always Encrypted is not supported in Linux and macOS
- In Linux and macOS, setlocale() only takes effect if it is invoked before the first connection. Attempting to set the locale after connection will not work
- Always Encrypted functionalities are only supported using MS ODBC Driver 17
- [Always Encrypted limitations](https://github.com/Microsoft/msphpsql/wiki/Features#aelimitation)
- When using sqlsrv_query with Always Encrypted feature, SQL type has to be specified for each input (see [here](https://github.com/Microsoft/msphpsql/wiki/Features#aebindparam))
- No support for inout / output params when using sql_variant type

### Known Issues
- Connection pooling on Linux doesn't work properly when using MS ODBC Driver 17
- When pooling is enabled in Linux or macOS
  - unixODBC <= 2.3.4 (Linux and macOS) might not return proper diagnostics information, such as error messages, warnings and informative messages
  - due to this unixODBC bug, fetch large data (such as xml, binary) as streams as a workaround. See the examples [here](https://github.com/Microsoft/msphpsql/wiki/Connection-Pooling-on-Linux-and-Mac)
- Connection with Connection Resiliency enabled does not resume properly with Connection Pooling (Issue [#678](https://github.com/Microsoft/msphpsql/issues/678))
- With ColumnEncryption enabled, calling stored procedure with XML parameter does not work (Issue [#674](https://github.com/Microsoft/msphpsql/issues/674))
- Cannot connect with both Connection Resiliency enabled and ColumnEncryption enabled (Issue [#577](https://github.com/Microsoft/msphpsql/issues/577))
- With ColumnEncryption enabled, retrieving a negative decimal value as output parameter causes truncation of the last digit (Issue [#705](https://github.com/Microsoft/msphpsql/issues/705))
- With ColumnEncryption enabled, cannot insert a double into a decimal column with precision and scale of (38, 38) (Issue [#706](https://github.com/Microsoft/msphpsql/issues/706))
- With ColumnEncryption enabled, when fetching decimals as output parameters bound to PDO::PARAM_BOOL or PDO::PARAM_INT, floats are returned, not integers (Issue [#707](https://github.com/Microsoft/msphpsql/issues/707))


## Windows/Linux/macOS 5.2.0-RC - 2017-12-20
Updated PECL release packages. Here is the list of updates:

### Added
- Added support for Ubuntu 17 (requires [MSODBC 17 preview](https://github.com/Microsoft/msphpsql/tree/dev/ODBC%2017%20binaries%20preview))
- Added support for Debian 9 (requires [MSODBC 17 preview](https://github.com/Microsoft/msphpsql/tree/dev/ODBC%2017%20binaries%20preview))

### Fixed
- Issue [#555](https://github.com/Microsoft/msphpsql/issues/555) - Hebrew strings truncation (requires [MSODBC 17 preview](https://github.com/Microsoft/msphpsql/tree/dev/ODBC%2017%20binaries%20preview))
- Issue [#615](https://github.com/Microsoft/msphpsql/issues/615) - Added error handling when fetching varchar(max) as a stream with Always Encrypted
- Adjusted precisions for numeric/decimal inputs with Always Encrypted
- Fixed bugs when binding parameters with Always Encrypted
- Fixed warnings as per Prefast code analysis

### Limitations
- In Linux and macOS, setlocale() only takes effect if it is invoked before the first connection. The subsequent locale setting will not work
- Always Encrypted functionalities are only supported using [MSODBC 17 preview](https://github.com/Microsoft/msphpsql/tree/dev/ODBC%2017%20binaries%20preview)
  - ODBC binaries for macOS available upon request
- MSODBC 17 preview msodbcsql.msi only works in Windows10
- [Always Encrypted limitations](https://github.com/Microsoft/msphpsql/wiki/Features#aelimitation)
- When using sqlsrv_query with Always Encrypted feature, SQL type has to be specified for each input (see [here](https://github.com/Microsoft/msphpsql/wiki/Features#aebindparam))
- No support for inout / output params when using sql_variant type

### Known Issues
- Connection pooling on Linux doesn't work properly when using the MSODBC17 preview
- When pooling is enabled in Linux or macOS
  - unixODBC <= 2.3.4 (Linux and macOS) might not return proper diagnostics information, such as error messages, warnings and informative messages
  - due to this unixODBC bug, fetch large data (such as xml, binary) as streams as a workaround. See the examples [here](https://github.com/Microsoft/msphpsql/wiki/Connection-Pooling-on-Linux-and-Mac)

## Windows/Linux/macOS 5.1.2-preview - 2017-11-21
Updated PECL release packages. Here is the list of updates:

### Fixed
- Support for non-UTF8 locales in Linux and macOS
- Fixed crash caused by executing an invalid query in a transaction (Issue [#434](https://github.com/Microsoft/msphpsql/issues/434))
- Fixed regression in sqlsrv_next_result returning a no fields error when the active result set is null (Issue [#581](https://github.com/Microsoft/msphpsql/issues/581))
- Fixed incorrect active result set when sqlsrv_next_result or PDOStatement::nextRowset is called when Column Encryption is enabled (Issue [#574](https://github.com/Microsoft/msphpsql/issues/574))
- Fixed data corruption in fetching from an encrypted max column after calling sqlsrv_next_result or PDOStatemet::nextRowset (Issue [#580](https://github.com/Microsoft/msphpsql/issues/580))
- Added error handling for using PDO::SQLSRV_ATTR_DIRECT_QUERY or PDO::ATTR_EMULATE_PREPARES in a Column Encryption enabled connection
- Added error handling for binding TEXT, NTEXT or IMAGE as output parameter (Issue [#231](https://github.com/Microsoft/msphpsql/issues/231))

### Limitations
- In Linux and macOS, setlocale() only takes effect if it is invoked before the first connection. The subsequent locale setting will not work
- Always Encrypted functionalities are only supported using [MSODBC 17 preview](https://github.com/Microsoft/msphpsql/tree/dev/ODBC%2017%20binaries%20preview)
  - ODBC binaries for macOS available upon request
- MSODBC 17 preview msodbcsql.msi only works in Windows10
- [Always Encrypted limitations](https://github.com/Microsoft/msphpsql/wiki/Features#aelimitation)
- When using sqlsrv_query with Always Encrypted feature, SQL type has to be specified for each input (see [here](https://github.com/Microsoft/msphpsql/wiki/Features#aebindparam))
- No support for inout / output params when using sql_variant type

### Known Issues
- Binding decimal input as a string when Column Encryption is enabled may change the precision of the input
- Connection pooling on Linux doesn't work properly when using the MSODBC17 preview
- When pooling is enabled in Linux or macOS
  - unixODBC <= 2.3.4 (Linux and macOS) might not return proper diagnostics information, such as error messages, warnings and informative messages
  - due to this unixODBC bug, fetch large data (such as xml, binary) as streams as a workaround. See the examples [here](https://github.com/Microsoft/msphpsql/wiki/Connection-Pooling-on-Linux-and-Mac)

## Windows/Linux 5.1.1-preview - 2017-10-20
Updated PECL release packages. Here is the list of updates:

### Fixed
- PDO::quote with string containing ASCII NUL character (Issue [#538]( https://github.com/Microsoft/msphpsql/issues/538))
- Appropriate error message is returned when calling nextRowset() or sqlsrv_next_result() on an empty result set (issue [#507 ](https://github.com/Microsoft/msphpsql/issues/507))
- Decimal types with no decimals are correctly handled when AE is enabled (PR [#544](https://github.com/Microsoft/msphpsql/pull/544))
- Search for installed ODBC drivers in Linux/macOS first before attempting to connect using the default ODBC driver 
- BIGINT as an output param no longer results in value out of range exception when the returned value is larger than a maximum integer ([PR #567](https://github.com/Microsoft/msphpsql/pull/567))

### Limitations
- Always Encrypted functionalities are only supported using [MSODBC 17 preview](https://github.com/Microsoft/msphpsql/tree/dev/ODBC%2017%20binaries%20preview)
  - ODBC binaries for macOS available upon request
- MSODBC 17 preview msodbcsql.msi only works for Windows10
- [Always Encrypted limitations](https://github.com/Microsoft/msphpsql/wiki/Features#aelimitation)
- When using sqlsrv_query with Always Encrypted feature, SQL type has to be specified for each input (see [here](https://github.com/Microsoft/msphpsql/wiki/Features#aebindparam))
- No support for inout / output params when using sql_variant type

### Known Issues
- Connection pooling on Linux doesn't work properly when using the MSODBC17 preview
- When pooling is enabled in Linux or MAC
  - unixODBC <= 2.3.4 (Linux and MAC) might not return proper diagnostics information, such as error messages, warnings and informative messages
  - due to this unixODBC bug, fetch large data (such as xml, binary) as streams as a workaround. See the examples [here](https://github.com/Microsoft/msphpsql/wiki/Connection-Pooling-on-Linux-and-Mac)

## Windows/Linux 5.1.0-preview - 2017-09-15
Updated PECL release packages. Here is the list of updates:

### Added
- Added support for SUSE 12
- Added support for Always Encrypted with basic CRUD functionalities (see [here](https://github.com/Microsoft/msphpsql/wiki/Features#aebindparam))
  - Support for Windows Certificate Store (use connection keyword ColumnEncryption)
  - Support for custom key store provider (use connection keywords ColumnEncryption, CEKeystoreProvider, CEKeystoreName, CEKeystoreEncryptKey)
  - Support for inserting into an encrypted column
  - Support for fetching from an encrypted column
- Added support for MSODBC 17 preview
- Added Driver option to set the ODBC driver, Added"Driver" option, valid values are ODBC Driver 13 for SQL Server,ODBC Driver 11 for SQL Server, and ODBC Driver 17 for SQL Server
  - If the user intends to use the new Always Encrypted features, we recommend you to specify explicitly the 'Driver' option to 'ODBC Driver 17 for SQL Server' in the connection string

### Limitations
- Always Encrypted functionalities are only supported using [MSODBC 17 preview](https://github.com/Microsoft/msphpsql/tree/dev/ODBC%2017%20binaries%20preview)
  - ODBC binaries for macOS available upon request
- MSODBC 17 preview msodbcsql.msi only works for Windows10
- [Always Encrypted limitations](https://github.com/Microsoft/msphpsql/wiki/Features#aelimitation)
- when using sqlsrv_query with Always Encrypted feature, SQL type has to be specified for each input (see [here](https://github.com/Microsoft/msphpsql/wiki/Features#aebindparam))
- No support for inout / output params when using sql_variant type

### Known Issues
- Connection pooling on Linux doesn't work properly if the user uses the MSODBC17 preview
- PDO::quote returns truncated string with garbage characters appended if the string contains a ASCII NUL ('/0') character
- Binding decimal type when using Always Encrypted in the SQLSRV x64 driver returns an error during insertion when the input does not have any decimal places
- When pooling is enabled in Linux or MAC
  - unixODBC <= 2.3.4 (Linux and MAC) might not return proper diagnostics information, such as error messages, warnings and informative messages
  - due to this unixODBC bug, fetch large data (such as xml, binary) as streams as a workaround. See the examples [here](https://github.com/Microsoft/msphpsql/wiki/Connection-Pooling-on-Linux-and-Mac)

## Windows/Linux/MAC 5.0.0-preview - 2017-07-31
Updated PECL release packages. Here is the list of updates:

### Added
- Added support for PHP 7.2 Beta 1

### Changed
- Implementation of PDO::lastInsertId($name) to return the last inserted sequence number if the sequence name is supplied to the function ([lastInsertId](https://github.com/Microsoft/msphpsql/wiki/Features#lastinsertid))
    
### Removed
- No longer support Ubuntu 15
- Supplying tablename into PDO::lastInsertId($name) no longer return the last inserted row ([lastInsertId](https://github.com/Microsoft/msphpsql/wiki/Features#lastinsertid))

### Limitation
- No support for inout / output params when using sql_variant type

### Known Issues
- When pooling is enabled in Linux or MAC
  - unixODBC <= 2.3.4 (Linux and MAC) might not return proper diagnostics information, such as error messages, warnings and informative messages
  - due to this unixODBC bug, fetch large data (such as xml, binary) as streams as a workaround. See the examples [here](https://github.com/Microsoft/msphpsql/wiki/Connection-Pooling-on-Linux-and-Mac)

## Windows/Linux/MAC 4.3.0 - 2017-07-06
Production Ready release for SQLSRV and PDO_SQLSRV drivers on Sierra, El Capitan, Debian 8, Ubuntu 15, Ubuntu 16, CentOS 7, and Windows. Here is the changlog since the last Production Ready release.

### Added
- Added Unicode Column name support ([issue #138](https://github.com/Microsoft/msphpsql/issues/138)). 
- Support for Always On Availability groups via Transparent Network IP Resolution ([TNIR](https://github.com/Microsoft/msphpsql/wiki/Features#TNIR))  
- Added support for sql_variant data type with limitation ([issue #51](https://github.com/Microsoft/msphpsql/issues/51) and [issue #127](https://github.com/Microsoft/msphpsql/issues/127))
- Support drivers on Debian Jessie (tested on Debian 8.7) 
- Connection Resiliency support in Windows 
- Connection pooling support for Linux and macOS  
- Support for Mac (El Capitan and above) 
- Azure Active Directory Authentication with ActiveDirectoryPassword and SqlPassword

### Fixed
- Fixed PECL installation errors when PHP was installed from source ([issue #213](https://github.com/Microsoft/msphpsql/issues/213)). 
- Fixed the assertion error (Linux) when fetching data from a binary column using the binary encoding ([issue #226](https://github.com/Microsoft/msphpsql/issues/226)). 
- Fixed issue output parameters bound to empty string ([issue #182](https://github.com/Microsoft/msphpsql/issues/182)). 
- Fixed a memory leak in closing connection resources. 
- Fixed load ordering issue in MacOS ([issue #417](https://github.com/Microsoft/msphpsql/issues/417)) 
- Added a workaround for a bug in unixODBC 2.3.4 when connection pooling is enabled. 
- Fixed the issue with driver loading order in macOS 
- Fixed null returned when an empty string is set to an output parameter ([issue #308](https://github.com/Microsoft/msphpsql/issues/308)).
- #### Fixed in SQLSRV  
	- Fixed sqlsrv client buffer size to only allow positive integers ([issue #228](https://github.com/Microsoft/msphpsql/issues/228)). 
	- Fixed sqlsrv_num_rows() when the client buffered result is null ([issue #330](https://github.com/Microsoft/msphpsql/issues/330)). 
	- Fixed issues with sqlsrv_has_rows() to prevent it from moving statement cursor ([issue #37](https://github.com/Microsoft/msphpsql/issues/37)). 
	- Fixed conversion warnings because of some const chars ([issue #332](https://github.com/Microsoft/msphpsql/issues/332)). 
	- Fixed debug abort error when building the driver in debug mode with PHP 7.1. 
	- Fixed string truncation when binding varchar(max), nvarchar(max), varbinary(max), and xml types ([issue #231](https://github.com/Microsoft/msphpsql/issues/231)). 
	- Fixed fatal error when fetching empty nvarchar ([issue #69](https://github.com/Microsoft/msphpsql/issues/69)). 
	- Fixed fatal error when calling sqlsrv_fetch() with an out of bound offset for SQLSRV_SCROLL_ABSOLUTE ([issue #223](https://github.com/Microsoft/msphpsql/issues/223)).
 - #### Fixed in PDO_SQLSRV
	- Fixed issue with SQLSRV_ATTR_FETCHES_NUMERIC_TYPE when column return type is set on statement ([issue #173](https://github.com/Microsoft/msphpsql/issues/173)). 
	- Improved performance by implementing a cache to store column SQL types and display sizes ([issue #189](https://github.com/Microsoft/msphpsql/issues/189)). 
	- Fixed segmentation fault with PDOStatement::getColumnMeta() when the supplied column index is out of range ([issue #224](https://github.com/Microsoft/msphpsql/issues/224)). 
	- Fixed issue with the unsupported attribute PDO::ATTR_PERSISTENT in connection ([issue #65](https://github.com/Microsoft/msphpsql/issues/65)). 
	- Fixed the issue with executing DELETE operation on a non-existent value ([issue #336](https://github.com/Microsoft/msphpsql/issues/336)). 
	- Fixed incorrectly binding of unicode parameter when emulate prepare is on and the encoding is set at the statement level ([issue #92](https://github.com/Microsoft/msphpsql/issues/92)). 
	- Fixed binary column binding when emulate prepare is on ([issue #140](https://github.com/Microsoft/msphpsql/issues/140)). 
	- Fixed wrong value returned when fetching varbinary value on Linux ([issue #270](https://github.com/Microsoft/msphpsql/issues/270)). 
	- Fixed binary data not returned when the column is bound by name ([issue #35](https://github.com/Microsoft/msphpsql/issues/35)). 
	- Fixed exception thrown on closeCursor() when the statement has not been executed ([issue #267](https://github.com/Microsoft/msphpsql/issues/267)).
	
### Limitation
- No support for inout / output params when using sql_variant type

### Known Issues
- When pooling is enabled in Linux or MAC
  - unixODBC <= 2.3.4 (Linux and MAC) might not return proper diagnostics information, such as error messages, warnings and informative messages
  - due to this unixODBC bug, fetch large data (such as xml, binary) as streams as a workaround. See the examples [here](https://github.com/Microsoft/msphpsql/wiki/Connection-Pooling-on-Linux-and-Mac)

## Windows/Linux/MAC 4.3.0-RC1 - 2017-06-21
Here is the list of updates:

### Added
- Transparent Network IP Resolution ([TNIR](https://github.com/Microsoft/msphpsql/wiki/Features#TNIR)) feature.

### Fixed
- Fixed a memory leak in closing connection resources.
- Fixed load ordering issue in MacOS ([issue #417](https://github.com/Microsoft/msphpsql/issues/417))

### Limitation
- No support for inout / output params when using sql_variant type

### Known Issues
- When pooling is enabled in Linux or MAC
  - unixODBC <= 2.3.4 (Linux and MAC) might not return proper diagnostics information, such as error messages, warnings and informative messages
  - due to this unixODBC bug, fetch large data (such as xml, binary) as streams as a workaround. See the examples [here](https://github.com/Microsoft/msphpsql/wiki/Connection-Pooling-on-Linux-and-Mac)
  

## Windows/Linux/MAC 4.2.0-preview - 2017-05-19
Here is the list of updates:

### Added
- Added support for sql_variant data type with limitation ([issue #51](https://github.com/Microsoft/msphpsql/issues/51) and [issue #127](https://github.com/Microsoft/msphpsql/issues/127))
- Support drivers on Debian Jessie (tested on Debian 8.7)

### Fixed
- Increased Test Coverage to 75%
- Bug fixes after running static analysis

### Limitation
- No support for inout / output params when using sql_variant type

### Known Issues
- User defined data types
- When pooling is enabled in Linux or MAC
  - unixODBC <= 2.3.4 (Linux and MAC) might not return proper diagnostics information, such as error messages, warnings and informative messages
  - due to this unixODBC bug, fetch large data (such as xml, binary) as streams as a workaround. See the examples [here](https://github.com/Microsoft/msphpsql/wiki/Connection-Pooling-on-Linux-and-Mac)

## Windows/Linux/MAC 4.1.9-preview - 2017-05-08
- Updated documentation for Readme regarding instructions for Linux and MAC 
- Updated PECL release packages. Here is the list of updates:
### Added
- Azure Active Directory Authentication with ActiveDirectoryPassword and SqlPassword

### Fixed
- Fixed output parameter returning garbage when the parameter is initialized to a type that is different from the output type ([issue #378](https://github.com/Microsoft/msphpsql/issues/378)).

#### PDO_SQLSRV only
- Fixed incorrectly binding of unicode parameter when emulate prepare is on and the encoding is set at the statement level ([issue #92](https://github.com/Microsoft/msphpsql/issues/92)).
- Fixed binary column binding when emulate prepare is on ([issue #140](https://github.com/Microsoft/msphpsql/issues/140)).

### Known Issues
- User defined data types and SQL_VARIANT ([issue #127](https://github.com/Microsoft/msphpsql/issues/127)).
- When pooling is enabled in Linux or MAC
  - unixODBC 2.3.1 (Linux) and unixODBC 2.3.4 (MAC) might not return proper diagnostics information, such as error messages, warnings and informative messages
  - due to this unixODBC bug, fetch large data (such as xml, binary) as streams as a workaround. See the examples [here](https://github.com/Microsoft/msphpsql/wiki/Connection-Pooling-on-Linux-and-Mac)

## Windows/Linux/MAC 4.1.8-preview - 2017-04-10
Updated documentation for Readme regarding instructions for Linux and MAC 
Updated PECL release packages. Here is the list of updates:
### Added
- [Connection Resiliency](https://github.com/Microsoft/msphpsql/wiki/Connection-Resiliency) now supported in Windows 
- [Connection pooling](https://github.com/Microsoft/msphpsql/wiki/Connection-Pooling-on-Linux-and-Mac) now works in MAC 

### Fixed
#### SQLSRV and PDO_SQLSRV
- Added a workaround for a bug in unixODBC 2.3.4 when connection pooling is enabled.
- Fixed the issue in MAC such that which driver is loaded first no longer matters.

#### SQLSRV only
- Fixed sqlsrv_num_rows() when the client buffered result is null ([issue #330](https://github.com/Microsoft/msphpsql/issues/330)).
- Fixed conversion warnings because of some const chars ([issue #332](https://github.com/Microsoft/msphpsql/issues/332)).

#### PDO_SQLSRV only
- Improved performance by implementing a cache to store column SQL types and display sizes ([issue #189](https://github.com/Microsoft/msphpsql/issues/189)).
- Fixed issue with the unsupported attribute PDO::ATTR_PERSISTENT in connection ([issue #65](https://github.com/Microsoft/msphpsql/issues/65)).
- Fixed the issue when deleting something that doesn't exist ([issue #336](https://github.com/Microsoft/msphpsql/issues/336)).

### Known Issues
- User defined data types and SQL_VARIANT ([issue #127](https://github.com/Microsoft/msphpsql/issues/127)).
- Binary column binding with emulate prepare ([issue #140](https://github.com/Microsoft/msphpsql/issues/140)).
- When pooling is enabled in Linux or MAC
  - unixODBC 2.3.1 (Linux) and unixODBC 2.3.4 (MAC) might not return proper diagnostics information, such as error messages, warnings and informative messages
  - due to this unixODBC bug, fetch large data (such as xml, binary) as streams as a workaround. See the examples [here](https://github.com/Microsoft/msphpsql/wiki/Connection-Pooling-on-Linux-and-Mac)

## Windows/Linux/MAC 4.1.7-preview - 2017-03-07
Updated PECL release packages. Here is the list of updates:
### Added
- The early technical preview (ETP) for SQLSRV and PDO_SQLSRV drivers for MAC with basic functionalities is now available. Both drivers has been built and tested on MAC OS version El Capitan (OS X 10.11).

### Fixed
#### SQLSRV and PDO_SQLSRV
- Fixed null returned when an empty string is set to an output parameter ([issue #308](https://github.com/Microsoft/msphpsql/issues/308)).
- Fixed memory leaks in buffered result sets.
- Fixed clang compile errors.

#### SQLSRV only
- Fixed debug abort error when building the driver in debug mode with PHP 7.1.
- Fixed string truncation when binding varchar(max), nvarchar(max), varbinary(max), and xml types ([issue #231](https://github.com/Microsoft/msphpsql/issues/231)).
- Fixed fatal error when fetching empty nvarchar ([issue #69](https://github.com/Microsoft/msphpsql/issues/69)).
- Fixed fatal error when calling sqlsrv_fetch() with an out of bound offset for SQLSRV_SCROLL_ABSOLUTE ([issue #223](https://github.com/Microsoft/msphpsql/issues/223)).

#### PDO_SQLSRV only
- Fixed wrong value returned when fetching varbinary value on Linux ([issue #270](https://github.com/Microsoft/msphpsql/issues/270)).
- Fixed binary data not returned when the column is bound by name ([issue #35](https://github.com/Microsoft/msphpsql/issues/35)).
- Fixed exception thrown on closeCursor() when the statement has not been executed ([issue #267](https://github.com/Microsoft/msphpsql/issues/267)).

### Known Issues
- User defined data types and SQL_VARIANT ([issue #127](https://github.com/Microsoft/msphpsql/issues/127)).
- Binary column binding with emulate prepare ([issue #140](https://github.com/Microsoft/msphpsql/issues/140)).
- Segmentation fault may result when an unsupported attribute is used for connection.

#### MAC only
- If loading both sqlsrv and pdo_sqlsrv, the order matters (even when dynamically). For PDO_SQLSRV scripts, load pdo_sqlsrv.so first. For SQLSRV scripts, load sqlsrv.so first.
- Connection pooling not working.

## Windows/Linux 4.1.6 - 2017-02-03
Updated PECL release packages. Here is the list of updates:
### Added
- Merged Linux and Windows code.
- Enabled connection pooling with unixODBC. To enable pooling:
  - in odbcinst.ini, add `Pooling=Yes` to the `[ODBC]` section and a positive `CPTimeout` value to `[ODBC Driver 13 for SQL Server]` section. See http://www.unixodbc.org/doc/conn_pool.html for detailed instructions.

###Fixed
- Fixed issues with sqlsrv_has_rows() to prevent it from moving statement cursor ([issue #37](https://github.com/Microsoft/msphpsql/issues/37)).
- Fixed sqlsrv client buffer size to only allow positive integers ([issue #228](https://github.com/Microsoft/msphpsql/issues/228)).
- Fixed PECL installation errors when PHP was installed from source ([issue #213](https://github.com/Microsoft/msphpsql/issues/213)).
- Fixed segmentation fault with PDOStatement::getColumnMeta() when the supplied column index is out of range ([issue #224](https://github.com/Microsoft/msphpsql/issues/224)).
- Fixed the assertion error (Linux) when fetching data from a binary column using the binary encoding ([issue #226](https://github.com/Microsoft/msphpsql/issues/226)).

## Windows 4.1.5 - 2017-01-19
Updated Windows drivers (4.1.5) compiled with PHP 7.0.14 and 7.1 are available. Here is the list of updates:

### Added
- Added Unicode Column name support([issue #138](https://github.com/Microsoft/msphpsql/issues/138)).

###Fixed
- Fixed issue output parameters bound to empty string ([issue #182](https://github.com/Microsoft/msphpsql/issues/182)).
- Fixed issue with SQLSRV_ATTR_FETCHES_NUMERIC_TYPE when column return type is set on statement ([issue #173](https://github.com/Microsoft/msphpsql/issues/173)). 


### Changed
- Code structure is updated to facilitate the development; shared codes between both drivers are moved to "shared" folder to avoid code duplication issues in development. To build the driver from source:
    - if you are building the driver from source using PHP source, copy the "shared" folder as a subfolder to both the sqlsrv and pdo_sqlsrv folders. 

## Linux 4.0.8 - 2016-12-19
Production release of Linux drivers is available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. Here is the list of updates:

### Added
- Added `SQLSRV_ATTR_FETCHES_NUMERIC_TYPE` attribute support in PDO_SQLSRV driver.`SQLSRV_ATTR_FETCHES_NUMERIC_TYPE` connection attribute flag handles numeric fetches from columns with numeric Sql types (only bit, integer, smallint, tinyint, float and real). This flag can be turned on by setting its value in  `PDO::setAttribute` to `true`, For example,
               `$conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE,true);` 
		  If `SQLSRV_ATTR_FETCHES_NUMERIC_TYPE`  is set to `true` the results from an integer column will be represented as an `int`, likewise, Sql types float and real will be represented as `float`. 
		  Note for exceptions:
	 - When connection option flag `ATTR_STRINGIFY_FETCHES` is on, even when `SQLSRV_ATTR_FETCHES_NUMERIC_TYPE` is on, the return value will still be string.
	 - 	When the returned PDO type in bind column is `PDO_PARAM_INT`, the return value from a integer column will be int even if `SQLSRV_ATTR_FETCHES_NUMERIC_TYPE` is off.
- Added Unicode Column name support([issue #138](https://github.com/Microsoft/msphpsql/issues/138)).

###Fixed
- Fixed issue with SQLSRV_ATTR_FETCHES_NUMERIC_TYPE when column return type is set on statement ([issue #173](https://github.com/Microsoft/msphpsql/issues/173)). 
- Fixed precision issues when double data type returned as strings using buffered queries in PDO_SQLSRV driver.
- Fixed issue with buffered cursor in PDO_SQLSRV driver when CharacterSet is UTF-8 ([issue #192](https://github.com/Microsoft/msphpsql/issues/192)).
- Fixed segmentation fault in error cases when error message is returned with emulate prepare attribute is set to true in PDO_SQLSRV driver.
- Fixed issue with empty output parameters on stored procedure([issue #182](https://github.com/Microsoft/msphpsql/issues/182)).
- Fixed memory leaks in buffered queries.


## Linux 4.0.7 - 2016-11-23
Linux drivers compiled with PHP 7.0.13 are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. 

### Added
- Ported buffered cursor to Linux.

### Changed
- Code structure is updated to facilitate the development; shared codes between both drivers are moved to "shared" folder to avoid code duplication issues in development. To build the driver from source, use "packagize" script as follows:
	 - if you are using the phpize, clone or download the “source”, run the script within the “source” directory and then run phpize.
	 - if you are building the driver from source using PHP source, give the path to the PHP source to the script. 

### Fixed
 - Fixed string truncation error when inserting long strings.
 - Fixed querying from large column name.
 - Fixed issue with trailing garbled characters in string retrieval.
 - Fixed issue with detecting invalid UTF-16 strings coming from server.
 - Fixed issues with binding input text, ntext, and image parameters.

## Linux 4.0.6 - 2016-10-25
Linux drivers compiled with PHP 7.0.12 are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. 

### Changed
 - Drivers versioning has been redesigned as Major#.Minor#.Release#.Build#. Build number is specific to binaries and it doesn't match with the number on the source.
 -  Compiler C++ 11 is enabled in config file.

### Fixed
 - Fixed the issue with duplicate warning messages in PDO_SQLSRV drivers when error mode is set to PDO::ERRMODE_WARNING.
 - Fixed the issue with invalid UTF-8 strings, those are detected before executing any queries and proper error message is returned. 
 - Fixed segmentation fault in sqlsrv_fetch_object and sqlsrv_fetch_array function.

## Windows 4.1.4 - 2016-10-25
Windows drivers compiled with PHP 7.0.12  and 7.1 are available. Here is the list of updates:

### Changed
 - Drivers versioning has been redesigned as Major#.Minor#.Release#.Build#. Build number is specific to binaries and it doesn't match with the number on the source.

### Fixed
 - Fixed the issue with duplicate warning messages in PDO_SQLSRV drivers when error mode is set to PDO::ERRMODE_WARNING.
  
## Linux 4.0.5 - 2016-10-04
Linux drivers compiled with PHP 7.0.11 are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. 

### Fixed
 - Fixed segmentation fault when calling PDOStatement::getColumnMeta on RedHat 7.2.
 - Fixed segmentation fault when fetch mode is set to ATTR_EMULATE_PREPARES on RedHat 7.2.
 - Fixed [issue #139](https://github.com/Microsoft/msphpsql/issues/139) : sqlsrv_fetch_object calls custom class constructor in static context and outputs an error.

## Windows 4.1.3 - 2016-10-04
Updated Windows drivers (4.1.3) compiled with PHP 7.0.11  and 7.1.0RC3 are available. Here is the list of updates:

### Fixed
- Fixed [issue #139](https://github.com/Microsoft/msphpsql/issues/139) : sqlsrv_fetch_object calls custom class constructor in static context and outputs an error.

##Linux 4.0.4 - 2016-09-09
Linux drivers compiled with PHP 7.0.10 are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. 

### Added
- Added Support for EMULATE_PREPARE feature.
- Added following integer SQL Types constants for cases which function-like SQL types constants cannot be used e.g. type comparison:

    SQLSRV constant | Typical SQL Server data type | SQL type identifier
    ------------ | ----------------------- | ----------------------
   SQLSRV_SQLTYPE_DECIMAL | decimal       | SQL_DECIMAL
   SQLSRV_SQLTYPE_NUMERIC | numeric       | SQL_NUMERIC
   SQLSRV_SQLTYPE_CHAR    | char          | SQL_CHAR
   SQLSRV_SQLTYPE_NCHAR   | nchar         | SQL_WCHAR
   SQLSRV_SQLTYPE_VARCHAR | varchar       | SQL_VARCHAR
   SQLSRV_SQLTYPE_NVARCHAR | nvarchar     | SQL_WVARCHAR
   SQLSRV_SQLTYPE_BINARY   | binary       | SQL_BINARY
   SQLSRV_SQLTYPE_VARBINARY  | varbinary   | SQL_VARBINARY

    Note: These constants should be used in type comparison operations (refer to issue [#87](https://github.com/Microsoft/msphpsql/issues/87) and [#99](https://github.com/Microsoft/msphpsql/issues/99) ), and don't replace the function like constants with similar syntax. For binding parameters you should use the function-like constants, otherwise you'll get an error.

### Fixed
 - Fixed  undefined symbols at SQL* error when loading the drivers.
 - Fixed undefined symbol issues at LocalAlloc and LocalFree on RedHat7.2.
 - Fixed [issue #144](https://github.com/Microsoft/msphpsql/issues/144) (floating point exception).
 - Fixed [issue #119](https://github.com/Microsoft/msphpsql/issues/119) (modifying class name in sqlsrv_fetch_object).

## Windows 4.1.2 - 2016-09-09
Updated Windows drivers (4.1.2) compiled with PHP 7.0.10 are available. Here is the list of updates:

### Added
- Added following integer SQL Types constants for cases which function-like SQL types constants cannot be used e.g. type comparison:

    SQLSRV constant | Typical SQL Server data type | SQL type identifier
    ------------ | ----------------------- | ----------------------
   SQLSRV_SQLTYPE_DECIMAL | decimal       | SQL_DECIMAL
   SQLSRV_SQLTYPE_NUMERIC | numeric       | SQL_NUMERIC
   SQLSRV_SQLTYPE_CHAR    | char          | SQL_CHAR
   SQLSRV_SQLTYPE_NCHAR   | nchar         | SQL_WCHAR
   SQLSRV_SQLTYPE_VARCHAR | varchar       | SQL_VARCHAR
   SQLSRV_SQLTYPE_NVARCHAR | nvarchar     | SQL_WVARCHAR
   SQLSRV_SQLTYPE_BINARY   | binary       | SQL_BINARY
   SQLSRV_SQLTYPE_VARBINARY  | varbinary   | SQL_VARBINARY

    Note: These constants should be used in type comparison operations (refer to issue [#87](https://github.com/Microsoft/msphpsql/issues/87) and [#99](https://github.com/Microsoft/msphpsql/issues/99) ), and don't replace the function like constants with similar syntax. For binding parameters you should use the function-like constants, otherwise you'll get an error.

### Fixed
 - Fixed [issue #119](https://github.com/Microsoft/msphpsql/issues/119) (modifying class name in sqlsrv_fetch_object).
 
 
## Linux 4.0.3 - 2016-08-23
Linux drivers compiled with PHP 7.0.9 are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. 

### Fixed
 - Fixed data corruption in binding integer parameters.
 - Fixed invalid sql_display_size error.
 - Fixed issue with invalid statement options.
 - Fixed binding bit parameters.

## Windows 4.1.1 - 2016-08-22
Updated Windows drivers(4.1.1) compiled with PHP 7.0.9 are available and include a couple of bug fixes:

### Fixed
- Fixed issue with storing integers in varchar field.
- Fixed issue with invalid connection handler if one connection fails.
- Fixed crash when emulate prepare is on.


## Linux 4.0.2 - 2016-07-29

### Fixed
 - The PDO_SQLSRV driver no longer requires PDO to be built as a shared extension.
 - Fixed an issue with format specifiers in error messages.
 - Fixed a segmentation fault when using buffered cursors.
 - Fixed an issue whereby calling sqlsrv_rows_affected on an empty result set would return a null result instead of 0.
 - Fixed an issue with error messages when there is an error in sizes in SQLSRV_SQLTYPE_*.

## Windows 4.1.0 - 2016-07-28

### Fixed
 - `SQLSRV_ATTR_FETCHES_NUMERIC_TYPE`  connection attribute flag is added to PDO_SQLSRV driver to handle numeric fetches from columns with numeric Sql types (only bit, integer, smallint, tinyint, float and real). This flag can be turned on by setting its value in  `PDO::setAttribute` to `true`, For example,
               `$conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE,true);` 
		  If `SQLSRV_ATTR_FETCHES_NUMERIC_TYPE`  is set to `true` the results from an integer column will be represented as an `int`, likewise, Sql types float and real will be represented as `float`. 
		  Note for exceptions:
	 - When connection option flag `ATTR_STRINGIFY_FETCHES` is on, even when `SQLSRV_ATTR_FETCHES_NUMERIC_TYPE` is on, the return value will still be string.
	 - 	When the returned PDO type in bind column is `PDO_PARAM_INT`, the return value from a integer column will be int even if `SQLSRV_ATTR_FETCHES_NUMERIC_TYPE` is off.
 - Fixed float truncation when using buffered query. 
 - Fixed handling of Unicode strings and binary when emulate prepare is on in `PDOStatement::bindParam`.  To bind a unicode string, `PDO::SQLSRV_ENCODING_UTF8` should be set using `$driverOption`, and to bind a string to column of Sql type binary, `PDO::SQLSRV_ENCODING_BINARY` should be set.
 - Fixed string truncation in bind output parameters when the size is not set and the length of initialized variable is less than the output.
 - Fixed bind string parameters as bidirectional parameters (`PDO::PARAM_INPUT_OUTPUT `) in PDO_SQLSRV driver. Note for output or bidirectional parameters, `PDOStatement::closeCursor` should be called to get the output value.

 
## Linux 4.0.1 - 2016-07-09

### Added
- Added support for PDO_SQLSRV driver on RedHat 7.

###Changed
- Improved handling varchar(MAX).
- Improved handling basic stream operations.

## Linux 4.0.0 - 2016-06-11

### Added
- The early technical preview (ETP) for SQLSRV and PDO_SQLSRV drivers for Linux with basic functionalities is now available. The SQLSRV driver has been built and tested on Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2, and PDO_SQLSRV driver has been built and tested on Ubuntu 15.04, Ubuntu 16.04.

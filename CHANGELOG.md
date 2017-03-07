# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 

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
- If loading both sqlsrv and pdo_sqlsrv, the order matters (even when dynamically). For PDO scripts, load pdo_sqlsrv.so first.
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

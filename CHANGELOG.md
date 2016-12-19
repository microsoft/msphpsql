# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 


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
 - Fixed the issue with  duplicate warning messages in PDO_SQLSRV drivers when error mode is set to PDO::ERRMODE_WARNING.
 - Fixed the issue with invalid UTF-8 strings, those are detected before executing any queries and proper error message is returned. 
 - Fixed segmentation fault in sqlsrv_fetch_object and sqlsrv_fetch_array function.

## Linux 4.0.5 - 2016-10-04
Linux drivers compiled with PHP 7.0.11 are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. 

### Fixed
 - Fixed segmentation fault when calling PDOStatement::getColumnMeta on RedHat 7.2.
 - Fixed segmentation fault when fetch mode is set to ATTR_EMULATE_PREPARES on RedHat 7.2.
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

## Linux 4.0.3 - 2016-08-23
Linux drivers compiled with PHP 7.0.9 are available for Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2. 

### Fixed
 - Fixed data corruption in binding integer parameters.
 - Fixed invalid sql_display_size error.
 - Fixed issue with invalid statement options.
 - Fixed binding bit parameters.


## Linux 4.0.2 - 2016-07-29

### Fixed
 - The PDO_SQLSRV driver no longer requires PDO to be built as a shared extension.
 - Fixed an issue with format specifiers in error messages.
 - Fixed a segmentation fault when using buffered cursors.
 - Fixed an issue whereby calling sqlsrv_rows_affected on an empty result set would return a null result instead of 0.
 - Fixed an issue with error messages when there is an error in sizes in SQLSRV_SQLTYPE_*.

## Linux 4.0.1 - 2016-08-09

### Added
- Added support for PDO_SQLSRV driver on RedHat 7.

###Changed
- Improved handling varchar(MAX).
- Improved handling basic stream operations.

## Linux 4.0.0 - 2016-06-11

### Added
- The early technical preview (ETP) for SQLSRV and PDO_SQLSRV drivers for Linux with basic functionalities is now available. The SQLSRV driver has been built and tested on Ubuntu 15.04, Ubuntu 16.04, and RedHat 7.2, and PDO_SQLSRV driver has been built and tested on Ubuntu 15.04, Ubuntu 16.04.


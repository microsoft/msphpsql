# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 

## Windows 4.1.5 - 2017-01-18
Updated Windows drivers (4.1.5) compiled with PHP 7.0.14 and 7.1 are available. Here is the list of updates:

### Added
- Added Unicode Column name support([issue #138](https://github.com/Microsoft/msphpsql/issues/138)).

###Fixed
- Fixed issue output parameters bound to empty string ([issue #182](https://github.com/Microsoft/msphpsql/issues/182)).
- Fixed issue with SQLSRV_ATTR_FETCHES_NUMERIC_TYPE when column return type is set on statement ([issue #173](https://github.com/Microsoft/msphpsql/issues/173)). 


### Changed
- Code structure is updated to facilitate the development; shared codes between both drivers are moved to "shared" folder to avoid code duplication issues in development. To build the driver from source:
    - if you are building the driver from source using PHP source, copy the "shared" folder as a subfolder to both the sqlsrv and pdo_sqlsrv folders. 

## Windows 4.1.4 - 2016-10-25
Windows drivers compiled with PHP 7.0.12  and 7.1 are available. Here is the list of updates:

### Changed
 - Drivers versioning has been redesigned as Major#.Minor#.Release#.Build#. Build number is specific to binaries and it doesn't match with the number on the source.

### Fixed
 - Fixed the issue with duplicate warning messages in PDO_SQLSRV drivers when error mode is set to PDO::ERRMODE_WARNING.
 
## Windows 4.1.3 - 2016-10-04
Updated Windows drivers (4.1.3) compiled with PHP 7.0.11  and 7.1.0RC3 are available. Here is the list of updates:

### Fixed
- Fixed [issue #139](https://github.com/Microsoft/msphpsql/issues/139) : sqlsrv_fetch_object calls custom class constructor in static context and outputs an error.

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
 
 
## Windows 4.1.1 - 2016-08-22
Updated Windows drivers(4.1.1) compiled with PHP 7.0.9 are available and include a couple of bug fixes:

### Fixed
- Fixed issue with storing integers in varchar field.
- Fixed issue with invalid connection handler if one connection fails.
- Fixed crash when emulate prepare is on.


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

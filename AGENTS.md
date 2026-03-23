# AI Agent Guidelines for Microsoft Drivers for PHP for SQL Server

This document provides guidance for AI coding agents working with the msphpsql GitHub repository — the open-source home of the **sqlsrv** and **pdo_sqlsrv** PHP extensions for Microsoft SQL Server.

## Quick Start

### Essential Context Files

| File | Purpose |
|------|---------|
| [README.md](README.md) | Project overview, prerequisites, build basics |
| [CHANGELOG.md](CHANGELOG.md) | Full release history; current version at top |
| [Linux-mac-install.md](Linux-mac-install.md) | Step-by-step install guide for every supported Unix platform |
| [buildscripts/README.md](buildscripts/README.md) | Detailed Windows build instructions (manual & scripted) |
| [source/shared/version.h](source/shared/version.h) | Single source of truth for version numbers and preview state |

### Repository at a Glance

| Area | Path | Description |
|------|------|-------------|
| Shared core | `source/shared/` | C++ code shared by both extensions |
| SQLSRV extension | `source/sqlsrv/` | Procedural PHP API extension |
| PDO_SQLSRV extension | `source/pdo_sqlsrv/` | PDO interface extension |
| Packaging script | `source/packagize.sh` | Copies `shared/` into each extension dir for PECL packaging |
| Functional tests | `test/functional/` | `.phpt` test suites for both extensions |
| Extended tests | `test/extended/` | Always Encrypted v2 special tests |
| BVT tests | `test/bvt/` | Basic verification tests |
| Performance tests | `test/Performance/` | Benchmark suite (phpbench) |
| Windows build scripts | `buildscripts/` | `builddrivers.py`, `buildtools.py` |
| Samples | `sample/` | `sqlsrv_sample.php`, `pdo_sqlsrv_sample.php` |
| CI (Linux/Mac) | [azure-pipelines.yml](azure-pipelines.yml) | Azure Pipelines — builds PHP from source, runs tests |
| CI (Windows) | [appveyor.yml](appveyor.yml) | AppVeyor — PHP SDK build matrix |
| Docker | [Dockerfile-msphpsql](Dockerfile-msphpsql) | Ubuntu 24.04 dev environment image |

## Architecture

### Two Extensions, One Shared Core

The drivers ship as two separate PHP extensions that share a common C++ core layer:

```
source/
├── shared/            # Core logic (ODBC, connection, statement, stream, error handling)
│   ├── core_conn.cpp          # Connection management
│   ├── core_init.cpp          # Driver initialization
│   ├── core_results.cpp       # Result set processing
│   ├── core_stmt.cpp          # Statement execution
│   ├── core_stream.cpp        # Stream handling
│   ├── core_util.cpp          # Utility functions
│   ├── core_sqlsrv.h          # Main shared header (includes Zend API, ODBC, platform abstractions)
│   ├── version.h              # Version constants
│   ├── FormattedPrint.cpp/h   # Cross-platform printf implementation
│   ├── StringFunctions.cpp/h  # Cross-platform string operations
│   ├── localizationimpl.cpp   # Localization/encoding support
│   ├── msodbcsql.h            # ODBC driver header
│   └── xplat_*.h, typedefs_for_linux.h, interlockedatomic*.h  # Platform abstractions
│
├── sqlsrv/            # Procedural API extension
│   ├── conn.cpp       # sqlsrv_connect(), sqlsrv_close(), etc.
│   ├── init.cpp       # Extension module init/shutdown
│   ├── stmt.cpp       # sqlsrv_query(), sqlsrv_fetch(), etc.
│   ├── util.cpp       # Error handling, logging
│   ├── config.m4      # Linux/Mac autoconf build
│   └── config.w32     # Windows JScript build
│
├── pdo_sqlsrv/        # PDO extension
│   ├── pdo_dbh.cpp    # PDO database handle (new PDO(...))
│   ├── pdo_init.cpp   # Extension module init/shutdown
│   ├── pdo_parser.cpp # SQL parser for named params
│   ├── pdo_stmt.cpp   # PDOStatement operations
│   ├── pdo_util.cpp   # Error handling
│   ├── config.m4      # Linux/Mac autoconf build
│   └── config.w32     # Windows JScript build
│
└── packagize.sh       # Copies shared/ into sqlsrv/ and pdo_sqlsrv/ for PECL
```

### Key Design Patterns

- **Zend API integration**: All C++ code uses the PHP Zend Engine API. The shared header `core_sqlsrv.h` includes `<php.h>` and `<zend.h>` with MSVC warning suppression for known benign warnings (4100, 4127, 4146, 4244, 4267, 4456, 4457, 4706).
- **Platform abstraction**: Unix builds use `typedefs_for_linux.h`, `xplat_*.h`, and `interlockedatomic*.h` to map Win32 types/functions to POSIX equivalents (e.g., `stricmp` → `strcasecmp`, `GetLastError` → `errno`).
- **ODBC layer**: All SQL Server communication goes through the Microsoft ODBC Driver for SQL Server (version 17 or 18). The code never talks to TDS directly.
- **MARS**: Multiple Active Result Sets enabled by default via `MARS_Connection={Yes}`.

## Build System

### Linux / macOS (autoconf)

Each extension has a `config.m4` that registers the extension with PHP's build system:

```bash
# Build from PHP source tree (extensions in ext/ directory)
phpize
./configure --with-sqlsrv  # or --with-pdo_sqlsrv
make
make install
```

The `config.m4` includes logic to avoid double-compiling shared sources when both extensions are built as static (non-shared) in the same PHP build.

### Windows (PHP SDK + JScript)

Each extension has a `config.w32` (JScript) for the PHP Windows SDK build:

- **Visual Studio**: VS2019 for PHP 8.3, VS2022 for PHP 8.4+
- **Security flags**: `/NXCOMPAT`, `/DYNAMICBASE`, `/guard:cf`, `/ZH:SHA_256`, `/W4`, `/WX`, `/CETCOMPAT`, `/Qspectre` (for VCVERS >= 1913)
- **Scripted builds**: `buildscripts/builddrivers.py` automates downloading PHP SDK, source, and building both extensions

### PECL Packaging

`source/packagize.sh` copies `shared/` files into both `sqlsrv/` and `pdo_sqlsrv/` directories, making each extension self-contained for PECL distribution. **Note**: PECL version strings must not contain hyphens (e.g., `5.13.0beta1` not `5.13.0-beta1`).

### Docker

The `Dockerfile-msphpsql` provides a complete Ubuntu 24.04 development environment with PHP, ODBC 18, and all build dependencies pre-installed.

## Version Management

All version information lives in `source/shared/version.h`:

| Constant | Purpose | Current |
|----------|---------|---------|
| `SQLVERSION_MAJOR` | Incompatible API changes | 5 |
| `SQLVERSION_MINOR` | Backward-compatible features | 13 |
| `SQLVERSION_PATCH` | Backward-compatible bug fixes | 0 |
| `SQLVERSION_BUILD` | Build metadata (appended as `+N`) | 0 |
| `PREVIEW` | Set to 1+ for beta releases, 0 for stable | 0 |
| `PHP_SQLSRV_VERSION` | PECL version string for sqlsrv | "5.13.0" |
| `PHP_PDO_SQLSRV_VERSION` | PECL version string for pdo_sqlsrv | "5.13.0" |

**Versioning rules**:
- Follows [semantic versioning](https://semver.org/)
- When `PREVIEW > 0`, the version string becomes `MAJOR.MINOR.PATCH-betaN` (e.g., `5.13.0-beta1`)
- PECL versions use `MAJOR.MINOR.PATCHbetaN` (no hyphen)
- The `PHP_SQLSRV_VERSION` and `PHP_PDO_SQLSRV_VERSION` constants must be updated manually (cannot use preprocessor macros due to PECL/Pickle constraints)

## Testing

### Test Format

Tests use PHP's standard `.phpt` format:

```
--TEST--
Description of the test
--SKIPIF--
<?php require('skipif_*.inc'); ?>
--FILE--
<?php
// Test code here
?>
--EXPECT--
Expected output
```

### Test Organization

| Directory | Contents | Count |
|-----------|----------|-------|
| `test/functional/sqlsrv/` | SQLSRV extension tests | ~260+ tests |
| `test/functional/pdo_sqlsrv/` | PDO_SQLSRV extension tests | ~280+ tests |
| `test/functional/setup/` | SQL scripts, BCP data files, Python setup scripts |
| `test/functional/inc/` | Shared binary data (GIF files), TVP test data |
| `test/extended/` | Always Encrypted v2 tests | ~12 tests |
| `test/bvt/` | Basic verification tests |
| `test/Performance/` | phpbench-based benchmarks |

### Test Configuration

Tests read connection info from `MsSetup.inc` (present in each test directory). Connection settings can be overridden via environment variables:

| Variable | Purpose | Default |
|----------|---------|---------|
| `MSSQL_SERVER` | SQL Server hostname | `TARGET_SERVER` placeholder |
| `MSSQL_DATABASE_NAME` | Database name | `TARGET_DATABASE` placeholder |
| `MSSQL_UID` | SQL login username | `TARGET_USERNAME` placeholder |
| `MSSQL_PWD` | SQL login password | `TARGET_PASSWORD` placeholder |
| `MSSQL_DRIVER` | ODBC driver version string | `ODBC Driver 18 for SQL Server` |

### Running Tests

```bash
# Run all sqlsrv tests
php run-tests.php test/functional/sqlsrv/

# Run all pdo_sqlsrv tests
php run-tests.php test/functional/pdo_sqlsrv/

# Run a single test
php run-tests.php test/functional/sqlsrv/sqlsrv_connect.phpt
```

### Test Helper Includes

| File | Purpose |
|------|---------|
| `MsSetup.inc` | Connection configuration (server, database, credentials, ODBC driver) |
| `MsCommon.inc` | ~600 lines of shared helpers: `connect()`, `isWindows()`, `testMode()`, `traceMode()`, table creation, data generators |
| `MsHelper.inc` | Additional helper functions |
| `MsData.inc` | Test data constants |
| `skipif*.inc` | Skip conditions (OS, extensions, features, server version) |
| `tools.inc` | Diagnostic and utility functions |

### Test Categories

Tests cover a wide range of features:
- **Connectivity**: Connect, close, connection options, connection resiliency, connection pooling
- **Data types**: All SQL Server types, Unicode, large objects, date/time, decimal precision
- **Fetch modes**: Forward-only, scrollable, buffered queries, client-side cursors
- **Transactions**: Commit, rollback, savepoints
- **Stored procedures**: Input/output params, return values, result sets
- **Always Encrypted**: Column encryption, key vault, enclave operations
- **Azure AD / Entra**: Authentication modes (`ActiveDirectoryPassword`, `ActiveDirectoryMSI`, etc.)
- **Table-Valued Parameters (TVP)**: Sending structured data
- **Bulk Copy (BCP)**: `test/functional/setup/*.dat` and `*.fmt` files

## Core Principles

1. **Shared Core, Separate Extensions**: Common logic goes in `source/shared/`. Extension-specific API binding goes in `source/sqlsrv/` or `source/pdo_sqlsrv/`. Never duplicate logic between extensions.
2. **ODBC Abstraction**: All database communication uses ODBC. Never implement protocol-level (TDS) communication.
3. **Cross-Platform**: Code must compile and run on Windows, Linux, and macOS. Use the platform abstraction headers (`xplat_*.h`, `typedefs_for_linux.h`) for OS-specific functionality.
4. **PHP Zend Compatibility**: Follow Zend Engine API conventions. Use `zend_*` functions for memory management. Be aware of thread-safety requirements (ZTS vs NTS builds).
5. **Security by Default**: Windows builds enforce `/NXCOMPAT`, `/DYNAMICBASE`, `/guard:cf`, `/Qspectre`, `/CETCOMPAT`. Never log credentials or connection strings in error output.
6. **Backward Compatibility**: Avoid breaking existing PHP userland code. New connection options should default to preserving existing behavior.
7. **Test Coverage**: All changes must include `.phpt` tests. Both `sqlsrv` and `pdo_sqlsrv` should be tested when shared code changes. Tests have standard dependencies connectivity to a server and database. If a test includes dependencies beyond the standard that aren't present, they should include relevant skip logic.

## Common Tasks

### Bug Fix Workflow

1. Reproduce the issue — write or identify a `.phpt` test that demonstrates the bug
2. Determine if the fix belongs in `source/shared/` (affects both extensions) or in an extension-specific file
3. Implement the fix
4. Add or update `.phpt` tests in both `test/functional/sqlsrv/` and `test/functional/pdo_sqlsrv/` if shared code changed
5. Update `CHANGELOG.md` with a description under the appropriate version heading

### Adding a New Connection Option

1. Add the ODBC keyword mapping in `source/shared/core_sqlsrv.h` (connection option definitions)
2. Register the option in `source/shared/core_conn.cpp` connection option arrays
3. Expose in `source/sqlsrv/conn.cpp` and/or `source/pdo_sqlsrv/pdo_dbh.cpp`
4. Default to backward-compatible value
5. Add tests for the new option in both test suites
6. Document in `CHANGELOG.md`

### Version Bump

1. Edit `source/shared/version.h`:
   - Update `SQLVERSION_MAJOR`, `SQLVERSION_MINOR`, and/or `SQLVERSION_PATCH`
   - Update `PHP_SQLSRV_VERSION` and `PHP_PDO_SQLSRV_VERSION` string literals
   - Set `PREVIEW` to 0 for stable, or 1+ for beta releases
2. Update `CHANGELOG.md` with new version heading and release notes
3. The file header comment `Microsoft Drivers X.YY for PHP for SQL Server` in source files may need updating for major/minor bumps

### Adding a New Test

1. Create a `.phpt` file in the appropriate `test/functional/` subdirectory
2. Include standard skip logic: `--SKIPIF--` with `<?php require('skipif_*.inc'); ?>`
3. Use `MsCommon.inc` helpers for connection setup and data generation
4. Ensure the test is deterministic and cleans up after itself (drop temp tables, close connections)
5. Provide explicit `--EXPECT--` or `--EXPECTF--` output

### Preparing a PECL Release

1. Set version in `source/shared/version.h` (ensure `PREVIEW` is set correctly)
2. Run `source/packagize.sh` to copy `shared/` into both extension directories
3. Verify `PHP_SQLSRV_VERSION` / `PHP_PDO_SQLSRV_VERSION` strings have no hyphens
4. Each extension directory becomes a self-contained PECL package

## CI / CD

### GitHub CI

| Pipeline | Platform | Trigger |
|----------|----------|---------|
| Azure Pipelines (`azure-pipelines.yml`) | Linux, macOS | Push to `dev`, `fix/*`; PRs to `dev` |
| AppVeyor (`appveyor.yml`) | Windows | Push (except legacy branches) |
| Codecov (`codecov.yml`) | Windows | Coverage reporting |

The Azure Pipelines job builds PHP from source on each target platform, compiles both extensions, then runs the full `.phpt` test suite against a SQL Server (Docker container on Linux, local instance on Windows/macOS).

### Internal CI/CD (msphpsql in ADO)

The internal Azure DevOps repo (`msphpsql`) contains the production CI/CD pipelines for official builds, matrix testing, signing, packaging, and release. See that repo's `AGENTS.md` for pipeline architecture details.

## Branching and Contributions

- **`dev`** — active development branch; all PRs should target `dev`
- **`main`** (or `master`) — stable release branch
- **`fix/*`** — bug fix branches (trigger CI)
- Pull requests require CI to pass before merge

## External Resources

| Resource | Link |
|----------|------|
| Microsoft Docs (PHP Drivers) | https://learn.microsoft.com/sql/connect/php/microsoft-php-driver-for-sql-server |
| ODBC Driver Docs | https://learn.microsoft.com/sql/connect/odbc/microsoft-odbc-driver-for-sql-server |
| PHP Internals (Building) | https://wiki.php.net/internals/windows/stepbystepbuild_sdk_2 |
| PECL sqlsrv | https://pecl.php.net/package/sqlsrv |
| PECL pdo_sqlsrv | https://pecl.php.net/package/pdo_sqlsrv |
| GitHub Issues | https://github.com/microsoft/msphpsql/issues |
| GitHub Releases | https://github.com/microsoft/msphpsql/releases |

## Getting Help

- Check `test/functional/` for usage patterns and expected behavior
- Read `MsCommon.inc` for available test helper functions
- Consult `core_sqlsrv.h` for internal data structures and ODBC wrappers
- For ODBC behavior, reference the Microsoft ODBC Driver documentation
- For PHP extension API, reference the [PHP Internals Book](https://www.phpinternalsbook.com/)

---

*This document is automatically loaded as context for AI agents working in this repository.*

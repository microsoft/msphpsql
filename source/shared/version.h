#ifndef VERSION_H
#define VERSION_H
//---------------------------------------------------------------------------------------------------------------------------------
// File: version.h
// Contents: Version number constants
//
// Microsoft Drivers 5.10 for PHP for SQL Server
// Copyright(c) Microsoft Corporation
// All rights reserved.
// MIT License
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files(the ""Software""), 
//  to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, 
//  and / or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions :
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
// THE SOFTWARE IS PROVIDED *AS IS*, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
//  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
//  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS 
//  IN THE SOFTWARE.
//---------------------------------------------------------------------------------------------------------------------------------

// helper macros to stringify the a macro value
#define STRINGIFY(a) TOSTRING(a)
#define TOSTRING(a) #a

// Increase Major number with backward incompatible breaking changes.
// Increase Minor with backward compatible new functionalities and API changes.
// Increase Patch for backward compatible fixes.
#define SQLVERSION_MAJOR 5
#define SQLVERSION_MINOR 10
#define SQLVERSION_PATCH 1
#define SQLVERSION_BUILD 0

// For previews, set this constant to 1, 2 and so on. Otherwise, set it to 0
#define PREVIEW 0
#define SEMVER_PRERELEASE

// Semantic versioning build metadata, build meta data is not counted in precedence order.
#define SEMVER_BUILDMETA

#if SQLVERSION_BUILD > 0
#undef SEMVER_BUILDMETA
#define SEMVER_BUILDMETA "+" STRINGIFY( SQLVERSION_BUILD )
#endif

// Main version, dot separated 3 digits, Major.Minor.Patch
#define VER_APIVERSION_STR      STRINGIFY( SQLVERSION_MAJOR ) "." STRINGIFY( SQLVERSION_MINOR ) "." STRINGIFY( SQLVERSION_PATCH )

// Semantic versioning: 
// For stable releases leave SEMVER_PRERELEASE empty
// Otherwise, for pre-releases, add '-' and change it to "beta" with a preview number
#if PREVIEW > 0
#undef SEMVER_PRERELEASE
#define SEMVER_PRERELEASE "beta" STRINGIFY(PREVIEW)
#define VER_FILEVERSION_STR     VER_APIVERSION_STR "-" SEMVER_PRERELEASE SEMVER_BUILDMETA
#else
#define VER_FILEVERSION_STR     VER_APIVERSION_STR SEMVER_PRERELEASE SEMVER_BUILDMETA
#endif

#define _FILEVERSION            SQLVERSION_MAJOR,SQLVERSION_MINOR,SQLVERSION_PATCH,SQLVERSION_BUILD

// PECL package version ('-' or '+' is not allowed) - to support Pickle do not use macros below
#define PHP_SQLSRV_VERSION      "5.10.1"
#define PHP_PDO_SQLSRV_VERSION  "5.10.1"

#endif // VERSION_H

//---------------------------------------------------------------------------------------------------------------------------------
// File: sqlversion.h
//
// Microsoft Drivers 4.0 for PHP for SQL Server
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

#ifndef _SQLVERSION_H_
#define _SQLVERSION_H_

#define USE_SQL_VERSION 1

#define VER_SQL_MAJOR  13
#define VER_SQL_MINOR  0

#define VER_SQL_BUILD  0
#define VER_SQL_REVISION  0


#define VER_SQL_ASSEMBLY_MAJOR  13
#define VER_SQL_ASSEMBLY_MINOR  0
#define VER_SQL_ASSEMBLY_SERVICEABILITY  0
#define VER_SQL_ASSEMBLY_REVISION  0

//
// For GDR branch, following line must be turned on
//
// #define GDR_BUILD   1

//
// The associated QFE build# is decided by the released team.
// environment variable defined in $(BASEDIR)\project.mk
//
// For QFE branch it's always 0.
//

#define VER_ASSOCIATED_HOTFIX_BUILD_STR   "0"

//
// VER_SP_LEVEL is used to specify Service Pack level
//
#define VER_SP_LEVEL  0

#endif

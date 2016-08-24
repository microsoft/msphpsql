
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

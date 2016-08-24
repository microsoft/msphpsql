//-----------------------------------------------------------------------------
// File:        winnls.h
//
// Copyright:   Copyright (c) Microsoft Corporation
//
// Contents:    Contains the minimal definitions to build on non-Windows platforms
//
// Comments:
//
// owners:
//    See source code ownership database in SqlDevDash 
//-----------------------------------------------------------------------------

#ifndef XPLAT_WINNLS_H
#define XPLAT_WINNLS_H

#include <stdlib.h>
#include "typedefs_for_linux.h"

struct threadlocaleinfostruct;
struct threadmbcinfostruct;
typedef struct threadlocaleinfostruct * pthreadlocinfo;
typedef struct threadmbcinfostruct * pthreadmbcinfo;

typedef struct localeinfo_struct
{
    pthreadlocinfo locinfo;
    pthreadmbcinfo mbcinfo;
} _locale_tstruct, *_locale_t;

#define LOCALE_SDECIMAL               0x0000000E   // decimal separator
#define LOCALE_SCURRENCY              0x00000014   // local monetary symbol
#define LOCALE_SMONDECIMALSEP         0x00000016   // monetary decimal separator
#define LOCALE_SMONTHOUSANDSEP        0x00000017   // monetary thousand separator
#define LOCALE_SMONGROUPING           0x00000018   // monetary grouping
#define LOCALE_ILDATE                 0x00000022   // long date format ordering (derived from LOCALE_SLONGDATE, use that instead)
#define LOCALE_ITIME                  0x00000023   // time format specifier (derived from LOCALE_STIMEFORMAT, use that instead)
#define LOCALE_SABBREVMONTHNAME1      0x00000044   // abbreviated name for January

#define LOCALE_IDEFAULTLANGUAGE       0x00000009   // default language id
#define LOCALE_IDEFAULTCOUNTRY        0x0000000A   // default country/region code, deprecated
#define LOCALE_IDEFAULTCODEPAGE       0x0000000B   // default oem code page
#define LOCALE_IDEFAULTANSICODEPAGE   0x00001004   // default ansi code page
#define LOCALE_IDEFAULTMACCODEPAGE    0x00001011   // default mac code page

#define LOCALE_STIMEFORMAT            0x00001003   // time format string, eg "HH:mm:ss"

typedef DWORD LCTYPE;

#define NORM_IGNORECASE           0x00000001  // ignore case
#define NORM_IGNORENONSPACE       0x00000002  // ignore nonspacing chars
#define NORM_IGNORESYMBOLS        0x00000004  // ignore symbols

#define LINGUISTIC_IGNORECASE     0x00000010  // linguistically appropriate 'ignore case'
#define LINGUISTIC_IGNOREDIACRITIC 0x00000020  // linguistically appropriate 'ignore nonspace'

#define NORM_IGNOREKANATYPE       0x00010000  // ignore kanatype
#define NORM_IGNOREWIDTH          0x00020000  // ignore width
#define NORM_LINGUISTIC_CASING    0x08000000  // use linguistic rules for casing


#define NORM_IGNORECASE           0x00000001  // ignore case

#define MB_PRECOMPOSED            0x00000001  // use precomposed chars
#define MB_COMPOSITE              0x00000002  // use composite chars
#define MB_USEGLYPHCHARS          0x00000004  // use glyph chars, not ctrl chars
#define MB_ERR_INVALID_CHARS      0x00000008  // error for invalid chars

#define WC_COMPOSITECHECK         0x00000200  // convert composite to precomposed
#define WC_DISCARDNS              0x00000010  // discard non-spacing chars
#define WC_SEPCHARS               0x00000020  // generate separate chars
#define WC_DEFAULTCHAR            0x00000040  // replace w/ default char


typedef WORD LANGID;




#define NLS_VALID_LOCALE_MASK  0x000fffff

#define MAKELANGID(p, s)       ((((WORD  )(s)) << 10) | (WORD  )(p))
#define MAKELCID(lgid, srtid)  ((DWORD)((((DWORD)((WORD  )(srtid))) << 16) |  \
                                         ((DWORD)((WORD  )(lgid)))))
#define LANG_NEUTRAL                     0x00
#define SUBLANG_DEFAULT                             0x01    // user default
#define SUBLANG_SYS_DEFAULT                         0x02    // system default
#define SORT_DEFAULT                     0x0     // sorting default
#define LANG_USER_DEFAULT      (MAKELANGID(LANG_NEUTRAL, SUBLANG_DEFAULT))
#define LOCALE_USER_DEFAULT    (MAKELCID(LANG_USER_DEFAULT, SORT_DEFAULT))
#define SUBLANG_ENGLISH_US                          0x01    // English (USA)
#define LANG_ENGLISH                     0x09
#define LOCALE_ENGLISH_US   MAKELCID(MAKELANGID(LANG_ENGLISH, SUBLANG_ENGLISH_US), SORT_DEFAULT)
#define LANG_SYSTEM_DEFAULT    (MAKELANGID(LANG_NEUTRAL, SUBLANG_SYS_DEFAULT))
#define LOCALE_SYSTEM_DEFAULT  (MAKELCID(LANG_SYSTEM_DEFAULT, SORT_DEFAULT))

BOOL
WINAPI
IsDBCSLeadByte(
    __inn BYTE  TestChar);


#ifdef MPLAT_UNIX
// XPLAT_ODBC_TODO: VSTS 718708 Localization
// Find way to remove this
LCID GetUserDefaultLCID();
#endif


BOOL IsValidCodePage(UINT  CodePage);

#define HIGH_SURROGATE_START  0xd800
#define HIGH_SURROGATE_END    0xdbff
#define LOW_SURROGATE_START   0xdc00
#define LOW_SURROGATE_END     0xdfff
#define IS_HIGH_SURROGATE(wch) (((wch) >= HIGH_SURROGATE_START) && ((wch) <= HIGH_SURROGATE_END))
#define IS_LOW_SURROGATE(wch)  (((wch) >= LOW_SURROGATE_START) && ((wch) <= LOW_SURROGATE_END))

int
GetLocaleInfoA(
    __inn LCID     Locale,
    __inn LCTYPE   LCType,
    __out_ecount_opt(cchData) LPSTR  lpLCData,
    __inn int      cchData);
int
GetLocaleInfoW(
    __inn LCID     Locale,
    __inn LCTYPE   LCType,
    __out_ecount_opt(cchData) LPWSTR  lpLCData,
    __inn int      cchData);
#ifdef UNICODE
#define GetLocaleInfo  GetLocaleInfoW
#else
#define GetLocaleInfo  GetLocaleInfoA
#endif // !UNICODE


#endif // XPLAT_WINNLS_H

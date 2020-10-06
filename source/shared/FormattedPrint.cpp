
//-----------------------------------------------------------------------------
// File:        FormattedPrint.cpp
//
//
// Contents:    Contains functions for handling Windows format strings
//              and UTF-16 on non-Windows platforms
//
// Microsoft Drivers 5.9 for PHP for SQL Server
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

#include <FormattedPrint.h>
#include <errno.h>

#include <iconv.h>

#include "StringFunctions.h"

// XPLAT_ODBC_TODO VSTS 819733 - MPlat: Reconcile std c++ usage between platforms
#include <vector>
#include <algorithm>
#include "sal_def.h"

#define PTR_IS_INT64 1

// SQL Server does not have a long double type
#define LONGDOUBLE_IS_DOUBLE 1
typedef double _LONGDOUBLE;

// XPLAT_ODBC_TODO VSTS VSTS 718708 Localization
#define _SAFECRT_IMPL

#if !defined(_countof)
#define _countof(_Array) (sizeof(_Array) / sizeof(_Array[0]))
#endif // _countof

#ifndef _VALIDATE_RETURN
#define _VALIDATE_RETURN( expr, errorcode, retexpr )                           \
    {                                                                          \
        int _Expr_val=!!(expr);                                                \
        if ( !( _Expr_val ) )                                                  \
        {                                                                      \
            assert(false);                                                     \
            errno = errorcode;                                                 \
            return ( retexpr );                                                \
        }                                                                      \
    }
#endif  /* _VALIDATE_RETURN */


static const char *__nullstring = "(null)";  /* string to print on null ptr */
static const wchar_t *__wnullstring = L"(null)";/* string to print on null ptr */

#define BUFFERSIZE    512
#define MAXPRECISION  BUFFERSIZE
#define _CVTBUFSIZE (309+40) /* # of digits in max. dp value + slop */


/* flag definitions */
#define FL_SIGN       0x00001   /* put plus or minus in front */
#define FL_SIGNSP     0x00002   /* put space or minus in front */
#define FL_LEFT       0x00004   /* left justify */
#define FL_LEADZERO   0x00008   /* pad with leading zeros */
#define FL_LONG       0x00010   /* long value given */
#define FL_SHORT      0x00020   /* short value given */
#define FL_SIGNED     0x00040   /* signed data given */
#define FL_ALTERNATE  0x00080   /* alternate form requested */
#define FL_NEGATIVE   0x00100   /* value is negative */
#define FL_FORCEOCTAL 0x00200   /* force leading '0' for octals */
#define FL_LONGDOUBLE 0x00400   /* long double value given */
#define FL_WIDECHAR   0x00800   /* wide characters */
#define FL_LONGLONG   0x01000   /* long long value given */
#define FL_I64        0x08000   /* __int64 value given */


/* state definitions */
enum STATE {
    ST_NORMAL,          /* normal state; outputting literal chars */
    ST_PERCENT,         /* just read '%' */
    ST_FLAG,            /* just read flag character */
    ST_WIDTH,           /* just read width specifier */
    ST_DOT,             /* just read '.' */
    ST_PRECIS,          /* just read precision specifier */
    ST_SIZE,            /* just read size specifier */
    ST_TYPE             /* just read type specifier */
    ,ST_INVALID           /* Invalid format */

};

#define NUMSTATES (ST_INVALID + 1)

/* character type values */
enum CHARTYPE {
    CH_OTHER,           /* character with no special meaning */
    CH_PERCENT,         /* '%' */
    CH_DOT,             /* '.' */
    CH_STAR,            /* '*' */
    CH_ZERO,            /* '0' */
    CH_DIGIT,           /* '1'..'9' */
    CH_FLAG,            /* ' ', '+', '-', '#' */
    CH_SIZE,            /* 'h', 'l', 'L', 'N', 'F', 'w' */
    CH_TYPE             /* type specifying character */
};


static const unsigned char __lookuptable_s[] = {
 /* ' ' */  0x06,
 /* '!' */  0x80,
 /* '"' */  0x80,
 /* '#' */  0x86,
 /* '$' */  0x80,
 /* '%' */  0x81,
 /* '&' */  0x80,
 /* ''' */  0x00,
 /* '(' */  0x00,
 /* ')' */  0x10,
 /* '*' */  0x03,
 /* '+' */  0x86,
 /* ',' */  0x80,
 /* '-' */  0x86,
 /* '.' */  0x82,
 /* '/' */  0x80,
 /* '0' */  0x14,
 /* '1' */  0x05,
 /* '2' */  0x05,
 /* '3' */  0x45,
 /* '4' */  0x45,
 /* '5' */  0x45,
 /* '6' */  0x85,
 /* '7' */  0x85,
 /* '8' */  0x85,
 /* '9' */  0x05,
 /* ':' */  0x00,
 /* ';' */  0x00,
 /* '<' */  0x30,
 /* '=' */  0x30,
 /* '>' */  0x80,
 /* '?' */  0x50,
 /* '@' */  0x80,
#if defined (_SAFECRT_IMPL)
 /* 'A' */  0x80,       // Disable %A format
#else  /* defined (_SAFECRT_IMPL) */
 /* 'A' */  0x88,
#endif  /* defined (_SAFECRT_IMPL) */
 /* 'B' */  0x00,
 /* 'C' */  0x08,
 /* 'D' */  0x00,
 /* 'E' */  0x28,
 /* 'F' */  0x27,
 /* 'G' */  0x38,
 /* 'H' */  0x50,
 /* 'I' */  0x57,
 /* 'J' */  0x80,
 /* 'K' */  0x00,
 /* 'L' */  0x07,
 /* 'M' */  0x00,
 /* 'N' */  0x37,
 /* 'O' */  0x30,
 /* 'P' */  0x30,
 /* 'Q' */  0x50,
 /* 'R' */  0x50,
 /* 'S' */  0x88,
 /* 'T' */  0x00,
 /* 'U' */  0x00,
 /* 'V' */  0x00,
 /* 'W' */  0x20,
 /* 'X' */  0x28,
 /* 'Y' */  0x80,
 /* 'Z' */  0x88,
 /* '[' */  0x80,
 /* '\' */  0x80,
 /* ']' */  0x00,
 /* '^' */  0x00,
 /* '_' */  0x00,
 /* '`' */  0x60,
#if defined (_SAFECRT_IMPL)
 /* 'a' */  0x60,       // Disable %a format
#else  /* defined (_SAFECRT_IMPL) */
 /* 'a' */  0x68,
#endif  /* defined (_SAFECRT_IMPL) */
 /* 'b' */  0x60,
 /* 'c' */  0x68,
 /* 'd' */  0x68,
 /* 'e' */  0x68,
 /* 'f' */  0x08,
 /* 'g' */  0x08,
 /* 'h' */  0x07,
 /* 'i' */  0x78,
 /* 'j' */  0x70,
 /* 'k' */  0x70,
 /* 'l' */  0x77,
 /* 'm' */  0x70,
 /* 'n' */  0x70,
 /* 'o' */  0x08,
 /* 'p' */  0x08,
 /* 'q' */  0x00,
 /* 'r' */  0x00,
 /* 's' */  0x08,
 /* 't' */  0x00,
 /* 'u' */  0x08,
 /* 'v' */  0x00,
 /* 'w' */  0x07,
 /* 'x' */  0x08
};

static inline CHARTYPE GetCharType( char ch )
{
    return ((ch < (' ') || ch > ('x')) ? CH_OTHER : (enum CHARTYPE)(__lookuptable_s[ch - (' ')] & 0xF));
}
static inline STATE GetState( CHARTYPE type, STATE oldState )
{
    return (enum STATE)(__lookuptable_s[type * NUMSTATES + oldState] >> 4);
}


static bool isleadbyte(unsigned char ch)
{
    return (FALSE != IsDBCSLeadByte(ch) );
}
static bool _isleadbyte_l(unsigned char ch, _locale_t loc)
{
    // XPLAT_ODBC_TODO VSTS 718708 Localization
    return ( FALSE != IsDBCSLeadByte(ch) );
}



#define _WCTOMB_S mplat_wctomb_s
errno_t mplat_wctomb_s(
   int *pRetValue,
   char *mbchar,
   size_t sizeInBytes,
   WCHAR wchar
)
{
    DWORD rc;
    size_t cch = SystemLocale::FromUtf16( CP_ACP, &wchar, 1, mbchar, sizeInBytes, NULL, &rc );
    *pRetValue = (int)cch;
    return (ERROR_SUCCESS == rc ? 0 : -1);
}

// Floating point print routines
void _CFLTCVT( double * dbl, char * buf, int bufSize, char fmt, int precision, int caps, _locale_t loc = NULL )
{
    const size_t local_bufsize = 8;
    char local_fmt[local_bufsize] = {'\0'};

    if ( 0 != caps )
    {
        fmt -= ('a') - ('A');    /* convert format char to upper */
    }
    int chars_printed = snprintf( local_fmt, local_bufsize, "%%.%d%c", precision-1, fmt );
    assert( 0 < chars_printed && (size_t)chars_printed < local_bufsize );

    // We want to use the platform version of snprintf so temporarily undef.
    // Formatting of floating pt values is complex so we didn't implement it here.
    // Even porting the CRT code would've been difficult.  Instead, we can use the
    // platform's snprintf for just floating pt values.  We have to undef to prevent
    // recursing right back to here.
#   undef snprintf
    chars_printed = snprintf( buf, bufSize, local_fmt, *dbl );
    assert( 0 < chars_printed && chars_printed < bufSize );
#   define snprintf mplat_snprintf
}

#if !LONGDOUBLE_IS_DOUBLE
// SQL Server does not support the long double data type so this should never be called.
// It will be compiled out on Linux.
void _CLDCVT( _LONGDOUBLE * dbl, char * buf, int bufSize, char fmt, int precision, int caps )
{
    assert(false);
}
#endif

static enum STATE ProcessSizeA( char sizeCh, char fmt_ch, char next_fmt_ch, int * advance, int * flags )
{
    *advance = 0;
    switch (sizeCh)
    {
    case 'l':
        /*
        * In order to handle the ll case, we depart from the
        * simple deterministic state machine.
        */
        if ( 'l' == fmt_ch )
        {
            *advance = 1;
            *flags |= FL_LONGLONG;
        }
        else
        {
            *flags |= FL_LONG;
        }
        break;

    case 'I':
        /*
        * In order to handle the I, I32, and I64 size modifiers, we
        * depart from the simple deterministic state machine. The
        * code below scans for characters following the 'I',
        * and defaults to 64 bit on WIN64 and 32 bit on WIN32
        */
#if PTR_IS_INT64
        *flags |= FL_I64;    /* 'I' => __int64 on WIN64 systems */
#endif  /* PTR_IS_INT64 */
        if ( '6' == fmt_ch && '4' == next_fmt_ch )
        {
            *advance = 2;
            *flags |= FL_I64;    /* I64 => __int64 */
        }
        else if ( '3' == fmt_ch && '2' == next_fmt_ch )
        {
            *advance = 2;
            *flags &= ~FL_I64;   /* I32 => __int32 */
        }
        else if (
            (fmt_ch == 'd') ||
            (fmt_ch == 'i') ||
            (fmt_ch == 'o') ||
            (fmt_ch == 'u') ||
            (fmt_ch == 'x') ||
            (fmt_ch == 'X') )
        {
            /*
            * Nothing further needed.  %Id (et al) is
            * handled just like %d, except that it defaults to 64 bits
            * on WIN64.  Fall through to the next iteration.
            */
        }
        else
        {
            return ST_NORMAL;
        }
        break;

    case 'h':
        *flags |= FL_SHORT;
        break;

    case 'w':
        *flags |= FL_WIDECHAR;
    }

    return ST_SIZE;
}

STATE ProcessSize( char sizeCh, const char * format, int * advance, int * flags )
{
    char formatCh = *format;
    char next_formatCh = ('\0' == formatCh ? '\0' : *(format+1));
    return ProcessSizeA( sizeCh, formatCh, next_formatCh, advance, flags );
}

// Tools\vc\src\crt\amd64\output.c
int FormattedPrintA( IFormattedPrintOutput<char> * output, const char *format, va_list argptr )
{
    int hexadd=0;          /* offset to add to number to get 'a'..'f' */
    char ch;               /* character just read */
    int flags=0;           /* flag word -- see #defines above for flag values */
    enum STATE state;      /* current state */
    enum CHARTYPE chclass; /* class of current character */
    int radix;             /* current conversion radix */
    int charsout;          /* characters currently written so far, -1 = IO error */
    int fldwidth = 0;      /* selected field width -- 0 means default */
    int precision = 0;     /* selected precision  -- -1 means default */
    char prefix[2];        /* numeric prefix -- up to two characters */
    int prefixlen=0;       /* length of prefix -- 0 means no prefix */
    int capexp=0;          /* non-zero = 'E' exponent signifient, zero = 'e' or unused */
    int no_output=0;       /* non-zero = prodcue no output for this specifier */
    union {
        char *sz;   /* pointer text to be printed, not zero terminated */
        WCHAR *wz;
        } text;

    int textlen;    /* length of the text in bytes/wchars to be printed.
                       textlen is in multibyte or wide chars if _UNICODE */
    union {
        char sz[BUFFERSIZE];
        } buffer = {'\0'};
    WCHAR wchar;                /* temp wchar_t */
    int bufferiswide=0;         /* non-zero = buffer contains wide chars already */

#ifndef _SAFECRT_IMPL
    _LocaleUpdate _loc_update(plocinfo);
#endif  /* _SAFECRT_IMPL */

    char *heapbuf = NULL; /* non-zero = test.sz using heap buffer to be freed */

    int advance; /* count of how much helper fxns need format ptr incremented */

    _VALIDATE_RETURN( ((output != NULL) && (format != NULL)), EINVAL, -1);

    charsout = 0;       /* no characters written yet */
    textlen = 0;        /* no text yet */
    state = ST_NORMAL;  /* starting state */
    heapbuf = NULL;     /* not using heap-allocated buffer */

    /* main loop -- loop while format character exist and no I/O errors */
    while ((ch = *format++) != '\0' && charsout >= 0) {
        // Find char class and next state
        chclass = GetCharType( ch );
        state = GetState( chclass, state );

        /* execute code for each state */
        switch (state) {

        case ST_INVALID:
            // "Incorrect format specifier"
            assert( false );
            errno = EINVAL;
            return -1;

        case ST_NORMAL:

        NORMAL_STATE:

            /* normal state -- just write character */
            bufferiswide = 0;
#ifdef _SAFECRT_IMPL
            if (isleadbyte((unsigned char)ch)) {
#else  /* _SAFECRT_IMPL */
            if (_isleadbyte_l((unsigned char)ch, _loc_update.GetLocaleT())) {
#endif  /* _SAFECRT_IMPL */
                // XPLAT_ODBC_TODO VSTS 718708 Localization
                // Deal with more than 2 storage units per character
                output->WRITE_CHAR(ch, &charsout);
                ch = *format++;
                /* don't fall off format string */
                _VALIDATE_RETURN( (ch != '\0'), EINVAL, -1);
            }
            output->WRITE_CHAR(ch, &charsout);
            break;

        case ST_PERCENT:
            /* set default value of conversion parameters */
            prefixlen = fldwidth = no_output = capexp = 0;
            flags = 0;
            precision = -1;
            bufferiswide = 0;   /* default */
            break;

        case ST_FLAG:
            /* set flag based on which flag character */
            switch (ch) {
            case ('-'):
                flags |= FL_LEFT;   /* '-' => left justify */
                break;
            case ('+'):
                flags |= FL_SIGN;   /* '+' => force sign indicator */
                break;
            case (' '):
                flags |= FL_SIGNSP; /* ' ' => force sign or space */
                break;
            case ('#'):
                flags |= FL_ALTERNATE;  /* '#' => alternate form */
                break;
            case ('0'):
                flags |= FL_LEADZERO;   /* '0' => pad with leading zeros */
                break;
            }
            break;

        case ST_WIDTH:
            /* update width value */
            if (ch == ('*')) {
                /* get width from arg list */
                fldwidth = va_arg(argptr, int);
                if (fldwidth < 0) {
                    /* ANSI says neg fld width means '-' flag and pos width */
                    flags |= FL_LEFT;
                    fldwidth = -fldwidth;
                }
            }
            else {
                /* add digit to current field width */
                fldwidth = fldwidth * 10 + (ch - ('0'));
            }
            break;

        case ST_DOT:
            /* zero the precision, since dot with no number means 0
               not default, according to ANSI */
            precision = 0;
            break;

        case ST_PRECIS:
            /* update precison value */
            if (ch == ('*')) {
                /* get precision from arg list */
                precision = va_arg(argptr, int);
                if (precision < 0)
                    precision = -1; /* neg precision means default */
            }
            else {
                /* add digit to current precision */
                precision = precision * 10 + (ch - ('0'));
            }
            break;

        case ST_SIZE:
            /* just read a size specifier, set the flags based on it */
            state = ProcessSize( ch, format, &advance, &flags );
            format += advance;
            if ( ST_NORMAL == state )
            {
                goto NORMAL_STATE;
            }
            break;

        case ST_TYPE:
            /* we have finally read the actual type character, so we       */
            /* now format and "print" the output.  We use a big switch     */
            /* statement that sets 'text' to point to the text that should */
            /* be printed, and 'textlen' to the length of this text.       */
            /* Common code later on takes care of justifying it and        */
            /* other miscellaneous chores.  Note that cases share code,    */
            /* in particular, all integer formatting is done in one place. */
            /* Look at those funky goto statements!                        */

            switch (ch) {

            case ('C'):   /* ISO wide character */
                if (!(flags & (FL_SHORT|FL_LONG|FL_WIDECHAR)))
                    flags |= FL_WIDECHAR;   /* ISO std. */
                /* fall into 'c' case */

            case ('c'): {
                /* print a single character specified by int argument */
                if (flags & (FL_LONG|FL_WIDECHAR)) {
                    errno_t e = 0;
                    wchar = (WCHAR) va_arg(argptr, int);
                    /* convert to multibyte character */
                    e = _WCTOMB_S(&textlen, buffer.sz, _countof(buffer.sz), wchar);

                    /* check that conversion was successful */
                    if (e != 0)
                        no_output = 1;
                } else {
                    /* format multibyte character */
                    /* this is an extension of ANSI */
                    unsigned short temp;
                    temp = (unsigned short) va_arg(argptr, int);
                    {
                        buffer.sz[0] = (char) temp;
                        textlen = 1;
                    }
                }
                text.sz = buffer.sz;
            }
            break;

            case ('Z'): {
                // 'Z' format specifier disabled
                _VALIDATE_RETURN(0, EINVAL, -1);
            }
            break;

            case ('S'):   /* ISO wide character string */
                if (!(flags & (FL_SHORT|FL_LONG|FL_WIDECHAR)))
                    flags |= FL_WIDECHAR;

            case ('s'): {
                /* print a string --                            */
                /* ANSI rules on how much of string to print:   */
                /*   all if precision is default,               */
                /*   min(precision, length) if precision given. */
                /* prints '(null)' if a null string is passed   */

                int i;
                char *p;       /* temps */
                WCHAR *pwch;

                /* At this point it is tempting to use strlen(), but */
                /* if a precision is specified, we're not allowed to */
                /* scan past there, because there might be no null   */
                /* at all.  Thus, we must do our own scan.           */

                i = (precision == -1) ? INT_MAX : precision;
                text.sz = (char *)va_arg(argptr, void *);

                /* scan for null upto i characters */
                if (flags & (FL_LONG|FL_WIDECHAR)) {
                    if (text.wz == NULL) /* NULL passed, use special string */
                        text.wz = (WCHAR *)__wnullstring;
                    bufferiswide = 1;
                    pwch = text.wz;
                    while ( i-- && *pwch )
                        ++pwch;
                    textlen = (int)(pwch - text.wz);
                    /* textlen now contains length in wide chars */
                } else {
                    if (text.sz == NULL) /* NULL passed, use special string */
                        text.sz = (char*) __nullstring;
                    p = text.sz;
                    while (i-- && *p)
                        ++p;
                    textlen = (int)(p - text.sz);    /* length of the string */
                }
            }
            break;


            case ('n'): {
                // We will not support %n
                _VALIDATE_RETURN(0, EINVAL, -1);
            }
            break;

            case ('E'):
            case ('G'):
            case ('A'):
                capexp = 1;                 /* capitalize exponent */
                ch += ('a') - ('A');    /* convert format char to lower */
                /* DROP THROUGH */
            case ('e'):
            case ('f'):
            case ('g'):
            case ('a'): {
                /* floating point conversion -- we call cfltcvt routines */
                /* to do the work for us.                                */
                flags |= FL_SIGNED;             /* floating point is signed conversion */
                text.sz = buffer.sz;            /* put result in buffer */
                int buffersize = BUFFERSIZE;    /* size of text.sz (used only for the call to _cfltcvt) */

                /* compute the precision value */
                if (precision < 0)
                    precision = 6;          /* default precision: 6 */
                else if (precision == 0 && ch == ('g'))
                    precision = 1;          /* ANSI specified */
                else if (precision > MAXPRECISION)
                    precision = MAXPRECISION;

                if (precision > BUFFERSIZE - _CVTBUFSIZE) {
                    /* conversion will potentially overflow local buffer */
                    /* so we need to use a heap-allocated buffer.        */
                    heapbuf = (char *)malloc(_CVTBUFSIZE + precision);
                    if (heapbuf != NULL)
                    {
                        text.sz = heapbuf;
                        buffersize = _CVTBUFSIZE + precision;
                    }
                    else
                        /* malloc failed, cap precision further */
                        precision = BUFFERSIZE - _CVTBUFSIZE;
                }

#ifdef _SAFECRT_IMPL
                /* for safecrt, we pass along the FL_ALTERNATE flag to _safecrt_cfltcvt */
                if (flags & FL_ALTERNATE)
                {
                    capexp |= FL_ALTERNATE;
                }
#endif  /* _SAFECRT_IMPL */

#if !LONGDOUBLE_IS_DOUBLE
                /* do the conversion */
                if (flags & FL_LONGDOUBLE) {
                    _LONGDOUBLE tmp;
                    tmp=va_arg(argptr, _LONGDOUBLE);
                    /* Note: assumes ch is in ASCII range */
                    _CLDCVT(&tmp, text.sz, buffersize, (char)ch, precision, capexp);
                } else
#endif  /* !LONGDOUBLE_IS_DOUBLE */
                {
                    double tmp;
                    tmp=va_arg(argptr, double);
                    /* Note: assumes ch is in ASCII range */
                    /* In safecrt, we provide a special version of _cfltcvt which internally calls printf (see safecrt_output_s.c) */
#ifndef _SAFECRT_IMPL
                    _cfltcvt_l(&tmp, text.sz, buffersize, (char)ch, precision, capexp, _loc_update.GetLocaleT());
#else  /* _SAFECRT_IMPL */
                    _CFLTCVT(&tmp, text.sz, buffersize, (char)ch, precision, capexp);
#endif  /* _SAFECRT_IMPL */
                }

#ifndef _SAFECRT_IMPL
                /* For safecrt, this is done already in _safecrt_cfltcvt */

                /* '#' and precision == 0 means force a decimal point */
                if ((flags & FL_ALTERNATE) && precision == 0)
                {
                    _forcdecpt_l(text.sz, _loc_update.GetLocaleT());
                }

                /* 'g' format means crop zero unless '#' given */
                if (ch == ('g') && !(flags & FL_ALTERNATE))
                {
                    _cropzeros_l(text.sz, _loc_update.GetLocaleT());
                }
#endif  /* _SAFECRT_IMPL */

                /* check if result was negative, save '-' for later */
                /* and point to positive part (this is for '0' padding) */
                if (*text.sz == '-') {
                    flags |= FL_NEGATIVE;
                    ++text.sz;
                }

                textlen = (int)strnlen_s(text.sz);     /* compute length of text */
            }
            break;

            case ('d'):
            case ('i'):
                /* signed decimal output */
                flags |= FL_SIGNED;
                radix = 10;
                goto COMMON_INT;

            case ('u'):
                radix = 10;
                goto COMMON_INT;

            case ('p'):
                /* write a pointer -- this is like an integer or long */
                /* except we force precision to pad with zeros and */
                /* output in big hex. */

                precision = 2 * sizeof(void *);     /* number of hex digits needed */
#if PTR_IS_INT64
                flags |= FL_I64;                    /* assume we're converting an int64 */
#endif  /* !PTR_IS_INT */
                /* DROP THROUGH to hex formatting */

            case ('X'):
                /* unsigned upper hex output */
                hexadd = ('A') - ('9') - 1;     /* set hexadd for uppercase hex */
                goto COMMON_HEX;

            case ('x'):
                /* unsigned lower hex output */
                hexadd = ('a') - ('9') - 1;     /* set hexadd for lowercase hex */
                /* DROP THROUGH TO COMMON_HEX */

            COMMON_HEX:
                radix = 16;
                if (flags & FL_ALTERNATE) {
                    /* alternate form means '0x' prefix */
                    prefix[0] = ('0');
                    prefix[1] = (char)(('x') - ('a') + ('9') + 1 + hexadd);  /* 'x' or 'X' */
                    prefixlen = 2;
                }
                goto COMMON_INT;

            case ('o'):
                /* unsigned octal output */
                radix = 8;
                if (flags & FL_ALTERNATE) {
                    /* alternate form means force a leading 0 */
                    flags |= FL_FORCEOCTAL;
                }
                /* DROP THROUGH to COMMON_INT */

            COMMON_INT: {
                /* This is the general integer formatting routine. */
                /* Basically, we get an argument, make it positive */
                /* if necessary, and convert it according to the */
                /* correct radix, setting text and textlen */
                /* appropriately. */

                ULONGLONG number;    /* number to convert */
                int digit;              /* ascii value of digit */
                LONGLONG l;              /* temp long value */

                /* 1. read argument into l, sign extend as needed */
                if (flags & FL_I64)
                    l = va_arg(argptr, LONGLONG);
                else if (flags & FL_LONGLONG)
                    l = va_arg(argptr, LONGLONG);
                else

                if (flags & FL_SHORT) {
                    if (flags & FL_SIGNED)
                        l = (short) va_arg(argptr, int); /* sign extend */
                    else
                        l = (unsigned short) va_arg(argptr, int);    /* zero-extend*/

                } else
                {
                    if (flags & FL_SIGNED)
                        l = (int)va_arg(argptr, int); /* sign extend */
                    else
                        l = (unsigned int) va_arg(argptr, int);    /* zero-extend*/
                }

                /* 2. check for negative; copy into number */
                if ( (flags & FL_SIGNED) && l < 0) {
                    number = -l;
                    flags |= FL_NEGATIVE;   /* remember negative sign */
                } else {
                    number = l;
                }

                if ( (flags & FL_I64) == 0 && (flags & FL_LONGLONG) == 0 ) {
                    /*
                     * Unless printing a full 64-bit value, insure values
                     * here are not in cananical longword format to prevent
                     * the sign extended upper 32-bits from being printed.
                     */
                    number &= 0xffffffff;
                }

                /* 3. check precision value for default; non-default */
                /*    turns off 0 flag, according to ANSI. */
                if (precision < 0)
                    precision = 1;  /* default precision */
                else {
                    flags &= ~FL_LEADZERO;
                    if (precision > MAXPRECISION)
                        precision = MAXPRECISION;
                }

                /* 4. Check if data is 0; if so, turn off hex prefix */
                if (number == 0)
                    prefixlen = 0;

                /* 5. Convert data to ASCII -- note if precision is zero */
                /*    and number is zero, we get no digits at all.       */

                text.sz = &buffer.sz[BUFFERSIZE-1];    /* last digit at end of buffer */

                while (precision-- > 0 || number != 0) {
                    digit = (int)(number % radix) + '0';
                    number /= radix;                /* reduce number */
                    if (digit > '9') {
                        /* a hex digit, make it a letter */
                        digit += hexadd;
                    }
                    *text.sz-- = (char)digit;       /* store the digit */
                }

                textlen = (int)((char *)&buffer.sz[BUFFERSIZE-1] - text.sz); /* compute length of number */
                ++text.sz;          /* text points to first digit now */


                /* 6. Force a leading zero if FORCEOCTAL flag set */
                if ((flags & FL_FORCEOCTAL) && (textlen == 0 || text.sz[0] != '0')) {
                    *--text.sz = '0';
                    ++textlen;      /* add a zero */
                }
            }
            break;
            }

            /* At this point, we have done the specific conversion, and */
            /* 'text' points to text to print; 'textlen' is length.  Now we */
            /* justify it, put on prefixes, leading zeros, and then */
            /* print it. */

            if (!no_output) {
                int padding;    /* amount of padding, negative means zero */

                if (flags & FL_SIGNED) {
                    if (flags & FL_NEGATIVE) {
                        /* prefix is a '-' */
                        prefix[0] = ('-');
                        prefixlen = 1;
                    }
                    else if (flags & FL_SIGN) {
                        /* prefix is '+' */
                        prefix[0] = ('+');
                        prefixlen = 1;
                    }
                    else if (flags & FL_SIGNSP) {
                        /* prefix is ' ' */
                        prefix[0] = (' ');
                        prefixlen = 1;
                    }
                }

                /* calculate amount of padding -- might be negative, */
                /* but this will just mean zero */
                padding = fldwidth - textlen - prefixlen;

                /* put out the padding, prefix, and text, in the correct order */

                if (!(flags & (FL_LEFT | FL_LEADZERO))) {
                    /* pad on left with blanks */
                    output->WRITE_MULTI_CHAR((' '), padding, &charsout);
                }

                /* write prefix */
                output->WRITE_STRING(prefix, prefixlen, &charsout);

                if ((flags & FL_LEADZERO) && !(flags & FL_LEFT)) {
                    /* write leading zeros */
                    output->WRITE_MULTI_CHAR(('0'), padding, &charsout);
                }

                /* write text */
                if (bufferiswide && (textlen > 0)) {
                    WCHAR *p;
                    int retval, count;
                    errno_t e = 0;
                    char L_buffer[MB_LEN_MAX+1] = {'\0'};

                    p = text.wz;
                    count = textlen;
                    while (count--) {
                        e = _WCTOMB_S(&retval, L_buffer, _countof(L_buffer), *p++);
                        if (e != 0 || retval == 0) {
                            charsout = -1;
                            break;
                        }
                        output->WRITE_STRING(L_buffer, retval, &charsout);
                    }
                } else {
                    output->WRITE_STRING(text.sz, textlen, &charsout);
                }

                if (charsout >= 0 && (flags & FL_LEFT)) {
                    /* pad on right with blanks */
                    output->WRITE_MULTI_CHAR((' '), padding, &charsout);
                }

                /* we're done! */
            }
            if (heapbuf) {
                free(heapbuf);
                heapbuf = NULL;
            }
            break;
        }
    }

    /* The format string shouldn't be incomplete - i.e. when we are finished
        with the format string, the last thing we should have encountered
        should have been a regular char to be output or a type specifier. Else
        the format string was incomplete */
    _VALIDATE_RETURN(((state == ST_NORMAL) || (state == ST_TYPE)), EINVAL, -1);

    return charsout;        /* return value = number of characters written */
}

// Used for holding the size and value of a variable argument.
// Uses INT and LONGLONG to hold all possible values.  Each is just a buffer to hold the right number of bits.
struct vararg_t
{
    enum ArgType_e
    {
        Unknown,
        Int32,
        Int64,
        ShouldBeInt32,
        ShouldBeInt64
    };

    vararg_t() : int64Val(0), int32Val(0), argType(vararg_t::Unknown) {}
    vararg_t( INT val ) : int64Val(0), int32Val(val), argType(vararg_t::Int32) {}
    vararg_t( LONGLONG ptr ) : int64Val(ptr), int32Val(0), argType(vararg_t::Int64) {}

    ArgType_e Type() const { return argType; }
    INT Int32Value() const { return int32Val; }
    LONGLONG Int64Value() const { return int64Val; }
    void * PtrValue() const
    {
#if PTR_IS_INT64
        return reinterpret_cast<void *>(int64Val);
#else
        return reinterpret_cast<void *>(int32Val);
#endif
    }

    void SetForInt32()
    {
        assert( vararg_t::Unknown == argType );
        argType = vararg_t::ShouldBeInt32;
    }
    void SetForInt64()
    {
        assert( vararg_t::Unknown == argType );
        argType = vararg_t::ShouldBeInt64;
    }
    void SetForPtr()
    {
#if PTR_IS_INT64
        SetForInt64();
#else
        SetForInt32();
#endif
    }

    void Int32Value( INT val )
    {
        assert( vararg_t::Unknown == argType || vararg_t::ShouldBeInt32 == argType );
        assert( 0 == int64Val );
        argType = vararg_t::Int32;
        int32Val = val;
    }
    void Int64Value( LONGLONG val )
    {
        assert( vararg_t::Unknown == argType || vararg_t::ShouldBeInt64 == argType );
        assert( 0 == int32Val );
        argType = vararg_t::Int64;
        int64Val = val;
    }

private:
    LONGLONG int64Val;
    INT int32Val;
    ArgType_e argType;
};

// Caches the var arg values in the supplied vector.  Types are determined by inspecting the format string.
// On error, sets errno and returns false
static bool GetFormatMessageArgsA( const char * format, std::vector< vararg_t > * argcache, va_list * Arguments )
{
    if ( NULL == format )
    {
        errno = EINVAL;
        return false;
    }

    const char *p = format;
    char fmt_ch;

    while( '\0' != (fmt_ch = *p++) )
    {
        if ( '%' != fmt_ch )
        {
            // continue to next format spec
        }
        else if ( '0' == *p || '\0' == *p )
        {
            // %0 or null term means end formatting
            break;
        }
        else if ( *p < '1' || '9' < *p )
        {
            // Escaped char, skip and keep going
            ++p;
        }
        else
        {
            // Integer must be [1..99]
            size_t argPos = *p++ - '0';
            if ( '0' <= *p && *p <= '9' )
            {
                argPos *= 10;
                argPos += *p++ - '0';
            }
            assert( 0 < argPos && argPos < 100 );

            if ( argcache->size() < argPos )
            {
                // Haven't processed this arg, yet
                argcache->resize( argPos );
            }

            if ( vararg_t::Unknown == argcache->at(argPos-1).Type() )
            {
                if ( '!' != *p )
                {
                    // Assume %s as per spec
                    argcache->at(argPos-1).SetForPtr();
                }
                else
                {
                    // Step over the initial '!' and process format specification
                    ++p;

                    char ch;
                    int flags = 0;
                    int advance = 0;
                    enum CHARTYPE chclass;
                    enum STATE state = ST_PERCENT;
                    bool found_terminator = false;
                    while ( !found_terminator && ('\0' != (ch = *p++)) )
                    {
                        chclass = GetCharType( ch );
                        state = GetState( chclass, state );

                        switch ( state )
                        {
                        case ST_DOT:
                        case ST_FLAG:
                            break;

                        case ST_WIDTH:
                        case ST_PRECIS:
                            if ( '*' == ch )
                            {
                                argcache->at(argPos-1).SetForInt32();
                                ++argPos;
                                if ( argcache->size() < argPos )
                                {
                                    argcache->resize( argPos );
                                }
                            }
                            break;

                        case ST_SIZE:
                            state = ProcessSize( ch, p, &advance, &flags );
                            p += advance;
                            if ( ST_SIZE != state )
                            {
                                // Size and type flags were inconsistent
                                errno = EINVAL;
                                return false;
                            }
                            break;

                        case ST_TYPE:
                            // Group into 32-bit and 64-bit sized args
                            assert( vararg_t::Unknown == argcache->at(argPos-1).Type() );
                            switch ( ch )
                            {
                            case 'C': // chars
                            case 'c':
                                argcache->at(argPos-1).SetForInt32();
                                break;

                            case 'd': // ints
                            case 'i':
                            case 'u':
                            case 'X':
                            case 'x':
                            case 'o':
                                // INT args
                                if ( (flags & FL_I64) || (flags & FL_LONGLONG) )
                                    argcache->at(argPos-1).SetForInt64();
                                else
                                    argcache->at(argPos-1).SetForInt32();
                                break;

                            case 'S': // strings
                            case 's':
                            case 'p': // pointer
                                argcache->at(argPos-1).SetForPtr();
                                break;

                            case 'E': // doubles (not supported as per spec)
                            case 'e':
                            case 'G':
                            case 'g':
                            case 'A':
                            case 'a':
                            case 'f':
                            default:
                                errno = EINVAL;
                                return false;
                            }
                            break;

                        case ST_NORMAL:
                            if ( '!' == ch )
                            {
                                found_terminator = true;
                                break;
                            }
                            // Fall thru to error, missing terminating '!'

                        default:
                            errno = EINVAL;
                            return false;
                        }
                    }

                    if ( !found_terminator )
                    {
                        // End of string before trailing '!' was found
                        errno = EINVAL;
                        return false;
                    }
                }
            }
        }
    }

    if ( 0 < argcache->size() && NULL == Arguments )
    {
        errno = EINVAL;
        return false;
    }

    // Cache var arg values now that we know the number and sizes
    for ( std::vector< vararg_t >::iterator arg = argcache->begin(); arg != argcache->end(); ++arg )
    {
        if ( vararg_t::Unknown == arg->Type() )
        {
            // Arg not referenced in format string so assume ptr sized.
            // This is a decent assumption since every arg gets ptr-size bytes to ensure alignment
            // of later arg values.  Verified this behavior with both Windows and Linux.
            arg->SetForPtr();
        }

        vararg_t::ArgType_e argtype = arg->Type();
        assert( vararg_t::ShouldBeInt32 == argtype || vararg_t::ShouldBeInt64 == argtype );

        if ( vararg_t::ShouldBeInt32 == argtype )
        {
            arg->Int32Value( (INT)va_arg(*Arguments, INT) );
        }
        else
        {
            arg->Int64Value( (LONGLONG)va_arg(*Arguments, LONGLONG) );
        }
    }

    return true;
}

// On success, returns the number of chars written into the buffer excluding null terminator.
// On error, sets errno and returns zero.
static DWORD FormatMessageToBufferA( const char * format, char * buffer, DWORD bufferWCharSize, const std::vector< vararg_t > & args )
{
    char * msg = buffer;
    DWORD bufsize = std::min(bufferWCharSize, (DWORD)64000);
    DWORD msg_pos = 0;
    const DWORD fmtsize = 32;
    char fmt[fmtsize] = {'\0'};
    DWORD fmt_pos;
    char fmt_ch;

    const char * p = format;
    while( msg_pos < bufsize && '\0' != (fmt_ch = *p++) )
    {
        if ( '%' != fmt_ch )
        {
            msg[msg_pos++] = fmt_ch;
        }
        else if ( '0' == *p || '\0' == *p )
        {
            // %0 or null term means end formatting
            break;
        }
        else if ( *p < '1' || '9' < *p )
        {
            // Escaped char, print and keep going
            // Eg.  "%n" == '\n'
            switch ( *p )
            {
            case 'a': msg[msg_pos++] = '\a'; break;
            case 'b': msg[msg_pos++] = '\b'; break;
            case 'f': msg[msg_pos++] = '\f'; break;
            case 'n': msg[msg_pos++] = '\n'; break;
            case 'r': msg[msg_pos++] = '\r'; break;
            case 't': msg[msg_pos++] = '\t'; break;
            case 'v': msg[msg_pos++] = '\v'; break;
            default:   msg[msg_pos++] = *p;    break;
            }
            ++p;
        }
        else
        {
            // Integer must be [1..99]
            size_t argPos = *p++ - '0';
            if ( '0' <= *p && *p <= '9' )
            {
                argPos *= 10;
                argPos += *p++ - '0';
            }
            assert( 0 < argPos && argPos < 100 );

            fmt_pos = 0;
            fmt[fmt_pos++] = '%';

            if ( '!' != *p )
            {
                // Assume %s as per spec
                fmt[fmt_pos++] = 's';
                fmt[fmt_pos] = '\0';
                int chars_printed = mplat_snprintf_s( &msg[msg_pos], bufsize-msg_pos, bufsize-msg_pos, fmt, args[argPos-1].PtrValue() );
                if ( chars_printed < 0 )
                {
                    errno = EINVAL;
                    return 0;
                }
                msg_pos += chars_printed;
            }
            else
            {
                // Skip over '!' and build format string
                ++p;
                char ch;
                int flags = 0;
                int advance = 0;
                enum CHARTYPE chclass;
                enum STATE state = ST_PERCENT;
                bool found_terminator = false;
                while ( fmt_pos < fmtsize && !found_terminator && ('\0' != (ch = *p++)) )
                {
                    chclass = GetCharType( ch );
                    state = GetState( chclass, state );

                    switch ( state )
                    {
                    case ST_SIZE:
                        state = ProcessSize( ch, p, &advance, &flags );
                        fmt[fmt_pos++] = ch;
                        while ( fmt_pos < fmtsize && 0 < advance-- )
                        {
                            fmt[fmt_pos++] = *p++;
                        }
                        break;

                    case ST_NORMAL:
                        assert( '!' == ch );
                        found_terminator = true;
                        break;

                    case ST_INVALID:
                    case ST_PERCENT:
                        errno = EINVAL;
                        return 0;

                    default:
                        fmt[fmt_pos++] = ch;
                        break;
                    }
                }

                if ( fmtsize <= fmt_pos )
                {
                    // Should not have a format string longer than 31 chars
                    // It can happen but shouldn't (eg. a bunch of size mods like %llllllllllllllld)
                    errno = EINVAL;
                    return 0;
                }

                fmt[fmt_pos] = '\0';

                // Format string might need up to 3 args (eg. %*.*d )
                // If more than one arg, then the first ones must be 32-bit ints
                // Hence, first 64-bit arg tells us the last arg we need to send.
                int chars_printed = 0;
                if ( vararg_t::Int64 == args[argPos-1].Type() )
                {
                    chars_printed = mplat_snprintf_s( &msg[msg_pos], bufsize-msg_pos, bufsize-msg_pos, fmt, args[argPos-1].Int64Value() );
                }
                else if ( args.size() == argPos )
                {
                    // No more args so send the one Int
                    chars_printed = mplat_snprintf_s( &msg[msg_pos], bufsize-msg_pos, bufsize-msg_pos, fmt, args[argPos-1].Int32Value() );
                }
                else if ( vararg_t::Int64 == args[argPos].Type() )
                {
                    chars_printed = mplat_snprintf_s( &msg[msg_pos], bufsize-msg_pos, bufsize-msg_pos, fmt, args[argPos-1].Int32Value(), args[argPos].Int64Value() );
                }
                else if ( args.size() == (argPos+1) )
                {
                    // No more args so send the two Ints
                    chars_printed = mplat_snprintf_s( &msg[msg_pos], bufsize-msg_pos, bufsize-msg_pos, fmt, args[argPos-1].Int32Value(), args[argPos-1].Int32Value() );
                }
                else if ( vararg_t::Int64 == args[argPos+1].Type() )
                {
                    chars_printed = mplat_snprintf_s( &msg[msg_pos], bufsize-msg_pos, bufsize-msg_pos, fmt, args[argPos-1].Int32Value(), args[argPos].Int32Value(), args[argPos+1].Int64Value() );
                }
                else
                {
                    chars_printed = mplat_snprintf_s( &msg[msg_pos], bufsize-msg_pos, bufsize-msg_pos, fmt, args[argPos-1].Int32Value(), args[argPos].Int32Value(), args[argPos+1].Int32Value() );
                }

                if ( chars_printed < 0 )
                {
                    errno = EINVAL;
                    return 0;
                }
                msg_pos += chars_printed;
            }
        }
    }

    if ( bufsize <= msg_pos )
    {
        errno = ERANGE;
        return 0;
    }

    msg[msg_pos] = '\0';
    return msg_pos;
}

// FormatMessage implementation details (see MSDN for more info)
//
// The Windows FormatMessage API is very rich, complex.  This is not an exact duplication of that function.
// Instead, the most important aspects of this function have been implemented here along with constraints to
// match how we use it within SNAC, BCP, and SQLCMD.
//
// Only these combinations of dwFlags are supported:
//  FORMAT_MESSAGE_FROM_STRING
//      Writes formatted message into supplied buffer
//  FORMAT_MESSAGE_ALLOCATE_BUFFER | FORMAT_MESSAGE_FROM_STRING
//      Allocates a buffer, writes formatted message into that buffer, returns buffer in lpBufffer
//  FORMAT_MESSAGE_FROM_SYSTEM | FORMAT_MESSAGE_IGNORE_INSERTS
//      Writes fixed, English message into the supplied buffer (do not have Windows resources to get real message)
//  FORMAT_MESSAGE_FROM_HMODULE
//      SQLCMD uses this to read strings from the RLL that have not been translated to the current lang
//
// dwLanguageId is ignored for FORMAT_MESSAGE_FROM_STRING as per spec
//      For FORMAT_MESSAGE_FROM_SYSTEM, we don't have Windows resources so language is irrelevant

DWORD FormatMessageA(DWORD dwFlags, LPCVOID lpSource, DWORD dwMessageId, DWORD dwLanguageId, LPTSTR lpBuffer, DWORD nSize, va_list *Arguments)
{
    DWORD chars_printed = 0;

    // XPLAT_ODBC_TODO VSTS 718708 Localization by handling FORMAT_MESSAGE_FROM_HMODULE and dwLanguageId param
    if ( dwFlags & FORMAT_MESSAGE_FROM_STRING )
    {
        // Format specification allows for reordering of insertions relative to var arg position
        // This means we need to walk thru the format specification to find the types of the var args in var arg order
        // We extract the var args in order based on the identified types
        // Finally, we re-walk the format specfication and perform the insertions

        // First pass thru the format string to determine all args and their types
        // This first pass also validates the format string and will return an error
        // if it is invalid.  This allows FormatMessageToBuffer to have less error
        // checking.
        std::vector< vararg_t > args;
        // Based on quick scan of RC files, the largest arg count was 7 so reserve 8 slots to reduce allocations
        args.reserve(8);
        if ( GetFormatMessageArgsA( reinterpret_cast<const char *>(lpSource), &args, Arguments ) )
        {
            if ( dwFlags == (FORMAT_MESSAGE_ALLOCATE_BUFFER | FORMAT_MESSAGE_FROM_STRING) )
            {
                *((char**)lpBuffer) = NULL;

                const DWORD max_size = 64000;
                char local_buf[max_size] = {'\0'};
                chars_printed = FormatMessageToBufferA( reinterpret_cast<const char *>(lpSource), local_buf, max_size, args );
                if ( 0 < chars_printed )
                {
                    size_t buf_size = std::min( max_size, std::max(nSize, (chars_printed+1)) );
                    char * return_buf = (char *)LocalAlloc(0, buf_size * sizeof(char));
                    if ( NULL == return_buf )
                    {
                        errno = ENOMEM;
                    }
                    else
                    {
                        mplat_cscpy(return_buf, local_buf);
                        *((char**)lpBuffer) = return_buf;
                    }
                }
            }
            else if ( dwFlags == FORMAT_MESSAGE_FROM_STRING )
            {
                chars_printed = FormatMessageToBufferA( reinterpret_cast<const char *>(lpSource), lpBuffer, std::min(nSize, (DWORD)64000), args );
            }
        }
    }
    else if ( dwFlags & FORMAT_MESSAGE_FROM_SYSTEM )
    {
        // Since we don't have the Windows system error messages available use a fixed message
        // Can not use a message ID for this since this same code is used by driver and tools,
        // each having their own RLL file.  Don't think we should be reserving an ID across all RLLs.
        const char systemMsg[] = "Error code 0x%X";
        if ( dwFlags & FORMAT_MESSAGE_ALLOCATE_BUFFER )
        {
            *((char**)lpBuffer) = NULL;

            // Add 9 for up to 8 hex digits plus null term (ignore removal of format specs)
            const size_t msgsize = (9 + sizeof(systemMsg)/sizeof(systemMsg[0]));
            char * return_buf = (char *)LocalAlloc(0, msgsize * sizeof(char));
            if ( NULL == return_buf )
            {
                errno = ENOMEM;
            }
            else
            {
                chars_printed = mplat_snprintf_s( return_buf, msgsize, msgsize, systemMsg, dwMessageId );
                // Assert that we did our buffer size math right
                assert( chars_printed < msgsize );
                if ( 0 < chars_printed )
                {
                    *((char**)lpBuffer) = return_buf;
                }
                else
                {
                    LocalFree( return_buf );
                    errno = EINVAL;
                }
            }
        }
        else
        {
            chars_printed = mplat_snprintf_s( lpBuffer, nSize, nSize, systemMsg, dwMessageId );
        }
    }

    return chars_printed;
}


//--------Other definitions from xplat stub sources--------------

BOOL IsDBCSLeadByte(__inn BYTE  TestChar)
{
    // XPLAT_ODBC_TODO: This is to allow BatchParser to function
    // BatchParser will single step thru utf8 code points
    // BatchParser needs to become utf8-aware
    // VSTS 718708 Localization
    if ( CP_UTF8 == SystemLocale::Singleton().AnsiCP() )
        return FALSE;
    // XPLAT_ODBC_TODO

    return IsDBCSLeadByteEx(SystemLocale::Singleton().AnsiCP(), TestChar);
}

BOOL IsDBCSLeadByteEx(
    __inn UINT  CodePage,
    __inn BYTE  TestChar)
{
    if ( 1 == SystemLocale::MaxCharCchSize(CodePage) )
        return FALSE;

    // Lead byte ranges for code pages, inclusive:
    // CP932
    //      0x81-0x9f, 0xe0-0xfc
    // CP936, CP949, CP950
    //      0x81-0xfe
    assert( 932 == CodePage || 936 == CodePage || 949 == CodePage || 950 == CodePage );
    if ( 932 == CodePage )
    {
        if ( TestChar < (unsigned char)0x81
            || (unsigned char)0xfc < TestChar
            || ((unsigned char)0x9f < TestChar && TestChar < (unsigned char)0xe0) )
        {
            return FALSE;
        }
    }
    else if ( TestChar < (unsigned char)0x81 || TestChar == (unsigned char)0xff )
        return FALSE;

    return TRUE;
}

int mplat_vsnprintf( char * buffer, size_t count, const char * format, va_list args )
{
    BufferOutput<char> output( buffer, count );
    return FormattedPrintA( &output, format, args );
}

int mplat_snprintf_s( char *buffer, size_t bufsize, size_t count, const char *format, ... )
{
    va_list args;
    va_start( args, format );
    int retcode = mplat_vsnprintf( buffer, std::min(bufsize, count), format, args );
    va_end( args );
    return retcode;
}

char * mplat_cscpy( char * dst, const char * src )
{
    char * cp = dst;

    while( (*cp++ = *src++) )
        ;       /* Copy src over dst */

    return( dst );
}

size_t mplat_wcslen( const WCHAR * str )
{
    const WCHAR * eos = str;
    while( *eos++ )
    {
    }
    return( (size_t)(eos - str- 1) );
}

HLOCAL LocalAlloc(UINT uFlags, SIZE_T uBytes)
{
    assert(uFlags == 0); // For now
    return malloc(uBytes);
}

HLOCAL LocalFree(HLOCAL hMem)
{
    assert(hMem != NULL);

    free(hMem);
    return NULL;
}

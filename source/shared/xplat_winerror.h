//---------------------------------------------------------------------------------------------------------------------------------
// File: xplat_winerror.h
//
// Contents: Contains the minimal definitions to build on non-Windows platforms
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

#ifndef XPLAT_WINERROR_H
#define XPLAT_WINERROR_H

#define NOERROR             0
#define WAIT_TIMEOUT                     258L    // dderror
#define S_OK ((HRESULT)0L)
#define S_FALSE ((HRESULT)1L)
#define E_NOTIMPL ((HRESULT) 0x80004001L)
#define E_FAIL ((HRESULT) 0x80004005L)
#define E_ABORT                          _HRESULT_TYPEDEF_(0x80004004L)
#define ERROR_HANDLE_EOF                 38L
#define E_UNEXPECTED ((HRESULT) 0x8000FFFFL)
#define SUCCEEDED(hr) (((HRESULT)(hr)) >= 0)
#define FAILED(hr) (((HRESULT)(hr)) < 0)
#define ERROR_SUCCESS   0L
#define ERROR_ACCESS_DENIED 5L
#define ERROR_TIMEOUT	1460L
#define E_POINTER _HRESULT_TYPEDEF_(0x80004003L)
#define _HRESULT_TYPEDEF_(_sc) ((HRESULT)_sc)
#define E_OUTOFMEMORY _HRESULT_TYPEDEF_(0x8007000EL)
#define NO_ERROR 0L
#define ERROR_CANCELLED 1223L
#define E_INVALIDARG _HRESULT_TYPEDEF_(0x80070057L)
#define DISP_E_TYPEMISMATCH _HRESULT_TYPEDEF_(0x80020005L)
#define DISP_E_OVERFLOW _HRESULT_TYPEDEF_(0x8002000AL)
#define ERROR_INSUFFICIENT_BUFFER 122L    // dderror
#define FACILITY_WIN32                   7
#define __HRESULT_FROM_WIN32(x) ((HRESULT)(x) <= 0 ? ((HRESULT)(x)) : ((HRESULT) (((x) & 0x0000FFFF) | (FACILITY_WIN32 << 16) | 0x80000000)))
#define HRESULT_FROM_WIN32(x) __HRESULT_FROM_WIN32(x)
#define SEVERITY_ERROR      1
#define FACILITY_ITF                     4
#define MAKE_HRESULT(sev,fac,code) \
    ((HRESULT) (((windowsULong_t)(sev)<<31) | ((windowsULong_t)(fac)<<16) | ((windowsULong_t)(code))) )
#define ERROR_INVALID_DATA               13L
#define ERROR_INVALID_PARAMETER          87L    // dderror
#define ERROR_POSSIBLE_DEADLOCK          1131L
#define ERROR_INVALID_FLAGS              1004L
#define ERROR_NO_UNICODE_TRANSLATION     1113L

#define ERROR_FILE_NOT_FOUND             2L
#define ERROR_PATH_NOT_FOUND             3L
#define ERROR_TOO_MANY_OPEN_FILES        4L
#define E_NOINTERFACE                    _HRESULT_TYPEDEF_(0x80004002L)

#define ERROR_MOD_NOT_FOUND              126L
#define ERROR_NO_MORE_FILES              18L
#define ERROR_FILE_EXISTS                80L
#define ERROR_ALREADY_EXISTS             183L
#define ERROR_SHARING_VIOLATION          32L
#define SCODE_CODE(sc)      ((sc) & 0xFFFF)
#define ERROR_READ_FAULT                 30L
#define ERROR_INTERNAL_ERROR             1359L
//----------------------------------------------------------------------------
// Error codes used by SNI
//
#define ERROR_NOT_ENOUGH_MEMORY          8L    // dderror
#define ERROR_IO_PENDING                 997L
#define WSA_IO_PENDING                   (ERROR_IO_PENDING)
#define WSAHOST_NOT_FOUND                11001L
#define WSATRY_AGAIN                     11002L
#define WSANO_RECOVERY                   11003L
#define WSANO_DATA                       11004L
#define WSATYPE_NOT_FOUND                10109L
#define WSA_NOT_ENOUGH_MEMORY            8L
#define WSAEINTR                         10004L
#define WSAEACCES                        10013L
#define WSAEFAULT                        10014L
#define WSAEINVAL                        10022L
#define WSAEMFILE                        10024L
#define WSAEWOULDBLOCK                   10035L
#define WSAEALREADY                      10037L
#define WSAENOTSOCK                      10038L
#define WSAEMSGSIZE                      10040L
#define WSAENOPROTOOPT                   10042L
#define WSAEPROTONOSUPPORT               10043L
#define WSAESOCKTNOSUPPORT               10044L
#define WSAEOPNOTSUPP                    10045L
#define WSAEAFNOSUPPORT                  10047L
#define WSAEADDRINUSE                    10048L
#define WSAEADDRNOTAVAIL                 10049L
#define WSAENETUNREACH                   10051L
#define WSAECONNRESET                    10054L
#define WSAENOBUFS                       10055L
#define WSAEISCONN                       10056L
#define WSAENOTCONN                      10057L
#define WSAETIMEDOUT                     10060L
#define WSAECONNREFUSED                  10061L
#define WSANOTINITIALISED                10093L
#define ERROR_OUTOFMEMORY                14L
#define ERROR_NOT_SUPPORTED              50L
#define ERROR_BUFFER_OVERFLOW            111L
#define ERROR_MAX_THRDS_REACHED          164L
#define ERROR_INVALID_OPERATION          4317L 
#define ERROR_INVALID_STATE              5023L
#define SEC_E_BAD_BINDINGS               _HRESULT_TYPEDEF_(0x80090346L)
#define ERROR_MORE_DATA                  234L    // dderror
#define ERROR_ARITHMETIC_OVERFLOW        534L
#define SEC_E_INCOMPLETE_MESSAGE         _HRESULT_TYPEDEF_(0x80090318L)
#define ERROR_OPERATION_ABORTED          995L
#define ERROR_CONNECTION_REFUSED         1225L 
#define SEC_E_OK                         ((HRESULT)0x00000000L)
#define SEC_E_UNSUPPORTED_FUNCTION       _HRESULT_TYPEDEF_(0x80090302L)
#define SEC_E_TARGET_UNKNOWN             _HRESULT_TYPEDEF_(0x80090303L)
#define SEC_E_OUT_OF_SEQUENCE            _HRESULT_TYPEDEF_(0x80090310L)
#define SEC_E_INVALID_TOKEN              _HRESULT_TYPEDEF_(0x80090308L)
#define SEC_I_CONTINUE_NEEDED            _HRESULT_TYPEDEF_(0x00090312L)
#define ERROR_INVALID_FUNCTION           1L    // dderror
#define TRUST_E_TIME_STAMP               _HRESULT_TYPEDEF_(0x80096005L)
#define CRYPT_E_NOT_FOUND                _HRESULT_TYPEDEF_(0x80092004L)


#endif // XPLAT_WINERROR_H

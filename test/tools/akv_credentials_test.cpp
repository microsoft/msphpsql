// Copyright (c) Microsoft Corporation. All rights reserved.
// Licensed under the MIT License.
// Test-only PHP/ODBC boundaries around the unmodified production functions.
#include <algorithm>
#include <cerrno>
#include <climits>
#include <cstddef>
#include <cstdint>
#include <cstring>
#include <iostream>
#include <new>
#include <stdexcept>
#include <string>
#include <vector>

#ifdef _MSC_VER
#pragma warning(disable: 4200)
#endif

using BYTE = unsigned char;
using DWORD = uint32_t;
using SQLWCHAR = wchar_t;
using SQLPOINTER = void*;

#include "akv_layout.inc"

const int SQLSRV_ENCODING_UTF8 = 1;
const int SQLSRV_ERROR_KEYSTORE_INVALID_VALUE = 2;
const int SQLSRV_ERROR_CONNECT_STRING_ENCODING_TRANSLATE = 3;
const int SQLSRV_ERROR_AKV_AUTH_MISSING = 4;
const int SQLSRV_ERROR_AKV_NAME_MISSING = 5;
const int SQLSRV_ERROR_AKV_SECRET_MISSING = 6;
const int SQL_COPT_SS_CEKEYSTOREDATA = 7;
const int SQL_IS_POINTER = -4;

size_t live_allocations = 0;
int last_error = 0;
bool fail_encoding = false;
bool fail_odbc = false;
std::vector<std::vector<BYTE>> expected_payloads;
size_t observed_calls = 0;

void require(bool condition, const char* message)
{
    if (!condition) {
        throw std::runtime_error(message);
    }
}

void* sqlsrv_malloc(size_t size)
{
    void* memory = ::operator new(size);
    ++live_allocations;
    return memory;
}

template<typename T> class sqlsrv_malloc_auto_ptr {
    T* pointer = nullptr;

public:
    sqlsrv_malloc_auto_ptr() = default;
    sqlsrv_malloc_auto_ptr(const sqlsrv_malloc_auto_ptr&) = delete;
    sqlsrv_malloc_auto_ptr& operator=(const sqlsrv_malloc_auto_ptr&) = delete;
    ~sqlsrv_malloc_auto_ptr()
    {
        if (pointer) {
            ::operator delete(pointer);
            --live_allocations;
        }
    }
    void operator=(T* value)
    {
        require(pointer == nullptr, "Unexpected allocation replacement.");
        pointer = value;
    }
    T* get() const { return pointer; }
    explicit operator bool() const { return pointer != nullptr; }
    bool operator==(std::nullptr_t) const { return pointer == nullptr; }
};

struct sqlsrv_conn {
    struct {
        bool enabled = true;
        bool akv_required = true;
        int akv_mode = AKVCFG_AUTHMODE_CLIENTKEY;
        sqlsrv_malloc_auto_ptr<char> akv_id;
        sqlsrv_malloc_auto_ptr<char> akv_secret;
    } ce_option;
};

namespace core {
struct CoreException {};

void SQLSetConnectAttr(sqlsrv_conn*, int attribute, SQLPOINTER value, int type)
{
    require(attribute == SQL_COPT_SS_CEKEYSTOREDATA && type == SQL_IS_POINTER,
            "Unexpected ODBC attribute.");
    require(observed_calls < expected_payloads.size(), "Unexpected provider call.");
    const CEKEYSTOREDATA* data = static_cast<CEKEYSTOREDATA*>(value);
    require(std::wstring(data->name) == L"AZURE_KEY_VAULT", "Incorrect provider name.");
    const std::vector<BYTE>& expected = expected_payloads[observed_calls++];
    require(data->dataSize == expected.size(), "Incorrect credential payload length.");
    require(std::memcmp(data->data, expected.data(), expected.size()) == 0,
            "Credential payload was truncated or changed.");
    if (fail_odbc) {
        throw CoreException();
    }
}
}

bool check_error(bool condition, int error)
{
    if (condition) {
        last_error = error;
    }
    return condition;
}

#ifndef _Inout_
#define _Inout_
#endif
#define CHECK_CUSTOM_ERROR(condition, conn, error) if (check_error((condition), (error)))
#ifdef _WIN32
#define strnlen_s(s) strnlen_s(s, INT_MAX)
#else
size_t strnlen_s(const char* value) { return strnlen(value, INT_MAX); }
int mplat_memcpy_s(void*, size_t, const void*, size_t);
#define memcpy_s mplat_memcpy_s
#endif

SQLWCHAR* utf16_string_from_mbcs_string(int, const char*, unsigned int, unsigned int*)
{
    if (fail_encoding) {
        return nullptr;
    }
    const SQLWCHAR name[] = L"AZURE_KEY_VAULT";
    SQLWCHAR* memory = static_cast<SQLWCHAR*>(sqlsrv_malloc(sizeof(name)));
    std::copy_n(name, sizeof(name) / sizeof(name[0]), memory);
    return memory;
}

#include "akv_implementation.inc"

std::string credential(size_t length)
{
    std::string value(length, 'a');
    for (size_t i = 0; i < length; ++i) {
        value[i] = static_cast<char>('!' + i % 90);
    }
    return value;
}

std::vector<BYTE> payload(BYTE attribute, const std::string& value)
{
    std::vector<BYTE> bytes(1, attribute);
    bytes.insert(bytes.end(), value.begin(), value.end());
    return bytes;
}

void set_credential(sqlsrv_malloc_auto_ptr<char>& destination, const std::string& value)
{
    destination = static_cast<char*>(sqlsrv_malloc(value.size() + 1));
    std::copy(value.begin(), value.end(), destination.get());
    destination.get()[value.size()] = '\0';
}

void test_configuration(DWORD mode, const std::string& principal, const std::string& secret)
{
    sqlsrv_conn conn;
    conn.ce_option.akv_mode = static_cast<int>(mode);
    set_credential(conn.ce_option.akv_id, principal);
    set_credential(conn.ce_option.akv_secret, secret);
    std::vector<BYTE> flags(1 + sizeof(mode));
    flags[0] = AKV_CONFIG_FLAGS;
    std::copy_n(reinterpret_cast<const BYTE*>(&mode), sizeof(mode), flags.begin() + 1);
    expected_payloads = {flags, payload(AKV_CONFIG_PRINCIPALID, principal),
                         payload(AKV_CONFIG_AUTHSECRET, secret)};
    observed_calls = 0;
    last_error = 0;
    const size_t allocations = live_allocations;
    load_azure_key_vault(&conn);
    require(observed_calls == expected_payloads.size(), "Missing provider call.");
    require(last_error == 0 && live_allocations == allocations, "Configuration leaked storage or reported an error.");
}

void test_rejection_and_cleanup()
{
    sqlsrv_conn conn;
    expected_payloads.clear();
    for (size_t size : {size_t(UINT_MAX), SIZE_MAX - sizeof(CEKEYSTOREDATA), SIZE_MAX}) {
        last_error = 0;
        bool rejected = false;
        try {
            configure_azure_key_vault(&conn, AKV_CONFIG_AUTHSECRET, "x", size);
        } catch (const core::CoreException&) {
            rejected = true;
        }
        require(rejected && last_error == SQLSRV_ERROR_KEYSTORE_INVALID_VALUE,
                "Unrepresentable credential length was not rejected.");
        require(live_allocations == 0, "Invalid length allocated storage.");
    }

    last_error = 0;
    fail_encoding = true;
    bool rejected = false;
    try {
        configure_azure_key_vault(&conn, AKV_CONFIG_PRINCIPALID, "x", 1);
    } catch (const core::CoreException&) {
        rejected = true;
    }
    fail_encoding = false;
    require(rejected && last_error == SQLSRV_ERROR_CONNECT_STRING_ENCODING_TRANSLATE,
            "Provider name encoding failure was not reported.");
    require(live_allocations == 0, "Encoding failure leaked credential storage.");

    const std::string value = credential(8192);
    expected_payloads = {payload(AKV_CONFIG_AUTHSECRET, value)};
    observed_calls = 0;
    fail_odbc = true;
    rejected = false;
    try {
        configure_azure_key_vault(&conn, AKV_CONFIG_AUTHSECRET, value.data(), value.size());
    } catch (const core::CoreException&) {
        rejected = true;
    }
    fail_odbc = false;
    require(rejected && observed_calls == 1, "ODBC failure was not propagated.");
    require(live_allocations == 0, "ODBC failure leaked credential storage.");
}

int main()
{
    try {
        for (DWORD mode : {DWORD(AKVCFG_AUTHMODE_CLIENTKEY), DWORD(AKVCFG_AUTHMODE_PASSWORD)}) {
            for (size_t size : {size_t(1), size_t(259), size_t(260), size_t(263),
                                size_t(264), size_t(4096), size_t(8192), size_t(1024 * 1024)}) {
                const std::string value = credential(size);
                test_configuration(mode, value, "secret");
                test_configuration(mode, "principal", value);
                test_configuration(mode, value, value);
                require(live_allocations == 0, "Configuration leaked allocations.");
            }
        }
        test_rejection_and_cleanup();
        std::cout << "PASS: exact AKV payloads for both fields and authentication modes; "
                     "boundary lengths, size rejection and exception cleanup.\n";
        return 0;
    } catch (const std::exception& error) {
        std::cerr << error.what() << '\n';
    } catch (const core::CoreException&) {
        std::cerr << "Unexpected driver error.\n";
    }
    return 1;
}

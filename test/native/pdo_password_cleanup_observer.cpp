// Copyright (c) Microsoft Corporation. All rights reserved.
// Licensed under the MIT License.
// Linked only into the disposable Linux test module. Never ship this observer.
// GNU ld --wrap intercepts erasure and Zend release in the real driver objects.
#include <cstddef>
#include <cstring>

namespace {
const size_t MAX_EXPECTED = 16;
const size_t MAX_VALUE = 1024;
struct expected_release {
    unsigned char value[MAX_VALUE];
    size_t length;
    void* pointer;
    bool wiped;
    bool released;
};
expected_release expected[MAX_EXPECTED];
size_t expected_count = 0;
unsigned int failures = 0;

void observe_wipe(void* pointer, size_t length)
{
    for (size_t i = 0; i < expected_count; ++i) {
        expected_release& entry = expected[i];
        if (!entry.wiped && length == entry.length &&
            std::memcmp(pointer, entry.value, length) == 0) {
            entry.pointer = pointer;
            entry.wiped = true;
            return;
        }
    }
}

void observe_release(void* pointer)
{
    for (size_t i = 0; i < expected_count; ++i) {
        expected_release& entry = expected[i];
        if (entry.wiped && !entry.released && entry.pointer == pointer) {
            // This inspection is BEFORE the allocator is called. No freed memory
            // is read, and neither expected values nor memory contents are logged.
            const unsigned char* bytes = static_cast<const unsigned char*>(pointer);
            for (size_t j = 0; j < entry.length; ++j) {
                if (bytes[j] != 0) {
                    ++failures;
                    break;
                }
            }
            entry.released = true;
            return;
        }
    }
}
}

extern "C" {
void __real_explicit_bzero(void*, size_t);
void __real___explicit_bzero_chk(void*, size_t, size_t);
void __real__efree(void*);

void __wrap_explicit_bzero(void* pointer, size_t length)
{
    observe_wipe(pointer, length);
    __real_explicit_bzero(pointer, length);
}

void __wrap___explicit_bzero_chk(void* pointer, size_t length, size_t object_size)
{
    observe_wipe(pointer, length);
    __real___explicit_bzero_chk(pointer, length, object_size);
}

void __wrap__efree(void* pointer)
{
    observe_release(pointer);
    __real__efree(pointer);
}

__attribute__((visibility("default"))) void cleanup_probe_reset()
{
    std::memset(expected, 0, sizeof(expected));
    expected_count = 0;
    failures = 0;
}

__attribute__((visibility("default"))) void cleanup_probe_expect(const char* value, size_t length)
{
    if (expected_count == MAX_EXPECTED || length >= MAX_VALUE) {
        ++failures;
        return;
    }
    expected_release& entry = expected[expected_count++];
    std::memcpy(entry.value, value, length);
    entry.value[length] = '\0';
    entry.length = length + 1; // include the owned buffer's NUL terminator
}

__attribute__((visibility("default"))) unsigned int cleanup_probe_failures()
{
    return failures;
}

__attribute__((visibility("default"))) size_t cleanup_probe_released()
{
    size_t released = 0;
    for (size_t i = 0; i < expected_count; ++i) {
        released += expected[i].released ? 1 : 0;
    }
    return released;
}
}
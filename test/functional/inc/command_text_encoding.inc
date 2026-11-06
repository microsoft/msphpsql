<?php

function commandTextLocale()
{
    if (PHP_OS_FAMILY !== 'Linux') {
        return false;
    }

    // Set LC_CTYPE before the first connection initializes the driver's locale.
    $locales = array_unique([
        setlocale(LC_CTYPE, 0),
        'zh_CN.gbk', 'zh_CN.GBK', 'zh_CN.gb2312',
        'zh_TW.BIG5', 'zh_TW.big5', 'ja_JP.CP932',
    ]);
    foreach ($locales as $locale) {
        if (preg_match('/\.(gbk|gb2312|CP936)$/i', $locale)) {
            $data = ['text' => "\xD6\xD0'text", 'lead' => "\xBF"];
        } elseif (preg_match('/\.(BIG5|CP950)$/i', $locale)) {
            $data = ['text' => "\xA4\xA4'text", 'lead' => "\xBF"];
        } elseif (preg_match('/\.CP932$/i', $locale)) {
            $data = ['text' => "\x92\x86'text", 'lead' => "\x81"];
        } else {
            continue;
        }
        if (setlocale(LC_CTYPE, $locale) !== false) {
            return $data;
        }
    }
    return false;
}

function checkCommandText($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function checkCommandEncodingMessage($message)
{
    checkCommandText(is_string($message) && $message !== '', 'Missing encoding error message');
    if (PHP_OS_FAMILY !== 'Windows') {
        // The Unix formatter exposes ERROR_NO_UNICODE_TRANSLATION (1113), not errno.
        checkCommandText(
            preg_match('/\b0x([0-9a-f]+)\b/i', $message, $match) === 1
                && hexdec($match[1]) === 1113,
            'Expected the converter error code, got ' . $message
        );
    }
}

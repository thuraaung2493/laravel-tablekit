<?php

namespace Thuraaung\TableKit\Support;

use InvalidArgumentException;

final class SafeUrl
{
    public static function validate(string $url): string
    {
        if (preg_match('/[\x00-\x20\\\\]/', $url)
            || preg_match('/^(?!https?:)[A-Za-z][A-Za-z0-9+.-]*:/i', $url)
            || strpos($url, '//') === 0) {
            throw new InvalidArgumentException('Actions require a safe HTTP(S) or relative URL.');
        }

        return $url;
    }
}

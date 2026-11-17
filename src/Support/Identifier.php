<?php

namespace Thuraaung\TableKit\Support;

use InvalidArgumentException;

final class Identifier
{
    public static function database(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/D', $name)) {
            throw new InvalidArgumentException('Expected a plain database column identifier.');
        }

        return $name;
    }

    public static function key(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $name)
            || in_array($name, ['__proto__', 'constructor', 'prototype'], true)
            || strpos($name, '_tk_') === 0) {
            throw new InvalidArgumentException('Invalid or reserved TableKit key.');
        }

        return $name;
    }
}

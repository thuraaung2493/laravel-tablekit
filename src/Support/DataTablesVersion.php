<?php

namespace Thuraaung\TableKit\Support;

use InvalidArgumentException;

final class DataTablesVersion
{
    public static function validate(string $version): string
    {
        if (!preg_match('/^(auto|[123]|[123]\.\d+\.\d+)$/D', $version)
            || (strpos($version, '1.') === 0 && version_compare($version, '1.10.0', '<'))) {
            throw new InvalidArgumentException('Expected auto, 1, 2, 3 or a supported full DataTables version.');
        }

        return $version;
    }
}

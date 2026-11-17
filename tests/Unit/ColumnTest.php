<?php

namespace Thuraaung\TableKit\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Thuraaung\TableKit\Columns\Column;

class ColumnTest extends TestCase
{
    public function test_raw_html_requires_explicit_opt_in(): void
    {
        $row = new class {
            public function getAttribute($key) { return '<b>Unsafe</b>'; }
        };
        $this->assertSame('&lt;b&gt;Unsafe&lt;/b&gt;', Column::make('name')->render($row));
        $this->assertSame('<b>Trusted</b>', Column::make('name')->html(function () {
            return '<b>Trusted</b>';
        })->render($row));
    }

    public function test_sql_expressions_cannot_be_column_identifiers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Column::make('name')->database('name; DROP TABLE users');
    }
}

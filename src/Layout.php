<?php

namespace Thuraaung\TableKit;

use InvalidArgumentException;

class Layout
{
    private array $values = [];
    private const ALLOWED = [
        'density' => ['compact', 'comfortable'],
        'toolbar' => ['top', 'bottom', 'both', 'none'],
        'filters' => ['collapsible', 'inline', 'sidebar', 'none'],
    ];

    public static function make(): self
    {
        return new self();
    }

    private function set(string $key, string $value): self
    {
        if (!in_array($value, self::ALLOWED[$key], true)) {
            throw new InvalidArgumentException('Invalid TableKit '.$key.': '.$value);
        }
        $this->values[$key] = $value;

        return $this;
    }

    public function density(string $value): self
    {
        return $this->set('density', $value);
    }

    public function toolbar(string $value): self
    {
        return $this->set('toolbar', $value);
    }

    public function filters(string $value): self
    {
        return $this->set('filters', $value);
    }

    public function resolve(array $global, array $overrides = []): array
    {
        $values = array_replace($global, $this->values, array_filter($overrides, function ($value) {
            return $value !== null;
        }));
        foreach (self::ALLOWED as $key => $allowed) {
            if (!isset($values[$key]) || !in_array($values[$key], $allowed, true)) {
                throw new InvalidArgumentException('Invalid TableKit layout: '.$key);
            }
        }

        return $values;
    }
}

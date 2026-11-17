<?php

namespace Thuraaung\TableKit\Columns;

use DateTimeInterface;
use InvalidArgumentException;
use Thuraaung\TableKit\Support\Identifier;

class Column
{
    public string $key;
    public string $heading;
    public string $field;
    public bool $isSearchable = false;
    public bool $isSortable = false;
    public bool $isHidden = false;
    public bool $isRaw = false;
    public ?string $columnWidth = null;
    public string $alignment = 'left';
    protected $formatter;
    protected array $required = [];

    protected function __construct(string $key)
    {
        $this->key = Identifier::key($key);
        $this->field = $key;
        $this->heading = ucwords(str_replace('_', ' ', $key));
    }

    public static function make(string $key): self
    {
        return new static($key);
    }

    public function label(string $label): self
    {
        $this->heading = $label;

        return $this;
    }

    public function database(string $field): self
    {
        $this->field = Identifier::database($field);

        return $this;
    }

    public function searchable(bool $enabled = true): self
    {
        $this->isSearchable = $enabled;

        return $this;
    }

    public function sortable(bool $enabled = true): self
    {
        $this->isSortable = $enabled;

        return $this;
    }

    public function hidden(bool $enabled = true): self
    {
        $this->isHidden = $enabled;

        return $this;
    }

    public function width(string $width): self
    {
        if (!preg_match('/^\d+(\.\d+)?(px|%|rem|em)$/D', $width)) {
            throw new InvalidArgumentException('Column width requires a CSS length.');
        }
        $this->columnWidth = $width;

        return $this;
    }

    public function align(string $alignment): self
    {
        if (!in_array($alignment, ['left', 'center', 'right'], true)) {
            throw new InvalidArgumentException('Invalid column alignment.');
        }
        $this->alignment = $alignment;

        return $this;
    }

    /** The callback receives the model; requires() lists its SQL dependencies. */
    public function format(callable $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    public function date(string $format = 'Y-m-d'): self
    {
        return $this->format(function ($row) use ($format) {
            $value = $row->getAttribute($this->attribute());

            return $value === null ? '' : ($value instanceof DateTimeInterface
                ? $value->format($format)
                : (new \DateTimeImmutable($value))->format($format));
        });
    }

    public function number(int $decimals = 0): self
    {
        return $this->format(function ($row) use ($decimals) {
            $value = $row->getAttribute($this->attribute());

            return $value === null ? '' : number_format((float) $value, $decimals);
        });
    }

    /** Explicit trust boundary: sanitize untrusted HTML inside the callback. */
    public function html(callable $formatter): self
    {
        $this->isRaw = true;

        return $this->format($formatter);
    }

    public function requires(array $fields): self
    {
        $this->required = array_map([Identifier::class, 'database'], $fields);

        return $this;
    }

    public function requiredColumns(): array
    {
        return $this->required ?: [$this->field];
    }

    protected function attribute(): string
    {
        $parts = explode('.', $this->field);

        return end($parts);
    }

    public function render($row): string
    {
        $value = $this->formatter ? call_user_func($this->formatter, $row)
            : $row->getAttribute($this->attribute());
        $text = (string) ($value ?? '');

        return $this->isRaw ? $text
            : htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function toArray(): array
    {
        return [
            'data' => $this->key,
            'name' => $this->key,
            'title' => $this->heading,
            'searchable' => $this->isSearchable,
            'orderable' => $this->isSortable,
            'visible' => !$this->isHidden,
            'width' => $this->columnWidth,
            'className' => 'tk-align-'.$this->alignment,
        ];
    }
}

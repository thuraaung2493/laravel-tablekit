<?php

namespace Thuraaung\TableKit\Filters;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Thuraaung\TableKit\Http\TableRequest;
use Thuraaung\TableKit\Processing\EloquentProcessor;
use Thuraaung\TableKit\Support\Identifier;

class Filter
{
    public string $key;
    public string $type;
    public string $field;
    public string $heading;
    public string $hint = '';
    public array $options = [];
    public $initial = null;
    protected string $inputTimezone = 'UTC';
    protected string $storageTimezone = 'UTC';
    protected array $validation = [];
    protected $callback;

    protected function __construct(string $key, string $type)
    {
        $this->key = Identifier::key($key);
        $this->field = $key;
        $this->heading = ucwords(str_replace('_', ' ', $key));
        $this->type = $type;
    }

    public static function text(string $key): self
    {
        return new static($key, 'text');
    }

    public static function select(string $key, array $options): self
    {
        $filter = new static($key, 'select');
        $filter->options = $options;

        return $filter;
    }

    public static function date(string $key): self
    {
        return new static($key, 'date');
    }

    public static function dateRange(string $key): self
    {
        return new static($key, 'dateRange');
    }

    public function label(string $label): self
    {
        $this->heading = $label;

        return $this;
    }

    public function placeholder(string $placeholder): self
    {
        $this->hint = $placeholder;

        return $this;
    }

    public function defaultValue($value): self
    {
        $this->initial = $value;

        return $this;
    }

    public function database(string $field): self
    {
        $this->field = Identifier::database($field);

        return $this;
    }

    /** Date input is local midnight; SQL boundaries use the storage timezone. */
    public function timezone(string $input, string $storage = 'UTC'): self
    {
        new DateTimeZone($input);
        new DateTimeZone($storage);
        $this->inputTimezone = $input;
        $this->storageTimezone = $storage;

        return $this;
    }

    public function rules(array $rules): self
    {
        $this->validation = $rules;

        return $this;
    }

    /** Receives (Builder $query, $validatedValue); add predicates to this builder. */
    public function applyUsing(callable $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    public function validate($value)
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }
        $key = 'filters.'.$this->key;
        if ($this->type === 'dateRange') {
            $rules = [
                $key => ['array', 'max:2'],
                $key.'.from' => ['nullable', 'date_format:Y-m-d'],
                $key.'.to' => ['nullable', 'date_format:Y-m-d'],
            ];
        } else {
            $rules = [$key => array_merge(
                ['string', 'max:500'],
                $this->type === 'select' ? [Rule::in(array_keys($this->options))] : [],
                $this->type === 'date' ? ['date_format:Y-m-d'] : [],
                $this->validation
            )];
        }
        Validator::make(['filters' => [$this->key => $value]], $rules)->validate();
        if ($this->type === 'dateRange') {
            if (array_diff(array_keys($value), ['from', 'to'])) {
                TableRequest::invalid($key, 'Unknown date range field.');
            }
            if (!empty($value['from']) && !empty($value['to']) && $value['from'] > $value['to']) {
                TableRequest::invalid($key, 'The end date must follow the start date.');
            }
        }

        return $value;
    }

    private function boundary(string $date, bool $nextDay = false): string
    {
        $value = new DateTimeImmutable($date.' 00:00:00', new DateTimeZone($this->inputTimezone));
        if ($nextDay) {
            $value = $value->modify('+1 day');
        }

        return $value->setTimezone(new DateTimeZone($this->storageTimezone))->format('Y-m-d H:i:s');
    }

    public function apply(Builder $query, $value): void
    {
        if ($value === null) {
            return;
        }
        if ($this->callback) {
            // Group callback predicates so even callback ORs retain base scopes.
            $query->where(function (Builder $nested) use ($value) {
                call_user_func($this->callback, $nested, $value);
            });
        } elseif ($this->type === 'text') {
            EloquentProcessor::like($query, $this->field, $value);
        } elseif ($this->type === 'select') {
            $query->where($this->field, $value);
        } else {
            $range = $this->type === 'date' ? ['from' => $value, 'to' => $value] : $value;
            if (!empty($range['from'])) {
                $query->where($this->field, '>=', $this->boundary($range['from']));
            }
            if (!empty($range['to'])) {
                $query->where($this->field, '<', $this->boundary($range['to'], true));
            }
        }
    }
}

<?php

namespace Thuraaung\TableKit\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Thuraaung\TableKit\DataTable;

final class TableRequest
{
    public int $draw;
    public int $start;
    public int $length;
    public string $search;
    public array $orders = [];
    public array $filters = [];

    public static function invalid(string $field, string $message): void
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    public static function parse(Request $request, DataTable $table, array $columns): self
    {
        $data = Validator::make($request->all(), [
            'draw' => 'sometimes|integer|min:0|max:2147483647',
            'start' => 'sometimes|integer|min:0|max:2147483647',
            'length' => 'sometimes|integer|min:-1|max:2147483647|not_in:0',
            'search' => 'sometimes|array',
            'search.value' => 'nullable|string|max:500',
            'search.regex' => 'sometimes|in:0,1,true,false',
            'order' => 'sometimes|array|max:20',
            'order.*' => 'array',
            'order.*.column' => 'required|integer|min:0',
            'order.*.dir' => 'required|in:asc,desc',
            'columns' => 'sometimes|array|max:200',
            'columns.*' => 'array',
            'columns.*.data' => 'required|string|max:100',
            'columns.*.name' => 'nullable|string|max:100',
            'columns.*.search' => 'sometimes|array',
            'columns.*.search.regex' => 'sometimes|in:0,1,true,false',
            'columns.*.search.value' => 'nullable|string|max:500',
            'filters' => 'sometimes|array|max:50',
        ])->validate();
        if (in_array($request->input('search.regex'), [true, 1, '1', 'true'], true)) {
            self::invalid('search.regex', 'Regular expression searches are disabled.');
        }
        $input = new self();
        $settings = $table->settings();
        $input->draw = (int) ($data['draw'] ?? 0);
        $input->start = (int) ($data['start'] ?? 0);
        $length = (int) ($data['length'] ?? $settings['page_length']);
        $input->length = $length === -1 ? $settings['max_page_length']
            : min($length, $settings['max_page_length']);
        $input->search = $settings['searchable'] ? (string) ($data['search']['value'] ?? '') : '';
        $keys = array_map(function ($column) { return $column->key; }, $columns);
        $offset = $settings['selectable'] ? 1 : 0;
        if ($offset) {
            array_unshift($keys, '_tk_select');
        }
        if ($table->rowActions()) {
            $keys[] = '_tk_actions';
        }
        foreach ($data['columns'] ?? [] as $index => $clientColumn) {
            if (!isset($keys[$index]) || $clientColumn['data'] !== $keys[$index]
                || (!empty($clientColumn['name']) && $clientColumn['name'] !== $keys[$index])) {
                self::invalid('columns', 'Column mapping does not match this table.');
            }
            if (!empty($clientColumn['search']['value'])
                || in_array($clientColumn['search']['regex'] ?? false, [true, 1, '1', 'true'], true)) {
                self::invalid('columns', 'Use the configured TableKit filters for column searches.');
            }
        }
        foreach ($data['order'] ?? [] as $order) {
            $index = (int) $order['column'] - $offset;
            if (!isset($columns[$index]) || !$columns[$index]->isSortable) {
                self::invalid('order', 'This column cannot be sorted.');
            }
            $input->orders[] = [$columns[$index]->field, $order['dir']];
        }
        $input->filters = $data['filters'] ?? [];
        $definitions = $table->definedFilters();
        foreach ($input->filters as $key => $value) {
            if (!isset($definitions[$key])) {
                self::invalid('filters', 'Unknown filter.');
            }
            $input->filters[$key] = $definitions[$key]->validate($value);
        }
        foreach ($definitions as $key => $filter) {
            if (!array_key_exists($key, $input->filters)) {
                // An omitted payload uses defaults; an explicit empty input resets it.
                $input->filters[$key] = $filter->validate($filter->initial);
            }
        }

        return $input;
    }
}

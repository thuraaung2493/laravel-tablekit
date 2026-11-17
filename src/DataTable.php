<?php

namespace Thuraaung\TableKit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Thuraaung\TableKit\Columns\Column;
use Thuraaung\TableKit\Http\TableRequest;
use Thuraaung\TableKit\Processing\EloquentProcessor;

abstract class DataTable
{
    protected array $settings = [];

    public function __construct()
    {
        $this->configure();
    }

    abstract public function query(): Builder;

    /** @return Column[] */
    abstract public function columns(): array;

    public function configure(): void
    {
    }

    public function authorize(Request $request): bool
    {
        return true;
    }

    public function __invoke(Request $request)
    {
        abort_unless($this->authorize($request), 403);
        $columns = $this->definedColumns();
        $input = TableRequest::parse($request, $this, $columns);

        return response()->json((new EloquentProcessor())->process($this, $input, $columns));
    }

    public function searchable(bool $enabled = true): self
    {
        $this->settings['searchable'] = $enabled;

        return $this;
    }

    public function selectable(bool $enabled = true): self
    {
        $this->settings['selectable'] = $enabled;

        return $this;
    }

    public function perPage(int $length): self
    {
        if ($length < 1) {
            throw new InvalidArgumentException('Page length must be positive.');
        }
        $this->settings['page_length'] = $length;

        return $this;
    }

    public function defaultSort(string $key, string $direction = 'asc'): self
    {
        if (!in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Invalid default sort direction.');
        }
        $this->settings['default_sort'] = [$key, $direction];

        return $this;
    }

    public function settings(): array
    {
        $settings = array_replace(config('tablekit.table', []), [
            'searchable' => true,
            'selectable' => false,
            'default_sort' => null,
        ], $this->settings);
        if (!is_int($settings['max_page_length'] ?? null) || $settings['max_page_length'] < 1) {
            throw new InvalidArgumentException('Maximum page length must be positive.');
        }
        if (!is_int($settings['page_length'] ?? null) || $settings['page_length'] < 1
            || !is_int($settings['search_delay'] ?? null) || $settings['search_delay'] < 0
            || !is_array($settings['page_length_options'] ?? null)
            || ($settings['server_side'] ?? true) !== true) {
            throw new InvalidArgumentException('Invalid TableKit paging/search configuration; server-side mode is required.');
        }
        foreach ($settings['page_length_options'] as $length) {
            if (!is_int($length) || $length < 1) {
                throw new InvalidArgumentException('Page length options must be positive integers.');
            }
        }
        $settings['page_length'] = min($settings['page_length'], $settings['max_page_length']);

        return $settings;
    }

    /** @return Column[] */
    public function definedColumns(): array
    {
        $columns = $this->columns();
        $keys = [];
        foreach ($columns as $column) {
            if (!$column instanceof Column || in_array($column->key, $keys, true)) {
                throw new InvalidArgumentException('Columns must be unique Column definitions.');
            }
            $keys[] = $column->key;
        }
        if (!$columns) {
            throw new InvalidArgumentException('At least one column is required.');
        }

        return array_values($columns);
    }

    /** @return \Thuraaung\TableKit\Filters\Filter[] */
    public function filters(): array
    {
        return [];
    }

    public function definedFilters(): array
    {
        $filters = [];
        foreach ($this->filters() as $filter) {
            if (!$filter instanceof \Thuraaung\TableKit\Filters\Filter || isset($filters[$filter->key])) {
                throw new InvalidArgumentException('Filters must be unique Filter definitions.');
            }
            $filters[$filter->key] = $filter;
        }

        return $filters;
    }

    public function actions(): array
    {
        return [];
    }

    public function rowActions(): array
    {
        return [];
    }

    public function bulkActions(): array
    {
        return [];
    }

    public function orderedActions(array $actions): array
    {
        $indexed = [];
        foreach (array_values($actions) as $index => $action) {
            if (!$action instanceof \Thuraaung\TableKit\Actions\Action) {
                throw new InvalidArgumentException('Expected an Action definition.');
            }
            $indexed[] = ['action' => $action, 'index' => $index];
        }
        usort($indexed, function ($a, $b) {
            return ($a['action']->position <=> $b['action']->position)
                ?: ($a['index'] <=> $b['index']);
        });

        return array_column($indexed, 'action');
    }

    public function layout(): Layout
    {
        return Layout::make();
    }

    public function dataTablesVersion(): string
    {
        return (string) config('tablekit.datatables.version', 'auto');
    }

    public function requiredColumns(): array
    {
        return [];
    }
}

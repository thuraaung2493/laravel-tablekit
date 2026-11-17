<?php

namespace Thuraaung\TableKit\Processing;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Thuraaung\TableKit\DataTable;
use Thuraaung\TableKit\Http\TableRequest;
use Thuraaung\TableKit\Support\Identifier;

final class EloquentProcessor
{
    /** SQL LIKE with a portable explicit escape character, including literal wildcards. */
    public static function like(Builder $query, string $field, string $value, string $boolean = 'and'): void
    {
        $wrapped = $query->getQuery()->getGrammar()->wrap(Identifier::database($field));
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value).'%';
        $query->whereRaw($wrapped." LIKE ? ESCAPE '!'", [$pattern], $boolean);
    }

    public function process(DataTable $table, TableRequest $input, array $columns): array
    {
        $base = $table->query();
        $total = $this->count($base);
        $query = clone $base;
        $searchable = array_filter($columns, function ($column) {
            return $column->isSearchable;
        });
        if ($input->search !== '' && $searchable) {
            $query->where(function (Builder $nested) use ($searchable, $input) {
                foreach ($searchable as $column) {
                    self::like($nested, $column->field, $input->search, 'or');
                }
            });
        }
        foreach ($table->definedFilters() as $key => $filter) {
            $filter->apply($query, $input->filters[$key] ?? null);
        }
        $filtered = $this->count($query);
        $orders = $input->orders;
        $default = $table->settings()['default_sort'];
        if (!$orders && $default) {
            foreach ($columns as $column) {
                if ($column->key === $default[0] && $column->isSortable) {
                    $orders[] = [$column->field, $default[1]];
                }
            }
            if (!$orders) {
                throw new InvalidArgumentException('Default sorting requires a sortable column key.');
            }
        }
        if ($orders) {
            $query->reorder();
            foreach ($orders as $order) {
                $query->orderBy($order[0], $order[1]);
            }
        }
        // A unique tie breaker also stabilizes developer-supplied ordering.
        $key = $query->getModel()->getQualifiedKeyName();
        $query->orderBy($key, 'asc');
        if ($query->getQuery()->columns === null) {
            $fields = array_merge([$key], $table->requiredColumns());
            if ($table->rowActions()) {
                $fields[] = $query->getModel()->qualifyColumn($query->getModel()->getRouteKeyName());
            }
            foreach ($columns as $column) {
                $fields = array_merge($fields, $column->requiredColumns());
            }
            $fields = array_map([Identifier::class, 'database'], $fields);
            $query->select(array_values(array_unique($fields)));
        }
        $rows = $query->skip($input->start)->take($input->length)->get();
        $data = [];
        $selectedIds = [];
        $rowActions = $table->orderedActions($table->rowActions());
        foreach ($rows as $row) {
            $item = [];
            foreach ($columns as $column) {
                $item[$column->key] = $column->render($row);
            }
            if ($table->settings()['selectable']) {
                $id = (string) $row->getKey();
                $selectedIds[] = $id;
                $item['_tk_select'] = '<input type="checkbox" data-tk-select value="'.e($id)
                    .'" aria-label="Select record '.e($id).'">';
            }
            if ($rowActions) {
                $rendered = [];
                foreach ($rowActions as $action) {
                    $html = $action->render($row);
                    if ($html !== '') {
                        $rendered[] = $html;
                    }
                }
                $primary = array_shift($rendered) ?? '';
                $secondary = $rendered ? '<details><summary aria-label="More actions"><span aria-hidden="true">•••</span></summary>'
                    .'<div class="tk-action-menu">'.implode('', $rendered).'</div></details>' : '';
                $item['_tk_actions'] = '<div class="tk-row-actions">'.$primary.$secondary.'</div>';
            }
            $data[] = $item;
        }

        $response = [
            'draw' => $input->draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ];
        if ($table->settings()['selectable']) {
            $response['pageToken'] = \Thuraaung\TableKit\Support\PageSelection::issue($table, $selectedIds, request());
        }

        return $response;
    }

    private function count(Builder $query): int
    {
        // Pagination count handles grouped queries without hydrating models.
        return (int) (clone $query)->toBase()->getCountForPagination();
    }
}

<?php

namespace Thuraaung\TableKit\View\Components;

use Illuminate\View\Component;
use InvalidArgumentException;
use Thuraaung\TableKit\DataTable;
use Thuraaung\TableKit\Support\DataTablesVersion;

class Table extends Component
{
    public DataTable $definition;
    public string $id;
    public string $url;
    public ?string $title;
    public ?string $description;
    public array $layout;
    public array $columns;
    public array $settings;
    public string $configJson;
    public array $filterDefinitions;
    public array $headerActions;
    public array $bulkActions;
    public bool $hasRowActions;

    public function __construct(
        $table,
        string $url,
        ?string $id = null,
        ?string $density = null,
        ?string $toolbar = null,
        ?string $filters = null,
        ?string $datatableVersion = null,
        ?string $title = null,
        ?string $description = null
    ) {
        if (config('tablekit.datatables.assets', 'external') !== 'external') {
            throw new InvalidArgumentException('DataTables assets must be provided externally by the application.');
        }
        $this->definition = is_string($table) ? app($table) : $table;
        if (!$this->definition instanceof DataTable) {
            throw new InvalidArgumentException('The table must extend TableKit DataTable.');
        }
        abort_unless($this->definition->authorize(request()), 403);
        $this->id = $id ?? 'tablekit-'.bin2hex(random_bytes(6));
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/D', $this->id)) {
            throw new InvalidArgumentException('Table ID must be an HTML identifier.');
        }
        \Thuraaung\TableKit\Support\SafeUrl::validate($url);
        $this->url = $url;
        $this->title = $title;
        $this->description = $description;
        $this->columns = $this->definition->definedColumns();
        $this->settings = $this->definition->settings();
        $this->layout = $this->definition->layout()->resolve(config('tablekit.layout'), [
            'density' => $density,
            'toolbar' => $toolbar,
            'filters' => $filters,
        ]);
        $version = DataTablesVersion::validate($datatableVersion ?? $this->definition->dataTablesVersion());
        $this->filterDefinitions = $this->definition->definedFilters();
        $this->headerActions = $this->definition->orderedActions($this->definition->actions());
        $this->bulkActions = $this->definition->orderedActions($this->definition->bulkActions());
        $this->hasRowActions = (bool) $this->definition->rowActions();
        $clientColumns = array_map(function ($column) { return $column->toArray(); }, $this->columns);
        if ($this->settings['selectable']) {
            array_unshift($clientColumns, [
                'data' => '_tk_select',
                'name' => '_tk_select',
                'orderable' => false,
                'searchable' => false,
            ]);
        }
        if ($this->hasRowActions) {
            $clientColumns[] = [
                'data' => '_tk_actions',
                'name' => '_tk_actions',
                'orderable' => false,
                'searchable' => false,
            ];
        }
        $defaults = [];
        foreach ($this->filterDefinitions as $key => $filter) {
            $defaults[$key] = $filter->validate($filter->initial);
        }
        $this->configJson = json_encode([
            'id' => $this->id,
            'url' => $url,
            'version' => $version,
            'columns' => $clientColumns,
            'filters' => $defaults,
            'settings' => $this->settings,
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    }

    public function render()
    {
        return view('tablekit::components.table');
    }
}

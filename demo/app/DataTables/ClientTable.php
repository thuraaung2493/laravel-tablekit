<?php

namespace TableKitDemo\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use TableKitDemo\Models\Client;
use Thuraaung\TableKit\Actions\Action;
use Thuraaung\TableKit\Actions\BulkAction;
use Thuraaung\TableKit\Actions\RowAction;
use Thuraaung\TableKit\Columns\Column;
use Thuraaung\TableKit\DataTable;
use Thuraaung\TableKit\Filters\Filter;

class ClientTable extends DataTable
{
    public function authorize(Request $request): bool
    {
        return Gate::allows('viewAny', Client::class);
    }

    public function query(): Builder
    {
        return Client::query()->where('tenant_id', 1);
    }

    public function configure(): void
    {
        $this->selectable()->perPage(25)->defaultSort('created_at', 'desc');
    }

    public function requiredColumns(): array
    {
        return ['tenant_id'];
    }

    public function columns(): array
    {
        return [
            Column::make('name')->searchable()->sortable(),
            Column::make('phone')->searchable(),
            Column::make('office')->searchable()->sortable(),
            Column::make('status')->sortable(),
            Column::make('created_at')->label('Registered Date')->date()->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status', ['active' => 'Active', 'inactive' => 'Inactive']),
            Filter::select('office', ['yangon' => 'Yangon', 'mandalay' => 'Mandalay', 'bago' => 'Bago']),
        ];
    }

    public function actions(): array
    {
        return [
            Action::make('create')->label('Add Client')->icon('plus')->route('clients.create')
                ->authorized(function () { return Gate::allows('create', Client::class); }),
            Action::make('export')->label('Export CSV (scoped)')->icon('download')->route('clients.export'),
        ];
    }

    public function rowActions(): array
    {
        return [
            RowAction::make('view')->route('clients.show')->can('view'),
            RowAction::make('edit')->route('clients.edit')->can('update'),
            RowAction::make('delete')->route('clients.destroy')->method('DELETE')->confirm()->can('delete'),
        ];
    }

    public function bulkActions(): array
    {
        return [BulkAction::make('archive')->label('Mark inactive')->route('clients.bulk')->confirm()];
    }
}

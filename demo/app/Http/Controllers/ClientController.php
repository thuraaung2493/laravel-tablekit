<?php

namespace TableKitDemo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use TableKitDemo\DataTables\ClientTable;
use TableKitDemo\Http\Requests\ClientRequest;
use TableKitDemo\Models\Client;
use Thuraaung\TableKit\Actions\BulkAction;
use Thuraaung\TableKit\Http\TableRequest;
use Thuraaung\TableKit\Layout;

class ClientController extends \Illuminate\Routing\Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Client::class);
        $input = $request->validate([
            'density' => ['sometimes', Rule::in(['compact', 'comfortable'])],
            'toolbar' => ['sometimes', Rule::in(['top', 'bottom', 'both', 'none'])],
            'filters' => ['sometimes', Rule::in(['collapsible', 'inline', 'sidebar', 'none'])],
            'engine' => ['sometimes', Rule::in(['110', '113', '2', '3'])],
        ]);

        return view('clients.index', ['options' => $input]);
    }

    public function create()
    {
        Gate::authorize('create', Client::class);

        return view('clients.form', ['client' => new Client()]);
    }

    public function store(ClientRequest $request)
    {
        Client::create(array_merge($request->validated(), ['tenant_id' => 1]));

        return redirect()->route('clients.index')->with('notice', 'Client created.');
    }

    public function show(Client $client)
    {
        Gate::authorize('view', $client);

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        Gate::authorize('update', $client);

        return view('clients.form', compact('client'));
    }

    public function update(ClientRequest $request, Client $client)
    {
        $client->update($request->validated());

        return redirect()->route('clients.index')->with('notice', 'Client updated.');
    }

    public function destroy(Client $client)
    {
        Gate::authorize('delete', $client);
        $client->delete();

        return redirect()->route('clients.index')->with('notice', 'Client deleted.');
    }

    public function bulk(Request $request)
    {
        DB::transaction(function () use ($request) {
            $rows = BulkAction::authorizedModels(new ClientTable(), $request, 'update');
            foreach ($rows as $row) {
                $row->update(['status' => 'inactive']);
            }
        });

        return redirect()->route('clients.index')->with('notice', 'Selected clients marked inactive.');
    }

    // A deliberately small app-owned export, not a core export integration.
    public function export(Request $request)
    {
        $table = new ClientTable();
        abort_unless($table->authorize($request), 403);
        $input = TableRequest::parse($request, $table, $table->definedColumns());
        $query = $table->query();
        foreach ($table->definedFilters() as $key => $filter) {
            $filter->apply($query, $input->filters[$key] ?? null);
        }
        if ($input->search !== '') {
            $query->where(function ($query) use ($table, $input) {
                foreach ($table->definedColumns() as $column) {
                    if ($column->isSearchable) {
                        \Thuraaung\TableKit\Processing\EloquentProcessor::like($query, $column->field, $input->search, 'or');
                    }
                }
            });
        }

        return response()->streamDownload(function () use ($query) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Name', 'Phone', 'Office', 'Status', 'Registered Date']);
            $query->select(['id', 'name', 'phone', 'office', 'status', 'created_at'])->chunkById(500, function ($rows) use ($stream) {
                foreach ($rows as $row) {
                    $values = [$row->name, $row->phone, $row->office, $row->status, (string) $row->created_at];
                    $safe = array_map(function ($value) {
                        return preg_match('/^[=+@\-\t\r\n]/', $value) ? "'".$value : $value;
                    }, $values);
                    fputcsv($stream, $safe);
                }
            });
            fclose($stream);
        }, 'clients.csv', ['Content-Type' => 'text/csv']);
    }
}

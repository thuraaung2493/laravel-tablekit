<?php

namespace Thuraaung\TableKit\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Thuraaung\TableKit\DataTable;
use Thuraaung\TableKit\Support\PageSelection;

class BulkAction extends Action
{
    protected string $httpMethod = 'POST';

    /** Validate page membership, reapply scopes and authorize every current record. */
    public static function authorizedModels(DataTable $table, Request $request, string $ability)
    {
        abort_unless($table->authorize($request), 403);
        $ids = PageSelection::validate($table, $request);
        $query = $table->query();
        $rows = $query->whereIn($query->getModel()->getQualifiedKeyName(), $ids)->get();
        abort_unless($rows->count() === count($ids), 403);
        foreach ($rows as $row) {
            Gate::authorize($ability, $row);
        }

        return $rows;
    }

    public function method(string $method): Action
    {
        if (strtoupper($method) === 'GET') {
            throw new \InvalidArgumentException('Bulk actions require a mutation method.');
        }

        return parent::method($method);
    }
}

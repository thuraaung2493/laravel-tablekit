<?php

namespace Thuraaung\TableKit\Support;

use Illuminate\Http\Request;
use Thuraaung\TableKit\DataTable;
use Thuraaung\TableKit\Http\TableRequest;

final class PageSelection
{
    private static function signature(string $payload): string
    {
        $key = (string) config('app.key');
        if ($key === '') {
            throw new \LogicException('Current-page selection requires an application key.');
        }

        return hash_hmac('sha256', $payload, $key);
    }

    private static function user(Request $request): string
    {
        $user = $request->user();

        return $user ? (string) $user->getAuthIdentifier() : '';
    }

    public static function issue(DataTable $table, array $ids, Request $request): string
    {
        $payload = base64_encode(json_encode([
            'table' => get_class($table),
            'user' => self::user($request),
            'expires' => time() + 1800,
            'ids' => array_map('strval', $ids),
        ], JSON_THROW_ON_ERROR));

        return $payload.'.'.self::signature($payload);
    }

    public static function validate(DataTable $table, Request $request): array
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:'.$table->settings()['max_page_length'],
            'ids.*' => 'required|string|max:255|distinct',
            'page_token' => 'required|string|max:100000',
        ]);
        $parts = explode('.', $validated['page_token']);
        if (count($parts) !== 2 || !hash_equals(self::signature($parts[0]), $parts[1])) {
            TableRequest::invalid('page_token', 'Invalid page selection.');
        }
        $payload = json_decode((string) base64_decode($parts[0], true), true);
        if (!is_array($payload) || ($payload['table'] ?? null) !== get_class($table)
            || ($payload['user'] ?? null) !== self::user($request)
            || ($payload['expires'] ?? 0) < time()
            || array_diff($validated['ids'], $payload['ids'] ?? [])) {
            TableRequest::invalid('ids', 'Select records from the current page and reload expired selections.');
        }

        return $validated['ids'];
    }
}

<?php

namespace TableKitDemo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use TableKitDemo\Models\Client;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('client')
            ? $this->user()->can('update', $this->route('client'))
            : $this->user()->can('create', Client::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'office' => 'required|in:yangon,mandalay,bago',
            'status' => 'required|in:active,inactive',
        ];
    }
}

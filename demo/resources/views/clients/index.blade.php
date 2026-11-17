@extends('layout')
@section('content')
    <form method="GET" class="demo-options" aria-label="Demo layout">
        @foreach(['density' => ['compact', 'comfortable'], 'toolbar' => ['top', 'bottom', 'both', 'none'], 'filters' => ['collapsible', 'inline', 'sidebar', 'none'], 'engine' => ['3', '2', '113', '110']] as $name => $values)
            <label>{{ ucfirst($name) }}
                <select name="{{ $name }}">
                    @foreach($values as $value)<option @if(($options[$name] ?? $values[0]) === $value) selected @endif>{{ $value }}</option>@endforeach
                </select>
            </label>
        @endforeach
        <button>Apply layout</button>
    </form>
    <x-tablekit::table :table="TableKitDemo\DataTables\ClientTable::class"
        :url="route('clients.data')" id="clients-table" title="Clients"
        description="Manage and browse client records"
        :density="$options['density'] ?? null" :toolbar="$options['toolbar'] ?? null"
        :filters="$options['filters'] ?? null" />
@endsection
@section('scripts')
    @if(($options['engine'] ?? '3') !== '3')<script src="/engines/jquery.js"></script>@endif
    <script src="/engines/dt{{ $options['engine'] ?? '3' }}.js"></script>
    <script src="/vendor/tablekit/js/adapters/datatables-v{{ in_array($options['engine'] ?? '3', ['110', '113']) ? '1' : ($options['engine'] ?? '3') }}.js"></script>
    <script src="/vendor/tablekit/js/tablekit.js"></script>
@endsection

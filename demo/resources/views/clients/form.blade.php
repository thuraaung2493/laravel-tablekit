@extends('layout')
@section('content')
    <section class="form-card">
        <h1>{{ $client->exists ? 'Edit Client' : 'Create Client' }}</h1>
        <p>Keep client contact and office details up to date.</p>
        @if($errors->any())
            <div role="alert"><p>Please correct the following:</p><ul>
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul></div>
        @endif
        <form method="POST" action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}">
            @csrf
            @if($client->exists)@method('PUT')@endif
            <label>Client Name <input name="name" required maxlength="120" value="{{ old('name', $client->name) }}"></label>
            <label>Phone <input name="phone" required maxlength="30" value="{{ old('phone', $client->phone) }}"></label>
            <label for="client-office">Office</label><select id="client-office" name="office">
                @foreach(['yangon' => 'Yangon', 'mandalay' => 'Mandalay', 'bago' => 'Bago'] as $value => $label)
                    <option value="{{ $value }}" @if(old('office', $client->office) === $value) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
            <label for="client-status">Status</label><select id="client-status" name="status">
                @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                    <option value="{{ $value }}" @if(old('status', $client->status) === $value) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
            <div class="form-actions"><button>Save</button><a href="{{ route('clients.index') }}">Cancel</a></div>
        </form>
    </section>
@endsection

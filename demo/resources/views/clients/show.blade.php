@extends('layout')
@section('content')
    <section class="form-card">
        <h1>{{ $client->name }}</h1>
        <dl><dt>Phone</dt><dd>{{ $client->phone }}</dd><dt>Office</dt><dd>{{ $client->office }}</dd><dt>Status</dt><dd>{{ $client->status }}</dd></dl>
        <a href="{{ route('clients.index') }}">Back to clients</a>
    </section>
@endsection

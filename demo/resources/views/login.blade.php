@extends('layout')
@section('content')
    <section class="form-card">
        <h1>TableKit demo</h1>
        <p>This local demo signs in as the seeded demo administrator.</p>
        <form method="POST" action="/login">@csrf<button>Enter demo</button></form>
    </section>
@endsection

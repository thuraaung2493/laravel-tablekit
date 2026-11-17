<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laravel TableKit demo</title>
    <link rel="stylesheet" href="/vendor/tablekit/css/tablekit.css">
    <link rel="stylesheet" href="/demo.css">
</head>
<body>
    <main>
        <nav><a href="{{ route('clients.index') }}">TableKit / Clients</a><span>Local demonstration</span></nav>
        @if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
        @yield('content')
    </main>
    @yield('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'OceaPress')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        form { display: flex; flex-direction: column; gap: 1rem; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <nav class="mx-auto flex max-w-6xl items-center gap-5 px-4 py-5">
        <a href="{{ route('employe.accueil') }}" class="font-bold text-blue-800">OceaPress</a>
        <div class="flex items-center gap-4">@yield('navigation')</div>
    </nav>
    <main class="mx-auto max-w-6xl px-4 pb-10">
        @yield('content')
    </main>
</body>
</html>

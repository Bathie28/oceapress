<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'OceaPress')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #0F172A; background: #FFFFFF; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; background: #FFFFFF !important; color: #0F172A !important; }
        a { color: #2563EB; text-underline-offset: 3px; }
        a:hover { color: #1E40AF; }
        button, input, select, textarea { font: inherit; }
        button, .op-button-primary { border: 0; border-radius: 9999px !important; background: #2563EB !important; color: #fff !important; padding: .7rem 1.15rem; font-weight: 650; cursor: pointer; }
        button:hover, .op-button-primary:hover { background: #1E40AF !important; }
        .op-button-secondary { display: inline-flex; align-items: center; justify-content: center; border: 1px solid #CBD5E1; border-radius: 9999px; background: #fff; color: #0F172A; padding: .65rem 1rem; font-weight: 600; text-decoration: none; }
        input:not([type=checkbox]):not([type=radio]):not([type=file]), select, textarea { border: 1px solid #CBD5E1; border-radius: .7rem; background: #fff; color: #0F172A; padding: .6rem .75rem; }
        input:focus, select:focus, textarea:focus, button:focus-visible, a:focus-visible { outline: 3px solid #93C5FD; outline-offset: 2px; }
        label { color: #64748B; }
        h1, h2, h3 { color: #0F172A; font-weight: 700; }
        h1 { letter-spacing: -.025em; }
        .bg-slate-100, .bg-gray-50, .bg-slate-50 { background-color: #F8FAFC !important; }
        .bg-white { background-color: #FFFFFF !important; }
        .rounded-xl.bg-white, .rounded-2xl.bg-white, .rounded-lg.bg-white { border: 1px solid #E2E8F0; }
        .bg-blue-700 { background-color: #2563EB !important; }
        .text-blue-700, .text-blue-800 { color: #2563EB !important; }
        .text-slate-500, .text-slate-600, .text-gray-600 { color: #64748B !important; }
        .bg-red-50 { background-color: #FEF2F2 !important; }
        .bg-emerald-50 { background-color: #ECFDF5 !important; }
        .bg-amber-50 { background-color: #FEF3C7 !important; }
        .rounded-xl, .rounded-2xl { border-radius: 1rem; }
        .shadow, .shadow-xl { box-shadow: 0 8px 28px rgb(17 24 39 / 6%); }
        table { border-collapse: collapse; }
        th { color: #64748B; font-weight: 600; }
        td, th { border-bottom: 1px solid #E2E8F0; }
        .op-shell { display: flex; min-height: 100vh; flex-direction: column; }
        .op-header { border-bottom: 1px solid #E2E8F0; background: #fff; }
        .op-header-inner { display: flex; max-width: 1200px; min-height: 76px; margin: 0 auto; align-items: center; justify-content: space-between; gap: 1rem; padding: .75rem 1.25rem; }
        .op-brand { display: inline-flex; align-items: center; gap: .7rem; color: #0F172A; font-size: 1.15rem; font-weight: 750; text-decoration: none; }
        .op-monogram { display: grid; width: 42px; height: 42px; place-items: center; border-radius: 14px; background: #2563EB; color: #FFFFFF; font-size: 1rem; font-weight: 850; letter-spacing: -.08em; }
        .op-nav { display: flex; align-items: center; justify-content: flex-end; gap: .45rem; flex-wrap: wrap; }
        .op-nav a { border-radius: 9999px; padding: .55rem .8rem; color: #64748B; text-decoration: none; font-size: .93rem; font-weight: 550; }
        .op-nav a:hover { background: #DBEAFE; color: #1E40AF; }
        .op-nav form { display: inline; margin: 0; }
        .op-nav button { padding: .55rem .85rem; font-size: .9rem; }
        .op-main { width: min(100% - 2rem, 1200px); margin: 0 auto; flex: 1; padding: 1.5rem 0 3rem; }
        .op-footer { padding: 1.25rem; color: #64748B; text-align: center; font-size: .85rem; }
        .op-login { display: grid; min-height: calc(100vh - 160px); place-items: center; }
        .op-login-card, .op-card { border: 1px solid #E2E8F0; border-radius: 1rem; background: #fff; box-shadow: 0 8px 28px rgb(15 23 42 / 5%); }
        .op-login-card { width: min(100%, 440px); padding: 2rem; }
        .op-banner { border-radius: .9rem; background: #DBEAFE; padding: 1rem 1.25rem; color: #1E40AF; }
        .op-badge { display: inline-flex; align-items: center; border-radius: 9999px; background: #DBEAFE; padding: .25rem .7rem; color: #2563EB; font-size: .85rem; font-weight: 650; }
        .op-alert-success { border-radius: .8rem; background: #ECFDF5; color: #065F46; padding: .85rem 1rem; }
        .op-alert-error { border-radius: .8rem; background: #FEF2F2; color: #B91C1C; padding: .85rem 1rem; }
        .op-alert-info { border-radius: .8rem; background: #DBEAFE; color: #1E40AF; padding: .85rem 1rem; }
        @media (max-width: 700px) { .op-header-inner { align-items: flex-start; flex-direction: column; } .op-nav { justify-content: flex-start; } }
    </style>
    @yield('head')
</head>
<body>
<div class="op-shell" data-layout="oceapress">
    <header class="op-header">
        <div class="op-header-inner">
            <a class="op-brand" href="@auth @if (auth()->user()->client){{ route('client.accueil') }}@elseif (auth()->user()->employe){{ route('employe.accueil') }}@elseif (auth()->user()->administrateur){{ route('admin.accueil') }}@else{{ route('login') }}@endif @else{{ route('login') }}@endauth">
                <span class="op-monogram" aria-hidden="true">OP</span><span>OceaPress</span>
            </a>
            @auth
                @php($utilisateurCourant = auth()->user())
                @if ($utilisateurCourant->client)
                    @unless ($utilisateurCourant->doitChangerMotDePasse)
                        <nav class="op-nav" aria-label="Navigation client">
                            <a href="{{ route('client.accueil') }}">Accueil client</a>
                            <a href="{{ route('client.commandes.index') }}">Mes commandes</a>
                            <a href="{{ route('client.notifications.index') }}">Mes notifications</a>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Déconnexion</button></form>
                        </nav>
                    @else
                        <nav class="op-nav" aria-label="Navigation client"><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Déconnexion</button></form></nav>
                    @endunless
                @elseif ($utilisateurCourant->employe)
                    <nav class="op-nav" aria-label="Navigation employé">
                        <a href="{{ route('employe.accueil') }}">Accueil employé</a>
                        <a href="{{ route('employe.tableau-de-bord') }}">Tableau de bord</a>
                        <a href="{{ route('employe.commandes.index') }}">Commandes</a>
                        <a href="{{ route('employe.commandes.create') }}">Nouvelle commande</a>
                        <a href="{{ route('employe.scan') }}">Scanner un QR code</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Déconnexion</button></form>
                    </nav>
                @elseif ($utilisateurCourant->administrateur)
                    <nav class="op-nav" aria-label="Navigation administrateur">
                        <a href="{{ route('admin.accueil') }}">Accueil administrateur</a>
                        <a href="{{ route('admin.pressings.index') }}">Gérer les pressings et tarifs</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Déconnexion</button></form>
                    </nav>
                @endif
            @endauth
        </div>
    </header>
    <main class="op-main">@yield('content')</main>
    <footer class="op-footer">© {{ date('Y') }} OceaPress</footer>
</div>
@yield('scripts')
</body>
</html>

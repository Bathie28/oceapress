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
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        body { margin: 0; background: #FAF7F2; color: #111827; }
        * { box-sizing: border-box; }
        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; }
        button:focus-visible, a:focus-visible { outline: 3px solid #93C5FD; outline-offset: 3px; }
    </style>
    @yield('head')
</head>
<body>
@auth
@php($utilisateurCourant = auth()->user())
@php($estEmploye = (bool) $utilisateurCourant->employe)
@php($estClient = (bool) $utilisateurCourant->client)
@php($estAdministrateur = (bool) $utilisateurCourant->administrateur)
@php($accueilRole = $estClient ? route('client.accueil') : ($estEmploye ? route('employe.accueil') : ($estAdministrateur ? route('admin.accueil') : route('login'))))
@php($nomUtilisateur = trim(($utilisateurCourant->prenom ?? '').' '.($utilisateurCourant->nom ?? '')))
<div class="min-h-screen md:flex" data-layout="connecte">
    <aside id="sidebar-connecte" class="fixed inset-y-0 left-0 z-50 hidden w-72 flex-col bg-[#F3E6D3] px-5 py-6 shadow-xl md:sticky md:top-0 md:flex md:h-screen md:w-72 md:flex-none md:shadow-none" aria-label="Navigation latérale">
        <div class="flex items-center justify-between">
            <a href="{{ $accueilRole }}" class="flex items-center gap-3 text-[#111827] font-bold">
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-[#F5C518] text-base font-extrabold" aria-hidden="true">OP</span>
                <span class="text-lg">OceaPress</span>
            </a>
            <button id="fermer-menu-connecte" type="button" class="rounded-lg p-2 text-[#111827] md:hidden" aria-label="Fermer le menu">✕</button>
        </div>

        <nav class="mt-8 space-y-2" aria-label="Navigation {{ $estEmploye ? 'employé' : ($estClient ? 'client' : ($estAdministrateur ? 'administrateur' : 'connectée')) }}">
            @if ($estEmploye)
                @php($liens = [
                    ['label' => 'Accueil employé', 'route' => 'employe.accueil'],
                    ['label' => 'Tableau de bord', 'route' => 'employe.tableau-de-bord'],
                    ['label' => 'Commandes', 'route' => 'employe.commandes.index'],
                    ['label' => 'Nouvelle commande', 'route' => 'employe.commandes.create'],
                    ['label' => 'Scanner un QR code', 'route' => 'employe.scan'],
                ])
            @elseif ($estClient && ! $utilisateurCourant->doitChangerMotDePasse)
                @php($liens = [
                    ['label' => 'Accueil client', 'route' => 'client.accueil'],
                    ['label' => 'Mes commandes', 'route' => 'client.commandes.index'],
                    ['label' => 'Mes notifications', 'route' => 'client.notifications.index'],
                ])
            @elseif ($estAdministrateur)
                @php($liens = [
                    ['label' => 'Accueil administrateur', 'route' => 'admin.accueil'],
                    ['label' => 'Gérer les pressings et tarifs', 'route' => 'admin.pressings.index'],
                ])
            @else
                @php($liens = [])
            @endif

            @foreach ($liens as $lien)
                @php($actif = request()->routeIs($lien['route']))
                <a href="{{ route($lien['route']) }}" @if ($actif) aria-current="page" @endif class="block rounded-xl px-4 py-3 text-sm font-semibold {{ $actif ? 'bg-[#111827] text-white' : 'text-[#374151] hover:bg-white/70 hover:text-[#111827]' }}">{{ $lien['label'] }}</a>
            @endforeach
        </nav>
    </aside>

    <button id="fond-menu-connecte" type="button" class="fixed inset-0 z-40 hidden bg-black/40 md:hidden" aria-label="Fermer le menu"></button>

    <div class="min-w-0 flex-1">
        <header class="flex min-h-20 items-center justify-between gap-4 border-b border-[#E5E7EB] bg-white px-4 py-3 md:px-8">
            <div class="flex items-center gap-3">
                <button id="ouvrir-menu-connecte" type="button" class="rounded-xl border border-[#D1D5DB] bg-white px-3 py-2 font-semibold text-[#111827] md:hidden" aria-label="Ouvrir le menu" aria-controls="sidebar-connecte" aria-expanded="false">Menu</button>
                <p class="text-lg font-bold text-[#111827] md:text-xl">@yield('pageTitle', View::hasSection('title') ? trim($__env->yieldContent('title')) : 'OceaPress')</p>
            </div>
            <div class="flex items-center gap-3">
                @if ($nomUtilisateur !== '')
                    <span class="hidden text-sm font-medium text-[#6B7280] sm:block">{{ $nomUtilisateur }}</span>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="rounded-xl bg-[#111827] px-4 py-2 text-sm font-semibold text-white">Déconnexion</button></form>
            </div>
        </header>
        <main class="min-h-[calc(100vh-5rem)] p-4 md:p-8">@yield('content')</main>
    </div>
</div>
@endauth

@guest
<div data-layout="connecte" class="min-h-screen">@yield('content')</div>
@endguest

@yield('scripts')
<script>
(() => {
    const sidebar = document.getElementById('sidebar-connecte');
    const openButton = document.getElementById('ouvrir-menu-connecte');
    const closeButton = document.getElementById('fermer-menu-connecte');
    const backdrop = document.getElementById('fond-menu-connecte');
    if (!sidebar || !openButton || !closeButton || !backdrop) return;

    const setMenu = (open) => {
        sidebar.classList.toggle('hidden', !open);
        sidebar.classList.toggle('flex', open);
        backdrop.classList.toggle('hidden', !open);
        openButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) closeButton.focus();
    };

    openButton.setAttribute('aria-controls', 'sidebar-connecte');
    openButton.setAttribute('aria-expanded', 'false');
    openButton.addEventListener('click', () => setMenu(openButton.getAttribute('aria-expanded') !== 'true'));
    closeButton.addEventListener('click', () => setMenu(false));
    backdrop.addEventListener('click', () => setMenu(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && openButton.getAttribute('aria-expanded') === 'true') {
            setMenu(false);
            openButton.focus();
        }
    });
})();
</script>
</body>
</html>

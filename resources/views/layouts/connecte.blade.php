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
        body { margin: 0; background: #111827; color: #111827; }
        * { box-sizing: border-box; }
        button, input, select, textarea { font: inherit; }
        button:focus-visible, a:focus-visible, input:focus-visible { outline: 3px solid #93C5FD; outline-offset: 3px; }
        .op-button-primary { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 9999px; background: #2563EB; color: #fff !important; padding: .7rem 1.15rem; font-weight: 650; cursor: pointer; text-decoration: none; }
        .op-button-primary:hover { background: #1E40AF; }
        .op-button-secondary { display: inline-flex; align-items: center; justify-content: center; border: 1px solid #CBD5E1; border-radius: 9999px; background: #fff; color: #0F172A; padding: .65rem 1rem; font-weight: 600; text-decoration: none; }
        .op-card { border: 0; border-radius: .75rem; background: #fff; box-shadow: 0 1px 3px rgb(15 23 42 / 8%); }
        .op-banner { border-radius: .9rem; background: #DBEAFE; padding: 1rem 1.25rem; color: #1E40AF; }
        .op-badge { display: inline-flex; align-items: center; border-radius: 9999px; background: #DBEAFE; padding: .25rem .7rem; color: #2563EB; font-size: .85rem; font-weight: 650; }
    </style>
    @yield('head')
</head>
<body>
@auth
@php
    $utilisateurCourant = auth()->user();
    $estEmploye = (bool) $utilisateurCourant->employe;
    $estClient = (bool) $utilisateurCourant->client;
    $estAdministrateur = (bool) $utilisateurCourant->administrateur;
    $accueilRole = $estClient ? route('client.accueil') : ($estEmploye ? route('employe.accueil') : ($estAdministrateur ? route('admin.accueil') : route('login')));
    $nomUtilisateur = trim(($utilisateurCourant->prenom ?? '').' '.($utilisateurCourant->nom ?? ''));
    $initiales = mb_strtoupper(mb_substr((string) $utilisateurCourant->prenom, 0, 1).mb_substr((string) $utilisateurCourant->nom, 0, 1));
    $roleLibelle = $estClient ? 'Client' : ($estEmploye ? 'Employé' : ($estAdministrateur ? 'Administrateur' : 'Utilisateur'));

    if ($estEmploye) {
        $groupes = [
            ['titre' => 'Vue d’ensemble', 'liens' => [
                ['label' => 'Accueil employé', 'route' => 'employe.accueil', 'icone' => 'home'],
                ['label' => 'Tableau de bord', 'route' => 'employe.tableau-de-bord', 'icone' => 'chart'],
            ]],
            ['titre' => 'Opérations', 'liens' => [
                ['label' => 'Commandes', 'route' => 'employe.commandes.index', 'icone' => 'list'],
                ['label' => 'Nouvelle commande', 'route' => 'employe.commandes.create', 'icone' => 'plus'],
                ['label' => 'Scanner un QR code', 'route' => 'employe.scan', 'icone' => 'scan'],
            ]],
        ];
        $raccourcis = [
            ['label' => 'Nouvelle commande', 'route' => 'employe.commandes.create', 'icone' => 'plus'],
            ['label' => 'Scanner un QR code', 'route' => 'employe.scan', 'icone' => 'scan'],
        ];
    } elseif ($estClient && ! $utilisateurCourant->doitChangerMotDePasse) {
        $groupes = [
            ['titre' => 'Mon espace', 'liens' => [
                ['label' => 'Accueil client', 'route' => 'client.accueil', 'icone' => 'home'],
                ['label' => 'Mes commandes', 'route' => 'client.commandes.index', 'icone' => 'list'],
                ['label' => 'Mes notifications', 'route' => 'client.notifications.index', 'icone' => 'bell'],
            ]],
        ];
        $raccourcis = [
            ['label' => 'Mes commandes', 'route' => 'client.commandes.index', 'icone' => 'list'],
            ['label' => 'Mes notifications', 'route' => 'client.notifications.index', 'icone' => 'bell', 'compteur' => \App\Models\Notification::query()->where('idClient', $utilisateurCourant->client->idClient)->where('lu', false)->count()],
        ];
    } elseif ($estAdministrateur) {
        $groupes = [
            ['titre' => 'Administration', 'liens' => [
                ['label' => 'Accueil administrateur', 'route' => 'admin.accueil', 'icone' => 'home'],
                ['label' => 'Gérer les pressings et tarifs', 'route' => 'admin.pressings.index', 'icone' => 'building'],
            ]],
        ];
        $raccourcis = [
            ['label' => 'Gérer les pressings et tarifs', 'route' => 'admin.pressings.index', 'icone' => 'building'],
        ];
    } else {
        $groupes = [];
        $raccourcis = [];
    }

    $titrePage = trim($__env->yieldContent('pageTitle'));
    $titreDeclare = $titrePage !== '';
    if (! $titreDeclare) {
        $titrePage = preg_replace('/\s+—\s+OceaPress$/u', '', trim($__env->yieldContent('title'))) ?: 'OceaPress';
    }
@endphp
<div class="min-h-screen lg:flex lg:h-screen lg:overflow-hidden" data-layout="connecte">
    <aside id="sidebar-connecte" class="fixed inset-y-0 left-0 z-50 hidden w-72 flex-col bg-op-sidebar px-4 py-6 text-op-sidebar-text shadow-xl lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-72 lg:flex-none lg:shadow-none" aria-label="Navigation latérale">
        <div class="flex items-center justify-between px-2">
            <a href="{{ $accueilRole }}" class="flex items-center gap-3 font-bold text-white">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-[#2563EB] text-sm font-extrabold text-white" aria-hidden="true">OP</span>
                <span class="text-lg text-white">OceaPress</span>
            </a>
            <button id="fermer-menu-connecte" type="button" class="rounded-lg p-2 text-white lg:hidden" aria-label="Fermer le menu">✕</button>
        </div>

        <nav class="mt-8 flex-1 space-y-6 overflow-y-auto" aria-label="Navigation {{ $estEmploye ? 'employé' : ($estClient ? 'client' : ($estAdministrateur ? 'administrateur' : 'connectée')) }}">
            @foreach ($groupes as $groupe)
                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-op-sidebar-text">{{ $groupe['titre'] }}</p>
                    <ul class="mt-2 space-y-1">
                        @foreach ($groupe['liens'] as $lien)
                            @php($actif = request()->routeIs($lien['route']))
                            <li>
                                <a href="{{ route($lien['route']) }}" @if ($actif) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold {{ $actif ? 'bg-[#2563EB] !text-white' : 'text-op-sidebar-text hover:bg-op-sidebar-line hover:text-white' }}"><x-icon :name="$lien['icone']" /><span>{{ $lien['label'] }}</span></a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="mt-auto flex w-full flex-col items-center text-center border-t border-op-sidebar-line pt-5">
            @if ($nomUtilisateur !== '')
                <p class="font-semibold text-white">{{ $nomUtilisateur }}</p>
            @endif
            <p class="mt-1 text-sm text-op-sidebar-text">{{ $roleLibelle }}</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl border border-op-sidebar-line px-4 py-2 text-sm font-semibold text-white hover:bg-op-sidebar-line"><x-icon name="logout" class="h-4 w-4 shrink-0" /><span>Se déconnecter</span></button>
            </form>
        </div>
    </aside>

    <button id="fond-menu-connecte" type="button" class="fixed inset-0 z-40 hidden bg-black/40 lg:hidden" aria-label="Fermer le menu"></button>

    <div class="min-w-0 flex-1 bg-op-page lg:mt-3 lg:overflow-y-auto lg:rounded-tl-3xl" data-panneau>
        <header class="sticky top-0 z-30 flex min-h-16 items-center gap-3 bg-white px-4 py-3 shadow-sm md:px-6">
            <button id="ouvrir-menu-connecte" type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-op-muted hover:bg-op-page hover:text-op-ink lg:hidden" aria-label="Ouvrir le menu" aria-controls="sidebar-connecte" aria-expanded="false"><x-icon name="menu" class="h-6 w-6 shrink-0" /><span class="sr-only">Menu</span></button>

            @if ($groupes !== [])
                <form id="recherche-rapide" role="search" class="relative min-w-0 flex-1 md:max-w-md" autocomplete="off">
                    <label for="recherche-rapide-champ" class="sr-only">Aller à une page</label>
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-op-muted"><x-icon name="search" class="h-4 w-4 shrink-0" /></span>
                    <input id="recherche-rapide-champ" type="search" list="recherche-rapide-pages" placeholder="Aller à une page…" class="block w-full rounded-lg border-0 bg-op-page py-2.5 pl-9 pr-3 text-sm text-op-ink placeholder:text-op-muted">
                    <datalist id="recherche-rapide-pages">
                        @foreach ($groupes as $groupe)
                            @foreach ($groupe['liens'] as $lien)
                                <option value="{{ $lien['label'] }}"></option>
                            @endforeach
                        @endforeach
                    </datalist>
                    <p id="recherche-rapide-message" class="absolute left-0 top-full mt-1 rounded-lg bg-white px-2 text-xs text-op-muted empty:hidden" role="status" aria-live="polite"></p>
                </form>
            @else
                <div class="flex-1"></div>
            @endif

            <div class="ml-auto flex items-center gap-2">
                @foreach ($raccourcis as $raccourci)
                    <a href="{{ route($raccourci['route']) }}" class="relative hidden h-10 w-10 place-items-center rounded-lg text-op-muted hover:bg-op-page hover:text-op-ink sm:grid" aria-label="{{ $raccourci['label'] }}{{ ($raccourci['compteur'] ?? 0) > 0 ? ' ('.$raccourci['compteur'].' non lues)' : '' }}" title="{{ $raccourci['label'] }}"><x-icon :name="$raccourci['icone']" class="h-6 w-6 shrink-0" />@if (($raccourci['compteur'] ?? 0) > 0)<span class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-md bg-red-500 px-1 text-[11px] font-bold leading-none text-white" data-compteur-notifications aria-hidden="true">{{ $raccourci['compteur'] }}</span>@endif</a>
                @endforeach
                <button id="plein-ecran" type="button" class="hidden h-10 w-10 place-items-center rounded-lg text-op-muted hover:bg-op-page hover:text-op-ink lg:grid" aria-label="Afficher en plein écran" aria-pressed="false" title="Plein écran"><x-icon name="fullscreen" class="h-6 w-6 shrink-0" /></button>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-op-tint-blue text-sm font-bold text-op-icon-blue" data-avatar><span aria-hidden="true">{{ $initiales }}</span><span class="sr-only">{{ $roleLibelle }} connecté</span></span>
            </div>
        </header>

        <main class="min-h-[calc(100vh-4rem)] bg-op-page p-4 md:p-8">
            <div class="mx-auto mb-6 flex max-w-7xl flex-wrap items-center justify-between gap-x-6 gap-y-1" data-entete-page>
                @if ($titreDeclare)
                    <h1 class="text-xl font-semibold text-op-ink">{{ $titrePage }}</h1>
                @endif
                <nav aria-label="Fil d’Ariane" class="text-sm text-op-muted">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a href="{{ $accueilRole }}" class="hover:text-op-accent hover:underline">Accueil</a></li>
                        <li aria-hidden="true">›</li>
                        <li class="text-op-ink">{{ $titrePage }}</li>
                    </ol>
                </nav>
            </div>
            @yield('content')
        </main>
    </div>
</div>
@endauth

@guest
<div data-layout="connecte" class="min-h-screen bg-op-page">@yield('content')</div>
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
        if (open) {
            const firstLink = sidebar.querySelector('nav a[href]');
            if (firstLink) {
                firstLink.focus();
            } else {
                closeButton.focus();
            }
        } else {
            openButton.focus();
        }
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
(() => {
    const form = document.getElementById('recherche-rapide');
    const champ = document.getElementById('recherche-rapide-champ');
    const message = document.getElementById('recherche-rapide-message');
    if (!form || !champ || !message) return;

    const pages = [...document.querySelectorAll('#sidebar-connecte nav a[href]')]
        .map((lien) => ({ libelle: lien.textContent.trim().toLowerCase(), href: lien.href }));

    champ.addEventListener('input', () => { message.textContent = ''; });
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const saisie = champ.value.trim().toLowerCase();
        if (saisie === '') return;
        const cible = pages.find((page) => page.libelle === saisie) || pages.find((page) => page.libelle.includes(saisie));
        if (cible) {
            window.location.href = cible.href;
        } else {
            message.textContent = 'Aucune page ne correspond à « ' + champ.value.trim() + ' ».';
        }
    });
})();
(() => {
    const bouton = document.getElementById('plein-ecran');
    if (!bouton || !document.documentElement.requestFullscreen) return;

    bouton.addEventListener('click', () => {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            document.documentElement.requestFullscreen().catch(() => {});
        }
    });
    document.addEventListener('fullscreenchange', () => {
        const actif = document.fullscreenElement !== null;
        bouton.setAttribute('aria-pressed', actif ? 'true' : 'false');
        bouton.setAttribute('aria-label', actif ? 'Quitter le plein écran' : 'Afficher en plein écran');
    });
})();
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordTarget);
        if (!input) return;
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        button.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
        button.setAttribute('aria-pressed', visible ? 'false' : 'true');
    });
});
</script>
</body>
</html>

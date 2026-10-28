@extends('layouts.connecte')

@section('title', 'Tableau de bord — OceaPress')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 text-[#111827]" style="background:#FFFBEB">
    <a href="{{ route('employe.accueil') }}" class="op-button-secondary">Retour à l’accueil employé</a>

    <header class="op-banner">
        <h1 class="text-3xl font-bold">Tableau de bord du pressing</h1>
    </header>

    @php
        $indicateurs = [
            'EN_ATTENTE' => 'Commandes en attente',
            'EN_TRAITEMENT' => 'Commandes en traitement',
            'PRETE' => 'Commandes prêtes',
            'NON_RECUPEREES' => 'Commandes non récupérées',
        ];
        $nombreNonRecuperees = $commandesNonRecuperees->count();
        $maximumJour = max(1, $commandesParJour->max('total'));
    @endphp

    <section aria-label="Indicateurs du tableau de bord">
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($indicateurs as $statut => $libelle)
                @php
                    $nombre = $statut === 'NON_RECUPEREES' ? $nombreNonRecuperees : ($compteurs[$statut] ?? 0);
                    $statutBadge = $statut === 'NON_RECUPEREES' ? 'PRETE' : $statut;
                @endphp
                <li>
                    <x-card class="h-full p-5" :data-statut="$statut" :data-count="$nombre">
                        <strong class="block text-3xl font-bold">{{ $nombre }}</strong>
                        <span class="mt-2 block text-sm text-slate-600">{{ $libelle }}</span>
                        <span class="mt-3 block"><x-status-badge :status="$statutBadge" /></span>
                    </x-card>
                </li>
            @endforeach
        </ul>
    </section>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
        <x-card class="p-6" aria-labelledby="graphique-titre">
            <h2 id="graphique-titre" class="mb-5 text-xl font-semibold">Commandes enregistrées sur 7 jours</h2>
            <div class="overflow-x-auto">
                <svg viewBox="0 0 700 260" class="h-auto min-w-[620px] w-full" role="img" aria-label="Nombre de commandes enregistrées par jour sur les sept derniers jours">
                    <line x1="48" y1="220" x2="670" y2="220" stroke="#E5E7EB" stroke-width="2" />
                    @foreach ($commandesParJour as $index => $jour)
                        @php
                            $x = 62 + ($index * 88);
                            $hauteur = (int) round(((int) $jour['total'] / $maximumJour) * 170);
                            $y = 220 - $hauteur;
                        @endphp
                        <rect x="{{ $x }}" y="{{ $y }}" width="48" height="{{ $hauteur }}" rx="4" fill="#2563EB" data-chart-bar="{{ $jour['total'] }}" data-day="{{ $jour['date'] }}" data-commandes-jour="{{ $jour['total'] }}">
                            <title>{{ $jour['libelle'] }} : {{ $jour['total'] }} commande(s)</title>
                        </rect>
                        <text x="{{ $x + 24 }}" y="246" text-anchor="middle" fill="#6B7280" font-size="12">{{ $jour['libelle'] }}</text>
                        <text x="{{ $x + 24 }}" y="{{ $y - 7 }}" text-anchor="middle" fill="#111827" font-size="12" font-weight="700">{{ $jour['total'] }}</text>
                    @endforeach
                </svg>
            </div>
        </x-card>

        <aside class="space-y-6" aria-label="Actions du tableau de bord">
            <x-card class="p-5" aria-labelledby="commandes-a-relancer-titre">
                <h2 id="commandes-a-relancer-titre" class="text-lg font-semibold">Commandes prêtes à relancer</h2>
                <ul class="mt-4 space-y-3">
                    @forelse ($commandesNonRecuperees as $commande)
                        <li class="rounded-lg border border-slate-200 p-3">
                            <a href="{{ route('employe.commandes.show', $commande->idCommande) }}" class="font-semibold text-blue-600 hover:underline">
                                {{ $commande->referenceCommande }}
                            </a>
                            <p class="mt-2 text-sm text-slate-600">
                                Niveau de relance atteint :
                                <span class="font-semibold text-[#111827]">{{ $commande->niveauRelanceAtteint ?? 'Aucun' }}</span>
                            </p>
                        </li>
                    @empty
                        <li class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">Aucune commande prête à relancer.</li>
                    @endforelse
                </ul>
            </x-card>

            <x-card class="p-5 border-l-4 border-yellow-400" aria-labelledby="restitution-aujourdhui-titre">
                <h2 id="restitution-aujourdhui-titre" class="text-lg font-semibold">Commandes prêtes à restituer aujourd’hui</h2>
                <ul class="mt-4 space-y-3">
                    @forelse ($commandesRestitutionAujourdhui as $commande)
                        <li class="rounded-lg border border-slate-200 p-3">
                            <a href="{{ route('employe.commandes.show', $commande->idCommande) }}" class="font-semibold text-blue-600 hover:underline">
                                {{ $commande->referenceCommande }}
                            </a>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ $commande->client->utilisateur->prenom }} {{ $commande->client->utilisateur->nom }}
                            </p>
                        </li>
                    @empty
                        <li class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">Aucune commande à restituer aujourd’hui.</li>
                    @endforelse
                </ul>
            </x-card>
        </aside>
    </div>
</div>
@endsection

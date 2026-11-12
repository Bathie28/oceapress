@php
    $maximumJour = max(1, $commandesParJour->max('total'));
@endphp
<div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
    <x-section-card title="Commandes enregistrées sur 7 jours" id="graphique-titre">
        <div class="overflow-x-auto">
            <svg viewBox="0 0 700 260" class="h-auto min-w-[520px] w-full" role="img" aria-label="Nombre de commandes enregistrées par jour sur les sept derniers jours">
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
    </x-section-card>

    <aside class="space-y-6" aria-label="Actions du tableau de bord">
        <x-section-card title="Commandes prêtes à relancer" id="commandes-a-relancer-titre">
            <ul class="space-y-3">
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
        </x-section-card>

        <x-section-card title="Commandes prêtes à restituer aujourd’hui" id="restitution-aujourdhui-titre" class="border-l-4 border-[#2563EB]">
            <ul class="space-y-3">
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
        </x-section-card>
    </aside>
</div>

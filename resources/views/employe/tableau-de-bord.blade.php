@extends('layouts.connecte')

@section('title', 'Tableau de bord — OceaPress')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 text-[#111827]">
    <a href="{{ route('employe.accueil') }}" class="inline-flex items-center gap-2 text-[#2563EB] hover:underline">
        <span aria-hidden="true">←</span>
        <span>Retour à l’accueil employé</span>
    </a>

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

    @include('employe.partials.activite')
</div>
@endsection

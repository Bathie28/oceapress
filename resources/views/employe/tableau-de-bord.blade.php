@extends('layouts.app')

@section('title', 'Tableau de bord — OceaPress')

@section('content')
<div class="px-4 py-8 mx-auto max-w-5xl space-y-6">
    <a href="{{ route('employe.accueil') }}" class="op-button-secondary">Retour à l’accueil employé</a>
    <header class="op-banner"><h1 class="text-3xl font-bold">Tableau de bord du pressing</h1></header>
    <section class="op-card p-6 mb-6" aria-labelledby="compteurs-titre">
        <h2 id="compteurs-titre" class="mb-4 text-xl font-semibold">Commandes par statut</h2>
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($compteurs as $statut => $nombre)
                <li class="rounded-xl border border-slate-200 p-4" data-statut="{{ $statut }}" data-count="{{ $nombre }}">
                    <x-status-badge :status="$statut" :label="str_replace('_', ' ', $statut)" /> <strong class="ml-2">{{ $nombre }}</strong>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="op-card p-6 mb-6" aria-labelledby="commandes-a-relancer-titre">
        <h2 id="commandes-a-relancer-titre" class="mb-4 text-xl font-semibold">Commandes prêtes non récupérées</h2>
        @forelse ($commandesNonRecuperees as $commande)
            <article class="border-b border-slate-200 py-4 last:border-0">
                <h3 class="text-lg font-semibold">{{ $commande->referenceCommande }}</h3>
                <p>Client : {{ $commande->client->utilisateur->prenom }} {{ $commande->client->utilisateur->nom }}</p>
                <p class="mt-2">Niveau de relance atteint : <x-status-badge :status="$commande->niveauRelanceAtteint ?? 'INCONNU'" :label="$commande->niveauRelanceAtteint ?? 'Aucun'" /></p>
            </article>
        @empty
            <p class="text-slate-600">Aucune commande prête non récupérée.</p>
        @endforelse
    </section>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Espace employé — OceaPress')

@section('content')
<div class="space-y-6 py-5">
    <header class="op-banner">
        <h1 class="text-3xl font-bold">Espace employé</h1>
        <p class="mt-2 text-slate-600">{{ $utilisateur->prenom }} {{ $utilisateur->nom }}</p>
    </header>
    <nav aria-label="Accès rapides" class="grid gap-4 sm:grid-cols-2">
        <x-secondary-button :href="route('employe.commandes.index')">Commandes</x-secondary-button>
        <x-secondary-button :href="route('employe.commandes.create')">Nouvelle commande</x-secondary-button>
        <x-secondary-button :href="route('employe.scan')">Scanner un QR code</x-secondary-button>
        <x-secondary-button :href="route('employe.tableau-de-bord')">Tableau de bord</x-secondary-button>
    </nav>
</div>
@endsection

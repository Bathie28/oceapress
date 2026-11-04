@extends('layouts.connecte')

@section('title', 'Espace employé — OceaPress')
@php use App\Models\Commande; @endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <header>
        <h1 class="text-3xl font-bold text-[#0F172A]">Bonjour {{ $utilisateur->prenom }}</h1>
        <p class="mt-2 text-[#64748B]">État actuel des commandes de votre pressing.</p>
    </header>

    <section aria-label="Indicateurs du pressing">
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <li><x-card class="h-full"><strong class="block text-3xl font-bold">{{ $compteurs[Commande::STATUT_EN_ATTENTE] }}</strong><span class="mt-2 block text-sm text-[#64748B]">En attente</span><span class="mt-3 block"><x-status-badge status="EN_ATTENTE" /></span></x-card></li>
            <li><x-card class="h-full"><strong class="block text-3xl font-bold">{{ $compteurs[Commande::STATUT_EN_TRAITEMENT] }}</strong><span class="mt-2 block text-sm text-[#64748B]">En traitement</span><span class="mt-3 block"><x-status-badge status="EN_TRAITEMENT" /></span></x-card></li>
            <li><x-card class="h-full"><strong class="block text-3xl font-bold">{{ $compteurs[Commande::STATUT_PRETE] }}</strong><span class="mt-2 block text-sm text-[#64748B]">Prêtes</span><span class="mt-3 block"><x-status-badge status="PRETE" /></span></x-card></li>
            <li><x-card class="h-full"><strong class="block text-3xl font-bold">{{ $compteurs[Commande::STATUT_RECUPEREE] }}</strong><span class="mt-2 block text-sm text-[#64748B]">Non récupérées</span><span class="mt-3 block"><x-status-badge status="PRETE" /></span></x-card></li>
        </ul>
    </section>

    <x-card aria-labelledby="acces-rapides-titre">
        <h2 id="acces-rapides-titre" class="text-xl font-semibold">Accès rapides</h2>
        <nav aria-label="Accès rapides" class="mt-4 grid gap-3 sm:grid-cols-3">
            <x-secondary-button :href="route('employe.commandes.create')" class="inline-flex items-center justify-center rounded-xl border border-[#CBD5E1] bg-white px-4 py-3 font-semibold text-[#0F172A] hover:bg-[#DBEAFE]">Nouvelle commande</x-secondary-button>
            <x-secondary-button :href="route('employe.scan')" class="inline-flex items-center justify-center rounded-xl border border-[#CBD5E1] bg-white px-4 py-3 font-semibold text-[#0F172A] hover:bg-[#DBEAFE]">Scanner un QR code</x-secondary-button>
            <x-secondary-button :href="route('employe.tableau-de-bord')" class="inline-flex items-center justify-center rounded-xl border border-[#CBD5E1] bg-white px-4 py-3 font-semibold text-[#0F172A] hover:bg-[#DBEAFE]">Tableau de bord</x-secondary-button>
        </nav>
    </x-card>
</div>
@endsection

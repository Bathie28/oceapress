@extends('layouts.connecte')

@section('title', 'Espace employé — OceaPress')
@section('pageTitle', 'Espace employé')
@php
    use App\Models\Commande;

    $enCours = $compteurs[Commande::STATUT_EN_ATTENTE] + $compteurs[Commande::STATUT_EN_TRAITEMENT] + $compteurs[Commande::STATUT_PRETE];
    $part = fn (int $nombre): int => $enCours > 0 ? (int) round($nombre / $enCours * 100) : 0;
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <p class="text-op-muted">Bonjour {{ $utilisateur->prenom }}. Voici l’état actuel des commandes de votre pressing.</p>

    <section aria-label="Indicateurs du pressing">
        <ul class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
            <li><x-stat-card name="en-attente" title="En attente" :value="$compteurs[Commande::STATUT_EN_ATTENTE]" icon="clock" tint="orange" subtitle="À prendre en charge" :progress="$part((int) $compteurs[Commande::STATUT_EN_ATTENTE])" :caption="$part((int) $compteurs[Commande::STATUT_EN_ATTENTE]).' % des commandes en cours'" /></li>
            <li><x-stat-card name="en-traitement" title="En traitement" :value="$compteurs[Commande::STATUT_EN_TRAITEMENT]" icon="refresh" tint="blue" subtitle="En cours de nettoyage" :progress="$part((int) $compteurs[Commande::STATUT_EN_TRAITEMENT])" :caption="$part((int) $compteurs[Commande::STATUT_EN_TRAITEMENT]).' % des commandes en cours'" /></li>
            <li><x-stat-card name="pretes" title="Prêtes" :value="$compteurs[Commande::STATUT_PRETE]" icon="check" tint="green" subtitle="À restituer au client" :progress="$part((int) $compteurs[Commande::STATUT_PRETE])" :caption="$part((int) $compteurs[Commande::STATUT_PRETE]).' % des commandes en cours'" /></li>
            <li><x-stat-card name="non-recuperees" title="Non récupérées" :value="$compteurs[Commande::STATUT_RECUPEREE]" icon="bag" tint="cyan" subtitle="Prêtes, pas encore retirées" :progress="$part((int) $compteurs[Commande::STATUT_RECUPEREE])" :caption="$part((int) $compteurs[Commande::STATUT_RECUPEREE]).' % des commandes en cours'" /></li>
        </ul>
    </section>

    @include('employe.partials.activite')

    <x-section-card title="Accès rapides" id="acces-rapides-titre">
        <nav aria-label="Accès rapides" class="grid gap-3 sm:grid-cols-3">
            <x-secondary-button :href="route('employe.commandes.create')" class="inline-flex items-center justify-center rounded-xl border border-[#CBD5E1] bg-white px-4 py-3 font-semibold text-[#0F172A] hover:bg-[#DBEAFE]">Nouvelle commande</x-secondary-button>
            <x-secondary-button :href="route('employe.scan')" class="inline-flex items-center justify-center rounded-xl border border-[#CBD5E1] bg-white px-4 py-3 font-semibold text-[#0F172A] hover:bg-[#DBEAFE]">Scanner un QR code</x-secondary-button>
            <x-secondary-button :href="route('employe.tableau-de-bord')" class="inline-flex items-center justify-center rounded-xl border border-[#CBD5E1] bg-white px-4 py-3 font-semibold text-[#0F172A] hover:bg-[#DBEAFE]">Tableau de bord</x-secondary-button>
        </nav>
    </x-section-card>
</div>
@endsection

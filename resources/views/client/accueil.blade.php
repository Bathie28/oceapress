@extends('layouts.connecte')

@section('title', 'Espace client — OceaPress')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <header>
        <h1 class="text-3xl font-bold text-[#0F172A]">Bonjour {{ $utilisateur->prenom }}</h1>
        <p class="mt-2 text-[#64748B]">Voici l’état actuel de vos commandes.</p>
    </header>

    <section aria-label="Synthèse du compte">
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <li>
                <x-card class="h-full">
                    <strong class="block text-4xl font-bold text-[#2563EB]" data-commandes-en-cours>{{ $nombreCommandesEnCours }}</strong>
                    <span class="mt-2 block text-sm font-semibold text-[#64748B]">Commandes en cours</span>
                </x-card>
            </li>
            <li>
                <x-card class="h-full">
                    <strong class="block text-2xl font-bold">{{ $commandesPretes->count() }}</strong>
                    <span class="mt-2 block text-sm font-semibold text-[#64748B]">Commandes prêtes à récupérer</span>
                </x-card>
            </li>
            <li>
                <x-card class="h-full">
                    <strong class="block text-2xl font-bold">{{ $notificationsNonLues->count() }}</strong>
                    <span class="mt-2 block text-sm font-semibold text-[#64748B]">Notifications non lues</span>
                </x-card>
            </li>
        </ul>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card aria-labelledby="commandes-pretes-titre">
            <h2 id="commandes-pretes-titre" class="text-xl font-semibold">Commandes prêtes à récupérer</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($commandesPretes as $commande)
                    <li class="flex items-center justify-between gap-4 rounded-xl border border-[#E2E8F0] p-4">
                        <div>
                            <a href="{{ route('client.commandes.show', $commande->idCommande) }}" class="font-semibold text-[#2563EB] hover:underline">{{ $commande->referenceCommande }}</a>
                            <div class="mt-2"><x-status-badge status="PRETE" /></div>
                        </div>
                    </li>
                @empty
                    <li class="rounded-xl bg-[#F8FAFC] p-4 text-[#64748B]">Aucune commande prête à récupérer.</li>
                @endforelse
            </ul>
        </x-card>

        <x-card aria-labelledby="notifications-titre">
            <div class="flex items-center justify-between gap-4">
                <h2 id="notifications-titre" class="text-xl font-semibold">Dernières notifications non lues</h2>
                <a href="{{ route('client.notifications.index') }}" class="text-sm font-semibold text-[#2563EB] hover:underline">Voir tout</a>
            </div>
            <ul class="mt-4 space-y-3">
                @forelse ($notificationsNonLues as $notification)
                    <li class="rounded-xl border border-[#E2E8F0] p-4">
                        <a href="{{ route('client.notifications.show', $notification->idNotification) }}" class="font-semibold text-[#2563EB] hover:underline">{{ $notification->titre }}</a>
                        <p class="mt-1 text-sm text-[#64748B]">{{ $notification->message }}</p>
                    </li>
                @empty
                    <li class="rounded-xl bg-[#F8FAFC] p-4 text-[#64748B]">Aucune notification non lue.</li>
                @endforelse
            </ul>
        </x-card>
    </div>

    @if ($nombreCommandesEnCours === 0 && $commandesPretes->isEmpty())
        <p class="rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-4 text-[#64748B]">Aucune commande en cours</p>
    @endif

    @if ($commandeAvecQrCode)
        <x-card>
            <h2 class="text-xl font-semibold">Accès au QR code</h2>
            <p class="mt-2 text-sm text-[#64748B]">Commande la plus récente avec QR code : {{ $commandeAvecQrCode->referenceCommande }}</p>
            <a href="{{ route('client.commandes.show', $commandeAvecQrCode->idCommande) }}" class="mt-4 inline-flex rounded-xl bg-[#2563EB] px-4 py-2 text-sm font-semibold text-white">Afficher le QR code</a>
        </x-card>
    @endif

    <x-secondary-button :href="route('client.commandes.index')">Consulter mes commandes</x-secondary-button>
</div>
@endsection

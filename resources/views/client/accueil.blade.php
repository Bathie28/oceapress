@extends('layouts.connecte')

@section('title', 'Espace client — OceaPress')
@section('pageTitle', 'Espace client')

@php
    $part = fn (int $nombre): int => $nombreCommandes > 0 ? (int) round($nombre / $nombreCommandes * 100) : 0;
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <p class="text-op-muted">Bonjour {{ $utilisateur->prenom }}. Voici l’état actuel de vos commandes.</p>

    <section aria-label="Synthèse du compte">
        <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <li><x-stat-card name="commandes-en-cours" title="Commandes en cours" :value="$nombreCommandesEnCours" icon="bag" tint="blue" subtitle="En attente ou en traitement" :progress="$part($nombreCommandesEnCours)" :caption="$part($nombreCommandesEnCours).' % de vos commandes'" /></li>
            <li><x-stat-card name="commandes-pretes" title="Prêtes à récupérer" :value="$commandesPretes->count()" icon="check" tint="green" subtitle="À retirer au pressing" :progress="$part($commandesPretes->count())" :caption="$part($commandesPretes->count()).' % de vos commandes'" /></li>
            <li><x-stat-card name="notifications-non-lues" title="Notifications non lues" :value="$nombreNotificationsNonLues" icon="bell" tint="orange" subtitle="À consulter" /></li>
        </ul>
    </section>

    @if ($nombreCommandesEnCours === 0 && $commandesPretes->isEmpty())
        <p class="op-card p-4 text-op-muted">Aucune commande en cours</p>
    @endif

    <x-section-card title="Accès au QR code" id="qr-code-titre">
        @if ($commandeAvecQrCode)
            <x-slot:action><a href="{{ route('client.commandes.show', $commandeAvecQrCode->idCommande) }}" class="op-button-primary !px-4 !py-2 !text-sm">Afficher le QR code</a></x-slot:action>
        @endif
        <div class="flex items-center gap-4">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-op-tint-cyan text-op-icon-cyan"><x-icon name="qr" class="h-6 w-6 shrink-0" /></span>
            @if ($commandeAvecQrCode)
                <p class="text-sm text-op-muted">Commande la plus récente avec QR code : <span class="font-semibold text-op-ink">{{ $commandeAvecQrCode->referenceCommande }}</span>. Présentez-le au pressing pour récupérer vos vêtements.</p>
            @else
                <p class="text-sm text-op-muted">Aucune commande avec un QR code actif pour le moment.</p>
            @endif
        </div>
    </x-section-card>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-section-card title="Commandes prêtes à récupérer" id="commandes-pretes-titre">
            <x-slot:action><a href="{{ route('client.commandes.index') }}" class="text-op-accent hover:underline">Consulter mes commandes</a></x-slot:action>
            <ul class="space-y-3">
                @forelse ($commandesPretes as $commande)
                    <li class="flex items-center justify-between gap-4 rounded-xl border border-op-line p-4">
                        <a href="{{ route('client.commandes.show', $commande->idCommande) }}" class="font-semibold text-op-accent hover:underline">{{ $commande->referenceCommande }}</a>
                        <x-status-badge status="PRETE" />
                    </li>
                @empty
                    <li class="rounded-xl bg-op-page p-4 text-op-muted">Aucune commande prête à récupérer.</li>
                @endforelse
            </ul>
        </x-section-card>

        <x-section-card title="Dernières notifications non lues" id="notifications-titre">
            <x-slot:action><a href="{{ route('client.notifications.index') }}" class="text-op-accent hover:underline">Voir tout</a></x-slot:action>
            <ul class="space-y-3">
                @forelse ($notificationsNonLues as $notification)
                    <li class="rounded-xl border border-op-line p-4">
                        <a href="{{ route('client.notifications.show', $notification->idNotification) }}" class="font-semibold text-op-accent hover:underline">{{ $notification->titre }}</a>
                        <p class="mt-1 text-sm text-op-muted">{{ $notification->message }}</p>
                    </li>
                @empty
                    <li class="rounded-xl bg-op-page p-4 text-op-muted">Aucune notification non lue.</li>
                @endforelse
            </ul>
        </x-section-card>
    </div>
</div>
@endsection

@extends('layouts.connecte')

@section('title', 'Espace administrateur — OceaPress')
@section('pageTitle', 'Espace administrateur')

@php
    $employesActifs = (int) $pressings->sum('employesActifs');
    $employesInactifs = (int) $pressings->sum('employesInactifs');
    $totalEmployes = $employesActifs + $employesInactifs;
    $part = fn (int $nombre): int => $totalEmployes > 0 ? (int) round($nombre / $totalEmployes * 100) : 0;
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <p class="text-op-muted">Bonjour {{ $utilisateur->prenom }}. Gérez les pressings et leurs équipes.</p>

    <section aria-label="Synthèse des pressings">
        <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <li><x-stat-card name="pressings-geres" title="Pressings gérés" :value="$pressings->count()" icon="building" tint="blue" subtitle="Sous votre responsabilité" /></li>
            <li><x-stat-card name="employes-actifs" title="Employés actifs" :value="$employesActifs" icon="users" tint="green" subtitle="Peuvent se connecter" :caption="$part($employesActifs).' % des employés'" /></li>
            <li><x-stat-card name="employes-inactifs" title="Employés inactifs" :value="$employesInactifs" icon="user-off" tint="orange" subtitle="Comptes désactivés" :caption="$part($employesInactifs).' % des employés'" /></li>
        </ul>
    </section>

    <x-section-card title="Pressings" id="pressings-titre">
        <x-slot:action><a href="{{ route('admin.pressings.index') }}" class="op-button-primary !px-4 !py-2 !text-sm">Gérer les pressings et tarifs</a></x-slot:action>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr><th class="border-b border-op-line p-3 text-sm font-semibold text-op-muted">Pressing</th><th class="border-b border-op-line p-3 text-sm font-semibold text-op-muted">Employés actifs</th><th class="border-b border-op-line p-3 text-sm font-semibold text-op-muted">Employés inactifs</th><th class="border-b border-op-line p-3 text-sm font-semibold text-op-muted">Accès</th></tr>
                </thead>
                <tbody>
                    @forelse ($pressings as $pressing)
                        <tr>
                            <td class="border-b border-op-line p-3 font-semibold">{{ $pressing->nom }}</td>
                            <td class="border-b border-op-line p-3">{{ $pressing->employesActifs }}</td>
                            <td class="border-b border-op-line p-3">{{ $pressing->employesInactifs }}</td>
                            <td class="border-b border-op-line p-3"><a href="{{ route('admin.pressings.tarifs', $pressing->idPressing) }}" class="font-semibold text-op-accent hover:underline">Voir les tarifs</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-6 text-center text-op-muted">Aucun pressing à afficher.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-section-card>
</div>
@endsection

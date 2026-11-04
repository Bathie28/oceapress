@extends('layouts.connecte')

@section('title', 'Espace administrateur — OceaPress')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <header>
        <h1 class="text-3xl font-bold text-[#0F172A]">Bonjour {{ $utilisateur->prenom }}</h1>
        <p class="mt-2 text-[#64748B]">Gérez les pressings et leurs équipes.</p>
    </header>

    <x-card aria-labelledby="pressings-titre">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 id="pressings-titre" class="text-xl font-semibold">Pressings</h2>
            <x-primary-button :href="route('admin.pressings.index')">Gérer les pressings et tarifs</x-primary-button>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr><th class="border-b border-[#E2E8F0] p-3 text-sm text-[#64748B]">Pressing</th><th class="border-b border-[#E2E8F0] p-3 text-sm text-[#64748B]">Employés actifs</th><th class="border-b border-[#E2E8F0] p-3 text-sm text-[#64748B]">Employés inactifs</th><th class="border-b border-[#E2E8F0] p-3 text-sm text-[#64748B]">Accès</th></tr>
                </thead>
                <tbody>
                    @forelse ($pressings as $pressing)
                        <tr>
                            <td class="border-b border-[#E2E8F0] p-3 font-semibold">{{ $pressing->nom }}</td>
                            <td class="border-b border-[#E2E8F0] p-3">{{ $pressing->employesActifs }}</td>
                            <td class="border-b border-[#E2E8F0] p-3">{{ $pressing->employesInactifs }}</td>
                            <td class="border-b border-[#E2E8F0] p-3"><a href="{{ route('admin.pressings.tarifs', $pressing->idPressing) }}" class="font-semibold text-[#2563EB] hover:underline">Voir les tarifs</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-6 text-center text-[#64748B]">Aucun pressing à afficher.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection

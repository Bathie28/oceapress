@extends('layouts.app')

@section('title', 'Commandes — OceaPress')
@section('navigation')
    <a href="{{ route('employe.commandes.index') }}" class="text-blue-700 hover:underline">Commandes</a>
    <a href="{{ route('employe.commandes.create') }}" class="text-blue-700 hover:underline">Nouvelle commande</a>
@endsection

@section('content')
    <div class="px-4 py-8 mx-auto max-w-5xl space-y-6">
        <header class="mb-8 flex items-center justify-between">
            <h1 class="text-3xl font-bold">Commandes</h1>
            <div class="flex gap-4"><a href="{{ route('employe.commandes.create') }}" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Nouvelle commande</a><a href="{{ route('employe.accueil') }}" class="self-center text-blue-700 hover:underline">Retour à l’espace employé</a></div>
        </header>

        <form method="GET" action="{{ route('employe.commandes.index') }}" class="mb-6 grid gap-4 rounded-xl bg-white border border-gray-200 p-6 shadow sm:grid-cols-2">
            <div class="mb-4">
                <x-form-label for="statut">Statut</x-form-label>
                <select id="statut" name="statut" class="block w-full rounded-xl border border-gray-300 px-3 py-2 focus:border-blue-600 focus:outline-none">
                    <option value="">Tous</option>
                    @foreach ($statuts as $statut)
                        <option value="{{ $statut }}" @selected(request('statut') === $statut)>{{ str_replace('_', ' ', $statut) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="mt-2 rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Filtrer</button>
        </form>

        <div class="mb-6 overflow-hidden rounded-xl bg-white border border-gray-200 p-6 shadow">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-sm text-slate-600"><tr><th class="px-4 py-3">Référence</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Montant</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($commandes as $commande)
                        <tr>
                            <td class="px-4 py-3"><a class="font-medium text-blue-700 hover:underline" href="{{ route('employe.commandes.show', $commande->idCommande) }}">{{ $commande->referenceCommande }}</a></td>
                            <td class="px-4 py-3">{{ $commande->client?->utilisateur?->prenom }} {{ $commande->client?->utilisateur?->nom }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$commande->statut" :label="str_replace('_', ' ', $commande->statut)" /></td>
                            <td class="px-4 py-3">{{ $commande->dateCreation }}</td>
                            <td class="px-4 py-3">{{ number_format((float) $commande->montantTotal, 0, '.', ' ') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-slate-500">Aucune commande.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>
@endsection

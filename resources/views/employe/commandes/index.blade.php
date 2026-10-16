@extends('layouts.app')

@section('title', 'Commandes — OceaPress')
@section('navigation')
    <a href="{{ route('employe.commandes.index') }}" class="text-blue-700 hover:underline">Commandes</a>
    <a href="{{ route('employe.commandes.create') }}" class="text-blue-700 hover:underline">Nouvelle commande</a>
@endsection

@section('content')
    <div class="mx-auto max-w-6xl space-y-6 py-5">
        <header class="mb-8 flex items-center justify-between">
            <h1 class="text-3xl font-bold">Commandes</h1>
            <div class="flex gap-4"><a href="{{ route('employe.commandes.create') }}" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Nouvelle commande</a><a href="{{ route('employe.accueil') }}" class="self-center text-blue-700 hover:underline">Retour à l’espace employé</a></div>
        </header>

        <form method="GET" action="{{ route('employe.commandes.index') }}" class="mb-6 flex items-end gap-3 rounded-xl bg-white p-4 shadow">
            <div>
                <label for="statut" class="mb-1 block text-sm font-medium">Statut</label>
                <select id="statut" name="statut" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Tous</option>
                    @foreach ($statuts as $statut)
                        <option value="{{ $statut }}" @selected(request('statut') === $statut)>{{ str_replace('_', ' ', $statut) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Filtrer</button>
        </form>

        <div class="overflow-hidden rounded-xl bg-white shadow">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-sm text-slate-600"><tr><th class="p-4">Référence</th><th class="p-4">Client</th><th class="p-4">Statut</th><th class="p-4">Date</th><th class="p-4">Montant</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($commandes as $commande)
                        <tr>
                            <td class="p-4"><a class="font-medium text-blue-700 hover:underline" href="{{ route('employe.commandes.show', $commande->idCommande) }}">{{ $commande->referenceCommande }}</a></td>
                            <td class="p-4">{{ $commande->client?->utilisateur?->prenom }} {{ $commande->client?->utilisateur?->nom }}</td>
                            <td class="p-4">{{ str_replace('_', ' ', $commande->statut) }}</td>
                            <td class="p-4">{{ $commande->dateCreation }}</td>
                            <td class="p-4">{{ number_format((float) $commande->montantTotal, 2, '.', ' ') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-slate-500">Aucune commande.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>
@endsection

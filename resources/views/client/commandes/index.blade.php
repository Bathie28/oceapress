@extends('layouts.app')

@section('title', 'Mes commandes — OceaPress')
@section('navigation')
    <a href="{{ route('client.commandes.index') }}" class="text-blue-700 hover:underline">Mes commandes</a>
    <a href="{{ route('client.notifications.index') }}" class="text-blue-700 hover:underline">Mes notifications</a>
@endsection

@section('content')
    <div class="mx-auto max-w-6xl space-y-6 py-5">
        <h1 class="text-3xl font-bold">Mes commandes</h1>
        <div class="overflow-hidden rounded-xl bg-white shadow">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-sm text-slate-600">
                    <tr><th class="p-4">Référence</th><th class="p-4">Statut</th><th class="p-4">Date</th><th class="p-4">Montant</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($commandes as $commande)
                        <tr>
                            <td class="p-4"><a class="font-medium text-blue-700 hover:underline" href="{{ route('client.commandes.show', $commande->idCommande) }}">{{ $commande->referenceCommande }}</a></td>
                            <td class="p-4">{{ str_replace('_', ' ', $commande->statut) }}</td>
                            <td class="p-4">{{ $commande->dateCreation }}</td>
                            <td class="p-4">{{ number_format((float) $commande->montantTotal, 0, '.', ' ') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-slate-500">Aucune commande.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

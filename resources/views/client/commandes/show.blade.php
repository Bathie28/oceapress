@extends('layouts.app')

@section('title', $commande->referenceCommande.' — OceaPress')
@section('navigation')
    <a href="{{ route('client.commandes.index') }}" class="text-blue-700 hover:underline">Mes commandes</a>
@endsection

@section('content')
    <div class="mx-auto max-w-6xl space-y-6 py-5">
        <a href="{{ route('client.commandes.index') }}" class="text-blue-700 hover:underline">← Mes commandes</a>
        <section class="rounded-xl bg-white p-6 shadow">
            <h1 class="text-2xl font-bold">{{ $commande->referenceCommande }}</h1>
            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Statut</dt><dd>{{ str_replace('_', ' ', $commande->statut) }}</dd></div>
                <div><dt class="text-sm text-slate-500">Date</dt><dd>{{ $commande->dateCreation }}</dd></div>
                <div><dt class="text-sm text-slate-500">Montant</dt><dd>{{ number_format((float) $commande->montantTotal, 2, '.', ' ') }}</dd></div>
            </dl>
        </section>

        <section class="rounded-xl bg-white p-6 shadow">
            <h2 class="mb-4 text-xl font-semibold">Facture</h2>
            @if (session('paiement_resultat'))
                <p class="mb-4 rounded-lg bg-emerald-50 p-3 text-emerald-800" role="status">{{ session('paiement_resultat') }}</p>
            @endif
            @if ($errors->has('paiement') || $errors->has('methode'))
                <p class="mb-4 rounded-lg bg-red-50 p-3 text-red-700" role="alert">{{ $errors->first('paiement') ?: $errors->first('methode') }}</p>
            @endif
            @if ($commande->facture)
                <dl class="grid gap-4 sm:grid-cols-3">
                    <div><dt class="text-sm text-slate-500">Numéro</dt><dd>{{ $commande->facture->numeroFacture }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Montant</dt><dd>{{ number_format((float) $commande->facture->montantTotal, 2, '.', ' ') }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Statut</dt><dd>{{ str_replace('_', ' ', $commande->facture->statut) }}</dd></div>
                </dl>
                @if ($commande->facture->statut === 'EN_ATTENTE')
                    <form method="POST" action="{{ route('client.factures.paiement', $commande->facture->idFacture) }}" class="mt-5 flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <label for="methode" class="mb-1 block text-sm font-medium">Méthode de paiement</label>
                            <select id="methode" name="methode" required class="rounded-lg border border-slate-300 px-3 py-2">
                                <option value="" selected disabled>Choisir une méthode</option>
                                <option value="WAVE">Wave</option>
                                <option value="ORANGE_MONEY">Orange Money</option>
                            </select>
                        </div>
                        <button class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Lancer le paiement</button>
                    </form>
                @endif
            @else
                <p>Facture non encore émise</p>
            @endif
        </section>

        <section class="rounded-xl bg-white p-6 shadow">
            <h2 class="mb-4 text-xl font-semibold">Articles</h2>
            <ul class="divide-y divide-slate-100">
                @forelse ($commande->articles as $article)
                    <li class="flex justify-between gap-4 py-3">
                        <span>{{ $article->typeArticle }}@if ($article->couleur) · {{ $article->couleur }}@endif</span>
                        <span>Quantité : {{ $article->quantite }}</span>
                    </li>
                @empty
                    <li class="py-3 text-slate-500">Aucun article.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-xl bg-white p-6 shadow">
            <h2 class="mb-4 text-xl font-semibold">Historique</h2>
            <ol class="space-y-3">
                @forelse ($commande->historiques as $historique)
                    <li class="border-l-2 border-blue-600 pl-3"><strong>{{ str_replace('_', ' ', $historique->nouveauStatut) }}</strong><span class="ml-2 text-sm text-slate-500">{{ $historique->dateChangement }}</span>@if ($historique->commentaire)<p>{{ $historique->commentaire }}</p>@endif</li>
                @empty
                    <li class="text-slate-500">Aucun historique.</li>
                @endforelse
            </ol>
        </section>
    </div>
@endsection

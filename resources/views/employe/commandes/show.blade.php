@extends('layouts.app')

@section('title', $commande->referenceCommande.' — OceaPress')
@section('navigation')
    <a href="{{ route('employe.commandes.index') }}" class="text-blue-700 hover:underline">Commandes</a>
    <a href="{{ route('employe.commandes.create') }}" class="text-blue-700 hover:underline">Nouvelle commande</a>
@endsection

@section('content')
    <div class="mx-auto max-w-6xl space-y-6 py-5">
        <a href="{{ route('employe.commandes.index') }}" class="text-blue-700 hover:underline">← Commandes</a>
        @if (session('restitution_resultat'))
            <div class="rounded-lg bg-emerald-50 p-4 text-emerald-800" role="status">{{ session('restitution_resultat') }}</div>
        @endif
        @if (session('motDePasseProvisoire'))
            <div class="rounded-lg bg-amber-50 p-4 text-amber-900" role="status">
                Mot de passe provisoire du client (à lui remettre maintenant) : <strong>{{ session('motDePasseProvisoire') }}</strong>
            </div>
        @endif
        @if ($errors->has('restitution'))
            <div class="rounded-lg bg-red-50 p-4 text-red-700" role="alert">{{ $errors->first('restitution') }}</div>
        @endif
        <section class="rounded-xl bg-white p-6 shadow">
            <h1 class="text-2xl font-bold">{{ $commande->referenceCommande }}</h1>
            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Client</dt><dd>{{ $commande->client?->utilisateur?->prenom }} {{ $commande->client?->utilisateur?->nom }}</dd></div>
                <div><dt class="text-sm text-slate-500">Statut</dt><dd>{{ str_replace('_', ' ', $commande->statut) }}</dd></div>
                <div><dt class="text-sm text-slate-500">Montant</dt><dd>{{ number_format((float) $commande->montantTotal, 0, '.', ' ') }}</dd></div>
            </dl>
        </section>

        @if ($commande->statut === 'PRETE')
            <a href="{{ route('employe.commandes.restitution.create', $commande->idCommande) }}" class="inline-flex rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white">Enregistrer une restitution</a>
        @endif

        @if ($errors->has('statut'))
            <div class="rounded-lg bg-red-50 p-4 text-red-700" role="alert">{{ $errors->first('statut') }}</div>
        @endif

        @php($statutsAccessibles = \App\Services\ChangerStatutCommande::TRANSITIONS_AUTORISEES[$commande->statut] ?? [])
        @if ($statutsAccessibles !== [])
            <section class="rounded-xl bg-white p-6 shadow">
                <h2 class="mb-4 text-xl font-semibold">Changer le statut</h2>
                <form method="POST" action="{{ route('employe.commandes.statut', $commande->idCommande) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div><label for="nouveauStatut" class="mb-1 block text-sm font-medium">Nouveau statut</label><select id="nouveauStatut" name="nouveauStatut" required class="rounded-lg border border-slate-300 px-3 py-2">
                        <option value="" selected disabled>Choisir un statut</option>
                        @foreach ($statutsAccessibles as $statut)
                            <option value="{{ $statut }}">{{ ['EN_ATTENTE' => 'En attente', 'EN_TRAITEMENT' => 'En traitement', 'PRETE' => 'Prête', 'RECUPEREE' => 'Récupérée', 'ANNULEE' => 'Annulée'][$statut] }}</option>
                        @endforeach
                    </select></div>
                    <div><label for="commentaire" class="mb-1 block text-sm font-medium">Commentaire</label><input id="commentaire" name="commentaire" class="rounded-lg border border-slate-300 px-3 py-2"></div>
                    <button class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Enregistrer le statut</button>
                </form>
            </section>
        @endif

        @if ($commande->facture)
            <section class="rounded-xl bg-white p-6 shadow">
                <h2 class="mb-4 text-xl font-semibold">Facturation</h2>
                <dl class="grid gap-4 sm:grid-cols-3">
                    <div><dt class="text-sm text-slate-500">Numéro</dt><dd>{{ $commande->facture->numeroFacture }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Montant</dt><dd>{{ number_format((float) $commande->facture->montantTotal, 0, '.', ' ') }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Statut</dt><dd>{{ str_replace('_', ' ', $commande->facture->statut) }}</dd></div>
                </dl>
            </section>
        @endif

        <section class="rounded-xl bg-white p-6 shadow">
            <h2 class="mb-4 text-xl font-semibold">Articles</h2>
            <ul class="divide-y divide-slate-100">
                @forelse ($commande->articles as $article)
                    <li class="flex flex-col justify-between gap-4 py-3 sm:flex-row"><span>{{ $article->typeArticle }} @if ($article->couleur) · {{ $article->couleur }} @endif <span>Quantité : {{ $article->quantite }}</span>
                        @foreach ($article->photos as $photo)<a href="{{ route('photos.show', $photo->idPhoto) }}"><img class="mt-2 h-28 rounded object-cover" src="{{ route('photos.show', $photo->idPhoto) }}" alt="Photo de restitution de {{ $article->typeArticle }}"></a>@endforeach
                    </span></li>
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

@extends('layouts.app')

@section('title', 'Restitution — '.$commande->referenceCommande)
@section('navigation')
    <a href="{{ route('employe.commandes.index') }}" class="text-blue-700 hover:underline">Commandes</a>
    <a href="{{ route('employe.commandes.create') }}" class="text-blue-700 hover:underline">Nouvelle commande</a>
@endsection

@section('content')
<div class="px-4 py-8 mx-auto max-w-5xl space-y-6">
    <a href="{{ route('employe.commandes.show', $commande->idCommande) }}" class="text-blue-700 hover:underline">← {{ $commande->referenceCommande }}</a>
    <section class="rounded-xl bg-white border border-gray-200 p-6 mb-6 shadow">
        <h1 class="mb-4 text-2xl font-bold">Restitution</h1>
        <p class="mb-6 text-slate-600">Indiquez la quantité présentée pour chaque type.</p>
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 p-4 text-red-700" role="alert">{{ $errors->first() }}</div>
        @endif
        <form method="POST" enctype="multipart/form-data" action="{{ route('employe.commandes.restitution.store', $commande->idCommande) }}" class="space-y-5">
            @csrf
            <fieldset class="space-y-3">
                <legend class="mb-2 font-semibold">Articles présentés</legend>
                @forelse ($commande->articles as $article)
                    <div class="mb-4 grid items-center gap-4 rounded-lg border border-slate-200 p-3 sm:grid-cols-2">
                        <x-form-label for="quantite-{{ $article->idArticle }}">{{ $article->typeArticle }} @if ($article->couleur) · {{ $article->couleur }} @endif</x-form-label>
                        <span>Quantité enregistrée : {{ $article->quantite }}</span>
                        <div class="mb-4"><x-form-label for="quantite-{{ $article->idArticle }}">Présentée</x-form-label>
                            <x-form-input id="quantite-{{ $article->idArticle }}" type="number" min="0" max="{{ $article->quantite }}" step="1"
                                name="quantitesPresentees[{{ $article->idArticle }}]"
                                value="{{ old('quantitesPresentees.'.$article->idArticle, $article->quantite) }}" required />
                        </div>
                        <div class="mb-4 sm:col-span-2">
                            <x-form-label for="photo-{{ $article->idArticle }}">Ajouter une photo (facultatif, JPEG ou PNG, 5 Mo maximum)</x-form-label>
                            <input id="photo-{{ $article->idArticle }}" name="photos[{{ $article->idArticle }}]" type="file" accept="image/jpeg,image/png" class="block w-full rounded-xl border border-gray-300 px-3 py-2 focus:border-blue-600 focus:outline-none">
                            @error('photos.'.$article->idArticle)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @empty
                    <p class="text-slate-500">Aucun article enregistré.</p>
                @endforelse
            </fieldset>
            <div class="mb-4"><x-form-label for="observation">Observation (facultative)</x-form-label><textarea id="observation" name="observation" rows="3" class="block w-full rounded-xl border border-gray-300 px-3 py-2 focus:border-blue-600 focus:outline-none">{{ old('observation') }}</textarea></div>
            <button class="mt-2 rounded-lg bg-emerald-700 px-5 py-3 font-semibold text-white hover:bg-emerald-800">Valider la restitution</button>
        </form>
    </section>
</div>
@endsection

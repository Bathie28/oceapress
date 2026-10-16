@extends('layouts.app')

@section('title', 'Restitution — '.$commande->referenceCommande)
@section('navigation')
    <a href="{{ route('employe.commandes.index') }}" class="text-blue-700 hover:underline">Commandes</a>
    <a href="{{ route('employe.commandes.create') }}" class="text-blue-700 hover:underline">Nouvelle commande</a>
@endsection

@section('content')
    <div class="mx-auto max-w-6xl space-y-6 py-5">
        <a href="{{ route('employe.commandes.show', $commande->idCommande) }}" class="text-blue-700 hover:underline">← {{ $commande->referenceCommande }}</a>
        <section class="rounded-xl bg-white p-6 shadow">
            <h1 class="mb-2 text-2xl font-bold">Restitution</h1>
            <p class="mb-6 text-slate-600">Cochez chaque article présenté.</p>
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 p-4 text-red-700" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('employe.commandes.restitution.store', $commande->idCommande) }}" class="space-y-5">
                @csrf
                <fieldset class="space-y-3">
                    <legend class="mb-2 font-semibold">Articles présentés</legend>
                    @forelse ($commande->articles as $article)
                        <div class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                            <input id="article-{{ $article->idArticle }}" type="checkbox" name="articles[]" value="{{ $article->idArticle }}" @checked(in_array($article->idArticle, old('articles', []))) class="rounded border-slate-300 text-blue-700">
                            <label for="article-{{ $article->idArticle }}">{{ $article->typeArticle }} @if ($article->couleur) � {{ $article->couleur }} @endif</label>
                        </div>
                    @empty
                        <p class="text-slate-500">Aucun article enregistré.</p>
                    @endforelse
                </fieldset>
                <div><label for="observation" class="mb-1 block font-medium">Observation (facultative)</label><textarea id="observation" name="observation" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('observation') }}</textarea></div>
                <button class="rounded-lg bg-emerald-700 px-5 py-3 font-semibold text-white hover:bg-emerald-800">Valider la restitution</button>
            </form>
        </section>
        </div>
@endsection

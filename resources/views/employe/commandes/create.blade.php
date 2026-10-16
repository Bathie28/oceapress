@extends('layouts.app')

@section('title', 'Nouvelle commande — OceaPress')
@section('navigation')
    <a href="{{ route('employe.commandes.index') }}" class="text-blue-700 hover:underline">Commandes</a>
@endsection

@section('content')
<div class="mx-auto max-w-4xl space-y-6 py-5">
        <a href="{{ route('employe.commandes.index') }}" class="text-blue-700 hover:underline">← Commandes</a>
        <h1 class="text-3xl font-bold">Nouvelle commande</h1>
        @if ($errors->any())
            <div class="rounded-lg bg-red-50 p-4 text-red-700" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ route('employe.commandes.store') }}" class="space-y-6">
            @csrf
            <section class="grid gap-4 rounded-xl bg-white p-6 shadow sm:grid-cols-2">
                <h2 class="text-xl font-semibold sm:col-span-2">Client</h2>
                <div><label for="email" class="mb-1 block text-sm font-medium">E-mail</label><input id="email" name="client[email]" type="email" value="{{ old('client.email') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                <div><label for="telephone" class="mb-1 block text-sm font-medium">Téléphone</label><input id="telephone" name="client[telephone]" value="{{ old('client.telephone') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                <div><label for="prenom" class="mb-1 block text-sm font-medium">Prénom</label><input id="prenom" name="client[prenom]" value="{{ old('client.prenom') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                <div><label for="nom" class="mb-1 block text-sm font-medium">Nom</label><input id="nom" name="client[nom]" value="{{ old('client.nom') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2"></div>
            </section>

            <section class="space-y-4 rounded-xl bg-white p-6 shadow">
                <h2 class="text-xl font-semibold">Articles</h2>
                @for ($index = 0; $index < 3; $index++)
                    <fieldset class="grid gap-3 rounded-lg border border-slate-200 p-4 sm:grid-cols-2">
                        <legend class="px-1 font-medium">Article {{ $index + 1 }}</legend>
                        <div><label for="typeArticle{{ $index }}" class="mb-1 block text-sm">Type</label><input id="typeArticle{{ $index }}" name="articles[{{ $index }}][typeArticle]" value="{{ old("articles.{$index}.typeArticle") }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                        <div><label for="couleur{{ $index }}" class="mb-1 block text-sm">Couleur</label><input id="couleur{{ $index }}" name="articles[{{ $index }}][couleur]" value="{{ old("articles.{$index}.couleur") }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                        <div><label for="description{{ $index }}" class="mb-1 block text-sm">Description</label><input id="description{{ $index }}" name="articles[{{ $index }}][description]" value="{{ old("articles.{$index}.description") }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                        <div><label for="prix{{ $index }}" class="mb-1 block text-sm">Prix unitaire (facultatif)</label><input id="prix{{ $index }}" name="articles[{{ $index }}][prixUnitaire]" type="number" min="0" step="0.01" value="{{ old("articles.{$index}.prixUnitaire") }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                    </fieldset>
                @endfor
            </section>

            <button class="rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">Créer la commande</button>
        </form>
    </div>
@endsection

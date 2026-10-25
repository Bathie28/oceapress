@extends('layouts.app')

@section('title', 'Espace administrateur — OceaPress')

@section('content')
<div class="space-y-6 py-5">
    <header class="op-banner">
        <h1 class="text-3xl font-bold">Espace administrateur</h1>
        <p class="mt-2 text-slate-600">{{ $utilisateur->prenom }} {{ $utilisateur->nom }}</p>
    </header>
    <x-secondary-button :href="route('admin.pressings.index')">Gérer les pressings et tarifs</x-secondary-button>
</div>
@endsection

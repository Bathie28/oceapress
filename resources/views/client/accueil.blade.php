@extends('layouts.app')

@section('title', 'Espace client — OceaPress')

@section('content')
<div class="space-y-6 py-5">
    <header class="op-banner">
        <h1 class="text-3xl font-bold">Espace client</h1>
        <p class="mt-2 text-slate-600">{{ $utilisateur->prenom }} {{ $utilisateur->nom }}</p>
    </header>
    <section class="grid gap-4 sm:grid-cols-2" aria-label="Services client">
        <x-secondary-button :href="route('client.commandes.index')">Consulter ses commandes</x-secondary-button>
        <x-secondary-button :href="route('client.notifications.index')">Consulter ses notifications</x-secondary-button>
    </section>
</div>
@endsection

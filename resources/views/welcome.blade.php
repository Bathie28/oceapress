@extends('layouts.app')

@section('title', 'OceaPress — Pressing')

@section('content')
<div class="mx-auto max-w-3xl space-y-6 py-10 text-center">
    <section class="op-card space-y-5 p-8 sm:p-12">
        <span class="op-monogram mx-auto" aria-hidden="true">OP</span>
        <h1 class="text-3xl font-bold">Bienvenue chez OceaPress</h1>
        <p class="text-slate-600">Accédez à votre espace pour suivre vos commandes et vos services de pressing.</p>
        <x-primary-button :href="route('login')">Se connecter</x-primary-button>
    </section>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Connexion — OceaPress')

@section('content')
<div class="op-login">
    <x-card class="op-login-card">
        <div class="mb-6 text-center">
            <span class="op-monogram mx-auto mb-4" aria-hidden="true">OP</span>
            <h1 class="text-2xl font-bold">Connexion à OceaPress</h1>
        </div>

        @if ($errors->any())
            <x-alert type="error" class="mb-4">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf
            <div class="mb-4">
                <x-form-label for="email">E-mail</x-form-label>
                <x-form-input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus />
            </div>
            <div class="mb-4">
                <x-form-label for="password">Mot de passe</x-form-label>
                <div class="relative">
                    <x-form-input id="password" name="password" type="password" required class="pr-20" />
                    <button type="button" data-password-toggle data-password-target="password" aria-label="Afficher le mot de passe" class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-sm font-semibold text-[#2563EB]">Afficher</button>
                </div>
            </div>
            <x-primary-button class="mt-2 w-full">Se connecter</x-primary-button>
        </form>
        <a class="mt-4 block text-center" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
    </x-card>
</div>
@endsection

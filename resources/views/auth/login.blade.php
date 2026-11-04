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
                    <x-form-input id="password" name="password" type="password" required class="pr-11" />
                    <button type="button" data-password-toggle data-password-target="password" aria-label="Afficher le mot de passe" aria-pressed="false" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-2 text-[#2563EB]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </button>
                </div>
            </div>
            <x-primary-button class="mt-2 w-full">Se connecter</x-primary-button>
        </form>
        <a class="mt-4 block text-center" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
    </x-card>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Mot de passe oublié — OceaPress')

@section('content')
<div class="op-login">
    <x-card class="op-login-card">
        <h1 class="mb-6 text-center text-2xl font-bold">Mot de passe oublié</h1>

        @if (session('status'))
            <x-alert type="info" class="mb-4">{{ session('status') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <div>
                <x-form-label for="email">E-mail</x-form-label>
                <x-form-input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus />
            </div>
            <x-primary-button class="mt-2 w-full">Envoyer le lien</x-primary-button>
        </form>

        <a class="mt-4 block text-center" href="{{ route('login') }}">Retour à la connexion</a>
    </x-card>
</div>
@endsection

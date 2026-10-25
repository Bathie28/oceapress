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
            <div>
                <label for="email" class="mb-1 block text-sm font-medium">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="w-full">
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Mot de passe</label>
                <input id="password" name="password" type="password" required class="w-full">
            </div>
            <x-primary-button class="w-full">Se connecter</x-primary-button>
        </form>
    </x-card>
</div>
@endsection

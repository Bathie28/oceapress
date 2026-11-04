@extends('layouts.app')

@section('title', 'Réinitialiser le mot de passe — OceaPress')

@section('content')
<div class="op-login">
    <x-card class="op-login-card">
        <h1 class="mb-6 text-center text-2xl font-bold">Réinitialiser le mot de passe</h1>

        @if ($errors->any())
            <x-alert type="error" class="mb-4">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <x-form-label for="email">E-mail</x-form-label>
                <x-form-input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus />
            </div>
            <div>
                <x-form-label for="password">Nouveau mot de passe</x-form-label>
                <div class="relative">
                    <x-form-input id="password" name="password" type="password" required class="pr-20" />
                    <button type="button" data-password-toggle data-password-target="password" aria-label="Afficher le mot de passe" aria-pressed="false" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-[#2563EB]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </button>
                </div>
            </div>
            <div>
                <x-form-label for="password_confirmation">Confirmation</x-form-label>
                <div class="relative">
                    <x-form-input id="password_confirmation" name="password_confirmation" type="password" required class="pr-20" />
                    <button type="button" data-password-toggle data-password-target="password_confirmation" aria-label="Afficher le mot de passe" aria-pressed="false" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-[#2563EB]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </button>
                </div>
            </div>
            <x-primary-button class="mt-2 w-full">Changer le mot de passe</x-primary-button>
        </form>

        <a class="mt-4 block text-center" href="{{ route('password.request') }}">Refaire une demande</a>
    </x-card>
</div>
@endsection

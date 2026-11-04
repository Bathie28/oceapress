@extends('layouts.connecte')

@section('title', 'Changer le mot de passe — OceaPress')

@section('content')
<div class="mx-auto max-w-2xl space-y-6 py-5">
    <x-card>
        <h1 class="mb-5 text-2xl font-bold">Changer le mot de passe</h1>
        @if ($errors->any())
            <div class="mb-4 space-y-2"><x-alert type="error">@foreach ($errors->all() as $error)<span class="block">{{ $error }}</span>@endforeach</x-alert></div>
        @endif
        <form method="post" action="{{ route('employe.mot-de-passe.update') }}" class="space-y-4">@csrf
            <div class="mb-4"><x-form-label for="password">Nouveau mot de passe</x-form-label><div class="relative"><x-form-input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" class="pr-20" /><button type="button" data-password-toggle data-password-target="password" aria-label="Afficher le mot de passe" aria-pressed="false" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-[#2563EB]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div></div>
            <div class="mb-4"><x-form-label for="password_confirmation">Confirmer le mot de passe</x-form-label><div class="relative"><x-form-input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="pr-20" /><button type="button" data-password-toggle data-password-target="password_confirmation" aria-label="Afficher le mot de passe" aria-pressed="false" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-[#2563EB]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div></div>
            <x-primary-button class="mt-2">Enregistrer</x-primary-button>
        </form>
    </x-card>
</div>
@endsection

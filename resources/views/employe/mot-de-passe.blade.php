@extends('layouts.app')

@section('title', 'Changer le mot de passe — OceaPress')

@section('content')
<div class="mx-auto max-w-2xl space-y-6 py-5">
    <x-card>
        <h1 class="mb-5 text-2xl font-bold">Changer le mot de passe</h1>
        @if ($errors->any())
            <div class="mb-4 space-y-2"><x-alert type="error">@foreach ($errors->all() as $error)<span class="block">{{ $error }}</span>@endforeach</x-alert></div>
        @endif
        <form method="post" action="{{ route('employe.mot-de-passe.update') }}" class="space-y-4">@csrf
            <div class="mb-4"><x-form-label for="password">Nouveau mot de passe</x-form-label><x-form-input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" /></div>
            <div class="mb-4"><x-form-label for="password_confirmation">Confirmer le mot de passe</x-form-label><x-form-input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" /></div>
            <x-primary-button class="mt-2">Enregistrer</x-primary-button>
        </form>
    </x-card>
</div>
@endsection

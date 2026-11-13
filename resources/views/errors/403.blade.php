@php
    $utilisateurConnecte = auth()->user();
    $roleLibelle = null;
    $lienEspace = route('login');

    if ($utilisateurConnecte !== null) {
        $roleLibelle = $utilisateurConnecte->client ? 'Client'
            : ($utilisateurConnecte->employe ? 'Employé'
            : ($utilisateurConnecte->administrateur ? 'Administrateur' : null));

        try {
            $lienEspace = route(app(\App\Http\Controllers\AuthController::class)->espaceDe($utilisateurConnecte));
        } catch (\RuntimeException) {
            $lienEspace = url('/');
        }
    }

    $nomConnecte = $utilisateurConnecte === null
        ? ''
        : trim(($utilisateurConnecte->prenom ?? '').' '.($utilisateurConnecte->nom ?? ''));
@endphp
@extends($utilisateurConnecte !== null ? 'layouts.connecte' : 'layouts.app')

@section('title', 'Accès refusé — OceaPress')
@section('pageTitle', 'Accès refusé')

@section('content')
<div class="{{ $utilisateurConnecte !== null ? 'mx-auto max-w-2xl' : 'op-login' }}">
    <x-card class="{{ $utilisateurConnecte !== null ? '' : 'op-login-card' }} space-y-4" data-erreur="403">
        @if ($utilisateurConnecte === null)
            <h1 class="text-2xl font-bold">Accès refusé</h1>
        @endif
        <p class="text-lg font-semibold">Vous ne pouvez pas faire cette action avec le compte actuel.</p>
        @if ($utilisateurConnecte !== null && ($nomConnecte !== '' || $roleLibelle !== null))
            <p class="text-op-muted">Compte connecté : <strong class="text-op-ink">{{ $nomConnecte }}</strong>@if ($roleLibelle) ({{ $roleLibelle }})@endif</p>
        @endif
        <p class="text-op-muted">Votre session a peut-être changé dans un autre onglet de ce navigateur.</p>
        <x-primary-button :href="$lienEspace">Retourner à mon espace</x-primary-button>
    </x-card>
</div>
@endsection

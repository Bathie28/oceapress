@extends('layouts.app')

@section('title', ($pressing ? 'Modifier' : 'Ajouter').' un pressing — OceaPress')

@section('content')
<div class="px-4 py-8 mx-auto max-w-3xl space-y-6">
    <p><a href="{{ route('admin.pressings.index') }}">Pressings</a></p>
    <h1 class="text-3xl font-bold">{{ $pressing ? 'Modifier un pressing' : 'Ajouter un pressing' }}</h1>

    @if ($errors->any())
        <x-alert type="error"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>
    @endif
    @if ($pressing && session('motDePasseProvisoire'))
        <x-alert type="warning">Mot de passe provisoire de l’employé : <strong id="mot-de-passe-provisoire">{{ session('motDePasseProvisoire') }}</strong></x-alert>
    @endif

    <section class="op-card space-y-4 p-6">
        <form method="POST" action="{{ $pressing ? route('admin.pressings.update', $pressing->idPressing) : route('admin.pressings.store') }}">
            @csrf
            @if ($pressing)
                @method('PUT')
            @endif

            <div class="mb-4">
                <x-form-label for="nom">Nom</x-form-label>
                <x-form-input id="nom" name="nom" value="{{ old('nom', $pressing?->nom) }}" maxlength="150" required />
            </div>
            <div class="mb-4">
                <x-form-label for="adresse">Adresse</x-form-label>
                <x-form-input id="adresse" name="adresse" value="{{ old('adresse', $pressing?->adresse) }}" maxlength="255" required />
            </div>
            <div class="mb-4">
                <x-form-label for="telephone">Téléphone</x-form-label>
                <x-form-input id="telephone" name="telephone" type="tel" value="{{ old('telephone', $pressing?->telephone) }}" maxlength="30" />
            </div>
            <div class="mb-4">
                <x-form-label for="email">E-mail</x-form-label>
                <x-form-input id="email" name="email" type="email" value="{{ old('email', $pressing?->email) }}" maxlength="190" />
            </div>
            <div class="mb-4">
                <x-form-label for="pesee">Pesée</x-form-label>
                <input id="pesee" name="pesee" type="checkbox" value="1" @checked(old('pesee', $pressing?->pesee ?? false))>
            </div>

            <button type="submit" class="mt-2">{{ $pressing ? 'Enregistrer les modifications' : 'Créer le pressing' }}</button>
        </form>
    </section>

    @if ($pressing)
    <section class="op-card space-y-4 p-6">
        <div class="flex items-center justify-between gap-4">
            <h2>Employés</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full divide-y">
                <thead><tr><th>Nom</th><th>Prénom</th><th>E-mail</th><th>Fonction</th></tr></thead>
                <tbody>
                @forelse ($pressing->employes as $employe)
                    <tr>
                        <td>{{ $employe->utilisateur?->nom }}</td>
                        <td>{{ $employe->utilisateur?->prenom }}</td>
                        <td>{{ $employe->utilisateur?->email }}</td>
                        <td>{{ $employe->fonction }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Aucun employé enregistré.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <details class="rounded-xl border border-gray-200 p-4">
            <summary class="cursor-pointer font-semibold">Ajouter un employé</summary>
            <form method="POST" action="{{ route('admin.pressings.employes.store', $pressing->idPressing) }}" class="mt-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><x-form-label for="employe-nom">Nom</x-form-label><x-form-input id="employe-nom" name="nom" value="{{ old('nom') }}" maxlength="100" required /></div>
                    <div><x-form-label for="employe-prenom">Prénom</x-form-label><x-form-input id="employe-prenom" name="prenom" value="{{ old('prenom') }}" maxlength="100" required /></div>
                    <div><x-form-label for="employe-email">E-mail</x-form-label><x-form-input id="employe-email" name="email" type="email" value="{{ old('email') }}" maxlength="190" required /></div>
                    <div><x-form-label for="employe-telephone">Téléphone</x-form-label><x-form-input id="employe-telephone" name="telephone" value="{{ old('telephone') }}" maxlength="30" /></div>
                    <div class="sm:col-span-2"><x-form-label for="employe-fonction">Fonction</x-form-label><x-form-input id="employe-fonction" name="fonction" value="{{ old('fonction') }}" maxlength="100" required /></div>
                </div>
                <button type="submit" class="mt-4">Créer l’employé</button>
            </form>
        </details>
    </section>
    @endif
</div>
@endsection

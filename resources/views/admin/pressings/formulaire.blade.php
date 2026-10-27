@extends('layouts.app')

@section('title', ($pressing ? 'Modifier' : 'Ajouter').' un pressing — OceaPress')

@section('content')
<div class="px-4 py-8 mx-auto max-w-3xl space-y-6">
    <p><a href="{{ route('admin.pressings.index') }}">Pressings</a></p>
    <h1 class="text-3xl font-bold">{{ $pressing ? 'Modifier un pressing' : 'Ajouter un pressing' }}</h1>

    @if ($errors->any())
        <x-alert type="error"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>
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
</div>
@endsection

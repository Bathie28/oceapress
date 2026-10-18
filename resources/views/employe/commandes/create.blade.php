@extends('layouts.app')

@section('title', 'Nouvelle commande — OceaPress')
@section('navigation')
    <a href="{{ route('employe.commandes.index') }}" class="text-blue-700 hover:underline">Commandes</a>
@endsection

@section('content')
<div class="mx-auto max-w-4xl space-y-6 py-5">
    <a href="{{ route('employe.commandes.index') }}" class="text-blue-700 hover:underline">← Commandes</a>
    <h1 class="text-3xl font-bold">Nouvelle commande</h1>
    @if ($errors->any())
        <div class="rounded-lg bg-red-50 p-4 text-red-700" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form method="POST" action="{{ route('employe.commandes.store') }}" class="space-y-6">
        @csrf
        <section class="grid gap-4 rounded-xl bg-white p-6 shadow sm:grid-cols-2">
            <h2 class="text-xl font-semibold sm:col-span-2">Client</h2>
            <div><label for="email">E-mail</label><input id="email" name="client[email]" type="email" value="{{ old('client.email') }}" required></div>
            <div><label for="telephone">Téléphone</label><input id="telephone" name="client[telephone]" value="{{ old('client.telephone') }}"></div>
            <div><label for="prenom">Prénom</label><input id="prenom" name="client[prenom]" value="{{ old('client.prenom') }}" required></div>
            <div><label for="nom">Nom</label><input id="nom" name="client[nom]" value="{{ old('client.nom') }}" required></div>
        </section>
        <section class="grid gap-4 rounded-xl bg-white p-6 shadow sm:grid-cols-2">
            <h2 class="text-xl font-semibold sm:col-span-2">Tarification</h2>
            <div>
                <label for="modeFacturation">Mode de facturation</label>
                <select id="modeFacturation" name="modeFacturation" required>
                    <option value="PIECE" @selected(old('modeFacturation', 'PIECE') === 'PIECE')>PIECE</option>
                    @if ($pesee)<option value="POIDS" @selected(old('modeFacturation') === 'POIDS')>POIDS</option>@endif
                </select>
            </div>
            @php($modeInitial = old('modeFacturation', 'PIECE'))
            <div><label for="formule">Formule</label><select id="formule" name="formule" required>
                @foreach (\App\Models\Tarif::FORMULES_PAR_MODE[$modeInitial] ?? [] as $formule)<option value="{{ $formule }}" data-mode="{{ $modeInitial }}" @selected(old('formule', $modeInitial === 'PIECE' ? \App\Models\Tarif::FORMULE_REPASSAGE : \App\Models\Tarif::FORMULE_LAVAGE) === $formule)>{{ $formule }}</option>@endforeach
            </select></div>
            <div id="poids-wrap" @if(old('modeFacturation', 'PIECE') !== 'POIDS') hidden @endif><label for="poids">Poids (kg)</label><input id="poids" name="poids" type="number" min="0.1" step="0.1" value="{{ old('poids') }}" @if(old('modeFacturation') === 'POIDS') required @endif></div>
        </section>
        <section class="space-y-4 rounded-xl bg-white p-6 shadow">
            <h2 class="text-xl font-semibold">Articles</h2>
            @for ($index = 0; $index < 3; $index++)
                <fieldset class="grid gap-3 rounded-lg border border-slate-200 p-4 sm:grid-cols-2">
                    <legend>Article {{ $index + 1 }}</legend>
                    <div><label for="typeArticle{{ $index }}">Type</label><input id="typeArticle{{ $index }}" name="articles[{{ $index }}][typeArticle]" value="{{ old("articles.{$index}.typeArticle") }}"></div>
                    <div><label for="couleur{{ $index }}">Couleur</label><input id="couleur{{ $index }}" name="articles[{{ $index }}][couleur]" value="{{ old("articles.{$index}.couleur") }}"></div>
                    <div><label for="description{{ $index }}">Description</label><input id="description{{ $index }}" name="articles[{{ $index }}][description]" value="{{ old("articles.{$index}.description") }}"></div>
                    <div class="quantite-wrap"><label for="quantite{{ $index }}">Quantité</label><input id="quantite{{ $index }}" name="articles[{{ $index }}][quantite]" type="number" min="1" step="1" value="{{ old("articles.{$index}.quantite", 1) }}"></div>
                </fieldset>
            @endfor
        </section>
        <button class="rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white">Créer la commande</button>
    </form>
</div>
<script>
(() => {
    const mode = document.getElementById('modeFacturation');
    const formule = document.getElementById('formule');
    const poidsWrap = document.getElementById('poids-wrap');
    const poids = document.getElementById('poids');
    const quantites = document.querySelectorAll('.quantite-wrap');
    const sync = () => {
        const selectedMode = mode.value;
        const formules = @json(\App\Models\Tarif::FORMULES_PAR_MODE);
        const current = formule.value;
        const allowed = formules[selectedMode];
        formule.replaceChildren(...allowed.map(value => new Option(value, value)));
        formule.value = allowed.includes(current) ? current : allowed[0];
        poidsWrap.hidden = selectedMode !== 'POIDS';
        poids.required = selectedMode === 'POIDS';
        quantites.forEach(element => element.hidden = selectedMode !== 'PIECE');
    };
    mode.addEventListener('change', sync);
    sync();
})();
</script>
@endsection

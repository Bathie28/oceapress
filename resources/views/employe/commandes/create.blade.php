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
            <div><label for="modeFacturation">Mode de facturation</label><select id="modeFacturation" name="modeFacturation" required>
                <option value="PIECE" @selected($modeInitial === 'PIECE')>PIECE</option>
                @if ($pesee)<option value="POIDS" @selected($modeInitial === 'POIDS')>POIDS</option>@endif
            </select></div>
            <div><label for="formule">Formule</label><select id="formule" name="formule" required>
                @foreach (\App\Models\Tarif::FORMULES_PAR_MODE[$modeInitial] as $formule)
                    <option value="{{ $formule }}" @selected($formuleInitiale === $formule)>{{ $formule }}</option>
                @endforeach
            </select></div>
            <div id="poids-wrap" @if($modeInitial !== 'POIDS') hidden @endif><label for="poids">Poids (kg)</label><input id="poids" name="poids" type="number" min="0.1" step="0.1" value="{{ old('poids') }}" @if($modeInitial === 'POIDS') required @endif></div>
        </section>
        <section class="space-y-4 rounded-xl bg-white p-6 shadow">
            <h2 class="text-xl font-semibold">Articles</h2>
            <div id="articles-list" data-types-url="{{ route('employe.commandes.types') }}">
                @forelse ($typesArticles as $index => $type)
                    <fieldset class="mb-4 grid gap-3 rounded-lg border border-slate-200 p-4 sm:grid-cols-2" data-type-row data-type="{{ $type }}">
                        <legend>{{ $type }}</legend>
                        <input type="hidden" name="articles[{{ $index }}][type]" value="{{ $type }}">
                        <div><label for="couleur{{ $index }}">Couleur</label><input id="couleur{{ $index }}" name="articles[{{ $index }}][couleur]" value="{{ old("articles.{$index}.couleur") }}"></div>
                        <div><label for="description{{ $index }}">Description</label><input id="description{{ $index }}" name="articles[{{ $index }}][description]" value="{{ old("articles.{$index}.description") }}"></div>
                        <div><label for="quantite{{ $index }}">Quantité</label><input id="quantite{{ $index }}" data-quantity type="number" min="0" step="1" name="articles[{{ $index }}][quantite]" value="{{ old("articles.{$index}.quantite", 0) }}"></div>
                    </fieldset>
                @empty
                    <p class="text-slate-500" data-empty>Aucun type disponible pour cette formule.</p>
                @endforelse
            </div>
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
    const list = document.getElementById('articles-list');
    const formulesParMode = @json(\App\Models\Tarif::FORMULES_PAR_MODE);
    const rowsState = new Map();
    let requestNumber = 0;

    const rememberRows = () => {
        list.querySelectorAll('[data-type-row]').forEach(row => {
            const type = row.dataset.type;
            const quantity = row.querySelector('[data-quantity]');
            const color = row.querySelector('input[name$="[couleur]"]');
            const description = row.querySelector('input[name$="[description]"]');
            rowsState.set(type, {
                quantity: quantity.value,
                color: color ? color.value : '',
                description: description ? description.value : '',
            });
        });
    };

    const renderRows = types => {
        const previousTypes = [...rowsState.keys()];
        previousTypes.forEach(type => {
            if (!types.includes(type)) rowsState.set(type, {...rowsState.get(type), quantity: '0'});
        });
        list.replaceChildren();
        let index = 0;
        types.forEach(type => {
            const state = rowsState.get(type) || {quantity: '0', color: '', description: ''};
            list.insertAdjacentHTML('beforeend', `<fieldset class="mb-4 grid gap-3 rounded-lg border border-slate-200 p-4 sm:grid-cols-2" data-type-row data-type="${escapeHtml(type)}"><legend>${escapeHtml(type)}</legend><input type="hidden" name="articles[${index}][type]" value="${escapeHtml(type)}"><div><label for="couleur${index}">Couleur</label><input id="couleur${index}" name="articles[${index}][couleur]" value="${escapeHtml(state.color)}"></div><div><label for="description${index}">Description</label><input id="description${index}" name="articles[${index}][description]" value="${escapeHtml(state.description)}"></div><div><label for="quantite${index}">Quantité</label><input id="quantite${index}" data-quantity type="number" min="0" step="1" name="articles[${index}][quantite]" value="${escapeHtml(state.quantity)}"></div></fieldset>`);
            index++;
        });
        previousTypes.filter(type => !types.includes(type)).forEach(type => {
            list.insertAdjacentHTML('beforeend', `<div hidden data-type-row data-type="${escapeHtml(type)}"><input type="hidden" name="articles[${index}][type]" value="${escapeHtml(type)}"><input type="hidden" data-quantity name="articles[${index}][quantite]" value="0"></div>`);
            index++;
        });
        if (types.length === 0) list.insertAdjacentHTML('afterbegin', '<p class="text-slate-500" data-empty>Aucun type disponible pour cette formule.</p>');
    };

    const escapeHtml = value => String(value).replace(/[&<>"']/g, character => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character]));

    const updateTypes = async () => {
        rememberRows();
        const currentRequest = ++requestNumber;
        const params = new URLSearchParams({formule: formule.value, modeFacturation: mode.value});
        const response = await fetch(`${list.dataset.typesUrl}?${params.toString()}`, {headers: {'Accept': 'application/json'}});
        if (!response.ok) return;
        const data = await response.json();
        if (currentRequest === requestNumber) renderRows(data.types);
    };

    const syncMode = async () => {
        const allowed = formulesParMode[mode.value];
        const current = formule.value;
        formule.replaceChildren(...allowed.map(value => new Option(value, value)));
        formule.value = allowed.includes(current) ? current : allowed[0];
        poidsWrap.hidden = mode.value !== 'POIDS';
        poids.required = mode.value === 'POIDS';
        await updateTypes();
    };

    formule.addEventListener('change', updateTypes);
    mode.addEventListener('change', syncMode);
})();
</script>
@endsection

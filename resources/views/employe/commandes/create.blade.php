@extends('layouts.app')

@section('title', 'Nouvelle commande — OceaPress')
@section('navigation')
    <a href="{{ route('employe.commandes.index') }}" class="text-blue-700 hover:underline">Commandes</a>
@endsection

@section('content')
<div class="px-4 py-8 mx-auto max-w-5xl space-y-6">
    <a href="{{ route('employe.commandes.index') }}" class="text-blue-700 hover:underline">← Commandes</a>
    <h1 class="text-3xl font-bold">Nouvelle commande</h1>
    @if ($errors->any())
        <div class="rounded-lg bg-red-50 p-4 text-red-700" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form method="POST" action="{{ route('employe.commandes.store') }}" class="space-y-6">
        @csrf
        <section class="grid gap-4 rounded-xl bg-white border border-gray-200 p-6 mb-6 sm:grid-cols-2">
            <h2 class="mb-4 text-xl font-semibold sm:col-span-2">Client</h2>
            <div class="mb-4 sm:col-span-2"><button id="rechercher-client" type="button" data-url="{{ route('employe.clients.rechercher') }}" class="rounded-lg border border-blue-700 px-4 py-2 text-blue-700">Rechercher le client</button><button id="modifier-client" type="button" class="ml-2 rounded-lg border border-slate-500 px-4 py-2 text-slate-700" hidden>Modifier l’e-mail et refaire la recherche</button><p id="resultat-client" class="mt-2 text-sm" role="status" aria-live="polite"></p></div>
            <div class="mb-4"><x-form-label for="email">E-mail</x-form-label><x-form-input id="email" name="client[email]" type="email" value="{{ old('client.email') }}" required /></div>
            <div class="mb-4"><x-form-label for="telephone">Téléphone</x-form-label><x-form-input id="telephone" name="client[telephone]" value="{{ old('client.telephone') }}" /></div>
            <div class="mb-4"><x-form-label for="prenom">Prénom</x-form-label><x-form-input id="prenom" name="client[prenom]" value="{{ old('client.prenom') }}" required /></div>
            <div class="mb-4"><x-form-label for="nom">Nom</x-form-label><x-form-input id="nom" name="client[nom]" value="{{ old('client.nom') }}" required /></div>
        </section>
        <section class="grid gap-4 rounded-xl bg-white border border-gray-200 p-6 mb-6 sm:grid-cols-2">
            <h2 class="mb-4 text-xl font-semibold sm:col-span-2">Tarification</h2>
            <div class="mb-4"><x-form-label for="modeFacturation">Mode de facturation</x-form-label><select id="modeFacturation" name="modeFacturation" required class="block w-full rounded-xl border border-gray-300 px-3 py-2 focus:border-blue-600 focus:outline-none">
                <option value="PIECE" @selected($modeInitial === 'PIECE')>PIECE</option>
                @if ($pesee)<option value="POIDS" @selected($modeInitial === 'POIDS')>POIDS</option>@endif
            </select></div>
            <div class="mb-4"><x-form-label for="formule">Formule</x-form-label><select id="formule" name="formule" required class="block w-full rounded-xl border border-gray-300 px-3 py-2 focus:border-blue-600 focus:outline-none">
                @foreach (\App\Models\Tarif::FORMULES_PAR_MODE[$modeInitial] as $formule)
                    <option value="{{ $formule }}" @selected($formuleInitiale === $formule)>{{ $formule }}</option>
                @endforeach
            </select></div>
            <div id="poids-wrap" class="mb-4" @if($modeInitial !== 'POIDS') hidden @endif><x-form-label for="poids">Poids (kg)</x-form-label><x-form-input id="poids" name="poids" type="number" min="0.1" step="0.1" value="{{ old('poids') }}" @if($modeInitial === 'POIDS') required @endif />@if($modeInitial === 'POIDS')<p id="poids-note" class="mt-2 text-sm text-slate-600">Le montant dépend du poids et de la formule seulement. Les quantités d’articles servent à la restitution.</p>@endif</div>
        </section>
        <section class="space-y-4 rounded-xl bg-white border border-gray-200 p-6 mb-6 shadow">
            <h2 class="mb-4 text-xl font-semibold">Articles</h2>
            <div id="articles-list" data-types-url="{{ route('employe.commandes.types') }}">
                @forelse ($typesArticles as $index => $type)
                    <fieldset class="mb-4 grid gap-3 rounded-lg border border-slate-200 p-4 sm:grid-cols-2" data-type-row data-type="{{ $type }}">
                        <legend>{{ $type }}</legend>
                        <input type="hidden" name="articles[{{ $index }}][type]" value="{{ $type }}">
                        <div class="mb-4"><x-form-label for="couleur{{ $index }}">Couleur</x-form-label><x-form-input id="couleur{{ $index }}" name="articles[{{ $index }}][couleur]" value="{{ old("articles.{$index}.couleur") }}" /></div>
                        <div class="mb-4"><x-form-label for="description{{ $index }}">Description</x-form-label><x-form-input id="description{{ $index }}" name="articles[{{ $index }}][description]" value="{{ old("articles.{$index}.description") }}" /></div>
                        <div class="mb-4"><x-form-label for="quantite{{ $index }}">Quantité</x-form-label><x-form-input id="quantite{{ $index }}" data-quantity type="number" min="0" step="1" name="articles[{{ $index }}][quantite]" value="{{ old("articles.{$index}.quantite", 0) }}" /></div>
                    </fieldset>
                @empty
                    <p class="text-slate-500" data-empty>Aucun type disponible pour cette formule.</p>
                @endforelse
            </div>
        </section>
        <button class="mt-2 rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white">Créer la commande</button>
    </form>
</div>
<script>
(() => {
    const searchClientButton = document.getElementById('rechercher-client');
    const editClientButton = document.getElementById('modifier-client');
    const searchClientResult = document.getElementById('resultat-client');
    const clientInputs = ['email', 'telephone', 'prenom', 'nom'].map(id => document.getElementById(id));
    const emailInput = document.getElementById('email');
    let clientRecherche = false;

    const clearSelectedClient = (viderChamps = false) => {
        clientRecherche = false;
        editClientButton.hidden = true;
        clientInputs.forEach(input => {
            input.readOnly = false;
            if (viderChamps) input.value = '';
        });
    };

    emailInput.addEventListener('input', () => {
        if (!clientRecherche) return;
        clearSelectedClient();
        searchClientResult.textContent = 'Le client sélectionné a été annulé. Relancez la recherche.';
    });

    editClientButton.addEventListener('click', () => {
        clearSelectedClient();
        searchClientResult.textContent = 'Le client sélectionné a été annulé. Modifiez l’e-mail puis relancez la recherche.';
        emailInput.focus();
    });

    searchClientButton.addEventListener('click', async () => {
        const params = new URLSearchParams();
        const email = emailInput.value.trim();
        const telephone = document.getElementById('telephone').value.trim();
        if (email) params.set('email', email);
        if (telephone) params.set('telephone', telephone);
        if (!email && !telephone) {
            searchClientResult.textContent = 'Saisissez un e-mail ou un téléphone.';
            return;
        }
        const response = await fetch(`${searchClientButton.dataset.url}?${params.toString()}`, {headers: {'Accept': 'application/json'}});
        if (!response.ok) {
            clearSelectedClient(true);
            searchClientResult.textContent = response.status === 404 ? 'Aucun client trouvé. Un nouveau compte sera créé avec ces coordonnées.' : 'La recherche a échoué.';
            return;
        }
        const client = await response.json();
        if (Object.hasOwn(client, 'email')) emailInput.value = client.email || '';
        if (Object.hasOwn(client, 'telephone')) document.getElementById('telephone').value = client.telephone || '';
        if (Object.hasOwn(client, 'prenom')) document.getElementById('prenom').value = client.prenom || '';
        if (Object.hasOwn(client, 'nom')) document.getElementById('nom').value = client.nom || '';
        clientInputs.forEach(input => { input.readOnly = true; });
        clientRecherche = true;
        editClientButton.hidden = false;
        searchClientResult.textContent = `Client trouvé : ${client.prenom ? client.prenom + ' ' : ''}${client.nom || ''} — ${client.telephone || 'aucun téléphone'}. La commande sera liée à ce compte.`;
    });

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
            list.insertAdjacentHTML('beforeend', `<fieldset class="mb-4 grid gap-3 rounded-lg border border-slate-200 p-4 sm:grid-cols-2" data-type-row data-type="${escapeHtml(type)}"><legend>${escapeHtml(type)}</legend><input type="hidden" name="articles[${index}][type]" value="${escapeHtml(type)}"><div class="mb-4"><label class="block mb-1 text-sm font-medium text-gray-700" for="couleur${index}">Couleur</label><input class="block w-full rounded-xl border border-gray-300 px-3 py-2 focus:border-blue-600 focus:outline-none" id="couleur${index}" name="articles[${index}][couleur]" value="${escapeHtml(state.color)}"></div><div class="mb-4"><label class="block mb-1 text-sm font-medium text-gray-700" for="description${index}">Description</label><input class="block w-full rounded-xl border border-gray-300 px-3 py-2 focus:border-blue-600 focus:outline-none" id="description${index}" name="articles[${index}][description]" value="${escapeHtml(state.description)}"></div><div class="mb-4"><label class="block mb-1 text-sm font-medium text-gray-700" for="quantite${index}">Quantité</label><input class="block w-full rounded-xl border border-gray-300 px-3 py-2 focus:border-blue-600 focus:outline-none" id="quantite${index}" data-quantity type="number" min="0" step="1" name="articles[${index}][quantite]" value="${escapeHtml(state.quantity)}"></div></fieldset>`);
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
        const poidsNote = document.getElementById('poids-note');
        if (mode.value === 'POIDS' && !poidsNote) {
            const note = document.createElement('p');
            note.id = 'poids-note';
            note.className = 'mt-2 text-sm text-slate-600';
            note.textContent = 'Le montant dépend du poids et de la formule' + ' seulement. Les quantités d’articles servent à la restitution.';
            poidsWrap.append(note);
        } else if (mode.value !== 'POIDS') {
            poidsNote?.remove();
        }
        await updateTypes();
    };

    formule.addEventListener('change', updateTypes);
    mode.addEventListener('change', syncMode);
})();
</script>
@endsection

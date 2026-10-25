@extends('layouts.app')

@section('title', 'Tarifs — '.$pressing->nom.' — OceaPress')

@section('content')
<div class="space-y-6 py-5">
    <p><a href="{{ route('admin.pressings.index') }}">Pressings</a></p>
    <h1>Tarifs de {{ $pressing->nom }}</h1>
    @if ($errors->any())<x-alert type="error"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>@endif
    <section class="op-card space-y-4 p-6">
        <h2>Pesée</h2>
        <form method="POST" action="{{ route('admin.pressings.mode', $pressing->idPressing) }}">
            @csrf
            <label for="pesee">Ce pressing effectue la pesée</label>
            <input id="pesee" name="pesee" type="checkbox" value="1" @checked($pressing->pesee)>
            <button type="submit">Enregistrer</button>
        </form>
    </section>
    <section class="op-card space-y-4 p-6"><h2>Grille tarifaire PIECE</h2>
        @php($tarifsAffiches = $pressing->tarifsAffiches())
        @php($groupesPiece = $tarifsAffiches->where('unite', 'PIECE')->groupBy('formule'))
        @forelse ($groupesPiece as $formule => $tarifs)
            <h3>{{ $formule }}</h3><table><thead><tr><th>Type d’article</th><th>Prix (F CFA)</th><th>Action</th></tr></thead><tbody>
            @foreach ($tarifs as $tarif)<tr><td>{{ $tarif->typeArticle }}</td><td>{{ number_format((float) $tarif->prix, 0, '.', ' ') }}</td><td>
                <form method="POST" action="{{ route('admin.tarifs.update', $tarif->idTarif) }}">@csrf<input type="hidden" name="idPressing" value="{{ $pressing->idPressing }}"><label for="prix-{{ $tarif->idTarif }}">Nouveau prix</label><input id="prix-{{ $tarif->idTarif }}" name="prix" type="number" min="0" step="1" value="{{ $tarif->prix }}" required><button type="submit">Modifier le prix</button></form>
            </td></tr>@endforeach
            </tbody></table>
        @empty<p>Aucun tarif pièce enregistré.</p>@endforelse
    </section>
    @if ($pressing->pesee)
    <section class="op-card space-y-4 p-6"><h2>Grille tarifaire KG</h2>
        @php($groupesKg = $tarifsAffiches->where('unite', 'KG')->groupBy('formule'))
        @forelse ($groupesKg as $formule => $tarifs)
            <h3>{{ $formule }}</h3><table><thead><tr><th>Tarif</th><th>Prix (F CFA / kg)</th><th>Action</th></tr></thead><tbody>
            @foreach ($tarifs as $tarif)<tr><td>Au kilo</td><td>{{ number_format((float) $tarif->prix, 0, '.', ' ') }}</td><td>
                <form method="POST" action="{{ route('admin.tarifs.update', $tarif->idTarif) }}">@csrf<input type="hidden" name="idPressing" value="{{ $pressing->idPressing }}"><label for="prix-{{ $tarif->idTarif }}">Nouveau prix</label><input id="prix-{{ $tarif->idTarif }}" name="prix" type="number" min="0" step="1" value="{{ $tarif->prix }}" required><button type="submit">Modifier le prix</button></form>
            </td></tr>@endforeach
            </tbody></table>
        @empty<p>Aucun tarif au kilo enregistré.</p>@endforelse
    </section>
    @endif
    <section class="op-card space-y-4 p-6">
        <h2>Ajouter un tarif</h2>
        <form method="POST" action="{{ route('admin.pressings.tarifs.store', $pressing->idPressing) }}">
            @csrf
            <label for="typeArticle">Type d’article (PIECE)</label><input id="typeArticle" name="typeArticle" value="{{ old('typeArticle') }}" maxlength="100">
            <label for="unite">Unité</label><select id="unite" name="unite" required><option value="PIECE">PIECE</option>@if($pressing->pesee)<option value="KG">KG</option>@endif</select>
            <label for="formule">Formule</label><select id="formule" name="formule" required>
                @foreach (\App\Models\Tarif::FORMULES_PAR_MODE['PIECE'] as $formule)<option value="{{ $formule }}" data-mode="PIECE">{{ $formule }}</option>@endforeach
                @if($pressing->pesee)@foreach (\App\Models\Tarif::FORMULES_PAR_MODE['POIDS'] as $formule)<option value="{{ $formule }}" data-mode="POIDS">{{ $formule }}</option>@endforeach @endif
            </select>
            <label for="prix">Prix (F CFA)</label><input id="prix" name="prix" type="number" min="0" step="1" value="{{ old('prix') }}" required>
            <button type="submit">Ajouter le tarif</button>
        </form>
    </section>
</div>
@endsection
@section('scripts')
<script>
(() => {
    const unite = document.getElementById('unite');
    const formule = document.getElementById('formule');
    const type = document.getElementById('typeArticle');
    const sync = () => {
        const mode = unite.value === 'KG' ? 'POIDS' : 'PIECE';
        [...formule.options].forEach(option => option.hidden = option.dataset.mode !== mode);
        if (formule.selectedOptions[0]?.dataset.mode !== mode) formule.value = [...formule.options].find(option => option.dataset.mode === mode).value;
        type.required = mode === 'PIECE';
        type.disabled = mode === 'POIDS';
    };
    unite.addEventListener('change', sync); sync();
})();
</script>
@endsection

<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Tarifs — {{ $pressing->nom }}</title></head>
<body>
<main>
    <p><a href="{{ route('admin.pressings.index') }}">Pressings</a></p>
    <h1>Tarifs de {{ $pressing->nom }}</h1>
    @if ($errors->any())
        <div role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <section>
        <h2>Mode de tarification</h2>
        <form method="POST" action="{{ route('admin.pressings.mode', $pressing->idPressing) }}">
            @csrf
            <label for="modeTarification">Mode</label>
            <select id="modeTarification" name="modeTarification" required>
                <option value="PIECE" @selected($pressing->modeTarification === 'PIECE')>PIECE</option>
                <option value="POIDS" @selected($pressing->modeTarification === 'POIDS')>POIDS</option>
            </select>
            <button type="submit">Modifier le mode</button>
        </form>
    </section>
    <section>
        <h2>Grille tarifaire</h2>
        @php($uniteActive = $pressing->modeTarification === 'PIECE' ? 'PIECE' : 'KG')
        @php($groupes = $pressing->tarifs->where('unite', $uniteActive)->groupBy('formule')->map(fn ($tarifs) => $tarifs->groupBy('unite')))
        @forelse ($groupes as $formule => $unites)
            <h3>{{ $formule }}</h3>
            @foreach ($unites as $unite => $tarifs)
                <h4>{{ $unite }}</h4>
                <table>
                    <thead><tr><th>Type d’article</th><th>Prix</th><th>Action</th></tr></thead>
                    <tbody>
                    @foreach ($tarifs as $tarif)
                        <tr>
                            <td>{{ $tarif->typeArticle ?? 'Au kilo' }}</td>
                            <td>{{ number_format((float) $tarif->prix, 2, '.', ' ') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.tarifs.update', $tarif->idTarif) }}">
                                    @csrf
                                    <input type="hidden" name="idPressing" value="{{ $pressing->idPressing }}">
                                    <label for="prix-{{ $tarif->idTarif }}">Nouveau prix</label>
                                    <input id="prix-{{ $tarif->idTarif }}" name="prix" type="number" min="0" step="0.01" value="{{ $tarif->prix }}" required>
                                    <button type="submit">Modifier le prix</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endforeach
        @empty
            <p>Aucun tarif enregistré.</p>
        @endforelse
    </section>
    <section>
        <h2>Ajouter un tarif</h2>
        <form method="POST" action="{{ route('admin.pressings.tarifs.store', $pressing->idPressing) }}">
            @csrf
            <label for="typeArticle">Type d’article (pour PIECE)</label>
            <input id="typeArticle" name="typeArticle" value="{{ old('typeArticle') }}" maxlength="100">
            <label for="formule">Formule</label>
            <select id="formule" name="formule" required>
                @foreach (\App\Models\Tarif::FORMULES as $formule)
                    <option value="{{ $formule }}" @selected(old('formule', \App\Models\Tarif::FORMULE_LAVAGE) === $formule)>{{ $formule }}</option>
                @endforeach
            </select>
            <label for="unite">Unité</label>
            <select id="unite" name="unite" required><option value="PIECE" @selected($uniteActive === 'PIECE')>PIECE</option><option value="KG" @selected($uniteActive === 'KG')>KG</option></select>
            <label for="prix">Prix</label>
            <input id="prix" name="prix" type="number" min="0" step="0.01" value="{{ old('prix') }}" required>
            <button type="submit">Ajouter le tarif</button>
        </form>
    </section>
</main>
</body>
</html>

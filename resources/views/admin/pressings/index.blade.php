<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Pressings — OceaPress</title></head>
<body>
<main>
    <h1>Pressings</h1>
    <ul>
        @foreach ($pressings as $pressing)
            <li>
                <a href="{{ route('admin.pressings.tarifs', $pressing->idPressing) }}">{{ $pressing->nom }}</a>
                <span>Pesée : {{ $pressing->pesee ? 'Oui' : 'Non' }}</span>
                @php($nombreTarifsAffiches = $pressing->tarifsAffiches()->count())
                <span>{{ $nombreTarifsAffiches }} {{ $nombreTarifsAffiches === 1 ? 'tarif' : 'tarifs' }}</span>
            </li>
        @endforeach
    </ul>
</main>
</body>
</html>

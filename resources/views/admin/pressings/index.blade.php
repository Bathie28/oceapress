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
                <span>Mode : {{ $pressing->modeTarification }}</span>
                <span>{{ $pressing->tarifs_count }} {{ $pressing->tarifs_count === 1 ? 'tarif' : 'tarifs' }}</span>
            </li>
        @endforeach
    </ul>
</main>
</body>
</html>

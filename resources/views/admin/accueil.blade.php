<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><title>Espace administrateur</title></head>
<body><main><h1>Espace administrateur</h1><p>{{ $utilisateur->prenom }} {{ $utilisateur->nom }}</p><a href="{{ route('admin.pressings.index') }}">Gérer les pressings et tarifs</a></main></body></html>

<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><title>Espace client</title></head>
<body><main><h1>Espace client</h1><p>{{ $utilisateur->prenom }} {{ $utilisateur->nom }}</p><nav><a href="{{ route('client.commandes.index') }}">Consulter ses commandes</a> <a href="{{ route('client.notifications.index') }}">Consulter ses notifications</a></nav></main></body></html>

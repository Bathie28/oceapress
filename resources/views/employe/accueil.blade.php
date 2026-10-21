<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Espace employé</title></head>
<body>
<main>
    <h1>Espace employé</h1>
    <p>{{ $utilisateur->prenom }} {{ $utilisateur->nom }}</p>
    <nav>
        <a href="{{ route('employe.commandes.index') }}">Commandes</a>
        <a href="{{ route('employe.commandes.create') }}">Nouvelle commande</a>
        <a href="{{ route('employe.scan') }}">Scanner un QR code</a>
    </nav>
</main>
</body>
</html>

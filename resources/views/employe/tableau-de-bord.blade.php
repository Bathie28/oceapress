<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Tableau de bord — OceaPress</title></head>
<body>
<main>
    <h1>Tableau de bord du pressing</h1>
    <a href="{{ route('employe.accueil') }}">Retour à l’accueil employé</a>

    <section aria-labelledby="compteurs-titre">
        <h2 id="compteurs-titre">Commandes par statut</h2>
        <ul>
            @foreach ($compteurs as $statut => $nombre)
                <li data-statut="{{ $statut }}" data-count="{{ $nombre }}">
                    {{ str_replace('_', ' ', $statut) }} : {{ $nombre }}
                </li>
            @endforeach
        </ul>
    </section>

    <section aria-labelledby="commandes-a-relancer-titre">
        <h2 id="commandes-a-relancer-titre">Commandes prêtes non récupérées</h2>
        @forelse ($commandesNonRecuperees as $commande)
            <article>
                <h3>{{ $commande->referenceCommande }}</h3>
                <p>Client : {{ $commande->client->utilisateur->prenom }} {{ $commande->client->utilisateur->nom }}</p>
                <p>Niveau de relance atteint : {{ $commande->niveauRelanceAtteint ?? 'Aucun' }}</p>
            </article>
        @empty
            <p>Aucune commande prête non récupérée.</p>
        @endforelse
    </section>
</main>
</body>
</html>

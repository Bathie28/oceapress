<?php

namespace Tests\Support;

use App\Models\Client;
use App\Models\Commande;
use App\Models\Employe;
use App\Models\HistoriqueCommande;
use App\Models\Pressing;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;

trait CreeDonneesTest
{
    protected function creerPressing(): Pressing
    {
        return Pressing::create([
            'nom' => 'Pressing Test', 'adresse' => '1 rue du Test',
            'dateCreation' => now()->toDateString(), 'statut' => 'ACTIF',
        ]);
    }

    protected function creerUtilisateur(array $attributs = []): Utilisateur
    {
        static $numero = 0;
        $numero++;

        return Utilisateur::create(array_merge([
            'nom' => 'Test', 'prenom' => 'Utilisateur',
            'email' => "test{$numero}@example.test", 'motDePasse' => 'secret',
        ], $attributs));
    }

    protected function creerClient(array $attributs = []): Client
    {
        $utilisateur = $this->creerUtilisateur($attributs);

        return Client::create([
            'idClient' => $utilisateur->idUtilisateur,
            'dateInscription' => now()->toDateString(),
        ]);
    }

    protected function creerEmploye(?Pressing $pressing = null): Employe
    {
        $pressing ??= $this->creerPressing();
        $utilisateur = $this->creerUtilisateur();

        DB::table('employe')->insert([
            'idEmploye' => $utilisateur->idUtilisateur,
            'fonction' => 'Agent', 'dateEmbauche' => now()->toDateString(),
            'idPressing' => $pressing->idPressing,
        ]);

        return Employe::findOrFail($utilisateur->idUtilisateur);
    }

    protected function creerCommande(?Client $client = null, ?Pressing $pressing = null, string $statut = Commande::STATUT_EN_ATTENTE): Commande
    {
        $client ??= $this->creerClient();
        $pressing ??= $this->creerPressing();
        $employe = $this->creerEmploye($pressing);

        $commande = Commande::create([
            'referenceCommande' => 'CMD-'.strtoupper(bin2hex(random_bytes(6))),
            'statut' => $statut, 'montantTotal' => 0,
            'idPressing' => $pressing->idPressing, 'idClient' => $client->idClient,
        ]);

        HistoriqueCommande::create([
            'ancienStatut' => null, 'nouveauStatut' => $statut,
            'idCommande' => $commande->idCommande, 'idEmploye' => $employe->idEmploye,
        ]);

        return $commande;
    }
}

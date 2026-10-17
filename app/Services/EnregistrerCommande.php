<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Client;
use App\Models\Commande;
use App\Models\HistoriqueCommande;
use App\Models\Facture;
use App\Models\Pressing;
use App\Models\Tarif;
use App\Models\QrCode;
use App\Models\Utilisateur;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EnregistrerCommande
{
    public function executer(array $client, array $articles, int $idEmploye, int $idPressing, string $formule = Tarif::FORMULE_LAVAGE, ?float $poids = null): Commande
    {
        return DB::transaction(function () use ($client, $articles, $idEmploye, $idPressing, $formule, $poids): Commande {
            $pressing = Pressing::whereKey($idPressing)->firstOrFail();
            $lignes = [];
            foreach ($articles as $article) {
                if (! is_array($article)
                    || ! isset($article['typeArticle'])
                    || trim((string) $article['typeArticle']) === '') {
                    throw new InvalidArgumentException('Chaque article doit avoir un typeArticle non vide.');
                }
                $lignes[] = [
                    'type' => trim((string) $article['typeArticle']),
                    'quantite' => (int) ($article['quantite'] ?? 1),
                ];
            }
            if ($lignes === []) {
                throw new InvalidArgumentException('Ajoutez au moins un article.');
            }

            $montant = app(CalculerMontant::class)->executer($pressing, $formule, $lignes, $poids);
            $utilisateur = Utilisateur::where('email', $client['email'])->first();

            if ($utilisateur === null) {
                $utilisateur = Utilisateur::create(Arr::only($client, [
                    'nom',
                    'prenom',
                    'email',
                    'telephone',
                    'motDePasse',
                ]));

                $clientCree = Client::create([
                    'idClient' => $utilisateur->idUtilisateur,
                    'dateInscription' => $client['dateInscription'] ?? now()->toDateString(),
                ]);
            } else {
                $estEmploye = DB::table('employe')
                    ->where('idEmploye', $utilisateur->idUtilisateur)
                    ->exists();
                $estAdministrateur = DB::table('administrateur')
                    ->where('idAdministrateur', $utilisateur->idUtilisateur)
                    ->exists();

                if ($estEmploye || $estAdministrateur) {
                    throw new \RuntimeException('Cet e-mail appartient à un employé ou un administrateur');
                }

                $clientCree = Client::find($utilisateur->idUtilisateur);

                if ($clientCree === null) {
                    $clientCree = Client::create([
                        'idClient' => $utilisateur->idUtilisateur,
                        'dateInscription' => $client['dateInscription'] ?? now()->toDateString(),
                    ]);
                }
            }

            $referenceCommande = $this->genererReferenceCommande();

            $commande = Commande::create([
                'referenceCommande' => $referenceCommande,
                'statut' => Commande::STATUT_EN_ATTENTE,
                'montantTotal' => $montant,
                'formule' => $formule,
                'poids' => $pressing->modeTarification === 'POIDS' ? $poids : null,
                'idPressing' => $idPressing,
                'idClient' => $clientCree->idClient,
            ]);

            foreach ($articles as $article) {
                Article::create([
                    ...Arr::only($article, [
                        'typeArticle',
                        'description',
                        'couleur',
                        'etatInitial',
                        'etatFinal',
                        'quantite',
                    ]),
                    'quantite' => (int) ($article['quantite'] ?? 1),
                    'idCommande' => $commande->idCommande,
                ]);
            }

            QrCode::create([
                'token' => $this->genererTokenQrCode(),
                'dateCreation' => now(),
                'actif' => true,
                'idCommande' => $commande->idCommande,
            ]);

            HistoriqueCommande::create([
                'ancienStatut' => null,
                'nouveauStatut' => Commande::STATUT_EN_ATTENTE,
                'dateChangement' => now(),
                'idCommande' => $commande->idCommande,
                'idEmploye' => $idEmploye,
            ]);

            Facture::create([
                'numeroFacture' => $this->genererNumeroFacture(),
                'dateEmission' => now(),
                'montantTotal' => $montant,
                'statut' => 'EN_ATTENTE',
                'idCommande' => $commande->idCommande,
            ]);

            return $commande;
        });
    }

    private function genererReferenceCommande(): string
    {
        do {
            $suffixe = '';

            for ($i = 0; $i < 6; $i++) {
                $suffixe .= chr(random_int(65, 90));
            }

            $reference = 'CMD-'.now()->format('Ymd').'-'.$suffixe;
        } while (Commande::where('referenceCommande', $reference)->exists());

        return $reference;
    }

    private function genererTokenQrCode(): string
    {
        do {
            $token = Str::random(32);
        } while (QrCode::where('token', $token)->exists());

        return $token;
    }

    private function genererNumeroFacture(): string
    {
        do {
            $suffixe = '';
            for ($i = 0; $i < 6; $i++) {
                $suffixe .= chr(random_int(65, 90));
            }
            $numero = 'FAC-'.now()->format('Ymd').'-'.$suffixe;
        } while (Facture::where('numeroFacture', $numero)->exists());

        return $numero;
    }
}

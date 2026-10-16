<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Client;
use App\Models\Commande;
use App\Models\HistoriqueCommande;
use App\Models\QrCode;
use App\Models\Utilisateur;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EnregistrerCommande
{
    public function executer(array $client, array $articles, int $idEmploye, int $idPressing): Commande
    {
        return DB::transaction(function () use ($client, $articles, $idEmploye, $idPressing): Commande {
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
                'montantTotal' => 0,
                'idPressing' => $idPressing,
                'idClient' => $clientCree->idClient,
            ]);

            foreach ($articles as $article) {
                if (! is_array($article)
                    || ! isset($article['typeArticle'])
                    || trim((string) $article['typeArticle']) === '') {
                    throw new InvalidArgumentException('Chaque article doit avoir un typeArticle non vide.');
                }

                Article::create([
                    ...Arr::only($article, [
                        'typeArticle',
                        'description',
                        'couleur',
                        'etatInitial',
                        'etatFinal',
                    ]),
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
}

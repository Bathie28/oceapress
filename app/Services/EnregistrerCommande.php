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
    public function executer(array $client, array $articles, int $idEmploye, int $idPressing, string $modeFacturation = Commande::MODE_FACTURATION_PIECE, string $formule = Tarif::FORMULE_REPASSAGE, ?float $poids = null): Commande
    {
        return DB::transaction(function () use ($client, $articles, $idEmploye, $idPressing, $modeFacturation, $formule, $poids): Commande {
            $pressing = Pressing::whereKey($idPressing)->firstOrFail();
            if (! in_array($formule, Tarif::FORMULES_PAR_MODE[$modeFacturation] ?? [], true)) {
                throw new \RuntimeException('Formule ou mode de facturation invalide.');
            }
            if ($modeFacturation === Commande::MODE_FACTURATION_POIDS && ! $pressing->pesee) {
                throw new \RuntimeException('Ce pressing ne propose pas la facturation au poids.');
            }
            $typesAutorises = $modeFacturation === Commande::MODE_FACTURATION_PIECE
                ? $pressing->typesPourFormule($formule)
                : collect(Tarif::FORMULES_PAR_MODE[Commande::MODE_FACTURATION_PIECE])
                    ->flatMap(fn (string $formulePiece) => $pressing->typesPourFormule($formulePiece))
                    ->unique()
                    ->values()
                    ->all();
            $lignesParType = [];
            foreach ($articles as $article) {
                if (! is_array($article)) {
                    throw new InvalidArgumentException('Chaque article doit avoir un type valide.');
                }
                $quantiteBrute = $article['quantite'] ?? 1;
                if (! is_numeric($quantiteBrute) || (int) $quantiteBrute != $quantiteBrute || (int) $quantiteBrute < 0) {
                    throw new InvalidArgumentException('Chaque article doit avoir un type valide et une quantité entière positive ou nulle.');
                }
                $quantite = (int) $quantiteBrute;
                if ($quantite === 0) {
                    continue;
                }
                $type = trim((string) ($article['type'] ?? $article['typeArticle'] ?? ''));
                if ($type === '' || ! in_array($type, $typesAutorises, true)) {
                    throw new \RuntimeException("Pas de tarif {$type} en {$formule}");
                }
                if (! isset($lignesParType[$type])) {
                    $lignesParType[$type] = [
                        'type' => $type,
                        'typeArticle' => $type,
                        'quantite' => 0,
                        'couleur' => $article['couleur'] ?? null,
                        'description' => $article['description'] ?? null,
                    ];
                }
                $lignesParType[$type]['quantite'] += $quantite;
            }
            $lignes = array_values($lignesParType);
            if ($lignes === []) {
                throw new \RuntimeException('Ajoutez au moins un article.');
            }

            if ($modeFacturation === Commande::MODE_FACTURATION_PIECE) {
                foreach ($lignes as &$ligne) {
                    $tarif = $pressing->tarifs()
                        ->where('typeArticle', $ligne['typeArticle'])
                        ->where('formule', $formule)
                        ->where('unite', 'PIECE')
                        ->first();
                    if ($tarif === null) {
                        throw new \RuntimeException("Pas de tarif {$ligne['typeArticle']} en {$formule}");
                    }
                    $ligne['prixUnitaire'] = (int) $tarif->prix;
                }
                unset($ligne);
            } else {
                foreach ($lignes as &$ligne) {
                    $ligne['prixUnitaire'] = null;
                }
                unset($ligne);
            }

            $montant = app(CalculerMontant::class)->executer($pressing, $modeFacturation, $formule, $lignes, $poids);
            $motDePasseProvisoire = null;
            $utilisateur = app(RechercherClient::class)->executer(
                $client['email'] ?? null,
                $client['telephone'] ?? null,
            );

            if ($utilisateur === null) {
                $compteAvecEmail = isset($client['email']) ? Utilisateur::where('email', $client['email'])->first() : null;
                if ($compteAvecEmail !== null) {
                    throw new \RuntimeException('Cet e-mail appartient à un compte qui n’est pas client.');
                }
                $compte = app(CreerCompteClient::class)->executer($client);
                $utilisateur = $compte['utilisateur'];
                $clientCree = $compte['client'];
                $motDePasseProvisoire = $compte['motDePasseProvisoire'];
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
                'modeFacturation' => $modeFacturation,
                'poids' => $modeFacturation === Commande::MODE_FACTURATION_POIDS ? $poids : null,
                'idPressing' => $idPressing,
                'idClient' => $clientCree->idClient,
            ]);

            foreach ($lignes as $article) {
                Article::create([
                    ...Arr::only($article, [
                        'typeArticle',
                        'description',
                        'couleur',
                        'quantite',
                        'prixUnitaire',
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

            Facture::create([
                'numeroFacture' => $this->genererNumeroFacture(),
                'dateEmission' => now(),
                'montantTotal' => $montant,
                'statut' => 'EN_ATTENTE',
                'idCommande' => $commande->idCommande,
            ]);

            if ($motDePasseProvisoire !== null) {
                $commande->setAttribute('motDePasseProvisoire', $motDePasseProvisoire);
            }

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

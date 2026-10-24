<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\HistoriqueCommande;
use App\Services\EnvoyerNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChangerStatutCommande
{
    public const TRANSITIONS_AUTORISEES = [
        Commande::STATUT_EN_ATTENTE => [
            Commande::STATUT_EN_TRAITEMENT,
            Commande::STATUT_ANNULEE,
        ],
        Commande::STATUT_EN_TRAITEMENT => [
            Commande::STATUT_PRETE,
            Commande::STATUT_ANNULEE,
        ],
        Commande::STATUT_PRETE => [
            Commande::STATUT_RECUPEREE,
        ],
        Commande::STATUT_RECUPEREE => [],
        Commande::STATUT_ANNULEE => [],
    ];

    public function executer(
        int $idCommande,
        string $nouveauStatut,
        int $idEmploye,
        ?string $commentaire = null,
    ): Commande {
        return DB::transaction(function () use ($idCommande, $nouveauStatut, $idEmploye, $commentaire): Commande {
            $commande = Commande::whereKey($idCommande)->lockForUpdate()->firstOrFail();
            $ancienStatut = $commande->statut;

            if (! in_array($nouveauStatut, self::TRANSITIONS_AUTORISEES[$ancienStatut] ?? [], true)) {
                throw new RuntimeException("Transition de {$ancienStatut} vers {$nouveauStatut} non autorisée");
            }

            $commande->statut = $nouveauStatut;

            if ($nouveauStatut === Commande::STATUT_RECUPEREE) {
                $commande->dateRecuperation = now();
            }

            $commande->save();

            HistoriqueCommande::create([
                'ancienStatut' => $ancienStatut,
                'nouveauStatut' => $nouveauStatut,
                'idEmploye' => $idEmploye,
                'commentaire' => $commentaire,
                'idCommande' => $commande->idCommande,
            ]);

            if ($nouveauStatut === Commande::STATUT_PRETE) {
                $commande->loadMissing('client');
                app(EnvoyerNotification::class)->executer(
                    $commande->client,
                    'Votre commande est prête',
                    'Votre commande '.$commande->referenceCommande.' est prête à être récupérée.',
                    'COMMANDE_PRETE',
                    ['IN_APP', 'EMAIL'],
                );
            }

            return $commande;
        });
    }
}

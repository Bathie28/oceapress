<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\Paiement;
use App\Models\Commande;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EnregistrerPaiement
{
    public function executer(int $idFacture, string $methode, string $statutPaiement, float $montant): Paiement
    {
        return DB::transaction(function () use ($idFacture, $methode, $statutPaiement, $montant): Paiement {
            $facture = Facture::whereKey($idFacture)->lockForUpdate()->firstOrFail();

            if (! in_array($methode, ['WAVE', 'ORANGE_MONEY'], true)) {
                throw new RuntimeException('Méthode de paiement invalide');
            }

            if (! in_array($statutPaiement, ['EN_ATTENTE', 'CONFIRME', 'ECHOUE'], true)) {
                throw new RuntimeException('Statut de paiement invalide');
            }

            $referencePaiement = $this->genererReferencePaiement();

            $paiement = Paiement::create([
                'referencePaiement' => $referencePaiement,
                'montant' => $montant,
                'datePaiement' => now(),
                'methode' => $methode,
                'statut' => $statutPaiement,
                'idFacture' => $facture->idFacture,
            ]);

            if ($statutPaiement === 'CONFIRME') {
                $facture->statut = 'PAYEE';
                $facture->save();
            }

            if (in_array($statutPaiement, ['CONFIRME', 'ECHOUE'], true)) {
                $commande = Commande::with('client')->findOrFail($facture->idCommande);
                $confirme = $statutPaiement === 'CONFIRME';
                app(EnvoyerNotification::class)->executer(
                    $commande->client,
                    $confirme ? 'Paiement confirmé' : 'Paiement échoué',
                    $confirme
                        ? 'Le paiement de votre facture a été confirmé.'
                        : 'Le paiement de votre facture a échoué. Vous pouvez réessayer.',
                    $confirme ? 'PAIEMENT_CONFIRME' : 'PAIEMENT_ECHOUE',
                    ['IN_APP'],
                );
            }

            return $paiement;
        });
    }

    private function genererReferencePaiement(): string
    {
        do {
            $reference = 'PAY-'.Str::upper(Str::random(10));
        } while (Paiement::where('referencePaiement', $reference)->exists());

        return $reference;
    }
}

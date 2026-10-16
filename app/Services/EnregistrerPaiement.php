<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\Paiement;
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

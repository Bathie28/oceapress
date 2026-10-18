<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Pressing;
use App\Models\Tarif;
use RuntimeException;

class CalculerMontant
{
    public function executer(Pressing $pressing, string $modeFacturation, string $formule, array $lignes, ?float $poids): int
    {
        if (! in_array($modeFacturation, [Commande::MODE_FACTURATION_PIECE, Commande::MODE_FACTURATION_POIDS], true)
            || ! in_array($formule, Tarif::FORMULES_PAR_MODE[$modeFacturation] ?? [], true)) {
            throw new RuntimeException('Formule ou mode de facturation invalide.');
        }

        if ($modeFacturation === Commande::MODE_FACTURATION_PIECE) {
            $total = 0;
            foreach ($lignes as $ligne) {
                $type = (string) ($ligne['type'] ?? '');
                $quantite = (int) ($ligne['quantite'] ?? 1);
                $tarif = $pressing->tarifs()->where('typeArticle', $type)->where('formule', $formule)->where('unite', 'PIECE')->first();
                if ($tarif === null) {
                    throw new RuntimeException("Pas de tarif pour {$type} en {$formule}");
                }
                $total += (int) $tarif->prix * $quantite;
            }
            return $total;
        }

        if (! $pressing->pesee) {
            throw new RuntimeException('Ce pressing ne propose pas la facturation au poids.');
        }
        if ($poids === null || $poids <= 0 || abs(($poids * 10) - round($poids * 10)) > 0.00001) {
            throw new RuntimeException('Un poids strictement positif au dixième de kilo est obligatoire.');
        }
        $tarif = $pressing->tarifs()->whereNull('typeArticle')->where('formule', $formule)->where('unite', 'KG')->first();
        if ($tarif === null) {
            throw new RuntimeException("Pas de tarif au poids en {$formule}");
        }
        $dixiemeKg = (int) round($poids * 10);
        return intdiv(((int) $tarif->prix * $dixiemeKg) + 5, 10);
    }
}

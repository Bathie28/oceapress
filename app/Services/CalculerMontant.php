<?php

namespace App\Services;

use App\Models\Pressing;
use App\Models\Tarif;
use RuntimeException;

class CalculerMontant
{
    public function executer(Pressing $pressing, string $formule, array $lignes, ?float $poids): float
    {
        if (! in_array($formule, Tarif::FORMULES, true)) {
            throw new RuntimeException('Formule de tarification invalide.');
        }

        if ($pressing->modeTarification === 'PIECE') {
            $totalCentimes = 0;

            foreach ($lignes as $ligne) {
                $type = (string) ($ligne['type'] ?? '');
                $quantite = (int) ($ligne['quantite'] ?? 1);
                $tarif = $pressing->tarifs()
                    ->where('typeArticle', $type)
                    ->where('formule', $formule)
                    ->where('unite', 'PIECE')
                    ->first();

                if ($tarif === null) {
                    throw new RuntimeException("Pas de tarif pour {$type} en {$formule}");
                }

                $totalCentimes += $this->prixEnCentimes((string) $tarif->prix) * $quantite;
            }

            return $totalCentimes / 100;
        }

        if ($pressing->modeTarification === 'POIDS') {
            if ($poids === null || $poids <= 0) {
                throw new RuntimeException('Un poids strictement positif est obligatoire pour cette tarification.');
            }

            $tarif = $pressing->tarifs()
                ->whereNull('typeArticle')
                ->where('formule', $formule)
                ->where('unite', 'KG')
                ->first();

            if ($tarif === null) {
                throw new RuntimeException("Pas de tarif au poids en {$formule}");
            }

            $milliKg = (int) round($poids * 1000, 0, PHP_ROUND_HALF_UP);
            $centimes = $this->prixEnCentimes((string) $tarif->prix);
            $totalCentimes = intdiv(($centimes * $milliKg) + 500, 1000);

            return $totalCentimes / 100;
        }

        throw new RuntimeException('Mode de tarification inconnu.');
    }

    private function prixEnCentimes(string $prix): int
    {
        [$euros, $centimes] = array_pad(explode('.', $prix, 2), 2, '00');

        return ((int) $euros * 100) + (int) str_pad(substr($centimes, 0, 2), 2, '0');
    }
}

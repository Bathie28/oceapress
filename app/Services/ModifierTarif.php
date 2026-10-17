<?php

namespace App\Services;

use App\Models\Tarif;
use RuntimeException;

class ModifierTarif
{
    public function executer(int $idTarif, float $prix): Tarif
    {
        if ($prix < 0) {
            throw new RuntimeException('Le prix ne peut pas être négatif.');
        }

        $tarif = Tarif::findOrFail($idTarif);
        $tarif->prix = $prix;
        $tarif->save();

        return $tarif;
    }
}

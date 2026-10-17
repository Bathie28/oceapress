<?php

namespace App\Services;

use App\Models\Pressing;
use App\Models\Tarif;
use RuntimeException;

class EnregistrerTarif
{
    public function executer(Pressing $pressing, ?string $typeArticle, string $formule, string $unite, float $prix): Tarif
    {
        $doublon = $pressing->tarifs()
            ->where('formule', $formule)
            ->where('unite', $unite)
            ->when($unite === 'PIECE', fn ($query) => $query->where('typeArticle', $typeArticle))
            ->exists();

        if ($doublon) {
            if ($unite === 'PIECE') {
                throw new RuntimeException('Un tarif existe déjà pour ce type d’article et cette formule');
            }

            throw new RuntimeException('Un tarif KG existe déjà pour cette formule et ce pressing.');
        }

        return $pressing->tarifs()->create([
            'typeArticle' => $typeArticle,
            'formule' => $formule,
            'unite' => $unite,
            'prix' => $prix,
        ]);
    }
}

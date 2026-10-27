<?php

namespace App\Services;

use App\Models\Pressing;
use InvalidArgumentException;

class CreerPressing
{
    public function executer(array $donnees): Pressing
    {
        foreach (['nom', 'adresse'] as $champ) {
            if (! is_string($donnees[$champ] ?? null) || trim($donnees[$champ]) === '') {
                throw new InvalidArgumentException('Le nom et l’adresse du pressing sont obligatoires.');
            }
        }

        return Pressing::create([
            'nom' => $donnees['nom'],
            'adresse' => $donnees['adresse'],
            'telephone' => $donnees['telephone'] ?? null,
            'email' => $donnees['email'] ?? null,
            'dateCreation' => now()->toDateString(),
            'statut' => Pressing::STATUT_ACTIF,
            'pesee' => (bool) ($donnees['pesee'] ?? false),
        ]);
    }
}

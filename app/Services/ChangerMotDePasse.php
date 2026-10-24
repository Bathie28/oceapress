<?php

namespace App\Services;

use App\Models\Utilisateur;

class ChangerMotDePasse
{
    public function executer(Utilisateur $utilisateur, string $motDePasse, string $confirmation): void
    {
        if (mb_strlen($motDePasse) < 8 || ! hash_equals($motDePasse, $confirmation)) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères et sa confirmation doit correspondre.');
        }

        $utilisateur->forceFill([
            'motDePasse' => $motDePasse,
            'doitChangerMotDePasse' => false,
        ])->save();
    }
}

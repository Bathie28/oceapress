<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\Pressing;
use App\Models\Utilisateur;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CreerEmploye
{
    /** @return array{utilisateur: Utilisateur, employe: Employe, motDePasseProvisoire: string} */
    public function executer(Pressing $pressing, array $donnees): array
    {
        if (Utilisateur::query()->where('email', $donnees['email'] ?? null)->exists()) {
            throw new InvalidArgumentException('Cette adresse e-mail est déjà utilisée.');
        }

        return DB::transaction(function () use ($pressing, $donnees): array {
            $motDePasseProvisoire = Str::random(48);
            $utilisateur = Utilisateur::create([
                ...Arr::only($donnees, ['nom', 'prenom', 'email', 'telephone']),
                'motDePasse' => $motDePasseProvisoire,
                'doitChangerMotDePasse' => true,
            ]);
            $employe = Employe::create([
                'idEmploye' => $utilisateur->idUtilisateur,
                'fonction' => $donnees['fonction'],
                'dateEmbauche' => $donnees['dateEmbauche'] ?? now()->toDateString(),
                'idPressing' => $pressing->idPressing,
            ]);

            return compact('utilisateur', 'employe', 'motDePasseProvisoire');
        });
    }
}

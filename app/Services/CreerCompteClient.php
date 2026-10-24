<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Utilisateur;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreerCompteClient
{
    /** @return array{utilisateur: Utilisateur, client: Client, motDePasseProvisoire: string} */
    public function executer(array $donnees): array
    {
        return DB::transaction(function () use ($donnees): array {
            $motDePasseProvisoire = Str::random(48);
            $utilisateur = Utilisateur::create([
                ...Arr::only($donnees, ['nom', 'prenom', 'email', 'telephone']),
                'motDePasse' => $motDePasseProvisoire,
                'doitChangerMotDePasse' => true,
            ]);
            $client = Client::create([
                'idClient' => $utilisateur->idUtilisateur,
                'dateInscription' => $donnees['dateInscription'] ?? now()->toDateString(),
            ]);

            return compact('utilisateur', 'client', 'motDePasseProvisoire');
        });
    }
}

<?php

namespace App\Services;

use App\Models\Utilisateur;

class RechercherClient
{
    public function executer(?string $email = null, ?string $telephone = null): ?Utilisateur
    {
        $emailNormalise = $email === null ? null : mb_strtolower(trim($email));
        $telephoneNormalise = $this->normaliserTelephone($telephone);

        if ($emailNormalise !== null && $emailNormalise !== '') {
            $client = Utilisateur::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$emailNormalise])
                ->whereHas('client')
                ->first();

            if ($client !== null) {
                return $client;
            }
        }

        if ($telephoneNormalise !== null && $telephoneNormalise !== '') {
            return Utilisateur::query()
                ->whereRaw("REGEXP_REPLACE(REGEXP_REPLACE(telephone, '[^0-9]', ''), '^221', '') = ?", [$telephoneNormalise])
                ->whereHas('client')
                ->orderBy('idUtilisateur')
                ->first();
        }

        return null;
    }

    private function normaliserTelephone(?string $telephone): ?string
    {
        if ($telephone === null) {
            return null;
        }

        $chiffres = preg_replace('/\D+/u', '', $telephone);

        if (str_starts_with($chiffres, '00221')) {
            return substr($chiffres, 5);
        }

        if (str_starts_with($chiffres, '221')) {
            return substr($chiffres, 3);
        }

        return $chiffres;
    }
}

<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Notification;
use Carbon\Carbon;

class RelancerCommandesNonRecuperees
{
    /** @var array<string, int> */
    private const NIVEAUX = [
        'J2' => 2,
        'J7' => 7,
        'J15' => 15,
        'J30' => 30,
    ];

    public function executer(): int
    {
        $nombreRelances = 0;
        $commandes = Commande::query()
            ->where('statut', Commande::STATUT_PRETE)
            ->with([
                'client',
                'historiques' => fn ($query) => $query
                    ->where('nouveauStatut', Commande::STATUT_PRETE)
                    ->orderByDesc('dateChangement')
                    ->orderByDesc('idHistorique'),
            ])
            ->get();

        foreach ($commandes as $commande) {
            $datePassagePrete = $commande->historiques->first()?->dateChangement;
            $joursDepuisPrete = $this->joursDepuis($datePassagePrete);

            if ($joursDepuisPrete === null) {
                continue;
            }

            foreach (self::NIVEAUX as $niveau => $seuilJours) {
                if ($joursDepuisPrete < $seuilJours || Notification::query()
                    ->where('idCommande', $commande->idCommande)
                    ->where('niveauRelance', $niveau)
                    ->exists()) {
                    continue;
                }

                app(EnvoyerNotification::class)->executer(
                    $commande->client,
                    'Rappel : votre commande est prête',
                    "Rappel {$niveau} : votre commande {$commande->referenceCommande} est prête à être récupérée.",
                    'RAPPEL_COMMANDE',
                    ['IN_APP', 'EMAIL'],
                    $commande->idCommande,
                    $niveau,
                );

                $nombreRelances++;
            }
        }

        return $nombreRelances;
    }

    public function niveauAtteint(?string $datePassagePrete): ?string
    {
        $joursDepuisPrete = $this->joursDepuis($datePassagePrete);

        if ($joursDepuisPrete === null) {
            return null;
        }

        foreach (array_reverse(self::NIVEAUX, true) as $niveau => $seuilJours) {
            if ($joursDepuisPrete >= $seuilJours) {
                return $niveau;
            }
        }

        return null;
    }

    private function joursDepuis(?string $datePassagePrete): ?int
    {
        if ($datePassagePrete === null) {
            return null;
        }

        $timezone = config('app.timezone');
        $dateReference = Carbon::parse($datePassagePrete, $timezone)->startOfDay();
        $aujourdHui = Carbon::today($timezone);

        if ($dateReference->greaterThan($aujourdHui)) {
            return null;
        }

        return (int) $dateReference->diffInDays($aujourdHui);
    }
}

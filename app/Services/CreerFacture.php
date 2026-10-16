<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Facture;
use App\Models\Article;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreerFacture
{
    public function executer(int $idCommande): Facture
    {
        return DB::transaction(function () use ($idCommande): Facture {
            $commande = Commande::whereKey($idCommande)->lockForUpdate()->firstOrFail();

            if ($commande->statut === Commande::STATUT_ANNULEE) {
                throw new RuntimeException('Une commande annulée ne peut pas être facturée');
            }

            if ($commande->facture()->exists()) {
                throw new RuntimeException('Une facture existe déjà pour cette commande');
            }

            $articlesSansPrix = Article::where('idCommande', $commande->idCommande)
                ->whereNull('prixUnitaire')
                ->count();

            if ($articlesSansPrix > 0) {
                throw new RuntimeException('Tous les articles doivent avoir un prix avant facturation');
            }

            return Facture::create([
                'numeroFacture' => $this->genererNumeroFacture(),
                'dateEmission' => now(),
                'montantTotal' => $commande->montantTotal,
                'statut' => 'EN_ATTENTE',
                'idCommande' => $commande->idCommande,
            ]);
        });
    }

    private function genererNumeroFacture(): string
    {
        do {
            $suffixe = '';

            for ($i = 0; $i < 6; $i++) {
                $suffixe .= chr(random_int(65, 90));
            }

            $numero = 'FAC-'.now()->format('Ymd').'-'.$suffixe;
        } while (Facture::where('numeroFacture', $numero)->exists());

        return $numero;
    }
}

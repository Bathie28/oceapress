<?php

namespace App\Services;

use App\Models\Anomalie;
use App\Models\Article;
use App\Models\Commande;
use App\Models\Restitution;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EnregistrerRestitution
{
    public function executer(
        int $idCommande,
        int $idEmploye,
        array $quantitesPresentees,
        ?string $observation = null,
    ): array {
        return DB::transaction(function () use ($idCommande, $idEmploye, $quantitesPresentees, $observation): array {
            $commande = Commande::whereKey($idCommande)->lockForUpdate()->firstOrFail();

            if ($commande->statut !== Commande::STATUT_PRETE) {
                throw new RuntimeException('La commande doit être prête pour être restituée');
            }
            if ($commande->restitution()->exists()) {
                throw new RuntimeException('Une restitution existe déjà pour cette commande');
            }

            $articles = Article::where('idCommande', $commande->idCommande)->get();
            $idsCommande = $articles->pluck('idArticle')->map(fn ($id): int => (int) $id)->all();
            $idsPresentees = array_map('intval', array_keys($quantitesPresentees));
            if (array_diff($idsPresentees, $idsCommande) !== []) {
                throw new RuntimeException('Une ligne présentée n’appartient pas à cette commande');
            }

            $manquants = [];
            foreach ($articles as $article) {
                $quantiteBrute = $quantitesPresentees[$article->idArticle] ?? $quantitesPresentees[(string) $article->idArticle] ?? 0;
                if (! is_numeric($quantiteBrute) || (int) $quantiteBrute != $quantiteBrute || (int) $quantiteBrute < 0) {
                    throw new RuntimeException('La quantité présentée doit être un entier positif ou nul');
                }
                $presentee = (int) $quantiteBrute;
                if ($presentee > (int) $article->quantite) {
                    throw new RuntimeException('La quantité présentée ne peut pas être supérieure à la quantité enregistrée');
                }
                if ($presentee < (int) $article->quantite) {
                    $manquants[] = [$article, (int) $article->quantite - $presentee];
                }
            }

            if ($manquants !== []) {
                foreach ($manquants as [$article, $manque]) {
                    Anomalie::create([
                        'type' => 'article manquant',
                        'description' => "Manque {$manque} sur {$article->quantite}",
                        'idArticle' => $article->idArticle,
                        'dateSignalement' => now(),
                    ]);
                }
                return ['restitution' => null, 'anomalies' => count($manquants)];
            }

            $restitution = Restitution::create([
                'statut' => 'CONFORME',
                'dateRestitution' => now(),
                'observation' => $observation,
                'idCommande' => $commande->idCommande,
                'idEmploye' => $idEmploye,
            ]);
            (new ChangerStatutCommande())->executer(
                $commande->idCommande,
                Commande::STATUT_RECUPEREE,
                $idEmploye,
                'Restitution conforme',
            );

            return ['restitution' => $restitution, 'anomalies' => 0];
        });
    }
}

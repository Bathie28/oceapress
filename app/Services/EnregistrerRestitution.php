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
        array $idArticlesVerifies,
        ?string $observation = null,
    ): array {
        return DB::transaction(function () use ($idCommande, $idEmploye, $idArticlesVerifies, $observation): array {
            $commande = Commande::whereKey($idCommande)->lockForUpdate()->firstOrFail();

            if ($commande->statut !== Commande::STATUT_PRETE) {
                throw new RuntimeException('La commande doit être prête pour être restituée');
            }

            if ($commande->restitution()->exists()) {
                throw new RuntimeException('Une restitution existe déjà pour cette commande');
            }

            $articles = Article::where('idCommande', $commande->idCommande)->get();
            $idsArticlesCommande = $articles->pluck('idArticle')->map(fn ($id): int => (int) $id)->all();
            $idsVerifies = array_values(array_unique(array_map(static fn ($id): int => (int) $id, $idArticlesVerifies)));

            if (array_diff($idsVerifies, $idsArticlesCommande) !== []) {
                throw new RuntimeException('Article n’appartenant pas à cette commande');
            }

            $articlesManquants = $articles->whereNotIn('idArticle', $idsVerifies);

            if ($articlesManquants->isNotEmpty()) {
                foreach ($articlesManquants as $article) {
                    Anomalie::create([
                        'type' => 'article manquant',
                        'description' => 'Article non présenté lors de la restitution',
                        'idArticle' => $article->idArticle,
                        'dateSignalement' => now(),
                    ]);
                }

                return ['restitution' => null, 'anomalies' => $articlesManquants->count()];
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

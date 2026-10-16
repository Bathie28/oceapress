<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Commande;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DefinirPrixArticle
{
    public function executer(int $idArticle, float $prix): Article
    {
        return DB::transaction(function () use ($idArticle, $prix): Article {
            $article = Article::whereKey($idArticle)->lockForUpdate()->firstOrFail();
            $commande = Commande::whereKey($article->idCommande)->lockForUpdate()->firstOrFail();

            if ($prix < 0) {
                throw new RuntimeException('Le prix unitaire ne peut pas être négatif');
            }

            if (in_array($commande->statut, [Commande::STATUT_ANNULEE, Commande::STATUT_RECUPEREE], true)) {
                throw new RuntimeException('Le prix ne peut pas être défini pour une commande annulée ou récupérée');
            }

            $article->prixUnitaire = $prix;
            $article->save();

            $commande->montantTotal = Article::where('idCommande', $commande->idCommande)
                ->whereNotNull('prixUnitaire')
                ->sum('prixUnitaire');
            $commande->save();

            return $article;
        });
    }
}

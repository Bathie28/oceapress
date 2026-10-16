<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Commande;
use App\Services\CreerFacture;
use App\Services\DefinirPrixArticle;
use App\Services\EnregistrerPaiement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class PaiementTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function facture(): \App\Models\Facture
    {
        $commande = $this->creerCommande();
        $article = Article::create(['typeArticle' => 'Chemise', 'idCommande' => $commande->idCommande]);
        app(DefinirPrixArticle::class)->executer($article->idArticle, 1000);

        return app(CreerFacture::class)->executer($commande->idCommande);
    }

    public function test_echec_garde_facture_en_attente_et_confirmation_la_paie(): void
    {
        $facture = $this->facture();
        $service = app(EnregistrerPaiement::class);
        $service->executer($facture->idFacture, 'WAVE', 'ECHOUE', 1000);
        $this->assertSame('EN_ATTENTE', $facture->fresh()->statut);

        $service->executer($facture->idFacture, 'ORANGE_MONEY', 'CONFIRME', 1000);
        $this->assertSame('PAYEE', $facture->fresh()->statut);
    }

    public function test_refuse_une_methode_hors_liste(): void
    {
        $facture = $this->facture();
        $this->expectException(RuntimeException::class);
        app(EnregistrerPaiement::class)->executer($facture->idFacture, 'CARTE', 'CONFIRME', 1000);
    }
}

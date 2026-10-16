<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Commande;
use App\Services\CreerFacture;
use App\Services\DefinirPrixArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class FactureTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function ajouterArticle(Commande $commande, string $type = 'Chemise'): Article
    {
        return Article::create(['typeArticle' => $type, 'idCommande' => $commande->idCommande]);
    }

    public function test_cree_une_facture_au_format_attendu_et_refuse_la_seconde(): void
    {
        $commande = $this->creerCommande();
        $article = $this->ajouterArticle($commande);
        app(DefinirPrixArticle::class)->executer($article->idArticle, 1250.50);

        $facture = app(CreerFacture::class)->executer($commande->idCommande);
        $this->assertMatchesRegularExpression('/^FAC-\d{8}-[A-Z]{6}$/', $facture->numeroFacture);

        $this->expectException(RuntimeException::class);
        app(CreerFacture::class)->executer($commande->idCommande);
    }

    public function test_refuse_une_commande_annulee_et_un_article_sans_prix(): void
    {
        $annulee = $this->creerCommande(null, null, Commande::STATUT_ANNULEE);
        try {
            app(CreerFacture::class)->executer($annulee->idCommande);
            $this->fail('Une commande annulée ne peut pas être facturée.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('annulée', $exception->getMessage());
        }

        $commande = $this->creerCommande();
        $this->ajouterArticle($commande);
        try {
            app(CreerFacture::class)->executer($commande->idCommande);
            $this->fail('Un article sans prix ne peut pas être facturé.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('prix', $exception->getMessage());
        }
    }

    public function test_montant_commande_est_la_somme_des_prix_unitaires(): void
    {
        $commande = $this->creerCommande();
        $premier = $this->ajouterArticle($commande, 'Chemise');
        $second = $this->ajouterArticle($commande, 'Pantalon');
        $prix = app(DefinirPrixArticle::class);
        $prix->executer($premier->idArticle, 1200);
        $prix->executer($second->idArticle, 2450.75);

        $this->assertSame('3650.75', $commande->fresh()->montantTotal);
    }
}

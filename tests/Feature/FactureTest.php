<?php

namespace Tests\Feature;

use App\Models\Facture;
use App\Services\EnregistrerCommande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class FactureTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    public function test_la_facture_est_emise_avec_la_commande_et_le_montant_calcule(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise',
            'formule' => 'REPASSAGE', 'unite' => 'PIECE', 'prix' => '1525',
        ]);

        $commande = app(EnregistrerCommande::class)->executer(
            ['nom' => 'Client', 'prenom' => 'Test', 'email' => 'facture-auto@example.test', 'motDePasse' => 'secret'],
            [['typeArticle' => 'Chemise', 'quantite' => 2]],
            $employe->idEmploye,
            $pressing->idPressing,
        );
        $facture = Facture::where('idCommande', $commande->idCommande)->firstOrFail();

        $this->assertMatchesRegularExpression('/^FAC-\\d{8}-[A-Z]{6}$/', $facture->numeroFacture);
        $this->assertSame('3050', $facture->montantTotal);
        $this->assertSame('EN_ATTENTE', $facture->statut);
    }

    public function test_une_erreur_de_tarif_n_ecrit_ni_commande_ni_facture(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);

        try {
            app(EnregistrerCommande::class)->executer(
                ['nom' => 'Client', 'prenom' => 'Test', 'email' => 'sans-tarif@example.test', 'motDePasse' => 'secret'],
                [['typeArticle' => 'Chemise']],
                $employe->idEmploye,
                $pressing->idPressing,
            );
            $this->fail('Une commande sans tarif devait être refusée.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Pas de tarif pour Chemise', $exception->getMessage());
        }

        $this->assertDatabaseMissing('utilisateur', ['email' => 'sans-tarif@example.test']);
        $this->assertDatabaseCount('commande', 0);
        $this->assertDatabaseCount('facture', 0);
    }

    public function test_deux_commandes_creent_chacune_une_facture_unique(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise',
            'formule' => 'REPASSAGE', 'unite' => 'PIECE', 'prix' => '1000',
        ]);
        $service = app(EnregistrerCommande::class);
        $client = ['nom' => 'Client', 'prenom' => 'Test', 'email' => 'deux-factures@example.test', 'motDePasse' => 'secret'];

        $premiere = $service->executer($client, [['typeArticle' => 'Chemise']], $employe->idEmploye, $pressing->idPressing);
        $seconde = $service->executer($client, [['typeArticle' => 'Chemise']], $employe->idEmploye, $pressing->idPressing);

        $this->assertSame(1, Facture::where('idCommande', $premiere->idCommande)->count());
        $this->assertSame(1, Facture::where('idCommande', $seconde->idCommande)->count());
    }
}

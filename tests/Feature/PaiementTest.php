<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Commande;
use App\Models\Facture;
use App\Models\Pressing;
use App\Services\EnregistrerCommande;
use App\Services\EnregistrerPaiement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class PaiementTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function facture(): Facture
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing,
            'typeArticle' => 'Chemise',
            'formule' => 'LAVAGE',
            'unite' => 'PIECE',
            'prix' => '1000.00',
        ]);
        $commande = app(EnregistrerCommande::class)->executer(
            ['nom' => 'Client', 'prenom' => 'Test', 'email' => 'paiement@example.test', 'motDePasse' => 'secret'],
            [['typeArticle' => 'Chemise']],
            $employe->idEmploye,
            $pressing->idPressing,
        );

        return Facture::where('idCommande', $commande->idCommande)->firstOrFail();
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

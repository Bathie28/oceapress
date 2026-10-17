<?php

namespace Tests\Feature;

use App\Models\Facture;
use App\Models\Pressing;
use App\Services\CalculerMontant;
use App\Services\EnregistrerCommande;
use App\Services\EnregistrerTarif;
use App\Services\ModifierTarif;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class TarifsServicesTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    public function test_refuse_un_second_tarif_piece_identique(): void
    {
        $pressing = $this->creerPressing();
        $service = app(EnregistrerTarif::class);
        $service->executer($pressing, 'Chemise', 'LAVAGE', 'PIECE', 10.00);

        try {
            $service->executer($pressing, 'Chemise', 'LAVAGE', 'PIECE', 12.00);
            $this->fail('Le doublon de tarif pièce devait être refusé.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Un tarif existe déjà pour ce type d’article et cette formule', $exception->getMessage());
        }
    }

    public function test_refuse_un_second_tarif_kg_identique(): void
    {
        $pressing = $this->creerPressing();
        $service = app(EnregistrerTarif::class);
        $service->executer($pressing, null, 'LAVAGE', 'KG', 10.00);

        $this->expectException(RuntimeException::class);
        $service->executer($pressing, null, 'LAVAGE', 'KG', 12.00);
    }

    public function test_modifier_tarif_met_a_jour_le_prix(): void
    {
        $pressing = $this->creerPressing();
        $tarif = app(EnregistrerTarif::class)->executer($pressing, 'Chemise', 'LAVAGE', 'PIECE', 10.00);

        $modifie = app(ModifierTarif::class)->executer($tarif->idTarif, 15.50);

        $this->assertSame('15.50', $modifie->fresh()->prix);
    }

    public function test_modifier_tarif_ne_change_pas_une_facture_existante_mais_tarife_les_commandes_suivantes(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        $tarif = app(EnregistrerTarif::class)->executer($pressing, 'Chemise', 'LAVAGE', 'PIECE', 10.00);
        $serviceCommande = app(EnregistrerCommande::class);
        $client = ['nom' => 'Client', 'prenom' => 'Test', 'email' => 'prix-historique@example.test', 'motDePasse' => 'secret'];
        $premiere = $serviceCommande->executer($client, [['typeArticle' => 'Chemise']], $employe->idEmploye, $pressing->idPressing);
        $factureExistante = Facture::where('idCommande', $premiere->idCommande)->firstOrFail();

        app(ModifierTarif::class)->executer($tarif->idTarif, 25.00);
        $seconde = $serviceCommande->executer($client, [['typeArticle' => 'Chemise']], $employe->idEmploye, $pressing->idPressing);

        $this->assertSame('10.00', $factureExistante->fresh()->montantTotal);
        $this->assertSame('25.00', Facture::where('idCommande', $seconde->idCommande)->firstOrFail()->montantTotal);
    }

    public function test_modifier_tarif_refuse_un_prix_negatif(): void
    {
        $pressing = $this->creerPressing();
        $tarif = app(EnregistrerTarif::class)->executer($pressing, 'Chemise', 'LAVAGE', 'PIECE', 10.00);

        $this->expectException(RuntimeException::class);
        app(ModifierTarif::class)->executer($tarif->idTarif, -1.00);
    }
}

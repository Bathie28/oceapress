<?php

namespace Tests\Feature;

use App\Models\Facture;
use App\Services\EnregistrerCommande;
use App\Services\EnregistrerTarif;
use App\Services\ModifierTarif;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $service->executer($pressing, 'Chemise', 'REPASSAGE', 'PIECE', 1000);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Un tarif existe déjà pour ce type d’article et cette formule');
        $service->executer($pressing, 'Chemise', 'REPASSAGE', 'PIECE', 1200);
    }

    public function test_refuse_un_second_tarif_kg_identique(): void
    {
        $pressing = $this->creerPressing(); $pressing->update(['pesee' => true]);
        $service = app(EnregistrerTarif::class);
        $service->executer($pressing, null, 'LAVAGE', 'KG', 1000);
        $this->expectException(RuntimeException::class);
        $service->executer($pressing, null, 'LAVAGE', 'KG', 1200);
    }

    public function test_modifier_tarif_met_a_jour_le_prix_entier(): void
    {
        $tarif = app(EnregistrerTarif::class)->executer($this->creerPressing(), 'Chemise', 'REPASSAGE', 'PIECE', 1000);
        $modifie = app(ModifierTarif::class)->executer($tarif->idTarif, 1550);
        $this->assertSame('1550', $modifie->fresh()->prix);
    }

    public function test_modifier_tarif_ne_change_pas_une_facture_deja_emise(): void
    {
        $pressing = $this->creerPressing(); $employe = $this->creerEmploye($pressing);
        $tarif = app(EnregistrerTarif::class)->executer($pressing, 'Chemise', 'REPASSAGE', 'PIECE', 1000);
        $client = ['nom' => 'Client', 'prenom' => 'Test', 'email' => 'historique-tarif@example.test', 'motDePasse' => 'secret'];
        $commande = app(EnregistrerCommande::class)->executer($client, [['typeArticle' => 'Chemise']], $employe->idEmploye, $pressing->idPressing);
        $facture = Facture::where('idCommande', $commande->idCommande)->firstOrFail();
        app(ModifierTarif::class)->executer($tarif->idTarif, 2500);
        $this->assertSame('1000', $facture->fresh()->montantTotal);
    }

    public function test_modifier_tarif_refuse_un_prix_negatif(): void
    {
        $tarif = app(EnregistrerTarif::class)->executer($this->creerPressing(), 'Chemise', 'REPASSAGE', 'PIECE', 1000);
        $this->expectException(RuntimeException::class);
        app(ModifierTarif::class)->executer($tarif->idTarif, -1);
    }

    public function test_refuse_un_tarif_kg_pour_un_pressing_sans_pesee(): void
    {
        $this->expectException(RuntimeException::class);
        app(EnregistrerTarif::class)->executer($this->creerPressing(), null, 'LAVAGE', 'KG', 1000);
    }

    public function test_refuse_une_formule_non_autorisee_pour_l_unite(): void
    {
        $this->expectException(RuntimeException::class);
        app(EnregistrerTarif::class)->executer($this->creerPressing(), 'Chemise', 'LAVAGE', 'PIECE', 1000);
    }

    public function test_tarif_repassage_en_doublon_est_refuse(): void
    {
        $pressing = $this->creerPressing(); $service = app(EnregistrerTarif::class);
        $service->executer($pressing, 'Chemise', 'REPASSAGE', 'PIECE', 1000);
        $this->expectException(RuntimeException::class);
        $service->executer($pressing, 'Chemise', 'REPASSAGE', 'PIECE', 1200);
    }
}

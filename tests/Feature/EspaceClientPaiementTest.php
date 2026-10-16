<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Commande;
use App\Models\Facture;
use App\Models\Paiement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class EspaceClientPaiementTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function connecterClient(): Client
    {
        $client = $this->creerClient();
        $this->actingAs($client->utilisateur);

        return $client;
    }

    private function creerFacture(Client $client, string $statut = 'EN_ATTENTE'): Facture
    {
        $commande = $this->creerCommande($client);

        return Facture::create([
            'numeroFacture' => 'FAC-'.strtoupper(bin2hex(random_bytes(6))),
            'montantTotal' => 2750.50,
            'statut' => $statut,
            'idCommande' => $commande->idCommande,
        ]);
    }

    public function test_lancement_du_paiement_cree_un_paiement_en_attente_et_garde_la_facture_en_attente(): void
    {
        $client = $this->connecterClient();
        $facture = $this->creerFacture($client);

        $this->post('/client/factures/'.$facture->idFacture.'/paiement', ['methode' => 'WAVE'])
            ->assertRedirect('/client/commandes/'.$facture->idCommande);

        $this->assertDatabaseHas('paiement', [
            'idFacture' => $facture->idFacture,
            'methode' => 'WAVE',
            'statut' => 'EN_ATTENTE',
            'montant' => '2750.50',
        ]);
        $this->assertSame('EN_ATTENTE', $facture->fresh()->statut);
    }

    public function test_le_statut_envoye_dans_la_requete_est_ignore(): void
    {
        $client = $this->connecterClient();
        $facture = $this->creerFacture($client);

        $this->post('/client/factures/'.$facture->idFacture.'/paiement', [
            'methode' => 'ORANGE_MONEY',
            'statut' => 'CONFIRME',
        ])->assertRedirect('/client/commandes/'.$facture->idCommande);

        $this->assertDatabaseHas('paiement', [
            'idFacture' => $facture->idFacture,
            'methode' => 'ORANGE_MONEY',
            'statut' => 'EN_ATTENTE',
            'montant' => '2750.50',
        ]);
        $this->assertSame('EN_ATTENTE', $facture->fresh()->statut);
    }

    public function test_payer_la_facture_d_un_autre_client_renvoie_404_sans_paiement(): void
    {
        $this->connecterClient();
        $autreClient = $this->creerClient();
        $facture = $this->creerFacture($autreClient);

        $this->post('/client/factures/'.$facture->idFacture.'/paiement', ['methode' => 'WAVE'])
            ->assertNotFound();

        $this->assertSame(0, Paiement::where('idFacture', $facture->idFacture)->count());
    }

    public function test_payer_une_facture_deja_payee_est_refuse_sans_creer_de_paiement(): void
    {
        $client = $this->connecterClient();
        $facture = $this->creerFacture($client, 'PAYEE');

        $this->from('/client/commandes/'.$facture->idCommande)
            ->post('/client/factures/'.$facture->idFacture.'/paiement', ['methode' => 'WAVE'])
            ->assertRedirect('/client/commandes/'.$facture->idCommande)
            ->assertSessionHasErrors('paiement');

        $this->assertSame(0, Paiement::where('idFacture', $facture->idFacture)->count());
    }

    public function test_methode_de_paiement_hors_liste_est_refusee(): void
    {
        $client = $this->connecterClient();
        $facture = $this->creerFacture($client);

        $this->from('/client/commandes/'.$facture->idCommande)
            ->post('/client/factures/'.$facture->idFacture.'/paiement', ['methode' => 'CASH'])
            ->assertRedirect('/client/commandes/'.$facture->idCommande)
            ->assertSessionHasErrors('methode');

        $this->assertSame(0, Paiement::where('idFacture', $facture->idFacture)->count());
    }
}

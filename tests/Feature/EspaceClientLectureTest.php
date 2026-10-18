<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Facture;
use App\Models\HistoriqueCommande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class EspaceClientLectureTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function connecterClient(): Client
    {
        $client = $this->creerClient();
        $this->actingAs($client->utilisateur);

        return $client;
    }

    public function test_un_client_ne_voit_que_ses_commandes(): void
    {
        $client = $this->connecterClient();
        $commande = $this->creerCommande($client);
        $commande->update(['dateCreation' => '2026-10-14 10:00:00', 'montantTotal' => 1450]);
        $commandePlusAncienne = $this->creerCommande($client);
        $commandePlusAncienne->update(['dateCreation' => '2026-10-13 10:00:00']);
        $commandeAutreClient = $this->creerCommande();

        $this->get('/client/commandes')->assertOk()
            ->assertSee($commande->referenceCommande)
            ->assertSee($commandePlusAncienne->referenceCommande)
            ->assertSee('EN ATTENTE')
            ->assertSee('2026-10-14 10:00:00')
            ->assertSee('1 450')
            ->assertSeeInOrder([$commande->referenceCommande, $commandePlusAncienne->referenceCommande])
            ->assertDontSee($commandeAutreClient->referenceCommande);
    }

    public function test_detail_d_une_commande_d_un_autre_client_renvoie_404(): void
    {
        $this->connecterClient();
        $commandeAutreClient = $this->creerCommande();

        $this->get('/client/commandes/'.$commandeAutreClient->idCommande)->assertNotFound();
    }

    public function test_detail_commande_affiche_reference_statut_montant_articles_et_historique(): void
    {
        $client = $this->connecterClient();
        $commande = $this->creerCommande($client);
        $commande->update(['montantTotal' => 1450]);
        Article::create([
            'typeArticle' => 'Chemise',
            'quantite' => 2,
            'idCommande' => $commande->idCommande,
        ]);
        HistoriqueCommande::where('idCommande', $commande->idCommande)
            ->update(['commentaire' => 'Commande prise en charge']);

        $this->get('/client/commandes/'.$commande->idCommande)->assertOk()
            ->assertSee($commande->referenceCommande)
            ->assertSee('EN ATTENTE')
            ->assertSee('1 450')
            ->assertSee('Chemise')
            ->assertSee('Quantité : 2')
            ->assertSee('Commande prise en charge');
    }

    public function test_detail_sans_facture_affiche_le_message_attendu(): void
    {
        $client = $this->connecterClient();
        $commande = $this->creerCommande($client);

        $this->get('/client/commandes/'.$commande->idCommande)->assertOk()
            ->assertSee('Facture non encore émise');
    }

    public function test_detail_avec_facture_affiche_son_numero(): void
    {
        $client = $this->connecterClient();
        $commande = $this->creerCommande($client);
        Facture::create([
            'numeroFacture' => 'FAC-20261016-ABCDEF',
            'montantTotal' => 1450,
            'statut' => 'EN_ATTENTE',
            'idCommande' => $commande->idCommande,
        ]);

        $this->get('/client/commandes/'.$commande->idCommande)->assertOk()
            ->assertSee('FAC-20261016-ABCDEF')
            ->assertSee('1 450')
            ->assertSee('EN ATTENTE');
    }

    public function test_un_employe_ne_peut_pas_acceder_a_la_liste_client(): void
    {
        $employe = $this->creerEmploye();

        $this->actingAs($employe->utilisateur)
            ->get('/client/commandes')
            ->assertForbidden();
    }

    public function test_un_visiteur_est_redirige_vers_la_connexion_depuis_les_commandes_client(): void
    {
        $this->get('/client/commandes')->assertRedirect('/login');
    }
}

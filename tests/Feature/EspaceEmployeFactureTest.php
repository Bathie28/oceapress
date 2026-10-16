<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Commande;
use App\Models\Employe;
use App\Models\Facture;
use App\Services\CreerFacture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class EspaceEmployeFactureTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function connecterEmploye(): Employe
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);

        return $employe;
    }

    private function ajouterArticle(Commande $commande, ?float $prix = 1000): Article
    {
        return Article::create([
            'typeArticle' => 'Chemise',
            'prixUnitaire' => $prix,
            'idCommande' => $commande->idCommande,
        ]);
    }

    public function test_emet_facture_pour_une_commande_du_pressing_avec_le_total(): void
    {
        $employe = $this->connecterEmploye();
        $commande = $this->creerCommande(null, $employe->pressing);
        $commande->update(['montantTotal' => 2750.50]);
        $this->ajouterArticle($commande, 2750.50);

        $this->post('/employe/commandes/'.$commande->idCommande.'/facture')
            ->assertRedirect('/employe/commandes/'.$commande->idCommande);

        $this->assertDatabaseHas('facture', [
            'idCommande' => $commande->idCommande,
            'montantTotal' => '2750.50',
            'statut' => 'EN_ATTENTE',
        ]);
        $this->assertSame(1, Facture::where('idCommande', $commande->idCommande)->count());
        $facture = Facture::where('idCommande', $commande->idCommande)->firstOrFail();
        $this->get('/employe/commandes/'.$commande->idCommande)->assertOk()
            ->assertSee($facture->numeroFacture)
            ->assertSee('2 750.50')
            ->assertSee('EN ATTENTE')
            ->assertDontSee('Émettre la facture');
    }

    public function test_seconde_emission_est_refusee_par_le_service_sans_creer_une_seconde_facture(): void
    {
        $employe = $this->connecterEmploye();
        $commande = $this->creerCommande(null, $employe->pressing);
        $this->ajouterArticle($commande);
        $this->post('/employe/commandes/'.$commande->idCommande.'/facture');

        $this->from('/employe/commandes/'.$commande->idCommande)
            ->post('/employe/commandes/'.$commande->idCommande.'/facture')
            ->assertRedirect('/employe/commandes/'.$commande->idCommande)
            ->assertSessionHas('errors', fn ($errors) => str_contains(
                $errors->first('facturation'), 'Une facture existe',
            ));

        $this->assertSame(1, Facture::where('idCommande', $commande->idCommande)->count());
    }

    public function test_commande_annulee_affiche_le_message_sans_bouton_et_refuse_l_emission_directe(): void
    {
        $employe = $this->connecterEmploye();
        $commande = $this->creerCommande(null, $employe->pressing, Commande::STATUT_ANNULEE);

        $this->get('/employe/commandes/'.$commande->idCommande)
            ->assertOk()
            ->assertSee('Une commande annulée ne peut pas être facturée')
            ->assertDontSee('Émettre la facture');

        $this->from('/employe/commandes/'.$commande->idCommande)
            ->post('/employe/commandes/'.$commande->idCommande.'/facture')
            ->assertRedirect('/employe/commandes/'.$commande->idCommande)
            ->assertSessionHas('errors', fn ($errors) => str_contains(
                $errors->first('facturation'), 'ne peut pas être facturée',
            ));

        $this->assertSame(0, Facture::where('idCommande', $commande->idCommande)->count());
    }

    public function test_emission_est_refusee_si_un_article_n_a_pas_de_prix(): void
    {
        $employe = $this->connecterEmploye();
        $commande = $this->creerCommande(null, $employe->pressing);
        $this->ajouterArticle($commande, null);

        $this->from('/employe/commandes/'.$commande->idCommande)
            ->post('/employe/commandes/'.$commande->idCommande.'/facture')
            ->assertRedirect('/employe/commandes/'.$commande->idCommande)
            ->assertSessionHas('errors', fn ($errors) => str_contains(
                $errors->first('facturation'), 'prix',
            ));

        $this->assertSame(0, Facture::where('idCommande', $commande->idCommande)->count());
    }

    public function test_commande_d_un_autre_pressing_renvoie_404_sans_appeler_le_service(): void
    {
        $this->connecterEmploye();
        $autrePressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $autrePressing);
        $service = Mockery::mock(CreerFacture::class);
        $service->shouldNotReceive('executer');
        $this->app->instance(CreerFacture::class, $service);

        $this->post('/employe/commandes/'.$commande->idCommande.'/facture')->assertNotFound();

        $this->assertSame(0, Facture::where('idCommande', $commande->idCommande)->count());
    }
}

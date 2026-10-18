<?php

namespace Tests\Feature;

use App\Models\Commande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class EspaceEmployeLectureTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function connexionEmploye()
    {
        $employe = $this->creerEmploye();
        $utilisateur = $employe->utilisateur;
        $this->actingAs($utilisateur);

        return [$employe, $utilisateur];
    }

    public function test_un_employe_ne_voit_que_les_commandes_de_son_pressing(): void
    {
        [$employe] = $this->connexionEmploye();
        $commandeLocale = $this->creerCommande(null, $employe->pressing);
        $autrePressing = $this->creerPressing();
        $commandeAutrePressing = $this->creerCommande(null, $autrePressing);

        $this->get('/employe/commandes')->assertOk()
            ->assertSee($commandeLocale->referenceCommande)
            ->assertDontSee($commandeAutrePressing->referenceCommande);
    }

    public function test_filtre_statut_ne_retourne_que_le_statut_demande(): void
    {
        [$employe] = $this->connexionEmploye();
        $enAttente = $this->creerCommande(null, $employe->pressing, Commande::STATUT_EN_ATTENTE);
        $prete = $this->creerCommande(null, $employe->pressing, Commande::STATUT_PRETE);

        $this->get('/employe/commandes?statut=PRETE')->assertOk()
            ->assertSee($prete->referenceCommande)
            ->assertDontSee($enAttente->referenceCommande);
    }

    public function test_statut_invalide_dans_le_filtre_est_ignore(): void
    {
        [$employe] = $this->connexionEmploye();
        $une = $this->creerCommande(null, $employe->pressing);
        $deux = $this->creerCommande(null, $employe->pressing, Commande::STATUT_PRETE);

        $this->get('/employe/commandes?statut=INCONNU')->assertOk()
            ->assertSee($une->referenceCommande)
            ->assertSee($deux->referenceCommande);
    }

    public function test_detail_d_une_commande_d_un_autre_pressing_renvoie_404(): void
    {
        [$employe] = $this->connexionEmploye();
        $autrePressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $autrePressing);

        $this->get('/employe/commandes/'.$commande->idCommande)->assertNotFound();
    }

    public function test_detail_commande_du_pressing_affiche_reference_et_montant(): void
    {
        [$employe] = $this->connexionEmploye();
        $commande = $this->creerCommande(null, $employe->pressing);
        $commande->update(['montantTotal' => 1235]);

        $this->get('/employe/commandes/'.$commande->idCommande)->assertOk()
            ->assertSee($commande->referenceCommande)
            ->assertSee('1 235');
    }

    public function test_accueil_employe_contient_les_liens_vers_les_commandes(): void
    {
        $this->connexionEmploye();

        $this->get('/employe')->assertOk()
            ->assertSee('Commandes')
            ->assertSee('href="'.route('employe.commandes.index').'"', false)
            ->assertSee('Nouvelle commande')
            ->assertSee('href="'.route('employe.commandes.create').'"', false);
    }
}

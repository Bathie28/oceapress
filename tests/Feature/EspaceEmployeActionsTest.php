<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\Article;
use App\Models\Employe;
use App\Models\HistoriqueCommande;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class EspaceEmployeActionsTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function connecterEmploye(): Employe
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);

        return $employe;
    }

    private function formulaireCommande(string $email = 'client-nouvelle@example.test'): array
    {
        return [
            'client' => [
                'email' => $email,
                'nom' => 'Client',
                'prenom' => 'Nouveau',
                'telephone' => '770000001',
            ],
            'articles' => [[
                'typeArticle' => 'Chemise',
                'couleur' => 'Bleu',
                'description' => 'Coton',
                'quantite' => 1,
            ]],
            'formule' => 'LAVAGE',
        ];
    }

    private function ajouterTarifPiece(Employe $employe, string $typeArticle, string $prix): void
    {
        DB::table('tarif')->insert([
            'idPressing' => $employe->pressingActif(),
            'typeArticle' => $typeArticle,
            'formule' => 'LAVAGE',
            'unite' => 'PIECE',
            'prix' => $prix,
        ]);
    }

    public function test_enregistrement_utilise_le_pressing_de_employe_malgre_id_pressing_du_formulaire(): void
    {
        $employe = $this->connecterEmploye();
        $this->ajouterTarifPiece($employe, 'Chemise', '1500.00');
        $autrePressing = $this->creerPressing();

        $response = $this->post('/employe/commandes/nouvelle', array_merge(
            $this->formulaireCommande(),
            ['idPressing' => $autrePressing->idPressing],
        ));

        $commande = Commande::where('idPressing', $employe->pressingActif())->firstOrFail();
        $response->assertRedirect('/employe/commandes/'.$commande->idCommande);
        $this->assertNotSame($autrePressing->idPressing, $commande->idPressing);
        $this->assertSame('1500.00', $commande->fresh()->montantTotal);
    }

    public function test_email_employe_ou_administrateur_refuse_sans_creer_commande(): void
    {
        $employeConnecte = $this->connecterEmploye();
        $this->ajouterTarifPiece($employeConnecte, 'Chemise', '1500.00');
        $autreEmploye = $this->creerEmploye($employeConnecte->pressing);
        $before = Commande::count();

        $response = $this->from('/employe/commandes/nouvelle')->post(
            '/employe/commandes/nouvelle',
            $this->formulaireCommande($autreEmploye->utilisateur->email),
        );

        $response->assertRedirect('/employe/commandes/nouvelle')
            ->assertSessionHasErrors('commande');
        $response->assertSessionHas('errors', fn ($errors) => str_contains(
            $errors->first('commande'), 'Cet e-mail appartient',
        ));
        $this->assertSame($before, Commande::count());

        $administrateur = $this->creerUtilisateur(['email' => 'admin-formulaire@example.test']);
        DB::table('administrateur')->insert(['idAdministrateur' => $administrateur->idUtilisateur]);
        $response = $this->from('/employe/commandes/nouvelle')->post(
            '/employe/commandes/nouvelle',
            $this->formulaireCommande($administrateur->email),
        );
        $response->assertSessionHasErrors('commande');
        $this->assertSame($before, Commande::count());
    }

    public function test_creation_ignore_deux_blocs_vides_et_ne_cree_qu_un_article(): void
    {
        $employe = $this->connecterEmploye();
        $donnees = $this->formulaireCommande();
        $donnees['articles'] = [
            ['typeArticle' => ' Chemise ', 'couleur' => 'Bleu', 'description' => 'Coton', 'quantite' => 1],
            ['typeArticle' => '   ', 'couleur' => '', 'description' => '', 'quantite' => ''],
            ['typeArticle' => '', 'couleur' => '', 'description' => '', 'quantite' => ''],
        ];
        $this->ajouterTarifPiece($employe, 'Chemise', '1500.00');

        $response = $this->post('/employe/commandes/nouvelle', $donnees);
        $commande = Commande::where('idPressing', $employe->pressingActif())->firstOrFail();

        $response->assertRedirect('/employe/commandes/'.$commande->idCommande);
        $this->assertSame(1, Article::where('idCommande', $commande->idCommande)->count());
        $this->assertSame('Chemise', Article::where('idCommande', $commande->idCommande)->firstOrFail()->typeArticle);
    }

    public function test_creation_sans_article_valide_affiche_message_et_ne_cree_rien(): void
    {
        $this->connecterEmploye();
        $donnees = $this->formulaireCommande();
        $donnees['articles'] = [
            ['typeArticle' => '', 'couleur' => '', 'description' => '', 'quantite' => ''],
            ['typeArticle' => '  ', 'couleur' => '', 'description' => '', 'quantite' => ''],
            ['typeArticle' => '', 'couleur' => '', 'description' => '', 'quantite' => ''],
        ];
        $before = Commande::count();

        $response = $this->from('/employe/commandes/nouvelle')
            ->post('/employe/commandes/nouvelle', $donnees);

        $response->assertRedirect('/employe/commandes/nouvelle')
            ->assertSessionHasErrors(['articles' => 'Ajoutez au moins un article']);
        $this->assertSame($before, Commande::count());
    }

    public function test_changement_statut_valide_met_a_jour_commande_et_historique(): void
    {
        $employe = $this->connecterEmploye();
        $commande = $this->creerCommande(null, $employe->pressing);
        $before = HistoriqueCommande::where('idCommande', $commande->idCommande)->count();

        $this->post('/employe/commandes/'.$commande->idCommande.'/statut', [
            'nouveauStatut' => Commande::STATUT_EN_TRAITEMENT,
            'commentaire' => 'Début du nettoyage',
        ])->assertRedirect('/employe/commandes/'.$commande->idCommande);

        $this->assertSame(Commande::STATUT_EN_TRAITEMENT, $commande->fresh()->statut);
        $this->assertSame($before + 1, HistoriqueCommande::where('idCommande', $commande->idCommande)->count());
    }

    public function test_page_commande_en_attente_ne_propose_que_les_statuts_accessibles(): void
    {
        $employe = $this->connecterEmploye();
        $commande = $this->creerCommande(null, $employe->pressing, Commande::STATUT_EN_ATTENTE);

        $this->get('/employe/commandes/'.$commande->idCommande)
            ->assertOk()
            ->assertDontSee('value="EN_ATTENTE"', false)
            ->assertDontSee('value="PRETE"', false)
            ->assertSee('value="EN_TRAITEMENT"', false)
            ->assertSee('value="ANNULEE"', false);
    }

    public function test_page_commande_en_traitement_ne_propose_que_les_statuts_accessibles(): void
    {
        $employe = $this->connecterEmploye();
        $commande = $this->creerCommande(null, $employe->pressing, Commande::STATUT_EN_TRAITEMENT);

        $this->get('/employe/commandes/'.$commande->idCommande)
            ->assertOk()
            ->assertDontSee('value="EN_ATTENTE"', false)
            ->assertSee('value="PRETE"', false)
            ->assertSee('value="ANNULEE"', false)
            ->assertSee('value="" selected disabled>Choisir un statut</option>', false);
    }

    public function test_transition_interdite_affiche_message_sans_nouvelle_ligne_historique(): void
    {
        $employe = $this->connecterEmploye();
        $commande = $this->creerCommande(null, $employe->pressing);
        $before = HistoriqueCommande::where('idCommande', $commande->idCommande)->count();

        $response = $this->post('/employe/commandes/'.$commande->idCommande.'/statut', [
            'nouveauStatut' => Commande::STATUT_PRETE,
        ]);

        $response->assertRedirect('/employe/commandes/'.$commande->idCommande)
            ->assertSessionHasErrors('statut');
        $response->assertSessionHas('errors', fn ($errors) => str_contains(
            $errors->first('statut'), 'Transition de EN_ATTENTE vers PRETE',
        ));
        $this->assertSame($before, HistoriqueCommande::where('idCommande', $commande->idCommande)->count());
    }

    public function test_changement_statut_hors_pressing_est_404_sans_historique(): void
    {
        $this->connecterEmploye();
        $autrePressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $autrePressing);
        $before = HistoriqueCommande::where('idCommande', $commande->idCommande)->count();

        $this->post('/employe/commandes/'.$commande->idCommande.'/statut', [
            'nouveauStatut' => Commande::STATUT_EN_TRAITEMENT,
        ])->assertNotFound();

        $this->assertSame($before, HistoriqueCommande::where('idCommande', $commande->idCommande)->count());
    }
}

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
                'type' => 'Chemise',
                'couleur' => 'Bleu',
                'description' => 'Coton',
                'quantite' => 1,
            ]],
            'modeFacturation' => 'PIECE',
            'formule' => 'REPASSAGE',
        ];
    }

    private function ajouterTarifPiece(Employe $employe, string $typeArticle, string $prix): void
    {
        DB::table('tarif')->insert([
            'idPressing' => $employe->pressingActif(),
            'typeArticle' => $typeArticle,
            'formule' => 'REPASSAGE',
            'unite' => 'PIECE',
            'prix' => $prix,
        ]);
    }

    public function test_enregistrement_utilise_le_pressing_de_employe_malgre_id_pressing_du_formulaire(): void
    {
        $employe = $this->connecterEmploye();
        $this->ajouterTarifPiece($employe, 'Chemise', '1500');
        $autrePressing = $this->creerPressing();

        $response = $this->post('/employe/commandes/nouvelle', array_merge(
            $this->formulaireCommande(),
            ['idPressing' => $autrePressing->idPressing],
        ));

        $commande = Commande::where('idPressing', $employe->pressingActif())->firstOrFail();
        $response->assertRedirect('/employe/commandes/'.$commande->idCommande);
        $this->assertNotSame($autrePressing->idPressing, $commande->idPressing);
        $this->assertSame('1500', $commande->fresh()->montantTotal);
    }

    public function test_email_employe_ou_administrateur_refuse_sans_creer_commande(): void
    {
        $employeConnecte = $this->connecterEmploye();
        $this->ajouterTarifPiece($employeConnecte, 'Chemise', '1500');
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

    public function test_creation_enregistre_les_types_de_grille_avec_leurs_quantites(): void
    {
        $employe = $this->connecterEmploye();
        $donnees = $this->formulaireCommande();
        $donnees['articles'] = [
            ['type' => 'Chemise', 'couleur' => 'Bleu', 'description' => 'Coton', 'quantite' => 2],
            ['type' => 'Pantalon', 'couleur' => '', 'description' => '', 'quantite' => 3],
        ];
        $this->ajouterTarifPiece($employe, 'Chemise', '1500');
        $this->ajouterTarifPiece($employe, 'Pantalon', '2000');

        $response = $this->post('/employe/commandes/nouvelle', $donnees);
        $commande = Commande::where('idPressing', $employe->pressingActif())->firstOrFail();

        $response->assertRedirect('/employe/commandes/'.$commande->idCommande);
        $this->assertSame(2, Article::where('idCommande', $commande->idCommande)->count());
        $this->assertSame([['Chemise', 2], ['Pantalon', 3]], Article::where('idCommande', $commande->idCommande)->orderBy('idArticle')->get(['typeArticle', 'quantite'])->map(fn ($a) => [$a->typeArticle, $a->quantite])->all());
        $this->assertSame('9000', $commande->fresh()->montantTotal);
    }

    public function test_mot_de_passe_provisoire_est_affiche_une_fois_copiable_et_absent_des_logs(): void
    {
        $employe = $this->connecterEmploye();
        $this->ajouterTarifPiece($employe, 'Chemise', '1500');

        $response = $this->post('/employe/commandes/nouvelle', $this->formulaireCommande());
        $commande = Commande::where('idPressing', $employe->pressingActif())->firstOrFail();
        $url = '/employe/commandes/'.$commande->idCommande;
        $motDePasseProvisoire = null;

        $response->assertRedirect($url)
            ->assertSessionHas('motDePasseProvisoire', function ($motDePasse) use (&$motDePasseProvisoire): bool {
                $motDePasseProvisoire = $motDePasse;

                return is_string($motDePasse) && $motDePasse !== '';
            });

        $this->get($url)->assertOk()
            ->assertSee('Copier')
            ->assertSee('id="mot-de-passe-provisoire"', false)
            ->assertSee($motDePasseProvisoire);

        $this->get($url)->assertOk()
            ->assertDontSee($motDePasseProvisoire)
            ->assertDontSee('id="mot-de-passe-provisoire"', false);

        foreach (glob(storage_path('logs/*.log')) ?: [] as $fichierLog) {
            $contenu = file_get_contents($fichierLog);
            $this->assertIsString($contenu);
            $this->assertStringNotContainsString($motDePasseProvisoire, $contenu, basename($fichierLog));
        }
    }

    public function test_creation_http_calcule_quantites_et_enregistre_prix_unitaire_pour_deux_chemises_et_un_pantalon(): void
    {
        $this->assertCreationHttpAvecQuantitesEtPrix(2, 1, '720');
    }

    public function test_creation_http_calcule_quantites_et_enregistre_prix_unitaire_pour_une_chemise_et_un_pantalon(): void
    {
        $this->assertCreationHttpAvecQuantitesEtPrix(1, 1, '480');
    }

    private function assertCreationHttpAvecQuantitesEtPrix(int $chemises, int $pantalons, string $montantAttendu): void
    {
        $employe = $this->connecterEmploye();
        $this->ajouterTarifPiece($employe, 'Chemise', '240');
        $this->ajouterTarifPiece($employe, 'Pantalon', '240');
        $donnees = $this->formulaireCommande('prix-quantites-'.$chemises.'-'.$pantalons.'@example.test');
        $donnees['articles'] = [
            ['type' => 'Chemise', 'quantite' => $chemises],
            ['type' => 'Pantalon', 'quantite' => $pantalons],
        ];

        $response = $this->post('/employe/commandes/nouvelle', $donnees);
        $commande = Commande::where('idPressing', $employe->pressingActif())->firstOrFail();
        $response->assertRedirect('/employe/commandes/'.$commande->idCommande);

        $this->assertSame($montantAttendu, $commande->fresh()->montantTotal);
        $this->assertSame([
            ['Chemise', $chemises, '240'],
            ['Pantalon', $pantalons, '240'],
        ], DB::table('article')->where('idCommande', $commande->idCommande)
            ->orderBy('idArticle')->get(['typeArticle', 'quantite', 'prixUnitaire'])
            ->map(fn ($article) => [$article->typeArticle, (int) $article->quantite, $article->prixUnitaire])
            ->all());
    }

    public function test_creation_sans_article_valide_affiche_message_et_ne_cree_rien(): void
    {
        $this->connecterEmploye();
        $donnees = $this->formulaireCommande();
        $donnees['articles'] = [['type' => 'Chemise', 'quantite' => 0]];
        $before = Commande::count();

        $response = $this->from('/employe/commandes/nouvelle')
            ->post('/employe/commandes/nouvelle', $donnees);

        $response->assertRedirect('/employe/commandes/nouvelle')
            ->assertSessionHasErrors('commande');
        $this->assertSame($before, Commande::count());
    }

    public function test_type_hors_grille_est_refuse_sans_ecriture(): void
    {
        $employe = $this->connecterEmploye();
        $donnees = $this->formulaireCommande();
        $donnees['articles'] = [['type' => 'Veste', 'quantite' => 1]];
        $before = Commande::count();
        $this->from('/employe/commandes/nouvelle')->post('/employe/commandes/nouvelle', $donnees)
            ->assertRedirect('/employe/commandes/nouvelle')->assertSessionHasErrors('commande');
        $this->assertSame($before, Commande::count());
    }

    public function test_formulaire_affiche_les_types_distincts_de_la_grille_piece(): void
    {
        $employe = $this->connecterEmploye();
        $this->ajouterTarifPiece($employe, 'Chemise', '1500');
        $this->ajouterTarifPiece($employe, 'Pantalon', '2000');
        DB::table('tarif')->insert([
            'idPressing' => $employe->pressingActif(), 'typeArticle' => 'Chemise',
            'formule' => 'LAVAGE_REPASSAGE', 'unite' => 'PIECE', 'prix' => '1700',
        ]);
        $this->get('/employe/commandes/nouvelle')->assertOk()
            ->assertSee('Chemise')->assertSee('Pantalon')->assertSee('quantite');
    }

    public function test_formulaire_filtre_les_types_selon_la_formule_choisie(): void
    {
        $employe = $this->connecterEmploye();
        $this->ajouterTarifPiece($employe, 'Pantalon', '1500');

        $this->get('/employe/commandes/nouvelle?formule=LAVAGE_REPASSAGE')->assertOk()
            ->assertDontSee('<legend>Pantalon</legend>', false);
        $this->get('/employe/commandes/nouvelle?formule=REPASSAGE')->assertOk()
            ->assertSee('<legend>Pantalon</legend>', false);
    }

    public function test_la_liste_du_formulaire_est_celle_de_pressing_types_pour_formule(): void
    {
        $employe = $this->connecterEmploye();
        $this->ajouterTarifPiece($employe, 'Chemise', '1500');
        DB::table('tarif')->insert([
            ['idPressing' => $employe->pressingActif(), 'typeArticle' => 'Chemise', 'formule' => 'LAVAGE_REPASSAGE', 'unite' => 'PIECE', 'prix' => '1700'],
            ['idPressing' => $employe->pressingActif(), 'typeArticle' => 'Pantalon', 'formule' => 'LAVAGE_REPASSAGE', 'unite' => 'PIECE', 'prix' => '2100'],
        ]);

        $expected = $employe->pressing->typesPourFormule('LAVAGE_REPASSAGE');
        $this->get('/employe/commandes/types?formule=LAVAGE_REPASSAGE')
            ->assertOk()->assertExactJson(['types' => $expected]);
    }

    public function test_formulaire_commande_limite_les_formules_et_le_poids_selon_le_pressing(): void
    {
        $employe = $this->connecterEmploye();
        $this->ajouterTarifPiece($employe, 'Chemise', '1500');
        $this->get('/employe/commandes/nouvelle')->assertOk()
            ->assertSee('value="PIECE"', false)
            ->assertDontSee('value="POIDS"', false)
            ->assertSee('value="REPASSAGE"', false)
            ->assertDontSee('value="LAVAGE"', false)
            ->assertSee('step="0.1"', false);

        $employe->pressing->update(['pesee' => true]);
        $this->get('/employe/commandes/nouvelle')->assertOk()
            ->assertSee('value="POIDS"', false)
            ->assertSee('"POIDS":["LAVAGE"', false);
    }

    public function test_message_de_tarification_au_poids_est_affiche_uniquement_en_mode_poids(): void
    {
        $employe = $this->connecterEmploye();
        $employe->pressing->update(['pesee' => true]);
        $message = 'Le montant dépend du poids et de la formule seulement. Les quantités d’articles servent à la restitution.';

        $this->get('/employe/commandes/nouvelle?modeFacturation=POIDS')
            ->assertOk()
            ->assertSee($message);
        $this->get('/employe/commandes/nouvelle?modeFacturation=PIECE')
            ->assertOk()
            ->assertDontSee($message);
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

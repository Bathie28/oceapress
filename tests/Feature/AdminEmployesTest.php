<?php

namespace Tests\Feature;

use App\Models\Pressing;
use App\Models\Utilisateur;
use App\Models\Restitution;
use App\Services\CreerEmploye;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class AdminEmployesTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function connecterAdministrateur(): void
    {
        $utilisateur = $this->creerUtilisateur(['email' => 'admin-employes@example.test']);
        DB::table('administrateur')->insert(['idAdministrateur' => $utilisateur->idUtilisateur]);
        $this->actingAs($utilisateur);
    }

    public function test_administrateur_cree_un_employe_affiche_son_mot_de_passe_une_fois_et_le_list_sur_le_pressing(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();

        $reponse = $this->post(route('admin.pressings.employes.store', $pressing->idPressing), [
            'nom' => 'Diallo', 'prenom' => 'Mamadou', 'email' => 'mamadou@example.test',
            'telephone' => '0700000000', 'fonction' => 'Caissier',
        ]);

        $reponse->assertRedirect(route('admin.pressings.edit', $pressing->idPressing));
        $motDePasse = $reponse->getSession()->get('motDePasseProvisoire');
        $this->assertNotEmpty($motDePasse);

        $utilisateur = Utilisateur::query()->where('email', 'mamadou@example.test')->firstOrFail();
        $this->assertTrue(Hash::check($motDePasse, $utilisateur->motDePasse));
        $this->assertTrue((bool) $utilisateur->doitChangerMotDePasse);
        $this->assertDatabaseHas('employe', [
            'idEmploye' => $utilisateur->idUtilisateur,
            'idPressing' => $pressing->idPressing,
            'fonction' => 'Caissier',
        ]);

        $premierePage = $this->get(route('admin.pressings.edit', $pressing->idPressing));
        $premierePage->assertOk()->assertSee('Employés')->assertSee('Diallo')->assertSee('Mamadou')
            ->assertSee('mamadou@example.test')->assertSee('Caissier')->assertSee($motDePasse);

        $htmlEmployes = $premierePage->getContent();
        $this->assertStringContainsString('id="mot-de-passe-provisoire"', $htmlEmployes);
        $this->assertStringContainsString('id="copier-mot-de-passe"', $htmlEmployes);
        $this->assertStringContainsString('navigator.clipboard.writeText', $htmlEmployes);
        $this->assertStringContainsString("document.execCommand('copy')", $htmlEmployes);
        $this->assertStringContainsString('Copié !', $htmlEmployes);
        $this->assertStringContainsString('2000', $htmlEmployes);
        $this->get(route('admin.pressings.edit', $pressing->idPressing))
            ->assertDontSee($motDePasse);
    }

    public function test_email_deja_utilise_refuse_la_creation_sans_ecriture(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();
        $this->creerUtilisateur(['email' => 'deja-utilise@example.test']);

        $utilisateursAvant = DB::table('utilisateur')->count();
        $employesAvant = DB::table('employe')->count();

        $this->from(route('admin.pressings.edit', $pressing->idPressing))
            ->post(route('admin.pressings.employes.store', $pressing->idPressing), [
                'nom' => 'Nouveau', 'prenom' => 'Employé', 'email' => 'deja-utilise@example.test',
                'fonction' => 'Agent',
            ])
            ->assertRedirect(route('admin.pressings.edit', $pressing->idPressing))
            ->assertSessionHasErrors('email');

        $this->assertSame($utilisateursAvant, DB::table('utilisateur')->count());
        $this->assertSame($employesAvant, DB::table('employe')->count());

        $this->expectException(InvalidArgumentException::class);
        app(CreerEmploye::class)->executer($pressing, [
            'nom' => 'Service', 'prenom' => 'Refusé', 'email' => 'deja-utilise@example.test',
            'fonction' => 'Agent',
        ]);
    }

    public function test_un_employe_ne_voit_que_les_commandes_de_son_pressing(): void
    {
        $employe = $this->creerEmploye();
        $commandeLocale = $this->creerCommande(null, $employe->pressing);
        $autrePressing = $this->creerPressing();
        $commandeAutre = $this->creerCommande(null, $autrePressing);

        $this->actingAs($employe->utilisateur)
            ->get(route('employe.commandes.index'))
            ->assertOk()
            ->assertSee($commandeLocale->referenceCommande)
            ->assertDontSee($commandeAutre->referenceCommande);
    }

    public function test_un_employe_et_un_client_obtiennent_403_sur_la_creation_admin(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur)
            ->post(route('admin.pressings.employes.store', $pressing->idPressing), [])
            ->assertForbidden();

        $client = $this->creerClient();
        $this->actingAs($client->utilisateur)
            ->get(route('admin.pressings.edit', $pressing->idPressing))
            ->assertForbidden();
    }

    public function test_employe_desactive_ne_peut_pas_se_connecter(): void
    {
        $employe = $this->creerEmploye();
        $employe->update(['actif' => false]);

        $this->post(route('login.store'), [
            'email' => $employe->utilisateur->email,
            'password' => 'secret',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_employe_actif_peut_toujours_se_connecter(): void
    {
        $employe = $this->creerEmploye();

        $this->post(route('login.store'), [
            'email' => $employe->utilisateur->email,
            'password' => 'secret',
        ])->assertRedirect(route('employe.accueil'));
        $this->assertAuthenticatedAs($employe->utilisateur);
    }

    public function test_desactivation_ne_supprime_pas_commande_restitution_ni_historique(): void
    {
        $this->connecterAdministrateur();
        $employe = $this->creerEmploye();
        $commande = $this->creerCommande(null, $employe->pressing);
        Restitution::create([
            'dateRestitution' => now(),
            'statut' => 'CONFORME',
            'idCommande' => $commande->idCommande,
            'idEmploye' => $employe->idEmploye,
        ]);
        $commandesAvant = DB::table('commande')->count();
        $restitutionsAvant = DB::table('restitution')->count();
        $historiquesAvant = DB::table('historique_commande')->count();

        $this->post(route('admin.pressings.employes.desactiver', [$employe->pressing->idPressing, $employe->idEmploye]))
            ->assertRedirect()
            ->assertSessionHas('statut', 'Employé désactivé.');
        $this->get(route('admin.pressings.edit', $employe->pressing->idPressing))
            ->assertOk()
            ->assertSee('Employé désactivé.');

        $this->assertDatabaseHas('employe', ['idEmploye' => $employe->idEmploye, 'actif' => false]);
        $this->assertSame($commandesAvant, DB::table('commande')->count());
        $this->assertSame($restitutionsAvant, DB::table('restitution')->count());
        $this->assertSame($historiquesAvant, DB::table('historique_commande')->count());
    }

    public function test_employe_desactive_est_reactive_par_administrateur_et_peut_se_connecter(): void
    {
        $this->connecterAdministrateur();
        $employe = $this->creerEmploye();
        $employe->update(['actif' => false]);
        $motDePasseAvant = $employe->utilisateur->motDePasse;
        $doitChangerAvant = (bool) $employe->utilisateur->doitChangerMotDePasse;

        $this->post(route('admin.pressings.employes.reactiver', [$employe->pressing->idPressing, $employe->idEmploye]))
            ->assertRedirect();

        $employe->refresh();
        $this->assertTrue((bool) $employe->actif);
        $this->assertSame($motDePasseAvant, $employe->utilisateur->fresh()->motDePasse);
        $this->assertSame($doitChangerAvant, (bool) $employe->utilisateur->fresh()->doitChangerMotDePasse);

        auth()->logout();
        $this->post(route('login.store'), [
            'email' => $employe->utilisateur->email,
            'password' => 'secret',
        ])->assertRedirect(route('employe.accueil'));
        $this->assertAuthenticatedAs($employe->utilisateur);
    }

    public function test_administrateur_ne_peut_pas_reactiver_employe_d_un_autre_pressing(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();
        $autrePressing = $this->creerPressing();
        $employe = $this->creerEmploye($autrePressing);
        $employe->update(['actif' => false]);

        $this->post(route('admin.pressings.employes.reactiver', [$pressing->idPressing, $employe->idEmploye]))
            ->assertForbidden();
        $this->assertFalse((bool) $employe->fresh()->actif);
    }

    public function test_reactivation_ne_supprime_pas_commande_restitution_ni_historique(): void
    {
        $this->connecterAdministrateur();
        $employe = $this->creerEmploye();
        $employe->update(['actif' => false]);
        $commande = $this->creerCommande(null, $employe->pressing);
        Restitution::create([
            'dateRestitution' => now(),
            'statut' => 'CONFORME',
            'idCommande' => $commande->idCommande,
            'idEmploye' => $employe->idEmploye,
        ]);
        $commandesAvant = DB::table('commande')->count();
        $restitutionsAvant = DB::table('restitution')->count();
        $historiquesAvant = DB::table('historique_commande')->count();

        $this->post(route('admin.pressings.employes.reactiver', [$employe->pressing->idPressing, $employe->idEmploye]))
            ->assertRedirect();

        $this->assertDatabaseHas('employe', ['idEmploye' => $employe->idEmploye, 'actif' => true]);
        $this->assertSame($commandesAvant, DB::table('commande')->count());
        $this->assertSame($restitutionsAvant, DB::table('restitution')->count());
        $this->assertSame($historiquesAvant, DB::table('historique_commande')->count());
    }

    public function test_bouton_action_correspond_a_letat_de_l_employe(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();
        $actif = $this->creerEmploye($pressing);
        $inactif = $this->creerEmploye($pressing);
        $inactif->update(['actif' => false]);

        $page = $this->get(route('admin.pressings.edit', $pressing->idPressing));
        $page->assertOk()->assertSee('Désactiver')->assertSee('Réactiver');
        $html = $page->getContent();
        $this->assertStringContainsString(route('admin.pressings.employes.desactiver', [$pressing->idPressing, $actif->idEmploye]), $html);
        $this->assertStringContainsString(route('admin.pressings.employes.reactiver', [$pressing->idPressing, $inactif->idEmploye]), $html);
    }

    public function test_administrateur_ne_peut_pas_desactiver_employe_d_un_autre_pressing(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();
        $autrePressing = $this->creerPressing();
        $employe = $this->creerEmploye($autrePressing);

        $this->post(route('admin.pressings.employes.desactiver', [$pressing->idPressing, $employe->idEmploye]))
            ->assertForbidden();
        $this->assertDatabaseHas('employe', ['idEmploye' => $employe->idEmploye, 'actif' => true]);
    }

    public function test_connexion_d_un_employe_cree_redirige_vers_le_changement_du_mot_de_passe(): void
    {
        $pressing = $this->creerPressing();
        $compte = app(CreerEmploye::class)->executer($pressing, [
            'nom' => 'Sow', 'prenom' => 'Awa', 'email' => 'awa-employe@example.test',
            'telephone' => null, 'fonction' => 'Réceptionniste',
        ]);

        $this->post(route('login.store'), [
            'email' => 'awa-employe@example.test',
            'password' => $compte['motDePasseProvisoire'],
        ])->assertRedirect(route('employe.mot-de-passe.edit'));

        $this->get(route('employe.mot-de-passe.edit'))->assertOk();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

/**
 * Un navigateur n'a qu'une session par site, partagée par tous ses onglets.
 * La page de connexion ne doit donc pas permettre de remplacer silencieusement
 * le compte connecté : sinon l'onglet resté ouvert sur l'ancien compte reçoit un 403.
 */
class ConnexionSessionOuverteTest extends TestCase
{
    use CreeDonneesTest, RefreshDatabase;

    private function creerAdministrateur(): Utilisateur
    {
        $utilisateur = $this->creerUtilisateur(['email' => 'admin-session@example.test']);
        DB::table('administrateur')->insert(['idAdministrateur' => $utilisateur->idUtilisateur]);

        return $utilisateur;
    }

    public function test_la_page_de_connexion_reste_accessible_sans_session(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('name="email"', false);
        $this->get(route('password.request'))->assertOk();
    }

    public function test_un_utilisateur_connecte_est_renvoye_vers_son_espace_au_lieu_du_formulaire(): void
    {
        $cas = [
            [$this->creerAdministrateur(), 'admin.accueil'],
            [$this->creerEmploye()->utilisateur, 'employe.accueil'],
            [$this->creerClient()->utilisateur, 'client.accueil'],
        ];

        foreach ($cas as [$utilisateur, $espace]) {
            $this->actingAs($utilisateur)->get(route('login'))->assertRedirect(route($espace));
        }
    }

    public function test_une_connexion_depuis_un_autre_onglet_ne_remplace_pas_la_session_ouverte(): void
    {
        $administrateur = $this->creerAdministrateur();
        $employe = $this->creerEmploye();
        $employe->utilisateur->forceFill(['motDePasse' => 'Employe2026!'])->save();

        $this->actingAs($administrateur)
            ->post(route('login.store'), ['email' => $employe->utilisateur->email, 'password' => 'Employe2026!'])
            ->assertRedirect(route('admin.accueil'));

        $this->assertAuthenticatedAs($administrateur);

        $this->post(route('admin.pressings.store'), ['nom' => 'Pressing après autre onglet', 'adresse' => '1 rue du Test'])
            ->assertRedirect(route('admin.pressings.index'));
        $this->assertDatabaseHas('pressing', ['nom' => 'Pressing après autre onglet']);
    }

    public function test_les_pages_de_mot_de_passe_oublie_renvoient_aussi_vers_l_espace(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);

        $this->get(route('password.request'))->assertRedirect(route('employe.accueil'));
        $this->post(route('password.email'), ['email' => $employe->utilisateur->email])->assertRedirect(route('employe.accueil'));
        $this->get(route('password.reset', ['token' => 'jeton']))->assertRedirect(route('employe.accueil'));
        $this->post(route('password.update'), [
            'token' => 'jeton', 'email' => $employe->utilisateur->email,
            'password' => 'Nouveau2026!', 'password_confirmation' => 'Nouveau2026!',
        ])->assertRedirect(route('employe.accueil'));
    }

    public function test_un_compte_sans_espace_n_entraine_pas_de_boucle_de_redirection(): void
    {
        $sansRole = $this->creerUtilisateur(['email' => 'sans-role@example.test']);

        $this->actingAs($sansRole)->get(route('login'))->assertRedirect('/');
        $this->get('/')->assertOk();
    }

    public function test_la_deconnexion_permet_ensuite_de_changer_de_compte(): void
    {
        $administrateur = $this->creerAdministrateur();
        $employe = $this->creerEmploye();
        $employe->utilisateur->forceFill(['motDePasse' => 'Employe2026!'])->save();

        $this->actingAs($administrateur)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post(route('login.store'), ['email' => $employe->utilisateur->email, 'password' => 'Employe2026!'])
            ->assertRedirect(route('employe.accueil'));
        $this->assertAuthenticatedAs($employe->utilisateur);
    }
}

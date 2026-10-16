<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employe;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function creerUtilisateurAvecMotDePasse(string $email, string $password = 'secret'): Utilisateur
    {
        return Utilisateur::create([
            'nom' => 'Test',
            'prenom' => 'Utilisateur',
            'email' => $email,
            'motDePasse' => Hash::make($password),
        ]);
    }

    private function creerClientAvecUtilisateur(Utilisateur $utilisateur): Client
    {
        return Client::create([
            'idClient' => $utilisateur->idUtilisateur,
            'dateInscription' => now()->toDateString(),
        ]);
    }

    private function creerEmployeAvecUtilisateur(Utilisateur $utilisateur, ?int $idPressing = null): Employe
    {
        $idPressing ??= $this->creerPressing()->idPressing;
        DB::table('employe')->insert([
            'idEmploye' => $utilisateur->idUtilisateur,
            'fonction' => 'Agent',
            'dateEmbauche' => now()->toDateString(),
            'idPressing' => $idPressing,
        ]);

        return Employe::findOrFail($utilisateur->idUtilisateur);
    }

    public function test_connexion_reussie_pour_un_client_redirige_vers_client(): void
    {
        $utilisateur = $this->creerUtilisateurAvecMotDePasse('client-auth@example.test');
        $this->creerClientAvecUtilisateur($utilisateur);

        $this->post('/login', ['email' => $utilisateur->email, 'password' => 'secret'])
            ->assertRedirect('/client')
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($utilisateur);
    }

    public function test_connexion_reussie_pour_un_employe_redirige_vers_employe(): void
    {
        $utilisateur = $this->creerUtilisateurAvecMotDePasse('employe-auth@example.test');
        $this->creerEmployeAvecUtilisateur($utilisateur);

        $this->post('/login', ['email' => $utilisateur->email, 'password' => 'secret'])
            ->assertRedirect('/employe')
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($utilisateur);
    }

    public function test_connexion_administrateur_redirige_vers_admin(): void
    {
        $utilisateur = $this->creerUtilisateurAvecMotDePasse('admin-auth@example.test');
        DB::table('administrateur')->insert(['idAdministrateur' => $utilisateur->idUtilisateur]);

        $this->post('/login', ['email' => $utilisateur->email, 'password' => 'secret'])
            ->assertRedirect('/admin')
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($utilisateur);
    }

    public function test_mot_de_passe_incorrect_affiche_un_message_generique_sans_connecter(): void
    {
        $utilisateur = $this->creerUtilisateurAvecMotDePasse('client-faux@example.test');
        $this->creerClientAvecUtilisateur($utilisateur);

        $this->from('/login')->post('/login', ['email' => $utilisateur->email, 'password' => 'incorrect'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Identifiants incorrects']);

        $this->assertGuest();
    }

    public function test_un_client_qui_tente_espace_employe_obtient_403(): void
    {
        $utilisateur = $this->creerUtilisateurAvecMotDePasse('client-403@example.test');
        $this->creerClientAvecUtilisateur($utilisateur);

        $this->actingAs($utilisateur)->get('/employe')->assertForbidden();
    }

    public function test_client_qui_tente_espace_admin_obtient_403(): void
    {
        $utilisateur = $this->creerUtilisateurAvecMotDePasse('client-admin-403@example.test');
        $this->creerClientAvecUtilisateur($utilisateur);

        $this->actingAs($utilisateur)->get('/admin')->assertForbidden();
    }

    public function test_un_employe_qui_tente_espace_admin_obtient_403(): void
    {
        $utilisateur = $this->creerUtilisateurAvecMotDePasse('employe-403@example.test');
        $this->creerEmployeAvecUtilisateur($utilisateur);

        $this->actingAs($utilisateur)->get('/admin')->assertForbidden();
    }

    public function test_un_visiteur_est_redirige_vers_connexion_depuis_espace_client(): void
    {
        $this->get('/client')->assertRedirect('/login');
    }

    public function test_deconnexion_ferme_la_session(): void
    {
        $utilisateur = $this->creerUtilisateurAvecMotDePasse('logout@example.test');
        $this->creerClientAvecUtilisateur($utilisateur);

        $this->actingAs($utilisateur)->withSession(['private_value' => 'secret'])->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionMissing('private_value');

        $this->assertGuest();
    }

    public function test_id_pressing_fourni_dans_la_requete_ne_change_pas_le_pressing_actif(): void
    {
        $utilisateur = $this->creerUtilisateurAvecMotDePasse('employe-pressing@example.test');
        $pressing = $this->creerPressing();
        $employe = $this->creerEmployeAvecUtilisateur($utilisateur, $pressing->idPressing);
        $autrePressing = $this->creerPressing();

        $this->actingAs($utilisateur)
            ->get('/employe?idPressing='.$autrePressing->idPressing)
            ->assertOk()
            ->assertViewHas('pressingActif', $pressing->idPressing);

        $this->assertSame($pressing->idPressing, $employe->fresh()->pressingActif());
    }

    public function test_connexion_refuse_un_compte_avec_plusieurs_roles(): void
    {
        $utilisateur = $this->creerUtilisateurAvecMotDePasse('multi-role@example.test');
        $this->creerClientAvecUtilisateur($utilisateur);
        $this->creerEmployeAvecUtilisateur($utilisateur);

        $this->from('/login')->post('/login', ['email' => $utilisateur->email, 'password' => 'secret'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_compte_sans_role_est_deconnecte_avec_message(): void
    {
        $utilisateur = $this->creerUtilisateurAvecMotDePasse('sans-role@example.test');

        $this->from('/login')->post('/login', ['email' => $utilisateur->email, 'password' => 'secret'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Ce compte n’a pas d’espace associé']);

        $this->assertGuest();
    }
}

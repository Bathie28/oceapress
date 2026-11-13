<?php

namespace Tests\Feature;

use App\Models\Pressing;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class PageErreur403Test extends TestCase
{
    use CreeDonneesTest, RefreshDatabase;

    private const MESSAGE = 'Vous ne pouvez pas faire cette action avec le compte actuel.';

    private const SESSION = 'Votre session a peut-être changé dans un autre onglet de ce navigateur.';

    private function creerAdministrateur(): Utilisateur
    {
        $utilisateur = $this->creerUtilisateur(['email' => 'admin-403@example.test', 'prenom' => 'Fatou', 'nom' => 'Ndiaye']);
        DB::table('administrateur')->insert(['idAdministrateur' => $utilisateur->idUtilisateur]);

        return $utilisateur;
    }

    private function assertLienVersEspace(string $html, string $url): void
    {
        $this->assertMatchesRegularExpression(
            '/<a[^>]*href="'.preg_quote($url, '/').'"[^>]*>\s*Retourner à mon espace\s*<\/a>/u',
            $html,
        );
    }

    public function test_un_client_sur_une_page_employe_voit_la_page_403_en_francais(): void
    {
        $client = $this->creerClient(['prenom' => 'Awa', 'nom' => 'Diallo']);

        $reponse = $this->actingAs($client->utilisateur)->get(route('employe.accueil'));

        $reponse->assertStatus(403)
            ->assertSee(self::MESSAGE)
            ->assertSee(self::SESSION)
            ->assertSee('Awa Diallo')
            ->assertSee('Client')
            ->assertSee('data-layout="connecte"', false)
            ->assertDontSee('Forbidden');
        $this->assertLienVersEspace($reponse->getContent(), route('client.accueil'));
    }

    public function test_un_employe_sur_une_page_administrateur_est_renvoye_vers_son_espace(): void
    {
        $employe = $this->creerEmploye();

        $reponse = $this->actingAs($employe->utilisateur)->get(route('admin.accueil'));

        $reponse->assertStatus(403)->assertSee(self::MESSAGE)->assertSee('Employé');
        $this->assertLienVersEspace($reponse->getContent(), route('employe.accueil'));
    }

    public function test_un_administrateur_sur_un_pressing_qu_il_ne_gere_pas_voit_la_meme_page(): void
    {
        $administrateur = $this->creerAdministrateur();
        $pressingAutre = Pressing::create([
            'nom' => 'Pressing non géré', 'adresse' => 'Ailleurs',
            'dateCreation' => now()->toDateString(), 'statut' => Pressing::STATUT_ACTIF,
        ]);

        $reponse = $this->actingAs($administrateur)->get(route('admin.pressings.edit', $pressingAutre->idPressing));

        $reponse->assertStatus(403)
            ->assertSee(self::MESSAGE)
            ->assertSee('Fatou Ndiaye')
            ->assertSee('Administrateur')
            ->assertDontSee('Pressing non géré');
        $this->assertLienVersEspace($reponse->getContent(), route('admin.accueil'));
    }

    public function test_sans_session_la_page_403_utilise_le_gabarit_public_et_renvoie_vers_la_connexion(): void
    {
        Route::middleware('web')->get('/test-erreur-403', fn () => abort(403));

        $reponse = $this->get('/test-erreur-403');

        $reponse->assertStatus(403)
            ->assertSee(self::MESSAGE)
            ->assertSee(self::SESSION)
            ->assertSee('data-layout="oceapress"', false)
            ->assertDontSee('Compte connecté');
        $this->assertLienVersEspace($reponse->getContent(), route('login'));
    }

    public function test_une_requete_json_garde_sa_reponse_403_en_json(): void
    {
        $client = $this->creerClient();

        $this->actingAs($client->utilisateur)
            ->getJson(route('employe.clients.rechercher', ['email' => 'quelqu.un@example.test']))
            ->assertStatus(403)
            ->assertJsonStructure(['message']);
    }
}

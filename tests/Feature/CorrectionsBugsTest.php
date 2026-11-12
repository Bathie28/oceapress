<?php

namespace Tests\Feature;

use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class CorrectionsBugsTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function connecterAdministrateur(): Utilisateur
    {
        $utilisateur = $this->creerUtilisateur(['email' => 'admin-corrections@example.test']);
        DB::table('administrateur')->insert(['idAdministrateur' => $utilisateur->idUtilisateur]);
        $this->actingAs($utilisateur);

        return $utilisateur;
    }

    public function test_employe_desactive_avec_une_session_ouverte_perd_l_acces(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);
        $employe->update(['actif' => false]);

        $this->get(route('employe.accueil'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_echec_de_creation_d_employe_ne_remplit_pas_le_formulaire_du_pressing(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();
        $existant = $this->creerUtilisateur(['email' => 'deja-pris@example.test']);
        $edition = route('admin.pressings.edit', $pressing->idPressing);

        $this->from($edition)->post(route('admin.pressings.employes.store', $pressing->idPressing), [
            'nom' => 'Diop', 'prenom' => 'Moussa', 'email' => $existant->email,
            'telephone' => '771112233', 'fonction' => 'Caissier',
        ])->assertRedirect($edition);

        $html = $this->get($edition)->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*id="nom"[^>]*value="Pressing Test"/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*id="email"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*id="telephone"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*id="employe-nom"[^>]*value="Diop"/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*id="employe-email"[^>]*value="deja-pris@example.test"/', $html);
    }

    public function test_echec_de_modification_du_pressing_ne_remplit_pas_le_formulaire_employe(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();
        $edition = route('admin.pressings.edit', $pressing->idPressing);

        $this->from($edition)->put(route('admin.pressings.update', $pressing->idPressing), [
            'nom' => 'Nouveau nom', 'adresse' => '', 'email' => 'pressing@example.test',
        ])->assertRedirect($edition);

        $html = $this->get($edition)->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*id="nom"[^>]*value="Nouveau nom"/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*id="employe-nom"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*id="employe-email"[^>]*value=""/', $html);
    }

    public function test_gabarit_connecte_definit_les_classes_op_utilisees_par_les_composants(): void
    {
        $this->connecterAdministrateur();

        $html = $this->get(route('admin.pressings.index'))->assertOk()->getContent();

        foreach (['.op-button-primary', '.op-button-secondary', '.op-card', '.op-banner', '.op-badge'] as $classe) {
            $this->assertMatchesRegularExpression('/<style>.*'.preg_quote($classe, '/').'[^{]*\{.*<\/style>/s', $html, "{$classe} doit être définie dans le gabarit connecté.");
        }
    }

    public function test_boutons_desactiver_et_reactiver_ont_le_style_de_bouton(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();
        $this->creerEmploye($pressing);
        $this->creerEmploye($pressing)->update(['actif' => false]);

        $html = $this->get(route('admin.pressings.edit', $pressing->idPressing))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<button[^>]*class="[^"]*op-button-primary[^"]*"[^>]*>Désactiver<\/button>/u', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*class="[^"]*op-button-primary[^"]*"[^>]*>Réactiver<\/button>/u', $html);
    }

    public function test_mode_de_facturation_en_tableau_ne_provoque_pas_d_erreur_500(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);

        $this->get('/employe/commandes/nouvelle?modeFacturation[]=x')->assertOk();

        $this->from(route('employe.commandes.create'))->post(route('employe.commandes.store'), [
            'client' => ['email' => 'tableau@example.test', 'nom' => 'A', 'prenom' => 'B'],
            'articles' => [['type' => 'Chemise', 'quantite' => 1]],
            'modeFacturation' => ['x'],
            'formule' => 'REPASSAGE',
        ])->assertRedirect(route('employe.commandes.create'))
            ->assertSessionHasErrors('modeFacturation');
    }

    public function test_le_seeder_par_defaut_s_execute_sans_erreur(): void
    {
        $this->assertSame(0, Artisan::call('db:seed'));
    }
}

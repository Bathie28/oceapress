<?php

namespace Tests\Feature;

use App\Models\Pressing;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class AdminTarifsTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function connecterAdministrateur(): Utilisateur
    {
        $utilisateur = $this->creerUtilisateur(['email' => 'admin-tarifs@example.test']);
        DB::table('administrateur')->insert(['idAdministrateur' => $utilisateur->idUtilisateur]);
        $this->actingAs($utilisateur);

        return $utilisateur;
    }

    public function test_administrateur_voit_la_liste_et_ajoute_un_tarif(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();
        DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing, 'typeArticle' => 'Pantalon',
            'formule' => 'REPASSAGE', 'unite' => 'PIECE', 'prix' => '1500',
        ]);

        $this->get('/admin/pressings')->assertOk()
            ->assertSee($pressing->nom)
            ->assertSee('Pesée : Non')
            ->assertSee('1 tarif')
            ->assertSeeText('Ajouter un pressing')
            ->assertSee('class="op-button-primary"', false);

        $reponseTarifs = $this->get('/admin/pressings/'.$pressing->idPressing.'/tarifs');
        $reponseTarifs->assertOk()
            ->assertSee('Pantalon')
            ->assertSee('LAVAGE');

        $htmlTarifs = $reponseTarifs->getContent();
        preg_match_all('/<button\b[^>]*>Modifier le prix<\/button>/', $htmlTarifs, $boutonsModifierPrix);
        $this->assertNotEmpty($boutonsModifierPrix[0]);

        $classesModifierAttendues = 'mt-2 w-fit self-center !px-4 !py-2 !text-sm';
        foreach ($boutonsModifierPrix[0] as $bouton) {
            preg_match('/<button\b[^>]*class="([^"]*)"[^>]*>Modifier le prix<\/button>/', $bouton, $classesBouton);
            $this->assertSame($classesModifierAttendues, $classesBouton[1] ?? null);
            $this->assertStringNotContainsString('w-full', $bouton);
            $this->assertStringNotContainsString('block', $bouton);
        }

        $classesAjouterAttendues = 'mt-2 w-fit !px-4 !py-2 !text-sm';
        preg_match('/<button\b[^>]*class="([^"]*)"[^>]*>Ajouter le tarif<\/button>/', $htmlTarifs, $boutonAjouter);
        $this->assertSame($classesAjouterAttendues, $boutonAjouter[1] ?? null);
        $this->assertStringNotContainsString('self-start', $boutonAjouter[0]);

        $this->post('/admin/pressings/'.$pressing->idPressing.'/tarifs', [
            'typeArticle' => 'Chemise', 'formule' => 'LAVAGE_REPASSAGE', 'unite' => 'PIECE', 'prix' => '2250',
        ])->assertRedirect('/admin/pressings/'.$pressing->idPressing.'/tarifs');

        $this->assertDatabaseHas('tarif', [
            'idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise',
            'formule' => 'LAVAGE_REPASSAGE', 'unite' => 'PIECE', 'prix' => '2250',
        ]);
    }

    public function test_formulaire_refuse_un_doublon_et_affiche_le_message_du_service(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();
        DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise',
            'formule' => 'REPASSAGE', 'unite' => 'PIECE', 'prix' => '1000',
        ]);

        $this->from('/admin/pressings/'.$pressing->idPressing.'/tarifs')
            ->post('/admin/pressings/'.$pressing->idPressing.'/tarifs', [
                'typeArticle' => 'Chemise', 'formule' => 'REPASSAGE', 'unite' => 'PIECE', 'prix' => '1200',
            ])
            ->assertRedirect('/admin/pressings/'.$pressing->idPressing.'/tarifs')
            ->assertSessionHasErrors('tarif');

        $this->assertSame(1, DB::table('tarif')->where('idPressing', $pressing->idPressing)->count());
    }

    public function test_un_employe_qui_tente_la_liste_admin_obtient_403(): void
    {
        $employe = $this->creerEmploye();

        $this->actingAs($employe->utilisateur)->get('/admin/pressings')->assertForbidden();
    }

    public function test_un_client_qui_tente_d_ajouter_un_tarif_obtient_403(): void
    {
        $client = $this->creerClient();
        $pressing = $this->creerPressing();

        $this->actingAs($client->utilisateur)
            ->post('/admin/pressings/'.$pressing->idPressing.'/tarifs', [
                'typeArticle' => 'Chemise', 'formule' => 'REPASSAGE', 'unite' => 'PIECE', 'prix' => '1200',
            ])
            ->assertForbidden();
    }

    public function test_modifier_un_tarif_d_un_autre_pressing_renvoie_404(): void
    {
        $this->connecterAdministrateur();
        $pressingAffiche = $this->creerPressing();
        $autrePressing = $this->creerPressing();
        $idTarif = DB::table('tarif')->insertGetId([
            'idPressing' => $autrePressing->idPressing, 'typeArticle' => 'Chemise',
            'formule' => 'REPASSAGE', 'unite' => 'PIECE', 'prix' => '1000',
        ], 'idTarif');

        $this->post('/admin/tarifs/'.$idTarif, [
            'idPressing' => $pressingAffiche->idPressing,
            'prix' => '2000',
        ])->assertNotFound();

        $this->assertSame('1000', DB::table('tarif')->where('idTarif', $idTarif)->value('prix'));
    }

    public function test_grille_piece_ne_montre_pas_la_section_kg_et_formulaire_propose_repassage(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();
        DB::table('tarif')->insert([
            ['idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise', 'formule' => 'REPASSAGE', 'unite' => 'PIECE', 'prix' => '1000'],
            ['idPressing' => $pressing->idPressing, 'typeArticle' => null, 'formule' => 'LAVAGE', 'unite' => 'KG', 'prix' => '8.00'],
        ]);

        $this->get('/admin/pressings/'.$pressing->idPressing.'/tarifs')
            ->assertOk()
            ->assertSee('Grille tarifaire PIECE')
            ->assertDontSee('Grille tarifaire KG')
            ->assertSee('<option value="REPASSAGE"', false)
            ->assertSee('<option value="PIECE">PIECE</option>', false);
    }

    public function test_grille_poids_ne_montre_pas_la_section_piece(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();
        DB::table('pressing')->where('idPressing', $pressing->idPressing)->update(['pesee' => true]);
        DB::table('tarif')->insert([
            ['idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise', 'formule' => 'REPASSAGE', 'unite' => 'PIECE', 'prix' => '1000'],
            ['idPressing' => $pressing->idPressing, 'typeArticle' => null, 'formule' => 'LAVAGE', 'unite' => 'KG', 'prix' => '800'],
        ]);

        $this->get('/admin/pressings/'.$pressing->idPressing.'/tarifs')
            ->assertOk()
            ->assertSee('Grille tarifaire PIECE')
            ->assertSee('Grille tarifaire KG');
    }

    public function test_compteur_affiche_seulement_les_tarifs_du_mode_visible(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();
        DB::table('tarif')->insert([
            ['idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise', 'formule' => 'REPASSAGE', 'unite' => 'PIECE', 'prix' => 1000],
            ['idPressing' => $pressing->idPressing, 'typeArticle' => 'Pantalon', 'formule' => 'REPASSAGE', 'unite' => 'PIECE', 'prix' => 1500],
            ['idPressing' => $pressing->idPressing, 'typeArticle' => null, 'formule' => 'LAVAGE', 'unite' => 'KG', 'prix' => 800],
        ]);

        $this->get('/admin/pressings')->assertOk()->assertSee('2 tarifs');

        $pressing->update(['pesee' => true]);
        $this->get('/admin/pressings')->assertOk()->assertSee('3 tarifs');
    }
}

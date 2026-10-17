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
            'formule' => 'LAVAGE', 'unite' => 'PIECE', 'prix' => '15.00',
        ]);

        $this->get('/admin/pressings')->assertOk()
            ->assertSee($pressing->nom)
            ->assertSee('PIECE')
            ->assertSee('1 tarif');

        $this->get('/admin/pressings/'.$pressing->idPressing.'/tarifs')->assertOk()
            ->assertSee('Pantalon')
            ->assertSee('LAVAGE');

        $this->post('/admin/pressings/'.$pressing->idPressing.'/tarifs', [
            'typeArticle' => 'Chemise', 'formule' => 'LAVAGE_REPASSAGE', 'unite' => 'PIECE', 'prix' => '22.50',
        ])->assertRedirect('/admin/pressings/'.$pressing->idPressing.'/tarifs');

        $this->assertDatabaseHas('tarif', [
            'idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise',
            'formule' => 'LAVAGE_REPASSAGE', 'unite' => 'PIECE', 'prix' => '22.50',
        ]);
    }

    public function test_formulaire_refuse_un_doublon_et_affiche_le_message_du_service(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();
        DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise',
            'formule' => 'LAVAGE', 'unite' => 'PIECE', 'prix' => '10.00',
        ]);

        $this->from('/admin/pressings/'.$pressing->idPressing.'/tarifs')
            ->post('/admin/pressings/'.$pressing->idPressing.'/tarifs', [
                'typeArticle' => 'Chemise', 'formule' => 'LAVAGE', 'unite' => 'PIECE', 'prix' => '12.00',
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
                'typeArticle' => 'Chemise', 'formule' => 'LAVAGE', 'unite' => 'PIECE', 'prix' => '12.00',
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
            'formule' => 'LAVAGE', 'unite' => 'PIECE', 'prix' => '10.00',
        ], 'idTarif');

        $this->post('/admin/tarifs/'.$idTarif, [
            'idPressing' => $pressingAffiche->idPressing,
            'prix' => '20.00',
        ])->assertNotFound();

        $this->assertSame('10.00', DB::table('tarif')->where('idTarif', $idTarif)->value('prix'));
    }
}

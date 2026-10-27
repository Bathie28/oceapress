<?php

namespace Tests\Feature;

use App\Models\Pressing;
use App\Models\Utilisateur;
use App\Services\CreerPressing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class AdminPressingsTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function connecterAdministrateur(): Utilisateur
    {
        $utilisateur = $this->creerUtilisateur(['email' => 'admin-pressings@example.test']);
        DB::table('administrateur')->insert(['idAdministrateur' => $utilisateur->idUtilisateur]);
        $this->actingAs($utilisateur);

        return $utilisateur;
    }

    public function test_administrateur_cree_un_pressing_actif_qui_apparait_sans_tarif(): void
    {
        $this->connecterAdministrateur();

        $this->post(route('admin.pressings.store'), [
            'nom' => 'Pressing du Port',
            'adresse' => '4 rue du Port',
            'telephone' => '0102030405',
            'email' => 'port@example.test',
            'pesee' => '1',
        ])->assertRedirect(route('admin.pressings.index'));

        $pressing = Pressing::query()->where('nom', 'Pressing du Port')->firstOrFail();

        $this->assertSame(now()->toDateString(), $pressing->dateCreation);
        $this->assertSame(Pressing::STATUT_ACTIF, $pressing->statut);
        $this->assertTrue($pressing->pesee);
        $this->assertSame(0, $pressing->tarifs()->count());

        $this->get(route('admin.pressings.index'))
            ->assertOk()
            ->assertSee('Pressing du Port')
            ->assertSee('0 tarif');
    }

    public function test_nom_vide_est_refuse_sans_creer_de_ligne(): void
    {
        $this->connecterAdministrateur();

        $this->from(route('admin.pressings.create'))
            ->post(route('admin.pressings.store'), [
                'nom' => '',
                'adresse' => '4 rue du Port',
            ])
            ->assertRedirect(route('admin.pressings.create'))
            ->assertSessionHasErrors('nom');

        $this->assertDatabaseCount('pressing', 0);
    }

    public function test_service_refuse_un_nom_ou_une_adresse_vide_sans_ecriture(): void
    {
        $service = app(CreerPressing::class);

        foreach ([
            ['nom' => ' ', 'adresse' => '4 rue du Port'],
            ['nom' => 'Pressing du Port', 'adresse' => '  '],
        ] as $donnees) {
            try {
                $service->executer($donnees);
                $this->fail('Le service aurait dû refuser les champs vides.');
            } catch (InvalidArgumentException) {
                $this->assertDatabaseCount('pressing', 0);
            }
        }
    }

    public function test_administrateur_modifie_un_pressing_existant(): void
    {
        $this->connecterAdministrateur();
        $pressing = $this->creerPressing();

        $this->get(route('admin.pressings.edit', $pressing->idPressing))
            ->assertOk()
            ->assertSee('Pressing Test')
            ->assertSee('1 rue du Test');

        $this->put(route('admin.pressings.update', $pressing->idPressing), [
            'nom' => 'Pressing Modifié',
            'adresse' => '8 avenue du Centre',
            'telephone' => '0611223344',
            'email' => 'centre@example.test',
            'pesee' => '1',
        ])->assertRedirect(route('admin.pressings.index'));

        $this->assertDatabaseHas('pressing', [
            'idPressing' => $pressing->idPressing,
            'nom' => 'Pressing Modifié',
            'adresse' => '8 avenue du Centre',
            'telephone' => '0611223344',
            'email' => 'centre@example.test',
            'pesee' => true,
        ]);
    }

    public function test_un_employe_et_un_client_obtiennent_403_sur_les_pages_admin(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur)
            ->get(route('admin.pressings.index'))
            ->assertForbidden();

        $client = $this->creerClient();
        $this->actingAs($client->utilisateur)
            ->get(route('admin.pressings.create'))
            ->assertForbidden();
    }
}

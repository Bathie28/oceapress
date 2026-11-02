<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\Pressing;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class AdminPerimetrePressingTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function creerAdministrateur(string $email): Utilisateur
    {
        $administrateur = $this->creerUtilisateur(['email' => $email]);
        DB::table('administrateur')->insert(['idAdministrateur' => $administrateur->idUtilisateur]);

        return $administrateur;
    }

    private function rattacher(Utilisateur $administrateur, Pressing $pressing): void
    {
        DB::table('gestion_pressing')->insert([
            'idAdministrateur' => $administrateur->idUtilisateur,
            'idPressing' => $pressing->idPressing,
        ]);
    }

    public function test_administrateur_ne_voit_que_ses_pressings_sur_la_gestion(): void
    {
        $administrateur = $this->creerAdministrateur('admin-perimetre-a@example.test');
        $autre = $this->creerAdministrateur('admin-perimetre-b@example.test');
        $pressingGere = $this->creerPressing();
        $pressingAutre = Pressing::create([
            'nom' => 'Pressing Autre',
            'adresse' => '2 rue Autre',
            'dateCreation' => now()->toDateString(),
            'statut' => Pressing::STATUT_ACTIF,
        ]);
        $this->rattacher($administrateur, $pressingGere);
        $this->rattacher($autre, $pressingAutre);

        $this->actingAs($administrateur)
            ->get(route('admin.pressings.index'))
            ->assertOk()
            ->assertSee($pressingGere->nom)
            ->assertDontSee($pressingAutre->nom);
    }

    public function test_administrateur_ne_peut_pas_modifier_un_pressing_externe(): void
    {
        $administrateur = $this->creerAdministrateur('admin-edit-a@example.test');
        $autre = $this->creerAdministrateur('admin-edit-b@example.test');
        $pressingAutre = $this->creerPressing();
        $this->rattacher($autre, $pressingAutre);

        $this->actingAs($administrateur)
            ->get(route('admin.pressings.edit', $pressingAutre->idPressing))
            ->assertForbidden();
    }

    public function test_creation_d_un_pressing_rattache_son_administrateur(): void
    {
        $administrateur = $this->creerAdministrateur('admin-create@example.test');

        $this->actingAs($administrateur)->post(route('admin.pressings.store'), [
            'nom' => 'Pressing Créé',
            'adresse' => '10 avenue Création',
        ])->assertRedirect(route('admin.pressings.index'));

        $pressing = Pressing::query()->where('nom', 'Pressing Créé')->firstOrFail();
        $this->assertDatabaseHas('gestion_pressing', [
            'idAdministrateur' => $administrateur->idUtilisateur,
            'idPressing' => $pressing->idPressing,
        ]);
    }

    public function test_backfill_rattache_les_pressings_existants_au_premier_administrateur(): void
    {
        $premier = $this->creerAdministrateur('admin-backfill-a@example.test');
        $second = $this->creerAdministrateur('admin-backfill-b@example.test');
        $pressing = $this->creerPressing();
        DB::table('gestion_pressing')->delete();

        $migration = require database_path('migrations/2026_11_02_000001_create_gestion_pressing_table.php');
        $migration->rattacherPressingsExistants();

        $this->assertDatabaseHas('gestion_pressing', [
            'idAdministrateur' => $premier->idUtilisateur,
            'idPressing' => $pressing->idPressing,
        ]);
        $this->assertDatabaseMissing('gestion_pressing', [
            'idAdministrateur' => $second->idUtilisateur,
            'idPressing' => $pressing->idPressing,
        ]);
    }

    public function test_administrateur_ne_desactive_pas_un_employe_externe(): void
    {
        $administrateur = $this->creerAdministrateur('admin-desactiver-a@example.test');
        $autre = $this->creerAdministrateur('admin-desactiver-b@example.test');
        $pressingAutre = $this->creerPressing();
        $this->rattacher($autre, $pressingAutre);
        $employe = $this->creerEmploye($pressingAutre);

        $this->actingAs($administrateur)
            ->post(route('admin.pressings.employes.desactiver', [$pressingAutre->idPressing, $employe->idEmploye]))
            ->assertForbidden();
        $this->assertTrue((bool) $employe->fresh()->actif);
    }

    public function test_administrateur_reactive_un_employe_de_son_pressing(): void
    {
        $administrateur = $this->creerAdministrateur('admin-reactiver@example.test');
        $pressing = $this->creerPressing();
        $this->rattacher($administrateur, $pressing);
        $employe = $this->creerEmploye($pressing);
        $employe->update(['actif' => false]);

        $this->actingAs($administrateur)
            ->post(route('admin.pressings.employes.reactiver', [$pressing->idPressing, $employe->idEmploye]))
            ->assertRedirect();
        $this->assertTrue((bool) $employe->fresh()->actif);
    }

    public function test_administrateur_ne_ajoute_pas_un_employe_a_un_pressing_externe(): void
    {
        $administrateur = $this->creerAdministrateur('admin-ajout-a@example.test');
        $autre = $this->creerAdministrateur('admin-ajout-b@example.test');
        $pressingAutre = $this->creerPressing();
        $this->rattacher($autre, $pressingAutre);

        $this->actingAs($administrateur)
            ->post(route('admin.pressings.employes.store', $pressingAutre->idPressing), [
                'nom' => 'Refusé',
                'prenom' => 'Employé',
                'email' => 'refuse@example.test',
                'fonction' => 'Agent',
            ])
            ->assertForbidden();
        $this->assertDatabaseMissing('utilisateur', ['email' => 'refuse@example.test']);
    }

    public function test_pressings_externes_absents_accueil_et_liste(): void
    {
        $administrateur = $this->creerAdministrateur('admin-accueil-a@example.test');
        $autre = $this->creerAdministrateur('admin-accueil-b@example.test');
        $pressingGere = $this->creerPressing();
        $pressingAutre = Pressing::create([
            'nom' => 'Pressing Externe Secret',
            'adresse' => '9 rue Externe',
            'dateCreation' => now()->toDateString(),
            'statut' => Pressing::STATUT_ACTIF,
        ]);
        $this->rattacher($administrateur, $pressingGere);
        $this->rattacher($autre, $pressingAutre);

        $this->actingAs($administrateur)
            ->get(route('admin.accueil'))
            ->assertOk()
            ->assertSee($pressingGere->nom)
            ->assertDontSee($pressingAutre->nom);

        $this->actingAs($administrateur)
            ->get(route('admin.pressings.index'))
            ->assertOk()
            ->assertSee($pressingGere->nom)
            ->assertDontSee($pressingAutre->nom);
    }
}

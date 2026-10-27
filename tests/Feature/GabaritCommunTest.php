<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class GabaritCommunTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    public function test_page_de_connexion_utilise_le_gabarit_et_preserve_ses_champs(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('data-layout="oceapress"', false)
            ->assertSee('OceaPress')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('Se connecter');
    }

    public function test_formulaire_de_connexion_utilise_les_classes_communes_de_champ_et_libelle(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('class="block mb-1 text-sm font-medium text-gray-700"', false)
            ->assertSee('class="block w-full rounded-xl border border-gray-300 px-3 py-2 focus:border-blue-600 focus:outline-none"', false);
    }

    public function test_accueil_employe_utilise_le_gabarit_et_sa_navigation(): void
    {
        $employe = $this->creerEmploye();

        $this->actingAs($employe->utilisateur)->get('/employe')->assertOk()
            ->assertSee('data-layout="oceapress"', false)
            ->assertSee('OP')
            ->assertSee('OceaPress')
            ->assertSee('href="'.route('employe.commandes.index').'"', false)
            ->assertSee('href="'.route('employe.commandes.create').'"', false)
            ->assertSee('href="'.route('employe.scan').'"', false)
            ->assertSee('href="'.route('employe.tableau-de-bord').'"', false);
    }

    public function test_page_d_accueil_publique_utilise_le_gabarit(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('data-layout="oceapress"', false)
            ->assertSee('OceaPress')
            ->assertSee('href="'.route('login').'"', false);
    }
}

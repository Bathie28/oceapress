<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class AffichageMotDePasseTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    public function test_pages_de_mot_de_passe_contiennent_un_bouton_icone_afficher(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('aria-label="Afficher le mot de passe"', false)
            ->assertSee('aria-pressed="false"', false)
            ->assertSee('<svg', false)
            ->assertDontSee('>Afficher</button>', false);

        $utilisateur = $this->creerUtilisateur(['email' => 'reset-visibilite@example.test']);
        $token = Password::broker()->createToken($utilisateur);
        $this->get(route('password.reset', $token))->assertOk()
            ->assertSee('aria-label="Afficher le mot de passe"', false)
            ->assertSee('aria-pressed="false"', false)
            ->assertSee('<svg', false)
            ->assertDontSee('>Afficher</button>', false);

        $client = $this->creerClient();
        $this->actingAs($client->utilisateur)
            ->get(route('client.mot-de-passe.edit'))
            ->assertOk()
            ->assertSee('aria-label="Afficher le mot de passe"', false)
            ->assertSee('aria-pressed="false"', false)
            ->assertSee('<svg', false)
            ->assertDontSee('>Afficher</button>', false);

        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur)
            ->get(route('employe.mot-de-passe.edit'))
            ->assertOk()
            ->assertSee('aria-label="Afficher le mot de passe"', false)
            ->assertSee('aria-pressed="false"', false)
            ->assertSee('<svg', false)
            ->assertDontSee('>Afficher</button>', false);
    }
}

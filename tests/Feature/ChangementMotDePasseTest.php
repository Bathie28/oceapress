<?php

namespace Tests\Feature;

use App\Models\Utilisateur;
use App\Services\CreerCompteClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class ChangementMotDePasseTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    public function test_creation_d_un_compte_client_genere_un_mot_de_passe_provisoire_hashé(): void
    {
        $compte = app(CreerCompteClient::class)->executer([
            'nom' => 'Client', 'prenom' => 'Test', 'email' => 'provisoire@example.test',
        ]);

        $this->assertNotEmpty($compte['motDePasseProvisoire']);
        $this->assertTrue(Hash::check($compte['motDePasseProvisoire'], $compte['utilisateur']->motDePasse));
        $this->assertNotSame($compte['motDePasseProvisoire'], $compte['utilisateur']->motDePasse);
        $this->assertTrue((bool) $compte['utilisateur']->doitChangerMotDePasse);
        $this->assertNotNull($compte['client']);
    }

    public function test_connexion_force_le_changement_puis_le_changement_libere_les_pages_client(): void
    {
        $compte = app(CreerCompteClient::class)->executer([
            'nom' => 'Client', 'prenom' => 'Test', 'email' => 'force@example.test',
        ]);

        $this->post('/login', ['email' => 'force@example.test', 'password' => $compte['motDePasseProvisoire']])
            ->assertRedirect(route('client.mot-de-passe.edit'));
        $this->get('/client/commandes')->assertRedirect(route('client.mot-de-passe.edit'));
        $this->get('/client/mot-de-passe')->assertOk();
        $this->post('/client/mot-de-passe', [
            'password' => 'nouveau-secret-123', 'password_confirmation' => 'nouveau-secret-123',
        ])->assertRedirect('/client');
        $this->assertFalse((bool) $compte['utilisateur']->fresh()->doitChangerMotDePasse);
        $this->get('/client')->assertOk();
    }

    public function test_refuse_un_mot_de_passe_trop_court(): void
    {
        $compte = app(CreerCompteClient::class)->executer([
            'nom' => 'Client', 'prenom' => 'Test', 'email' => 'court@example.test',
        ]);
        $this->actingAs($compte['utilisateur'])
            ->post('/client/mot-de-passe', ['password' => 'court', 'password_confirmation' => 'court'])
            ->assertSessionHasErrors('password');
        $this->assertTrue((bool) $compte['utilisateur']->fresh()->doitChangerMotDePasse);
    }

    public function test_un_client_ne_peut_pas_voir_la_commande_d_un_autre_client(): void
    {
        $client = $this->creerClient();
        $autre = $this->creerClient();
        $commande = $this->creerCommande($autre);

        $this->actingAs($client->utilisateur)->get('/client/commandes/'.$commande->idCommande)->assertNotFound();
    }
}

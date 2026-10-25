<?php

namespace Tests\Feature;

use Tests\Support\CreeDonneesTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RechercheClientNouvelleCommandeTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    public function test_recherche_par_email_renvoie_les_champs_du_client_pour_pre_remplissage(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        $client = $this->creerClient([
            'prenom' => 'Awa', 'nom' => 'Diop', 'email' => 'awa@example.test', 'telephone' => '77 123 45 67',
        ]);
        $this->creerCommande($client, $pressing);

        $this->actingAs($employe->utilisateur)
            ->getJson('/employe/clients/rechercher?email=awa%40example.test')
            ->assertOk()
            ->assertExactJson([
                'prenom' => 'Awa', 'nom' => 'Diop', 'email' => 'awa@example.test', 'telephone' => '77 123 45 67',
            ]);

        $html = $this->actingAs($employe->utilisateur)
            ->get('/employe/commandes/nouvelle')
            ->assertOk()
            ->assertSee('id="email"', false)
            ->assertSee('id="telephone"', false)
            ->assertSee('id="prenom"', false)
            ->assertSee('id="nom"', false)
            ->getContent();
        $this->assertStringContainsString("emailInput.value = client.email || ''", $html);
        $this->assertStringContainsString("document.getElementById('telephone').value = client.telephone || ''", $html);
        $this->assertStringContainsString("document.getElementById('prenom').value = client.prenom || ''", $html);
        $this->assertStringContainsString("document.getElementById('nom').value = client.nom || ''", $html);
        $this->assertStringContainsString('clientInputs.forEach(input => { input.readOnly = true; })', $html);
        $this->assertStringContainsString("editClientButton.addEventListener('click'", $html);
    }

    public function test_recherche_sans_resultat_conserve_des_champs_vides_et_modifiables(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);

        $this->actingAs($employe->utilisateur)
            ->getJson('/employe/clients/rechercher?email=inconnu%40example.test')
            ->assertNotFound();

        $html = $this->actingAs($employe->utilisateur)->get('/employe/commandes/nouvelle')->assertOk()->getContent();
        foreach (['email', 'telephone', 'prenom', 'nom'] as $id) {
            $this->assertMatchesRegularExpression('/<input[^>]*id="'.$id.'"[^>]*value=""[^>]*>/s', $html);
            $this->assertDoesNotMatchRegularExpression('/<input[^>]*id="'.$id.'"[^>]*readonly[^>]*>/s', $html);
        }
    }

    public function test_client_d_un_autre_pressing_ne_renvoie_que_nom_et_telephone(): void
    {
        $client = $this->creerClient([
            'prenom' => 'Mina', 'nom' => 'Ba', 'email' => 'mina@example.test', 'telephone' => '77 000 00 03',
        ]);
        $this->creerCommande($client, $this->creerPressing());
        $employe = $this->creerEmploye($this->creerPressing());

        $this->actingAs($employe->utilisateur)
            ->getJson('/employe/clients/rechercher?email=mina%40example.test')
            ->assertOk()
            ->assertExactJson(['nom' => 'Mina Ba', 'telephone' => '77 000 00 03']);
    }

    public function test_modifier_email_annule_le_client_recherche_et_reactive_les_champs(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        $client = $this->creerClient([
            'prenom' => 'Awa', 'nom' => 'Diop', 'email' => 'awa@example.test', 'telephone' => '77 123 45 67',
        ]);
        $this->creerCommande($client, $pressing);
        $html = $this->actingAs($employe->utilisateur)->get('/employe/commandes/nouvelle')->assertOk()->getContent();

        $this->assertStringContainsString("emailInput.addEventListener('input'", $html);
        $this->assertStringContainsString('clearSelectedClient()', $html);
        $this->assertStringContainsString("input.readOnly = false", $html);
    }
}

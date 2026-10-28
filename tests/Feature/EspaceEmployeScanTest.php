<?php

namespace Tests\Feature;

use App\Models\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class EspaceEmployeScanTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private const MESSAGE_REFUS = 'QR invalide ou commande introuvable';

    public function test_employe_scannant_un_token_de_son_pressing_est_redirige_vers_la_commande(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);
        $commande = $this->creerCommande(null, $employe->pressing);
        $token = 'token-prive-'.bin2hex(random_bytes(8));
        QrCode::create(['token' => $token, 'actif' => true, 'idCommande' => $commande->idCommande]);

        $this->post('/employe/scan', ['token' => $token])
            ->assertRedirect(route('employe.commandes.show', $commande->idCommande));
    }

    public function test_token_d_un_autre_pressing_affiche_le_message_commun_sans_reveler_la_commande(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);
        $autrePressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $autrePressing);
        $token = 'token-autre-pressing-'.bin2hex(random_bytes(8));
        QrCode::create(['token' => $token, 'actif' => true, 'idCommande' => $commande->idCommande]);

        $response = $this->from('/employe/scan')->post('/employe/scan', ['token' => $token]);
        $response
            ->assertRedirect('/employe/scan')
            ->assertSessionHasErrors(['token' => self::MESSAGE_REFUS]);
        $this->assertStringNotContainsString((string) $commande->idCommande, $response->headers->get('Location'));
    }

    public function test_un_client_qui_tente_la_page_de_scan_obtient_403(): void
    {
        $client = $this->creerClient();
        $this->actingAs($client->utilisateur);

        $this->get('/employe/scan')->assertForbidden();
    }

    public function test_requete_de_scan_sans_token_est_refusee_par_validation(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);

        $this->from('/employe/scan')->post('/employe/scan', [])
            ->assertRedirect('/employe/scan')
            ->assertSessionHasErrors('token');
    }

    public function test_token_n_est_pas_dans_l_url_de_redirection_apres_un_scan_reussi(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);
        $commande = $this->creerCommande(null, $employe->pressing);
        $token = 'token-ne-doit-pas-etre-dans-url-'.bin2hex(random_bytes(8));
        QrCode::create(['token' => $token, 'actif' => true, 'idCommande' => $commande->idCommande]);

        $response = $this->post('/employe/scan', ['token' => $token]);

        $response->assertRedirect(route('employe.commandes.show', $commande->idCommande));
        $this->assertStringNotContainsString($token, $response->headers->get('Location'));
    }

    public function test_page_de_scan_contient_le_lecteur_camera_le_champ_manuel_et_le_formulaire_post(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);

        $this->get('/employe/scan')->assertOk()
            ->assertSee('id="qrcode-reader"', false)
            ->assertSee('name="token-manuel"', false)
            ->assertSee('name="token"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('html5-qrcode@2.3.8')
            ->assertSee('facingMode')
            ->assertSee('Caméra indisponible : saisissez le code');
    }

    public function test_bouton_rechercher_la_commande_a_un_espace_superieur(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);

        $this->get('/employe/scan')->assertOk()
            ->assertSee('mt-3 block rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white', false);
    }

    public function test_accueil_employe_propose_le_lien_vers_le_scanner(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);

        $this->get('/employe')->assertOk()
            ->assertSee('Scanner un QR code')
            ->assertSee('href="'.route('employe.scan').'"', false);
    }
}

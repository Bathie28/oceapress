<?php

namespace Tests\Feature;

use App\Models\QrCode;
use App\Services\GenererImageQrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class QrCodeClientTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    public function test_le_client_voit_le_qr_actif_de_sa_commande_et_pas_le_qr_inactif(): void
    {
        $client = $this->creerClient();
        $commande = $this->creerCommande($client);
        $tokenInactif = 'ancien-token-inactif-'.bin2hex(random_bytes(6));
        $tokenActif = 'token-actif-'.bin2hex(random_bytes(6));
        QrCode::create(['token' => $tokenInactif, 'actif' => false, 'idCommande' => $commande->idCommande]);
        QrCode::create(['token' => $tokenActif, 'actif' => true, 'idCommande' => $commande->idCommande]);

        $svgActif = app(GenererImageQrCode::class)->executer($tokenActif);
        $svgInactif = app(GenererImageQrCode::class)->executer($tokenInactif);
        $response = $this->actingAs($client->utilisateur)->get('/client/commandes/'.$commande->idCommande);

        $response->assertOk()
            ->assertSee('Votre QR Code')
            ->assertSee($commande->referenceCommande)
            ->assertSee('Agrandir le QR Code')
            ->assertSee('<dialog', false)
            ->assertSee($svgActif, false)
            ->assertDontSee($svgInactif, false);
    }

    public function test_un_client_ne_peut_pas_afficher_le_qr_d_une_commande_etrangere(): void
    {
        $clientA = $this->creerClient();
        $clientB = $this->creerClient();
        $commandeB = $this->creerCommande($clientB);
        QrCode::create(['token' => 'token-prive-'.bin2hex(random_bytes(6)), 'actif' => true, 'idCommande' => $commandeB->idCommande]);

        $this->actingAs($clientA->utilisateur)
            ->get('/client/commandes/'.$commandeB->idCommande)
            ->assertNotFound();
    }

    public function test_la_page_ne_place_jamais_le_token_dans_une_url(): void
    {
        $client = $this->creerClient();
        $commande = $this->creerCommande($client);
        $token = 'secret-qr-token-'.bin2hex(random_bytes(8));
        QrCode::create(['token' => $token, 'actif' => true, 'idCommande' => $commande->idCommande]);

        $html = $this->actingAs($client->utilisateur)
            ->get('/client/commandes/'.$commande->idCommande)
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString($token, $html);
        preg_match_all('/(?:href|src)="([^"]+)"/i', $html, $urlMatches);
        foreach ($urlMatches[1] as $url) {
            $this->assertStringNotContainsString($token, html_entity_decode($url));
        }
    }

    public function test_service_genere_un_svg_valide_sans_fichier_et_encode_le_token(): void
    {
        $token = 'token-de-test-decodable-1234567890';
        $svg = app(GenererImageQrCode::class)->executer($token);
        $document = new \DOMDocument();

        $this->assertTrue($document->loadXML($svg));
        $this->assertSame('svg', $document->documentElement?->localName);
        $this->assertGreaterThan(0, $document->getElementsByTagName('path')->length);
        $this->assertNotSame($svg, app(GenererImageQrCode::class)->executer($token.'-different'));
        $this->assertFileDoesNotExist(storage_path('app/public/'.$token.'.svg'));
    }

    public function test_sans_qr_actif_la_page_affiche_le_message_indisponible(): void
    {
        $client = $this->creerClient();
        $commande = $this->creerCommande($client);
        QrCode::create(['token' => 'token-inactif-'.bin2hex(random_bytes(6)), 'actif' => false, 'idCommande' => $commande->idCommande]);

        $this->actingAs($client->utilisateur)
            ->get('/client/commandes/'.$commande->idCommande)
            ->assertOk()
            ->assertSee('QR Code indisponible, contactez le pressing');
    }
}

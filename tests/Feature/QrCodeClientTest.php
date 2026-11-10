<?php

namespace Tests\Feature;

use App\Models\QrCode;
use App\Services\GenererImageQrCode;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ConstruitCodeQrTest;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class QrCodeClientTest extends TestCase
{
    use ConstruitCodeQrTest, CreeDonneesTest, RefreshDatabase;

    public function test_la_serialisation_qr_ne_contient_pas_le_token(): void
    {
        $client = $this->creerClient();
        $commande = $this->creerCommande($client);
        $token = $this->codeQr($commande->idCommande);
        $qrCode = QrCode::create([
            'token' => $token,
            'actif' => true,
            'idCommande' => $commande->idCommande,
        ]);

        $this->assertArrayNotHasKey('token', $qrCode->toArray());
    }

    public function test_le_client_voit_le_qr_actif_de_sa_commande_et_pas_le_qr_inactif(): void
    {
        $client = $this->creerClient();
        $commande = $this->creerCommande($client);
        $tokenInactif = $this->codeQr($commande->idCommande, now()->subMinutes(2));
        $tokenActif = $this->codeQr($commande->idCommande);
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

        $html = $response->getContent();
        $this->assertStringContainsString('class="flex justify-center', $html);
        $this->assertStringContainsString('!bg-white', $html);
        $this->assertStringContainsString('!text-slate-900', $html);
        $this->assertStringNotContainsString('rounded-full', $html);
    }

    public function test_un_client_ne_peut_pas_afficher_le_qr_d_une_commande_etrangere(): void
    {
        $clientA = $this->creerClient();
        $clientB = $this->creerClient();
        $commandeB = $this->creerCommande($clientB);
        QrCode::create(['token' => $this->codeQr($commandeB->idCommande), 'actif' => true, 'idCommande' => $commandeB->idCommande]);

        $this->actingAs($clientA->utilisateur)
            ->get('/client/commandes/'.$commandeB->idCommande)
            ->assertNotFound();
    }

    public function test_la_page_ne_place_jamais_le_token_dans_une_url(): void
    {
        $client = $this->creerClient();
        $commande = $this->creerCommande($client);
        $token = $this->codeQr($commande->idCommande);
        QrCode::create(['token' => $token, 'actif' => true, 'idCommande' => $commande->idCommande]);

        $html = $this->actingAs($client->utilisateur)
            ->get('/client/commandes/'.$commande->idCommande)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($token, $html);
        preg_match_all('/(?:href|src)="([^"]+)"/i', $html, $urlMatches);
        foreach ($urlMatches[1] as $url) {
            $this->assertStringNotContainsString($token, html_entity_decode($url));
        }
    }

    public function test_service_genere_un_svg_valide_sans_fichier_et_encode_le_token(): void
    {
        $token = 'token-de-test-decodable-1234567890';
        $svg = app(GenererImageQrCode::class)->executer($token);
        $document = new \DOMDocument;

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

    public function test_la_page_client_affiche_le_code_courant_et_regenere_le_qr_a_chaque_tranche(): void
    {
        $client = $this->creerClient();
        $commande = $this->creerCommande($client);
        $instantAvantChangement = CarbonImmutable::parse('2026-11-10 10:01:59 UTC');
        $instantApresChangement = CarbonImmutable::parse('2026-11-10 10:02:00 UTC');
        $codePrecedent = $this->codeQr($commande->idCommande, $instantAvantChangement);
        $codeCourant = $this->codeQr($commande->idCommande, $instantApresChangement);
        QrCode::create(['token' => $codePrecedent, 'actif' => true, 'idCommande' => $commande->idCommande]);
        $this->travelTo($instantAvantChangement);

        $svgPrecedent = app(GenererImageQrCode::class)->executer($codePrecedent);
        $htmlAvant = $this->actingAs($client->utilisateur)
            ->get('/client/commandes/'.$commande->idCommande)
            ->assertOk()
            ->assertSee($svgPrecedent, false)
            ->assertSee($codePrecedent)
            ->getContent();

        $this->travelTo($instantApresChangement);
        $svgCourant = app(GenererImageQrCode::class)->executer($codeCourant);
        $htmlApres = $this->get('/client/commandes/'.$commande->idCommande)
            ->assertOk()
            ->assertSee($svgCourant, false)
            ->assertSee($codeCourant)
            ->assertDontSee($svgPrecedent, false)
            ->getContent();

        $this->assertNotSame($svgPrecedent, $svgCourant);
        $this->assertStringContainsString('setInterval(() => window.location.reload(), 120000)', $htmlAvant);
        $this->assertStringContainsString('setInterval(() => window.location.reload(), 120000)', $htmlApres);
    }
}

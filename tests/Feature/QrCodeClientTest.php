<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\QrCode;
use App\Services\ChangerStatutCommande;
use App\Services\GenererImageQrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class QrCodeClientTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    public function test_la_serialisation_qr_ne_contient_pas_le_token(): void
    {
        $client = $this->creerClient();
        $commande = $this->creerCommande($client);
        $qrCode = QrCode::create([
            'token' => 'token-secret-'.bin2hex(random_bytes(8)),
            'actif' => true,
            'idCommande' => $commande->idCommande,
        ]);

        $this->assertArrayNotHasKey('token', $qrCode->toArray());
    }

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

        $this->assertSame(1, substr_count($html, $token), 'Le token ne figure que dans la ligne « Code de secours ».');
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

    public function test_la_page_client_affiche_le_meme_qr_fixe_dans_le_temps_sans_rechargement_automatique(): void
    {
        $client = $this->creerClient();
        $commande = $this->creerCommande($client);
        $token = 'token-fixe-'.bin2hex(random_bytes(8));
        QrCode::create(['token' => $token, 'actif' => true, 'idCommande' => $commande->idCommande]);
        $svgAttendu = app(GenererImageQrCode::class)->executer($token);

        $htmlAvant = $this->actingAs($client->utilisateur)
            ->get('/client/commandes/'.$commande->idCommande)
            ->assertOk()
            ->assertSee($svgAttendu, false)
            ->getContent();

        $this->travelTo(now()->addMinutes(10));
        $htmlApres = $this->get('/client/commandes/'.$commande->idCommande)
            ->assertOk()
            ->assertSee($svgAttendu, false)
            ->getContent();

        foreach ([$htmlAvant, $htmlApres] as $html) {
            $this->assertStringNotContainsString('location.reload', $html);
            $this->assertStringNotContainsString('Code actuel', $html);
        }
        $this->assertSame($token, QrCode::where('idCommande', $commande->idCommande)->where('actif', true)->value('token'));
    }

    public function test_commande_active_affiche_le_code_de_secours_sous_le_qr_code(): void
    {
        $client = $this->creerClient();
        $commande = $this->creerCommande($client);
        $token = 'code-secours-'.bin2hex(random_bytes(8));
        QrCode::create(['token' => $token, 'actif' => true, 'idCommande' => $commande->idCommande]);

        $html = $this->actingAs($client->utilisateur)
            ->get('/client/commandes/'.$commande->idCommande)
            ->assertOk()
            ->assertSee('Code de secours (saisie manuelle au comptoir)')
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<p[^>]*class="[^"]*text-xs[^"]*"[^>]*>\s*Code de secours \(saisie manuelle au comptoir\)\s*:\s*<code[^>]*data-code-secours[^>]*>'.preg_quote($token, '/').'<\/code>\s*<\/p>/',
            $html,
        );
        $this->assertSame(1, substr_count($html, $token), 'Le token ne doit apparaître qu’une fois, dans la ligne de secours.');
        $this->assertGreaterThan(strpos($html, 'id="agrandir-qr"'), strpos($html, 'data-code-secours'), 'La ligne doit se trouver sous le QR Code.');
    }

    public function test_commande_recuperee_n_affiche_plus_le_code_de_secours(): void
    {
        $client = $this->creerClient();
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        $commande = $this->creerCommande($client, $pressing, Commande::STATUT_PRETE);
        $token = 'code-secours-'.bin2hex(random_bytes(8));
        QrCode::create(['token' => $token, 'actif' => true, 'idCommande' => $commande->idCommande]);
        $url = '/client/commandes/'.$commande->idCommande;

        $this->actingAs($client->utilisateur)->get($url)->assertOk()->assertSee($token);

        app(ChangerStatutCommande::class)->executer($commande->idCommande, Commande::STATUT_RECUPEREE, $employe->idEmploye);

        $html = $this->get($url)->assertOk()->getContent();

        $this->assertStringNotContainsString($token, $html);
        $this->assertStringNotContainsString('Code de secours', $html);
        $this->assertStringNotContainsString('data-code-secours', $html);
        $this->assertStringContainsString('QR Code indisponible, contactez le pressing', $html);
    }

    public function test_le_code_de_secours_n_apparait_dans_aucune_url_ni_redirection(): void
    {
        $client = $this->creerClient();
        $commande = $this->creerCommande($client);
        $token = 'code-secours-'.bin2hex(random_bytes(8));
        QrCode::create(['token' => $token, 'actif' => true, 'idCommande' => $commande->idCommande]);

        $reponse = $this->actingAs($client->utilisateur)->get('/client/commandes/'.$commande->idCommande)->assertOk();
        $html = $reponse->getContent();

        preg_match_all('/\b(?:href|src|action|formaction|poster|content|data-[a-z-]*url)\s*=\s*"([^"]*)"/i', $html, $attributs);
        $this->assertNotEmpty($attributs[1]);
        foreach ($attributs[1] as $valeur) {
            $this->assertStringNotContainsString($token, html_entity_decode($valeur), 'Le token ne doit figurer dans aucune URL de la page.');
        }

        preg_match_all('/<a\b[^>]*>.*?<\/a>/s', $html, $liens);
        foreach ($liens[0] as $lien) {
            $this->assertStringNotContainsString($token, $lien, 'Le token ne doit figurer dans aucun lien.');
        }

        preg_match_all('/<script\b[^>]*>.*?<\/script>/s', $html, $scripts);
        foreach ($scripts[0] as $script) {
            $this->assertStringNotContainsString($token, $script, 'Le token ne doit figurer dans aucun script.');
        }

        foreach ($reponse->headers->all() as $valeurs) {
            foreach ($valeurs as $valeur) {
                $this->assertStringNotContainsString($token, (string) $valeur, 'Le token ne doit figurer dans aucun en-tête de réponse.');
            }
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Facture;
use App\Models\Paiement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class WavePaiementTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://oceapress.test']);
        config(['services.wave.api_key' => 'test-wave-api-key']);
        config(['services.wave.api_url' => 'https://api.wave.test']);
        config(['services.wave.webhook_secret' => 'test-wave-webhook-secret']);
        Http::preventStrayRequests();
    }

    private function creerFacture(Client $client, string $statut = 'EN_ATTENTE'): Facture
    {
        $commande = $this->creerCommande($client);

        return Facture::create([
            'numeroFacture' => 'FAC-'.strtoupper(bin2hex(random_bytes(6))),
            'montantTotal' => 2751,
            'statut' => $statut,
            'idCommande' => $commande->idCommande,
        ]);
    }

    private function webhook(array $event, bool $signatureValide = true)
    {
        $body = json_encode($event, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.$body, (string) config('services.wave.webhook_secret'));

        return $this->call('POST', '/webhooks/wave', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_WAVE_SIGNATURE' => 't='.$timestamp.',v1='.($signatureValide ? $signature : str_repeat('0', 64)),
        ], $body);
    }

    public function test_creation_de_session_wave_enregistre_la_reference_et_redirige_vers_wave(): void
    {
        Http::fake(['*/v1/checkout/sessions' => Http::response([
            'id' => 'cos-test-session-101',
            'wave_launch_url' => 'https://pay.wave.com/c/cos-test-session-101',
        ], 200)]);

        $client = $this->creerClient();
        $facture = $this->creerFacture($client);

        $this->actingAs($client->utilisateur)
            ->post('/client/factures/'.$facture->idFacture.'/paiement', ['methode' => 'WAVE'])
            ->assertRedirect('https://pay.wave.com/c/cos-test-session-101');

        $paiement = Paiement::where('idFacture', $facture->idFacture)->sole();
        $this->assertSame('cos-test-session-101', $paiement->referencePaiement);
        $this->assertSame('WAVE', $paiement->methode);
        $this->assertSame('EN_ATTENTE', $paiement->statut);
        $this->assertSame('EN_ATTENTE', $facture->fresh()->statut);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/checkout/sessions')
            && $request['amount'] === '2751'
            && $request['currency'] === 'XOF'
            && $request['client_reference'] !== ''
            && str_starts_with($request['success_url'], 'https://')
            && str_starts_with($request['error_url'], 'https://'));
    }

    public function test_erreur_api_wave_ne_conserve_aucun_paiement_en_attente(): void
    {
        Http::fake(['*/v1/checkout/sessions' => Http::response(['error' => 'unavailable'], 503)]);
        $client = $this->creerClient();
        $facture = $this->creerFacture($client);

        $this->from('/client/commandes/'.$facture->idCommande)
            ->actingAs($client->utilisateur)
            ->post('/client/factures/'.$facture->idFacture.'/paiement', ['methode' => 'WAVE'])
            ->assertRedirect('/client/commandes/'.$facture->idCommande);

        $this->assertSame(0, Paiement::where('idFacture', $facture->idFacture)->count());
        $this->assertSame('EN_ATTENTE', $facture->fresh()->statut);
    }

    public function test_absence_de_cle_wave_affiche_indisponible_sans_creer_de_paiement(): void
    {
        config(['services.wave.api_key' => '']);
        $client = $this->creerClient();
        $facture = $this->creerFacture($client);

        $this->from('/client/commandes/'.$facture->idCommande)
            ->actingAs($client->utilisateur)
            ->post('/client/factures/'.$facture->idFacture.'/paiement', ['methode' => 'WAVE'])
            ->assertRedirect('/client/commandes/'.$facture->idCommande)
            ->assertSessionHasErrors([
                'paiement' => 'Paiement en ligne indisponible pour le moment',
            ]);

        $this->assertSame(0, Paiement::where('idFacture', $facture->idFacture)->count());
        $this->assertSame('EN_ATTENTE', $facture->fresh()->statut);
    }

    public function test_webhook_valide_confirme_facture_paiement_et_notification(): void
    {
        $client = $this->creerClient();
        $facture = $this->creerFacture($client);
        $paiement = app(\App\Services\EnregistrerPaiement::class)
            ->executer($facture->idFacture, 'WAVE', 'EN_ATTENTE', 2751);
        $paiement->update(['referencePaiement' => 'cos-test-session-201']);

        $this->webhook([
            'id' => 'EV_test_success_201',
            'type' => 'checkout.session.completed',
            'data' => ['id' => 'cos-test-session-201', 'payment_status' => 'succeeded', 'checkout_status' => 'complete'],
        ])->assertOk();

        $this->assertSame('PAYEE', $facture->fresh()->statut);
        $this->assertSame('CONFIRME', $paiement->fresh()->statut);
        $this->assertDatabaseHas('notification', [
            'idClient' => $client->idClient,
            'canal' => 'IN_APP',
            'type' => 'PAIEMENT_CONFIRME',
            'message' => 'Le paiement de votre facture '.$facture->numeroFacture.' a été confirmé.',
        ]);
    }

    public function test_signature_invalide_est_refusee_sans_ecriture(): void
    {
        $client = $this->creerClient();
        $facture = $this->creerFacture($client);
        $paiement = app(\App\Services\EnregistrerPaiement::class)
            ->executer($facture->idFacture, 'WAVE', 'EN_ATTENTE', 2751);
        $paiement->update(['referencePaiement' => 'cos-test-session-301']);

        $this->webhook([
            'id' => 'EV_test_invalid_301',
            'type' => 'checkout.session.completed',
            'data' => ['id' => 'cos-test-session-301', 'payment_status' => 'succeeded'],
        ], false)->assertForbidden();

        $this->assertSame('EN_ATTENTE', $paiement->fresh()->statut);
        $this->assertSame('EN_ATTENTE', $facture->fresh()->statut);
        $this->assertDatabaseCount('notification', 0);
        $this->assertDatabaseCount('wave_webhook_events', 0);
    }

    public function test_webhook_sans_signature_renvoie_403_sans_ecriture(): void
    {
        $client = $this->creerClient();
        $facture = $this->creerFacture($client);
        $paiement = app(\App\Services\EnregistrerPaiement::class)
            ->executer($facture->idFacture, 'WAVE', 'EN_ATTENTE', 2751);
        $paiement->update(['referencePaiement' => 'cos-test-no-signature-302']);

        $body = json_encode([
            'id' => 'EV_test_no_signature_302',
            'type' => 'checkout.session.completed',
            'data' => ['id' => 'cos-test-no-signature-302', 'payment_status' => 'succeeded'],
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->call('POST', '/webhooks/wave', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertForbidden();

        $this->assertSame('EN_ATTENTE', $paiement->fresh()->statut);
        $this->assertSame('EN_ATTENTE', $facture->fresh()->statut);
        $this->assertDatabaseCount('notification', 0);
        $this->assertDatabaseCount('wave_webhook_events', 0);
    }

    public function test_webhook_duplique_ne_produit_qu_un_paiement_confirme_et_une_notification(): void
    {
        $client = $this->creerClient();
        $facture = $this->creerFacture($client);
        $paiement = app(\App\Services\EnregistrerPaiement::class)
            ->executer($facture->idFacture, 'WAVE', 'EN_ATTENTE', 2751);
        $paiement->update(['referencePaiement' => 'cos-test-session-401']);
        $event = [
            'id' => 'EV_test_duplicate_401',
            'type' => 'checkout.session.completed',
            'data' => ['id' => 'cos-test-session-401', 'payment_status' => 'succeeded'],
        ];

        $this->webhook($event)->assertOk();
        $this->webhook($event)->assertOk();

        $this->assertSame(1, Paiement::where('idFacture', $facture->idFacture)->count());
        $this->assertSame('CONFIRME', $paiement->fresh()->statut);
        $this->assertDatabaseCount('notification', 1);
    }

    public function test_webhook_echec_garde_facture_en_attente_et_cree_notification(): void
    {
        $client = $this->creerClient();
        $facture = $this->creerFacture($client);
        $paiement = app(\App\Services\EnregistrerPaiement::class)
            ->executer($facture->idFacture, 'WAVE', 'EN_ATTENTE', 2751);
        $paiement->update(['referencePaiement' => 'cos-test-session-501']);

        $this->webhook([
            'id' => 'EV_test_failure_501',
            'type' => 'checkout.session.payment_failed',
            'data' => ['id' => 'cos-test-session-501', 'payment_status' => 'failed', 'checkout_status' => 'failed'],
        ])->assertOk();

        $this->assertSame('ECHOUE', $paiement->fresh()->statut);
        $this->assertSame('EN_ATTENTE', $facture->fresh()->statut);
        $this->assertDatabaseHas('notification', ['idClient' => $client->idClient, 'type' => 'PAIEMENT_ECHOUE']);
    }

    public function test_evenement_pour_session_inconnue_est_ignore_sans_erreur(): void
    {
        $this->webhook([
            'id' => 'EV_test_unknown_601',
            'type' => 'checkout.session.completed',
            'data' => ['id' => 'cos-not-found-601', 'payment_status' => 'succeeded'],
        ])->assertOk();

        $this->assertDatabaseCount('paiement', 0);
        $this->assertDatabaseCount('notification', 0);
    }

    public function test_retour_de_succes_sans_webhook_ne_confirme_pas_la_facture(): void
    {
        $client = $this->creerClient();
        $facture = $this->creerFacture($client);
        $paiement = app(\App\Services\EnregistrerPaiement::class)
            ->executer($facture->idFacture, 'WAVE', 'EN_ATTENTE', 2751);
        $paiement->update(['referencePaiement' => 'cos-test-session-701']);

        $this->actingAs($client->utilisateur)
            ->get('/client/factures/'.$facture->idFacture.'/wave/succes')
            ->assertRedirect('/client/commandes/'.$facture->idCommande);

        $this->assertSame('EN_ATTENTE', $paiement->fresh()->statut);
        $this->assertSame('EN_ATTENTE', $facture->fresh()->statut);
    }
}

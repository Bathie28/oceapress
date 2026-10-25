<?php

namespace Tests\Feature;

use App\Mail\NotificationClient;
use App\Models\Commande;
use App\Models\Facture;
use App\Models\Notification;
use App\Services\ChangerStatutCommande;
use App\Services\CreerCompteClient;
use App\Services\EnregistrerPaiement;
use App\Services\EnvoyerNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    public function test_commande_prete_cree_une_notification_et_envoie_un_email_de_test(): void
    {
        Mail::fake();
        $employe = $this->creerEmploye();
        $client = $this->creerClient(['email' => 'pret@example.test']);
        $commande = $this->creerCommande($client, $employe->pressing, Commande::STATUT_EN_TRAITEMENT);

        app(ChangerStatutCommande::class)->executer($commande->idCommande, Commande::STATUT_PRETE, $employe->idEmploye);

        $this->assertDatabaseHas('notification', [
            'idClient' => $client->idClient,
            'canal' => 'IN_APP',
            'type' => 'COMMANDE_PRETE',
        ]);
        Mail::assertSent(NotificationClient::class, fn (NotificationClient $mail) => $mail->hasTo('pret@example.test'));
    }

    public function test_echec_email_conserve_la_notification_et_est_journalise(): void
    {
        $client = $this->creerClient(['email' => 'echec@example.test']);
        Mail::shouldReceive('to')->once()->with('echec@example.test')->andThrow(new RuntimeException('SMTP indisponible'));
        Log::shouldReceive('error')->once();

        app(EnvoyerNotification::class)->executer(
            $client, 'Rappel', 'Votre commande vous attend.', 'RAPPEL', ['IN_APP', 'EMAIL'],
        );

        $this->assertDatabaseHas('notification', [
            'idClient' => $client->idClient,
            'titre' => 'Rappel',
            'canal' => 'IN_APP',
        ]);
    }

    public function test_mot_de_passe_provisoire_n_est_jamais_inclus_dans_un_email_ou_une_notification(): void
    {
        Mail::fake();

        $compte = app(CreerCompteClient::class)->executer([
            'nom' => 'Nouveau', 'prenom' => 'Client',
            'email' => 'nouveau-client@example.test', 'telephone' => '771234567',
        ]);
        $motDePasseProvisoire = $compte['motDePasseProvisoire'];

        $this->assertNotEmpty($motDePasseProvisoire);
        Mail::assertNothingOutgoing();
        $this->assertSame(0, Notification::where('idClient', $compte['client']->idClient)->count());

        app(EnvoyerNotification::class)->executer(
            $compte['client'], 'Bienvenue', 'Votre compte client est prêt.', 'INFO', ['EMAIL'],
        );

        $notification = Notification::where('idClient', $compte['client']->idClient)->firstOrFail();
        $this->assertStringNotContainsString($motDePasseProvisoire, $notification->titre.$notification->message);
        Mail::assertSent(NotificationClient::class, function (NotificationClient $mail) use ($motDePasseProvisoire): bool {
            return ! str_contains($mail->titre.$mail->corps, $motDePasseProvisoire)
                && ! str_contains($mail->render(), $motDePasseProvisoire);
        });
    }

    public function test_un_client_ne_voit_que_ses_notifications_et_ouvrir_marque_comme_lu(): void
    {
        $clientA = $this->creerClient(['email' => 'notification-a@example.test']);
        $clientB = $this->creerClient(['email' => 'notification-b@example.test']);
        $notificationA = $this->creerNotification($clientA->idClient, 'Pour A');
        $notificationB = $this->creerNotification($clientB->idClient, 'Pour B');

        $this->actingAs($clientA->utilisateur)
            ->get('/client/notifications')
            ->assertOk()
            ->assertSee('Pour A')
            ->assertDontSee('Pour B');

        $this->get('/client/notifications/'.$notificationA->idNotification)->assertOk();
        $this->assertDatabaseHas('notification', ['idNotification' => $notificationA->idNotification, 'lu' => true]);
        $this->get('/client/notifications/'.$notificationB->idNotification)->assertNotFound();
    }

    public function test_paiement_confirme_ou_echoue_envoie_une_notification_dans_l_application_seulement(): void
    {
        Mail::fake();
        $client = $this->creerClient(['email' => 'paiement-notif@example.test']);
        $commande = $this->creerCommande($client);
        $facture = Facture::create([
            'numeroFacture' => 'FAC-NOTIF-'.bin2hex(random_bytes(4)),
            'dateEmission' => now(), 'montantTotal' => 1200, 'statut' => 'EN_ATTENTE',
            'idCommande' => $commande->idCommande,
        ]);

        app(EnregistrerPaiement::class)->executer($facture->idFacture, 'WAVE', 'CONFIRME', 1200);
        app(EnregistrerPaiement::class)->executer($facture->idFacture, 'WAVE', 'ECHOUE', 1200);

        $this->assertSame(2, Notification::where('idClient', $client->idClient)->where('canal', 'IN_APP')->count());
        Mail::assertNothingSent();
    }

    private function creerNotification(int $idClient, string $titre): Notification
    {
        return Notification::create([
            'titre' => $titre, 'message' => 'Message de test', 'type' => 'INFO',
            'canal' => 'IN_APP', 'dateEnvoi' => now(), 'lu' => false, 'idClient' => $idClient,
        ]);
    }
}

<?php

namespace App\Services;

use App\Mail\NotificationClient;
use App\Models\Client;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnvoyerNotification
{
    /** @param list<string> $canaux */
    public function executer(
        Client $client,
        string $titre,
        string $message,
        string $type,
        array $canaux,
        ?int $idCommande = null,
        ?string $niveauRelance = null,
    ): Notification
    {
        $notification = Notification::create([
            'titre' => $titre,
            'message' => $message,
            'type' => $type,
            'canal' => 'IN_APP',
            'dateEnvoi' => now(),
            'lu' => false,
            'idClient' => $client->idClient,
            'idCommande' => $idCommande,
            'niveauRelance' => $niveauRelance,
        ]);

        if (in_array('EMAIL', array_map('strtoupper', $canaux), true)) {
            try {
                $client->loadMissing('utilisateur');
                Mail::to($client->utilisateur->email)->send(new NotificationClient($titre, $message));
            } catch (Throwable $exception) {
                Log::error('Échec de l’envoi d’une notification par e-mail.', [
                    'idClient' => $client->idClient,
                    'idNotification' => $notification->idNotification,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return $notification;
    }
}

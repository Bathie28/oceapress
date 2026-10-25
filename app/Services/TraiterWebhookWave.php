<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Facture;
use App\Models\Paiement;
use Illuminate\Support\Facades\DB;

class TraiterWebhookWave
{
    public function executer(string $eventId, string $type, string $sessionId): void
    {
        DB::transaction(function () use ($eventId, $type, $sessionId): void {
            $paiement = Paiement::query()
                ->where('methode', 'WAVE')
                ->where('referencePaiement', $sessionId)
                ->lockForUpdate()
                ->first();

            // Unknown references are acknowledged without persisting anything.
            if (! $paiement) {
                return;
            }

            $inserted = DB::table('wave_webhook_events')->insertOrIgnore([
                'eventId' => $eventId,
                'idPaiement' => $paiement->idPaiement,
                'dateTraitement' => now(),
            ]);
            if ($inserted === 0) {
                return;
            }

            $facture = Facture::query()->whereKey($paiement->idFacture)->lockForUpdate()->firstOrFail();
            $commande = Commande::with('client')->findOrFail($facture->idCommande);

            if ($type === 'checkout.session.completed' && $paiement->statut !== 'CONFIRME') {
                $paiement->statut = 'CONFIRME';
                $paiement->datePaiement = now();
                $paiement->save();
                $facture->statut = 'PAYEE';
                $facture->save();

                app(EnvoyerNotification::class)->executer(
                    $commande->client,
                    'Paiement confirmé',
                    'Le paiement de votre facture '.$facture->numeroFacture.' a été confirmé.',
                    'PAIEMENT_CONFIRME',
                    ['IN_APP'],
                );
            } elseif ($type === 'checkout.session.payment_failed' && $paiement->statut === 'EN_ATTENTE') {
                $paiement->statut = 'ECHOUE';
                $paiement->save();

                app(EnvoyerNotification::class)->executer(
                    $commande->client,
                    'Paiement échoué',
                    'Le paiement de votre facture '.$facture->numeroFacture.' a échoué. Vous pouvez réessayer.',
                    'PAIEMENT_ECHOUE',
                    ['IN_APP'],
                );
            }
        });
    }
}

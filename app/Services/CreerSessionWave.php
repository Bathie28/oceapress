<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\Paiement;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CreerSessionWave
{
    public function executer(Facture $facture): string
    {
        $apiKey = config('services.wave.api_key');
        $apiUrl = config('services.wave.api_url');
        if (! is_string($apiKey) || $apiKey === '' || ! is_string($apiUrl) || $apiUrl === '') {
            throw new RuntimeException('La configuration Wave est incomplète.');
        }

        $baseUrl = rtrim((string) config('app.url'), '/');
        $successUrl = $baseUrl.route('client.factures.wave.succes', $facture->idFacture, false);
        $errorUrl = $baseUrl.route('client.factures.wave.echec', $facture->idFacture, false);
        if (! str_starts_with($successUrl, 'https://') || ! str_starts_with($errorUrl, 'https://')) {
            throw new RuntimeException('Les URL de retour Wave doivent utiliser HTTPS.');
        }

        $clientReference = 'FAC-'.$facture->numeroFacture.'-PAY-'.Str::upper(Str::random(16));

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(10)
                ->post(rtrim($apiUrl, '/').'/v1/checkout/sessions', [
                    'amount' => (string) (int) round((float) $facture->montantTotal),
                    'currency' => 'XOF',
                    'client_reference' => $clientReference,
                    'success_url' => $successUrl,
                    'error_url' => $errorUrl,
                ]);
            $response->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new RuntimeException('Wave n’a pas pu créer la session de paiement.', previous: $exception);
        }

        $sessionId = $response->json('id');
        $launchUrl = $response->json('wave_launch_url');
        if (! is_string($sessionId) || $sessionId === '' || ! is_string($launchUrl)
            || ! filter_var($launchUrl, FILTER_VALIDATE_URL)
            || ! str_starts_with($launchUrl, 'https://')) {
            throw new RuntimeException('La réponse Wave ne contient pas de session valide.');
        }

        try {
            Paiement::create([
                'referencePaiement' => $sessionId,
                'montant' => (int) round((float) $facture->montantTotal),
                'datePaiement' => null,
                'methode' => 'WAVE',
                'statut' => 'EN_ATTENTE',
                'idFacture' => $facture->idFacture,
            ]);
        } catch (Throwable $exception) {
            throw new RuntimeException('La session Wave a été créée, mais son paiement n’a pas pu être enregistré.', previous: $exception);
        }

        return $launchUrl;
    }
}

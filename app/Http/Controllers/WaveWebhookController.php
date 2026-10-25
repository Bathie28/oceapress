<?php

namespace App\Http\Controllers;

use App\Services\TraiterWebhookWave;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use JsonException;

class WaveWebhookController extends Controller
{
    public function __invoke(Request $request, TraiterWebhookWave $traiter): Response
    {
        $rawBody = $request->getContent();
        if (! $this->signatureValide($request->header('Wave-Signature', ''), $rawBody)) {
            return response('Signature invalide.', 403);
        }

        try {
            $event = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response('Événement invalide.', 400);
        }

        $eventId = $event['id'] ?? null;
        $type = $event['type'] ?? null;
        $sessionId = $event['data']['id'] ?? null;
        if (! is_string($eventId) || $eventId === '' || ! is_string($type) || ! is_string($sessionId)) {
            return response('Événement invalide.', 400);
        }

        if ($type === 'checkout.session.completed'
            && ($event['data']['payment_status'] ?? null) === 'succeeded') {
            $traiter->executer($eventId, $type, $sessionId);
        } elseif ($type === 'checkout.session.payment_failed'
            && ($event['data']['payment_status'] ?? null) === 'failed') {
            $traiter->executer($eventId, $type, $sessionId);
        } else {
            Log::info('Événement Wave ignoré.', ['eventId' => $eventId, 'type' => $type]);
        }

        return response('OK', 200);
    }

    private function signatureValide(string $header, string $rawBody): bool
    {
        $secret = config('services.wave.webhook_secret');
        if (! is_string($secret) || $secret === '' || $header === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key === 't' && is_string($value)) {
                $timestamp = $value;
            } elseif ($key === 'v1' && is_string($value)) {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || ! ctype_digit($timestamp) || $signatures === []) {
            return false;
        }

        $sentAt = (int) $timestamp;
        $now = now()->timestamp;
        if ($sentAt < $now - 300 || $sentAt > $now + 30) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.$rawBody, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}

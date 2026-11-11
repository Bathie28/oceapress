<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\QrCode;
use RuntimeException;

class VerifierQrCode
{
    private const MESSAGE_REFUS = 'QR invalide ou commande introuvable';

    public function executer(string $token, int $idPressing): Commande
    {
        $qrCode = QrCode::query()
            ->where('token', $token)
            ->where('actif', true)
            ->first();

        if ($qrCode === null) {
            throw new RuntimeException(self::MESSAGE_REFUS);
        }

        $commande = Commande::query()
            ->whereKey($qrCode->idCommande)
            ->where('idPressing', $idPressing)
            ->first();

        if ($commande === null) {
            throw new RuntimeException(self::MESSAGE_REFUS);
        }

        return $commande;
    }
}

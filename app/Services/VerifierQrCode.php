<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\QrCode;
use RuntimeException;

class VerifierQrCode
{
    private const MESSAGE_REFUS = 'QR invalide ou commande introuvable';

    public function __construct(private readonly GenererCodeQr $genererCodeQr)
    {
    }

    public function executer(string $token, int $idPressing): Commande
    {
        if (! preg_match('/\AQR-([1-9][0-9]{0,9})-[0-9]+-[A-F0-9]{16}\z/', $token, $correspondance)) {
            throw new RuntimeException(self::MESSAGE_REFUS);
        }

        $idCommande = (int) $correspondance[1];
        $qrCode = QrCode::query()
            ->where('idCommande', $idCommande)
            ->where('actif', true)
            ->first();

        if ($qrCode === null) {
            throw new RuntimeException(self::MESSAGE_REFUS);
        }

        $commande = Commande::query()
            ->whereKey($idCommande)
            ->where('idPressing', $idPressing)
            ->first();

        if ($commande === null || ! $this->genererCodeQr->estValide($token, $idCommande)) {
            throw new RuntimeException(self::MESSAGE_REFUS);
        }

        return $commande;
    }
}

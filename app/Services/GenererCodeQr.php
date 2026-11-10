<?php

namespace App\Services;

use DateTimeInterface;
use RuntimeException;

class GenererCodeQr
{
    private const DUREE_TRANCHE_SECONDES = 120;
    private const LONGUEUR_SIGNATURE = 16;

    public function executer(int $idCommande, ?DateTimeInterface $instant = null): string
    {
        return $this->pourTranche($idCommande, $this->tranche($instant));
    }

    public function estValide(string $code, int $idCommande, ?DateTimeInterface $instant = null): bool
    {
        $trancheCourante = $this->tranche($instant);

        return hash_equals($this->pourTranche($idCommande, $trancheCourante), $code)
            || hash_equals($this->pourTranche($idCommande, $trancheCourante - 1), $code);
    }

    private function pourTranche(int $idCommande, int $tranche): string
    {
        $secret = config('services.qr.secret');
        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('La clé QR_SECRET doit être configurée.');
        }

        $signature = strtoupper(substr(
            hash_hmac('sha256', "{$idCommande}:{$tranche}", $secret),
            0,
            self::LONGUEUR_SIGNATURE,
        ));

        return "QR-{$idCommande}-{$tranche}-{$signature}";
    }

    private function tranche(?DateTimeInterface $instant): int
    {
        return intdiv(($instant?->getTimestamp() ?? now()->getTimestamp()), self::DUREE_TRANCHE_SECONDES);
    }
}

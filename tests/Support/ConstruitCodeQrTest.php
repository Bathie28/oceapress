<?php

namespace Tests\Support;

use DateTimeInterface;

trait ConstruitCodeQrTest
{
    protected function codeQr(int $idCommande, ?DateTimeInterface $instant = null): string
    {
        config(['services.qr.secret' => 'cle-secrete-test-qr']);

        $tranche = intdiv(($instant?->getTimestamp() ?? now()->timestamp), 120);
        $signature = strtoupper(substr(hash_hmac('sha256', "{$idCommande}:{$tranche}", 'cle-secrete-test-qr'), 0, 16));

        return "QR-{$idCommande}-{$tranche}-{$signature}";
    }
}

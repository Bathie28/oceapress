<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Tests\Support\ConstruitCodeQrTest;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;
use RuntimeException as NativeRuntimeException;

class VerifierQrCodeTest extends TestCase
{
    use RefreshDatabase, ConstruitCodeQrTest, CreeDonneesTest;

    private const MESSAGE_REFUS = 'QR invalide ou commande introuvable';

    private function compteurs(): array
    {
        return collect(['utilisateur', 'client', 'employe', 'pressing', 'commande', 'qr_code', 'historique_commande', 'article', 'facture'])
            ->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])
            ->all();
    }

    private function erreurPourToken(string $token, int $idPressing): NativeRuntimeException
    {
        try {
            app(\App\Services\VerifierQrCode::class)->executer($token, $idPressing);
            $this->fail('Le token devait être refusé.');
        } catch (NativeRuntimeException $exception) {
            return $exception;
        }
    }

    public function test_un_token_actif_du_pressing_renvoie_sa_commande_sans_ecriture(): void
    {
        $pressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $pressing);
        $token = $this->codeQr($commande->idCommande);
        QrCode::create(['token' => $token, 'actif' => true, 'idCommande' => $commande->idCommande]);
        $avant = $this->compteurs();

        $resultat = app(\App\Services\VerifierQrCode::class)->executer($token, $pressing->idPressing);

        $this->assertSame($commande->idCommande, $resultat->idCommande);
        $this->assertSame($avant, $this->compteurs());
    }

    public function test_un_token_de_la_tranche_precedente_est_accepte_a_la_seconde_du_changement(): void
    {
        $pressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $pressing);
        $instantAvantChangement = \Carbon\CarbonImmutable::parse('2026-11-10 10:01:59 UTC');
        $instantApresChangement = \Carbon\CarbonImmutable::parse('2026-11-10 10:02:00 UTC');
        $tokenPrecedent = $this->codeQr($commande->idCommande, $instantAvantChangement);
        QrCode::create([
            'token' => $this->codeQr($commande->idCommande, $instantApresChangement),
            'actif' => true,
            'idCommande' => $commande->idCommande,
        ]);
        $this->travelTo($instantApresChangement);

        $resultat = app(\App\Services\VerifierQrCode::class)->executer($tokenPrecedent, $pressing->idPressing);

        $this->assertSame($commande->idCommande, $resultat->idCommande);
    }

    public function test_un_token_de_la_tranche_courante_est_accepte_si_le_token_persiste_est_ancien(): void
    {
        $pressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $pressing);
        $instantAvantChangement = \Carbon\CarbonImmutable::parse('2026-11-10 10:01:59 UTC');
        $instantApresChangement = \Carbon\CarbonImmutable::parse('2026-11-10 10:02:00 UTC');
        $tokenCourant = $this->codeQr($commande->idCommande, $instantApresChangement);
        QrCode::create([
            'token' => $this->codeQr($commande->idCommande, $instantAvantChangement),
            'actif' => true,
            'idCommande' => $commande->idCommande,
        ]);
        $this->travelTo($instantApresChangement);

        $resultat = app(\App\Services\VerifierQrCode::class)->executer($tokenCourant, $pressing->idPressing);

        $this->assertSame($commande->idCommande, $resultat->idCommande);
    }

    public function test_un_token_de_deux_tranches_precedentes_est_refuse(): void
    {
        $pressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $pressing);
        $instantAncien = \Carbon\CarbonImmutable::parse('2026-11-10 10:01:59 UTC');
        $instantCourant = \Carbon\CarbonImmutable::parse('2026-11-10 10:04:00 UTC');
        $tokenAncien = $this->codeQr($commande->idCommande, $instantAncien);
        QrCode::create([
            'token' => $this->codeQr($commande->idCommande, $instantCourant),
            'actif' => true,
            'idCommande' => $commande->idCommande,
        ]);
        $this->travelTo($instantCourant);

        $exception = $this->erreurPourToken($tokenAncien, $pressing->idPressing);

        $this->assertSame(self::MESSAGE_REFUS, $exception->getMessage());
    }

    public function test_un_token_inconnu_est_refuse_sans_ecriture(): void
    {
        $pressing = $this->creerPressing();
        $avant = $this->compteurs();

        $exception = $this->erreurPourToken('qr-inconnu', $pressing->idPressing);

        $this->assertSame(self::MESSAGE_REFUS, $exception->getMessage());
        $this->assertSame($avant, $this->compteurs());
    }

    public function test_un_code_avec_une_signature_modifiee_est_refuse_sans_ecriture(): void
    {
        $pressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $pressing);
        $token = $this->codeQr($commande->idCommande);
        QrCode::create(['token' => $token, 'actif' => true, 'idCommande' => $commande->idCommande]);
        $signatureAlteree = substr($token, -1) === '0' ? '1' : '0';
        $tokenAlteree = substr($token, 0, -1).$signatureAlteree;
        $avant = $this->compteurs();

        $exception = $this->erreurPourToken($tokenAlteree, $pressing->idPressing);

        $this->assertSame(self::MESSAGE_REFUS, $exception->getMessage());
        $this->assertSame($avant, $this->compteurs());
    }

    public function test_un_token_d_un_autre_pressing_a_le_meme_refus_qu_un_token_inconnu_sans_ecriture(): void
    {
        $pressingActif = $this->creerPressing();
        $autrePressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $autrePressing);
        $token = $this->codeQr($commande->idCommande);
        QrCode::create(['token' => $token, 'actif' => true, 'idCommande' => $commande->idCommande]);
        $avant = $this->compteurs();

        $erreurAutrePressing = $this->erreurPourToken($token, $pressingActif->idPressing);
        $erreurInconnu = $this->erreurPourToken('qr-inconnu', $pressingActif->idPressing);

        $this->assertSame(self::MESSAGE_REFUS, $erreurAutrePressing->getMessage());
        $this->assertSame($erreurInconnu->getMessage(), $erreurAutrePressing->getMessage());
        $this->assertSame($avant, $this->compteurs());
    }

    public function test_une_commande_ne_peut_pas_avoir_deux_qr_actifs(): void
    {
        $pressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $pressing);
        QrCode::create(['token' => $this->codeQr($commande->idCommande), 'actif' => true, 'idCommande' => $commande->idCommande]);

        $this->expectException(QueryException::class);
        QrCode::create([
            'token' => $this->codeQr($commande->idCommande, now()->addMinutes(2)),
            'actif' => true,
            'idCommande' => $commande->idCommande,
        ]);
    }
}

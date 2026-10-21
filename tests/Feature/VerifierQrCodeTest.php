<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;
use RuntimeException as NativeRuntimeException;

class VerifierQrCodeTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

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
        QrCode::create(['token' => 'qr-actif-local', 'actif' => true, 'idCommande' => $commande->idCommande]);
        $avant = $this->compteurs();

        $resultat = app(\App\Services\VerifierQrCode::class)->executer('qr-actif-local', $pressing->idPressing);

        $this->assertSame($commande->idCommande, $resultat->idCommande);
        $this->assertSame($avant, $this->compteurs());
    }

    public function test_un_token_inconnu_est_refuse_sans_ecriture(): void
    {
        $pressing = $this->creerPressing();
        $avant = $this->compteurs();

        $exception = $this->erreurPourToken('qr-inconnu', $pressing->idPressing);

        $this->assertSame(self::MESSAGE_REFUS, $exception->getMessage());
        $this->assertSame($avant, $this->compteurs());
    }

    public function test_un_token_inactif_remplace_est_refuse_sans_ecriture(): void
    {
        $pressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $pressing);
        QrCode::create(['token' => 'qr-remplace', 'actif' => false, 'idCommande' => $commande->idCommande]);
        QrCode::create(['token' => 'qr-remplacement-actif', 'actif' => true, 'idCommande' => $commande->idCommande]);
        $avant = $this->compteurs();

        $exception = $this->erreurPourToken('qr-remplace', $pressing->idPressing);

        $this->assertSame(self::MESSAGE_REFUS, $exception->getMessage());
        $this->assertSame($avant, $this->compteurs());
    }

    public function test_un_token_d_un_autre_pressing_a_le_meme_refus_qu_un_token_inconnu_sans_ecriture(): void
    {
        $pressingActif = $this->creerPressing();
        $autrePressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $autrePressing);
        QrCode::create(['token' => 'qr-autre-pressing', 'actif' => true, 'idCommande' => $commande->idCommande]);
        $avant = $this->compteurs();

        $erreurAutrePressing = $this->erreurPourToken('qr-autre-pressing', $pressingActif->idPressing);
        $erreurInconnu = $this->erreurPourToken('qr-inconnu', $pressingActif->idPressing);

        $this->assertSame(self::MESSAGE_REFUS, $erreurAutrePressing->getMessage());
        $this->assertSame($erreurInconnu->getMessage(), $erreurAutrePressing->getMessage());
        $this->assertSame($avant, $this->compteurs());
    }
}

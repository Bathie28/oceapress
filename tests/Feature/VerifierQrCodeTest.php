<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
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

    public function test_un_token_fixe_reste_accepte_longtemps_apres_la_creation_de_la_commande(): void
    {
        $pressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $pressing);
        QrCode::create(['token' => 'qr-fixe-durable', 'actif' => true, 'idCommande' => $commande->idCommande]);

        foreach ([2, 4, 60 * 24 * 10] as $minutes) {
            $this->travelTo(now()->addMinutes($minutes));

            $resultat = app(\App\Services\VerifierQrCode::class)->executer('qr-fixe-durable', $pressing->idPressing);

            $this->assertSame($commande->idCommande, $resultat->idCommande);
        }
    }

    public function test_un_code_calcule_qui_n_est_pas_le_token_stocke_est_refuse(): void
    {
        $pressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $pressing);
        QrCode::create(['token' => 'qr-fixe-stocke', 'actif' => true, 'idCommande' => $commande->idCommande]);
        $codeCalcule = 'QR-'.$commande->idCommande.'-'.intdiv(now()->timestamp, 120).'-0123456789ABCDEF';
        $avant = $this->compteurs();

        $exception = $this->erreurPourToken($codeCalcule, $pressing->idPressing);

        $this->assertSame(self::MESSAGE_REFUS, $exception->getMessage());
        $this->assertSame($avant, $this->compteurs());
    }

    public function test_un_token_modifie_d_un_caractere_est_refuse_sans_ecriture(): void
    {
        $pressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $pressing);
        QrCode::create(['token' => 'qr-fixe-exact-0', 'actif' => true, 'idCommande' => $commande->idCommande]);
        $avant = $this->compteurs();

        $exception = $this->erreurPourToken('qr-fixe-exact-1', $pressing->idPressing);

        $this->assertSame(self::MESSAGE_REFUS, $exception->getMessage());
        $this->assertSame($avant, $this->compteurs());
    }

    public function test_une_commande_ne_peut_pas_avoir_deux_qr_actifs(): void
    {
        $pressing = $this->creerPressing();
        $commande = $this->creerCommande(null, $pressing);
        QrCode::create(['token' => 'qr-actif-unique', 'actif' => true, 'idCommande' => $commande->idCommande]);

        $this->expectException(QueryException::class);
        QrCode::create(['token' => 'qr-actif-en-trop', 'actif' => true, 'idCommande' => $commande->idCommande]);
    }
}

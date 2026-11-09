<?php

namespace Tests\Feature;

use App\Models\Anomalie;
use App\Models\Article;
use App\Models\Commande;
use App\Models\HistoriqueCommande;
use App\Models\QrCode;
use App\Models\Restitution;
use App\Services\ChangerStatutCommande;
use App\Services\EnregistrerRestitution;
use App\Services\VerifierQrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use RuntimeException;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class RestitutionTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function commandePrete(): array
    {
        $employe = $this->creerEmploye();
        $commande = $this->creerCommande();
        $service = app(ChangerStatutCommande::class);
        $service->executer($commande->idCommande, Commande::STATUT_EN_TRAITEMENT, $employe->idEmploye);
        $service->executer($commande->idCommande, Commande::STATUT_PRETE, $employe->idEmploye);
        $articles = collect([
            ['typeArticle' => 'Chemise', 'quantite' => 3],
            ['typeArticle' => 'Pantalon', 'quantite' => 2],
        ])->map(fn (array $data) => Article::create([...$data, 'idCommande' => $commande->idCommande]));

        return [$commande, $employe, $articles];
    }

    public function test_refuse_si_commande_pas_prete(): void
    {
        $employe = $this->creerEmploye();
        $commande = $this->creerCommande();
        $this->expectException(RuntimeException::class);
        app(EnregistrerRestitution::class)->executer($commande->idCommande, $employe->idEmploye, []);
    }

    public function test_toutes_les_quantites_presentees_enregistrent_restitution_conforme(): void
    {
        [$commande, $employe, $articles] = $this->commandePrete();
        $avant = HistoriqueCommande::where('idCommande', $commande->idCommande)->count();
        $quantites = $articles->mapWithKeys(fn (Article $article) => [$article->idArticle => $article->quantite])->all();

        $resultat = app(EnregistrerRestitution::class)->executer($commande->idCommande, $employe->idEmploye, $quantites);

        $this->assertSame('CONFORME', $resultat['restitution']->statut);
        $this->assertSame(Commande::STATUT_RECUPEREE, $commande->fresh()->statut);
        $this->assertSame($avant + 1, HistoriqueCommande::where('idCommande', $commande->idCommande)->count());
        $this->expectException(RuntimeException::class);
        app(EnregistrerRestitution::class)->executer($commande->idCommande, $employe->idEmploye, $quantites);
    }

    public function test_restitution_conforme_desactive_le_qr_et_le_rend_inaccessible(): void
    {
        [$commande, $employe, $articles] = $this->commandePrete();
        $qrCode = QrCode::create([
            'token' => 'qr-restitution-conforme',
            'actif' => true,
            'idCommande' => $commande->idCommande,
        ]);
        $quantites = $articles->mapWithKeys(fn (Article $article) => [$article->idArticle => $article->quantite])->all();

        app(EnregistrerRestitution::class)->executer($commande->idCommande, $employe->idEmploye, $quantites);

        $this->assertFalse((bool) $qrCode->fresh()->actif);
        try {
            app(VerifierQrCode::class)->executer($qrCode->token, $commande->idPressing);
            $this->fail('Le token d’une commande récupérée devait être invalide.');
        } catch (RuntimeException $exception) {
            $this->assertSame('QR invalide ou commande introuvable', $exception->getMessage());
        }

        $this->actingAs($commande->client->utilisateur)
            ->get('/client/commandes/'.$commande->idCommande)
            ->assertOk()
            ->assertSee('QR Code indisponible, contactez le pressing')
            ->assertDontSee($qrCode->token);
    }

    public function test_restitution_desactive_un_qr_actif_et_preserve_ceux_des_commandes_non_recuperees(): void
    {
        [$commande, $employe, $articles] = $this->commandePrete();
        $qrRecupere = QrCode::create([
            'token' => 'qr-recupere',
            'actif' => true,
            'idCommande' => $commande->idCommande,
        ]);
        $commandeNonRecuperee = $this->creerCommande(null, $commande->pressing, Commande::STATUT_PRETE);
        $qrNonRecupere = QrCode::create([
            'token' => 'qr-non-recupere',
            'actif' => true,
            'idCommande' => $commandeNonRecuperee->idCommande,
        ]);
        $quantites = $articles->mapWithKeys(fn (Article $article) => [$article->idArticle => $article->quantite])->all();

        app(EnregistrerRestitution::class)->executer($commande->idCommande, $employe->idEmploye, $quantites);

        $this->assertFalse((bool) $qrRecupere->fresh()->actif);
        $this->assertTrue((bool) $qrNonRecupere->fresh()->actif);
    }

    public function test_quantite_manquante_cree_anomalie_sans_restitution_et_garde_commande_prete(): void
    {
        [$commande, $employe, $articles] = $this->commandePrete();
        $quantites = $articles->mapWithKeys(fn (Article $article) => [$article->idArticle => $article->quantite])->all();
        $quantites[$articles->first()->idArticle] = 2;

        $resultat = app(EnregistrerRestitution::class)->executer($commande->idCommande, $employe->idEmploye, $quantites);

        $this->assertNull($resultat['restitution']);
        $this->assertSame(1, $resultat['anomalies']);
        $this->assertSame(0, Restitution::count());
        $this->assertSame(Commande::STATUT_PRETE, $commande->fresh()->statut);
        $this->assertStringContainsString('Manque 1 sur 3', Anomalie::firstOrFail()->description);
        $this->assertSame($articles->first()->idArticle, Anomalie::firstOrFail()->idArticle);
    }

    public function test_quantite_presentee_superieure_est_refusee(): void
    {
        [$commande, $employe, $articles] = $this->commandePrete();
        $quantites = $articles->mapWithKeys(fn (Article $article) => [$article->idArticle => $article->quantite])->all();
        $quantites[$articles->first()->idArticle] = 4;
        try {
            app(EnregistrerRestitution::class)->executer($commande->idCommande, $employe->idEmploye, $quantites);
            $this->fail('Un excédent devait être refusé.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('supérieure', mb_strtolower($exception->getMessage()));
        }
        $this->assertSame(0, Restitution::count());
        $this->assertSame(0, Anomalie::count());
    }

    public function test_ligne_d_une_autre_commande_est_refusee(): void
    {
        [$commande, $employe, $articles] = $this->commandePrete();
        $autreCommande = $this->creerCommande();
        $articleAutre = Article::create(['typeArticle' => 'Jupe', 'idCommande' => $autreCommande->idCommande]);
        $quantites = $articles->mapWithKeys(fn (Article $article) => [$article->idArticle => $article->quantite])->all();
        $quantites[$articleAutre->idArticle] = 1;
        $this->expectException(RuntimeException::class);
        app(EnregistrerRestitution::class)->executer($commande->idCommande, $employe->idEmploye, $quantites);
    }

    public function test_seconde_restitution_est_refusee_par_le_service_et_la_base(): void
    {
        [$commande, $employe, $articles] = $this->commandePrete();
        $quantites = $articles->mapWithKeys(fn (Article $article) => [$article->idArticle => $article->quantite])->all();
        app(EnregistrerRestitution::class)->executer($commande->idCommande, $employe->idEmploye, $quantites);
        $restitution = Restitution::where('idCommande', $commande->idCommande)->firstOrFail();
        try {
            Restitution::create([
                'dateRestitution' => now(), 'statut' => 'CONFORME', 'idCommande' => $commande->idCommande,
                'idEmploye' => $employe->idEmploye,
            ]);
            $this->fail('La contrainte uq_restitution_commande devait refuser la seconde insertion.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', (string) $exception->getCode());
            $this->assertStringContainsString('uq_restitution_commande', $exception->getMessage());
        }
        $this->assertNotNull($restitution->fresh());
    }
}

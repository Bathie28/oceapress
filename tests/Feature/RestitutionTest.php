<?php

namespace Tests\Feature;

use App\Models\Anomalie;
use App\Models\Article;
use App\Models\Commande;
use App\Models\HistoriqueCommande;
use App\Models\Restitution;
use App\Services\ChangerStatutCommande;
use App\Services\EnregistrerRestitution;
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

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
        $statuts = app(ChangerStatutCommande::class);
        $statuts->executer($commande->idCommande, Commande::STATUT_EN_TRAITEMENT, $employe->idEmploye);
        $statuts->executer($commande->idCommande, Commande::STATUT_PRETE, $employe->idEmploye);

        return [$commande, $employe];
    }

    public function test_refuse_si_commande_pas_prete(): void
    {
        $employe = $this->creerEmploye();
        $commande = $this->creerCommande();
        $this->expectException(RuntimeException::class);
        app(EnregistrerRestitution::class)->executer($commande->idCommande, $employe->idEmploye, []);
    }

    public function test_restitution_partielle_signale_anomalie_sans_restitution_et_garde_commande_prete(): void
    {
        [$commande, $employe] = $this->commandePrete();
        $articles = collect(['Chemise', 'Pantalon'])->map(fn ($type) => Article::create([
            'typeArticle' => $type, 'idCommande' => $commande->idCommande,
        ]));

        $resultat = app(EnregistrerRestitution::class)->executer(
            $commande->idCommande, $employe->idEmploye, [$articles->first()->idArticle],
        );

        $this->assertNull($resultat['restitution']);
        $this->assertSame(1, $resultat['anomalies']);
        $this->assertSame(1, Anomalie::count());
        $this->assertSame(0, Restitution::count());
        $this->assertSame(Commande::STATUT_PRETE, $commande->fresh()->statut);
    }

    public function test_restitution_complete_recupere_commande_et_refuse_seconde_restitution(): void
    {
        [$commande, $employe] = $this->commandePrete();
        $articles = collect(['Chemise', 'Pantalon'])->map(fn ($type) => Article::create([
            'typeArticle' => $type, 'idCommande' => $commande->idCommande,
        ]));
        $avant = HistoriqueCommande::where('idCommande', $commande->idCommande)->count();
        $resultat = app(EnregistrerRestitution::class)->executer(
            $commande->idCommande, $employe->idEmploye, $articles->pluck('idArticle')->all(),
        );

        $this->assertSame('CONFORME', $resultat['restitution']->statut);
        $this->assertSame(Commande::STATUT_RECUPEREE, $commande->fresh()->statut);
        $this->assertSame(3, $avant);
        $this->assertSame(4, HistoriqueCommande::where('idCommande', $commande->idCommande)->count());

        $this->expectException(RuntimeException::class);
        app(EnregistrerRestitution::class)->executer(
            $commande->idCommande, $employe->idEmploye, $articles->pluck('idArticle')->all(),
        );
    }

    public function test_seconde_restitution_refusee_par_la_base(): void
    {
        [$commande, $employe] = $this->commandePrete();
        $articles = collect(['Chemise', 'Pantalon'])->map(fn ($type) => Article::create([
            'typeArticle' => $type, 'idCommande' => $commande->idCommande,
        ]));

        app(EnregistrerRestitution::class)->executer(
            $commande->idCommande, $employe->idEmploye, $articles->pluck('idArticle')->all(),
        );

        $premiereRestitution = Restitution::where('idCommande', $commande->idCommande)->firstOrFail();

        try {
            Restitution::create([
                'dateRestitution' => now(),
                'statut' => 'CONFORME',
                'observation' => 'Deuxième insertion directe',
                'idCommande' => $commande->idCommande,
                'idEmploye' => $employe->idEmploye,
            ]);
            $this->fail('La contrainte uq_restitution_commande devait refuser la seconde insertion.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', (string) $exception->getCode());
            $this->assertStringContainsString('1062', $exception->getMessage());
            $this->assertStringContainsString('uq_restitution_commande', $exception->getMessage());
        }

        $this->assertSame(1, Restitution::where('idCommande', $commande->idCommande)->count());
        $this->assertNotNull($premiereRestitution->fresh());
    }
}

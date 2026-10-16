<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\HistoriqueCommande;
use App\Services\ChangerStatutCommande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class ChangerStatutCommandeTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    public function test_effectue_les_transitions_valides_et_date_la_recuperation(): void
    {
        $employe = $this->creerEmploye();
        $commande = $this->creerCommande();
        $service = app(ChangerStatutCommande::class);

        $this->assertNull($commande->dateRecuperation);
        $service->executer($commande->idCommande, Commande::STATUT_EN_TRAITEMENT, $employe->idEmploye);
        $service->executer($commande->idCommande, Commande::STATUT_PRETE, $employe->idEmploye);
        $this->assertNull($commande->fresh()->dateRecuperation);
        $service->executer($commande->idCommande, Commande::STATUT_RECUPEREE, $employe->idEmploye);

        $this->assertSame(Commande::STATUT_RECUPEREE, $commande->fresh()->statut);
        $this->assertNotNull($commande->fresh()->dateRecuperation);
        $this->assertSame(4, HistoriqueCommande::where('idCommande', $commande->idCommande)->count());
    }

    public function test_refuse_transition_interdite_sans_ajouter_historique(): void
    {
        $employe = $this->creerEmploye();
        $commande = $this->creerCommande();
        $historique = HistoriqueCommande::where('idCommande', $commande->idCommande)->count();

        try {
            app(ChangerStatutCommande::class)->executer($commande->idCommande, Commande::STATUT_PRETE, $employe->idEmploye);
            $this->fail('Une exception devait être levée.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Transition de', $exception->getMessage());
        }

        $this->assertSame($historique, HistoriqueCommande::where('idCommande', $commande->idCommande)->count());
    }

    public function test_annule_depuis_en_attente_puis_refuse_toute_transition(): void
    {
        $employe = $this->creerEmploye();
        $commande = $this->creerCommande();
        $service = app(ChangerStatutCommande::class);
        $service->executer($commande->idCommande, Commande::STATUT_ANNULEE, $employe->idEmploye);
        $historique = HistoriqueCommande::where('idCommande', $commande->idCommande)->count();

        try {
            $service->executer($commande->idCommande, Commande::STATUT_EN_TRAITEMENT, $employe->idEmploye);
            $this->fail('Une exception devait être levée.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Transition de', $exception->getMessage());
        }

        $this->assertSame($historique, HistoriqueCommande::where('idCommande', $commande->idCommande)->count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\HistoriqueCommande;
use App\Models\Notification;
use App\Services\RelancerCommandesNonRecuperees;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class RelancerCommandesNonRecupereesTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->travelTo(now()->setDate(2026, 11, 2)->setTime(10, 0));
    }

    public function test_commande_prete_depuis_deux_jours_recoit_j2_une_seule_fois(): void
    {
        $commande = $this->creerCommandePreteIlYA(2);

        $this->assertSame(1, app(RelancerCommandesNonRecuperees::class)->executer());
        $this->assertSame(0, app(RelancerCommandesNonRecuperees::class)->executer());

        $this->assertDatabaseHas('notification', [
            'idCommande' => $commande->idCommande,
            'niveauRelance' => 'J2',
            'canal' => 'IN_APP',
        ]);
        $this->assertSame(1, Notification::where('idCommande', $commande->idCommande)->count());
        Mail::assertSentTimes(\App\Mail\NotificationClient::class, 1);
    }

    public function test_commande_prete_depuis_sept_jours_recoit_j2_et_j7_sans_doublon(): void
    {
        $commande = $this->creerCommandePreteIlYA(7);

        $this->assertSame(2, app(RelancerCommandesNonRecuperees::class)->executer());
        $this->assertSame(0, app(RelancerCommandesNonRecuperees::class)->executer());

        $this->assertEqualsCanonicalizing(
            ['J2', 'J7'],
            Notification::where('idCommande', $commande->idCommande)->pluck('niveauRelance')->all(),
        );
        $this->assertSame(2, Notification::where('idCommande', $commande->idCommande)->count());
    }

    public function test_commandes_recuperees_ou_annulees_ne_sont_jamais_relancees(): void
    {
        $pressing = $this->creerPressing();
        $recuperee = $this->creerCommande(null, $pressing, Commande::STATUT_RECUPEREE);
        $annulee = $this->creerCommande(null, $pressing, Commande::STATUT_ANNULEE);

        $this->assertSame(0, app(RelancerCommandesNonRecuperees::class)->executer());
        $this->assertSame(0, Notification::whereIn('idCommande', [$recuperee->idCommande, $annulee->idCommande])->count());
    }

    public function test_la_date_de_creation_ne_remplace_pas_la_date_du_passage_a_prete(): void
    {
        $commande = $this->creerCommandePreteIlYA(1);
        $commande->dateCreation = now()->subDays(45);
        $commande->save();

        $this->assertSame(0, app(RelancerCommandesNonRecuperees::class)->executer());

        HistoriqueCommande::where('idCommande', $commande->idCommande)
            ->where('nouveauStatut', Commande::STATUT_PRETE)
            ->update(['dateChangement' => now()->subDays(2)]);

        $this->assertSame(1, app(RelancerCommandesNonRecuperees::class)->executer());
        $this->assertDatabaseHas('notification', [
            'idCommande' => $commande->idCommande,
            'niveauRelance' => 'J2',
        ]);
    }

    public function test_tableau_de_bord_affiche_compteurs_et_commandes_du_pressing_connecte(): void
    {
        $employe = $this->creerEmploye();
        $commandePrete = $this->creerCommandePreteIlYA(7, $employe->pressing);
        $commandeAttente = $this->creerCommande(null, $employe->pressing, Commande::STATUT_EN_ATTENTE);
        $commandeAutrePressing = $this->creerCommandePreteIlYA(7);

        $this->actingAs($employe->utilisateur)
            ->get('/employe/tableau-de-bord')
            ->assertOk()
            ->assertSee('data-statut="PRETE" data-count="1"', false)
            ->assertSee('data-statut="EN_ATTENTE" data-count="1"', false)
            ->assertSee($commandePrete->referenceCommande)
            ->assertSee('J7')
            ->assertDontSee($commandeAutrePressing->referenceCommande)
            ->assertDontSee($commandeAttente->referenceCommande);
    }

    public function test_accueil_employe_lie_au_tableau_de_bord(): void
    {
        $employe = $this->creerEmploye();

        $this->actingAs($employe->utilisateur)
            ->get('/employe')
            ->assertOk()
            ->assertSee('href="'.route('employe.tableau-de-bord').'"', false);
    }

    public function test_planificateur_enregistre_la_relance_quotidienne_a_neuf_heures(): void
    {
        $tache = collect(app(Schedule::class)->events())
            ->first(fn ($evenement) => str_contains($evenement->command ?? '', 'oceapress:relancer'));

        $this->assertNotNull($tache, 'La commande oceapress:relancer doit être planifiée.');
        $this->assertSame('0 9 * * *', $tache->expression);
        $this->assertSame('Africa/Dakar', config('app.timezone'));
        $this->assertArrayHasKey('oceapress:relancer', Artisan::all());
    }

    private function creerCommandePreteIlYA(int $jours, $pressing = null): Commande
    {
        $commande = $this->creerCommande(null, $pressing, Commande::STATUT_PRETE);

        HistoriqueCommande::where('idCommande', $commande->idCommande)
            ->where('nouveauStatut', Commande::STATUT_PRETE)
            ->update(['dateChangement' => now()->subDays($jours)]);

        return $commande;
    }
}

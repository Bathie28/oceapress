<?php

namespace Tests\Feature;

use App\Models\Anomalie;
use App\Models\Article;
use App\Models\Commande;
use App\Models\Employe;
use App\Models\Restitution;
use App\Services\ChangerStatutCommande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class EspaceEmployeRestitutionTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function commandePrete(Employe $employe): array
    {
        $commande = $this->creerCommande(null, $employe->pressing);
        $service = app(ChangerStatutCommande::class);
        $service->executer($commande->idCommande, Commande::STATUT_EN_TRAITEMENT, $employe->idEmploye);
        $service->executer($commande->idCommande, Commande::STATUT_PRETE, $employe->idEmploye);
        $articles = collect(['Chemise', 'Pantalon'])->map(fn (string $type) => Article::create([
            'typeArticle' => $type,
            'idCommande' => $commande->idCommande,
        ]));

        return [$commande, $articles];
    }

    public function test_restitution_complete_depuis_interface_recupere_commande_et_cree_restitution_conforme(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);
        [$commande, $articles] = $this->commandePrete($employe);
        $url = '/employe/commandes/'.$commande->idCommande.'/restitution';

        $this->get($url)->assertOk()->assertSee('Restitution');
        $response = $this->post($url, [
            'articles' => $articles->pluck('idArticle')->all(),
            'observation' => 'Tous les articles sont présents',
        ]);

        $response->assertRedirect('/employe/commandes/'.$commande->idCommande)
            ->assertSessionHas('restitution_resultat', 'Restitution conforme enregistrée.');
        $this->assertSame(Commande::STATUT_RECUPEREE, $commande->fresh()->statut);
        $this->assertSame('CONFORME', Restitution::where('idCommande', $commande->idCommande)->firstOrFail()->statut);
    }

    public function test_restitution_partielle_cree_une_anomalie_sans_restitution_et_garde_commande_prete(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);
        [$commande, $articles] = $this->commandePrete($employe);

        $response = $this->post('/employe/commandes/'.$commande->idCommande.'/restitution', [
            'articles' => [$articles->first()->idArticle],
            'observation' => 'Un article manque',
        ]);

        $response->assertRedirect('/employe/commandes/'.$commande->idCommande)
            ->assertSessionHas('restitution_resultat', 'Restitution partielle : 1 anomalie créée.');
        $this->assertSame(1, Anomalie::count());
        $this->assertSame(0, Restitution::count());
        $this->assertSame(Commande::STATUT_PRETE, $commande->fresh()->statut);
    }

    public function test_restitution_d_une_commande_d_un_autre_pressing_est_404(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);
        $autreEmploye = $this->creerEmploye();
        [$commande] = $this->commandePrete($autreEmploye);

        $this->post('/employe/commandes/'.$commande->idCommande.'/restitution', [
            'articles' => [],
        ])->assertNotFound();

        $this->assertSame(0, Anomalie::count());
        $this->assertSame(0, Restitution::count());
    }
}

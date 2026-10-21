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
        $articles = collect([
            ['typeArticle' => 'Chemise', 'quantite' => 3],
            ['typeArticle' => 'Pantalon', 'quantite' => 2],
        ])->map(fn (array $data) => Article::create([...$data, 'idCommande' => $commande->idCommande]));

        return [$commande, $articles];
    }

    public function test_page_restitution_affiche_une_ligne_par_type_et_les_quantites_enregistrees(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);
        [$commande, $articles] = $this->commandePrete($employe);

        $response = $this->get('/employe/commandes/'.$commande->idCommande.'/restitution')->assertOk();
        foreach ($articles as $article) {
            $response->assertSee($article->typeArticle)
                ->assertSee('name="quantitesPresentees['.$article->idArticle.']"', false)
                ->assertSee('value="'.$article->quantite.'"', false);
        }
    }

    public function test_quantite_manquante_cree_anomalie_et_l_ecran_l_indique(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);
        [$commande, $articles] = $this->commandePrete($employe);
        $premiere = $articles->first();
        $quantites = $articles->mapWithKeys(fn (Article $article) => [$article->idArticle => $article->quantite])->all();
        $quantites[$premiere->idArticle] = 2;

        $this->post('/employe/commandes/'.$commande->idCommande.'/restitution', [
            'quantitesPresentees' => $quantites,
            'observation' => 'Une pièce manque',
        ])->assertRedirect('/employe/commandes/'.$commande->idCommande)
            ->assertSessionHas('restitution_resultat', 'Restitution partielle : 1 anomalie créée.');

        $this->assertSame(1, Anomalie::count());
        $this->assertStringContainsString('Manque 1 sur 3', Anomalie::firstOrFail()->description);
        $this->assertSame(0, Restitution::count());
        $this->assertSame(Commande::STATUT_PRETE, $commande->fresh()->statut);
        $this->get('/employe/commandes/'.$commande->idCommande)->assertOk()->assertSee('Restitution partielle');
    }

    public function test_restitution_complete_depuis_interface_recupere_commande_et_cree_restitution_conforme(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);
        [$commande, $articles] = $this->commandePrete($employe);
        $quantites = $articles->mapWithKeys(fn (Article $article) => [$article->idArticle => $article->quantite])->all();
        $url = '/employe/commandes/'.$commande->idCommande.'/restitution';

        $this->post($url, ['quantitesPresentees' => $quantites, 'observation' => 'Tout est présent'])
            ->assertRedirect('/employe/commandes/'.$commande->idCommande)
            ->assertSessionHas('restitution_resultat', 'Restitution conforme enregistrée.');
        $this->assertSame(Commande::STATUT_RECUPEREE, $commande->fresh()->statut);
        $this->assertSame('CONFORME', Restitution::where('idCommande', $commande->idCommande)->firstOrFail()->statut);
    }

    public function test_restitution_d_une_commande_d_un_autre_pressing_est_404(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);
        $autreEmploye = $this->creerEmploye();
        [$commande] = $this->commandePrete($autreEmploye);

        $this->post('/employe/commandes/'.$commande->idCommande.'/restitution', [
            'quantitesPresentees' => [],
        ])->assertNotFound();
        $this->assertSame(0, Anomalie::count());
        $this->assertSame(0, Restitution::count());
    }
}

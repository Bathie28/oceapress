<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Commande;
use App\Models\Facture;
use App\Models\HistoriqueCommande;
use App\Models\Pressing;
use App\Models\Utilisateur;
use App\Services\EnregistrerCommande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class EnregistrerCommandeTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function donneesClient(string $email): array
    {
        return ['nom' => 'Client', 'prenom' => 'Test', 'email' => $email, 'motDePasse' => 'secret'];
    }

    private function ajouterTarifPiece(Pressing $pressing, string $type, string $prix, string $formule = 'LAVAGE'): void
    {
        DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing,
            'typeArticle' => $type,
            'formule' => $formule,
            'unite' => 'PIECE',
            'prix' => $prix,
        ]);
    }

    public function test_cree_commande_articles_qr_historique_et_facture_avec_le_total_calcule(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        $this->ajouterTarifPiece($pressing, 'Chemise', '10.01');
        $this->ajouterTarifPiece($pressing, 'Pantalon', '2.35');

        $commande = app(EnregistrerCommande::class)->executer(
            $this->donneesClient('commande@example.test'),
            [
                ['typeArticle' => 'Chemise', 'quantite' => 3],
                ['typeArticle' => 'Pantalon', 'quantite' => 2],
            ],
            $employe->idEmploye,
            $pressing->idPressing,
            'LAVAGE',
        );

        $this->assertSame(2, Article::where('idCommande', $commande->idCommande)->count());
        $this->assertSame([3, 2], Article::where('idCommande', $commande->idCommande)->orderBy('idArticle')->pluck('quantite')->all());
        $this->assertTrue($commande->qrCodeActif()->exists());
        $this->assertSame(1, HistoriqueCommande::where('idCommande', $commande->idCommande)->count());
        $this->assertSame(Commande::STATUT_EN_ATTENTE, $commande->statut);
        $this->assertSame('LAVAGE', $commande->fresh()->formule);
        $this->assertNull($commande->fresh()->poids);
        $this->assertSame('34.73', $commande->fresh()->montantTotal);
        $this->assertDatabaseHas('facture', [
            'idCommande' => $commande->idCommande,
            'montantTotal' => '34.73',
            'statut' => 'EN_ATTENTE',
        ]);
    }

    public function test_reutilise_le_client_et_emet_une_facture_pour_chaque_commande(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        $this->ajouterTarifPiece($pressing, 'Chemise', '10.00');
        $donnees = $this->donneesClient('meme@example.test');
        $service = app(EnregistrerCommande::class);

        $premiere = $service->executer($donnees, [['typeArticle' => 'Chemise']], $employe->idEmploye, $pressing->idPressing);
        $seconde = $service->executer($donnees, [['typeArticle' => 'Chemise']], $employe->idEmploye, $pressing->idPressing);

        $this->assertSame($premiere->idClient, $seconde->idClient);
        $this->assertSame(1, Utilisateur::where('email', $donnees['email'])->count());
        $this->assertSame(1, Facture::where('idCommande', $premiere->idCommande)->count());
        $this->assertSame(1, Facture::where('idCommande', $seconde->idCommande)->count());
    }

    public function test_ne_prend_pas_en_compte_un_prix_saisi_dans_une_ligne(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        $this->ajouterTarifPiece($pressing, 'Chemise', '12.34');

        $commande = app(EnregistrerCommande::class)->executer(
            $this->donneesClient('prix-ignore@example.test'),
            [['typeArticle' => 'Chemise', 'prixUnitaire' => 9999.99]],
            $employe->idEmploye,
            $pressing->idPressing,
        );

        $article = Article::where('idCommande', $commande->idCommande)->firstOrFail();
        $this->assertNull($article->prixUnitaire);
        $this->assertSame('12.34', $commande->fresh()->montantTotal);
        $this->assertSame('12.34', Facture::where('idCommande', $commande->idCommande)->firstOrFail()->montantTotal);
    }

    public function test_commande_en_repassage_est_facturee_au_tarif_repassage(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        $this->ajouterTarifPiece($pressing, 'Chemise', '7.25', 'REPASSAGE');

        $commande = app(EnregistrerCommande::class)->executer(
            $this->donneesClient('commande-repassage@example.test'),
            [['typeArticle' => 'Chemise', 'quantite' => 2]],
            $employe->idEmploye,
            $pressing->idPressing,
            'REPASSAGE',
        );

        $this->assertSame('REPASSAGE', $commande->fresh()->formule);
        $this->assertSame('14.50', $commande->fresh()->montantTotal);
        $this->assertSame('14.50', Facture::where('idCommande', $commande->idCommande)->firstOrFail()->montantTotal);
    }

    public function test_refuse_un_email_d_un_employe_ou_administrateur_sans_commande(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        $this->ajouterTarifPiece($pressing, 'Chemise', '10.00');
        $this->assertEmailRefuse($employe->utilisateur->email, $employe->idEmploye, $pressing);

        $administrateur = $this->creerUtilisateur(['email' => 'admin@example.test']);
        DB::table('administrateur')->insert(['idAdministrateur' => $administrateur->idUtilisateur]);
        $this->assertEmailRefuse($administrateur->email, $employe->idEmploye, $pressing);
    }

    private function assertEmailRefuse(string $email, int $idEmploye, Pressing $pressing): void
    {
        $before = Commande::count();
        try {
            app(EnregistrerCommande::class)->executer(
                $this->donneesClient($email),
                [['typeArticle' => 'Chemise']],
                $idEmploye,
                $pressing->idPressing,
            );
            $this->fail('Une exception devait être levée.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Cet e-mail appartient', $exception->getMessage());
        }
        $this->assertSame($before, Commande::count());
    }
}

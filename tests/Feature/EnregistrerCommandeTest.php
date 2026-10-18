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

    private function ajouterTarifPiece(Pressing $pressing, string $type, string $prix, string $formule = 'REPASSAGE'): void
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
        $this->ajouterTarifPiece($pressing, 'Chemise', '1001');
        $this->ajouterTarifPiece($pressing, 'Pantalon', '235');

        $commande = app(EnregistrerCommande::class)->executer(
            $this->donneesClient('commande@example.test'),
            [
                ['typeArticle' => 'Chemise', 'quantite' => 3],
                ['typeArticle' => 'Pantalon', 'quantite' => 2],
            ],
            $employe->idEmploye,
            $pressing->idPressing,
            'PIECE', 'REPASSAGE',
        );

        $this->assertSame(2, Article::where('idCommande', $commande->idCommande)->count());
        $this->assertSame([3, 2], Article::where('idCommande', $commande->idCommande)->orderBy('idArticle')->pluck('quantite')->all());
        $this->assertTrue($commande->qrCodeActif()->exists());
        $this->assertSame(1, HistoriqueCommande::where('idCommande', $commande->idCommande)->count());
        $this->assertSame(Commande::STATUT_EN_ATTENTE, $commande->statut);
        $this->assertSame('REPASSAGE', $commande->fresh()->formule);
        $this->assertNull($commande->fresh()->poids);
        $this->assertSame('3473', $commande->fresh()->montantTotal);
        $this->assertDatabaseHas('facture', [
            'idCommande' => $commande->idCommande,
            'montantTotal' => '3473',
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
        $this->ajouterTarifPiece($pressing, 'Chemise', '1234');

        $commande = app(EnregistrerCommande::class)->executer(
            $this->donneesClient('prix-ignore@example.test'),
            [['typeArticle' => 'Chemise', 'prixUnitaire' => 9999.99]],
            $employe->idEmploye,
            $pressing->idPressing,
        );

        $article = Article::where('idCommande', $commande->idCommande)->firstOrFail();
        $this->assertNull($article->prixUnitaire);
        $this->assertSame('1234', $commande->fresh()->montantTotal);
        $this->assertSame('1234', Facture::where('idCommande', $commande->idCommande)->firstOrFail()->montantTotal);
    }

    public function test_commande_en_repassage_est_facturee_au_tarif_repassage(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        $this->ajouterTarifPiece($pressing, 'Chemise', '725', 'REPASSAGE');

        $commande = app(EnregistrerCommande::class)->executer(
            $this->donneesClient('commande-repassage@example.test'),
            [['typeArticle' => 'Chemise', 'quantite' => 2]],
            $employe->idEmploye,
            $pressing->idPressing,
            'PIECE', 'REPASSAGE',
        );

        $this->assertSame('REPASSAGE', $commande->fresh()->formule);
        $this->assertSame('1450', $commande->fresh()->montantTotal);
        $this->assertSame('1450', Facture::where('idCommande', $commande->idCommande)->firstOrFail()->montantTotal);
    }

    public function test_refuse_une_commande_poids_pour_un_pressing_sans_pesee(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        try {
            app(EnregistrerCommande::class)->executer($this->donneesClient('poids-refuse@example.test'), [], $employe->idEmploye, $pressing->idPressing, 'POIDS', 'LAVAGE', 2.0);
            $this->fail('Le mode poids devait être refusé pour ce pressing.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('poids', mb_strtolower($exception->getMessage()));
        }
        $this->assertSame(0, Commande::count());
    }

    public function test_refuse_une_formule_hors_mode(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        try {
            app(EnregistrerCommande::class)->executer($this->donneesClient('formule-refuse@example.test'), [['typeArticle' => 'Chemise']], $employe->idEmploye, $pressing->idPressing, 'PIECE', 'LAVAGE');
            $this->fail('La formule ne devait pas être autorisée.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('formule', mb_strtolower($exception->getMessage()));
        }
        $this->assertSame(0, Commande::count());
    }

    public function test_un_client_peut_avoir_une_commande_pesee_et_une_commande_a_la_piece(): void
    {
        $pressing = $this->creerPressing();
        $pressing->update(['pesee' => true]);
        $employe = $this->creerEmploye($pressing);
        DB::table('tarif')->insert([
            ['idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise', 'formule' => 'REPASSAGE', 'unite' => 'PIECE', 'prix' => 1500],
            ['idPressing' => $pressing->idPressing, 'typeArticle' => null, 'formule' => 'LAVAGE', 'unite' => 'KG', 'prix' => 1200],
        ]);
        $service = app(EnregistrerCommande::class);
        $client = $this->donneesClient('deux-modes@example.test');
        $piece = $service->executer($client, [['typeArticle' => 'Chemise', 'quantite' => 2]], $employe->idEmploye, $pressing->idPressing, 'PIECE', 'REPASSAGE');
        $poids = $service->executer($client, [['typeArticle' => 'Sac pesé']], $employe->idEmploye, $pressing->idPressing, 'POIDS', 'LAVAGE', 1.5);
        $this->assertSame('PIECE', $piece->fresh()->modeFacturation);
        $this->assertSame('POIDS', $poids->fresh()->modeFacturation);
        $this->assertSame('3000', $piece->fresh()->montantTotal);
        $this->assertSame('1800', $poids->fresh()->montantTotal);
        $this->assertSame(2, Facture::whereIn('idCommande', [$piece->idCommande, $poids->idCommande])->count());
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

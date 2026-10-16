<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Commande;
use App\Models\HistoriqueCommande;
use App\Models\Pressing;
use App\Models\Utilisateur;
use App\Services\EnregistrerCommande;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_cree_une_commande_avec_deux_articles_un_qr_actif_et_un_historique(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);

        $commande = app(EnregistrerCommande::class)->executer(
            $this->donneesClient('commande@example.test'),
            [['typeArticle' => 'Chemise'], ['typeArticle' => 'Pantalon']],
            $employe->idEmploye,
            $pressing->idPressing,
        );

        $this->assertSame(2, Article::where('idCommande', $commande->idCommande)->count());
        $this->assertTrue($commande->qrCodeActif()->exists());
        $this->assertSame(1, HistoriqueCommande::where('idCommande', $commande->idCommande)->count());
        $this->assertSame(Commande::STATUT_EN_ATTENTE, $commande->statut);
    }

    public function test_reutilise_le_client_pour_un_email_deja_connu(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        $donnees = $this->donneesClient('meme@example.test');
        $service = app(EnregistrerCommande::class);

        $premiere = $service->executer($donnees, [['typeArticle' => 'Chemise']], $employe->idEmploye, $pressing->idPressing);
        $seconde = $service->executer($donnees, [['typeArticle' => 'Veste']], $employe->idEmploye, $pressing->idPressing);

        $this->assertSame($premiere->idClient, $seconde->idClient);
        $this->assertSame(1, Utilisateur::where('email', $donnees['email'])->count());
    }

    public function test_refuse_un_email_d_un_employe_ou_administrateur_sans_commande(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        $this->assertEmailRefuse($employe->utilisateur->email, $employe->idEmploye, $pressing);

        $administrateur = $this->creerUtilisateur(['email' => 'admin@example.test']);
        \Illuminate\Support\Facades\DB::table('administrateur')->insert(['idAdministrateur' => $administrateur->idUtilisateur]);
        $this->assertEmailRefuse($administrateur->email, $employe->idEmploye, $pressing);
    }

    private function assertEmailRefuse(string $email, int $idEmploye, Pressing $pressing): void
    {
        $before = Commande::count();
        try {
            app(EnregistrerCommande::class)->executer($this->donneesClient($email), [['typeArticle' => 'Chemise']], $idEmploye, $pressing->idPressing);
            $this->fail('Une exception devait être levée.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Cet e-mail appartient', $exception->getMessage());
        }
        $this->assertSame($before, Commande::count());
    }
}

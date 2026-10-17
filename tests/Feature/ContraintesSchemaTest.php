<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Commande;
use App\Models\Facture;
use App\Models\HistoriqueCommande;
use App\Models\Paiement;
use App\Models\Pressing;
use App\Models\QrCode;
use App\Models\Restitution;
use App\Models\Utilisateur;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class ContraintesSchemaTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function assertDuplicateKey(callable $operation, string $contrainte): void
    {
        try {
            $operation();
            $this->fail("La contrainte {$contrainte} devait refuser l'insertion.");
        } catch (QueryException $exception) {
            $this->assertStringContainsString('1062', $exception->getMessage());
            $this->assertStringContainsString($contrainte, $exception->getMessage());
        }
    }

    private function assertCheckConstraint(callable $operation, string $contrainte): void
    {
        try {
            $operation();
            $this->fail("La contrainte {$contrainte} devait refuser l'insertion.");
        } catch (QueryException $exception) {
            $this->assertStringContainsString('3819', $exception->getMessage());
            $this->assertStringContainsString($contrainte, $exception->getMessage());
        }
    }

    private function creerFactureValide(?Commande $commande = null, ?string $numero = null): Facture
    {
        $commande ??= $this->creerCommande();

        return Facture::create([
            'numeroFacture' => $numero ?? 'FAC-'.strtoupper(bin2hex(random_bytes(6))),
            'montantTotal' => 1250,
            'statut' => 'EN_ATTENTE',
            'idCommande' => $commande->idCommande,
        ]);
    }

    private function creerPaiementValide(Facture $facture, ?string $reference = null): Paiement
    {
        return Paiement::create([
            'referencePaiement' => $reference ?? 'PAY-'.strtoupper(bin2hex(random_bytes(6))),
            'montant' => 1250,
            'methode' => 'WAVE',
            'statut' => 'EN_ATTENTE',
            'idFacture' => $facture->idFacture,
        ]);
    }

    public function test_unique_utilisateur_email(): void
    {
        $email = 'unique@example.test';
        $this->creerUtilisateur(['email' => $email]);

        $this->assertDuplicateKey(fn () => $this->creerUtilisateur(['email' => $email]), 'uq_utilisateur_email');
    }

    public function test_unique_commande_reference(): void
    {
        $commande = $this->creerCommande();

        $this->assertDuplicateKey(fn () => Commande::create([
            'referenceCommande' => $commande->referenceCommande,
            'statut' => 'EN_ATTENTE', 'montantTotal' => 0,
            'idPressing' => $commande->idPressing, 'idClient' => $commande->idClient,
        ]), 'uq_commande_reference');
    }

    public function test_unique_qr_code_token(): void
    {
        $commande = $this->creerCommande();
        $token = 'token-'.bin2hex(random_bytes(8));
        QrCode::create(['token' => $token, 'actif' => false, 'idCommande' => $commande->idCommande]);

        $this->assertDuplicateKey(fn () => QrCode::create([
            'token' => $token, 'actif' => false, 'idCommande' => $commande->idCommande,
        ]), 'uq_qr_code_token');
    }

    public function test_unique_qr_code_commande_active_sur_colonne_generee(): void
    {
        $commande = $this->creerCommande();
        QrCode::create(['token' => 'actif-'.bin2hex(random_bytes(8)), 'actif' => true, 'idCommande' => $commande->idCommande]);

        $this->assertDuplicateKey(fn () => QrCode::create([
            'token' => 'actif-'.bin2hex(random_bytes(8)), 'actif' => true, 'idCommande' => $commande->idCommande,
        ]), 'uq_qr_code_commande_active');
    }

    public function test_unique_facture_numero(): void
    {
        $numero = 'FAC-'.strtoupper(bin2hex(random_bytes(6)));
        $this->creerFactureValide(numero: $numero);
        $commandeSeconde = $this->creerCommande();

        $this->assertDuplicateKey(fn () => $this->creerFactureValide($commandeSeconde, $numero), 'uq_facture_numero');
    }

    public function test_unique_facture_commande(): void
    {
        $commande = $this->creerCommande();
        $this->creerFactureValide($commande);

        $this->assertDuplicateKey(fn () => $this->creerFactureValide($commande), 'uq_facture_commande');
    }

    public function test_unique_paiement_reference(): void
    {
        $reference = 'PAY-'.strtoupper(bin2hex(random_bytes(6)));
        $this->creerPaiementValide($this->creerFactureValide(), $reference);
        $this->creerPaiementValide($this->creerFactureValide());
        $factureSeconde = Facture::latest('idFacture')->firstOrFail();

        $this->assertDuplicateKey(fn () => $this->creerPaiementValide($factureSeconde, $reference), 'uq_paiement_reference');
    }

    public function test_unique_restitution_commande(): void
    {
        $commande = $this->creerCommande();
        $employe = $this->creerEmploye();
        Restitution::create(['statut' => 'CONFORME', 'idCommande' => $commande->idCommande, 'idEmploye' => $employe->idEmploye]);

        $this->assertDuplicateKey(fn () => Restitution::create([
            'statut' => 'CONFORME', 'idCommande' => $commande->idCommande, 'idEmploye' => $employe->idEmploye,
        ]), 'uq_restitution_commande');
    }

    public function test_check_pressing_statut(): void
    {
        $this->assertCheckConstraint(fn () => Pressing::create([
            'nom' => 'Invalid', 'adresse' => '1 rue Test', 'dateCreation' => now()->toDateString(), 'statut' => 'INVALIDE',
        ]), 'chk_pressing_statut');
    }

    public function test_check_pressing_mode_tarification(): void
    {
        $this->assertCheckConstraint(fn () => DB::table('pressing')->insert([
            'nom' => 'Invalid mode', 'adresse' => '1 rue Test',
            'dateCreation' => now()->toDateString(), 'statut' => 'ACTIF',
            'modeTarification' => 'INVALID',
        ]), 'chk_pressing_mode');
    }

    public function test_check_tarif_formule(): void
    {
        $pressing = $this->creerPressing();
        $this->assertCheckConstraint(fn () => DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing, 'typeArticle' => null,
            'formule' => 'INVENTE', 'unite' => 'KG', 'prix' => 10,
        ]), 'chk_tarif_formule');
    }

    public function test_formule_inventee_reste_refusee_pour_tarif_et_commande(): void
    {
        $pressing = $this->creerPressing();
        $this->assertCheckConstraint(fn () => DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise',
            'formule' => 'INVENTE', 'unite' => 'PIECE', 'prix' => 10,
        ]), 'chk_tarif_formule');

        $commande = $this->creerCommande(pressing: $pressing);
        $this->assertCheckConstraint(fn () => DB::table('commande')
            ->where('idCommande', $commande->idCommande)
            ->update(['formule' => 'INVENTE']), 'chk_commande_formule');
    }

    public function test_formule_repassage_est_acceptee_pour_tarif_et_commande(): void
    {
        $pressing = $this->creerPressing();
        DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise',
            'formule' => 'REPASSAGE', 'unite' => 'PIECE', 'prix' => 10,
        ]);

        $commande = $this->creerCommande(pressing: $pressing);
        DB::table('commande')->where('idCommande', $commande->idCommande)->update(['formule' => 'REPASSAGE']);

        $this->assertDatabaseHas('tarif', ['idPressing' => $pressing->idPressing, 'formule' => 'REPASSAGE']);
        $this->assertSame('REPASSAGE', $commande->fresh()->formule);
    }

    public function test_check_tarif_unite(): void
    {
        $pressing = $this->creerPressing();
        try {
            DB::table('tarif')->insert([
                'idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise',
                'formule' => 'LAVAGE', 'unite' => 'INVALIDE', 'prix' => 10,
            ]);
            $this->fail('La contrainte chk_tarif_unite devait refuser l\'insertion.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('3819', $exception->getMessage());
        }
    }

    public function test_check_tarif_prix(): void
    {
        $pressing = $this->creerPressing();
        $this->assertCheckConstraint(fn () => DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise',
            'formule' => 'LAVAGE', 'unite' => 'PIECE', 'prix' => -1,
        ]), 'chk_tarif_prix');
    }

    public function test_check_tarif_coherence_unite_type_article(): void
    {
        $pressing = $this->creerPressing();
        $this->assertCheckConstraint(fn () => DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing, 'typeArticle' => null,
            'formule' => 'LAVAGE', 'unite' => 'PIECE', 'prix' => 10,
        ]), 'chk_tarif_coherence');
    }

    public function test_check_commande_formule(): void
    {
        $commande = $this->creerCommande();
        $this->assertCheckConstraint(fn () => DB::table('commande')
            ->where('idCommande', $commande->idCommande)
            ->update(['formule' => 'INVALID']), 'chk_commande_formule');
    }

    public function test_check_commande_poids(): void
    {
        $commande = $this->creerCommande();
        $this->assertCheckConstraint(fn () => DB::table('commande')
            ->where('idCommande', $commande->idCommande)
            ->update(['poids' => -0.001]), 'chk_commande_poids');
    }

    public function test_check_article_quantite(): void
    {
        $commande = $this->creerCommande();
        $article = Article::create(['typeArticle' => 'Chemise', 'idCommande' => $commande->idCommande]);
        $this->assertCheckConstraint(fn () => DB::table('article')
            ->where('idArticle', $article->idArticle)
            ->update(['quantite' => 0]), 'chk_article_quantite');
    }

    public function test_check_commande_statut(): void
    {
        $commande = $this->creerCommande();
        $this->assertCheckConstraint(fn () => DB::table('commande')->where('idCommande', $commande->idCommande)->update(['statut' => 'INVALIDE']), 'chk_commande_statut');
    }

    public function test_check_historique_ancien_statut(): void
    {
        $commande = $this->creerCommande();
        $employe = $this->creerEmploye();
        $this->assertCheckConstraint(fn () => HistoriqueCommande::create([
            'ancienStatut' => 'INVALIDE', 'nouveauStatut' => 'EN_ATTENTE',
            'idCommande' => $commande->idCommande, 'idEmploye' => $employe->idEmploye,
        ]), 'chk_historique_ancien_statut');
    }

    public function test_check_historique_nouveau_statut(): void
    {
        $commande = $this->creerCommande();
        $employe = $this->creerEmploye();
        $this->assertCheckConstraint(fn () => HistoriqueCommande::create([
            'ancienStatut' => 'EN_ATTENTE', 'nouveauStatut' => 'INVALIDE',
            'idCommande' => $commande->idCommande, 'idEmploye' => $employe->idEmploye,
        ]), 'chk_historique_nouveau_statut');
    }

    public function test_check_facture_statut(): void
    {
        $commande = $this->creerCommande();
        $this->assertCheckConstraint(fn () => Facture::create([
            'numeroFacture' => 'FAC-'.strtoupper(bin2hex(random_bytes(6))),
            'montantTotal' => 100, 'statut' => 'INVALIDE', 'idCommande' => $commande->idCommande,
        ]), 'chk_facture_statut');
    }

    public function test_check_notification_canal(): void
    {
        $client = $this->creerClient();
        $this->assertCheckConstraint(fn () => DB::table('notification')->insert([
            'titre' => 'Notification', 'message' => 'Test', 'type' => 'INFO',
            'canal' => 'INVALIDE', 'idClient' => $client->idClient,
        ]), 'chk_notification_canal');
    }

    public function test_check_paiement_methode(): void
    {
        $facture = $this->creerFactureValide();
        $this->assertCheckConstraint(fn () => Paiement::create([
            'referencePaiement' => 'PAY-'.strtoupper(bin2hex(random_bytes(6))),
            'montant' => 100, 'methode' => 'INVALIDE', 'statut' => 'EN_ATTENTE', 'idFacture' => $facture->idFacture,
        ]), 'chk_paiement_methode');
    }

    public function test_check_paiement_statut(): void
    {
        $facture = $this->creerFactureValide();
        $this->assertCheckConstraint(fn () => Paiement::create([
            'referencePaiement' => 'PAY-'.strtoupper(bin2hex(random_bytes(6))),
            'montant' => 100, 'methode' => 'WAVE', 'statut' => 'INVALIDE', 'idFacture' => $facture->idFacture,
        ]), 'chk_paiement_statut');
    }

    public function test_check_restitution_statut(): void
    {
        $commande = $this->creerCommande();
        $employe = $this->creerEmploye();
        $this->assertCheckConstraint(fn () => Restitution::create([
            'statut' => 'INVALIDE', 'idCommande' => $commande->idCommande, 'idEmploye' => $employe->idEmploye,
        ]), 'chk_restitution_statut');
    }
}

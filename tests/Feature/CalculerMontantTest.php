<?php

namespace Tests\Feature;

use App\Models\Pressing;
use App\Services\CalculerMontant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class CalculerMontantTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function pressing(bool $pesee = false): Pressing
    {
        $pressing = $this->creerPressing();
        $pressing->update(['pesee' => $pesee]);
        return $pressing->fresh();
    }

    private function tarif(Pressing $pressing, ?string $type, string $formule, string $unite, int $prix): void
    {
        DB::table('tarif')->insert(['idPressing' => $pressing->idPressing, 'typeArticle' => $type, 'formule' => $formule, 'unite' => $unite, 'prix' => $prix]);
    }

    public function test_refuse_un_tarif_piece_manquant(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Pas de tarif pour Chemise en REPASSAGE');
        app(CalculerMontant::class)->executer($this->pressing(), 'PIECE', 'REPASSAGE', [['type' => 'Chemise', 'quantite' => 1]], null);
    }

    public function test_calcule_le_montant_piece_avec_les_quantites_en_francs_entiers(): void
    {
        $pressing = $this->pressing();
        $this->tarif($pressing, 'Chemise', 'REPASSAGE', 'PIECE', 1001);
        $this->tarif($pressing, 'Pantalon', 'REPASSAGE', 'PIECE', 235);
        $montant = app(CalculerMontant::class)->executer($pressing, 'PIECE', 'REPASSAGE', [
            ['type' => 'Chemise', 'quantite' => 3], ['type' => 'Pantalon', 'quantite' => 2],
        ], null);
        $this->assertSame(3473, $montant);
    }

    public function test_calcule_le_montant_poids_arrondi_au_franc_le_plus_proche(): void
    {
        $pressing = $this->pressing(true);
        $this->tarif($pressing, null, 'LAVAGE', 'KG', 1235);
        $montant = app(CalculerMontant::class)->executer($pressing, 'POIDS', 'LAVAGE', [], 0.5);
        $this->assertSame(618, $montant);
    }

    public function test_refuse_le_mode_poids_sans_poids_positif(): void
    {
        $pressing = $this->pressing(true);
        $this->tarif($pressing, null, 'LAVAGE', 'KG', 1000);
        foreach ([null, 0.0, -0.5] as $poids) {
            try {
                app(CalculerMontant::class)->executer($pressing, 'POIDS', 'LAVAGE', [], $poids);
                $this->fail('Un poids nul ou négatif devait être refusé.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('poids', mb_strtolower($exception->getMessage()));
            }
        }
    }

    public function test_refuse_une_formule_non_autorisee_pour_le_mode(): void
    {
        $this->expectException(RuntimeException::class);
        app(CalculerMontant::class)->executer($this->pressing(), 'PIECE', 'LAVAGE', [], null);
    }
}

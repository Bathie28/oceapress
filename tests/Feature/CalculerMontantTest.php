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

    private function pressingEnMode(string $mode): Pressing
    {
        $pressing = $this->creerPressing();
        DB::table('pressing')->where('idPressing', $pressing->idPressing)->update(['modeTarification' => $mode]);

        return $pressing->fresh();
    }

    private function ajouterTarif(Pressing $pressing, ?string $type, string $formule, string $unite, string $prix): void
    {
        DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing,
            'typeArticle' => $type,
            'formule' => $formule,
            'unite' => $unite,
            'prix' => $prix,
        ]);
    }

    public function test_refuse_un_tarif_piece_manquant(): void
    {
        $pressing = $this->pressingEnMode('PIECE');

        try {
            app(CalculerMontant::class)->executer($pressing, 'LAVAGE', [
                ['type' => 'Chemise', 'quantite' => 1],
            ], null);
            $this->fail('Un tarif manquant devait être refusé.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Pas de tarif pour Chemise en LAVAGE', $exception->getMessage());
        }
    }

    public function test_refuse_le_mode_poids_sans_poids_positif(): void
    {
        $pressing = $this->pressingEnMode('POIDS');
        $this->ajouterTarif($pressing, null, 'LAVAGE', 'KG', '10.00');

        foreach ([null, 0.0, -0.5] as $poids) {
            try {
                app(CalculerMontant::class)->executer($pressing, 'LAVAGE', [], $poids);
                $this->fail('Un poids nul ou négatif devait être refusé.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('poids', mb_strtolower($exception->getMessage()));
            }
        }
    }

    public function test_calcule_le_montant_piece_avec_les_quantites_en_centimes(): void
    {
        $pressing = $this->pressingEnMode('PIECE');
        $this->ajouterTarif($pressing, 'Chemise', 'LAVAGE', 'PIECE', '10.01');
        $this->ajouterTarif($pressing, 'Pantalon', 'LAVAGE', 'PIECE', '2.35');

        $montant = app(CalculerMontant::class)->executer($pressing, 'LAVAGE', [
            ['type' => 'Chemise', 'quantite' => 3],
            ['type' => 'Pantalon', 'quantite' => 2],
        ], null);

        $this->assertSame(34.73, $montant);
    }

    public function test_calcule_le_montant_poids_en_centimes(): void
    {
        $pressing = $this->pressingEnMode('POIDS');
        $this->ajouterTarif($pressing, null, 'LAVAGE', 'KG', '12.34');

        $montant = app(CalculerMontant::class)->executer($pressing, 'LAVAGE', [], 0.375);

        $this->assertSame(4.63, $montant);
    }

    public function test_calcule_un_tarif_repassage_a_la_piece(): void
    {
        $pressing = $this->pressingEnMode('PIECE');
        $this->ajouterTarif($pressing, 'Chemise', 'REPASSAGE', 'PIECE', '7.25');

        $montant = app(CalculerMontant::class)->executer($pressing, 'REPASSAGE', [
            ['type' => 'Chemise', 'quantite' => 2],
        ], null);

        $this->assertSame(14.50, $montant);
    }
}

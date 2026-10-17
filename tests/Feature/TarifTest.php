<?php

namespace Tests\Feature;

use App\Models\Pressing;
use App\Services\EnregistrerTarif;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class TarifTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    public function test_refuse_un_second_tarif_kg_pour_le_meme_pressing_et_la_meme_formule(): void
    {
        $pressing = $this->creerPressing();
        DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing,
            'typeArticle' => null,
            'formule' => 'LAVAGE',
            'unite' => 'KG',
            'prix' => '12.50',
        ]);

        try {
            app(EnregistrerTarif::class)->executer($pressing, null, 'LAVAGE', 'KG', 15.00);
            $this->fail('Un second tarif KG pour la même formule devait être refusé.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('tarif kg existe déjà', mb_strtolower($exception->getMessage()));
        }

        $this->assertSame(1, DB::table('tarif')->where('idPressing', $pressing->idPressing)->where('formule', 'LAVAGE')->where('unite', 'KG')->count());
    }
}

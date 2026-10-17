<?php

namespace Tests\Feature;

use App\Models\Facture;
use App\Models\Pressing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreerComptesDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cree_une_grille_complete_et_des_commandes_demonstration_facturees_au_bon_total(): void
    {
        $this->assertSame('oceapress_test', config('database.connections.mysql.database'));

        Artisan::call('demo:creer-comptes');

        $pressing = Pressing::where('nom', 'Pressing Démo')->firstOrFail();
        $tarifs = DB::table('tarif')->where('idPressing', $pressing->idPressing)->get();
        $this->assertCount(6, $tarifs);
        $this->assertSame(4, $tarifs->where('unite', 'PIECE')->count());
        $this->assertSame(2, $tarifs->where('unite', 'KG')->count());
        $this->assertSame(2, DB::table('commande')->where('idPressing', $pressing->idPressing)->count());
        $factures = Facture::query()
            ->join('commande', 'commande.idCommande', '=', 'facture.idCommande')
            ->where('commande.idPressing', $pressing->idPressing)
            ->orderBy('facture.idFacture')
            ->get(['facture.montantTotal']);
        $this->assertCount(2, $factures);
        $this->assertSame(['40.00', '40.00'], $factures->pluck('montantTotal')->all());
    }
}

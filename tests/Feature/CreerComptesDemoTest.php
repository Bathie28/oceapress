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
        $this->assertTrue($pressing->pesee);
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
        $this->assertSame(['4000', '1600'], $factures->pluck('montantTotal')->all());
        $commandes = DB::table('commande')->where('idPressing', $pressing->idPressing)->orderBy('idCommande')->get();
        $this->assertSame(['PIECE', 'POIDS'], $commandes->pluck('modeFacturation')->all());
        $this->assertSame(['Chemise', 'Pantalon'], DB::table('article')->where('idCommande', $commandes[0]->idCommande)->orderBy('idArticle')->pluck('typeArticle')->all());
        $this->assertSame(['Chemise', 'Pantalon'], DB::table('article')->where('idCommande', $commandes[1]->idCommande)->orderBy('idArticle')->pluck('typeArticle')->all());
        $this->assertSame([1, 1], DB::table('article')->where('idCommande', $commandes[1]->idCommande)->orderBy('idArticle')->pluck('quantite')->all());
    }

    public function test_un_second_lancement_ne_modifie_pas_les_comptes_ou_tarifs_existants(): void
    {
        Artisan::call('demo:creer-comptes');
        $comptes = DB::table('utilisateur')->count();
        $pressings = DB::table('pressing')->count();
        $tarifs = DB::table('tarif')->count();
        $commandes = DB::table('commande')->count();

        Artisan::call('demo:creer-comptes');

        $this->assertSame($comptes, DB::table('utilisateur')->count());
        $this->assertSame($pressings, DB::table('pressing')->count());
        $this->assertSame($tarifs, DB::table('tarif')->count());
        $this->assertSame($commandes, DB::table('commande')->count());
        $this->assertStringContainsString('Comptes déjà présents', Artisan::output());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\Client;
use App\Models\Pressing;
use App\Models\Utilisateur;
use App\Services\EnregistrerCommande;
use App\Services\RechercherClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class RechercherClientTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    public function test_recherche_par_email_puis_telephone_retourne_le_meme_compte(): void
    {
        $utilisateur = $this->creerUtilisateur([
            'email' => 'unique.client@example.test',
            'telephone' => '77 123 45 67',
        ]);
        Client::create(['idClient' => $utilisateur->idUtilisateur, 'dateInscription' => now()->toDateString()]);

        $parEmail = app(RechercherClient::class)->executer(' UNIQUE.CLIENT@example.test ', null);
        $parTelephoneLocal = app(RechercherClient::class)->executer(null, '77-123-45-67');
        $parTelephoneInternational = app(RechercherClient::class)->executer(null, '+221 77 123 45 67');

        $this->assertSame($utilisateur->idUtilisateur, $parEmail?->idUtilisateur);
        $this->assertSame($utilisateur->idUtilisateur, $parTelephoneLocal?->idUtilisateur);
        $this->assertSame($utilisateur->idUtilisateur, $parTelephoneInternational?->idUtilisateur);
        $this->assertSame(1, Utilisateur::where('email', 'unique.client@example.test')->count());
    }

    public function test_commande_reutilise_le_compte_trouve_par_telephone_sans_nouveau_mot_de_passe(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        $client = $this->creerClient(['email' => 'deja-client@example.test', 'telephone' => '77 000 00 02']);
        DB::table('tarif')->insert([
            'idPressing' => $pressing->idPressing,
            'typeArticle' => 'Chemise', 'formule' => 'REPASSAGE', 'unite' => 'PIECE', 'prix' => 1000,
        ]);

        $commande = app(EnregistrerCommande::class)->executer([
            'nom' => 'Nom saisi', 'prenom' => 'Prénom saisi', 'email' => 'nouvel-email@example.test', 'telephone' => '+221 77 000 00 02',
        ], [['type' => 'Chemise']], $employe->idEmploye, $pressing->idPressing);

        $this->assertSame($client->idClient, $commande->idClient);
        $this->assertNull($commande->motDePasseProvisoire);
        $this->assertSame(1, Utilisateur::where('telephone', '77 000 00 02')->count());
    }

    public function test_recherche_d_un_autre_pressing_ne_renvoie_que_nom_et_telephone(): void
    {
        $pressingOrigine = $this->creerPressing();
        $client = $this->creerClient([
            'nom' => 'Confidentiel', 'prenom' => 'Mina', 'email' => 'mina@example.test', 'telephone' => '07-00-00-03',
        ]);
        $commandeAilleurs = $this->creerCommande($client, $pressingOrigine);
        $pressingCourant = $this->creerPressing();
        $employe = $this->creerEmploye($pressingCourant);

        $response = $this->actingAs($employe->utilisateur)
            ->getJson('/employe/clients/rechercher?email=mina%40example.test');

        $response->assertOk()->assertExactJson(['nom' => 'Mina Confidentiel', 'telephone' => '07-00-00-03']);
        $this->assertStringNotContainsString($commandeAilleurs->referenceCommande, $response->getContent());
        $response->assertJsonMissingPath('email')->assertJsonMissingPath('commandes');
    }

    public function test_client_voit_ses_commandes_de_tous_les_pressings(): void
    {
        $client = $this->creerClient();
        $premiere = $this->creerCommande($client, $this->creerPressing());
        $seconde = $this->creerCommande($client, $this->creerPressing());

        $response = $this->actingAs($client->utilisateur)->get('/client/commandes')->assertOk();

        $response->assertSee($premiere->referenceCommande)->assertSee($seconde->referenceCommande);
        $this->assertSame(2, Commande::where('idClient', $client->idClient)->count());
    }
}

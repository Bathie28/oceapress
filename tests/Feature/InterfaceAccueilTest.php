<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\Notification;
use App\Models\Pressing;
use App\Models\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class InterfaceAccueilTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    public function test_chaque_accueil_repond_200_et_utilise_la_barre_laterale_active(): void
    {
        $client = $this->creerClient();
        $this->actingAs($client->utilisateur)
            ->get(route('client.accueil'))
            ->assertOk()
            ->assertSee('data-layout="connecte"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('Accueil client');

        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur)
            ->get(route('employe.accueil'))
            ->assertOk()
            ->assertSee('data-layout="connecte"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('Accueil employé');

        $administrateur = $this->creerUtilisateur(['email' => 'admin-interface@example.test']);
        DB::table('administrateur')->insert(['idAdministrateur' => $administrateur->idUtilisateur]);
        $this->actingAs($administrateur)
            ->get(route('admin.accueil'))
            ->assertOk()
            ->assertSee('data-layout="connecte"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('Accueil administrateur');
    }

    public function test_client_affiche_le_nombre_de_commandes_en_cours_et_une_commande_prete(): void
    {
        $client = $this->creerClient();
        $pressing = $this->creerPressing();
        $enAttente = $this->creerCommande($client, $pressing, Commande::STATUT_EN_ATTENTE);
        $enTraitement = $this->creerCommande($client, $pressing, Commande::STATUT_EN_TRAITEMENT);
        $prete = $this->creerCommande($client, $pressing, Commande::STATUT_PRETE);

        Notification::create([
            'titre' => 'Commande prête',
            'message' => 'Votre commande est prête.',
            'type' => 'STATUT',
            'canal' => 'IN_APP',
            'dateEnvoi' => now(),
            'lu' => false,
            'idClient' => $client->idClient,
            'idCommande' => $prete->idCommande,
        ]);
        QrCode::create([
            'token' => 'qr-client-interface',
            'actif' => true,
            'idCommande' => $enTraitement->idCommande,
        ]);

        $this->actingAs($client->utilisateur)
            ->get(route('client.accueil'))
            ->assertOk()
            ->assertSee('2')
            ->assertSee($prete->referenceCommande)
            ->assertSee('Accès au QR code');
    }

    public function test_client_sans_commande_affiche_un_etat_vide(): void
    {
        $client = $this->creerClient();

        $this->actingAs($client->utilisateur)
            ->get(route('client.accueil'))
            ->assertOk()
            ->assertSee('Aucune commande en cours');
    }

    public function test_employe_affiche_les_compteurs_du_seul_pressing(): void
    {
        $pressing = $this->creerPressing();
        $autrePressing = Pressing::create([
            'nom' => 'Autre pressing',
            'adresse' => '2 rue Autre',
            'dateCreation' => now()->toDateString(),
            'statut' => Pressing::STATUT_ACTIF,
        ]);
        $employe = $this->creerEmploye($pressing);

        $this->creerCommande(null, $pressing, Commande::STATUT_EN_ATTENTE);
        $this->creerCommande(null, $pressing, Commande::STATUT_EN_TRAITEMENT);
        $this->creerCommande(null, $pressing, Commande::STATUT_PRETE);
        $this->creerCommande(null, $autrePressing, Commande::STATUT_EN_ATTENTE);
        $this->creerCommande(null, $autrePressing, Commande::STATUT_EN_ATTENTE);

        $this->actingAs($employe->utilisateur)
            ->get(route('employe.accueil'))
            ->assertOk()
            ->assertViewHas('compteurs', function (array $compteurs): bool {
                return $compteurs[Commande::STATUT_EN_ATTENTE] === 1
                    && $compteurs[Commande::STATUT_EN_TRAITEMENT] === 1
                    && $compteurs[Commande::STATUT_PRETE] === 1;
            });
    }

    public function test_administrateur_affiche_les_pressings_qu_il_gere(): void
    {
        $administrateur = $this->creerUtilisateur(['email' => 'admin-accueil@example.test']);
        DB::table('administrateur')->insert(['idAdministrateur' => $administrateur->idUtilisateur]);
        $pressing = Pressing::create([
            'nom' => 'Pressing Géré',
            'adresse' => '10 avenue Gérée',
            'dateCreation' => now()->toDateString(),
            'statut' => Pressing::STATUT_ACTIF,
        ]);
        $actif = $this->creerEmploye($pressing);
        DB::table('employe')->where('idEmploye', $actif->idEmploye)->update(['actif' => true]);
        $inactif = $this->creerEmploye($pressing);
        DB::table('employe')->where('idEmploye', $inactif->idEmploye)->update(['actif' => false]);

        $this->actingAs($administrateur)
            ->get(route('admin.accueil'))
            ->assertOk()
            ->assertSee('Pressing Géré')
            ->assertViewHas('pressings', function ($pressings) use ($pressing): bool {
                return $pressings->contains(fn ($item) => $item->idPressing === $pressing->idPressing);
            });
    }
}

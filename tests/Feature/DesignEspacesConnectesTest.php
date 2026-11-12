<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\Notification;
use App\Models\Pressing;
use App\Models\QrCode;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class DesignEspacesConnectesTest extends TestCase
{
    use CreeDonneesTest, RefreshDatabase;

    private function creerAdministrateur(): Utilisateur
    {
        $utilisateur = $this->creerUtilisateur(['email' => 'admin-design@example.test']);
        DB::table('administrateur')->insert(['idAdministrateur' => $utilisateur->idUtilisateur]);

        return $utilisateur;
    }

    /** @return array<string, array{0: Utilisateur, 1: string}> */
    private function accueils(): array
    {
        return [
            'client' => [$this->creerClient()->utilisateur, route('client.accueil')],
            'employé' => [$this->creerEmploye()->utilisateur, route('employe.accueil')],
            'administrateur' => [$this->creerAdministrateur(), route('admin.accueil')],
        ];
    }

    public function test_chaque_accueil_connecte_contient_la_barre_laterale_l_en_tete_et_le_bouton_menu(): void
    {
        foreach ($this->accueils() as $role => [$utilisateur, $url]) {
            $html = $this->actingAs($utilisateur)->get($url)->assertOk()->getContent();

            $this->assertMatchesRegularExpression('/<aside id="sidebar-connecte"[^>]*class="[^"]*bg-op-sidebar[^"]*"[^>]*aria-label="Navigation latérale"/u', $html, "Barre latérale absente ({$role}).");
            $this->assertMatchesRegularExpression('/<header[^>]*>.*?<button id="ouvrir-menu-connecte"[^>]*aria-expanded="false"[^>]*>.*?<span class="sr-only">Menu<\/span><\/button>.*?<\/header>/su', $html, "En-tête ou bouton Menu absent ({$role}).");
            $this->assertStringContainsString('aria-label="Fil d’Ariane"', $html, "Fil d’Ariane absent ({$role}).");
            $this->assertMatchesRegularExpression('/data-avatar><span aria-hidden="true">UT<\/span>/', $html, "Initiales absentes ({$role}).");
            $this->assertStringNotContainsString('<img', $html, "Aucune image externe attendue ({$role}).");
        }
    }

    public function test_la_deconnexion_reste_un_formulaire_post_avec_jeton_csrf_sur_chaque_accueil(): void
    {
        foreach ($this->accueils() as $role => [$utilisateur, $url]) {
            $html = $this->actingAs($utilisateur)->get($url)->assertOk()->getContent();

            $this->assertMatchesRegularExpression(
                '/<form[^>]*method="POST"[^>]*action="[^"]*\/logout"[^>]*>\s*<input[^>]*type="hidden"[^>]*name="_token"[^>]*value="[^"]+"[^>]*>.*?<button[^>]*type="submit"[^>]*>.*?Se déconnecter.*?<\/button>\s*<\/form>/su',
                $html,
                "Formulaire de déconnexion incorrect ({$role}).",
            );
            $this->assertSame(1, substr_count($html, '/logout'), "Une seule déconnexion attendue ({$role}).");
            $this->assertStringNotContainsString('href="'.route('logout').'"', $html);
        }

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_la_barre_laterale_regroupe_le_menu_et_marque_la_page_active_en_bleu(): void
    {
        $employe = $this->creerEmploye();

        $html = $this->actingAs($employe->utilisateur)->get(route('employe.accueil'))->assertOk()->getContent();

        $this->assertStringContainsString('Vue d’ensemble', $html);
        $this->assertStringContainsString('Opérations', $html);
        $this->assertMatchesRegularExpression('/<a href="[^"]*\/employe"\s+aria-current="page"\s+class="[^"]*bg-\[#2563EB\] !text-white[^"]*">/', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
    }

    private function valeurIndicateur(string $html, string $nom): string
    {
        $this->assertMatchesRegularExpression('/data-stat="'.preg_quote($nom, '/').'".*?data-stat-value>([^<]*)</s', $html, "Indicateur {$nom} absent.");
        preg_match('/data-stat="'.preg_quote($nom, '/').'".*?data-stat-value>([^<]*)</s', $html, $resultat);

        return trim($resultat[1]);
    }

    public function test_accueil_employe_affiche_les_compteurs_issus_de_la_base(): void
    {
        $pressing = $this->creerPressing();
        $employe = $this->creerEmploye($pressing);
        $autrePressing = $this->creerPressing();
        foreach (range(1, 3) as $i) {
            $this->creerCommande(null, $pressing, Commande::STATUT_EN_ATTENTE);
        }
        $this->creerCommande(null, $pressing, Commande::STATUT_EN_TRAITEMENT);
        $this->creerCommande(null, $pressing, Commande::STATUT_PRETE);
        $this->creerCommande(null, $pressing, Commande::STATUT_PRETE);
        $this->creerCommande(null, $autrePressing, Commande::STATUT_EN_ATTENTE);

        $html = $this->actingAs($employe->utilisateur)->get(route('employe.accueil'))->assertOk()->getContent();

        $this->assertSame('3', $this->valeurIndicateur($html, 'en-attente'));
        $this->assertSame('1', $this->valeurIndicateur($html, 'en-traitement'));
        $this->assertSame('2', $this->valeurIndicateur($html, 'pretes'));
        $this->assertSame('2', $this->valeurIndicateur($html, 'non-recuperees'));
        $this->assertMatchesRegularExpression('/data-stat="en-attente".*?data-stat-progress="50".*?50 % des commandes en cours/s', $html);
        $this->assertMatchesRegularExpression('/data-stat="en-traitement".*?data-stat-progress="17"/s', $html);
        $this->assertStringContainsString('Commandes enregistrées sur 7 jours', $html);
        $this->assertStringContainsString('data-day="'.now()->toDateString().'" data-commandes-jour="6"', $html);
        $this->assertStringContainsString('Commandes prêtes à relancer', $html);
    }

    public function test_accueil_client_affiche_les_compteurs_issus_de_la_base(): void
    {
        $client = $this->creerClient();
        $pressing = $this->creerPressing();
        $this->creerCommande($client, $pressing, Commande::STATUT_EN_ATTENTE);
        $enTraitement = $this->creerCommande($client, $pressing, Commande::STATUT_EN_TRAITEMENT);
        $this->creerCommande($client, $pressing, Commande::STATUT_PRETE);
        $this->creerCommande($this->creerClient(), $pressing, Commande::STATUT_EN_ATTENTE);
        QrCode::create(['token' => 'qr-design-client', 'actif' => true, 'idCommande' => $enTraitement->idCommande]);
        foreach (range(1, 7) as $i) {
            Notification::create([
                'titre' => "Notification {$i}", 'message' => 'Message', 'type' => 'STATUT', 'canal' => 'IN_APP',
                'dateEnvoi' => now(), 'lu' => $i === 7, 'idClient' => $client->idClient,
            ]);
        }

        $html = $this->actingAs($client->utilisateur)->get(route('client.accueil'))->assertOk()->getContent();

        $this->assertSame('2', $this->valeurIndicateur($html, 'commandes-en-cours'));
        $this->assertSame('1', $this->valeurIndicateur($html, 'commandes-pretes'));
        $this->assertSame('6', $this->valeurIndicateur($html, 'notifications-non-lues'));
        $this->assertMatchesRegularExpression('/data-compteur-notifications[^>]*>6<\/span>/', $html);
        $this->assertMatchesRegularExpression('/data-stat="commandes-en-cours".*?data-stat-progress="67".*?67 % de vos commandes/s', $html);
        $this->assertStringContainsString('Accès au QR code', $html);
        $this->assertStringContainsString($enTraitement->referenceCommande, $html);
        $this->assertStringContainsString('href="'.route('client.commandes.show', $enTraitement->idCommande).'"', $html);
    }

    public function test_accueil_administrateur_affiche_les_compteurs_issus_de_la_base(): void
    {
        $administrateur = $this->creerAdministrateur();
        $this->actingAs($administrateur);
        $premier = $this->creerPressing();
        $second = $this->creerPressing();
        $this->creerEmploye($premier);
        $this->creerEmploye($premier);
        $this->creerEmploye($second);
        $this->creerEmploye($second)->update(['actif' => false]);
        Pressing::create(['nom' => 'Pressing non géré', 'adresse' => 'Ailleurs', 'dateCreation' => now()->toDateString(), 'statut' => 'ACTIF']);

        $html = $this->get(route('admin.accueil'))->assertOk()->getContent();

        $this->assertSame('2', $this->valeurIndicateur($html, 'pressings-geres'));
        $this->assertSame('3', $this->valeurIndicateur($html, 'employes-actifs'));
        $this->assertSame('1', $this->valeurIndicateur($html, 'employes-inactifs'));
        $this->assertStringNotContainsString('data-stat-progress', $html, 'Aucune barre de progression attendue sur l’accueil administrateur.');
        $this->assertStringNotContainsString('role="progressbar"', $html);
        $this->assertStringNotContainsString('75 % des employés', $html, 'Aucune légende attendue sous la carte des employés actifs.');
        $this->assertStringNotContainsString('25 % des employés', $html, 'Aucune légende attendue sous la carte des employés inactifs.');
        $this->assertDoesNotMatchRegularExpression('/\d+\s%\sdes employés/u', $html);
        $this->assertStringContainsString('Pressing Test', $html);
        $this->assertStringNotContainsString('Pressing non géré', $html);
    }

    public function test_aucune_page_connectee_ne_contient_de_texte_d_exemple_ni_de_montant_fictif(): void
    {
        $pages = [
            [$this->creerClient()->utilisateur, ['client.accueil', 'client.commandes.index', 'client.notifications.index', 'client.mot-de-passe.edit']],
            [$this->creerEmploye()->utilisateur, ['employe.accueil', 'employe.tableau-de-bord', 'employe.commandes.index', 'employe.commandes.create', 'employe.scan', 'employe.mot-de-passe.edit']],
            [$this->creerAdministrateur(), ['admin.accueil', 'admin.pressings.index', 'admin.pressings.create']],
        ];

        foreach ($pages as [$utilisateur, $routes]) {
            foreach ($routes as $nomRoute) {
                $html = $this->actingAs($utilisateur)->get(route($nomRoute))->assertOk()->getContent();
                $texte = html_entity_decode(strip_tags(preg_replace('/<(script|style)\b.*?<\/\1>/s', '', $html)));

                $this->assertDoesNotMatchRegularExpression('/lorem ipsum|john doe|jane doe|texte d.exemple|donn[ée]es? fictives?|example\.com/iu', $texte, "Texte d’exemple sur {$nomRoute}.");
                $this->assertDoesNotMatchRegularExpression('/\d[\d\s.,]*\s?(€|\$|F\s?CFA|FCFA|XOF|EUR|USD)/u', $texte, "Montant affiché sans donnée sur {$nomRoute}.");

                preg_match_all('/data-stat-value>([^<]*)</', $html, $valeurs);
                foreach ($valeurs[1] as $valeur) {
                    $this->assertSame('0', trim($valeur), "Indicateur non nul sans donnée sur {$nomRoute}.");
                }
            }
        }
    }

    public function test_la_carte_d_indicateur_affiche_titre_valeur_et_icone_dans_un_carre_teinte(): void
    {
        $html = Blade::render('<x-stat-card name="en-attente" title="En attente" :value="$nombre" icon="clock" tint="orange" />', ['nombre' => 7]);

        $this->assertStringContainsString('data-stat="en-attente"', $html);
        $this->assertStringContainsString('En attente', $html);
        $this->assertMatchesRegularExpression('/<p[^>]*text-3xl[^>]*data-stat-value>7<\/p>/', $html);
        $this->assertMatchesRegularExpression('/<span class="[^"]*h-12 w-12[^"]*rounded-xl bg-op-tint-orange text-op-icon-orange"[^>]*data-stat-icon><svg/', $html);
    }

    public function test_la_carte_de_section_affiche_titre_action_a_droite_et_contenu(): void
    {
        $html = Blade::render(<<<'BLADE'
<x-section-card title="Pressings" id="pressings-titre">
    <x-slot:action><a href="/admin/pressings">Tout voir</a></x-slot:action>
    <p>Contenu de la section</p>
</x-section-card>
BLADE);

        $this->assertMatchesRegularExpression('/<section[^>]*aria-labelledby="pressings-titre"/', $html);
        $this->assertMatchesRegularExpression('/<h2[^>]*id="pressings-titre"[^>]*>Pressings<\/h2>\s*<div[^>]*data-section-action><a href="\/admin\/pressings">Tout voir<\/a><\/div>/', $html);
        $this->assertStringContainsString('justify-between', $html);
        $this->assertStringContainsString('<p>Contenu de la section</p>', $html);
    }
}

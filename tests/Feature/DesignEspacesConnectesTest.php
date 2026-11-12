<?php

namespace Tests\Feature;

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
            $this->assertMatchesRegularExpression('/<header[^>]*>.*?<button id="ouvrir-menu-connecte"[^>]*aria-expanded="false"[^>]*>Menu<\/button>.*?<\/header>/su', $html, "En-tête ou bouton Menu absent ({$role}).");
            $this->assertStringContainsString('id="recherche-rapide-champ"', $html, "Champ de recherche absent ({$role}).");
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

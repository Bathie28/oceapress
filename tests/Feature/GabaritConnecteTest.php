<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class GabaritConnecteTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function rendreGabarit(): string
    {
        return Blade::render(<<<'BLADE'
@extends('layouts.connecte')
@section('title', 'Titre de test')
@section('pageTitle', 'Titre de page')
@section('content')<div>Contenu de test</div>@endsection
BLADE);
    }

    public function test_un_employe_voit_la_barre_laterale_employe(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);

        $html = $this->rendreGabarit();

        $this->assertStringContainsString('data-layout="connecte"', $html);
        $this->assertStringContainsString('Navigation employé', $html);
        foreach ([
            'Accueil employé',
            'Tableau de bord',
            'Commandes',
            'Nouvelle commande',
            'Scanner un QR code',
        ] as $libelle) {
            $this->assertStringContainsString($libelle, $html);
        }
        $this->assertStringContainsString('Employé', $html);
        $this->assertStringContainsString($employe->utilisateur->nom, $html);
    }

    public function test_un_client_ne_voit_pas_la_navigation_employe(): void
    {
        $client = $this->creerClient();
        $this->actingAs($client->utilisateur);

        $html = $this->rendreGabarit();

        $this->assertStringContainsString('Navigation client', $html);
        $this->assertStringContainsString('Mes commandes', $html);
        $this->assertStringContainsString('Mes notifications', $html);
        $this->assertStringContainsString('Client', $html);
        $this->assertStringContainsString($client->utilisateur->nom, $html);
        $this->assertStringNotContainsString('Scanner un QR code', $html);
        $this->assertStringNotContainsString('Nouvelle commande', $html);
    }

    public function test_un_administrateur_voit_la_navigation_administrateur(): void
    {
        $administrateur = $this->creerUtilisateur(['email' => 'admin-gabarit@example.test']);
        DB::table('administrateur')->insert(['idAdministrateur' => $administrateur->idUtilisateur]);
        $this->actingAs($administrateur);

        $html = $this->rendreGabarit();

        $this->assertStringContainsString('Navigation administrateur', $html);
        $this->assertStringContainsString('Accueil administrateur', $html);
        $this->assertStringContainsString('Gérer les pressings et tarifs', $html);
        $this->assertStringContainsString('Administrateur', $html);
        $this->assertStringContainsString($administrateur->nom, $html);
    }

    public function test_tableau_de_bord_employe_utilise_la_barre_laterale_sans_navigation_du_haut(): void
    {
        $employe = $this->creerEmploye();

        $reponse = $this->actingAs($employe->utilisateur)
            ->get(route('employe.tableau-de-bord'))
            ->assertOk()
            ->assertSee('data-layout="connecte"', false)
            ->assertSee('aria-label="Navigation latérale"', false)
            ->assertSee('aria-label="Ouvrir le menu"', false)
            ->assertSee('Menu')
            ->assertSee('Retour à l’accueil employé')
            ->assertDontSee('data-layout="oceapress"', false);

        $this->assertStringContainsString('<span aria-hidden="true">←</span>', $reponse->getContent());
        $this->assertStringContainsString('text-[#2563EB]', $reponse->getContent());
        $this->assertStringContainsString('hover:underline', $reponse->getContent());
        $this->assertStringNotContainsString('<x-secondary-button', $reponse->getContent());
    }

    public function test_la_deconnexion_est_un_formulaire_post_sans_dupliquer_l_utilisateur_dans_le_header(): void
    {
        $client = $this->creerClient(['nom' => 'Dupont', 'prenom' => 'Alice']);
        $this->actingAs($client->utilisateur);

        $html = $this->rendreGabarit();

        $this->assertStringContainsString('<aside id="sidebar-connecte"', $html);
        $this->assertStringContainsString('Alice Dupont', $html);
        $this->assertMatchesRegularExpression(
            '/<div class="[^"]*mt-auto[^"]*flex[^"]*flex-col[^"]*items-center[^"]*text-center[^"]*border-t/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/<form[^>]*method="POST"[^>]*action="[^"]*\/logout"[^>]*>.*?<input[^>]*type="hidden"[^>]*name="_token"/s',
            $html,
        );
        $this->assertStringContainsString('Se déconnecter', $html);
        $this->assertSame(1, substr_count($html, '/logout'));
        $headerStart = strpos($html, '<header');
        $headerEnd = strpos($html, '</header>', $headerStart);
        $header = substr($html, $headerStart, $headerEnd - $headerStart);
        $this->assertStringNotContainsString('Alice Dupont', $header);
        $this->assertStringNotContainsString('Se déconnecter', $header);
    }

    public function test_menu_mobile_et_lien_actif_sont_accessibles(): void
    {
        $employe = $this->creerEmploye();
        $this->actingAs($employe->utilisateur);

        $request = Request::create('/employe/tableau-de-bord', 'GET');
        $route = app(\Illuminate\Routing\Router::class)->getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $employe->utilisateur);
        app()->instance('request', $request);

        $html = $this->rendreGabarit();

        $this->assertStringContainsString('id="ouvrir-menu-connecte"', $html);
        $this->assertStringContainsString('aria-label="Ouvrir le menu"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('Tableau de bord', $html);
        $this->assertStringContainsString("bg-[#2563EB] !text-white", $html);
        $this->assertStringContainsString('lg:sticky', $html);
        $this->assertStringContainsString('lg:flex', $html);
        $this->assertStringContainsString('lg:hidden', $html);
        $this->assertStringContainsString('sidebar.querySelector', $html);
        $this->assertStringContainsString('openButton.focus()', $html);
    }
}

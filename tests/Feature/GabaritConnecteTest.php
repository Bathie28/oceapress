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
    }

    public function test_un_client_ne_voit_pas_la_navigation_employe(): void
    {
        $client = $this->creerClient();
        $this->actingAs($client->utilisateur);

        $html = $this->rendreGabarit();

        $this->assertStringContainsString('Navigation client', $html);
        $this->assertStringContainsString('Mes commandes', $html);
        $this->assertStringContainsString('Mes notifications', $html);
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
    }
}

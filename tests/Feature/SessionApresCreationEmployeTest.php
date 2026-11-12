<?php

namespace Tests\Feature;

use App\Models\Pressing;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SessionApresCreationEmployeTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_employe_conserve_la_session_administrateur(): void
    {
        Artisan::call('demo:creer-comptes');

        $administrateur = Utilisateur::where('email', 'admin@demo.test')->firstOrFail();
        $pressing = Pressing::where('nom', 'Pressing Démo')->firstOrFail();

        $this->post('/login', [
            'email' => $administrateur->email,
            'password' => 'Demo2026!',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($administrateur);

        $this->post("/admin/pressings/{$pressing->idPressing}/employes", [
            'nom' => 'Session',
            'prenom' => 'Test',
            'email' => 'session-reproduction@example.test',
            'telephone' => '0700000001',
            'fonction' => 'Caissier',
        ])->assertRedirect("/admin/pressings/{$pressing->idPressing}/edit");

        $this->get('/admin/pressings')->assertOk();
    }
}

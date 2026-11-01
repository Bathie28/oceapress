<?php

namespace Tests\Feature;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class ResetMotDePasseTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private const REPONSE_GENERIQUE = 'Si un compte existe, un e-mail a été envoyé';

    public function test_adresse_inconnue_repond_de_maniere_generique_sans_email(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'inconnu@example.test'])
            ->assertSessionHas('status', self::REPONSE_GENERIQUE);

        Notification::assertNothingSent();
    }

    public function test_adresse_connue_declenche_un_email_de_reinitialisation(): void
    {
        Notification::fake();
        $utilisateur = $this->creerUtilisateur(['email' => 'connu@example.test']);

        $this->post(route('password.email'), ['email' => 'connu@example.test'])
            ->assertSessionHas('status', self::REPONSE_GENERIQUE);

        Notification::assertSentTo($utilisateur, ResetPassword::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'connu@example.test']);
    }

    public function test_lien_valide_change_le_mot_de_passe_et_retire_l_ancien(): void
    {
        $utilisateur = $this->creerUtilisateur(['email' => 'valide@example.test', 'motDePasse' => 'ancien-secret']);
        $token = Password::broker()->createToken($utilisateur);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'valide@example.test',
            'password' => 'nouveau-secret-123',
            'password_confirmation' => 'nouveau-secret-123',
        ])->assertRedirect(route('login'));

        $this->assertFalse(Auth::attempt(['email' => 'valide@example.test', 'password' => 'ancien-secret']));
        $this->assertTrue(Auth::attempt(['email' => 'valide@example.test', 'password' => 'nouveau-secret-123']));
    }

    public function test_lien_expire_apres_plus_de_60_minutes(): void
    {
        $utilisateur = $this->creerUtilisateur(['email' => 'expire@example.test']);
        $token = Password::broker()->createToken($utilisateur);
        DB::table('password_reset_tokens')
            ->where('email', 'expire@example.test')
            ->update(['created_at' => now()->subMinutes(61)]);

        $this->from(route('password.reset', $token))->post(route('password.update'), [
            'token' => $token,
            'email' => 'expire@example.test',
            'password' => 'nouveau-secret-123',
            'password_confirmation' => 'nouveau-secret-123',
        ])->assertRedirect(route('password.reset', $token))
            ->assertSessionHasErrors(['email' => 'Lien expiré']);
    }

    public function test_lien_deja_utilise_est_refuse(): void
    {
        $utilisateur = $this->creerUtilisateur(['email' => 'utilise@example.test']);
        $token = Password::broker()->createToken($utilisateur);
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'utilise@example.test',
            'password' => 'nouveau-secret-123',
            'password_confirmation' => 'nouveau-secret-123',
        ])->assertRedirect(route('login'));

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'utilise@example.test',
            'password' => 'autre-secret-123',
            'password_confirmation' => 'autre-secret-123',
        ])->assertSessionHasErrors(['email']);
    }

    public function test_reinitialisation_desactive_doit_changer_mot_de_passe(): void
    {
        $utilisateur = $this->creerUtilisateur([
            'email' => 'provisoire@example.test',
            'doitChangerMotDePasse' => true,
        ]);
        $token = Password::broker()->createToken($utilisateur);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'provisoire@example.test',
            'password' => 'nouveau-secret-123',
            'password_confirmation' => 'nouveau-secret-123',
        ])->assertRedirect(route('login'));

        $this->assertFalse((bool) $utilisateur->fresh()->doitChangerMotDePasse);
    }
}

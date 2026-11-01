<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class ResetMotDePasseController extends Controller
{
    private const REPONSE_GENERIQUE = 'Si un compte existe, un e-mail a été envoyé';

    public function afficherDemande(): View
    {
        return view('auth.forgot-password');
    }

    public function envoyerLien(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink(['email' => $request->string('email')->toString()]);

        return back()->with('status', self::REPONSE_GENERIQUE);
    }

    public function afficherReinitialisation(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email', '')]);
    }

    public function reinitialiser(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($utilisateur, string $motDePasse): void {
                $utilisateur->forceFill([
                    'motDePasse' => $motDePasse,
                    'doitChangerMotDePasse' => false,
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Votre mot de passe a été réinitialisé.');
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => $status === Password::INVALID_TOKEN ? 'Lien expiré' : __($status),
        ]);
    }
}

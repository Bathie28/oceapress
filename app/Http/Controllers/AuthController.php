<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function afficherConnexion(): View
    {
        return view('auth.login');
    }

    public function connecter(Request $request): RedirectResponse
    {
        $identifiants = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($identifiants)) {
            return back()->withErrors(['email' => 'Identifiants incorrects'])->onlyInput('email');
        }

        $request->session()->regenerate();
        /** @var Utilisateur $utilisateur */
        $utilisateur = Auth::user();
        $utilisateur->load(['client', 'employe', 'administrateur']);
        $roles = collect([$utilisateur->client, $utilisateur->employe, $utilisateur->administrateur])
            ->filter()
            ->count();

        if ($roles > 1) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Ce compte ne peut pas être connecté.']);
        }

        if ($roles === 0) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Ce compte n’a pas d’espace associé']);
        }

        return redirect()->route($this->espaceDe($utilisateur));
    }

    public function deconnecter(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function espaceDe(Utilisateur $u): string
    {
        if ($u->client) {
            return 'client.accueil';
        }

        if ($u->employe) {
            return 'employe.accueil';
        }

        if ($u->administrateur) {
            return 'admin.accueil';
        }

        throw new \RuntimeException('Ce compte n’a pas d’espace associé');
    }
}

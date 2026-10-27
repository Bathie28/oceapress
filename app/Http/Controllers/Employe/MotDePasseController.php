<?php

namespace App\Http\Controllers\Employe;

use App\Http\Controllers\Controller;
use App\Services\ChangerMotDePasse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MotDePasseController extends Controller
{
    public function edit(): View
    {
        return view('employe.mot-de-passe');
    }

    public function update(Request $request, ChangerMotDePasse $changer): RedirectResponse
    {
        $donnees = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $changer->executer($request->user(), $donnees['password'], $donnees['password_confirmation']);

        return redirect()->route('employe.accueil')->with('status', 'Mot de passe modifié.');
    }
}

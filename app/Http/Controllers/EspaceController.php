<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class EspaceController extends Controller
{
    public function clientAccueil(Request $request): View
    {
        return view('client.accueil', ['utilisateur' => $request->user()]);
    }

    public function employeAccueil(Request $request): View
    {
        $utilisateur = $request->user();
        $pressingActif = $utilisateur->employe->pressingActif();

        return view('employe.accueil', compact('utilisateur', 'pressingActif'));
    }

    public function adminAccueil(Request $request): View
    {
        return view('admin.accueil', ['utilisateur' => $request->user()]);
    }
}

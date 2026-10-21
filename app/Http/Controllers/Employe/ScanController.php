<?php

namespace App\Http\Controllers\Employe;

use App\Http\Controllers\Controller;
use App\Services\VerifierQrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ScanController extends Controller
{
    private const MESSAGE_REFUS = 'QR invalide ou commande introuvable';

    public function create(): View
    {
        return view('employe.scan');
    }

    public function store(Request $request, VerifierQrCode $verifierQrCode): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        try {
            $commande = $verifierQrCode->executer(
                $validated['token'],
                $request->user()->employe->pressingActif(),
            );
        } catch (RuntimeException) {
            return back()->withErrors(['token' => self::MESSAGE_REFUS]);
        }

        return redirect()->route('employe.commandes.show', $commande->idCommande);
    }
}

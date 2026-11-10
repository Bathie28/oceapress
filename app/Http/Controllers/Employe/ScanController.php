<?php

namespace App\Http\Controllers\Employe;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Services\VerifierQrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ScanController extends Controller
{
    private const MESSAGE_REFUS = 'QR invalide ou commande introuvable';

    private const MESSAGE_REFERENCE_INTROUVABLE = 'Commande introuvable.';

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

    public function rechercherParReference(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:30', 'regex:/\ACMD-[A-Z0-9-]+\z/'],
        ]);

        $commande = Commande::query()
            ->where('referenceCommande', $validated['reference'])
            ->where('idPressing', $request->user()->employe->pressingActif())
            ->first();

        if ($commande === null) {
            return back()->withErrors(['reference' => self::MESSAGE_REFERENCE_INTROUVABLE]);
        }

        return redirect()->route('employe.commandes.show', $commande->idCommande);
    }
}

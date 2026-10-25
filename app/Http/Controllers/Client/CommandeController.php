<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Services\GenererImageQrCode;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommandeController extends Controller
{
    public function index(Request $request): View
    {
        $idClient = $request->user()->client->idClient;

        return view('client.commandes.index', [
            'commandes' => Commande::query()
                ->where('idClient', $idClient)
                ->orderByDesc('dateCreation')
                ->get(),
        ]);
    }

    public function show(Request $request, int $commande): View
    {
        $idClient = $request->user()->client->idClient;
        $commande = Commande::query()
            ->with(['articles', 'historiques', 'facture', 'qrCodeActif'])
            ->where('idClient', $idClient)
            ->whereKey($commande)
            ->firstOrFail();

        $qrCodeSvg = $commande->qrCodeActif === null
            ? null
            : app(GenererImageQrCode::class)->executer($commande->qrCodeActif->token);

        return view('client.commandes.show', compact('commande', 'qrCodeSvg'));
    }
}

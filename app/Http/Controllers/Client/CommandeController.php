<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Services\GenererCodeQr;
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
            ->with(['articles.photos', 'historiques', 'facture', 'qrCodeActif'])
            ->where('idClient', $idClient)
            ->whereKey($commande)
            ->firstOrFail();

        $qrCodeSvg = $commande->qrCodeActif === null
            ? null
            : app(GenererImageQrCode::class)->executer(
                app(GenererCodeQr::class)->executer($commande->idCommande),
            );

        return view('client.commandes.show', compact('commande', 'qrCodeSvg'));
    }
}

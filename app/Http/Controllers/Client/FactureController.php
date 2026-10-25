<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Facture;
use App\Services\EnregistrerPaiement;
use App\Services\CreerSessionWave;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class FactureController extends Controller
{
    public function storePaiement(Request $request, int $facture, EnregistrerPaiement $enregistrerPaiement, CreerSessionWave $creerSessionWave): RedirectResponse
    {
        $idClient = $request->user()->client->idClient;
        $facture = Facture::query()
            ->whereIn('idCommande', Commande::query()->select('idCommande')->where('idClient', $idClient))
            ->whereKey($facture)
            ->firstOrFail();

        if (in_array($facture->statut, ['PAYEE', 'ANNULEE'], true)) {
            return back()->withErrors(['paiement' => 'Cette facture ne peut plus être payée.']);
        }

        $validated = $request->validate([
            'methode' => ['required', 'string', 'in:WAVE,ORANGE_MONEY'],
        ]);

        try {
            if ($validated['methode'] === 'WAVE') {
                return redirect()->away($creerSessionWave->executer($facture));
            }

            $enregistrerPaiement->executer(
                $facture->idFacture,
                $validated['methode'],
                'EN_ATTENTE',
                (float) $facture->montantTotal,
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['paiement' => $exception->getMessage()]);
        }

        return redirect()->route('client.commandes.show', $facture->idCommande)
            ->with('paiement_resultat', 'Votre demande de paiement a été enregistrée.');
    }

    public function retourWave(Request $request, int $facture): RedirectResponse
    {
        $idClient = $request->user()->client->idClient;
        $facture = Facture::query()
            ->whereIn('idCommande', Commande::query()->select('idCommande')->where('idClient', $idClient))
            ->whereKey($facture)
            ->firstOrFail();

        return redirect()->route('client.commandes.show', $facture->idCommande)
            ->with('paiement_resultat', 'Le statut du paiement sera mis a jour apres confirmation de Wave.');
    }
}

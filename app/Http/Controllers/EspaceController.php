<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Commande;
use App\Services\RelancerCommandesNonRecuperees;

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

    public function tableauDeBordEmploye(Request $request, RelancerCommandesNonRecuperees $relancer): View
    {
        $idPressing = $request->user()->employe->pressingActif();
        $statuts = [
            Commande::STATUT_EN_ATTENTE,
            Commande::STATUT_EN_TRAITEMENT,
            Commande::STATUT_PRETE,
            Commande::STATUT_RECUPEREE,
            Commande::STATUT_ANNULEE,
        ];
        $compteurs = array_fill_keys($statuts, 0);
        $compteurs = array_replace($compteurs, Commande::query()
            ->where('idPressing', $idPressing)
            ->selectRaw('statut, COUNT(*) AS total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->all());

        $commandesNonRecuperees = Commande::query()
            ->where('idPressing', $idPressing)
            ->where('statut', Commande::STATUT_PRETE)
            ->with([
                'client.utilisateur',
                'historiques' => fn ($query) => $query
                    ->where('nouveauStatut', Commande::STATUT_PRETE)
                    ->orderByDesc('dateChangement')
                    ->orderByDesc('idHistorique'),
            ])
            ->orderByDesc('idCommande')
            ->get()
            ->map(function (Commande $commande) use ($relancer): Commande {
                $commande->niveauRelanceAtteint = $relancer->niveauAtteint(
                    $commande->historiques->first()?->dateChangement,
                );

                return $commande;
            });

        return view('employe.tableau-de-bord', compact('compteurs', 'commandesNonRecuperees'));
    }

    public function adminAccueil(Request $request): View
    {
        return view('admin.accueil', ['utilisateur' => $request->user()]);
    }
}

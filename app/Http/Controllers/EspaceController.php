<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Commande;
use App\Models\Notification;
use App\Services\RelancerCommandesNonRecuperees;
use Illuminate\Support\Carbon;

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

    public function tableauDeBordEmploye(Request $request): View
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
            ->get();

        $niveauxParCommande = Notification::query()
            ->whereIn('idCommande', $commandesNonRecuperees->pluck('idCommande'))
            ->whereNotNull('niveauRelance')
            ->get(['idCommande', 'niveauRelance'])
            ->groupBy('idCommande');

        $commandesNonRecuperees->each(function (Commande $commande) use ($niveauxParCommande): void {
            $ordreNiveaux = ['J2' => 2, 'J7' => 7, 'J15' => 15, 'J30' => 30];
            $commande->niveauRelanceAtteint = $niveauxParCommande
                ->get($commande->idCommande, collect())
                ->pluck('niveauRelance')
                ->sortBy(fn (string $niveau) => $ordreNiveaux[$niveau] ?? 0)
                ->last();
        });

        $debutSeptJours = Carbon::today()->subDays(6)->startOfDay();
        $datesEnregistrements = Commande::query()
            ->where('idPressing', $idPressing)
            ->whereBetween('dateCreation', [$debutSeptJours, Carbon::today()->endOfDay()])
            ->pluck('dateCreation')
            ->map(fn ($dateCreation) => Carbon::parse($dateCreation)->toDateString());

        $commandesParJour = collect(range(6, 0))->map(function (int $jours) use ($datesEnregistrements): array {
            $jour = Carbon::today()->subDays($jours);
            $date = $jour->toDateString();

            return [
                'date' => $date,
                'libelle' => $jour->locale('fr')->isoFormat('dd D'),
                'total' => $datesEnregistrements->filter(fn (string $dateEnregistree) => $dateEnregistree === $date)->count(),
            ];
        });

        $commandesRestitutionAujourdhui = Commande::query()
            ->where('idPressing', $idPressing)
            ->where('statut', Commande::STATUT_PRETE)
            ->whereDate('datePrevue', Carbon::today())
            ->with('client.utilisateur')
            ->orderBy('referenceCommande')
            ->get();

        return view('employe.tableau-de-bord', compact(
            'compteurs',
            'commandesNonRecuperees',
            'commandesParJour',
            'commandesRestitutionAujourdhui',
        ));
    }

    public function adminAccueil(Request $request): View
    {
        return view('admin.accueil', ['utilisateur' => $request->user()]);
    }
}

<?php

namespace App\Http\Controllers\Employe;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Pressing;
use App\Models\Tarif;
use App\Services\ChangerStatutCommande;
use App\Services\EnregistrerCommande;
use App\Services\EnregistrerRestitution;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use RuntimeException;
use Illuminate\View\View;

class CommandeController extends Controller
{
    private const STATUTS = [
        Commande::STATUT_EN_ATTENTE,
        Commande::STATUT_EN_TRAITEMENT,
        Commande::STATUT_PRETE,
        Commande::STATUT_RECUPEREE,
        Commande::STATUT_ANNULEE,
    ];

    public function index(Request $request): View
    {
        $query = Commande::query()
            ->with('client.utilisateur')
            ->where('idPressing', $this->pressingActif($request));
        $statut = $request->query('statut');

        if (is_string($statut) && in_array($statut, self::STATUTS, true)) {
            $query->where('statut', $statut);
        }

        return view('employe.commandes.index', [
            'commandes' => $query->orderByDesc('dateCreation')->get(),
            'statuts' => self::STATUTS,
            'statutSelectionne' => $statut,
        ]);
    }

    public function show(Request $request, int $commande): View
    {
        $commande = Commande::query()
            ->with(['client.utilisateur', 'articles', 'historiques', 'facture'])
            ->where('idPressing', $this->pressingActif($request))
            ->whereKey($commande)
            ->firstOrFail();

        return view('employe.commandes.show', compact('commande'));
    }

    public function create(Request $request): View
    {
        $pressing = Pressing::findOrFail($this->pressingActif($request));
        $mode = old('modeFacturation', $request->query('modeFacturation', Commande::MODE_FACTURATION_PIECE));
        if (! $pressing->pesee && $mode === Commande::MODE_FACTURATION_POIDS) {
            $mode = Commande::MODE_FACTURATION_PIECE;
        }
        $formules = Tarif::FORMULES_PAR_MODE[$mode] ?? Tarif::FORMULES_PAR_MODE[Commande::MODE_FACTURATION_PIECE];
        $formule = old('formule', $request->query('formule', $formules[0]));
        if (! in_array($formule, $formules, true)) {
            $formule = $formules[0];
        }
        $typesArticles = $mode === Commande::MODE_FACTURATION_PIECE
            ? $pressing->typesPourFormule($formule)
            : collect(Tarif::FORMULES_PAR_MODE[Commande::MODE_FACTURATION_PIECE])
                ->flatMap(fn (string $formulePiece) => $pressing->typesPourFormule($formulePiece))
                ->unique()
                ->sort()
                ->values()
                ->all();

        return view('employe.commandes.create', [
            'pesee' => $pressing->pesee,
            'typesArticles' => $typesArticles,
            'modeInitial' => $mode,
            'formuleInitiale' => $formule,
        ]);
    }

    public function types(Request $request): JsonResponse
    {
        $formulesConnues = collect(Tarif::FORMULES_PAR_MODE)->flatten()->unique()->values()->all();
        $validated = $request->validate([
            'formule' => ['required', 'string', 'in:'.implode(',', $formulesConnues)],
            'modeFacturation' => ['sometimes', 'string', 'in:PIECE,POIDS'],
        ]);
        $pressing = Pressing::findOrFail($this->pressingActif($request));
        $mode = $validated['modeFacturation'] ?? Commande::MODE_FACTURATION_PIECE;
        $types = $mode === Commande::MODE_FACTURATION_PIECE
            ? $pressing->typesPourFormule($validated['formule'])
            : collect(Tarif::FORMULES_PAR_MODE[Commande::MODE_FACTURATION_PIECE])
                ->flatMap(fn (string $formulePiece) => $pressing->typesPourFormule($formulePiece))
                ->unique()
                ->sort()
                ->values()
                ->all();

        return response()->json(['types' => $types]);
    }

    public function store(Request $request, EnregistrerCommande $enregistrerCommande): RedirectResponse
    {
        $validated = $request->validate([
            'client.email' => ['required', 'email', 'max:190'],
            'client.nom' => ['required', 'string', 'max:100'],
            'client.prenom' => ['required', 'string', 'max:100'],
            'client.telephone' => ['nullable', 'string', 'max:30'],
            'articles' => ['nullable', 'array'],
            'articles.*.type' => ['nullable', 'string', 'max:100'],
            'articles.*.couleur' => ['nullable', 'string', 'max:60'],
            'articles.*.description' => ['nullable', 'string'],
            'articles.*.quantite' => ['nullable', 'integer', 'min:0'],
            'modeFacturation' => ['required', 'string', 'in:PIECE,POIDS'],
            'formule' => ['required', 'string', 'in:'.implode(',', Tarif::FORMULES_PAR_MODE[$request->input('modeFacturation')] ?? [])],
            'poids' => ['required_if:modeFacturation,POIDS', 'nullable', 'numeric', 'gt:0', 'regex:/^\\d+(?:\\.\\d)?$/'],
        ]);

        $articles = collect($validated['articles'] ?? [])
            ->map(fn (array $article) => [...$article, 'type' => trim((string) ($article['type'] ?? ''))])
            ->filter(fn (array $article) => $article['type'] !== '' && (int) ($article['quantite'] ?? 0) > 0)
            ->values()
            ->all();
        if ($articles === []) {
            return back()->withErrors(['commande' => 'Ajoutez au moins un article avec une quantité supérieure ou égale à 1'])->withInput();
        }
        $client = [...$validated['client'], 'motDePasse' => Str::random(48)];

        try {
            $commande = $enregistrerCommande->executer(
                $client,
                $articles,
                $request->user()->employe->idEmploye,
                $this->pressingActif($request),
                $validated['modeFacturation'],
                $validated['formule'],
                isset($validated['poids']) ? (float) $validated['poids'] : null,
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['commande' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('employe.commandes.show', $commande->idCommande);
    }

    public function updateStatus(Request $request, int $commande, ChangerStatutCommande $changerStatutCommande): RedirectResponse
    {
        $commande = $this->commandeDuPressing($request, $commande);
        $validated = $request->validate([
            'nouveauStatut' => ['required', 'string', 'in:'.implode(',', self::STATUTS)],
            'commentaire' => ['nullable', 'string'],
        ]);

        try {
            $changerStatutCommande->executer(
                $commande->idCommande,
                $validated['nouveauStatut'],
                $request->user()->employe->idEmploye,
                $validated['commentaire'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return redirect()->route('employe.commandes.show', $commande->idCommande)
                ->withErrors(['statut' => $exception->getMessage()]);
        }

        return redirect()->route('employe.commandes.show', $commande->idCommande);
    }

    public function createRestitution(Request $request, int $commande): View
    {
        $commande = $this->commandeDuPressing($request, $commande);
        $commande->load('articles');

        return view('employe.commandes.restitution', compact('commande'));
    }

    public function storeRestitution(Request $request, int $commande, EnregistrerRestitution $enregistrerRestitution): RedirectResponse
    {
        $commande = $this->commandeDuPressing($request, $commande);
        $validated = $request->validate([
            'quantitesPresentees' => ['sometimes', 'array'],
            'quantitesPresentees.*' => ['integer', 'min:0'],
            'observation' => ['nullable', 'string'],
        ]);

        try {
            $resultat = $enregistrerRestitution->executer(
                $commande->idCommande,
                $request->user()->employe->idEmploye,
                $validated['quantitesPresentees'] ?? [],
                $validated['observation'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return redirect()->route('employe.commandes.show', $commande->idCommande)
                ->withErrors(['restitution' => $exception->getMessage()]);
        }

        $message = $resultat['restitution'] !== null
            ? 'Restitution conforme enregistrée.'
            : 'Restitution partielle : '.$resultat['anomalies'].' anomalie'.($resultat['anomalies'] === 1 ? '' : 's').' créée'.($resultat['anomalies'] === 1 ? '' : 's').'.';

        return redirect()->route('employe.commandes.show', $commande->idCommande)
            ->with('restitution_resultat', $message);
    }

    private function commandeDuPressing(Request $request, int $idCommande): Commande
    {
        return Commande::query()
            ->where('idPressing', $this->pressingActif($request))
            ->whereKey($idCommande)
            ->firstOrFail();
    }

    private function pressingActif(Request $request): int
    {
        return $request->user()->employe->pressingActif();
    }
}

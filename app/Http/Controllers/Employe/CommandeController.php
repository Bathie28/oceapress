<?php

namespace App\Http\Controllers\Employe;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Services\ChangerStatutCommande;
use App\Services\CreerFacture;
use App\Services\DefinirPrixArticle;
use App\Services\EnregistrerCommande;
use App\Services\EnregistrerRestitution;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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

    public function create(): View
    {
        return view('employe.commandes.create');
    }

    public function store(Request $request, EnregistrerCommande $enregistrerCommande, DefinirPrixArticle $definirPrixArticle): RedirectResponse
    {
        $validated = $request->validate([
            'client.email' => ['required', 'email', 'max:190'],
            'client.nom' => ['required', 'string', 'max:100'],
            'client.prenom' => ['required', 'string', 'max:100'],
            'client.telephone' => ['nullable', 'string', 'max:30'],
            'articles' => ['nullable', 'array'],
            'articles.*.typeArticle' => ['nullable', 'string', 'max:100'],
            'articles.*.couleur' => ['nullable', 'string', 'max:60'],
            'articles.*.description' => ['nullable', 'string'],
            'articles.*.prixUnitaire' => ['nullable', 'numeric', 'min:0'],
        ]);

        $articles = collect($validated['articles'] ?? [])
            ->map(fn (array $article) => [...$article, 'typeArticle' => trim((string) ($article['typeArticle'] ?? ''))])
            ->filter(fn (array $article) => $article['typeArticle'] !== '')
            ->values()
            ->all();
        if ($articles === []) {
            return back()->withErrors(['articles' => 'Ajoutez au moins un article'])->withInput();
        }
        $client = [...$validated['client'], 'motDePasse' => Str::random(48)];

        try {
            $commande = $enregistrerCommande->executer(
                $client,
                $articles,
                $request->user()->employe->idEmploye,
                $this->pressingActif($request),
            );

            $articlesCrees = $commande->articles()->orderBy('idArticle')->get();
            foreach ($articles as $index => $article) {
                if (isset($article['prixUnitaire']) && $article['prixUnitaire'] !== '') {
                    $definirPrixArticle->executer(
                        $articlesCrees[$index]->idArticle,
                        (float) $article['prixUnitaire'],
                    );
                }
            }
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

    public function storeFacture(Request $request, int $commande, CreerFacture $creerFacture): RedirectResponse
    {
        $commande = $this->commandeDuPressing($request, $commande);

        try {
            $creerFacture->executer($commande->idCommande);
        } catch (RuntimeException $exception) {
            return redirect()->route('employe.commandes.show', $commande->idCommande)
                ->withErrors(['facturation' => $exception->getMessage()]);
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
            'articles' => ['sometimes', 'array'],
            'articles.*' => ['integer'],
            'observation' => ['nullable', 'string'],
        ]);

        try {
            $resultat = $enregistrerRestitution->executer(
                $commande->idCommande,
                $request->user()->employe->idEmploye,
                $validated['articles'] ?? [],
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

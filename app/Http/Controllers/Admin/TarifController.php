<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pressing;
use App\Models\Tarif;
use App\Services\CreerPressing;
use App\Services\EnregistrerTarif;
use App\Services\ModifierTarif;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class TarifController extends Controller
{
    public function index(): View
    {
        return view('admin.pressings.index', [
            'pressings' => Pressing::with('tarifs')->orderBy('nom')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pressings.formulaire', ['pressing' => null]);
    }

    public function storePressing(Request $request, CreerPressing $creerPressing): RedirectResponse
    {
        $validated = $this->validerDonneesPressing($request);

        try {
            $creerPressing->executer($validated);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['nom' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.pressings.index');
    }

    public function edit(Pressing $pressing): View
    {
        return view('admin.pressings.formulaire', ['pressing' => $pressing]);
    }

    public function updatePressing(Request $request, Pressing $pressing): RedirectResponse
    {
        $validated = $this->validerDonneesPressing($request);
        $validated['pesee'] = (bool) ($validated['pesee'] ?? false);
        $pressing->update($validated);

        return redirect()->route('admin.pressings.index');
    }

    public function show(Pressing $pressing): View
    {
        return view('admin.pressings.tarifs', [
            'pressing' => $pressing->load(['tarifs' => fn ($query) => $query->orderBy('formule')->orderBy('unite')->orderBy('typeArticle')]),
        ]);
    }

    public function store(Request $request, Pressing $pressing, EnregistrerTarif $enregistrerTarif): RedirectResponse
    {
        $validated = $request->validate([
            'typeArticle' => ['nullable', 'required_if:unite,PIECE', 'string', 'max:100'],
            'formule' => ['required', 'string', 'in:'.implode(',', Tarif::FORMULES_PAR_MODE[$request->input('unite') === 'KG' ? 'POIDS' : 'PIECE'])],
            'unite' => ['required', 'string', 'in:PIECE,KG'],
            'prix' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $enregistrerTarif->executer(
                $pressing,
                $validated['unite'] === 'KG' ? null : $validated['typeArticle'],
                $validated['formule'],
                $validated['unite'],
                (float) $validated['prix'],
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['tarif' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.pressings.tarifs', $pressing->idPressing);
    }

    public function update(Request $request, int $tarif, ModifierTarif $modifierTarif): RedirectResponse
    {
        $enregistre = Tarif::findOrFail($tarif);
        $validated = $request->validate([
            'idPressing' => ['required', 'integer', 'exists:pressing,idPressing'],
            'prix' => ['required', 'integer', 'min:0'],
        ]);
        abort_unless((int) $validated['idPressing'] === (int) $enregistre->idPressing, 404);

        try {
            $modifierTarif->executer($enregistre->idTarif, (float) $validated['prix']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['tarif' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.pressings.tarifs', $enregistre->idPressing);
    }

    public function updateMode(Request $request, Pressing $pressing): RedirectResponse
    {
        $validated = $request->validate(['pesee' => ['nullable', 'boolean']]);
        $pressing->pesee = (bool) ($validated['pesee'] ?? false);
        $pressing->save();

        return redirect()->route('admin.pressings.tarifs', $pressing->idPressing);
    }

    private function validerDonneesPressing(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'adresse' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],
            'pesee' => ['nullable', 'boolean'],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pressing;
use App\Models\Tarif;
use App\Services\EnregistrerTarif;
use App\Services\ModifierTarif;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class TarifController extends Controller
{
    public function index(): View
    {
        return view('admin.pressings.index', [
            'pressings' => Pressing::withCount('tarifs')->orderBy('nom')->get(),
        ]);
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
            'formule' => ['required', 'string', 'in:'.implode(',', Tarif::FORMULES)],
            'unite' => ['required', 'string', 'in:PIECE,KG'],
            'prix' => ['required', 'numeric', 'min:0'],
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
            'prix' => ['required', 'numeric'],
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
        $validated = $request->validate(['modeTarification' => ['required', 'string', 'in:PIECE,POIDS']]);
        $pressing->modeTarification = $validated['modeTarification'];
        $pressing->save();

        return redirect()->route('admin.pressings.tarifs', $pressing->idPressing);
    }
}

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EspaceController;
use App\Http\Controllers\Employe\CommandeController;
use App\Http\Controllers\Client\CommandeController as ClientCommandeController;
use App\Http\Controllers\Client\FactureController as ClientFactureController;
use App\Http\Controllers\Admin\TarifController as AdminTarifController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'afficherConnexion'])->name('login');
Route::post('/login', [AuthController::class, 'connecter'])->name('login.store');
Route::post('/logout', [AuthController::class, 'deconnecter'])->name('logout');

Route::prefix('client')->name('client.')->middleware('role:client')->group(function () {
    Route::get('/', [EspaceController::class, 'clientAccueil'])->name('accueil');
    Route::get('/commandes', [ClientCommandeController::class, 'index'])->name('commandes.index');
    Route::get('/commandes/{commande}', [ClientCommandeController::class, 'show'])->name('commandes.show');
    Route::post('/factures/{facture}/paiement', [ClientFactureController::class, 'storePaiement'])->name('factures.paiement');
});

Route::prefix('employe')->name('employe.')->middleware('role:employe')->group(function () {
    Route::get('/', [EspaceController::class, 'employeAccueil'])->name('accueil');
    Route::get('/commandes', [CommandeController::class, 'index'])->name('commandes.index');
    Route::get('/commandes/nouvelle', [CommandeController::class, 'create'])->name('commandes.create');
    Route::get('/commandes/types', [CommandeController::class, 'types'])->name('commandes.types');
    Route::post('/commandes/nouvelle', [CommandeController::class, 'store'])->name('commandes.store');
    Route::post('/commandes/{commande}/statut', [CommandeController::class, 'updateStatus'])->name('commandes.statut');
    Route::get('/commandes/{commande}/restitution', [CommandeController::class, 'createRestitution'])->name('commandes.restitution.create');
    Route::post('/commandes/{commande}/restitution', [CommandeController::class, 'storeRestitution'])->name('commandes.restitution.store');
    Route::get('/commandes/{commande}', [CommandeController::class, 'show'])->name('commandes.show');
});

Route::prefix('admin')->name('admin.')->middleware('role:administrateur')->group(function () {
    Route::get('/', [EspaceController::class, 'adminAccueil'])->name('accueil');
    Route::get('/pressings', [AdminTarifController::class, 'index'])->name('pressings.index');
    Route::get('/pressings/{pressing}/tarifs', [AdminTarifController::class, 'show'])->name('pressings.tarifs');
    Route::post('/pressings/{pressing}/tarifs', [AdminTarifController::class, 'store'])->name('pressings.tarifs.store');
    Route::post('/pressings/{pressing}/mode', [AdminTarifController::class, 'updateMode'])->name('pressings.mode');
    Route::post('/tarifs/{tarif}', [AdminTarifController::class, 'update'])->name('tarifs.update');
});

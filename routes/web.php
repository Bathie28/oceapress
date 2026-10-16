<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EspaceController;
use App\Http\Controllers\Employe\CommandeController;
use App\Http\Controllers\Client\CommandeController as ClientCommandeController;
use App\Http\Controllers\Client\FactureController as ClientFactureController;

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
    Route::post('/commandes/nouvelle', [CommandeController::class, 'store'])->name('commandes.store');
    Route::post('/commandes/{commande}/statut', [CommandeController::class, 'updateStatus'])->name('commandes.statut');
    Route::get('/commandes/{commande}/restitution', [CommandeController::class, 'createRestitution'])->name('commandes.restitution.create');
    Route::post('/commandes/{commande}/restitution', [CommandeController::class, 'storeRestitution'])->name('commandes.restitution.store');
    Route::get('/commandes/{commande}', [CommandeController::class, 'show'])->name('commandes.show');
});

Route::prefix('admin')->name('admin.')->middleware('role:administrateur')->group(function () {
    Route::get('/', [EspaceController::class, 'adminAccueil'])->name('accueil');
});

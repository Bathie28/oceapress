<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EspaceController;
use App\Http\Controllers\Employe\CommandeController;
use App\Http\Controllers\Employe\ScanController;
use App\Http\Controllers\Employe\MotDePasseController as EmployeMotDePasseController;
use App\Http\Controllers\Client\CommandeController as ClientCommandeController;
use App\Http\Controllers\Client\FactureController as ClientFactureController;
use App\Http\Controllers\Client\MotDePasseController;
use App\Http\Controllers\Client\NotificationController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\WaveWebhookController;
use App\Http\Controllers\Admin\TarifController as AdminTarifController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'afficherConnexion'])->name('login');
Route::post('/login', [AuthController::class, 'connecter'])->name('login.store');
Route::post('/logout', [AuthController::class, 'deconnecter'])->name('logout');
Route::post('/webhooks/wave', WaveWebhookController::class)->name('webhooks.wave');
Route::get('/photos/{photo}', [PhotoController::class, 'show'])->middleware('auth')->whereNumber('photo')->name('photos.show');

Route::middleware('role:client')->prefix('client')->name('client.')->group(function () {
    Route::get('/mot-de-passe', [MotDePasseController::class, 'edit'])->name('mot-de-passe.edit');
    Route::post('/mot-de-passe', [MotDePasseController::class, 'update'])->name('mot-de-passe.update');
});

Route::prefix('client')->name('client.')->middleware(['role:client', 'mot-de-passe'])->group(function () {
    Route::get('/', [EspaceController::class, 'clientAccueil'])->name('accueil');
    Route::get('/commandes', [ClientCommandeController::class, 'index'])->name('commandes.index');
    Route::get('/commandes/{commande}', [ClientCommandeController::class, 'show'])->name('commandes.show');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/factures/{facture}/paiement', [ClientFactureController::class, 'storePaiement'])->name('factures.paiement');
    Route::get('/factures/{facture}/wave/succes', [ClientFactureController::class, 'retourWave'])->name('factures.wave.succes');
    Route::get('/factures/{facture}/wave/echec', [ClientFactureController::class, 'retourWave'])->name('factures.wave.echec');
});

Route::prefix('employe')->name('employe.')->middleware(['role:employe', 'mot-de-passe'])->group(function () {
    Route::get('/mot-de-passe', [EmployeMotDePasseController::class, 'edit'])->name('mot-de-passe.edit');
    Route::post('/mot-de-passe', [EmployeMotDePasseController::class, 'update'])->name('mot-de-passe.update');
});

Route::prefix('employe')->name('employe.')->middleware(['role:employe', 'mot-de-passe'])->group(function () {
    Route::get('/', [EspaceController::class, 'employeAccueil'])->name('accueil');
    Route::get('/tableau-de-bord', [EspaceController::class, 'tableauDeBordEmploye'])->name('tableau-de-bord');
    Route::get('/scan', [ScanController::class, 'create'])->name('scan');
    Route::post('/scan', [ScanController::class, 'store'])->middleware('throttle:30,1')->name('scan.store');
    Route::get('/commandes', [CommandeController::class, 'index'])->name('commandes.index');
    Route::get('/commandes/nouvelle', [CommandeController::class, 'create'])->name('commandes.create');
    Route::get('/commandes/types', [CommandeController::class, 'types'])->name('commandes.types');
    Route::get('/clients/rechercher', [CommandeController::class, 'rechercherClient'])->name('clients.rechercher');
    Route::post('/commandes/nouvelle', [CommandeController::class, 'store'])->name('commandes.store');
    Route::post('/commandes/{commande}/statut', [CommandeController::class, 'updateStatus'])->name('commandes.statut');
    Route::get('/commandes/{commande}/restitution', [CommandeController::class, 'createRestitution'])->name('commandes.restitution.create');
    Route::post('/commandes/{commande}/restitution', [CommandeController::class, 'storeRestitution'])->name('commandes.restitution.store');
    Route::get('/commandes/{commande}', [CommandeController::class, 'show'])->name('commandes.show');
});

Route::prefix('admin')->name('admin.')->middleware('role:administrateur')->group(function () {
    Route::get('/', [EspaceController::class, 'adminAccueil'])->name('accueil');
    Route::get('/pressings', [AdminTarifController::class, 'index'])->name('pressings.index');
    Route::get('/pressings/create', [AdminTarifController::class, 'create'])->name('pressings.create');
    Route::post('/pressings', [AdminTarifController::class, 'storePressing'])->name('pressings.store');
    Route::get('/pressings/{pressing}/edit', [AdminTarifController::class, 'edit'])->name('pressings.edit');
    Route::post('/pressings/{pressing}/employes', [AdminTarifController::class, 'storeEmploye'])->name('pressings.employes.store');
    Route::put('/pressings/{pressing}', [AdminTarifController::class, 'updatePressing'])->name('pressings.update');
    Route::get('/pressings/{pressing}/tarifs', [AdminTarifController::class, 'show'])->name('pressings.tarifs');
    Route::post('/pressings/{pressing}/tarifs', [AdminTarifController::class, 'store'])->name('pressings.tarifs.store');
    Route::post('/pressings/{pressing}/mode', [AdminTarifController::class, 'updateMode'])->name('pressings.mode');
    Route::post('/tarifs/{tarif}', [AdminTarifController::class, 'update'])->name('tarifs.update');
});

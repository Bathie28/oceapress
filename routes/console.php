<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\RelancerCommandesNonRecuperees;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('oceapress:relancer', function (RelancerCommandesNonRecuperees $relancer): void {
    $nombreRelances = $relancer->executer();
    $this->info("{$nombreRelances} relance(s) envoyée(s).");
})->purpose('Relancer les commandes prêtes non récupérées');

Schedule::command('oceapress:relancer')->dailyAt('09:00');

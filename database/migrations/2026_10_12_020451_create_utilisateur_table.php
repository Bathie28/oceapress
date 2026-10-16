<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateur', function (Blueprint $table) {
            $table->integer('idUtilisateur', true);
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('email', 190)->unique('uq_utilisateur_email');
            $table->string('telephone', 30)->nullable();
            $table->string('motDePasse', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateur');
    }
};
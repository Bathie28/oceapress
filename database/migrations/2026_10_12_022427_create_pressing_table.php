<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pressing', function (Blueprint $table) {
            $table->integer('idPressing', true);
            $table->string('nom', 150);
            $table->string('adresse', 255);
            $table->string('telephone', 30)->nullable();
            $table->string('email', 190)->nullable();
            $table->date('dateCreation');
            $table->string('statut', 30)->default('ACTIF');
        });

        DB::statement("ALTER TABLE pressing ADD CONSTRAINT chk_pressing_statut CHECK (statut IN ('ACTIF', 'INACTIF', 'FERME'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pressing');
    }
};

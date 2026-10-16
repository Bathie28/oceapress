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
        Schema::create('restitution', function (Blueprint $table) {
            $table->integer('idRestitution', true);
            $table->dateTime('dateRestitution')->nullable();
            $table->string('statut', 40)->default('EN_ATTENTE');
            $table->text('observation')->nullable();
            $table->integer('idCommande')->unique('uq_restitution_commande');
            $table->integer('idEmploye');
            $table->primary('idRestitution');
            $table->foreign('idCommande', 'fk_restitution_commande')
                ->references('idCommande')
                ->on('commande')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->foreign('idEmploye', 'fk_restitution_employe')
                ->references('idEmploye')
                ->on('employe')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        DB::statement("ALTER TABLE restitution ADD CONSTRAINT chk_restitution_statut CHECK (statut IN ('CONFORME','NON_CONFORME','EN_ATTENTE'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restitution');
    }
};

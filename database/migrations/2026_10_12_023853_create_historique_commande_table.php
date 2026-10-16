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
        Schema::create('historique_commande', function (Blueprint $table) {
            $table->integer('idHistorique', true);
            $table->string('ancienStatut', 40)->nullable();
            $table->string('nouveauStatut', 40);
            $table->dateTime('dateChangement')->useCurrent();
            $table->text('commentaire')->nullable();
            $table->integer('idCommande');
            $table->integer('idEmploye');
            $table->primary('idHistorique');
            $table->foreign('idCommande', 'fk_historique_commande')
                ->references('idCommande')
                ->on('commande')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->foreign('idEmploye', 'fk_historique_employe')
                ->references('idEmploye')
                ->on('employe')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->index(['idCommande', 'dateChangement'], 'idx_historique_commande_date');
        });

        DB::statement("ALTER TABLE historique_commande ADD CONSTRAINT chk_historique_ancien_statut CHECK (ancienStatut IS NULL OR ancienStatut IN ('EN_ATTENTE','EN_TRAITEMENT','PRETE','RECUPEREE','ANNULEE'))");
        DB::statement("ALTER TABLE historique_commande ADD CONSTRAINT chk_historique_nouveau_statut CHECK (nouveauStatut IN ('EN_ATTENTE','EN_TRAITEMENT','PRETE','RECUPEREE','ANNULEE'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historique_commande');
    }
};

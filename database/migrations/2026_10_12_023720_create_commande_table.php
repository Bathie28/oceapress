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
        Schema::create('commande', function (Blueprint $table) {
            $table->integer('idCommande', true);
            $table->string('referenceCommande', 100)->unique('uq_commande_reference');
            $table->dateTime('dateCreation')->useCurrent();
            $table->date('datePrevue')->nullable();
            $table->dateTime('dateRecuperation')->nullable();
            $table->string('statut', 40)->default('EN_ATTENTE');
            $table->decimal('montantTotal', 10, 2)->default(0);
            $table->integer('idPressing');
            $table->integer('idClient');
            $table->primary('idCommande');
            $table->foreign('idPressing', 'fk_commande_pressing')
                ->references('idPressing')
                ->on('pressing')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->foreign('idClient', 'fk_commande_client')
                ->references('idClient')
                ->on('client')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->index(['idPressing', 'statut'], 'idx_commande_pressing_statut');
        });

        DB::statement("ALTER TABLE commande ADD CONSTRAINT chk_commande_statut CHECK (statut IN ('EN_ATTENTE', 'EN_TRAITEMENT', 'PRETE', 'RECUPEREE', 'ANNULEE'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commande');
    }
};

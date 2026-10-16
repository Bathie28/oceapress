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
        Schema::create('facture', function (Blueprint $table) {
            $table->integer('idFacture', true);
            $table->string('numeroFacture', 100)->unique('uq_facture_numero');
            $table->dateTime('dateEmission')->useCurrent();
            $table->decimal('montantTotal', 10, 2);
            $table->string('statut', 40)->default('EN_ATTENTE');
            $table->integer('idCommande')->unique('uq_facture_commande');
            $table->primary('idFacture');
            $table->foreign('idCommande', 'fk_facture_commande')
                ->references('idCommande')
                ->on('commande')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        DB::statement("ALTER TABLE facture ADD CONSTRAINT chk_facture_statut CHECK (statut IN ('EN_ATTENTE','PAYEE','ANNULEE'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facture');
    }
};

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
        Schema::create('paiement', function (Blueprint $table) {
            $table->integer('idPaiement', true);
            $table->string('referencePaiement', 190)->unique('uq_paiement_reference');
            $table->decimal('montant', 10, 2);
            $table->dateTime('datePaiement')->nullable();
            $table->string('methode', 40);
            $table->string('statut', 40)->default('EN_ATTENTE');
            $table->integer('idFacture');
            $table->primary('idPaiement');
            $table->foreign('idFacture', 'fk_paiement_facture')
                ->references('idFacture')
                ->on('facture')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        DB::statement("ALTER TABLE paiement ADD CONSTRAINT chk_paiement_methode CHECK (methode IN ('WAVE','ORANGE_MONEY'))");
        DB::statement("ALTER TABLE paiement ADD CONSTRAINT chk_paiement_statut CHECK (statut IN ('EN_ATTENTE','CONFIRME','ECHOUE'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiement');
    }
};

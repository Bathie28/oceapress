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
        Schema::create('qr_code', function (Blueprint $table) {
            $table->integer('idQRCode', true);
            $table->string('token', 255)->unique('uq_qr_code_token');
            $table->dateTime('dateCreation')->useCurrent();
            $table->boolean('actif')->default(true);
            $table->integer('idCommande');
            $table->primary('idQRCode');
            $table->foreign('idCommande', 'fk_qr_code_commande')
                ->references('idCommande')
                ->on('commande')
                ->onDelete('restrict');
        });

        DB::statement('ALTER TABLE qr_code ADD COLUMN activeCommandeId INT GENERATED ALWAYS AS (CASE WHEN actif = TRUE THEN idCommande ELSE NULL END) STORED, ADD CONSTRAINT uq_qr_code_commande_active UNIQUE (activeCommandeId)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_code');
    }
};

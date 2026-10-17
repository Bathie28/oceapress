<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarif', function (Blueprint $table) {
            $table->integer('idTarif', true);
            $table->integer('idPressing');
            $table->string('typeArticle', 100)->nullable();
            $table->string('formule', 30);
            $table->string('unite', 10);
            $table->decimal('prix', 10, 2);
            $table->primary('idTarif');
            $table->foreign('idPressing', 'fk_tarif_pressing')
                ->references('idPressing')
                ->on('pressing')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->index(['idPressing', 'formule', 'unite'], 'idx_tarif_pressing_formule_unite');
        });

        DB::statement("ALTER TABLE tarif ADD CONSTRAINT chk_tarif_formule CHECK (formule IN ('LAVAGE', 'LAVAGE_REPASSAGE'))");
        DB::statement("ALTER TABLE tarif ADD CONSTRAINT chk_tarif_unite CHECK (unite IN ('PIECE', 'KG'))");
        DB::statement('ALTER TABLE tarif ADD CONSTRAINT chk_tarif_prix CHECK (prix >= 0)');
        DB::statement("ALTER TABLE tarif ADD CONSTRAINT chk_tarif_coherence CHECK ((unite = 'PIECE' AND typeArticle IS NOT NULL) OR (unite = 'KG' AND typeArticle IS NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif');
    }
};

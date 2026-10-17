<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commande', function (Blueprint $table) {
            $table->string('formule', 30)->default('LAVAGE');
            $table->decimal('poids', 8, 3)->nullable();
        });

        DB::statement("ALTER TABLE commande ADD CONSTRAINT chk_commande_formule CHECK (formule IN ('LAVAGE', 'LAVAGE_REPASSAGE'))");
        DB::statement('ALTER TABLE commande ADD CONSTRAINT chk_commande_poids CHECK (poids IS NULL OR poids > 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE commande DROP CHECK chk_commande_poids');
        DB::statement('ALTER TABLE commande DROP CHECK chk_commande_formule');

        Schema::table('commande', function (Blueprint $table) {
            $table->dropColumn(['formule', 'poids']);
        });
    }
};

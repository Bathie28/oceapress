<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tarif DROP CONSTRAINT chk_tarif_formule');
        DB::statement("ALTER TABLE tarif ADD CONSTRAINT chk_tarif_formule CHECK (formule IN ('LAVAGE', 'REPASSAGE', 'LAVAGE_REPASSAGE'))");

        DB::statement('ALTER TABLE commande DROP CONSTRAINT chk_commande_formule');
        DB::statement("ALTER TABLE commande ADD CONSTRAINT chk_commande_formule CHECK (formule IN ('LAVAGE', 'REPASSAGE', 'LAVAGE_REPASSAGE'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tarif DROP CONSTRAINT chk_tarif_formule');
        DB::statement("ALTER TABLE tarif ADD CONSTRAINT chk_tarif_formule CHECK (formule IN ('LAVAGE', 'LAVAGE_REPASSAGE'))");

        DB::statement('ALTER TABLE commande DROP CONSTRAINT chk_commande_formule');
        DB::statement("ALTER TABLE commande ADD CONSTRAINT chk_commande_formule CHECK (formule IN ('LAVAGE', 'LAVAGE_REPASSAGE'))");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE pressing DROP CONSTRAINT chk_pressing_mode');
        Schema::table('pressing', function (Blueprint $table) {
            $table->boolean('pesee')->default(false)->after('statut');
        });
        DB::statement("UPDATE pressing SET pesee = (modeTarification = 'POIDS')");
        Schema::table('commande', function (Blueprint $table) {
            $table->string('modeFacturation', 10)->default('PIECE')->after('formule');
        });
        DB::statement("UPDATE commande c JOIN pressing p ON p.idPressing = c.idPressing SET c.modeFacturation = IF(p.modeTarification = 'POIDS', 'POIDS', 'PIECE')");
        Schema::table('pressing', fn (Blueprint $table) => $table->dropColumn('modeTarification'));

        Schema::table('commande', function (Blueprint $table) {
            $table->decimal('montantTotal', 10, 0)->default(0)->change();
            $table->decimal('poids', 6, 2)->nullable()->change();
        });
        DB::statement("ALTER TABLE commande ALTER COLUMN formule SET DEFAULT 'REPASSAGE'");
        DB::statement("ALTER TABLE commande ADD CONSTRAINT chk_commande_mode_facturation CHECK (modeFacturation IN ('PIECE', 'POIDS'))");
        DB::statement('ALTER TABLE commande DROP CONSTRAINT chk_commande_poids');
        DB::statement('ALTER TABLE commande ADD CONSTRAINT chk_commande_poids CHECK (poids IS NULL OR (poids > 0 AND poids * 10 = ROUND(poids * 10)))');

        Schema::table('tarif', fn (Blueprint $table) => $table->decimal('prix', 10, 0)->change());
        Schema::table('article', fn (Blueprint $table) => $table->decimal('prixUnitaire', 10, 0)->nullable()->change());
        Schema::table('facture', fn (Blueprint $table) => $table->decimal('montantTotal', 10, 0)->change());
        Schema::table('paiement', fn (Blueprint $table) => $table->decimal('montant', 10, 0)->change());

    }

    public function down(): void
    {
        DB::statement('ALTER TABLE commande DROP CONSTRAINT chk_commande_mode_facturation');
        DB::statement('ALTER TABLE commande DROP CONSTRAINT chk_commande_poids');
        DB::statement('ALTER TABLE commande ADD CONSTRAINT chk_commande_poids CHECK (poids IS NULL OR poids > 0)');

        Schema::table('tarif', fn (Blueprint $table) => $table->decimal('prix', 10, 2)->change());
        Schema::table('article', fn (Blueprint $table) => $table->decimal('prixUnitaire', 10, 2)->nullable()->change());
        Schema::table('facture', fn (Blueprint $table) => $table->decimal('montantTotal', 10, 2)->change());
        Schema::table('paiement', fn (Blueprint $table) => $table->decimal('montant', 10, 2)->change());

        Schema::table('commande', function (Blueprint $table) {
            $table->decimal('montantTotal', 10, 2)->default(0)->change();
            $table->decimal('poids', 8, 3)->nullable()->change();
            $table->dropColumn('modeFacturation');
        });
        DB::statement("ALTER TABLE commande ALTER COLUMN formule SET DEFAULT 'LAVAGE'");

        Schema::table('pressing', function (Blueprint $table) {
            $table->string('modeTarification', 10)->default('PIECE')->after('statut');
        });
        DB::statement("UPDATE pressing SET modeTarification = IF(pesee, 'POIDS', 'PIECE')");
        Schema::table('pressing', fn (Blueprint $table) => $table->dropColumn('pesee'));
        DB::statement("ALTER TABLE pressing ADD CONSTRAINT chk_pressing_mode CHECK (modeTarification IN ('PIECE', 'POIDS'))");
    }
};

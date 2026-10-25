<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification', function (Blueprint $table): void {
            $table->integer('idCommande')->nullable()->after('idClient');
            $table->string('niveauRelance', 3)->nullable()->after('idCommande');
            $table->foreign('idCommande', 'fk_notification_commande')
                ->references('idCommande')
                ->on('commande')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->unique(['idCommande', 'niveauRelance'], 'uq_notification_commande_niveau_relance');
        });

        DB::statement("ALTER TABLE notification ADD CONSTRAINT chk_notification_niveau_relance CHECK (niveauRelance IS NULL OR niveauRelance IN ('J2','J7','J15','J30'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE notification DROP CHECK chk_notification_niveau_relance');

        Schema::table('notification', function (Blueprint $table): void {
            $table->dropForeign('fk_notification_commande');
            $table->dropUnique('uq_notification_commande_niveau_relance');
            $table->dropColumn(['idCommande', 'niveauRelance']);
        });
    }
};

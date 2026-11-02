<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestion_pressing', function (Blueprint $table) {
            $table->integer('idAdministrateur');
            $table->integer('idPressing');

            $table->primary(['idAdministrateur', 'idPressing']);
            $table->foreign('idAdministrateur', 'fk_gestion_pressing_administrateur')
                ->references('idAdministrateur')
                ->on('administrateur')
                ->onDelete('cascade');
            $table->foreign('idPressing', 'fk_gestion_pressing_pressing')
                ->references('idPressing')
                ->on('pressing')
                ->onDelete('cascade');
        });

        $this->rattacherPressingsExistants();
    }

    public function rattacherPressingsExistants(): void
    {
        $premierAdministrateur = DB::table('administrateur')
            ->orderBy('idAdministrateur')
            ->value('idAdministrateur');

        if ($premierAdministrateur === null) {
            return;
        }

        foreach (DB::table('pressing')->pluck('idPressing') as $idPressing) {
            DB::table('gestion_pressing')->insertOrIgnore([
                'idAdministrateur' => $premierAdministrateur,
                'idPressing' => $idPressing,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gestion_pressing');
    }
};

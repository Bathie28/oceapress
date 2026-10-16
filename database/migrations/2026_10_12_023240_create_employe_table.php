<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employe', function (Blueprint $table) {
            $table->integer('idEmploye');
            $table->string('fonction', 100);
            $table->date('dateEmbauche')->nullable();
            $table->integer('idPressing');
            $table->primary('idEmploye');
            $table->foreign('idEmploye', 'fk_employe_utilisateur')
                ->references('idUtilisateur')
                ->on('utilisateur')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreign('idPressing', 'fk_employe_pressing')
                ->references('idPressing')
                ->on('pressing')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employe');
    }
};

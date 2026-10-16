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
        Schema::create('article', function (Blueprint $table) {
            $table->integer('idArticle', true);
            $table->string('typeArticle', 100);
            $table->text('description')->nullable();
            $table->string('couleur', 60)->nullable();
            $table->string('etatInitial', 100)->nullable();
            $table->string('etatFinal', 100)->nullable();
            $table->integer('idCommande');
            $table->primary('idArticle');
            $table->foreign('idCommande', 'fk_article_commande')
                ->references('idCommande')
                ->on('commande')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article');
    }
};

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
        Schema::create('anomalie', function (Blueprint $table) {
            $table->integer('idAnomalie', true);
            $table->string('type', 100);
            $table->text('description');
            $table->dateTime('dateSignalement')->useCurrent();
            $table->integer('idArticle');
            $table->primary('idAnomalie');
            $table->foreign('idArticle', 'fk_anomalie_article')
                ->references('idArticle')
                ->on('article')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anomalie');
    }
};

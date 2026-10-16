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
        Schema::create('photo', function (Blueprint $table) {
            $table->integer('idPhoto', true);
            $table->string('urlPhoto', 500);
            $table->dateTime('datePrise')->useCurrent();
            $table->string('type', 60);
            $table->integer('idArticle');
            $table->primary('idPhoto');
            $table->foreign('idArticle', 'fk_photo_article')
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
        Schema::dropIfExists('photo');
    }
};

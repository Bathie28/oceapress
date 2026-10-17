<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article', function (Blueprint $table) {
            $table->integer('quantite')->default(1);
        });

        DB::statement('ALTER TABLE article ADD CONSTRAINT chk_article_quantite CHECK (quantite >= 1)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE article DROP CHECK chk_article_quantite');

        Schema::table('article', function (Blueprint $table) {
            $table->dropColumn('quantite');
        });
    }
};

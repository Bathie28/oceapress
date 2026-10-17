<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pressing', function (Blueprint $table) {
            $table->string('modeTarification', 10)->default('PIECE');
        });

        DB::statement("ALTER TABLE pressing ADD CONSTRAINT chk_pressing_mode CHECK (modeTarification IN ('PIECE', 'POIDS'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pressing DROP CHECK chk_pressing_mode');

        Schema::table('pressing', function (Blueprint $table) {
            $table->dropColumn('modeTarification');
        });
    }
};

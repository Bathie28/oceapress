<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wave_webhook_events', function (Blueprint $table) {
            $table->string('eventId', 100)->primary();
            $table->integer('idPaiement');
            $table->dateTime('dateTraitement')->useCurrent();
            $table->foreign('idPaiement', 'fk_wave_event_paiement')
                ->references('idPaiement')->on('paiement')
                ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wave_webhook_events');
    }
};

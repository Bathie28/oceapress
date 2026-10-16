<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification', function (Blueprint $table) {
            $table->integer('idNotification', true);
            $table->string('titre', 150);
            $table->text('message');
            $table->string('type', 50);
            $table->string('canal', 30)->default('IN_APP');
            $table->dateTime('dateEnvoi')->nullable();
            $table->boolean('lu')->default(false);
            $table->integer('idClient');
            $table->primary('idNotification');
            $table->foreign('idClient', 'fk_notification_client')
                ->references('idClient')
                ->on('client')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        DB::statement("ALTER TABLE notification ADD CONSTRAINT chk_notification_canal CHECK (canal IN ('EMAIL','IN_APP'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification');
    }
};

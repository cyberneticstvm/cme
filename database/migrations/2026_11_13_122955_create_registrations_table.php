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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->string('full_name', 120);
            $table->string('profession', 24);
            $table->string('email', 254)->unique();
            $table->string('phone', 16);
            $table->string('organisation', 180)->nullable();
            $table->string('city', 100);
            $table->string('state', 100);
            $table->text('message')->nullable();
            $table->timestamp('consented_at');
            $table->timestamp('acknowledgement_sent_at')->nullable();
            $table->timestamp('notification_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};

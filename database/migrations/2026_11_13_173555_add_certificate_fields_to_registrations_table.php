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
        Schema::table('registrations', function (Blueprint $table) {
            $table->uuid('certificate_id')->nullable()->unique();
            $table->string('certificate_number', 64)->nullable()->unique();
            $table->timestamp('certificate_issued_at')->nullable();
            $table->string('certificate_issued_by', 120)->nullable();
            $table->timestamp('certificate_sent_at')->nullable();
            $table->string('certificate_sent_to', 254)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['certificate_id']);
            $table->dropUnique(['certificate_number']);
            $table->dropColumn(['certificate_id', 'certificate_number', 'certificate_issued_at', 'certificate_issued_by', 'certificate_sent_at', 'certificate_sent_to']);
        });
    }
};

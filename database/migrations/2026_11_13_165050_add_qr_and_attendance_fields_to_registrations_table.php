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
            $table->unsignedTinyInteger('pass_qr_version')->default(0);
            $table->timestamp('checked_in_at')->nullable()->index();
            $table->string('checked_in_by', 120)->nullable();
            $table->uuid('checked_in_pass_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['checked_in_at']);
            $table->dropColumn(['pass_qr_version', 'checked_in_at', 'checked_in_by', 'checked_in_pass_id']);
        });
    }
};

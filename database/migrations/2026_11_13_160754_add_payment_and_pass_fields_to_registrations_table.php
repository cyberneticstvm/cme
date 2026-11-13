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
            $table->string('payment_status', 24)->default('pending')->index();
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->text('payment_notes')->nullable();
            $table->timestamp('payment_confirmed_at')->nullable();
            $table->string('payment_confirmed_by', 120)->nullable();
            $table->timestamp('payment_updated_at')->nullable();
            $table->string('payment_updated_by', 120)->nullable();
            $table->uuid('pass_id')->nullable()->unique();
            $table->timestamp('pass_issued_at')->nullable();
            $table->timestamp('pass_sent_at')->nullable();
            $table->string('pass_sent_to', 254)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['pass_id']);
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['payment_status', 'payment_amount', 'payment_reference', 'payment_notes', 'payment_confirmed_at', 'payment_confirmed_by', 'payment_updated_at', 'payment_updated_by', 'pass_id', 'pass_issued_at', 'pass_sent_at', 'pass_sent_to']);
        });
    }
};

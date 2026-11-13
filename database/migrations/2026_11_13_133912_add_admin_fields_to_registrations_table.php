<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->string('status', 24)->default('new');
            $table->text('admin_notes')->nullable();
            $table->index(['profession', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropIndex(['profession', 'created_at']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['status', 'admin_notes']);
        });
    }
};

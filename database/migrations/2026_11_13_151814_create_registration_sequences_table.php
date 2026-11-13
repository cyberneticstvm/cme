<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_sequences', function (Blueprint $table): void {
            $table->string('name')->primary();
            $table->unsignedInteger('last_number');
        });

        $lastNumber = DB::table('registrations')->whereRaw('LENGTH(reference) = 5')
            ->whereBetween('reference', ['10000', '99999'])->max('reference');

        DB::table('registration_sequences')->insert([
            'name' => 'visionx-2026',
            'last_number' => max(9999, (int) $lastNumber),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_sequences');
    }
};

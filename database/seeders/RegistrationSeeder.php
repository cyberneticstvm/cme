<?php

namespace Database\Seeders;

use App\Models\Registration;
use App\Profession;
use Illuminate\Database\Seeder;

class RegistrationSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        foreach (Profession::cases() as $profession) {
            Registration::factory()->create(['profession' => $profession]);
        }
    }
}

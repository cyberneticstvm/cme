<?php

namespace Database\Factories;

use App\Models\Registration;
use App\Profession;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Registration> */
class RegistrationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'profession' => fake()->randomElement(Profession::cases()),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+919497005700',
            'organisation' => fake()->company(),
            'city' => 'Kollam',
            'state' => 'Kerala',
            'message' => null,
            'consented_at' => now(),
        ];
    }
}

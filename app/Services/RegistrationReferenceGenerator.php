<?php

namespace App\Services;

use App\Exceptions\RegistrationCapacityReached;
use Illuminate\Support\Facades\DB;

class RegistrationReferenceGenerator
{
    public function next(): string
    {
        return DB::transaction(function (): string {
            $sequence = DB::table('registration_sequences')->where('name', 'visionx-2026');
            $updated = (clone $sequence)->where('last_number', '<', 99999)->increment('last_number');

            if ($updated !== 1) {
                throw new RegistrationCapacityReached;
            }

            return (string) $sequence->value('last_number');
        }, 5);
    }
}

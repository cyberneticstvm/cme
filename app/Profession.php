<?php

namespace App;

enum Profession: string
{
    case Doctor = 'doctor';
    case Optometrist = 'optometrist';
    case Optician = 'optician';

    public function label(): string
    {
        return match ($this) {
            self::Doctor => 'Doctor',
            self::Optometrist => 'Optometrist',
            self::Optician => 'Optician',
        };
    }
}

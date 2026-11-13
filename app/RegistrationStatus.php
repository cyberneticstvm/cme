<?php

namespace App;

enum RegistrationStatus: string
{
    case New = 'new';
    case Reviewed = 'reviewed';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

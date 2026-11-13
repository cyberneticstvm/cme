<?php

namespace App;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

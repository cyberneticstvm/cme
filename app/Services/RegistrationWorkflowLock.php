<?php

namespace App\Services;

use App\Models\Registration;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class RegistrationWorkflowLock
{
    public function run(Registration $registration, Closure $operation): mixed
    {
        try {
            return Cache::lock('registration-workflow:'.$registration->id, 180)->block(5, function () use ($registration, $operation): mixed {
                return $operation(Registration::findOrFail($registration->id));
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['registration' => 'This registration is being updated. Please wait a moment and try again.']);
        }
    }
}

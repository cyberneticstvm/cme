<?php

namespace App\Services;

use App\Mail\RegistrationAcknowledgement;
use App\Mail\RegistrationNotification;
use App\Models\Registration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RegistrationMailer
{
    public function sendPending(Registration $registration): void
    {
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            Log::warning('Registration emails pending: configure an outgoing mail transport.', [
                'registration_id' => $registration->id,
            ]);

            return;
        }

        try {
            Cache::lock('registration-mail:'.$registration->id, 120)->get(function () use ($registration): void {
                $registration->refresh();

                if ($registration->acknowledgement_sent_at === null) {
                    try {
                        Mail::to($registration->email, $registration->full_name)
                            ->send(new RegistrationAcknowledgement($registration));
                        $registration->acknowledgement_sent_at = now();
                        $registration->save();
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                }

                $receiver = trim((string) config('services.registration.receiver_email'));

                if ($registration->notification_sent_at === null && filter_var($receiver, FILTER_VALIDATE_EMAIL)) {
                    try {
                        Mail::to($receiver, config('services.registration.receiver_name'))
                            ->send(new RegistrationNotification($registration));
                        $registration->notification_sent_at = now();
                        $registration->save();
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                } elseif ($registration->notification_sent_at === null) {
                    Log::warning('Registration notification pending: configure REGISTRATION_RECEIVER_EMAIL.', [
                        'registration_id' => $registration->id,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}

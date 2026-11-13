<?php

namespace App\Console\Commands;

use App\Models\Registration;
use App\Services\RegistrationMailer;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class RetryRegistrationEmails extends Command
{
    protected $signature = 'registrations:retry-emails';

    protected $description = 'Retry pending registration emails without resending completed messages';

    public function handle(RegistrationMailer $mailer): int
    {
        $pending = 0;
        Registration::query()->where(function (Builder $query): void {
            $query->whereNull('acknowledgement_sent_at')->orWhereNull('notification_sent_at');
        })->eachById(function (Registration $registration) use ($mailer, &$pending): void {
            $mailer->sendPending($registration);
            if ($registration->acknowledgement_sent_at === null || $registration->notification_sent_at === null) {
                $pending++;
            }
        });

        if ($pending > 0) {
            $this->warn($pending.' registration(s) still have pending emails. Check receiver and mail configuration, and the application log.');

            return self::FAILURE;
        }

        $this->info('All registration emails have been handed to the configured mail transport.');

        return self::SUCCESS;
    }
}

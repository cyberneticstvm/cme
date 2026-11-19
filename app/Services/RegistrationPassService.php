<?php

namespace App\Services;

use App\Mail\DelegatePass;
use App\Models\Registration;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RegistrationPassService
{
    public function __construct(private RegistrationWorkflowLock $lock, private DelegatePassPdf $pdf) {}

    public function generateAndSend(Registration $registration): bool
    {
        return $this->lock->run($registration, function (Registration $current): bool {
            if (! $current->canIssuePass()) {
                throw ValidationException::withMessages(['registration' => 'Cancelled registrations cannot receive delegate passes.']);
            }

            $disk = Storage::disk('passes');
            $previousPath = $current->passPath();
            $needsNewPass = ! $previousPath || $current->pass_qr_version !== PassQrCode::VERSION
                || ! $current->pass_issued_at || ! $disk->exists($previousPath);
            if ($needsNewPass) {
                $current->pass_id = (string) Str::uuid();
                $current->pass_issued_at = now();
                $current->pass_qr_version = PassQrCode::VERSION;
                $current->pass_sent_at = null;
                $current->pass_sent_to = null;
            }

            $bytes = $this->pdf->render($current);
            if (! $disk->put($current->passPath(), $bytes)) {
                throw new RuntimeException('Unable to store the delegate pass.');
            }
            if ($needsNewPass) {
                try {
                    $current->save();
                } catch (\Throwable $exception) {
                    $disk->delete($current->passPath());
                    throw $exception;
                }
                if ($previousPath) {
                    $disk->delete($previousPath);
                }
            }
            if (in_array(config('mail.default'), ['log', 'array'], true)) {
                return false;
            }
            Mail::to($current->email, $current->full_name)->send(new DelegatePass($current));
            $current->pass_sent_at = now();
            $current->pass_sent_to = $current->email;
            $current->save();

            return true;
        });
    }

    public function revoke(Registration $registration): void
    {
        if ($registration->passPath()) {
            Storage::disk('passes')->delete($registration->passPath());
        }
        $registration->pass_qr_version = 0;
        $registration->pass_id = null;
        $registration->pass_issued_at = null;
        $registration->pass_sent_at = null;
        $registration->pass_sent_to = null;
    }
}

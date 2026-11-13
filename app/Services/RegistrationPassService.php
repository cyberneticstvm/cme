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

    public function assertEligible(Registration $registration): void
    {
        if (! $registration->canIssuePass()) {
            throw ValidationException::withMessages(['payment_status' => 'Confirm receipt of payment before generating, downloading or emailing a pass. Cancelled registrations cannot receive passes.']);
        }
    }

    public function generateAndSend(Registration $registration): bool
    {
        return $this->lock->run($registration, function (Registration $current): bool {
            $this->assertEligible($current);
            if (! $current->pass_id || $current->pass_qr_version !== PassQrCode::VERSION || ! Storage::disk('passes')->exists($current->passPath())) {
                $this->revoke($current);
                $current->pass_id = (string) Str::uuid();
                $current->pass_issued_at = now();
                $current->pass_qr_version = PassQrCode::VERSION;
                $bytes = $this->pdf->render($current);
                if (! Storage::disk('passes')->put($current->passPath(), $bytes)) {
                    throw new RuntimeException('Unable to store the delegate pass.');
                }
                try {
                    $current->save();
                } catch (\Throwable $exception) {
                    Storage::disk('passes')->delete($current->passPath());
                    throw $exception;
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

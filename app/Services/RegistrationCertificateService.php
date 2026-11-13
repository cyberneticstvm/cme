<?php

namespace App\Services;

use App\Mail\ParticipationCertificate;
use App\Models\Registration;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class RegistrationCertificateService
{
    public function __construct(private RegistrationWorkflowLock $lock, private ParticipationCertificatePdf $pdf) {}

    public function assertEligible(Registration $registration): void
    {
        if (! $registration->canIssueCertificate()) {
            throw ValidationException::withMessages(['certificate' => 'Certificates can be issued only to checked-in delegates with an active registration.']);
        }
    }

    public function generateAndSend(Registration $registration, string $issuer): bool
    {
        return $this->lock->run($registration, function (Registration $current) use ($issuer): bool {
            $this->assertEligible($current);
            if (! $current->certificate_id || ! Storage::disk('certificates')->exists($current->certificatePath())) {
                $this->revoke($current);
                $current->certificate_id = (string) Str::uuid();
                $current->certificate_number = 'VX26-PC-'.$current->reference;
                $current->certificate_issued_at = now();
                $current->certificate_issued_by = mb_substr($issuer, 0, 120);
                $bytes = $this->pdf->render($current);
                if (! Storage::disk('certificates')->put($current->certificatePath(), $bytes)) {
                    throw new RuntimeException('Unable to store the participation certificate.');
                }
                try {
                    $current->save();
                } catch (Throwable $exception) {
                    Storage::disk('certificates')->delete($current->certificatePath());
                    throw $exception;
                }
            }
            if (in_array(config('mail.default'), ['log', 'array'], true)) {
                return false;
            }
            Mail::to($current->email, $current->full_name)->send(new ParticipationCertificate($current));
            $current->certificate_sent_at = now();
            $current->certificate_sent_to = $current->email;
            $current->save();

            return true;
        });
    }

    public function revoke(Registration $registration): void
    {
        if ($registration->certificatePath()) {
            Storage::disk('certificates')->delete($registration->certificatePath());
        }
        $registration->certificate_id = null;
        $registration->certificate_number = null;
        $registration->certificate_issued_at = null;
        $registration->certificate_issued_by = null;
        $registration->certificate_sent_at = null;
        $registration->certificate_sent_to = null;
    }
}

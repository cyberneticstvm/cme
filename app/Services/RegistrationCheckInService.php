<?php

namespace App\Services;

use App\Models\Registration;
use App\PaymentStatus;
use App\RegistrationStatus;

class RegistrationCheckInService
{
    public function __construct(private PassQrCode $qr, private RegistrationWorkflowLock $lock) {}

    /** @return array<string, mixed> */
    public function verify(string $code): array
    {
        $token = $this->qr->token($code);
        $registration = $token ? Registration::where('pass_id', $token)->first() : null;

        return $this->result($registration, $token);
    }

    /** @return array<string, mixed> */
    public function checkIn(string $code, string $operator): array
    {
        $token = $this->qr->token($code);
        $registration = $token ? Registration::where('pass_id', $token)->first() : null;
        if (! $registration) {
            return $this->result(null, $token);
        }

        return $this->lock->run($registration, function (Registration $current) use ($token, $operator): array {
            $result = $this->result($current, $token);
            if (! $result['can_check_in']) {
                return $result;
            }
            $updated = Registration::whereKey($current->id)
                ->where('pass_id', $token)->where('pass_qr_version', PassQrCode::VERSION)->whereNotNull('pass_issued_at')
                ->where('payment_status', PaymentStatus::Confirmed)->whereNotNull('payment_confirmed_at')
                ->where('status', '!=', RegistrationStatus::Cancelled)->whereNull('checked_in_at')
                ->update(['checked_in_at' => now(), 'checked_in_by' => mb_substr($operator, 0, 120), 'checked_in_pass_id' => $token]);
            $current->refresh();
            if ($updated !== 1) {
                return $this->result($current, $token);
            }

            return array_replace($this->result($current, $token), [
                'state' => 'checked_in', 'heading' => 'Check-in confirmed',
                'message' => 'Attendance has been recorded. Welcome this delegate to VISION X 2026.',
            ]);
        });
    }

    /** @return array<string, mixed> */
    private function result(?Registration $registration, ?string $token): array
    {
        $state = 'invalid';
        $heading = 'Pass not recognised';
        $message = 'This pass is unknown, replaced or revoked. Please check the delegate record before admitting anyone.';
        if ($registration && $registration->pass_id === $token) {
            if ($registration->status === RegistrationStatus::Cancelled) {
                [$state, $heading, $message] = ['cancelled', 'Registration cancelled', 'This registration is cancelled. Entry is not permitted.'];
            } elseif (! $registration->canIssuePass()) {
                [$state, $heading, $message] = ['unpaid', 'Payment is not confirmed', 'Entry is blocked until the organising team confirms payment.'];
            } elseif ($registration->pass_qr_version !== PassQrCode::VERSION || ! $registration->pass_issued_at) {
                [$state, $heading, $message] = ['invalid', 'Pass needs to be replaced', 'Generate and email a current QR pass from the delegate payment page.'];
            } elseif ($registration->checked_in_at) {
                [$state, $heading, $message] = ['already_checked_in', 'Already checked in', 'Attendance was recorded earlier. Do not admit this pass again.'];
            } else {
                [$state, $heading, $message] = ['ready', 'Ready for check-in', 'Registration and payment are valid. Match the delegate details, then confirm attendance.'];
            }
        } else {
            $registration = null;
        }

        return [
            'state' => $state, 'heading' => $heading, 'message' => $message, 'can_check_in' => $state === 'ready',
            'registration' => $registration ? [
                'reference' => $registration->reference, 'full_name' => $registration->full_name,
                'profession' => $registration->profession->label(), 'email' => $registration->email,
                'organisation' => $registration->organisation, 'status' => $registration->status->label(),
                'payment_status' => $registration->payment_status->label(),
                'checked_in_at' => $registration->checked_in_at?->toIso8601String(),
                'checked_in_display' => $registration->checked_in_at ? $registration->checked_in_at->timezone('Asia/Kolkata')->format('d M Y, h:i A').' IST' : null,
                'checked_in_by' => $registration->checked_in_by,
                'manage_url' => route('admin.registrations.payment', $registration),
            ] : null,
        ];
    }
}
